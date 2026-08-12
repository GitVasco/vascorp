<?php
/**
 * Impresión de pedido — formato nuevo (v2).
 * Activar con PEDIDO_IMPRESION = "nuevo" en controladores/config.php
 * Entry point: impresion_pedido.php (enruta aquí).
 */

if (!defined("PEDIDO_IMPRESION_V2_LOADED")) {
    define("PEDIDO_IMPRESION_V2_LOADED", true);
}

require_once __DIR__ . "/../../controladores/config.php";
require_once __DIR__ . "/../../controladores/pedidos.controlador.php";
require_once __DIR__ . "/../../modelos/pedidos.modelo.php";
require_once __DIR__ . "/../../controladores/decisiones-credito.config.php";
require_once __DIR__ . "/../../modelos/decisiones-credito.modelo.php";

date_default_timezone_set("America/Lima");

$codigo = isset($_GET["codigo"]) ? trim((string) $_GET["codigo"]) : "";
if ($codigo === "" || !ctype_digit($codigo)) {
    echo "Pedido no válido.";
    exit;
}

$respuesta = ControladorPedidos::ctrPedidoImpresionCab($codigo);
if (!$respuesta) {
    echo "Pedido no encontrado.";
    exit;
}

$moneda = (isset($respuesta["lista"]) && $respuesta["lista"] == "precio1") ? " $ " : " S/ ";
$totales = ControladorPedidos::ctrPedidoImpresionTotales($codigo);
$pedidos = controladorPedidos::ctrMostraPedidosCabecera($codigo);

$FILAS_POR_HOJA = 46; // margen para cabecera + pie; evita hoja vacía al final

$articulos = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 1000);
if (!is_array($articulos)) {
    $articulos = array();
}

/**
 * Agrupa filas consecutivas del mismo modelo (colores + separador 99).
 */
function pedidoV2AgruparPorModelo(array $filas)
{
    $grupos = array();
    $buffer = array();
    $modeloActual = null;

    foreach ($filas as $fila) {
        $modelo = isset($fila["modelo"]) ? (string) $fila["modelo"] : "";
        if ($modeloActual === null) {
            $modeloActual = $modelo;
        }
        if ($modelo !== $modeloActual) {
            if (!empty($buffer)) {
                $grupos[] = $buffer;
            }
            $buffer = array();
            $modeloActual = $modelo;
        }
        $buffer[] = $fila;
    }

    if (!empty($buffer)) {
        $grupos[] = $buffer;
    }

    return $grupos;
}

/**
 * Pagina sin cortar un modelo: si no cabe completo, pasa entero a la hoja siguiente.
 * Si un solo modelo supera el máximo, igual va completo (no se parte).
 *
 * @return array[] lista de hojas; cada hoja es lista de filas
 */
function pedidoV2HojasSinCortarModelo(array $filas, $maxFilas)
{
    $maxFilas = max(1, (int) $maxFilas);
    $grupos = pedidoV2AgruparPorModelo($filas);
    $hojas = array();
    $hojaActual = array();
    $filasEnHoja = 0;

    foreach ($grupos as $grupo) {
        $n = count($grupo);
        if ($n <= 0) {
            continue;
        }

        if ($filasEnHoja > 0 && ($filasEnHoja + $n) > $maxFilas) {
            $hojas[] = $hojaActual;
            $hojaActual = array();
            $filasEnHoja = 0;
        }

        foreach ($grupo as $fila) {
            $hojaActual[] = $fila;
        }
        $filasEnHoja += $n;

        if ($filasEnHoja >= $maxFilas) {
            $hojas[] = $hojaActual;
            $hojaActual = array();
            $filasEnHoja = 0;
        }
    }

    if (!empty($hojaActual)) {
        $hojas[] = $hojaActual;
    }

    if (empty($hojas)) {
        $hojas[] = array();
    }

    return $hojas;
}

