<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Kardex

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Kardex</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <div class="form-group">

                    <label class="col-form-label col-lg-1 col-md-3 col-sm-3">Código</label>
                    <div class="col-lg-1">
                        <input type="text" data-toggle="modal" data-target="#modalMP" class="form-control input-sm" id="codpro" name="codpro" autocomplete="off">
                    </div>

                    <label class="col-form-label col-lg-1 col-md-3 col-sm-3">Cod. Fab</label>
                    <div class="col-lg-1">
                        <input type="text" class="form-control input-sm" id="codfab" name="codfab" readonly>
                    </div>

                    <label class="col-form-label col-lg-1 col-md-3 col-sm-3">Descripción</label>
                    <div class="col-lg-4">
                        <input type="text" class="form-control input-sm" id="descripcion" name="descripcion" readonly>
                    </div>

                    <label class="col-form-label col-lg-1 col-md-3 col-sm-3">Color</label>
                    <div class="col-lg-2">
                        <input type="text" class="form-control input-sm" id="color" name="color" readonly>
                    </div>

                </div>

                <div class="form-group" style="padding-top:25px">

                    <label class="col-form-label col-lg-1 col-md-3 col-sm-3">Stock Actual</label>
                    <div class="col-lg-1">
                        <input type="text" class="form-control input-sm" id="stock" name="stock" readonly>
                    </div>

                    <label class="col-form-label col-lg-1 col-md-3 col-sm-3">Año</label>
                    <div class="col-lg-2">
                        <select name="ano" id="ano" class="form-control input-sm">
                            <option value="">Seleccione Año</option>
                            <option value="2016" <?php echo date('Y') == "2016" ? "selected" : "" ?>>2016</option>
                            <option value="2017" <?php echo date('Y') == "2017" ? "selected" : "" ?>>2017</option>
                            <option value="2018" <?php echo date('Y') == "2018" ? "selected" : "" ?>>2018</option>
                            <option value="2019" <?php echo date('Y') == "2019" ? "selected" : "" ?>>2019</option>
                            <option value="2020" <?php echo date('Y') == "2020" ? "selected" : "" ?>>2020</option>
                            <option value="2021" <?php echo date('Y') == "2021" ? "selected" : "" ?>>2021</option>
                            <option value="2022" <?php echo date('Y') == "2022" ? "selected" : "" ?>>2022</option>
                            <option value="2023" <?php echo date('Y') == "2023" ? "selected" : "" ?>>2023</option>
                            <option value="2024" <?php echo date('Y') == "2024" ? "selected" : "" ?>>2024</option>
                            <option value="2025" <?php echo date('Y') == "2025" ? "selected" : "" ?>>2025</option>
                            <option value="2026" <?php echo date('Y') == "2026" ? "selected" : "" ?>>2026</option>
                            <option value="2027" <?php echo date('Y') == "2027" ? "selected" : "" ?>>2027</option>
                            <option value="2028" <?php echo date('Y') == "2028" ? "selected" : "" ?>>2028</option>
                            <option value="2029" <?php echo date('Y') == "2029" ? "selected" : "" ?>>2029</option>
                            <option value="2030" <?php echo date('Y') == "2030" ? "selected" : "" ?>>2030</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <button id="filtrar" name="filtrar" class="btn btn-primary btnFiltrar" disabled><i class="fa fa-search"></i> Filtrar</button>
                    </div>

                    <!-- Boton para exportar a excel -->
                    <div class="col-lg-2">
                        <button id="exportar" name="exportar" class="btn btn-success btnExportar" disabled><i class="fa fa-file-excel-o"></i> Exportar a Excel</button>
                    </div>


                </div>




            </div>

            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive tablaKardexMateriaPrima" width="100%">

                    <thead>

                        <tr>

                            <th style="width:200px">N° Documento</th>
                            <th style="width:80px">Mes</th>
                            <th style="width:100px">Fecha</th>
                            <th>Razón Social</th>
                            <th style="width:120px">Stock Inicial</th>
                            <th style="width:120px">Ingresos</th>
                            <th style="width:120px">Salidas</th>

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

<div id="modalMP" class="modal fade" role="dialog">

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

                        <!-- TABLA DE DETALLES -->
                        <div class="box-body">

                            <table class="table table-bordered table-striped dt-responsive tablaMpKardex" width="100%">

                                <thead>

                                    <tr>

                                        <th style="min-width:50px">Código</th>
                                        <th style="min-width:50px">Cod. Fab</th>
                                        <th style="min-width:350px">Descripcion</th>
                                        <th style="min-width:200px">Color</th>
                                        <th style="min-width:10px">Stock</th>
                                        <th>#</th>

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

<script>
    window.document.title = "Kardex"
</script>