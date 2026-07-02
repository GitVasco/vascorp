<?php

header('Content-Type: text/html; charset=ISO-8859-1');

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";

date_default_timezone_set('America/Lima');
$fecha = date("d-m-Y");

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco");
$objPHPExcel->getProperties()->setTitle("Letras urgentes protesto");

$headerStyle = [
	'font' => ['bold' => true, 'size' => 11],
	'fill' => [
		'type' => PHPExcel_Style_Fill::FILL_SOLID,
		'color' => ['rgb' => 'D7DBDD']
	],
	'borders' => [
		'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
	],
	'alignment' => [
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
	]
];

$bodyStyle = [
	'borders' => [
		'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
	],
	'alignment' => [
		'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
	]
];

$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("Urgentes");

$sheet->setCellValue('A1', 'LETRAS URGENTES Y ULTIMO DIA - PLAZO DE PROTESTO');
$sheet->mergeCells('A1:M1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Fecha de reporte:');
$sheet->setCellValue('B2', $fecha);
$sheet->getStyle('A2')->getFont()->setBold(true);

$headers = [
	'A' => 'N°',
	'B' => 'Nro. Letra',
	'C' => 'Cliente',
	'D' => 'Teléfono',
	'E' => 'Vendedor',
	'F' => 'Fec. Emisión',
	'G' => 'Fec. Vencimiento',
	'H' => 'Fec. Límite Protesto',
	'I' => 'Días háb. trans.',
	'J' => 'Días háb. rest.',
	'K' => 'Saldo',
	'L' => 'Nro. Único',
	'M' => 'Estado'
];

$fila = 4;
foreach ($headers as $col => $titulo) {
	$sheet->setCellValue($col . $fila, $titulo);
}
$sheet->getStyle('A' . $fila . ':M' . $fila)->applyFromArray($headerStyle);

$letras = ControladorCuentas::ctrLetrasPlazoProtestoUrgentes();
$cont = 0;

foreach ($letras as $letra) {
	$cont++;
	$fila++;

	$estado = $letra['estado_plazo'] === 'ULTIMO DIA' ? 'Último día' : 'Urgente';

	$sheet->setCellValue('A' . $fila, $cont);
	$sheet->setCellValueExplicit('B' . $fila, $letra['num_cta'], PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit('C' . $fila, utf8_encode($letra['cliente'] . ' - ' . $letra['nombre']), PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit('D' . $fila, utf8_encode($letra['telefono']), PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValueExplicit('E' . $fila, utf8_encode($letra['vendedor']), PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValue('F' . $fila, $letra['fecha']);
	$sheet->setCellValue('G' . $fila, $letra['fecha_ven']);
	$sheet->setCellValue('H' . $fila, $letra['fecha_limite_protesto']);
	$sheet->setCellValue('I' . $fila, $letra['dias_transcurridos']);
	$sheet->setCellValue('J' . $fila, $letra['dias_restantes']);
	$sheet->setCellValue('K' . $fila, number_format($letra['saldo'], 2));
	$sheet->setCellValueExplicit('L' . $fila, utf8_encode($letra['num_unico']), PHPExcel_Cell_DataType::TYPE_STRING);
	$sheet->setCellValue('M' . $fila, utf8_encode($estado));

	$sheet->getStyle('A' . $fila . ':M' . $fila)->applyFromArray($bodyStyle);
}

$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(16);
$sheet->getColumnDimension('C')->setWidth(40);
$sheet->getColumnDimension('D')->setWidth(14);
$sheet->getColumnDimension('E')->setWidth(10);
$sheet->getColumnDimension('F')->setWidth(14);
$sheet->getColumnDimension('G')->setWidth(14);
$sheet->getColumnDimension('H')->setWidth(18);
$sheet->getColumnDimension('I')->setWidth(14);
$sheet->getColumnDimension('J')->setWidth(14);
$sheet->getColumnDimension('K')->setWidth(12);
$sheet->getColumnDimension('L')->setWidth(16);
$sheet->getColumnDimension('M')->setWidth(14);

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="LETRAS URGENTES PROTESTO ' . $fecha . '.xls"');

$objWriter->save('php://output');
exit;
