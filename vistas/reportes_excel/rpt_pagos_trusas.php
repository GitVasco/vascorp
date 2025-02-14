<?php

header('Content-Type: text/html; charset=ISO-8859-1');

/* 
* RECIBIMOS VARIABLE DESDE LA VISTA
*/

$inicio = $_GET["inicio"];
$fin = $_GET["fin"];
$id = $_GET["id"];

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

mysql_query("SET lc_time_names = 'es_ES'");

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
$sqlHoja = mysql_query("SELECT 
                        q.id,
                        CONCAT(
                          'Pagos ',
                          YEAR(q.inicio),
                          '-',
                          MONTH(q.inicio),
                          '-',
                          DAY(q.inicio),
                          ' Al ',
                          YEAR(q.fin),
                          '-',
                          MONTH(q.fin),
                          '-',
                          DAY(q.fin)
                        ) AS fecha 
                        FROM
                        quincenasjf q 
                        WHERE q.id = $id") or die(mysql_error());

$respHoja = mysql_fetch_array($sqlHoja);

$objPHPExcel->createSheet(0);
$objPHPExcel->setActiveSheetIndex(0);

# Titulo de la hoja
$objPHPExcel->getActiveSheet()->setTitle($respHoja["fecha"]);

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
#query para sacar los datos de la cabecera
$sqlCabecera = mysql_query("SELECT 
                            MIN(DATE(t.fecha)) AS i,
                            MAX(DATE(t.fecha)) AS f,
                            CONCAT(
                              'PRODUCCIÓN DE DESTAJEROS DEL ',
                              DATE_FORMAT(MIN(DATE(t.fecha)), '%d'),
                              ' DE ',
                              UPPER(DATE_FORMAT(MIN(DATE(t.fecha)), '%M')),
                              ' AL ',
                              DATE_FORMAT(MAX(DATE(t.fecha)), '%d'),
                              ' DE ',
                              UPPER(DATE_FORMAT(MAX(DATE(t.fecha)), '%M')),
                              ' DEL ',
                              DATE_FORMAT(MIN(DATE(t.fecha)), '%Y')
                            ) AS titulo 
                            FROM
                            totalesjf t 
                            WHERE DATE(t.fecha) BETWEEN '$inicio'
                            AND '$fin'") or die(mysql_error());

$respCabecera = mysql_fetch_array($sqlCabecera);

$fila = 2;
$objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(45.67);

$objPHPExcel->getActiveSheet()->SetCellValue("E$fila", $respCabecera["titulo"]);
$objPHPExcel->getActiveSheet()->mergeCells("E$fila:K$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "E$fila:K$fila");
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("E$fila:K$fila")->getAlignment()->setWrapText(true);

$fila = 4;

$objPHPExcel->getActiveSheet()->SetCellValue("B$fila", "ÁREA:");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "B$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "PRODUCCIÓN DE TRUSAS");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "D$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", "FECHA");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "J$fila");

$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $fecha);
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_2, "K$fila");

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

$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", "Produc");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Sueldo Básico Quincena");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "G$fila");
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", "Categoría");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "H$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", "Diferencia Prod/Sueldo");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "I$fila");
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", "Incentivo");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "J$fila");
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", "Total");
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "K$fila");
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setWrapText(true);

