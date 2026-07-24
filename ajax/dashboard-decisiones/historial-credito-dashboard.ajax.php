<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/decisiones-credito.modelo.php";
require_once "../../modelos/dashboard-decisiones.modelo.php";
require_once "../../controladores/decisiones-credito.controlador.php";
require_once "../../controladores/dashboard-decisiones.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!dcUsuarioPuedeVerHistorialCredito()) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para ver el historial."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(array("ok" => false, "msg" => "Método no permitido"));
    exit;
}

$filtros = array(
    "fecha_desde" => isset($_POST["fecha_desde"]) ? trim((string) $_POST["fecha_desde"]) : "",
    "fecha_hasta" => isset($_POST["fecha_hasta"]) ? trim((string) $_POST["fecha_hasta"]) : "",
    "vendedor" => isset($_POST["vendedor"]) ? trim((string) $_POST["vendedor"]) : "",
    "dias_abiertos" => isset($_POST["dias_abiertos"]) ? (int) $_POST["dias_abiertos"] : 3,
);

echo json_encode(ControladorDecisionesCredito::ctrDashboardGestionCredito($filtros));
