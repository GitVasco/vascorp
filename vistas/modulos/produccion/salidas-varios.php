<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Movimientos Varios General

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Movimientos Varios General</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <div class="btn-group pull-left">

                    <?php


                    $salida = "";

                    echo '<button class="btn btn-primary  btnCrearSalidaVarios" salida="' . $salida . '" title="Crear Salida Varios">

                  Crear Salida 

                </button>';


                    ?>

                </div>


            </div>

            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive tablaSalidaVarios" width="100%">

                    <thead>

                        <tr>
                            <th>Id</th>
                            <th>Código</th>
                            <th>Cod. Cliente</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </section>

</div>

<!--=====================================
MODAL FACTURAR
======================================-->

<div id="modalFacturar" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 50% !important;">

        <div class="modal-content">

            <form role="form" method="post" id="formFacturarSalida">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Pasar Movimiento a:</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <div class="box box-primary col-lg-12 ">

                            <div class="box-header">

                                <b>Datos Principales</b>

                            </div>

                            <!-- ENTRADA PARA EL CODIGO DEL SALIDA-->

                            <div class="form-group col-lg-3">

                                <label>Cod. Movimiento</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="codSalida" name="codSalida" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NOMBRE DEL CLIENTE-->

                            <div class="form-group col-lg-9">

                                <label>Cliente</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="nomCli" name="nomCli" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL codigo DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Cod. Cliente</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="codCli" name="codCli" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL TIPO DOCUMENTO DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Tipo Documento</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="tipDoc" name="tipDoc" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NUMERO DOCUMENTO DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Nro. Documento</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="nroDoc" name="nroDoc" readonly>

                                    <input type="hidden" class="form-control input-sm" name="dscto" id="dscto" readonly>
                                    <input type="hidden" class="form-control input-sm" name="codVen" id="codVen" readonly>
                                    <input type="hidden" name="idUsuario" id="idUsuario" value="<?php echo $_SESSION["id"]; ?>">

                                </div>

                            </div>

                        </div>

                        <div class="box box-success col-lg-12 ">

                            <div class="box-header">

                                <b>Documento Destino</b>

                            </div>

                            <!-- ENTRADA PARA TIPO DE DOCUMENTO -->

                            <div class="form-group col-lg-6">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-share-square-o"></i></span>
                                    <select type="text" class="form-control input-sm selectpicker" name="tdoc" id="tdoc2" data-live-search="true" required>
                                        <option value="">Seleccionar tipo de documento</option>

                                        <?php

                                        $item = "tipo_dato";
                                        $valor = "TTOP";

                                        $array = array('E01', 'E02', 'E05', 'E20', 'E30', 'E49', 'E50', 'S01', 'S02', 'S03', 'S04', 'S25', 'S26', 'S27', 'S28', 'S30', 'S55', 'S60', 'S70', 'S96', 'S16');

                                        $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);

                                        $opciones = array();

                                        foreach ($documentos as $key => $value) {
                                            if (!in_array($value["codigo"], $array)) {
                                                $opciones[$value["codigo"]] = $value["codigo"] . " - " . $value["descripcion"];
                                            }
                                        }

                                        ksort($opciones); // Ordena el array $opciones por clave (código)

                                        foreach ($opciones as $codigo => $opcion) {
                                            echo '<option value="' . $codigo . '">' . $opcion . '</option>';
                                        }

                                        ?>

                                    </select>

                                </div>

                            </div>


                            <!-- ENTRADA PARA NUMERO DE SERIE DEL DOCUMENTO SEPARADO-->

                            <div class="form-group col-lg-6">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                    <input type="text" class="form-control input-md" name="serieSalida" id="serieSalida" readonly>
                                    <input type="hidden" name="nomTipo" id="nomTipo" readonly>


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

                    <button type="submit" class="btn btn-primary">Generar Documento</button>

                </div>

            </form>

            <?php

            $facturar = new controladorFacturacion();
            $facturar->ctrFacturarSalida();

            ?>

        </div>

    </div>

</div>

<script>
    window.document.title = "Salida Varios"
</script>