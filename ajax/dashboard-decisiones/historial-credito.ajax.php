<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/decisiones-credito.modelo.php";
require_once "../../modelos/categorias-clientes.modelo.php";
require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../controladores/decisiones-credito.controlador.php";

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
    "tipo_accion" => isset($_POST["tipo_accion"]) ? trim((string) $_POST["tipo_accion"]) : "",
    "q" => isset($_POST["q"]) ? trim((string) $_POST["q"]) : "",
    "usuario_id" => isset($_POST["usuario_id"]) ? (int) $_POST["usuario_id"] : 0,
    "limite" => isset($_POST["limite"]) ? (int) $_POST["limite"] : 200,
);

echo json_encode(ControladorDecisionesCredito::ctrListarHistorialAcciones($filtros));
