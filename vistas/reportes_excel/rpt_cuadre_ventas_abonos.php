<?php

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
    session_start();
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../modelos/conexion.php";
require_once "../../modelos/cuadre-ventas.modelo.php";
require_once "../../controladores/cuadre-ventas.controlador.php";

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo "Acceso no autorizado.";
    exit;
}

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "cuadre_ventas")) {
    echo "Acceso no autorizado.";
    exit;
}

if (!ControladorCuadreVentas::ctrPuedeProcesar() && !ControladorCuadreVentas::ctrPuede("validar")) {
    echo "Sin permiso para este reporte.";
    exit;
}

date_default_timezone_set("America/Lima");

$fecha = isset($_GET["fecha"]) ? $_GET["fecha"] : "";
$res = ControladorCuadreVentas::ctrFilasExcelAbonosProcesar($fecha);
if (empty($res["ok"])) {
    echo isset($res["msg"]) ? $res["msg"] : "No se pudo armar el Excel.";
    exit;
}

$fechaOk = $res["fecha"];
$periodo = isset($res["periodo"]) ? $res["periodo"] : "";
$porHoja = isset($res["por_hoja"]) && is_array($res["por_hoja"]) ? $res["por_hoja"] : array();
if (empty($porHoja) && !empty($res["filas"])) {
    $porHoja = array("ABONOS" => $res["filas"]);
}

function cvAbExcelTexto($valor)
{
    if ($valor === null) {
        return "";
    }
    $texto = (string) $valor;
    if (function_exists("mb_convert_encoding")) {
        return mb_convert_encoding($texto, "UTF-8", "UTF-8");
    }
    return $texto;
}

function cvAbExcelFecha($ymd)
{
    $ymd = substr(trim((string) $ymd), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
        return $ymd;
    }
    return substr($ymd, 8, 2) . "/" . substr($ymd, 5, 2) . "/" . substr($ymd, 0, 4);
}

function cvAbNombreHoja($nombre)
{
    $nombre = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', " ", (string) $nombre);
    $nombre = trim($nombre);
    if ($nombre === "") {
        $nombre = "Hoja";
    }
    if (function_exists("mb_substr")) {
        return mb_substr($nombre, 0, 31, "UTF-8");
    }
    return substr($nombre, 0, 31);
}

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator("Corp. Vasco")
    ->setTitle("Abonos cuadre de ventas")
    ->setSubject("Abonos " . $periodo . " " . $fechaOk);

$estiloCabecera = array(
    "font" => array("bold" => true, "size" => 10, "color" => array("rgb" => "FFFFFF"), "name" => "Calibri"),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "1F4E79")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        "wrap" => true,
    ),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "16365C")),
    ),
);

$estiloCelda = array(
    "font" => array("size" => 10, "name" => "Calibri"),
    "alignment" => array(
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP,
        "wrap" => true,
    ),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CCCCCC")),
    ),
);

$estiloMonto = array(
    "font" => array("size" => 10, "name" => "Calibri", "color" => array("rgb" => "C00000"), "bold" => true),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CCCCCC")),
    ),
);

$cabeceras = array(
    "A" => "PERIODO",
    "B" => "Fecha",
    "C" => "Descripción operación",
    "D" => "Monto",
    "E" => "Operación",
    "F" => "DOCUMENTO",
    "G" => "TIPO DOCUMENTO",
    "H" => "RUC",
    "I" => "CLIENTE",
    "J" => "SALDO",
    "K" => "OBSERVACIONES",
);

$anchos = array(
    "A" => 14,
    "B" => 12,
    "C" => 28,
    "D" => 12,
    "E" => 16,
    "F" => 18,
    "G" => 14,
    "H" => 14,
    "I" => 36,
    "J" => 10,
    "K" => 28,
);

$formatoMoneda = '#,##0.00';
$primera = true;
$idxHoja = 0;

if (empty($porHoja)) {
    $porHoja = array("SIN DATOS" => array());
}

foreach ($porHoja as $nombreHoja => $filasHoja) {
    if ($primera) {
        $hoja = $objPHPExcel->getActiveSheet();
        $primera = false;
    } else {
        $hoja = $objPHPExcel->createSheet();
    }
    $hoja->setTitle(cvAbNombreHoja($nombreHoja));
    $idxHoja++;

    foreach ($cabeceras as $col => $tituloCol) {
        $hoja->setCellValue($col . "1", $tituloCol);
        $hoja->getStyle($col . "1")->applyFromArray($estiloCabecera);
    }
    $hoja->getRowDimension(1)->setRowHeight(24);

    $fila = 2;
    foreach ($filasHoja as $item) {
        $nDocs = isset($item["n_docs"]) ? (int) $item["n_docs"] : 1;
        if ($nDocs < 1) {
            $nDocs = 1;
        }
        $alto = 15 + (($nDocs - 1) * 13);
        if ($alto > 90) {
            $alto = 90;
        }

        $hoja->setCellValueExplicit("A" . $fila, cvAbExcelTexto($item["periodo"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("B" . $fila, cvAbExcelFecha($item["fecha"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("C" . $fila, cvAbExcelTexto($item["descripcion"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValue("D" . $fila, (float) $item["monto"]);
        $hoja->setCellValueExplicit("E" . $fila, cvAbExcelTexto($item["operacion"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("F" . $fila, cvAbExcelTexto($item["documento"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("G" . $fila, cvAbExcelTexto($item["tipo_documento"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("H" . $fila, cvAbExcelTexto($item["ruc"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("I" . $fila, cvAbExcelTexto($item["cliente"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("J" . $fila, cvAbExcelTexto($item["saldo"]), PHPExcel_Cell_DataType::TYPE_STRING);
        $hoja->setCellValueExplicit("K" . $fila, cvAbExcelTexto($item["observaciones"]), PHPExcel_Cell_DataType::TYPE_STRING);

        $hoja->getStyle("A" . $fila . ":K" . $fila)->applyFromArray($estiloCelda);
        $hoja->getStyle("D" . $fila)->applyFromArray($estiloMonto);
        $hoja->getStyle("D" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
        $hoja->getStyle("E" . $fila)->getFont()->getColor()->setRGB("C00000");
        $hoja->getRowDimension($fila)->setRowHeight($alto);
        $fila++;
    }

    foreach ($anchos as $col => $ancho) {
        $hoja->getColumnDimension($col)->setWidth($ancho);
    }
    $hoja->freezePane("A2");
    if ($fila > 2) {
        $hoja->setAutoFilter("A1:K1");
    }
}

$objPHPExcel->setActiveSheetIndex(0);

$nombreArchivo = "cuadre_abonos_" . $fechaOk . ".xls";

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
