<?php

session_start();
// $id=$_GET['nrooc'];
// $id=$_POST['nrooc'];

// echo $id;



// header("Content-Type: text/html;charset=utf-8");

// <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

// header("Content-Type: text/html;charset=ISO-8859-1");


header('Content-Type: text/html; charset=ISO-8859-1');



$id = $_GET["idNotaSalida"];



//ajuntar la libreria excel
include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/usuarios.controlador.php";
require_once "../../modelos/usuarios.modelo.php";

/* 
* LLAMAMOS A LA CONEXION
*/
$conexion = Conexion::conectar();
// mismo charset que usaba la conexión mysql_* original (se mantiene utf8_encode más abajo)
$conexion->exec("set names latin1");
/* 
* CONFIGURAMOS LA FECHA ACTUAL
*/
date_default_timezone_set('America/Lima');
$fechaactual = getdate();

$fecha = date("d-m-Y");

$fechahora = new DateTime();

$hora = $fechahora->format("H:i:s");


$objPHPExcel = new PHPExcel(); //nueva instancia

$objPHPExcel->getProperties()->setCreator("Leydi"); //autor
$objPHPExcel->getProperties()->setTitle("Reporte de Nota de Salida"); //titulo

//inicio estilos
$titulo = new PHPExcel_Style(); //nuevo estilo
$titulo->applyFromArray(
  array(
    'alignment' => array( //alineacion
      'wrap' => false,
      'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
    ),
    'font' => array( //fuente
      'bold' => true,
      'size' => 16
    )
  )
);

$observaciones = new PHPExcel_Style(); //nuevo estilo
$observaciones->applyFromArray(
  array(
    'alignment' => array( //alineacion
      'wrap' => false,
      'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
    ),
    'font' => array( //fuente
      'bold' => true,
      'size' => 8
    )
  )
);

$subtitulo = new PHPExcel_Style(); //nuevo estilo

$subtitulo->applyFromArray(
  array(
    'fill' => array( //relleno de color
      'type' => PHPExcel_Style_Fill::FILL_SOLID,
      'color' => array('argb' => 'FF3399FF')
    ),
    'borders' => array( //bordes
      'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    )
  )
);


//FIRMAS

$firmas = new PHPExcel_Style(); //nuevo estilo
$firmas->applyFromArray(
  array(
    'alignment' => array( //alineacion
      'wrap' => false,
      'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
    ),
    'font' => array( //fuente
      'bold' => true,
      'size' => 14
    ),
    'borders' => array( //bordes
      'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    )
  )
);


$bordes = new PHPExcel_Style(); //nuevo estilo

$bordes->applyFromArray(
  array(
    'borders' => array(
      'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    )
  )
);
//fin estilos

$objPHPExcel->createSheet(0); //crear hoja
$objPHPExcel->setActiveSheetIndex(0); //seleccionar hora
$objPHPExcel->getActiveSheet()->setTitle("Reporte de Nota de Salida"); //establecer titulo de hoja

//orientacion hoja
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);

//tipo papel
$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);


//establecer impresion a pagina completa
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToPage(true);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);
//fin: establecer impresion a pagina completa


//establecer margenes
$margin = 0.5 / 3.54; // 0.5 centimetros
$marginBottom = 1.2 / 3.54; //1.2 centimetros
$objPHPExcel->getActiveSheet()->getPageMargins()->setTop($margin);
$objPHPExcel->getActiveSheet()->getPageMargins()->setBottom($marginBottom);
$objPHPExcel->getActiveSheet()->getPageMargins()->setLeft($margin);
$objPHPExcel->getActiveSheet()->getPageMargins()->setRight($margin);
//fin: establecer margenes



// //incluir una imagen
// $objDrawing = new PHPExcel_Worksheet_Drawing();
// $objDrawing->setPath('phpexcel_logo.jpg'); //ruta
// $objDrawing->setHeight(75); //altura
// $objDrawing->setCoordinates('A1');
// $objDrawing->setWorksheet($objPHPExcel->getActiveSheet()); //incluir la imagen
// //fin: incluir una imagen

