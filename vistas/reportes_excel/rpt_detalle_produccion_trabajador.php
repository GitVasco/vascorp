<?php

date_default_timezone_set('America/Lima');

$inicio = isset($_GET["inicio"]) ? $_GET["inicio"] : "";
$fin = isset($_GET["fin"]) ? $_GET["fin"] : "";
$trabajador = isset($_GET["trabajador"]) ? $_GET["trabajador"] : "";
$sector = isset($_GET["sector"]) ? $_GET["sector"] : null;

if ($inicio === "" || $fin === "" || $trabajador === "") {
    header('HTTP/1.1 400 Bad Request');
    die('Parámetros incompletos.');
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../modelos/produccion.modelo.php";
require_once "../../controladores/produccion.controlador.php";
require_once "rpt_detalle_produccion_lib.php";

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco");
$objPHPExcel->getProperties()->setTitle("DetalleProduccionTrabajador");

$estilos = rptDetalleProduccionEstilos();
$objPHPExcel->setActiveSheetIndex(0);

if (!rptDetalleProduccionEscribirHoja($objPHPExcel->getActiveSheet(), $inicio, $fin, $trabajador, $estilos)) {
    header('HTTP/1.1 404 Not Found');
    die('Trabajador no encontrado.');
}

$info = ControladorProduccion::ctrRptDetalleProduccionTrabajadorInfo($inicio, $fin, $trabajador);
$objPHPExcel->getActiveSheet()->setTitle(rptDetalleProduccionNombreHoja($trabajador, $info['nombre']));

$tsI = strtotime($inicio);
$tsF = strtotime($fin);
$nombreArchivo = 'Detalle produccion ' . $trabajador . ' ' . date('Y-m-d', $tsI) . ' al ' . date('Y-m-d', $tsF);

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xls"');

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objWriter->save('php://output');
