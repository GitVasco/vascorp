<?php

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
    session_start();
}

include "../reportes_excel/Classes/PHPExcel.php";
require_once "../../controladores/config.php";
require_once "../../controladores/inteligencia-comercial.config.php";
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

@ini_set("max_execution_time", "300");
@ini_set("memory_limit", "512M");

date_default_timezone_set("America/Lima");
$fecha = date("d-m-Y");
$fechaHora = date("d/m/Y H:i");

$idGenerador = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
$generador = $idGenerador > 0 ? ModeloLineaCredito::mdlUsuarioActivoPorId($idGenerador) : null;
$nombreSolicitante = trim((string) $solicitante["nombre"]);
$nombreGenerador = ($generador && !empty($generador["nombre"])) ? trim((string) $generador["nombre"]) : "Sistema";
$nombrePc = (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] !== "")
    ? gethostbyaddr($_SERVER["REMOTE_ADDR"])
    : "";

$clientes = ModeloLineaCredito::mdlExportExcelClientesConDeudaCxc();

function cxcExportTexto($valor)
{
    if ($valor === null) {
        return "";
    }

    $texto = (string) $valor;

    if (function_exists("mb_convert_encoding")) {
        return mb_convert_encoding($texto, "UTF-8", "UTF-8");
    }

    return $texto;
}

function cxcExportCalificacion(array $fila)
{
    if (!isset($fila["score_riesgo"]) || $fila["score_riesgo"] === null || $fila["score_riesgo"] === "") {
        return "";
    }

    $score = (float) $fila["score_riesgo"];
    $etiqueta = "Riesgo Alto";

    if (function_exists("icClasificarScore")) {
        $clasificacion = icClasificarScore($score);
        if (!empty($clasificacion["etiqueta"])) {
            $etiqueta = $clasificacion["etiqueta"];
        }
    } elseif ($score >= 90) {
        $etiqueta = "Excelente";
    } elseif ($score >= 80) {
        $etiqueta = "Bueno";
    } elseif ($score >= 70) {
        $etiqueta = "Aceptable";
    } elseif ($score >= 60) {
        $etiqueta = "Riesgo Medio";
    }

    return $etiqueta . " (" . number_format($score, 0) . ")";
}

function cxcExportLineaManual($valor)
{
    if ($valor === null || $valor === "") {
        return "";
    }

    return (float) $valor > 0 ? (float) $valor : "";
}

function cxcExportCupo($lineaAprobada, $lineaPropuesta, $deudaActual)
{
    $base = 0.0;

    if ($lineaAprobada !== null && $lineaAprobada !== "" && (float) $lineaAprobada > 0) {
        $base = (float) $lineaAprobada;
    } elseif ($lineaPropuesta !== null && $lineaPropuesta !== "" && (float) $lineaPropuesta > 0) {
        $base = (float) $lineaPropuesta;
    } else {
        return "";
    }

    return $base - (float) $deudaActual;
}

function cxcExportCorreoValido($correo)
{
    $correo = trim((string) $correo);

    if ($correo === "") {
        return "";
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return "";
    }

    return $correo;
}

function cxcExportEstiloFondo($rgb, $negrita = false, $textoRgb = null)
{
    $estilo = array(
        "fill" => array(
            "type" => PHPExcel_Style_Fill::FILL_SOLID,
            "color" => array("rgb" => $rgb),
        ),
    );

    if ($negrita || $textoRgb !== null) {
        $estilo["font"] = array();
        if ($negrita) {
            $estilo["font"]["bold"] = true;
        }
        if ($textoRgb !== null) {
            $estilo["font"]["color"] = array("rgb" => $textoRgb);
        }
    }

    return $estilo;
}

function cxcExportNormalizarHex($color)
{
    $color = trim((string) $color);
    if ($color === "") {
        return "";
    }

    $color = ltrim($color, "#");
    if (!preg_match('/^[0-9A-Fa-f]{6}$/', $color)) {
        return "";
    }

    return strtoupper($color);
}

/**
 * Contraste simple: si el fondo es oscuro, texto blanco.
 */
function cxcExportTextoContraste($hexFondo)
{
    $r = hexdec(substr($hexFondo, 0, 2));
    $g = hexdec(substr($hexFondo, 2, 2));
    $b = hexdec(substr($hexFondo, 4, 2));
    $luminancia = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

    return $luminancia < 140 ? "FFFFFF" : "333333";
}

