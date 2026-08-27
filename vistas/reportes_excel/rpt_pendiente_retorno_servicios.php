<?php

if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
	header("HTTP/1.1 403 Forbidden");
	echo "Acceso no autorizado.";
	exit;
}

if (!isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
	header("HTTP/1.1 403 Forbidden");
	echo "Sin permiso de producción";
	exit;
}

date_default_timezone_set("America/Lima");

require_once dirname(__FILE__) . "/Classes/PHPExcel.php";
require_once dirname(__FILE__) . "/../../modelos/conexion.php";
require_once dirname(__FILE__) . "/../../modelos/servicio.modelo.php";

$filas = ModeloServicios::mdlPendienteRetornoServicios();
if (!is_array($filas)) {
	$filas = array();
}

function pendRetTexto($valor)
{
	if ($valor === null) {
		return "";
	}
	return (string) $valor;
}

function pendRetFechaExcel($ymd)
{
	$ymd = substr(trim((string) $ymd), 0, 10);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd) || $ymd === "0000-00-00") {
		return null;
	}
	$t = strtotime($ymd . " 00:00:00");
	if ($t === false) {
		return null;
	}
	return PHPExcel_Shared_Date::PHPToExcel($t);
}

$estiloCab = array(
	"font" => array("bold" => true, "color" => array("rgb" => "FFFFFF"), "size" => 10, "name" => "Calibri"),
	"fill" => array(
		"type" => PHPExcel_Style_Fill::FILL_SOLID,
		"color" => array("rgb" => "3C8DBC")
	),
	"alignment" => array(
		"horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		"vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER
	),
	"borders" => array(
		"allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN)
	)
);
$estiloFila = array(
	"font" => array("size" => 10, "name" => "Calibri"),
	"borders" => array(
		"allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN)
	),
	"alignment" => array(
		"vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER
	)
);

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
	->setCreator("Corp. Vasco")
	->setTitle("Pendiente retorno servicios");

$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("Pendiente retorno");

$cols = array(
	"A" => "Taller",
	"B" => "Servicio",
	"C" => "Fecha emision",
	"D" => "Articulo",
	"E" => "Color",
	"F" => "Talla",
	"G" => "Cantidad"
);
foreach ($cols as $col => $txt) {
	$sheet->setCellValue($col . "1", $txt);
}
$sheet->getStyle("A1:G1")->applyFromArray($estiloCab);
$sheet->getRowDimension(1)->setRowHeight(20);

$fila = 2;
foreach ($filas as $r) {
	$sheet->setCellValueExplicit("A{$fila}", pendRetTexto($r["taller"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("B{$fila}", pendRetTexto($r["codigo"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$excelFecha = pendRetFechaExcel(isset($r["fecha_emision"]) ? $r["fecha_emision"] : "");
	if ($excelFecha === null) {
		$sheet->setCellValue("C{$fila}", pendRetTexto($r["fecha_emision"]));
	} else {
		$sheet->setCellValue("C{$fila}", $excelFecha);
		$sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode("DD/MM/YYYY");
	}
	$sheet->setCellValueExplicit("D{$fila}", pendRetTexto($r["articulo"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValue("E{$fila}", pendRetTexto($r["color"]));
	$sheet->setCellValue("F{$fila}", pendRetTexto($r["talla"]));
	$sheet->setCellValue("G{$fila}", (int) $r["cantidad"]);
	$sheet->getStyle("A{$fila}:G{$fila}")->applyFromArray($estiloFila);
	$fila++;
}

if ($fila === 2) {
	$sheet->setCellValue("A2", "No hay saldo pendiente de retorno");
	$sheet->mergeCells("A2:G2");
}

foreach (range("A", "G") as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}
$sheet->freezePane("A2");
$sheet->setAutoFilter("A1:G1");

$nombre = "pendiente_retorno_servicios.xls";
header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header("Cache-Control: max-age=0");

$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
$writer->save("php://output");
exit;
