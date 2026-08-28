<?php

header('Content-Type: text/html; charset=ISO-8859-1');

/* 
* RECIBIMOS VARIABLE DESDE LA VISTA
*/

#$id = $_GET["codigo"];

date_default_timezone_set('America/Lima');
$dia=date('d/m/Y');
$hora=date('h:i:s');



/* 
* LLAMAMOS A LA LIBRERIA PHPEXCEL
*/
include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/usuarios.controlador.php";
require_once "../../modelos/usuarios.modelo.php";

require_once "../../controladores/articulos.controlador.php";
require_once "../../modelos/articulos.modelo.php";

/* 
* LLAMAMOS A LA CONEXION
*/
$con=ControladorUsuarios::ctrMostrarConexiones("id",1);

try {
  $conexion = new PDO(
    "mysql:host=" . $con["ip"] . ";dbname=" . $con["db"],
    $con["user"],
    $con["pwd"],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
  );
  $conexion->exec("set names utf8");
} catch (PDOException $e) {
  die("No se pudo conectar a la base de datos.");
}

/* 
* CONFIGURAMOS LA FECHA ACTUAL
*/
$fechaactual = getdate();
$fecha = "$fechaactual[mday]/$fechaactual[mon]/$fechaactual[year]";

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
#negrita-subrayado-13-rojo
$texto_1 = new PHPExcel_Style();
$texto_1->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'font' => array(
      'bold' => true,
      'color' => array('rgb' => 'FF0008'),
      'underline' =>true,
      'size' => 13
    )
));

#negrita-11-negro
$texto_2 = new PHPExcel_Style();
$texto_2->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'font' => array(
      'bold' => true,
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 11
    )
));

#normal-10
$texto_3 = new PHPExcel_Style();
$texto_3->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'font' => array(
      'bold' => false,
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 10
    )
));

#normal-11-azul
$texto_4 = new PHPExcel_Style();
$texto_4->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'font' => array(
      'bold' => true,
      'underline' =>false,
      'color' => array('rgb' => '0400FF'),
      'size' => 11
    )
));

#negrita-11-rojo
$borde_1 = new PHPExcel_Style();
$borde_1->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => 'FF0008'),
      'size' => 8
    )
));

#negrita-11-azul
$borde_2 = new PHPExcel_Style();
$borde_2->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => '0400FF'),
      'size' => 8
    )
));

#negrita-11-negro
$borde_3 = new PHPExcel_Style();
$borde_3->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 8
    )
));

#negrita-11-celeste
$borde_4 = new PHPExcel_Style();
$borde_4->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => '0174BB'),
      'size' => 8
    )
));

#negrita-11-verde
$borde_5 = new PHPExcel_Style();
$borde_5->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => '00833E'),
      'size' => 8
    )
));

#normal-10-negro
$borde_6 = new PHPExcel_Style();
$borde_6->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 8
    )
));

#negrita-10-vino
$borde_7 = new PHPExcel_Style();
$borde_7->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => 'A4001E'),
      'size' => 8
    )
));

#negrita-10-rojo/rosado
$borde_8 = new PHPExcel_Style();
$borde_8->applyFromArray(
  array('alignment' => array(
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
      'underline' =>false,
      'color' => array('rgb' => 'FF0008'),
      'size' => 8
    )
));

#solo verticales: agrupa tallas del mismo color / modelo / taller
$borde_2v = new PHPExcel_Style();
$borde_2v->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'borders' => array(
        'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array(
      'bold' => true,
      'underline' =>false,
      'color' => array('rgb' => '0400FF'),
      'size' => 8
    )
));

$borde_3v = new PHPExcel_Style();
$borde_3v->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'borders' => array(
        'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array(
      'bold' => true,
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 8
    )
));

$borde_6v = new PHPExcel_Style();
$borde_6v->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'borders' => array(
        'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array(
      'bold' => false,
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 8
    )
));

#cierre de grupo (linea inferior)
$borde_2b = new PHPExcel_Style();
$borde_2b->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'borders' => array(
        'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array(
      'bold' => true,
      'underline' =>false,
      'color' => array('rgb' => '0400FF'),
      'size' => 8
    )
));

