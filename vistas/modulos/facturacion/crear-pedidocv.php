<?php
$pedidoCodigo = $_GET["pedido"];

$pedido = ControladorPedidos::ctrMostrarTemporal($pedidoCodigo);
$item = "codigo";
$valor = $pedido["cliente"];
$clientes = ControladorClientes::ctrMostrarClientesP($item, $valor);

?>
<div class="content-wrapper crear-pedidocv-page">

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

        <div class="row crear-pedidocv-main">

            <!--=====================================
            EL FORMULARIO
            ======================================-->

            <div class="col-xs-12 col-md-7 crear-pedidocv-form-col">

                <div class="box box-success">

                    <div class="box-header with-border"></div>

                    <form role="form" method="post" class="formularioPedidoCV">

                        <div class="box-body">

                            <div class="box">

                                <?php

                                date_default_timezone_set('America/Lima');
                                $ahora = date('Y/m/d h:i:s');

                                ?>

                                <div class="crear-pedidocv-cabecera-pedido">

                                <p class="crear-pedidocv-cabecera-titulo"><i class="fa fa-file-text-o"></i> Cabecera del pedido</p>

                                <div class="crear-pedidocv-cabecera-grid">

                                <!--=====================================
                                ENTRADA DEL RESPONSABLE
                                ======================================-->

                                <div class="form-group crear-pedidocv-cabecera-campo">

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

                                <div class="form-group crear-pedidocv-cabecera-campo">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                        <?= '<input type="text" class="form-control input-sm" id="nuevoCodigo" name="nuevoCodigo" value="' . $pedidoCodigo . '" readonly>'; ?>


                                    </div>

                                </div>

                                <!--=====================================
                                ENTRADA DEL CLIENTE
                                ======================================-->
                                <?php if ($pedidoCodigo == "") : ?>
                                <p class="help-block small crear-pedidocv-cabecera-ayuda crear-pedidocv-fila-completa"><i class="fa fa-info-circle"></i> Abra el desplegable de <strong>Cliente</strong> para cargar el listado (la <strong>primera vez</strong> puede demorar unos segundos).</p>
                                <?php endif; ?>

                                <div class="form-group crear-pedidocv-cabecera-campo crear-pedidocv-fila-completa">
                                    <label for="seleccionarCliente" class="crear-pedidocv-label-campo">Cliente</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-users"></i></span>
                                        <input type="hidden" class="form-control input-sm" id="codCliente" name="codCliente" value="<?= $pedido["cliente"] ?>">
                                        <select class="form-control selectpicker" id="seleccionarCliente" name="seleccionarCliente" data-carga-clientes-al-abrir="1" data-live-search="true" data-size="10" required>
                                            <option value="">Seleccionar Cliente</option>
                                        </select>
                                    </div>
                                </div>


                                <!--=====================================
                                ENTRADA DEL VENDEDOR
                                ======================================-->

                                <div class="form-group crear-pedidocv-cabecera-campo crear-pedidocv-fila-completa">

                                    <label for="seleccionarVendedor" class="crear-pedidocv-label-campo">Vendedor</label>

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-shopping-cart"></i></span>

                                        <select class="form-control selectpicker" id="seleccionarVendedor" name="seleccionarVendedor" data-live-search="true" data-size="10" required>

                                            <?php

                                            $valor = $_GET["pedido"];
                                            $pedido = ControladorPedidos::ctrMostrarTemporal($valor);

                                            $vendedorPedido = ($pedido && isset($pedido["vendedor"])) ? trim((string) $pedido["vendedor"]) : "";

                                            if ($vendedorPedido === "") {
                                                echo '<option value="">Seleccione Vendedor</option>';
                                            }

                                            $vendedores = ControladorVendedores::ctrMostrarVendedores(null, null);

                                            usort($vendedores, function ($a, $b) {
                                                return strcmp($a["codigo"], $b["codigo"]);
                                            });

                                            foreach ($vendedores as $key => $value) {
                                                $selected = ($vendedorPedido !== "" && $value["codigo"] == $vendedorPedido) ? ' selected' : '';
                                                echo '<option value="' . $value["codigo"] . '"' . $selected . '>' . $value["codigo"] . ' - ' . $value["descripcion"] . '</option>';
                                            }


                                            ?>

                                        </select>

                                    </div>

                                </div>

                                </div><!-- .crear-pedidocv-cabecera-grid -->

                                </div><!-- .crear-pedidocv-cabecera-pedido -->

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
                                <div class="crear-pedidocv-lista-bloque">

                                <div class="form-group buscador crear-pedidocv-buscador" id="elid">
                                    <div class="row">
                                        <label for="buscador" class="col-xs-12 col-sm-2 col-md-1 control-label">Buscar:</label>
                                        <div class="col-xs-12 col-sm-10 col-md-11">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="buscador" name="buscador" />
                                                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--=====================================
                                ENTRADA PARA AGREGAR PRODUCTO
                                ======================================-->

                                <div class="nuevoProductoPedido crear-pedidocv-detalle-scroll">

                                    <table class="table table-condensed table-hover crear-pedidocv-tabla-lineas">
                                        <colgroup>
                                            <col class="crear-pedidocv-col-item">
                                            <col class="crear-pedidocv-col-cant">
                                            <col class="crear-pedidocv-col-punit-sin">
                                            <col class="crear-pedidocv-col-punit-con">
                                            <col class="crear-pedidocv-col-total">
                                            <col class="crear-pedidocv-col-total-igv">
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th class="crear-pedidocv-col-item">Artículo</th>
                                                <th class="text-right crear-pedidocv-col-cant crear-pedidocv-th-num">Cant.</th>
                                                <th class="text-right crear-pedidocv-col-punit-sin crear-pedidocv-th-num">Unit. s/IGV</th>
                                                <th class="text-right crear-pedidocv-col-punit-con crear-pedidocv-th-num">Unit. c/IGV</th>
                                                <th class="text-right crear-pedidocv-col-total crear-pedidocv-th-num">Total s/IGV</th>
                                                <th class="text-right crear-pedidocv-col-total-igv crear-pedidocv-th-num">Total c/IGV</th>
                                            </tr>
                                        </thead>
                                        <tbody id="updDiv">
                                        <?php

                                        $listaArtPed = ControladorPedidos::ctrMostrarDetallesTemporalB($_GET["pedido"]);

                                        foreach ($listaArtPed as $articuloPedido) {
                                            $total_detalle = $articuloPedido["cantidad"] * $articuloPedido["precio"];
                                            $packingEsc = htmlspecialchars($articuloPedido["packing"], ENT_QUOTES, "UTF-8");
                                            $articuloEsc = htmlspecialchars($articuloPedido["articulo"], ENT_QUOTES, "UTF-8");
                                            $precioUnit = (float) $articuloPedido["precio"];
                                            $cantidad = (int) $articuloPedido["cantidad"];
                                            $totalLinea = round($total_detalle, 2);
                                            $totalIgv = round($total_detalle * 1.18, 2);
                                            $precioConIgv = round($precioUnit * 1.18, 4);

                                            echo '<tr class="mundito">';
                                            echo '<td class="crear-pedidocv-celda-articulo">';
                                            echo '<span class="crear-pedidocv-linea-desc" title="' . $packingEsc . '">' . $packingEsc . '</span>';
                                            echo '<input type="text" class="crear-pedidocv-campo-sistema nuevaDescripcionArticulo" articulo="' . $articuloEsc . '" value="' . $packingEsc . '" articuloP="' . $articuloEsc . '" readonly required tabindex="-1" aria-hidden="true">';
                                            echo '<input type="text" class="crear-pedidocv-campo-sistema nuevoPunit" value="' . $precioUnit . '" readonly tabindex="-1" aria-hidden="true">';
                                            echo '<input type="text" class="crear-pedidocv-campo-sistema nuevoPunitC" value="' . $precioConIgv . '" readonly tabindex="-1" aria-hidden="true">';
                                            echo '<input type="text" class="crear-pedidocv-campo-sistema nuevoTotalC" value="' . $totalIgv . '" readonly tabindex="-1" aria-hidden="true">';
                                            echo '</td>';
                                            echo '<td class="text-right crear-pedidocv-celda-num">' . $cantidad;
                                            echo '<input type="number" class="crear-pedidocv-campo-sistema nuevaCantidadArtPed" min="1" value="' . $cantidad . '" artPed="' . htmlspecialchars($articuloPedido["pedidos"], ENT_QUOTES, "UTF-8") . '" nuevoArtPed="0" required readonly tabindex="-1" aria-hidden="true">';
                                            echo '</td>';
                                            echo '<td class="text-right crear-pedidocv-celda-num">' . number_format($precioUnit, 4, '.', '') . '</td>';
                                            echo '<td class="text-right crear-pedidocv-celda-num">' . number_format($precioConIgv, 4, '.', '') . '</td>';
                                            echo '<td class="text-right crear-pedidocv-celda-num ingresoPrecio">' . number_format($totalLinea, 2);
                                            echo '<input type="text" class="crear-pedidocv-campo-sistema nuevoPrecioArticulo" precioReal="' . $precioUnit . '" value="' . $totalLinea . '" readonly required tabindex="-1" aria-hidden="true">';
                                            echo '</td>';
                                            echo '<td class="text-right crear-pedidocv-celda-num"><strong>' . number_format($totalIgv, 2) . '</strong></td>';
                                            echo '</tr>';
                                        }

                                        ?>
                                        </tbody>
                                    </table>

                                </div>

                                </div><!-- .crear-pedidocv-lista-bloque -->

                                <input type="hidden" id="listaProductosPedidos" name="listaProductosPedidos">

                                <hr class="crear-pedidocv-hr-lista">

                                <div class="crear-pedidocv-zona-final">

                                <p class="crear-pedidocv-zona-final-titulo"><i class="fa fa-calculator"></i> Resumen y cierre</p>

                                <div class="crear-pedidocv-totales-panel">

                                <p class="crear-pedidocv-totales-titulo"><i class="fa fa-calculator"></i> Totales del pedido</p>

                                <div class="row crear-pedidocv-totales" id="updDivC">

                                    <!--=====================================
                                    SUB TOTALES Y TOTALES
                                    ======================================-->

                                    <div class="form-group row">

                                        <!--=====================================
                                        TOTAL BRUTO
                                        ======================================-->

                                        <div class="form-group crear-pedidocv-total-fila crear-pedidocv-total-fila--simple">

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

                                        <div class="form-group crear-pedidocv-total-fila crear-pedidocv-total-fila--split">

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

                                        <div class="form-group crear-pedidocv-total-fila crear-pedidocv-total-fila--simple">

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

                                        <div class="form-group crear-pedidocv-total-fila crear-pedidocv-total-fila--split">

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

                                        <div class="form-group crear-pedidocv-total-fila crear-pedidocv-total-fila--simple crear-pedidocv-total-fila--final">

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

                                </div><!-- .crear-pedidocv-totales-panel -->

                                <div class="crear-pedidocv-sep-totales-cierre" aria-hidden="true"></div>

                                <!--=====================================
                                CIERRE: CONDICIÓN Y AGENCIA
                                ======================================-->

                                <div class="crear-pedidocv-cierre-pedido">

                                    <p class="crear-pedidocv-cierre-titulo"><i class="fa fa-clipboard"></i> Datos para crear el pedido</p>

                                    <p class="help-block small crear-pedidocv-cierre-ayuda">Indique la <strong>condición de venta</strong>. La agencia de transportes es opcional si aún no la define.</p>

                                    <div class="row crear-pedidocv-cierre-campos">

                                        <div class="col-xs-12 col-sm-6">

                                <div class="form-group crear-pedidocv-campo-cierre">

                                    <label for="condicionVenta">Condición de venta <span class="text-danger" title="Obligatorio">*</span></label>

                                        <select class="form-control selectpicker crear-pedidocv-select-cierre" id="condicionVenta" name="condicionVenta" data-live-search="true" data-width="100%" data-size="8" title="Seleccionar condición de venta" required>

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

                                                echo '<option value="">Seleccionar condición de venta…</option>';
                                                //var_dump($condiciones);

                                                foreach ($condiciones as $key => $value) {

                                                    echo '<option value="' . $value["id"] . '">' . $value["codigo"] . ' - ' . $value["descripcion"] . '</option>';
                                                }
                                            }

                                            ?>

                                        </select>

                                </div>

                                        </div>

                                        <div class="col-xs-12 col-sm-6">

                                <div class="form-group crear-pedidocv-campo-cierre">

                                    <label for="agencia">Agencia de transportes <span class="crear-pedidocv-etiq-opcional">opcional</span></label>

                                        <?php



                                        $valor = $_GET["pedido"];

                                        $pedido = ControladorPedidos::ctrMostrarTemporal($valor);
                                        //var_dump("pedido", $pedido["agencia"]);

                                        if ($pedido["agencia"] > 0) {

                                            echo '<select class="form-control selectpicker crear-pedidocv-select-cierre" id="agencia" name="agencia" data-live-search="true" data-width="100%" data-size="8" title="Seleccionar agencia">';

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

                                            echo '<select class="form-control selectpicker crear-pedidocv-select-cierre" id="agencia" name="agencia" data-live-search="true" data-width="100%" data-size="8" title="Seleccionar agencia">';

                                            $item = null;
                                            $valor = null;

                                            $agencias = ControladorAgencias::ctrMostrarAgencias($item, $valor);

                                            //var_dump($agencias);

                                            echo '<option value="">Seleccionar agencia…</option>';

                                            foreach ($agencias as $key => $value) {

                                                echo '<option value="' . $value["id"] . '">' . $value["id"] . ' - ' . $value["nombre"] . '</option>';
                                            }
                                        }

                                        ?>

                                        </select>

                                </div>

                                        </div>

                                    </div><!-- .crear-pedidocv-cierre-campos -->

                                </div><!-- .crear-pedidocv-cierre-pedido -->

                                </div><!-- .crear-pedidocv-zona-final -->

                            </div>

                        </div>

                        <div class="crear-pedidocv-barra-acciones">

                            <button onclick="history.back()" type="button" class="btn btn-default crear-pedidocv-btn crear-pedidocv-btn-cancelar">
                                <i class="fa fa-times"></i> Cancelar
                            </button>

                            <button type="submit" class="btn btn-primary crear-pedidocv-btn crear-pedidocv-btn-crear">
                                <i class="fa fa-check"></i> Crear pedido
                            </button>

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

            <div class="col-xs-12 col-md-5 crear-pedidocv-panel-modelos">

                <div class="box box-warning">

                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-th"></i> Grilla por modelo</h3>
                    </div>

                    <div class="box-body crear-pedidocv-panel-modelos-body">

                        <?php
                        $codigoImprimirGrilla = isset($_GET["pedido"]) ? trim((string) $_GET["pedido"]) : "";
                        ?>
                        <div class="crear-pedidocv-toolbar-modelo">
                            <label for="modelo" class="control-label">Modelo</label>
                            <div class="crear-pedidocv-campo-modelo">
                                <input type="text" class="form-control input-sm" id="modelo" name="modelo">
                            </div>
                            <div class="crear-pedidocv-toolbar-modelo-acciones btn-group" role="group" aria-label="Acciones de grilla">
                                <button type="button" class="btn btn-default btn-sm crear-pedidocv-btn btnImprimirPedido" codigo="<?php echo htmlspecialchars($codigoImprimirGrilla, ENT_QUOTES, "UTF-8"); ?>" title="Imprimir pedido" <?php echo ($codigoImprimirGrilla === "") ? "disabled" : ""; ?>>
                                    <i class="fa fa-print"></i> Imprimir
                                </button>
                                <button type="button" class="btn btn-default btn-sm crear-pedidocv-btn refreshDetalle" pedido="<?php echo htmlspecialchars($_GET["pedido"], ENT_QUOTES, "UTF-8"); ?>" title="Actualizar grilla">
                                    <i class="fa fa-refresh"></i>
                                </button>
                                <button type="button" class="btn btn-primary btn-sm crear-pedidocv-btn modificarArtPedC" data-toggle="modal" data-target="#modalModificarClienteP">
                                    <i class="fa fa-plus"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <p class="help-block small crear-pedidocv-help-modelo"><i class="fa fa-info-circle"></i> Tras escribir el modelo, pulse <strong>Enter</strong> para abrir el detalle (mismo efecto que <strong>Agregar</strong>).</p>

                        <div class="crear-pedidocv-zona-scroll" id="updDivB">

                        <div class="crear-pedidocv-grilla-inner">
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

                            <div class="zona_impresion crear-pedidocv-grilla-wrap">

                                <?php
                                $respuestas = ModeloPedidos::mdlPedidoImpresionC($codigo);
                                ?>

                                <table class="tablaVerPed crear-pedidocv-grilla-table table table-condensed">

                                    <thead>
                                        <tr>
                                            <th class="col-modelo"></th>
                                            <th class="col-color"></th>
                                            <th class="col-talla text-center">S</th>
                                            <th class="col-talla text-center">M</th>
                                            <th class="col-talla text-center">L</th>
                                            <th class="col-talla text-center">XL</th>
                                            <th class="col-talla text-center">XXL</th>
                                            <th class="col-talla text-center">XS</th>
                                            <th class="col-talla text-center"></th>
                                            <th class="col-talla text-center"></th>
                                            <th class="col-talla text-center"></th>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th class="col-talla text-center">28</th>
                                            <th class="col-talla text-center">30</th>
                                            <th class="col-talla text-center">32</th>
                                            <th class="col-talla text-center">34</th>
                                            <th class="col-talla text-center">36</th>
                                            <th class="col-talla text-center">38</th>
                                            <th class="col-talla text-center">40</th>
                                            <th class="col-talla text-center">42</th>
                                            <th></th>
                                        </tr>
                                        <tr class="crear-pedidocv-grilla-head-labels">
                                            <th class="col-modelo text-left">Modelo</th>
                                            <th class="col-color">Color</th>
                                            <th class="col-talla text-center">3</th>
                                            <th class="col-talla text-center">4</th>
                                            <th class="col-talla text-center">6</th>
                                            <th class="col-talla text-center">8</th>
                                            <th class="col-talla text-center">10</th>
                                            <th class="col-talla text-center">12</th>
                                            <th class="col-talla text-center">14</th>
                                            <th class="col-talla text-center">16</th>
                                            <th class="col-total">TOTAL</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                <?php
                                $prevModelo = null;
                                $buttonId = 0;
                                foreach ($respuestas as $row) {
                                    $sepModelo = ($prevModelo !== null && $prevModelo != $row['modelo']);
                                    $trClass = $sepModelo ? ' crear-pedidocv-sep-modelo' : '';

                                    echo "<tr class=\"crear-pedidocv-grilla-fila{$trClass}\">";
                                    echo "<td class=\"col-modelo text-left\"><button type=\"button\" class=\"btn btn-link btn-link-grilla\" id=\"modeloButton$buttonId\" pedido=\"{$codigo}\" modelo=\"{$row["modelo"]}\">" . htmlspecialchars($row['modelo'], ENT_QUOTES, 'UTF-8') . "</button></td>";
                                    echo "<td class=\"col-color\">" . htmlspecialchars($row['color'], ENT_QUOTES, 'UTF-8') . "</td>";

                                    for ($i = 1; $i <= 8; $i++) {
                                        $key = 't' . $i;
                                        if ($row[$key] != 0) {
                                            echo "<td class=\"col-talla text-center\"><button type=\"button\" class=\"btn btn-link btn-link-grilla\" id=\"button$buttonId\" pedido=\"{$codigo}\" articulo=\"{$row["modelo"]}{$row["cod_color"]}{$i}\">" . (int) $row[$key] . "</button></td>";
                                            $buttonId++;
                                        } else {
                                            echo "<td class=\"col-talla\"></td>";
                                        }
                                    }

                                    echo "<td class=\"col-total text-center\">" . (int) $row['total'] . "</td>";
                                    echo "</tr>";

                                    $prevModelo = $row['modelo'];
                                }
                                ?>
                                    </tbody>

                                    <tfoot>
                                        <tr class="crear-pedidocv-grilla-totales">
                                            <th class="col-modelo text-left">TOTALES</th>
                                            <th class="col-color text-left">PEDIDO</th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t1"]; ?></th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t2"]; ?></th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t3"]; ?></th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t4"]; ?></th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t5"]; ?></th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t6"]; ?></th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t7"]; ?></th>
                                            <th class="col-talla text-center"><?php echo (int) $totales["t8"]; ?></th>
                                            <th class="col-total text-center"><?php echo (int) $totales["total"]; ?></th>
                                        </tr>
                                    </tfoot>

                                </table>

                            </div>

                        </div>

                        </div><!-- #updDivB -->

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

    <div class="modal-dialog crear-pedidocv-modal-ancho">

        <div class="modal-content">

            <!-- <form role="form" method="post" class="formularioPedido"> -->
            <form role="form" method="post" class="formularioPedido">

                <!--=====================================
                CABEZA DEL MODAL
                ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle Artículos</h4>
                    <small style="opacity:.95; display:block; margin-top:4px;">
                        Stock visual (no bloquea; descuenta pedidos y lo ya cargado aquí):
                        <span class="label" style="background:#d4edda;color:#155724;">hay</span>
                        <span class="label" style="background:#fff3cd;color:#856404;">poco (≤5)</span>
                        <span class="label" style="background:#f8d7da;color:#721c24;">sin stock</span>
                        <span class="label" style="background:#e2d5f1;color:#4a2c6a;">descontinuado</span>
                        — pase el mouse sobre la talla para ver el detalle
                    </small>

                </div>

                <style>
                    #modalModificarClienteP .tablaColTal input.stock-ok {
                        background-color: #d4edda;
                        border-color: #28a745;
                    }
                    #modalModificarClienteP .tablaColTal input.stock-bajo {
                        background-color: #fff3cd;
                        border-color: #ffc107;
                    }
                    #modalModificarClienteP .tablaColTal input.stock-cero {
                        background-color: #f8d7da;
                        border-color: #dc3545;
                    }
                    #modalModificarClienteP .tablaColTal input.stock-descontinuado {
                        background-color: #e2d5f1;
                        border-color: #7b5ea7;
                        color: #4a2c6a;
                        font-weight: 600;
                    }
                    #modalModificarClienteP .tablaColTal input.stock-ok:focus,
                    #modalModificarClienteP .tablaColTal input.stock-bajo:focus,
                    #modalModificarClienteP .tablaColTal input.stock-cero:focus,
                    #modalModificarClienteP .tablaColTal input.stock-descontinuado:focus {
                        outline: none;
                        box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 0 2px rgba(60,141,188,.25);
                    }
                </style>

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
                            if (typeof pedidoCvRecargarDetallePedido === "function") {
                                pedidoCvRecargarDetallePedido();
                            }
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
                            if (typeof pedidoCvRecargarDetallePedido === "function") {
                                pedidoCvRecargarDetallePedido();
                            }
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