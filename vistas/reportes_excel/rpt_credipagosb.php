<?php

header('Content-Type: text/html; charset=ISO-8859-1');



/* 
* LLAMAMOS A LA LIBRERIA PHPEXCEL
*/
include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/usuarios.controlador.php";
require_once "../../modelos/usuarios.modelo.php";
/* 
* LLAMAMOS A LA CONEXION
*/
$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);

$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
mysql_select_db($con["db"], $conexion);

/* 
* CONFIGURAMOS LA FECHA ACTUAL
*/
date_default_timezone_set('America/Lima');
$fechaactual = getdate();

$fecha = date("d-m-Y");

$inicio = $_GET["inicio"];
$final  = $_GET["fin"];

/* 
* INSTANCIAMOS
*/
$objPHPExcel = new PHPExcel();

/* 
* CONFIGURAMOS AL CREADOR DEL ARCHIVO
*/
$objPHPExcel->getProperties()->setCreator("Corp. Vasco"); //autor
$objPHPExcel->getProperties()->setTitle("00000020"); //titulo

/* 
* INICIO DE ESTILOS
*/

#negrita subrayado T-11
$texto1 = new PHPExcel_Style();
$texto1->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'font' => array(
            'bold' => true,
            'underline' => true,
            'size' => 11
        )
    )
);

#negrita T-11
$texto2 = new PHPExcel_Style();
$texto2->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false
        ),
        'font' => array(
            'bold' => true,
            'underline' => false,
            'size' => 11
        )
    )
);
$texto3 = new PHPExcel_Style();
$texto3->applyFromArray(
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

#bordes grueso: izquierda-arriba-derecha, color  GRIS NEGRITA T11
$borde1 = new PHPExcel_Style();
$borde1->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'D7DBDD')
        ),
        'borders' => array(
            'top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => true,
            'size' => 11
        )
    )
);

#bordes grueso: izquierda-derecha, color  GRIS NEGRITA T11
$borde2 = new PHPExcel_Style();
$borde2->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'D7DBDD')
        ),
        'borders' => array(
            'top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => true,
            'size' => 11
        )
    )
);

#bordes grueso: izquierda-derecha-abajo, color  GRIS NEGRITA T11
$borde3 = new PHPExcel_Style();
$borde3->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'D7DBDD')
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => true,
            'size' => 11
        )
    )
);

#bordes derecho delgado / borde izquiedo grueso / borde abajo delgado
$borde4 = new PHPExcel_Style();
$borde4->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => false,
            'size' => 10
        )
    )
);

#bordes derecho delgado / borde izquiedo delgado / borde abajo delgado
$borde5 = new PHPExcel_Style();
$borde5->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => false,
            'size' => 10
        )
    )
);

#bordes derecho grueso / borde izquiedo delgado / borde abajo delgado
$borde6 = new PHPExcel_Style();
$borde6->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => false,
            'size' => 10
        )
    )
);

#bordes grueso: izquierda-arriba-derecha, color  GRIS NEGRITA T11
$borde7 = new PHPExcel_Style();
$borde7->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'D7DBDD')
        ),
        'borders' => array(
            'top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => true,
            'size' => 11
        )
    )
);

#bordes grueso: ABAJO
$borde8 = new PHPExcel_Style();
$borde8->applyFromArray(
    array(
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        )
    )
);

#bordes grueso: izquierda-derecha-abajo-arriba, color  GRIS NEGRITA T10
$borde9 = new PHPExcel_Style();
$borde9->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'D7DBDD')
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => true,
            'size' => 10
        )
    )
);


#bordes grueso: ABAJO
$borde10 = new PHPExcel_Style();
$borde10->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
        ),
        'borders' => array(
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
        ),
        'font' => array(
            'bold' => false,
            'size' => 10
        )
    )
);


#bordes grueso: izquierda-arriba-derecha, color  GRIS NEGRITA T11
$borde11 = new PHPExcel_Style();
$borde11->applyFromArray(
    array(
        'alignment' => array(
            'wrap' => false,
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
        ),
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'D7DBDD')
        ),
        'borders' => array(
            'top' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
            'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
        ),
        'font' => array(
            'bold' => true,
            'size' => 11
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
$objPHPExcel->getActiveSheet()->setTitle("Credipagos -" . $fecha);

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
$objDrawing->setWidthAndHeight(200, 150);
$objDrawing->setCoordinates('B1');
$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());


