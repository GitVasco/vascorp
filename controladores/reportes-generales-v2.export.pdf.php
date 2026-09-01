<?php

$tcpdfBase = dirname(__FILE__) . '/../extensiones/tcpdf';
require_once $tcpdfBase . '/pdf/config/tcpdf_config_alt.php';
require_once $tcpdfBase . '/tcpdf.php';

if (!class_exists('TCPDF')) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'No se pudo cargar la libreria PDF.';
    exit;
}

class ReportesGeneralesV2ExportPdf extends TCPDF
{
    public $reportTitle = 'Reporte';
    public $pageOrientation = 'P';

    public function Header()
    {
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(0, 5, 'CORPORACION VASCO S.A.C.', 0, false, 'L');
        $this->Cell(0, 5, date('d/m/Y H:i'), 0, true, 'R');
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(0, 6, $this->reportTitle, 0, true, 'C');
        $this->Ln(1);
    }
}
