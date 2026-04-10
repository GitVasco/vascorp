<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Ingresos

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Ingresos</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <a href="crear-ingresos">

                    <button class="btn btn-primary">

                        Agregar Ingresos

                    </button>

                </a>
                <a href="crear-ingresos-multi">

                    <button class="btn btn-info" title="Vista prueba: tabla multi-taller">

                        Crear ingreso (multi-taller)

                    </button>

                </a>
                <a href="crear-segunda">

                    <button class="btn btn-danger">

                        Crear segunda

                    </button>

                </a>

                <a href="cerrar-arreglos">

                    <button class="btn btn-warning">

                        Cerrar Arreglos

                    </button>

                </a>

                <button class="btn btn-info btnIngresoDeta" data-toggle='modal' data-target='#modalVerIngresoDeta' codigoServicio><i class="fa fa-eye"></i> Ver ingresos</button>

                <?php if ($_SESSION["id"] == "6" || $_SESSION["id"] == "11" || $_SESSION["id"] == "7") : ?>

                    <button class="btn btn-success btnAgregarTaller" data-toggle='modal' data-target='#modalAgregarTaller'><i class="fa fa-plus"></i> Agregar a Taller</button>

                <?php endif ?>


                <button class="btn btn-outline-success btnReporteIngresoM" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Ingresos de stock </button>
                <button type="button" class="btn btn-default pull-right" id="daterange-btnIngresoM">
                    <span>
                        <i class="fa fa-calendar"></i>

                        <?php

                        if (isset($_GET["fechaInicial"])) {

                            echo $_GET["fechaInicial"] . " - " . $_GET["fechaFinal"];
                        } else {

                            echo 'Rango de fecha';
                        }

                        ?>

                    </span>

                    <i class="fa fa-caret-down"></i>

                </button>

            </div>




            <div class="box-body">

                <input type="hidden" value="<?= $_SESSION["perfil"]; ?>" id="perfilOculto">
                <input type="hidden" value="<?= $_GET["ruta"]; ?>" id="rutaAcceso">
                <table class="table table-bordered table-striped dt-responsive tablaIngresoM" width="100%">

                    <thead>

                        <tr>

                            <th>N°</th>
                            <th>Tipo</th>
                            <th>Responsable</th>
                            <th>Taller</th>
                            <th>Documento</th>
                            <th>Guia</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Almacen</th>
                            <th style="width:180px">Acciones</th>

                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </section>

</div>
<!--=====================================
MODAL VISUALIZAR INFORMACION
======================================-->

<div id="modalVisualizarIngreso" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 70% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle del Ingreso</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA CODIGO DEL OC-->

                        <div class="form-group col-lg-2">

                            <label>Ingreso</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="cierre" id="cierre" required readonly></strong>

                                <input type="hidden" class="form-control input-sm" name="tipoIng" id="tipoIng">

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA GUIA-->

                        <div class="form-group col-lg-2">

                            <label>N° Guia</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="guia" id="guia" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA FECHA-->

                        <div class="form-group col-lg-2">

                            <label>Creación</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="fecha" id="fecha" required></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA RESPONSABLE-->

                        <div class="form-group col-lg-2">

                            <label>Responsable</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="nombre" id="nombre" required readonly></strong>

                            </div>

                        </div>


                        <!-- ENTRADA PARA LA CANTIDAD-->

                        <div class="form-group col-lg-2">

                            <label>Cantidad Total</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="cantidad" id="cantidad" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL ESTADO-->

                        <div class="form-group col-lg-2">

                            <label>Estado</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="estado" id="estado" required readonly></strong>

                            </div>

                        </div>

                        <!-- TABLA DE DETALLES -->

                        <div class="form-group col-lg-12">
                            <label>TABLA DETALLES</label>
                        </div>

                        <div class="box-body">

                            <table class="table table-bordered table-striped dt-responsive tablaDetalleIngreso" width="100%">

                                <thead>

                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th>S</th>
                                        <th>M</th>
                                        <th>L</th>
                                        <th>XL</th>
                                        <th>XXL</th>
                                        <th>XS</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>

                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th>28</th>
                                        <th>30</th>
                                        <th>32</th>
                                        <th>34</th>
                                        <th>36</th>
                                        <th>38</th>
                                        <th>40</th>
                                        <th>42</th>
                                        <th></th>
                                    </tr>

                                    <tr>
                                        <th>Taller</th>
                                        <th>Fecha</th>
                                        <th>Modelo</th>
                                        <th>Nombre</th>
                                        <th>Color</th>
                                        <th>3</th>
                                        <th>4</th>
                                        <th>6</th>
                                        <th>8</th>
                                        <th>10</th>
                                        <th>12</th>
                                        <th>14</th>
                                        <th>16</th>
                                        <th>Total</th>
                                    </tr>

                                </thead>

                                <tbody>



                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar</button>

                </div>



            </form>

        </div>
        <?php

        $actualizar = new ControladorIngresos();
        $actualizar->ctrActualizarFecha();

        ?>


    </div>