//establecer titulos de impresion en cada hoja
//$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);

// TITULO
$fila = 2;
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'Credipagos');
$objPHPExcel->getActiveSheet()->mergeCells("D$fila:E$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto3, "D$fila:E$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("g$fila", 'fecha:');
$objPHPExcel->getActiveSheet()->setSharedStyle($texto1, "g$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $fecha);
$objPHPExcel->getActiveSheet()->setSharedStyle($texto2, "H$fila");


$fila = 4;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Tipo');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "A$fila");
$objPHPExcel->getActiveSheet()->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'Documento');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "B$fila");
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'Fecha');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "C$fila");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'Cod.');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "D$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'Cliente');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "E$fila");
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", 'C. P');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Doc. Origen');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "G$fila");
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", 'Monto');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "H$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", 'Notas');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "I$fila");
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


#query para sacar los datos deL detalle
$sqlDetalle = mysql_query("SELECT 
                            c.tipo_doc,
                            c.num_cta,
                            c.fecha,
                            c.cliente,
                            cc.nombre,
                            c.cod_pago,
                            c.doc_origen,
                            ROUND(c.monto, 2) AS monto,
                            c.notas
                            FROM
                            cuenta_ctejf c 
                            LEFT JOIN clientesjf cc 
                              ON c.cliente = cc.codigo 
                            WHERE c.tip_mov = '-' 
                            AND c.cod_pago = '82' 
                            AND YEAR(c.fecha) >= '2021' 
                            AND (
                              c.fecha BETWEEN '$inicio' 
                              AND '$final'
                            ) 
                            ORDER BY c.tipo_doc,
                            c.num_cta") or die(mysql_error());

while ($respDetalle = mysql_fetch_array($sqlDetalle)) {


    $fila += 1;

    $objPHPExcel->getActiveSheet()->setCellValueExplicit("A$fila", $respDetalle["tipo_doc"], PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", utf8_encode($respDetalle["num_cta"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($respDetalle["fecha"]));
    $objPHPExcel->getActiveSheet()->setCellValueExplicit("D$fila", $respDetalle["cliente"], PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", utf8_encode($respDetalle["nombre"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", utf8_encode($respDetalle["cod_pago"]));
    $objPHPExcel->getActiveSheet()->setCellValueExplicit("G$fila", $respDetalle["doc_origen"], PHPExcel_Cell_DataType::TYPE_STRING);

    if ($respDetalle["monto"] >= 0 && $respDetalle["monto"] < 1000) {

        $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respDetalle["monto"]);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0.00");

        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    } else if ($respDetalle["monto"] >= 1000 && $respDetalle["monto"] < 1000000) {

        $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respDetalle["monto"]);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0,000.00");

        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    }

    $objPHPExcel->getActiveSheet()->setCellValueExplicit("I$fila", utf8_encode($respDetalle["notas"]), PHPExcel_Cell_DataType::TYPE_STRING);

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "A$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "B$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "C$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "D$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "E$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "F$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "G$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "I$fila");
}

#query para sacar el total
$sqlTotal = mysql_query("SELECT 
                            c.cod_pago,
                            SUM(c.monto) AS monto 
                            FROM
                            cuenta_ctejf c 
                            LEFT JOIN clientesjf cc 
                            ON c.cliente = cc.codigo 
                            WHERE c.tip_mov = '-' 
                            AND c.cod_pago = '82' 
                            AND YEAR(c.fecha) >= '2021' 
                            AND (
                            c.fecha BETWEEN '$inicio' 
                            AND '$final'
                            ) 
                            GROUP BY c.cod_pago") or die(mysql_error());

$respTotal = mysql_fetch_array($sqlTotal);

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'TOTAL');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde7, "G$fila");

if ($respTotal["monto"] >= 0 && $respTotal["monto"] < 1000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respTotal["monto"]);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0.00");

    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
} else if ($respTotal["monto"] >= 1000 && $respTotal["monto"] < 1000000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respTotal["monto"]);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0,000.00");

    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
}


//*RESUMEN

$fila += 1;
$fila += 1;
$fila += 1;

