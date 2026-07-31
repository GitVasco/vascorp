<?php

function rptDetalleProduccionEstilos()
{
    $headerStyle = new PHPExcel_Style();
    $headerStyle->applyFromArray(array(
        'font' => array('bold' => true, 'size' => 10),
        'borders' => array(
            'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            'wrap' => true,
        ),
    ));

    $cellStyle = new PHPExcel_Style();
    $cellStyle->applyFromArray(array(
        'font' => array('size' => 9),
        'borders' => array(
            'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        ),
    ));

    $cellCompStyle = new PHPExcel_Style();
    $cellCompStyle->applyFromArray(array(
        'font' => array('size' => 9, 'color' => array('rgb' => 'FF0000')),
        'borders' => array(
            'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        ),
    ));

    $totalStyle = new PHPExcel_Style();
    $totalStyle->applyFromArray(array(
        'font' => array('bold' => true, 'size' => 10),
        'borders' => array(
            'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        ),
    ));

    return array(
        'header' => $headerStyle,
        'cell' => $cellStyle,
        'cellComp' => $cellCompStyle,
        'total' => $totalStyle,
    );
}

function rptDetalleProduccionSubtitulo($info)
{
    $partes = array(strtoupper($info['nombre']));
    $prod = (float) $info['total_produccion'];
    $bono = (float) $info['bono'];
    $pendiente = (float) $info['pendiente'];

    if ($bono > 0) {
        $partes[] = 'BONO RANGO ' . $info['rango'] . ' S/' . number_format($bono, 2);
    } else {
        $partes[] = 'SIN BONO';
    }

    $partes[] = 'Producción S/' . number_format($prod, 2);

    if ($pendiente < 0) {
        $partes[] = 'Monto Pendiente S/' . number_format($pendiente, 2);
    }

    return implode(' - ', $partes);
}

function rptDetalleProduccionEsCompensacion($row)
{
    return strtoupper(trim((string) $row['cod_modelo'])) === 'C001';
}

function rptDetalleProduccionTituloPeriodo($inicio, $fin)
{
    $tsI = strtotime($inicio);
    $tsF = strtotime($fin);
    return 'DETALLE DE PRODUCCION ' . date('d-m-Y', $tsI) . ' AL ' . date('d-m-Y', $tsF);
}

function rptDetalleProduccionEscribirHoja($sheet, $inicio, $fin, $trabajador, $estilos)
{
    $info = ControladorProduccion::ctrRptDetalleProduccionTrabajadorInfo($inicio, $fin, $trabajador);
    if (!$info) {
        return false;
    }

    $filas = ControladorProduccion::ctrRptDetalleProduccionTrabajadorDetalle($inicio, $fin, $trabajador);
    $titulo = rptDetalleProduccionTituloPeriodo($inicio, $fin);
    $subtitulo = rptDetalleProduccionSubtitulo($info);

    $sheet->mergeCells('A1:T1');
    $sheet->SetCellValue('A1', $titulo);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A2:T2');
    $sheet->SetCellValue('A2', $subtitulo);
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);

    $fila = 4;
    $headers = array(
        'A' => 'trabajador',
        'B' => 'ct',
        'C' => 'sq',
        'D' => 'mes',
        'E' => 'dd',
        'F' => 'mod',
        'G' => 'des_modelo',
        'H' => 'cc',
        'I' => 'color',
        'J' => 'op',
        'K' => 'nombre',
        'L' => 'pre',
        'M' => 's',
        'N' => 'm',
        'O' => 'l',
        'P' => 'xl',
        'Q' => 'xxl',
        'R' => 'xs',
        'S' => 'unidades',
        'T' => 'soles',
    );

    foreach ($headers as $col => $label) {
        $sheet->SetCellValue($col . $fila, $label);
    }
    $sheet->setSharedStyle($estilos['header'], 'A' . $fila . ':T' . $fila);

    $sumUnidades = 0;
    $sumSoles = 0;

    foreach ($filas as $row) {
        $fila++;
        $esComp = rptDetalleProduccionEsCompensacion($row);
        $estiloFila = $esComp ? $estilos['cellComp'] : $estilos['cell'];

        $sheet->SetCellValue("A$fila", $row['trabajador']);
        $sheet->SetCellValue("B$fila", $row['cod_trab']);
        $sheet->SetCellValue("C$fila", $row['sector']);
        $sheet->SetCellValue("D$fila", $row['mes']);
        $sheet->SetCellValue("E$fila", $row['dd']);
        $sheet->SetCellValue("F$fila", $row['cod_modelo']);
        $sheet->SetCellValue("G$fila", $row['des_modelo']);
        $sheet->SetCellValue("H$fila", $row['cc']);
        $sheet->SetCellValue("I$fila", $row['color']);
        $sheet->SetCellValue("J$fila", $row['op']);
        $sheet->SetCellValue("K$fila", $row['nombre']);
        $sheet->SetCellValue("L$fila", $row['pre']);
        $sheet->SetCellValue("M$fila", $row['t1']);
        $sheet->SetCellValue("N$fila", $row['t2']);
        $sheet->SetCellValue("O$fila", $row['t3']);
        $sheet->SetCellValue("P$fila", $row['t4']);
        $sheet->SetCellValue("Q$fila", $row['t5']);
        $sheet->SetCellValue("R$fila", $row['t6']);
        $sheet->SetCellValue("S$fila", $row['unidades']);
        $sheet->SetCellValue("T$fila", $row['soles']);
        $sheet->setSharedStyle($estiloFila, "A$fila:T$fila");

        $sumUnidades += (float) $row['unidades'];
        $sumSoles += (float) $row['soles'];
    }

    $fila++;
    $sheet->mergeCells("A$fila:R$fila");
    $sheet->SetCellValue("A$fila", 'Total general');
    $sheet->SetCellValue("S$fila", $sumUnidades);
    $sheet->SetCellValue("T$fila", round($sumSoles, 2));
    $sheet->setSharedStyle($estilos['total'], "A$fila:T$fila");
    $sheet->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    foreach (range('A', 'T') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return true;
}

function rptDetalleProduccionNombreHoja($codTrab, $nombre)
{
    $hoja = preg_replace('/[\[\]\*\?:\/\\\\]/', '', $codTrab . ' ' . $nombre);
    if (function_exists('mb_substr')) {
        return mb_substr($hoja, 0, 31);
    }
    return substr($hoja, 0, 31);
}
