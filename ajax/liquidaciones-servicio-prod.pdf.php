<?php

session_start();

date_default_timezone_set("America/Lima");

require_once "../controladores/config.php";
require_once "../modelos/conexion.php";
require_once "../modelos/liquidaciones-servicio-prod.modelo.php";

$tcpdfBase = dirname(__FILE__) . "/../extensiones/tcpdf";
require_once $tcpdfBase . "/pdf/config/tcpdf_config_alt.php";
require_once $tcpdfBase . "/tcpdf.php";

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok"
    || !isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
    http_response_code(403);
    echo "Sin permiso para este módulo.";
    exit;
}

$liq = ModeloLiquidacionesServicioProd::mdlObtener(isset($_GET["id"]) ? $_GET["id"] : 0);
if (!$liq) {
    http_response_code(404);
    echo "Liquidación no encontrada.";
    exit;
}

class LiquidacionServicioProdPdf extends TCPDF
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
        $this->Cell(140, 5, "Liquidación de Servicios " . $this->numero . " - Impreso " . date("d/m/Y H:i"), 0, false, "L");
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

$pdf = new LiquidacionServicioProdPdf("P", "mm", "A4", true, "UTF-8", false);
$pdf->numero = $liq["numero"];
$pdf->anulada = $liq["estado"] === "ANULADA";
$pdf->SetCreator("Vascorp");
$pdf->SetTitle("Liquidación de Servicios " . $liq["numero"]);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 14);
$pdf->AddPage();
$pdf->SetDrawColor($cLinea[0], $cLinea[1], $cLinea[2]);
$pdf->SetLineWidth(0.2);

/* ---------- Encabezado: banda con nombre de la empresa y número ---------- */
$banda = function ($subtitulo) use ($pdf, $cOscuro, $cAcento, $liq) {
    $pdf->SetFillColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
    $pdf->Rect(0, 0, 210, 21, "F");
    $pdf->SetFillColor($cAcento[0], $cAcento[1], $cAcento[2]);
    $pdf->Rect(0, 21, 210, 1.2, "F");
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont("helvetica", "B", 13);
    $pdf->SetXY(10, 4);
    $pdf->Cell(110, 7, "CORPORACION VASCO S.A.C.", 0, 0, "L");
    $pdf->SetTextColor(190, 210, 228);
    $pdf->SetFont("helvetica", "", 7.5);
    $pdf->SetXY(10, 11.2);
    $pdf->Cell(110, 4, "RUC 20513613939", 0, 0, "L");
    $pdf->SetFont("helvetica", "", 7);
    $pdf->SetXY(10, 14.8);
    $pdf->Cell(110, 4, "Cal. Santo Toribio Nro. 259, Urb. Santa Luisa 1ra Etapa, San Martín de Porres, Lima", 0, 0, "L");
    $pdf->SetFont("helvetica", "B", 7.5);
    $pdf->SetXY(100, 4.5);
    $pdf->Cell(100, 4, $subtitulo, 0, 1, "R");
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont("helvetica", "B", 17);
    $pdf->SetXY(100, 9);
    $pdf->Cell(100, 9, $liq["numero"], 0, 1, "R");
    $pdf->SetTextColor(0, 0, 0);

};
$banda("LIQUIDACIÓN DE SERVICIOS DE PRODUCCIÓN");
$pdf->SetY(25);

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
$estadoTxt = $liq["estado"] === "LIQUIDADA" ? "POR PAGAR" : $liq["estado"];
$colorEstado = $liq["estado"] === "PAGADA" ? array(0, 130, 70) : ($liq["estado"] === "ANULADA" ? array(190, 30, 30) : $cAmbarTxt);
$caja("TALLER (PRESTADOR DEL SERVICIO) Y LIQUIDACIÓN", array(
    array("Razón social", $liq["taller_razon"]),
    array("Dirección", $liq["taller_direccion"]),
));
$caja(null, array(
    array("RUC", $liq["taller_ruc"]),
    array("Taller (interno)", $liq["taller"] . " - " . $liq["taller_nombre"]),
    array("Cta. detracción", ($liq["taller_cta_detraccion"] ? $liq["taller_cta_detraccion"] . " (B. Nación)" : "-")),
    array("Cuenta BCP", ($liq["taller_cta_bcp"] ? $liq["taller_cta_bcp"] : "-")),
    array("Periodo cierres", fd($liq["fecha_desde"]) . " al " . fd($liq["fecha_hasta"])),
    array("Estado", $estadoTxt . ($liq["estado"] === "ANULADA" ? " - " . $liq["motivo_anulacion"] : ""), $colorEstado),
), true);
if ($liq["estado"] === "PAGADA") {
    $caja(null, array(
        array("Fecha de pago", fd($liq["fecha_pago"])),
        array("Referencia", $liq["referencia_pago"]),
    ), true);
}
$pdf->Ln(3);

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