//establecer titulos de impresion en cada hoja
$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 10);



$sqlPro = $conexion->prepare("SELECT DISTINCT vc.Tip, vc.Ser, vc.Nro, DATE_FORMAT(vc.FecEmi, '%d/%m/%Y') AS FecEmi, cli.Ruc, cli.CodCli, cli.RazCli, cli.DirCli, vc.UsuReg, vc.detdocsal 
      from Ventas_Cab vc, Clientes AS cli
        where  cli.Ruc= vc.Ruc
         and vc.Nro= :id 
       ");
$sqlPro->bindValue(":id", $id, PDO::PARAM_STR);
$sqlPro->execute();



$resPro = $sqlPro->fetch(PDO::FETCH_BOTH);



$fila = 1;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Empresa :');
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'CORPORACION VASCO S.A.C.');

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila",  'Fecha:');
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", $fecha);


// $objPHPExcel->getActiveSheet()->SetCellValue("D$fila",  $fecha);
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Tipo:');
$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $resPro["Tip"]);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);



$fila = 2;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Local :');
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", '.:: CORPORACION VASCO S.A.C. ::.');

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Hora: ");
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", $hora);
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Serie:');
$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $resPro["Ser"]);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode('000');

$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);


$fila = 3;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'Registrado por:');
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", $resPro["UsuReg"]);

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'Orden de Corte:');
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", $resPro["detdocsal"]);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Número:');
$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", $resPro["Nro"]);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getNumberFormat()->setFormatCode('000000');

$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);


$fila = 5;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'F.Emision :');
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", $resPro["FecEmi"]);
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'Cliente :');
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", $resPro["Ruc"] . '-' . $resPro["RazCli"]);
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);




$fila = 6;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", "Moneda : ");
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "NUEVOS SOLES");
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Direccion: ");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila",  utf8_encode($resPro["DirCli"]));
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);


$fila = 7;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", "Almacen: ");
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "MATERIA PRIMA");
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Tipo de Salida:");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", "11-SALIDA DE ALMACEN");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);


$fila = 9;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", "NOTA DE SALIDA DEL ALMACEN - MATERIA PRIMA");
$objPHPExcel->getActiveSheet()->mergeCells("A$fila:G$fila"); //unir celdas
$objPHPExcel->getActiveSheet()->setSharedStyle($titulo, "A$fila:G$fila"); //establecer estilo

$fila = 10;

//titulos de columnas
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'ITE');
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'COD FABRICA');
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'DESCRIPCION');
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'COLOR');
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'PAIS');
$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", 'UND');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'CANTIDAD');
$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", 'DESTINO');
$objPHPExcel->getActiveSheet()->setSharedStyle($subtitulo, "A$fila:H$fila"); //establecer estilo
$objPHPExcel->getActiveSheet()->getStyle("A$fila:H$fila")->getFont()->setBold(true); //negrita

