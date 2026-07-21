<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link href="css/ticket_v8.css" target="_blank" rel="stylesheet" type="text/css">
</head>

<body>
    <!-- <body onload="window.print();"> -->
    <?php
    require_once "../../controladores/cuentas.controlador.php";
    require_once "../../modelos/cuentas.modelo.php";

    require_once "../../extensiones/cantidad_en_letras.php";

    //declaramos la zona horaria
    date_default_timezone_set('America/Lima');

    /* 
    todo: traemos todos lso datos para el ticket
    */
    $cliente = $_GET["cliente"];
    $linea = $_GET["linea"];
    #var_dump($cliente);

    /*
     * Config única de vendedores:
     * - marca: etiqueta en el detalle (JACKYFORM / ROSAFLOR / VASCO / ELASTICOS)
     * - opcion: 1 JackyForm | 2 Rosaflor | 4 22A/26A
     *   La opción 3 (Ambos) = 1 + 2
     */
    $configVendedores = [
        '00'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '00A' => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '00B' => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '01'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '02'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '03'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '04'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '05'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '07'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '07A' => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '14'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '15'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '19'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '21'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '22'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '22A' => ['marca' => 'JACKYFORM', 'opcion' => '4'],
        '23'  => ['marca' => 'ELASTICOS', 'opcion' => '1'],
        '24J' => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '25'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '27'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '31'  => ['marca' => 'JACKYFORM', 'opcion' => '1'],
        '18'  => ['marca' => 'VASCO',     'opcion' => '2'],
        '18A' => ['marca' => 'ROSAFLOR',  'opcion' => '2'],
        '24'  => ['marca' => 'ROSAFLOR',  'opcion' => '2'],
        '26'  => ['marca' => 'ROSAFLOR',  'opcion' => '2'],
        '26A' => ['marca' => 'ROSAFLOR',  'opcion' => '4'],
        '28'  => ['marca' => 'ROSAFLOR',  'opcion' => '2'],
        '30'  => ['marca' => 'ROSAFLOR',  'opcion' => '2'],
        '32'  => ['marca' => 'ROSAFLOR',  'opcion' => '2'],
    ];

    $logosLinea = [
        '1' => 'jackyform_paloma2.png',
        '2' => 'rosaflor.png',
        '3' => 'jackyform_paloma2.png',
        '4' => 'jackyform_paloma2.png',
    ];

    $lineaConsulta = in_array($linea, ['1', '2', '3', '4'], true) ? $linea : '3';
    $logo = $logosLinea[$lineaConsulta];

    $codigos = [];
    foreach ($configVendedores as $codigo => $cfg) {
        if ($lineaConsulta === '3') {
            if ($cfg['opcion'] === '1' || $cfg['opcion'] === '2') {
                $codigos[] = $codigo;
            }
        } elseif ($cfg['opcion'] === $lineaConsulta) {
            $codigos[] = $codigo;
        }
    }
    $vendedor = "'" . implode("','", $codigos) . "'";

    $ctaCab = Controladorcuentas::ctrEstadoCuentaCab($cliente, $vendedor);
    #var_dump($ctaCab);
    $ctaDet = Controladorcuentas::ctrEstadoCuentaDet($cliente, $vendedor);
    #var_dump($ctaDet);

    $hoy = date("d-m-y");

    $montoTotal = 0;

    ?>
    <div class="<!-- zona_impresion -->">
        <!-- codigo imprimir -->

        <?php

        echo ' <table border="0" align="left" width="1000px">

                        <thead>
                    
                            <tr>
                        
                                <th style="text-align:left;" colspan="11">CORPORACION VASCO S.A.C.</th>
                                <img src="../../vistas/img/plantilla/' . $logo . '" width="200px" height="100px">
                        
                            </tr>

                            <tr>
                        
                                <th style="text-align:left;" colspan="11">Área de créditos y cobranzas</th>
                        
                            </tr>                            
                        
                            <tr>
                        
                                <th style="width:10%;text-align:left;">Cod. Cliente</th>
                                <td style="width:50%" colspan="4">' . $ctaCab["cliente"] . '</td>
                                <th colspan="1"></th>
                                <th style="text-align:right;">FECHA</th>
                                <td style="width:10%;text-align:right;" colspan="4">' . $hoy . '</td>
                        
                            </tr>
                        
                            <tr>
                        
                                <th style="width:10%;text-align:left;">CLIENTE:</th>
                                <td style="width:50%" colspan="4">' . $ctaCab["nombre"] . '</td>
                                <th colspan="1"></th>
                                <th style="text-align:right;">Deuda Total:</th>
                                <th style="width:10%;text-align:right;" colspan="4">S/ ' . number_format($ctaCab["monto_total"], 2) . '</td>                             

                            </tr>
                        
                            <tr>
                        
                                <th style="width:10%;text-align:left;">DIRECCIÓN:</th>
                                <td style="width:50%" colspan="10">' . $ctaCab["direccion"] . '</td>

                            </tr>                            
                    
                            <tr>
                        
                                <th style="width:10%;text-align:left;">ZONA:</th>
                                <td style="width:50%" colspan="10">' . $ctaCab["nom_ubigeo"] . '</td>

                            </tr>    

                            <tr>
                        
                                <th style="width:10%;text-align:left;">RUC/DNI:</th>
                                <td style="width:50%" colspan="10">' . $ctaCab["documento"] . '</td>

                            </tr>  
                            
                            <tr>
                        
                                <th style="width:10%;text-align:left;">TELÉFONO:</th>
                                <td style="width:50%" colspan="10">' . $ctaCab["telefono"] . '</td>

                            </tr>  
                            
                            <tr>
                        
                                <th style="width:10%;text-align:left;"></th>
                                <th style="width:50%" colspan="10">Cta Recaudadora - BCP: 191-1553564-0-64</th>

                            </tr>                            

                        </thead>
                    
                </table>';

        echo '<br>';
        echo '<br>';

        echo '<table border="1" align="left" width="1000px">

                <thead>
                    <tr></tr>

                    <tr>

                        <th style="width:8%;text-align:left;">T/D</th>
                        <th style="width:12%;text-align:left;">DOCUMENTO</th>
                        <th style="width:8%;text-align:left;">FECHA EMISIÓN</th>
                        <th style="width:8%;text-align:left;">FECHA VEN.</th>
                        <th style="width:12%;text-align:left;">VEND.</th>
                        <th style="width:8%;text-align:left;">NRO ÚNICO</th>
                        <th style="width:8%;text-align:left;">BANCO</th>
                        <th style="width:8%;text-align:left;">MONTO TOTAL</th>
                        <th style="width:8%;text-align:left;">SALDO PENDIENTE</th>
                        <th style="width:7%;text-align:left;">GASTOS</th>
                        <th style="width:8%;text-align:left;">DEUDA TOTAL</th>
                        <th style="width:5%;text-align:left;">PROT.</th>
                        
                    </tr>
            
                </thead>
        
            </table>';

        echo '<table border="1" align="left" width="1000px">';

        foreach ($ctaDet as $key => $value) {

            $montoTotal += $value["monto"];

            $codVendedor = $value["vendedor"];
            $marcaLinea = isset($configVendedores[$codVendedor])
                ? $configVendedores[$codVendedor]["marca"]
                : "JACKYFORM";

            if ($value["protesta"] == "SI") {

                $prot = '<td style="width:5%;text-align:center;"><b>' . $value["protesta"] . '</b></td>';

                $gasto = '<td style="width:7%;text-align:right;"><b>S/ ' . number_format($value["gasto"], 2) . '</b></td>';
            } else {

                $prot = '<td style="width:5%;text-align:center;">' . $value["protesta"] . '</td>';

                $gasto = '<td style="width:7%;text-align:right;">S/ ' . number_format($value["gasto"], 2) . '</td>';
            }

            echo '<tr>
                                
                                <td style="width:8%;text-align:left;">' . $value["tipo_documento"] . '</td>
                                <td style="width:12%;text-align:left;">' . $value["num_cta"] . '</td>
                                <td style="width:8%;text-align:left;">' . $value["fecha"] . '</td>
                                <td style="width:8%;text-align:left;">' . $value["fecha_ven"] . '</td>
                                <td style="width:12%;text-align:left;">' . $value["vendedor"] . ' - ' . $marcaLinea . '</td>
                                <td style="width:8%;text-align:left;">' . $value["num_unico"] . '</td>
                                <td style="width:8%;text-align:center;">' . $value["banco"] . '</td>
                                <td style="width:8%;text-align:right;">S/ ' . number_format($value["monto"], 2) . '</td>
                                <td style="width:8%;text-align:right;">S/ ' . number_format($value["saldo"], 2) . '</td>
                                ' . $gasto . '
                                <td style="width:8%;text-align:right;"><b>S/ ' . number_format($value["monto_total"], 2) . '</b></td>
                                ' . $prot . '                                

                        </tr>';
        }

        echo '</table>';

        echo '<table border="1" align="left" width="1000px">

                <thead>
                    <tr></tr>

                    <tr>

                        <th style="width:8%;text-align:left;"></th>
                        <th style="width:12%;text-align:left;"></th>
                        <th style="width:8%;text-align:left;"></th>
                        <th style="width:8%;text-align:left;"></th>
                        <th style="width:12%;text-align:left;"></th>
                        <th style="width:8%;text-align:left;"></th>
                        <th style="width:8%;text-align:left;"></th>
                        <th style="width:8%;text-align:right;">S/ ' . number_format($montoTotal, 2) . '</th>
                        <th style="width:8%;text-align:right;">S/ ' . number_format($ctaCab["saldo"], 2) . '</th>
                        <th style="width:7%;text-align:right;">S/ ' . number_format($ctaCab["gastos"], 2) . '</th>
                        <th style="width:8%;text-align:right;">S/ ' . number_format($ctaCab["monto_total"], 2) . '</th>
                        <th style="width:5%;text-align:left;"></th>
                        
                    </tr>
            
                </thead>
        
            </table>';

        ?>


    </div>
    <p>&nbsp;</p>

</body>

</html>