<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../controladores/inteligencia-comercial.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/inteligencia-comercial.modelo.php";
require_once "../../modelos/dashboard-decisiones.modelo.php";
require_once "../../modelos/decisiones-credito.modelo.php";
require_once "../../modelos/linea-credito.modelo.php";
require_once "../../modelos/grupos-empresariales.modelo.php";
require_once "../../controladores/inteligencia-comercial.controlador.php";
require_once "../../controladores/linea-credito.controlador.php";
require_once "../../controladores/dashboard-decisiones.controlador.php";
require_once "../../controladores/decisiones-credito.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para el Centro de Decisiones"));
    exit;
}

$accion = isset($_REQUEST["accion"]) ? trim((string) $_REQUEST["accion"]) : "estado";

switch ($accion) {
    case "catalogo":
        $respuesta = array("ok" => true) + ControladorDecisionesCredito::ctrCatalogo();
        break;

    case "estado":
        $codigoPedido = isset($_REQUEST["codigo_pedido"]) ? (int) $_REQUEST["codigo_pedido"] : 0;
        $codigoCliente = isset($_REQUEST["codigo_cliente"]) ? trim((string) $_REQUEST["codigo_cliente"]) : "";
        $respuesta = ControladorDecisionesCredito::ctrEstadoPedido($codigoPedido, $codigoCliente);
        break;

    case "registrar":
        $respuesta = ControladorDecisionesCredito::ctrRegistrarNoAprobacion();
        break;

    case "solicitar":
        $respuesta = ControladorDecisionesCredito::ctrCrearSolicitud();
        break;

    case "resolver_solicitud":
        $respuesta = ControladorDecisionesCredito::ctrResolverSolicitud();
        break;

    case "cerrar_decision":
        $respuesta = ControladorDecisionesCredito::ctrCerrarDecision();
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
