<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Editar Orden de Corte

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Editar orden de corte</li>

        </ol>

    </section>

    <section class="content">

        <div class="row">

            <!--=====================================
      EL FORMULARIO
      ======================================-->

            <div class="col-lg-5 col-xs-12">

                <div class="box box-success">

                    <div class="box-header with-border"></div>

                    <form role="form" method="post" class="formularioOrdenCorte">

                        <div class="box-body">

                            <div class="box">

                                <?php

                                $item = "codigo";
                                $valor = $_GET["codigo"];

                                $ordencorte = ControladorOrdenCorte::ctrMostrarOrdenCorte($item, $valor);
                                #var_dump("ordencorte", $ordencorte);

                                date_default_timezone_set('America/Lima');
                                $ahora = date('Y/m/d h:i:s');

                                ?>

                                <!--=====================================
                ENTRADA DEL VENDEDOR
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                        <b><input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo $_SESSION["nombre"]; ?>" readonly></b>

                                        <input type="hidden" name="idUsuario" value="<?php echo $_SESSION["id"]; ?>">

                                        <input type="hidden" name="fechaActual" value="<?php echo $ahora; ?>">

                                        <input type="hidden" name="codigoE" value="<?php echo $ordencorte["codigo"]; ?>">


                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA DEL CODIGO INTERNO
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                        <b><input type="text" class="form-control" id="editarCodigo" name="editarCodigo" value="<?php echo $ordencorte["codigo"]; ?>" readonly></b>


                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA DE LA CONFIGURACION
                ======================================-->

                                <div class="form-group">

                                    <div class="input-group">

                                        <span class="input-group-addon"><i class="fa fa-percent"></i></span>

                                        <b><input type="text" class="form-control" id="configuracion" name="configuracion" value="<?php echo $ordencorte["configuracion"]; ?>" readonly></b>

                                    </div>

                                </div>

                                <!--=====================================
                TITULOS
                ======================================-->

                                <div class="box box-primary">

                                    <div class="row">

                                        <div class="col-xs-6">

                                            <label>Articulo</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">Ord. Corte</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">SinProg</label>

                                        </div>

                                        <div class="col-xs-2">

                                            <label for="">Mes</label>

                                        </div>

                                    </div>

                                </div>

                                <!--=====================================
                ENTRADA PARA AGREGAR ARTICULOS
                ======================================-->

                                <div class="form-group row nuevoArticuloOC">

                                    <?php

                                    $listaArticuloOC = ControladorOrdenCorte::ctrMostrarDetallesOrdenCorte("ordencorte", $ordencorte["codigo"]);
                                    #var_dump("ordencorte", $ordencorte["codigo"]);
                                    #var_dump("listaArticuloOC", $listaArticuloOC);

                                    foreach ($listaArticuloOC as $key => $value) {

                                        $infoArticulo = ControladorArticulos::ctrMostrarArticulos($value["articulo"]);
                                        //var_dump("infoArticulo", $infoArticulo);
                                        $prodArticulo = ControladorArticulos::ctrMostrarProduccion($value["articulo"]);
                                        //var_dump($prodArticulo["prod"]);
                                        $vtaArticulo = ControladorArticulos::ctrMostrarVentas($value["articulo"]);
                                        #var_dump($prodArticulo["prod"]);

                                        $ocAntiguo = $infoArticulo["ord_corte"] - $value["cantidad"];
                                        #var_dump("ocAntiguo", $ocAntiguo);

                                        $proySum = $infoArticulo["proyeccion"] - ($infoArticulo["ord_corte"] + $infoArticulo["alm_corte"] + $infoArticulo["taller"] + $prodArticulo["prod"] + $value["cantidad"]);
                                        //var_dump($proySum);

                                        $pendienteReal = $infoArticulo["proyeccion"] - $prodArticulo["prod"] - $value["cantidad"];
                                        //var_dump($infoArticulo["proyeccion"],$prodArticulo["prod"]);
                                        $pendiente = $infoArticulo["proyeccion"] - $prodArticulo["prod"];

                                        $stockG = $infoArticulo["stockG"];
                                        #var_dump($stockG);

                                        $ventasG = $vtaArticulo["vtas"] + $infoArticulo["pedidos"];

                                        $mes = ($stockG + $value["cantidad"]) / ($ventasG * 1.3);
                                        #var_dump($mes);


                                        echo '<div class="row" style="padding:5px 15px">

                            <div class="col-xs-6" style="padding-right:0px">
                        
                              <div class="input-group">
                        
                                <span class="input-group-addon"><button type="button" class="btn btn-danger btn-xs quitarOC" articuloOC="' . $infoArticulo["articulo"] . '"><i class="fa fa-times"></i></button></span>
                        
                                <input type="text" class="form-control nuevaDescripcionProducto input-sm" articuloOC="' . $infoArticulo["articulo"] . '" value="' . $infoArticulo["packing"] . '" codigoAC="' . $infoArticulo["articulo"] . '" readonly required>
                        
                              </div>
                        
                            </div>
                        
                            <div class="col-xs-2">
                        
                              <input type="number" class="form-control nuevaCantidadArticuloOC input-sm" id="nuevaCantidadArticuloOC" min="1" value="' . $value["cantidad"] . '" ord_corte="' . $ocAntiguo . '" articulo="' . $infoArticulo["articulo"] . '" nuevoOrdCorte="' . $infoArticulo["ord_corte"] . '" required>
                        
                            </div>';

                                        if ($proySum > 0) {

                                            echo '<div class="col-xs-2 pendiente">

                              <input style="color:#008000; background-color:white;" type="text" class="form-control nuevoPendienteProy input-sm" id="' . $infoArticulo["articulo"] . '" value="' . $pendienteReal . '" pendienteReal="' . $pendiente . '" readonly></input>

                            </div>';
                                        } else {

                                            echo '<div class="col-xs-2 pendiente">

                              <input style="color:#FF0000; background-color:pink;" type="text" class="form-control nuevoPendienteProy input-sm" id="' . $infoArticulo["articulo"] . '" value="' . $pendienteReal . '" pendienteReal="' . $pendiente . '" readonly></input>

                            </div>';
                                        }

                                        if (round($mes, 2) < 2.1) {

                                            echo '<div class="col-xs-2 mes">

                              <input style="color:#8B0000; background-color:pink;" type="text" class="form-control nuevoMes input-sm" id="' . $infoArticulo["articulo"] . 'M" value="' . round($mes, 2) . '" mesReal="' . round($mes, 2) . '" stockG="' . $stockG . '" ventasG="' . $ventasG . '" readonly>                

                            </div>';
                                        } else {

                                            echo '<div class="col-xs-2 mes">

                              <input style="color:#8B0000; background-color:white;" type="text" class="form-control nuevoMes input-sm" id="' . $infoArticulo["articulo"] . 'M" value="' . round($mes, 2) . '" mesReal="' . round($mes, 2) . '" stockG="' . $stockG . '" ventasG="' . $ventasG . '" readonly>                

                            </div>';
                                        }



                                        echo '</div>';
                                    }


                                    ?>

                                </div>

                                <input type="hidden" id="listaArticulosOC" name="listaArticulosOC">

                                <div class="row">

                                    <!--=====================================
                  ENTRADA TOTAL
                  ======================================-->

                                    <div class="col-xs-6 pull-right">

                                        <table class="table">

                                            <thead>

                                                <tr>
                                                    <th>Total</th>
                                                </tr>

                                            </thead>

                                            <tbody>

                                                <tr>

                                                    <td style="width: 50%">

                                                        <div class="input-group">

                                                            <span class="input-group-addon"><i class="fa fa-scissors"></i></span>

                                                            <input type="text" min="1" class="form-control input-lg" id="nuevoTotalOrdenCorte" name="nuevoTotalOrdenCorte" total="<?php echo $ordencorte["total"]; ?>" value="<?php echo $ordencorte["total"]; ?>" readonly required>

                                                            <input type="hidden" name="totalOrdenCorte" id="totalOrdenCorte" value="<?php echo $ordencorte["total"]; ?>">


                                                        </div>

                                                    </td>

                                                </tr>

                                            </tbody>

                                        </table>

                                    </div>

                                </div>

                                <hr>

                                <br>

                            </div>

                        </div>

                        <div class="box-footer">

                            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-floppy-o"></i> Guardar Orden Corte</button>

                            <a href="ordencorte" id="cancel" name="cancel" class="btn btn-danger"><i class="fa fa-times-circle"></i> Cancelar</a>
                        </div>

                    </form>

                    <?php

                    $editarOrdenCorte = new ControladorOrdenCorte();
                    $editarOrdenCorte->ctrEditarOrdenCorte();

                    ?>


                </div>

            </div>

            <!--=====================================
      LA TABLA DE ARTICULOS
      ======================================-->

            <div class="col-lg-7 hidden-md hidden-sm hidden-xs">

                <div class="box box-warning">

                    <div class="box-header with-border"></div>

                    <div class="box-body">

                        <table class="table table-bordered table-striped table-condensed tablaArticulosOrdenCorte" width="100%">

                            <thead>

                                <tr>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th>Proy</th>
                                    <th>Prod</th>
                                    <th>Avance</th>
                                    <th>Stock</th>
                                    <th>Ped.</th>
                                    <th>En Taller</th>
                                    <th>Alm. Corte</th>
                                    <th>Ord. Corte</th>
                                    <th>Vtas 30d</th>
                                    <th>Xprog</th>
                                    <th style="width:10px">Acciones</th>
                                </tr>

                            </thead>



                        </table>

                    </div>

                </div>


            </div>

        </div>

    </section>

</div>