<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/dashboard-decisiones.modelo.php";
require_once "../../modelos/decisiones-credito.modelo.php";
require_once "../../modelos/categorias-clientes.modelo.php";
require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../controladores/dashboard-decisiones.controlador.php";
require_once "../../vistas/modulos/dashboard-decisiones/helpers.php";

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

$limite = isset($_POST["limite"]) ? (int) $_POST["limite"] : 80;
$vendedor = isset($_POST["vendedor"]) ? trim((string) $_POST["vendedor"]) : "";
$datos = ControladorDashboardDecisiones::ctrColaPedidosCredito($limite, $vendedor);

$generados = $datos["generados"];
$aprobados = $datos["aprobados"];
$resumenCola = $datos["resumen"];

ob_start();
include __DIR__ . "/../../vistas/modulos/historial-credito/tabla-cola.php";
$html = ob_get_clean();

echo json_encode(array(
    "ok" => true,
    "html" => $html,
    "vendedor" => isset($datos["vendedor"]) ? $datos["vendedor"] : "",
    "resumen" => $resumenCola,
));
