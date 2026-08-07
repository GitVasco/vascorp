<?php

/**
 * Informe gerencial Dashboard Gerencial:
 * - Recuperación de ventas del período (mes de pago)
 * - CxC pendientes de recuperación
 * - Deuda abierta por vendedor
 */

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
    session_start();
}

@ini_set("max_execution_time", "180");
@ini_set("memory_limit", "256M");

include __DIR__ . "/Classes/PHPExcel.php";
require_once __DIR__ . "/../../controladores/config.php";
require_once __DIR__ . "/../../controladores/permisos-modulos.config.php";
require_once __DIR__ . "/../../controladores/dashboard-gerencial.config.php";
require_once __DIR__ . "/../../controladores/dashboard-gerencial.controlador.php";
require_once __DIR__ . "/../../modelos/dashboard-gerencial.modelo.php";
require_once __DIR__ . "/../../modelos/dashboard-cobranzas.modelo.php";

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo "Acceso no autorizado.";
    exit;
}

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "dashboard_gerencial")) {
    echo "Sin permiso para el dashboard gerencial.";
    exit;
}

date_default_timezone_set("America/Lima");

$filtros = ControladorDashboardGerencial::ctrParseFiltros(array(
    "anio" => isset($_GET["anio"]) ? $_GET["anio"] : (isset($_GET["año"]) ? $_GET["año"] : null),
    "mes" => isset($_GET["mes"]) ? $_GET["mes"] : null,
    "vendedor" => isset($_GET["vendedor"]) ? $_GET["vendedor"] : "",
    "modo" => isset($_GET["modo"]) ? $_GET["modo"] : "vs_anio_ant",
    "periodo_a_desde" => isset($_GET["periodo_a_desde"]) ? $_GET["periodo_a_desde"] : null,
    "periodo_a_hasta" => isset($_GET["periodo_a_hasta"]) ? $_GET["periodo_a_hasta"] : null,
    "periodo_b_desde" => isset($_GET["periodo_b_desde"]) ? $_GET["periodo_b_desde"] : null,
    "periodo_b_hasta" => isset($_GET["periodo_b_hasta"]) ? $_GET["periodo_b_hasta"] : null,
));

$origen = ControladorDashboardGerencial::ctrOrigenCobranza($filtros);

$rangoCobro = isset($origen["periodo_cobro"]) ? $origen["periodo_cobro"] : array(
    "desde" => "",
    "hasta" => "",
    "label" => "",
);

// Preferir el rango de ventas/origen ya resuelto por el controlador.
$rango = $rangoCobro;

$docsRaw = ModeloDashboardGerencial::mdlDocsPendienteRecuperacion(
    isset($rango["desde"]) ? $rango["desde"] : "",
    isset($rango["hasta"]) ? $rango["hasta"] : "",
    isset($filtros["vendedor"]) ? $filtros["vendedor"] : "",
    1,
    5000
);

$ventaPeriodo = isset($origen["venta_periodo"]) ? (float) $origen["venta_periodo"] : 0.0;
$recupTotal = isset($origen["total_recuperacion"])
    ? (float) $origen["total_recuperacion"]
    : (isset($origen["recuperado_periodo"]) ? (float) $origen["recuperado_periodo"] : 0.0);
$pendienteKpi = isset($origen["pendiente_periodo"]) ? (float) $origen["pendiente_periodo"] : 0.0;
$pctRecup = isset($origen["pct_recup_periodo"]) ? (float) $origen["pct_recup_periodo"] : 0.0;
$saldoCartera = isset($docsRaw["total_saldo"]) ? (float) $docsRaw["total_saldo"] : 0.0;
$docsTotal = isset($docsRaw["total_docs"]) ? (int) $docsRaw["total_docs"] : 0;
$porVendedor = isset($docsRaw["por_vendedor"]) ? $docsRaw["por_vendedor"] : array();
$filasDocs = isset($docsRaw["filas"]) ? $docsRaw["filas"] : array();
$filasRecup = isset($origen["filas_recuperacion"]) ? $origen["filas_recuperacion"] : array();

