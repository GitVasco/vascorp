<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link href="css/ticket_v9.css" target="_blank" rel="stylesheet" type="text/css">
    <style>
        @media print {
            .pagina {
                page-break-after: always;
            }

            .pagina:last-child {
                page-break-after: auto;
            }

            .pie-documento {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }

        .pie-documento {
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

    $anno = date("Y", strtotime($venta["fecha_emision"]));
    $tabla = "movimientosjf_" . $anno;

    $modelo = ControladorFacturacion::ctrMostrarModeloImpresionV2($tabla, $documento, $tipo, 0, 100);
    $subtotal = $venta["neto"] - $venta["dscto"];

    $monto_letra = $venta["tipo_moneda"] == "1"
        ? CantidadEnLetra($venta["total"])
        : str_replace("SOLES", "DOLARES AMERICANOS", CantidadEnLetra($venta["total"]));

    $cantidaUnidades = 0;
    foreach ($modelo as $value2) {
        $cantidaUnidades += $value2["cantidad"];
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

    // Igual que el original: ~20 ítems por hoja de detalle.
    // En la última hoja (con pie + aviso) se reserva espacio.
    $ITEMS_POR_PAGINA = 20;
    $ITEMS_CON_PIE = 14;

    /**
     * Parte ítems en páginas reales (nunca crea hoja vacía con cabecera).
     * - Si cabe en una hoja de detalle → una sola página (ítems + pie).
     * - Si hay más → reserva espacio al pie en la última hoja y reparte
     *   el resto de forma pareja (evita hojas con 1–2 modelos).
     */
    function particionarItems($items, $porPagina, $itemsConPie)
    {
        $total = count($items);

        if ($total === 0) {
            return [[]];
        }

        // Una sola hoja de detalle: no repetir cabecera
        if ($total <= $porPagina) {
            return [$items];
        }

        $enUltima = min($itemsConPie, $total);
        $antes = $total - $enUltima;
        $numAntes = (int) ceil($antes / $porPagina);
        $base = (int) floor($antes / $numAntes);
        $extra = $antes % $numAntes;

        $paginas = [];
        $offset = 0;
        for ($i = 0; $i < $numAntes; $i++) {
            $size = $base + ($i < $extra ? 1 : 0);
            $paginas[] = array_slice($items, $offset, $size);
            $offset += $size;
        }
        $paginas[] = array_slice($items, $offset);

        return $paginas;
    }

    function renderFilaItem($value)
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

    $cabecera = '<table border="0">
        <thead>
            <tr>
                <td style="width:150px;">
                    <div style="align:center;">
                        <img src="../../vistas/img/plantilla/jackyform_paloma2.png" width="150px" height="75px">
                    </div>
                </td>
                <td style="width:350px;">
                    <div>
                        <img src="../../vistas/img/plantilla/jackyform_letras.png" width="150px" height="75px" style="margin-left: auto; margin-right: auto; display: block;">
                        <p style="margin:0px; text-align:center;"><b>Corporación Vasco S.A.C.</b></p>
                        <p style="margin:0px; text-align:center;">Cal.Santo Toribio Nro. 259 - Urb Santa Luisa 1ra Etapa</p>
                        <p style="margin:0px; text-align:center;">San Martin de Porres - Lima - Lima</p>
                        <p style="margin:0px; text-align:center;">Telfs: 537-2501/536-4024 Cel 964570509 / 964543475</p>
                        <p style="margin:0px; text-align:center;">Página Web: www.jackyform.com.pe</p>
                        <p style="margin:0px; text-align:center;">Email: gerenciadeventas@jackyform.com.pe</p>
                        <p style="margin:0px; text-align:center;">cuentascorrientes@jackyform.com.pe</p>
                        <p style="margin:0px; text-align:center;"><i>Confecciones de Prendas de Ropa Interior</i></p>
                    </div>
                </td>
                <td style="width:250px; height:100px; border: 1px solid black;">
                    <p style="text-align:center;"><b>RUC: 20513613939</b></p>
                    <p style="text-align:center;"><b>' . $venta["tipo_documento"] . ' DE VENTA ELECTRONICA</b></p>
                    <p style="text-align:center;"><b>Nro.: ' . substr($venta["documento"], 0, 4) . "-" . substr($venta["documento"], 4, 12) . '</b></p>
                </td>
            </tr>
        </thead>
    </table>';

    $cliente = '<table>
        <thead>
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
        </thead>
    </table>';

    $feccon = '<table>
        <thead>
            <tr>
                <th style="width:150px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">Fecha Emisión</th>
                <th style="width:200px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">Condición de Pago</th>
                <th style="width:100px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">O. Compra</th>
                <th style="width:150px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">Fecha de Vencimiento</th>
                <th style="width:150px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">No. Guia Remisión</th>
            </tr>
            <tr>
                <td style="text-align:center;">' . $venta["fecha"] . '</td>
                <td style="text-align:center;">' . $venta["descripcion"] . '</td>
                <td style="text-align:center;">' . $ordenCompraImp . '</td>
                <td style="text-align:center;">' . $venta["fecha_vencimiento"] . '</td>
                <td style="text-align:center;">' . $venta["doc_guia"] . '</td>
            </tr>
        </thead>
    </table>';

    $cabDet = '<table>
        <thead>
            <tr>
                <th style="width:80px; border-radius: 5px; border: 1px solid #000000; padding: 1px; background-color:#E0DEDE;">CODIGO</th>
                <th style="width:80px; border-radius: 5px; border: 1px solid #000000; padding: 1px; background-color:#E0DEDE;">CANT.</th>
                <th style="width:50px; border-radius: 5px; border: 1px solid #000000; padding: 1px; background-color:#E0DEDE;">UND</th>
                <th style="width:250px; border-radius: 5px; border: 1px solid #000000; padding: 1px; background-color:#E0DEDE;">DESCRIPCIÓN</th>
                <th style="width:80px; border-radius: 5px; border: 1px solid #000000; padding: 1px; background-color:#E0DEDE;">V.UNIT</th>
                <th style="width:80px; border-radius: 5px; border: 1px solid #000000; padding: 1px; background-color:#E0DEDE;">DSCTO</th>
                <th style="width:80px; border-radius: 5px; border: 1px solid #000000; padding: 1px; background-color:#E0DEDE;">P.VENTA</th>
            </tr>
        </thead>
    </table>';

    $pie = '<table class="pie-documento">
        <tr>
            <td style="width:500px; border-radius: 10px; border: 1px solid #000000; padding: 1px; vertical-align:top;">
                <p style="margin:2px 0;">Observaciones</p>
                <p style="margin:2px 0;">Nro. Unidades: ' . $cantidaUnidades . '</p>
                ' . $htmlRetencionObs . '
            </td>
            <td style="width:220px; border-radius: 10px; border: 1px solid #000000; padding: 1px;">
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
            <td colspan="2" style="width:720px; border-radius: 5px; border: 1px solid #000000; padding: 2px;">Son: ' . $monto_letra . '</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 14px; padding-top: 4px;"><b>Cuentas autorizadas para depósitos y transferencias:</b></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 13px;">Bco. Crédito del Perú: 191-1553564-0-64</td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 13px;">Yape empresa: 945 470 738</td>
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

    $paginas = particionarItems($modelo, $ITEMS_POR_PAGINA, $ITEMS_CON_PIE);
    $totalPaginas = count($paginas);
    ?>

    <div class="zona_impresion">
        <?php foreach ($paginas as $indice => $itemsPagina): ?>
            <div class="pagina">
                <?php
                echo $cabecera;
                echo $cliente;
                echo $feccon;
                echo $cabDet;
                echo '<table>';
                foreach ($itemsPagina as $value) {
                    echo renderFilaItem($value);
                }
                echo '</table>';

                // Pie solo en la última página, pegado a los ítems (sin cabecera extra)
                if ($indice === $totalPaginas - 1) {
                    echo $pie;
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>

</body>

</html>
