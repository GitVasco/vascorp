<?php

require_once "../../../controladores/facturacion.controlador.php";
require_once "../../../modelos/facturacion.modelo.php";

require_once "../../cantidad_en_letras.php";


//REQUERIMOS LA CLASE TCPDF

require_once('tcpdf_include.php');
$fecha = new Datetime();
$fechaActual = $fecha->format("d / m / Y");
$fechaCabecera = "Fecha:" . $fechaActual;

//parametros GET
$tipo = $_GET["tipo"];
$documento = $_GET["documento"];
$venta = ControladorFacturacion::ctrMostrarVentaImpresion($documento, $tipo);

$serie = substr($venta["documento"], 0, 4);

$anno = date("Y", strtotime($venta["fecha_emision"]));
$tabla = "movimientosjf_" . $anno;

$modelo = ControladorFacturacion::ctrMostrarModeloImpresionV2($tabla, $documento, $tipo, 0, 100);
$unidad = ControladorFacturacion::ctrMostrarUnidadesImpresion($documento, $tipo, $tabla);
// var_dump($modelo);

$subtotal = $venta["neto"] - $venta["dscto"];


class MYPDF extends TCPDF
{

    //Page header
    public function Header()
    {
        // Set font
        $fecha = new Datetime();
        $fechaActual = $fecha->format("d/m/Y");
        $fechaCabecera = "Fecha:" . $fechaActual;
        $this->SetFont('helvetica', 'B', 9);
        $tipo = $_GET["tipo"];
        $documento = $_GET["documento"];
        $venta = ControladorFacturacion::ctrMostrarCreditoImpresion($documento, $tipo);
        $documento2 =  substr($venta["documento"], 0, 4) . "-" . substr($venta["documento"], 4, 12);
        $destino =  substr($venta["doc_destino"], 0, 3) . "-" . substr($venta["doc_destino"], 3, 10);

        $inicialOrigen = substr($venta["doc_origen"], 0, 1);

        $serieOrigen = substr($venta["doc_origen"], 0, 4);
        $documentoOrigen = substr($venta["doc_origen"], 4, 12);

        if ($inicialOrigen == 'B' || $serieOrigen == "EB01") {
            $tipoOrigen = 'Boleta de ventas';
        } else {
            $tipoOrigen = 'Facturas';
        }
        $image_file = K_PATH_IMAGES . 'paloma_azul.png';
        $this->Image($image_file, 10, 10, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

        $image_file = K_PATH_IMAGES . 'jackyform_letras.png';
        $this->Image($image_file, 60, 10, 50, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->MultiCell(15, 5, '', 0, 'C', 0, 0, '', '', true);

        $this->SetFont('helvetica', 'B', 9);

        $this->MultiCell(70, 35, 'RUC: 20513613939' . "\n\n" . 'NOTA CREDITO ELECTRONICA' . "\n\n" . 'Nro.: ' . $documento2 . '   ', 1, 'C', 0, 0, '', '', true, 0, false, true, 35, 'M');


        // Title
        // Title
        $this->Ln(13);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(140, 0, 'Corporación Vasco S.A.C.', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->SetFont('helvetica', 'A', 8);
        $this->Cell(140, 0, 'Cal.Santo Toribio Nro. 259 - Urb Santa Luisa 1ra Etapa', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(140, 0, 'San Martin de Porres - Lima - Lima', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(140, 0, 'Telfs: 537-2501/536-4024 Cel 964570509 / 964543475', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(140, 0, 'Página Web: www.jackyform.com.pe', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(140, 0, 'Email: gerenciadeventas@jackyform.com.pe', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(150, 0, 'cuentascorrientes@jackyform.com.pe', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->SetFont('helvetica', 'I', 9);
        $this->Cell(140, 0, 'Confecciones de Prendas de Ropa Interior', 0, false, 'C', 0, '', 0, false, false, false);

        $this->SetFont('helvetica', 'A', 9);
        $this->Ln(4);
        $this->Cell(120, 10, 'Cliente:           ' . $venta["nombre"], 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'Moneda:           S/', 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'IGV:           18%', 0, false, 'L', 0, '', 0, false, false, false);
        $this->Ln(7);
        $this->MultiCell(20, 5, 'Dirección:    ', 0, 'L', 0, 0, '', '', true);
        $this->MultiCell(66, 5, $venta["direccion"], 0, 'L', 0, 0, '', '', true);
        $this->Cell(34, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'Tipo:                ' . $tipoOrigen, 0, false, 'L', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(120, 10, 'Ciudad:          ' . $venta["nom_ubigeo"], 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'Referencia(s): ' . $serieOrigen . '-' . $documentoOrigen, 0, false, 'L', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(120, 10, 'Nro RUC:       ' . $venta["dni"], 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'Tipo:                ' . $venta["nom_tipo_con"], 0, false, 'L', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(120, 10, 'Cod. Cliente:  ' . $venta["cliente"], 0, false, 'L', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'Motivo:             ' . $venta["nom_motivo"], 0, false, 'L', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(0, 10, 'Vendedor:      ' . $venta["vendedor"] . "-" . $venta["nom_vendedor"], 0, false, 'L', 0, '', 0, false, false, false);
        $this->Ln(2);
        $image_file = K_PATH_IMAGES . 'bordes1.png';
        $this->Image($image_file, 10, 85, 190, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->Ln(0);
        $this->Cell(35, 10, 'Fecha de Emisión', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(37, 10, 'Condición de Pago', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'Orden de Compra', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(34, 10, 'Fecha de Vencimiento', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(40, 10, 'No. Guia de Remisión', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(4);
        $this->Cell(35, 10, $venta["fecha"], 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(37, 10, $venta["descripcion"], 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(40, 10, '', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(34, 10, '', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(40, 10, $destino, 0, false, 'C', 0, '', 0, false, false, false);
        $this->Ln(2);
        $image_file = K_PATH_IMAGES . 'borde6.png';
        $this->Image($image_file, 11, 97, 189, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->Ln(1);
        $this->Cell(15, 5, 'CODIGO', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(23, 5, 'CANTIDAD', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(14, 5, 'UND.', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(63, 5, 'DESCRIPCIÓN', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(23, 5, 'V. UNITARIA', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(23, 5, 'DSCTOS', 0, false, 'C', 0, '', 0, false, false, false);
        $this->Cell(24, 5, 'P.VENTA', 0, false, 'C', 0, '', 0, false, false, false);
    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'A', 8);
        // Page number
        $this->Cell(0, 10, 'Representación Impresa del Documento Electronico.', 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $this->Ln(4);
        //$this->Cell(0, 10, 'Autorizado mediante Resolución de Intendencia No. 034005004177/SUNAT', 0, false, 'L', 0, '', 0, false, 'T', 'M');
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
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->AddPage('P', 'A4');
$pdf->setPage(1, true);


// use the font
$pdf->SetFont('Helvetica', 'A', 8);
//---------------------------------------------------------

$texto = $venta["observacion"];

$monto_letra = CantidadEnLetra(($venta["total"]));
$pdf->Ln(72);

if ($serie == "B002" || $serie == "F002" || $serie == "B004" || $serie == "F004" || $serie == "FR02") {

    $pdf->Ln(5);
    $pdf->Cell(100, 5, $texto, '', false, 'L', 0, '', 0, false, 'T', 'M');
    $pdf->Ln(15);
    $tamaño = 1;

    $image_file = K_PATH_IMAGES . 'borde4.png';
    $image_file2 = K_PATH_IMAGES . 'borde2.png';
} else {



    foreach ($modelo as $key => $value) {
        $pdf->Ln(4);
        $pdf->SetFillColor(0, 240, 240);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(16, 5, $value["modelo"], 'LR', false, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(23, 5, ($value["cantidad"] * -1), 'LR', false, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(14, 5, $value["unidad"], 'LR', false, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(62, 5, $value["nombre"], 'LR', false, 'L', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(24, 5, $value["precio"], 'LR', false, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(21, 5, $value["dscto1"], 'LR', false, 'R', 0, '', 0, false, 'T', 'M');
        $pdf->Cell(23, 5, (round($value["total"] * -1, 2)), 'LR', false, 'R', 0, '', 0, false, 'T', 'M');
    }
    $tamaño = count($modelo);

    $image_file = K_PATH_IMAGES . 'borde4.png';
    $image_file2 = K_PATH_IMAGES . 'borde2.png';
}



if ($tamaño >= 25) {

    $pdf->AddPage();
    $pdf->Image($image_file, 10, 110, 190, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    $pdf->Ln(1);
    $pdf->Cell(120, 10, 'Observaciones', 0, false, 'L', 0, '', 0, false, false, false);

    $pdf->Cell(20, 10, 'Op. Gravadas', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(30, 10, 'S/', 0, false, 'C', 0, '', 0, false, false, false);
    $pdf->Cell(12, 10, number_format(($venta["neto"]), 2), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '   Nro unidades        ' . ($unidad["cantidad"] * -1), 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Op. Inafecta', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(42, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Op. Exonerada', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(42, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Total Op. Gratuitas', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(42, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Descuentos Totales', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(42, 10, $venta["dscto"], 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Sub Total', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(42, 10, number_format($subtotal, 2), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'ISC', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(42, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(10, 10, 'IGV', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(10, 10, '18%', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(42, 10, ($venta["igv"] * -1), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(120, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Total', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(30, 10, 'S/', 0, false, 'C', 0, '', 0, false, false, false);
    $pdf->Cell(12, 10, number_format(($venta["total"]), 2), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(2);
    $pdf->Image($image_file2, 10, 170, 190, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
} else {
    $pdf->Ln(5);
    $pdf->Cell(0, 11, $pdf->Image($image_file, $pdf->GetX(), $pdf->GetY(), 185), 0);
    $pdf->Ln(1);
    $pdf->Cell(125, 10, '   Observaciones', 0, false, 'L', 0, '', 0, false, false, false);

    $pdf->Cell(20, 10, 'Op. Gravadas', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(25, 10, 'S/', 0, false, 'C', 0, '', 0, false, false, false);
    $pdf->Cell(12, 10, number_format(($venta["neto"] * -1), 2), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '   Nro unidades        ' . ($unidad["cantidad"] * -1), 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Op. Inafecta', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(37, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Op. Exonerada', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(37, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Total Op. Gratuitas', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(37, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Descuentos Totales', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(37, 10, $venta["dscto"], 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Sub Total', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(37, 10, number_format($subtotal * -1, 2), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'ISC', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(37, 10, '0.00', 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(10, 10, 'IGV', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(10, 10, '18%', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(37, 10, number_format(($venta["igv"] * -1), 2), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(5);
    $pdf->Cell(125, 10, '', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(20, 10, 'Total', 0, false, 'L', 0, '', 0, false, false, false);
    $pdf->Cell(25, 10, 'S/', 0, false, 'C', 0, '', 0, false, false, false);
    $pdf->Cell(12, 10, number_format(($venta["total"] * -1), 2), 0, false, 'R', 0, '', 0, false, false, false);
    $pdf->Ln(15);
    $pdf->Cell(0, 11, $pdf->Image($image_file2, $pdf->GetX(), $pdf->GetY(), 185), 0);
}


$pdf->Ln(0);
$pdf->Cell(0, 7, 'Son: ' . $monto_letra, 0, false, 'L', 0, '', 0, false, false, false);
$pdf->Ln(6);
$pdf->Cell(120, 10, 'Cta. Recaudadora Bco. Crédito:  191-99334889-0-69', 0, false, 'L', 0, '', 0, false, false, false);
$pdf->Cell(70, 10, 'CANCELADO', 0, false, 'L', 0, '', 0, false, false, false);
$pdf->Ln(8);
$pdf->Cell(180, 10, 'Lima, ________ de __________________ de _______', 0, false, 'R', 0, '', 0, false, false, false);
// ---------------------------------------------------------
//SALIDA DEL ARCHIVO 
$pdf->Output('notacredito.pdf');

//NOBRE DEL ARCHIVO PDF A GUARDAR PARA ENVIAR POR CORREO LUEGO
//$pdf->Output('C:\xampp\htdocs\rosalinda\vistas\generar_xml\documentos_pdf\10094806777-07-'.substr($venta["documento"],0,4)."-".substr($venta["documento"],4,12).".pdf", 'F');
