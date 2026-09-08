<?php

if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION["id"])) {
	header("HTTP/1.1 403 Forbidden");
	echo "Sin sesión";
	exit;
}

header("Content-Type: text/html; charset=ISO-8859-1");

include "Classes/PHPExcel.php";
include "estilos.php";
require_once "../../controladores/articulos.controlador.php";
require_once "../../modelos/articulos.modelo.php";

date_default_timezone_set("America/Lima");
$fecha = date("d-m-Y");

$linea = isset($_GET["linea"]) ? trim((string) $_GET["linea"]) : "";
$sublinea = isset($_GET["sublinea"]) ? trim((string) $_GET["sublinea"]) : "";
$mp = isset($_GET["mp"]) ? trim((string) $_GET["mp"]) : "";

if ($linea === "" && $sublinea === "" && $mp === "") {
	header("HTTP/1.1 400 Bad Request");
	echo "Debe indicar al menos un filtro";
	exit;
}

$articulos = ModeloArticulos::mdlMostrarSeguimientoPorReceta($linea, $sublinea, $mp);
$explosion = null;
if ($mp !== "") {
	$explosion = ModeloArticulos::mdlExplosionMpOrdCorteSeguimientoReceta($linea, $sublinea, $mp);
}

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco")->setTitle("Seguimiento recetas");

$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("Articulos");

$sheet->SetCellValue("A1", "Seguimiento recetas");
$sheet->mergeCells("A1:Q1");
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14);

$sheet->SetCellValue("A2", "Fecha:");
$sheet->SetCellValue("B2", $fecha);
$sheet->SetCellValue("A3", "Linea:");
$sheet->SetCellValue("B3", $linea !== "" ? $linea : "—");
$sheet->SetCellValue("D3", "Sublinea:");
$sheet->SetCellValue("E3", $sublinea !== "" ? $sublinea : "—");
$sheet->SetCellValue("G3", "MP:");
$sheet->SetCellValue("H3", $mp !== "" ? $mp : "—");

$headers = array(
	"A" => "Modelo",
	"B" => "Nombre",
	"C" => "Color",
	"D" => "Talla",
	"E" => "Estado",
	"F" => "Proyeccion",
	"G" => "% Avance",
	"H" => "Stock",
	"I" => "Pedidos",
	"J" => "En Taller",
	"K" => "En Servicio",
	"L" => "En Arreglos",
	"M" => "Alm. Corte",
	"N" => "Ord. Corte",
	"O" => "Ult 30d",
	"P" => "Duracion Mes",
	"Q" => "Articulo",
);

$filaHead = 5;
foreach ($headers as $col => $titulo) {
	$sheet->SetCellValue($col . $filaHead, $titulo);
	$sheet->setSharedStyle($borde2, $col . $filaHead);
	$sheet->getStyle($col . $filaHead)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}

