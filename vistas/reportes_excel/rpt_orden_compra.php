<?php

session_start();
// $id=$_GET['nrooc'];
// $id=$_POST['nrooc'];

// echo $id;



// header("Content-Type: text/html;charset=utf-8");

// <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

// header("Content-Type: text/html;charset=ISO-8859-1");


header('Content-Type: text/html; charset=ISO-8859-1');



$id = $_GET["idOrdenCompra"];



//ajuntar la libreria excel
include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/usuarios.controlador.php";
require_once "../../modelos/usuarios.modelo.php";

/* 
* LLAMAMOS A LA CONEXION
*/
$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);

$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
mysql_select_db($con["db"], $conexion);

$fechaactual = getdate();
// print_r($fechaactual);
$fecha = "$fechaactual[mday]/$fechaactual[mon]/$fechaactual[year]";

//$UsuReg=$_SESSION['nombre'];



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

$titulo2 = new PHPExcel_Style(); //nuevo estilo
$titulo2->applyFromArray(
  array(
    'alignment' => array( //alineacion
      'wrap' => false,
      'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
    ),
    'font' => array( //fuente
      'bold' => true,
      'size' => 20
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

$bordes = new PHPExcel_Style(); //nuevo estilo

$bordes->applyFromArray(
  array(
    'borders' => array(
      'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array( //fuente
      'bold' => true,
      'size' => 14
    )
  )
);

$bordes1 = new PHPExcel_Style(); //nuevo estilo

$bordes1->applyFromArray(
  array(
    'borders' => array(
      'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
      'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    ),
    'font' => array( //fuente
      'bold' => true,
      'size' => 12
    )
  )
);


//fin estilos

$objPHPExcel->createSheet(0); //crear hoja
$objPHPExcel->setActiveSheetIndex(0); //seleccionar hora
$objPHPExcel->getActiveSheet()->setTitle("Orden de Compra"); //establecer titulo de hoja

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


//incluir una imagen
$objDrawing = new PHPExcel_Worksheet_Drawing();
$objDrawing->setPath('../img/plantilla/LogoJacky.png'); //ruta
$objDrawing->setHeight(75); //altura
// $objDrawing->setWeight(10); //altura
$objDrawing->setCoordinates('B2');
$objDrawing->setWorksheet($objPHPExcel->getActiveSheet()); //incluir la imagen
//fin: incluir una imagen






//establecer titulos de impresion en cada hoja
$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 10);

$sqlPro = mysql_query("SELECT DISTINCT 
            (oCompra.Nro),
            oCompra.NroProforma,
            oCompra.Ser,
            oCompra.Nro,
            DATE_FORMAT(oCompra.FecEmi, '%d/%m/%Y') AS FecEmi,
            oCompra.Centcosto,
            p.CodRuc,
            RucPro,
            RazPro,
            DirPro,
            Telpro1,
            TelPro2,
            TelPro3,
            FaxPro,
            ConPro,
            EmaPro,
            EmaPro2,
            WebPro,
            TieEnt,
            oCompra.TipPago,
            p.Dia,
            oCompra.Mo,
            Moneda,
            NroCta,
            EstPro,
            Observa,
            oCompra.Centcosto,
            DATE_FORMAT(oCompra.Fecllegada, '%d/%m/%Y') AS Fecllegada,
            IFNULL(Ubigeo.Distrito, '') AS Dispro,
            oCompra.Obser,
            oCompra.tCambio,
            IFNULL(Tabla_M_Detalle_1.Des_Larga, '') AS DesTipPago,
            IFNULL(Tabla_M_Detalle_2.Des_Larga, '') AS CenCosto,
            IFNULL(Tabla_M_Detalle_3.Des_Larga, '') AS Moneda2,
            p.banco,
            (SELECT 
              Descripcion 
            FROM
              maestrajf t 
            WHERE t.tipo_dato = 'TBAN' 
              AND t.codigo = p.banco) AS nom_ban,
            p.moneda,
            (SELECT 
              des_larga 
            FROM
              tabla_m_detalle t 
            WHERE t.cod_argumento = p.moneda 
              AND t.cod_tabla = 'TMON') AS nom_mon,
            p.nrocta,
            p.cci,
            p.banco1,
            (SELECT 
              Descripcion 
            FROM
              maestrajf t 
            WHERE t.tipo_dato = 'TBAN' 
              AND t.codigo = p.banco1) AS nom_ban1,
            p.moneda1,
            (SELECT 
              des_larga 
            FROM
              tabla_m_detalle t 
            WHERE t.cod_argumento = p.moneda1 
              AND t.cod_tabla = 'TMON') AS nom_mon1,
            p.nrocta1,
            p.cci1 
            FROM
            Proveedor p 
            LEFT JOIN Ubigeo 
              ON p.UbiPro = Ubigeo.Codigo 
            LEFT JOIN oCompra 
              ON p.CodRuc = oCompra.CodRuc 
            LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_1 
              ON oCompra.TipPago = Tabla_M_Detalle_1.Cod_Argumento 
              AND (
                Tabla_M_Detalle_1.Cod_Tabla = 'TFOR' 
                OR Tabla_M_Detalle_1.Cod_Tabla IS NULL
              ) 
            LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_2 
              ON oCompra.CentCosto = Tabla_M_Detalle_2.Des_Corta 
              AND (
                Tabla_M_Detalle_2.Cod_Tabla = 'TDET' 
                OR Tabla_M_Detalle_2.Cod_Tabla IS NULL
              ) 
            LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_3 
              ON oCompra.Mo = Tabla_M_Detalle_3.Cod_Argumento 
              AND (
                Tabla_M_Detalle_3.Cod_Tabla = 'TMON' 
                OR Tabla_M_Detalle_3.Cod_Tabla IS NULL
              ) 
            WHERE oCompra.Ser IS NOT NULL 
                        AND Nro = $id
                      ORDER BY Nro ASC ");

$resPro = mysql_fetch_array($sqlPro);

$fila = 2;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'CORPORACION VASCO S.A.C.');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Orden de Compra N°:');
$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $resPro["Nro"]);
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode('000000');
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:I$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($titulo, "G$fila:I$fila"); //establecer estilo
// $objPHPExcel->getActiveSheet()->mergeCells("J$fila:K$fila");


$fila = 3;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'R.U.C. : 20513613939');


$fila = 4;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'Dirección: CALLE SANTO TORIBIO No 259');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Fec.Emisión:');
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", $resPro["FecEmi"]);
$objPHPExcel->getActiveSheet()->mergeCells("J$fila:K$fila");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:I$fila");
// $objPHPExcel->getActiveSheet()->setCellValue('A3', 29);
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

