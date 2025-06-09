<div class="content-wrapper">

    <section class="content-header">

        <h1>
            <?php


            if (isset($_GET["numCta"])) {

                $cuentas = ControladorCuentas::ctrMostrarCuentasV2($_GET["numCta"], $_GET["codCuenta"]);

                //$cliente = ControladorClientes::ctrMostrarClientes("codigo", $cuentas["cliente"]);
            } else {

                $cuentas = '';
                $cliente = '';
                $cuentas["num_cta"] = '';
                $cuentas["tipo_doc"] = '';
                $cuentas["num_cta"] = '';
                $cuentas["fecha"] = '';
                $cuentas["fecha_ven"] = '';
                $cuentas["cliente"] = '';
                $cliente["nombre"] = '';
                $cuentas["vendedor"] = '';
                $cuentas["estado"] = '';
                $cuentas["saldo"] = '';
                $cuentas["num_unico"] = '';
                $cuentas["monto"] = '';
            }


            //var_dump($cuentas["num_cta"]);



            ?>
            Administrar cancelaciones de N° de cuenta <?php echo $cuentas["num_cta"] ?>

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar cancelaciones</li>

        </ol>

    </section>

    <section class="content ">
        <div class="  col-lg-5">
            <div class="box box-success">
                <div class="box-body">
                    <div class="col-md-3" style="margin-bottom:10px">

                        <a href="<?php echo $_GET["rutas"] ?>" class="btn btn-danger"><i class="fa fa-arrow-left"> Atrás </i></a>

                    </div>
                    <div class="col-md-12"></div>

                    <div class="col-md-3 ">
                        <label for="">Tipo Documento</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["tipo_doc"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Nro Documento</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["num_cta"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Fecha</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["fecha"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Fecha Vencimiento</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["fecha_ven"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Clientes</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["cliente"]; ?>" readonly>
                    </div>

                    <div class="col-md-6">
                        <div style="margin-top:25px"></div>
                        <input type="text" class="form-control" value="<?php echo $cuentas["nombre"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Vendedor</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["vendedor"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Estado</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["estado"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Saldo</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["saldo"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Nro unico</label>
                        <input type="text" class="form-control" value="<?php echo $cuentas["num_unico"]; ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="">Total</label>
                        <input type="text" class="form-control" value="<?php echo "S/." . $cuentas["monto"]; ?>" readonly>
                    </div>

                    <?php if ($cuentas["saldo"] != 0) { ?>
                        <div class="col-md-3" style="margin-top:30px;">
                            <button class='btn btn-success btnCancelarCuenta2' numCta="<?php echo $_GET["numCta"] ?>" codCta="<?php echo $_GET["codCuenta"] ?>" data-toggle='modal' data-target='#modalCancelarCuenta' title='Cancelar cuenta'><i class='fa fa-money'></i> Cancelar cuenta</button>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class=" col-lg-7">
            <div class="box box-warning">
                <div class="box-body">
                    <table class="table table-bordered table-striped dt-responsive tablaVerCuentas" width="100%">

                        <thead>

                            <tr>
                                <th>Tipo</th>
                                <th>Doc. origen</th>
                                <th>Fecha</th>
                                <th>Notas</th>
                                <th>Monto</th>
                                <th>Acciones</th>

                            </tr>

                        </thead>

                        <tbody>
                        </tbody>

                    </table>

                </div>
            </div>
        </div>

    </section>

</div>


<!--=====================================
MODAL EDITAR TIPO PAGO
======================================-->

<div id="modalEditarCancelacion" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 85% !important;">

        <div class="modal-content">

            <form role="form" method="post" onsubmit="return checkSubmitG();">
                <input type="hidden" id="rutas" name="rutas" value="<?php echo $_GET["rutas"]; ?>">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar Cancelacion</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Documento por cancelar</b></label>
                            <label for=""><b>Tipo de cancelacion</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="cancelarCodigo" id="cancelarCodigo" data-live-search="true" required>
                                    <option value="">Seleccionar cancelacion</option>

                                    <?php
                                    $item = "tipo_dato";
                                    $valor = "TCAN";

                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);

                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                                <input type="hidden" id="cancelarUsuario" name="cancelarUsuario" value="<?php echo $_SESSION["id"] ?>">
                                <input type="hidden" id="idCuenta2" name="idCuenta2">
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Nro de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="cancelarDocumento" id="cancelarDocumento" required>
                                <input type="hidden" class="form-control input-lg" name="docEditar" id="docEditar" required>
                                <input type="hidden" class="form-control input-lg" name="tipEditar" id="tipEditar" required>
                                <input type="hidden" class="form-control input-lg" name="cliEditar" id="cliEditar" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA FECHA  -->
                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for="">Fecha último pago</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="cancelarFechaUltima" id="cancelarFechaUltima" min="<?php echo CIERRE_PERIODO ?>" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA NOTA -->

                        <div class="form-group col-lg-3">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Notas</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-width"></i></span>

                                <input type="text" class="form-control input-lg" name="cancelarNota" id="cancelarNota" required>

                            </div>

                        </div>


                        <div class="form-group col-lg-3">
                            <div style="margin-top:23px"></div>
                            <label for="">Monto </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="cancelarMonto2" id="cancelarMonto2">
                                <input type="hidden" id="cancelarMontoAntiguo" name="cancelarMontoAntiguo">
                                <input type="hidden" id="cancelarSaldoAntiguo" name="cancelarSaldoAntiguo" value="<?php echo $cuentas["saldo"]; ?>">
                                <input type="hidden" id="cancelarVendedor" name="cancelarVendedor">
                                <input type="hidden" id="cancelarCliente" name="cancelarCliente">

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" id="btnBlocClic" class="btn btn-primary">Editar cancelacion</button>

                </div>

            </form>

            <?php

            $editarCancelacion = new ControladorCuentas();
            $editarCancelacion->ctrEditarCancelacion();

            ?>


        </div>

    </div>

</div>

<!--=====================================
MODAL CANCELAR CUENTA
======================================-->

<div id="modalCancelarCuenta" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 85% !important;">

        <div class="modal-content">

            <form role="form" method="post" onsubmit="return checkSubmitGC();">
                <input type="hidden" id="rutas" name="rutas" value="<?php echo $_GET["rutas"]; ?>">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Cancelar cuenta</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-1">
                            <label for=""><b>Tipo de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarTipoDocumento2" id="cancelarTipoDocumento2" value="<?php echo $cuentas["tipo_doc"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Nro de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarDocumentoOriginal2" id="cancelarDocumentoOriginal2" value="<?php echo $cuentas["num_cta"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Fecha Emisión</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="date" class="form-control input-md" name="cancelarFechaOrigen2" id="cancelarFechaOrigen2" value="<?php echo $cuentas["fecha"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Fecha Vencimiento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="date" class="form-control input-md" name="cancelarVencimientoOrigen2" id="cancelarVencimientoOrigen2" value="<?php echo $cuentas["fecha_ven"]; ?>" readonly>

                            </div>

                        </div>
                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Clientes</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarCliente2" id="cancelarCliente2" value="<?php echo $cuentas["cliente"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-3">
                            <div style="margin-top:46px"></div>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarClienteNomOrigen2" id="cancelarClienteNomOrigen2" value="<?php echo $cuentas["nombre"]; ?>" readonly>

                            </div>

                        </div>
                        <div class="col-lg-12"></div>
                        <div class="form-group col-lg-1">
                            <label for=""><b>Vendedor</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarVendedor2" id="cancelarVendedor2" value="<?php echo $cuentas["vendedor"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-2">
                            <label for=""><b>Estado</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarEstado2" id="cancelarEstado2" value="<?php echo $cuentas["estado"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-2">
                            <label for=""><b>Saldo</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="number" class="form-control input-md" name="cancelarSaldoAntiguo2" id="cancelarSaldoAntiguo2" value="<?php echo $cuentas["saldo"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-2">
                            <label for=""><b>Num. Unico</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="number" class="form-control input-md" name="cancelarNumUnico2" id="cancelarNumUnico2" value="<?php echo $cuentas["estado"]; ?>" readonly>

                            </div>

                        </div>


                        <div class="form-group col-lg-2">
                            <label for=""><b>Total S/.</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="number" class="form-control input-md" name="cancelarTotal2" id="cancelarTotal2" value="<?php echo $cuentas["saldo"]; ?>" readonly>

                            </div>

                        </div>

                        <div class="col-lg-12 bg-primary"></div>

                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Documento por cancelar</b></label><br>
                            <label for=""><b>Tipo de cancelacion</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <select type="text" class="form-control input-md selectpicker" name="cancelarCodigo2" id="cancelarCodigo2" data-size="10" data-live-search="true" required>
                                    <option value="">Seleccionar tipo de cancelacion</option>

                                    <?php
                                    $item = "tipo_dato";
                                    $valor = "TCAN";

                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);

                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                                <input type="hidden" id="cancelarUsuario2" name="cancelarUsuario2" value="<?php echo $_SESSION["id"] ?>">
                                <input type="hidden" id="idCuenta3" name="idCuenta3" value="<?php echo $cuentas["id"]; ?>">
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Nro de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarDocumento2" id="cancelarDocumento2" value="<?php echo $cuentas["num_cta"]; ?>" placeholder="Documento origen">
                            </div>

                        </div>

                        <?php
                        date_default_timezone_set("America/Lima");
                        $fecha = new DateTime();
                        ?>
                        <!-- ENTRADA PARA LA FECHA  -->
                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for="">Fecha </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-md" name="cancelarFechaUltima2" id="cancelarFechaUltima2" value="<?php echo $fecha->format("Y-m-d") ?>" min="<?php echo CIERRE_PERIODO ?>" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA NOTA -->

                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Notas</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-width"></i></span>

                                <input type="text" class="form-control input-md" name="cancelarNota2" id="cancelarNota2">

                            </div>

                        </div>


                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for="">Monto </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-md" name="cancelarMonto3" id="cancelarMonto3" value="0" required>

                            </div>

                        </div>

                        <div class="form-group col-lg-2">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Saldo</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-width"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-md" name="cancelarSaldo2" id="cancelarSaldo2" value="<?php echo $cuentas["saldo"]; ?>" readonly>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" id="btnBlocClicC" class="btn btn-primary">Cancelar cuenta</button>

                </div>

            </form>

            <?php

            $cancelarCuenta2 = new ControladorCuentas();
            $cancelarCuenta2->ctrCancelarCuenta2();

            ?>


        </div>

    </div>

</div>

<?php

$eliminarCancelacion = new ControladorCuentas();
$eliminarCancelacion->ctrEliminarCancelacion($_GET["rutas"]);

?>

<script>
    window.document.title = "Cancelaciones de cuenta"
</script>