function cxcExportColorRiesgo($score)
{
    if ($score === null || $score === "") {
        return null;
    }

    $n = (float) $score;
    // Misma escala que Inteligencia Comercial / línea de crédito
    if ($n >= 90) {
        return array("fondo" => "ABEBC6", "texto" => "1E8449"); // Excelente
    }
    if ($n >= 80) {
        return array("fondo" => "AED6F1", "texto" => "1A5276"); // Bueno
    }
    if ($n >= 70) {
        return array("fondo" => "D6EAF8", "texto" => "2874A6"); // Aceptable
    }
    if ($n >= 60) {
        return array("fondo" => "F9E79F", "texto" => "7D6608"); // Riesgo medio
    }

    return array("fondo" => "F5B7B1", "texto" => "922B21"); // Riesgo alto
}

function cxcExportColorearCategoriaYRiesgo($hoja, $filaNum, $categoriaColor, $scoreRiesgo)
{
    $hexCat = cxcExportNormalizarHex($categoriaColor);
    if ($hexCat !== "") {
        $hoja->getStyle("K" . $filaNum)->applyFromArray(
            cxcExportEstiloFondo($hexCat, true, cxcExportTextoContraste($hexCat))
        );
    }

    $colorRiesgo = cxcExportColorRiesgo($scoreRiesgo);
    if ($colorRiesgo !== null) {
        $hoja->getStyle("L" . $filaNum)->applyFromArray(
            cxcExportEstiloFondo($colorRiesgo["fondo"], true, $colorRiesgo["texto"])
        );
    }
}

function cxcExportColorearMontosClave($hoja, $filaNum, $cupo, $deudaActual, $deudaVencida, $docsVencidos, $docsProtestados, $diasMora)
{
    // Cupo disponible: verde / ámbar / rojo
    if ($cupo !== "" && $cupo !== null) {
        $cupoNum = (float) $cupo;
        if ($cupoNum < 0) {
            $hoja->getStyle("P" . $filaNum)->applyFromArray(cxcExportEstiloFondo("F5B7B1", true));
        } elseif ($cupoNum == 0.0) {
            $hoja->getStyle("P" . $filaNum)->applyFromArray(cxcExportEstiloFondo("F9E79F", true));
        } else {
            $hoja->getStyle("P" . $filaNum)->applyFromArray(cxcExportEstiloFondo("ABEBC6"));
        }
    }

    // Deuda actual: ámbar suave si hay saldo
    if ((float) $deudaActual > 0) {
        $hoja->getStyle("R" . $filaNum)->applyFromArray(cxcExportEstiloFondo("FCF3CF"));
    }

    // Deuda vencida: rojo/naranja si > 0
    if ((float) $deudaVencida > 0) {
        $hoja->getStyle("S" . $filaNum)->applyFromArray(cxcExportEstiloFondo("F5B7B1", true));
    }

    // Docs vencidos
    if ((int) $docsVencidos > 0) {
        $hoja->getStyle("V" . $filaNum)->applyFromArray(cxcExportEstiloFondo("FAD7A0"));
    }

    // Docs protestados
    if ((int) $docsProtestados > 0) {
        $hoja->getStyle("W" . $filaNum)->applyFromArray(cxcExportEstiloFondo("F1948A", true));
    }

    // Días mora máx
    if ($diasMora !== "" && $diasMora !== null) {
        $dias = (int) $diasMora;
        if ($dias > 90) {
            $hoja->getStyle("X" . $filaNum)->applyFromArray(cxcExportEstiloFondo("E74C3C", true));
            $hoja->getStyle("X" . $filaNum)->getFont()->getColor()->setRGB("FFFFFF");
        } elseif ($dias > 30) {
            $hoja->getStyle("X" . $filaNum)->applyFromArray(cxcExportEstiloFondo("F5B041", true));
        } elseif ($dias > 0) {
            $hoja->getStyle("X" . $filaNum)->applyFromArray(cxcExportEstiloFondo("F9E79F"));
        }
    }
}