#query para sacar los datos del detalle
// $sqlDetalle = mysql_query("SELECT 
// a.id_trabajador,
// @i := @i + 1 AS contador,
// CONCAT(
//   t.nom_tra,
//   ' ',
//   t.ape_pat_tra,
//   ' ',
//   t.ape_mat_tra
// ) AS nombre,
// COUNT(a.fecha) - 1 AS dias,
// ROUND(et.produccion, 2) AS produccion,
// ROUND(et.sueldo_quincena, 2) AS sueldo_quincena,
// et.categoria,
// et.diferencia,
// et.incentivo,
// CASE
//   WHEN et.produccion > et.sueldo_quincena 
//   THEN ROUND(et.produccion + et.incentivo, 2) 
//   ELSE ROUND(et.sueldo_quincena, 2) 
// END AS total 
// FROM
// asistenciasjf a 
// LEFT JOIN 
//   (SELECT 
//     et.trabajador,
//     SUM(total_precio) AS produccion,
//     (t.sueldo_total / 2) AS sueldo_quincena,
//     CASE
//       WHEN SUM(total_precio) >= 600 
//       THEN 'A' 
//       WHEN SUM(total_precio) >= 550 
//       AND SUM(total_precio) < 600 
//       THEN 'B' 
//       WHEN SUM(total_precio) >= 501 
//       AND SUM(total_precio) < 550 
//       THEN 'C' 
//       WHEN SUM(total_precio) >= 475 
//       AND SUM(total_precio) <= 500 
//       THEN 'D' 
//       WHEN SUM(total_precio) >= 450 
//       AND SUM(total_precio) < 475 
//       THEN 'E' 
//       WHEN SUM(total_precio) >= 0 
//       AND SUM(total_precio) < 450 
//       THEN 'F' 
//     END AS categoria,
//     CASE
//       WHEN SUM(total_precio) > (t.sueldo_total / 2) 
//       THEN 0 
//       WHEN SUM(total_precio) < (t.sueldo_total / 2) 
//       THEN ROUND(
//         SUM(total_precio) - (t.sueldo_total / 2),
//         2
//       ) 
//     END AS diferencia,
//     CASE
//       WHEN SUM(total_precio) > (t.sueldo_total / 2) 
//       AND (SUM(total_precio) >= 600) 
//       THEN 110 
//       WHEN SUM(total_precio) > (t.sueldo_total / 2) 
//       AND (
//         SUM(total_precio) >= 550 
//         AND SUM(total_precio) < 600
//       ) 
//       THEN 110 
//       WHEN SUM(total_precio) > (t.sueldo_total / 2) 
//       AND (
//         SUM(total_precio) >= 500 
//         AND SUM(total_precio) < 550
//       ) 
//       THEN 100 
//       WHEN SUM(total_precio) > (t.sueldo_total / 2) 
//       AND (
//         SUM(total_precio) >= 475 
//         AND SUM(total_precio) < 500
//       ) 
//       THEN 85 
//       WHEN SUM(total_precio) > (t.sueldo_total / 2) 
//       AND (
//         SUM(total_precio) >= 450 
//         AND SUM(total_precio) < 475
//       ) 
//       THEN 70 
//       WHEN SUM(total_precio) > (t.sueldo_total / 2) 
//       AND (
//         SUM(total_precio) >= 0 
//         AND SUM(total_precio) < 450
//       ) 
//       THEN 55 
//       ELSE 0 
//     END AS incentivo 
//   FROM
//     entallerjf et 
//     LEFT JOIN trabajadorjf t 
//       ON et.trabajador = t.cod_tra 
//     LEFT JOIN articulojf a 
//       ON et.articulo = a.articulo 
//     LEFT JOIN modelojf m 
//       ON a.modelo = m.modelo 
//   WHERE (
//       DATE(et.fecha_terminado) BETWEEN '$inicio' 
//       AND '$fin'
//     ) 
//     AND m.tipo NOT IN ('BRASIER') 
//   GROUP BY et.trabajador) AS et 
//   ON a.id_trabajador = et.trabajador 
// LEFT JOIN trabajadorjf t 
//   ON a.id_trabajador = t.cod_tra 
// LEFT JOIN tipo_trabajadorjf tt 
//   ON t.cod_tip_tra = tt.cod_tip_tra 
// CROSS JOIN 
//   (SELECT 
//     @i := 0) r 
// WHERE (
//   DATE(a.fecha) BETWEEN '$inicio' 
//   AND '$fin'
// ) 
// AND tt.cod_tip_tra = '1' 
// AND et.produccion > 0 
// AND a.estado = 'asistio' 
// GROUP BY a.id_trabajador 
// ORDER BY 
// CASE
//   WHEN et.produccion > et.sueldo_quincena 
//   THEN ROUND(et.produccion + et.incentivo, 2) 
//   ELSE ROUND(et.sueldo_quincena, 2) 
// END DESC") or die(mysql_error());


