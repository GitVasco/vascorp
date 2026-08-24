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

$anio = isset($_GET["anio"]) ? (int) $_GET["anio"] : (int) date("Y");
if ($anio < 2000 || $anio > 2100) {
	$anio = (int) date("Y");
}

$data = ModeloServicios::mdlHistorialAnualServicios($anio);
$cabeceras = isset($data["cabeceras"]) && is_array($data["cabeceras"]) ? $data["cabeceras"] : array();
$detalles = isset($data["detalles"]) && is_array($data["detalles"]) ? $data["detalles"] : array();

function histServTexto($valor)
{
	if ($valor === null) {
		return "";
	}
	return (string) $valor;
}

function histServFechaExcel($ymd)
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

function histServEscribirFecha($sheet, $celda, $ymd)
{
	$excel = histServFechaExcel($ymd);
	if ($excel === null) {
		$sheet->setCellValue($celda, "");
		return;
	}
	$sheet->setCellValue($celda, $excel);
	$sheet->getStyle($celda)->getNumberFormat()->setFormatCode("DD/MM/YYYY");
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
	->setTitle("Historial servicios " . $anio);

$objPHPExcel->setActiveSheetIndex(0);
$hojaCab = $objPHPExcel->getActiveSheet();
$hojaCab->setTitle("Servicios " . $anio);

$colsCab = array(
	"A" => "Codigo",
	"B" => "Guia",
	"C" => "Taller",
	"D" => "Usuario",
	"E" => "Fecha envio",
	"F" => "Mes envio",
	"G" => "Enviado",
	"H" => "Cerrado",
	"I" => "Saldo",
	"J" => "Primer cierre",
	"K" => "Ultimo cierre",
	"L" => "Fecha fin",
	"M" => "Dias",
	"N" => "Estado"
);
foreach ($colsCab as $col => $txt) {
	$hojaCab->setCellValue($col . "1", $txt);
}
$hojaCab->getStyle("A1:N1")->applyFromArray($estiloCab);
$hojaCab->getRowDimension(1)->setRowHeight(20);

$fila = 2;
foreach ($cabeceras as $r) {
	$hojaCab->setCellValueExplicit("A{$fila}", histServTexto($r["codigo"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$hojaCab->setCellValueExplicit("B{$fila}", histServTexto($r["guia"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$hojaCab->setCellValue("C{$fila}", histServTexto($r["taller"]));
	$hojaCab->setCellValue("D{$fila}", histServTexto($r["usuario"]));
	histServEscribirFecha($hojaCab, "E{$fila}", isset($r["fecha_envio"]) ? $r["fecha_envio"] : "");
	$hojaCab->setCellValue("F{$fila}", (int) $r["mes_envio"]);
	$hojaCab->setCellValue("G{$fila}", (int) $r["enviado"]);
	$hojaCab->setCellValue("H{$fila}", (int) $r["cerrado"]);
	$hojaCab->setCellValue("I{$fila}", (int) $r["saldo"]);
	histServEscribirFecha($hojaCab, "J{$fila}", isset($r["fecha_primer_cierre"]) ? $r["fecha_primer_cierre"] : "");
	histServEscribirFecha($hojaCab, "K{$fila}", isset($r["fecha_ultimo_cierre"]) ? $r["fecha_ultimo_cierre"] : "");
	histServEscribirFecha($hojaCab, "L{$fila}", isset($r["fecha_fin"]) ? $r["fecha_fin"] : "");
	if ($r["dias"] === null || $r["dias"] === "") {
		$hojaCab->setCellValue("M{$fila}", "");
	} else {
		$hojaCab->setCellValue("M{$fila}", (int) $r["dias"]);
	}
	$hojaCab->setCellValue("N{$fila}", histServTexto($r["estado"]));
	$hojaCab->getStyle("A{$fila}:N{$fila}")->applyFromArray($estiloFila);
	$fila++;
}

if ($fila === 2) {
	$hojaCab->setCellValue("A2", "No hay servicios enviados en " . $anio);
	$hojaCab->mergeCells("A2:N2");
}

foreach (range("A", "N") as $col) {
	$hojaCab->getColumnDimension($col)->setAutoSize(true);
}
$hojaCab->freezePane("A2");
$hojaCab->setAutoFilter("A1:N1");

$hojaDet = $objPHPExcel->createSheet(1);
$hojaDet->setTitle("Detalle " . $anio);

$colsDet = array(
	"A" => "Codigo",
	"B" => "Guia",
	"C" => "Taller",
	"D" => "Usuario",
	"E" => "Fecha envio",
	"F" => "Mes envio",
	"G" => "Articulo",
	"H" => "Modelo",
	"I" => "Nombre",
	"J" => "Color",
	"K" => "Talla",
	"L" => "Enviado",
	"M" => "Cerrado",
	"N" => "Saldo",
	"O" => "Primer cierre",
	"P" => "Ultimo cierre",
	"Q" => "Fecha fin",
	"R" => "Dias",
	"S" => "Estado"
);
foreach ($colsDet as $col => $txt) {
	$hojaDet->setCellValue($col . "1", $txt);
}
$hojaDet->getStyle("A1:S1")->applyFromArray($estiloCab);
$hojaDet->getRowDimension(1)->setRowHeight(20);

$fila = 2;
foreach ($detalles as $r) {
	$hojaDet->setCellValueExplicit("A{$fila}", histServTexto($r["codigo"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$hojaDet->setCellValueExplicit("B{$fila}", histServTexto($r["guia"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$hojaDet->setCellValue("C{$fila}", histServTexto($r["taller"]));
	$hojaDet->setCellValue("D{$fila}", histServTexto($r["usuario"]));
	histServEscribirFecha($hojaDet, "E{$fila}", isset($r["fecha_envio"]) ? $r["fecha_envio"] : "");
	$hojaDet->setCellValue("F{$fila}", (int) $r["mes_envio"]);
	$hojaDet->setCellValueExplicit("G{$fila}", histServTexto($r["articulo"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$hojaDet->setCellValueExplicit("H{$fila}", histServTexto($r["modelo"]), PHPExcel_Cell_DataType::TYPE_STRING);
	$hojaDet->setCellValue("I{$fila}", histServTexto($r["nombre"]));
	$hojaDet->setCellValue("J{$fila}", histServTexto($r["color"]));
	$hojaDet->setCellValue("K{$fila}", histServTexto($r["talla"]));
	$hojaDet->setCellValue("L{$fila}", (int) $r["enviado"]);
	$hojaDet->setCellValue("M{$fila}", (int) $r["cerrado"]);
	$hojaDet->setCellValue("N{$fila}", (int) $r["saldo"]);
	histServEscribirFecha($hojaDet, "O{$fila}", isset($r["fecha_primer_cierre"]) ? $r["fecha_primer_cierre"] : "");
	histServEscribirFecha($hojaDet, "P{$fila}", isset($r["fecha_ultimo_cierre"]) ? $r["fecha_ultimo_cierre"] : "");
	histServEscribirFecha($hojaDet, "Q{$fila}", isset($r["fecha_fin"]) ? $r["fecha_fin"] : "");
	if ($r["dias"] === null || $r["dias"] === "") {
		$hojaDet->setCellValue("R{$fila}", "");
	} else {
		$hojaDet->setCellValue("R{$fila}", (int) $r["dias"]);
	}
	$hojaDet->setCellValue("S{$fila}", histServTexto($r["estado"]));
	$hojaDet->getStyle("A{$fila}:S{$fila}")->applyFromArray($estiloFila);
	$fila++;
}

if ($fila === 2) {
	$hojaDet->setCellValue("A2", "No hay detalle de servicios enviados en " . $anio);
	$hojaDet->mergeCells("A2:S2");
}

foreach (range("A", "S") as $col) {
	$hojaDet->getColumnDimension($col)->setAutoSize(true);
}
$hojaDet->freezePane("A2");
$hojaDet->setAutoFilter("A1:S1");

$objPHPExcel->setActiveSheetIndex(0);

$nombre = "historial_servicios_" . $anio . ".xls";
header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header("Cache-Control: max-age=0");

$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
$writer->save("php://output");
exit;
