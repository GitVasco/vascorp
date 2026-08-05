<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Almacén de corte

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Almacén de corte</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">
            <div class="box-header with-border">
                <div class="col-lg-2">
                    <select name="selectModeloCorte" id="selectModeloCorte" class="form-control input-lg selectpicker" data-live-search="true" data-size="10">
                        <option value="">--------Seleccionar modelo-------</option>
                        <?php
                        $item = null;
                        $valor = null;

                        $modelo = ControladorModelos::ctrMostrarModelos($item, $valor);
                        foreach ($modelo as $key => $value) {
                            echo '<option value="' . $value["modelo"] . '">' . $value["modelo"] . " - " . $value["nombre"] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary btnLimpiarModeloCorte" name="btnLimpiarModeloCorte"><i class="fa fa-refresh"></i> Limpiar</button>
                </div>

                <button class="btn btn-outline-success pull-right btnReporteAlmacen" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Almacén de corte </button>
            </div>
            <div class="box-body">

                <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">

                <table class="table table-bordered table-striped dt-responsive tablaCortes" width="100%">

                    <thead>

                        <tr>

                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th style="text-align: center">S</th>
                            <th style="text-align: center">M</th>
                            <th style="text-align: center">L</th>
                            <th style="text-align: center">XL</th>
                            <th style="text-align: center">XXL</th>
                            <th style="text-align: center">XS</th>
                            <th style="text-align: center"></th>
                            <th style="text-align: center"></th>
                            <th></th>

                        </tr>

                        <tr>

                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th style="text-align: center">28</th>
                            <th style="text-align: center">30</th>
                            <th style="text-align: center">32</th>
                            <th style="text-align: center">34</th>
                            <th style="text-align: center">36</th>
                            <th style="text-align: center">38</th>
                            <th style="text-align: center">40</th>
                            <th style="text-align: center">42</th>
                            <th></th>

                        </tr>

                        <tr>

                            <th>
                                <center>Modelo</center>
                            </th>
                            <th>Nombre</th>
                            <th>Color</th>
                            <th>Estado</th>
                            <th style="text-align: center">3</th>
                            <th style="text-align: center">4</th>
                            <th style="text-align: center">6</th>
                            <th style="text-align: center">8</th>
                            <th style="text-align: center">10</th>
                            <th style="text-align: center">12</th>
                            <th style="text-align: center">14</th>
                            <th style="text-align: center">16</th>
                            <th>
                                <center>Total</center>
                            </th>

                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </section>

</div>

<!--=====================================
MODAL MANDAR A TALLER
======================================-->
<div id="modalMandarTaller" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" class="formularioAlmacenCorte">

                <!--=====================================
                CABEZA DEL MODAL
                ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Mandar a Taller</h4>

                </div>

                <!--=====================================
                CUERPO DEL MODAL
                ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <input type="hidden" name="usuario" value="<?php echo $_SESSION["id"]; ?>">

                        <input type="hidden" name="precio_doc" id="precio_doc">

                        <input type="hidden" name="tiempo_stand" id="tiempo_stand">

                        <input type="hidden" name="precio_total" id="precio_total">

                        <input type="hidden" name="tiempo_total" id="tiempo_total">

                        <input type="hidden" name="nuevoCorte" id="nuevoCorte">

                        <!-- ENTRADA PARA EL ARITCULO -->

                        <div class="form-group col-lg-4">

                            <label>Articulo</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevoArticulo" name="nuevoArticulo" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-8">

                            <label>Nombre</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevoNombre" name="nuevoNombre" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL MODELO -->

                        <div class="form-group col-lg-4">

                            <label>Modelo</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevoModelo" name="nuevoModelo" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL COLOR -->

                        <div class="form-group col-lg-4">

                            <label>Color</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevoColor" name="nuevoColor" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA TALLA -->

                        <div class="form-group col-lg-4">

                            <label>Talla</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevaTalla" name="nuevaTalla" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL TOTAL DEL CORTE -->

                        <div class="form-group ">


                            <div class="col-xs-3">
                                <label>Enviar a talleres</label>
                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-arrow-up"></i></span>

                                    <input type="number" class="form-control input-md" id="almCorte" name="almCorte" min="0" placeholder="Por enviar" required readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL TOTAL DEL CORTE -->

                            <div class="col-xs-3" style="padding-top:25px">
                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-arrow-down"></i></span>

                                    <input type="number" class="form-control input-md" id="nuevoAlmCorte" name="nuevoAlmCorte" min="0" max="" placeholder="Mandar" required>

                                </div>

                            </div>

                            <!-- ENTRADA PARA LA GUIA -->
                            <div class="col-xs-6">
                                <label>Guía</label>
                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                    <input type="text" class="form-control input-sm" id="nuevaGuia" name="nuevaGuia" required>

                                </div>
                            </div>

                        </div>

                        <div class="col-lg-12"></div>
                        <!--=====================================
                        TALLER DESTINO (tipo en sectorjf decide tickets / servicio)
                        ======================================-->

                        <div class="form-group col-lg-12 campoSector">

                            <label>Taller destino</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-users"></i></span>

                                <select class="form-control selectpicker" id="seleccionarSectorServicio" name="seleccionarSectorServicio" data-live-search="true" title="Seleccionar taller" required>

                                    <option value="">Seleccionar taller</option>

                                    <?php

                                    $sectoresInternos = ControladorSectores::ctrSectoresPorTipo(0);
                                    $sectoresExternos = ControladorSectores::ctrSectoresPorTipo(1);

                                    echo '<optgroup label="Taller (interno — imprime tickets)">';
                                    foreach ($sectoresInternos as $value) {
                                        echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '" data-tipo="0">'
                                            . htmlspecialchars($value["cod_sector"] . " - " . $value["nom_sector"])
                                            . '</option>';
                                    }
                                    echo '</optgroup>';

                                    echo '<optgroup label="Servicio (externo)">';
                                    foreach ($sectoresExternos as $value) {
                                        echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '" data-tipo="1">'
                                            . htmlspecialchars($value["cod_sector"] . " - " . $value["nom_sector"])
                                            . '</option>';
                                    }
                                    echo '</optgroup>';

                                    ?>

                                </select>

                            </div>
                            <p class="help-block text-muted" style="margin-top:6px;margin-bottom:0">Interno: tickets. Externo: va a servicio. Un solo taller por envío.</p>

                        </div>
                    </div>
                </div>
                <!--=====================================
                PIE DEL MODAL
                ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-success">Mandar</button>

                </div>

            </form>

            <?php

            $mandarTaller = new ControladorCortes();
            $mandarTaller->ctrMandarTaller();

            ?>

        </div>

    </div>

</div>

<!--=====================================
MODAL MANDAR A TALLER TOTAL
======================================-->
<div id="modalMandarTallerTotal" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" class="formularioAlmacenCorteTotal">

                <!--=====================================
                CABEZA DEL MODAL
                ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Mandar a Taller Global</h4>

                </div>

                <!--=====================================
                CUERPO DEL MODAL
                ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <input type="hidden" name="usuario" value="<?php echo $_SESSION["id"]; ?>">

                        <!-- ENTRADA PARA EL ARITCULO -->
                        <div class="form-group col-lg-4">

                            <label>Modelo</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevoModeloT" name="nuevoModeloT" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->
                        <div class="form-group col-lg-8">

                            <label>Nombre</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevoNombreT" name="nuevoNombreT" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL COLOR -->
                        <div class="form-group col-lg-4">

                            <label>Color</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevoColorT" name="nuevoColorT" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA GUIA -->
                        <div class="form-group col-lg-4">

                            <label>Guía</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="text" class="form-control input-sm" id="nuevaGuiaT" name="nuevaGuiaT" required>

                            </div>
                        </div>

                        <div class="col-lg-12"></div>

                        <!--=====================================
                        TALLER DESTINO (tipo en sectorjf decide tickets / servicio)
                        ======================================-->
                        <div class="form-group col-lg-12 campoSectorTotal">

                            <label>Taller destino</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-users"></i></span>

                                <select class="form-control selectpicker" id="seleccionarSectorServicioTotal" name="seleccionarSectorServicioTotal" data-live-search="true" title="Seleccionar taller" required>

                                    <option value="">Seleccionar taller</option>

                                    <?php

                                    $sectoresInternosT = ControladorSectores::ctrSectoresPorTipo(0);
                                    $sectoresExternosT = ControladorSectores::ctrSectoresPorTipo(1);

                                    echo '<optgroup label="Taller (interno — imprime tickets)">';
                                    foreach ($sectoresInternosT as $value) {
                                        echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '" data-tipo="0">'
                                            . htmlspecialchars($value["cod_sector"] . " - " . $value["nom_sector"])
                                            . '</option>';
                                    }
                                    echo '</optgroup>';

                                    echo '<optgroup label="Servicio (externo)">';
                                    foreach ($sectoresExternosT as $value) {
                                        echo '<option value="' . htmlspecialchars($value["cod_sector"]) . '" data-tipo="1">'
                                            . htmlspecialchars($value["cod_sector"] . " - " . $value["nom_sector"])
                                            . '</option>';
                                    }
                                    echo '</optgroup>';

                                    ?>

                                </select>

                            </div>
                            <p class="help-block text-muted" style="margin-top:6px;margin-bottom:0">Interno: tickets. Externo: va a servicio. Un solo taller por envío.</p>

                        </div>

                        <div class="row">

                            <div class="col-xs-6">

                                <label>Tallas</label>

                            </div>

                            <div class="col-xs-3">

                                <label for="">Cantidad</label>

                            </div>

                            <div class="col-xs-3">

                                <label for="">Saldo</label>

                            </div>

                        </div>


                        <div class="col-lg-12 form-group row nuevasTallas">

                        </div>


                        <input type="hidden" id="listaTallas" name="listaTallas">

                    </div>

                    <!--=====================================
                    PIE DEL MODAL
                    ======================================-->

                    <div class="modal-footer">

                        <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                        <button type="submit" class="btn btn-success">Mandar</button>

                    </div>
                </div>
            </form>

            <?php

            $mandarTaller = new ControladorCortes();
            $mandarTaller->ctrMandarTallerTotal();

            ?>

        </div>

    </div>

</div>



<script>
    window.document.title = "Almacen de corte"
</script>