<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/inteligencia-comercial.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/inteligencia-comercial.modelo.php";
require_once "../../modelos/categorias-clientes.modelo.php";
require_once "../../controladores/inteligencia-comercial.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para Inteligencia Comercial"));
    exit;
}

$cliente = isset($_POST["cliente"]) ? trim((string) $_POST["cliente"]) : "";
$grupo = isset($_POST["grupo"]) ? trim((string) $_POST["grupo"]) : "";

if ($grupo !== "") {
    $respuesta = ControladorInteligenciaComercial::ctrGenerarResumenIaGrupo($grupo);
} elseif ($cliente !== "") {
    $respuesta = ControladorInteligenciaComercial::ctrGenerarResumenIa($cliente);
} else {
    echo json_encode(array("ok" => false, "msg" => "Cliente o grupo no indicado"));
    exit;
}

if (defined("JSON_UNESCAPED_UNICODE")) {
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode($respuesta);
}
