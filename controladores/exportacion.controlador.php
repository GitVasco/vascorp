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

        // Desde 13-nov-2026 la exportación se emite solo en Comprobantes especiales → Nuevo comprobante → Exportación
        if ($exterior) {
            return "El cliente es del exterior (" . self::nombrePais(strtoupper($p["pais_iso"])) . "): la factura de exportación se emite en Comprobantes especiales → Nuevo comprobante → tipo Exportación.";
        }
        if ($esFx) {
            return "La serie FX es solo para exportación (Comprobantes especiales → Nuevo comprobante → Exportación). Si el cliente es del exterior, márquelo en Clientes (casilla «Cliente del exterior» y país).";
        }
        return "";
    }

    /* =====================================================
       FASE 3B: EMITIR DESDE PEDIDO
    ===================================================== */

    const ESTADOS_EMITIR = "APROBADO,APT,CONFIRMADO";

    public static function tablasEmitir()
    {
        $faltan = ModeloExportacion::mdlTablasListas();
        if (!ModeloExportacion::mdlTablaFacturas()) {
            $faltan[] = "exp_facturajf";
        }
        return $faltan;
    }

    /** Revisión del pedido antes de emitir: errores bloquean, avisos solo informan. */
    public static function ctrRevisarPedido($codigo)
    {
        $codigo = trim((string) $codigo);
        if ($codigo === "" || !ctype_digit($codigo)) {
            return array("ok" => false, "msg" => "Pedido no válido.");
        }
        $p = ModeloExportacion::mdlPedidoEmitir($codigo);
        if (!$p) {
            return array("ok" => false, "msg" => "Pedido no encontrado.");
        }
        $cfg = self::config();
        $errores = array();
        $avisos = array();

        if (!in_array(strtoupper($p["estado"]), explode(",", self::ESTADOS_EMITIR), true)) {
            $errores[] = "El pedido está " . $p["estado"] . "; solo se emiten pedidos aprobados, APT o confirmados.";
        }
        if (!self::esExterior($p["pais_iso"])) {
            $errores[] = "El cliente no es del exterior (sin país asignado en Clientes).";
        }
        if ($p["lista"] !== "precio1") {
            $errores[] = "El pedido usa la lista " . $p["lista"] . "; debe ser la lista 1 (USD).";
        }
        if ($cfg["serie"] === "") {
            $errores[] = "No hay serie FX configurada. " . implode(" ", $cfg["avisos"]);
        }
        $sinPrecio = ModeloPedidos::mdlArticulosPrecioCeroPedido($codigo);
        if (!empty($sinPrecio)) {
            $errores[] = count($sinPrecio) === 1 ? "Hay 1 modelo sin precio." : ("Hay " . count($sinPrecio) . " modelos sin precio.");
        }
        if (class_exists("ModeloDecisionesCredito")) {
            $control = ModeloDecisionesCredito::mdlControlBloqueantePendiente((int) $codigo);
            if ($control) {
                $errores[] = "Control de créditos pendiente: " . $control["mensaje"];
            }
        }
        $previa = ModeloExportacion::mdlFacturaPorPedido($codigo);
        if ($previa) {
            $errores[] = "Este pedido ya se emitió como " . $previa["documento"] . ".";
        }

        $detalle = ModeloExportacion::mdlDetallePedido($codigo);
        $sinStock = 0;
        foreach ($detalle as $d) {
            $sinStock += (int) $d["sin_stock"];
        }
        if ($sinStock > 0) {
            $avisos[] = $sinStock . " talla(s)/color(es) con stock del almacén 01 menor a lo pedido (se descuenta igual, como en la facturación normal).";
        }
        if (trim((string) $p["direccion"]) === "") {
            $avisos[] = "El cliente no tiene dirección.";
        }

        $p["pais"] = self::nombrePais(strtoupper($p["pais_iso"]));
        return array(
            "ok" => true,
            "pedido" => $p,
            "detalle" => $detalle,
            "serie" => $cfg["serie"],
            "errores" => $errores,
            "avisos" => $avisos,
            "puede_emitir" => count($errores) === 0,
        );
    }

    /**
     * Tipo de cambio (venta) de una fecha:
     * 1) totalesjf del día; 2) si no, API (JsonPeApi) y se guarda en totalesjf; 3) si la API falla, el último de totalesjf.
     * Devuelve array(ok, venta, compra, fuente, texto).
     */
    public static function ctrTipoCambioDia($fecha = null)
    {
        date_default_timezone_set("America/Lima");
        $fecha = $fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date("Y-m-d");

        $tot = ModeloExportacion::mdlTipoCambioTotales($fecha);
        if ($tot && (float) $tot["cambio_venta"] > 0) {
            return array("ok" => true, "fecha" => $fecha, "venta" => (float) $tot["cambio_venta"], "compra" => (float) $tot["cambio_compra"],
                "fuente" => "totales", "texto" => "Totales del día");
        }

        if (class_exists("JsonPeApi")) {
            $api = JsonPeApi::consultarTipoCambio($fecha);
            $venta = isset($api["venta"]) ? $api["venta"] : null;
            if ((!isset($api["_ok"]) || $api["_ok"] === true) && is_numeric($venta) && (float) $venta > 0) {
                $compra = isset($api["compra"]) && is_numeric($api["compra"]) ? (float) $api["compra"] : 0;
                $guardado = false;
                if ($tot && class_exists("ModeloUtilidades")) {
                    $u = ModeloUtilidades::mdlActualizarTipCambioTotales($fecha, $compra, (float) $venta);
                    $guardado = !empty($u["ok"]);
                }
                return array("ok" => true, "fecha" => $fecha, "venta" => (float) $venta, "compra" => $compra,
                    "fuente" => "api", "texto" => $guardado ? "API SUNAT (actualizado en totales)" : "API SUNAT");
            }
        }

        if (class_exists("ModeloUtilidades")) {
            $prev = ModeloUtilidades::mdlUltimoTipCambioTotalesAntes($fecha);
            if ($prev && (float) $prev["cambio_venta"] > 0) {
                return array("ok" => true, "fecha" => $fecha, "venta" => (float) $prev["cambio_venta"], "compra" => (float) $prev["cambio_compra"],
                    "fuente" => "anterior", "texto" => "Último registrado (" . $prev["fecha"] . "); la API no respondió");
            }
        }

        return array("ok" => false, "fecha" => $fecha, "msg" => "No se encontró el tipo de cambio (totales ni API).");
    }

    /** Tipo de cambio opcional: vacío = NULL; si viene, número positivo con hasta 4 decimales. */
    public static function normalizarTipoCambio($v)
    {
        $v = trim(str_replace(",", ".", (string) $v));
        if ($v === "") {
            return array(true, null);
        }
        if (!preg_match('/^\d{1,3}(\.\d{1,4})?$/', $v) || (float) $v <= 0) {
            return array(false, null);
        }
        return array(true, $v);
    }

    /**
     * Emite la factura FX del pedido con las funciones existentes (mismo orden y datos que
     * ControladorFacturacion::ctrFacturarN para factura), sin pasar por su formulario.
     * Almacén 01, exportacion = 1, moneda 2 (USD). No envía a eFact.
     */
    public static function ctrEmitir($codigo, $tipoCambio)
    {
        list($tcOk, $tc) = self::normalizarTipoCambio($tipoCambio);
        if (!$tcOk) {
            return array("ok" => false, "msg" => "Tipo de cambio no válido (ej. 3.7520).");
        }
        $codigo = trim((string) $codigo);
        if ($codigo === "" || !ctype_digit($codigo)) {
            return array("ok" => false, "msg" => "Pedido no válido.");
        }

        $pdo = Conexion::conectar();
        if (!ModeloExportacion::mdlBloquearPedido($pdo, $codigo)) {
            return array("ok" => false, "msg" => "El pedido se está emitiendo en otra ventana. Espere y actualice.");
        }

        try {
            $rev = self::ctrRevisarPedido($codigo);
            if (empty($rev["ok"])) {
                return $rev;
            }
            if (!$rev["puede_emitir"]) {
                return array("ok" => false, "msg" => implode(" ", $rev["errores"]));
            }
            $p = $rev["pedido"];
            $serie = $rev["serie"];

            if ($tc === null) {
                $tcDia = self::ctrTipoCambioDia(date("Y-m-d"));
                if (!empty($tcDia["ok"])) {
                    $tc = number_format($tcDia["venta"], 4, ".", "");
                }
            }

            $usuario = isset($_SESSION["id"]) ? $_SESSION["id"] : "";
            $usureg = self::usuario();
            $pcreg = isset($_SERVER["REMOTE_ADDR"]) ? gethostbyaddr($_SERVER["REMOTE_ADDR"]) : "";
            $dscto = $p["dscto"] === null || $p["dscto"] === "" ? 0 : $p["dscto"];

            $documento = ModeloFacturacion::mdlAsignarSiguienteDocumento("01", $serie);
            if (!$documento) {
                return array("ok" => false, "msg" => "No se pudo asignar el correlativo de la serie " . $serie . ". No se registró nada.");
            }

            $stock = ModeloArticulos::mdlActualizarStockPedido($codigo, "stock01");
            if ($stock !== "ok") {
                return array("ok" => false, "msg" => "No se pudo descontar el stock. El número " . $documento . " quedó reservado sin usar: avisar a TI.");
            }

            ControladorFacturacion::ctrRegistrarCuentaCorriente($codigo, "01", $documento, $usuario, $usureg, $pcreg);
            ControladorFacturacion::ctrRegistrarMovimientos($codigo, "S03", $documento, $p["cliente"], $p["vendedor"], $dscto, "FACTURA", "01");
            ControladorFacturacion::ctrRegistrarVentas($codigo, "S03", $documento, "", $codigo, $usuario, "FACTURA", $usureg, $pcreg, null, null, null, null, 1, 2, "");
            ModeloFacturacion::mdlActualizarPedidoF($codigo);
            ModeloFacturacion::mdlActualizarPedidoB($codigo);
            if ($tc !== null) {
                ModeloExportacion::mdlGuardarTipoCambioVenta($documento, $tc);
            }

            ModeloExportacion::mdlRegistrarFactura(array(
                "documento" => $documento,
                "pedido" => $codigo,
                "cliente" => $p["cliente"],
                "pais_iso" => strtoupper($p["pais_iso"]),
                "tipo_cambio" => $tc,
                "total" => $p["total"],
                "usuario" => $usureg,
            ));
            $f = ModeloExportacion::mdlFacturaPorPedido($codigo);

            return array("ok" => true, "documento" => $documento, "id" => $f ? (int) $f["id"] : 0, "tipo_cambio" => $tc);
        } finally {
            ModeloExportacion::mdlLiberarPedido($pdo, $codigo);
        }
    }

    public static function ctrGuardarTipoCambio($id, $valor)
    {
        list($ok, $tc) = self::normalizarTipoCambio($valor);
        if (!$ok) {
            return array("ok" => false, "msg" => "Tipo de cambio no válido (ej. 3.7520).");
        }
        $f = ModeloExportacion::mdlFactura($id);
        if (!$f) {
            return array("ok" => false, "msg" => "Factura no encontrada.");
        }
        ModeloExportacion::mdlGuardarTipoCambio($id, $tc);
        ModeloExportacion::mdlGuardarTipoCambioVenta($f["documento"], $tc);
        return array("ok" => true, "tipo_cambio" => $tc);
    }

    /** Completa el tipo de cambio de una factura con el del día de emisión (totales → API → último). */
    public static function ctrTipoCambioAuto($id)
    {
        $f = ModeloExportacion::mdlFactura($id);
        if (!$f) {
            return array("ok" => false, "msg" => "Factura no encontrada.");
        }
        $dia = self::ctrTipoCambioDia(substr((string) $f["creado_en"], 0, 10));
        if (empty($dia["ok"])) {
            return $dia;
        }
        $tc = number_format($dia["venta"], 4, ".", "");
        ModeloExportacion::mdlGuardarTipoCambio($id, $tc);
        ModeloExportacion::mdlGuardarTipoCambioVenta($f["documento"], $tc);
        return array("ok" => true, "tipo_cambio" => $tc, "texto" => $dia["texto"]);
    }

    private static function rutaFisica($ruta)
    {
        if (preg_match('#^([A-Za-z]:|/|\\\\)#', $ruta)) {
            return $ruta;
        }
        return __DIR__ . "/" . $ruta;
    }

    /**
     * Deja el CSV nuevo en la carpeta que vigila eFact (mismo mecanismo que Comprobantes especiales)
     * y marca la venta como ENVIADO igual que el envío normal (mdlEnviarTXT).
     */
    public static function ctrEnviar($id)
    {
        $f = ModeloExportacion::mdlFactura($id);
        if (!$f) {
            return array("ok" => false, "msg" => "Factura no encontrada.");
        }
        if ($f["estado"] !== "GENERADO") {
            return array("ok" => false, "msg" => "La factura ya está " . $f["estado"] . ".");
        }
        $csv = self::ctrArmarCsv($f["documento"], "nuevo");
        if (empty($csv["ok"])) {
            return $csv;
        }
        if (!defined("ORIGEN") || !defined("DESTINO_INVOICE")) {
            return array("ok" => false, "msg" => "Falta configurar las carpetas de eFact (config.php).");
        }
        $origenDir = self::rutaFisica(ORIGEN);
        $destinoDir = self::rutaFisica(DESTINO_INVOICE);
        if (!is_dir($origenDir)) {
            @mkdir($origenDir, 0775, true);
        }
        $base = preg_replace('/\.csv$/', '', $csv["nombre"]);
        $tmp = $origenDir . $base . ".txt";
        if (file_put_contents($tmp, $csv["contenido"]) === false) {
            return array("ok" => false, "msg" => "No se pudo escribir el archivo temporal.");
        }
        if (!ModeloExportacion::mdlMarcarEnviada($id, $csv["nombre"], self::usuario())) {
            @unlink($tmp);
            return array("ok" => false, "msg" => "La factura ya fue enviada desde otra ventana.");
        }
        if (!@rename($tmp, $destinoDir . $csv["nombre"])) {
            @unlink($tmp);
            ModeloExportacion::mdlVolverGenerada($id);
            return array("ok" => false, "msg" => "No se pudo dejar el archivo en la carpeta de eFact.");
        }
        ModeloFacturacion::mdlEnviarTXT("S03", $f["documento"]);
        return array("ok" => true, "documento" => $f["documento"], "archivo" => $csv["nombre"], "avisos" => $csv["avisos"]);
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
