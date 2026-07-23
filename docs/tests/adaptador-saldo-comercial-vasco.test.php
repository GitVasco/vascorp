<?php
/**
 * Pruebas unitarias del AdaptadorSaldoComercialVasco (sin PHPUnit / sin BD).
 *
 * Uso: php docs/tests/adaptador-saldo-comercial-vasco.test.php
 */

require_once __DIR__ . "/../../controladores/adaptador-saldo-comercial-vasco.php";

$fallos = 0;
$ok = 0;

function assert_true($cond, $msg)
{
    global $fallos, $ok;
    if ($cond) {
        $ok++;
        echo "OK  $msg\n";
    } else {
        $fallos++;
        echo "FAIL  $msg\n";
    }
}

function assert_eq($esperado, $actual, $msg)
{
    $pass = $esperado === $actual
        || (is_numeric($esperado) && is_numeric($actual) && abs((float) $esperado - (float) $actual) < 0.001);
    assert_true($pass, $msg . " (esperado=" . var_export($esperado, true) . ", actual=" . var_export($actual, true) . ")");
}

$hoy = "2026-07-22";

// 1) Sin regularizaciones: entrada = salida
$doc = array(
    "tipo_doc" => "01",
    "num_cta" => "F001-100",
    "monto" => 1000.00,
    "saldo" => 1000.00,
    "fecha" => "2026-01-10",
    "fecha_ven" => "2026-02-10",
);
$proy = AdaptadorSaldoComercialVasco::proyectarCuenta(
    array("doc_key" => "RUC:201", "deuda_total" => 1000, "vencido_total" => 1000, "cant_docs" => 1),
    array($doc),
    array(),
    $hoy
);
assert_true($proy["sin_cambios"] || count($proy["documentos"]) === 1, "sin mapa: conserva documento");
assert_eq(1000.0, (float) $proy["resumen"]["deuda_total"], "sin mapa: deuda igual");
assert_eq(1, count($proy["documentos"]), "sin mapa: 1 doc");
assert_eq(1000.0, (float) $proy["documentos"][0]["saldo"], "sin mapa: saldo igual");
assert_true(empty($proy["omitir"]), "sin mapa: no omitir");

// 2) Regularización parcial
$proy2 = AdaptadorSaldoComercialVasco::proyectarCuenta(
    array("doc_key" => "RUC:201", "deuda_total" => 1000, "vencido_total" => 1000),
    array($doc),
    array("01|F001-100" => 300.00),
    $hoy
);
assert_eq(700.0, (float) $proy2["resumen"]["deuda_total"], "parcial: deuda comercial 700");
assert_eq(700.0, (float) $proy2["documentos"][0]["saldo"], "parcial: saldo comercial 700");
assert_eq(1000.0, (float) $proy2["documentos"][0]["_saldo_oficial"], "parcial: conserva saldo oficial");
assert_eq(700.0, (float) $proy2["resumen"]["vencido_total"], "parcial: vencido recalculado");

// 3) Regularización total → excluir documento
$proy3 = AdaptadorSaldoComercialVasco::proyectarCuenta(
    array("doc_key" => "RUC:201", "deuda_total" => 1000, "vencido_total" => 1000),
    array($doc),
    array("01|F001-100" => 1000.00),
    $hoy
);
assert_eq(0, count($proy3["documentos"]), "total: documento excluido");
assert_eq(0.0, (float) $proy3["resumen"]["deuda_total"], "total: deuda 0");
assert_true(!empty($proy3["omitir"]), "total: omitir cuenta del lote");

// 4) No aplicar más que el saldo oficial
$saldo = AdaptadorSaldoComercialVasco::saldoComercial(100.00, 500.00);
assert_eq(0.0, $saldo, "tope: aplicable no supera oficial");

// 5) Varios documentos: solo uno regularizado; no vencido no suma vencido
$docs = array(
    array(
        "tipo_doc" => "01",
        "num_cta" => "A-1",
        "monto" => 200,
        "saldo" => 200,
        "fecha" => "2026-06-01",
        "fecha_ven" => "2026-08-01", // futuro
    ),
    array(
        "tipo_doc" => "01",
        "num_cta" => "A-2",
        "monto" => 500,
        "saldo" => 500,
        "fecha" => "2026-01-01",
        "fecha_ven" => "2026-02-01", // vencido
    ),
);
$proy5 = AdaptadorSaldoComercialVasco::proyectarCuenta(
    array("doc_key" => "DNI:1", "deuda_total" => 700, "vencido_total" => 500),
    $docs,
    array("01|A-2" => 200.00),
    $hoy
);
assert_eq(2, count($proy5["documentos"]), "multi: ambos docs (parcial en A-2)");
assert_eq(500.0, (float) $proy5["resumen"]["deuda_total"], "multi: deuda 200+300=500");
assert_eq(300.0, (float) $proy5["resumen"]["vencido_total"], "multi: solo A-2 vencido comercial");

// 6) limpiarParaApi no expone internos
$limpio = AdaptadorSaldoComercialVasco::limpiarDocumentoParaApi($proy2["documentos"][0]);
assert_true(!isset($limpio["_saldo_oficial"]), "api: sin _saldo_oficial");
assert_true(!isset($limpio["_monto_aplicable"]), "api: sin _monto_aplicable");
assert_eq(700.0, (float) $limpio["saldo"], "api: saldo comercial intacto");

// 7) Identidad proyectarDocumento sin aplicable
$p0 = AdaptadorSaldoComercialVasco::proyectarDocumento($doc, 0);
assert_true($p0 !== null, "cero aplicable: no excluye");
assert_eq(1000.0, (float) $p0["saldo"], "cero aplicable: saldo igual");

echo "\nResultado: $ok ok, $fallos fallos\n";
exit($fallos > 0 ? 1 : 0);