$sqlDetalle = mysql_query("SELECT 
	et.trabajador as id_trabajador,
	CONCAT(
  t.nom_tra,
  ' ',
  t.ape_pat_tra,
  ' ',
  t.ape_mat_tra
) as nombre,
	COUNT(distinct DATE(et.fecha_terminado)) as dias,
	ROUND(SUM(et.total_precio), 2) as produccion,
	ROUND(t.sueldo_total / 2, 2) as sueldo_quincena,
	case
		when (
    SUM(et.total_precio) - (t.sueldo_total / 2) > 0
  )
		and (t.sueldo_total / 2) >= 600 
  then 'A'
		when (
    SUM(et.total_precio) - (t.sueldo_total / 2) > 0
  )
		and (
    (t.sueldo_total / 2) < 600
		and (t.sueldo_total / 2) >= 565
  ) 
  then 'B'
		when (
    SUM(et.total_precio) - (t.sueldo_total / 2) > 0
  )
		and (
    (t.sueldo_total / 2) < 565
		and (t.sueldo_total / 2) >= 500
  ) 
  then 'C'
		else 'D'
	end categoria,
	case
		when (
    SUM(et.total_precio) - (t.sueldo_total / 2)
  ) > 0 
  then 0
		else ROUND(
    SUM(et.total_precio) - (t.sueldo_total / 2),
    2
  )
	end as diferencia,
	case
		when (
    SUM(et.total_precio) - (t.sueldo_total / 2) > 0
  )
		and (t.sueldo_total / 2) >= 600 
  then 140
		when (
    SUM(et.total_precio) - (t.sueldo_total / 2) > 0
  )
		and (
    (t.sueldo_total / 2) < 600
		and (t.sueldo_total / 2) >= 565
  ) 
  then 125
		when (
    SUM(et.total_precio) - (t.sueldo_total / 2) > 0
  )
		and (
    (t.sueldo_total / 2) < 565
		and (t.sueldo_total / 2) >= 500
  ) 
  then 0
		else 0
	end as incentivo
FROM
entallerjf et 
LEFT JOIN trabajadorjf t 
  ON et.trabajador = t.cod_tra 
WHERE (
  DATE(et.fecha_terminado) BETWEEN '$inicio' 
  AND '$fin'
) 
AND t.cod_tip_tra = 1 
GROUP BY et.trabajador 
ORDER BY produccion DESC ") or die(mysql_error());

$produccion = 0;
$sueldo_quincena = 0;
$diferencia = 0;
$incentivo = 0;
$total = 0;
$totalGeneral = 0;
$conta = 1;

while ($respDetalle = mysql_fetch_array($sqlDetalle)) {

  $total = $respDetalle["produccion"] + $respDetalle["incentivo"];

  $fila += 1;
  $objPHPExcel->getActiveSheet()->SetCellValue("B$fila", utf8_encode($respDetalle["id_trabajador"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("C$fila", utf8_encode($conta));
  $objPHPExcel->getActiveSheet()->SetCellValue("D$fila", utf8_encode($respDetalle["nombre"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("E$fila", utf8_encode($respDetalle["dias"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("F$fila", utf8_encode($respDetalle["produccion"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("G$fila", utf8_encode($respDetalle["sueldo_quincena"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("H$fila", utf8_encode($respDetalle["categoria"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("I$fila", utf8_encode($respDetalle["diferencia"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("J$fila", utf8_encode($respDetalle["incentivo"]));
  $objPHPExcel->getActiveSheet()->SetCellValue("K$fila", utf8_encode($total));

  $conta++;

  if (utf8_encode($respDetalle["produccion"]) < utf8_encode($respDetalle["sueldo_quincena"])) {

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_1, "F$fila");
    $objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  } else {

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "F$fila");
    $objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  }

  if (utf8_encode($respDetalle["categoria"]) == "A") {

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_5, "H$fila");
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  } elseif (utf8_encode($respDetalle["categoria"]) == "B") {

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_4, "H$fila");
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  } elseif (utf8_encode($respDetalle["categoria"]) == "C") {

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_2, "H$fila");
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  } else {

    $objPHPExcel->getActiveSheet()->setSharedStyle($borde_7, "H$fila");
    $objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  }

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "B$fila");
  $objPHPExcel->getActiveSheet()->getStyle("B$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "C$fila");
  $objPHPExcel->getActiveSheet()->getStyle("C$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "D$fila");

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "E$fila");
  $objPHPExcel->getActiveSheet()->getStyle("E$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "G$fila");
  $objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "I$fila");
  $objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_6, "J$fila");
  $objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "K$fila");
  $objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

  $produccion = $produccion + $respDetalle["produccion"];
  $sueldo_quincena = $sueldo_quincena + $respDetalle["sueldo_quincena"];
  $diferencia = $diferencia + $respDetalle["diferencia"];
  $incentivo = $incentivo + $respDetalle["incentivo"];
  $totalGeneral +=  $total;
}

/*
todo: FIN DE DETALLE
*/
$fila += 2;
$objPHPExcel->getActiveSheet()->SetCellValue("G$fila", "Dif. Sueldo");
$objPHPExcel->getActiveSheet()->mergeCells("G$fila:H$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "G$fila:H$fila");
$objPHPExcel->getActiveSheet()->getStyle("G$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", $diferencia);
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "I$fila");
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setWrapText(true);

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("D$fila", "Total Producción");
$objPHPExcel->getActiveSheet()->mergeCells("D$fila:E$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "D$fila:E$fila");
$objPHPExcel->getActiveSheet()->getStyle("D$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("F$fila", $produccion);
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "F$fila");
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("F$fila")->getAlignment()->setWrapText(true);

$objPHPExcel->getActiveSheet()->SetCellValue("H$fila", "Total Producción");
$objPHPExcel->getActiveSheet()->mergeCells("H$fila:I$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "H$fila:I$fila");
$objPHPExcel->getActiveSheet()->getStyle("H$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("J$fila", $incentivo);
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "J$fila");
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("J$fila")->getAlignment()->setWrapText(true);

$fila += 1;
$objPHPExcel->getActiveSheet()->SetCellValue("I$fila", "TOTAL A PAGAR");
$objPHPExcel->getActiveSheet()->mergeCells("I$fila:J$fila");
$objPHPExcel->getActiveSheet()->setSharedStyle($texto_5, "I$fila:J$fila");
$objPHPExcel->getActiveSheet()->getStyle("I$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$objPHPExcel->getActiveSheet()->SetCellValue("K$fila", $totalGeneral);
$objPHPExcel->getActiveSheet()->setSharedStyle($borde_3, "K$fila");
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle("K$fila")->getAlignment()->setWrapText(true);

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
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(11.44);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(12.87);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(4.29);

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
$sqlArchivo = mysql_query("SELECT 
                                q.id,
                                CONCAT(
                                  'Pagos ',
                                  YEAR(q.inicio),
                                  '-',
                                  MONTH(q.inicio),
                                  '-',
                                  DAY(q.inicio),
                                  ' Al ',
                                  YEAR(q.fin),
                                  '-',
                                  MONTH(q.fin),
                                  '-',
                                  DAY(q.fin)
                                ) AS fecha 
                                FROM
                                quincenasjf q 
                                WHERE q.id = $id") or die(mysql_error());

$respArchivo = mysql_fetch_array($sqlArchivo);


# Nombre del archivo
header('Content-Disposition: attachment; filename="' . $respArchivo["fecha"] . '.xls"');


//forzar a descarga por el navegador
$objWriter->save('php://output');