function cxcExportEscribirFila($hoja, $filaNum, array $datos, array $estiloCelda, $formatoMoneda, array $colsMoneda, array $colsTexto, array $colsEntero = array())
{
    $letras = array(
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P",
        "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
    );

    foreach ($datos as $idx => $valor) {
        if (!isset($letras[$idx])) {
            break;
        }

        $col = $letras[$idx];

        if (in_array($col, $colsTexto, true)) {
            $hoja->setCellValueExplicit($col . $filaNum, (string) $valor, PHPExcel_Cell_DataType::TYPE_STRING);
        } else {
            $hoja->setCellValue($col . $filaNum, $valor);
        }
    }

    $ultimaCol = $letras[count($datos) - 1];
    $hoja->getStyle("A" . $filaNum . ":" . $ultimaCol . $filaNum)->applyFromArray($estiloCelda);

    foreach ($colsMoneda as $colMon) {
        if ($hoja->getCell($colMon . $filaNum)->getValue() !== "" && $hoja->getCell($colMon . $filaNum)->getValue() !== null) {
            $hoja->getStyle($colMon . $filaNum)->getNumberFormat()->setFormatCode($formatoMoneda);
        }
    }

    foreach ($colsEntero as $colEnt) {
        if ($hoja->getCell($colEnt . $filaNum)->getValue() !== "" && $hoja->getCell($colEnt . $filaNum)->getValue() !== null) {
            $hoja->getStyle($colEnt . $filaNum)->getNumberFormat()->setFormatCode("0");
        }
    }
}

function cxcExportConfigurarEncabezadoDatos($hoja, $titulo, $ultimaCol, array $estiloTitulo, array $cabeceras, array $estiloCabecera)
{
    $hoja->mergeCells("A1:" . $ultimaCol . "1");
    $hoja->setCellValue("A1", $titulo);
    $hoja->getStyle("A1")->applyFromArray($estiloTitulo);
    $hoja->getRowDimension(1)->setRowHeight(24);

    $filaCab = 2;
    foreach ($cabeceras as $col => $tituloCol) {
        $hoja->setCellValue($col . $filaCab, $tituloCol);
        $hoja->getStyle($col . $filaCab)->applyFromArray($estiloCabecera);
    }

    return $filaCab;
}

function cxcExportProtegerHojaCompleta(PHPExcel_Worksheet $hoja, $ultimaCol, $ultimaFila)
{
    $ultimaFila = max(1, (int) $ultimaFila);

    $hoja->getStyle("A1:" . $ultimaCol . $ultimaFila)
        ->getProtection()
        ->setLocked(PHPExcel_Style_Protection::PROTECTION_PROTECTED);

    $protection = $hoja->getProtection();
    $protection->setSheet(true);
    $protection->setSelectLockedCells(true);
    $protection->setSelectUnlockedCells(true);
    $protection->setFormatCells(false);
    $protection->setFormatColumns(false);
    $protection->setFormatRows(false);
    $protection->setInsertColumns(false);
    $protection->setInsertRows(false);
    $protection->setInsertHyperlinks(false);
    $protection->setDeleteColumns(false);
    $protection->setDeleteRows(false);
    $protection->setSort(false);
    $protection->setAutoFilter(false);
    $protection->setPivotTables(false);
}

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator("Corp. Vasco")
    ->setTitle("Cuentas por cobrar - clientes con deuda");

$estiloTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloCabecera = array(
    "font" => array("bold" => true, "size" => 10, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        "wrap" => true,
    ),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "BBBBBB")),
    ),
);

$estiloCelda = array(
    "font" => array("size" => 10),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "DDDDDD")),
    ),
);

$estiloMetaTitulo = array(
    "font" => array("bold" => true, "size" => 14, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "3C8DBC")),
    "alignment" => array(
        "horizontal" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        "vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ),
);

$estiloMetaEtiqueta = array(
    "font" => array("bold" => true, "size" => 11),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "EAF2F8")),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CCCCCC")),
    ),
);

$estiloMetaValor = array(
    "font" => array("size" => 11),
    "borders" => array(
        "allborders" => array("style" => PHPExcel_Style_Border::BORDER_THIN, "color" => array("rgb" => "CCCCCC")),
    ),
);