$objPHPExcel->getActiveSheet()->getStyle("C$fila:H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);



//rellenar con contenido



$sql = $conexion->prepare("SELECT DISTINCT 
    vd.Item,
    pro.CodFab,
    pro.CodPro,
    TbCol.Des_Larga AS DesCol,
    TbPai.Des_Larga AS DesPai,
    TbUnd.Des_Larga AS DesUnd,
    vd.CanVta,
    pro.DesPro,
    pro.ColPro,
    pro.CosPro,
    vd.CodPro,
    vd.PreVta,
    vd.CanVta,
    CASE
      WHEN vd.CenCosto IS NULL 
      THEN '' 
      ELSE cc.nombre_area 
    END AS Destino 
  FROM
    Venta_Det vd 
    INNER JOIN Producto pro 
      ON pro.CodPro = vd.CodPro 
    LEFT JOIN Tabla_M_Detalle AS TbCol 
      ON TbCol.Cod_Tabla IN ('TCOL') 
      AND TbCol.Cod_Argumento = pro.ColPro 
    LEFT JOIN Tabla_M_Detalle AS TbPai 
      ON TbPai.Cod_Tabla IN ('PAIS') 
      AND TbPai.Cod_Argumento = pro.PaiPro 
    LEFT JOIN Tabla_M_Detalle AS TbUnd 
      ON TbUnd.Cod_Tabla IN ('TUND') 
      AND TbUnd.Cod_Argumento = pro.UndPro 
    LEFT JOIN centro_costos cc 
      ON cc.cod_area = vd.CenCosto 
  WHERE vd.Nro = :id 
  ORDER BY Item ASC");
$sql->bindValue(":id", $id, PDO::PARAM_STR);
$sql->execute();




while ($res = $sql->fetch(PDO::FETCH_BOTH)) {

  // $CodPro=$res["CodPro"]; 
  // ITE COD PROD  DESCRIPCION COLOR COLOR PROV. CANTIDAD  UND P.UNITARIO  % DSCTO TOTAL



  $fila += 1;
  $objPHPExcel->getActiveSheet()->SetCellValue("A$fila", $res["Item"]);
  $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", $res["CodPro"]);
  $objPHPExcel->getActiveSheet()->SetCellValue("C$fila",  utf8_encode($res["DesPro"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("D$fila",  utf8_encode($res["DesCol"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("E$fila",  utf8_encode($res["DesPai"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", $res["DesUnd"]);
  $objPHPExcel->getActiveSheet()->SetCellValue("G$fila", $res["CanVta"]);
  $objPHPExcel->getActiveSheet()->SetCellValue("H$fila",  utf8_encode($res["Destino"]));







  //Establecer estilo
  $objPHPExcel->getActiveSheet()->setSharedStyle($bordes, "A$fila:H$fila");

  $objPHPExcel->getActiveSheet()->getStyle("A$fila:H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
  $objPHPExcel->getActiveSheet()->getStyle("B$fila")->getNumberFormat()->setFormatCode('00000');
}



//insertar formula
$fila += 2;
$objPHPExcel->getActiveSheet()->SetCellValue("A$fila", '');

//insertar formula
$fila += 3;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'ALMACÉN DE MATERIA PRIMA');
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'SOLICITANTE');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'LOGÍSTICA');

$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->setSharedStyle($firmas, "C$fila"); //establecer estilo
$objPHPExcel->getActiveSheet()->setSharedStyle($firmas, "E$fila"); //establecer estilo
$objPHPExcel->getActiveSheet()->setSharedStyle($firmas, "G$fila"); //establecer estilo





//recorrer las columnas
// foreach (range( 'C', 'D' , 'E' , 'F' , 'G' , 'H' , 'I' , 'J') as $columnID) {
//autodimensionar las columnas
// $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
// $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setWidth(10);

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(14);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(17);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(17);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(17);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(17);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);

// }






$objPHPExcel->getActiveSheet()->setSharedStyle($observaciones, "A$fila"); //establecer estilo



//establecer pie de impresion en cada hoja
$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&R&F página &P / &N');

//*************Guardar como excel 2003*********************************
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel); //Escribir archivo

// Establecer formado de Excel 2003
header("Content-Type: application/vnd.ms-excel");

// nombre del archivo
header('Content-Disposition: attachment; filename="Reporte_NotaSalida.xls"');
//**********************************************************************

//****************Guardar como excel 2007*******************************
//$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); //Escribir archivo
//
//// Establecer formado de Excel 2007
//header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//
//// nombre del archivo
//header('Content-Disposition: attachment; filename="kiuvox.xlsx"');
//**********************************************************************

//forzar a descarga por el navegador
$objWriter->save('php://output');
