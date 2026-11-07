<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/ordenes-servicio-prod.modelo.php";

$tcpdfBase = dirname(__FILE__) . "/../extensiones/tcpdf";
require_once $tcpdfBase . "/pdf/config/tcpdf_config_alt.php";
require_once $tcpdfBase . "/tcpdf.php";

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok"
    || !isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
    http_response_code(403);
    echo "Sin permiso para este módulo.";
    exit;
}

$orden = ModeloOrdenesServicioProd::mdlObtener(isset($_GET["id"]) ? $_GET["id"] : 0);
if (!$orden) {
    http_response_code(404);
    echo "Orden no encontrada.";
    exit;
}

class OrdenServicioProdPdf extends TCPDF
{
    public $numero = "";
    public $anulada = false;

    public function Header()
    {
        if ($this->anulada) {
            $this->SetFont("helvetica", "B", 60);
            $this->SetTextColor(235, 200, 200);
            $this->StartTransform();
            $this->Rotate(40, 105, 150);
            $this->Text(50, 150, "ANULADA");
            $this->StopTransform();
            $this->SetTextColor(0, 0, 0);
        }
    }

    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont("helvetica", "", 7);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(140, 5, "Orden de Servicio " . $this->numero . " - Impreso " . date("d/m/Y H:i"), 0, false, "L");
        $this->Cell(0, 5, "Pág. " . $this->getAliasNumPage() . " / " . $this->getAliasNbPages(), 0, false, "R");
        $this->SetTextColor(0, 0, 0);
    }
}

function fd($f, $conHora = false)
{
    if (!$f) {
        return "";
    }
    $t = strtotime($f);
    return $t ? date($conHora ? "d/m/Y H:i" : "d/m/Y", $t) : $f;
}

$W = 190; // ancho útil A4 vertical (márgenes de 10 mm)

// Paleta
$cOscuro = array(28, 44, 66);     // banda y encabezados de sección
$cAcento = array(60, 141, 188);   // azul del sistema
$cClaro = array(238, 243, 248);   // etiquetas y subtítulos
$cZebra = array(247, 249, 252);   // filas alternas
$cLinea = array(196, 205, 216);   // bordes
$cAmbar = array(255, 243, 205);
$cAmbarTxt = array(150, 95, 0);

$pdf = new OrdenServicioProdPdf("P", "mm", "A4", true, "UTF-8", false);
$pdf->numero = $orden["numero"];
$pdf->anulada = $orden["estado"] === "ANULADA";
$pdf->SetCreator("Vascorp");
$pdf->SetTitle("Orden de Servicio " . $orden["numero"]);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 14);
$pdf->AddPage();
$pdf->SetDrawColor($cLinea[0], $cLinea[1], $cLinea[2]);
$pdf->SetLineWidth(0.2);

/* ---------- Encabezado: banda con nombre de la empresa y número ---------- */
$pdf->SetFillColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->Rect(0, 0, 210, 25, "F");
$pdf->SetFillColor($cAcento[0], $cAcento[1], $cAcento[2]);
$pdf->Rect(0, 25, 210, 1.2, "F");
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("helvetica", "B", 13);
$pdf->SetXY(10, 5);
$pdf->Cell(110, 7, "CORPORACION VASCO S.A.C.", 0, 0, "L");
$pdf->SetTextColor(190, 210, 228);
$pdf->SetFont("helvetica", "", 7.5);
$pdf->SetXY(10, 13.5);
$pdf->Cell(110, 4, "RUC 20513613939", 0, 0, "L");
$pdf->SetFont("helvetica", "", 7);
$pdf->SetXY(10, 17.5);
$pdf->Cell(110, 4, "Cal. Santo Toribio Nro. 259, Urb. Santa Luisa 1ra Etapa, San Martín de Porres, Lima", 0, 0, "L");
$pdf->SetFont("helvetica", "B", 7.5);
$pdf->SetXY(100, 6);
$pdf->Cell(100, 4, "ORDEN DE SERVICIO DE PRODUCCIÓN", 0, 1, "R");
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("helvetica", "B", 17);
$pdf->SetXY(100, 11);
$pdf->Cell(100, 9, $orden["numero"], 0, 1, "R");
$pdf->SetTextColor(0, 0, 0);
$pdf->SetY(30);