</div>


<!--=====================================
MODAL VISUALIZAR INFORMACION
======================================-->
<div id="modalVerIngresoDeta" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 65% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle del Ingreso</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">
                    <div class="box-header width-border">
                        <button type="button" class="btn btn-default pull-right" id="daterange-btnVerIngresos">
                            <span>
                                <i class="fa fa-calendar"></i>

                                <?php

                                if (isset($_GET["fechaInicial"])) {

                                    echo $_GET["fechaInicial"] . " - " . $_GET["fechaFinal"];
                                } else {

                                    echo 'Rango de fecha';
                                }

                                ?>

                            </span>

                            <i class="fa fa-caret-down"></i>

                        </button>

                    </div>

                    <div class="box-body">
                        <table class="table table-bordered table-striped  tablaDetalleIngresoTotal" width="100%">

                            <thead>

                                <tr>
                                    <th></th>
                                    <th style="width:100px"></th>
                                    <th></th>
                                    <th style="width:130px"></th>
                                    <th></th>
                                    <th></th>
                                    <th style="width:180px"></th>
                                    <th style="width:100px"></th>
                                    <th>S</th>
                                    <th>M</th>
                                    <th>L</th>
                                    <th>XL</th>
                                    <th>XXL</th>
                                    <th>XS</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>

                                <tr>
                                    <th></th>
                                    <th style="width:100px"></th>
                                    <th></th>
                                    <th style="width:130px"></th>
                                    <th></th>
                                    <th></th>
                                    <th style="width:180px"></th>
                                    <th style="width:100px"></th>
                                    <th>28</th>
                                    <th>30</th>
                                    <th>32</th>
                                    <th>34</th>
                                    <th>36</th>
                                    <th>38</th>
                                    <th>40</th>
                                    <th>42</th>
                                    <th></th>
                                </tr>

                                <tr>
                                    <th>Tipo</th>
                                    <th style="width:100px">Taller</th>
                                    <th>Guia</th>
                                    <th style="width:130px">Fecha</th>
                                    <th>Documento</th>
                                    <th>Modelo</th>
                                    <th style="width:180px">Nombre</th>
                                    <th style="width:100px">Color</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>6</th>
                                    <th>8</th>
                                    <th>10</th>
                                    <th>12</th>
                                    <th>14</th>
                                    <th>16</th>
                                    <th>Total</th>
                                </tr>

                            </thead>

                            <tbody>



                            </tbody>

                        </table>
                    </div>





                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                </div>



            </form>

        </div>

    </div>

</div>

<!--=====================================
AGREGAR CANTIDAD A TALLER
======================================-->
<div id="modalAgregarTaller" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 50% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
                CABEZA DEL MODAL
                ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar Cantidad a Taller</h4>

                </div>

                <!--=====================================
                CUERPO DEL MODAL
                ======================================-->

                <div class="modal-body">
                    <div class="box-body">

                        <div class="form-group col-lg-6">
                            <label for="">Articulo</label>
                            <div class="input-group">

                                <span class="input-group-addon input-lg"><i class="fa fa-hand-o-right"></i></span>
                                <select class="form-control input-lg selectpicker" id="articulo" name="articulo" data-live-search="true" data-size="10" required>
                                    <option value="">Seleccionar Articulo</option>
                                    <?php

                                    $articulo = controladorArticulos::ctrMostrarArticulosTicket();
                                    foreach ($articulo as $key => $value) {
                                        echo '<option value="' . $value["articulo"] . '">' . $value["modelo"] . " - " . $value["color"] . " - " . $value["talla"] . '</option>';
                                    }
                                    ?>
                                </select>

                                <input type="hidden" name="usuario" value="<?php echo $_SESSION["nombre"] ?>">

                            </div>
                        </div>

                        <div class="form-group col-lg-6">
                            <label for="">Cantidad</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-hand-o-right"></i></span>

                                <input type="number" class="form-control input-lg" name="cantidad" id="cantidad" required>
                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
                PIE DEL MODAL
                ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Crear Ingreso</button>

                </div>

                <?php
                $activar = new ControladorIngresos();
                $activar->ctrAgregarTaller();

                ?>

            </form>

        </div>

    </div>

</div>

<?php
$eliminarIngreso = new ControladorIngresos();
$eliminarIngreso->ctrEliminarIngreso();

$eliminarSegunda = new ControladorIngresos();
$eliminarSegunda->ctrEliminarSegunda();
?>

<script>
    window.document.title = "Ingresos"
</script>