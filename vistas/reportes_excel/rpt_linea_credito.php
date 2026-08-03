<?php

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
    session_start();
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/config.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../controladores/inteligencia-comercial.config.php";
require_once "../../modelos/linea-credito.modelo.php";

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "linea_credito")) {
    echo "Acceso no autorizado.";
    exit;
}

$idSolicitud = isset($_GET["solicitud_por"]) ? (int) $_GET["solicitud_por"] : 0;
$solicitante = ModeloLineaCredito::mdlUsuarioActivoPorId($idSolicitud);

if (!$solicitante) {
    echo "Debe indicar quién solicita el reporte (usuario activo).";
    exit;
}

@ini_set("max_execution_time", "300");
@ini_set("memory_limit", "512M");

date_default_timezone_set("America/Lima");
$fecha = date("d-m-Y");
$fechaHora = date("d/m/Y H:i");

$idGenerador = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$generador = $idGenerador > 0 ? ModeloLineaCredito::mdlUsuarioActivoPorId($idGenerador) : null;
$nombreSolicitante = trim((string) $solicitante["nombre"]);
$nombreGenerador = ($generador && !empty($generador["nombre"])) ? trim((string) $generador["nombre"]) : "Sistema";
$nombrePc = (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] !== "")
    ? gethostbyaddr($_SERVER["REMOTE_ADDR"])
    : "";

ModeloLineaCredito::mdlRepararLineasAprobadasDesdeHistorial();
$clientes = ModeloLineaCredito::mdlExportExcelClientes();
$grupos = ModeloLineaCredito::mdlExportExcelGrupos();

function lcExportTexto($valor)
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

function lcExportCalificacion(array $fila)
{
    if (!isset($fila["score_riesgo"]) || $fila["score_riesgo"] === null || $fila["score_riesgo"] === "") {
        return "";
    }

    $score = (float) $fila["score_riesgo"];
    $etiqueta = "Riesgo Alto";

    if (function_exists("icClasificarScore")) {
        $clasificacion = icClasificarScore($score);
        if (!empty($clasificacion["etiqueta"])) {
            $etiqueta = $clasificacion["etiqueta"];
        }
    } elseif ($score >= 90) {
        $etiqueta = "Excelente";
    } elseif ($score >= 80) {
        $etiqueta = "Bueno";
    } elseif ($score >= 70) {
        $etiqueta = "Aceptable";
    } elseif ($score >= 60) {
        $etiqueta = "Riesgo Medio";
    }

    return $etiqueta . " (" . number_format($score, 0) . ")";
}

function lcExportLineaManual($valor)
{
    if ($valor === null || $valor === "") {
        return "";
    }

    return (float) $valor > 0 ? (float) $valor : "";
}

function lcExportEscribirFila($hoja, $filaNum, array $datos, array $estiloCelda, $formatoMoneda, array $colsMoneda, array $colsTexto)
{
    $letras = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P");

    foreach ($datos as $idx => $valor) {
        if (!isset($letras[$idx])) {
            break;
        }

        $col = $letras[$idx];

        if (in_array($col, $colsTexto, true)) {
            $hoja->setCellValueExplicit($col . $filaNum, (string) $valor, PHPExcel_Cell_DataType::TYPE_STRING);
        } else {
            $hoja->setCellValue($col . $filaNum, $valor);
        }
    }

    $ultimaCol = $letras[count($datos) - 1];
    $hoja->getStyle("A" . $filaNum . ":" . $ultimaCol . $filaNum)->applyFromArray($estiloCelda);

    foreach ($colsMoneda as $colMon) {
        if ($hoja->getCell($colMon . $filaNum)->getValue() !== "" && $hoja->getCell($colMon . $filaNum)->getValue() !== null) {
            $hoja->getStyle($colMon . $filaNum)->getNumberFormat()->setFormatCode($formatoMoneda);
        }
    }
}

