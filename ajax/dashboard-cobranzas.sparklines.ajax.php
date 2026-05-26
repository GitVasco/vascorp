<?php

date_default_timezone_set("America/Lima");

require_once "../controladores/dashboard-cobranzas.controlador.php";
require_once "../modelos/dashboard-cobranzas.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_GET["anno"]) || !isset($_GET["mes"])) {
    echo json_encode(array("ok" => false, "msg" => "Parámetros incompletos"));
    exit;
}

$anno = intval($_GET["anno"]);
$mes = intval($_GET["mes"]);
$vendedor = isset($_GET["vendedor"]) ? trim($_GET["vendedor"]) : "";
$vendedorTop = isset($_GET["vendedor_top"]) ? trim($_GET["vendedor_top"]) : "";

$sparklines = ControladorDashboardCobranzas::ctrSparklines(
    $anno,
    $mes,
    $vendedor,
    $vendedorTop
);

echo json_encode(array(
    "ok" => true,
    "sparklines" => $sparklines,
));
