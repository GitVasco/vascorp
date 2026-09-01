<?php

if (!isset($_SESSION)) {
	session_start();
}

if (function_exists("ob_start")) {
	ob_start();
}
if (function_exists("date_default_timezone_set")) {
	date_default_timezone_set("America/Lima");
}

if (!isset($_SESSION["id"])) {
	rmExplosionExcelFail(403, "Sin sesión");
}

if (isset($_SESSION["tarjetas"]) && (int) $_SESSION["tarjetas"] !== 1) {
	rmExplosionExcelFail(403, "Sin permiso de tarjetas");
}

require_once dirname(__FILE__) . "/../controladores/recetas-modelo.controlador.php";
require_once dirname(__FILE__) . "/../modelos/recetas-modelo.modelo.php";

$resp = ControladorRecetasModelo::ctrPrevisualizarExplosionMatriz($_POST);
$data = (isset($resp["data"]) && is_array($resp["data"])) ? $resp["data"] : array();
$consolidados = isset($data["consolidados"]) && is_array($data["consolidados"]) ? $data["consolidados"] : array();

if (empty($resp["ok"]) && !count($consolidados)) {
	rmExplosionExcelFail(400, isset($resp["mensaje"]) ? $resp["mensaje"] : "No se pudo generar la explosión");
}

require_once dirname(__FILE__) . "/../vistas/reportes_excel/Classes/PHPExcel.php";

$modelo = isset($data["modelo"]) ? (string) $data["modelo"] : "";
$nombre = isset($data["nombre_modelo"]) ? (string) $data["nombre_modelo"] : "";
$version = isset($data["version"]) ? (string) $data["version"] : "";
$estado = isset($data["estado_receta"]) ? (string) $data["estado_receta"] : "";
$colores = isset($data["colores"]) && is_array($data["colores"]) ? $data["colores"] : array();
$tallas = isset($data["tallas"]) && is_array($data["tallas"]) ? $data["tallas"] : array();
$cantidades = isset($data["cantidades"]) && is_array($data["cantidades"]) ? $data["cantidades"] : array();
$detalle = isset($data["detalle"]) && is_array($data["detalle"]) ? $data["detalle"] : array();
$errores = isset($data["errores"]) && is_array($data["errores"]) ? $data["errores"] : array();
$cantidadTotal = isset($data["cantidad_total"]) ? $data["cantidad_total"] : 0;

$qtyMap = array();
foreach ($cantidades as $c) {
	$ck = (isset($c["cod_color"]) ? $c["cod_color"] : "") . "|" . (isset($c["cod_talla"]) ? $c["cod_talla"] : "");
	$qtyMap[$ck] = isset($c["cantidad"]) ? $c["cantidad"] : 0;
}

$headStyle = array(
	"font" => array("bold" => true, "color" => array("rgb" => "FFFFFF")),
	"fill" => array(
		"type" => PHPExcel_Style_Fill::FILL_SOLID,
		"color" => array("rgb" => "3C8DBC"),
	),
);
$telaStyle = array(
	"fill" => array(
		"type" => PHPExcel_Style_Fill::FILL_SOLID,
		"color" => array("rgb" => "DFF0D8"),
	),
);

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco")->setTitle("Explosion de materiales");

/* ========== Hoja 1: Explosión consolidada ========== */
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("Explosion");

$sheet->setCellValue("A1", "Explosión de materiales");
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue("A2", "Modelo");
$sheet->setCellValue("B2", $modelo . ($nombre !== "" && $nombre !== $modelo ? " — " . $nombre : ""));
$sheet->setCellValue("A3", "Versión");
$sheet->setCellValue("B3", $version . ($estado !== "" ? " (" . $estado . ")" : ""));
$sheet->setCellValue("A4", "Prendas");
$sheet->setCellValue("B4", is_numeric($cantidadTotal) ? (float) $cantidadTotal : $cantidadTotal);
$sheet->getStyle("A2:A4")->getFont()->setBold(true);

$headersExp = array(
	"A" => "Cód. MP",
	"B" => "Descripción",
	"C" => "Color MP",
	"D" => "Und",
	"E" => "Consumo total",
	"F" => "Tela principal",
	"G" => "Roles",
);
$filaHead = 6;
foreach ($headersExp as $col => $titulo) {
	$sheet->setCellValue($col . $filaHead, $titulo);
}
$sheet->getStyle("A6:G6")->applyFromArray($headStyle);

