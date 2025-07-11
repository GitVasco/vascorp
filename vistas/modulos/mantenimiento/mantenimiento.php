<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Tabla Maestra

        </h1>

        <ol class="breadcrumb">

            <li><a href="#"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Tabla Maestra</li>

        </ol>

    </section>

    <section class="content">

        <div class="row">

            <!--=====================================
            EL FORMULARIO
            ======================================-->

            <div class="col-lg-8 col-xs-12">

                <div class="box box-primary">


                    <div class="box-header with-border"></div>

                    <div class="box-header with-border">

                        <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarMantenimiento">
                            <i class="fa fa-wrench"></i>
                            Agregar mantenimiento

                        </button>

                    </div>

                    <div class="box-body">

                        <table class="table table-bordered table-striped dt-responsive TablaMantenimientoCabecera" width="100%">

                            <thead>

                                <tr>
                                    <th>Cód.</th>
                                    <th>Tip.</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Maquina</th>
                                    <th>Ubicación</th>
                                    <th>Responsable</th>
                                    <th>Items</th>
                                    <th>Total S/</th>
                                    <th>Observaciones</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>

                            </thead>

                        </table>

                    </div>

                </div>


            </div>

            <!--=====================================
            LA TABLA DE PRODUCTOS
            ======================================-->

            <div class="col-lg-4 col-xs-12">

                <div class="box box-info">

                    <div class="box-header with-border"></div>

                    <div class="box-body">

                        <table class="table table-bordered table-striped dt-responsive TablaMantenimientoDetalle" width="100%">

                            <thead>

                                <tr>
                                    <th style="width: 10px; max-width: 46px !important;">Mante</th>
                                    <th>Item</th>
                                    <th>Cant.</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                    <th>Observacion</th>
                                    <th style="width: 100px">Acciones</th>
                                </tr>

                            </thead>

                        </table>

                    </div>

                </div>


            </div>

        </div>

    </section>

</div>