$fila = 6;
foreach ($articulos as $value) {
	$sheet->setCellValueExplicit("A$fila", isset($value["modelo"]) ? (string) $value["modelo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->SetCellValue("B$fila", isset($value["nombre"]) ? $value["nombre"] : "");
	$sheet->SetCellValue("C$fila", isset($value["color"]) ? $value["color"] : "");
	$sheet->SetCellValue("D$fila", isset($value["talla"]) ? $value["talla"] : "");
	$sheet->SetCellValue("E$fila", isset($value["estado"]) ? $value["estado"] : "");
	$sheet->SetCellValue("F$fila", isset($value["proyeccion"]) ? $value["proyeccion"] : "");
	$sheet->SetCellValue("G$fila", isset($value["avance"]) ? $value["avance"] : "");
	$sheet->SetCellValue("H$fila", isset($value["stockB"]) ? $value["stockB"] : "");
	$sheet->SetCellValue("I$fila", isset($value["pedidos"]) ? $value["pedidos"] : "");
	$sheet->SetCellValue("J$fila", isset($value["taller"]) ? $value["taller"] : "");
	$sheet->SetCellValue("K$fila", isset($value["servicio"]) ? $value["servicio"] : "");
	$sheet->SetCellValue("L$fila", isset($value["arreglos"]) ? $value["arreglos"] : "");
	$sheet->SetCellValue("M$fila", isset($value["alm_corte"]) ? $value["alm_corte"] : "");
	$sheet->SetCellValue("N$fila", isset($value["ord_corte"]) ? $value["ord_corte"] : "");
	$sheet->SetCellValue("O$fila", isset($value["ult_mes"]) ? $value["ult_mes"] : "");
	$sheet->SetCellValue("P$fila", isset($value["dura_tc"]) ? $value["dura_tc"] : "");
	$sheet->setCellValueExplicit("Q$fila", isset($value["articulo"]) ? (string) $value["articulo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
	$fila++;
}

foreach (range("A", "Q") as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

if ($mp !== "" && is_array($explosion)) {
	$sheet2 = $objPHPExcel->createSheet(1);
	$sheet2->setTitle("MP requerida");

	$resumen = isset($explosion["resumen"]) && is_array($explosion["resumen"]) ? $explosion["resumen"] : array();
	$consolidados = isset($explosion["consolidados"]) && is_array($explosion["consolidados"]) ? $explosion["consolidados"] : array();

	$sheet2->SetCellValue("A1", "Materia prima requerida (ord. corte)");
	$sheet2->mergeCells("A1:F1");
	$sheet2->getStyle("A1")->getFont()->setBold(true)->setSize(14);

	$sheet2->SetCellValue("A2", "MP:");
	$sheet2->SetCellValue("B2", $mp);
	$sheet2->SetCellValue("A3", "Articulos:");
	$sheet2->SetCellValue("B3", isset($resumen["articulos"]) ? $resumen["articulos"] : 0);
	$sheet2->SetCellValue("D3", "Uds. ord. corte:");
	$sheet2->SetCellValue("E3", isset($resumen["unidades_ord_corte"]) ? $resumen["unidades_ord_corte"] : 0);

	$headersMp = array(
		"A" => "MP",
		"B" => "Descripcion",
		"C" => "Color",
		"D" => "Unidad",
		"E" => "Cantidad necesaria",
		"F" => "Rol",
	);
	$filaHeadMp = 5;
	foreach ($headersMp as $col => $titulo) {
		$sheet2->SetCellValue($col . $filaHeadMp, $titulo);
		$sheet2->setSharedStyle($borde2, $col . $filaHeadMp);
		$sheet2->getStyle($col . $filaHeadMp)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	}

	$filaMp = 6;
	if (empty($consolidados)) {
		$sheet2->SetCellValue("A$filaMp", "Sin materia prima calculada");
	} else {
		foreach ($consolidados as $row) {
			$roles = isset($row["roles"]) && is_array($row["roles"]) ? implode(" / ", $row["roles"]) : "";
			if (!empty($row["es_tela_principal"])) {
				$roles = ($roles !== "" ? $roles . " · " : "") . "Tela principal";
			}
			$sheet2->setCellValueExplicit("A$filaMp", isset($row["mp_codigo"]) ? (string) $row["mp_codigo"] : "", PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet2->SetCellValue("B$filaMp", isset($row["mp_descripcion"]) ? $row["mp_descripcion"] : "");
			$sheet2->SetCellValue("C$filaMp", isset($row["mp_color"]) ? $row["mp_color"] : "");
			$sheet2->SetCellValue("D$filaMp", isset($row["unidad"]) ? $row["unidad"] : "");
			$sheet2->SetCellValue("E$filaMp", isset($row["consumo_total"]) ? $row["consumo_total"] : "");
			$sheet2->SetCellValue("F$filaMp", $roles);
			$filaMp++;
		}
	}

	foreach (range("A", "F") as $col) {
		$sheet2->getColumnDimension($col)->setAutoSize(true);
	}
}

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="Seguimiento-recetas-' . $fecha . '.xls"');
$objWriter->save("php://output");