// $objPHPExcel->getActiveSheet()->getStyle('A3')->getNumberFormat()->setFormatCode('0000'); 


$fila = 5;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'Distrito: SAN MARTIN DE PORRES');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Fec.Entrega:');
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", $resPro["Fecllegada"]);
$objPHPExcel->getActiveSheet()->mergeCells("J$fila:K$fila");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:I$fila");
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$fila = 6;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'Telefono(s): 5372501');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'Forma de Pago:');
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($resPro["DesTipPago"]) . ' - DÍAS: ' . utf8_encode($resPro["Dia"]));
$objPHPExcel->getActiveSheet()->mergeCells("J$fila:K$fila");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:I$fila");


$fila = 7;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "Proveedor: ");
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($resPro["RazPro"]));
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Moneda: ");
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($resPro["Moneda2"]));
// $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
// $objPHPExcel->getActiveSheet() ->getStyle("J$fila")  ->getAlignment()  ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->mergeCells("G$fila:I$fila");
$objPHPExcel->getActiveSheet()->mergeCells("J$fila:K$fila");


$fila = 8;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "R.U.C.:");
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($resPro["RucPro"]));
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Telefonos: " . utf8_encode($resPro["Telpro1"]));

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Tipo de Cambio:");
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($resPro["tCambio"]));
$objPHPExcel->getActiveSheet()->mergeCells("J$fila:K$fila");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:I$fila");


$fila = 9;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "Dirección:");
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($resPro["DirPro"]) . ' - ' . utf8_encode($resPro["Dispro"]));

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "N° Cotización/Proforma:");
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($resPro["NroProforma"]));
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$objPHPExcel->getActiveSheet()->mergeCells("J$fila:K$fila");


$fila = 10;

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "CTA: ");
$objPHPExcel->getActiveSheet()->SetCellValue(
  "C$fila",
  !empty(utf8_encode($resPro["nrocta"]))
    ? utf8_encode($resPro["nom_ban"]) . " " . utf8_encode($resPro["nom_mon"]) . ": " . utf8_encode($resPro["nrocta"]) . (!empty(utf8_encode($resPro["cci"])) ? " /CCI: " . utf8_encode($resPro["cci"]) : "")
    : ""
);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Contacto:");
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($resPro["ConPro"]));
$objPHPExcel->getActiveSheet()->mergeCells("I$fila:K$fila");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:H$fila");

