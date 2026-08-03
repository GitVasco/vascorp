<?php

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
    session_start();
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../modelos/cuentas.modelo.php";
require_once "../../modelos/linea-credito.modelo.php";

if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    echo "Acceso no autorizado.";
    exit;
}

if (!isset($_SESSION["cuenta"]) || (int) $_SESSION["cuenta"] !== 1) {
    echo "Acceso no autorizado.";
    exit;
}

$idSolicitud = isset($_GET["solicitud_por"]) ? (int) $_GET["solicitud_por"] : 0;
$solicitante = ModeloLineaCredito::mdlUsuarioActivoPorId($idSolicitud);

if (!$solicitante) {
    echo "Debe indicar quién solicita el reporte (usuario activo).";
    exit;
}

@ini_set("max_execution_time", "120");
@ini_set("memory_limit", "256M");

date_default_timezone_set("America/Lima");
$fechaCorte = date("d-m-y");
$fechaArchivo = date("d-m-Y");
$fechaHora = date("d/m/Y H:i");

$idGenerador = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$generador = $idGenerador > 0 ? ModeloLineaCredito::mdlUsuarioActivoPorId($idGenerador) : null;
$nombreSolicitante = trim((string) $solicitante["nombre"]);
$nombreGenerador = ($generador && !empty($generador["nombre"])) ? trim((string) $generador["nombre"]) : "Sistema";
$nombrePc = (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] !== "")
    ? gethostbyaddr($_SERVER["REMOTE_ADDR"])
    : "";

// Cobranza: mes anterior por defecto (editable por GET anio/mes)
$anioCobranza = isset($_GET["anio"]) ? (int) $_GET["anio"] : (int) date("Y", strtotime("first day of last month"));
$mesCobranza = isset($_GET["mes"]) ? (int) $_GET["mes"] : (int) date("n", strtotime("first day of last month"));
if ($mesCobranza < 1 || $mesCobranza > 12) {
    $mesCobranza = (int) date("n", strtotime("first day of last month"));
}
if ($anioCobranza < 2000 || $anioCobranza > 2100) {
    $anioCobranza = (int) date("Y", strtotime("first day of last month"));
}

$nombresMesCobranza = array(
    1 => "ENERO", 2 => "FEBRERO", 3 => "MARZO", 4 => "ABRIL",
    5 => "MAYO", 6 => "JUNIO", 7 => "JULIO", 8 => "AGOSTO",
    9 => "SEPTIEMBRE", 10 => "OCTUBRE", 11 => "NOVIEMBRE", 12 => "DICIEMBRE",
);
$nombreMesCobranza = isset($nombresMesCobranza[$mesCobranza])
    ? $nombresMesCobranza[$mesCobranza]
    : (string) $mesCobranza;

$resumen = ModeloCuentas::mdlResumenCtasCtesGerencia();
$porVendedor = ModeloCuentas::mdlCxcPorVendedorGerencia();
$estadoLetras = ModeloCuentas::mdlEstadoLetrasPorCobrarGerencia();
$letrasPorMes = ModeloCuentas::mdlLetrasPorVencimientoMesGerencia();
$facturasPorMes = ModeloCuentas::mdlFacturasPorVencimientoMesGerencia();
$vencidosPorVendedor = ModeloCuentas::mdlDocumentosVencidosPorVendedorGerencia();
$incobrablesPorVendedor = ModeloCuentas::mdlDocumentosIncobrablesPorVendedorGerencia();
$cobranza = ModeloCuentas::mdlResumenCobranzaPorVendedorGerencia($anioCobranza, $mesCobranza);
$cobranzaTipoDoc = ModeloCuentas::mdlResumenCobranzaPorTipoDocGerencia($anioCobranza, $mesCobranza);

$facturas = (float) $resumen["facturas"];
$boletas = (float) $resumen["boletas"];
$letras = (float) $resumen["letras"];
$guias = (float) $resumen["guias_varios"];
$total = (float) $resumen["total"];

$letrasBanco = (float) $estadoLetras["banco_credito"];
$letrasCartera = (float) $estadoLetras["cartera"];
$letrasProtestadas = (float) $estadoLetras["protestadas"];
$letrasPorAceptar = (float) $estadoLetras["por_aceptar"];
$letrasEstadoTotal = (float) $estadoLetras["total"];

$pct = function ($monto) use ($total) {
    if ($total <= 0) {
        return 0;
    }
    return (int) round(($monto / $total) * 100);
};

$pctFacturas = $pct($facturas);
$pctBoletas = $pct($boletas);
$pctLetras = $pct($letras);
$pctGuias = $pct($guias);

// Ajuste para que la suma de % enteros sea 100 cuando hay saldo
$sumaPct = $pctFacturas + $pctBoletas + $pctLetras + $pctGuias;
if ($total > 0 && $sumaPct !== 100) {
    $diffs = array(
        "facturas" => abs(($facturas / $total) * 100 - $pctFacturas),
        "boletas" => abs(($boletas / $total) * 100 - $pctBoletas),
        "letras" => abs(($letras / $total) * 100 - $pctLetras),
        "guias" => abs(($guias / $total) * 100 - $pctGuias),
    );
    arsort($diffs);
    $clave = key($diffs);
    $ajuste = 100 - $sumaPct;
    if ($clave === "facturas") {
        $pctFacturas += $ajuste;
    } elseif ($clave === "boletas") {
        $pctBoletas += $ajuste;
    } elseif ($clave === "letras") {
        $pctLetras += $ajuste;
    } else {
        $pctGuias += $ajuste;
    }
}

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator("Corp. Vasco")
    ->setTitle("Resumen Ctas Ctes Gerencia")
    ->setSubject("Estado de cuenta gerencia");