<!--=====================================
MODAL AGREGAR MANTENIMIENTO
======================================-->
<div id="modalAgregarMantenimiento" class="modal fade" role="dialog">

    <div class="modal-dialog modal-dialog-centered" style="width:75%">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
            CABEZA DEL MODAL
            ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar Mantenimiento</h4>

                </div>

                <!--=====================================
            CUERPO DEL MODAL
            ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <label>DATOS DEL MANTENIMIENTO</label>
                        <div class="form-group">

                            <!-- ENTRADA PARA EL ID -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">COD. INT.</label>
                            <div class="col-lg-4">

                                <?php

                                $interno = ControladorMantenimiento::ctrMostrarCorrelativo();
                                #var_dump($mantenimiento);

                                echo '<input type="text" class="form-control input-md"  name="nuevoId"  id ="nuevoId" value="' . $interno["correlativo"] . '" readonly>'

                                ?>

                            </div>

                            <!-- ENTRADA PARA TIPO MANTENIMIENTO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">TIPO</label>
                            <div class="col-lg-3">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoTipo" id="nuevoTipo" data-size="10" required>

                                    <option value="">SELECCIONAR</option>
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Correctivo">Correctivo</option>

                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA MANTENIMIENTO PROGRAMADO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">MANTE. INICIO</label>
                            <div class="col-lg-4">

                                <input type="datetime-local" class="form-control input-md" name="nuevoInicio" id="nuevoInicio">

                            </div>

                            <!-- ENTRADA PARA MANTENIMIENTO PROGRAMADO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">MANTE. FIN</label>
                            <div class="col-lg-4">

                                <input type="datetime-local" class="form-control input-md" name="nuevoFin" id="nuevoFin">

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA LA UBICACION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">MAQUINA</label>
                            <div class="col-lg-4">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevaMaquina" id="nuevaMaquina" data-size="10" required>

                                    <?php

                                    $valor = null;
                                    $mantenimiento = ControladorMantenimiento::ctrMostrarEquipos($valor);
                                    #var_dump($mantenimiento);

                                    echo '<option value="">SELECCIONAR MÁQUINA</option>';

                                    foreach ($mantenimiento as $key => $value) {

                                        echo '<option value="' . $value["cod_tipo"] . '">' . $value["cod_tipo"] . ' - ' . $value["descripcion"] . ' - ' . $value["modelo_equipo"] . '</option>';
                                    }

                                    ?>

                                </select>

                            </div>

                            <!-- ENTRADA PARA LA UBICACION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">UBICACIÓN</label>
                            <div class="col-lg-4">

                                <input type="hidden" class="form-control input-md" name="nuevaUbicacion" id="nuevaUbicacion" readonly>

                                <input type="text" class="form-control input-md" name="nuevaNombreUbicacion" id="nuevaNombreUbicacion" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL MECANICO -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">RESPONSABLE</label>
                            <div class="col-lg-4">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoResponsable" id="nuevoResponsable" data-size="10">

                                    <option value="">SELECCIONAR RESPONSABLE</option>

                                    <?php

                                    $valor = 'TMEC';
                                    $mec = ControladorMateriaPrima::ctrGlobalMaestra($valor);
                                    #var_dump($mec);

                                    foreach ($mec as $key => $value) {

                                        echo '<option value="' . $value["cod_argumento"] . '">' . $value["cod_argumento"] . ' - ' . $value["des_larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                            <!-- ENTRADA PARA EL ESTADO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">ESTADO</label>
                            <div class="col-lg-4">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoEstado" id="nuevoEstado" data-size="10" required>

                                    <option value="">SELECCIONAR</option>
                                    <option value="HECHO">HECHO</option>
                                    <option value="NO HECHO">NO HECHO</option>

                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA OPERARIO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OPERARIO</label>
                            <div class="col-lg-6">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoOperario" id="nuevoOperario" data-size="10">

                                    <option value="">SELECCIONAR OPERARIO</option>

                                    <?php

                                    $mec = ControladorTrabajador::ctrMostrarTrabajadorActivo();
                                    #var_dump($mec);

                                    foreach ($mec as $key => $value) {

                                        echo '<option value="' . $value["cod_tra"] . '">' . $value["cod_tra"] . ' - ' . $value["trabajador"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group">

                            <!-- ENTRADA PARA TIPO MOTOR-->
                            <label for="" class="col-form-label col-lg-4 col-md-3 col-sm-3">DESCRIPCIÓN DEL MANTENIMIENTO</label>
                            <div class="col-lg-12">

                                <textarea type="textarea" rows="5" cols="136" id="nuevaObservacion" name="nuevaObservacion" placeholder="Detallar lo realizado con la máquina"></textarea>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group">

                            <!-- ENTRADA PARA TIPO MOTOR-->
                            <label class="col-form-label col-lg-2 col-md-3 col-sm-3">REPUESTOS</label>
                            <div class="col-lg-3 col-xs-12">
                                <button type="button" class="btn btn-primary btn-xs" id="cargarTablaRpt" name="cargarTablaRpt">Agregar</button>
                            </div>

                            <div class="col-lg-3 col-xs-12">
                                <button type="button" class="btn btn-danger btn-xs" id="ocultarTablaRpt" name="ocultarTablaRpt">Ocultar</button>
                            </div>


                            <div class="col-lg-12 col-xs-12" id="divRpt" hidden>

                                <div class="box box-info">

                                    <div class="box-header with-border"></div>

                                    <div class="box-body">

                                        <table class="table table-bordered table-condensed table-striped dt-responsive TablaMantenimientoRepuestos" width="100%">

                                            <thead>

                                                <tr>
                                                    <th style="width: 10px; max-width: 46px !important;">CodPro</th>
                                                    <th>CodFab</th>
                                                    <th style="width: 10px; min-width: 350px !important;">Descripción</th>
                                                    <th>Unidad</th>
                                                    <th>Stock</th>
                                                    <th style="text-align: center;">Cantidad</th>
                                                    <th>Costo</th>
                                                    <th>Acciones</th>
                                                </tr>

                                            </thead>

                                        </table>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <input type="hidden" id="repuestosSeleccionados" name="repuestosSeleccionados">

                <!--=====================================
            PIE DEL MODAL
            ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Registrar mantenimiento</button>

                </div>

            </form>

            <?php

            $crearMantenimiento = new ControladorMantenimiento();
            $crearMantenimiento->ctrCrearMantenimiento();

            ?>

        </div>

    </div>

</div>

<!--=====================================
MODAL EDITAR MANTENIMIENTO
======================================-->
<div id="modalEditarMantenimiento" class="modal fade" role="dialog">

    <div class="modal-dialog modal-dialog-centered" style="width:50%">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
            CABEZA DEL MODAL
            ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar Mantenimiento</h4>

                </div>

                <!--=====================================
            CUERPO DEL MODAL
            ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <label>DATOS DEL MANTENIMIENTO</label>
                        <div class="form-group">

                            <!-- ENTRADA PARA EL ID -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">COD. INT.</label>
                            <div class="col-lg-4">

                                <input type="text" class="form-control input-md" name="editarId" id="editarId" readonly>
                                <input type="hidden" class="form-control input-md" name="id" id="id" readonly>

                            </div>

                            <!-- ENTRADA PARA TIPO MANTENIMIENTO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">TIPO</label>
                            <div class="col-lg-3">

                                <input type="text" class="form-control input-md" name="editarTipo" id="editarTipo" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA MANTENIMIENTO PROGRAMADO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">MANTE. INICIO</label>
                            <div class="col-lg-4">

                                <input type="datetime-local" class="form-control input-md" name="editarInicio" id="editarInicio" required>

                            </div>

                            <!-- ENTRADA PARA MANTENIMIENTO PROGRAMADO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">MANTE. FIN</label>
                            <div class="col-lg-4">

                                <input type="datetime-local" class="form-control input-md" name="editarFin" id="editarFin" required>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA LA UBICACION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">MAQUINA</label>
                            <div class="col-lg-4">

                                <input type="text" class="form-control input-md" name="editarMaquina" id="editarMaquina" readonly>
                                <input type="hidden" class="form-control input-md" name="editarMaquinaCod" id="editarMaquinaCod" readonly>

                            </div>

                            <!-- ENTRADA PARA LA UBICACION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">UBICACIÓN</label>
                            <div class="col-lg-4">

                                <input type="text" class="form-control input-md" name="editarNombreUbicacion" id="editarNombreUbicacion" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL MECANICO -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">RESPONSABLE</label>
                            <div class="col-lg-4">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarResponsable" id="editarResponsable" data-size="10">

                                    <option value="">SELECCIONAR RESPONSABLE</option>

                                    <?php

                                    $valor = 'TMEC';
                                    $mec = ControladorMateriaPrima::ctrGlobalMaestra($valor);
                                    #var_dump($mec);

                                    foreach ($mec as $key => $value) {

                                        echo '<option value="' . $value["cod_argumento"] . '">' . $value["cod_argumento"] . ' - ' . $value["des_larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                            <!-- ENTRADA PARA EL ESTADO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">ESTADO</label>
                            <div class="col-lg-4">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarEstado" id="editarEstado" data-size="10" required>

                                    <option value="">SELECCIONAR</option>
                                    <option value="HECHO">HECHO</option>
                                    <option value="NO HECHO">NO HECHO</option>

                                </select>


                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA OPERARIO-->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OPERARIO</label>
                            <div class="col-lg-6">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarOperario" id="editarOperario" data-size="10">

                                    <option value="">SELECCIONAR OPERARIO</option>

                                    <?php

                                    $mec = ControladorTrabajador::ctrMostrarTrabajadorActivo();
                                    #var_dump($mec);

                                    foreach ($mec as $key => $value) {

                                        echo '<option value="' . $value["cod_tra"] . '">' . $value["cod_tra"] . ' - ' . $value["trabajador"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group">

                            <!-- ENTRADA PARA TIPO MOTOR-->
                            <label for="" class="col-form-label col-lg-4 col-md-3 col-sm-3">DESCRIPCIÓN DEL MANTENIMIENTO</label>
                            <div class="col-lg-12">

                                <textarea type="textarea" rows="5" cols="136" id="editarObservacion" name="editarObservacion" placeholder="Detallar lo realizado con la máquina"></textarea>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group">

                            <!-- ENTRADA PARA TIPO MOTOR-->
                            <label class="col-form-label col-lg-2 col-md-3 col-sm-3">REPUESTOS</label>
                            <div class="col-lg-3 col-xs-12">
                                <button type="button" class="btn btn-primary btn-xs" id="cargarTablaRptE" name="cargarTablaRptE">Agregar</button>
                            </div>

                            <div class="col-lg-3 col-xs-12">
                                <button type="button" class="btn btn-danger btn-xs" id="ocultarTablaRptE" name="ocultarTablaRptE">Ocultar</button>
                            </div>


                            <div class="col-lg-12 col-xs-12" id="divRptE" hidden>

                                <div class="box box-info">

                                    <div class="box-header with-border"></div>

                                    <div class="box-body">

                                        <table class="table table-bordered table-condensed table-striped dt-responsive TablaMantenimientoRepuestos" width="100%">

                                            <thead>

                                                <tr>
                                                    <th style="width: 10px; max-width: 46px !important;">CodPro</th>
                                                    <th>CodFab</th>
                                                    <th style="width: 10px; min-width: 350px !important;">Descripción</th>
                                                    <th>Unidad</th>
                                                    <th>Stock</th>
                                                    <th>Costo</th>
                                                    <th>Acciones</th>
                                                </tr>

                                            </thead>

                                        </table>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
            PIE DEL MODAL
            ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Editar mantenimiento</button>

                </div>

            </form>

            <?php

            $editarMantenimiento = new ControladorMantenimiento();
            $editarMantenimiento->ctrEditarMantenimiento();

            ?>

        </div>

    </div>

</div>

<!--=====================================
MODAL EDITAR DETALLE MANTENIMIENTO
======================================-->
<div id="modalEditarRepuestos" class="modal fade" role="dialog">

    <div class="modal-dialog modal-dialog-centered" style="width:30%">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
            CABEZA DEL MODAL
            ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar Detalle</h4>

                </div>

                <!--=====================================
            CUERPO DEL MODAL
            ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <label>DATOS DEL REPUESTO</label>
                        <div class="form-group">

                            <!-- ENTRADA PARA EL ID -->
                            <label for="" class="col-form-label col-lg-3 col-md-3 col-sm-3">COD. INT.</label>
                            <div class="col-lg-3">

                                <input type="text" class="form-control input-md" name="editarIdD" id="editarIdD" readonly>
                                <input type="hidden" class="form-control input-md" name="idD" id="idD" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>
                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL ID -->
                            <label for="" class="col-form-label col-lg-3 col-md-3 col-sm-3">Descripcion</label>
                            <div class="col-lg-9">

                                <input type="text" class="form-control input-md" name="editarNombre" id="editarNombre" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>
                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL ID -->
                            <label for="" class="col-form-label col-lg-3 col-md-3 col-sm-3">Cantidad</label>
                            <div class="col-lg-3">

                                <input type="number" step="any " class="form-control input-md" name="editarCantidadD" id="editarCantidadD">

                            </div>

                            <!-- ENTRADA PARA EL ID -->
                            <label for="" class="col-form-label col-lg-3 col-md-3 col-sm-3">Precio</label>
                            <div class="col-lg-3">

                                <input type="number" step="any " class="form-control input-md" name="editarPrecio" id="editarPrecio" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>
                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA TIPO MOTOR-->
                            <label for="" class="col-form-label col-lg-12 col-md-3 col-sm-3">DESCRIPCIÓN DEL MANTENIMIENTO</label>
                            <div class="col-lg-12">

                                <textarea type="textarea" rows="5" cols="75" id="editarObservacionD" name="editarObservacionD" placeholder="Detallar lo realizado con la máquina"></textarea>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
            PIE DEL MODAL
            ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Editar detalle</button>

                </div>

            </form>

            <?php

            $editarMantenimientoDetalle = new ControladorMantenimiento();
            $editarMantenimientoDetalle->ctrEditarMantenimientoDetalle();

            ?>

        </div>

    </div>

</div>

<?php

$anularMantenimientoDetalle = new ControladorMantenimiento();
$anularMantenimientoDetalle->ctrAnularMantenimientoDetalle();

?>

<script>
    window.document.title = "Mantenimiento"
</script>