<?php

/**
 * Facturas de exportación (docs/efact/EXPORTACION-diseno.md).
 * Fase 1: configuración (constantes con valor por defecto), ficha de cliente y partidas por línea.
 * No modifica ni reemplaza el flujo actual de facturación (serie FX en ControladorFacturacion).
 */
class ControladorExportacion
{
    const RUC_EMISOR = "20513613939";

    /** Acceso: quien tiene el módulo de facturación. */
    public static function puedeUsar()
    {
        if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
            return false;
        }
        return isset($_SESSION["facturacion"]) && (string) $_SESSION["facturacion"] === "1";
    }

    /**
     * Configuración vigente. Se puede fijar en config.php con
     * EXPORTACION_SERIE, EXPORTACION_LISTA y EXPORTACION_ALMACEN.
     * Sin EXPORTACION_SERIE se usa la única serie FX de talonariosjf; si hay varias, queda vacía.
     */
    public static function config()
    {
        $series = ModeloExportacion::mdlSeriesFX();
        if (defined("EXPORTACION_SERIE")) {
            $serie = EXPORTACION_SERIE;
        } else {
            $serie = count($series) === 1 ? $series[0]["serie"] : "";
        }

        $avisos = array();
        if (!count($series)) {
            $avisos[] = "No hay series FX en talonariosjf.";
        } elseif ($serie === "") {
            $avisos[] = "Hay varias series FX: definir EXPORTACION_SERIE en config.php.";
        } else {
            $existe = false;
            foreach ($series as $s) {
                if ($s["serie"] === $serie) {
                    $existe = true;
                }
            }
            if (!$existe) {
                $avisos[] = "La serie " . $serie . " no existe en talonariosjf.";
            }
        }

        return array(
            "serie" => $serie,
            "series_fx" => $series,
            "lista" => defined("EXPORTACION_LISTA") ? EXPORTACION_LISTA : "precio1",
            "almacen" => defined("EXPORTACION_ALMACEN") ? EXPORTACION_ALMACEN : "01",
            "moneda" => "USD",
            "avisos" => $avisos,
        );
    }

    private static function t($v, $max = 0)
    {
        $v = trim(preg_replace('/[\r\n\t,]+/', ' ', (string) $v));
        $v = preg_replace('/\s{2,}/', ' ', $v);
        if ($max > 0 && function_exists("mb_substr")) {
            $v = mb_substr($v, 0, $max, "UTF-8");
        }
        return $v;
    }

    private static function usuario()
    {
        return isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";
    }

    /** Países para el selector de Clientes (ISO 3166-1 alfa-2 => nombre). Vacío = Perú. */
    public static function paises()
    {
        return array(
            "AR" => "ARGENTINA", "BO" => "BOLIVIA", "BR" => "BRASIL", "CA" => "CANADÁ", "CL" => "CHILE",
            "CN" => "CHINA", "CO" => "COLOMBIA", "CR" => "COSTA RICA", "CU" => "CUBA", "DE" => "ALEMANIA",
            "DO" => "REPÚBLICA DOMINICANA", "EC" => "ECUADOR", "ES" => "ESPAÑA", "FR" => "FRANCIA",
            "GB" => "REINO UNIDO", "GT" => "GUATEMALA", "HN" => "HONDURAS", "IT" => "ITALIA", "JP" => "JAPÓN",
            "MX" => "MÉXICO", "NI" => "NICARAGUA", "NL" => "PAÍSES BAJOS", "PA" => "PANAMÁ", "PT" => "PORTUGAL",
            "PY" => "PARAGUAY", "SV" => "EL SALVADOR", "US" => "ESTADOS UNIDOS", "UY" => "URUGUAY",
            "VE" => "VENEZUELA",
        );
    }

    public static function nombrePais($iso)
    {
        $p = self::paises();
        return isset($p[$iso]) ? $p[$iso] : (string) $iso;
    }

    /**
     * Guarda el país del cliente desde la pantalla Clientes, después de que su alta o edición salió bien.
     * Solo actúa si el formulario envió el campo y la columna existe; vacío o PE = Perú (NULL).
     */
    public static function ctrGuardarPaisCliente($codigo, $campoPost)
    {
        if (!isset($_POST[$campoPost]) || !ModeloExportacion::mdlTienePais()) {
            return false;
        }
        $iso = strtoupper(trim((string) $_POST[$campoPost]));
        if ($iso === "" || $iso === "PE") {
            $iso = null;
        } elseif (!array_key_exists($iso, self::paises())) {
            return false;
        }
        return ModeloExportacion::mdlGuardarPaisCliente(trim((string) $codigo), $iso);
    }

    /* =====================================================
       PEDIDOS: exportación = cliente con país distinto de Perú
    ===================================================== */

    public static function esExterior($iso)
    {
        $iso = strtoupper(trim((string) $iso));
        return $iso !== "" && $iso !== "PE";
    }

    /** Datos para el modal Facturar (filtrar series y avisos). */
    public static function ctrInfoPedido($codigoPedido)
    {
        $p = ModeloExportacion::mdlPedidoExportacion($codigoPedido);
        if (!$p) {
            return array("ok" => false, "exterior" => false);
        }
        $exterior = self::esExterior($p["pais_iso"]);
        return array(
            "ok" => true,
            "exterior" => $exterior,
            "pais_iso" => $exterior ? strtoupper($p["pais_iso"]) : "",
            "pais" => $exterior ? self::nombrePais(strtoupper($p["pais_iso"])) : "",
            "lista" => $p["lista"],
            "lista_ok" => !$exterior || $p["lista"] === "precio1",
        );
    }

    /** Datos para la pantalla crear pedido. */
    public static function ctrInfoCliente($codigo)
    {
        $c = ModeloExportacion::mdlClienteExportacion($codigo);
        if (!$c || !self::esExterior($c["pais_iso"])) {
            return array("ok" => true, "exterior" => false);
        }
        $iso = strtoupper($c["pais_iso"]);
        return array(
            "ok" => true,
            "exterior" => true,
            "pais_iso" => $iso,
            "pais" => self::nombrePais($iso),
            "lista" => $c["lista_precios"],
            "lista_ok" => $c["lista_precios"] === "precio1",
        );
    }

    /**
     * Control del servidor en ControladorFacturacion::ctrFacturarN antes de asignar correlativo.
     * Devuelve "" si se puede seguir; si no, el mensaje para el usuario.
     * Solo factura/boleta (01, 03); guía, nota de crédito y proforma no se tocan.
     */
    public static function ctrValidarFacturarPedido($codigoPedido, $tipoDocumento, $serie)
    {
        if (!in_array((string) $tipoDocumento, array("01", "03"), true)) {
            return "";
        }
        $p = ModeloExportacion::mdlPedidoExportacion($codigoPedido);
        if (!$p) {
            return "";
        }
        $esFx = strtoupper(substr(trim((string) $serie), 0, 2)) === "FX";
        $exterior = self::esExterior($p["pais_iso"]);

        if ($esFx && !$exterior) {
            return "La serie FX es solo para clientes del exterior. Si el cliente es del exterior, márquelo en Clientes (casilla «Cliente del exterior» y país).";
        }
        if ($exterior && !$esFx) {
            return "El cliente es del exterior (" . self::nombrePais(strtoupper($p["pais_iso"])) . "): debe emitirse con una serie FX.";
        }
        if ($esFx && (string) $tipoDocumento !== "01") {
            return "La exportación solo se emite como factura.";
        }
        if ($esFx && $p["lista"] !== "precio1") {
            return "Un pedido de exportación debe usar la lista de precios 1 (USD). Cambie la lista del pedido antes de facturar.";
        }
        return "";
    }

    /** Editar precio de lista: mismo permiso que Maestros → Modelos (ficha_modelos → precios). */
    public static function puedeEditarPrecios()
    {
        return function_exists("usuarioPuedeModulo")
            && usuarioPuedeModulo("gestion_comercial", "ficha_modelos", "precios");
    }

    public static function ctrGuardarPrecio1($in)
    {
        if (!self::puedeEditarPrecios()) {
            return array("ok" => false, "msg" => "Sin permiso para editar precios de lista.");
        }
        $modelo = self::t(isset($in["modelo"]) ? $in["modelo"] : "", 20);
        $txt = str_replace(",", ".", trim(isset($in["precio"]) ? (string) $in["precio"] : ""));
        if ($modelo === "" || !ModeloExportacion::mdlModeloActivo($modelo)) {
            return array("ok" => false, "msg" => "Modelo no válido o no activo.");
        }
        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $txt)) {
            return array("ok" => false, "msg" => "Precio no válido (máximo 2 decimales).");
        }
        $precio = number_format((float) $txt, 2, ".", "");
        if (!ModeloExportacion::mdlTieneFilaPrecio($modelo)) {
            return array("ok" => false, "msg" => "El modelo no tiene registro de precios; créelo en Maestros → Modelos.");
        }
        $anterior = ModeloExportacion::mdlPrecio1Actual($modelo);
        try {
            $ok = ModeloExportacion::mdlGuardarPrecio1($modelo, $precio);
        } catch (Exception $e) {
            $ok = false;
        }
        if (!$ok) {
            return array("ok" => false, "msg" => "No se pudo guardar el precio.");
        }
        $guardado = ModeloExportacion::mdlPrecio1Actual($modelo);
        if ($guardado === null || abs($guardado - (float) $precio) > 0.004) {
            if ($anterior !== null) {
                ModeloExportacion::mdlGuardarPrecio1($modelo, number_format($anterior, 4, ".", ""));
            }
            return array("ok" => false, "msg" => "La base no aceptó el precio " . $precio . " (quedó " . ($guardado === null ? "vacío" : $guardado) . "); se restauró el anterior. Revisar el tipo de la columna preciojf.precio1.");
        }
        return array("ok" => true, "precio" => $precio);
    }

    /* =====================================================
       FASE 2 — CSV DE EXPORTACIÓN (solo vista previa, no envía)
       "actual" reproduce en memoria lo que genera hoy ControladorFacturacion::ctrGenerarFEFacBolB
       (esa función mueve el archivo a la carpeta de eFact, por eso no se llama).
       "nuevo" es el mismo archivo con los cambios de docs/efact/EXPORTACION-diseno.md §4 Fase 2.
    ===================================================== */

    private static function v($d, $k)
    {
        return isset($d[$k]) && $d[$k] !== null ? (string) $d[$k] : "";
    }

    private static function comas($ini, $fin)
    {
        return ControladorFacturacion::calcularComas($ini, $fin);
    }

    /** Índice 0-based → letra de columna (0 = A, 26 = AA). */
    private static function letraColumna($i)
    {
        $s = "";
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = (int) (($i - $m) / 26);
        }
        return $s;
    }

    /** Monto en letras en dólares (convertir() devuelve "... Y 00/100 SOLES"). */
    public static function montoLetrasUSD($monto)
    {
        require_once __DIR__ . "/../extensiones/cantidad_en_letras_v2.php";
        $txt = trim(convertir($monto));
        return preg_replace('/\bSOLES$/', 'DOLARES AMERICANOS', $txt);
    }

    /**
     * Arma el CSV de exportación en memoria.
     * @param string $modo "actual" | "nuevo"
     * @return array{ok:bool,msg?:string,nombre?:string,contenido?:string,avisos?:array}
     */
    public static function ctrArmarCsv($documento, $modo)
    {
        $documento = strtoupper(trim((string) $documento));
        if (strpos($documento, "FX") !== 0) {
            return array("ok" => false, "msg" => "Solo facturas de exportación (serie FX).");
        }
        $d = ModeloFacturacion::mdlFEFacturaCabB($documento);
        if (!$d || self::v($d, "b1") === "") {
            return array("ok" => false, "msg" => "No se encontró la factura " . $documento . ".");
        }
        $det = ModeloFacturacion::mdlFEFacturaDetAExportacion($documento);
        if (!count($det)) {
            return array("ok" => false, "msg" => "La factura no tiene líneas en movimientos del año en curso.");
        }

        $nuevo = $modo === "nuevo";
        $avisos = array();

        if ($nuevo) {
            // Emisor: la consulta actual repite el alias h5 y deja la provincia del emisor en "-" y h6 vacío.
            $d["h5"] = "LIMA";
            $d["h6"] = "-";

            $pais = ModeloExportacion::mdlPaisCliente(self::v($d, "d8"));
            if ($pais === "" || $pais === "PE") {
                $avisos[] = "El cliente " . self::v($d, "d8") . " no tiene país del exterior en Clientes; se dejó el país del archivo actual.";
            } else {
                $d["k6"] = $pais;
            }

            if (self::v($d, "b6") !== "0") {
                $d["a6"] = "00000000";
            }
            $d["b6"] = "0";
        }

        $fila1 = self::v($d, "a1") . ',' . self::v($d, "b1") . ',' . self::v($d, "c1") . ',' . self::v($d, "d1") . ',' .
            self::v($d, "e1") . ',' . self::v($d, "f1") . ',' . self::v($d, "g1") . self::comas('g', 'n') .
            self::v($d, "n1") . ',' . self::v($d, "o1") . self::comas('o', 'q') .
            self::v($d, "q1") . self::comas('q', 'u') . self::v($d, "u1") . self::comas('u', 'z') .
            self::v($d, "z1") . self::comas('z', 'ap') . self::v($d, "ap1") . self::comas('ap', 'as') .
            self::v($d, "as1") . self::comas('as', 'bh') .
            self::v($d, "bh1") . ',' . self::v($d, "bi1") . ',' . self::v($d, "bj1") . self::comas('bj', 'br');

        $fila2 = str_repeat(',', 61);
        $fila3 = str_repeat(',', 61);

        $fila5 = "";
        foreach (array("a5", "b5", "c5", "d5", "e5", "f5", "g5", "h5", "i5", "j5", "k5", "l5", "m5") as $k) {
            $fila5 .= self::v($d, $k) . ',';
        }
        $fila6 = "";
        foreach (array("a6", "b6", "c6", "d6", "e6", "f6", "g6", "h6", "i6", "j6", "k6", "l6") as $k) {
            $fila6 .= self::v($d, $k) . ',';
        }

        if ($nuevo) {
            $letras = self::montoLetrasUSD(self::v($d, "n1"));
        } else {
            require_once __DIR__ . "/../extensiones/cantidad_en_letras_v2.php";
            $letras = str_replace("SOLES", "DOLARES AMERICANOS", convertir(self::v($d, "n1")));
        }
        $fila7 = $letras . str_repeat(',', 41);

        $fila8 = self::v($d, "a8") . ',,' . self::v($d, "g3") . ',' . self::v($d, "d8") . ',' .
            self::v($d, "e8") . ',' . self::v($d, "f8") . ',' . self::v($d, "g8") . ',,,,,,';

        $lineas = array($fila1, $fila2, $fila3, $fila5, $fila6, $fila7, $fila8);
        $mapaCodigos = $nuevo ? ModeloExportacion::mdlMapaPartidas() : array();
        $sinCodigo = array();

        foreach ($det as $i => $r) {
            if ($nuevo) {
                $linea = self::v($r, "linea");
                $codigo = isset($mapaCodigos[$linea]) ? $mapaCodigos[$linea] : "";
                if ($codigo === "") {
                    $sinCodigo[$linea] = true;
                }
            } else {
                // Hoy $codigo_arancel no está definido dentro de ctrGenerarFEFacBolB (config.php ya se cargó desde el modelo).
                $codigo = "No encontrado";
            }
            $lineas[] = ($i + 1) . ',' . self::v($r, "b9") . ',' . self::v($r, "c9") . ',' . self::v($r, "d9") . ',' .
                self::v($r, "e9") . ',' . self::v($r, "f9") . self::comas('f', 'i') .
                self::v($r, "i9") . ',' . self::v($r, "j9") . ',' . self::v($r, "k9") . ',' . self::v($r, "l9") . ',' .
                self::v($r, "m9") . self::comas('m', 'r') . $codigo . ',' . self::v($r, "s9") . ',' . self::v($r, "t9") . ',' .
                self::v($r, "u9") . self::comas('u', 'w') . self::v($r, "w9") . ',' . self::v($r, "x9") . self::comas('x', 'ak') .
                self::v($r, "ak9") . ',' . self::v($r, "al9") . self::comas('al', 'ap') . self::v($r, "ap9") . self::comas('ap', 'ar');
        }
        $lineas[] = "FF00FF";

        if (count($sinCodigo)) {
            $avisos[] = "Líneas sin código de producto SUNAT (va vacío): " . implode(", ", array_keys($sinCodigo)) . ".";
        }

        return array(
            "ok" => true,
            "nombre" => self::RUC_EMISOR . "-" . self::v($d, "c1") . "-" . self::v($d, "b1") . ".csv",
            "contenido" => implode(PHP_EOL, $lineas),
            "avisos" => $avisos,
        );
    }

    /** Compara el CSV actual y el nuevo celda por celda. */
    public static function ctrCompararCsv($documento)
    {
        $a = self::ctrArmarCsv($documento, "actual");
        if (empty($a["ok"])) {
            return $a;
        }
        $n = self::ctrArmarCsv($documento, "nuevo");
        if (empty($n["ok"])) {
            return $n;
        }
        $la = explode(PHP_EOL, $a["contenido"]);
        $ln = explode(PHP_EOL, $n["contenido"]);
        $filasLegacy = array(1, 2, 3, 5, 6, 7, 8);
        $diffs = array();
        $max = max(count($la), count($ln));
        for ($f = 0; $f < $max; $f++) {
            $ca = isset($la[$f]) ? explode(",", $la[$f]) : array();
            $cn = isset($ln[$f]) ? explode(",", $ln[$f]) : array();
            $etiqueta = $f < count($filasLegacy) ? "Fila " . $filasLegacy[$f] : ($f === $max - 1 ? "Fin" : "Ítem " . ($f - count($filasLegacy) + 1));
            $mc = max(count($ca), count($cn));
            for ($k = 0; $k < $mc; $k++) {
                $va = isset($ca[$k]) ? $ca[$k] : null;
                $vn = isset($cn[$k]) ? $cn[$k] : null;
                if ($va !== $vn) {
                    $diffs[] = array("linea" => $f + 1, "fila" => $etiqueta, "columna" => self::letraColumna($k), "actual" => $va, "nuevo" => $vn);
                }
            }
        }
        return array(
            "ok" => true,
            "nombre" => $n["nombre"],
            "actual" => $a["contenido"],
            "nuevo" => $n["contenido"],
            "diferencias" => $diffs,
            "avisos" => $n["avisos"],
        );
    }

    public static function ctrGuardarPartida($in)
    {
        $d = array(
            "linea" => self::t(isset($in["linea"]) ? $in["linea"] : "", 30),
            "partida" => preg_replace('/[^0-9]/', '', isset($in["partida"]) ? (string) $in["partida"] : ""),
            "descripcion" => self::t(isset($in["descripcion"]) ? $in["descripcion"] : "", 150),
            "activo" => !empty($in["activo"]) && (string) $in["activo"] !== "0" ? 1 : 0,
        );

        if ($d["linea"] === "") {
            return array("ok" => false, "msg" => "Falta la línea.");
        }
        if ($d["partida"] !== "" && strlen($d["partida"]) !== 8) {
            return array("ok" => false, "msg" => "El código de producto SUNAT debe tener 8 dígitos (catálogo 25).");
        }
        if ($d["partida"] === "") {
            $d["activo"] = 0;
        }

        $ok = ModeloExportacion::mdlGuardarPartida($d, self::usuario());
        return $ok ? array("ok" => true) : array("ok" => false, "msg" => "No se pudo guardar el código.");
    }
}
