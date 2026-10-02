<?php

@ini_set("display_errors", "0");
error_reporting(0);
date_default_timezone_set('America/Lima');

header('Content-Type: text/html; charset=ISO-8859-1');



/* 
* LLAMAMOS A LA LIBRERIA PHPEXCEL
*/
include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/usuarios.controlador.php";
require_once "../../modelos/usuarios.modelo.php";
require_once "../../controladores/servicio.controlador.php";
require_once "../../modelos/servicio.modelo.php";
require_once "../../modelos/recetas-modelo.modelo.php";
require_once "../../modelos/recetas-modelo.resolucion.php";
require_once "../../controladores/recetas-modelo.controlador.php";
/* 
* LLAMAMOS A LA CONEXION
*/
$conexion = Conexion::conectar();

$fechaactual = getdate();
$fecha = date("d-m-Y");
$codigo = $_GET["idServicio"];

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("Corp. Vasco");
$objPHPExcel->getProperties()->setTitle("Guía de servicio " . $codigo);
$objPHPExcel->setActiveSheetIndex(0);


$tablaBorde = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
$tablaCabecera = array(
  'font' => array('bold' => true, 'size' => 10),
  'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'D7DBDD')),
  'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER, 'wrap' => true),
  'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM))
);

/*
* DATOS DEL SERVICIO
*/
$servicio = ModeloServicios::mdlMostrarServicios("serviciosjf", "codigo", $codigo);
$detalleServ = ModeloServicios::mdlVisualizarServicioDetalle($codigo);
$taller = "";
if (!empty($detalleServ)) {
  $taller = trim($detalleServ[0]["cod_sector"] . " - " . $detalleServ[0]["nom_sector"], " -");
} elseif (is_array($servicio) && isset($servicio["nom_sector"])) {
  $taller = $servicio["nom_sector"];
}
$fechaServicio = (is_array($servicio) && !empty($servicio["fecha"])) ? date("d-m-Y", strtotime($servicio["fecha"])) : $fecha;

/*
* HELPERS DE HOJA
*/
function prepararHoja($hoja, $titulo, $ultCol, $textoTitulo, $codigo, $taller, $fechaServicio, $fecha)
{
  $hoja->setTitle($titulo);
  $hoja->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
  $hoja->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
  $hoja->getPageSetup()->setFitToPage(true);
  $hoja->getPageSetup()->setFitToWidth(1);
  $hoja->getPageSetup()->setFitToHeight(0);
  $m = 0.5 / 2.54;
  $hoja->getPageMargins()->setTop($m)->setBottom($m)->setLeft($m)->setRight($m);

  $logo = new PHPExcel_Worksheet_Drawing();
  $logo->setPath('img/jackyform_letras.png');
  $logo->setWidthAndHeight(150, 56);
  $logo->setCoordinates('A1');
  $logo->setWorksheet($hoja);
  $hoja->getRowDimension(1)->setRowHeight(24);
  $hoja->getRowDimension(2)->setRowHeight(24);

  $hoja->setCellValue("C2", $textoTitulo . " N° " . $codigo);
  $hoja->mergeCells("C2:" . $ultCol . "2");
  $hoja->getStyle("C2")->applyFromArray(array(
    'font' => array('bold' => true, 'size' => 15, 'color' => array('rgb' => 'C00000')),
    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER)
  ));

  $info = array(array("TALLER:", $taller), array("FECHA DEL SERVICIO:", $fechaServicio), array("FECHA DE IMPRESIÓN:", $fecha));
  $f = 4;
  foreach ($info as $i) {
    $hoja->setCellValue("A$f", $i[0]);
    $hoja->mergeCells("A$f:B$f");
    $hoja->setCellValue("C$f", $i[1]);
    $hoja->getStyle("A$f")->getFont()->setBold(true);
    $hoja->getStyle("C$f")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    $f++;
  }
  return 8;
}

