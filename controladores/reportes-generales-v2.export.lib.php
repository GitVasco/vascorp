<?php

/**
 * Exportación unificada Reportes generales v2 (Excel + PDF desde datos de preview).
 */
class ReportesGeneralesV2ExportLib
{
    const PDF_LINE_HEIGHT = 3.5;

    public static function emit($formato, array $payload)
    {
        $layout = self::buildExportLayout($payload);
        if ($formato === 'pdf') {
            self::emitPdf($layout);
            return;
        }
        self::emitExcel($layout, $payload);
    }

    /**
     * Layout compartido Excel / PDF.
     */
    private static function buildExportLayout(array $payload)
    {
        $title = isset($payload['title']) ? (string) $payload['title'] : 'Reporte';
        $columns = isset($payload['columns']) && is_array($payload['columns']) ? $payload['columns'] : array();
        $rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : array();
        $kpis = isset($payload['kpis']) && is_array($payload['kpis']) ? $payload['kpis'] : array();
        $orientation = self::resolveOrientation($columns);
        $widths = self::columnWidths($columns, $orientation);

        return array(
            'title' => $title,
            'reporteId' => isset($payload['reporteId']) ? (string) $payload['reporteId'] : 'reporte',
            'columns' => $columns,
            'rows' => $rows,
            'kpis' => $kpis,
            'orientation' => $orientation,
            'widths' => $widths,
            'truncated' => !empty($payload['truncated']),
            'kpiLine' => self::formatKpiLine($kpis),
        );
    }

