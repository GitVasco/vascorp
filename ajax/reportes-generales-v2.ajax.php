<?php

session_start();

date_default_timezone_set('America/Lima');

require_once '../controladores/reportes-generales-v2.config.php';
require_once '../controladores/reportes-generales-v2.controlador.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['iniciarSesion']) || $_SESSION['iniciarSesion'] !== 'ok') {
    echo json_encode(array('ok' => false, 'error' => 'Sesión no válida'));
    exit;
}

if (!ReportesGeneralesV2Config::puedeAcceder()) {
    echo json_encode(array('ok' => false, 'error' => 'Sin permiso para reportes generales v2'));
    exit;
}

$accion = isset($_REQUEST['accion']) ? trim((string) $_REQUEST['accion']) : 'preview';

$filtros = ControladorReportesGeneralesV2::ctrParseFiltros(array(
    'reporte' => isset($_REQUEST['reporte']) ? $_REQUEST['reporte'] : '',
    'orden1' => isset($_REQUEST['orden1']) ? $_REQUEST['orden1'] : '',
    'orden2' => isset($_REQUEST['orden2']) ? $_REQUEST['orden2'] : '',
    'tip_doc' => isset($_REQUEST['tip_doc']) ? $_REQUEST['tip_doc'] : '',
    'canc' => isset($_REQUEST['canc']) ? $_REQUEST['canc'] : '',
    'cli' => isset($_REQUEST['cli']) ? $_REQUEST['cli'] : '',
    'vend' => isset($_REQUEST['vend']) ? $_REQUEST['vend'] : '',
    'banco' => isset($_REQUEST['banco']) ? $_REQUEST['banco'] : '',
    'inicio' => isset($_REQUEST['inicio']) ? $_REQUEST['inicio'] : '',
    'fin' => isset($_REQUEST['fin']) ? $_REQUEST['fin'] : '',
));

if ($accion === 'catalogo') {
    echo json_encode(array('ok' => true) + ControladorReportesGeneralesV2::ctrCatalogoJson(), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($accion === 'preview') {
    echo json_encode(ControladorReportesGeneralesV2::ctrPreview($filtros), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($accion === 'export') {
    $formato = isset($_REQUEST['formato']) ? strtolower(trim((string) $_REQUEST['formato'])) : '';
    if ($formato === 'excel') {
        $formato = 'xlsx';
    }
    if ($formato !== 'xlsx' && $formato !== 'pdf') {
        echo json_encode(array('ok' => false, 'error' => 'Formato no válido.'), JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(ControladorReportesGeneralesV2::ctrExport($formato, $filtros), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array('ok' => false, 'error' => 'Acción no válida.'), JSON_UNESCAPED_UNICODE);