// Agrupa en Otros: meses de pago anteriores al período + (opcionalmente se listan todos los del período).
$anioPeriodo = 0;
$mesPeriodo = 0;
if (!empty($rango["desde"])) {
    $pDesde = explode("-", substr((string) $rango["desde"], 0, 10));
    if (count($pDesde) >= 2) {
        $anioPeriodo = (int) $pDesde[0];
        $mesPeriodo = (int) $pDesde[1];
    }
}
$filasRecupVista = array();
$otrosRecupMonto = 0.0;
$nOtrosRecup = 0;
foreach ($filasRecup as $item) {
    $anioItem = isset($item["anio"]) ? (int) $item["anio"] : 0;
    $mesItem = isset($item["mes"]) ? (int) $item["mes"] : 0;
    $antes = $anioPeriodo > 0 && $mesPeriodo > 0 &&
        ($anioItem < $anioPeriodo || ($anioItem === $anioPeriodo && $mesItem < $mesPeriodo));
    if ($antes) {
        $otrosRecupMonto += isset($item["monto"]) ? (float) $item["monto"] : 0.0;
        $nOtrosRecup++;
    } else {
        $filasRecupVista[] = $item;
    }
}
if ($nOtrosRecup > 0 && $otrosRecupMonto > 0) {
    $filasRecupVista[] = array(
        "label" => "Otros (" . $nOtrosRecup . ")",
        "monto" => $otrosRecupMonto,
        "pct" => $recupTotal > 0 ? round(($otrosRecupMonto / $recupTotal) * 100, 1) : 0.0,
        "esOtros" => true,
    );
}
$filasRecup = $filasRecupVista;

$vendedorFiltro = trim((string) $filtros["vendedor"]);
$vendedorLabel = $vendedorFiltro !== "" ? $vendedorFiltro : "TODOS";
$periodoLabel = isset($rango["label"]) ? (string) $rango["label"] : "";
$fechaHora = date("d/m/Y H:i");
$fechaArchivo = date("Y-m-d_His");
$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : (
    isset($_SESSION["usuario"]) ? (string) $_SESSION["usuario"] : "Usuario"
);
$nombrePc = (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] !== "")
    ? gethostbyaddr($_SERVER["REMOTE_ADDR"])
    : "";
$modoFiltro = isset($filtros["modo"]) ? (string) $filtros["modo"] : "vs_anio_ant";
$modoLabel = $modoFiltro === "periodos" ? "Periodos personalizados" : "Mes / año";
$rangoDesde = isset($rango["desde"]) ? (string) $rango["desde"] : "";
$rangoHasta = isset($rango["hasta"]) ? (string) $rango["hasta"] : "";
$diferenciaKpi = round($pendienteKpi - $saldoCartera, 2);

function dgExcelTexto($valor)
{
    if ($valor === null) {
        return "";
    }
    return (string) $valor;
}

function dgExcelFecha($valor)
{
    if (!$valor || $valor === "0000-00-00") {
        return "";
    }
    $s = substr((string) $valor, 0, 10);
    $p = explode("-", $s);
    if (count($p) !== 3) {
        return $s;
    }
    return $p[2] . "/" . $p[1] . "/" . $p[0];
}

$estiloTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "1E293B")),
);
$estiloSubtitulo = array(
    "font" => array("size" => 10, "color" => array("rgb" => "475569")),
);
$estiloHeader = array(
    "font" => array("bold" => true, "size" => 10, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "1D4ED8")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CBD5E1")),
    ),
);
$estiloCelda = array(
    "font" => array("size" => 9),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "E2E8F0")),
    ),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
);
$estiloTotal = array(
    "font" => array("bold" => true, "size" => 10),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "F1F5F9")),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CBD5E1")),
    ),
);
$estiloKpiLabel = array(
    "font" => array("bold" => true, "size" => 9, "color" => array("rgb" => "334155")),
);
$estiloNota = array(
    "font" => array("italic" => true, "size" => 8, "color" => array("rgb" => "64748B")),
);

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator("Corp. Vasco")
    ->setTitle("Informe recuperación gerencial")
    ->setDescription("Dashboard Gerencial — recuperación de ventas y CxC pendientes");

/* ========== Hoja 1: Recuperación ========== */
$sheet1 = $objPHPExcel->getActiveSheet();
$sheet1->setTitle("Recuperacion");

