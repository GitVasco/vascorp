<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Ordenes de Corte

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Ordenes de corte</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <a href="crear-ordencorte">

                    <button class="btn btn-primary">

                        Agregar Orden de Corte

                    </button>

                </a>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalSubirCSVOrdenCorte">
                    <i class="fa fa-upload"></i> Subir CSV
                </button>
                <button class="btn btn-info btnOrdenCorteDeta" data-toggle='modal' data-target='#modalVerOrdenCorteDeta' ordencorte><i class="fa fa-eye"></i> Ver OC saldo</button>
                <button class="btn btn-warning btnOrdenCorteCantidad" data-toggle='modal' data-target='#modalVerOrdenCorteCantidad' ordencorte><i class="fa fa-eye"></i> Ver OC cantidad</button>
                <button class="btn btn-outline-success btnReporteOrdenC" modelo="" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Orden corte </button>
                <button class="btn btn-outline-success btnReporteOrdenMP" modelo="" style="border:green 1px solid">
                    <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Materia Prima </button>
                <button type="button" class="btn btn-default pull-right" id="daterange-btnCorte">

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
                <table class="table table-bordered table-striped dt-responsive tablaOrdenCorte" width="100%">

                    <thead>

                        <tr>

                            <th style="width:100px;">Or. de Corte</th>
                            <th>Responsable</th>
                            <th>
                                <center>Cantidad Total</center>
                            </th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th style="width:250px">Acciones</th>

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

<div id="modalVisualizarOC" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 70% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle de la Orden de Corte Saldo</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA CODIGO DEL OC-->

                        <div class="form-group col-lg-3">

                            <label>Orden de Corte</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="ordencorte" id="ordencorte" required readonly></strong>

                            </div>

                        </div>


                        <!-- ENTRADA PARA LA FECHA-->

                        <div class="form-group col-lg-3">

                            <label>Creación</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="fecha" id="fecha" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA CONFIGURACION-->

                        <div class="form-group col-lg-3">

                            <label>Configuración</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="configuracion" id="configuracion" required readonly></strong>

                            </div>

                        </div>


                        <!-- ENTRADA PARA LA RESPONSABLE-->

                        <div class="form-group col-lg-3">

                            <label>Responsable</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="nombre" id="nombre" required readonly></strong>

                            </div>

                        </div>


                        <!-- ENTRADA PARA LA CANTIDAD-->

                        <div class="form-group col-lg-4">

                            <label>Cantidad Inicial</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="cantidad" id="cantidad" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL SALDO-->

                        <div class="form-group col-lg-4">

                            <label>Saldo Actual</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="saldo" id="saldo" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL ESTADO-->

                        <div class="form-group col-lg-4">

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

                            <table class="table table-bordered table-striped dt-responsive tablaVerOrdenCorte" width="100%">

                                <thead>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">S</th>
                                        <th style="width:100px">M</th>
                                        <th style="width:100px">L</th>
                                        <th style="width:100px">XL</th>
                                        <th style="width:100px">XXL</th>
                                        <th style="width:100px">XS</th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">28</th>
                                        <th style="width:100px">30</th>
                                        <th style="width:100px">32</th>
                                        <th style="width:100px">34</th>
                                        <th style="width:100px">36</th>
                                        <th style="width:100px">38</th>
                                        <th style="width:100px">40</th>
                                        <th style="width:100px">42</th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th>Ord. Corte</th>
                                        <th style="width:100px">Modelo</th>
                                        <th style="width:280px">Nombre</th>
                                        <th style="width:150px">Color</th>
                                        <th style="width:100px">3</th>
                                        <th style="width:100px">4</th>
                                        <th style="width:100px">6</th>
                                        <th style="width:100px">8</th>
                                        <th style="width:100px">10</th>
                                        <th style="width:100px">12</th>
                                        <th style="width:100px">14</th>
                                        <th style="width:100px">16</th>
                                        <th style="width:100px">Total</th>
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

                </div>



            </form>

        </div>

    </div>

</div>