$borde_3b = new PHPExcel_Style();
$borde_3b->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'borders' => array(
        'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array(
      'bold' => true,
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 8
    )
));

$borde_6b = new PHPExcel_Style();
$borde_6b->applyFromArray(
  array('alignment' => array(
      'wrap' => false
    ),
    'borders' => array(
        'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
        'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array(
      'bold' => false,
      'underline' =>false,
      'color' => array('rgb' => '000000'),
      'size' => 8
    )
));

/* 
* FIN DE ESTILOS
*/

/* 
* CONFIGURAMOS LA 1ERA HOJA
*/

$objPHPExcel->createSheet(0);
$objPHPExcel->setActiveSheetIndex(0);

# Titulo de la hoja
$objPHPExcel->getActiveSheet()->setTitle('Urgencias');

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

# Establecer cabecera de cada Hoja
$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 8);
$objPHPExcel->getActiveSheet()->freezePaneByColumnAndRow(0,9);


# Incluir una imagen
$objDrawing = new PHPExcel_Worksheet_Drawing();
$objDrawing->setPath('img/jackyform_letras.png'); //ruta
$objDrawing->setWidthAndHeight(200, 150);
$objDrawing->setCoordinates('A1');
$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

/* 
todo: INICIO CABECERA
*/

#query para sacar los datos de la cabecera
$sqlCabecera = $conexion->query("SELECT DISTINCT 
                                    ROUND(urgencia, 0) AS urgencia,
                                    p.pedidos,
                                    f.faltantes,
                                    ROUND((f.faltantes * 100 / p.pedidos), 2) AS quiebre 
                                    FROM
                                    articulojf a 
                                    JOIN 
                                    (SELECT 
                                        SUM(a.pedidos) AS pedidos 
                                    FROM
                                        articulojf a 
                                    WHERE a.estado = 'activo') AS p 
                                    JOIN 
                                    (SELECT 
                                        SUM(a.stock - a.pedidos) AS faltantes 
                                    FROM
                                        articulojf a 
                                    WHERE a.pedidos > a.stock 
                                        AND a.estado = 'activo') AS f");

$respCabecera = $sqlCabecera->fetch(PDO::FETCH_ASSOC);
if (!$respCabecera) {
  $respCabecera = array(
    "urgencia" => 0,
    "faltantes" => 0,
    "quiebre" => 0,
    "pedidos" => 0
  );
}

$fila = 2;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'STOCK POR DEBAJO DEL '.utf8_encode($respCabecera["urgencia"]).'% DE LAS VENTAS DE LOS ULTIMOS');
$objPHPExcel->getActiveSheet()->mergeCells("C$fila:I$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_1, "C$fila:I$fila");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$fila =3;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", '30 DIAS');
$objPHPExcel->getActiveSheet()->mergeCells("C$fila:I$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_1, "C$fila:I$fila");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", 'FECHA:');
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "J$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $dia);
$objPHPExcel->getActiveSheet()->mergeCells("K$fila:L$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "K$fila:L$fila");
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$fila =4;
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", 'HORA:');
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "J$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $hora);
$objPHPExcel->getActiveSheet()->mergeCells("K$fila:L$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "K$fila:L$fila");
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$fila =6;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'Prendas Faltantes :');
$objPHPExcel->getActiveSheet()->mergeCells("B$fila:C$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_3, "B$fila:C$fila");
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", utf8_encode($respCabecera["faltantes"]));
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "D$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", utf8_encode($respCabecera["quiebre"]));
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", 'Prendas en pedidos :');
$objPHPExcel->getActiveSheet()->mergeCells("H$fila:J$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_4, "H$fila:J$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", utf8_encode($respCabecera["pedidos"]));
$objPHPExcel->getActiveSheet()->mergeCells("K$fila:L$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "K$fila:L$fila");
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

/* 
todo: FIN CABECERA
*/

/* 
todo: INICIO DETALLE
*/
$fila = 8;
$objPHPExcel->getActiveSheet()->getRowDimension($fila)->setRowHeight(38.06);

$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'TALLER');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "A$fila");
$objPHPExcel->getActiveSheet()->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("A$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'ART.');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "B$fila");
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'NOMBRE');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "C$fila");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'COLOR');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "D$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'TALLA');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "E$fila");
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", 'PROYEC');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_4, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", '% AVANCE');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_5, "G$fila");
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", 'MAT. FALTANTE');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "H$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", 'STOCK');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_4, "I$fila");
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", 'PEDIDOS');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "J$fila");
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", 'EN TALLER');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "K$fila");
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("L$fila", 'ALM. CORTE');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "L$fila");
$objPHPExcel->getActiveSheet()->getStyle("L$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("L$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("L$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("M$fila", 'ORD. CORTE');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "M$fila");
$objPHPExcel->getActiveSheet()->getStyle("M$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("M$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("M$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("N$fila", 'VTAS. ULT. 30 DIAS');
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_4, "N$fila");
$objPHPExcel->getActiveSheet()->getStyle("N$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("N$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("N$fila")->getAlignment()->setWrapText(true);


$articulos  = ControladorArticulos::ctrArticulosUrgencia();

for ($i=0; $i < count($articulos)-1; $i++) { 
    
    $fila+= 1;

    //* nombre del taller
    if(!isset($articulos[$i-1]["nom_taller"])){

        $articulos[$i-1]["nom_taller"] =  "FALLAR";

    }else{

        $articulos[$i-1]["nom_taller"];

    }
    
    if($articulos[$i]["nom_taller"] == $articulos[$i-1]["nom_taller"]){

            $nom_taller = "";        

    }else{

        $nom_taller = $articulos[$i]["nom_taller"];

    }

    //*modelo
    if(!isset($articulos[$i-1]["modelo"])){

        $articulos[$i-1]["modelo"] =  "FALLAR";

    }else{

        $articulos[$i-1]["modelo"];

    }
    
    if($articulos[$i]["modelo"] == $articulos[$i-1]["modelo"]){

            $modelo = "";        

    }else{

        $modelo = $articulos[$i]["modelo"];

    }

    //*nombre
    if(!isset($articulos[$i-1]["nombre"])){

        $articulos[$i-1]["nombre"] =  "FALLAR";

    }else{

        $articulos[$i-1]["nombre"];

    }
    
    if($articulos[$i]["nombre"] == $articulos[$i-1]["nombre"]){

            $nombre = "";        

    }else{

        $nombre = $articulos[$i]["nombre"];

    }


    //*color
    if(!isset($articulos[$i-1]["color"])){

        $articulos[$i-1]["color"] =  "FALLAR";

    }else{

        $articulos[$i-1]["color"];

    }
    
    if($articulos[$i]["color"] == $articulos[$i-1]["color"] && $articulos[$i]["nombre"] == $articulos[$i-1]["nombre"]){

            $color = "";        

    }else{

        $color = $articulos[$i]["color"];

    }

    $objPHPExcel->getActiveSheet()->SetCellValue("A$fila", utf8_encode($nom_taller));
    $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", utf8_encode($modelo));
    $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($nombre));
    $objPHPExcel->getActiveSheet()->SetCellValue("D$fila", utf8_encode($color));
    $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", "T".utf8_encode($articulos[$i]["talla"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", utf8_encode($articulos[$i]["proyeccion"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("G$fila", utf8_encode($articulos[$i]["avance"])." %");
    $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", utf8_encode($articulos[$i]["mp_faltante"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($articulos[$i]["stockB"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($articulos[$i]["pedidos"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", utf8_encode($articulos[$i]["taller"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("L$fila", utf8_encode($articulos[$i]["alm_corteA"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("M$fila", utf8_encode($articulos[$i]["ord_corte"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("N$fila", utf8_encode($articulos[$i]["ult_mes"]));

    $esUltimo = ($i >= count($articulos) - 2);
    $sig = $esUltimo ? null : $articulos[$i + 1];
    $cambioTaller = $esUltimo || ($articulos[$i]["nom_taller"] != $sig["nom_taller"]);
    $cambioModelo = $cambioTaller || ($articulos[$i]["modelo"] != $sig["modelo"]);
    $cambioColor = $cambioModelo || ($articulos[$i]["color"] != $sig["color"]) || ($articulos[$i]["nombre"] != $sig["nombre"]);

    $objPHPExcel->getActiveSheet()->setSharedStyle($cambioTaller ? $borde_3b : $borde_3v, "A$fila");
    $objPHPExcel->getActiveSheet()->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->setSharedStyle($cambioModelo ? $borde_2b : $borde_2v, "B$fila");
    $objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->setSharedStyle($cambioModelo ? $borde_6b : $borde_6v, "C$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($cambioColor ? $borde_6b : $borde_6v, "D$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "E$fila");
    $objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_5, "F$fila");

    if(utf8_encode($articulos[$i]["avance"]) < 90){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_5, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }

    if(utf8_encode($articulos[$i]["mp_faltante"]) == "F/TELA"){

      $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "H$fila");

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_7, "H$fila");

    }

    if(utf8_encode($articulos[$i]["stockB"]) < 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "I$fila");

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_7, "I$fila");

    }

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "J$fila");

    if(utf8_encode($articulos[$i]["taller"]) <= 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "K$fila");

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "K$fila");

    }

    if(utf8_encode($articulos[$i]["alm_corte"]) <= 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "L$fila");
        $objPHPExcel->getActiveSheet()->getStyle("L$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "L$fila");
        $objPHPExcel->getActiveSheet()->getStyle("L$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }
    
    if(utf8_encode($articulos[$i]["ord_corte"]) <= 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "M$fila");

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "M$fila");

    }

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_4, "N$fila");    

}

#query para sacar los datos deL detalle
/* $sqlDetalle = mysql_query("SELECT 
                                'a' AS inicio,
                                'T3 - TRUSAS' AS nom_taller,
                                a.articulo,
                                a.id_marca,
                                a.marca,
                                a.modelo,
                                a.nombre,
                                a.cod_color,
                                a.color,
                                a.cod_talla,
                                a.talla,
                                a.estado,
                                a.urgencia,
                                a.mp_faltante,
                                a.alerta,
                                ROUND(
                                    (
                                    IFNULL(a.ult_mes, 0) * a.urgencia / 100
                                    ),
                                    0
                                ) AS configuracion,
                                CASE
                                    WHEN a.stock < 0 
                                    THEN 0 
                                    ELSE a.stock 
                                END AS stock,
                                (a.stock - a.pedidos) AS stockB,
                                a.pedidos,
                                (a.taller + a.servicio) AS taller,
                                a.alm_corte,
                                CASE
                                    WHEN a.alerta = '0' 
                                    THEN a.alm_corte 
                                    ELSE CONCAT('I-', a.alm_corte) 
                                END alm_corteA,
                                a.ord_corte,
                                a.proyeccion,
                                IFNULL(a.prod, 0) AS prod,
                                IFNULL(
                                    ROUND(
                                    (IFNULL(a.prod, 0) / a.proyeccion) * 100,
                                    2
                                    ),
                                    0
                                ) AS avance,
                                IFNULL(a.ult_mes, 0) AS ult_mes 
                                FROM
                                articulojf a 
                                LEFT JOIN modelojf m 
                                    ON a.modelo = m.modelo 
                                WHERE ROUND(
                                    (
                                    IFNULL(a.ult_mes, 0) * a.urgencia / 100
                                    ),
                                    0
                                ) > (a.stock - a.pedidos) 
                                AND a.estado = 'Activo' 
                                AND LEFT(a.modelo, 1) NOT IN ('D') 
                                AND a.servicio = 0 
                                AND m.tipo NOT IN ('BRASIER','SEAMLESS') 
                                UNION
                                SELECT 
                                'b' AS inicio,
                                CONCAT(
                                    CASE
                                    WHEN sd.taller IS NULL 
                                    THEN cd.taller 
                                    WHEN cd.taller IS NULL 
                                    THEN sd.taller 
                                    WHEN sd.taller = cd.taller 
                                    THEN sd.taller 
                                    WHEN sd.taller <> cd.taller 
                                    THEN sd.taller 
                                    END,
                                    ' - ',
                                    (SELECT 
                                    nom_sector 
                                    FROM
                                    sectorjf s 
                                    WHERE s.cod_sector = 
                                    CASE
                                        WHEN sd.taller IS NULL 
                                        THEN cd.taller 
                                        WHEN cd.taller IS NULL 
                                        THEN sd.taller 
                                        WHEN sd.taller = cd.taller 
                                        THEN sd.taller 
                                        WHEN sd.taller <> cd.taller 
                                        THEN sd.taller 
                                    END)
                                ) AS nom_sector,
                                a.articulo,
                                a.id_marca,
                                a.marca,
                                a.modelo,
                                a.nombre,
                                a.cod_color,
                                a.color,
                                a.cod_talla,
                                a.talla,
                                a.estado,
                                a.urgencia,
                                a.mp_faltante,
                                a.alerta,
                                ROUND(
                                    (
                                    IFNULL(a.ult_mes, 0) * a.urgencia / 100
                                    ),
                                    0
                                ) AS configuracion,
                                CASE
                                    WHEN a.stock < 0 
                                    THEN 0 
                                    ELSE a.stock 
                                END AS stock,
                                (a.stock - a.pedidos) AS stockB,
                                a.pedidos,
                                CASE
                                    WHEN sd.taller IS NULL 
                                    THEN cd.cantidad 
                                    WHEN cd.taller IS NULL 
                                    THEN sd.saldo 
                                    WHEN sd.taller = cd.taller 
                                    THEN sd.saldo + cd.cantidad 
                                    WHEN sd.taller <> cd.taller 
                                    THEN sd.saldo 
                                END AS taller,
                                a.alm_corte,
                                CASE
                                    WHEN a.alerta = '0' 
                                    THEN a.alm_corte 
                                    ELSE CONCAT('I-', a.alm_corte) 
                                END alm_corteA,
                                a.ord_corte,
                                a.proyeccion,
                                IFNULL(a.prod, 0) AS prod,
                                IFNULL(
                                    ROUND(
                                    (IFNULL(a.prod, 0) / a.proyeccion) * 100,
                                    2
                                    ),
                                    0
                                ) AS avance,
                                IFNULL(a.ult_mes, 0) AS ult_mes 
                                FROM
                                articulojf a 
                                LEFT JOIN 
                                    (SELECT 
                                    LEFT(sd.codigo, 2) AS taller,
                                    sd.articulo,
                                    SUM(sd.saldo) AS saldo 
                                    FROM
                                    servicios_detallejf sd 
                                    WHERE sd.saldo > 0 
                                    AND sd.cerrar = 0 
                                    GROUP BY LEFT(sd.codigo, 2),
                                    sd.articulo) AS sd 
                                    ON a.articulo = sd.articulo 
                                LEFT JOIN 
                                    (SELECT 
                                    LEFT(cd.codigo, 2) AS taller,
                                    cd.articulo,
                                    SUM(cd.cantidad) AS cantidad 
                                    FROM
                                    cierres_detallejf cd 
                                    WHERE cd.cantidad > 0 
                                    GROUP BY LEFT(cd.codigo, 2),
                                    cd.articulo) AS cd 
                                    ON a.articulo = cd.articulo 
                                WHERE a.estado = 'ACTIVO' 
                                AND servicio > 0 
                                AND ROUND(
                                    (
                                    IFNULL(a.ult_mes, 0) * a.urgencia / 100
                                    ),
                                    0
                                ) > (a.stock - a.pedidos) 
                                GROUP BY a.articulo,
                                sd.taller 
                                UNION
                                SELECT 
                                'c' AS inicio,
                                'T1 - BRASIER' AS nom_taller,
                                a.articulo,
                                a.id_marca,
                                a.marca,
                                a.modelo,
                                a.nombre,
                                a.cod_color,
                                a.color,
                                a.cod_talla,
                                a.talla,
                                a.estado,
                                a.urgencia,
                                a.mp_faltante,
                                a.alerta,
                                ROUND(
                                    (
                                    IFNULL(a.ult_mes, 0) * a.urgencia / 100
                                    ),
                                    0
                                ) AS configuracion,
                                CASE
                                    WHEN a.stock < 0 
                                    THEN 0 
                                    ELSE a.stock 
                                END AS stock,
                                (a.stock - a.pedidos) AS stockB,
                                a.pedidos,
                                (a.taller + a.servicio) AS taller,
                                a.alm_corte,
                                CASE
                                    WHEN a.alerta = '0' 
                                    THEN a.alm_corte 
                                    ELSE CONCAT('I-', a.alm_corte) 
                                END alm_corteA,
                                a.ord_corte,
                                a.proyeccion,
                                IFNULL(a.prod, 0) AS prod,
                                IFNULL(
                                    ROUND(
                                    (IFNULL(a.prod, 0) / a.proyeccion) * 100,
                                    2
                                    ),
                                    0
                                ) AS avance,
                                IFNULL(a.ult_mes, 0) AS ult_mes 
                                FROM
                                articulojf a 
                                LEFT JOIN modelojf m 
                                    ON a.modelo = m.modelo 
                                WHERE ROUND(
                                    (
                                    IFNULL(a.ult_mes, 0) * a.urgencia / 100
                                    ),
                                    0
                                ) > (a.stock - a.pedidos) 
                                AND a.estado = 'Activo' 
                                AND LEFT(a.modelo, 1) NOT IN ('D') 
                                AND m.tipo IN ('BRASIER','SEAMLESS') 
                                ORDER BY inicio,
                                nom_taller,
                                articulo"
              
) or die(mysql_error()); */

/* while($respDetalle = mysql_fetch_array($sqlDetalle)){



    $fila+= 1;
    $objPHPExcel->getActiveSheet()->SetCellValue("A$fila", utf8_encode($respDetalle["nom_taller"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", utf8_encode($respDetalle["modelo"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($respDetalle["nombre"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("D$fila", utf8_encode($respDetalle["color"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", "T".utf8_encode($respDetalle["talla"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", utf8_encode($respDetalle["proyeccion"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("G$fila", utf8_encode($respDetalle["avance"])." %");
    $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", utf8_encode($respDetalle["mp_faltante"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($respDetalle["stockB"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($respDetalle["pedidos"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", utf8_encode($respDetalle["taller"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("L$fila", utf8_encode($respDetalle["alm_corteA"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("M$fila", utf8_encode($respDetalle["ord_corte"]));
    $objPHPExcel->getActiveSheet()->SetCellValue("N$fila", utf8_encode($respDetalle["ult_mes"]));

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "A$fila");
    $objPHPExcel->getActiveSheet()->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "B$fila");
    $objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "C$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "D$fila");
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "E$fila");
    $objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_5, "F$fila");

    if(utf8_encode($respDetalle["avance"]) < 90){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_5, "G$fila");
        $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }

    if(utf8_encode($respDetalle["mp_faltante"]) == "F/TELA"){

      $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "H$fila");

  }else{

      $objPHPExcel->getActiveSheet()->setSharedStyle($borde_7, "H$fila");

  }

    if(utf8_encode($respDetalle["stockB"]) < 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "I$fila");

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_7, "I$fila");

    }

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "J$fila");

    if(utf8_encode($respDetalle["taller"]) <= 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "K$fila");

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "K$fila");

    }

    if(utf8_encode($respDetalle["alm_corte"]) <= 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_8, "L$fila");
        $objPHPExcel->getActiveSheet()->getStyle("L$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "L$fila");
        $objPHPExcel->getActiveSheet()->getStyle("L$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    }
    
    if(utf8_encode($respDetalle["ord_corte"]) <= 0){

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "M$fila");

    }else{

        $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "M$fila");

    }

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_4, "N$fila");

} */




/* 
todo: FIN DETALLE
*/


# Ajustar el tamaño de las columnas
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(11.42);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(7.85);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25.71);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(13.14);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(5.71);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(8.57);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(8.57);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(8.57);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(8.57);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(8.57);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(10);



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
header('Content-Disposition: attachment; filename="Urgencias-'.date('d-m-Y').'.xls"');


//forzar a descarga por el navegador
$objWriter->save('php://output');
