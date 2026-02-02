<?php
$pedidoCodigo = $_GET["pedido"];

$pedido = ControladorPedidos::ctrMostrarTemporal($pedidoCodigo);
$item = "codigo";
$valor = $pedido["cliente"];
$clientes = ControladorClientes::ctrMostrarClientesP($item, $valor);

?>
<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Crear pedido

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Crear pedido</li>

        </ol>

    </section>

    <section class="content">

        <div class="row">

            <!--=====================================
            EL FORMULARIO
            ======================================-->

            <div class="col-lg-7 col-xs-12">

                <div class="box box-success">

                    <div class="box-header with-border"></div>

                    <form role="form" method="post" class="formularioPedidoCV">

                        <div class="box-body">

                            <div class="box">

                                <?php

                                date_default_timezone_set('America/Lima');
                                $ahora = date('Y/m/d h:i:s');

                                ?>

                                <!--=====================================
                                ENTRADA DEL RESPONSABLE
                                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                        <input type="text" class="form-control input-sm" id="nuevoResponsable" name="nuevoResponsable" value="<?php echo $_SESSION["nombre"]; ?>" readonly>

                                        <input type="hidden" name="idUsuario" id="idUsuario" value="<?php echo $_SESSION["id"]; ?>">

                                        <input type="hidden" name="fechaActual" value="<?php echo $ahora; ?>">

                                        <input type="hidden" name="lista" id="lista">

                                    </div>

                                </div>

                                <!--=====================================
                                ENTRADA DEL CODIGO
                                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                        <?= '<input type="text" class="form-control input-sm" id="nuevoCodigo" name="nuevoCodigo" value="' . $pedidoCodigo . '" readonly>'; ?>


                                    </div>

                                </div>

                                <!--=====================================
                                ENTRADA DEL CLIENTE
                                ======================================-->
                                <div class="form-group">
                                    <div class='progress progress-striped'>
                                        <div id='progressBar1' class='progress-bar' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100' style='width: 0%'>0%</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-users"></i></span>
                                        <input type="hidden" class="form-control input-sm" id="codCliente" name="codCliente" value="<?= $pedido["cliente"] ?>">
                                        <select class="form-control selectpicker" id="seleccionarCliente" name="seleccionarCliente" data-live-search="true" data-size="10" required>
                                            <option value="">Seleccionar Cliente</option>
                                        </select>
                                        <?php if ($pedidoCodigo == "") : ?>
                                            <button type='button' class='btn btn-primary btnCargarCliente'> Cargar</button>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <!--=====================================
                                ENTRADA DEL VENDEDOR
                                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-shopping-cart"></i></span>

                                        <select class="form-control selectpicker" id="seleccionarVendedor" name="seleccionarVendedor" data-live-search="true" data-size="10" required>

                                            <?php

                                            $valor = $_GET["pedido"];
                                            $pedido = ControladorPedidos::ctrMostrarTemporal($valor);

                                            if ($pedido["vendedor"] != "") {
                                                $vendedor = ControladorVendedores::ctrMostrarVendedores("codigo", $pedido["vendedor"]);
                                                echo '<option value="' . $vendedor["codigo"] . '">' . $vendedor["codigo"] . ' - ' . $vendedor["descripcion"] . '</option>';

                                                $vendedores = ControladorVendedores::ctrMostrarVendedores(null, null);
                                            } else {
                                                echo '<option value="">Seleccione Vendedor</option>';
                                                $vendedores = ControladorVendedores::ctrMostrarVendedores(null, null);
                                            }

                                            // Ordenamos los vendedores por descripción alfabéticamente
                                            usort($vendedores, function ($a, $b) {
                                                return strcmp($a["codigo"], $b["codigo"]);
                                            });

                                            // Ahora generamos las opciones ya ordenadas
                                            foreach ($vendedores as $key => $value) {
                                                echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . ' - ' . $value["descripcion"] . '</option>';
                                            }


                                            ?>

                                        </select>

                                    </div>

                                </div>

                                <!--=====================================
                                ENTRADA LA LISTA DE PRECIOS
                                ======================================-->

                                <?php

                                $valor = $_GET["pedido"];

                                $pedido = ControladorPedidos::ctrMostrarTemporal($valor);
                                #var_dump("pedido", $pedido);

                                if ($pedido["codigo"] != "") {

                                    echo '<input type="hidden" class="form-control input-sm" id="seleccionarLista" name="seleccionarLista" value="' . $pedido["lista"] . '" readonly>';
                                } else {

                                    echo '<input type="hidden" class="form-control input-sm" id="seleccionarLista" name="seleccionarLista" value="' . $pedido["lista"] . '" readonly>';
                                }

                                ?>
                                <div class=" form-group buscador" id="elid" style="padding-bottom:25px">
                                    <label for="" class="col-form-label col-lg-1">Buscar:</label>
                                    <div class="col-lg-11">
                                        <div class="input-group">

                                            <input type="text" class="form-control " id="buscador" name="buscador" />
                                            <div class="input-group-addon"><i class="fa fa-search"></i></div>
                                        </div>
                                    </div>
                                </div>
                                <!--=====================================
                                ENTRADA PARA AGREGAR PRODUCTO
                                ======================================-->

                                <div class="form-group row nuevoProductoPedido" style="height:500px; overflow: scroll;">

                                    <!--=====================================
                                            TITULOS
                                    ======================================-->

                                    <div class="box box-primary">

                                        <div class="row">

                                            <div class="col-xs-5">

                                                <label>Item</label>

                                            </div>

                                            <div class="col-xs-2">

                                                <label for="">Cantidad</label>

                                            </div>

                                            <div class="col-xs-2">

                                                <label for="">P. Unit</label>

                                            </div>

                                            <div class="col-xs-1">

                                                <label for="">Total</label>

                                            </div>

                                            <div class="col-xs-1">

                                                <label for="">U. IGV</label>

                                            </div>

                                            <div class="col-xs-1">

                                                <label for="">T. IGV</label>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="box box-primary" id="updDiv">
                                        <?php

                                        $listaArtPed = ControladorPedidos::ctrMostrarDetallesTemporalB($_GET["pedido"]);

                                        foreach ($listaArtPed as $articuloPedido) {
                                            $total_detalle = $articuloPedido["cantidad"] * $articuloPedido["precio"];

                                            echo '<div class="row mundito" style="padding:5px 15px">
                                                    <div class="col-xs-5" style="padding-right:0px">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">
                                                                <button type="button" class="btn btn-danger btn-xs quitarArtPed" articulo="' . $articuloPedido["articulo"] . '" disabled><i class="fa fa-times"></i></button>
                                                            </span>
                                                            <input type="text" class="form-control nuevaDescripcionArticulo input-sm" articulo="' . $articuloPedido["articulo"] . '"  value="' . $articuloPedido["packing"] . '" articuloP="' . $articuloPedido["articulo"] . '" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-2">
                                                        <input type="number" class="form-control nuevaCantidadArtPed input-sm"  min="1" value="' . $articuloPedido["cantidad"] . '" artPed="' . $articuloPedido["pedidos"] . '" nuevoArtPed="0" required readonly>
                                                    </div>
                                                    <div class="col-xs-2">
                                                        <input type="text" class="form-control nuevoPunit input-sm"  min="1" value="' . $articuloPedido["precio"] . '" readonly>
                                                    </div>                                                
                                                    <div class="col-xs-1 ingresoPrecio" style="padding-left:0px">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control nuevoPrecioArticulo input-sm" precioReal="' . $articuloPedido["precio"] . '"  value="' . round($total_detalle, 2) . '" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-1">
                                                        <input type="text" class="form-control nuevoPunitC input-sm"  min="1" value="' . ($articuloPedido["precio"] * 1.18) . '" readonly>
                                                    </div>
                                                    <div class="col-xs-1">
                                                        <input type="text" class="form-control nuevoTotalC input-sm"  min="1" value="' . round($total_detalle * 1.18, 2) . '" readonly>
                                                    </div>
                                                </div>';
                                        }

                                        ?>

                                    </div>
                                </div>

                                <input type="hidden" id="listaProductosPedidos" name="listaProductosPedidos">

                                <hr>

                                <div class="row" id="updDivC">

                                    <!--=====================================
                                    SUB TOTALES Y TOTALES
                                    ======================================-->

                                    <div class="form-group row">

                                        <!--=====================================
                                        TOTAL BRUTO
                                        ======================================-->

                                        <div class="form-group">

                                            <div class="col-xs-4">
                                            </div>

                                            <div class="col-xs-3">
                                                <div class="input-group pull-right">

                                                    <span class="form-control"><b>Op. Gravadas S/</b></span>

                                                </div>
                                            </div>

                                            <div class="col-xs-2">

                                                <input type="hidden">

                                            </div>

                                            <div class="col-xs-3">

                                                <div class="input-group">

                                                    <?php

                                                    $valor = $_GET["pedido"];

                                                    $totalArt = ControladorPedidos::ctrMostrarTemporalTotal($valor);

                                                    //var_dump($totalArt["totalArt"]);

                                                    echo '<input type="text" style="text-align:right;" min="1" class="form-control" id="nuevoSubTotalA" name="nuevoSubTotalA" value="' . number_format($totalArt["totalArt"], 2) . '" readonly required>';

                                                    echo '<input type="hidden" id="nuevoSubTotal" name="nuevoSubTotal" value="' . $totalArt["totalArt"] . '">';

                                                    ?>



                                                </div>

                                            </div>
                                        </div>

                                        <!--=====================================
                                        DESCUENTOS
                                        ======================================-->

                                        <div class="form-group">

                                            <div class="col-xs-4">
                                            </div>

                                            <div class="col-xs-3">
                                                <div class="input-group pull-right">

                                                    <span class="form-control"><b>Descuento %</b></span>

                                                </div>
                                            </div>

                                            <div class="col-xs-2">

                                                <?php

                                                $valor = $_GET["pedido"];

                                                $descuento = ControladorPedidos::ctrMostrarTemporal($valor);
                                                //var_dump($descuento["descuento_total"]);

                                                if ($descuento == false) {

                                                    //var_dump("hola 0");

                                                    echo '<input type="number" step="any" class="form-control" min="0" id="descPer" name="descPer" value="0">';
                                                } else if ($descuento["descuento_total"] == "0") {

                                                    //var_dump("hola 1");

                                                    echo '<input type="number" step="any" class="form-control" min="0" id="descPer" name="descPer" value="0">';
                                                } else {

                                                    //var_dump("hola 2");

                                                    $subD = $descuento["op_gravada"];
                                                    $descD = $descuento["descuento_total"];

                                                    $descN = $descD / $subD * 100;

                                                    //var_dump(round($descN,2));

                                                    echo '<input type="number" step="any" class="form-control" min="0" id="descPer" name="descPer" value="' . round($descN, 2) . '">';
                                                }

                                                ?>


                                            </div>

                                            <div class="col-xs-3">

                                                <div class="input-group">

                                                    <?php

                                                    $valor = $_GET["pedido"];

                                                    $descuento = ControladorPedidos::ctrMostrarTemporal($valor);
                                                    //var_dump($descuento["descuento_total"]);

                                                    if ($descuento == false) {

                                                        //var_dump("hola 0");

                                                        echo '<input type="text" style="text-align:right;" min="0" class="form-control" id="descTotal" name="descTotal" placeholder="0.00" readonly>';
                                                    } else if ($descuento["descuento_total"] == "0") {

                                                        //var_dump("hola 1");

                                                        echo '<input type="text" style="text-align:right;" min="0" class="form-control" id="descTotal" name="descTotal" placeholder="0.00" readonly>';
                                                    } else {

                                                        $decuentoR = round($descuento["descuento_total"], 2);

                                                        echo '<input type="text" style="text-align:right;" min="0" class="form-control" id="descTotal" name="descTotal" placeholder="0.00" value="' . $decuentoR . '" readonly>';
                                                    }

                                                    ?>


                                                </div>

                                            </div>
                                        </div>

                                        <!--=====================================
                                        SUB TOTAL
                                        ======================================-->

                                        <div class="form-group">

                                            <div class="col-xs-4">
                                            </div>

                                            <div class="col-xs-3">
                                                <div class="input-group pull-right">

                                                    <span class="form-control"><b>Sub Total S/</b></span>

                                                </div>
                                            </div>

                                            <div class="col-xs-2">

                                                <input type="hidden">

                                            </div>

                                            <div class="col-xs-3">

                                                <div class="input-group">

                                                    <?php

                                                    $valor = $_GET["pedido"];

                                                    $subTotalA = ControladorPedidos::ctrMostrarTemporal($valor);
                                                    //var_dump($subTotalA["sub_total"]);

                                                    if ($subTotalA == false) {

                                                        //var_dump("hola 0");

                                                        echo '<input type="text" style="text-align:right;" min="1" class="form-control" id="subTotal" name="subTotal" value="0" readonly>';
                                                    } else if ($subTotalA["descuento_total"] == "0") {

                                                        //var_dump("hola 1");

                                                        echo '<input type="text" style="text-align:right;" min="1" class="form-control" id="subTotal" name="subTotal" value="0" readonly>';
                                                    } else {

                                                        echo '<input type="text" style="text-align:right;" min="1" class="form-control" id="subTotal" name="subTotal" value="' . $subTotalA["sub_total"] . '" readonly>';
                                                    }

                                                    ?>

                                                </div>

                                            </div>
                                        </div>

                                        <!--=====================================
                                        IMPUESTO
                                        ======================================-->

                                        <div class="form-group">

                                            <div class="col-xs-4">
                                            </div>

                                            <div class="col-xs-3">
                                                <div class="input-group pull-right">

                                                    <span class="form-control"><b>IGV %</b></span>

                                                </div>
                                            </div>

                                            <div class="col-xs-2">

                                                <input type="number" step="any" class="form-control" min="1" id="impPer" name="impPer" value="18" readonly>

                                            </div>

                                            <div class="col-xs-3">

                                                <div class="input-group">


                                                    <?php
                                                    $valor = $_GET["pedido"];
                                                    $igvA = ControladorPedidos::ctrMostrarTemporal($valor);

                                                    $impTotal = 0;

                                                    if ($igvA !== false) {
                                                        if ($igvA["descuento_total"] == "0") {
                                                            $neto = $igvA["lista"] == "precio1" ? 0 : $totalArt["totalArt"] * 0.18;
                                                            $impTotal = round($neto, 2);
                                                        } else {
                                                            $impTotal = $igvA["igv"];
                                                        }
                                                    }

                                                    echo '<input type="text" style="text-align:right;" min="1" class="form-control" id="impTotal" name="impTotal" value="' . $impTotal . '" readonly>';
                                                    ?>


                                                </div>

                                            </div>
                                        </div>

                                        <!--=====================================
                                        TOTAL
                                        ======================================-->

                                        <div class="form-group">

                                            <div class="col-xs-4">
                                            </div>

                                            <div class="col-xs-3">
                                                <div class="input-group pull-right">

                                                    <span class="form-control"><b>Total S/</b></span>

                                                </div>
                                            </div>

                                            <div class="col-xs-2">

                                                <input type="hidden">

                                            </div>

                                            <div class="col-xs-3">

                                                <div class="input-group">


                                                    <?php
                                                    $valor = $_GET["pedido"];
                                                    $totalA = ControladorPedidos::ctrMostrarTemporal($valor);

                                                    if ($totalA == false) {
                                                        $nuevoTotal = 0;
                                                    } else if ($totalA["descuento_total"] == "0") {
                                                        $neto = $totalA["lista"] == "precio1" ? $totalArt["totalArt"] : $totalArt["totalArt"] * 1.18;
                                                        $nuevoTotal = round($neto, 2);
                                                    } else {
                                                        $nuevoTotal = $totalA["total"];
                                                    }

                                                    echo '<input type="text" style="text-align:right;" min="1" class="form-control" id="nuevoTotal" name="nuevoTotal" value="' . $nuevoTotal . '" readonly>';
                                                    ?>


                                                </div>

                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <hr>

                                <!--=====================================
                                ENTRADA MÉTODO DE PAGO
                                ======================================-->

                                <div class="form-group">

                                    <label>Condición de Venta</label>

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-money"></i></span>

                                        <select class="form-control selectpicker" id="condicionVenta" name="condicionVenta" data-live-search="true" required>

                                            <?php
                                            $valor = $_GET["pedido"];

                                            $pedido = ControladorPedidos::ctrMostrarTemporal($valor);
                                            //var_dump("pedido", $pedido["condicion_venta"]);

                                            if ($pedido["condicion_venta"] > 0) {

                                                $item = "id";
                                                $valor = $pedido["condicion_venta"];

                                                $condiciones = ControladorCondicionVentas::ctrMostrarCondicionVentas($item, $valor);
                                                //var_dump($condiciones["descripcion"]);

                                                echo '<option value="' . $condiciones["id"] . '">' . $condiciones["codigo"] . ' - ' . $condiciones["descripcion"] . '</option>';

                                                $cond2 = ControladorCondicionVentas::ctrMostrarCondicionVentas(null, null);

                                                //var_dump($cond2);

                                                foreach ($cond2 as $key => $value) {

                                                    echo '<option value="' . $value["id"] . '">' . $value["codigo"] . ' - ' . $value["descripcion"] . '</option>';
                                                }
                                            } else {

                                                $item = null;
                                                $valor = null;

                                                $condiciones = ControladorCondicionVentas::ctrMostrarCondicionVentas($item, $valor);

                                                echo '<option value="">Seleccione método de pago</option>';
                                                //var_dump($condiciones);

                                                foreach ($condiciones as $key => $value) {

                                                    echo '<option value="' . $value["id"] . '">' . $value["codigo"] . ' - ' . $value["descripcion"] . '</option>';
                                                }
                                            }

                                            ?>

                                        </select>

                                    </div>

                                </div>

                                <!--=====================================
                                ENTRADA LA AGENCIA
                                ======================================-->

                                <div class="form-group">

                                    <label>AGENCIA DE TRANSPORTES</label>

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-plane"></i></span>



                                        <?php



                                        $valor = $_GET["pedido"];

                                        $pedido = ControladorPedidos::ctrMostrarTemporal($valor);
                                        //var_dump("pedido", $pedido["agencia"]);

                                        if ($pedido["agencia"] > 0) {

                                            echo '<select class="form-control selectpicker" id="agencia" name="agencia" data-live-search="true">';

                                            $item = "id";
                                            $valor = $pedido["agencia"];

                                            $agencias = ControladorAgencias::ctrMostrarAgencias($item, $valor);

                                            //var_dump($agencias["nombre"]);

                                            echo '<option value="' . $agencias["id"] . '">' . $agencias["id"] . ' - ' . $agencias["nombre"] . '</option>';

                                            $cond2 = ControladorAgencias::ctrMostrarAgencias(null, null);

                                            //var_dump($cond2);

                                            foreach ($cond2 as $key => $value) {

                                                echo '<option value="' . $value["id"] . '">' . $value["id"] . ' - ' . $value["nombre"] . '</option>';
                                            }
                                        } else {

                                            echo '<select class="form-control selectpicker" id="agencia" name="agencia" data-live-search="true">';

                                            $item = null;
                                            $valor = null;

                                            $agencias = ControladorAgencias::ctrMostrarAgencias($item, $valor);

                                            //var_dump($agencias);

                                            echo '<option value="">Seleccionar Agencia</option>';

                                            foreach ($agencias as $key => $value) {

                                                echo '<option value="' . $value["id"] . '">' . $value["id"] . ' - ' . $value["nombre"] . '</option>';
                                            }
                                        }

                                        ?>

                                        </select>

                                    </div>

                                </div <br>

                            </div>

                        </div>

                        <div class="box-header with-border">

                            <button onclick="history.back()" type="button" class="btn btn-danger pull-left">Cancelar
                            </button>

                            <button type="submit" class="btn btn-primary pull-right">Crear Pedido</button>


                        </div>

                    </form>

                    <?php

                    $totalesPedido = new ControladorPedidos();
                    $totalesPedido->ctrCrearPedidoTotales();

                    ?>

                </div>

            </div>

            <!--=====================================
            LA TABLA DE PRODUCTOS
            ======================================-->

            <div class="col-lg-5 hidden-md hidden-sm hidden-xs">

                <div class="box box-warning">

                    <div class="box-header with-border"></div>

                    <div class="box-body">

                        <label for="" class="col-form-label col-lg-2">Modelo</label>
                        <div class="col-lg-4">
                            <input type="text" class="form-control input-md" id='modelo' name='modelo'>
                        </div>
                        <div class="form-group col-lg-2">
                            <button class='btn btn-primary btn-md modificarArtPedC' data-toggle='modal' data-target='#modalModificarClienteP'>Agregar</button>
                        </div>

                        <div class="form-group col-lg-2">
                            <button class='btn btn-success btn-md refreshDetalle' pedido='<?php echo $_GET["pedido"]; ?>'><i class="fa fa-refresh"></i></button>
                        </div>

                    </div>

                    <div class="box-body" id="updDivB">

                        <body>
                            <?php

                            require_once "controladores/pedidos.controlador.php";
                            require_once "modelos/pedidos.modelo.php";

                            /* 
                            * TRAEMOS LOS DATOS DEL PEDIDO
                            */
                            $codigo = $_GET["pedido"];
                            //var_dump($codigo);

                            $respuesta = ControladorPedidos::ctrPedidoImpresionCab($codigo);
                            //var_dump($respuesta["pedido"]);
                            //var_dump($respuesta);

                            $totales = ControladorPedidos::ctrPedidoImpresionTotales($codigo);
                            //var_dump($totales);

                            date_default_timezone_set("America/Lima");

                            //var_dump($respuesta["fecha"]);

                            $originalDate = $respuesta["fecha"];
                            $newDate = date("d/m/Y", strtotime($originalDate));
                            //var_dump($newDate);

                            ?>

                            <div class="zona_impresion">

                                <table border="1" align="left" width="700px">

                                    <thead>
                                        <tr>
                                            <th style="width:10%"></th>
                                            <th style="width:20%"></th>
                                            <th style="width:6%;text-align:center;">S</th>
                                            <th style="width:6%;text-align:center;">M</th>
                                            <th style="width:6%;text-align:center;">L</th>
                                            <th style="width:6%;text-align:center;">XL</th>
                                            <th style="width:6%;text-align:center;">XXL</th>
                                            <th style="width:6%;text-align:center;">XS</th>
                                            <th style="width:6%;text-align:center;"></th>
                                            <th style="width:6%;text-align:center;"></th>
                                            <th style="width:6%;text-align:center;"></th>
                                        </tr>

                                        <tr>
                                            <th style="width:10%"></th>
                                            <th style="width:20%"></th>
                                            <th style="width:6%;text-align:center;">28</th>
                                            <th style="width:6%;text-align:center;">30</th>
                                            <th style="width:6%;text-align:center;">32</th>
                                            <th style="width:6%;text-align:center;">34</th>
                                            <th style="width:6%;text-align:center;">36</th>
                                            <th style="width:6%;text-align:center;">38</th>
                                            <th style="width:6%;text-align:center;">40</th>
                                            <th style="width:6%;text-align:center;">42</th>
                                            <th style="width:6%;text-align:center;"></th>
                                        </tr>

                                        <tr>
                                            <th style="width:10%;text-align:left;">Modelo</th>
                                            <th style="width:20%">Color</th>
                                            <th style="width:6%;text-align:center;">3</th>
                                            <th style="width:6%;text-align:center;">4</th>
                                            <th style="width:6%;text-align:center;">6</th>
                                            <th style="width:6%;text-align:center;">8</th>
                                            <th style="width:6%;text-align:center;">10</th>
                                            <th style="width:6%;text-align:center;">12</th>
                                            <th style="width:6%;text-align:center;">14</th>
                                            <th style="width:6%;text-align:center;">16</th>
                                            <th style="width:6%">TOTAL</th>
                                        </tr>
                                    </thead>

                                </table>

                                <?php
                                $respuestas = ModeloPedidos::mdlPedidoImpresionC($codigo);

                                echo "<table border='1' class='tablaVerPed' style='border:dashed' align='left' width='700px'>";

                                $prevModelo = null;
                                $buttonId = 0;
                                foreach ($respuestas as $row) {
                                    if ($prevModelo !== null && $prevModelo != $row['modelo']) {
                                        echo "<tr><td colspan='13' style='background-color: black; height: 5px;'></td></tr>";
                                    }

                                    echo "<tr>";
                                    echo "<td style='width:10%;font-weight: bold;' class='text-left'><button style='margin:0; padding:0; font-size: 1.2em;' class='btn btn-link' id='modeloButton$buttonId' pedido='{$codigo}' modelo='{$row["modelo"]}'>" . $row['modelo'] . "</button></td>";
                                    echo "<td style='width:20%;font-weight: normal;'>" . $row['color'] . "</td>";

                                    for ($i = 1; $i <= 8; $i++) {
                                        $key = 't' . $i;
                                        if ($row[$key] != 0) {
                                            echo "<td style='width:6%;font-weight: bold;' class='text-center'><button style='margin:0; padding:0; font-size: 1.2em;' class='btn btn-link' id='button$buttonId' pedido='{$codigo}' articulo='{$row["modelo"]}{$row["cod_color"]}{$i}'>" . $row[$key] . "</button></td>";
                                            $buttonId++;
                                        } else {
                                            echo "<td style='width:6%;font-weight: bold;'></td>";
                                        }
                                    }

                                    echo "<td style='width:6%;font-weight: bold;'>" . $row['total'] . "</td>";
                                    echo "</tr>";

                                    $prevModelo = $row['modelo'];
                                }

                                echo "</table>";
                                ?>


                                <table border="1" align="left" width="700px">

                                    </thead>

                                    <tr>

                                        <th style="width:10%;text-align:left;">TOTALES</th>
                                        <th style="width:20%;text-align:left;">PEDIDO</th>
                                        <th style="width:6%"><?php echo $totales["t1"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["t2"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["t3"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["t4"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["t5"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["t6"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["t7"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["t8"]; ?></th>
                                        <th style="width:6%"><?php echo $totales["total"]; ?></th>

                                    </tr>

                                    </thead>

                                </table>

                                <br>



                            </div>
                            <p>&nbsp;</p>

                        </body>

                    </div>

                </div>


            </div>

        </div>

    </section>

</div>
<!--=====================================
MODAL MODIFICAR ARTICULOS
======================================-->

<div id="modalModificarClienteP" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 60% !important;">

        <div class="modal-content">

            <!-- <form role="form" method="post" class="formularioPedido"> -->
            <form role="form" method="post" class="formularioPedido">

                <!--=====================================
                CABEZA DEL MODAL
                ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle Artículos</h4>

                </div>

                <!--=====================================
                CUERPO DEL MODAL
                ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <div class="box box-primary">

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="modeloModalA" name="modeloModalA" readonly>

                                </div>

                            </div>

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-money"></i></span>

                                    <input type="text" class="form-control input-sm" id="precioA" name="precioA">

                                </div>

                            </div>

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="clienteA" name="clienteA" placeholder="Tiene que escoger el Cliente" required>

                                </div>

                            </div>

                            <div class="form-group col-lg-3">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="vendedorA" name="vendedorA" placeholder="Tiene que escoger el Vendedor" required>

                                    <input type="hidden" class="form-control input-sm" id="nLista" name="nLista" readonly>

                                    <input type="hidden" class="form-control input-sm" id="agenciaA" name="agenciaA" readonly>

                                    <input type="hidden" class="form-control input-sm" id="usuario" name="usuario" value="<?php echo $_SESSION["id"]; ?>">

                                    <!-- <input type="hidden" class="form-control input-sm" id="tal1" name="tal1">
                                    <input type="hidden" class="form-control input-sm" id="tal2" name="tal2">
                                    <input type="hidden" class="form-control input-sm" id="tal3" name="tal3">
                                    <input type="hidden" class="form-control input-sm" id="tal4" name="tal4">
                                    <input type="hidden" class="form-control input-sm" id="tal5" name="tal5">
                                    <input type="hidden" class="form-control input-sm" id="tal6" name="tal6">
                                    <input type="hidden" class="form-control input-sm" id="tal7" name="tal7">
                                    <input type="hidden" class="form-control input-sm" id="tal8" name="tal8"> -->

                                </div>

                            </div>


                            <?php

                            $pedido = $_GET["pedido"];

                            echo '<input type="hidden" class="form-control input-sm" id="pedido" name="pedido" value="' . $pedido . '" readonly>';


                            ?>


                        </div>

                        <div class="box box-warning col-lg-12">

                            <!-- TABLA DE DETALLES -->

                            <label>TABLA DETALLES</label>

                            <div class="box-body">

                                <table class="table table-bordered table-striped dt-responsive tablaColTal" width="100%">

                                    <thead>

                                        <tr>
                                            <th style="width:50px"></th>
                                            <th style="width:200px"></th>
                                            <th style="width:100px">S</th>
                                            <th style="width:100px">M</th>
                                            <th style="width:100px">L</th>
                                            <th style="width:100px">XL</th>
                                            <th style="width:100px">XXL</th>
                                            <th style="width:100px">XS</th>
                                            <th style="width:100px"></th>
                                            <th style="width:100px"></th>
                                        </tr>

                                        <tr>
                                            <th style="width:50px"></th>
                                            <th style="width:200px"></th>
                                            <th style="width:100px">28</th>
                                            <th style="width:100px">30</th>
                                            <th style="width:100px">32</th>
                                            <th style="width:100px">34</th>
                                            <th style="width:100px">36</th>
                                            <th style="width:100px">38</th>
                                            <th style="width:100px">40</th>
                                            <th style="width:100px">42</th>
                                        </tr>

                                        <tr>
                                            <th style="width:50px">Modelo</th>
                                            <th style="width:200px">Color</th>
                                            <th style="width:100px">3</th>
                                            <th style="width:100px">4</th>
                                            <th style="width:100px">6</th>
                                            <th style="width:100px">8</th>
                                            <th style="width:100px">10</th>
                                            <th style="width:100px">12</th>
                                            <th style="width:100px">14</th>
                                            <th style="width:100px">16</th>
                                        </tr>

                                    </thead>

                                    <tbody>
                                        <tr class="detalleCT">

                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="box box-success">

                    <div class="form-group col-lg-4">

                        <label> Total Unidades</label>

                        <div class="input-group">

                            <input type="text" name="totalCantidadA" id="totalCantidadA" readonly>


                        </div>

                    </div>

                    <div class="form-group col-lg-4">

                        <label> Total Soles</label>

                        <div class="input-group">

                            <input type="text" name="totalSolesA" id="totalSolesA" readonly>


                        </div>


                    </div>

                    <div class="form-group col-lg-4">

                        <label></label>

                        <div class="input-group">

                            <button type="button" class="btn btn-success pull-left btnCalCantA">Calcular</button>

                        </div>


                    </div>

                </div>

                <!--=====================================
                PIE DEL MODAL
                ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="button" id="guardarModelo" class="btn btn-primary">Guardar Modelo</button>

                </div>



            </form>

            <?php

            /* $crearPedido = new ControladorPedidos();
            $crearPedido->ctrCrearPedido(); */

            ?>

        </div>

    </div>

</div>

<!--=====================================
MODAL PARA GENERAR EL PEDIDO
======================================-->

<div id="modalGenerarPedido" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#008080; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Resumen de Pedido</h4>

                </div>

                <!--=====================================
            CUERPO DEL MODAL
            ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group">

                            <label>Código de Pedido</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-certificate"></i></span>

                                <input type="text" class="form-control input-sm" name="codigoM" id="codigoM" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group">

                            <label>Cliente</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <input type="text" class="form-control input-sm" name="codClienteM" id="codClienteM" required readonly>

                                <input type="text" class="form-control input-sm" name="nomClienteM" id="nomClienteM" required readonly>

                            </div>

                        </div>


                        <!-- ENTRADA PARA EL VENDEDOR-->

                        <div class="form-group">

                            <label>Vendedor</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-child"></i></span>

                                <input type="text" class="form-control input-sm" name="vendedorM" id="vendedorM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-12 pull-right">

                            <div>

                                <h4>
                                    <label>Totales</label>
                                </h4>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LOS TOTALES-->

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <span class="input-group-addon" style="width: 150px;">Op. Gravada <b>S/</b></span>

                                <input type="text" class="form-control input-sm" style="text-align:right;" name="opGravadaM" id="opGravadaM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <span class="input-group-addon" style="width: 150px;">Descuento <b>S/</b></span>

                                <input type="text" class="form-control input-sm" style="text-align:right;" name="descuentoM" id="descuentoM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <span class="input-group-addon" style="width: 150px;">Subtotal <b>S/</b></span>

                                <input type="text" class="form-control input-sm" style="text-align:right;" name="subTotalM" id="subTotalM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <span class="input-group-addon" style="width: 150px;">Igv <b>18%</b></span>

                                <input type="text" class="form-control input-sm" style="text-align:right;" name="igvM" id="igvM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <span class="input-group-addon" style="width: 150px;">Total <b>S/</b></span>

                                <input type="text" class="form-control input-sm" style="text-align:right;" name="totalM" id="totalM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <input type="hidden" class="form-control input-sm" style="text-align:right;" name="articulosM" id="articulosM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <input type="hidden" class="form-control input-lg" style="text-align:right;" name="condicionVentaM" id="condicionVentaM" required readonly>

                                <input type="hidden" class="form-control input-lg" style="text-align:right;" name="agenciaM" id="agenciaM" required readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-7 pull-right">

                            <div class="input-group">

                                <input type="hidden" class="form-control input-sm" name="usuarioM" id="usuarioM">

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
            PIE DEL MODAL
            ======================================-->

                <div class="modal-footer">

                    <button type="submit" class="btn btn-primary">Crear Pedido</button>

                </div>

            </form>


            <?php

            /* $totalesPedido = new ControladorPedidos();
            $totalesPedido->ctrCrearPedidoTotales(); */

            ?>


        </div>

    </div>

</div>

<script>
    window.document.title = "Crear pedido"
</script>

<script>
    $(document).ready(function() {
        $(document).on('click', '.btn-link', function() {
            let buttonId = $(this).attr('id');
            let articulo = $(this).attr('articulo');
            let modelo = $(this).attr('modelo');
            let pedido = $(this).attr('pedido');

            if (articulo && buttonId) {
                var datos = new FormData();
                datos.append("articuloC", articulo);
                datos.append("pedidoC", pedido);

                $.ajax({
                    url: "ajax/pedidos.ajax.php",
                    method: "POST",
                    data: datos,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function(respuesta) {
                        console.log("respuesta", respuesta);
                        if (respuesta == "ok") {
                            Command: toastr["error"]("El articulo fue eliminado");
                            $("#updDiv").load(" #updDiv"); //actualizas el div
                            $("#updDivC").load(" #updDivC"); //actualizas el div
                            $("#updDivB").load(" #updDivB"); //actualizas el div
                        }
                    },
                });
            }

            if (modelo && pedido) {
                console.log("🚀 ~ file: crear-pedidocv.php:1524 ~ $ ~ modelo:", modelo)
                var datos = new FormData();
                datos.append("modeloB", modelo);
                datos.append("pedidoB", pedido);

                $.ajax({
                    url: "ajax/pedidos.ajax.php",
                    method: "POST",
                    data: datos,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    success: function(respuesta) {
                        console.log("respuesta", respuesta);
                        if (respuesta == "ok") {
                            Command: toastr["error"]("El modelo fue eliminado");
                            $("#updDiv").load(" #updDiv"); //actualizas el div
                            $("#updDivC").load(" #updDivC"); //actualizas el div
                            $("#updDivB").load(" #updDivB"); //actualizas el div
                        }
                    },
                });
            }
        });
    });




    $('.nuevoProductoPedido').ready(function() {
        $('#buscador').keyup(function() {

            //console.log("hola mundo")

            var nombres = $('.nuevaDescripcionArticulo');
            //console.log(nombres.val())
            //console.log(nombres.length())

            var buscando = $(this).val();
            //console.log(buscando.length);

            var item = '';

            for (var i = 0; i < nombres.length; i++) {

                item = $(nombres[i]).val();
                item2 = $(nombres[i]).val().toLowerCase();
                // console.log(item);

                for (var x = 0; x < item.length; x++) {

                    if (buscando.length == 0 || item.indexOf(buscando) > -1 || item2.indexOf(buscando) > -1) {

                        $(nombres[i]).parents('.mundito').show();

                    } else {

                        $(nombres[i]).parents('.mundito').hide();

                    }
                }
            }
        });
    });
</script>