$sheet1->setCellValue("A1", "INFORME GERENCIAL — RECUPERACIÓN DE VENTAS");
$sheet1->mergeCells("A1:E1");
$sheet1->getStyle("A1")->applyFromArray($estiloTitulo);

$sheet1->setCellValue("A2", "Período: " . $periodoLabel . "  |  Vendedor: " . $vendedorLabel . "  |  Generado: " . $fechaHora . "  |  Por: " . $usuario);
$sheet1->mergeCells("A2:E2");
$sheet1->getStyle("A2")->applyFromArray($estiloSubtitulo);

$sheet1->setCellValue("A4", "Ventas período (sin IGV)");
$sheet1->setCellValue("B4", $ventaPeriodo);
$sheet1->setCellValue("C4", "Recuperado hasta hoy");
$sheet1->setCellValue("D4", $recupTotal);
$sheet1->setCellValue("E4", "% recuperado");
$sheet1->setCellValue("F4", $pctRecup / 100);
$sheet1->getStyle("A4")->applyFromArray($estiloKpiLabel);
$sheet1->getStyle("C4")->applyFromArray($estiloKpiLabel);
$sheet1->getStyle("E4")->applyFromArray($estiloKpiLabel);
$sheet1->getStyle("B4")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet1->getStyle("D4")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet1->getStyle("F4")->getNumberFormat()->setFormatCode('0.0%');

$sheet1->setCellValue("A5", "Pendiente (venta − recuperado)");
$sheet1->setCellValue("B5", $pendienteKpi);
$sheet1->setCellValue("C5", "Cartera abierta listada");
$sheet1->setCellValue("D5", $saldoCartera);
$sheet1->setCellValue("E5", "Docs cartera");
$sheet1->setCellValue("F5", $docsTotal);
$sheet1->getStyle("A5")->applyFromArray($estiloKpiLabel);
$sheet1->getStyle("C5")->applyFromArray($estiloKpiLabel);
$sheet1->getStyle("E5")->applyFromArray($estiloKpiLabel);
$sheet1->getStyle("B5")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet1->getStyle("D5")->getNumberFormat()->setFormatCode('#,##0.00');

$anioInforme = isset($filtros["anio"]) ? (int) $filtros["anio"] : (int) date("Y");
$mensualAnio = isset($origen["mensual"]) && is_array($origen["mensual"]) ? $origen["mensual"] : array();

$fila = 7;
$sheet1->setCellValue("A" . $fila, "Año " . $anioInforme . " — recuperación mes a mes");
$sheet1->mergeCells("A" . $fila . ":H" . $fila);
$sheet1->getStyle("A" . $fila)->applyFromArray($estiloKpiLabel);
$fila++;

$headersAnio = array(
    "Mes",
    "Venta",
    "Recuperado",
    "Pendiente",
    "% recuperado",
    "Cobrado del mes",
    "Mismo mes",
    "% cobro mismo mes",
);
$col = "A";
foreach ($headersAnio as $h) {
    $sheet1->setCellValue($col . $fila, $h);
    $col++;
}
$sheet1->getStyle("A" . $fila . ":H" . $fila)->applyFromArray($estiloHeader);
$fila++;

$sumVentaAnio = 0.0;
$sumRecupAnio = 0.0;
$sumPendAnio = 0.0;
$sumCobradoAnio = 0.0;
$sumMismoAnio = 0.0;

