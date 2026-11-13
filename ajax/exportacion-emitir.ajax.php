<?php

/**
 * Exportación — emitir desde pedido (docs/efact/EXPORTACION-diseno.md, Fase 3B).
 * Emisión: Comprobantes especiales → Nuevo comprobante → tipo Exportación (exportacion-crear.js).
 * Listado y envío a eFact: Comprobantes especiales → Listado y envío → pestaña Exportación (exportacion-emitir.js).
 */

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/permisos-modulos.config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/exportacion.modelo.php";
require_once "../controladores/exportacion.controlador.php";
// Funciones existentes que se llaman (no se modifican)
require_once "../modelos/facturacion.modelo.php";
require_once "../controladores/facturacion.controlador.php";
require_once "../modelos/pedidos.modelo.php";
require_once "../modelos/articulos.modelo.php";
require_once "../controladores/decisiones-credito.config.php";
require_once "../modelos/decisiones-credito.modelo.php";
// Tipo de cambio: totalesjf y API json.pe (mismas funciones que Movimientos / Utilidades)
require_once "../helpers/jsonpe.api.php";
require_once "../modelos/utilidades.modelo.php";

header("Content-Type: application/json; charset=utf-8");

function expEmiJson($r, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ControladorExportacion::puedeUsar()) {
    expEmiJson(array("ok" => false, "msg" => "Sin permiso para este módulo."), 403);
}

$faltan = ControladorExportacion::tablasEmitir();
if (count($faltan)) {
    expEmiJson(array("ok" => false, "faltan" => $faltan, "msg" => "Faltan tablas: " . implode(", ", $faltan) . ". Ejecutar docs/sql/exportacion-fase3.sql."), 400);
}

$accion = isset($_REQUEST["accion"]) ? trim($_REQUEST["accion"]) : "";
$esPost = $_SERVER["REQUEST_METHOD"] === "POST";

switch ($accion) {
    case "bandeja":
        $cfg = ControladorExportacion::config();
        expEmiJson(array(
            "ok" => true,
            "serie" => $cfg["serie"],
            "avisos" => $cfg["avisos"],
            "paises" => ControladorExportacion::paises(),
            "datos" => ModeloExportacion::mdlBandejaPedidos(),
        ));

    case "revisar":
        $r = ControladorExportacion::ctrRevisarPedido(isset($_REQUEST["pedido"]) ? $_REQUEST["pedido"] : "");
        expEmiJson($r, !empty($r["ok"]) ? 200 : 400);

    case "emitir":
        if (!$esPost) {
            expEmiJson(array("ok" => false, "msg" => "Método no permitido."), 405);
        }
        $r = ControladorExportacion::ctrEmitir(
            isset($_POST["pedido"]) ? $_POST["pedido"] : "",
            isset($_POST["tipo_cambio"]) ? $_POST["tipo_cambio"] : ""
        );
        expEmiJson($r, !empty($r["ok"]) ? 200 : 400);

    case "emitidas":
        $desde = isset($_REQUEST["desde"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST["desde"]) ? $_REQUEST["desde"] : date("Y-m-01");
        $hasta = isset($_REQUEST["hasta"]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_REQUEST["hasta"]) ? $_REQUEST["hasta"] : date("Y-m-d");
        expEmiJson(array("ok" => true, "paises" => ControladorExportacion::paises(), "datos" => ModeloExportacion::mdlFacturasEmitidas($desde, $hasta)));

    case "tipo-cambio":
        if (!$esPost) {
            expEmiJson(array("ok" => false, "msg" => "Método no permitido."), 405);
        }
        $r = ControladorExportacion::ctrGuardarTipoCambio(isset($_POST["id"]) ? (int) $_POST["id"] : 0, isset($_POST["tipo_cambio"]) ? $_POST["tipo_cambio"] : "");
        expEmiJson($r, !empty($r["ok"]) ? 200 : 400);

    case "tipo-cambio-dia":
        $r = ControladorExportacion::ctrTipoCambioDia(isset($_REQUEST["fecha"]) ? $_REQUEST["fecha"] : null);
        expEmiJson($r, !empty($r["ok"]) ? 200 : 404);

    case "tipo-cambio-auto":
        if (!$esPost) {
            expEmiJson(array("ok" => false, "msg" => "Método no permitido."), 405);
        }
        $r = ControladorExportacion::ctrTipoCambioAuto(isset($_POST["id"]) ? (int) $_POST["id"] : 0);
        expEmiJson($r, !empty($r["ok"]) ? 200 : 400);

    case "ver-archivo":
        $f = ModeloExportacion::mdlFactura(isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0);
        if (!$f) {
            expEmiJson(array("ok" => false, "msg" => "Factura no encontrada."), 404);
        }
        $r = ControladorExportacion::ctrArmarCsv($f["documento"], "nuevo");
        expEmiJson($r, !empty($r["ok"]) ? 200 : 400);

    case "enviar":
        if (!$esPost) {
            expEmiJson(array("ok" => false, "msg" => "Método no permitido."), 405);
        }
        $r = ControladorExportacion::ctrEnviar(isset($_POST["id"]) ? (int) $_POST["id"] : 0);
        expEmiJson($r, !empty($r["ok"]) ? 200 : 400);

    default:
        expEmiJson(array("ok" => false, "msg" => "Acción no válida."), 400);
}
