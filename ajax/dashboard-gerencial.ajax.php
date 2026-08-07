<?php

session_start();

date_default_timezone_set('America/Lima');

require_once '../controladores/config.php';
require_once '../controladores/permisos-modulos.config.php';
require_once '../controladores/dashboard-gerencial.config.php';
require_once '../controladores/dashboard-gerencial.controlador.php';
require_once '../controladores/dashboard-cobranzas.controlador.php';
require_once '../controladores/dashboard-cxc.config.php';
require_once '../controladores/dashboard-cxc.controlador.php';
require_once '../controladores/movimientos.controlador.php';
require_once '../modelos/dashboard-gerencial.modelo.php';
require_once '../modelos/dashboard-cobranzas.modelo.php';
require_once '../modelos/dashboard-cxc.modelo.php';
require_once '../modelos/metas-vendedor.modelo.php';
require_once '../modelos/movimientos.modelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['iniciarSesion']) || $_SESSION['iniciarSesion'] !== 'ok') {
    echo json_encode(array('ok' => false, 'msg' => 'Sesión no válida'));
    exit;
}

if (!function_exists('usuarioPuedeVerModulo') || !usuarioPuedeVerModulo('gestion_comercial', 'dashboard_gerencial')) {
    echo json_encode(array('ok' => false, 'msg' => 'Sin permiso para el dashboard gerencial'));
    exit;
}

$accion = isset($_GET['accion']) ? trim((string) $_GET['accion']) : 'base';

$filtros = ControladorDashboardGerencial::ctrParseFiltros(array(
    'anio' => isset($_GET['anio']) ? $_GET['anio'] : (isset($_GET['año']) ? $_GET['año'] : null),
    'mes' => isset($_GET['mes']) ? $_GET['mes'] : null,
    'vendedor' => isset($_GET['vendedor']) ? $_GET['vendedor'] : '',
    'modo' => isset($_GET['modo']) ? $_GET['modo'] : 'vs_anio_ant',
    'periodo_a_desde' => isset($_GET['periodo_a_desde']) ? $_GET['periodo_a_desde'] : null,
    'periodo_a_hasta' => isset($_GET['periodo_a_hasta']) ? $_GET['periodo_a_hasta'] : null,
    'periodo_b_desde' => isset($_GET['periodo_b_desde']) ? $_GET['periodo_b_desde'] : null,
    'periodo_b_hasta' => isset($_GET['periodo_b_hasta']) ? $_GET['periodo_b_hasta'] : null,
));

try {
    switch ($accion) {
        case 'kpis':
            $payload = ControladorDashboardGerencial::ctrKpis($filtros);
            break;
        case 'ventas_mensual':
            $payload = ControladorDashboardGerencial::ctrVentasMensual($filtros);
            break;
        case 'ventas_vs_anio':
            $payload = ControladorDashboardGerencial::ctrVentasVsAnioPasado($filtros);
            break;
        case 'ventas_periodos':
            $payload = ControladorDashboardGerencial::ctrVentasPeriodos($filtros);
            break;
        case 'cobranzas_mensual':
            $payload = ControladorDashboardGerencial::ctrCobranzasMensual($filtros);
            break;
        case 'cobranzas_vs_anio':
            $payload = ControladorDashboardGerencial::ctrCobranzasVsAnioPasado($filtros);
            break;
        case 'cobranzas_periodos':
            $payload = ControladorDashboardGerencial::ctrCobranzasPeriodos($filtros);
            break;
        case 'origen_cobranza':
            $payload = ControladorDashboardGerencial::ctrOrigenCobranza($filtros);
            break;
        case 'pendiente_recuperacion':
            $pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
            $payload = ControladorDashboardGerencial::ctrPendienteRecuperacionDocs($filtros, $pagina);
            break;
        case 'proyeccion_cobranzas':
            $payload = ControladorDashboardGerencial::ctrProyeccionCobranzas($filtros);
            break;
        case 'base':
        default:
            $payload = ControladorDashboardGerencial::ctrDatosBase($filtros);
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
        'msg' => 'Error al consultar el dashboard gerencial',
    ));
}
