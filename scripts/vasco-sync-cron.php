<?php

/**
 * Sincronización programada vascorp → Vasco Online (clientes, grupos, cuentas).
 * Equivale a lo que hace la pantalla "Vasco Online" a mano, pero sin navegador.
 *
 * Uso (CLI):
 *   php scripts/vasco-sync-cron.php [fase] [--finalize] [--max-lotes=N] [--reintentos=N]
 *
 * Fases:
 *   clientes   maestro de clientes
 *   grupos     grupos empresariales + asignaciones (miembros)
 *   cuentas    estados de cuenta
 *   todo       clientes → grupos → cuentas (orden obligatorio). Es el valor por defecto.
 *
 * --finalize   Al terminar cuentas envía el cierre de corrida (finalize:true), que PURGA en
 *              Vasco los clientes que ya no deben. Solo se envía si TODA la corrida de cuentas
 *              fue limpia (sin lotes fallidos ni parciales y con al menos una cuenta guardada).
 *              Recomendado: una vez al día, no en cada corrida horaria.
 *
 * Códigos de salida: 0 OK · 1 error · 2 terminó con advertencias (filas rechazadas) · 3 ya hay otra corrida
 *
 * Log: scripts/logs/vasco-sync-AAAAMMDD.log (carpeta ignorada por git)
 */

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Solo CLI\n");
}

date_default_timezone_set("America/Lima");
set_time_limit(0);
ini_set("memory_limit", "512M");

$raiz = dirname(__DIR__);

require_once $raiz . "/controladores/config.php";
require_once $raiz . "/controladores/vasco-online.config.php";
require_once $raiz . "/modelos/conexion.php";
require_once $raiz . "/modelos/vasco-sync.modelo.php";
require_once $raiz . "/controladores/vasco-sync.controlador.php";

// ---------------------------------------------------------------- argumentos
$fase = "todo";
$finalizeCuentas = false;
$maxLotes = 2000;      // tope de seguridad por fase
$reintentos = 3;       // intentos por lote ante error de red / HTTP

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === "--finalize") {
        $finalizeCuentas = true;
    } elseif (strpos($arg, "--max-lotes=") === 0) {
        $maxLotes = max(1, (int) substr($arg, 12));
    } elseif (strpos($arg, "--reintentos=") === 0) {
        $reintentos = max(1, (int) substr($arg, 13));
    } elseif (in_array($arg, array("clientes", "grupos", "cuentas", "todo"), true)) {
        $fase = $arg;
    } else {
        fwrite(STDERR, "Argumento no reconocido: $arg\n");
        exit(1);
    }
}

// ---------------------------------------------------------------- log
$dirLogs = __DIR__ . "/logs";
if (!is_dir($dirLogs)) {
    @mkdir($dirLogs, 0775, true);
}
$archivoLog = $dirLogs . "/vasco-sync-" . date("Ymd") . ".log";

function logLinea($texto)
{
    global $archivoLog;
    $linea = "[" . date("Y-m-d H:i:s") . "] " . $texto . "\n";
    echo $linea;
    @file_put_contents($archivoLog, $linea, FILE_APPEND);
}

// ---------------------------------------------------------------- lock (una sola corrida a la vez)
$lock = fopen(__DIR__ . "/logs/vasco-sync.lock", "c");
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    logLinea("Ya hay una sincronización en curso. Se omite esta corrida.");
    exit(3);
}

// ---------------------------------------------------------------- helpers
$hayErrores = false;
$hayAdvertencias = false;

/**
 * Recorre lotes 1..N de una fase hasta que el API/BD devuelva lote vacío.
 *
 * @param string   $etiqueta
 * @param callable $enviar  fn(int $lote, string $traceId): array
 * @param string   $traceId Se actualiza con el que devuelve el API
 * @return array {errores, parciales, lotes, guardados, enviados}
 */
function correrFase($etiqueta, callable $enviar, &$traceId)
{
    global $maxLotes, $reintentos;

    $r = array("errores" => 0, "parciales" => 0, "lotes" => 0, "guardados" => 0, "enviados" => 0);

    for ($lote = 1; $lote <= $maxLotes; $lote++) {
        $resp = null;

        for ($intento = 1; $intento <= $reintentos; $intento++) {
            $resp = $enviar($lote, $traceId);
            if (is_array($resp) && !empty($resp["ok"])) {
                break;
            }
            logLinea("$etiqueta lote $lote intento $intento/$reintentos falló: " . (isset($resp["msg"]) ? $resp["msg"] : "sin respuesta"));
            if ($intento < $reintentos) {
                sleep(5 * $intento);
            }
        }

        if (!is_array($resp) || empty($resp["ok"])) {
            $r["errores"]++;
            // Si falla un lote no seguimos: los siguientes dependen del mismo offset.
            logLinea("$etiqueta ABORTADA en lote $lote.");
            break;
        }

        if (!empty($resp["trace_id"])) {
            $traceId = $resp["trace_id"];
        }

        if (!empty($resp["skipped"])) {
            break; // ya no hay más registros
        }

        $r["lotes"]++;
        $r["enviados"] += (int) (isset($resp["customers_sent"]) ? $resp["customers_sent"]
            : (isset($resp["accounts_sent"]) ? $resp["accounts_sent"] : 0));
        $r["guardados"] += (int) (isset($resp["processed"]) ? $resp["processed"] : 0);

        logLinea("$etiqueta · " . $resp["msg"]);

        if (!empty($resp["partial"])) {
            $r["parciales"]++;
            $failed = isset($resp["failed"]) && is_array($resp["failed"]) ? $resp["failed"] : array();
            foreach (array_slice($failed, 0, 20) as $f) {
                logLinea("   ↳ rechazado: " . json_encode($f, JSON_UNESCAPED_UNICODE));
            }
            if (count($failed) > 20) {
                logLinea("   ↳ … y " . (count($failed) - 20) . " más");
            }
        }
    }

    if ($lote > $maxLotes) {
        logLinea("$etiqueta cortada por tope --max-lotes=$maxLotes");
        $r["errores"]++;
    }

    return $r;
}