<div id="modalVisualizarOCCantidad" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 70% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle de la Orden de Corte Cantidad</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA CODIGO DEL OC-->

                        <div class="form-group col-lg-3">

                            <label>Orden de Corte</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="ordencorte2" id="ordencorte2" required readonly></strong>

                            </div>

                        </div>


                        <!-- ENTRADA PARA LA FECHA-->

                        <div class="form-group col-lg-3">

                            <label>Creación</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="fecha2" id="fecha2" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA CONFIGURACION-->

                        <div class="form-group col-lg-3">

                            <label>Configuración</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="configuracion2" id="configuracion2" required readonly></strong>

                            </div>

                        </div>


                        <!-- ENTRADA PARA LA RESPONSABLE-->

                        <div class="form-group col-lg-3">

                            <label>Responsable</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="nombre2" id="nombre2" required readonly></strong>

                            </div>

                        </div>


                        <!-- ENTRADA PARA LA CANTIDAD-->

                        <div class="form-group col-lg-4">

                            <label>Cantidad Inicial</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="cantidad2" id="cantidad2" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL SALDO-->

                        <div class="form-group col-lg-4">

                            <label>Saldo Actual</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="saldo2" id="saldo2" required readonly></strong>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL ESTADO-->

                        <div class="form-group col-lg-4">

                            <label>Estado</label>

                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-caret-square-o-right"></i></span>

                                <strong><input type="text" class="form-control input-sm" name="estado2" id="estado2" required readonly></strong>

                            </div>

                        </div>

                        <!-- TABLA DE DETALLES -->

                        <div class="form-group col-lg-12">
                            <label>TABLA DETALLES</label>
                        </div>

                        <div class="box-body">

                            <table class="table table-bordered table-striped dt-responsive tablaVerOrdenCorteCantidad" width="100%">

                                <thead>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">S</th>
                                        <th style="width:100px">M</th>
                                        <th style="width:100px">L</th>
                                        <th style="width:100px">XL</th>
                                        <th style="width:100px">XXL</th>
                                        <th style="width:100px">XS</th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">28</th>
                                        <th style="width:100px">30</th>
                                        <th style="width:100px">32</th>
                                        <th style="width:100px">34</th>
                                        <th style="width:100px">36</th>
                                        <th style="width:100px">38</th>
                                        <th style="width:100px">40</th>
                                        <th style="width:100px">42</th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th>Ord. Corte</th>
                                        <th style="width:100px">Modelo</th>
                                        <th style="width:280px">Nombre</th>
                                        <th style="width:150px">Color</th>
                                        <th style="width:100px">3</th>
                                        <th style="width:100px">4</th>
                                        <th style="width:100px">6</th>
                                        <th style="width:100px">8</th>
                                        <th style="width:100px">10</th>
                                        <th style="width:100px">12</th>
                                        <th style="width:100px">14</th>
                                        <th style="width:100px">16</th>
                                        <th style="width:100px">Total</th>
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

                </div>



            </form>

        </div>

    </div>

</div>

<!--=====================================
MODAL VISUALIZAR INFORMACION
======================================-->

<div id="modalVerOrdenCorteDeta" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 70% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle de saldo de Ordenes de Corte</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-header">
                        <button type="button" class="btn btn-default pull-right" id="daterange-btnGeneralCorte">
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

                        <!-- TABLA DE DETALLES -->

                        <div class="form-group col-lg-12">
                            <label>TABLA DETALLES</label>
                        </div>

                        <div class="box-body">

                            <table class="table table-bordered table-striped dt-responsive tablaDetalleOrdenCorteTotal" width="100%">

                                <thead>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">S</th>
                                        <th style="width:100px">M</th>
                                        <th style="width:100px">L</th>
                                        <th style="width:100px">XL</th>
                                        <th style="width:100px">XXL</th>
                                        <th style="width:100px">XS</th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">28</th>
                                        <th style="width:100px">30</th>
                                        <th style="width:100px">32</th>
                                        <th style="width:100px">34</th>
                                        <th style="width:100px">36</th>
                                        <th style="width:100px">38</th>
                                        <th style="width:100px">40</th>
                                        <th style="width:100px">42</th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th>Ord. Corte</th>
                                        <th style="width:100px">Fecha</th>
                                        <th style="width:100px">Modelo</th>
                                        <th style="width:280px">Nombre</th>
                                        <th style="width:150px">Color</th>
                                        <th style="width:100px">3</th>
                                        <th style="width:100px">4</th>
                                        <th style="width:100px">6</th>
                                        <th style="width:100px">8</th>
                                        <th style="width:100px">10</th>
                                        <th style="width:100px">12</th>
                                        <th style="width:100px">14</th>
                                        <th style="width:100px">16</th>
                                        <th style="width:100px">Total</th>
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

                </div>



            </form>

        </div>

    </div>