$estiloTitulo = array(
    "font" => array(
        "bold" => true,
        "underline" => PHPExcel_Style_Font::UNDERLINE_SINGLE,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloSeccion = array(
    "font" => array(
        "bold" => true,
        "underline" => PHPExcel_Style_Font::UNDERLINE_SINGLE,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
    ),
);

$estiloEtiqueta = array(
    "font" => array(
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
    ),
);

$estiloNumero = array(
    "font" => array(
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
);

$estiloPorcentaje = array(
    "font" => array(
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
);

$estiloTotalNumero = array(
    "font" => array(
        "bold" => true,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
    "borders" => array(
        "outline" => array(
            "style" => PHPExcel_Style_Border::BORDER_MEDIUM,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$estiloTotalPct = array(
    "font" => array(
        "bold" => true,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "borders" => array(
        "outline" => array(
            "style" => PHPExcel_Style_Border::BORDER_MEDIUM,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$estiloMoneda = array(
    "font" => array(
        "bold" => true,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
);

$estiloHeaderTabla = array(
    "font" => array(
        "bold" => true,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
);

$formatoCeldaMonto = function ($sheet, $celda, $monto, $estiloNumero) {
    if ($monto === null || abs((float) $monto) < 0.005) {
        $sheet->setCellValue($celda, "-");
        $sheet->getStyle($celda)->applyFromArray(array(
            "font" => array("size" => 11, "name" => "Calibri"),
            "alignment" => array("horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT),
        ));
        return;
    }
    $sheet->setCellValue($celda, (float) $monto);
    $sheet->getStyle($celda)->applyFromArray($estiloNumero);
};

// =====================
// HOJA 1: RESUMEN
// =====================
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle("Resumen");

$sheet->mergeCells("B1:H1");
$sheet->setCellValue("B1", "CORPORACION VASCO LINEA JACKY FORM");
$sheet->getStyle("B1")->applyFromArray($estiloTitulo);

$sheet->mergeCells("B2:H2");
$sheet->setCellValue("B2", "Resumen de Ctas Ctes - En Soles al " . $fechaCorte);
$sheet->getStyle("B2")->applyFromArray($estiloTitulo);

$sheet->setCellValue("B4", "Total Documentos x Cobrar");
$sheet->getStyle("B4")->applyFromArray($estiloSeccion);

$filas = array(
    6 => array("Facturas", $facturas, $pctFacturas),
    7 => array("Boletas", $boletas, $pctBoletas),
    8 => array("Letras", $letras, $pctLetras),
    9 => array("Guias Varios", $guias, $pctGuias),
);

foreach ($filas as $fila => $datos) {
    $sheet->setCellValue("B" . $fila, $datos[0]);
    $sheet->getStyle("B" . $fila)->applyFromArray($estiloEtiqueta);

    $sheet->setCellValue("G" . $fila, $datos[1]);
    $sheet->getStyle("G" . $fila)->applyFromArray($estiloNumero);

    $sheet->setCellValue("H" . $fila, $datos[2] . "%");
    $sheet->getStyle("H" . $fila)->applyFromArray($estiloPorcentaje);
}

$sheet->setCellValue("E11", "S/");
$sheet->getStyle("E11")->applyFromArray($estiloMoneda);

$sheet->setCellValue("G11", $total);
$sheet->getStyle("G11")->applyFromArray($estiloTotalNumero);

$sheet->setCellValue("H11", "100%");
$sheet->getStyle("H11")->applyFromArray($estiloTotalPct);

// --- Bloque: Cuentas por cobrar por vendedor ---
$filaBloque = 14;
$sheet->setCellValue("B" . $filaBloque, "Cuentas por cobrar por vendedor");
$sheet->getStyle("B" . $filaBloque)->applyFromArray($estiloSeccion);

$filaHeader = $filaBloque + 1;
$sheet->setCellValue("E" . $filaHeader, "FACTURAS");
$sheet->setCellValue("F" . $filaHeader, "GUIAS");
$sheet->setCellValue("G" . $filaHeader, "LETRAS");
$sheet->setCellValue("H" . $filaHeader, "TOTAL");
$sheet->getStyle("E" . $filaHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("F" . $filaHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("G" . $filaHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("H" . $filaHeader)->applyFromArray($estiloHeaderTabla);

$filaData = $filaHeader + 1;
$sumFacturasVend = 0.0;
$sumGuiasVend = 0.0;
$sumLetrasVend = 0.0;
$sumTotalVend = 0.0;

foreach ($porVendedor as $vend) {
    $sheet->setCellValue("B" . $filaData, $vend["nombre"]);
    $sheet->getStyle("B" . $filaData)->applyFromArray($estiloEtiqueta);

    $formatoCeldaMonto($sheet, "E" . $filaData, $vend["facturas"], $estiloNumero);
    $formatoCeldaMonto($sheet, "F" . $filaData, $vend["guias"], $estiloNumero);
    $formatoCeldaMonto($sheet, "G" . $filaData, $vend["letras"], $estiloNumero);
    $formatoCeldaMonto($sheet, "H" . $filaData, $vend["total"], $estiloNumero);

    $sumFacturasVend += (float) $vend["facturas"];
    $sumGuiasVend += (float) $vend["guias"];
    $sumLetrasVend += (float) $vend["letras"];
    $sumTotalVend += (float) $vend["total"];

    $filaData++;
}

$estiloTotalVend = array(
    "font" => array(
        "bold" => true,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
    "borders" => array(
        "top" => array(
            "style" => PHPExcel_Style_Border::BORDER_THIN,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$sheet->setCellValue("B" . $filaData, "TOTAL");
$sheet->getStyle("B" . $filaData)->applyFromArray(array(
    "font" => array("bold" => true, "size" => 11, "name" => "Calibri"),
    "alignment" => array("horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT),
));

$sheet->setCellValue("E" . $filaData, $sumFacturasVend);
$sheet->setCellValue("F" . $filaData, $sumGuiasVend);
$sheet->setCellValue("G" . $filaData, $sumLetrasVend);
$sheet->setCellValue("H" . $filaData, $sumTotalVend);
$sheet->getStyle("E" . $filaData)->applyFromArray($estiloTotalVend);
$sheet->getStyle("F" . $filaData)->applyFromArray($estiloTotalVend);
$sheet->getStyle("G" . $filaData)->applyFromArray($estiloTotalVend);
$sheet->getStyle("H" . $filaData)->applyFromArray($estiloTotalVend);

// --- Bloque: Estado de letras por cobrar (debajo de CxC por vendedor) ---
$estiloLetraEtiqueta = array(
    "font" => array(
        "bold" => true,
        "underline" => PHPExcel_Style_Font::UNDERLINE_SINGLE,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
    ),
);

$estiloLetraMonto = array(
    "font" => array(
        "size" => 11,
        "name" => "Calibri",
        "underline" => PHPExcel_Style_Font::UNDERLINE_SINGLE,
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
);

$estiloLetraMontoSimple = array(
    "font" => array(
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
);

$filaLetrasTitulo = $filaData + 3;
$sheet->setCellValue("B" . $filaLetrasTitulo, "ESTADO DE LETRAS POR COBRAR");
$sheet->getStyle("B" . $filaLetrasTitulo)->applyFromArray($estiloSeccion);

$filasLetras = array(
    array("BANCO CREDITO", $letrasBanco, true),
    array("CARTERA", $letrasCartera, false),
    array("PROTESTADAS", $letrasProtestadas, false),
    array("POR ACEPTAR", $letrasPorAceptar, true),
);

$filaLetra = $filaLetrasTitulo + 2;
foreach ($filasLetras as $datoLetra) {
    $sheet->setCellValue("B" . $filaLetra, $datoLetra[0]);
    $sheet->getStyle("B" . $filaLetra)->applyFromArray($estiloLetraEtiqueta);

    $sheet->setCellValue("G" . $filaLetra, $datoLetra[1]);
    $sheet->getStyle("G" . $filaLetra)->applyFromArray(
        $datoLetra[2] ? $estiloLetraMonto : $estiloLetraMontoSimple
    );

    $filaLetra++;
}

$filaLetraTotal = $filaLetra;
$sheet->setCellValue("G" . $filaLetraTotal, $letrasEstadoTotal);
$sheet->getStyle("G" . $filaLetraTotal)->applyFromArray($estiloTotalNumero);

// --- Bloque: Letras x cobrar según vencimiento ---
$filaVencTitulo = $filaLetraTotal + 3;
$sheet->setCellValue("B" . $filaVencTitulo, "LETRAS X COBRAR SEGÚN VENCIMIENTO");
$sheet->getStyle("B" . $filaVencTitulo)->applyFromArray($estiloSeccion);

$filaVenc = $filaVencTitulo + 2;
$sumLetrasVenc = 0.0;
foreach ($letrasPorMes as $mesVend) {
    $sheet->setCellValue("B" . $filaVenc, $mesVend["nombre"]);
    $sheet->getStyle("B" . $filaVenc)->applyFromArray($estiloEtiqueta);

    $sheet->setCellValue("G" . $filaVenc, (float) $mesVend["saldo"]);
    $sheet->getStyle("G" . $filaVenc)->applyFromArray($estiloNumero);

    $sumLetrasVenc += (float) $mesVend["saldo"];
    $filaVenc++;
}

$filaVencTotal = $filaVenc;
$sheet->setCellValue("G" . $filaVencTotal, $sumLetrasVenc);
$sheet->getStyle("G" . $filaVencTotal)->applyFromArray($estiloTotalNumero);

// --- Bloque: Facturas x cobrar según vencimiento ---
$filaFacTitulo = $filaVencTotal + 3;
$sheet->setCellValue("B" . $filaFacTitulo, "FACTURAS X COBRAR SEGÚN VENCIMIENTO");
$sheet->getStyle("B" . $filaFacTitulo)->applyFromArray($estiloSeccion);

$filaFacHeader = $filaFacTitulo + 1;
$sheet->setCellValue("E" . $filaFacHeader, "Facturas");
$sheet->setCellValue("F" . $filaFacHeader, "Guias");
$sheet->getStyle("E" . $filaFacHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("F" . $filaFacHeader)->applyFromArray($estiloHeaderTabla);

$filaFac = $filaFacHeader + 1;
$sumFacMes = 0.0;
$sumGuiasMes = 0.0;
$sumFacTotalMes = 0.0;

foreach ($facturasPorMes as $mesFac) {
    $sheet->setCellValue("B" . $filaFac, $mesFac["nombre"]);
    $sheet->getStyle("B" . $filaFac)->applyFromArray($estiloEtiqueta);

    $formatoCeldaMonto($sheet, "E" . $filaFac, $mesFac["facturas"], $estiloNumero);
    $formatoCeldaMonto($sheet, "F" . $filaFac, $mesFac["guias"], $estiloNumero);
    $formatoCeldaMonto($sheet, "G" . $filaFac, $mesFac["total"], $estiloNumero);

    $sumFacMes += (float) $mesFac["facturas"];
    $sumGuiasMes += (float) $mesFac["guias"];
    $sumFacTotalMes += (float) $mesFac["total"];

    $filaFac++;
}

$estiloTotalFacLinea = array(
    "font" => array(
        "bold" => true,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
    "borders" => array(
        "top" => array(
            "style" => PHPExcel_Style_Border::BORDER_THIN,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$sheet->setCellValue("E" . $filaFac, $sumFacMes);
$sheet->setCellValue("F" . $filaFac, $sumGuiasMes);
$sheet->setCellValue("G" . $filaFac, $sumFacTotalMes);
$sheet->getStyle("E" . $filaFac)->applyFromArray($estiloTotalFacLinea);
$sheet->getStyle("F" . $filaFac)->applyFromArray($estiloTotalFacLinea);
$sheet->getStyle("G" . $filaFac)->applyFromArray($estiloTotalNumero);

// --- Bloque: Documentos vencidos x vendedor ---
$filaVenTitulo = $filaFac + 3;
$sheet->setCellValue("B" . $filaVenTitulo, "Documentos Vencidos x Vendedor");
$sheet->getStyle("B" . $filaVenTitulo)->applyFromArray($estiloSeccion);

$filaVenHeader = $filaVenTitulo + 1;
$sheet->setCellValue("E" . $filaVenHeader, "Facturas");
$sheet->setCellValue("F" . $filaVenHeader, "Guias");
$sheet->setCellValue("G" . $filaVenHeader, "Letras");
$sheet->setCellValue("H" . $filaVenHeader, "Total");
$sheet->getStyle("E" . $filaVenHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("F" . $filaVenHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("G" . $filaVenHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("H" . $filaVenHeader)->applyFromArray($estiloHeaderTabla);

$filaVen = $filaVenHeader + 1;
$sumVenFac = 0.0;
$sumVenGuias = 0.0;
$sumVenLetras = 0.0;
$sumVenTotal = 0.0;

foreach ($vencidosPorVendedor as $vendVen) {
    $sheet->setCellValue("B" . $filaVen, $vendVen["nombre"]);
    $sheet->getStyle("B" . $filaVen)->applyFromArray($estiloEtiqueta);

    $formatoCeldaMonto($sheet, "E" . $filaVen, $vendVen["facturas"], $estiloNumero);
    $formatoCeldaMonto($sheet, "F" . $filaVen, $vendVen["guias"], $estiloNumero);
    $formatoCeldaMonto($sheet, "G" . $filaVen, $vendVen["letras"], $estiloNumero);
    $formatoCeldaMonto($sheet, "H" . $filaVen, $vendVen["total"], $estiloNumero);

    $sumVenFac += (float) $vendVen["facturas"];
    $sumVenGuias += (float) $vendVen["guias"];
    $sumVenLetras += (float) $vendVen["letras"];
    $sumVenTotal += (float) $vendVen["total"];

    $filaVen++;
}

$sheet->setCellValue("B" . $filaVen, "TOTAL");
$sheet->getStyle("B" . $filaVen)->applyFromArray(array(
    "font" => array("bold" => true, "size" => 11, "name" => "Calibri"),
    "alignment" => array("horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT),
));
$sheet->setCellValue("E" . $filaVen, $sumVenFac);
$sheet->setCellValue("F" . $filaVen, $sumVenGuias);
$sheet->setCellValue("G" . $filaVen, $sumVenLetras);
$sheet->setCellValue("H" . $filaVen, $sumVenTotal);
$sheet->getStyle("E" . $filaVen)->applyFromArray($estiloTotalVend);
$sheet->getStyle("F" . $filaVen)->applyFromArray($estiloTotalVend);
$sheet->getStyle("G" . $filaVen)->applyFromArray($estiloTotalVend);
$sheet->getStyle("H" . $filaVen)->applyFromArray($estiloTotalVend);

// --- Bloque: Documentos incobrables x vendedor ---
$filaIncTitulo = $filaVen + 3;
$sheet->setCellValue("B" . $filaIncTitulo, "Documentos Incobrables x Vendedor");
$sheet->getStyle("B" . $filaIncTitulo)->applyFromArray($estiloSeccion);

$filaIncHeader = $filaIncTitulo + 1;
$sheet->setCellValue("E" . $filaIncHeader, "Facturas");
$sheet->setCellValue("F" . $filaIncHeader, "Guias");
$sheet->setCellValue("G" . $filaIncHeader, "LETRAS");
$sheet->setCellValue("H" . $filaIncHeader, "Total");
$sheet->getStyle("E" . $filaIncHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("F" . $filaIncHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("G" . $filaIncHeader)->applyFromArray($estiloHeaderTabla);
$sheet->getStyle("H" . $filaIncHeader)->applyFromArray($estiloHeaderTabla);

$filaInc = $filaIncHeader + 1;
$sumIncFac = 0.0;
$sumIncGuias = 0.0;
$sumIncLetras = 0.0;
$sumIncTotal = 0.0;

foreach ($incobrablesPorVendedor as $vendInc) {
    $sheet->setCellValue("B" . $filaInc, $vendInc["nombre"]);
    $sheet->getStyle("B" . $filaInc)->applyFromArray($estiloEtiqueta);

    $formatoCeldaMonto($sheet, "E" . $filaInc, $vendInc["facturas"], $estiloNumero);
    $formatoCeldaMonto($sheet, "F" . $filaInc, $vendInc["guias"], $estiloNumero);
    $formatoCeldaMonto($sheet, "G" . $filaInc, $vendInc["letras"], $estiloNumero);
    $formatoCeldaMonto($sheet, "H" . $filaInc, $vendInc["total"], $estiloNumero);

    $sumIncFac += (float) $vendInc["facturas"];
    $sumIncGuias += (float) $vendInc["guias"];
    $sumIncLetras += (float) $vendInc["letras"];
    $sumIncTotal += (float) $vendInc["total"];

    $filaInc++;
}

$sheet->setCellValue("B" . $filaInc, "TOTAL");
$sheet->getStyle("B" . $filaInc)->applyFromArray(array(
    "font" => array("bold" => true, "size" => 11, "name" => "Calibri"),
    "alignment" => array("horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT),
));
$sheet->setCellValue("E" . $filaInc, $sumIncFac);
$sheet->setCellValue("F" . $filaInc, $sumIncGuias);
$sheet->setCellValue("G" . $filaInc, $sumIncLetras);
$sheet->setCellValue("H" . $filaInc, $sumIncTotal);
$sheet->getStyle("E" . $filaInc)->applyFromArray($estiloTotalVend);
$sheet->getStyle("F" . $filaInc)->applyFromArray($estiloTotalVend);
$sheet->getStyle("G" . $filaInc)->applyFromArray($estiloTotalVend);
$sheet->getStyle("H" . $filaInc)->applyFromArray($estiloTotalVend);

$sheet->getColumnDimension("A")->setWidth(3);
$sheet->getColumnDimension("B")->setWidth(22);
$sheet->getColumnDimension("C")->setWidth(3);
$sheet->getColumnDimension("D")->setWidth(3);
$sheet->getColumnDimension("E")->setWidth(14);
$sheet->getColumnDimension("F")->setWidth(14);
$sheet->getColumnDimension("G")->setWidth(14);
$sheet->getColumnDimension("H")->setWidth(14);

$sheet->getRowDimension(1)->setRowHeight(18);
$sheet->getRowDimension(2)->setRowHeight(18);

// =====================
// HOJA 2: COBRANZA
// =====================
$sheetCob = $objPHPExcel->createSheet();
$sheetCob->setTitle("Cobranza");

$estiloTituloCob = array(
    "font" => array(
        "bold" => true,
        "size" => 12,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloHeaderCob = array(
    "font" => array(
        "bold" => true,
        "size" => 10,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        "wrap" => true,
    ),
    "borders" => array(
        "allborders" => array(
            "style" => PHPExcel_Style_Border::BORDER_THIN,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$estiloCeldaCob = array(
    "font" => array(
        "size" => 10,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
    "borders" => array(
        "allborders" => array(
            "style" => PHPExcel_Style_Border::BORDER_THIN,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$estiloNombreCob = array(
    "font" => array(
        "size" => 10,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
    ),
    "borders" => array(
        "allborders" => array(
            "style" => PHPExcel_Style_Border::BORDER_THIN,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$estiloTotalCob = array(
    "font" => array(
        "bold" => true,
        "size" => 10,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
    "borders" => array(
        "allborders" => array(
            "style" => PHPExcel_Style_Border::BORDER_THIN,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$colsCob = $cobranza["columnas"];
// Grupo 1: cobranza líquida | Grupo 2: ajustes / descuentos / otros
$grupoCob1 = array("LETRAS BANCO", "DEPOSITOS BCP", "YAPE", "EFECTIVO");
$grupoCob2 = array(
    "DEVOLUCION DE PRENDAS",
    "DSCTOS FACTURAS",
    "DSCTOS GUIAS",
    "REF",
    "RENOVACIÓN",
    "AJUSTE",
);

$letrasCol = range("A", "Z");
// A = vendedor, B... = columnas pago, última = Total general
$numColsPago = count($colsCob);
$colTotalIdx = $numColsPago + 1; // 0=A vendedor
$colTotalLetra = $letrasCol[$colTotalIdx];
$colFinGrupo1 = $letrasCol[count($grupoCob1)]; // E
$colIniGrupo2 = $letrasCol[count($grupoCob1) + 1]; // F
$colFinGrupo2 = $letrasCol[$numColsPago]; // K

$estiloBordeGrupo = array(
    "borders" => array(
        "right" => array(
            "style" => PHPExcel_Style_Border::BORDER_MEDIUM,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$estiloSubtotalGrupo = array(
    "font" => array(
        "bold" => true,
        "size" => 11,
        "name" => "Calibri",
    ),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
    "numberformat" => array(
        "code" => "#,##0.00",
    ),
    "borders" => array(
        "outline" => array(
            "style" => PHPExcel_Style_Border::BORDER_MEDIUM,
            "color" => array("rgb" => "000000"),
        ),
    ),
);

$sheetCob->mergeCells("A1:" . $colTotalLetra . "1");
$sheetCob->setCellValue(
    "A1",
    "RESUMEN DE COBRANZA X VENDEDOR - MES DE " . $nombreMesCobranza . " " . $anioCobranza . " / CORPORACION VASCO SAC"
);
$sheetCob->getStyle("A1")->applyFromArray($estiloTituloCob);
$sheetCob->getRowDimension(1)->setRowHeight(22);

$filaHeaderCob = 3;
$sheetCob->setCellValue("A" . $filaHeaderCob, "Vendedor");
$sheetCob->getStyle("A" . $filaHeaderCob)->applyFromArray($estiloHeaderCob);

foreach ($colsCob as $i => $nombreCol) {
    $letra = $letrasCol[$i + 1];
    $sheetCob->setCellValue($letra . $filaHeaderCob, $nombreCol);
    $sheetCob->getStyle($letra . $filaHeaderCob)->applyFromArray($estiloHeaderCob);
}
$sheetCob->setCellValue($colTotalLetra . $filaHeaderCob, "Total general");
$sheetCob->getStyle($colTotalLetra . $filaHeaderCob)->applyFromArray($estiloHeaderCob);
$sheetCob->getRowDimension($filaHeaderCob)->setRowHeight(30);

$filaCob = $filaHeaderCob + 1;
$filaInicioDatosCob = $filaCob;
foreach ($cobranza["filas"] as $filaVend) {
    $sheetCob->setCellValue("A" . $filaCob, $filaVend["nombre"]);
    $sheetCob->getStyle("A" . $filaCob)->applyFromArray($estiloNombreCob);

    foreach ($colsCob as $i => $nombreCol) {
        $letra = $letrasCol[$i + 1];
        $monto = (float) $filaVend["montos"][$nombreCol];
        if (abs($monto) < 0.005) {
            $sheetCob->setCellValue($letra . $filaCob, null);
        } else {
            $sheetCob->setCellValue($letra . $filaCob, $monto);
        }
        $sheetCob->getStyle($letra . $filaCob)->applyFromArray($estiloCeldaCob);
    }

    $sheetCob->setCellValue($colTotalLetra . $filaCob, (float) $filaVend["total"]);
    $sheetCob->getStyle($colTotalLetra . $filaCob)->applyFromArray($estiloTotalCob);

    $filaCob++;
}
$filaFinDatosCob = $filaCob - 1;

$filaTotalCob = $filaCob;
$sheetCob->setCellValue("A" . $filaTotalCob, "Total general");
$sheetCob->getStyle("A" . $filaTotalCob)->applyFromArray(array_merge($estiloNombreCob, array(
    "font" => array("bold" => true, "size" => 10, "name" => "Calibri"),
)));

$sumGrupo1 = 0.0;
$sumGrupo2 = 0.0;
foreach ($colsCob as $i => $nombreCol) {
    $letra = $letrasCol[$i + 1];
    $monto = (float) $cobranza["totales"][$nombreCol];
    if (abs($monto) < 0.005) {
        $sheetCob->setCellValue($letra . $filaTotalCob, null);
    } else {
        $sheetCob->setCellValue($letra . $filaTotalCob, $monto);
    }
    $sheetCob->getStyle($letra . $filaTotalCob)->applyFromArray($estiloTotalCob);

    if (in_array($nombreCol, $grupoCob1, true)) {
        $sumGrupo1 += $monto;
    }
    if (in_array($nombreCol, $grupoCob2, true)) {
        $sumGrupo2 += $monto;
    }
}
$sheetCob->setCellValue($colTotalLetra . $filaTotalCob, (float) $cobranza["total_general"]);
$sheetCob->getStyle($colTotalLetra . $filaTotalCob)->applyFromArray($estiloTotalCob);

// Separador visual entre grupos (borde medio al cierre del grupo 1)
if ($filaFinDatosCob >= $filaInicioDatosCob) {
    $sheetCob->getStyle($colFinGrupo1 . $filaHeaderCob . ":" . $colFinGrupo1 . $filaTotalCob)
        ->applyFromArray($estiloBordeGrupo);
}

// Fila de subtotales por grupo
$filaGruposCob = $filaTotalCob + 1;
$sheetCob->mergeCells("B" . $filaGruposCob . ":" . $colFinGrupo1 . $filaGruposCob);
$sheetCob->setCellValue("B" . $filaGruposCob, $sumGrupo1);
$sheetCob->getStyle("B" . $filaGruposCob . ":" . $colFinGrupo1 . $filaGruposCob)
    ->applyFromArray($estiloSubtotalGrupo);

$sheetCob->mergeCells($colIniGrupo2 . $filaGruposCob . ":" . $colFinGrupo2 . $filaGruposCob);
$sheetCob->setCellValue($colIniGrupo2 . $filaGruposCob, $sumGrupo2);
$sheetCob->getStyle($colIniGrupo2 . $filaGruposCob . ":" . $colFinGrupo2 . $filaGruposCob)
    ->applyFromArray($estiloSubtotalGrupo);

$sheetCob->setCellValue($colTotalLetra . $filaGruposCob, (float) $cobranza["total_general"]);
$sheetCob->getStyle($colTotalLetra . $filaGruposCob)->applyFromArray($estiloSubtotalGrupo);
$sheetCob->getRowDimension($filaGruposCob)->setRowHeight(20);

$sheetCob->getColumnDimension("A")->setWidth(22);
foreach ($colsCob as $i => $nombreCol) {
    $letra = $letrasCol[$i + 1];
    $sheetCob->getColumnDimension($letra)->setWidth(14);
}
$sheetCob->getColumnDimension($colTotalLetra)->setWidth(14);

// --- Segundo bloque: cobranza por tipo de documento ---
$filaDocTitulo = $filaGruposCob + 3;
$sheetCob->mergeCells("A" . $filaDocTitulo . ":" . $colTotalLetra . $filaDocTitulo);
$sheetCob->setCellValue(
    "A" . $filaDocTitulo,
    "RESUMEN DE COBRANZA X TIPO DOCUMENTO - MES DE " . $nombreMesCobranza . " " . $anioCobranza . " / CORPORACION VASCO SAC"
);
$sheetCob->getStyle("A" . $filaDocTitulo)->applyFromArray($estiloTituloCob);
$sheetCob->getRowDimension($filaDocTitulo)->setRowHeight(22);

$filaHeaderDoc = $filaDocTitulo + 2;
$sheetCob->setCellValue("A" . $filaHeaderDoc, "Tipo doc.");
$sheetCob->getStyle("A" . $filaHeaderDoc)->applyFromArray($estiloHeaderCob);

foreach ($colsCob as $i => $nombreCol) {
    $letra = $letrasCol[$i + 1];
    $sheetCob->setCellValue($letra . $filaHeaderDoc, $nombreCol);
    $sheetCob->getStyle($letra . $filaHeaderDoc)->applyFromArray($estiloHeaderCob);
}
$sheetCob->setCellValue($colTotalLetra . $filaHeaderDoc, "Total general");
$sheetCob->getStyle($colTotalLetra . $filaHeaderDoc)->applyFromArray($estiloHeaderCob);
$sheetCob->getRowDimension($filaHeaderDoc)->setRowHeight(30);

$filaDoc = $filaHeaderDoc + 1;
$filaInicioDoc = $filaDoc;
foreach ($cobranzaTipoDoc["filas"] as $filaTipo) {
    $sheetCob->setCellValue("A" . $filaDoc, $filaTipo["nombre"]);
    $sheetCob->getStyle("A" . $filaDoc)->applyFromArray($estiloNombreCob);

    foreach ($colsCob as $i => $nombreCol) {
        $letra = $letrasCol[$i + 1];
        $monto = (float) $filaTipo["montos"][$nombreCol];
        if (abs($monto) < 0.005) {
            $sheetCob->setCellValue($letra . $filaDoc, null);
        } else {
            $sheetCob->setCellValue($letra . $filaDoc, $monto);
        }
        $sheetCob->getStyle($letra . $filaDoc)->applyFromArray($estiloCeldaCob);
    }

    $sheetCob->setCellValue($colTotalLetra . $filaDoc, (float) $filaTipo["total"]);
    $sheetCob->getStyle($colTotalLetra . $filaDoc)->applyFromArray($estiloTotalCob);
    $filaDoc++;
}
$filaFinDoc = $filaDoc - 1;

$filaTotalDoc = $filaDoc;
$sheetCob->setCellValue("A" . $filaTotalDoc, "Total general");
$sheetCob->getStyle("A" . $filaTotalDoc)->applyFromArray(array_merge($estiloNombreCob, array(
    "font" => array("bold" => true, "size" => 10, "name" => "Calibri"),
)));

$sumGrupo1Doc = 0.0;
$sumGrupo2Doc = 0.0;
foreach ($colsCob as $i => $nombreCol) {
    $letra = $letrasCol[$i + 1];
    $monto = (float) $cobranzaTipoDoc["totales"][$nombreCol];
    if (abs($monto) < 0.005) {
        $sheetCob->setCellValue($letra . $filaTotalDoc, null);
    } else {
        $sheetCob->setCellValue($letra . $filaTotalDoc, $monto);
    }
    $sheetCob->getStyle($letra . $filaTotalDoc)->applyFromArray($estiloTotalCob);

    if (in_array($nombreCol, $grupoCob1, true)) {
        $sumGrupo1Doc += $monto;
    }
    if (in_array($nombreCol, $grupoCob2, true)) {
        $sumGrupo2Doc += $monto;
    }
}
$sheetCob->setCellValue($colTotalLetra . $filaTotalDoc, (float) $cobranzaTipoDoc["total_general"]);
$sheetCob->getStyle($colTotalLetra . $filaTotalDoc)->applyFromArray($estiloTotalCob);

if ($filaFinDoc >= $filaInicioDoc) {
    $sheetCob->getStyle($colFinGrupo1 . $filaHeaderDoc . ":" . $colFinGrupo1 . $filaTotalDoc)
        ->applyFromArray($estiloBordeGrupo);
}

$filaGruposDoc = $filaTotalDoc + 1;
$sheetCob->mergeCells("B" . $filaGruposDoc . ":" . $colFinGrupo1 . $filaGruposDoc);
$sheetCob->setCellValue("B" . $filaGruposDoc, $sumGrupo1Doc);
$sheetCob->getStyle("B" . $filaGruposDoc . ":" . $colFinGrupo1 . $filaGruposDoc)
    ->applyFromArray($estiloSubtotalGrupo);

$sheetCob->mergeCells($colIniGrupo2 . $filaGruposDoc . ":" . $colFinGrupo2 . $filaGruposDoc);
$sheetCob->setCellValue($colIniGrupo2 . $filaGruposDoc, $sumGrupo2Doc);
$sheetCob->getStyle($colIniGrupo2 . $filaGruposDoc . ":" . $colFinGrupo2 . $filaGruposDoc)
    ->applyFromArray($estiloSubtotalGrupo);

$sheetCob->setCellValue($colTotalLetra . $filaGruposDoc, (float) $cobranzaTipoDoc["total_general"]);
$sheetCob->getStyle($colTotalLetra . $filaGruposDoc)->applyFromArray($estiloSubtotalGrupo);
$sheetCob->getRowDimension($filaGruposDoc)->setRowHeight(20);

// =====================
// HOJA 3: METADATOS
// =====================
$sheetMeta = $objPHPExcel->createSheet();
$sheetMeta->setTitle("Metadatos");

$estiloMetaTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloMetaSeccion = array(
    "font" => array("bold" => true, "size" => 11, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "5D6D7E")),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
);

$estiloMetaEtiqueta = array(
    "font" => array("bold" => true, "size" => 10, "name" => "Calibri"),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP),
);

$estiloMetaValor = array(
    "font" => array("size" => 10, "name" => "Calibri"),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_TOP, "wrap" => true),
);

$sheetMeta->mergeCells("A1:B1");
$sheetMeta->setCellValue("A1", "INFORMACIÓN DEL REPORTE — ESTADO DE CUENTA GERENCIA");
$sheetMeta->getStyle("A1")->applyFromArray($estiloMetaTitulo);
$sheetMeta->getRowDimension(1)->setRowHeight(26);

$codigosIncobrablesMeta = "00A, 01, 03, 05A, 07A, 14, 15";
if (file_exists(dirname(__FILE__) . "/../../controladores/dashboard-cxc.config.php")) {
    require_once dirname(__FILE__) . "/../../controladores/dashboard-cxc.config.php";
    if (function_exists("dashboardCxcVendedoresIncobrables")) {
        $codigosIncobrablesMeta = implode(", ", dashboardCxcVendedoresIncobrables());
    }
}

$metaFilas = array(
    array("_sec", "DATOS DEL REPORTE"),
    array("Reporte", "Estado de cuenta gerencia (Resumen + Cobranza)"),
    array("Fecha de exportación", $fechaHora),
    array("Corte CxC (Resumen)", $fechaCorte),
    array("Periodo cobranza", $nombreMesCobranza . " " . $anioCobranza),
    array("Solicitado por", $nombreSolicitante),
    array("Generado por", $nombreGenerador),
    array("Equipo (PC)", $nombrePc !== "" ? $nombrePc : "No identificado"),
    array("Total CxC (Resumen)", "S/ " . number_format($total, 2, ".", ",")),
    array("Total cobranza del mes", "S/ " . number_format((float) $cobranza["total_general"], 2, ".", ",")),

    array("_sec", "HOJA RESUMEN — BLOQUES"),
    array("Total Documentos x Cobrar", "Saldos pendientes (tip_mov +, estado PENDIENTE, saldo > 0) por tipo: Facturas 01+ND 08, Boletas 03, Letras 85, Guias Varios 09 (proformas)."),
    array("Cuentas por cobrar por vendedor", "Mismo universo CxC, agrupado por vendedor. Facturas = 01+03+08; Guias = 09; Letras = 85."),
    array("Estado de letras por cobrar", "Solo letras 85. Prioridad: CARTERA (num_unico) → PROTESTADAS → BANCO CREDITO (banco 02) → POR ACEPTAR (resto)."),
    array("Letras x cobrar según vencimiento", "Letras pendientes con fecha_ven ≥ hoy, sumadas por mes de vencimiento."),
    array("Facturas x cobrar según vencimiento", "Facturas/boletas/ND + proformas con fecha_ven ≥ hoy, por mes. Columnas Facturas / Guias."),
    array("Documentos vencidos x vendedor", "fecha_ven < hoy, excluye vendedores incobrables."),
    array("Documentos incobrables x vendedor", "fecha_ven < hoy, solo códigos de vendedor: " . $codigosIncobrablesMeta . "."),

    array("_sec", "HOJA COBRANZA"),
    array("Fuente", "Movimientos tip_mov = '-' (abonos/cancelaciones) del mes/año seleccionado."),
    array("Columnas de pago", "LETRAS BANCO (00,TR) | DEPOSITOS BCP (05,06,14,16,17,18) | YAPE (15) | EFECTIVO (80,82) | DEVOLUCION (13,96) | DSCTOS FACTURAS (97) | DSCTOS GUIAS (10) | REF (RF) | RENOVACIÓN (85) | AJUSTE (98)."),
    array("Grupo líquido", "LETRAS BANCO + DEPOSITOS BCP + YAPE + EFECTIVO."),
    array("Grupo ajustes", "DEVOLUCION DE PRENDAS + DSCTOS FACTURAS + DSCTOS GUIAS + REF + RENOVACIÓN + AJUSTE."),
    array("Por vendedor", "Pivot vendedor × tipo de pago."),
    array("Por tipo documento", "Pivot FACTURAS (01/03/07/08) / GUIAS (09) / LETRAS × tipo de pago."),

    array("_sec", "NOTAS"),
    array("Uso del reporte", "Informe gerencial de cartera y cobranza. No reemplaza el estado de cuenta operativo por cliente."),
    array("Actualización", "Los montos son en vivo al momento de exportar."),
    array("Nota", "Esta hoja es de solo lectura."),
);

$filaMeta = 3;
foreach ($metaFilas as $metaItem) {
    if ($metaItem[0] === "_sec") {
        $sheetMeta->mergeCells("A" . $filaMeta . ":B" . $filaMeta);
        $sheetMeta->setCellValue("A" . $filaMeta, $metaItem[1]);
        $sheetMeta->getStyle("A" . $filaMeta . ":B" . $filaMeta)->applyFromArray($estiloMetaSeccion);
        $sheetMeta->getRowDimension($filaMeta)->setRowHeight(20);
        $filaMeta++;
        continue;
    }

    $sheetMeta->setCellValue("A" . $filaMeta, $metaItem[0]);
    $sheetMeta->setCellValue("B" . $filaMeta, $metaItem[1]);
    $sheetMeta->getStyle("A" . $filaMeta)->applyFromArray($estiloMetaEtiqueta);
    $sheetMeta->getStyle("B" . $filaMeta)->applyFromArray($estiloMetaValor);
    $sheetMeta->getStyle("B" . $filaMeta)->getAlignment()->setWrapText(true);
    $filaMeta++;
}

$sheetMeta->getColumnDimension("A")->setWidth(36);
$sheetMeta->getColumnDimension("B")->setWidth(95);

$sheetMeta->getStyle("A1:B" . max(1, $filaMeta - 1))
    ->getProtection()
    ->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);

$protectionMeta = $sheetMeta->getProtection();
$protectionMeta->setSheet(true);
$protectionMeta->setSelectLockedCells(true);
$protectionMeta->setSelectUnlockedCells(true);
$protectionMeta->setFormatCells(false);
$protectionMeta->setFormatColumns(false);
$protectionMeta->setFormatRows(false);
$protectionMeta->setInsertColumns(false);
$protectionMeta->setInsertRows(false);
$protectionMeta->setDeleteColumns(false);
$protectionMeta->setDeleteRows(false);
$protectionMeta->setSort(false);
$protectionMeta->setAutoFilter(false);

$objPHPExcel->setActiveSheetIndex(0);

$nombreArchivo = "Estado_Cuenta_Gerencia_" . $fechaArchivo . ".xls";

while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment;filename=\"" . $nombreArchivo . "\"");
header("Cache-Control: max-age=0");
header("Pragma: public");

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
$objWriter->save("php://output");
exit;