function acumular(array $r)
{
    global $hayErrores, $hayAdvertencias;
    if ($r["errores"] > 0) {
        $hayErrores = true;
    }
    if ($r["parciales"] > 0) {
        $hayAdvertencias = true;
    }
}

// ---------------------------------------------------------------- ejecución
$inicio = microtime(true);
logLinea("=== Inicio sync Vasco Online · fase=$fase · entorno=" . $GLOBALS["vasco_online_entorno"] . " · finalize=" . ($finalizeCuentas ? "sí" : "no") . " ===");

if (obtenerApiKeyVascoOnline() === "") {
    logLinea("ERROR: API key no configurada en config.php");
    exit(1);
}

$salud = ControladorVascoSync::ctrProbarConexion();
if (empty($salud["ok"])) {
    logLinea("ERROR: el API no responde: " . (isset($salud["msg"]) ? $salud["msg"] : "sin detalle"));
    exit(1);
}
logLinea("Conexión al API OK");

try {
    // 1) Clientes
    if ($fase === "clientes" || $fase === "todo") {
        $trace = ControladorVascoSync::generarTraceId();
        $r = correrFase("clientes", function ($lote, $t) {
            return ControladorVascoSync::ctrSincronizarLoteClientes($lote, $t);
        }, $trace);
        logLinea("Clientes: {$r['lotes']} lote(s), {$r['enviados']} enviados, {$r['parciales']} parcial(es), {$r['errores']} error(es)");
        acumular($r);
        if ($r["errores"] > 0 && $fase === "todo") {
            logLinea("Se detiene: sin clientes completos no se sincronizan grupos ni cuentas.");
            goto fin;
        }
    }

    // 2) Grupos y luego miembros (cada uno con su trace_id, igual que la pantalla)
    if ($fase === "grupos" || $fase === "todo") {
        $trace = ControladorVascoSync::generarTraceIdGrupos();
        $r = correrFase("grupos", function ($lote, $t) {
            return ControladorVascoSync::ctrSincronizarLoteGrupos($lote, $t);
        }, $trace);
        acumular($r);

        if ($r["errores"] === 0) {
            $trace = ControladorVascoSync::generarTraceIdMiembrosGrupos();
            $r2 = correrFase("miembros", function ($lote, $t) {
                return ControladorVascoSync::ctrSincronizarLoteMiembrosGrupos($lote, $t);
            }, $trace);
            acumular($r2);
            logLinea("Miembros: {$r2['lotes']} lote(s), {$r2['enviados']} enviados, {$r2['parciales']} parcial(es), {$r2['errores']} error(es)");
        } else {
            logLinea("Se omiten miembros porque grupos falló.");
        }
    }

    // 3) Cuentas (+ finalize opcional)
    if ($fase === "cuentas" || $fase === "todo") {
        $trace = ControladorVascoSync::generarTraceIdCuentas();
        $r = correrFase("cuentas", function ($lote, $t) {
            return ControladorVascoSync::ctrSincronizarLoteCuentas($lote, $t);
        }, $trace);
        logLinea("Cuentas: {$r['lotes']} lote(s), {$r['enviados']} enviadas, {$r['guardados']} guardadas, {$r['parciales']} parcial(es), {$r['errores']} error(es)");
        acumular($r);

        if ($finalizeCuentas) {
            $limpia = $r["errores"] === 0 && $r["parciales"] === 0 && $r["guardados"] > 0 && $r["lotes"] > 0;
            if (!$limpia) {
                logLinea("FINALIZE OMITIDO: la corrida de cuentas no fue 100% limpia (evita purgar datos válidos en Vasco).");
                $hayAdvertencias = true;
            } else {
                $fin = ControladorVascoSync::ctrFinalizarSyncCuentas($trace, $r["lotes"] + 1);
                logLinea("Finalize · " . (isset($fin["msg"]) ? $fin["msg"] : "sin respuesta"));
                if (empty($fin["ok"])) {
                    $hayErrores = true;
                }
            }
        }
    }
} catch (Exception $e) {
    logLinea("EXCEPCIÓN: " . $e->getMessage() . " en " . basename($e->getFile()) . ":" . $e->getLine());
    $hayErrores = true;
} catch (Throwable $e) { // PHP 7+ (en 5.6 esta clase no existe y este bloque simplemente no aplica)
    logLinea("EXCEPCIÓN: " . $e->getMessage() . " en " . basename($e->getFile()) . ":" . $e->getLine());
    $hayErrores = true;
}

fin:
$seg = round(microtime(true) - $inicio, 1);
$codigo = $hayErrores ? 1 : ($hayAdvertencias ? 2 : 0);
logLinea("=== Fin · {$seg}s · código de salida $codigo ===");

flock($lock, LOCK_UN);
exit($codigo);