foreach ($mensualAnio as $item) {
    $ventaM = isset($item["venta"]) ? (float) $item["venta"] : 0.0;
    $recupM = isset($item["recuperado"]) ? (float) $item["recuperado"] : 0.0;
    $pendM = isset($item["pendiente"]) ? (float) $item["pendiente"] : max(0.0, $ventaM - $recupM);
    $cobradoM = isset($item["cobrado"]) ? (float) $item["cobrado"] : 0.0;
    $mismoM = isset($item["mismo_mes"]) ? (float) $item["mismo_mes"] : 0.0;
    $pctRecupM = isset($item["pct_recuperado"])
        ? (float) $item["pct_recuperado"]
        : ($ventaM > 0 ? round(($recupM / $ventaM) * 100, 1) : 0.0);
    $pctMismoM = isset($item["pct_mismo_mes"])
        ? (float) $item["pct_mismo_mes"]
        : ($cobradoM > 0 ? round(($mismoM / $cobradoM) * 100, 1) : 0.0);

    $sumVentaAnio += $ventaM;
    $sumRecupAnio += $recupM;
    $sumPendAnio += $pendM;
    $sumCobradoAnio += $cobradoM;
    $sumMismoAnio += $mismoM;

    $sheet1->setCellValue("A" . $fila, isset($item["label"]) ? $item["label"] : "");
    $sheet1->setCellValue("B" . $fila, $ventaM);
    $sheet1->setCellValue("C" . $fila, $recupM);
    $sheet1->setCellValue("D" . $fila, $pendM);
    $sheet1->setCellValue("E" . $fila, $pctRecupM / 100);
    $sheet1->setCellValue("F" . $fila, $cobradoM);
    $sheet1->setCellValue("G" . $fila, $mismoM);
    $sheet1->setCellValue("H" . $fila, $pctMismoM / 100);
    $sheet1->getStyle("A" . $fila . ":H" . $fila)->applyFromArray($estiloCelda);
    $sheet1->getStyle("B" . $fila . ":D" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet1->getStyle("F" . $fila . ":G" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet1->getStyle("E" . $fila)->getNumberFormat()->setFormatCode('0.0%');
    $sheet1->getStyle("H" . $fila)->getNumberFormat()->setFormatCode('0.0%');
    $fila++;
}

$sheet1->setCellValue("A" . $fila, "TOTAL " . $anioInforme);
$sheet1->setCellValue("B" . $fila, $sumVentaAnio);
$sheet1->setCellValue("C" . $fila, $sumRecupAnio);
$sheet1->setCellValue("D" . $fila, $sumPendAnio);
$sheet1->setCellValue("E" . $fila, $sumVentaAnio > 0 ? ($sumRecupAnio / $sumVentaAnio) : 0);
$sheet1->setCellValue("F" . $fila, $sumCobradoAnio);
$sheet1->setCellValue("G" . $fila, $sumMismoAnio);
$sheet1->setCellValue("H" . $fila, $sumCobradoAnio > 0 ? ($sumMismoAnio / $sumCobradoAnio) : 0);
$sheet1->getStyle("A" . $fila . ":H" . $fila)->applyFromArray($estiloTotal);
$sheet1->getStyle("B" . $fila . ":D" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet1->getStyle("F" . $fila . ":G" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet1->getStyle("E" . $fila)->getNumberFormat()->setFormatCode('0.0%');
$sheet1->getStyle("H" . $fila)->getNumberFormat()->setFormatCode('0.0%');
$fila += 2;

$sheet1->setCellValue("A" . $fila, "Período seleccionado (" . $periodoLabel . "): recuperación por mes de pago");
$sheet1->mergeCells("A" . $fila . ":E" . $fila);
$sheet1->getStyle("A" . $fila)->applyFromArray($estiloKpiLabel);
$fila++;

$headersRecup = array("Mes pago", "Monto", "Acumulado", "% sobre ventas", "% acum. sobre ventas");
$col = "A";
foreach ($headersRecup as $h) {
    $sheet1->setCellValue($col . $fila, $h);
    $col++;
}
$sheet1->getStyle("A" . $fila . ":E" . $fila)->applyFromArray($estiloHeader);
$fila++;

$acum = 0.0;
foreach ($filasRecup as $item) {
    $monto = isset($item["monto"]) ? (float) $item["monto"] : 0.0;
    $acum += $monto;
    $pctFila = $ventaPeriodo > 0 ? round(($monto / $ventaPeriodo) * 1000) / 10 : 0;
    $pctVenta = $ventaPeriodo > 0 ? round(($acum / $ventaPeriodo) * 1000) / 10 : 0;

    $sheet1->setCellValue("A" . $fila, isset($item["label"]) ? $item["label"] : "");
    $sheet1->setCellValue("B" . $fila, $monto);
    $sheet1->setCellValue("C" . $fila, $acum);
    $sheet1->setCellValue("D" . $fila, $pctFila / 100);
    $sheet1->setCellValue("E" . $fila, $pctVenta / 100);
    $sheet1->getStyle("A" . $fila . ":E" . $fila)->applyFromArray($estiloCelda);
    $sheet1->getStyle("B" . $fila . ":C" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet1->getStyle("D" . $fila . ":E" . $fila)->getNumberFormat()->setFormatCode('0.0%');
    $fila++;
}

$sheet1->setCellValue("A" . $fila, "TOTAL RECUPERADO");
$sheet1->setCellValue("B" . $fila, $recupTotal);
$sheet1->setCellValue("C" . $fila, $recupTotal);
$sheet1->setCellValue("D" . $fila, $pctRecup / 100);
$sheet1->setCellValue("E" . $fila, $pctRecup / 100);
$sheet1->getStyle("A" . $fila . ":E" . $fila)->applyFromArray($estiloTotal);
$sheet1->getStyle("B" . $fila . ":C" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet1->getStyle("D" . $fila . ":E" . $fila)->getNumberFormat()->setFormatCode('0.0%');
$fila++;

$sheet1->setCellValue("A" . $fila, "PENDIENTE");
$sheet1->setCellValue("B" . $fila, $pendienteKpi);
$sheet1->setCellValue("E" . $fila, $ventaPeriodo > 0 ? ($pendienteKpi / $ventaPeriodo) : 0);
$sheet1->getStyle("A" . $fila . ":E" . $fila)->applyFromArray($estiloTotal);
$sheet1->getStyle("B" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet1->getStyle("E" . $fila)->getNumberFormat()->setFormatCode('0.0%');
$fila += 2;

$sheet1->setCellValue(
    "A" . $fila,
    "Nota: montos sin IGV. El bloque anual muestra venta/recuperado/pendiente de cada mes del año. El bloque del período detalla en qué mes se cobraron las ventas seleccionadas (anteriores al período → Otros). Cartera listada excluye vendedores 06* y 08*."
);
$sheet1->mergeCells("A" . $fila . ":H" . $fila);
$sheet1->getStyle("A" . $fila)->applyFromArray($estiloNota);

foreach (range("A", "H") as $c) {
    $sheet1->getColumnDimension($c)->setAutoSize(true);
}

/* ========== Hoja 2: CxC pendientes ========== */
$sheet2 = $objPHPExcel->createSheet();
$sheet2->setTitle("CxC pendientes");

$sheet2->setCellValue("A1", "CXC PENDIENTES DE RECUPERACIÓN — CARTERA ABIERTA");
$sheet2->mergeCells("A1:J1");
$sheet2->getStyle("A1")->applyFromArray($estiloTitulo);

$sheet2->setCellValue(
    "A2",
    "Período cargo: " . $periodoLabel .
    "  |  Vendedor: " . $vendedorLabel .
    "  |  Docs: " . $docsTotal .
    "  |  Saldo: " . number_format($saldoCartera, 2, ".", ",") .
    "  |  Generado: " . $fechaHora
);
$sheet2->mergeCells("A2:J2");
$sheet2->getStyle("A2")->applyFromArray($estiloSubtitulo);

$headersDocs = array(
    "Cliente", "Nombre cliente", "Documento", "Fecha", "Vence",
    "Días", "Vendedor", "Nombre vendedor", "Monto", "Saldo",
);
$col = "A";
foreach ($headersDocs as $h) {
    $sheet2->setCellValue($col . "4", $h);
    $col++;
}
$sheet2->getStyle("A4:J4")->applyFromArray($estiloHeader);

$fila = 5;
foreach ($filasDocs as $doc) {
    $docLabel = trim((isset($doc["tipo_doc"]) ? $doc["tipo_doc"] : "") . " " . (isset($doc["num_cta"]) ? $doc["num_cta"] : ""));
    $sheet2->setCellValueExplicit(
        "A" . $fila,
        dgExcelTexto(isset($doc["cliente"]) ? $doc["cliente"] : ""),
        PHPExcel_Cell_DataType::TYPE_STRING
    );
    $sheet2->setCellValue("B" . $fila, dgExcelTexto(isset($doc["nombre_cliente"]) ? $doc["nombre_cliente"] : ""));
    $sheet2->setCellValueExplicit("C" . $fila, $docLabel, PHPExcel_Cell_DataType::TYPE_STRING);
    $sheet2->setCellValue("D" . $fila, dgExcelFecha(isset($doc["fecha"]) ? $doc["fecha"] : ""));
    $sheet2->setCellValue("E" . $fila, dgExcelFecha(isset($doc["fecha_ven"]) ? $doc["fecha_ven"] : ""));
    $sheet2->setCellValue("F" . $fila, isset($doc["dias_vencido"]) ? (int) $doc["dias_vencido"] : 0);
    $sheet2->setCellValueExplicit(
        "G" . $fila,
        dgExcelTexto(isset($doc["vendedor"]) ? $doc["vendedor"] : ""),
        PHPExcel_Cell_DataType::TYPE_STRING
    );
    $sheet2->setCellValue("H" . $fila, dgExcelTexto(isset($doc["nombre_vendedor"]) ? $doc["nombre_vendedor"] : ""));
    $sheet2->setCellValue("I" . $fila, isset($doc["monto"]) ? (float) $doc["monto"] : 0);
    $sheet2->setCellValue("J" . $fila, isset($doc["saldo"]) ? (float) $doc["saldo"] : 0);
    $sheet2->getStyle("A" . $fila . ":J" . $fila)->applyFromArray($estiloCelda);
    $sheet2->getStyle("I" . $fila . ":J" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
    if (!empty($doc["vencido"])) {
        $sheet2->getStyle("F" . $fila)->getFont()->getColor()->setRGB("B91C1C");
        $sheet2->getStyle("F" . $fila)->getFont()->setBold(true);
    }
    $fila++;
}

$sheet2->setCellValue("A" . $fila, "TOTAL");
$sheet2->setCellValue("J" . $fila, $saldoCartera);
$sheet2->getStyle("A" . $fila . ":J" . $fila)->applyFromArray($estiloTotal);
$sheet2->getStyle("J" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
$fila += 2;

$sheet2->setCellValue(
    "A" . $fila,
    "Criterio: tip_mov='+', estado=PENDIENTE, saldo>0, fecha de cargo en el período. Excluye vendedores 06* y 08*. Montos sin IGV."
);
$sheet2->mergeCells("A" . $fila . ":J" . $fila);
$sheet2->getStyle("A" . $fila)->applyFromArray($estiloNota);

foreach (range("A", "J") as $c) {
    $sheet2->getColumnDimension($c)->setAutoSize(true);
}

/* ========== Hoja 3: Deuda por vendedor ========== */
$sheet3 = $objPHPExcel->createSheet();
$sheet3->setTitle("Deuda por vendedor");

$sheet3->setCellValue("A1", "DEUDA ABIERTA POR VENDEDOR");
$sheet3->mergeCells("A1:E1");
$sheet3->getStyle("A1")->applyFromArray($estiloTitulo);

$sheet3->setCellValue(
    "A2",
    "Período: " . $periodoLabel . "  |  Vendedor filtro: " . $vendedorLabel . "  |  Generado: " . $fechaHora
);
$sheet3->mergeCells("A2:E2");
$sheet3->getStyle("A2")->applyFromArray($estiloSubtitulo);

$headersVend = array("Vendedor", "Nombre", "Docs", "Saldo", "% sobre cartera");
$col = "A";
foreach ($headersVend as $h) {
    $sheet3->setCellValue($col . "4", $h);
    $col++;
}
$sheet3->getStyle("A4:E4")->applyFromArray($estiloHeader);

$fila = 5;
foreach ($porVendedor as $vend) {
    $sheet3->setCellValueExplicit(
        "A" . $fila,
        dgExcelTexto(isset($vend["vendedor"]) ? $vend["vendedor"] : ""),
        PHPExcel_Cell_DataType::TYPE_STRING
    );
    $sheet3->setCellValue("B" . $fila, dgExcelTexto(isset($vend["nombre"]) ? $vend["nombre"] : ""));
    $sheet3->setCellValue("C" . $fila, isset($vend["docs"]) ? (int) $vend["docs"] : 0);
    $sheet3->setCellValue("D" . $fila, isset($vend["saldo"]) ? (float) $vend["saldo"] : 0);
    $sheet3->setCellValue("E" . $fila, (isset($vend["pct"]) ? (float) $vend["pct"] : 0) / 100);
    $sheet3->getStyle("A" . $fila . ":E" . $fila)->applyFromArray($estiloCelda);
    $sheet3->getStyle("D" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet3->getStyle("E" . $fila)->getNumberFormat()->setFormatCode('0.0%');
    $fila++;
}

$sheet3->setCellValue("A" . $fila, "TOTAL");
$sheet3->setCellValue("C" . $fila, $docsTotal);
$sheet3->setCellValue("D" . $fila, $saldoCartera);
$sheet3->setCellValue("E" . $fila, $saldoCartera > 0 ? 1 : 0);
$sheet3->getStyle("A" . $fila . ":E" . $fila)->applyFromArray($estiloTotal);
$sheet3->getStyle("D" . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet3->getStyle("E" . $fila)->getNumberFormat()->setFormatCode('0.0%');
$fila += 2;

$sheet3->setCellValue("A" . $fila, "Misma cartera que la hoja CxC pendientes. Ordenado por saldo descendente.");
$sheet3->mergeCells("A" . $fila . ":E" . $fila);
$sheet3->getStyle("A" . $fila)->applyFromArray($estiloNota);

foreach (range("A", "E") as $c) {
    $sheet3->getColumnDimension($c)->setAutoSize(true);
}

/* ========== Hoja 4: Metadatos ========== */
$sheetMeta = $objPHPExcel->createSheet();
$sheetMeta->setTitle("Metadatos");

$estiloMetaTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "1D4ED8")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);
$estiloMetaSeccion = array(
    "font" => array("bold" => true, "size" => 11, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "475569")),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
);
$estiloMetaEtiqueta = array(
    "font" => array("bold" => true, "size" => 10),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP),
);
$estiloMetaValor = array(
    "font" => array("size" => 10),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP, "wrap" => true),
);

$sheetMeta->mergeCells("A1:B1");
$sheetMeta->setCellValue("A1", "INFORMACIÓN DEL REPORTE — DASHBOARD GERENCIAL / RECUPERACIÓN");
$sheetMeta->getStyle("A1")->applyFromArray($estiloMetaTitulo);
$sheetMeta->getRowDimension(1)->setRowHeight(26);

$metaFilas = array(
    array("_sec", "DATOS DEL REPORTE"),
    array("Reporte", "Dashboard Gerencial — Recuperación de ventas y CxC pendientes"),
    array("Módulo", "gestion_comercial / dashboard_gerencial"),
    array("Fecha de exportación", $fechaHora),
    array("Generado por", $usuario),
    array("Equipo (PC)", $nombrePc !== "" ? $nombrePc : "No identificado"),
    array("Modo de filtro", $modoLabel),
    array("Período", $periodoLabel),
    array("Rango desde", $rangoDesde),
    array("Rango hasta", $rangoHasta),
    array("Vendedor", $vendedorLabel),
    array("Ventas período (sin IGV)", "S/ " . number_format($ventaPeriodo, 2, ".", ",")),
    array("Recuperado hasta hoy (sin IGV)", "S/ " . number_format($recupTotal, 2, ".", ",")),
    array("% recuperado", number_format($pctRecup, 1, ".", ",") . "%"),
    array("Pendiente KPI (venta − recuperado)", "S/ " . number_format($pendienteKpi, 2, ".", ",")),
    array("Cartera abierta listada (sin IGV)", "S/ " . number_format($saldoCartera, 2, ".", ",")),
    array("Diferencia KPI vs cartera", "S/ " . number_format($diferenciaKpi, 2, ".", ",")),
    array("Docs en cartera listada", (string) $docsTotal),

    array("_sec", "HOJA RECUPERACION"),
    array("Bloque anual", "Mes a mes del año del filtro: venta, recuperado hasta hoy, pendiente, % recuperado, cobrado del mes, mismo mes y % cobro mismo mes."),
    array("Bloque del período", "De las ventas del período seleccionado, desglose por mes de pago. Meses anteriores al inicio del período se agrupan en Otros."),
    array("% y % acum. del período", "Ambos sobre ventas del período (sin IGV). El % acum. culmina en el % recuperado."),
    array("Fuente ventas", "ventajf.neto tipos S02, S03, S70, E05, S05."),
    array("Fuente recuperación", "cuenta_ctejf abonos tip_mov='-' efectivo (mrSqlInCodigosCobranzaEfectiva), sin IGV (÷1.18). Origen = fecha_ori del abono o fecha del cargo (+)."),

    array("_sec", "HOJA CXC PENDIENTES"),
    array("Universo", "Documentos tip_mov='+', estado=PENDIENTE, saldo>0, fecha de cargo dentro del período de ventas."),
    array("Exclusiones", "Vendedores cuyo código empieza con 06 o 08 (hardcode solo de este informe/modal)."),
    array("Montos", "Monto y saldo sin IGV (÷1.18)."),
    array("Código vendedor / cliente", "Exportados como texto para conservar ceros a la izquierda."),
    array("Orden", "Vencidos primero, luego por fecha_ven ASC y saldo DESC."),

    array("_sec", "HOJA DEUDA POR VENDEDOR"),
    array("Contenido", "Misma cartera que CxC pendientes, agregada por vendedor."),
    array("Columnas", "Vendedor, nombre (maestrajf TVEND.descripcion), docs, saldo sin IGV, % sobre cartera listada."),
    array("Orden", "Saldo descendente."),

    array("_sec", "DEFINICIONES"),
    array("Pendiente KPI", "max(0, ventas del período − recuperado hasta hoy de docs con origen en el período). No necesariamente igual a SUM(saldo) de CxC."),
    array("Cartera abierta", "SUM(saldo) de documentos pendientes listados (puede ser menor que el pendiente KPI por desfase ventajf vs cuenta corriente, NC, tipos, exclusiones 06/08, etc.)."),
    array("% cobro mismo mes", "Del cobrado de un mes calendario, la parte cuyo origen del documento cae en el mismo mes del pago."),

    array("_sec", "NOTAS"),
    array("Uso del reporte", "Informe gerencial de recuperación de ventas. No reemplaza el detalle operativo de CxC por cliente."),
    array("Actualización", "Los montos son en vivo al momento de exportar."),
    array("Nota", "Esta hoja es de solo lectura."),
);

$filaMeta = 3;
foreach ($metaFilas as $metaItem) {
    if ($metaItem[0] === "_sec") {
        $sheetMeta->mergeCells("A" . $filaMeta . ":B" . $filaMeta);
        $sheetMeta->setCellValue("A" . $filaMeta, $metaItem[1]);
        $sheetMeta->getStyle("A" . $filaMeta . ":B" . $filaMeta)->applyFromArray($estiloMetaSeccion);
        $sheetMeta->getRowDimension($filaMeta)->setRowHeight(20);
        $filaMeta++;
        continue;
    }

    $sheetMeta->setCellValue("A" . $filaMeta, $metaItem[0]);
    $sheetMeta->setCellValue("B" . $filaMeta, $metaItem[1]);
    $sheetMeta->getStyle("A" . $filaMeta)->applyFromArray($estiloMetaEtiqueta);
    $sheetMeta->getStyle("B" . $filaMeta)->applyFromArray($estiloMetaValor);
    $sheetMeta->getStyle("B" . $filaMeta)->getAlignment()->setWrapText(true);
    $filaMeta++;
}

$sheetMeta->getColumnDimension("A")->setWidth(40);
$sheetMeta->getColumnDimension("B")->setWidth(100);

$sheetMeta->getStyle("A1:B" . max(1, $filaMeta - 1))
    ->getProtection()
    ->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);

$protectionMeta = $sheetMeta->getProtection();
$protectionMeta->setSheet(true);
$protectionMeta->setSelectLockedCells(true);
$protectionMeta->setSelectUnlockedCells(true);
$protectionMeta->setFormatCells(false);
$protectionMeta->setFormatColumns(false);
$protectionMeta->setFormatRows(false);
$protectionMeta->setInsertColumns(false);
$protectionMeta->setInsertRows(false);
$protectionMeta->setDeleteColumns(false);
$protectionMeta->setDeleteRows(false);
$protectionMeta->setSort(false);
$protectionMeta->setAutoFilter(false);

$objPHPExcel->setActiveSheetIndex(0);

$nombreArchivo = "DG_Recuperacion_" . preg_replace('/[^A-Za-z0-9_-]+/', "_", $periodoLabel) . "_" . $fechaArchivo . ".xls";

while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment;filename=\"" . $nombreArchivo . "\"");
header("Cache-Control: max-age=0");
header("Pragma: public");

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
$objWriter->save("php://output");
exit;
