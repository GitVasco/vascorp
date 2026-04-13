<?php

date_default_timezone_set('America/Lima');

$inicio = isset($_GET["inicio"]) ? $_GET["inicio"] : "";
$fin = isset($_GET["fin"]) ? $_GET["fin"] : "";
$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../modelos/produccion.modelo.php";
require_once "../../controladores/produccion.controlador.php";

$quincenaRow = ControladorProduccion::ctrRptPagosTrusasProduccionQuincena($id);
if (!$quincenaRow) {
    header('HTTP/1.1 400 Bad Request');
    die('Quincena no encontrada.');
}
$tsI = strtotime($quincenaRow['inicio']);
$tsF = strtotime($quincenaRow['fin']);
$tituloHoja = 'Pagos ' . date('Y', $tsI) . '-' . date('n', $tsI) . '-' . date('j', $tsI) . ' Al ' . date('Y', $tsF) . '-' . date('n', $tsF) . '-' . date('j', $tsF);
$nombreArchivoXls = 'Pagos Trusas por monto ' . date('Y', $tsI) . '-' . date('n', $tsI) . '-' . date('j', $tsI) . ' Al ' . date('Y', $tsF) . '-' . date('n', $tsF) . '-' . date('j', $tsF);

$fecha = date('d/m/Y');

/* 
* INSTANCIAMOS
*/
$objPHPExcel = new PHPExcel();

/* 
* CONFIGURAMOS AL CREADOR DEL ARCHIVO
*/
$objPHPExcel->getProperties()->setCreator("Corp. Vasco"); //autor
$objPHPExcel->getProperties()->setTitle("PagosTrusasPorProduccion"); //titulo

/* 
* INICIO DE ESTILOS
*/
#negrita-subrayado-13-rojo
$texto_1 = new PHPExcel_Style();
$texto_1->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'font' => array(
            'bold' => true,
            'color' => array('rgb' => 'FF0008'),
            'underline' => true,
            'size' => 13
        )
    )
);

#negrita-11-negro
$texto_2 = new PHPExcel_Style();
$texto_2->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => '000000'),
            'size' => 11
        )
    )
);

#normal-10
$texto_3 = new PHPExcel_Style();
$texto_3->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'font' => array(
            'bold' => false,
            'underline' => false,
            'color' => array('rgb' => '000000'),
            'size' => 10
        )
    )
);

#normal-11-azul
$texto_4 = new PHPExcel_Style();
$texto_4->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => '0400FF'),
            'size' => 11
        )
    )
);

#negrita-14-negro
$texto_5 = new PHPExcel_Style();
$texto_5->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => '000000'),
            'size' => 14
        )
    )
);

#negrita-11-rojo
$borde_1 = new PHPExcel_Style();
$borde_1->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => 'FF0008'),
            'size' => 10
        )
    )
);

#negrita-11-azul
$borde_2 = new PHPExcel_Style();
$borde_2->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => '0400FF'),
            'size' => 10
        )
    )
);

#negrita-11-negro
$borde_3 = new PHPExcel_Style();
$borde_3->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => '000000'),
            'size' => 10
        )
    )
);

#negrita-11-celeste
$borde_4 = new PHPExcel_Style();
$borde_4->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => '0174BB'),
            'size' => 10
        )
    )
);

#negrita-11-verde
$borde_5 = new PHPExcel_Style();
$borde_5->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => '00833E'),
            'size' => 10
        )
    )
);

#normal-10-negro
$borde_6 = new PHPExcel_Style();
$borde_6->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => false,
            'underline' => false,
            'color' => array('rgb' => '000000'),
            'size' => 10
        )
    )
);

#negrita-10-vino
$borde_7 = new PHPExcel_Style();
$borde_7->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => 'A4001E'),
            'size' => 10
        )
    )
);

#negrita-10-rojo/rosado
$borde_8 = new PHPExcel_Style();
$borde_8->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'EEABAB')
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'color' => array('rgb' => 'FF0008'),
            'size' => 10
        )
    )
);

/* 
* FIN DE ESTILOS
*/

/* 
* CONFIGURAMOS LA 1ERA HOJA
*/
$objPHPExcel->createSheet(0);
$objPHPExcel->setActiveSheetIndex(0);

# Titulo de la hoja
$objPHPExcel->getActiveSheet()->setTitle($tituloHoja);

# Orientacion hoja
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);

# Tipo Papel
$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

# Establecer impresion a pagina completa
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToPage(true);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);

# Establecer margenes
$marginV = 0.5 / 3.54; // 0.5 centimetros

$objPHPExcel->getActiveSheet()->getPageMargins()->setTop($marginV);
$objPHPExcel->getActiveSheet()->getPageMargins()->setBottom($marginV);
$objPHPExcel->getActiveSheet()->getPageMargins()->setLeft($marginV);
$objPHPExcel->getActiveSheet()->getPageMargins()->setRight($marginV);