$fila = 11;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "CTA 2:");
$objPHPExcel->getActiveSheet()->SetCellValue(
  "C$fila",
  !empty(utf8_encode($resPro["nrocta1"]))
    ? utf8_encode($resPro["nom_ban1"]) . " " . utf8_encode($resPro["nom_mon1"]) . ": " . utf8_encode($resPro["nrocta1"]) . (!empty(utf8_encode($resPro["cci1"])) ? " /CCI: " . utf8_encode($resPro["cci1"]) : "")
    : ""
);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Email:");
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($resPro["EmaPro"]));
$objPHPExcel->getActiveSheet()->mergeCells("I$fila:K$fila");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:H$fila");


$fila = 12;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "Observaciones:");
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($resPro["Obser"]));
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Centro de Costo:");
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($resPro["CenCosto"]));
$objPHPExcel->getActiveSheet()->mergeCells("I$fila:K$fila");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:H$fila");


$fila = 14;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "Sirva(n)se atender el siguiente Pedido :");

$fila = 15;

//titulos de columnas
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", 'ITEM');
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", 'COD PROD');
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", 'DESCRIPCION');
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", 'COLOR');
$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", 'COLOR PROV.');
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", 'CANTIDAD');
$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", 'UND');
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", 'P.UNITARIO');
$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", '% DSCTO');
$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", 'TOTAL');
//$objPHPExcel->getActiveSheet()->SetCellValue("L$fila", 'ESTADO');
$objPHPExcel->getActiveSheet()->setSharedStyle($subtitulo, "B$fila:K$fila"); //establecer estilo
$objPHPExcel->getActiveSheet()->getStyle("A$fila:K$fila")->getFont()->setBold(true); //negrita

//  <td style="mso-number-format:'0.00';">12346579.00</td>
//  Exportar de PHP a Excel y dar formato a celdas

// //rellenar con contenido