$formatoMoneda = '#,##0.00';
$ultimaCol = "Y";
$colsMoneda = array("N", "O", "P", "Q", "R", "S", "T");
$colsTexto = array("A", "B", "I", "J", "M", "Y");
$colsEntero = array("U", "V", "W", "X");

$cabeceras = array(
    "A" => "RUC",
    "B" => "Código",
    "C" => "Razón social",
    "D" => "Dirección",
    "E" => "Departamento",
    "F" => "Provincia",
    "G" => "Distrito",
    "H" => "Correo",
    "I" => "Teléfono",
    "J" => "Vendedor",
    "K" => "Categoría",
    "L" => "Calificación",
    "M" => "Grupo",
    "N" => "Línea propuesta",
    "O" => "Línea aprobada",
    "P" => "Cupo disponible",
    "Q" => "Venta del año",
    "R" => "Deuda actual",
    "S" => "Deuda vencida",
    "T" => "Deuda por vencer",
    "U" => "Docs pendientes",
    "V" => "Docs vencidos",
    "W" => "Docs protestados",
    "X" => "Días mora máx",
    "Y" => "Último pago",
);

$anchos = array(
    "A" => 14,
    "B" => 10,
    "C" => 34,
    "D" => 28,
    "E" => 14,
    "F" => 14,
    "G" => 14,
    "H" => 24,
    "I" => 14,
    "J" => 18,
    "K" => 14,
    "L" => 18,
    "M" => 28,
    "N" => 13,
    "O" => 13,
    "P" => 13,
    "Q" => 12,
    "R" => 12,
    "S" => 12,
    "T" => 13,
    "U" => 12,
    "V" => 11,
    "W" => 12,
    "X" => 11,
    "Y" => 12,
);

/* ===========================================================================
 * HOJA 1 — CLIENTES CON DEUDA
 * ======================================================================== */
$hoja1 = $objPHPExcel->setActiveSheetIndex(0);
$hoja1->setTitle("Clientes con deuda");

$filaCab = cxcExportConfigurarEncabezadoDatos(
    $hoja1,
    "CUENTAS POR COBRAR — CLIENTES CON DEUDA (" . $fecha . ")",
    $ultimaCol,
    $estiloTitulo,
    $cabeceras,
    $estiloCabecera
);

$fila = $filaCab + 1;
$totalDeuda = 0;
$totalVencida = 0;

foreach ($clientes as $item) {
    $deudaActual = (float) $item["deuda_actual"];
    $deudaVencida = (float) $item["deuda_vencida"];
    $totalDeuda += $deudaActual;
    $totalVencida += $deudaVencida;

    $ultimoPago = "";
    if (!empty($item["ultimo_pago"]) && $item["ultimo_pago"] !== "0000-00-00") {
        $ts = strtotime($item["ultimo_pago"]);
        $ultimoPago = $ts ? date("d/m/Y", $ts) : cxcExportTexto($item["ultimo_pago"]);
    }

    $diasMora = ($item["dias_mora_max"] === null || $item["dias_mora_max"] === "")
        ? ""
        : (int) $item["dias_mora_max"];

    $cupo = cxcExportCupo($item["linea_aprobada"], $item["linea_propuesta"], $deudaActual);
    $docsVencidos = (int) $item["docs_vencidos"];
    $docsProtestados = (int) $item["docs_protestados"];
    $nombreGrupo = !empty($item["nombre_grupo"])
        ? $item["nombre_grupo"]
        : (isset($item["codigo_grupo"]) ? $item["codigo_grupo"] : "");

    cxcExportEscribirFila($hoja1, $fila, array(
        cxcExportTexto($item["ruc"]),
        cxcExportTexto($item["codigo_cliente"]),
        cxcExportTexto($item["razon_social"]),
        cxcExportTexto($item["direccion"]),
        cxcExportTexto($item["departamento"]),
        cxcExportTexto($item["provincia"]),
        cxcExportTexto($item["distrito"]),
        cxcExportTexto(cxcExportCorreoValido(isset($item["correo"]) ? $item["correo"] : "")),
        cxcExportTexto($item["telefono"]),
        cxcExportTexto($item["vendedor"]),
        cxcExportTexto($item["categoria_nombre"]),
        cxcExportCalificacion($item),
        cxcExportTexto($nombreGrupo),
        (float) $item["linea_propuesta"],
        cxcExportLineaManual($item["linea_aprobada"]),
        $cupo,
        (float) $item["venta_anio"],
        $deudaActual,
        $deudaVencida,
        (float) $item["deuda_por_vencer"],
        (int) $item["docs_pendientes"],
        $docsVencidos,
        $docsProtestados,
        $diasMora,
        $ultimoPago,
    ), $estiloCelda, $formatoMoneda, $colsMoneda, $colsTexto, $colsEntero);

    cxcExportColorearCategoriaYRiesgo(
        $hoja1,
        $fila,
        isset($item["categoria_color"]) ? $item["categoria_color"] : "",
        isset($item["score_riesgo"]) ? $item["score_riesgo"] : null
    );

    cxcExportColorearMontosClave(
        $hoja1,
        $fila,
        $cupo,
        $deudaActual,
        $deudaVencida,
        $docsVencidos,
        $docsProtestados,
        $diasMora
    );
    $fila++;
}

