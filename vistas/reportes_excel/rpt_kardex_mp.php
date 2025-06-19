<?php

header('Content-Type: text/html; charset=ISO-8859-1');
date_default_timezone_set('America/Lima');

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/materiaprima.controlador.php";
require_once "../../modelos/materiaprima.modelo.php";

isset($_GET["codigo"]) ? $codigo = $_GET["codigo"] : $codigo = null;
isset($_GET["ano"]) ? $ano = $_GET["ano"] : $ano = null;
$ano_ant = $ano - 1;

$materiaprima = ModeloMateriaPrima::mdlMostrarMateriaPrima2($codigo);

$objPHPExcel = new PHPExcel();

$objPHPExcel->getProperties()->setCreator("Corp. Vasco"); //autor
$objPHPExcel->getProperties()->setTitle("Kardex Materia Prima"); //titulo

$objPHPExcel->createSheet(0);
$objPHPExcel->setActiveSheetIndex(0);

$objPHPExcel->getActiveSheet()->setTitle("Kardex Materia Prima");

$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToPage(true);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);

$marginV = 0.5 / 3.54; // 0.5 centimetros
$objPHPExcel->getActiveSheet()->getPageMargins()->setTop($marginV);
$objPHPExcel->getActiveSheet()->getPageMargins()->setBottom($marginV);
$objPHPExcel->getActiveSheet()->getPageMargins()->setLeft($marginV);
$objPHPExcel->getActiveSheet()->getPageMargins()->setRight($marginV);

$fila = 1;
$objPHPExcel->getActiveSheet()->mergeCells("A$fila:G$fila");
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Kardex Materia Prima ' . date('d/m/Y') . " - " . $materiaprima["despro"] . " - " . $ano);
$objPHPExcel->getActiveSheet()->getStyle("A$fila")->getFont()->setSize(16);
$objPHPExcel->getActiveSheet()->getStyle("A$fila")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// Datos de la materia prima
$fila = 3;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Código:');
$objPHPExcel->getActiveSheet()->SetCellValueExplicit("B$fila", $materiaprima["codpro"], PHPExcel_Cell_DataType::TYPE_STRING);
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'Descripción:');
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", $materiaprima["despro"]);
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'Cod. Fab:');
$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", $materiaprima["codfab"]);

$fila = 4;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Color:');
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", $materiaprima["color"]);
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'Unidad:');
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", $materiaprima["unidad"]);
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'Stock Actual:');
$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", $materiaprima["stock"]);

$fila = 6;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Documento');
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'Mes');
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'Fecha');
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'Razón Social');
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'Stock Inicial');
$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", 'Ingresos');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Salidas');

// Aplicar negrita a los títulos de la cabecera
$objPHPExcel->getActiveSheet()->getStyle("A3:H4")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("A6:G6")->getFont()->setBold(true);

$kardex = ControladorMateriaPrima::ctrMostrarKardexMP($codigo, $ano, $ano_ant);

usort($kardex, function ($a, $b) {
    return strtotime($a['FecEmi']) - strtotime($b['FecEmi']);
});

$totalIngresos = 0;
$totalSalidas = 0;

foreach ($kardex as $i => $item) {
    $fila++;

    $objPHPExcel->getActiveSheet()->SetCellValue("A$fila", $item["nDoc"]);
    $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", $item["Fecha"]);
    $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", $item["FecEmi"]);
    $objPHPExcel->getActiveSheet()->SetCellValue("D$fila", $item["Razon"]);
    $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", $item["StockIni"]);
    $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", $item["CanIng"]);
    $objPHPExcel->getActiveSheet()->SetCellValue("G$fila", $item["CanSal"]);

    $totalIngresos += $item["CanIng"];
    $totalSalidas += $item["CanSal"];
}

$fila++;
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'Totales:');
$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", $totalIngresos);
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", $totalSalidas);


$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);


$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel); //Escribir archivo

header("Content-Type: application/vnd.ms-excel");

header("Content-Disposition: attachment;filename=\"Kardex_Materia_Prima_$codigo.xls\"");
header("Cache-Control: max-age=0");

$objWriter->save("php://output"); // Guardar el archivo en la salida estándar