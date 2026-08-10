<?php

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
	echo "Acceso no autorizado.";
	exit;
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/abonos.controlador.php";
require_once "../../modelos/abonos.modelo.php";
require_once "../../modelos/linea-credito.modelo.php";

date_default_timezone_set("America/Lima");
$fechaHoy = date("d-m-Y");
$fechaHora = date("d/m/Y H:i");

$idGenerador = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$generador = $idGenerador > 0 ? ModeloLineaCredito::mdlUsuarioActivoPorId($idGenerador) : null;
$nombreGenerador = ($generador && !empty($generador["nombre"]))
	? trim((string) $generador["nombre"])
	: (isset($_SESSION["nombre"]) ? trim((string) $_SESSION["nombre"]) : "Sistema");
$nombrePc = (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] !== "")
	? gethostbyaddr($_SERVER["REMOTE_ADDR"])
	: "";

$anio = isset($_GET["anio"]) ? trim((string) $_GET["anio"]) : "";
$mes = isset($_GET["mes"]) ? trim((string) $_GET["mes"]) : "";
$motivo = isset($_GET["motivo"]) ? trim((string) $_GET["motivo"]) : "";
if ($motivo === "") {
	$motivo = null;
}

$stats = ControladorAbonos::ctrEstadisticasMensuales($anio, $mes);
$anioReporte = isset($stats["anio"]) ? (int) $stats["anio"] : (int) date("Y");
$mesReporte = isset($stats["mes"]) ? $stats["mes"] : "todos";
$mesParaLista = ($mesReporte === "todos" || $mesReporte === null || $mesReporte === "")
	? null
	: $mesReporte;

$abonos = ControladorAbonos::ctrMostrarAbonos(null, null, $motivo, $anioReporte, $mesParaLista);
if (!is_array($abonos)) {
	$abonos = array();
}

$periodoTexto = !empty($stats["periodo_anio_completo"])
	? ("Todo el año " . $anioReporte)
	: ((isset($stats["mes_nombre"]) ? $stats["mes_nombre"] : "") . " " . $anioReporte);

$motivoTexto = "Todos";
if ($motivo === "sin") {
	$motivoTexto = "Sin motivo";
} elseif ($motivo !== null && $motivo !== "") {
	$motivoTexto = ControladorAbonos::ctrEtiquetaMotivoPendiente($motivo);
	if ($motivoTexto === "") {
		$motivoTexto = $motivo;
	}
}

function abonosExportProtegerHoja(PHPExcel_Worksheet $hoja, $ultimaCol, $ultimaFila)
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
	->setTitle("Reporte Abonos")
	->setSubject("Abonos pendientes y estadísticas")
	->setDescription("Exportado por " . $nombreGenerador);

$estiloTitulo = array(
	"font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
	"fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
	"alignment" => array(
		"horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		"vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
	),
);

$estiloSubtitulo = array(
	"font" => array("bold" => true, "size" => 11),
	"fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "D9EAF7")),
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
		"allborders" => array(
			"style" => PHPExcel_Style_Border::BORDER_THIN,
			"color" => array("rgb" => "BBBBBB"),
		),
	),
);

$estiloCelda = array(
	"font" => array("size" => 10),
	"borders" => array(
		"allborders" => array(
			"style" => PHPExcel_Style_Border::BORDER_THIN,
			"color" => array("rgb" => "DDDDDD"),
		),
	),
);

