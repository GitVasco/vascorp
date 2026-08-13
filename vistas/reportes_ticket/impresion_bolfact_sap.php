<?php
/**
 * Impresión factura/boleta — formato multipágina estilo SAP.
 * Activar con BOLFACT_IMPRESION = "sap" en controladores/config.php
 */
if (!defined("BOLFACT_SAP_LOADED")) {
    define("BOLFACT_SAP_LOADED", true);
}

require_once __DIR__ . "/../../controladores/config.php";
?>
<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link href="css/ticket_v9.css" rel="stylesheet" type="text/css">
    <style>
        @media print {
            .pagina {
                page-break-after: always;
            }

            .pagina:last-child {
                page-break-after: auto;
            }

            .bloque-final {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }

        .bloque-final {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .pie-documento .aviso-importante {
            font-size: 12px;
            text-align: justify;
            padding: 2px 0;
            line-height: 1.25;
        }

        .pie-documento .pie-firma {
            text-align: center;
            padding: 6px 0 2px;
        }

        .pie-documento .pie-legal {
            padding-top: 4px;
            font-size: 11px;
        }

        .pagina {
            box-sizing: border-box;
        }

        .pagina-detalle {
            min-height: 0;
        }

        .continuar-hoja {
            text-align: center;
            margin: 8px 0 0;
            font-size: 12px;
            font-style: italic;
        }

        .tabla-items td {
            font-size: 12px;
            line-height: 1.15;
            padding: 1px 2px;
            vertical-align: top;
        }
    </style>
</head>

<body>
    <?php
    require_once "../../controladores/facturacion.controlador.php";
    require_once "../../modelos/facturacion.modelo.php";
    require_once "../../extensiones/cantidad_en_letras.php";

    $tipo = $_GET["tipo"];
    $documento = $_GET["documento"];
    $venta = ControladorFacturacion::ctrMostrarVentaImpresion($documento, $tipo);

    $moneda = $venta["tipo_moneda"] == "1" ? "S/" : "$";
    $exportacion = $venta["exportacion"];

    $gravadas = $exportacion == 1 ? "0.00" : number_format($venta["neto"], 2);
    $exonerada = $exportacion == 1 ? number_format($venta["neto"], 2) : "0.00";

    switch ($venta["tip_doc_cli"]) {
        case '1':
            $tip_doc_cli = "DNI";
            break;
        case '6':
            $tip_doc_cli = "RUC";
            break;
        default:
            $tip_doc_cli = "Sin Doc.";
            break;
    }

    $documentoCliente = $exportacion == 1 ? "00000000" : $venta["dni"];
    $docNumFmt = substr($venta["documento"], 0, 4) . "-" . substr($venta["documento"], 4, 12);

    $anno = date("Y", strtotime($venta["fecha_emision"]));
    $tabla = "movimientosjf_" . $anno;

    $modelo = ControladorFacturacion::ctrMostrarModeloImpresionV2($tabla, $documento, $tipo, 0, 100);
    $subtotal = $venta["neto"] - $venta["dscto"];

    $monto_letra = $venta["tipo_moneda"] == "1"
        ? CantidadEnLetra($venta["total"])
        : str_replace("SOLES", "DOLARES AMERICANOS", CantidadEnLetra($venta["total"]));

    $cantidaUnidades = 0;
    foreach ($modelo as $fila) {
        $cantidaUnidades += $fila["cantidad"];
    }

    $ordenCompraImp = "";
    if (
        (!function_exists("feCsvOrdenCompraActiva") || feCsvOrdenCompraActiva())
        && !empty($venta["orden_compra"])
    ) {
        $ordenCompraImp = htmlspecialchars(trim($venta["orden_compra"]));
    }

    $retencionImp = ControladorFacturacion::ctrRetencionIgvImpresion($venta);
    $htmlRetencionObs = "";
    $htmlRetencionTotales = "";
    if (!empty($retencionImp["aplica"])) {
        $pctRet = rtrim(rtrim(number_format((float) $retencionImp["factor"] * 100, 2, ".", ""), "0"), ".");
        $htmlRetencionObs = '<p style="margin:2px 0;"><b>Retención IGV '
            . $pctRet . '%</b> — Base: ' . $moneda . ' ' . $retencionImp["base"]
            . ' | Monto: ' . $moneda . ' ' . $retencionImp["monto"] . '</p>';
        $htmlRetencionTotales = '<tr>
                        <td style="width:170px;">Retención IGV ' . $pctRet . '% ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . $retencionImp["monto"] . '</td>
                    </tr>';
    }

    // --- Paginación por líneas (no por cantidad fija de ítems) ---
    $LINEAS_PAGINA = defined("BOLFACT_SAP_LINEAS_PAGINA") ? (int) BOLFACT_SAP_LINEAS_PAGINA : 54;
    $LINEAS_CAB_P1 = defined("BOLFACT_SAP_LINEAS_CAB_P1") ? (int) BOLFACT_SAP_LINEAS_CAB_P1 : 20;
    $LINEAS_CAB_P2 = defined("BOLFACT_SAP_LINEAS_CAB_P2") ? (int) BOLFACT_SAP_LINEAS_CAB_P2 : 5;
    $LINEAS_PIE_BASE = defined("BOLFACT_SAP_LINEAS_PIE") ? (int) BOLFACT_SAP_LINEAS_PIE : 17;
    $LINEAS_CONTINUA = defined("BOLFACT_SAP_LINEAS_CONTINUA") ? (int) BOLFACT_SAP_LINEAS_CONTINUA : 1;
    $CHARS_LINEA_DESC = defined("BOLFACT_SAP_CHARS_LINEA_DESC") ? (int) BOLFACT_SAP_CHARS_LINEA_DESC : 38;

    /**
     * Líneas que ocupa una fila de detalle según longitud de descripción.
     */
    function bolfactSapLineasItem($nombre, $charsPorLinea)
    {
        $nombre = trim((string) $nombre);
        if ($nombre === "") {
            return 1;
        }

        return max(1, (int) ceil(mb_strlen($nombre, "UTF-8") / max(1, $charsPorLinea)));
    }

    /**
     * Pie: base + extras por retención o monto en letras largo.
     */
    function bolfactSapLineasPie($lineasBase, $montoLetra, $tieneRetencion)
    {
        $lineas = max(1, (int) $lineasBase);
        if ($tieneRetencion) {
            $lineas++;
        }
        if (mb_strlen((string) $montoLetra, "UTF-8") > 80) {
            $lineas++;
        }

        return $lineas;
    }

    function bolfactSapSumLineasItems(array $items, $desde, $hasta, $charsPorLinea)
    {
        $total = 0;
        for ($i = $desde; $i < $hasta; $i++) {
            $nombre = isset($items[$i]["nombre"]) ? $items[$i]["nombre"] : "";
            $total += bolfactSapLineasItem($nombre, $charsPorLinea);
        }

        return $total;
    }

    /**
     * Reparte ítems por capacidad real de líneas:
     * - Hoja 1: resta cabecera completa.
     * - Hojas 2+: resta cabecera compacta.
     * - Última hoja: ítems + pie en el mismo bloque (nunca pie solo).
     */
    function bolfactSapParticionarPorLineas(
        array $items,
        $lineasPagina,
        $lineasCabP1,
        $lineasCabP2,
        $lineasPie,
        $lineasContinua,
        $charsPorLinea
    ) {
        $n = count($items);
        if ($n === 0) {
            return array(array());
        }

        $paginas = array();
        $offset = 0;
        $pageIndex = 0;

        while ($offset < $n) {
            $cab = ($pageIndex === 0) ? $lineasCabP1 : $lineasCabP2;
            $restLines = bolfactSapSumLineasItems($items, $offset, $n, $charsPorLinea);

            if ($restLines <= ($lineasPagina - $cab - $lineasPie)) {
                $paginas[] = array_slice($items, $offset);
                break;
            }

            $maxDetail = $lineasPagina - $cab - $lineasContinua;
            $take = 0;
            $used = 0;

            for ($i = $offset; $i < $n; $i++) {
                $nombre = isset($items[$i]["nombre"]) ? $items[$i]["nombre"] : "";
                $li = bolfactSapLineasItem($nombre, $charsPorLinea);
                if ($used + $li > $maxDetail && $take > 0) {
                    break;
                }
                $used += $li;
                $take++;
            }

            if ($take <= 0) {
                $take = 1;
            }

            while ($take > 0) {
                $after = $n - $offset - $take;
                if ($after === 0) {
                    $take--;
                    continue;
                }

                $afterLines = bolfactSapSumLineasItems($items, $offset + $take, $n, $charsPorLinea);
                if ($afterLines <= ($lineasPagina - $lineasCabP2 - $lineasPie)) {
                    break;
                }

                if ($take <= 1) {
                    break;
                }

                break;
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

    function renderFilaItemSap($value)
    {
        return '<tr>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
            <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
            <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
        </tr>';
    }

    $cabeceraCompleta = '<table border="0">
        <tr>
            <td style="width:150px;">
                <div style="text-align:center;">
                    <img src="../../vistas/img/plantilla/jackyform_paloma2.png" width="150" height="75" alt="">
                </div>
            </td>
            <td style="width:350px;">
                <div>
                    <img src="../../vistas/img/plantilla/jackyform_letras.png" width="150" height="75" style="margin-left:auto;margin-right:auto;display:block;" alt="">
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
                <p style="text-align:center;margin:0;"><b>' . $venta["tipo_documento"] . ' DE VENTA ELECTRONICA</b></p>
                <p style="text-align:center;margin:0;"><b>Nro.: ' . $docNumFmt . '</b></p>
                <p style="text-align:center;margin:4px 0 0;">Hoja {{HOJA}} de {{TOTAL}}</p>
            </td>
        </tr>
    </table>';

    $cabeceraCompacta = '<table border="0">
        <tr>
            <td style="width:420px; vertical-align:middle;">
                <p style="margin:0;"><b>Corporación Vasco S.A.C.</b></p>
                <p style="margin:0;">Cliente: ' . htmlspecialchars($venta["nombre"]) . '</p>
                <p style="margin:0;font-size:11px;">' . $tip_doc_cli . ': ' . $documentoCliente
        . ' | Emisión: ' . $venta["fecha"] . ' | Guía: ' . $venta["doc_guia"] . '</p>
            </td>
            <td style="width:300px; border:1px solid black; text-align:center; vertical-align:middle;">
                <p style="margin:0;"><b>' . $venta["tipo_documento"] . ' DE VENTA ELECTRONICA</b></p>
                <p style="margin:0;"><b>Nro.: ' . $docNumFmt . '</b></p>
                <p style="margin:0;">Hoja {{HOJA}} de {{TOTAL}}</p>
            </td>
        </tr>
    </table>';

    $cliente = '<table>
        <tr>
            <td style="width:80px;">Cliente:</td>
            <td>' . $venta["nombre"] . '</td>
        </tr>
        <tr>
            <td style="width:80px;">Dirección:</td>
            <td>' . $venta["direccion"] . '</td>
        </tr>
        <tr>
            <td style="width:80px;">Ciudad:</td>
            <td>' . $venta["nom_ubigeo"] . '</td>
        </tr>
        <tr>
            <td style="width:80px;">' . $tip_doc_cli . '</td>
            <td>' . $documentoCliente . '</td>
        </tr>
        <tr>
            <td style="width:80px;">Cod. Cliente:</td>
            <td>' . $venta["cliente"] . '</td>
        </tr>
        <tr>
            <td style="width:80px;">Vendedor:</td>
            <td>' . $venta["vendedor"] . ' - ' . $venta["nom_vendedor"] . '</td>
        </tr>
    </table>';

    $feccon = '<table>
        <tr>
            <th style="width:150px; border-radius:10px; border:2px solid #000; padding:1px;">Fecha Emisión</th>
            <th style="width:200px; border-radius:10px; border:2px solid #000; padding:1px;">Condición de Pago</th>
            <th style="width:100px; border-radius:10px; border:2px solid #000; padding:1px;">O. Compra</th>
            <th style="width:150px; border-radius:10px; border:2px solid #000; padding:1px;">Fecha de Vencimiento</th>
            <th style="width:150px; border-radius:10px; border:2px solid #000; padding:1px;">No. Guia Remisión</th>
        </tr>
        <tr>
            <td style="text-align:center;">' . $venta["fecha"] . '</td>
            <td style="text-align:center;">' . $venta["descripcion"] . '</td>
            <td style="text-align:center;">' . $ordenCompraImp . '</td>
            <td style="text-align:center;">' . $venta["fecha_vencimiento"] . '</td>
            <td style="text-align:center;">' . $venta["doc_guia"] . '</td>
        </tr>
    </table>';

    $cabDet = '<table>
        <tr>
            <th style="width:80px; border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;">CODIGO</th>
            <th style="width:80px; border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;">CANT.</th>
            <th style="width:50px; border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;">UND</th>
            <th style="width:250px; border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;">DESCRIPCIÓN</th>
            <th style="width:80px; border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;">V.UNIT</th>
            <th style="width:80px; border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;">DSCTO</th>
            <th style="width:80px; border-radius:5px; border:1px solid #000; padding:1px; background-color:#E0DEDE;">P.VENTA</th>
        </tr>
    </table>';

    $pie = '<table class="pie-documento">
        <tr>
            <td style="width:500px; border-radius:10px; border:1px solid #000; padding:1px; vertical-align:top;">
                <p style="margin:2px 0;">Observaciones</p>
                <p style="margin:2px 0;">Nro. Unidades: ' . $cantidaUnidades . '</p>
                ' . $htmlRetencionObs . '
            </td>
            <td style="width:220px; border-radius:10px; border:1px solid #000; padding:1px;">
                <table>
                    <tr>
                        <td style="width:170px;">Op. Gravadas ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . $gravadas . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">Op. Inafecta ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">0.00</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">Op. Exonerada ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . $exonerada . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">Total Op. Gratuitas ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">0.00</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">Descuentos Totales ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format($venta["dscto"], 2) . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">Sub Totales ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format($subtotal, 2) . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">ISC ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">0.00</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">IGV ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format($venta["igv"], 2) . '</td>
                    </tr>
                    ' . $htmlRetencionTotales . '
                    <tr>
                        <td style="width:170px;">TOTAL ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format($venta["total"], 2) . '</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="width:720px; border-radius:5px; border:1px solid #000; padding:2px;">Son: ' . $monto_letra . '</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size:14px; padding-top:4px;"><b>Cuentas autorizadas para depósitos y transferencias:</b></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size:13px;">Bco. Crédito del Perú: 191-1553564-0-64</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size:13px;">Yape empresa: 945 470 738</td>
        </tr>
        <tr>
            <td colspan="2" class="aviso-importante">
                <b>Aviso importante:</b> Realice depósitos y transferencias solo en las cuentas autorizadas indicadas. Exija su cargo de cobranza con fecha, monto y firma. No abone a cuentas personales de vendedores, terceros ni ex colaboradores. Pagos fuera de estas cuentas quedan fuera de nuestra responsabilidad. Solo el cargo de cobranza servirá de sustento ante cualquier reclamo.
            </td>
        </tr>
        <tr>
            <td colspan="2" class="pie-firma">CANCELADO</td>
        </tr>
        <tr>
            <td colspan="2" class="pie-firma">Lima, ________ de __________________ de _______</td>
        </tr>
        <tr>
            <td colspan="2" class="pie-legal">Representación Impresa del Documento Electronico, consulte en www.efact.com</td>
        </tr>
        <tr>
            <td colspan="2" class="pie-legal">Autorizado mediante Resolución de Intendencia No. 034005004177/SUNAT</td>
        </tr>
    </table>';

    $lineasPie = bolfactSapLineasPie($LINEAS_PIE_BASE, $monto_letra, !empty($retencionImp["aplica"]));

    $paginas = bolfactSapParticionarPorLineas(
        $modelo,
        $LINEAS_PAGINA,
        $LINEAS_CAB_P1,
        $LINEAS_CAB_P2,
        $lineasPie,
        $LINEAS_CONTINUA,
        $CHARS_LINEA_DESC
    );
    $totalPaginas = count($paginas);
    ?>

    <!-- bolfact-sap paginacion-lineas -->

    <div class="zona_impresion">
        <?php foreach ($paginas as $indice => $itemsPagina): ?>
            <?php
            $esUltima = ($indice === $totalPaginas - 1);
            $esPrimera = ($indice === 0);
            $clasePagina = $esUltima ? 'pagina pagina-final' : 'pagina pagina-detalle';
            ?>
            <div class="<?php echo $clasePagina; ?>">
                <?php
                if ($esPrimera) {
                    echo str_replace(
                        array('{{HOJA}}', '{{TOTAL}}'),
                        array($indice + 1, $totalPaginas),
                        $cabeceraCompleta
                    );
                    echo $cliente;
                    echo $feccon;
                } else {
                    echo str_replace(
                        array('{{HOJA}}', '{{TOTAL}}'),
                        array($indice + 1, $totalPaginas),
                        $cabeceraCompacta
                    );
                }

                echo $cabDet;

                if ($esUltima) {
                    echo '<div class="bloque-final">';
                }

                echo '<table class="tabla-items">';
                foreach ($itemsPagina as $value) {
                    echo renderFilaItemSap($value);
                }
                echo '</table>';

                if ($esUltima) {
                    echo $pie;
                    echo '</div>';
                }

                if (!$esUltima && $totalPaginas > 1) {
                    echo '<p class="continuar-hoja">Continúa en la siguiente hoja</p>';
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>

</html>
