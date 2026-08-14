<?php

if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION["id"])) {
	http_response_code(403);
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode(array("ok" => false, "mensaje" => "Sin sesión"));
	return;
}

if (isset($_SESSION["tarjetas"]) && (int) $_SESSION["tarjetas"] !== 1) {
	http_response_code(403);
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso de tarjetas"));
	return;
}

$modelo = isset($_POST["modelo"]) ? trim((string) $_POST["modelo"]) : "";
$version = isset($_POST["version"]) ? trim((string) $_POST["version"]) : "";
$estado = isset($_POST["estado"]) ? trim((string) $_POST["estado"]) : "";
$nombre = isset($_POST["nombre"]) ? trim((string) $_POST["nombre"]) : "";
$raw = isset($_POST["filas"]) ? $_POST["filas"] : "";
$filas = is_string($raw) ? json_decode($raw, true) : $raw;

$idReceta = 0;
if (isset($_POST["id_receta"])) {
	$idReceta = (int) $_POST["id_receta"];
} elseif (isset($_GET["id_receta"])) {
	$idReceta = (int) $_GET["id_receta"];
}

if ($idReceta > 0) {
	require_once dirname(__FILE__) . "/../controladores/recetas-modelo.controlador.php";
	require_once dirname(__FILE__) . "/../modelos/recetas-modelo.modelo.php";
	$armado = ControladorRecetasModelo::ctrArmarFilasTarjetasExcel($idReceta);
	if (empty($armado["ok"])) {
		http_response_code(400);
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode(array("ok" => false, "mensaje" => isset($armado["mensaje"]) ? $armado["mensaje"] : "No se pudo armar el Excel"));
		return;
	}
	$d = isset($armado["data"]) && is_array($armado["data"]) ? $armado["data"] : array();
	$modelo = isset($d["modelo"]) ? (string) $d["modelo"] : $modelo;
	$version = isset($d["version"]) ? (string) $d["version"] : $version;
	$estado = isset($d["estado"]) ? (string) $d["estado"] : $estado;
	$nombre = isset($d["nombre"]) ? (string) $d["nombre"] : $nombre;
	$filas = isset($d["filas"]) ? $d["filas"] : array();
}

if (!is_array($filas) || !count($filas)) {
	http_response_code(400);
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode(array("ok" => false, "mensaje" => "No hay filas para exportar"));
	return;
}

if (count($filas) > 50000) {
	$filas = array_slice($filas, 0, 50000);
}

require_once dirname(__FILE__) . "/../vistas/reportes_excel/Classes/PHPExcel.php";

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco")->setTitle("Tarjetas por receta");
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("Tarjetas");

$sheet->setCellValue("A1", "Modelo");
$sheet->setCellValue("B1", $modelo);
$sheet->setCellValue("A2", "Nombre");
$sheet->setCellValue("B2", $nombre);
$sheet->setCellValue("A3", "Versión");
$sheet->setCellValue("B3", $version);
$sheet->setCellValue("A4", "Estado");
$sheet->setCellValue("B4", $estado);
$sheet->getStyle("A1:A4")->getFont()->setBold(true);

$headers = array(
	"A" => "Artículo",
	"B" => "Color",
	"C" => "Talla",
	"D" => "Sublínea",
	"E" => "Cód. MP",
	"F" => "MP (nombre)",
	"G" => "Color MP",
	"H" => "Consumo",
	"I" => "Und",
);
$filaHead = 6;
foreach ($headers as $col => $titulo) {
	$sheet->setCellValue($col . $filaHead, $titulo);
}
$sheet->getStyle("A6:I6")->applyFromArray(array(
	"font" => array("bold" => true, "color" => array("rgb" => "FFFFFF")),
	"fill" => array(
		"type" => PHPExcel_Style_Fill::FILL_SOLID,
		"color" => array("rgb" => "3C8DBC"),
	),
));

$r = 7;
foreach ($filas as $f) {
	if (!is_array($f)) {
		continue;
	}
	$sheet->setCellValueExplicit("A" . $r, isset($f["articulo"]) ? (string) $f["articulo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("B" . $r, isset($f["color"]) ? (string) $f["color"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("C" . $r, isset($f["talla"]) ? (string) $f["talla"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("D" . $r, isset($f["sublinea"]) ? (string) $f["sublinea"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("E" . $r, isset($f["mp_codigo"]) ? (string) $f["mp_codigo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("F" . $r, isset($f["mp_nombre"]) ? (string) $f["mp_nombre"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("G" . $r, isset($f["color_mp"]) ? (string) $f["color_mp"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$consumo = isset($f["consumo"]) ? $f["consumo"] : "";
	if ($consumo !== "" && is_numeric($consumo)) {
		$sheet->setCellValue("H" . $r, (float) $consumo);
	} else {
		$sheet->setCellValueExplicit("H" . $r, (string) $consumo, PHPExcel_Cell_DataType::TYPE_STRING);
	}
	$sheet->setCellValueExplicit("I" . $r, isset($f["unidad"]) ? (string) $f["unidad"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$r++;
}

$sheet->getColumnDimension("A")->setWidth(14);
$sheet->getColumnDimension("B")->setWidth(16);
$sheet->getColumnDimension("C")->setWidth(10);
$sheet->getColumnDimension("D")->setWidth(12);
$sheet->getColumnDimension("E")->setWidth(12);
$sheet->getColumnDimension("F")->setWidth(42);
$sheet->getColumnDimension("G")->setWidth(16);
$sheet->getColumnDimension("H")->setWidth(12);
$sheet->getColumnDimension("I")->setWidth(8);

$safeModelo = preg_replace('/[^\w.-]+/', "_", $modelo !== "" ? $modelo : "modelo");
$nombreArchivo = "tarjetas-" . $safeModelo . ($version !== "" ? "-v" . preg_replace('/[^\w.-]+/', "_", $version) : "") . ".xls";

while (ob_get_level()) {
	ob_end_clean();
}

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header("Cache-Control: max-age=0");

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objWriter->save("php://output");
exit;