$hojasDatos = pedidoV2HojasSinCortarModelo($articulos, $FILAS_POR_HOJA);
$hojas = count($hojasDatos);

$fechaEmision = !empty($respuesta["fecha"])
    ? date("d/m/Y", strtotime($respuesta["fecha"]))
    : "—";

$fechaAprobacionRaw = null;
if (class_exists("ModeloDecisionesCredito")
    && method_exists("ModeloDecisionesCredito", "mdlFechaAprobacionPedido")
) {
    $fechaAprobacionRaw = ModeloDecisionesCredito::mdlFechaAprobacionPedido((int) $codigo);
}
$fechaAprobacion = $fechaAprobacionRaw
    ? date("d/m/Y", strtotime($fechaAprobacionRaw))
    : "—";

$mostrarSello = function_exists("dcPedidoMostrarSelloControlCreditoImpresion")
    && dcPedidoMostrarSelloControlCreditoImpresion($codigo);
$selloHtml = "";
if ($mostrarSello && function_exists("dcHtmlSelloControlCreditoImpresionDireccion")) {
    $selloHtml = dcHtmlSelloControlCreditoImpresionDireccion();
}

/**
 * Vacía cantidades <= 0 como el formato antiguo.
 */
function pedidoV2CantidadCelda($valor)
{
    if ($valor === "" || $valor === null) {
        return "";
    }
    if ((float) $valor <= 0) {
        return "";
    }

    return $valor;
}

/**
 * Precio mostrado (USD lista precio1; resto con IGV).
 */
function pedidoV2PrecioTexto($lista, $precio, $moneda)
{
    $precioNum = (float) $precio;
    $mostrar = ($lista == "precio1") ? $precioNum : ($precioNum * 1.18);

    return $moneda . str_pad(number_format($mostrar, 2), 5, " ", STR_PAD_LEFT);
}

function pedidoV2Esc($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}

/** Anchos fijos compartidos por cabecera, cuerpo y totales (evita desfase entre hojas). */
function pedidoV2Colgroup()
{
    echo '<colgroup>';
    echo '<col class="c-modelo" style="width:18%">';
    echo '<col class="c-color" style="width:16%">';
    for ($i = 0; $i < 8; $i++) {
        echo '<col class="c-talla" style="width:7%">';
    }
    echo '<col class="c-total" style="width:10%">';
    echo '</colgroup>';
}

