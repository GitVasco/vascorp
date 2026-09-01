<?php

session_start();

date_default_timezone_set('America/Lima');

require_once '../../controladores/reportes-generales-v2.config.php';
require_once '../../controladores/reportes-generales-v2.controlador.php';
require_once '../../controladores/reportes-generales-v2.servicio.php';
require_once '../../controladores/reportes-generales-v2.export.lib.php';

if (!isset($_SESSION['iniciarSesion']) || $_SESSION['iniciarSesion'] !== 'ok') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Sesion no valida';
    exit;
}

if (!ReportesGeneralesV2Config::puedeAcceder()) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Sin permiso';
    exit;
}

$formato = isset($_GET['formato']) ? strtolower(trim((string) $_GET['formato'])) : '';
if ($formato !== 'pdf' && $formato !== 'xlsx') {
    header('HTTP/1.1 400 Bad Request');
    echo 'Formato no valido';
    exit;
}

$filtros = ControladorReportesGeneralesV2::ctrParseFiltros(array(
    'reporte' => isset($_GET['reporte']) ? $_GET['reporte'] : '',
    'orden1' => isset($_GET['orden1']) ? $_GET['orden1'] : '',
    'orden2' => isset($_GET['orden2']) ? $_GET['orden2'] : '',
    'tip_doc' => isset($_GET['tip_doc']) ? $_GET['tip_doc'] : '',
    'canc' => isset($_GET['canc']) ? $_GET['canc'] : '',
    'cli' => isset($_GET['cli']) ? $_GET['cli'] : '',
    'vend' => isset($_GET['vend']) ? $_GET['vend'] : '',
    'banco' => isset($_GET['banco']) ? $_GET['banco'] : '',
    'inicio' => isset($_GET['inicio']) ? $_GET['inicio'] : '',
    'fin' => isset($_GET['fin']) ? $_GET['fin'] : '',
));

$tpl = ReportesGeneralesV2Config::find($filtros['reporte']);
if ($tpl === null || $tpl['estado'] !== 'listo') {
    header('HTTP/1.1 404 Not Found');
    echo 'Reporte no disponible';
    exit;
}

$cap = ReportesGeneralesV2Config::exportCapacidades($tpl);
if ($formato === 'pdf' && empty($cap['pdf'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'PDF no disponible';
    exit;
}
if ($formato === 'xlsx' && empty($cap['excel'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Excel no disponible';
    exit;
}

$payload = ReportesGeneralesV2Servicio::exportPayload($filtros['reporte'], $filtros);
if (!is_array($payload) || empty($payload['ok'])) {
    header('HTTP/1.1 400 Bad Request');
    $msg = (is_array($payload) && isset($payload['error'])) ? $payload['error'] : 'No se pudo generar el reporte';
    echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    exit;
}

ReportesGeneralesV2ExportLib::emit($formato, $payload);
