<?php

// Evita que warnings/notices de la librería antigua corrompan el binario.
@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
    session_start();
}

/*
* LIBRERIA PHPEXCEL + CONTROLADOR / MODELO
*/
include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/config.php";
require_once "../../controladores/descuentos-compuestos.controlador.php";
require_once "../../modelos/descuentos-compuestos.modelo.php";

/*
* CONTROL DE ACCESO (misma regla que la pantalla)
*/
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo "Acceso no autorizado.";
    exit;
}

date_default_timezone_set("America/Lima");
$fecha = date("d-m-Y");

/*
* DATOS
*/
$listado = ControladorDescuentosCompuestos::ctrListarDescuentosCompuestos("", 100000, "");
$resumenCliente = ControladorDescuentosCompuestos::ctrResumenPorCliente();

/*
* INSTANCIA
*/
$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator("Corp. Vasco")
    ->setTitle("Descuentos Compuestos ESSO");

/*
* ESTILOS
*/
$estiloTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array("horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
);

$estiloCabecera = array(
    "font" => array("bold" => true, "size" => 10, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array("horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER, "wrap" => true),
    "borders" => array("allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "BBBBBB"))),
);

$estiloCelda = array(
    "font" => array("size" => 10),
    "borders" => array("allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "DDDDDD"))),
);

$estiloTotal = array(
    "font" => array("bold" => true, "size" => 10),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "EAF2F8")),
    "borders" => array("allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "BBBBBB"))),
);

$formatoMoneda = '#,##0.00';

/* ===========================================================================
 * HOJA 1 — LISTADO COMPLETO
 * ======================================================================== */
$hoja1 = $objPHPExcel->setActiveSheetIndex(0);
$hoja1->setTitle("Listado");

$hoja1->mergeCells("A1:K1");
$hoja1->setCellValue("A1", "DESCUENTOS COMPUESTOS ESSO - LISTADO  (" . $fecha . ")");
$hoja1->getStyle("A1")->applyFromArray($estiloTitulo);
$hoja1->getRowDimension(1)->setRowHeight(24);

$cabeceras1 = array(
    "A" => "Tipo Doc",
    "B" => "Nro Documento",
    "C" => "Fecha",
    "D" => "Cód. Cliente",
    "E" => "Cliente",
    "F" => "Monto",
    "G" => "Nota original",
    "H" => "Nota estándar",
    "I" => "Monto %1",
    "J" => "Monto %2",
    "K" => "Estado",
);

$filaCab = 3;
foreach ($cabeceras1 as $col => $titulo) {
    $hoja1->setCellValue($col . $filaCab, $titulo);
    $hoja1->getStyle($col . $filaCab)->applyFromArray($estiloCabecera);
}

$fila = $filaCab + 1;
$sumMonto = 0;
$sumP1 = 0;
$sumP2 = 0;

foreach ($listado as $item) {
    $monto = (float) $item["monto"];
    $m1 = $item["monto_pct1_final"] !== null ? (float) $item["monto_pct1_final"] : null;
    $m2 = $item["monto_pct2_final"] !== null ? (float) $item["monto_pct2_final"] : null;

    $estadoTxt = $item["origen_nota"] === "MANUAL" ? "CONFIRMADO"
        : ($item["origen_nota"] === "AUTO" ? "SUGERIDO" : "POR REVISAR");

    $hoja1->setCellValueExplicit("A" . $fila, (string) $item["tipo_doc"], PHPExcel_Cell_DataType::TYPE_STRING);
    $hoja1->setCellValueExplicit("B" . $fila, (string) $item["num_cta"], PHPExcel_Cell_DataType::TYPE_STRING);
    $hoja1->setCellValue("C" . $fila, $item["fecha"]);
    $hoja1->setCellValueExplicit("D" . $fila, (string) $item["cliente"], PHPExcel_Cell_DataType::TYPE_STRING);
    $hoja1->setCellValue("E" . $fila, utf8_encode((string) $item["nombre_cliente"]));
    $hoja1->setCellValue("F" . $fila, $monto);
    $hoja1->setCellValue("G" . $fila, utf8_encode((string) $item["notas_original"]));
    $hoja1->setCellValue("H" . $fila, $item["nota_estandar_final"]);

    if ($m1 !== null) {
        $hoja1->setCellValue("I" . $fila, $m1);
        $sumP1 += $m1;
    } else {
        $hoja1->setCellValue("I" . $fila, "-");
    }

    if ($m2 !== null) {
        $hoja1->setCellValue("J" . $fila, $m2);
        $sumP2 += $m2;
    } else {
        $hoja1->setCellValue("J" . $fila, "-");
    }

    $hoja1->setCellValue("K" . $fila, $estadoTxt);

    $hoja1->getStyle("A" . $fila . ":K" . $fila)->applyFromArray($estiloCelda);
    $hoja1->getStyle("F" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
    $hoja1->getStyle("I" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
    $hoja1->getStyle("J" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);

    $sumMonto += $monto;
    $fila++;
}

// Fila de totales
$hoja1->setCellValue("E" . $fila, "TOTALES");
$hoja1->setCellValue("F" . $fila, $sumMonto);
$hoja1->setCellValue("I" . $fila, $sumP1);
$hoja1->setCellValue("J" . $fila, $sumP2);
$hoja1->getStyle("A" . $fila . ":K" . $fila)->applyFromArray($estiloTotal);
$hoja1->getStyle("F" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
$hoja1->getStyle("I" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
$hoja1->getStyle("J" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
$hoja1->getStyle("E" . $fila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$anchos1 = array("A" => 10, "B" => 18, "C" => 12, "D" => 12, "E" => 38, "F" => 14, "G" => 16, "H" => 16, "I" => 14, "J" => 14, "K" => 14);
foreach ($anchos1 as $col => $ancho) {
    $hoja1->getColumnDimension($col)->setWidth($ancho);
}
$hoja1->freezePane("A4");

/* ===========================================================================
 * HOJA 2 — RESUMEN POR CLIENTE
 * ======================================================================== */
$hoja2 = $objPHPExcel->createSheet(1);
$hoja2->setTitle("Resumen por cliente");

$hoja2->mergeCells("A1:I1");
$hoja2->setCellValue("A1", "RESUMEN POR CLIENTE  (" . $fecha . ")");
$hoja2->getStyle("A1")->applyFromArray($estiloTitulo);
$hoja2->getRowDimension(1)->setRowHeight(24);

$cabeceras2 = array(
    "A" => "Cód. Cliente",
    "B" => "Cliente",
    "C" => "Registros",
    "D" => "Sugeridos",
    "E" => "Confirmados",
    "F" => "Por revisar",
    "G" => "Monto total",
    "H" => "Monto base (1er %)",
    "I" => "Monto adicional (2do %)",
);

$filaCab2 = 3;
foreach ($cabeceras2 as $col => $titulo) {
    $hoja2->setCellValue($col . $filaCab2, $titulo);
    $hoja2->getStyle($col . $filaCab2)->applyFromArray($estiloCabecera);
}

$fila2 = $filaCab2 + 1;
$tTotal = 0;
$tSug = 0;
$tConf = 0;
$tRev = 0;
$tMonto = 0;
$tBase = 0;
$tAdic = 0;

foreach ($resumenCliente as $item) {
    $hoja2->setCellValueExplicit("A" . $fila2, (string) $item["codigo"], PHPExcel_Cell_DataType::TYPE_STRING);
    $hoja2->setCellValue("B" . $fila2, utf8_encode((string) $item["nombre"]));
    $hoja2->setCellValue("C" . $fila2, (int) $item["total"]);
    $hoja2->setCellValue("D" . $fila2, (int) $item["sugeridos"]);
    $hoja2->setCellValue("E" . $fila2, (int) $item["confirmados"]);
    $hoja2->setCellValue("F" . $fila2, (int) $item["por_revisar"]);
    $hoja2->setCellValue("G" . $fila2, (float) $item["monto_total"]);
    $hoja2->setCellValue("H" . $fila2, (float) $item["monto_base"]);
    $hoja2->setCellValue("I" . $fila2, (float) $item["monto_adicional"]);

    $hoja2->getStyle("A" . $fila2 . ":I" . $fila2)->applyFromArray($estiloCelda);
    $hoja2->getStyle("G" . $fila2 . ":I" . $fila2)->getNumberFormat()->setFormatCode($formatoMoneda);

    $tTotal += (int) $item["total"];
    $tSug += (int) $item["sugeridos"];
    $tConf += (int) $item["confirmados"];
    $tRev += (int) $item["por_revisar"];
    $tMonto += (float) $item["monto_total"];
    $tBase += (float) $item["monto_base"];
    $tAdic += (float) $item["monto_adicional"];
    $fila2++;
}

$hoja2->setCellValue("B" . $fila2, "TOTALES");
$hoja2->setCellValue("C" . $fila2, $tTotal);
$hoja2->setCellValue("D" . $fila2, $tSug);
$hoja2->setCellValue("E" . $fila2, $tConf);
$hoja2->setCellValue("F" . $fila2, $tRev);
$hoja2->setCellValue("G" . $fila2, $tMonto);
$hoja2->setCellValue("H" . $fila2, $tBase);
$hoja2->setCellValue("I" . $fila2, $tAdic);
$hoja2->getStyle("A" . $fila2 . ":I" . $fila2)->applyFromArray($estiloTotal);
$hoja2->getStyle("G" . $fila2 . ":I" . $fila2)->getNumberFormat()->setFormatCode($formatoMoneda);
$hoja2->getStyle("B" . $fila2)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$anchos2 = array("A" => 12, "B" => 40, "C" => 11, "D" => 11, "E" => 12, "F" => 11, "G" => 16, "H" => 18, "I" => 20);
foreach ($anchos2 as $col => $ancho) {
    $hoja2->getColumnDimension($col)->setWidth($ancho);
}
$hoja2->freezePane("A4");

/*
* SALIDA (Excel 2003 .xls, mismo writer que el resto de reportes del sistema)
*/
$objPHPExcel->setActiveSheetIndex(0);

$nombreArchivo = "descuentos_compuestos_esso_" . $fecha . ".xls";

// Limpia cualquier salida previa (espacios, BOM, warnings) que dañaría el archivo.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header("Cache-Control: max-age=0");
header("Pragma: public");

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objWriter->save("php://output");
exit;
