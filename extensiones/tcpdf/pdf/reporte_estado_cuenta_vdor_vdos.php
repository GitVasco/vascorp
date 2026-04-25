<?php

require_once "../../../controladores/cuentas.controlador.php";
require_once "../../../modelos/cuentas.modelo.php";

require_once "../../../controladores/vendedor.controlador.php";
require_once "../../../modelos/vendedor.modelo.php";

//declarar zona horaria lima
date_default_timezone_set('America/Lima');

/** Convierte fecha SQL a dd-mm-yy para ahorrar ancho en el PDF. */
function reporteVdorFechaCorta($fechaSql)
{
    if ($fechaSql === null || $fechaSql === '' || $fechaSql === '0000-00-00' || $fechaSql === '9999-12-31') {
        return '';
    }
    $t = strtotime($fechaSql);
    return $t ? date('d-m-y', $t) : '';
}

/** @param int $px */
function reporteVdorW($px)
{
    return (int) $px . 'px';
}

//REQUERIMOS LA CLASE TCPDF

require_once('tcpdf_include.php');
$fecha = new Datetime();
$fechaActual = $fecha->format("d / m / Y");
$fechaCabecera = "Fecha:" . $fechaActual;

/*
 * Ajuste de columnas: edita solo estos arrays (valores en px, sin "px" en el número).
 * - reporteVdorAnchosCabecera: fila Cód. vendedor (bloque HTML encima de la grilla)
 * - reporteVdorAnchosCuerpo: filas de detalle (fuente 10)
 * - reporteVdorAnchosTotal: solo fila "Total Vencidos" (tabla aparte, fuente 12)
 */
$reporteVdorAnchosCabecera = array(
    'esp_inicio'  => 26,
    'cod'         => 60,
    'descripcion' => 242,
    'esp_fin_1'   => 27,
    'esp_fin_2'   => 50,
    'esp_fin_3'   => 35,
);

$reporteVdorAnchosCuerpo = array(
    'tipo'    => 25,
    'nro_doc' => 85,
    'f_emi'   => 65,
    'f_ven'   => 65,
    'origen'  => 85,
    'cliente' => 220,
    'm_orig'  => 70,
    'saldo'   => 70,
    'zona'    => 100,
    'prot'    => 35,
);

/*
 * Fila "Total Vencidos": tabla aparte con fuente más grande (12); puede usar anchos distintos al cuerpo.
 */
$reporteVdorAnchosTotal = array(
    'tipo'    => 28,
    'nro_doc' => 90,
    'f_emi'   => 68,
    'f_ven'   => 68,
    'origen'  => 88,
    'cliente' => 235,
    'm_orig'  => 78,
    'saldo'   => 78,
    'zona'    => 105,
    'prot'    => 38,
);

$reporteVdorTextoColumnasPdf = 'T.    Nro. doc.          F. Emi           F. Ven          Origen              Cod. y Cliente                                        Monto        Saldo          Zona                         Prot.';

$reporteVdorLargoLineaEq = 130;
$reporteVdorLineaEq = str_repeat('=', $reporteVdorLargoLineaEq);

class MYPDF extends TCPDF
{

    /** @var null|string título de columnas en el encabezado de página; si null usa $reporteVdorTextoColumnasPdf (global) */
    public $textoColumnasVdor = null;
    /** @var null|string línea de = bajo el título; si null usa $reporteVdorLineaEq (global) */
    public $lineaEqVdor = null;

