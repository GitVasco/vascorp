<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../controladores/decisiones-credito.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/dashboard-decisiones.modelo.php";
require_once "../../modelos/decisiones-credito.modelo.php";
require_once "../../modelos/metas-vendedor.modelo.php";
require_once "../../modelos/categorias-clientes.modelo.php";
require_once "../../controladores/categorias-clientes.controlador.php";
require_once "../../controladores/dashboard-decisiones.controlador.php";
require_once "../../vistas/modulos/dashboard-decisiones/helpers.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo json_encode(array("ok" => false, "msg" => "Sesión no válida"));
    exit;
}

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "centro_decisiones")) {
    echo json_encode(array("ok" => false, "msg" => "Sin permiso para el Centro de Decisiones."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(array("ok" => false, "msg" => "Método no permitido"));
    exit;
}

$tRequest = microtime(true);

$vendedorRaw = isset($_POST["vendedor"]) ? trim((string) $_POST["vendedor"]) : "";

if ($vendedorRaw !== "") {
    $vendedorSeleccionado = ModeloDashboardDecisiones::normalizarVendedorFiltro($vendedorRaw);

    if ($vendedorSeleccionado === "") {
        echo json_encode(array(
            "ok" => false,
            "msg" => "No tienes permiso para consultar este vendedor.",
        ));
        exit;
    }
} else {
    $vendedorSeleccionado = "";
}

$_GET["vendedor"] = $vendedorSeleccionado;
ModeloDashboardDecisiones::setVendedorFiltro($vendedorSeleccionado);

// Cache de vendedores ya poblada por normalizar; reutilizar sin nueva query.
$vendedoresPermitidos = ControladorDashboardDecisiones::ctrVendedoresPermitidos();

$tDatos = microtime(true);
$datos = ControladorDashboardDecisiones::ctrDatosDashboard();
$msDatos = round((microtime(true) - $tDatos) * 1000);

$pedidos = $datos["pedidos"];
$cartera = $datos["cartera"];
$alertas = $datos["alertas"];
$topGenerados = $datos["top_generados"];
$generados = $datos["generados"];
$estancados = $datos["estancados"];
$atraso = $datos["atraso"];
$avanceVentas = $datos["avance_ventas"];
$facturadoResumen = $datos["facturado_resumen"];
$facturado = $datos["facturado"];
$articulosRiesgo = $datos["articulos_riesgo"];

$tRender = microtime(true);
ob_start();
include __DIR__ . "/../../vistas/modulos/dashboard-decisiones/contenido-dashboard.php";
$html = ob_get_clean();
$msRender = round((microtime(true) - $tRender) * 1000);

if (defined("DD_PERF_LOG") && DD_PERF_LOG) {
    error_log(sprintf(
        "[DD_PERF] ajax refrescar vendedor=%s datos=%dms render=%dms total=%dms html=%dB",
        $vendedorSeleccionado,
        $msDatos,
        $msRender,
        round((microtime(true) - $tRequest) * 1000),
        strlen($html)
    ));
}

$respuesta = array(
    "ok" => true,
    "html" => $html,
    "vendedor" => $vendedorSeleccionado,
);

if (defined("JSON_UNESCAPED_UNICODE")) {
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode($respuesta);
}
