<?php

date_default_timezone_set('America/Lima');

$inicio = isset($_GET["inicio"]) ? $_GET["inicio"] : "";
$fin = isset($_GET["fin"]) ? $_GET["fin"] : "";
$sector = isset($_GET["sector"]) ? $_GET["sector"] : null;

if ($sector === "null" || $sector === "") {
    $sector = null;
}

if ($inicio === "" || $fin === "") {
    header('HTTP/1.1 400 Bad Request');
    die('Parámetros incompletos.');
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../modelos/produccion.modelo.php";
require_once "../../controladores/produccion.controlador.php";
require_once "rpt_detalle_produccion_lib.php";

$trabajadores = ControladorProduccion::ctrRptDetalleProduccionTrabajadoresIds($inicio, $fin, $sector);

if (count($trabajadores) === 0) {
    header('HTTP/1.1 404 Not Found');
    die('No hay trabajadores con producción en el periodo.');
}

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco");
$objPHPExcel->getProperties()->setTitle("DetalleProduccionTodos");

$estilos = rptDetalleProduccionEstilos();
$hojaIndex = 0;

foreach ($trabajadores as $codTrab) {
    $info = ControladorProduccion::ctrRptDetalleProduccionTrabajadorInfo($inicio, $fin, $codTrab);
    if (!$info) {
        continue;
    }

    if ($hojaIndex > 0) {
        $objPHPExcel->createSheet($hojaIndex);
    }

    $objPHPExcel->setActiveSheetIndex($hojaIndex);
    $sheet = $objPHPExcel->getActiveSheet();
    rptDetalleProduccionEscribirHoja($sheet, $inicio, $fin, $codTrab, $estilos);
    $sheet->setTitle(rptDetalleProduccionNombreHoja($codTrab, $info['nombre']));
    $hojaIndex++;
}

if ($hojaIndex === 0) {
    header('HTTP/1.1 404 Not Found');
    die('No se pudo generar detalle para los trabajadores.');
}

$objPHPExcel->setActiveSheetIndex(0);

$tsI = strtotime($inicio);
$tsF = strtotime($fin);
$nombreArchivo = 'Detalle produccion todos ' . date('Y-m-d', $tsI) . ' al ' . date('Y-m-d', $tsF);

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xls"');

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objWriter->save('php://output');