foreach ($anchos as $col => $ancho) {
    $hoja1->getColumnDimension($col)->setWidth($ancho);
}
$hoja1->freezePane("A3");

/* ===========================================================================
 * HOJA 2 — METADATOS (protegida)
 * ======================================================================== */
$hoja2 = $objPHPExcel->createSheet(1);
$hoja2->setTitle("Metadatos");

$hoja2->mergeCells("A1:B1");
$hoja2->setCellValue("A1", "INFORMACIÓN DEL REPORTE — CUENTAS POR COBRAR");
$hoja2->getStyle("A1")->applyFromArray($estiloMetaTitulo);
$hoja2->getRowDimension(1)->setRowHeight(26);

$estiloMetaSeccion = array(
    "font" => array("bold" => true, "size" => 11, "color" => array("rgb" => "FFFFFF")),
    "fill" => array("type" => PHPExcel_Style_Fill::FILL_SOLID, "color" => array("rgb" => "5D6D7E")),
    "alignment" => array("vertical" => PHPExcel_Style_Alignment::VERTICAL_CENTER),
);

$metaFilas = array(
    array("_sec", "DATOS DEL REPORTE"),
    array("Reporte", "Cuentas por cobrar — clientes con deuda"),
    array("Fecha de exportación", $fechaHora),
    array("Solicitado por", $nombreSolicitante),
    array("Generado por", $nombreGenerador),
    array("Equipo (PC)", $nombrePc !== "" ? $nombrePc : "No identificado"),
    array("Total clientes", count($clientes)),
    array("Total deuda actual", "S/ " . number_format($totalDeuda, 2, ".", ",")),
    array("Total deuda vencida", "S/ " . number_format($totalVencida, 2, ".", ",")),
    array("Quiénes aparecen", "Solo clientes con saldo pendiente > 0 (documentos tip_mov +, estado PENDIENTE)."),

    array("_sec", "GLOSARIO — QUÉ SIGNIFICA CADA COLUMNA"),
    array("Línea propuesta", "Monto sugerido por cálculos internos del sistema (Inteligencia Comercial). No es una aprobación formal de créditos; sirve como referencia."),
    array("Sin línea propuesta", "Puede quedar vacío si el cliente aún no tiene snapshot/cálculo de línea, o si el motor no generó recomendación. No implica necesariamente mal riesgo."),
    array("Línea aprobada", "Línea registrada/autorizada por Créditos (manual). Si el cliente pertenece a un grupo empresarial, puede regir la línea del grupo."),
    array("Cupo disponible", "Línea de referencia − deuda actual. Prioriza línea aprobada; si no hay, usa la propuesta. Negativo = deuda supera la línea de referencia."),
    array("Calificación / riesgo", "Score de riesgo crediticio del sistema (0–100). Mayor score = mejor comportamiento histórico esperado."),
    array("Categoría", "Clasificación comercial vigente del cliente (o de su grupo). El color de la celda es el definido en el maestro de categorías."),
    array("Deuda actual", "Suma de saldos pendientes a la fecha de exportación."),
    array("Deuda vencida", "Parte de la deuda cuya fecha de vencimiento ya pasó."),
    array("Deuda por vencer", "Parte de la deuda aún no vencida."),
    array("Docs / mora / protesta", "Cantidad de documentos pendientes, vencidos o protestados; días de mora = atraso máximo entre docs vencidos."),
    array("Correo", "Solo se muestra si tiene formato de e-mail válido; si no, se omite."),
    array("Venta del año", "Ventas del año en curso según reglas internas (canales de crédito; excluye contado/showroom según config)."),

    array("_sec", "LEYENDA DE COLORES"),
    array("Riesgo — Excelente (≥90)", "Fondo verde en columna Calificación"),
    array("Riesgo — Bueno (80–89)", "Fondo azul en columna Calificación"),
    array("Riesgo — Aceptable (70–79)", "Fondo celeste en columna Calificación"),
    array("Riesgo — Medio (60–69)", "Fondo ámbar en columna Calificación"),
    array("Riesgo — Alto (<60)", "Fondo rojo en columna Calificación"),
    array("Color categoría", "Usa el color del maestro de categorías comerciales (no es semáforo de mora)"),
    array("Color cupo", "Verde = hay margen; Ámbar = 0; Rojo = sobrepasado (negativo)"),
    array("Color deuda", "Ámbar suave = deuda actual; Rojo = deuda vencida > 0"),
    array("Color docs / mora", "Naranja = docs vencidos; Rojo = protestados; Mora: amarillo 1–30, naranja 31–90, rojo >90"),

    array("_sec", "NOTAS PARA OTRAS ÁREAS"),
    array("Uso del reporte", "Apoyo a supervisión/cobranzas. No reemplaza la evaluación formal de Créditos ni el estado de cuenta operativo."),
    array("Actualización", "Deuda y documentos son en vivo al momento de exportar. Línea/score dependen del último cálculo o registro en el módulo de línea de crédito."),
    array("Nota", "Esta hoja es de solo lectura."),
);

