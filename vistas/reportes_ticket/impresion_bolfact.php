<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link href="css/ticket_v9.css" target="_blank" rel="stylesheet" type="text/css">
</head>

<!-- <body onload="window.print();"> -->

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
    $cantModelo = count($modelo);
    $subtotal = $venta["neto"] - $venta["dscto"];

    $monto_letra = $venta["tipo_moneda"] == "1" ? CantidadEnLetra($venta["total"]) : str_replace("SOLES", "DOLARES AMERICANOS", CantidadEnLetra($venta["total"]));

    //$monto_letra = CantidadEnLetra($venta["total"]);
    //var_dump($venta);
    ?>

    <div class="zona_impresion">

        <?php

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
                    <th style="width:150px; border-radius: 10px;  border: 2px solid #000000;  padding: 1px;">Fecha Emisión</th>
                    <th style="width:200px; border-radius: 10px;  border: 2px solid #000000;  padding: 1px;">Condición de Pago</th>
                    <th style="width:100px; border-radius: 10px;  border: 2px solid #000000;  padding: 1px;">O. Compra</th>
                    <th style="width:150px; border-radius: 10px;  border: 2px solid #000000;  padding: 1px;">Fecha de Vencimiento</th>
                    <th style="width:150px; border-radius: 10px;  border: 2px solid #000000;  padding: 1px;">No. Guia Remisión</th>
                </tr>
                
                <tr>
                    <td style="text-align:center;">' . $venta["fecha"] . '</td>
                    <td style="text-align:center;">' . $venta["descripcion"] . '</td>
                    <td style="text-align:center;"></td>
                    <td style="text-align:center;">' . $venta["fecha_vencimiento"] . '</td>
                    <td style="text-align:center;">' . $venta["doc_guia"] . '</td>
                </tr>

            </thead>

        </table>';

        $cabDet = '<table>

            <thead>

                <tr>
                    <th style="width:80px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px; background-color:#E0DEDE;">CODIGO</th>
                    <th style="width:80px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px; background-color:#E0DEDE;">CANT.</th>
                    <th style="width:50px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px; background-color:#E0DEDE;">UND</th>
                    <th style="width:250px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px; background-color:#E0DEDE;">DESCRIPCIÓN</th>
                    <th style="width:80px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px; background-color:#E0DEDE;">V.UNIT</th>
                    <th style="width:80px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px; background-color:#E0DEDE;">DSCTO</th>
                    <th style="width:80px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px; background-color:#E0DEDE;">P.VENTA</th>                                                
                </tr>

            </thead>

        </table> ';

        $cantidaUnidades = 0;

        foreach ($modelo as $key => $value2) {
            $cantidaUnidades += $value2["cantidad"];
        }

        $pie = '<table>
            <tr>
                <td style="width:500px; border-radius: 10px;  border: 1px solid #000000;  padding: 1px;">
                    <p>Observaciones</p>
                    <p>Nro. Unidades: ' . $cantidaUnidades . '</p>
                </td>
                <td style="width:220px; border-radius: 10px;  border: 1px solid #000000;  padding: 1px;">
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
                        <tr>
                            <td style="width:170px;">TOTAL ' . $moneda . '</td>
                            <td style="width:50px; text-align:right;">' . number_format($venta["total"], 2) . '</td>
                        </tr> 
                    </table>
                </td>
            </tr>

            <tr>
                <td style="width:720px; border-radius: 5px;  border: 1px solid #000000;  padding: 1px;">Son: ' . $monto_letra . '</td>
            </tr>
            <tr></tr>
            <tr>
                <td style="font-size: 15px;"><b>Cuentas autorizadas para depósitos y transferencias:</b></td>
            </tr>
            <tr>
                <td style="font-size: 14px;">Bco. Crédito del Perú: 191-1553564-0-64</td>
            </tr>
            <tr>
                <td style="font-size: 14px;">Yape empresa: 945 470 738</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; font-size: 14px; text-align: justify;">
                    <b>Aviso importante:</b> Realice depósitos y transferencias solo en las cuentas autorizadas indicadas. Exija su cargo de cobranza con fecha, monto y firma. No abone a cuentas personales de vendedores, terceros ni ex colaboradores. Pagos fuera de estas cuentas quedan fuera de nuestra responsabilidad. Solo el cargo de cobranza servirá de sustento ante cualquier reclamo.
                </td>
            </tr>
            <tr>
                <td style="width:220px; height:50px; text-align:CENTER;">CANCELADO</td>
            </tr>
            <tr>
                <td style="width:220px; height:50px; text-align:CENTER;">Lima, ________ de __________________ de _______</td>
            </tr>

            <tr>
                <td>Representación Impresa del Documento Electronico, consulte en www.efact.com</td>
            </tr>
            <tr>
                <td>Autorizado mediante Resolución de Intendencia No. 034005004177/SUNAT</td>
            </tr>

        </table>';

        //todo: 1 página
        if ($cantModelo <= 20) {

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                echo '<tr>
                    <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                    <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                    <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                    <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                    <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                    <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                    <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                </tr>';
            }

            echo '</table>';

            echo $pie;
        }

        //todo: 2 Páginas
        else if ($cantModelo > 20 && $cantModelo <= 40) {

            //*PAGINA 1

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key <= 20) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*PAGINA 2

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 20) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            echo $pie;
        }

        //todo: 3 páginas
        else if ($cantModelo > 40 && $cantModelo <= 60) {

            //*PAGINA 1

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key <= 20) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*PAGINA 2

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 20 && $key <= 40) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            //*pagina 3

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 40 && $key <= 60) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            echo $pie;
        }

        //todo: 4 páginas
        else if ($cantModelo > 60 && $cantModelo <= 80) {

            //*PAGINA 1

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key <= 20) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*PAGINA 2

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 20 && $key <= 40) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*PAGINA 3

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 40 && $key <= 60) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }


            //*PAGINA 4

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 60 && $key <= 80) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            echo $pie;
        }

        //todo: 5 páginas
        else if ($cantModelo > 80 && $cantModelo <= 100) {

            //*PAGINA 1

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key <= 20) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*PAGINA 2

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 20 && $key <= 40) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*PAGINA 3

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 40 && $key <= 60) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }


            //*PAGINA 4

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 60 && $key <= 80) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            for ($i = 0; $i < 65; $i++) {

                echo '<table>
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*PAGINA 5

            echo $cabecera;

            echo $cliente;

            echo $feccon;

            echo $cabDet;

            echo '<table>';

            foreach ($modelo as $key => $value) {

                if ($key > 80 && $key <= 100) {

                    echo '<tr>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["modelo"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["cantidad"] . '</td>
                        <td style="width:50px; border-right: 1px solid; text-align:center;">' . $value["unidad"] . '</td>
                        <td style="width:250px; border-right: 1px solid;">' . $value["nombre"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["precio"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["dscto1"] . '</td>
                        <td style="width:80px; border-right: 1px solid; text-align:right;">' . $value["total"] . '</td>
                    </tr>';
                }
            }

            echo '</table>';

            echo $pie;
        }

        ?>

    </div>

</body>

</html>