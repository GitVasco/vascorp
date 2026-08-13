<?php

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
    session_start();
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/permisos-modulos.config.php";
require_once "../../modelos/cuentas.modelo.php";
require_once "../../modelos/linea-credito.modelo.php";

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo "Acceso no autorizado.";
    exit;
}

if (
    !isset($_SESSION["cuenta"]) ||
    (int) $_SESSION["cuenta"] !== 1 ||
    !function_exists("usuarioPuedeVerModulo") ||
    !usuarioPuedeVerModulo("gestion_comercial", "estado_cuenta_gerencia")
) {
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

$anios = 2;
$graciaDias = 8;
$datos = ModeloCuentas::mdlClasificacionMorosidadClientes($anios, $graciaDias);
$parametros = $datos["parametros"];
$clientes = $datos["clientes"];
$resumen = $datos["resumen"];

$leyendaClases = array(
    "Puntual" => "Pagan a tiempo (hasta {$graciaDias} días después del vencimiento)",
    "Regular" => "Demoran más de {$graciaDias} y hasta 30 días",
    "Moroso" => "Demoran más de 30 y hasta 60 días",
    "Crítico" => "Demoran más de 60 días",
);

$coloresClase = array(
    "Puntual" => array("fondo" => "ABEBC6", "texto" => "1E8449"),
    "Regular" => array("fondo" => "F9E79F", "texto" => "7D6608"),
    "Moroso" => array("fondo" => "F5B041", "texto" => "7E5109"),
    "Crítico" => array("fondo" => "E74C3C", "texto" => "FFFFFF"),
);

function cmExportTexto($valor)
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

function cmExportEstiloFondo($rgb, $negrita = false, $textoRgb = null)
{
    $estilo = array(
        "fill" => array(
            "type" => PHPExcel_Style_Fill::FILL_SOLID,
            "color" => array("rgb" => $rgb),
        ),
    );

    if ($negrita || $textoRgb !== null) {
        $estilo["font"] = array();
        if ($negrita) {
            $estilo["font"]["bold"] = true;
        }
        if ($textoRgb !== null) {
            $estilo["font"]["color"] = array("rgb" => $textoRgb);
        }
    }

    return $estilo;
}

function cmExportProtegerHoja(PHPExcel_Worksheet $hoja, $ultimaCol, $ultimaFila)
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
    ->setTitle("Clasificación de morosidad de clientes");

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

$estiloTotal = array(
    "font" => array("bold" => true, "size" => 10),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "D5D8DC")),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "BBBBBB")),
    ),
);