</div>

<!--=====================================
MODAL VISUALIZAR INFORMACION
======================================-->

<div id="modalVerOrdenCorteCantidad" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 70% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Detalle de la Orden de Corte Original</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-header">
                        <button type="button" class="btn btn-default pull-right" id="daterange-btnCantidadCorte">
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

                        <!-- TABLA DE DETALLES -->

                        <div class="form-group col-lg-12">
                            <label>TABLA DETALLES</label>
                        </div>

                        <div class="box-body">

                            <table class="table table-bordered table-striped dt-responsive tablaCantidadOrdenCorteTotal" width="100%">

                                <thead>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">S</th>
                                        <th style="width:100px">M</th>
                                        <th style="width:100px">L</th>
                                        <th style="width:100px">XL</th>
                                        <th style="width:100px">XXL</th>
                                        <th style="width:100px">XS</th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th></th>
                                        <th style="width:100px"></th>
                                        <th style="width:100px"></th>
                                        <th style="width:280px"></th>
                                        <th style="width:150px"></th>
                                        <th style="width:100px">28</th>
                                        <th style="width:100px">30</th>
                                        <th style="width:100px">32</th>
                                        <th style="width:100px">34</th>
                                        <th style="width:100px">36</th>
                                        <th style="width:100px">38</th>
                                        <th style="width:100px">40</th>
                                        <th style="width:100px">42</th>
                                        <th style="width:100px"></th>
                                    </tr>

                                    <tr>
                                        <th>Ord. Corte</th>
                                        <th style="width:100px">Fecha</th>
                                        <th style="width:100px">Modelo</th>
                                        <th style="width:280px">Nombre</th>
                                        <th style="width:150px">Color</th>
                                        <th style="width:100px">3</th>
                                        <th style="width:100px">4</th>
                                        <th style="width:100px">6</th>
                                        <th style="width:100px">8</th>
                                        <th style="width:100px">10</th>
                                        <th style="width:100px">12</th>
                                        <th style="width:100px">14</th>
                                        <th style="width:100px">16</th>
                                        <th style="width:100px">Total</th>
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

                </div>



            </form>

        </div>

    </div>

</div>

<!--=====================================
MODAL SUBIR CSV - CREAR ORDEN DE CORTE
======================================-->
<div id="modalSubirCSVOrdenCorte" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#5cb85c; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-upload"></i> Registrar OC desde CSV</h4>
            </div>
            <div class="modal-body">
                <p>Suba un archivo CSV delimitado por comas con dos columnas: <strong>código de artículo</strong> y <strong>cantidad</strong>.</p>
                <p class="text-muted small">Ejemplo (primera línea opcional como encabezado):<br>
                <code>articulo,cantidad</code><br>
                <code>1010101,50</code><br>
                <code>1010102,100</code></p>
                <div class="form-group">
                    <label>Archivo CSV</label>
                    <input type="file" class="form-control" id="archivoCSVOrdenCorte" accept=".csv" required>
                </div>
                <div id="resumenCSVOrdenCorte" class="alert alert-info" style="display:none;"></div>
                <div id="errorCSVOrdenCorte" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnProcesarCSVOrdenCorte">
                    <i class="fa fa-check"></i> Registrar orden de corte
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.document.title = "Orden de corte"
</script>