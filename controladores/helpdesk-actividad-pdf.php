<?php

class HelpdeskActividadPdf extends TCPDF
{
    public function Footer()
    {
        $this->SetY(-11);
        $this->SetFont("helvetica", "I", 7);
        $this->SetTextColor(100, 110, 125);
        $this->Cell(
            0,
            8,
            "Vascorp · Helpdesk TI · " . $this->getAliasNumPage() . " / " . $this->getAliasNbPages(),
            0,
            false,
            "C"
        );
    }
}