/* ---------- Detalle por cierre ---------- */
// Solo se muestran las tallas que realmente tienen cantidades en esta liquidación
$tallas = ModeloLiquidacionesServicioProd::TALLAS;
$usadas = array();
foreach ($liq["cierres"] as $c) {
    foreach ($c["detalle"] as $d) {
        for ($i = 1; $i <= 8; $i++) {
            if ((int) $d["t$i"] > 0) {
                $usadas[$i] = true;
            }
        }
    }
}
$usadas = array_keys($usadas);
sort($usadas);

$wTalla = 8;
$wFin = array("Total" => 12, "Docenas" => 12, "P. doc." => 12, "Importe" => 18);
$wModelo = 13;
$wColor = 24;
$wOsp = 19;
$wServ = 14;
$wDesc = $W - $wModelo - $wColor - $wOsp - $wServ - $wTalla * count($usadas) - array_sum($wFin);
$charsDesc = max(8, (int) floor($wDesc / 1.55));
$charsColor = (int) floor($wColor / 1.55);

$cabeceraTabla = function () use ($pdf, $tallas, $usadas, $wTalla, $wFin, $wModelo, $wColor, $wDesc, $wOsp, $wServ, $cabTabla) {
    $pdf->SetFont("helvetica", "B", 7);
    $cabTabla();
    $pdf->Cell($wModelo, 5.5, "Modelo", 1, 0, "C", true);
    $pdf->Cell($wDesc, 5.5, "Descripción", 1, 0, "C", true);
    $pdf->Cell($wColor, 5.5, "Color", 1, 0, "C", true);
    $pdf->Cell($wServ, 5.5, "Servicio", 1, 0, "C", true);
    $pdf->Cell($wOsp, 5.5, "OSP", 1, 0, "C", true);
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

$seccion("DETALLE DE PRENDAS TERMINADAS");
$notaIgv = !empty($liq["precios_igv"])
    ? "Nota: el P. doc. (precio por docena) y el Importe de este detalle INCLUYEN IGV."
    : "Nota: el P. doc. (precio por docena) y el Importe de este detalle NO incluyen IGV (valores netos).";
$pdf->SetFillColor($cClaro[0], $cClaro[1], $cClaro[2]);
$pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetFont("helvetica", "B", 7.5);
$pdf->Cell($W, 5, "  " . $notaIgv, 1, 1, "L", true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(1.5);
$wCola = $wFin["Total"] + $wFin["Docenas"] + $wFin["P. doc."] + $wFin["Importe"];

foreach ($liq["cierres"] as $c) {
    if ($pdf->GetY() > 235) {
        $pdf->AddPage();
        $pdf->SetY(14);
    }
    $pdf->SetFont("helvetica", "B", 8);
    $pdf->SetFillColor($cClaro[0], $cClaro[1], $cClaro[2]);
    $pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
    $pdf->Cell($W, 5.5, "  Cierre " . $c["cierre"] . "   |   Guía: " . $c["guia"] . "   |   Fecha: " . fd($c["fecha_cierre"]), 1, 1, "L", true);
    $pdf->SetTextColor(0, 0, 0);
    $cabeceraTabla();
    $subU = 0;
    $subI = 0;
    $n = 0;
    foreach ($c["detalle"] as $d) {
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
        $pdf->Cell($wServ, 5, (string) $d["servicio"], 1, 0, "C", $z);
        if ($d["osp_numero"]) {
            $pdf->Cell($wOsp, 5, $d["osp_numero"], 1, 0, "C", $z);
        } else {
            $pdf->SetFillColor($cAmbar[0], $cAmbar[1], $cAmbar[2]);
            $pdf->SetTextColor($cAmbarTxt[0], $cAmbarTxt[1], $cAmbarTxt[2]);
            $pdf->Cell($wOsp, 5, "SIN OSP", 1, 0, "C", true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor($cZebra[0], $cZebra[1], $cZebra[2]);
        }
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
    $pdf->Cell($W - $wCola, 5.5, "Subtotal cierre " . $c["cierre"], 1, 0, "R", true);
    $pdf->Cell($wFin["Total"], 5.5, number_format($subU), 1, 0, "R", true);
    $pdf->Cell($wFin["Docenas"], 5.5, number_format($subU / 12, 2), 1, 0, "R", true);
    $pdf->Cell($wFin["P. doc."], 5.5, "", 1, 0, "R", true);
    $pdf->Cell($wFin["Importe"], 5.5, number_format($subI, 2), 1, 1, "R", true);
    $pdf->Ln(4);
}

/* ---------- Totales, observaciones y firmas ---------- */
$sinOspTotal = 0;
foreach ($liq["cierres"] as $c) {
    foreach ($c["detalle"] as $d) {
        if (!$d["osp_numero"]) {
            $sinOspTotal++;
        }
    }
}
// Totales por modelo (base de los totales de la hoja y del cuadro «Cómo facturar»)
$tasaIgv = 0.18;
$conIgv = !empty($liq["precios_igv"]);
$porModelo = array();
foreach ($liq["cierres"] as $c) {
    foreach ($c["detalle"] as $d) {
        $k = (string) $d["modelo"];
        if (!isset($porModelo[$k])) {
            $porModelo[$k] = array("modelo" => $k, "nombre" => $d["nombre"], "cantidad" => 0, "importe" => 0.0);
        }
        $porModelo[$k]["cantidad"] += (int) $d["cantidad"];
        $porModelo[$k]["importe"] += (float) $d["importe"];
    }
}
ksort($porModelo);
$sumNeto = 0;
$sumIgv = 0;
$sumTotal = 0;
$sumCant = 0;
foreach ($porModelo as $k => $m) {
    $imp = round($m["importe"], 2);
    if ($conIgv) {
        $total = $imp;
        $neto = round($total / (1 + $tasaIgv), 2);
        $igvM = round($total - $neto, 2);
    } else {
        $neto = $imp;
        $igvM = round($neto * $tasaIgv, 2);
        $total = round($neto + $igvM, 2);
    }
    $docs = $m["cantidad"] / 12;
    $porModelo[$k]["docs"] = $docs;
    $porModelo[$k]["neto"] = $neto;
    $porModelo[$k]["igv"] = $igvM;
    $porModelo[$k]["total"] = $total;
    $porModelo[$k]["p_sin"] = $docs > 0 ? round($neto / $docs, 2) : 0;
    $porModelo[$k]["p_con"] = $docs > 0 ? round($total / $docs, 2) : 0;
    $sumNeto += $neto;
    $sumIgv += $igvM;
    $sumTotal += $total;
    $sumCant += $m["cantidad"];
}
if ($pdf->GetY() > 245) {
    $pdf->AddPage();
    $pdf->SetY(14);
}
// Neto / IGV / Total: depende de si los precios del taller ya incluyen IGV (marca congelada al emitir)
$neto = round($sumNeto, 2);
$igv = round($sumIgv, 2);
$totalPagar = round($sumTotal, 2);
$pdf->SetFillColor($cClaro[0], $cClaro[1], $cClaro[2]);
$pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetFont("helvetica", "", 9);
$pdf->Cell($W - 60, 5.5, "SUBTOTAL NETO (sin IGV)  ", 1, 0, "R", true);
$pdf->Cell(60, 5.5, "S/ " . number_format($neto, 2) . "  ", 1, 1, "R", true);
$pdf->Cell($W - 60, 5.5, "IGV (18%)  ", 1, 0, "R", true);
$pdf->Cell(60, 5.5, "S/ " . number_format($igv, 2) . "  ", 1, 1, "R", true);
$pdf->SetFillColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("helvetica", "B", 9);
$pdf->Cell($W - 85, 8, "TOTAL A PAGAR  (" . number_format($liq["total_cierres"]) . " cierres)  ", 0, 0, "R", true);
$pdf->Cell(25, 8, number_format($liq["total_unidades"]) . " un.", 0, 0, "R", true);
$pdf->SetFillColor($cAcento[0], $cAcento[1], $cAcento[2]);
$pdf->SetFont("helvetica", "B", 12);
$pdf->Cell(60, 8, "S/ " . number_format($totalPagar, 2) . "  ", 0, 1, "R", true);
$pdf->SetTextColor(110, 110, 110);
$pdf->SetFont("helvetica", "I", 7);
$pdf->Cell(0, 5, !empty($liq["precios_igv"])
    ? "Los precios por docena de este taller incluyen IGV: el neto resulta de dividir el total entre 1.18."
    : "Importes calculados con el precio por docena (sin IGV); el IGV (18%) se suma al neto.", 0, 1, "L");
$pdf->SetTextColor(0, 0, 0);
if ($sinOspTotal > 0) {
    $pdf->SetFillColor($cAmbar[0], $cAmbar[1], $cAmbar[2]);
    $pdf->SetTextColor($cAmbarTxt[0], $cAmbarTxt[1], $cAmbarTxt[2]);
    $pdf->SetFont("helvetica", "I", 7);
    $pdf->Cell($W, 5, " Líneas sin OSP: " . $sinOspTotal . ". La OSP se emite después del cierre; reimprima la liquidación una vez emitida para que figure el número.", 0, 1, "L", true);
    $pdf->SetTextColor(0, 0, 0);
}

if (trim((string) $liq["observaciones"]) !== "") {
    $pdf->Ln(1);
    $seccion("Observaciones");
    $pdf->SetFont("helvetica", "", 8);
    $pdf->MultiCell(0, 4, $liq["observaciones"], 0, "L");
}

if ($pdf->GetY() > 262) {
    $pdf->AddPage();
    $pdf->SetY(14);
}
$pdf->Ln(11);
$y = $pdf->GetY();
$pdf->SetDrawColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->Line(10, $y, 68, $y);
$pdf->Line(76, $y, 134, $y);
$pdf->Line(142, $y, 200, $y);
$pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetFont("helvetica", "", 7.5);
$pdf->SetXY(10, $y + 1);
$pdf->Cell(58, 4, "Emitido por: " . mb_substr((string) $liq["usuario_nombre"], 0, 22), 0, 0, "C");
$pdf->SetX(76);
$pdf->Cell(58, 4, "V°B° Producción / Gerencia", 0, 0, "C");
$pdf->SetX(142);
$pdf->Cell(58, 4, "Recibí conforme - Taller", 0, 1, "C");
$pdf->SetX(142);
$pdf->Cell(58, 4, "(firma y sello)", 0, 1, "C");

/* ============================================================
   2.ª HOJA: CÓMO FACTURAR (para el taller)
   ============================================================ */
$pdf->AddPage();
$banda("CÓMO FACTURAR");
$pdf->SetY(26);

$caja("FACTURAR A NOMBRE DE", array(
    array("Razón social", "CORPORACION VASCO S.A.C."),
    array("RUC", "20513613939"),
    array("Dirección fiscal", "Cal. Santo Toribio Nro. 259, Urb. Santa Luisa 1ra Etapa, San Martín de Porres, Lima"),
));
$caja(null, array(
    array("Moneda", "Soles (S/)"),
    array("Liquidación", $liq["numero"] . " · " . fd($liq["fecha_desde"]) . " al " . fd($liq["fecha_hasta"])),
), true);
$pdf->Ln(4);

$seccion("DETALLE A FACTURAR (TOTAL POR MODELO)");
$wf = array("Cód." => 17, "Nombre" => 52, "Cant." => 17, "P.doc s/IGV" => 22, "P.doc c/IGV" => 22, "Valor venta" => 22, "IGV 18%" => 18, "Total" => 20);
$cabFact = function () use ($pdf, $cabTabla, $wf) {
    $pdf->SetFont("helvetica", "B", 7);
    $cabTabla();
    foreach ($wf as $t => $w) {
        $pdf->Cell($w, 6, $t, 1, 0, "C", true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont("helvetica", "", 7.5);
};
$cabFact();
$n = 0;
foreach ($porModelo as $m) {
    if ($pdf->GetY() > 262) {
        $pdf->AddPage();
        $pdf->SetY(14);
        $cabFact();
    }
    $z = ($n++ % 2) === 1;
    $pdf->SetFillColor($cZebra[0], $cZebra[1], $cZebra[2]);
    $pdf->Cell($wf["Cód."], 5.5, $m["modelo"], 1, 0, "L", $z);
    $pdf->Cell($wf["Nombre"], 5.5, mb_substr((string) $m["nombre"], 0, 34), 1, 0, "L", $z);
    $pdf->Cell($wf["Cant."], 5.5, number_format($m["cantidad"]), 1, 0, "R", $z);
    $pdf->Cell($wf["P.doc s/IGV"], 5.5, number_format($m["p_sin"], 2), 1, 0, "R", $z);
    $pdf->Cell($wf["P.doc c/IGV"], 5.5, number_format($m["p_con"], 2), 1, 0, "R", $z);
    $pdf->Cell($wf["Valor venta"], 5.5, number_format($m["neto"], 2), 1, 0, "R", $z);
    $pdf->Cell($wf["IGV 18%"], 5.5, number_format($m["igv"], 2), 1, 0, "R", $z);
    $pdf->SetFont("helvetica", "B", 7.5);
    $pdf->Cell($wf["Total"], 5.5, number_format($m["total"], 2), 1, 1, "R", $z);
    $pdf->SetFont("helvetica", "", 7.5);
}
$pdf->SetFont("helvetica", "B", 7.5);
$pdf->SetFillColor(226, 234, 243);
$pdf->Cell($wf["Cód."] + $wf["Nombre"], 6, "TOTAL", 1, 0, "R", true);
$pdf->Cell($wf["Cant."], 6, number_format($sumCant), 1, 0, "R", true);
$pdf->Cell($wf["P.doc s/IGV"] + $wf["P.doc c/IGV"], 6, "", 1, 0, "R", true);
$pdf->Cell($wf["Valor venta"], 6, number_format($neto, 2), 1, 0, "R", true);
$pdf->Cell($wf["IGV 18%"], 6, number_format($igv, 2), 1, 0, "R", true);
$pdf->Cell($wf["Total"], 6, number_format($totalPagar, 2), 1, 1, "R", true);
$pdf->Ln(4);

if ($pdf->GetY() > 235) {
    $pdf->AddPage();
    $pdf->SetY(14);
}
$pdf->SetFillColor($cClaro[0], $cClaro[1], $cClaro[2]);
$pdf->SetTextColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetFont("helvetica", "", 9);
$pdf->Cell($W - 60, 6, "VALOR DE VENTA (sin IGV)  ", 1, 0, "R", true);
$pdf->Cell(60, 6, "S/ " . number_format($neto, 2) . "  ", 1, 1, "R", true);
$pdf->Cell($W - 60, 6, "IGV (18%)  ", 1, 0, "R", true);
$pdf->Cell(60, 6, "S/ " . number_format($igv, 2) . "  ", 1, 1, "R", true);
$pdf->SetFillColor($cOscuro[0], $cOscuro[1], $cOscuro[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont("helvetica", "B", 9);
$pdf->Cell($W - 60, 8.5, "IMPORTE TOTAL A FACTURAR  ", 0, 0, "R", true);
$pdf->SetFillColor($cAcento[0], $cAcento[1], $cAcento[2]);
$pdf->SetFont("helvetica", "B", 12.5);
$pdf->Cell(60, 8.5, "S/ " . number_format($totalPagar, 2) . "  ", 0, 1, "R", true);
$pdf->Ln(3);

$pdf->SetTextColor(70, 85, 105);
$pdf->SetFont("helvetica", "", 8);
$pdf->MultiCell($W, 4.2,
    "• Emita la factura a nombre de CORPORACION VASCO S.A.C. (RUC 20513613939) por el importe total indicado, con una línea por cada modelo.\n" .
    "• P. doc. s/IGV = precio por docena sin IGV; P. doc. c/IGV = precio por docena con IGV (18%).\n" .
    "• Valor venta = cantidad ÷ 12 × precio por docena sin IGV; Total = valor de venta + IGV.\n" .
    "• Consigne en la factura la referencia: " . $liq["numero"] . ".",
    0, "L");
$pdf->SetTextColor(0, 0, 0);

$pdf->Output("LiquidacionServicio_" . $liq["numero"] . ".pdf", "I");
