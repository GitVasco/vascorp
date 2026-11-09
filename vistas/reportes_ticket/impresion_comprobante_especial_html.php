<?php
/**
 * Impresión de un comprobante especial (tipo GRATUITA) — mismo formato que la impresión
 * multipágina estilo SAP de facturas/boletas (impresion_bolfact_sap.php): cabecera completa en la
 * hoja 1, cabecera compacta desde la hoja 2, "Hoja X de Y", pie junto a los últimos ítems.
 * Función pura: recibe el comprobante (ModeloComprobantesEspeciales::mdlObtener) y devuelve el HTML.
 * No es la representación oficial: el PDF con QR y hash lo emite eFact.
 */

function ceImpH($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function ceImpMonto($v)
{
    return number_format((float) $v, 2, ".", ",");
}

function ceImpCant($v)
{
    return rtrim(rtrim(number_format((float) $v, 3, ".", ""), "0"), ".");
}

/** Líneas que ocupa una fila de detalle según la longitud de la descripción. */
function ceImpLineasItem($nombre, $charsPorLinea)
{
    $nombre = trim((string) $nombre);
    if ($nombre === "") {
        return 1;
    }
    return max(1, (int) ceil(mb_strlen($nombre, "UTF-8") / max(1, $charsPorLinea)));
}

function ceImpSumLineas(array $items, $desde, $hasta, $charsPorLinea)
{
    $total = 0;
    for ($i = $desde; $i < $hasta; $i++) {
        $total += ceImpLineasItem(isset($items[$i]["nombre"]) ? $items[$i]["nombre"] : "", $charsPorLinea);
    }
    return $total;
}

/**
 * Reparte ítems por capacidad real de líneas (misma lógica que bolfactSapParticionarPorLineas):
 * hoja 1 resta la cabecera completa, hojas 2+ la compacta, y la última hoja lleva ítems + pie juntos.
 */
function ceImpParticionar(array $items, $lineasPagina, $lineasCabP1, $lineasCabP2, $lineasPie, $lineasContinua, $charsPorLinea)
{
    $n = count($items);
    if ($n === 0) {
        return array(array());
    }
    $paginas = array();
    $offset = 0;
    $pageIndex = 0;
    while ($offset < $n) {
        $cab = ($pageIndex === 0) ? $lineasCabP1 : $lineasCabP2;
        $restLines = ceImpSumLineas($items, $offset, $n, $charsPorLinea);
        if ($restLines <= ($lineasPagina - $cab - $lineasPie)) {
            $paginas[] = array_slice($items, $offset);
            break;
        }
        $maxDetail = $lineasPagina - $cab - $lineasContinua;
        $take = 0;
        $used = 0;
        for ($i = $offset; $i < $n; $i++) {
            $li = ceImpLineasItem(isset($items[$i]["nombre"]) ? $items[$i]["nombre"] : "", $charsPorLinea);
            if ($used + $li > $maxDetail && $take > 0) {
                break;
            }
            $used += $li;
            $take++;
        }
        if ($take <= 0) {
            $take = 1;
        }
        $paginas[] = array_slice($items, $offset, $take);
        $offset += $take;
        $pageIndex++;
    }
    return empty($paginas) ? array(array()) : $paginas;
}

function ceImpFilaItem($v)
{
    return '<tr>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . ceImpH($v["modelo"]) . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . ceImpH($v["cantidad"]) . '</td>
            <td style="width:50px; border-right: 1px solid; text-align:center;">' . ceImpH($v["unidad"]) . '</td>
            <td style="width:250px; border-right: 1px solid;">' . ceImpH($v["nombre"]) . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . ceImpH($v["precio"]) . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . ceImpH($v["dscto1"]) . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . ceImpH($v["total"]) . '</td>
        </tr>';
}

/** Devuelve el HTML completo de la impresión. $g = ModeloComprobantesEspeciales::mdlObtener(). */
function ceImpresionHtml($g, $baseCss = "css/ticket_v9.css", $baseImg = "../../vistas/img/plantilla/")
{
    $esFactura = $g["tipo_doc"] === "01";
    $tipoDocumento = $esFactura ? "FACTURA" : "BOLETA";
    $partes = explode("-", $g["documento"]);
    $docNumFmt = $partes[0] . "-" . $partes[1];
    $moneda = "S/";
    $gravado = (int) $g["afectacion"] >= 11 && (int) $g["afectacion"] <= 16;
    $tipDocCli = $g["receptor_tipo_doc"] === "6" ? "RUC" : "DNI";
    $ciudad = trim(implode(" - ", array_filter(array($g["receptor_dpto"], $g["receptor_prov"], $g["receptor_dist"]))));
    $fecha = date("d/m/Y", strtotime($g["fecha_emision"]));
    $afeTxt = !empty($g["afectacion_texto"]) ? $g["afectacion_texto"] : $g["afectacion"];
    $obs = ($g["observaciones"] !== null && $g["observaciones"] !== "") ? $g["observaciones"] : $g["motivo"];

    // Ítems con las claves que usa la tabla de detalle de las facturas
    $modelo = array();
    $unidades = 0;
    foreach ($g["items"] as $it) {
        $unidades += (float) $it["cantidad"];
        $modelo[] = array(
            "modelo" => $it["codigo"],
            "cantidad" => ceImpCant($it["cantidad"]),
            "unidad" => $it["unidad_cod"],
            "nombre" => $it["descripcion"],
            "precio" => ceImpMonto($it["valor_ref_unit"]),
            "dscto1" => "0.00",
            "total" => ceImpMonto($it["valor_venta"]),
        );
    }

    // Paginación por líneas (mismas constantes que el formato SAP si están definidas en config)
    $LINEAS_PAGINA = defined("BOLFACT_SAP_LINEAS_PAGINA") ? (int) BOLFACT_SAP_LINEAS_PAGINA : 54;
    $LINEAS_CAB_P1 = defined("BOLFACT_SAP_LINEAS_CAB_P1") ? (int) BOLFACT_SAP_LINEAS_CAB_P1 : 20;
    $LINEAS_CAB_P2 = defined("BOLFACT_SAP_LINEAS_CAB_P2") ? (int) BOLFACT_SAP_LINEAS_CAB_P2 : 5;
    $LINEAS_PIE = 13; // pie más corto que el de una venta (sin cuentas, aviso ni CANCELADO)
    $LINEAS_CONTINUA = defined("BOLFACT_SAP_LINEAS_CONTINUA") ? (int) BOLFACT_SAP_LINEAS_CONTINUA : 1;
    $CHARS_LINEA_DESC = defined("BOLFACT_SAP_CHARS_LINEA_DESC") ? (int) BOLFACT_SAP_CHARS_LINEA_DESC : 38;

    $cabeceraCompleta = '<table border="0">
        <tr>
            <td style="width:150px;">
                <div style="text-align:center;">
                    <img src="' . $baseImg . 'jackyform_paloma2.png" width="150" height="75" alt="">
                </div>
            </td>
            <td style="width:350px;">
                <div>
                    <img src="' . $baseImg . 'jackyform_letras.png" width="150" height="75" style="margin-left:auto;margin-right:auto;display:block;" alt="">
                    <p style="margin:0;text-align:center;"><b>Corporación Vasco S.A.C.</b></p>
                    <p style="margin:0;text-align:center;">Cal.Santo Toribio Nro. 259 - Urb Santa Luisa 1ra Etapa</p>
                    <p style="margin:0;text-align:center;">San Martin de Porres - Lima - Lima</p>
                    <p style="margin:0;text-align:center;">Telfs: 537-2501/536-4024 Cel 964570509 / 964543475</p>
                    <p style="margin:0;text-align:center;">Página Web: www.jackyform.com.pe</p>
                    <p style="margin:0;text-align:center;">Email: gerenciadeventas@jackyform.com.pe</p>
                    <p style="margin:0;text-align:center;">cuentascorrientes@jackyform.com.pe</p>
                    <p style="margin:0;text-align:center;"><i>Confecciones de Prendas de Ropa Interior</i></p>
                </div>
            </td>
            <td style="width:250px; height:100px; border:1px solid black;">
                <p style="text-align:center;margin:0;"><b>RUC: 20513613939</b></p>
                <p style="text-align:center;margin:0;"><b>' . $tipoDocumento . ' DE VENTA ELECTRONICA</b></p>
                <p style="text-align:center;margin:0;"><b>Nro.: ' . ceImpH($docNumFmt) . '</b></p>
                <p style="text-align:center;margin:4px 0 0;">Hoja {{HOJA}} de {{TOTAL}}</p>
            </td>
        </tr>
    </table>';

    $cabeceraCompacta = '<table border="0">
        <tr>
            <td style="width:420px; vertical-align:middle;">
                <p style="margin:0;"><b>Corporación Vasco S.A.C.</b></p>
                <p style="margin:0;">Cliente: ' . ceImpH($g["receptor_nombre"]) . '</p>
                <p style="margin:0;font-size:11px;">' . $tipDocCli . ': ' . ceImpH($g["receptor_doc"]) . ' | Emisión: ' . $fecha . ' | Afectación IGV: ' . ceImpH($afeTxt) . '</p>
            </td>
            <td style="width:300px; border:1px solid black; text-align:center; vertical-align:middle;">
                <p style="margin:0;"><b>' . $tipoDocumento . ' DE VENTA ELECTRONICA</b></p>
                <p style="margin:0;"><b>Nro.: ' . ceImpH($docNumFmt) . '</b></p>
                <p style="margin:0;">Hoja {{HOJA}} de {{TOTAL}}</p>
            </td>
        </tr>
    </table>';

    $cliente = '<table>
        <tr><td style="width:80px;">Cliente:</td><td>' . ceImpH($g["receptor_nombre"]) . '</td></tr>
        <tr><td style="width:80px;">Dirección:</td><td>' . ceImpH($g["receptor_direccion"]) . '</td></tr>
        <tr><td style="width:80px;">Ciudad:</td><td>' . ceImpH($ciudad) . '</td></tr>
        <tr><td style="width:80px;">' . $tipDocCli . '</td><td>' . ceImpH($g["receptor_doc"]) . '</td></tr>
        <tr><td style="width:80px;">Motivo:</td><td>' . ceImpH($g["motivo"]) . '</td></tr>
        <tr><td style="width:80px;">Usuario:</td><td>' . ceImpH($g["usuario_registro"]) . '</td></tr>
    </table>';

    $feccon = '<table>
        <tr>
            <th style="width:100px; border-radius:10px; border:2px solid #000; padding:1px;">Fecha Emisión</th>
            <th style="width:120px; border-radius:10px; border:2px solid #000; padding:1px;">Condición de Pago</th>
            <th style="width:80px; border-radius:10px; border:2px solid #000; padding:1px;">O. Compra</th>
            <th style="width:150px; border-radius:10px; border:2px solid #000; padding:1px;">Fecha de Vencimiento</th>
            <th style="width:270px; border-radius:10px; border:2px solid #000; padding:1px;">Afectación IGV</th>
        </tr>
        <tr>
            <td style="text-align:center;">' . $fecha . '</td>
            <td style="text-align:center;">CONTADO</td>
            <td style="text-align:center;"></td>
            <td style="text-align:center;"></td>
            <td style="text-align:center;">' . ceImpH($afeTxt) . '</td>
        </tr>
    </table>';

    $th = 'border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;';
    $cabDet = '<table>
        <tr>
            <th style="width:80px; ' . $th . '">CODIGO</th>
            <th style="width:80px; ' . $th . '">CANT.</th>
            <th style="width:50px; ' . $th . '">UND</th>
            <th style="width:250px; ' . $th . '">DESCRIPCIÓN</th>
            <th style="width:80px; ' . $th . '">V.REF.</th>
            <th style="width:80px; ' . $th . '">DSCTO</th>
            <th style="width:80px; ' . $th . '">VALOR</th>
        </tr>
    </table>';

    $fila = function ($txt, $val, $negrita = false) use ($moneda) {
        $b1 = $negrita ? '<b>' : '';
        $b2 = $negrita ? '</b>' : '';
        return '<tr>
                        <td style="width:170px;">' . $b1 . $txt . ' ' . $moneda . $b2 . '</td>
                        <td style="width:50px; text-align:right;">' . $b1 . $val . $b2 . '</td>
                    </tr>';
    };

    $pie = '<table class="pie-documento">
        <tr>
            <td style="width:500px; border-radius:10px; border:1px solid #000; padding:1px; vertical-align:top;">
                <p style="margin:2px 0;">Observaciones</p>
                <p style="margin:2px 0;">Nro. Unidades: ' . ceImpCant($unidades) . '</p>
                <p style="margin:2px 0;">' . ceImpH($obs) . '</p>
            </td>
            <td style="width:220px; border-radius:10px; border:1px solid #000; padding:1px;">
                <table>
                    ' . $fila('Op. Gravadas', '0.00')
                      . $fila('Op. Inafecta', '0.00')
                      . $fila('Op. Exonerada', '0.00')
                      . $fila('Total Op. Gratuitas', ceImpMonto($g["total_gratuito"]), true)
                      . $fila('Descuentos Totales', '0.00')
                      . $fila('Sub Totales', '0.00')
                      . $fila('ISC', '0.00')
                      . $fila('IGV' . ($gravado ? ' 18%' : ''), ceImpMonto($gravado ? $g["igv_informativo"] : 0))
                      . $fila('TOTAL', '0.00', true) . '
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="width:720px; border-radius:5px; border:1px solid #000; padding:2px;">Son: CERO Y 00/100 SOLES</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size:13px; padding-top:4px;"><b>TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE</b></td>
        </tr>
        <tr>
            <td colspan="2" class="pie-legal">Representación Impresa del Documento Electronico, consulte en www.efact.com</td>
        </tr>
        <tr>
            <td colspan="2" class="pie-legal">Vista previa interna del sistema: el PDF oficial (con código QR y hash) lo emite eFact.</td>
        </tr>
    </table>';

    $paginas = ceImpParticionar($modelo, $LINEAS_PAGINA, $LINEAS_CAB_P1, $LINEAS_CAB_P2, $LINEAS_PIE, $LINEAS_CONTINUA, $CHARS_LINEA_DESC);
    $totalPaginas = count($paginas);

    $html = '<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>' . ceImpH($g["documento"]) . '</title>
    <link href="' . $baseCss . '" rel="stylesheet" type="text/css">
    <style>
        @media print {
            .pagina { page-break-after: always; }
            .pagina:last-child { page-break-after: auto; }
            .bloque-final { page-break-inside: avoid; break-inside: avoid; }
        }
        .bloque-final { page-break-inside: avoid; break-inside: avoid; }
        .pie-documento .pie-legal { padding-top: 4px; font-size: 11px; }
        .pagina { box-sizing: border-box; }
        .pagina-detalle { min-height: 0; }
        .continuar-hoja { text-align: center; margin: 8px 0 0; font-size: 12px; font-style: italic; }
        .tabla-items td { font-size: 12px; line-height: 1.15; padding: 1px 2px; vertical-align: top; }
    </style>
</head>

<body>
    <div class="zona_impresion">';
    foreach ($paginas as $indice => $itemsPagina) {
        $esUltima = ($indice === $totalPaginas - 1);
        $esPrimera = ($indice === 0);
        $html .= '<div class="' . ($esUltima ? 'pagina pagina-final' : 'pagina pagina-detalle') . '">';
        $hojas = array('{{HOJA}}', '{{TOTAL}}');
        $nums = array($indice + 1, $totalPaginas);
        if ($esPrimera) {
            $html .= str_replace($hojas, $nums, $cabeceraCompleta) . $cliente . $feccon;
        } else {
            $html .= str_replace($hojas, $nums, $cabeceraCompacta);
        }
        $html .= $cabDet;
        if ($esUltima) {
            $html .= '<div class="bloque-final">';
        }
        $html .= '<table class="tabla-items">';
        foreach ($itemsPagina as $v) {
            $html .= ceImpFilaItem($v);
        }
        $html .= '</table>';
        if ($esUltima) {
            $html .= $pie . '</div>';
        }
        if (!$esUltima && $totalPaginas > 1) {
            $html .= '<p class="continuar-hoja">Continúa en la siguiente hoja</p>';
        }
        $html .= '</div>';
    }
    $html .= '
    </div>
</body>

</html>';
    return $html;
}
