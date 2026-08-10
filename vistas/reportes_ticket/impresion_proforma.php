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

    $moneda = (isset($venta["tipo_moneda"]) && $venta["tipo_moneda"] == "1") ? "S/" : "$";

    switch (isset($venta["tip_doc_cli"]) ? $venta["tip_doc_cli"] : "") {
        case '1':
            $tip_doc_cli = "DNI";
            break;
        case '6':
            $tip_doc_cli = "RUC";
            break;
        default:
            $tip_doc_cli = "Doc.";
            break;
    }

    $documentoCliente = isset($venta["dni"]) ? $venta["dni"] : "";
    // Proforma: serie = 3 primeros dígitos (ej. 060-004099)
    $documentoFmt = (strlen($venta["documento"]) > 3)
        ? substr($venta["documento"], 0, 3) . "-" . substr($venta["documento"], 3)
        : $venta["documento"];

    $anno = date("Y", strtotime($venta["fecha_emision"]));
    $tabla = "movimientosjf_" . $anno;
    $modelo = ControladorFacturacion::ctrMostrarModeloProforma($tabla, $documento, $tipo);

    $subtotal = $venta["neto"] - $venta["dscto"];
    $monto_letra = ($moneda == "S/")
        ? CantidadEnLetra($venta["total"])
        : str_replace("SOLES", "DOLARES AMERICANOS", CantidadEnLetra($venta["total"]));

    $cantidaUnidades = 0;
    foreach ($modelo as $fila) {
        $cantidaUnidades += $fila["cantidad"];
    }

    $nomVendedor = isset($venta["nom_vendedor"]) ? $venta["nom_vendedor"] : "";
    $vendedorFmt = trim($venta["vendedor"] . ($nomVendedor !== "" ? " - " . $nomVendedor : ""));

    $ITEMS_POR_PAGINA = 20;
    $ITEMS_CON_PIE = 14;

    /**
     * Varias hojas: reserva pie en la última y reparte el resto de forma pareja
     * (evita hojas con 1–2 modelos, p. ej. 20+20+2+14).
     */
    function particionarItemsProforma($items, $porPagina, $itemsConPie)
    {
        $total = count($items);

        if ($total === 0) {
            return array(array());
        }

        if ($total <= $porPagina) {
            return array($items);
        }

        $enUltima = min($itemsConPie, $total);
        $antes = $total - $enUltima;
        $numAntes = (int) ceil($antes / $porPagina);
        $base = (int) floor($antes / $numAntes);
        $extra = $antes % $numAntes;

        $paginas = array();
        $offset = 0;
        for ($i = 0; $i < $numAntes; $i++) {
            $size = $base + ($i < $extra ? 1 : 0);
            $paginas[] = array_slice($items, $offset, $size);
            $offset += $size;
        }
        $paginas[] = array_slice($items, $offset);

        return $paginas;
    }

    function renderFilaProforma($value)
    {
        return '<tr>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
            <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
            <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . number_format((float) $value["precio"], 2) . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . number_format((float) $value["dscto1"], 2) . '</td>
            <td style="width:80px; border-right: 1px solid; text-align:right;">' . number_format((float) $value["total"], 2) . '</td>
        </tr>';
    }

    $cabecera = '<table border="0">
        <thead>
            <tr>
                <td style="width:150px;">
                    <div style="text-align:center;">
                        <img src="../../vistas/img/plantilla/jackyform_paloma2.png" width="150" height="75" alt="">
                    </div>
                </td>
                <td style="width:350px;">
                    <div>
                        <img src="../../vistas/img/plantilla/jackyform_letras.png" width="150" height="75" style="margin-left: auto; margin-right: auto; display: block;" alt="">
                        <p style="margin:0; text-align:center;"><b>Corporación Vasco S.A.C.</b></p>
                        <p style="margin:0; text-align:center;">Cal.Santo Toribio Nro. 259 - Urb Santa Luisa 1ra Etapa</p>
                        <p style="margin:0; text-align:center;">San Martin de Porres - Lima - Lima</p>
                        <p style="margin:0; text-align:center;">Telfs: 537-2501/536-4024 Cel 964570509 / 964543475</p>
                        <p style="margin:0; text-align:center;">Página Web: www.jackyform.com.pe</p>
                        <p style="margin:0; text-align:center;">Email: gerenciadeventas@jackyform.com.pe</p>
                        <p style="margin:0; text-align:center;">cuentascorrientes@jackyform.com.pe</p>
                        <p style="margin:0; text-align:center;"><i>Confecciones de Prendas de Ropa Interior</i></p>
                    </div>
                </td>
                <td style="width:250px; height:100px; border: 1px solid black;">
                    <p style="text-align:center;"><b>RUC: 20513613939</b></p>
                    <p style="text-align:center;"><b>PROFORMA</b></p>
                    <p style="text-align:center;"><b>Nro.: ' . $documentoFmt . '</b></p>
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
                <td>' . (isset($venta["nom_ubigeo"]) ? $venta["nom_ubigeo"] : "") . '</td>
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
                <td>' . $vendedorFmt . '</td>
            </tr>
        </thead>
    </table>';

    $feccon = '<table>
        <thead>
            <tr>
                <th style="width:180px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">Fecha Emisión</th>
                <th style="width:270px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">Condición de Pago</th>
                <th style="width:270px; border-radius: 10px; border: 2px solid #000000; padding: 1px;">Documento</th>
            </tr>
            <tr>
                <td style="text-align:center;">' . $venta["fecha"] . '</td>
                <td style="text-align:center;">' . $venta["descripcion"] . '</td>
                <td style="text-align:center;">' . $documentoFmt . '</td>
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
            </td>
            <td style="width:220px; border-radius: 10px; border: 1px solid #000000; padding: 1px;">
                <table>
                    <tr>
                        <td style="width:170px;">Op. Gravadas ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format((float) $venta["neto"], 2) . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">Descuentos Totales ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format((float) $venta["dscto"], 2) . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">Sub Totales ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format((float) $subtotal, 2) . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;">IGV ' . $moneda . '</td>
                        <td style="width:50px; text-align:right;">' . number_format((float) $venta["igv"], 2) . '</td>
                    </tr>
                    <tr>
                        <td style="width:170px;"><b>TOTAL ' . $moneda . '</b></td>
                        <td style="width:50px; text-align:right;"><b>' . number_format((float) $venta["total"], 2) . '</b></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="width:720px; border-radius: 5px; border: 1px solid #000000; padding: 2px;">Son: ' . $monto_letra . '</td>
        </tr>
        <tr>
            <td colspan="2" class="aviso-importante">
                <b>Aviso importante:</b> Los pagos deben realizarse en efectivo directamente a su ejecutivo de ventas. Exija su cargo de cobranza con fecha, monto y firma. No nos responsabilizamos por pagos abonados a cuentas de ejecutivos de ventas ni de terceros. Solo el cargo de cobranza servirá de sustento ante cualquier reclamo.
            </td>
        </tr>
        <tr>
            <td colspan="2" class="pie-legal">Documento informativo (proforma) — Corporación Vasco S.A.C. · RUC 20513613939</td>
        </tr>
    </table>';

    $paginas = particionarItemsProforma($modelo, $ITEMS_POR_PAGINA, $ITEMS_CON_PIE);
    $totalPaginas = count($paginas);
    ?>

    <div class="zona_impresion">
        <?php foreach ($paginas as $indice => $itemsPagina) { ?>
            <div class="pagina">
                <?php
                echo $cabecera;
                echo $cliente;
                echo $feccon;
                echo $cabDet;
                echo '<table>';
                foreach ($itemsPagina as $value) {
                    echo renderFilaProforma($value);
                }
                echo '</table>';

                if ($indice === $totalPaginas - 1) {
                    echo $pie;
                }
                ?>
            </div>
        <?php } ?>
    </div>

</body>

</html>