# Incluir una imagen
$objDrawing = new PHPExcel_Worksheet_Drawing();
$objDrawing->setPath('img/jackyform_letras.png'); //ruta
$objDrawing->setWidthAndHeight(300, 250);
$objDrawing->setCoordinates('B1');
$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

/* 
todo: INICIO CABECERA
*/
$respCabecera = ControladorProduccion::ctrRptPagosTrusasProduccionCabecera($inicio, $fin);
$tituloCabecera = ($respCabecera && !empty($respCabecera['titulo']))
    ? $respCabecera['titulo']
    : 'PRODUCCIÓN DE DESTAJEROS';

$fila = 2;
$objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(45.67);

$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", $tituloCabecera);
$objPHPExcel->getActiveSheet()->mergeCells("E$fila:I$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "E$fila:I$fila");
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila:I$fila")->getAlignment()->setWrapText(true);

$fila = 4;

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "ÁREA:");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "B$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "TRUSAS — PAGO POR MONTO PRODUCIDO (S/.)");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "D$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", "FECHA");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "H$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", $fecha);
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "I$fila");

$fila = 5;

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "LÍNEA:");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "B$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "JACKYFORM");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "D$fila");

/* 
todo: FIN CABECERA
*/


/* 
todo: INICIO DE DETALLE
*/

$fila = 7;
$objPHPExcel->getActiveSheet()->getRowDimension('7')->setRowHeight(45.67);

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "Cod.");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "B$fila");
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", "Pos.");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "C$fila");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Maquinista");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "D$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", "Dias Lab.");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "E$fila");
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", "Producción (S/.)");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Rango");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "G$fila");
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", "Bono S/.");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "H$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", "Total a pagar (prod. + bono)");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "I$fila");
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setWrapText(true);

$filasDetalle = ControladorProduccion::ctrRptPagosTrusasProduccionDetalle($inicio, $fin);

$sumProduccion = 0;
$sumBonos = 0;
$totalGeneral = 0;
$conta = 1;

foreach ($filasDetalle as $respDetalle) {

    $montoProducido = (float) $respDetalle["produccion"];
    $bono = (float) $respDetalle["bono"];
    /* Igual que el reporte clásico: total = producción (monto) + bono/incentivo */
    $totalFila = $montoProducido + $bono;

    $fila += 1;
    $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", utf8_encode($respDetalle["id_trabajador"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($conta));
    $objPHPExcel->getActiveSheet()->SetCellValue("D$fila", utf8_encode($respDetalle["nombre"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", utf8_encode($respDetalle["dias"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", utf8_encode($respDetalle["produccion"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("G$fila", utf8_encode($respDetalle["rango"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", utf8_encode($bono));
    $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($totalFila));

    $conta++;

    if ($montoProducido < 500) {
        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "F$fila");
        $objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    } else {
        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "F$fila");
        $objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }

    $r = $respDetalle["rango"];
    if ($r === "A") {
        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_5, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    } elseif ($r === "B") {
        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_4, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    } elseif ($r === "C") {
        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    } elseif ($r === "D") {
        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_7, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    } else {
        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "B$fila");
    $objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "C$fila");
    $objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "D$fila");

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "E$fila");
    $objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "H$fila");
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "I$fila");
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $sumProduccion += $montoProducido;
    $sumBonos += $bono;
    $totalGeneral += $totalFila;
}

/*
todo: FIN DE DETALLE
*/

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "Escala (monto producido S/.): D 500-565 = bono S/50 | C 566-580 = S/125 | B 581-630 = S/140 | A 631+ = S/160 | Menos de S/500: sin bono.");
$objPHPExcel->getActiveSheet()->mergeCells("B$fila:I$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_3, "B$fila:I$fila");
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setWrapText(true);

$fila += 2;
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Total producción (S/.)");
$objPHPExcel->getActiveSheet()->mergeCells("D$fila:E$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "D$fila:E$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", $sumProduccion);
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Total bonos (S/.)");
$objPHPExcel->getActiveSheet()->mergeCells("D$fila:G$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "D$fila:G$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $sumBonos);
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "H$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "TOTAL A PAGAR (producción + bonos)");
$objPHPExcel->getActiveSheet()->mergeCells("D$fila:H$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "D$fila:H$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", $totalGeneral);
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "I$fila");
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setWrapText(true);

/* 
todo: INICIO TOTALES
*/



/* 
todo: FIN TOTALES
*/

# Ajustar el tamaño de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(4.29);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(6.44);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(6.44);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(37.18);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(11.44);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(11.44);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(11.44);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(11.44);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(14.3);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(4.29);

/* 
* CREAR EL ARCHIVO
*/
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel); //Escribir archivo

/* 
* Establecer formado de Excel 2003
*/
header("Content-Type: application/vnd.ms-excel");

header('Content-Disposition: attachment; filename="' . $nombreArchivoXls . '.xls"');


//forzar a descarga por el navegador
$objWriter->save('php://output');
