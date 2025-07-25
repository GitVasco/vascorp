<?php

require_once "../../../controladores/cuentas.controlador.php";
require_once "../../../modelos/cuentas.modelo.php";
// feecha lima 
date_default_timezone_set('America/Lima');

//REQUERIMOS LA CLASE TCPDF

require_once('tcpdf_include.php');
$fecha = new Datetime();
$fechaActual = $fecha->format("d / m / Y");
$fechaCabecera = "Fecha:" . $fechaActual;
class MYPDF extends TCPDF
{

    //Page header
    public function Header()
    {
        // Set font
        $fecha = new Datetime();
        $fechaActual = $fecha->format("d/m/Y");
        $fechaCabecera = "Fecha:" . $fechaActual;
        $this->SetFont('helvetica', 'B', 8);
        // Title
        $this->Cell(0, 8, 'CORPORACIÓN VASCO S.A.C.', 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(0, 8, $fechaCabecera, 0, false, 'R', 0, '', 0, false, false, false);

        $this->Ln(2);
        $this->Cell(0, 15, 'PAGOS EFECTUADOS  - ' . $_GET["inicio"] . ' al ' . $_GET["fin"], 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(7);
        $this->Cell(0, 9, 'Tip    Nro. doc.                Fecha               Cliente       Razon social / Nombre cliente                      Cob            Notas                  Fact. S/                   Letra S/           ', 0, 1, 'C', 0, '', 0, false, false, false);

        $this->Cell(0, 0, '================================================================================================================================================', 0, 1, 'L', 0, '', 0, false, 'M', 'M');
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
$pdf->SetMargins(1, PDF_MARGIN_TOP, 1);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->AddPage('P', 'A4');
$pdf->setPage(1, true);


//parametros GET
$consulta = $_GET["consulta"];
// echo '<pre>consulta ';
// print_r($consulta);
// echo '</pre>';
$orden1 = $_GET["orden1"];
// echo '<pre>orden1 ';
// print_r($orden1);
// echo '</pre>';
$orden2 = $_GET["orden2"];
// echo '<pre>orden2 ';
// print_r($orden2);
// echo '</pre>';
$inicio = $_GET["inicio"];
// echo '<pre>inicio ';
// print_r($inicio);
// echo '</pre>';
$fin = $_GET["fin"];
// echo '<pre>fin ';
// print_r($fin);
// echo '</pre>';
$canc = $_GET["canc"];
// echo '<pre>canc ';
// print_r($canc);
// echo '</pre>';
$vend = $_GET["vend"];
// echo '<pre>vend ';
// print_r($vend);
// echo '</pre>';

// convert TTF font to TCPDF format and store it on the fonts folder
$fontname = TCPDF_FONTS::addTTFfont('../../lucida-console.ttf', 'TrueTypeUnicode', '', 96);

// use the font
$pdf->SetFont($fontname, '', 7, '', false);
//---------------------------------------------------------


$pdf->SetFont($fontname, '', 8, '', false);



$cuentas = ControladorCuentas::ctrMostrarReportePagos($orden1, $orden2, $canc, $vend, $inicio, $fin);

$total = ControladorCuentas::ctrMostrarReporteTotalPagos($orden1, $orden2, $canc, $vend, $inicio, $fin);


foreach ($cuentas as $key => $value) {

    $tamCliente = strlen($value["nombre"]);

    if ($tamCliente > 28) { // Reducimos el límite a 28 para dar espacio al código del vendedor
        $nomCliente = substr($value["nombre"], 0, 28);
    } else {
        $nomCliente = $value["nombre"];
    }

    if ($value["tipo_doc"] == '-1') {

        $bloque3 = '<table  style="text-align:center">
                        <tbody>
                            <tr>
                                <td style="width:80px"><b>' . $value["num_cta"] . '</b></td>
                                <td style="width:130px"><b>' . $value["fecha"] . '</b></td>
                            </tr>
                        </tbody>
                    </table>';

        $pdf->writeHTML($bloque3, false, false, false, false, '');
    } else if ($value["tipo_doc"] == '999') {

        if ($orden1 == 'fecha_ven') {

            $bloque3 = '<table style="border-top:1px solid #000;width:500px" >
                            </table>
                            <table  style="text-align:center;padding-top:5px;padding-bottom:5px">
                            <tbody>
                                <tr>
                                    <td style="width:460px;text-align:right">Total fecha de pago: </td>
                                    <td style="width:51px;text-align:right">' . $value["fact"] . '</td>
                                    <td style="width:59px;text-align:right">' . $value["letra"] . '</td>
                                </tr>
                            </tbody>
                        </table>';
        } else {

            $bloque3 = '<table style="border-top:1px solid #000;width:500px" >
                        </table>
                        <table  style="text-align:center;padding-top:5px;padding-bottom:5px">
                            <tbody>
                                <tr>
                                    <td style="width:460px;text-align:right">Total Tip doc: </td>
                                    <td style="width:51px;text-align:right">' . $value["fact"] . '</td>
                                    <td style="width:59px;text-align:right">' . $value["letra"] . '</td>
                                </tr>
                            </tbody>
                        </table>';
        }

        $pdf->writeHTML($bloque3, false, false, false, false, '');
    } else {

        $bloque3 = '<table  style="text-align:center">
                        <tbody>
                            <tr>
                                <td style="width:20px">' . $value["tipo_doc"] . '</td>
                                <td style="width:70px;text-align:left">' . $value["num_cta"] . '</td>
                                <td style="width:55px">' . $value["fecha"] . '</td>
                                <td style="width:50px;text-align:left">' . $value["cliente"] . '</td>
                                <td style="width:160px;text-align:left">' . $nomCliente . '</td>
                                <td style="width:20px;text-align:left">' . $value["vendedor"] . '</td>
                                <td style="width:20px">' . $value["cod_pago"] . '</td>
                                <td style="width:90px;text-align:left">' . $value["notas"] . '</td>
                                <td style="width:50px;text-align:right">' . $value["fact"] . '</td>
                                <td style="width:60px;text-align:right">' . $value["letra"] . '</td>
                            </tr>
                        </tbody>
                    </table>';

        $pdf->writeHTML($bloque3, false, false, false, false, '');
    }
}

$bloque4 = ' <table style="border-top:1px solid #000;width:100px" >
            </table>
            <table style="padding-top:20px;text-align:right">
                <tbody>
                    <tr >
                    
                    <td  style="width:460px;" ><b>' . $total["total_gral"] . '</b></td>   
                    <td  style="width:60px; ">' . $total["fact"] . '</td>
                    <td  style="width:60px; ">' . $total["letra"] . '</td>
                    </tr>
                </tbody>
            </table>';

$pdf->writeHTML($bloque4, false, false, false, false, '');
// ---------------------------------------------------------
//SALIDA DEL ARCHIVO 

//$pdf->Output('factura.pdf', 'D');
$pdf->Output('reporte_cuenta.pdf');