$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'NRO DE CTA REDAUDADORA BCP: 191-1553564-0-64');
$objPHPExcel->getActiveSheet()->mergeCells("A$fila:E$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "A$fila:E$fila");
$fila += 1;

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Cv');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "A$fila");
$objPHPExcel->getActiveSheet()->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'RUC/DNI');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "B$fila");
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", '');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "C$fila");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'Cod.');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "D$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'Cliente');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "E$fila");
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", 'C. D');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Doc. Origen');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "G$fila");
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", 'Monto');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde2, "H$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


#query para sacar los datos deL detalle
$sqlDetalle2 = mysql_query("SELECT 
                                c.vendedor,
                                cc.documento,
                                c.cliente,
                                cc.nombre,
                                c.tipo_doc,
                                c.num_cta,
                                SUM(c.monto) AS monto 
                                FROM
                                cuenta_ctejf c 
                                LEFT JOIN clientesjf cc 
                                ON c.cliente = cc.codigo 
                                WHERE c.tip_mov = '-' 
                                AND c.cod_pago = '82' 
                                AND YEAR(c.fecha) >= '2021' 
                                AND YEAR(c.fecha) >= '2021' 
                                AND (
                                c.fecha BETWEEN '$inicio' 
                                AND '$final'
                                ) 
                                GROUP BY c.cliente,
                                c.num_cta 
                                ORDER BY c.vendedor") or die(mysql_error());

while ($respDetalle2 = mysql_fetch_array($sqlDetalle2)) {


    $fila += 1;

    $objPHPExcel->getActiveSheet()->setCellValueExplicit("A$fila", $respDetalle2["vendedor"], PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->setCellValueExplicit("B$fila", $respDetalle2["documento"], PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->setCellValueExplicit("D$fila", $respDetalle2["cliente"], PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", utf8_encode($respDetalle2["nombre"]));
    $objPHPExcel->getActiveSheet()->setCellValueExplicit("F$fila", $respDetalle2["tipo_doc"], PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->setCellValueExplicit("G$fila", $respDetalle2["num_cta"], PHPExcel_Cell_DataType::TYPE_STRING);


    if ($respDetalle2["monto"] >= 0 && $respDetalle2["monto"] < 1000) {

        $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respDetalle2["monto"]);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0.00");

        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    } else if ($respDetalle2["monto"] >= 1000 && $respDetalle2["monto"] < 1000000) {

        $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respDetalle2["monto"]);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0,000.00");

        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    }




    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "A$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "B$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "C$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "D$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "E$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "F$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde5, "G$fila");
}


#query para sacar el total
$sqlTotal = mysql_query("SELECT 
                    c.cod_pago,
                    SUM(c.monto) AS monto 
                    FROM
                    cuenta_ctejf c 
                    LEFT JOIN clientesjf cc 
                    ON c.cliente = cc.codigo 
                    WHERE c.tip_mov = '-' 
                    AND c.cod_pago = '82' 
                    AND YEAR(c.fecha) >= '2021' 
                    AND (
                    c.fecha BETWEEN '$inicio' 
                    AND '$final'
                    ) 
                    GROUP BY c.cod_pago") or die(mysql_error());

$respTotal = mysql_fetch_array($sqlTotal);

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'TOTAL');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde7, "G$fila");

if ($respTotal["monto"] >= 0 && $respTotal["monto"] < 1000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respTotal["monto"]);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0.00");

    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
} else if ($respTotal["monto"] >= 1000 && $respTotal["monto"] < 1000000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $respTotal["monto"]);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode("0,000.00");

    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
}


# Ajustar el tamaño de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(4.57);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(14.28);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(14.28);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(9.99);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(49.98);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(4.57);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(14.28);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(11.43);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);




/* 
* CREAR EL ARCHIVO
*/
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel); //Escribir archivo

/* 
* Establecer formado de Excel 2003
*/
header("Content-Type: application/vnd.ms-excel");

/* 
* CONFIGURAR EL NOMBRE DEL ARCHIVO
*/



# Nombre del archivo
header('Content-Disposition: attachment; filename=" Credipagos - ' . $fecha . '.xlsx"');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');


//forzar a descarga por el navegador
$objWriter->save('php://output');
