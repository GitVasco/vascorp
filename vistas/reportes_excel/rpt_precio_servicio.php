<?php

header('Content-Type: text/html; charset=ISO-8859-1');

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/servicio.controlador.php";
require_once "../../modelos/servicio.modelo.php";

date_default_timezone_set('America/Lima');
$fecha = date("d-m-Y");

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco");
$objPHPExcel->getProperties()->setTitle("Precio servicios");

$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("PRECIO SERVICIOS");

$sheet->SetCellValue("A1", "PRECIO SERVICIOS");
$sheet->SetCellValue("D1", "Fecha: " . $fecha);
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(13);

$cabecera = array("A" => "N°", "B" => "TALLER", "C" => "MODELO", "D" => "NOMBRE", "E" => "PRECIO DOC");
$fila = 3;
foreach ($cabecera as $col => $titulo) {
    $sheet->SetCellValue("$col$fila", $titulo);
}
$sheet->getStyle("A$fila:E$fila")->getFont()->setBold(true);
$sheet->getStyle("A$fila:E$fila")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('D7DBDD');
$sheet->getStyle("A$fila:E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$precios = ControladorServicios::ctrMostrarPrecioServicios(null, null);

$cont = 0;
foreach ($precios as $p) {
    $cont++;
    $fila++;
    $sheet->SetCellValue("A$fila", $cont);
    $sheet->SetCellValue("B$fila", $p["taller"] . " - " . $p["nom_sector"]);
    $sheet->setCellValueExplicit("C$fila", $p["modelo"], PHPExcel_Cell_DataType::TYPE_STRING);
    $sheet->SetCellValue("D$fila", $p["nombre"]);
    $sheet->SetCellValue("E$fila", $p["precio_doc"]);
}

if ($cont > 0) {
    $sheet->getStyle("A3:E$fila")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
}

foreach (array("A" => 6, "B" => 32, "C" => 14, "D" => 30, "E" => 14) as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="PRECIO SERVICIOS - ' . $fecha . '.xls"');

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objWriter->save('php://output');
