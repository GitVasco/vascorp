<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/metas-retos.config.php";
require_once "../controladores/dashboard-gerencial.config.php";
require_once "../controladores/dashboard-cxc.config.php";
require_once "../controladores/informe-semanal-vendedor.controlador.php";
require_once "../modelos/dashboard-gerencial.modelo.php";
require_once "../modelos/dashboard-cobranzas.modelo.php";
require_once "../modelos/dashboard-cxc.modelo.php";
require_once "../modelos/zonas-comerciales.modelo.php";
require_once "../modelos/informe-semanal-vendedor.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "informe_semanal_vendedor")) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para el informe semanal"));
    exit;
}

$accion = isset($_GET["accion"]) ? trim((string) $_GET["accion"]) : "informe";

$filtros = ControladorInformeSemanalVendedor::ctrParseFiltros(array(
    "vendedor" => isset($_GET["vendedor"]) ? $_GET["vendedor"] : "",
    "semana" => isset($_GET["semana"]) ? $_GET["semana"] : "",
));

try {
    if ($accion === "vendedores") {
        $payload = array(
            "ok" => true,
            "vendedores" => ControladorInformeSemanalVendedor::ctrVendedoresFiltro(),
        );
    } else {
        $payload = ControladorInformeSemanalVendedor::ctrInforme($filtros);
    }
} catch (Exception $e) {
    $payload = array("ok" => false, "msg" => "No se pudo armar el informe. Intente de nuevo.");
}

echo json_encode($payload);
