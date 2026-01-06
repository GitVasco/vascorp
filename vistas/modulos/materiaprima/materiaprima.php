<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Administrar Materia Prima

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar materia prima</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">
            <div class="box-header with-border">

                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarMateriaPrima">

                    Agregar Materia Prima

                </button>

                <div class="pull-right">

                    <button class="btn btn-outline-success btnReporteMateria" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Materia Prima </button>

                    <button type="button" class="btn btn-success" id="saldosMatPri" name="saldosMatPri" data-toggle="modal" data-target="#modalSaldosMesMP">
                        Saldos a una Fecha
                    </button>
                </div>
            </div>
            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive tablaMateriaPrima" width="100%">

                    <thead>

                        <tr>

                            <th>Código</th>
                            <th>Cod. Fab.</th>
                            <th>Descripcion</th>
                            <th>Color</th>
                            <th>Talla</th>
                            <th>Unidad</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th style="width:220px">Acciones</th>

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
MODAL AGREGAR MATERIA PRIMA
======================================-->

<div id="modalAgregarMateriaPrima" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width:77%">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar Materia Prima</h4>

                </div>

                <?php
                date_default_timezone_set('America/Lima');
                $fecha = new DateTime();
                ?>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <div class="form-group" id="alertaCodigoFab">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COD. FABRICA</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="nuevoCodigoFab" id="nuevoCodigoFab" placeholder="INGRESAR CODIGO FAB" readonly required>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COD ALTERNO</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="nuevoCodigoAlt" id="nuevoCodigoAlt" placeholder="Ingresar código">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3 text-right">FECHA EMISION</label>
                            <div class="col-lg-2">

                                <input type="date" class="form-control input-md" name="nuevaFechaEmision" value="<?php echo $fecha->format("Y-m-d") ?>" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA LINEA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">LINEA</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevaLinea" id="nuevaLinea" data-size="10" required>

                                    <?php

                                    $lineas = ControladorMateriaPrima::ctrMostrarLineas();

                                    echo '<option value="">SELECCIONAR LINEAS</option>';

                                    foreach ($lineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Corta"] . '">' . $value["Des_Corta"] . ' - ' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA SUB LINEA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">SUB LINEA</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevaSubLinea" id="nuevaSubLinea" data-size="10" required>
                                    <option value="">SELECCIONAR SUBLINEAS</option>

                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL COLOR -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COLOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoColorMateria" id="nuevoColorMateria" data-size="10" required>

                                    <?php

                                    $colores = ControladorMateriaPrima::ctrMostrarColores();

                                    echo '<option value="">SELECCIONAR COLOR</option>';

                                    foreach ($colores as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA TALLA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">TALLA</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevaTallaMateria" id="nuevaTallaMateria" data-size="10" required>

                                    <?php

                                    $tallas = ControladorMateriaPrima::ctrMostrarTallas();

                                    echo '<option value="">SELECCIONAR TALLA</option>';

                                    foreach ($tallas as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA DESCRIPCION -->
                        <div class="form-group" style="padding-top:25px">


                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">DESCRIPCION</label>
                            <div class="col-lg-11">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="nuevaDescripcion" id="nuevaDescripcionMat" placeholder="Ingresar descripción" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA UND. MEDIDA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">UNID. MEDIDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevaUnidadMedida" data-size="10" required>

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $bancos = ControladorMateriaPrima::ctrMostrarUndMedida();

                                    echo '<option value="">SELECCIONAR UNID MEDIDA</option>';

                                    foreach ($bancos as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . ' - ' . $value["Des_Corta"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PESO</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoPeso" value="0.00">

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">%AD VAL</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoAdVal" value="0.00">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">%SEGURO</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoSeguro" value="0.00">

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">STK ACTUAL</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoStockActual" value="0.00" readonly>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">STK MIN</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoStockMinimo" value="0.00">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">STK MAX.</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoStockMaximo" value="0.00">

                            </div>

                        </div>
                        <div class="col-lg-12"></div>
                        <label for="" style="padding-top:25px">PROVEEDORES X LISTA DE PRECIO</label>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoProveedor" id="nuevoProveedor" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="nuevaMoneda" id="nuevaMoneda" readonly>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">P S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoPrecioSIGV">

                            </div>

                        </div>


                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoProveedor1" id="nuevoProveedor1" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="nuevaMoneda1" id="nuevaMoneda1" readonly>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">P S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoPrecioSIGV1">

                            </div>

                        </div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="nuevoProveedor2" id="nuevoProveedor2" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="nuevaMoneda2" id="nuevaMoneda2" readonly>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">P S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="nuevoPrecioSIGV2">

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 1 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 1</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="nuevaObservacion1" placeholder="Ingresar observación de proveedor 1">

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 2 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 2</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="nuevaObservacion2" placeholder="Ingresar observación de proveedor 2">

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 3 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 3</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="nuevaObservacion3" placeholder="Ingresar observación de proveedor 3">

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar materia prima</button>

                </div>

            </form>

            <?php

            $guardarMateriaPrima = new ControladorMateriaPrima();
            $guardarMateriaPrima->ctrCrearMateriaPrima();

            ?>

        </div>

    </div>

</div>




<!--=====================================
MODAL EDITAR MATERIA PRIMA
======================================-->

<div id="modalEditarMateriaPrima" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width:77%">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data" id="formularioEditarMateria">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar Materia Prima</h4>

                </div>



                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">



                        <div class="form-group" id="alertaCodigoFab">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COD. FABRICA</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="editarCodigoFab" id="editarCodigoFab" placeholder="INGRESAR CODIGO FAB" readonly required>
                                <input type="hidden" name="editarCodigoPro" id="editarCodigoPro">
                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COD ALTERNO</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="editarCodigoAlt" id="editarCodigoAlt">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3 text-right">FECHA EMISION</label>
                            <div class="col-lg-2">

                                <input type="date" class="form-control input-md" name="editarFechaEmision" id="editarFechaEmision" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA LINEA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">LINEA</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarLinea" id="editarLinea" disabled>

                                    <?php

                                    $lineas = ControladorMateriaPrima::ctrMostrarLineas();

                                    echo '<option value="">SELECCIONAR LINEAS</option>';

                                    foreach ($lineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Corta"] . '">' . $value["Des_Corta"] . ' - ' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3 text-right">REGISTRADO POR</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="editarUsuarioReg" id="editarUsuarioReg" readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA SUB LINEA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">SUB LINEA</label>
                            <div class="col-lg-5">

                                <input type="text" class="form-control input-md" id="editarSubLinea2" name="editarSubLinea2" readonly>
                                <input type="hidden" id="editarSubLinea" name="editarSubLinea">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL COLOR -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COLOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarColorMateria" id="editarColorMateria" disabled>

                                    <?php

                                    $colores = ControladorMateriaPrima::ctrMostrarColores();

                                    echo '<option value="">SELECCIONAR COLOR</option>';

                                    foreach ($colores as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA TALLA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">TALLA</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarTallaMateria" id="editarTallaMateria" disabled>

                                    <?php

                                    $tallas = ControladorMateriaPrima::ctrMostrarTallas();

                                    echo '<option value="">SELECCIONAR TALLA</option>';

                                    foreach ($tallas as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA DESCRIPCION -->
                        <div class="form-group" style="padding-top:25px">


                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">DESCRIPCION</label>
                            <div class="col-lg-11">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="editarDescripcion" id="editarDescripcion" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA UND. MEDIDA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">UNID. MEDIDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarUnidadMedida" id="editarUnidadMedida" data-size="10" required>

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $bancos = ControladorMateriaPrima::ctrMostrarUndMedida();

                                    echo '<option value="">SELECCIONAR UNID MEDIDA</option>';

                                    foreach ($bancos as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . ' - ' . $value["Des_Corta"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PESO</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarPeso" id="editarPeso">

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">%AD VAL</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarAdVal" id="editarAdVal">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">%SEGURO</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarSeguro" id="editarSeguro">

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">STK ACTUAL</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarStockActual" id="editarStockActual" readonly>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">STK MIN</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarStockMinimo" id="editarStockMinimo">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">STK MAX.</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarStockMaximo" id="editarStockMaximo">

                            </div>

                        </div>
                        <div class="col-lg-12"></div>
                        <label for="" style="padding-top:25px">PROVEEDORES X LISTA DE PRECIO</label>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-4 col-md-3">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarProveedor" id="editarProveedor" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" name="editarMoneda" id="editarMoneda" data-live-search="true">
                                    <option value="">SELECCIONAR MONEDAS</option>
                                    <?php
                                    $valor = null;
                                    $sublineas = ControladorProveedores::ctrMostrarMonedas();


                                    foreach ($sublineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Larga"] . '">' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">P S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarPrecioSIGV" id="editarPrecioSIGV">

                            </div>

                            <div class="col-lg-1">
                                <button type="button" class="btn btn-info form-control" id="btnLimpiarProveedor1" title="Limpiar Proveedor 1"><i class="fa fa-refresh"></i></button>
                            </div>

                        </div>


                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-4 col-md-3">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarProveedor1" id="editarProveedor1" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" name="editarMoneda1" id="editarMoneda1" data-live-search="true">
                                    <option value="">SELECCIONAR MONEDAS</option>
                                    <?php
                                    $valor = null;
                                    $sublineas = ControladorProveedores::ctrMostrarMonedas();


                                    foreach ($sublineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Larga"] . '">' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">P S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarPrecioSIGV1" id="editarPrecioSIGV1">

                            </div>

                            <div class="col-lg-1">
                                <button type="button" class="btn btn-info form-control" id="btnLimpiarProveedor2" title="Limpiar Proveedor 2"><i class="fa fa-refresh"></i></button>
                            </div>

                        </div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-4 col-md-3">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="editarProveedor2" id="editarProveedor2" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" name="editarMoneda2" id="editarMoneda2" data-live-search="true">
                                    <option value="">SELECCIONAR MONEDAS</option>
                                    <?php
                                    $valor = null;
                                    $sublineas = ControladorProveedores::ctrMostrarMonedas();


                                    foreach ($sublineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Larga"] . '">' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">P S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="editarPrecioSIGV2" id="editarPrecioSIGV2">

                            </div>

                            <div class="col-lg-1">
                                <button type="button" class="btn btn-info form-control" id="btnLimpiarProveedor3" title="Limpiar Proveedor 3"><i class="fa fa-refresh"></i></button>
                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 1 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 1</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="editarObservacion1" id="editarObservacion1">

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 2 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 2</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="editarObservacion2" id="editarObservacion2">

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 3 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 3</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="editarObservacion3" id="editarObservacion3">

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="button" class="btn btn-primary btnEditarCambiosMateria">Guardar cambios</button>

                </div>

            </form>

            <?php

            $editarMateriaPrima = new ControladorMateriaPrima();
            $editarMateriaPrima->ctrEditarMateriaPrima();

            ?>

        </div>

    </div>

</div>


<!--=====================================
MODAL DUPLICAR MATERIA PRIMA
======================================-->

<div id="modalDuplicarMateriaPrima" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width:77%">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data" id="formDuplicarMateriaPrima">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Nuevo color Materia Prima</h4>

                </div>



                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">



                        <div class="form-group" id="alertaCodigoFab2">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COD. FABRICA</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="duplicarCodigoFab" id="duplicarCodigoFab" placeholder="INGRESAR CODIGO FAB" readonly required>
                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COD ALTERNO</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="duplicarCodigoAlt" id="duplicarCodigoAlt">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3 text-right">FECHA EMISION</label>
                            <div class="col-lg-2">

                                <input type="date" class="form-control input-md" name="duplicarFechaEmision" value="<?php echo $fecha->format("Y-m-d"); ?>" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA LINEA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">LINEA</label>
                            <div class="col-lg-5">
                                <input type="hidden" name="duplicarFamPro" id="duplicarFamPro">
                                <select class="form-control input-md selectpicker" data-live-search="true" name="duplicarLinea" id="duplicarLinea" disabled>

                                    <?php

                                    $lineas = ControladorMateriaPrima::ctrMostrarLineas();

                                    echo '<option value="">SELECCIONAR LINEAS</option>';

                                    foreach ($lineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Corta"] . '">' . $value["Des_Corta"] . ' - ' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3 text-right">CODIGO</label>
                            <div class="col-lg-2">

                                <input type="text" class="form-control input-md" name="duplicarCodigoPro" id="duplicarCodigoPro" readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA SUB LINEA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">SUB LINEA</label>
                            <div class="col-lg-5">

                                <input type="text" class="form-control input-md" id="duplicarSubLinea2" name="duplicarSubLinea2" readonly>

                                <input type="hidden" id="duplicarSubLinea" name="duplicarSubLinea">
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL COLOR -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">COLOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="duplicarColorMateria" id="duplicarColorMateria" required>

                                    <?php

                                    $colores = ControladorMateriaPrima::ctrMostrarColores();

                                    echo '<option value="">SELECCIONAR COLOR</option>';

                                    foreach ($colores as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA TALLA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">TALLA</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="duplicarTallaMateria" id="duplicarTallaMateria" required>

                                    <?php

                                    $tallas = ControladorMateriaPrima::ctrMostrarTallas();

                                    echo '<option value="">SELECCIONAR TALLA</option>';

                                    foreach ($tallas as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA DESCRIPCION -->
                        <div class="form-group" style="padding-top:25px">


                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">DESCRIPCION</label>
                            <div class="col-lg-11">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="duplicarDescripcion" id="duplicarDescripcion" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA UND. MEDIDA -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">UNID. MEDIDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="duplicarUnidadMedida" id="duplicarUnidadMedida" data-size="10" required>

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $bancos = ControladorMateriaPrima::ctrMostrarUndMedida();

                                    echo '<option value="">SELECCIONAR UNID MEDIDA</option>';

                                    foreach ($bancos as $key => $value) {

                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . ' - ' . $value["Des_Larga"] . ' - ' . $value["Des_Corta"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PESO</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarPeso" id="duplicarPeso">

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">%AD VAL</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarAdVal" id="duplicarAdVal">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">%SEGURO</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarSeguro" id="duplicarSeguro">

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">STK ACTUAL</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarStockActual" id="duplicarStockActual" readonly>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">STK MIN</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarStockMinimo" id="duplicarStockMinimo">
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">STK MAX.</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarStockMaximo" id="duplicarStockMaximo">

                            </div>

                        </div>
                        <div class="col-lg-12"></div>
                        <label for="" style="padding-top:25px">PROVEEDORES X LISTA DE PRECIO</label>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="duplicarProveedor" id="duplicarProveedor" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" name="duplicarMoneda" id="duplicarMoneda" data-live-search="true">
                                    <option value="">SELECCIONAR MONEDAS</option>
                                    <?php
                                    $valor = null;
                                    $sublineas = ControladorProveedores::ctrMostrarMonedas();


                                    foreach ($sublineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Larga"] . '">' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">PRECIO S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarPrecioSIGV" id="duplicarPrecioSIGV">

                            </div>


                        </div>


                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="duplicarProveedor1" id="duplicarProveedor1" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" name="duplicarMoneda1" id="duplicarMoneda1" data-live-search="true">
                                    <option value="">SELECCIONAR MONEDAS</option>
                                    <?php
                                    $valor = null;
                                    $sublineas = ControladorProveedores::ctrMostrarMonedas();


                                    foreach ($sublineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Larga"] . '">' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">PRECIO S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarPrecioSIGV1" id="duplicarPrecioSIGV1">

                            </div>

                        </div>

                        <div class="form-group" style="padding-top:25px">

                            <!-- ENTRADA PARA EL CÓDIGO FABRICA -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">PROVEEDOR</label>
                            <div class="col-lg-5">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="duplicarProveedor2" id="duplicarProveedor2" data-size="10">

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $proveedores = ControladorProveedores::ctrMostrarProveedores($item, $valor);

                                    echo '<option value="">SELECCIONAR PROVEEDOR</option>';

                                    foreach ($proveedores as $key => $value) {

                                        echo '<option value="' . $value["CodRuc"] . '">' . $value["CodRuc"] . ' - ' . $value["RazPro"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>
                            <!-- ENTRADA PARA EL CÓDIGO ALTERNO-->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3">MONEDA</label>
                            <div class="col-lg-2">

                                <select class="form-control input-md selectpicker" name="duplicarMoneda2" id="duplicarMoneda2" data-live-search="true">
                                    <option value="">SELECCIONAR MONEDAS</option>
                                    <?php
                                    $valor = null;
                                    $sublineas = ControladorProveedores::ctrMostrarMonedas();


                                    foreach ($sublineas as $key => $value) {

                                        echo '<option value="' . $value["Des_Larga"] . '">' . $value["Des_Larga"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                            <!-- ENTRADA PARA LA FECHA DE EMISION -->
                            <label for="" class="col-form-label col-lg-1 col-md-3 col-sm-3 ">PRECIO S/IGV</label>
                            <div class="col-lg-2">

                                <input type="number" min="0" step="any" class="form-control input-md" name="duplicarPrecioSIGV2" id="duplicarPrecioSIGV2">

                            </div>

                        </div>

                        <div class="col-lg-12"></div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 1 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 1</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="duplicarObservacion1" id="duplicarObservacion1">

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 2 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 2</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="duplicarObservacion2" id="duplicarObservacion2">

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA OBSERVACION DE PROVEEDOR 3 -->
                        <div class="form-group" style="padding-top:25px">

                            <label for="" class="col-form-label col-lg-2 col-md-3 col-sm-3">OBSERVACION PROV 3</label>
                            <div class="col-lg-10">

                                <input type="text" class="form-control input-md" style="text-transform:uppercase;" name="duplicarObservacion3" id="duplicarObservacion3">

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar nuevo color</button>

                </div>

            </form>

            <?php

            // $duplicarMateriaPrima = new ControladorMateriaPrima();
            // $duplicarMateriaPrima -> ctrDuplicarMateriaPrima();

            ?>

        </div>

    </div>

</div>


<!--=====================================
MODAL VISUALIZAR INFORMACION
======================================-->

<div id="modalVisualizarArticulos" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 80% !important;">

        <div class="modal-content">

            <form role="form" method="post">

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

                        <!-- ENTRADA PARA CODIGO DE LA MATERIA PRIMA-->

                        <div class="form-group col-lg-3">

                            <label>CodPro</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="codpro" id="codpro" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL CODIGO DE LA LINEA-->

                        <div class="form-group col-lg-3">

                            <label>Código Línea</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="codLinea" id="codLinea" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA LINEA-->

                        <div class="form-group col-lg-6">

                            <label>Línea</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="linea" id="linea" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL CODIGO DE FABRICA-->

                        <div class="form-group col-lg-3">

                            <label>CodFab</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="codfab" id="codfab" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA DESCRIPCION-->

                        <div class="form-group col-lg-3">

                            <label>Descripcion</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="descripcion" id="descripcion" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA UNIDAD-->

                        <div class="form-group col-lg-3">

                            <label>Unidad</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="unidad" id="unidad" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL COLOR-->

                        <div class="form-group col-lg-3">

                            <label>Color</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="color" id="color" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LAS VENTAS-->

                        <div class="form-group col-lg-3">

                            <label>Salidas Totales</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="salidasT" id="salidasT" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL PROMEDIO DE VENTAS POR MES-->

                        <div class="form-group col-lg-3">

                            <label>Promedio de Salidas</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="prom" id="prom" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL STOCK-->

                        <div class="form-group col-lg-3">

                            <label>Stock</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="stock" id="stock" required readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL PROVEEDOR-->

                        <div class="form-group col-lg-3">

                            <label>Proveedor Principal</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <input type="text" class="form-control input-sm" name="proveedor" id="proveedor" required readonly>

                            </div>

                        </div>

                        <!-- TABLA DE DETALLES -->

                        <label>TABLA DETALLES</label>

                        <div class="box-body">
                            <div id="scroll2">
                                <table class="table table-bordered table-striped dt-responsive tablaDetalleArticulo" width="100%">

                                    <thead>

                                        <tr>

                                            <th style="width:100px">Articulo</th>
                                            <th style="width:100px">Modelo</th>
                                            <th>Nombre</th>
                                            <th>Color</th>
                                            <th>Talla</th>
                                            <th>Estado</th>
                                            <th>Consumo</th>
                                            <th style="width:60px">TP</th>

                                        </tr>

                                    </thead>

                                    <tbody>



                                    </tbody>

                                </table>
                            </div>

                        </div>

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
MODAL EDITAR COSTOS
======================================-->

<div id="modalEditarCostos" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" onsubmit="return false">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar Costos Materia Prima</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA EL CÓDIGO -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-code"></i></span>

                                <input type="text" class="form-control input-lg" id="codigo" name="codigo" readonly required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA DESCRIPCIÓN -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-product-hunt"></i></span>

                                <input type="text" class="form-control input-lg" id="descripcionMP" name="descripcionMP" readonly required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA COLOR -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-paint-brush"></i></span>

                                <input type="text" class="form-control input-lg" id="colorMP" name="colorMP" readonly required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LOS COSTOS -->

                        <div class="form-group">

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-money"></i></span>

                                <input type="number" class="form-control input-lg" id="costo" name="costo" step="any" placeholder="Ingrese Costo en S/" required>

                            </div>

                        </div>



                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar cambios</button>

                </div>

            </form>

            <?php

            $editarMateriaPrimaCosto = new ControladorMateriaPrima();
            $editarMateriaPrimaCosto->ctrEditarMateriaPrimaCosto();

            ?>


        </div>

    </div>

</div>

<!--------------------------------
* Modal para saldos
--------------------------------->
<div id="modalSaldosMesMP" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" id="formularioSaldosMatPri">

                <!--=====================================
                CABEZA DEL MODAL
                ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Saldos a una fecha</h4>

                </div>

                <!--=====================================
                CUERPO DEL MODAL
                ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <div class="form-group col-lg-6">

                            <label>Fecha Fin</label>

                            <input type="date" class="form-control" id="fFin" name="fFin" min="2021-01-01" value="<?= date('Y-m-d') ?>">

                        </div>

                    </div>

                </div>

                <!--=====================================
                PIE DEL MODAL
                ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Salir</button>

                    <button type="button" id="generarSaldoMatPri" name="generarSaldoMatPri" class="btn btn-primary btnGenerarSaldoMatPri">Exportar</button>

                </div>

            </form>

        </div>

    </div>

</div>


<?php

$anularMateriaPrima = new ControladorMateriaPrima();
$anularMateriaPrima->ctrAnularMateriaPrima();

?>

<script src="vistas/js/materiaprima.js"></script>
<script>
    window.document.title = "Materia Prima";
    window.RUTA_MATERIAPRIMA = "<?php echo obtenerRutaMateriaPrima(); ?>";

    // Inicializar tabla con paginación del cliente (solo si no estamos en modo servidor)
    <?php if (!isset($modo_servidor_materiaprima) || !$modo_servidor_materiaprima): ?>
        $(document).ready(function() {
            var tipoPaginacion = "<?php echo (defined('TIPO_PAGINACION_MATERIAPRIMA')) ? TIPO_PAGINACION_MATERIAPRIMA : 'cliente'; ?>";
            // En materiaprima.php siempre usamos la tabla cliente
            if (tipoPaginacion === "servidor") {
                // Si solo está configurado servidor, redirigir a materiaprima-test
                // Pero si estamos aquí, significa que la configuración permite cliente
                cargarTablaMateriaPrima();
            } else {
                // Para "cliente" o "ambos", usar tabla cliente
                cargarTablaMateriaPrima();
            }
        });
    <?php endif; ?>
</script>