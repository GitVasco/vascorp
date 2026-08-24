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

date_default_timezone_set("America/Lima");

$fecha = isset($_GET["fecha"]) ? $_GET["fecha"] : "";
$res = ControladorCuadreVentas::ctrFilasExcel($fecha);
if (empty($res["ok"])) {
    echo isset($res["msg"]) ? $res["msg"] : "No se pudo armar el Excel.";
    exit;
}

$fechaOk = $res["fecha"];
$filas = $res["filas"];
$periodo = substr($fechaOk, 5, 2) . "-" . substr($fechaOk, 0, 4);

function cvExcelTexto($valor)
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

function cvExcelFecha($ymd)
{
    $ymd = substr(trim((string) $ymd), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
        return $ymd;
    }
    return substr($ymd, 8, 2) . "/" . substr($ymd, 5, 2) . "/" . substr($ymd, 0, 4);
}

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator("Corp. Vasco")
    ->setTitle("Cuadre de ventas")
    ->setSubject("Cuadre de ventas " . $periodo);

$estiloTitulo = array(
    "font" => array("bold" => true, "size" => 12, "name" => "Calibri"),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloCabecera = array(
    "font" => array("bold" => true, "size" => 10, "color" => array("rgb" => "FFFFFF"), "name" => "Calibri"),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        "wrap" => true,
    ),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "2E6B8A")),
    ),
);

$estiloCelda = array(
    "font" => array("size" => 10, "name" => "Calibri"),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CCCCCC")),
    ),
);

$cabeceras = array(
    "A" => "Periodo",
    "B" => "Fecha del día",
    "C" => "Responsable",
    "D" => "Grupo de marca",
    "E" => "Vendedor",
    "F" => "Distrito / Departamento",
    "G" => "Código cliente",
    "H" => "Documento cliente",
    "I" => "Nombre cliente",
    "J" => "Tipo de documento de venta",
    "K" => "Código tipo documento",
    "L" => "Nro documento",
    "M" => "Fecha de emisión",
    "N" => "Monto",
    "O" => "Fecha de pago",
    "P" => "Monto abonado",
    "Q" => "Forma de pago",
    "R" => "Nro OP",
    "S" => "Estado",
);

$anchos = array(
    "A" => 12,
    "B" => 14,
    "C" => 18,
    "D" => 16,
    "E" => 28,
    "F" => 22,
    "G" => 14,
    "H" => 16,
    "I" => 36,
    "J" => 22,
    "K" => 12,
    "L" => 18,
    "M" => 14,
    "N" => 12,
    "O" => 14,
    "P" => 14,
    "Q" => 22,
    "R" => 24,
    "S" => 14,
);

$colsTexto = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "O", "Q", "R", "S");
$colsMoneda = array("N", "P");
$ultimaCol = "S";
$formatoMoneda = '#,##0.00';

$hoja = $objPHPExcel->getActiveSheet();
$hoja->setTitle("Cuadre");
$hoja->mergeCells("A1:" . $ultimaCol . "1");
$hoja->setCellValue("A1", "CUADRE DE VENTAS — " . $periodo . " — " . cvExcelFecha($fechaOk));
$hoja->getStyle("A1")->applyFromArray($estiloTitulo);
$hoja->getRowDimension(1)->setRowHeight(22);

foreach ($cabeceras as $col => $tituloCol) {
    $hoja->setCellValue($col . "2", $tituloCol);
    $hoja->getStyle($col . "2")->applyFromArray($estiloCabecera);
}
$hoja->getRowDimension(2)->setRowHeight(28);

$fila = 3;
foreach ($filas as $item) {
    $datos = array(
        cvExcelTexto($item["periodo"]),
        cvExcelFecha($item["fecha_dia"]),
        cvExcelTexto($item["responsable"]),
        cvExcelTexto($item["marca"]),
        cvExcelTexto($item["vendedor"]),
        cvExcelTexto($item["zona"]),
        cvExcelTexto($item["codigo_cliente"]),
        cvExcelTexto($item["documento_cliente"]),
        cvExcelTexto($item["nombre_cliente"]),
        cvExcelTexto($item["tipo_documento"]),
        cvExcelTexto($item["codigo_tipo"]),
        cvExcelTexto($item["nro_documento"]),
        cvExcelFecha($item["fecha_emision"]),
        (float) $item["monto"],
        cvExcelFecha($item["fecha_pago"]),
        (float) $item["monto_abonado"],
        cvExcelTexto($item["forma_pago"]),
        cvExcelTexto($item["nro_op"]),
        cvExcelTexto(isset($item["estado"]) ? $item["estado"] : ""),
    );
    $letras = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S");
    foreach ($datos as $idx => $valor) {
        $col = $letras[$idx];
        if (in_array($col, $colsTexto, true)) {
            $hoja->setCellValueExplicit($col . $fila, (string) $valor, PHPExcel_Cell_DataType::TYPE_STRING);
        } else {
            $hoja->setCellValue($col . $fila, $valor);
        }
    }
    $hoja->getStyle("A" . $fila . ":" . $ultimaCol . $fila)->applyFromArray($estiloCelda);
    foreach ($colsMoneda as $colMon) {
        $hoja->getStyle($colMon . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
    }
    $fila++;
}

foreach ($anchos as $col => $ancho) {
    $hoja->getColumnDimension($col)->setWidth($ancho);
}
$hoja->freezePane("A3");
$hoja->setAutoFilter("A2:" . $ultimaCol . "2");

$nombreArchivo = "cuadre_ventas_" . $fechaOk . ".xls";

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
