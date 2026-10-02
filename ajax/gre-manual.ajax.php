<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/gre-manual.modelo.php";
require_once "../controladores/gre-manual.controlador.php";

header("Content-Type: application/json; charset=utf-8");

function greJson($r, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ControladorGreManual::puedeUsar()) {
    greJson(array("ok" => false, "msg" => "Sin permiso para este módulo."), 403);
}

$accion = isset($_REQUEST["accion"]) ? trim($_REQUEST["accion"]) : "";
$q = isset($_REQUEST["q"]) ? trim($_REQUEST["q"]) : "";

switch ($accion) {
    case "catalogos":
        $faltan = ModeloGreManual::mdlTablasListas();
        if (count($faltan)) {
            greJson(array("ok" => true, "faltan" => $faltan));
        }
        greJson(array(
            "ok" => true,
            "motivos" => ControladorGreManual::motivos(),
            "remitente" => ControladorGreManual::remitente(),
            "series" => ModeloGreManual::mdlSeries(),
            "unidades" => ModeloGreManual::mdlUnidades(),
            "choferes" => ModeloGreManual::mdlChoferes(),
            "vehiculos" => ModeloGreManual::mdlVehiculos(),
            "agencias" => ModeloGreManual::mdlAgencias(),
            "faltan" => array(),
        ));

    case "buscar":
        if (strlen($q) < 2) {
            greJson(array("ok" => true, "datos" => array()));
        }
        $tipo = isset($_REQUEST["tipo"]) ? $_REQUEST["tipo"] : "";
        $mapa = array(
            "cliente" => "mdlBuscarClientes",
            "proveedor" => "mdlBuscarProveedores",
            "ubigeo" => "mdlBuscarUbigeos",
            "modelo" => "mdlBuscarModelos",
            "articulo" => "mdlBuscarArticulos",
            "mp" => "mdlBuscarMateriaPrima",
        );
        if (!isset($mapa[$tipo])) {
            greJson(array("ok" => false, "msg" => "Tipo de búsqueda inválido."), 400);
        }
        greJson(array("ok" => true, "datos" => call_user_func(array("ModeloGreManual", $mapa[$tipo]), $q)));

    case "listar":
        $desde = isset($_REQUEST["desde"]) ? $_REQUEST["desde"] : date("Y-m-01");
        $hasta = isset($_REQUEST["hasta"]) ? $_REQUEST["hasta"] : date("Y-m-d");
        $estado = isset($_REQUEST["estado"]) ? $_REQUEST["estado"] : "";
        if ($estado !== "" && !in_array($estado, array("GENERADO", "ENVIADO", "ANULADO"), true)) {
            $estado = "";
        }
        $tipoF = isset($_REQUEST["tipo"]) && in_array($_REQUEST["tipo"], array("ELECTRONICA", "INTERNA"), true) ? $_REQUEST["tipo"] : "";
        greJson(array("ok" => true, "datos" => ModeloGreManual::mdlListar($desde, $hasta, $estado, $tipoF)));

    case "series":
        greJson(array("ok" => true, "series" => ModeloGreManual::mdlSeries()));

    case "convertir":
        $r = ModeloGreManual::mdlConvertirAElectronica((int) (isset($_POST["id"]) ? $_POST["id"] : 0), isset($_POST["serie"]) ? trim($_POST["serie"]) : "");
        greJson($r, !empty($r["ok"]) ? 200 : 400);

    case "obtener":
        $g = ModeloGreManual::mdlObtener((int) (isset($_REQUEST["id"]) ? $_REQUEST["id"] : 0));
        if (!$g) {
            greJson(array("ok" => false, "msg" => "Guía no encontrada."), 404);
        }
        greJson(array("ok" => true, "guia" => $g));

    case "guardar":
        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
        $serie = isset($_POST["serie"]) ? trim($_POST["serie"]) : "";
        $r = ControladorGreManual::ctrGuardar($id, $serie, $_POST);
        greJson($r, !empty($r["ok"]) ? 200 : 400);

    case "enviar":
        $r = ControladorGreManual::ctrEnviar((int) (isset($_POST["id"]) ? $_POST["id"] : 0));
        greJson($r, !empty($r["ok"]) ? 200 : 400);

    case "anular":
        $ok = ModeloGreManual::mdlAnular((int) (isset($_POST["id"]) ? $_POST["id"] : 0));
        greJson($ok ? array("ok" => true) : array("ok" => false, "msg" => "Solo se anula una guía GENERADA."), $ok ? 200 : 400);

    case "eliminar":
        $ok = ModeloGreManual::mdlEliminar((int) (isset($_POST["id"]) ? $_POST["id"] : 0));
        greJson($ok ? array("ok" => true) : array("ok" => false, "msg" => "Solo se elimina una guía GENERADA o ANULADA."), $ok ? 200 : 400);

    case "correlativo-info":
        if (!ControladorGreManual::puedeCorregirCorrelativo()) {
            greJson(array("ok" => false, "msg" => "Sin permiso para corregir el correlativo."), 403);
        }
        greJson(array("ok" => true, "series" => ModeloGreManual::mdlSeriesCorrelativo()));

    case "correlativo-fijar":
        if (!ControladorGreManual::puedeCorregirCorrelativo()) {
            greJson(array("ok" => false, "msg" => "Sin permiso para corregir el correlativo."), 403);
        }
        $n = isset($_POST["numero"]) ? trim($_POST["numero"]) : "";
        if ($n === "" || !ctype_digit($n) || (int) $n > 99999999) {
            greJson(array("ok" => false, "msg" => "Número inválido."), 400);
        }
        $r = ModeloGreManual::mdlFijarCorrelativo(isset($_POST["serie"]) ? trim($_POST["serie"]) : "", (int) $n);
        greJson($r, !empty($r["ok"]) ? 200 : 400);

    default:
        greJson(array("ok" => false, "msg" => "Acción no válida."), 400);
}
