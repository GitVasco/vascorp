<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/comprobantes-especiales.modelo.php";
require_once "../controladores/comprobantes-especiales.controlador.php";
// Solo para consultar RUC/DNI (misma API que usa Guías manuales)
require_once "../modelos/gre-manual.modelo.php";
require_once "../controladores/gre-manual.controlador.php";

header("Content-Type: application/json; charset=utf-8");

function ceJson($r, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ControladorComprobantesEspeciales::puedeUsar()) {
    ceJson(array("ok" => false, "msg" => "Sin permiso para este módulo."), 403);
}

$accion = isset($_REQUEST["accion"]) ? trim($_REQUEST["accion"]) : "";

switch ($accion) {
    case "catalogos":
        $faltan = ModeloComprobantesEspeciales::mdlTablasListas();
        if (count($faltan)) {
            ceJson(array("ok" => true, "faltan" => $faltan));
        }
        ceJson(array(
            "ok" => true,
            "faltan" => array(),
            "tipos" => ControladorComprobantesEspeciales::tipos(),
            "afectacion_nombres" => ControladorComprobantesEspeciales::catalogoAfectaciones(),
            "perfiles" => ControladorComprobantesEspeciales::perfiles(),
            "unidades" => ControladorComprobantesEspeciales::unidades(),
            "series" => ModeloComprobantesEspeciales::mdlSeries(),
        ));

    case "consultar-doc":
        $r = ControladorGreManual::ctrConsultarDocumento(isset($_REQUEST["tipo_doc"]) ? $_REQUEST["tipo_doc"] : "", isset($_REQUEST["numero"]) ? $_REQUEST["numero"] : "");
        ceJson($r, !empty($r["ok"]) ? 200 : 400);

    case "listar":
        $desde = isset($_REQUEST["desde"]) ? $_REQUEST["desde"] : date("Y-m-01");
        $hasta = isset($_REQUEST["hasta"]) ? $_REQUEST["hasta"] : date("Y-m-d");
        $estado = isset($_REQUEST["estado"]) ? $_REQUEST["estado"] : "";
        if ($estado !== "" && !in_array($estado, array("GENERADO", "ENVIADO", "BAJA", "ANULADO"), true)) {
            $estado = "";
        }
        $lote = isset($_REQUEST["lote"]) ? trim($_REQUEST["lote"]) : "";
        $tipoOp = isset($_REQUEST["tipo_operacion"]) && ControladorComprobantesEspeciales::perfil($_REQUEST["tipo_operacion"]) ? $_REQUEST["tipo_operacion"] : "";
        ceJson(array("ok" => true, "datos" => ModeloComprobantesEspeciales::mdlListar($desde, $hasta, $estado, $lote, $tipoOp)));

    case "crear-lote":
        $in = $_POST;
        if (isset($in["receptores"]) && is_string($in["receptores"])) {
            $in["receptores"] = json_decode($in["receptores"], true);
        }
        if (isset($in["items"]) && is_string($in["items"])) {
            $in["items"] = json_decode($in["items"], true);
        }
        $r = ControladorComprobantesEspeciales::ctrCrearLote($in);
        ceJson($r, !empty($r["ok"]) ? 200 : 400);

    case "enviar":
        $r = ControladorComprobantesEspeciales::ctrEnviar((int) (isset($_POST["id"]) ? $_POST["id"] : 0));
        ceJson($r, !empty($r["ok"]) ? 200 : 400);

    case "ver-csv":
        // Para revisar el archivo: texto plano, no envía ni cambia el comprobante
        $r = ControladorComprobantesEspeciales::ctrVerArchivo((int) (isset($_REQUEST["id"]) ? $_REQUEST["id"] : 0));
        if (empty($r["ok"])) {
            ceJson($r, 400);
        }
        header("Content-Type: text/plain; charset=utf-8");
        header("Content-Disposition: inline; filename=\"" . $r["nombre"] . "\"");
        echo $r["contenido"];
        exit;

    case "baja":
        $ok = ModeloComprobantesEspeciales::mdlMarcarBaja((int) (isset($_POST["id"]) ? $_POST["id"] : 0));
        ceJson($ok ? array("ok" => true) : array("ok" => false, "msg" => "Solo se marca baja en un comprobante ENVIADO."), $ok ? 200 : 400);

    case "anular":
        $ok = ModeloComprobantesEspeciales::mdlAnular((int) (isset($_POST["id"]) ? $_POST["id"] : 0));
        ceJson($ok ? array("ok" => true) : array("ok" => false, "msg" => "Solo se anula un comprobante GENERADO."), $ok ? 200 : 400);

    case "eliminar":
        $ok = ModeloComprobantesEspeciales::mdlEliminar((int) (isset($_POST["id"]) ? $_POST["id"] : 0));
        ceJson($ok ? array("ok" => true) : array("ok" => false, "msg" => "Solo se elimina un comprobante GENERADO o ANULADO."), $ok ? 200 : 400);

    default:
        ceJson(array("ok" => false, "msg" => "Acción no válida."), 400);
}
