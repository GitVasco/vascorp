<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Precio servicios

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar precio servicios</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarPrecioServicio">

                    Agregar precio servicio

                </button>

                <div class="pull-right">
                    <button class="btn btn-outline-success btnReportePrecioServicio" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte precio servicios </button>
                </div>
            </div>

            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive tablaPrecioServicios" width="100%">

                    <thead>

                        <tr>

                            <th>Taller</th>
                            <th>Modelo</th>
                            <th>Descripcion</th>
                            <th>Precio Docena</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</div>

<!--=====================================
MODAL AGREGAR TIPO PAGO
======================================-->

<div id="modalAgregarPrecioServicio" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar precio servicio</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA EL TALLER -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-height"></i></span>

                                <select class="form-control input-md selectpicker" name="nuevoTallerPrecio" id="nuevoTallerPrecio" data-live-search="true" data-size="10" required>
                                    <option value="">Seleccionar taller</option>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $talleres = ControladorSectores::ctrMostrarSectores($item, $valor);

                                    foreach ($talleres as $key => $value) {
                                        echo '<option value="' . $value["cod_sector"] . '">' . $value["cod_sector"] . "-" . $value["nom_sector"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL MODELO -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <select class="form-control input-md selectpicker" name="nuevoModeloPrecio" id="nuevoModeloPrecio" data-live-search="true" data-size="10" required>
                                    <option value="">Seleccionar modelo</option>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $modelos = ControladorModelos::ctrMostrarModelos($item, $valor);

                                    foreach ($modelos as $key => $value) {
                                        if ($value["estado"] == 'ACTIVO' || $value["estado"] == 'Activo') {
                                            echo '<option value="' . $value["modelo"] . '">' . $value["modelo"] . "-" . $value["nombre"] . '</option>';
                                        }
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" step="any" min="0" class="form-control input-md" name="nuevoPrecioDocenaServicio" placeholder="Ingresar precio docena" required>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar precio de servicio</button>

                </div>

            </form>


            <?php

            $crearPrecioServicio = new ControladorServicios();
            $crearPrecioServicio->ctrCrearPrecioServicio();

            ?>


        </div>

    </div>

</div>


<!--=====================================
MODAL EDITAR TIPO PAGO
======================================-->

<div id="modalEditarPrecioServicio" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar precio servicio</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">



                        <!-- ENTRADA PARA EL TALLER -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-height"></i></span>

                                <select class="form-control input-md selectpicker" name="editarTallerPrecio" id="editarTallerPrecio" data-live-search="true" data-size="10" required>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $talleres = ControladorSectores::ctrMostrarSectores($item, $valor);

                                    foreach ($talleres as $key => $value) {
                                        echo '<option value="' . $value["cod_sector"] . '">' . $value["cod_sector"] . "-" . $value["nom_sector"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL MODELO -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <select class="form-control input-md selectpicker" id="editarModeloPrecio" name="editarModeloPrecio" data-live-search="true" data-size="10" required>
                                    <?php
                                    $item = null;
                                    $valor = null;
                                    $modelos = ControladorModelos::ctrMostrarModelos($item, $valor);

                                    foreach ($modelos as $key => $value) {
                                        if ($value["estado"] == 'ACTIVO' || $value["estado"] == 'Activo') {
                                            echo '<option value="' . $value["modelo"] . '">' . $value["modelo"] . " - " . $value["nombre"] . '</option>';
                                        }
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" step="any" min="0" class="form-control input-md" name="editarPrecioDocenaServicio" id="editarPrecioDocenaServicio" required>
                                <input type="hidden" id="idPrecioServicio" name="idPrecioServicio">

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar cambios</button>

                </div>

            </form>

            <?php

            $editarPrecioServicio = new ControladorServicios();
            $editarPrecioServicio->ctrEditarPrecioServicio();

            ?>


        </div>

    </div>

</div>


<?php

$eliminarPrecioServicio = new ControladorServicios();
$eliminarPrecioServicio->ctrEliminarPrecioServicio();

?>

<script>
    window.document.title = "Precio de servicios"
</script>