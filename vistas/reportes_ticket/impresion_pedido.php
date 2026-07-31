<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link href="css/ticket_v2.css" target="_blank" rel="stylesheet" type="text/css">
</head>

<body onload="window.print();">
    <?php

    require_once "../../controladores/pedidos.controlador.php";
    require_once "../../modelos/pedidos.modelo.php";
    require_once "../../controladores/decisiones-credito.config.php";
    require_once "../../modelos/decisiones-credito.modelo.php";

    /* 
    * TRAEMOS LOS DATOS DEL PEDIDO
    */
    $codigo = $_GET["codigo"];
    //var_dump($codigo);

    $respuesta = ControladorPedidos::ctrPedidoImpresionCab($codigo);
    //var_dump($respuesta["pedido"]);
    //var_dump($respuesta["lista"]);

    $moneda = $respuesta["lista"] == "precio1" ? " $ " : " S/ ";

    $totales = ControladorPedidos::ctrPedidoImpresionTotales($codigo);
    //var_dump($totales);

    $pedidos = controladorPedidos::ctrMostraPedidosCabecera($codigo);

    date_default_timezone_set("America/Lima");

    //var_dump($respuesta["fecha"]);

    $originalDate = $respuesta["fecha"];
    $newDate = date("d/m/Y", strtotime($originalDate));
    //var_dump($newDate);

    //*Construir hojas
    $ini = 0;
    $fin = 1000;

    $articulos = ControladorPedidos::ctrPedidoImpresionB($codigo, $ini, $fin);
    $cantidadArticulos = count($articulos);
    #var_dump(count($articulos));

    $filasDireccionImpresion = function_exists("dcHtmlFilasDireccionImpresionPedido")
        ? dcHtmlFilasDireccionImpresionPedido($codigo, $respuesta)
        : "";

    ?>
    <div class="zona_impresion">

        <?php

        //todo: 1 PAGINA
        if ($cantidadArticulos <= 50) {

            //*Cabecera GLOBAL
            echo ' <table border="0" align="left" width="900px">

                        <thead>
                    
                            <tr>
                        
                                <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                        
                            </tr>
                        
                            <tr>
                        
                                <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                <th colspan="6"></th>
                                <th style="width:6%;text-align:left;">FECHA</th>
                                <td colspan="2">' . $newDate . '</td>
                        
                            </tr>
                        
                            <tr>
                        
                                <th style="width:10%;text-align:left;">CLIENTE:</th>
                                <td colspan="4">' . $respuesta["nombre"] . '</td>
                                <th colspan="2"></th>
                                <th style="width:6%">Cod:</th>
                                <td colspan="2">' . $respuesta["codigo"] . '</td>
                                <th style="width:6%"></th>
                        
                            </tr>
                        
                            ' . $filasDireccionImpresion . '
                        
                            <tr>
                        
                                <th style="width:10%;text-align:left;">VENDEDOR</th>
                                <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                <td colspan="2">' . $respuesta["documento"] . '</td>
                                <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                <th style="width:1%"></th>
                                <th style="width:1%"></th>
                                <th style="width:1%"></th>
                                <th style="width:1%"></th>
                        
                            </tr>
                        
                            <tr>
                        
                                <th style="width:10%"></th>
                                <th style="width:30%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                                <th style="width:6%"></th>
                        
                            </tr>
                    
                        </thead>
                    
                </table>';

            //*Cabecera PEDIDO
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            foreach ($articulos as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($respuesta["lista"] == "precio1" ? $value["precio"] : $value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">

                    </thead>                
                        <tr>               
                            <th style="width:10%;text-align:left;">TOTALES</th>
                            <th style="width:30%;text-align:left;">PEDIDO</th>
                            <th style="width:6%">' . number_format($totales["t1"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["t2"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["t3"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["t4"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["t5"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["t6"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["t7"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["t8"], 0) . '</th>
                            <th style="width:6%">' . number_format($totales["total"], 0) . '</th>              
                        </tr>                
                    </thead>
            
                </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">

                        </thead>
                    
                        <tr>                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>                    
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                            <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">Forma de Pago</td>
                            <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>               
                        </tr>
                    
                        </thead>
            
                </table>';
        }
        //todo: 2 PAGINAS
        else if ($cantidadArticulos > 50 && $cantidadArticulos <= 100) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($respuesta["lista"] == "precio1" ? $value["precio"] : $value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 100);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($respuesta["lista"] == "precio1" ? $value["precio"] : $value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">

                    </thead>                
                        <tr>               
                            <th style="width:10%;text-align:left;">TOTALES</th>
                            <th style="width:30%;text-align:left;">PEDIDO</th>
                            <th style="width:6%">' . $totales["t1"] . '</th>
                            <th style="width:6%">' . $totales["t2"] . '</th>
                            <th style="width:6%">' . $totales["t3"] . '</th>
                            <th style="width:6%">' . $totales["t4"] . '</th>
                            <th style="width:6%">' . $totales["t5"] . '</th>
                            <th style="width:6%">' . $totales["t6"] . '</th>
                            <th style="width:6%">' . $totales["t7"] . '</th>
                            <th style="width:6%">' . $totales["t8"] . '</th>
                            <th style="width:6%">' . $totales["total"] . '</th>                
                        </tr>                
                    </thead>
            
                </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">

                        </thead>
                    
                        <tr>                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>                    
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                            <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">Forma de Pago</td>
                            <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>               
                        </tr>
                    
                        </thead>
            
                </table>';
        }
        //todo: 3 PAGINAS
        else if ($cantidadArticulos > 100 && $cantidadArticulos <= 150) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">

                    </thead>                
                        <tr>               
                            <th style="width:10%;text-align:left;">TOTALES</th>
                            <th style="width:30%;text-align:left;">PEDIDO</th>
                            <th style="width:6%">' . $totales["t1"] . '</th>
                            <th style="width:6%">' . $totales["t2"] . '</th>
                            <th style="width:6%">' . $totales["t3"] . '</th>
                            <th style="width:6%">' . $totales["t4"] . '</th>
                            <th style="width:6%">' . $totales["t5"] . '</th>
                            <th style="width:6%">' . $totales["t6"] . '</th>
                            <th style="width:6%">' . $totales["t7"] . '</th>
                            <th style="width:6%">' . $totales["t8"] . '</th>
                            <th style="width:6%">' . $totales["total"] . '</th>                
                        </tr>                
                    </thead>
            
                </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">

                        </thead>
                    
                        <tr>                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>                    
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                            <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">Forma de Pago</td>
                            <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>               
                        </tr>
                    
                        </thead>
            
                </table>';
        }
        //todo: 4 PAGINAS
        else if ($cantidadArticulos > 150 && $cantidadArticulos <= 200) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 3                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 4
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 4
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 4
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 150, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">

                    </thead>                
                        <tr>               
                            <th style="width:10%;text-align:left;">TOTALES</th>
                            <th style="width:30%;text-align:left;">PEDIDO</th>
                            <th style="width:6%">' . $totales["t1"] . '</th>
                            <th style="width:6%">' . $totales["t2"] . '</th>
                            <th style="width:6%">' . $totales["t3"] . '</th>
                            <th style="width:6%">' . $totales["t4"] . '</th>
                            <th style="width:6%">' . $totales["t5"] . '</th>
                            <th style="width:6%">' . $totales["t6"] . '</th>
                            <th style="width:6%">' . $totales["t7"] . '</th>
                            <th style="width:6%">' . $totales["t8"] . '</th>
                            <th style="width:6%">' . $totales["total"] . '</th>                
                        </tr>                
                    </thead>
            
                </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">

                        </thead>
                    
                        <tr>                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>                    
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                            <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">Forma de Pago</td>
                            <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>               
                        </tr>
                    
                        </thead>
            
                </table>';
        }
        //todo: 5 PAGINAS
        else if ($cantidadArticulos > 200 && $cantidadArticulos <= 250) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 3                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 4
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 4
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 4
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 150, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 4                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 5
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 5
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 5
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 200, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">

                    </thead>                
                        <tr>               
                            <th style="width:10%;text-align:left;">TOTALES</th>
                            <th style="width:30%;text-align:left;">PEDIDO</th>
                            <th style="width:6%">' . $totales["t1"] . '</th>
                            <th style="width:6%">' . $totales["t2"] . '</th>
                            <th style="width:6%">' . $totales["t3"] . '</th>
                            <th style="width:6%">' . $totales["t4"] . '</th>
                            <th style="width:6%">' . $totales["t5"] . '</th>
                            <th style="width:6%">' . $totales["t6"] . '</th>
                            <th style="width:6%">' . $totales["t7"] . '</th>
                            <th style="width:6%">' . $totales["t8"] . '</th>
                            <th style="width:6%">' . $totales["total"] . '</th>                
                        </tr>                
                    </thead>
            
                </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">

                        </thead>
                    
                        <tr>                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>                    
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                            <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">Forma de Pago</td>
                            <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>               
                        </tr>
                    
                        </thead>
            
                </table>';
        }
        //todo: 6 PAGINAS
        else if ($cantidadArticulos > 250 && $cantidadArticulos <= 300) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 3                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 4
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 4
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 4
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 150, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 4                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 5
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 5
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 5
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 200, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 5                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                        <tr>
                            <td></td>
                        </tr>
                    </table>';
            }

            //*Cabecera GLOBAL PAG 6
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 6
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 6
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 250, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">

                    </thead>                
                        <tr>               
                            <th style="width:10%;text-align:left;">TOTALES</th>
                            <th style="width:30%;text-align:left;">PEDIDO</th>
                            <th style="width:6%">' . $totales["t1"] . '</th>
                            <th style="width:6%">' . $totales["t2"] . '</th>
                            <th style="width:6%">' . $totales["t3"] . '</th>
                            <th style="width:6%">' . $totales["t4"] . '</th>
                            <th style="width:6%">' . $totales["t5"] . '</th>
                            <th style="width:6%">' . $totales["t6"] . '</th>
                            <th style="width:6%">' . $totales["t7"] . '</th>
                            <th style="width:6%">' . $totales["t8"] . '</th>
                            <th style="width:6%">' . $totales["total"] . '</th>                
                        </tr>                
                    </thead>
            
                </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">

                        </thead>
                    
                        <tr>                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>                    
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                            <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                        </tr>
                    
                        <tr>                    
                            <td style="width:10%;text-align:left;">Forma de Pago</td>
                            <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>               
                        </tr>
                    
                        </thead>
            
                </table>';
        }
        //todo: 7 PAGINAS
        else if ($cantidadArticulos > 300 && $cantidadArticulos <= 350) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 3                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 4
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 4
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 4
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 150, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 4                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 5
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 5
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 5
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 200, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 5                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 6
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 6
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 6
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 250, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 6                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 7
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 7
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 7
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 300, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">
            
                                </thead>                
                                    <tr>               
                                        <th style="width:10%;text-align:left;">TOTALES</th>
                                        <th style="width:30%;text-align:left;">PEDIDO</th>
                                        <th style="width:6%">' . $totales["t1"] . '</th>
                                        <th style="width:6%">' . $totales["t2"] . '</th>
                                        <th style="width:6%">' . $totales["t3"] . '</th>
                                        <th style="width:6%">' . $totales["t4"] . '</th>
                                        <th style="width:6%">' . $totales["t5"] . '</th>
                                        <th style="width:6%">' . $totales["t6"] . '</th>
                                        <th style="width:6%">' . $totales["t7"] . '</th>
                                        <th style="width:6%">' . $totales["t8"] . '</th>
                                        <th style="width:6%">' . $totales["total"] . '</th>                
                                    </tr>                
                                </thead>
                        
                            </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">
            
                                    </thead>
                                
                                    <tr>                    
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>                    
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                                        <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">Forma de Pago</td>
                                        <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>               
                                    </tr>
                                
                                    </thead>
                        
                            </table>';
        }
        //todo: 8 PAGINAS
        else if ($cantidadArticulos > 350 && $cantidadArticulos <= 400) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 3                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 4
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 4
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 4
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 150, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 4                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 5
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 5
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 5
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 200, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 5                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 6
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 6
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 6
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 250, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 6                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 7
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 7
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 7
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 300, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 7                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 8
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 8
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 8
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 350, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">
            
                                </thead>                
                                    <tr>               
                                        <th style="width:10%;text-align:left;">TOTALES</th>
                                        <th style="width:30%;text-align:left;">PEDIDO</th>
                                        <th style="width:6%">' . $totales["t1"] . '</th>
                                        <th style="width:6%">' . $totales["t2"] . '</th>
                                        <th style="width:6%">' . $totales["t3"] . '</th>
                                        <th style="width:6%">' . $totales["t4"] . '</th>
                                        <th style="width:6%">' . $totales["t5"] . '</th>
                                        <th style="width:6%">' . $totales["t6"] . '</th>
                                        <th style="width:6%">' . $totales["t7"] . '</th>
                                        <th style="width:6%">' . $totales["t8"] . '</th>
                                        <th style="width:6%">' . $totales["total"] . '</th>                
                                    </tr>                
                                </thead>
                        
                            </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">
            
                                    </thead>
                                
                                    <tr>                    
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>                    
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                                        <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">Forma de Pago</td>
                                        <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>               
                                    </tr>
                                
                                    </thead>
                        
                            </table>';
        }
        //todo: 9 PAGINAS
        else if ($cantidadArticulos > 400 && $cantidadArticulos <= 450) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 3                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 4
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 4
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 4
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 150, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 4                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 5
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 5
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 5
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 200, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 5                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 6
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 6
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 6
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 250, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 6                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 7
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 7
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 7
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 300, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 7                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 8
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 8
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 8
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 350, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 8                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 9
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 9
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 9
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 400, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">
            
                                </thead>                
                                    <tr>               
                                        <th style="width:10%;text-align:left;">TOTALES</th>
                                        <th style="width:30%;text-align:left;">PEDIDO</th>
                                        <th style="width:6%">' . $totales["t1"] . '</th>
                                        <th style="width:6%">' . $totales["t2"] . '</th>
                                        <th style="width:6%">' . $totales["t3"] . '</th>
                                        <th style="width:6%">' . $totales["t4"] . '</th>
                                        <th style="width:6%">' . $totales["t5"] . '</th>
                                        <th style="width:6%">' . $totales["t6"] . '</th>
                                        <th style="width:6%">' . $totales["t7"] . '</th>
                                        <th style="width:6%">' . $totales["t8"] . '</th>
                                        <th style="width:6%">' . $totales["total"] . '</th>                
                                    </tr>                
                                </thead>
                        
                            </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">
            
                                    </thead>
                                
                                    <tr>                    
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>                    
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                                        <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">Forma de Pago</td>
                                        <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>               
                                    </tr>
                                
                                    </thead>
                        
                            </table>';
        }
        //todo: 10 PAGINAS
        else if ($cantidadArticulos > 450 && $cantidadArticulos <= 500) {

            //*Cabecera GLOBAL PAG 1
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 1
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 1
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 0, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 1                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 2
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 2
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 2
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 50, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 2                    
            for ($i = 0; $i < 18; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 3
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 3
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 3
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 100, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 3                    
            for ($i = 0; $i < 22; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 4
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 4
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 4
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 150, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 4                    
            for ($i = 0; $i < 24; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 5
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 5
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 5
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 200, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 5                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                                    <tr>
                                        <td></td>
                                    </tr>
                                </table>';
            }

            //*Cabecera GLOBAL PAG 6
            echo '<table border="0" align="left" width="900px">
            
                                <thead>
                            
                                    <tr>
                                
                                        <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                                        <td style="width:30%">' . $respuesta["pedido"] . '</td>
                                        <th colspan="6"></th>
                                        <th style="width:6%;text-align:left;">FECHA</th>
                                        <td colspan="2">' . $newDate . '</td>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">CLIENTE:</th>
                                        <td colspan="4">' . $respuesta["nombre"] . '</td>
                                        <th colspan="2"></th>
                                        <th style="width:6%">Cod:</th>
                                        <td colspan="2">' . $respuesta["codigo"] . '</td>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                                
                                    ' . $filasDireccionImpresion . '
                                
                                    <tr>
                                
                                        <th style="width:10%;text-align:left;">VENDEDOR</th>
                                        <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                                        <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                                        <td colspan="2">' . $respuesta["documento"] . '</td>
                                        <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                        <th style="width:1%"></th>
                                
                                    </tr>
                                
                                    <tr>
                                
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                
                                    </tr>
                            
                                </thead>
                            
                            </table>';

            //*Cabecera PEDIDO PAG 6
            echo '<table border="1" align="left" width="900px">
            
                                <thead>
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">S</th>
                                    <th style="width:6%">M</th>
                                    <th style="width:6%">L</th>
                                    <th style="width:6%">XL</th>
                                    <th style="width:6%">XXL</th>
                                    <th style="width:6%">XS</th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%"></th>
                                    <th style="width:30%"></th>
                                    <th style="width:6%">28</th>
                                    <th style="width:6%">30</th>
                                    <th style="width:6%">32</th>
                                    <th style="width:6%">34</th>
                                    <th style="width:6%">36</th>
                                    <th style="width:6%">38</th>
                                    <th style="width:6%">40</th>
                                    <th style="width:6%">42</th>
                                    <th style="width:6%"></th>
                                </tr>
                            
                                <tr>
                                    <th style="width:10%;text-align:left;">Modelo</th>
                                    <th style="width:20%">Color</th>
                                    <th style="width:6%">3</th>
                                    <th style="width:6%">4</th>
                                    <th style="width:6%">6</th>
                                    <th style="width:6%">8</th>
                                    <th style="width:6%">10</th>
                                    <th style="width:6%">12</th>
                                    <th style="width:6%">14</th>
                                    <th style="width:6%">16</th>
                                    <th style="width:6%">TOTAL</th>
                                </tr>
                                </thead>
                        
                            </table>';

            //*Cuerpo PAG 6
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 250, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                            <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                            <th style="width:30%;text-align:left;">====================</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%;font-weight: normal;">===</th>
                                            <th style="width:6%">===</th>
                                        </tr>';
                } else {

                    echo '<tr>
                                            <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                            <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                            <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                            <th style="width:6%">' . $value["total"] . '</th>
                                        </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 6                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 7
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 7
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 7
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 300, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 7                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 8
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 8
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 8
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 350, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 8                    
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 9
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 9
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 9
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 400, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*RELLENO PAG 9                   
            for ($i = 0; $i < 23; $i++) {
                echo '<table border="0" align="left" width="900px">
                    <tr>
                        <td></td>
                    </tr>
                </table>';
            }

            //*Cabecera GLOBAL PAG 10
            echo '<table border="0" align="left" width="900px">

                    <thead>
                
                        <tr>
                    
                            <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">Nro. PEDIDO</th>
                            <td style="width:30%">' . $respuesta["pedido"] . '</td>
                            <th colspan="6"></th>
                            <th style="width:6%;text-align:left;">FECHA</th>
                            <td colspan="2">' . $newDate . '</td>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">CLIENTE:</th>
                            <td colspan="4">' . $respuesta["nombre"] . '</td>
                            <th colspan="2"></th>
                            <th style="width:6%">Cod:</th>
                            <td colspan="2">' . $respuesta["codigo"] . '</td>
                            <th style="width:6%"></th>
                    
                        </tr>
                    
                        ' . $filasDireccionImpresion . '
                    
                        <tr>
                    
                            <th style="width:10%;text-align:left;">VENDEDOR</th>
                            <td style="width:30%">' . $respuesta["vendedor"] . '</td>
                            <th style="width:6%;text-align:left;">' . $respuesta["tipo_doc"] . '</th>
                            <td colspan="2">' . $respuesta["documento"] . '</td>
                            <th style="width:50%">' . $respuesta["nom_agencia"] . '</th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                            <th style="width:1%"></th>
                    
                        </tr>
                    
                        <tr>
                    
                            <th style="width:10%"></th>
                            <th style="width:30%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                            <th style="width:6%"></th>
                    
                        </tr>
                
                    </thead>
                
                </table>';

            //*Cabecera PEDIDO PAG 10
            echo '<table border="1" align="left" width="900px">

                    <thead>
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">S</th>
                        <th style="width:6%">M</th>
                        <th style="width:6%">L</th>
                        <th style="width:6%">XL</th>
                        <th style="width:6%">XXL</th>
                        <th style="width:6%">XS</th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%"></th>
                        <th style="width:30%"></th>
                        <th style="width:6%">28</th>
                        <th style="width:6%">30</th>
                        <th style="width:6%">32</th>
                        <th style="width:6%">34</th>
                        <th style="width:6%">36</th>
                        <th style="width:6%">38</th>
                        <th style="width:6%">40</th>
                        <th style="width:6%">42</th>
                        <th style="width:6%"></th>
                    </tr>
                
                    <tr>
                        <th style="width:15%;text-align:left;">Modelo</th>
                        <th style="width:20%">Color</th>
                        <th style="width:6%">3</th>
                        <th style="width:6%">4</th>
                        <th style="width:6%">6</th>
                        <th style="width:6%">8</th>
                        <th style="width:6%">10</th>
                        <th style="width:6%">12</th>
                        <th style="width:6%">14</th>
                        <th style="width:6%">16</th>
                        <th style="width:6%">TOTAL</th>
                    </tr>
                    </thead>
            
                </table>';

            //*Cuerpo PAG 10
            echo '<table border="1" style="border:dashed" align="left" width="900px">';

            $articulosP1 = ControladorPedidos::ctrPedidoImpresionB($codigo, 450, 50);

            foreach ($articulosP1 as $key => $value) {

                if ($value["t1"] <= 0) {

                    $value["t1"] = " ";
                } else {

                    $value["t1"];
                }

                if ($value["t2"] <= 0) {

                    $value["t2"] = " ";
                } else {

                    $value["t2"];
                }

                if ($value["t3"] <= 0) {

                    $value["t3"] = " ";
                } else {

                    $value["t3"];
                }

                if ($value["t4"] <= 0) {

                    $value["t4"] = " ";
                } else {

                    $value["t4"];
                }

                if ($value["t5"] <= 0) {

                    $value["t5"] = " ";
                } else {

                    $value["t5"];
                }

                if ($value["t6"] <= 0) {

                    $value["t6"] = " ";
                } else {

                    $value["t6"];
                }

                if ($value["t7"] <= 0) {

                    $value["t7"] = " ";
                } else {

                    $value["t7"];
                }

                if ($value["t8"] <= 0) {

                    $value["t8"] = " ";
                } else {

                    $value["t8"];
                }

                if ($value["cod_color"] == "99") {

                    echo '<tr>
                                <th style="width:10%;font-weight: normal;text-align:left;">=====</th>
                                <th style="width:30%;text-align:left;">====================</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%;font-weight: normal;">===</th>
                                <th style="width:6%">===</th>
                            </tr>';
                } else {

                    echo '<tr>
                                <th style="width:15%;font-weight: normal;text-align:left;"><b>' . str_pad($value["modelo"], 10, "-", STR_PAD_RIGHT) . '</b>' . $moneda . str_pad(number_format($value["precio"] * 1.18, 2), 5, " ", STR_PAD_LEFT) . '</th>
                                <th style="width:20%;text-align:left;">' . $value["color"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t1"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t2"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t3"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t4"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t5"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t6"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t7"] . '</th>
                                <th style="width:6%;font-weight: normal;">' . $value["t8"] . '</th>
                                <th style="width:6%">' . $value["total"] . '</th>
                            </tr>';
                }
            }

            echo '</table';

            //*Total unidades
            echo '<table border="1" align="left" width="900px">
            
                                </thead>                
                                    <tr>               
                                        <th style="width:10%;text-align:left;">TOTALES</th>
                                        <th style="width:30%;text-align:left;">PEDIDO</th>
                                        <th style="width:6%">' . $totales["t1"] . '</th>
                                        <th style="width:6%">' . $totales["t2"] . '</th>
                                        <th style="width:6%">' . $totales["t3"] . '</th>
                                        <th style="width:6%">' . $totales["t4"] . '</th>
                                        <th style="width:6%">' . $totales["t5"] . '</th>
                                        <th style="width:6%">' . $totales["t6"] . '</th>
                                        <th style="width:6%">' . $totales["t7"] . '</th>
                                        <th style="width:6%">' . $totales["t8"] . '</th>
                                        <th style="width:6%">' . $totales["total"] . '</th>                
                                    </tr>                
                                </thead>
                        
                            </table>';

            //*Detalle fin de pedido
            echo '<table border="0" align="left" width="900px">
            
                                    </thead>
                                
                                    <tr>                    
                                        <th style="width:10%"></th>
                                        <th style="width:30%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>                    
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">TOTAL ' . $moneda . '</td>
                                        <th style="width:30%;text-align:left;">' . number_format($pedidos["total"], 2) . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                    </tr>
                                
                                    <tr>                    
                                        <td style="width:10%;text-align:left;">Forma de Pago</td>
                                        <th colspan="7" style="width:30%;text-align:left;">' . $pedidos["descripcion"] . '</th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>
                                        <th style="width:6%"></th>               
                                    </tr>
                                
                                    </thead>
                        
                            </table>';
        }

        ?>


    </div>
    <p>&nbsp;</p>

</body>

</html>