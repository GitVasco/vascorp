<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../controladores/dashboard-cobranzas.controlador.php";
require_once "../modelos/dashboard-cobranzas.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para el dashboard de cobranzas"));
    exit;
}

if (!isset($_GET["anno"]) || !isset($_GET["mes"])) {
    echo json_encode(array("ok" => false, "msg" => "Parámetros incompletos"));
    exit;
}

$anno = intval($_GET["anno"]);
$mes = intval($_GET["mes"]);
$vendedor = isset($_GET["vendedor"]) ? trim($_GET["vendedor"]) : "";
$vendedorTop = isset($_GET["vendedor_top"]) ? trim($_GET["vendedor_top"]) : "";

$datos = ControladorDashboardCobranzas::ctrDatosGraficos(
    $anno,
    $mes,
    $vendedor,
    $vendedorTop
);

echo json_encode(array(
    "ok" => true,
    "sparklines" => $datos["sparklines"],
    "cobranza_semana" => $datos["cobranza_semana"],
    "cobranza_dia_semana" => $datos["cobranza_dia_semana"],
    "evolucion_acumulada" => $datos["evolucion_acumulada"],
    "comparativo_mensual" => $datos["comparativo_mensual"],
    "top_vendedores" => $datos["top_vendedores"],
    "heatmap_cobranza" => $datos["heatmap_cobranza"],
    "top_clientes" => $datos["top_clientes"],
    "dias_sin_cobranza" => $datos["dias_sin_cobranza"],
    "distribucion_tipo_ingreso" => $datos["distribucion_tipo_ingreso"],
));
