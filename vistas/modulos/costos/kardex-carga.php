<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Administrar Kardex

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar Kardex</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarKardex">

                    Cargar Kardex

                </button>
            </div>

            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive TablaKardexCostos" width="100%">

                    <thead>

                        <tr>

                            <th>Id</th>
                            <th>Tipo</th>
                            <th>Codigo</th>
                            <th>Año</th>
                            <th>Mes</th>
                            <th>Saldo Inicial</th>
                            <th>Ingreso</th>
                            <th>Salida</th>
                            <th>Saldo Final</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
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
MODAL AGREGAR CENTRO DE COSTOS
======================================-->

<div id="modalAgregarKardex" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar Kardex</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <div class="form-group">
                            <label class="col-form-label col-lg-4 col-md-1">Tipo Kardex</label>
                            <div class="col-lg-8 col-md-3">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="tipoKardex" id="tipoKardex" required>
                                    <option value="">Seleccionar Tipo Kardex</option>
                                    <option value="APT">Producto Terminado</option>
                                    <option value="AMP">Materia Prima</option>
                                    <option value="APP">Producto en Proceso</option>

                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="padding-top:25px">
                            <label class="col-form-label col-lg-4 col-md-1">Año</label>
                            <div class="col-lg-8 col-md-3">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="anno" id="anno" required>
                                    <option value="">Seleccionar Año</option>

                                    <option value="2020">2020</option>
                                    <option value="2021">2021</option>
                                    <option value="2022">2022</option>
                                    <option value="2023">2023</option>
                                    <option value="2024">2024</option>
                                    <option value="2025">2025</option>
                                    <option value="2025">2026</option>

                                </select>

                            </div>

                        </div>

                        <div class="form-group" style="padding-top:25px">
                            <label class="col-form-label col-lg-4 col-md-1">Mes</label>
                            <div class="col-lg-8 col-md-3">

                                <select class="form-control input-md selectpicker" data-live-search="true" name="mes" id="mes" required>
                                    <option value="">Seleccionar Mes</option>

                                    <option value="Enero">Enero</option>
                                    <option value="febrero">febrero</option>
                                    <option value="Marzo">Marzo</option>
                                    <option value="Abril">Abril</option>
                                    <option value="Mayo">Mayo</option>
                                    <option value="Junio">Junio</option>
                                    <option value="Julio">Julio</option>
                                    <option value="Agosto">Agosto</option>
                                    <option value="Septiembre">Septiembre</option>
                                    <option value="Octubre">Octubre</option>
                                    <option value="Noviembre">Noviembre</option>
                                    <option value="Diciembre">Diciembre</option>


                                </select>

                            </div>

                        </div>

                        <div class="form-group" style="padding-top:25px">
                            <label class="col-form-label col-lg-3 col-md-1">Saldo Inicial</label>
                            <div class="col-lg-3 col-md-3">

                                <input type="text" class="form-control input-md" name="saldo_inicial" id="saldo_inicial">

                            </div>

                            <label class="col-form-label col-lg-3 col-md-1">Ingresos</label>
                            <div class="col-lg-3 col-md-3">

                                <input type="text" class="form-control input-md" name="ingresos" id="ingresos">

                            </div>

                        </div>


                        <div class="form-group" style="padding-top:25px">
                            <label class="col-form-label col-lg-3 col-md-1">Salidas</label>
                            <div class="col-lg-3 col-md-3">

                                <input type="text" class="form-control input-md" name="salidas" id="salidas">

                            </div>

                            <label class="col-form-label col-lg-3 col-md-1">Saldo Final</label>
                            <div class="col-lg-3 col-md-3">

                                <input type="text" class="form-control input-md" name="saldo_final" id="saldo_final">

                            </div>

                        </div>

                        <div class="form-group" style="padding-top:25px">

                            <label class="col-form-label col-lg-2 col-md-1">Archivo</label>
                            <div class="col-lg-10">
                                <input type="file" name="archivo" id="archivo" class="form-control" required>
                            </div>

                        </div>


                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Cargar Kardex</button>

                </div>

                <?php

                $crearCentroCostos = new ControladorCentroCostos();
                $crearCentroCostos->ctrCargarKardexB();

                ?>

            </form>

        </div>

    </div>

</div>

<script>
    window.document.title = "Kardex"
</script>