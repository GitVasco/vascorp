<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/inteligencia-comercial.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/inteligencia-comercial.modelo.php";
require_once "../../modelos/dashboard-decisiones.modelo.php";
require_once "../../controladores/inteligencia-comercial.controlador.php";
require_once "../../controladores/dashboard-decisiones.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para el Centro de Decisiones"));
    exit;
}

$cliente = isset($_POST["cliente"]) ? trim((string) $_POST["cliente"]) : "";
$pedido = isset($_POST["pedido"]) ? trim((string) $_POST["pedido"]) : "";

if ($cliente === "") {
    echo json_encode(array("ok" => false, "msg" => "Cliente no indicado"));
    exit;
}

$respuesta = ControladorDashboardDecisiones::ctrMiniInteligenciaCliente($cliente, $pedido);

if (defined("JSON_UNESCAPED_UNICODE")) {
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode($respuesta);
}