$estiloEtiqueta = array(
	"font" => array("bold" => true, "size" => 10),
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

$estiloMetaSeccion = array(
	"font" => array("bold" => true, "size" => 11, "color" => array("rgb" => "FFFFFF")),
	"fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "5D6D7E")),
	"alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
);

$formatoMoneda = '#,##0.00';

$delMes = isset($stats["del_mes"]) && is_array($stats["del_mes"]) ? $stats["del_mes"] : array();
$acumAnio = isset($stats["acumulado_anio"]) && is_array($stats["acumulado_anio"])
	? $stats["acumulado_anio"]
	: array();

$sumMontoLista = 0.0;
foreach ($abonos as $abonoTmp) {
	$sumMontoLista += isset($abonoTmp["monto"]) ? (float) $abonoTmp["monto"] : 0.0;
}

/* ===========================================================================
 * HOJA 1 — RESUMEN / ESTADÍSTICAS
 * ======================================================================== */
$hoja1 = $objPHPExcel->setActiveSheetIndex(0);
$hoja1->setTitle("Resumen");

$hoja1->mergeCells("A1:F1");
$hoja1->setCellValue("A1", "REPORTE DE ABONOS — ESTADÍSTICAS (" . $fechaHoy . ")");
$hoja1->getStyle("A1")->applyFromArray($estiloTitulo);
$hoja1->getRowDimension(1)->setRowHeight(24);

$hoja1->setCellValue("A3", "Periodo");
$hoja1->setCellValue("B3", $periodoTexto);
$hoja1->setCellValue("A4", "Filtro motivo");
$hoja1->setCellValue("B4", $motivoTexto);
$hoja1->setCellValue("A5", "Generado por");
$hoja1->setCellValue("B5", $nombreGenerador . " — " . $fechaHora);
$hoja1->getStyle("A3:A5")->applyFromArray($estiloEtiqueta);

$hoja1->mergeCells("A7:F7");
$hoja1->setCellValue("A7", "Estadística del periodo seleccionado");
$hoja1->getStyle("A7")->applyFromArray($estiloSubtitulo);

$cabStats = array(
	"A" => "Concepto",
	"B" => "Cantidad",
	"C" => "Monto S/",
	"D" => "% pendiente",
);
$filaCab = 8;
foreach ($cabStats as $col => $titulo) {
	$hoja1->setCellValue($col . $filaCab, $titulo);
	$hoja1->getStyle($col . $filaCab)->applyFromArray($estiloCabecera);
}

$filasPeriodo = array(
	array("Pendientes", isset($delMes["pendientes_cant"]) ? $delMes["pendientes_cant"] : 0, isset($delMes["pendientes_monto"]) ? $delMes["pendientes_monto"] : 0, null),
	array("Aplicados (05/15 + OP-)", isset($delMes["aplicados_cant"]) ? $delMes["aplicados_cant"] : 0, isset($delMes["aplicados_monto"]) ? $delMes["aplicados_monto"] : 0, null),
	array(
		"Total",
		isset($delMes["total_cant"]) ? $delMes["total_cant"] : 0,
		(isset($delMes["pendientes_monto"]) ? (float) $delMes["pendientes_monto"] : 0)
			+ (isset($delMes["aplicados_monto"]) ? (float) $delMes["aplicados_monto"] : 0),
		isset($delMes["pct_pendiente"]) ? $delMes["pct_pendiente"] : null,
	),
);

$fila = 9;
foreach ($filasPeriodo as $item) {
	$hoja1->setCellValue("A" . $fila, $item[0]);
	$hoja1->setCellValue("B" . $fila, (int) $item[1]);
	$hoja1->setCellValue("C" . $fila, (float) $item[2]);
	$hoja1->getStyle("C" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
	if ($item[3] === null) {
		$hoja1->setCellValue("D" . $fila, "—");
	} else {
		$hoja1->setCellValue("D" . $fila, (float) $item[3] / 100);
		$hoja1->getStyle("D" . $fila)->getNumberFormat()->setFormatCode("0.0%");
	}
	$hoja1->getStyle("A" . $fila . ":D" . $fila)->applyFromArray($estiloCelda);
	$fila++;
}

$hoja1->mergeCells("A13:F13");
$hoja1->setCellValue("A13", "Acumulado del año " . $anioReporte);
$hoja1->getStyle("A13")->applyFromArray($estiloSubtitulo);

$filaCab2 = 14;
foreach ($cabStats as $col => $titulo) {
	$hoja1->setCellValue($col . $filaCab2, $titulo);
	$hoja1->getStyle($col . $filaCab2)->applyFromArray($estiloCabecera);
}

$filasAnio = array(
	array("Pendientes", isset($acumAnio["pendientes_cant"]) ? $acumAnio["pendientes_cant"] : 0, isset($acumAnio["pendientes_monto"]) ? $acumAnio["pendientes_monto"] : 0, null),
	array("Aplicados (05/15 + OP-)", isset($acumAnio["aplicados_cant"]) ? $acumAnio["aplicados_cant"] : 0, isset($acumAnio["aplicados_monto"]) ? $acumAnio["aplicados_monto"] : 0, null),
	array(
		"Total",
		isset($acumAnio["total_cant"]) ? $acumAnio["total_cant"] : 0,
		(isset($acumAnio["pendientes_monto"]) ? (float) $acumAnio["pendientes_monto"] : 0)
			+ (isset($acumAnio["aplicados_monto"]) ? (float) $acumAnio["aplicados_monto"] : 0),
		isset($acumAnio["pct_pendiente"]) ? $acumAnio["pct_pendiente"] : null,
	),
);

$fila = 15;
foreach ($filasAnio as $item) {
	$hoja1->setCellValue("A" . $fila, $item[0]);
	$hoja1->setCellValue("B" . $fila, (int) $item[1]);
	$hoja1->setCellValue("C" . $fila, (float) $item[2]);
	$hoja1->getStyle("C" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
	if ($item[3] === null) {
		$hoja1->setCellValue("D" . $fila, "—");
	} else {
		$hoja1->setCellValue("D" . $fila, (float) $item[3] / 100);
		$hoja1->getStyle("D" . $fila)->getNumberFormat()->setFormatCode("0.0%");
	}
	$hoja1->getStyle("A" . $fila . ":D" . $fila)->applyFromArray($estiloCelda);
	$fila++;
}

$hoja1->setCellValue("A19", "Nota");
$hoja1->mergeCells("B19:F19");
$hoja1->setCellValue(
	"B19",
	"Pendientes = abonos aún en bandeja. Aplicados = movimientos en cuentas con códigos 05/15 y nota OP-. Aproximación operativa. Ver hoja Metadatos."
);
$hoja1->getStyle("A19")->applyFromArray($estiloEtiqueta);

$anchos1 = array("A" => 28, "B" => 14, "C" => 16, "D" => 14, "E" => 14, "F" => 14);
foreach ($anchos1 as $col => $ancho) {
	$hoja1->getColumnDimension($col)->setWidth($ancho);
}

/* ===========================================================================
 * HOJA 2 — DETALLE DE ABONOS PENDIENTES
 * ======================================================================== */
$hoja2 = $objPHPExcel->createSheet(1);
$hoja2->setTitle("Abonos pendientes");

$hoja2->mergeCells("A1:J1");
$hoja2->setCellValue("A1", "ABONOS PENDIENTES — " . strtoupper($periodoTexto) . " (" . $fechaHoy . ")");
$hoja2->getStyle("A1")->applyFromArray($estiloTitulo);
$hoja2->getRowDimension(1)->setRowHeight(24);

$hoja2->setCellValue("A2", "Filtro motivo: " . $motivoTexto . " | Generado por: " . $nombreGenerador);
$hoja2->getStyle("A2")->applyFromArray($estiloEtiqueta);

$cabeceras2 = array(
	"A" => "Fecha",
	"B" => "Descripción",
	"C" => "Monto S/",
	"D" => "Agencia",
	"E" => "Nombre agencia/canal",
	"F" => "Operación",
	"G" => "Motivo",
	"H" => "Observación",
	"I" => "Usuario motivo",
	"J" => "Fecha motivo",
);

$filaCabDet = 4;
foreach ($cabeceras2 as $col => $titulo) {
	$hoja2->setCellValue($col . $filaCabDet, $titulo);
	$hoja2->getStyle($col . $filaCabDet)->applyFromArray($estiloCabecera);
}

$fila = $filaCabDet + 1;
$sumMonto = 0.0;

foreach ($abonos as $abono) {
	$monto = isset($abono["monto"]) ? (float) $abono["monto"] : 0.0;
	$sumMonto += $monto;

	$agenciaRaw = isset($abono["agencia"]) ? (string) $abono["agencia"] : "";
	$match = ControladorAbonos::ctrResolverAgenciaBcp($agenciaRaw);
	$nombreAgencia = $match !== null ? $match["nombre"] : "";

	$codigoMotivo = isset($abono["motivo_pendiente"]) ? $abono["motivo_pendiente"] : "";
	$etiquetaMotivo = ControladorAbonos::ctrEtiquetaMotivoPendiente($codigoMotivo);
	if ($etiquetaMotivo === "") {
		$etiquetaMotivo = "—";
	}

	$hoja2->setCellValue("A" . $fila, isset($abono["fecha"]) ? $abono["fecha"] : "");
	$hoja2->setCellValue("B" . $fila, isset($abono["descripcion"]) ? $abono["descripcion"] : "");
	$hoja2->setCellValue("C" . $fila, $monto);
	$hoja2->getStyle("C" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
	$hoja2->getStyle("C" . $fila)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$hoja2->setCellValueExplicit("D" . $fila, $agenciaRaw, PHPExcel_Cell_DataType::TYPE_STRING);
	$hoja2->setCellValue("E" . $fila, $nombreAgencia);
	$hoja2->setCellValueExplicit(
		"F" . $fila,
		isset($abono["num_ope"]) ? (string) $abono["num_ope"] : "",
		PHPExcel_Cell_DataType::TYPE_STRING
	);
	$hoja2->setCellValue("G" . $fila, $etiquetaMotivo);
	$hoja2->setCellValue(
		"H" . $fila,
		isset($abono["observacion_pendiente"]) ? $abono["observacion_pendiente"] : ""
	);
	$hoja2->setCellValue(
		"I" . $fila,
		isset($abono["motivo_usuario"]) ? $abono["motivo_usuario"] : ""
	);
	$hoja2->setCellValue(
		"J" . $fila,
		isset($abono["motivo_fecha"]) ? $abono["motivo_fecha"] : ""
	);

	$hoja2->getStyle("A" . $fila . ":J" . $fila)->applyFromArray($estiloCelda);
	$fila++;
}

$hoja2->setCellValue("A" . $fila, "");
$hoja2->setCellValue("B" . $fila, "TOTAL (" . count($abonos) . " abonos)");
$hoja2->setCellValue("C" . $fila, $sumMonto);
$hoja2->getStyle("C" . $fila)->getNumberFormat()->setFormatCode($formatoMoneda);
$hoja2->getStyle("A" . $fila . ":J" . $fila)->applyFromArray($estiloSubtitulo);
$hoja2->getStyle("B" . $fila)->getFont()->setBold(true);
$hoja2->getStyle("C" . $fila)->getFont()->setBold(true);

$anchos2 = array(
	"A" => 12,
	"B" => 45,
	"C" => 14,
	"D" => 12,
	"E" => 28,
	"F" => 14,
	"G" => 24,
	"H" => 35,
	"I" => 18,
	"J" => 18,
);
foreach ($anchos2 as $col => $ancho) {
	$hoja2->getColumnDimension($col)->setWidth($ancho);
}
$hoja2->freezePane("A5");

/* ===========================================================================
 * HOJA 3 — METADATOS (protegida)
 * ======================================================================== */
$hoja3 = $objPHPExcel->createSheet(2);
$hoja3->setTitle("Metadatos");

$hoja3->mergeCells("A1:B1");
$hoja3->setCellValue("A1", "INFORMACIÓN DEL REPORTE — ABONOS");
$hoja3->getStyle("A1")->applyFromArray($estiloMetaTitulo);
$hoja3->getRowDimension(1)->setRowHeight(26);

$pctPeriodo = isset($delMes["pct_pendiente"]) && $delMes["pct_pendiente"] !== null
	? number_format((float) $delMes["pct_pendiente"], 1, ".", ",") . "%"
	: "—";
$pctAnio = isset($acumAnio["pct_pendiente"]) && $acumAnio["pct_pendiente"] !== null
	? number_format((float) $acumAnio["pct_pendiente"], 1, ".", ",") . "%"
	: "—";

$metaFilas = array(
	array("_sec", "DATOS DEL REPORTE"),
	array("Reporte", "Abonos — pendientes de aplicar y estadísticas"),
	array("Fecha de exportación", $fechaHora),
	array("Generado por", $nombreGenerador),
	array("Equipo (PC)", $nombrePc !== "" ? $nombrePc : "No identificado"),
	array("Periodo", $periodoTexto),
	array("Filtro motivo", $motivoTexto),
	array("Abonos en detalle", (string) count($abonos)),
	array("Monto detalle S/", "S/ " . number_format($sumMontoLista, 2, ".", ",")),
	array("% pendiente del periodo", $pctPeriodo),
	array("% pendiente acumulado año", $pctAnio),

	array("_sec", "HOJAS DEL ARCHIVO"),
	array("Hoja Resumen", "Estadísticas del periodo seleccionado y acumulado del año."),
	array("Hoja Abonos pendientes", "Detalle de abonos aún en bandeja según filtros."),
	array("Hoja Metadatos", "Responsabilidad y glosario. Solo lectura."),

	array("_sec", "GLOSARIO"),
	array("Pendientes", "Abonos bancarios cargados que todavía no se aplicaron a una cuenta corriente."),
	array("Aplicados", "Movimientos en cuentas corrientes tip_mov '-', códigos 05 (DEP. CTACTE) o 15 (YAPE-BCP), con nota OP- (nro. de operación del abono)."),
	array("% pendiente", "Pendientes ÷ (pendientes + aplicados). Aproximación operativa; un abono puede generar más de un movimiento si se aplica por partes."),
	array("Motivo", "Etiqueta de por qué el abono aún no se aplicó (No identificado, Referencia incompleta, etc.)."),
	array("Observación", "Texto libre opcional asociado al motivo."),
	array("Agencia / canal", "Código del extracto; si está en el catálogo BCP se muestra el nombre."),
	array("Alcance de fechas", "Solo se consideran abonos desde 2026 en adelante."),

	array("_sec", "NOTAS"),
	array("Uso", "Apoyo a cobranza/tesorería para seguir abonos no identificados o no aplicados a tiempo."),
	array("Actualización", "Los datos reflejan el momento de la exportación. Al aplicar un abono en Cancelar Abonos, deja de aparecer en el detalle."),
	array("Nota", "Esta hoja es de solo lectura."),
);

$filaMeta = 3;
foreach ($metaFilas as $metaItem) {
	if ($metaItem[0] === "_sec") {
		$hoja3->mergeCells("A" . $filaMeta . ":B" . $filaMeta);
		$hoja3->setCellValue("A" . $filaMeta, $metaItem[1]);
		$hoja3->getStyle("A" . $filaMeta . ":B" . $filaMeta)->applyFromArray($estiloMetaSeccion);
		$hoja3->getRowDimension($filaMeta)->setRowHeight(20);
	} else {
		$hoja3->setCellValue("A" . $filaMeta, $metaItem[0]);
		$hoja3->setCellValue("B" . $filaMeta, $metaItem[1]);
		$hoja3->getStyle("A" . $filaMeta)->applyFromArray($estiloMetaEtiqueta);
		$hoja3->getStyle("B" . $filaMeta)->applyFromArray($estiloMetaValor);
		$hoja3->getStyle("B" . $filaMeta)->getAlignment()->setWrapText(true);
	}
	$filaMeta++;
}

$hoja3->getColumnDimension("A")->setWidth(28);
$hoja3->getColumnDimension("B")->setWidth(78);
abonosExportProtegerHoja($hoja3, "B", $filaMeta - 1);

$objPHPExcel->setActiveSheetIndex(0);

$sufijoMes = ($mesReporte === "todos" || $mesReporte === null || $mesReporte === "")
	? "anual"
	: ("m" . str_pad((string) (int) $mesReporte, 2, "0", STR_PAD_LEFT));
$nombreArchivo = "abonos_" . $anioReporte . "_" . $sufijoMes . "_" . $fechaHoy . ".xls";

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