/* ---------- Datos del taller y de la liquidación ---------- */
$caja = function ($titulo, $filas, $dosColumnas = false) use ($pdf, $W, $cOscuro, $cClaro) {
    if ($titulo !== null) {
        $pdf->SetFillColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont("helvetica", "B", 8);
        $pdf->Cell($W, 5.5, "  " . $titulo, 1, 1, "L", true);
        $pdf->SetTextColor(0, 0, 0);
    }
    $pdf->SetFillColor($cClaro[0], $cClaro[1], $cClaro[2]);
    $valor = function ($w, $txt, $borde, $ln, $color) use ($pdf) {
        $pdf->SetFont("helvetica", $color ? "B" : "", 8);
        if ($color) {
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
        }
        $pdf->Cell($w, 5.5, $txt, $borde, $ln, "L");
        $pdf->SetTextColor(0, 0, 0);
    };
    if ($dosColumnas) {
        for ($i = 0; $i < count($filas); $i += 2) {
            foreach (array($filas[$i], isset($filas[$i + 1]) ? $filas[$i + 1] : array("", "")) as $k => $f) {
                $pdf->SetFont("helvetica", "B", 8);
                $pdf->Cell($k === 0 ? 38 : 28, 5.5, " " . $f[0], $k === 0 ? "LB" : "B", 0, "L", true);
                $valor($k === 0 ? 57 : 67, " " . mb_substr((string) $f[1], 0, $k === 0 ? 36 : 42), $k === 0 ? "B" : "RB", $k === 0 ? 0 : 1, isset($f[2]) ? $f[2] : null);
            }
        }
        return;
    }
    foreach ($filas as $f) {
        $pdf->SetFont("helvetica", "B", 8);
        $pdf->Cell(38, 5.5, " " . $f[0], "LB", 0, "L", true);
        $valor($W - 38, " " . mb_substr((string) $f[1], 0, 95), "RB", 1, isset($f[2]) ? $f[2] : null);
    }
};
$caja("DATOS DEL TALLER (PRESTADOR DEL SERVICIO)", array(
    array("Razón social", $orden["taller_razon"]),
    array("RUC", $orden["taller_ruc"]),
    array("Dirección", $orden["taller_direccion"]),
    array("Taller (interno)", $orden["taller"] . " - " . $orden["taller_nombre"]),
));
$caja(null, array(
    array("Cta. detracción", ($orden["taller_cta_detraccion"] ? $orden["taller_cta_detraccion"] . " (B. Nación)" : "-")),
    array("Cuenta BCP", ($orden["taller_cta_bcp"] ? $orden["taller_cta_bcp"] : "-")),
), true);
$pdf->Ln(3);
$estadoTxt = $orden["estado"];
$colorEstado = $orden["estado"] === "ANULADA" ? array(190, 30, 30) : array(0, 130, 70);
$filasLiq = array(
    array("Periodo servicios", fd($orden["fecha_desde"]) . " al " . fd($orden["fecha_hasta"])),
    array("Emitido por", $orden["usuario_nombre"]),
    array("Estado", $estadoTxt . ($orden["estado"] === "ANULADA" ? " - " . $orden["motivo_anulacion"] : ""), $colorEstado),
);
$caja("DATOS DE LA ORDEN", $filasLiq, true);
$pdf->Ln(4);

/* Título de sección con barra de acento */
$seccion = function ($texto) use ($pdf, $cAcento, $cOscuro) {
    $y = $pdf->GetY();
    $pdf->SetFillColor($cAcento[0], $cAcento[1], $cAcento[2]);
    $pdf->Rect(10, $y + 0.5, 1.6, 4.5, "F");
    $pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
    $pdf->SetFont("helvetica", "B", 9);
    $pdf->SetX(13.5);
    $pdf->Cell(0, 5.5, $texto, 0, 1, "L");
    $pdf->SetTextColor(0, 0, 0);
};
$cabTabla = function () use ($pdf, $cOscuro) {
    $pdf->SetFillColor($cOscuro[0] + 20, $cOscuro[1] + 25, $cOscuro[2] + 30);
    $pdf->SetTextColor(255, 255, 255);
};


