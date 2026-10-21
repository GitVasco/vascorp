<?php
    require_once "../../controladores/pedidos.controlador.php";
    require_once "../../modelos/pedidos.modelo.php";

    /*
    todo: traemos todos lso datos para el ticket
    */
    $fecha = isset($_GET["fecha"]) ? trim((string) $_GET["fecha"]) : "";
    $vend = isset($_GET["vend"]) ? trim((string) $_GET["vend"]) : "";

    $pedido = ($fecha !== "" && $vend !== "")
        ? ControladorPedidos::ctrMostrarTemporalFecVen($fecha, $vend)
        : [];
    if (!is_array($pedido)) {
        $pedido = [];
    }

    $cantidadPedidos = count($pedido);
    $nombreVendedor = "";
    if ($cantidadPedidos > 0 && !empty($pedido[0]["vendedor_nombre"])) {
        $nombreVendedor = trim((string) $pedido[0]["vendedor_nombre"]);
    }
    if ($nombreVendedor === "") {
        $nombreVendedor = "Usuario " . $vend;
    }
    $fechaTitulo = str_replace("/", "-", $fecha);
    $tituloPdf = "Pedidos " . $fechaTitulo . " " . $nombreVendedor;
?>
<html>

<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title><?php echo htmlspecialchars($tituloPdf, ENT_QUOTES, "UTF-8"); ?></title>
  <link href="css/ticket_v7.css" target="_blank" rel="stylesheet" type="text/css">
</head>

<body  onload="window.print();">
        <!-- codigo imprimir -->
        <div class="zona_impresion">

            <!-- Cabecera -->
            <table border="0" width="100%">

                <tr>
                    <td><strong>Corporación Vasco S.A.C</strong></td>

                    <td align="RIGHT"><strong><?php echo $fecha; ?></strong></td>

                </tr>  

            </table>
            
            <br>

            <table border="0" align="center">

                <tr>
                    <td><strong>PEDIDOS PROCESADOS</strong></td>
                </tr>
                <tr>
                    <td>Carga: <?php echo htmlspecialchars($fecha, ENT_QUOTES, "UTF-8"); ?> &middot; <?php echo htmlspecialchars($nombreVendedor, ENT_QUOTES, "UTF-8"); ?> &middot; <?php echo $cantidadPedidos; ?> <?php echo $cantidadPedidos === 1 ? "pedido" : "pedidos"; ?></td>
                </tr>

            </table>

            <table border="" style="border:2px solid;" width="100%">

                <tr>
                    <td style="width:4%;"><strong>TD</strong></td>
                    <td style="width:9%;"><strong>Nro. Doc.</strong></td>
                    <td style="width:8%;"><strong>Fecha</strong></td>
                    <td style="width:11%;"><strong>Cod. Cli.</strong></td>
                    <td style="width:30%;"><strong>Nombre</strong></td>
                    <td style="width:9%;" align="right"><strong>Total</strong></td>
                    <td><strong>Cond. Vta.</strong></td>
                </tr>  

            </table>     
            
            <table border="" style="border:2px solid;" width="100%">

                <?php

                    foreach ($pedido as $key => $value) {

                        echo '<tr>
                                    <td style="width:4%;">'.$value["tipo"].'</td>
                                    <td style="width:9%;">'.$value["codigo"].'</td>
                                    <td style="width:8%;">'.$value["fecha"].'</td>
                                    <td style="width:11%;">'.$value["cliente"].'</td>
                                    <td style="width:30%;">'.$value["nombre"].'</td>
                                    <td style="width:9%;" align="right">'.$value["total"].'</td>
                                    <td>'.$value["descripcion"].'</td>
                                </tr> ';

                    }

                ?>

            </table>             

        </div>

    </body>

</html>