    public static function emitExcel(array $layout, array $payload)
    {
        require_once dirname(__FILE__) . '/../vistas/reportes_excel/Classes/PHPExcel.php';

        $columns = $layout['columns'];
        $colCount = count($columns);
        $lastColIndex = max($colCount - 1, 0);
        $lastColLetter = PHPExcel_Cell::stringFromColumnIndex($lastColIndex);

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $safeTitle = preg_replace('/[^\w\s\-]/u', '', $layout['title']);
        if ($safeTitle === '') {
            $safeTitle = 'Reporte';
        }
        $sheet->setTitle(substr($safeTitle, 0, 31));

        $row = 1;
        $sheet->mergeCells('A' . $row . ':' . $lastColLetter . $row);
        $sheet->setCellValue('A' . $row, 'CORPORACION VASCO S.A.C.');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->mergeCells('A' . $row . ':' . $lastColLetter . $row);
        $sheet->setCellValue('A' . $row, $layout['title']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        $sheet->mergeCells('A' . $row . ':' . $lastColLetter . $row);
        $sheet->setCellValue('A' . $row, 'Generado: ' . date('d/m/Y H:i'));
        $row++;

        if ($layout['kpiLine'] !== '') {
            $sheet->mergeCells('A' . $row . ':' . $lastColLetter . $row);
            $sheet->setCellValue('A' . $row, $layout['kpiLine']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $row++;
        $headerRow = $row;
        $colIndex = 0;
        foreach ($columns as $col) {
            $label = isset($col['label']) ? (string) $col['label'] : '';
            $sheet->setCellValueByColumnAndRow($colIndex, $headerRow, $label);
            $sheet->getStyleByColumnAndRow($colIndex, $headerRow)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($colIndex, $headerRow)->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E6E6E6');
            $excelWidth = self::columnExcelWidth($col, $layout['widths'][$colIndex]);
            $sheet->getColumnDimensionByColumn($colIndex)->setWidth($excelWidth);
            $colIndex++;
        }

        $rowNum = $headerRow + 1;
        foreach ($layout['rows'] as $dataRow) {
            if (!is_array($dataRow)) {
                continue;
            }
            $colIndex = 0;
            $rowType = isset($dataRow['_rowType']) ? (string) $dataRow['_rowType'] : 'detail';
            foreach ($columns as $col) {
                $key = isset($col['key']) ? (string) $col['key'] : '';
                $val = ($key !== '' && isset($dataRow[$key])) ? $dataRow[$key] : '';
                $sheet->setCellValueByColumnAndRow($colIndex, $rowNum, $val);
                if ($rowType !== 'detail') {
                    $sheet->getStyleByColumnAndRow($colIndex, $rowNum)->getFont()->setBold(true);
                }
                if (self::isWrapColumn($col)) {
                    $sheet->getStyleByColumnAndRow($colIndex, $rowNum)->getAlignment()->setWrapText(true);
                }
                if (isset($col['type']) && $col['type'] === 'money') {
                    $sheet->getStyleByColumnAndRow($colIndex, $rowNum)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                }
                $colIndex++;
            }
            $rowNum++;
        }

        if ($layout['truncated']) {
            $rowNum++;
            $sheet->mergeCells('A' . $rowNum . ':' . $lastColLetter . $rowNum);
            $sheet->setCellValue(
                'A' . $rowNum,
                'Nota: exportacion limitada a ' . ReportesGeneralesV2Servicio::EXPORT_LIMIT . ' filas.'
            );
        }

        $sheet->freezePane('A' . ($headerRow + 1));

        $filename = self::buildFilename($layout, 'xls');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new PHPExcel_Writer_Excel5($objPHPExcel);
        $writer->save('php://output');
        exit;
    }

    public static function emitPdf(array $layout)
    {
        require_once dirname(__FILE__) . '/reportes-generales-v2.export.pdf.php';

        $columns = $layout['columns'];
        $rows = $layout['rows'];
        $orientation = $layout['orientation'];
        $widths = $layout['widths'];

        $pdf = new ReportesGeneralesV2ExportPdf($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->reportTitle = $layout['title'];
        $pdf->pageOrientation = $orientation;
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetMargins(8, 22, 8);
        $pdf->SetHeaderMargin(4);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->AddPage($orientation);

        if ($layout['kpiLine'] !== '') {
            $pdf->MultiCell(0, 4, $layout['kpiLine'], 0, 'L', false, 1);
            $pdf->Ln(1);
        }

        self::pdfTableHeader($pdf, $columns, $widths);

        foreach ($rows as $dataRow) {
            if (!is_array($dataRow)) {
                continue;
            }
            $rowType = isset($dataRow['_rowType']) ? (string) $dataRow['_rowType'] : 'detail';
            if ($rowType !== 'detail') {
                $pdf->SetFont('helvetica', 'B', 7);
            } else {
                $pdf->SetFont('helvetica', '', 7);
            }
            self::pdfTableRow($pdf, $columns, $widths, $dataRow);
        }

        if ($layout['truncated']) {
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'I', 7);
            $pdf->MultiCell(
                0,
                4,
                'Nota: exportacion limitada a ' . ReportesGeneralesV2Servicio::EXPORT_LIMIT . ' filas.',
                0,
                'L',
                false,
                1
            );
        }

        $filename = self::buildFilename($layout, 'pdf');
        $pdf->Output($filename, 'D');
        exit;
    }

    private static function formatKpiLine(array $kpis)
    {
        $parts = array();
        foreach ($kpis as $kpi) {
            if (!is_array($kpi)) {
                continue;
            }
            $label = isset($kpi['label']) ? (string) $kpi['label'] : '';
            $value = isset($kpi['value']) ? (string) $kpi['value'] : '';
            if ($label === '' && $value === '') {
                continue;
            }
            $parts[] = $label . ': ' . $value;
        }
        return implode('   |   ', $parts);
    }

    private static function resolveOrientation(array $columns)
    {
        if (count($columns) >= 7) {
            return 'L';
        }
        foreach ($columns as $col) {
            $key = isset($col['key']) ? (string) $col['key'] : '';
            if (in_array($key, array('nombre', 'notas', 'concepto', 'doc_origen'), true)) {
                return 'L';
            }
        }
        return 'P';
    }

    private static function columnWeight($key)
    {
        $weights = array(
            'tipo_doc' => 1.0,
            'num_cta' => 1.6,
            'fecha' => 1.3,
            'fecha_ven' => 1.3,
            'vendedor' => 0.9,
            'cliente' => 1.0,
            'nombre' => 4.0,
            'saldo' => 1.4,
            'saldo_fecha' => 1.4,
            'protesta' => 0.9,
            'num_unico' => 1.6,
            'banco' => 1.1,
            'concepto' => 3.5,
            'estado_doc' => 1.2,
            'tip_mov' => 0.8,
            'monto' => 1.3,
            'cod_pago' => 1.0,
            'doc_origen' => 2.0,
            'notas' => 2.5,
            'fact' => 1.2,
            'letra' => 1.2,
        );
        return isset($weights[$key]) ? (float) $weights[$key] : 1.0;
    }

    private static function columnWidths(array $columns, $orientation)
    {
        $pageWidth = ($orientation === 'L') ? 277.0 : 190.0;
        $pageWidth -= 16.0;
        $totalWeight = 0.0;
        foreach ($columns as $col) {
            $key = isset($col['key']) ? (string) $col['key'] : '';
            $totalWeight += self::columnWeight($key);
        }
        if ($totalWeight <= 0) {
            $totalWeight = (float) max(count($columns), 1);
        }

        $widths = array();
        foreach ($columns as $col) {
            $key = isset($col['key']) ? (string) $col['key'] : '';
            $widths[] = round($pageWidth * self::columnWeight($key) / $totalWeight, 1);
        }
        return $widths;
    }

    private static function columnExcelWidth(array $col, $pdfWidth)
    {
        $key = isset($col['key']) ? (string) $col['key'] : '';
        $base = max(8, round($pdfWidth * 0.38, 1));
        if (in_array($key, array('nombre', 'concepto', 'notas', 'doc_origen'), true)) {
            $base = max($base, 28);
        }
        return $base;
    }

    private static function isWrapColumn(array $col)
    {
        $key = isset($col['key']) ? (string) $col['key'] : '';
        return in_array($key, array('nombre', 'concepto', 'notas', 'doc_origen'), true);
    }

    private static function sanitizePdfText($text)
    {
        $text = str_replace("\r", '', (string) $text);
        $text = str_replace("\n", ' ', $text);
        return trim($text);
    }

    private static function buildFilename(array $layout, $ext)
    {
        $id = isset($layout['reporteId']) ? (string) $layout['reporteId'] : 'reporte';
        $id = preg_replace('/[^a-z0-9_\-]/i', '_', $id);
        return 'rgv2_' . $id . '_' . date('Ymd_His') . '.' . $ext;
    }

    private static function pdfTableHeader($pdf, array $columns, array $widths)
    {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(230, 230, 230);
        $i = 0;
        foreach ($columns as $col) {
            $label = isset($col['label']) ? (string) $col['label'] : '';
            $w = isset($widths[$i]) ? $widths[$i] : 20;
            $pdf->Cell($w, 5, $label, 1, 0, 'C', true);
            $i++;
        }
        $pdf->Ln();
    }

    private static function pdfTableRow($pdf, array $columns, array $widths, array $row)
    {
        $lineH = self::PDF_LINE_HEIGHT;
        $cells = array();
        $nb = 1;

        foreach ($columns as $i => $col) {
            $key = isset($col['key']) ? (string) $col['key'] : '';
            $val = ($key !== '' && isset($row[$key])) ? (string) $row[$key] : '';
            $val = self::sanitizePdfText($val);
            $w = isset($widths[$i]) ? $widths[$i] : 20;
            $align = (isset($col['type']) && $col['type'] === 'money') ? 'R' : 'L';
            $n = (int) $pdf->getNumLines($val, $w);
            if ($n < 1) {
                $n = 1;
            }
            if ($n > $nb) {
                $nb = $n;
            }
            $cells[] = array('w' => $w, 'val' => $val, 'align' => $align);
        }

        $h = $lineH * $nb;
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pageBreak = $pdf->getPageHeight() - $pdf->getBreakMargin();

        if ($y + $h > $pageBreak) {
            $pdf->AddPage($pdf->pageOrientation);
            $y = $pdf->GetY();
            $x = $pdf->GetX();
            self::pdfTableHeader($pdf, $columns, $widths);
            $y = $pdf->GetY();
            $x = $pdf->GetX();
        }

        foreach ($cells as $cell) {
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(
                $cell['w'],
                $lineH,
                $cell['val'],
                1,
                $cell['align'],
                false,
                0,
                $x,
                $y,
                true,
                0,
                false,
                true,
                $h,
                'M'
            );
            $x += $cell['w'];
        }

        $margins = $pdf->getMargins();
        $pdf->SetXY($margins['left'], $y + $h);
    }
}