function lcExportConfigurarEncabezadoDatos($hoja, $titulo, $ultimaCol, array $estiloTitulo, array $cabeceras, array $estiloCabecera)
{
    $hoja->mergeCells("A1:" . $ultimaCol . "1");
    $hoja->setCellValue("A1", $titulo);
    $hoja->getStyle("A1")->applyFromArray($estiloTitulo);
    $hoja->getRowDimension(1)->setRowHeight(24);

    $filaCab = 2;
    foreach ($cabeceras as $col => $tituloCol) {
        $hoja->setCellValue($col . $filaCab, $tituloCol);
        $hoja->getStyle($col . $filaCab)->applyFromArray($estiloCabecera);
    }

    return $filaCab;
}

function lcExportProtegerHojaCompleta(PHPExcel_Worksheet $hoja, $ultimaCol, $ultimaFila)
{
    $ultimaFila = max(1, (int) $ultimaFila);

    $hoja->getStyle("A1:" . $ultimaCol . $ultimaFila)
        ->getProtection()
        ->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);

    $protection = $hoja->getProtection();
    $protection->setSheet(true);
    $protection->setSelectLockedCells(true);
    $protection->setSelectUnlockedCells(true);
    $protection->setFormatCells(false);
    $protection->setFormatColumns(false);
    $protection->setFormatRows(false);
    $protection->setInsertColumns(false);
    $protection->setInsertRows(false);
    $protection->setInsertHyperlinks(false);
    $protection->setDeleteColumns(false);
    $protection->setDeleteRows(false);
    $protection->setSort(false);
    $protection->setAutoFilter(false);
    $protection->setPivotTables(false);
}

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator("Corp. Vasco")
    ->setTitle("Linea de credito");

$estiloTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloCabecera = array(
    "font" => array("bold" => true, "size" => 10, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        "wrap" => true,
    ),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "BBBBBB")),
    ),
);

$estiloCelda = array(
    "font" => array("size" => 10),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "DDDDDD")),
    ),
);

$estiloMetaTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloMetaEtiqueta = array(
    "font" => array("bold" => true, "size" => 11),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "EAF2F8")),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CCCCCC")),
    ),
);

$estiloMetaValor = array(
    "font" => array("size" => 11),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CCCCCC")),
    ),
);

$formatoMoneda = '#,##0.00';
$ultimaCol = "O";
$colsMoneda = array("K", "L", "M", "N", "O");
$colsTexto = array("A", "H");

$cabeceras = array(
    "A" => "RUC",
    "B" => "Razón social",
    "C" => "Dirección",
    "D" => "Departamento",
    "E" => "Provincia",
    "F" => "Distrito",
    "G" => "Correo",
    "H" => "Teléfono",
    "I" => "Categoría",
    "J" => "Calificación",
    "K" => "Línea propuesta",
    "L" => "Línea aprobada",
    "M" => "Venta del año",
    "N" => "Deuda actual",
    "O" => "Deuda vencida",
);

$anchos = array(
    "A" => 14,
    "B" => 34,
    "C" => 30,
    "D" => 16,
    "E" => 16,
    "F" => 16,
    "G" => 26,
    "H" => 16,
    "I" => 16,
    "J" => 18,
    "K" => 14,
    "L" => 16,
    "M" => 14,
    "N" => 14,
    "O" => 14,
);

/* ===========================================================================
 * HOJA 1 — POR CLIENTE (sin protección)
 * ======================================================================== */
$hoja1 = $objPHPExcel->setActiveSheetIndex(0);
$hoja1->setTitle("Por cliente");

$filaCab = lcExportConfigurarEncabezadoDatos(
    $hoja1,
    "LÍNEA DE CRÉDITO — POR CLIENTE (" . $fecha . ")",
    $ultimaCol,
    $estiloTitulo,
    $cabeceras,
    $estiloCabecera
);

$fila = $filaCab + 1;
foreach ($clientes as $item) {
    lcExportEscribirFila($hoja1, $fila, array(
        lcExportTexto($item["ruc"]),
        lcExportTexto($item["razon_social"]),
        lcExportTexto($item["direccion"]),
        lcExportTexto($item["departamento"]),
        lcExportTexto($item["provincia"]),
        lcExportTexto($item["distrito"]),
        lcExportTexto($item["correo"]),
        lcExportTexto($item["telefono"]),
        lcExportTexto($item["categoria_nombre"]),
        lcExportCalificacion($item),
        (float) $item["linea_propuesta"],
        lcExportLineaManual($item["linea_aprobada"]),
        (float) $item["venta_anio"],
        (float) $item["deuda_actual"],
        (float) $item["deuda_vencida"],
    ), $estiloCelda, $formatoMoneda, $colsMoneda, $colsTexto);
    $fila++;
}