$sql = mysql_query("SELECT DISTINCT 
    oComDet.Tip,
    oComDet.Ser,
    oComDet.Nro,
    oComDet.Cod_Local,
    oComDet.Cod_Entidad,
    oComDet.CodRuc,
    oComDet.Item,
    oComDet.CodPro,
    Producto.CodFab,
    oComDet.UndPro,
    ColProv,
    CanPro,
    oComDet.PrePro,
    oComDet.DscPro,
    ImpPro,
    pDscto,
    pFob,
    pCosto,
    oComDet.EstOco,
    Producto.DesPro,
    Producto.ColPro,
    Tabla_M_Detalle_1.Des_Larga AS Color,
    Tabla_M_Detalle_2.Des_Corta AS Unidad,
    IFNULL(Tabla_M_Detalle_3.Des_Larga, '') AS Color1,
    oCompra.SubTotal,
    oCompra.Igv,
    oCompra.Total,
    IFNULL(Tabla_M_Detalle_4.Des_Larga, '') AS EstAC 
  FROM
    oComDet 
    INNER JOIN Producto 
      ON oComDet.Cod_Local = Producto.Cod_Local 
      AND oComDet.Cod_Entidad = Producto.Cod_Entidad 
      AND oComDet.CodPro = Producto.CodPro 
    LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_1 
      ON oComDet.Cod_Local = Tabla_M_Detalle_1.Cod_Local 
      AND oComDet.Cod_Entidad = Tabla_M_Detalle_1.Cod_Entidad 
      AND Producto.ColPro = Tabla_M_Detalle_1.Cod_Argumento 
    LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_2 
      ON oComDet.Cod_Local = Tabla_M_Detalle_2.Cod_Local 
      AND oComDet.Cod_Entidad = Tabla_M_Detalle_2.Cod_Entidad 
      AND Producto.UndPro = Tabla_M_Detalle_2.Cod_Argumento 
    LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_3 
      ON oComDet.Cod_Local = Tabla_M_Detalle_3.Cod_Local 
      AND oComDet.Cod_Entidad = Tabla_M_Detalle_3.Cod_Entidad 
      AND ColProv = Tabla_M_Detalle_3.Cod_Argumento 
      AND (
        Tabla_M_Detalle_3.Cod_Tabla = 'TCOL' 
        OR Tabla_M_Detalle_3.Cod_Tabla IS NULL
      ) 
    LEFT JOIN oCompra 
      ON oComDet.Cod_Local = oCompra.Cod_Local 
      AND oComDet.Cod_Entidad = oCompra.Cod_Entidad 
      AND oComDet.Nro = oCompra.Nro 
    LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_4 
      ON oCompra.Cod_Local = Tabla_M_Detalle_4.Cod_Local 
      AND oComDet.Cod_Entidad = Tabla_M_Detalle_4.Cod_Entidad 
      AND oComDet.estac = Tabla_M_Detalle_4.Des_Corta 
  WHERE (
      Tabla_M_Detalle_1.Cod_Tabla = 'TCOL'
    ) 
    AND (
      Tabla_M_Detalle_2.Cod_Tabla = 'TUND'
    ) 
    AND (
      Tabla_M_Detalle_4.Cod_Tabla = 'EOC1' 
      OR Tabla_M_Detalle_4.Cod_Tabla IS NULL
    ) 
    AND oComDet.Nro=$id   
  ORDER BY oComDet.Item ASC ");




while ($res = mysql_fetch_array($sql)) {

  $objPHPExcel->getActiveSheet()->getStyle("C$fila")->getNumberFormat()->setFormatCode('00000');
  // $CodPro=$res["CodPro"]; 
  // ITE COD PROD  DESCRIPCION COLOR COLOR PROV. CANTIDAD  UND P.UNITARIO  % DSCTO TOTAL



  $fila += 1;
  $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", $res["Item"]);

  $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", $res["CodFab"]);

  $objPHPExcel->getActiveSheet()->SetCellValue("D$fila", utf8_encode($res["DesPro"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", utf8_encode($res["Color"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", utf8_encode($res["Color1"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("G$fila", utf8_encode($res["CanPro"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", utf8_encode($res["Unidad"]));
  //$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($res["PrePro"]));
  //$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($res["DscPro"]));
  //$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", utf8_encode($res["ImpPro"]));
  //$objPHPExcel->getActiveSheet()->SetCellValue("L$fila", utf8_encode($res["EstAC"]));

  //*PRECIO
  if ($res["PrePro"] == 0) {

    $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", '');
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["PrePro"] > 0 && $res["PrePro"] < 1) {

    $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", $res["PrePro"]);
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["PrePro"] >= 1 && $res["PrePro"] < 1000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", $res["PrePro"]);
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getNumberFormat()->setFormatCode("0.000");
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["PrePro"] >= 1000 && $res["PrePro"] < 1000000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", $res["PrePro"]);
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getNumberFormat()->setFormatCode("0,000.00");
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  }

  //*descuento
  if ($res["DscPro"] == 0) {

    $objPHPExcel->getActiveSheet()->SetCellValue("J$fila", '');
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["DscPro"] > 0 && $res["DscPro"] < 1) {

    $objPHPExcel->getActiveSheet()->SetCellValue("J$fila", $res["DscPro"]);
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["DscPro"] >= 1 && $res["DscPro"] < 1000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("J$fila", $res["DscPro"]);
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["DscPro"] >= 1000 && $res["DscPro"] < 1000000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("J$fila", $res["DscPro"]);
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getNumberFormat()->setFormatCode("0,000.00");
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  }

  //*total
  if ($res["ImpPro"] == 0) {

    $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", '');
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["ImpPro"] > 0 && $res["ImpPro"] < 1) {

    $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res["ImpPro"]);
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["ImpPro"] >= 1 && $res["ImpPro"] < 1000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res["ImpPro"]);
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  } else if ($res["ImpPro"] >= 1000 && $res["ImpPro"] < 1000000) {

    $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res["ImpPro"]);
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0,000.00");
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(14);
    $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
  }

  $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

  $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

  //Establecer estilo
  $objPHPExcel->getActiveSheet()->getStyle("C$fila")->getNumberFormat()->setFormatCode('00000');



  $objPHPExcel->getActiveSheet()->setSharedStyle($bordes, "B$fila:C$fila");
  $objPHPExcel->getActiveSheet()->setSharedStyle($bordes1, "D$fila");
  $objPHPExcel->getActiveSheet()->setSharedStyle($bordes, "E$fila:H$fila");
}


$sql1 = mysql_query("SELECT DISTINCT 
 oCompra.SubTotal,
 oCompra.Igv,
 oCompra.Total,
 IFNULL(Tabla_M_Detalle_5.Des_Corta, '') AS Moneda2 
FROM
 oCompra 
 LEFT JOIN Tabla_M_Detalle AS Tabla_M_Detalle_5 
   ON oCompra.Cod_Local = Tabla_M_Detalle_5.Cod_Local 
   AND oCompra.Cod_Entidad = Tabla_M_Detalle_5.Cod_Entidad 
   AND oCompra.Mo = Tabla_M_Detalle_5.Cod_Argumento 
WHERE (
   Tabla_M_Detalle_5.Cod_Tabla = 'TMON' 
   OR Tabla_M_Detalle_5.Cod_Tabla IS NULL
 ) 
 AND oCompra.Nro =$id  ");




$res1 = mysql_fetch_array($sql1);

$fila += 1;
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", "Valor Venta " . $res1["Moneda2"]);
$objPHPExcel->getActiveSheet()->mergeCells("I$fila:J$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($titulo, "I$fila:J$fila"); //establecer estilo

if ($res1["SubTotal"] == 0) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", '');
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["SubTotal"] > 0 && $res1["SubTotal"] < 1) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["SubTotal"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["SubTotal"] >= 1 && $res1["SubTotal"] < 1000) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["SubTotal"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["SubTotal"] >= 1000 && $res1["SubTotal"] < 1000000) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["SubTotal"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0,000.00");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
}


$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", "I.G.V. " . $res1["Moneda2"]);
$objPHPExcel->getActiveSheet()->mergeCells("I$fila:J$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($titulo, "I$fila:J$fila"); //establecer estilo

if ($res1["Igv"] == 0) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", '');
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["Igv"] > 0 && $res1["Igv"] < 1) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["Igv"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["Igv"] >= 1 && $res1["Igv"] < 1000) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["Igv"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["Igv"] >= 1000 && $res1["Igv"] < 1000000) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["Igv"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0,000.00");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
}

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", "Total " . $res1["Moneda2"]);
$objPHPExcel->getActiveSheet()->mergeCells("I$fila:J$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($titulo, "I$fila:J$fila"); //establecer estilo

if ($res1["Total"] == 0) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", '');
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["Total"] > 0 && $res1["Total"] < 1) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["Total"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["Total"] >= 1 && $res1["Total"] < 1000) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["Total"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0.0000");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
} else if ($res1["Total"] >= 1000 && $res1["Total"] < 1000000) {

  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $res1["Total"]);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getNumberFormat()->setFormatCode("0,000.00");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getFont()->setBold(true)->setSize(18);
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
}



// //insertar formula
// $fila+=2;
// $objPHPExcel->getActiveSheet()->SetCellValue("A$fila", 'SUMA');
// $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", '=1+2');

//recorrer las columnas
// foreach (range( 'C', 'D' , 'E' , 'F' , 'G' , 'H' , 'I' , 'J') as $columnID) {
//   //autodimensionar las columnas
//   $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
// }

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(4);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(19);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(35);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(17);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(6);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(17);






$fila += 1;
$fila += 1;
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", "Logistica");
$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", " V°B° Gerencia");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("C$fila", "Rayer Bailon P.");
$objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

/*$fila+=1;
  $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", "44883138");
   $objPHPExcel->getActiveSheet() ->getStyle("C$fila")  ->getAlignment()  ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet() ->getStyle("E$fila")  ->getAlignment()  ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  */


$objPHPExcel->getActiveSheet()->setSharedStyle($observaciones, "A$fila"); //establecer estilo



$fila += 1;
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "CONSIDERACIONES: ");
$objPHPExcel->getActiveSheet()->setSharedStyle($observaciones, "A$fila");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "1.- Indispensable enviar los comprobantes electronicos: Factura, Guia de Remisión , CRD, XML y PDF a los siguientes correos: contabilidad@jackyform.com.pe y logistica2@jackyform.com.pe ");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "2.- En la Factura y en la Guía de Remisión, hacer referencia al Número de Orden de Compra(OBLIGATORIO).");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "3.- La mercadería se entregará en el Almacén adjuntando los documentos originales: Guía de Remisión, Factura y Letras si fuese el caso.");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "4.- El almacen recepcionará la documentación y derivará la misma a Logística con su visto bueno.");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "5.- La mercaderia de no ajustarse a las caracteristicas solicitadas, será devuelto.");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "6.- Para el caso de Letras, el pago de las mismas no deberan coincidir con los dias 15 y 30 de cada Mes.");
// $fila += 1;
// $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "7.- El monto maximo en la generación de una Letra no deberá exceder los S/. 15,000.");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "8.- Todas las letras deberán ser colocadas en la entidad financiera BANCO DE CREDITO DEL PERÚ");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "9.- Horario de Recepción de mercaderia : Lunes a Viernes 8:00 a 12:00  y  14:00 a 17:00 pm. / Sabados: 8:00 a 11:00 am. ");
$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "10.- Dirección : Calle Santo Toribio 259 Urb. Santa Luisa SMP - LIMA ");




//establecer pie de impresion en cada hoja
$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&R&F página &P / &N');

//*************Guardar como excel 2003*********************************
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel); //Escribir archivo

// Establecer formado de Excel 2003
header("Content-Type: application/vnd.ms-excel");

// nombre del archivo
header('Content-Disposition: attachment; filename="Reporte_OC.xls"');
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