$r = 7;
foreach ($consolidados as $c) {
	$sheet->setCellValueExplicit("A" . $r, isset($c["mp_codigo"]) ? (string) $c["mp_codigo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("B" . $r, isset($c["mp_descripcion"]) ? (string) $c["mp_descripcion"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("C" . $r, isset($c["mp_color"]) ? (string) $c["mp_color"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit("D" . $r, isset($c["unidad"]) ? (string) $c["unidad"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$total = isset($c["consumo_total"]) ? $c["consumo_total"] : 0;
	if (is_numeric($total)) {
		$sheet->setCellValue("E" . $r, (float) $total);
	} else {
		$sheet->setCellValueExplicit("E" . $r, (string) $total, PHPExcel_Cell_DataType::TYPE_STRING);
	}
	$esTela = !empty($c["es_tela_principal"]);
	$sheet->setCellValue("F" . $r, $esTela ? "SI" : "");
	$roles = isset($c["roles"]) && is_array($c["roles"]) ? implode(", ", $c["roles"]) : "";
	$sheet->setCellValueExplicit("G" . $r, $roles, PHPExcel_Cell_DataType::TYPE_STRING);
	if ($esTela) {
		$sheet->getStyle("A" . $r . ":G" . $r)->applyFromArray($telaStyle);
	}
	$r++;
}

$sheet->getColumnDimension("A")->setWidth(12);
$sheet->getColumnDimension("B")->setWidth(42);
$sheet->getColumnDimension("C")->setWidth(16);
$sheet->getColumnDimension("D")->setWidth(8);
$sheet->getColumnDimension("E")->setWidth(16);
$sheet->getColumnDimension("F")->setWidth(16);
$sheet->getColumnDimension("G")->setWidth(28);

/* ========== Hoja 2: Matriz de cantidades ========== */
$sheet2 = $objPHPExcel->createSheet();
$sheet2->setTitle("Cantidades");
$sheet2->setCellValue("A1", "Cantidades por color y talla");
$sheet2->getStyle("A1")->getFont()->setBold(true)->setSize(14);
$sheet2->setCellValue("A2", "Modelo");
$sheet2->setCellValue("B2", $modelo);
$sheet2->getStyle("A2")->getFont()->setBold(true);

$sheet2->setCellValue("A4", "Color");
$colIdx = 1;
foreach ($tallas as $t) {
	$colLetter = rmExplosionExcelCol($colIdx);
	$sheet2->setCellValue($colLetter . "4", isset($t["talla"]) && $t["talla"] !== "" ? $t["talla"] : $t["cod_talla"]);
	$colIdx++;
}
$colTotalLetter = rmExplosionExcelCol($colIdx);
$sheet2->setCellValue($colTotalLetter . "4", "Total");
$lastColLetter = $colTotalLetter;
$sheet2->getStyle("A4:" . $lastColLetter . "4")->applyFromArray($headStyle);

$row = 5;
$totCol = array();
foreach ($tallas as $i => $t) {
	$totCol[$i] = 0.0;
}
$granTotal = 0.0;
foreach ($colores as $col) {
	$codColor = isset($col["cod_color"]) ? $col["cod_color"] : "";
	$nomColor = isset($col["color"]) && $col["color"] !== "" ? $col["color"] : $codColor;
	$sheet2->setCellValueExplicit("A" . $row, (string) $nomColor, PHPExcel_Cell_DataType::TYPE_STRING);
	$colIdx = 1;
	$rowTotal = 0.0;
	foreach ($tallas as $i => $t) {
		$codTalla = isset($t["cod_talla"]) ? $t["cod_talla"] : "";
		$ck = $codColor . "|" . $codTalla;
		$val = isset($qtyMap[$ck]) ? (float) $qtyMap[$ck] : 0;
		$colLetter = rmExplosionExcelCol($colIdx);
		if ($val > 0) {
			$sheet2->setCellValue($colLetter . $row, $val);
		}
		$rowTotal += $val;
		$totCol[$i] += $val;
		$colIdx++;
	}
	$sheet2->setCellValue($colTotalLetter . $row, $rowTotal);
	$granTotal += $rowTotal;
	$row++;
}
$sheet2->setCellValue("A" . $row, "Total");
$sheet2->getStyle("A" . $row)->getFont()->setBold(true);
$colIdx = 1;
foreach ($tallas as $i => $t) {
	$colLetter = rmExplosionExcelCol($colIdx);
	$sheet2->setCellValue($colLetter . $row, $totCol[$i]);
	$colIdx++;
}
$sheet2->setCellValue($colTotalLetter . $row, $granTotal);
$sheet2->getStyle("A" . $row . ":" . $colTotalLetter . $row)->getFont()->setBold(true);
$sheet2->getColumnDimension("A")->setWidth(22);
for ($i = 1; $i <= $colIdx; $i++) {
	$sheet2->getColumnDimension(rmExplosionExcelCol($i))->setWidth(10);
}

/* ========== Hoja 3: Detalle por artículo ========== */
$sheet3 = $objPHPExcel->createSheet();
$sheet3->setTitle("Detalle");
$headersDet = array(
	"A" => "Artículo",
	"B" => "Color",
	"C" => "Talla",
	"D" => "Cantidad",
	"E" => "Cód. MP",
	"F" => "Descripción",
	"G" => "Color MP",
	"H" => "Und",
	"I" => "Consumo und.",
	"J" => "Consumo total",
	"K" => "Tela",
	"L" => "Rol",
);
foreach ($headersDet as $col => $titulo) {
	$sheet3->setCellValue($col . "1", $titulo);
}
$sheet3->getStyle("A1:L1")->applyFromArray($headStyle);

$r = 2;
foreach ($detalle as $f) {
	$sheet3->setCellValueExplicit("A" . $r, isset($f["articulo"]) ? (string) $f["articulo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet3->setCellValueExplicit("B" . $r, isset($f["color"]) ? (string) $f["color"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet3->setCellValueExplicit("C" . $r, isset($f["talla"]) ? (string) $f["talla"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$cant = isset($f["cantidad"]) ? $f["cantidad"] : 0;
	$sheet3->setCellValue("D" . $r, is_numeric($cant) ? (float) $cant : $cant);
	$sheet3->setCellValueExplicit("E" . $r, isset($f["mp_codigo"]) ? (string) $f["mp_codigo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet3->setCellValueExplicit("F" . $r, isset($f["mp_descripcion"]) ? (string) $f["mp_descripcion"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet3->setCellValueExplicit("G" . $r, isset($f["mp_color"]) ? (string) $f["mp_color"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet3->setCellValueExplicit("H" . $r, isset($f["unidad"]) ? (string) $f["unidad"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$cu = isset($f["consumo_unitario"]) ? $f["consumo_unitario"] : "";
	if ($cu !== "" && is_numeric($cu)) {
		$sheet3->setCellValue("I" . $r, (float) $cu);
	}
	$ct = isset($f["consumo_total"]) ? $f["consumo_total"] : "";
	if ($ct !== "" && is_numeric($ct)) {
		$sheet3->setCellValue("J" . $r, (float) $ct);
	}
	$sheet3->setCellValue("K" . $r, !empty($f["es_tela_principal"]) ? "SI" : "");
	$sheet3->setCellValueExplicit("L" . $r, isset($f["nombre_rol"]) ? (string) $f["nombre_rol"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$r++;
}
$sheet3->getColumnDimension("A")->setWidth(14);
$sheet3->getColumnDimension("B")->setWidth(16);
$sheet3->getColumnDimension("C")->setWidth(10);
$sheet3->getColumnDimension("D")->setWidth(12);
$sheet3->getColumnDimension("E")->setWidth(12);
$sheet3->getColumnDimension("F")->setWidth(42);
$sheet3->getColumnDimension("G")->setWidth(16);
$sheet3->getColumnDimension("H")->setWidth(8);
$sheet3->getColumnDimension("I")->setWidth(14);
$sheet3->getColumnDimension("J")->setWidth(14);
$sheet3->getColumnDimension("K")->setWidth(8);
$sheet3->getColumnDimension("L")->setWidth(22);

if (count($errores)) {
	$sheet4 = $objPHPExcel->createSheet();
	$sheet4->setTitle("Errores");
	$sheet4->setCellValue("A1", "Artículo");
	$sheet4->setCellValue("B1", "Rol");
	$sheet4->setCellValue("C1", "Tipo");
	$sheet4->setCellValue("D1", "Mensaje");
	$sheet4->getStyle("A1:D1")->applyFromArray($headStyle);
	$r = 2;
	foreach ($errores as $e) {
		$sheet4->setCellValueExplicit("A" . $r, isset($e["articulo"]) ? (string) $e["articulo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet4->setCellValueExplicit("B" . $r, isset($e["rol"]) ? (string) $e["rol"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet4->setCellValueExplicit("C" . $r, isset($e["tipo"]) ? (string) $e["tipo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet4->setCellValueExplicit("D" . $r, isset($e["mensaje"]) ? (string) $e["mensaje"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
		$r++;
	}
	$sheet4->getColumnDimension("A")->setWidth(14);
	$sheet4->getColumnDimension("B")->setWidth(22);
	$sheet4->getColumnDimension("C")->setWidth(16);
	$sheet4->getColumnDimension("D")->setWidth(70);
}

$objPHPExcel->setActiveSheetIndex(0);

$safeModelo = preg_replace('/[^\w.-]+/', "_", $modelo !== "" ? $modelo : "modelo");
$nombreArchivo = "explosion-" . $safeModelo . ($version !== "" ? "-v" . preg_replace('/[^\w.-]+/', "_", $version) : "") . ".xls";

@ini_set("display_errors", "0");
error_reporting(E_ERROR | E_PARSE);

while (ob_get_level()) {
	ob_end_clean();
}

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header("Cache-Control: max-age=0");
header("Pragma: public");

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objWriter->save("php://output");
exit;

function rmExplosionExcelFail($code, $mensaje)
{
	while (ob_get_level()) {
		ob_end_clean();
	}
	http_response_code((int) $code);
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode(array("ok" => false, "mensaje" => $mensaje));
	exit;
}

function rmExplosionExcelCol($index)
{
	$index = (int) $index;
	if ($index < 0) {
		$index = 0;
	}
	$out = "";
	while ($index >= 0) {
		$out = chr(65 + ($index % 26)) . $out;
		$index = (int) floor($index / 26) - 1;
	}
	return $out;
}