    //Page header
    public function Header()
    {
        global $reporteVdorTextoColumnasPdf, $reporteVdorLineaEq;

        $txtCol = $this->textoColumnasVdor !== null ? $this->textoColumnasVdor : $reporteVdorTextoColumnasPdf;
        $linea  = $this->lineaEqVdor !== null ? $this->lineaEqVdor : $reporteVdorLineaEq;

        $fecha = new Datetime();
        $fechaActual = $fecha->format("d/m/Y");
        $fechaCabecera = "Fecha:" . $fechaActual;
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(0, 8, 'CORPORACIÓN VASCO S.A.C.', 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(0, 8, $fechaCabecera, 0, false, 'R', 0, '', 0, false, false, false);

        $this->Ln(2);
        $this->Cell(0, 15, 'DOCUMENTOS VENCIDOS - ' . $fechaActual, 0, false, 'C', 0, '', 0, false, false, false);

        $this->Ln(7);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 9, $txtCol, 0, 1, 'C', 0, '', 0, false, false, false);

        $this->Cell(0, 0, $linea, 0, 1, 'L', 0, '', 0, false, 'M', 'M');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
// set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
// set margins
$pdf->SetMargins(5, PDF_MARGIN_TOP, 5);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->AddPage('L', 'A4');
$pdf->setPage(1, true);


//parametros GET
$vendedor = $_GET["vendedor"];

$cuentas = ControladorCuentas::ctrEstadoCtaVdorVdos($vendedor);
$vendedor = ControladorVendedores::ctrMostrarVendedores("codigo", $vendedor);
#var_dump($cuentas);

// convert TTF font to TCPDF format and store it on the fonts folder
$fontname = TCPDF_FONTS::addTTFfont('../../lucida-console.ttf', 'TrueTypeUnicode', '', 96);

// use the font
$pdf->SetFont($fontname, '', 7, '', false);
//---------------------------------------------------------


$pdf->SetFont($fontname, '', 12, '', false);

$cab = $reporteVdorAnchosCabecera;
$wCabIni = reporteVdorW($cab['esp_inicio']);
$wCabCod = reporteVdorW($cab['cod']);
$wCabDesc = reporteVdorW($cab['descripcion']);
$wCabE1 = reporteVdorW($cab['esp_fin_1']);
$wCabE2 = reporteVdorW($cab['esp_fin_2']);
$wCabE3 = reporteVdorW($cab['esp_fin_3']);

$bloque1 = '<table style="text-center" >
                <tbody>
                    <tr>
                        <td style="width:' . $wCabIni . '"></td>
                        <td style="width:' . $wCabCod . '">Cod: ' . $_GET["vendedor"] . '</td>
                        <td style="width:' . $wCabDesc . '"><strong>' . $vendedor["descripcion"] . '</strong></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="width:' . $wCabE1 . '"></td>
                        <td style="width:' . $wCabE2 . '"></td>
                        <td style="width:' . $wCabE3 . '"></td>
                    </tr>

                    <tr>
                        <td style="width:' . $wCabIni . '"></td>
                        <td style="width:' . $wCabCod . '"></td>
                        <td style="width:' . $wCabDesc . '"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="width:' . $wCabE1 . '"></td>
                        <td style="width:' . $wCabE2 . '"></td>
                        <td style="width:' . $wCabE3 . '"></td>
                    </tr>
                                        
                </tbody>
            </table>';

$pdf->writeHTML($bloque1, false, false, false, false, '');

$pdf->SetFont($fontname, '', 10, '', false);

/* Anchos cuerpo reutilizables (una sola definición) */
$wC = $reporteVdorAnchosCuerpo;
$wTipo = reporteVdorW($wC['tipo']);
$wNro = reporteVdorW($wC['nro_doc']);
$wFEmi = reporteVdorW($wC['f_emi']);
$wFVen = reporteVdorW($wC['f_ven']);
$wOrig = reporteVdorW($wC['origen']);
$wCli = reporteVdorW($wC['cliente']);
$wM = reporteVdorW($wC['m_orig']);
$wSal = reporteVdorW($wC['saldo']);
$wZona = reporteVdorW($wC['zona']);
$wProt = reporteVdorW($wC['prot']);

$wT = $reporteVdorAnchosTotal;
$wTotTipo = reporteVdorW($wT['tipo']);
$wTotNro = reporteVdorW($wT['nro_doc']);
$wTotFEmi = reporteVdorW($wT['f_emi']);
$wTotFVen = reporteVdorW($wT['f_ven']);
$wTotOrig = reporteVdorW($wT['origen']);
$wTotCli = reporteVdorW($wT['cliente']);
$wTotM = reporteVdorW($wT['m_orig']);
$wTotSal = reporteVdorW($wT['saldo']);
$wTotZona = reporteVdorW($wT['zona']);
$wTotProt = reporteVdorW($wT['prot']);

foreach ($cuentas as $key => $value) {

    if ($value["tipo_doc"] == "ZZ") {

        $pdf->SetFont($fontname, 'B', 12, '', false);

        $bloque3 = '<table style="text-align:center;font-weight:bold;margin-top:10px;padding-top:6px;padding-bottom:4px" cellpadding="2" cellspacing="0">
        <tbody>
            <tr style="font-weight:bold">
                <td style="width:' . $wTotTipo . '"></td>
                <td style="width:' . $wTotNro . '"></td>
                <td style="width:' . $wTotFEmi . '"></td>
                <td style="width:' . $wTotFVen . '"></td>
                <td style="width:' . $wTotOrig . '"></td>
                <td style="width:' . $wTotCli . '"><strong>Total Vencidos S/</strong></td>
                <td style="width:' . $wTotM . ';text-align:right"><strong>' . number_format((float)(isset($value["monto"]) ? $value["monto"] : 0), 2) . '</strong></td>
                <td style="width:' . $wTotSal . ';text-align:right"><strong>' . number_format((float)(isset($value["saldo"]) ? $value["saldo"] : 0), 2) . '</strong></td>
                <td style="width:' . $wTotZona . '"></td>
                <td style="width:' . $wTotProt . '"></td>
            </tr>
        </tbody>
    </table>';
    } else {

        $pdf->SetFont($fontname, '', 10, '', false);

        $cliNombre = trim($value["cliente"] . ' ' . (isset($value["nombre"]) ? $value["nombre"] : ''));
        $cliNombre = substr($cliNombre, 0, 35);

        $nom_ubigeo = substr($value["nom_ubigeo"], 0, 12);

        $fEmi = reporteVdorFechaCorta(isset($value["fecha"]) ? $value["fecha"] : '');
        $fVen = reporteVdorFechaCorta(isset($value["fecha_ven"]) ? $value["fecha_ven"] : '');

        $bloque3 = '<table style="text-center" >
        <tbody>
            <tr>
                <td style="width:' . $wTipo . '">' . $value["tipo_doc"] . '</td>
                <td style="width:' . $wNro . '">' . $value["num_cta"] . '</td>
                <td style="width:' . $wFEmi . '">' . $fEmi . '</td>
                <td style="width:' . $wFVen . '">' . $fVen . '</td>
                <td style="width:' . $wOrig . '">' . $value["doc_origen"] . '</td>
                <td style="width:' . $wCli . '">' . $cliNombre . '</td>
                <td style="width:' . $wM . ';text-align:right">' . number_format((float)(isset($value["monto"]) ? $value["monto"] : 0), 2) . '</td>
                <td style="width:' . $wSal . ';text-align:right">' . number_format($value["saldo"], 2) . '</td>
                <td style="width:' . $wZona . '">' . $nom_ubigeo . '</td>
                <td style="width:' . $wProt . '">' . $value["protesta"] . '</td>
            </tr>
        </tbody>
    </table>';
    }





    $pdf->writeHTML($bloque3, false, false, false, false, '');
}

// ---------------------------------------------------------
//SALIDA DEL ARCHIVO 

//$pdf->Output('factura.pdf', 'D');
$pdf->Output('reporte_cuenta.pdf');
