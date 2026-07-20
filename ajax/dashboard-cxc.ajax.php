<?php

session_start();

date_default_timezone_set('America/Lima');

require_once '../controladores/config.php';
require_once '../controladores/permisos-modulos.config.php';
require_once '../controladores/dashboard-cxc.config.php';
require_once '../controladores/dashboard-cxc.controlador.php';
require_once '../modelos/dashboard-cxc.modelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['iniciarSesion']) || $_SESSION['iniciarSesion'] !== 'ok') {
    echo json_encode(array('ok' => false, 'msg' => 'Sesión no válida'));
    exit;
}

if (!function_exists('usuarioPuedeVerModulo') || !usuarioPuedeVerModulo('gestion_comercial', 'dashboard_cxc')) {
    echo json_encode(array('ok' => false, 'msg' => 'Sin permiso para el dashboard de cuentas por cobrar'));
    exit;
}

$accion = isset($_GET['accion']) ? trim((string) $_GET['accion']) : 'dashboard';

$filtros = ControladorDashboardCxc::ctrParseFiltros(array(
    'anio' => isset($_GET['anio']) ? $_GET['anio'] : (isset($_GET['año']) ? $_GET['año'] : null),
    'mes' => isset($_GET['mes']) ? $_GET['mes'] : null,
    'vendedor' => isset($_GET['vendedor']) ? $_GET['vendedor'] : '',
    'cliente' => isset($_GET['cliente']) ? $_GET['cliente'] : '',
    'zona' => isset($_GET['zona']) ? $_GET['zona'] : 0,
    'rango' => isset($_GET['rango']) ? $_GET['rango'] : '',
    'todos_vendedores' => isset($_GET['todos_vendedores']) ? $_GET['todos_vendedores'] : '',
));

$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$porPagina = isset($_GET['por_pagina']) ? (int) $_GET['por_pagina'] : 25;
$orden = isset($_GET['orden']) ? trim((string) $_GET['orden']) : 'vencido_desc';

try {
    switch ($accion) {
        case 'kpis':
            $payload = ControladorDashboardCxc::ctrKpis($filtros);
            break;
        case 'antiguedad':
            $payload = ControladorDashboardCxc::ctrAntiguedad($filtros);
            break;
        case 'por_vendedor':
            $payload = ControladorDashboardCxc::ctrPorVendedor($filtros);
            break;
        case 'tablas_vendedor':
            $payload = ControladorDashboardCxc::ctrTablasVendedor($filtros);
            break;
        case 'proyeccion_pagos':
            $payload = ControladorDashboardCxc::ctrProyeccionPagos($filtros);
            break;
        case 'por_rango':
            $payload = ControladorDashboardCxc::ctrPorRango($filtros);
            break;
        case 'top_clientes':
            $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 10;
            $payload = ControladorDashboardCxc::ctrTopClientes($filtros, $limite);
            break;
        case 'ventas':
            $payload = ControladorDashboardCxc::ctrDatosVentas($filtros);
            break;
        case 'detalle':
            $payload = ControladorDashboardCxc::ctrDetalleDocumentos($filtros, $pagina, $porPagina, $orden);
            break;
        case 'dashboard':
        default:
            $payload = ControladorDashboardCxc::ctrDatosDashboard($filtros, $pagina, $porPagina);
            break;
    }

    echo json_encode(array(
        'ok' => true,
        'accion' => $accion,
        'data' => $payload,
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'ok' => false,
        'msg' => 'Error al consultar el dashboard de CxC',
    ));
}
