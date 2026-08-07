<?php

if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
	header("HTTP/1.1 403 Forbidden");
	echo "Sin permiso de producción";
	exit;
}

date_default_timezone_set("America/Lima");

require_once dirname(__FILE__) . "/Classes/PHPExcel.php";
require_once dirname(__FILE__) . "/../../modelos/conexion.php";
require_once dirname(__FILE__) . "/../../modelos/programacion-taller-semana.modelo.php";
require_once dirname(__FILE__) . "/../../controladores/programacion-taller-semana.controlador.php";

$anio = isset($_GET["anio"]) ? (int) $_GET["anio"] : 0;
$semana = isset($_GET["semana"]) ? (int) $_GET["semana"] : 0;
if ($anio < 1 || $semana < 1) {
	$act = ModeloProgramacionTallerSemana::mdlSemanaActual();
	$anio = (int) $act["anio"];
	$semana = (int) $act["semana"];
}

$filtros = array(
	"cod_sector" => isset($_GET["taller"]) ? trim((string) $_GET["taller"]) : "",
	"modelo" => isset($_GET["modelo"]) ? trim((string) $_GET["modelo"]) : "",
	"nivel" => isset($_GET["nivel"]) ? trim((string) $_GET["nivel"]) : ""
);

$rango = ModeloProgramacionTallerSemana::mdlRangoSemana($anio, $semana);
$rows = ModeloProgramacionTallerSemana::mdlExportDetalleSemana($anio, $semana, $filtros);
if (!is_array($rows)) {
	$rows = array();
}

$mapNiveles = ControladorProgramacionTallerSemana::ctrMapaNiveles();

usort($rows, function ($a, $b) use ($mapNiveles) {
	$oa = isset($mapNiveles[$a["nivel"]]["orden"]) ? (int) $mapNiveles[$a["nivel"]]["orden"] : 99;
	$ob = isset($mapNiveles[$b["nivel"]]["orden"]) ? (int) $mapNiveles[$b["nivel"]]["orden"] : 99;
	if ($oa !== $ob) {
		return $oa - $ob;
	}
	$cmp = strcmp((string) $a["modelo"], (string) $b["modelo"]);
	if ($cmp !== 0) {
		return $cmp;
	}
	$cmp = strcmp((string) $a["cod_color"], (string) $b["cod_color"]);
	if ($cmp !== 0) {
		return $cmp;
	}
	$cmp = strcmp((string) $a["cod_talla"], (string) $b["cod_talla"]);
	if ($cmp !== 0) {
		return $cmp;
	}
	return strcmp((string) $a["talla"], (string) $b["talla"]);
});

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco");
$objPHPExcel->getProperties()->setTitle("Programacion taller semana {$semana}-{$anio}");

$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("Semana {$semana}");

$estiloCab = array(
	"font" => array("bold" => true, "color" => array("rgb" => "FFFFFF"), "size" => 10),
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
	"font" => array("size" => 10),
	"borders" => array(
		"allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN)
	),
	"alignment" => array(
		"vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER
	)
);

$sheet->setCellValue("A1", "Programación semanal por taller");
$sheet->mergeCells("A1:N1");
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(13);

$rangoTxt = $rango
	? ($rango["fecha_inicio"] . " → " . $rango["fecha_fin"])
	: "";
$sheet->setCellValue("A2", "Semana {$semana} / {$anio}" . ($rangoTxt !== "" ? "  ({$rangoTxt})" : ""));
$sheet->mergeCells("A2:N2");

$headers = array(
	"A4" => "Urgencia",
	"B4" => "Cod",
	"C4" => "Modelo",
	"D4" => "Color",
	"E4" => "Cod Color",
	"F4" => "Talla",
	"G4" => "Stock Disponib",
	"H4" => "Almacén de Corte",
	"I4" => "Orden de Corte",
	"J4" => "IND",
	"K4" => "IND2",
	"L4" => "Destino",
	"M4" => "Semana",
	"N4" => "Suma de ORDEN"
);
foreach ($headers as $cell => $txt) {
	$sheet->setCellValue($cell, $txt);
}
$sheet->getStyle("A4:N4")->applyFromArray($estiloCab);
$sheet->getRowDimension(4)->setRowHeight(20);

$fila = 5;
foreach ($rows as $r) {
	$nivelId = isset($r["nivel"]) ? $r["nivel"] : "";
	$nivelNom = isset($mapNiveles[$nivelId]["nombre"])
		? strtoupper($mapNiveles[$nivelId]["nombre"])
		: strtoupper($nivelId);
	$nivelColor = isset($mapNiveles[$nivelId]["color"])
		? str_replace("#", "", $mapNiveles[$nivelId]["color"])
		: "DDDDDD";
	if (strlen($nivelColor) !== 6) {
		$nivelColor = "DDDDDD";
	}

	$sheet->setCellValue("A{$fila}", $nivelNom);
	$sheet->setCellValueExplicit("B{$fila}", (string) $r["modelo"], PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValue("C{$fila}", $r["nombre_modelo"]);
	$sheet->setCellValue("D{$fila}", $r["color"]);
	$sheet->setCellValueExplicit("E{$fila}", (string) $r["cod_color"], PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValue("F{$fila}", $r["talla"] !== "" && $r["talla"] !== null ? $r["talla"] : $r["cod_talla"]);
	$sheet->setCellValue("G{$fila}", (int) $r["stock_disponible"]);
	$sheet->setCellValue("H{$fila}", (int) $r["alm_corte"]);
	$sheet->setCellValue("I{$fila}", (int) $r["ord_corte"]);
	$sheet->setCellValue("J{$fila}", $r["ind"] === null || $r["ind"] === "" ? null : (float) $r["ind"]);
	$sheet->setCellValue("K{$fila}", $r["ind2"] === null || $r["ind2"] === "" ? null : (float) $r["ind2"]);
	$sheet->setCellValue("L{$fila}", $r["nom_sector"]);
	$sheet->setCellValue("M{$fila}", "SEMANA " . (int) $r["semana"]);
	$sheet->setCellValue("N{$fila}", (int) $r["suma_orden"]);

	$sheet->getStyle("A{$fila}:N{$fila}")->applyFromArray($estiloFila);
	$sheet->getStyle("A{$fila}")->applyFromArray(array(
		"fill" => array(
			"type" => PHPExcel_Style_Fill::FILL_SOLID,
			"color" => array("rgb" => $nivelColor)
		),
		"font" => array("bold" => true, "size" => 10)
	));
	$sheet->getStyle("I{$fila}")->getFont()->getColor()->setRGB("C00000");
	$sheet->getStyle("I{$fila}")->getFont()->setBold(true);
	if ((int) $r["alm_corte"] > 0) {
		$sheet->getStyle("H{$fila}")->getFont()->getColor()->setRGB("0070C0");
	}

	$fila++;
}

foreach (range("A", "N") as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}
$sheet->freezePane("A5");

if ($fila === 5) {
	$sheet->setCellValue("A5", "Sin líneas programadas (con saldo en alm. corte u OC) para esta semana/filtros");
	$sheet->mergeCells("A5:N5");
}

$nombre = "programacion_taller_S{$semana}_{$anio}.xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment;filename=\"{$nombre}\"");
header("Cache-Control: max-age=0");

$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
$writer->save("php://output");
exit;