function bloqueFirmas($hoja, $fila, $izqDesde, $izqHasta, $derDesde, $derHasta)
{
  $fila += 3;
  $bloques = array(
    array($izqDesde, $izqHasta, "ENTREGA - RESPONSABLE DEL ENVÍO"),
    array($derDesde, $derHasta, "RECIBE - RESPONSABLE DEL SERVICIO")
  );
  foreach ($bloques as $b) {
    $d = $b[0]; $h = $b[1];
    $hoja->setCellValue("$d$fila", $b[2]);
    $hoja->mergeCells("$d$fila:$h$fila");
    $hoja->getStyle("$d$fila:$h$fila")->applyFromArray(array(
      'font' => array('bold' => true, 'size' => 10),
      'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'D7DBDD')),
      'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),
      'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM))
    ));
    // espacio para la firma
    $hoja->mergeCells("$d" . ($fila + 1) . ":$h" . ($fila + 1));
    $hoja->getStyle("$d" . ($fila + 1) . ":$h" . ($fila + 1))->applyFromArray(array(
      'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM))
    ));
    $hoja->setCellValue("$d" . ($fila + 2), "Firma");
    $hoja->mergeCells("$d" . ($fila + 2) . ":$h" . ($fila + 2));
    $hoja->getStyle("$d" . ($fila + 2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $hoja->getStyle("$d" . ($fila + 2))->getFont()->setSize(9);
    $campos = array("Nombre:", "DNI:", "Fecha y hora:");
    foreach ($campos as $k => $c) {
      $r = $fila + 3 + $k;
      $hoja->setCellValue("$d$r", $c);
      $hoja->mergeCells("$d$r:$h$r");
      $hoja->getStyle("$d$r:$h$r")->applyFromArray(array(
        'font' => array('size' => 10),
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_BOTTOM),
        'borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN))
      ));
      $hoja->getRowDimension($r)->setRowHeight(22);
    }
  }
  $hoja->getRowDimension($fila + 1)->setRowHeight(60);
}


#detalle de MP desde las recetas del modelo (PUBLICADA, o BORRADOR si no hay publicada)
$stmtArt = $conexion->prepare("SELECT 
              a.articulo,
              a.modelo,
              a.cod_color,
              a.color,
              a.cod_talla,
              a.talla,
              SUM(s.cantidad) AS cantidad
              FROM servicios_detallejf s
              INNER JOIN articulojf a ON a.articulo = s.articulo
              WHERE s.codigo = :codigo
              GROUP BY a.articulo, a.modelo, a.cod_color, a.color, a.cod_talla, a.talla
              HAVING SUM(s.cantidad) > 0");
$stmtArt->bindValue(":codigo", $codigo, PDO::PARAM_STR);
$stmtArt->execute();

$porModelo = array();
foreach ($stmtArt->fetchAll(PDO::FETCH_ASSOC) as $art) {
    $porModelo[trim($art["modelo"])][] = $art;
}

$lineasMp = array('BLO', 'ELA', 'ETI', 'SES');
$mapaMp = array();
$modelosSinReceta = array();

foreach ($porModelo as $modelo => $arts) {
    $receta = ModeloRecetasModelo::mdlRecetaPreferidaModelo($modelo);
    $estructura = $receta ? ControladorRecetasModelo::ctrEstructuraReceta((int) $receta["id"]) : null;
    if (!$estructura) {
        $modelosSinReceta[] = $modelo;
        continue;
    }

    $conCantidad = array();
    foreach ($arts as $art) {
        $conCantidad[] = array("articulo" => $art, "cantidad" => (float) $art["cantidad"]);
    }

    $resultado = ServicioRecetasModeloResolucion::resolverMatriz(
        $estructura["lineas"],
        $estructura["variantes_por_detalle"],
        $conCantidad,
        $estructura["mp_info"]
    );

    foreach ($resultado["consolidados"] as $c) {
        $mp = trim($c["mp_codigo"]);
        $sub = isset($estructura["mp_info"][$mp]["codigo_sublinea"]) ? $estructura["mp_info"][$mp]["codigo_sublinea"] : "";
        if (!in_array(substr($sub, 0, 3), $lineasMp, true)) {
            continue;
        }
        if (!isset($mapaMp[$mp])) {
            $mapaMp[$mp] = 0.0;
        }
        $mapaMp[$mp] += (float) $c["consumo_total"];
    }
}

$filasMp = array();
if (!empty($mapaMp)) {
    $codigosMp = array_keys($mapaMp);
    $marcas = implode(",", array_fill(0, count($codigosMp), "?"));
    $stmtMp = $conexion->prepare("SELECT 
              (SELECT t.des_larga FROM tabla_m_detalle t 
                WHERE t.cod_tabla = 'TLIN' AND t.des_corta = LEFT(p.codfab, 3)) AS linea,
              LEFT(p.codfab, 6) AS codlinea,
              p.codpro AS mat_pri,
              p.despro AS descripcion,
              (SELECT t.des_larga FROM tabla_m_detalle t 
                WHERE t.cod_tabla = 'TCOL' AND t.cod_argumento = p.colpro) AS color,
              (SELECT t.des_larga FROM tabla_m_detalle t 
                WHERE t.cod_tabla = 'TUND' AND t.cod_argumento = p.undpro) AS unidad
              FROM producto p
              WHERE p.codpro IN ($marcas)");
    $stmtMp->execute($codigosMp);
    foreach ($stmtMp->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $r["total"] = $mapaMp[trim($r["mat_pri"])];
        $filasMp[] = $r;
    }
    usort($filasMp, function ($x, $y) {
        return strcmp($x["linea"] . $x["codlinea"], $y["linea"] . $y["codlinea"]);
    });
}


/*
* HOJA 1: ARTÍCULOS
*/
$hoja = $objPHPExcel->getActiveSheet();
$fila = prepararHoja($hoja, "ARTICULOS", "M", "GUÍA DE SERVICIO - ARTÍCULOS", $codigo, $taller, $fechaServicio, $fecha);

$cab = array("A" => "N°", "B" => "MODELO", "C" => "NOMBRE", "D" => "COLOR", "E" => "T1", "F" => "T2", "G" => "T3", "H" => "T4", "I" => "T5", "J" => "T6", "K" => "T7", "L" => "T8", "M" => "TOTAL");
$tallasA = array("E" => "28", "F" => "30", "G" => "32", "H" => "34", "I" => "36", "J" => "38", "K" => "40", "L" => "42");
$tallasB = array("E" => "3", "F" => "4", "G" => "6", "H" => "8", "I" => "10", "J" => "12", "K" => "14", "L" => "16");
foreach ($cab as $col => $txt) {
  if (isset($tallasA[$col])) {
    $hoja->setCellValue($col . $fila, $tallasA[$col]);
    $hoja->setCellValue($col . ($fila + 1), $tallasB[$col]);
  } else {
    $hoja->setCellValue($col . $fila, $txt);
    $hoja->mergeCells($col . $fila . ":" . $col . ($fila + 1));
  }
}
$hoja->getStyle("A$fila:M" . ($fila + 1))->applyFromArray($tablaCabecera);
$fila += 2;
$inicio = $fila;

$n = 0;
$totales = array_fill(1, 8, 0);
$totalGeneral = 0;
foreach ($detalleServ as $value) {
  $n++;
  $hoja->setCellValue("A$fila", $n);
  $hoja->setCellValue("B$fila", $value["modelo"]);
  $hoja->setCellValue("C$fila", $value["nombre"]);
  $hoja->setCellValue("D$fila", $value["color"]);
  $col = "E";
  for ($t = 1; $t <= 8; $t++) {
    $v = (float) $value["t$t"];
    if ($v > 0) {
      $hoja->setCellValue("$col$fila", $v);
      $totales[$t] += $v;
    }
    $col++;
  }
  $hoja->setCellValue("M$fila", (float) $value["total"]);
  $totalGeneral += (float) $value["total"];
  $fila++;
}
if ($n > 0) {
  $hoja->getStyle("A$inicio:M" . ($fila - 1))->applyFromArray($tablaBorde);
}
$hoja->getStyle("A$inicio:B" . ($fila - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$hoja->getStyle("E$inicio:M" . ($fila - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// fila de totales
$hoja->setCellValue("A$fila", "TOTAL");
$hoja->mergeCells("A$fila:D$fila");
$col = "E";
for ($t = 1; $t <= 8; $t++) {
  if ($totales[$t] > 0) {
    $hoja->setCellValue("$col$fila", $totales[$t]);
  }
  $col++;
}
$hoja->setCellValue("M$fila", $totalGeneral);
$hoja->getStyle("A$fila:M$fila")->applyFromArray($tablaCabecera);
$hoja->getStyle("A$fila")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

$anchos = array("A" => 6, "B" => 11, "C" => 45, "D" => 18, "M" => 10);
foreach (range("E", "L") as $c) { $anchos[$c] = 7; }
foreach ($anchos as $c => $w) { $hoja->getColumnDimension($c)->setWidth($w); }

bloqueFirmas($hoja, $fila, "A", "D", "F", "M");

/*
* HOJA 2: MATERIA PRIMA
*/
$hoja2 = $objPHPExcel->createSheet(1);
$objPHPExcel->setActiveSheetIndex(1);
$fila = prepararHoja($hoja2, "MATERIA PRIMA", "I", "GUÍA DE SERVICIO - MATERIA PRIMA", $codigo, $taller, $fechaServicio, $fecha);

$cab2 = array("A" => "LINEA", "B" => "COD. FAB", "C" => "COD. PRO", "D" => "DESCRIPCIÓN", "E" => "COLOR", "F" => "CONSUMO", "G" => "UNIDAD", "H" => "MANDAR", "I" => "MARCAR");
foreach ($cab2 as $col => $txt) {
  $hoja2->setCellValue($col . $fila, $txt);
}
$hoja2->getStyle("A$fila:I$fila")->applyFromArray($tablaCabecera);
$fila++;
$inicio = $fila;
foreach ($filasMp as $r) {
  $hoja2->setCellValue("A$fila", $r["linea"]);
  $hoja2->setCellValue("B$fila", $r["codlinea"]);
  $hoja2->setCellValueExplicit("C$fila", $r["mat_pri"], PHPExcel_Cell_DataType::TYPE_STRING);
  $hoja2->setCellValue("D$fila", $r["descripcion"]);
  $hoja2->setCellValue("E$fila", $r["color"]);
  $hoja2->setCellValue("F$fila", round($r["total"], 4));
  $hoja2->setCellValue("G$fila", $r["unidad"]);
  $fila++;
}
if (!empty($filasMp)) {
  $hoja2->getStyle("A$inicio:I" . ($fila - 1))->applyFromArray($tablaBorde);
  $hoja2->getStyle("F$inicio:F" . ($fila - 1))->getNumberFormat()->setFormatCode('#,##0.0000');
  $hoja2->getStyle("F$inicio:F" . ($fila - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
}
if (!empty($modelosSinReceta)) {
  $hoja2->setCellValue("A$fila", "SIN RECETA (no incluidos): " . implode(", ", $modelosSinReceta));
  $hoja2->getStyle("A$fila")->getFont()->setBold(true)->getColor()->setRGB("C00000");
  $fila++;
}

$anchos2 = array("A" => 16, "B" => 10, "C" => 10, "D" => 45, "E" => 18, "F" => 12, "G" => 10, "H" => 10, "I" => 10);
foreach ($anchos2 as $c => $w) { $hoja2->getColumnDimension($c)->setWidth($w); }

bloqueFirmas($hoja2, $fila, "A", "D", "E", "I");

$objPHPExcel->setActiveSheetIndex(0);

/*
* CREAR EL ARCHIVO
*/
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="GUIA SERVICIO ' . $codigo . ' - ' . $fecha . '.xls"');
header("Cache-Control: max-age=0");

$objWriter->save('php://output');