/* ---------- Resumen de guías ---------- */
$seccion("GUÍAS DE REMISIÓN Y SERVICIOS INCLUIDOS");
$pdf->SetFont("helvetica", "B", 8);
$cabTabla();
$pdf->Cell(40, 5.5, "Servicio", 1, 0, "C", true);
$pdf->Cell(60, 5.5, "Guía de remisión", 1, 0, "C", true);
$pdf->Cell(50, 5.5, "Fecha servicio", 1, 0, "C", true);
$pdf->Cell(40, 5.5, "Unidades", 1, 1, "C", true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont("helvetica", "", 8);
$pend = 0;
$n = 0;
foreach ($orden["servicios"] as $s) {
    $tieneGuia = $s["guia"] !== null && $s["guia"] !== "";
    $z = ($n++ % 2) === 1;
    $pdf->SetFillColor($cZebra[0], $cZebra[1], $cZebra[2]);
    $pdf->Cell(40, 5.5, $s["servicio"], 1, 0, "C", $z);
    if ($tieneGuia) {
        $pdf->Cell(60, 5.5, $s["guia"], 1, 0, "C", $z);
    } else {
        $pend++;
        $pdf->SetFillColor($cAmbar[0], $cAmbar[1], $cAmbar[2]);
        $pdf->SetTextColor($cAmbarTxt[0], $cAmbarTxt[1], $cAmbarTxt[2]);
        $pdf->Cell(60, 5.5, "PENDIENTE", 1, 0, "C", true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor($cZebra[0], $cZebra[1], $cZebra[2]);
    }
    $pdf->Cell(50, 5.5, fd($s["fecha_servicio"]), 1, 0, "C", $z);
    $pdf->Cell(40, 5.5, number_format($s["unidades"]), 1, 1, "R", $z);
}
if ($pend > 0) {
    $pdf->Ln(1);
    $pdf->SetFillColor($cAmbar[0], $cAmbar[1], $cAmbar[2]);
    $pdf->SetTextColor($cAmbarTxt[0], $cAmbarTxt[1], $cAmbarTxt[2]);
    $pdf->SetFont("helvetica", "I", 7);
    $pdf->MultiCell($W, 5, " Guías pendientes de emisión: " . $pend . ". Esta orden precede a la guía de remisión; reimprímala una vez emitidas para que figuren los números.", 0, "L", true);
    $pdf->SetTextColor(0, 0, 0);
}
$pdf->Ln(4);

/* ---------- Detalle por servicio ---------- */
// Solo se muestran las tallas que realmente tienen cantidades en esta orden
$tallas = ModeloOrdenesServicioProd::TALLAS;
$usadas = array();
foreach ($orden["servicios"] as $s) {
    foreach ($s["detalle"] as $d) {
        for ($i = 1; $i <= 8; $i++) {
            if ((int) $d["t$i"] > 0) {
                $usadas[$i] = true;
            }
        }
    }
}
$usadas = array_keys($usadas);
sort($usadas);

$wTalla = 9;
$wFin = array("Total" => 13, "Docenas" => 13, "P. doc." => 13, "Importe" => 19);
$wModelo = 14;
$wColor = 26;
$wDesc = $W - $wModelo - $wColor - $wTalla * count($usadas) - array_sum($wFin);
$charsDesc = max(8, (int) floor($wDesc / 1.55));
$charsColor = (int) floor($wColor / 1.55);

$cabeceraTabla = function () use ($pdf, $tallas, $usadas, $wTalla, $wFin, $wModelo, $wColor, $wDesc, $cabTabla) {
    $pdf->SetFont("helvetica", "B", 7);
    $cabTabla();
    $pdf->Cell($wModelo, 5.5, "Modelo", 1, 0, "C", true);
    $pdf->Cell($wDesc, 5.5, "Descripción", 1, 0, "C", true);
    $pdf->Cell($wColor, 5.5, "Color", 1, 0, "C", true);
    foreach ($usadas as $i) {
        $pdf->Cell($wTalla, 5.5, $tallas[$i - 1], 1, 0, "C", true);
    }
    foreach ($wFin as $t => $w) {
        $pdf->Cell($w, 5.5, $t, 1, 0, "C", true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont("helvetica", "", 7);
};

$seccion("DETALLE DE PRENDAS ENVIADAS A SERVICIO");
$wCola = $wFin["Total"] + $wFin["Docenas"] + $wFin["P. doc."] + $wFin["Importe"];

foreach ($orden["servicios"] as $s) {
    if ($pdf->GetY() > 235) {
        $pdf->AddPage();
        $pdf->SetY(14);
    }
    $tieneGuia = $s["guia"] !== null && $s["guia"] !== "";
    $pdf->SetFont("helvetica", "B", 8);
    $pdf->SetFillColor($cClaro[0], $cClaro[1], $cClaro[2]);
    $pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
    $pdf->Cell($W, 5.5, "  Servicio " . $s["servicio"] . "   |   Guía: " . ($tieneGuia ? $s["guia"] : "PENDIENTE DE EMISIÓN") . "   |   Fecha: " . fd($s["fecha_servicio"]), 1, 1, "L", true);
    $pdf->SetTextColor(0, 0, 0);
    $cabeceraTabla();
    $subU = 0;
    $subI = 0;
    $n = 0;
    foreach ($s["detalle"] as $d) {
        if ($pdf->GetY() > 265) {
            $pdf->AddPage();
            $pdf->SetY(14);
            $cabeceraTabla();
        }
        $z = ($n++ % 2) === 1;
        $pdf->SetFillColor($cZebra[0], $cZebra[1], $cZebra[2]);
        $pdf->Cell($wModelo, 5, $d["modelo"], 1, 0, "L", $z);
        $pdf->Cell($wDesc, 5, mb_substr((string) $d["nombre"], 0, $charsDesc), 1, 0, "L", $z);
        $pdf->Cell($wColor, 5, mb_substr((string) $d["color"], 0, $charsColor), 1, 0, "L", $z);
        foreach ($usadas as $i) {
            $v = (int) $d["t$i"];
            $pdf->Cell($wTalla, 5, $v > 0 ? $v : "", 1, 0, "C", $z);
        }
        $pdf->Cell($wFin["Total"], 5, number_format($d["cantidad"]), 1, 0, "R", $z);
        $pdf->Cell($wFin["Docenas"], 5, number_format($d["cantidad"] / 12, 2), 1, 0, "R", $z);
        $pdf->Cell($wFin["P. doc."], 5, number_format($d["precio_doc"], 2), 1, 0, "R", $z);
        $pdf->Cell($wFin["Importe"], 5, number_format($d["importe"], 2), 1, 1, "R", $z);
        $subU += (int) $d["cantidad"];
        $subI += (float) $d["importe"];
    }
    $pdf->SetFont("helvetica", "B", 7);
    $pdf->SetFillColor(226, 234, 243);
    $pdf->Cell($W - $wCola, 5.5, "Subtotal servicio " . $s["servicio"], 1, 0, "R", true);
    $pdf->Cell($wFin["Total"], 5.5, number_format($subU), 1, 0, "R", true);
    $pdf->Cell($wFin["Docenas"], 5.5, number_format($subU / 12, 2), 1, 0, "R", true);
    $pdf->Cell($wFin["P. doc."], 5.5, "", 1, 0, "R", true);
    $pdf->Cell($wFin["Importe"], 5.5, number_format($subI, 2), 1, 1, "R", true);
    $pdf->Ln(4);
}

/* ---------- Totales, observaciones y firmas ---------- */
if ($pdf->GetY() > 245) {
    $pdf->AddPage();
    $pdf->SetY(14);
}
$pdf->SetFillColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("helvetica", "B", 9);
$pdf->Cell($W - 85, 8, "TOTAL ORDEN  (" . number_format($orden["total_servicios"]) . " servicios)  ", 0, 0, "R", true);
$pdf->Cell(25, 8, number_format($orden["total_unidades"]) . " un.", 0, 0, "R", true);
$pdf->SetFillColor($cAcento[0], $cAcento[1], $cAcento[2]);
$pdf->SetFont("helvetica", "B", 12);
$pdf->Cell(60, 8, "S/ " . number_format($orden["total_importe"], 2) . "  ", 0, 1, "R", true);
$pdf->SetTextColor(110, 110, 110);
$pdf->SetFont("helvetica", "I", 7);
$pdf->Cell(0, 5, "Importes sin IGV, calculados con el precio por docena vigente en el sistema al momento de emitir la orden.", 0, 1, "L");
$pdf->SetTextColor(0, 0, 0);

if (trim((string) $orden["observaciones"]) !== "") {
    $pdf->Ln(1);
    $seccion("Observaciones");
    $pdf->SetFont("helvetica", "", 8);
    $pdf->MultiCell(0, 4, $orden["observaciones"], 0, "L");
}

if ($pdf->GetY() > 255) {
    $pdf->AddPage();
    $pdf->SetY(14);
}
$pdf->Ln(14);
$y = $pdf->GetY();
$pdf->SetDrawColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->Line(10, $y, 68, $y);
$pdf->Line(76, $y, 134, $y);
$pdf->Line(142, $y, 200, $y);
$pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetFont("helvetica", "", 7.5);
$pdf->SetXY(10, $y + 1);
$pdf->Cell(58, 4, "Emitido por: " . mb_substr((string) $orden["usuario_nombre"], 0, 22), 0, 0, "C");
$pdf->SetX(76);
$pdf->Cell(58, 4, "V°B° Producción / Gerencia", 0, 0, "C");
$pdf->SetX(142);
$pdf->Cell(58, 4, "Recibí conforme - Taller", 0, 1, "C");
$pdf->SetX(142);
$pdf->Cell(58, 4, "(firma y sello)", 0, 1, "C");

$pdf->Output("OrdenServicio_" . $orden["numero"] . ".pdf", "I");