$estiloNota = array(
    "font" => array("size" => 9, "italic" => true, "color" => array("rgb" => "555555")),
    "alignment" => array("wrap" => true, "vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP),
);

$estiloMetaTitulo = $estiloTitulo;

$estiloMetaSeccion = array(
    "font" => array("bold" => true, "size" => 11, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "5D6D7E")),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
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
    "alignment" => array("wrap" => true, "vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP),
);

$formatoMoneda = '#,##0.00';
$totalClientes = count($clientes);
$totalDeuda = 0.0;
$totalDocs = 0;
foreach ($clientes as $item) {
    $totalDeuda += (float) $item["deuda_actual"];
    $totalDocs += (int) $item["docs_evaluados"];
}

/* ===========================================================================
 * HOJA 1 — RESUMEN
 * ======================================================================== */
$hoja1 = $objPHPExcel->setActiveSheetIndex(0);
$hoja1->setTitle("Resumen");

$hoja1->mergeCells("A1:F1");
$hoja1->setCellValue("A1", "CLASIFICACIÓN DE MOROSIDAD — RESUMEN (" . $fecha . ")");
$hoja1->getStyle("A1")->applyFromArray($estiloTitulo);
$hoja1->getRowDimension(1)->setRowHeight(24);

$cabResumen = array(
    "A" => "Clasificación",
    "B" => "Criterio",
    "C" => "Clientes",
    "D" => "%",
    "E" => "Días promedio",
    "F" => "Deuda actual",
);

foreach ($cabResumen as $col => $tituloCol) {
    $hoja1->setCellValue($col . "2", $tituloCol);
    $hoja1->getStyle($col . "2")->applyFromArray($estiloCabecera);
}

$fila = 3;
foreach ($resumen as $item) {
    $nombre = $item["clasificacion"];
    $nClientes = (int) $item["clientes"];
    $pct = $totalClientes > 0 ? round(($nClientes / $totalClientes) * 100, 1) : 0;

    $hoja1->setCellValue("A" . $fila, $nombre);
    $hoja1->setCellValue("B" . $fila, isset($leyendaClases[$nombre]) ? $leyendaClases[$nombre] : "");
    $hoja1->setCellValue("C" . $fila, $nClientes);
    $hoja1->setCellValue("D" . $fila, $pct);
    $hoja1->setCellValue("E" . $fila, (float) $item["dias_promedio"]);
    $hoja1->setCellValue("F" . $fila, (float) $item["deuda_actual"]);
    $hoja1->getStyle("A" . $fila . ":F" . $fila)->applyFromArray($estiloCelda);
    $hoja1->getStyle("F" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
    $hoja1->getStyle("D" . $fila)->getNumberFormat()->setFormatCode("0.0");
    $hoja1->getStyle("E" . $fila)->getNumberFormat()->setFormatCode("0.0");

    if (isset($coloresClase[$nombre])) {
        $hoja1->getStyle("A" . $fila)->applyFromArray(
            cmExportEstiloFondo($coloresClase[$nombre]["fondo"], true, $coloresClase[$nombre]["texto"])
        );
    }

    $fila++;
}

$hoja1->setCellValue("A" . $fila, "Total");
$hoja1->setCellValue("B" . $fila, "");
$hoja1->setCellValue("C" . $fila, $totalClientes);
$hoja1->setCellValue("D" . $fila, $totalClientes > 0 ? 100 : 0);
$hoja1->setCellValue("E" . $fila, "");
$hoja1->setCellValue("F" . $fila, $totalDeuda);
$hoja1->getStyle("A" . $fila . ":F" . $fila)->applyFromArray($estiloTotal);
$hoja1->getStyle("F" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);

$filaNota = $fila + 2;
$hoja1->mergeCells("A" . $filaNota . ":F" . ($filaNota + 2));
$hoja1->setCellValue(
    "A" . $filaNota,
    "Cómo se calcula: clientes con documentos emitidos en los últimos {$anios} años (desde "
    . date("d/m/Y", strtotime($parametros["desde"]))
    . "). El atraso se mide desde el vencimiento. Si ya se pagó, se usa la fecha de pago; si sigue vencido, se usa hoy. "
    . "{$graciaDias} días de gracia = Puntual. Excluye vendedores 06* y 08* (ventas internas / show room)."
);
$hoja1->getStyle("A" . $filaNota . ":F" . ($filaNota + 2))->applyFromArray($estiloNota);
$hoja1->getRowDimension($filaNota)->setRowHeight(20);
$hoja1->getRowDimension($filaNota + 1)->setRowHeight(20);
$hoja1->getRowDimension($filaNota + 2)->setRowHeight(20);

$anchosResumen = array("A" => 14, "B" => 58, "C" => 12, "D" => 10, "E" => 16, "F" => 16);
foreach ($anchosResumen as $col => $ancho) {
    $hoja1->getColumnDimension($col)->setWidth($ancho);
}
$hoja1->freezePane("A3");
cmExportProtegerHoja($hoja1, "F", $filaNota + 2);

/* ===========================================================================
 * HOJA 2 — CLIENTES
 * ======================================================================== */
$hoja2 = $objPHPExcel->createSheet(1);
$hoja2->setTitle("Clientes");

$ultimaColCli = "K";
$hoja2->mergeCells("A1:" . $ultimaColCli . "1");
$hoja2->setCellValue("A1", "CLASIFICACIÓN DE MOROSIDAD — CLIENTES (" . $fecha . ")");
$hoja2->getStyle("A1")->applyFromArray($estiloTitulo);
$hoja2->getRowDimension(1)->setRowHeight(24);

$cabClientes = array(
    "A" => "Clasificación",
    "B" => "Código",
    "C" => "RUC",
    "D" => "Razón social",
    "E" => "Vendedor",
    "F" => "Días promedio",
    "G" => "Docs evaluados",
    "H" => "Docs pagados",
    "I" => "Docs vencidos abiertos",
    "J" => "Deuda actual",
    "K" => "Último pago",
);

foreach ($cabClientes as $col => $tituloCol) {
    $hoja2->setCellValue($col . "2", $tituloCol);
    $hoja2->getStyle($col . "2")->applyFromArray($estiloCabecera);
}

$fila = 3;
foreach ($clientes as $item) {
    $ultimoPago = "";
    if (!empty($item["ultimo_pago"]) && $item["ultimo_pago"] !== "0000-00-00") {
        $ts = strtotime($item["ultimo_pago"]);
        $ultimoPago = $ts ? date("d/m/Y", $ts) : cmExportTexto($item["ultimo_pago"]);
    }

    $nombre = isset($item["clasificacion"]) ? $item["clasificacion"] : "";

    $hoja2->setCellValueExplicit("A" . $fila, $nombre, PHPExcel_Cell_DataType::TYPE_STRING);
    $hoja2->setCellValueExplicit("B" . $fila, cmExportTexto($item["codigo_cliente"]), PHPExcel_Cell_DataType::TYPE_STRING);
    $hoja2->setCellValueExplicit("C" . $fila, cmExportTexto($item["ruc"]), PHPExcel_Cell_DataType::TYPE_STRING);
    $hoja2->setCellValue("D" . $fila, cmExportTexto($item["razon_social"]));
    $hoja2->setCellValue("E" . $fila, cmExportTexto($item["vendedor"]));
    $hoja2->setCellValue("F" . $fila, (float) $item["dias_promedio"]);
    $hoja2->setCellValue("G" . $fila, (int) $item["docs_evaluados"]);
    $hoja2->setCellValue("H" . $fila, (int) $item["docs_pagados"]);
    $hoja2->setCellValue("I" . $fila, (int) $item["docs_pendientes_vencidos"]);
    $hoja2->setCellValue("J" . $fila, (float) $item["deuda_actual"]);
    $hoja2->setCellValueExplicit("K" . $fila, $ultimoPago, PHPExcel_Cell_DataType::TYPE_STRING);

    $hoja2->getStyle("A" . $fila . ":" . $ultimaColCli . $fila)->applyFromArray($estiloCelda);
    $hoja2->getStyle("F" . $fila)->getNumberFormat()->setFormatCode("0.0");
    $hoja2->getStyle("J" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);

    if (isset($coloresClase[$nombre])) {
        $hoja2->getStyle("A" . $fila)->applyFromArray(
            cmExportEstiloFondo($coloresClase[$nombre]["fondo"], true, $coloresClase[$nombre]["texto"])
        );
    }

    if ((float) $item["deuda_actual"] > 0) {
        $hoja2->getStyle("J" . $fila)->applyFromArray(cmExportEstiloFondo("FCF3CF"));
    }

    $fila++;
}

$anchosCli = array(
    "A" => 14,
    "B" => 12,
    "C" => 14,
    "D" => 40,
    "E" => 22,
    "F" => 14,
    "G" => 14,
    "H" => 13,
    "I" => 18,
    "J" => 14,
    "K" => 13,
);
foreach ($anchosCli as $col => $ancho) {
    $hoja2->getColumnDimension($col)->setWidth($ancho);
}
$hoja2->freezePane("A3");
if ($fila > 3) {
    $hoja2->setAutoFilter("A2:" . $ultimaColCli . ($fila - 1));
}

/* ===========================================================================
 * HOJA 3 — METADATOS
 * ======================================================================== */
$hoja3 = $objPHPExcel->createSheet(2);
$hoja3->setTitle("Metadatos");

$hoja3->mergeCells("A1:B1");
$hoja3->setCellValue("A1", "INFORMACIÓN DEL REPORTE — CLASIFICACIÓN DE MOROSIDAD");
$hoja3->getStyle("A1")->applyFromArray($estiloMetaTitulo);
$hoja3->getRowDimension(1)->setRowHeight(26);

$metaFilas = array(
    array("_sec", "DATOS DEL REPORTE"),
    array("Reporte", "Clasificación de clientes por hábito de pago"),
    array("Fecha de exportación", $fechaHora),
    array("Solicitado por", $nombreSolicitante),
    array("Generado por", $nombreGenerador),
    array("Equipo (PC)", $nombrePc !== "" ? $nombrePc : "No identificado"),
    array("Total clientes", $totalClientes),
    array("Total deuda actual", "S/ " . number_format($totalDeuda, 2, ".", ",")),
    array("Documentos evaluados", $totalDocs),

    array("_sec", "REGLAS DE CLASIFICACIÓN"),
    array("Ventana", "Últimos {$anios} años: clientes con documentos emitidos desde " . date("d/m/Y", strtotime($parametros["desde"])) . "."),
    array("Base del cálculo", "El atraso se mide en días desde el vencimiento (no desde la emisión). Promedio por cliente."),
    array("Gracia", "{$graciaDias} días: si el promedio es menor o igual a {$graciaDias}, el cliente es Puntual."),
    array("Puntual", $leyendaClases["Puntual"]),
    array("Regular", $leyendaClases["Regular"]),
    array("Moroso", $leyendaClases["Moroso"]),
    array("Crítico", $leyendaClases["Crítico"]),
    array("Documentos pagados", "Días = fecha de último pago − fecha de vencimiento (mínimo 0)."),
    array("Documentos aún vencidos", "Días = hoy − fecha de vencimiento (mínimo 0). Así el atraso vigente también cuenta."),
    array("Quiénes aparecen", "Clientes con documentos emitidos en la ventana (pagados o con saldo)."),
    array("Vendedores excluidos", "Códigos que empiezan con 06 u 08 (ventas internas / show room)."),
    array("Deuda actual", "Saldo pendiente a la fecha de exportación (puede ser 0 si ya pagó todo)."),

    array("_sec", "LEYENDA DE COLORES"),
    array("Puntual", "Fondo verde"),
    array("Regular", "Fondo ámbar"),
    array("Moroso", "Fondo naranja"),
    array("Crítico", "Fondo rojo"),

    array("_sec", "NOTAS"),
    array("Uso", "Apoyo a gerencia para ver cómo pagan de costumbre. No reemplaza el estado de cuenta ni la evaluación de créditos."),
    array("Nota", "Esta hoja es de solo lectura."),
);

$filaMeta = 3;
foreach ($metaFilas as $metaItem) {
    if ($metaItem[0] === "_sec") {
        $hoja3->mergeCells("A" . $filaMeta . ":B" . $filaMeta);
        $hoja3->setCellValue("A" . $filaMeta, $metaItem[1]);
        $hoja3->getStyle("A" . $filaMeta . ":B" . $filaMeta)->applyFromArray($estiloMetaSeccion);
        $hoja3->getRowDimension($filaMeta)->setRowHeight(20);
        $filaMeta++;
        continue;
    }

    $hoja3->setCellValue("A" . $filaMeta, $metaItem[0]);
    $hoja3->setCellValue("B" . $filaMeta, $metaItem[1]);
    $hoja3->getStyle("A" . $filaMeta)->applyFromArray($estiloMetaEtiqueta);
    $hoja3->getStyle("B" . $filaMeta)->applyFromArray($estiloMetaValor);
    $hoja3->getStyle("B" . $filaMeta)->getAlignment()->setWrapText(true);
    $hoja3->getRowDimension($filaMeta)->setRowHeight(-1);

    if (isset($coloresClase[$metaItem[0]]) && in_array($metaItem[0], array("Puntual", "Regular", "Moroso", "Crítico"), true)) {
        $hoja3->getStyle("A" . $filaMeta)->applyFromArray(
            cmExportEstiloFondo($coloresClase[$metaItem[0]]["fondo"], true, $coloresClase[$metaItem[0]]["texto"])
        );
    }

    $filaMeta++;
}

$hoja3->getColumnDimension("A")->setWidth(28);
$hoja3->getColumnDimension("B")->setWidth(92);
cmExportProtegerHoja($hoja3, "B", $filaMeta - 1);

$objPHPExcel->setActiveSheetIndex(0);

$nombreArchivo = "clasificacion_morosidad_" . date("Y-m-d") . ".xls";

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