$mapaColorLeyenda = array(
    "Riesgo — Excelente (≥90)" => "ABEBC6",
    "Riesgo — Bueno (80–89)" => "AED6F1",
    "Riesgo — Aceptable (70–79)" => "D6EAF8",
    "Riesgo — Medio (60–69)" => "F9E79F",
    "Riesgo — Alto (<60)" => "F5B7B1",
    "Color cupo" => "ABEBC6",
    "Color deuda" => "FCF3CF",
    "Color docs / mora" => "FAD7A0",
);

$filaMeta = 3;
foreach ($metaFilas as $metaItem) {
    if ($metaItem[0] === "_sec") {
        $hoja2->mergeCells("A" . $filaMeta . ":B" . $filaMeta);
        $hoja2->setCellValue("A" . $filaMeta, $metaItem[1]);
        $hoja2->getStyle("A" . $filaMeta . ":B" . $filaMeta)->applyFromArray($estiloMetaSeccion);
        $hoja2->getRowDimension($filaMeta)->setRowHeight(20);
        $filaMeta++;
        continue;
    }

    $hoja2->setCellValue("A" . $filaMeta, $metaItem[0]);
    $hoja2->setCellValue("B" . $filaMeta, $metaItem[1]);
    $hoja2->getStyle("A" . $filaMeta)->applyFromArray($estiloMetaEtiqueta);
    $hoja2->getStyle("B" . $filaMeta)->applyFromArray($estiloMetaValor);
    $hoja2->getStyle("B" . $filaMeta)->getAlignment()->setWrapText(true);
    $hoja2->getRowDimension($filaMeta)->setRowHeight(-1);

    if (isset($mapaColorLeyenda[$metaItem[0]])) {
        $hex = $mapaColorLeyenda[$metaItem[0]];
        $hoja2->getStyle("A" . $filaMeta)->applyFromArray(
            cxcExportEstiloFondo($hex, true, cxcExportTextoContraste($hex))
        );
    }

    $filaMeta++;
}

$hoja2->getColumnDimension("A")->setWidth(32);
$hoja2->getColumnDimension("B")->setWidth(88);
cxcExportProtegerHojaCompleta($hoja2, "B", $filaMeta - 1);

$objPHPExcel->setActiveSheetIndex(0);

$nombreArchivo = "cuentas_deuda_clientes_" . date("Y-m-d") . ".xls";

while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header("Cache-Control: max-age=0");
header("Pragma: public");

$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
$objWriter->save("php://output");
exit;