function pedidoV2CabeceraHoja(array $respuesta, $fechaEmision, $fechaAprobacion, $selloHtml)
{
    $direccion = trim(isset($respuesta["direccion"]) ? (string) $respuesta["direccion"] : "");
    $nomUbi = trim(isset($respuesta["nom_ubi"]) ? (string) $respuesta["nom_ubi"] : "");
    $direccionCompleta = $direccion;
    if ($nomUbi !== "" && stripos($direccion, $nomUbi) === false) {
        $direccionCompleta = $direccion !== "" ? ($direccion . " — " . $nomUbi) : $nomUbi;
    }

    $agenciaId = isset($respuesta["agencia"]) ? (int) $respuesta["agencia"] : 0;
    $agenciaNombre = trim(isset($respuesta["nom_agencia"]) ? (string) $respuesta["nom_agencia"] : "");
    $agenciaTxt = "";
    if ($agenciaId > 0) {
        $agenciaTxt = ($agenciaNombre !== "")
            ? ($agenciaId . " - " . $agenciaNombre)
            : (string) $agenciaId;
    }

    $tipoDoc = trim(isset($respuesta["tipo_doc"]) ? (string) $respuesta["tipo_doc"] : "");
    $documento = trim(isset($respuesta["documento"]) ? (string) $respuesta["documento"] : "");
    $docTxt = ($tipoDoc !== "" || $documento !== "")
        ? trim($tipoDoc . ": " . $documento)
        : "";

    echo '<table class="pedido-v2-cab">';

    echo '<tr class="fila-sep">';
    echo '<td class="empresa">CORPORACION VASCO S.A.C.</td>';
    echo '<td class="pedido-nro">Nº PEDIDO&nbsp;&nbsp;' . pedidoV2Esc($respuesta["pedido"]) . '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td class="pedido-v2-celda-texto"><span class="lbl">Cliente:</span> '
        . '<span class="pedido-v2-truncar" title="' . pedidoV2Esc($respuesta["nombre"]) . '">'
        . pedidoV2Esc($respuesta["nombre"]) . '</span>';
    if ($docTxt !== "") {
        echo ' &nbsp;·&nbsp; <span class="lbl">' . pedidoV2Esc($docTxt) . '</span>';
    }
    echo '</td>';
    echo '<td class="meta"><span class="lbl">Emisión:</span> ' . pedidoV2Esc($fechaEmision) . '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td class="pedido-v2-celda-direccion"><span class="lbl">Dirección:</span> '
        . pedidoV2Esc($direccionCompleta) . '</td>';
    echo '<td class="meta"><span class="lbl">Aprobación:</span> ' . pedidoV2Esc($fechaAprobacion) . '</td>';
    echo '</tr>';

    // Vendedor a la izquierda; AVISAR debajo de Emisión/Aprobación (misma fila)
    echo '<tr' . ($agenciaTxt === "" ? ' class="fila-sep"' : "") . '>';
    echo '<td>';
    echo '<span class="lbl">Vendedor:</span> ' . pedidoV2Esc($respuesta["vendedor"]);
    echo ' &nbsp;·&nbsp; <span class="lbl">Cod. cliente:</span> ' . pedidoV2Esc($respuesta["codigo"]);
    echo '</td>';
    if ($selloHtml !== "") {
        echo '<td class="meta pedido-v2-sello-meta">' . $selloHtml . '</td>';
    } else {
        echo '<td></td>';
    }
    echo '</tr>';

    if ($agenciaTxt !== "") {
        echo '<tr class="fila-sep">';
        echo '<td colspan="2"><span class="lbl">Agencia:</span> ' . pedidoV2Esc($agenciaTxt) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

function pedidoV2TheadTallas()
{
    echo '<thead>';
    echo '<tr>';
    echo '<th class="col-modelo"></th><th class="col-color"></th>';
    echo '<th class="col-talla">S</th><th class="col-talla">M</th><th class="col-talla">L</th>';
    echo '<th class="col-talla">XL</th><th class="col-talla">XXL</th><th class="col-talla">XS</th>';
    echo '<th class="col-talla"></th><th class="col-talla"></th><th class="col-total"></th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th class="col-modelo"></th><th class="col-color"></th>';
    echo '<th class="col-talla">28</th><th class="col-talla">30</th><th class="col-talla">32</th>';
    echo '<th class="col-talla">34</th><th class="col-talla">36</th><th class="col-talla">38</th>';
    echo '<th class="col-talla">40</th><th class="col-talla">42</th><th class="col-total"></th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th class="col-modelo">Modelo</th><th class="col-color">Color</th>';
    echo '<th class="col-talla">3</th><th class="col-talla">4</th><th class="col-talla">6</th>';
    echo '<th class="col-talla">8</th><th class="col-talla">10</th><th class="col-talla">12</th>';
    echo '<th class="col-talla">14</th><th class="col-talla">16</th><th class="col-total">TOTAL</th>';
    echo '</tr>';
    echo '</thead>';
}

function pedidoV2FilaArticulo(array $value, array $respuesta, $moneda)
{
    if ((string) $value["cod_color"] === "99") {
        // 11 celdas vacías (no colspan) para no romper anchos de columna
        echo '<tr class="pedido-v2-sep-modelo">';
        for ($i = 0; $i < 11; $i++) {
            echo '<td></td>';
        }
        echo '</tr>';
        return;
    }

    $modeloPad = str_pad((string) $value["modelo"], 10, "-", STR_PAD_RIGHT);
    $precioTxt = pedidoV2PrecioTexto(
        isset($respuesta["lista"]) ? $respuesta["lista"] : "",
        isset($value["precio"]) ? $value["precio"] : 0,
        $moneda
    );

    echo '<tr>';
    echo '<td class="col-modelo"><b>' . pedidoV2Esc($modeloPad) . '</b>'
        . pedidoV2Esc($precioTxt) . '</td>';
    echo '<td class="col-color">' . pedidoV2Esc($value["color"]) . '</td>';
    for ($t = 1; $t <= 8; $t++) {
        $key = "t" . $t;
        echo '<td class="col-talla">' . pedidoV2Esc(pedidoV2CantidadCelda($value[$key])) . '</td>';
    }
    echo '<td class="col-total">' . pedidoV2Esc($value["total"]) . '</td>';
    echo '</tr>';
}

function pedidoV2PieFinal(array $totales, array $pedidos, $moneda)
{
    echo '<table class="pedido-v2-grid pedido-v2-totales">';
    pedidoV2Colgroup();
    echo '<tr>';
    echo '<td class="col-modelo">TOTALES</td>';
    echo '<td class="col-color">PEDIDO</td>';
    for ($t = 1; $t <= 8; $t++) {
        $key = "t" . $t;
        $val = isset($totales[$key]) ? number_format((float) $totales[$key], 0) : "0";
        echo '<td class="col-talla">' . $val . '</td>';
    }
    $totalU = isset($totales["total"]) ? number_format((float) $totales["total"], 0) : "0";
    echo '<td class="col-total">' . $totalU . '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<table class="pedido-v2-pie">';
    echo '<tr>';
    echo '<td class="lbl">TOTAL' . pedidoV2Esc($moneda) . '</td>';
    echo '<td>' . number_format(isset($pedidos["total"]) ? (float) $pedidos["total"] : 0, 2) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td class="lbl">Forma de pago</td>';
    echo '<td>' . pedidoV2Esc(isset($pedidos["descripcion"]) ? $pedidos["descripcion"] : "") . '</td>';
    echo '</tr>';
    echo '</table>';
}

?>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Pedido <?php echo pedidoV2Esc($respuesta["pedido"]); ?></title>
    <link href="css/ticket_pedido_v2.css" rel="stylesheet" type="text/css">
</head>
<body onload="window.print();">
<div class="zona_impresion">
<?php

for ($h = 0; $h < $hojas; $h++) {
    $filasHoja = isset($hojasDatos[$h]) ? $hojasDatos[$h] : array();
    $esUltima = ($h === $hojas - 1);

    echo '<div class="pedido-v2-hoja' . ($esUltima ? ' pedido-v2-hoja-ultima' : '') . '">';

    pedidoV2CabeceraHoja(
        $respuesta,
        $fechaEmision,
        $fechaAprobacion,
        $selloHtml
    );

    // Una sola tabla: thead + tbody → mismos anchos en todas las hojas
    echo '<table class="pedido-v2-grid">';
    pedidoV2Colgroup();
    pedidoV2TheadTallas();
    echo '<tbody>';
    foreach ($filasHoja as $value) {
        pedidoV2FilaArticulo($value, $respuesta, $moneda);
    }
    echo '</tbody>';
    echo '</table>';

    if ($esUltima) {
        echo '<div class="pedido-v2-pie-wrap">';
        pedidoV2PieFinal(
            is_array($totales) ? $totales : array(),
            is_array($pedidos) ? $pedidos : array(),
            $moneda
        );
        echo '</div>';
    }

    echo '<div class="pedido-v2-hoja-num">Hoja ' . ($h + 1) . ' / ' . (int) $hojas . '</div>';

    echo '</div>';
}

?>
</div>
</body>
</html>
