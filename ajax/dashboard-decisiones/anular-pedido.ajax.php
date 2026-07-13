<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/dashboard-decisiones.modelo.php";
require_once "../../controladores/dashboard-decisiones.controlador.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!dcUsuarioPuedeAnularPedido()) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para realizar esta acción."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(array("ok" => false, "msg" => "Método no permitido"));
    exit;
}

$codigoPedido = isset($_POST["codigo_pedido"]) ? trim((string) $_POST["codigo_pedido"]) : "";
$respuesta = ControladorDashboardDecisiones::ctrAnularPedidoGenerado($codigoPedido);

echo json_encode($respuesta);