foreach ($anchos as $col => $ancho) {
    $hoja1->getColumnDimension($col)->setWidth($ancho);
}
$hoja1->freezePane("A3");

/* ===========================================================================
 * HOJA 2 — POR GRUPO (sin protección)
 * ======================================================================== */
$hoja2 = $objPHPExcel->createSheet(1);
$hoja2->setTitle("Por grupo");

$filaCab = lcExportConfigurarEncabezadoDatos(
    $hoja2,
    "LÍNEA DE CRÉDITO — POR GRUPO (" . $fecha . ")",
    $ultimaCol,
    $estiloTitulo,
    $cabeceras,
    $estiloCabecera
);

$fila = $filaCab + 1;
foreach ($grupos as $item) {
    $nombreGrupo = "[" . lcExportTexto($item["codigo_grupo"]) . "] " . lcExportTexto($item["razon_social"]);

    lcExportEscribirFila($hoja2, $fila, array(
        "",
        $nombreGrupo,
        "",
        "",
        "",
        "",
        "",
        "",
        lcExportTexto($item["categoria_nombre"]),
        lcExportCalificacion($item),
        (float) $item["linea_propuesta"],
        lcExportLineaManual($item["linea_aprobada"]),
        (float) $item["venta_anio"],
        (float) $item["deuda_actual"],
        (float) $item["deuda_vencida"],
    ), $estiloCelda, $formatoMoneda, $colsMoneda, $colsTexto);
    $fila++;
}

foreach ($anchos as $col => $ancho) {
    $hoja2->getColumnDimension($col)->setWidth($ancho);
}
$hoja2->freezePane("A3");

/* ===========================================================================
 * HOJA 3 — METADATOS (única hoja protegida)
 * ======================================================================== */
$hoja3 = $objPHPExcel->createSheet(2);
$hoja3->setTitle("Metadatos");

$hoja3->mergeCells("A1:B1");
$hoja3->setCellValue("A1", "INFORMACIÓN DEL REPORTE — LÍNEA DE CRÉDITO");
$hoja3->getStyle("A1")->applyFromArray($estiloMetaTitulo);
$hoja3->getRowDimension(1)->setRowHeight(26);

$metaFilas = array(
    array("Reporte", "Línea de crédito — cartera activa"),
    array("Fecha de exportación", $fechaHora),
    array("Solicitado por", $nombreSolicitante),
    array("Generado por", $nombreGenerador),
    array("Equipo (PC)", $nombrePc !== "" ? $nombrePc : "No identificado"),
    array("Total clientes", count($clientes)),
    array("Total grupos", count($grupos)),
    array("Hoja Por cliente", "Detalle por RUC / local"),
    array("Hoja Por grupo", "Consolidado por grupo empresarial"),
    array("Nota", "Esta hoja es de solo lectura. Los datos editables están en las otras hojas."),
);

$filaMeta = 3;
foreach ($metaFilas as $metaItem) {
    $hoja3->setCellValue("A" . $filaMeta, $metaItem[0]);
    $hoja3->setCellValue("B" . $filaMeta, $metaItem[1]);
    $hoja3->getStyle("A" . $filaMeta)->applyFromArray($estiloMetaEtiqueta);
    $hoja3->getStyle("B" . $filaMeta)->applyFromArray($estiloMetaValor);
    $filaMeta++;
}

$hoja3->getColumnDimension("A")->setWidth(28);
$hoja3->getColumnDimension("B")->setWidth(52);
lcExportProtegerHojaCompleta($hoja3, "B", $filaMeta - 1);

$objPHPExcel->setActiveSheetIndex(0);

$nombreArchivo = "linea_credito_" . date("Y-m-d") . ".xls";

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
