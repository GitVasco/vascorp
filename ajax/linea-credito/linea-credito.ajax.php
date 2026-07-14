<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/inteligencia-comercial.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/inteligencia-comercial.modelo.php";
require_once "../../modelos/linea-credito.modelo.php";
require_once "../../modelos/grupos-empresariales.modelo.php";
require_once "../../modelos/categorias-clientes.modelo.php";
require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../controladores/inteligencia-comercial.controlador.php";
require_once "../../controladores/linea-credito.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso"));
    exit;
}

$accion = isset($_REQUEST["accion"]) ? trim((string) $_REQUEST["accion"]) : "listar";

switch ($accion) {
    case "listar":
        $busqueda = isset($_REQUEST["busqueda"]) ? trim((string) $_REQUEST["busqueda"]) : "";
        $respuesta = array(
            "ok" => true,
            "filas" => ControladorLineaCredito::ctrListar($busqueda),
        );
        break;

    case "detalle":
        $cliente = isset($_REQUEST["codigo_cliente"]) ? trim((string) $_REQUEST["codigo_cliente"]) : "";
        $respuesta = ControladorLineaCredito::ctrDetalleCliente($cliente);
        break;

    case "actualizar_cliente":
        $cliente = isset($_POST["codigo_cliente"]) ? trim((string) $_POST["codigo_cliente"]) : "";
        $respuesta = ControladorLineaCredito::ctrActualizarCliente($cliente);
        break;

    case "cierre_mensual":
        $respuesta = ControladorLineaCredito::ctrCierreMensual();
        break;

    case "cierre_mensual_lote":
        $limite = isset($_POST["limite"]) ? (int) $_POST["limite"] : 15;
        $respuesta = ControladorLineaCredito::ctrCierreMensualLote($limite);
        break;

    case "detalle_grupo":
        $grupo = isset($_REQUEST["codigo_grupo"]) ? trim((string) $_REQUEST["codigo_grupo"]) : "";
        $respuesta = ControladorLineaCredito::ctrDetalleGrupo($grupo);
        break;

    case "registrar_linea_grupo":
        $respuesta = ControladorLineaCredito::ctrRegistrarLineaGrupo();
        break;

    case "registrar_linea":
        $respuesta = ControladorLineaCredito::ctrRegistrarLinea();
        break;

    default:
        $respuesta = array("ok" => false, "msg" => "Acción no válida.");
        break;
}

if (defined("JSON_UNESCAPED_UNICODE")) {
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode($respuesta);
}
