<?php
require_once "modelos/linea-credito.modelo.php";
$usuariosActivosCxc = ModeloLineaCredito::mdlUsuariosActivos();
$idUsuarioSesionCxc = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
?>
<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Administrar cuentas

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar cuentas</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <button class="btn btn-primary btnCodigoCuenta" data-toggle="modal" data-target="#modalAgregarCuenta" title="Agregar cuentas">
                    <i class="fa fa-plus"></i>
                    <span class="sr-only">Agregar cuentas</span>
                </button>

                <button class="btn btn-danger" data-toggle="modal" data-target="#modalImportarBanco" title="Cancelar letras">
                    <i class="fa fa-ban"></i>
                    <span class="sr-only">Cancelar letras</span>
                </button>

                <button class="btn btn-warning" data-toggle="modal" data-target="#modalActualizarUnico" title="Actualizar número único">
                    <i class="fa fa-refresh"></i>
                    <span class="sr-only">Actualizar número único</span>
                </button>

                <div class="col-lg-2 pull-right">
                    <select class="form-control input-lg selectpicker" name="selectAnoCuenta" id="selectAnoCuenta" data-live-search="true" data-size="10">
                        <option value="">Seleccionar Año</option>
                        <option value="2014">2014</option>
                        <option value="2015">2015</option>
                        <option value="2016">2016</option>
                        <option value="2017">2017</option>
                        <option value="2018">2018</option>
                        <option value="2019">2019</option>
                        <option value="2020">2020</option>
                        <option value="2021">2021</option>
                        <option value="2022">2022</option>
                        <option value="2023">2023</option>
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                        <option value="2029">2029</option>
                        <option value="2030">2030</option>
                    </select>
                </div>

                <div class="pull-right">
                    <button class="btn btn-outline-success" style="border:green 1px solid" data-toggle="modal" data-target="#modalFechasCredipagos">
                        <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte Credipagos </button>
                </div>

                <div class="pull-right">
                    <button class="btn btn-outline-success btnReporteCuentas" ano="null" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/excel.png" width="20px"> Reporte cuentas </button>
                </div>

                <?php if (function_exists("usuarioPuedeVerModulo") && usuarioPuedeVerModulo("gestion_comercial", "estado_cuenta_gerencia")) : ?>
                <div class="pull-right">
                    <button type="button" class="btn btn-outline-success" id="btnReporteClasificacionMorosidad" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/excel.png" width="20px"> Clasificación morosidad </button>
                </div>
                <?php endif; ?>

                <div class="pull-right">
                    <button class="btn btn-outline-success btnDocContado" ano="null" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/download.png" width="20px"> Reporte Doc. Contado </button>
                </div>

                <div class="pull-right">
                    <button class="btn btn-outline-success btnPorAceptar" ano="null" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/download.png" width="20px"> Letras x Aceptar </button>
                </div>

                <div class="pull-right">
                    <button class="btn btn-outline-success btnProyeccionPagos" ano="null" style="border:green 1px solid">
                        <img src="vistas/img/plantilla/download.png" width="20px"> Proyección Pagos </button>
                </div>

                <div class="pull-right" style="margin-right: 10px;">
                    <button type="button" class="btn btn-default btnAbrirConfigLetra" title="Configurar impresión de letras">
                        <i class="fa fa-cog"></i> Configuración impresión
                    </button>
                </div>
                <div class="pull-right" style="margin-right: 10px;">
                    <button type="button" class="btn btn-default btnImprimirPlantillaLetra" title="Imprimir formato de letra">
                        <i class="fa fa-file-o"></i> Plantilla
                    </button>
                </div>


            </div>

            <div class="modal fade" id="modalConfigImpresionLetra" tabindex="-1" role="dialog" aria-labelledby="modalConfigImpresionLetraLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background:#3c8dbc; color:white">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title" id="modalConfigImpresionLetraLabel">Configuración de impresión de letras</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="configLetraFondo">Fondo del formato</label>
                                <select id="configLetraFondo" class="form-control">
                                    <option value="1">Con fondo</option>
                                    <option value="0">Sin fondo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="configLetraSegunda">Segunda hoja</label>
                                <select id="configLetraSegunda" class="form-control">
                                    <option value="1">Con 2da hoja</option>
                                    <option value="0">Sin 2da hoja</option>
                                </select>
                            </div>
                            <p class="text-muted">
                                Estas opciones se aplican a todas las impresiones de letras y plantillas hasta que las cambies nuevamente.
                            </p>
                            <hr style="margin: 20px 0; border-top: 2px solid #ddd;">
                            <div class="form-group">
                                <label for="configLetraFormatoAntiguo" style="color: #f39c12;">
                                    <i class="fa fa-clock-o"></i> Formato antiguo (temporal)
                                </label>
                                <select id="configLetraFormatoAntiguo" class="form-control">
                                    <option value="0">Usar formato nuevo</option>
                                    <option value="1">Usar formato antiguo</option>
                                </select>
                                <small class="text-muted">
                                    Esta opción permite usar el formato antiguo de impresión. Es temporal y no afecta las otras configuraciones.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary btnGuardarConfigLetra">Guardar configuración</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive tablaCuentas" width="100%">

                    <thead>

                        <tr>
                            <th style="width:10px">Tipo Doc.</th>
                            <th>Nro Doc.</th>
                            <th>Cliente</th>
                            <th style="width:10px">Ven.</th>
                            <th>Fecha</th>
                            <th style="width:20px">Vencimiento</th>
                            <th>Monto</th>
                            <th>Saldo</th>
                            <th style="width:20px">Estado doc.</th>
                            <th style="width:100px">Nro. unico</th>
                            <th>Prote.</th>
                            <th>Doc. origen</th>
                            <th style="width:200px">Acciones</th>

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

<div id="modalAgregarCuenta" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 85% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar cuenta</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Documento por cobrar</b></label>
                            <label for=""><b>Tipo de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="nuevoCodigo" data-live-search="true" data-size="10" required>
                                    <option value="">Seleccionar tipo de documento</option>

                                    <?php
                                    $item = "tipo_dato";
                                    $valor = "tdoc";

                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);
                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Nro de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="nuevoDocumento" placeholder="Numero de documento" required>
                                <input type="hidden" name="ruta" value="<?php echo obtenerRutaCuentas(); ?>">

                            </div>

                        </div>


                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-7">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Notas</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-width"></i></span>

                                <input type="text" class="form-control input-lg" name="nuevaNota" placeholder="Ingresar nota">

                            </div>

                        </div>
                        <div class="col-lg-12"></div>
                        <!-- ENTRADA PARA EL CLIENTE -->

                        <div class="form-group col-lg-3">

                            <label for=""><b>Cliente</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" data-live-search="true" name="nuevoCliente" id="nuevoClienteCuenta" data-size="10" required></select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL VENDEDOR -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Vendedor</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="nuevoVendedor" data-live-search="true" data-size="10" required>
                                    <option value="">Seleccionar vendedor</option>

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $vendedor = ControladorVendedores::ctrMostrarVendedores($item, $valor);

                                    foreach ($vendedor as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                        </div>
                        <!-- ENTRADA PARA EL RENOVAR -->
                        <div class="form-group col-lg-7">
                            <div class="input-group">
                                <label for="">Renovación</label>
                                <label class="switch"> <input type="checkbox" name='renovacion' value="1"> <span class="slider round"></span></label>
                                <div style="margin-top:23px"></div>
                                <label for="">Protestado</label>
                                <label class="switch"> <input type="checkbox" name='protestado' value="1"> <span class="slider round"></span></label>
                            </div>
                        </div>

                        <div class="col-lg-12"></div>
                        <!-- ENTRADA PARA EL NOMBRE -->
                        <div class="form-group col-lg-4">
                            <label for="">Fecha</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="nuevaFecha">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL banco -->

                        <div class="form-group col-lg-4">
                            <label for="">Banco</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="nuevoBanco" data-live-search="true" data-size="10">
                                    <option value="">Seleccionar banco</option>

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $bancos = ControladorBancos::ctrMostrarBancos($item, $valor);

                                    foreach ($bancos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                        </div>
                        <!-- ENTRADA PARA EL banco -->

                        <div class="form-group col-lg-4">
                            <label for=""><b>Doc. que genero la deuda</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="nuevoTipoDocumento" data-live-search="true" data-size="10">
                                    <option value="">Seleccionar tipo de documento</option>

                                    <?php

                                    $item = "tipo_dato";
                                    $valor = "tdoc";

                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);

                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-4">
                            <label for="">Fecha de vencimiento</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="nuevaFechaVenc" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-4">
                            <label for="">Nro. unico</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-sort-numeric-asc"></i></span>

                                <input type="text" class="form-control input-lg" name="nuevoUnico" placeholder="Ingresar nro. unico">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-4">
                            <label for="">Doc. origen</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="nuevoOrigen" placeholder="Ingresar documento" required>

                            </div>

                        </div>


                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha de aceptación</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="nuevaFechaAcep">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha de envio</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="nuevaFechaEnvio">

                            </div>

                        </div>

                        <div class="form-group col-lg-3">
                            <label for="">Saldo</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="nuevoSaldo" id="nuevoSaldo" readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha Ult. pago</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="nuevaFechaUltima">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL VENDEDOR -->

                        <div class="form-group col-lg-4">
                            <label for="">Tipo de moneda</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="nuevaMoneda" id="nuevaMoneda" data-live-search="true" required>
                                    <option value="">Seleccionar moneda</option>
                                    <option value="Soles">Soles</option>
                                    <option value="Dólares">Dólares</option>
                                </select>
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha de abono</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="nuevaFechaAbono">

                            </div>

                        </div>
                        <div class="form-group col-lg-5">
                            <label for="">Estado</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-bolt"></i></span>

                                <input type="text" class="form-control input-lg" name="nuevoEstado1" id="nuevoEstado1" readonly>

                            </div>
                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-2">
                            <label for="">Monto</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="nuevoMonto" id="nuevoMonto" placeholder="Ingresar monto" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Tipo de cambio</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="nuevoTipoCambio" placeholder="Ingresar tipo de cambio">
                                <input type="hidden" name="nuevoUsuario" value="<?php echo $_SESSION["id"]; ?>">
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL VENDEDOR -->

                        <div class="form-group col-lg-3">
                            <label for="">Estado de doc.</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="nuevoEstado" id="nuevoEstado" data-live-search="true">
                                    <option value="">Seleccionar estado de documento</option>
                                    <option value="01">01-COBRANZAS</option>
                                    <option value="02">02-COBRANZAS GARANTIA</option>
                                    <option value="03">03-DESCUENTO</option>
                                </select>
                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar cuenta</button>

                </div>

            </form>


            <?php

            $crearCuenta = new ControladorCuentas();
            $crearCuenta->ctrCrearCuenta();

            ?>


        </div>

    </div>

</div>


<!--=====================================
MODAL EDITAR TIPO PAGO
======================================-->

<div id="modalEditarCuenta" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 85% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar cuenta</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Documento por cobrar</b></label>
                            <label for=""><b>Tipo de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="editarCodigo" id="editarCodigo" data-live-search="true" data-size="10" required>
                                    <option value="">Seleccionar tipo de documento</option>

                                    <?php

                                    $item = "tipo_dato";
                                    $valor = "tdoc";
                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);

                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                                <input type="hidden" id="editarUsuario" name="editarUsuario" value="<?php echo $_SESSION["id"] ?>">
                                <input type="hidden" id="idCuenta" name="idCuenta">
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Nro de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="editarDocumento" id="editarDocumento" required>
                                <input type="hidden" name="editarRuta" value="<?php echo obtenerRutaCuentas(); ?>">
                            </div>

                        </div>


                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-7">
                            <div style="margin-top:23px"></div>
                            <label for=""><b>Notas</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-width"></i></span>

                                <input type="text" class="form-control input-lg" name="editarNota" id="editarNota">

                            </div>

                        </div>
                        <div class="col-lg-12"></div>
                        <!-- ENTRADA PARA EL CLIENTE -->

                        <div class="form-group col-lg-3">

                            <label for=""><b>Cliente</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" data-live-search="true" name="editarCliente" id="editarCliente" data-size="10" required></select>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL VENDEDOR -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Vendedor</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="editarVendedor" id="editarVendedor" data-live-search="true" data-size="10" required>
                                    <option value="">Seleccionar vendedor</option>

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $vendedor = ControladorVendedores::ctrMostrarVendedores($item, $valor);

                                    foreach ($vendedor as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">'  . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                        </div>
                        <!-- ENTRADA PARA EL RENOVAR -->
                        <div class="form-group col-lg-7">
                            <div class="input-group">
                                <label for="">Renovación</label>
                                <label class="switch"> <input type="checkbox" name='editarRenovacion' id="editarRenovacion" value="1"> <span class="slider round"></span></label>
                                <div style="margin-top:23px"></div>
                                <label for="">Protestado</label>
                                <label class="switch"> <input type="checkbox" name='editarProtestado' id="editarProtestado" value="1"> <span class="slider round"></span></label>
                            </div>
                        </div>

                        <div class="col-lg-12"></div>
                        <!-- ENTRADA PARA EL NOMBRE -->
                        <div class="form-group col-lg-4">
                            <label for="">Fecha</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="editarFecha" id="editarFecha">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL banco -->

                        <div class="form-group col-lg-4">
                            <label for="">Banco</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="editarBanco" id="editarBanco" data-live-search="true" data-size="10">
                                    <option value="">Seleccionar banco</option>

                                    <?php
                                    $item = null;
                                    $valor = null;

                                    $bancos = ControladorBancos::ctrMostrarBancos($item, $valor);

                                    foreach ($bancos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                        </div>
                        <!-- ENTRADA PARA EL banco -->

                        <div class="form-group col-lg-4">
                            <label for=""><b>Doc. que genero la deuda</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="editarTipoDocumento" id="editarTipoDocumento" data-live-search="true" data-size="10">
                                    <option value="">Seleccionar tipo de documento</option>

                                    <?php
                                    $item = "tipo_dato";
                                    $valor = "tdoc";

                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);

                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }

                                    ?>
                                </select>
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-4">
                            <label for="">Fecha de vencimiento</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="editarFechaVenc" id="editarFechaVenc">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-4">
                            <label for="">Nro. unico</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-sort-numeric-asc"></i></span>

                                <input type="text" class="form-control input-lg" name="editarUnico" id="editarUnico">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-4">
                            <label for="">Doc. origen</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="editarOrigen" id="editarOrigen">

                            </div>

                        </div>


                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha de aceptación</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="editarFechaAcep" id="editarFechaAcep">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha de envio</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="editarFechaEnvio" id="editarFechaEnvio">

                            </div>

                        </div>

                        <div class="form-group col-lg-3">
                            <label for="">Saldo</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="editarSaldo" id="editarSaldo" readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha Ult. pago</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="editarFechaUltima" id="editarFechaUltima">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL VENDEDOR -->

                        <div class="form-group col-lg-4">
                            <label for="">Tipo de moneda</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="editarMoneda" id="editarMoneda" data-live-search="true">
                                    <option value="">Seleccionar moneda</option>
                                    <option value="Soles">Soles</option>
                                    <option value="Dólares">Dólares</option>
                                </select>
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Fecha de abono</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="editarFechaAbono" id="editarFechaAbono">

                            </div>

                        </div>
                        <div class="form-group col-lg-5">
                            <label for="">Estado</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-bolt"></i></span>

                                <input type="text" class="form-control input-lg" name="editarEstado1" id="editarEstado1" readonly>

                            </div>
                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-2">
                            <label for="">Monto</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="editarMonto" id="editarMonto" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Tipo de cambio</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="editarTipoCambio" id="editarTipoCambio">

                            </div>

                        </div>

                        <!-- ENTRADA PARA EL VENDEDOR -->

                        <div class="form-group col-lg-3">
                            <label for="">Estado de doc.</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <select type="text" class="form-control input-lg selectpicker" name="editarEstado" id="editarEstado" data-live-search="true">
                                    <option value="">Seleccionar estado de documento</option>
                                    <option value="01">01-COBRANZAS</option>
                                    <option value="02">02-COBRANZAS GARANTIA</option>
                                    <option value="03">03-DESCUENTO</option>
                                </select>
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

            $editarCuenta = new ControladorCuentas();
            $editarCuenta->ctrEditarCuenta();

            ?>


        </div>

    </div>

</div>

<div id="modalAgregarLetras" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 55% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Agregar Letras</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Tipo de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                <input type="text" class="form-control input-lg" name="letraCodigo" id="letraCodigo" readonly>

                                <input type="hidden" id="letraUsuario" name="letraUsuario" value="<?php echo $_SESSION["id"] ?>">
                                <input type="hidden" id="idCuenta3" name="idCuenta3">
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-4">
                            <label for=""><b>Nro de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="letraDocumento" id="letraDocumento" readonly>

                            </div>

                        </div>


                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-offset-2 col-lg-3 ">
                            <label for=""><b>Fecha</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-text-width"></i></span>

                                <input type="text" class="form-control input-lg" name="letraFecha" id="letraFecha" readonly>

                            </div>

                        </div>
                        <div class="col-lg-12"></div>
                        <!-- ENTRADA PARA EL CLIENTE -->

                        <div class="form-group col-lg-2">

                            <label for=""><b>Cliente</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <input type="text" class="form-control input-lg" name="letraCli" id="letraCli" readonly>
                            </div>

                        </div>

                        <div class="form-group col-lg-6">
                            <label for="">Nombres</label>
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                <input type="text" class="form-control input-lg" name="letraCliente" id="letraCliente" readonly>
                            </div>

                        </div>

                        <!-- ENTRADA PARA EL VENDEDOR -->

                        <div class="form-group  col-lg-3">
                            <label for=""><b>Vendedor</b></label>
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                <input type="text" class="form-control input-lg" name="letraVendedor" id="letraVendedor" readonly>
                                <input type="hidden" class="form-control input-lg" name="letraMoneda" id="letraMoneda">
                            </div>

                        </div>
                        <div class="col-lg-12"></div>


                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for="">Monto</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="letraMonto" id="letraMonto" readonly>

                            </div>

                        </div>

                        <div class="form-group col-lg-3 col-lg-offset-5">
                            <label for="">Saldo</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="letraSaldo" id="letraSaldo" readonly>

                            </div>

                        </div>
                        <div class="col-lg-8"></div>
                        <div class="col-lg-12"></div>

                        <div class="col-lg-3">
                            <div class="input-group">
                                <span class="input-group-addon"><b>N° letras</b></span>

                                <input type="number" min="0" class="form-control input-lg" name="nroLetra" id="nroLetra" required>

                            </div>

                        </div>

                        <div class="col-lg-3">
                            <div class="input-group">
                                <span class="input-group-addon"><b>Vencen cada</b></span>

                                <input type="number" min="0" class="form-control input-lg" name="sumaFecha" id="sumaFecha" required>

                            </div>

                        </div>

                        <div class="col-lg-2">
                            <input type="number" min="0" class="form-control input-lg" name="sumaIntervalo" id="sumaIntervalo" required>
                        </div>

                        <div class="col-lg-4">
                            <div style="margin-top:5px"></div>
                            <button type="button" class="btn btn-primary btnGenerarLetra"><i class="fa fa-refresh"></i> Generar</button>
                            <button type="button" class="btn btn-danger btnLimpiarLetra"><i class="fa fa-trash"></i> Limpiar</button>
                        </div>

                        <div class="col-lg-12">
                            <!--=====================================
                    TITULOS
            ======================================-->
                            <br>
                            <div class="box box-primary">

                                <div class="col-lg-3">

                                    <label>Vencimiento</label>

                                </div>

                                <div class="col-lg-6">

                                    <label for="">Observaciones</label>

                                </div>

                                <div class="col-lg-2">

                                    <label for="">Monto</label>

                                </div>

                            </div>
                        </div>
                        <div class="listaLetras"></div>
                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Guardar letras</button>

                </div>

            </form>

            <?php

            $agregarLetra = new ControladorCuentas();
            $agregarLetra->ctrAgregarLetra();

            ?>


        </div>

    </div>

</div>


<!--=====================================
MODAL IMPORTAR CUENTAS DE BANCO
======================================-->

<div id="modalImportarBanco" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Cancelar letras</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group">
                            <label for="">
                                <h3>Archivo de banco para cancelar letras</h3>
                            </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <input type="file" min="0" class="form-control input-lg" name="nuevaImportacion" id="nuevaImportacion" required>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary" name="importBanco">Cancelar letras</button>

                </div>

            </form>


            <?php

            $importarBanco = new ControladorCuentas();
            $importarBanco->ctrImportarCuenta();

            ?>


        </div>

    </div>

</div>

<!--=====================================
MODAL IMPORTAR CUENTAS DE BANCO
======================================-->

<div id="modalActualizarUnico" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Actualizar número único</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group">
                            <label for="">
                                <h3>Archivo de banco con números únicos</h3>
                            </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <input type="file" min="0" class="form-control input-lg" name="nuevaUnico" id="nuevaUnico" required>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary" name="importLetra">Actualizar números únicos</button>

                </div>

            </form>


            <?php

            $importarLetra = new ControladorCuentas();
            $importarLetra->ctrImportarLetra();

            ?>


        </div>

    </div>

</div>

<!--=====================================
MODAL DIVIDIR LETRA
======================================-->

<div id="modalDividirLetra" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 85% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Dividir letra</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">


                        <!-- ENTRADA PARA EL CODIGO -->

                        <div class="form-group col-lg-3">
                            <label for=""><b>Tipo de documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <input type="text" class="form-control input-lg " name="dividirDocumento" id="dividirDocumento" data-live-search="true" readonly>

                                <input type="hidden" id="dividirUsuario" name="dividirUsuario" value="<?php echo $_SESSION["id"] ?>">
                                <input type="hidden" id="idCuenta4" name="idCuenta4">
                            </div>

                        </div>
                        <!-- ENTRADA PARA LA FECHA  -->
                        <div class="form-group col-lg-offset-6 col-lg-3">
                            <label for="">Fecha </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="dividirFecha" id="dividirFecha" readonly>

                            </div>

                        </div>
                        <!-- ENTRADA PARA EL NOMBRE -->

                        <div class="form-group col-lg-3">
                            <label for=""><b>Nro. Documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="dividirNroDocumento" id="dividirNroDocumento" readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA FECHA  -->
                        <div class="form-group col-lg-offset-6 col-lg-3">
                            <label for="">Fecha Vencimiento</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="dividirFechaVencimiento" id="dividirFechaVencimiento" readonly>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA NOTA -->

                        <div class="form-group col-lg-2">
                            <label for=""><b>Clientes</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <input type="text" class="form-control input-lg" name="dividirCliente" id="dividirCliente" readonly>
                                <input type="hidden" name="dividirVendedor" id="dividirVendedor">

                                <input type="hidden" name="dividirNumUnico" id="dividirNumUnico">
                                <input type="hidden" name="dividirFechaCep" id="dividirFechaCep">


                            </div>

                        </div>
                        <div class="form-group col-lg-4">
                            <br>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-user"></i></span>

                                <input type="text" class="form-control input-lg" name="dividirNomCliente" id="dividirNomCliente" readonly>


                            </div>

                        </div>


                        <div class="form-group col-lg-offset-3 col-lg-3">
                            <label for="">Saldo </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-usd"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="dividirSaldo" id="dividirSaldo" readonly>

                            </div>

                        </div>

                        <div class="box  box-primary  col-lg-12">
                            <label for="">Nuevo Documento</label>
                        </div>
                        <div class="form-group col-lg-3">
                            <label for=""><b>Nro. Documento</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="text" class="form-control input-lg" name="dividirNroDocumento2" id="dividirNroDocumento2" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA FECHA  -->
                        <div class="form-group col-lg-offset-6 col-lg-3">
                            <label for="">Fecha </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="dividirFecha2" id="dividirFecha2" required>

                            </div>

                        </div>

                        <div class="form-group col-lg-3">
                            <label for=""><b>Monto S/.</b></label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-credit-card"></i></span>

                                <input type="number" min="0" step="any" class="form-control input-lg" name="dividirMonto" id="dividirMonto" value="0" required>

                            </div>

                        </div>

                        <!-- ENTRADA PARA LA FECHA  -->
                        <div class="form-group col-lg-offset-6 col-lg-3">
                            <label for="">Fecha Vencimiento</label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                <input type="date" class="form-control input-lg" name="dividirFechaVencimiento2" id="dividirFechaVencimiento2" required>

                            </div>

                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Dividir letra</button>

                </div>

            </form>

            <?php
            $dividirLetra = new ControladorCuentas();
            $dividirLetra->ctrDividirLetra();
            ?>


        </div>

    </div>

</div>


<!--=====================================
MODAL IMPORTAR CUENTAS DE BANCO
======================================-->

<div id="modalFechasCredipagos" class="modal fade" role="dialog">

    <div class="modal-dialog">

        <div class="modal-content">

            <form role="form" method="post" enctype="multipart/form-data">

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Credipagos</h4>

                </div>

                <div class="modal-body">

                    <div class="box-body">

                        <!-- ENTRADA PARA EL CODIGO -->

                        <?php

                        $control = ControladorCuentas::ctrControlFechas();
                        //var_dump($control);

                        ?>

                        <div class="form-group">
                            <label for="">
                                <h3>Desde:</h3>
                            </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <input type="date" min="<?php echo $control["inicio"] ?>" max="<?php echo $control["fin"] ?>" class="form-control input-lg" name="fechaInicio" id="fechaInicio" required>

                                <input type="hidden" name="iniF" id="iniF" value="<?php echo $control["inicio"] ?>">
                                <input type="hidden" name="finF" id="finF" value="<?php echo $control["fin"] ?>">

                            </div>

                        </div>

                        <div class=" form-group">
                            <label for="">
                                <h3>Hasta:</h3>
                            </label>
                            <div class="input-group">

                                <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                <input type="date" min="<?php echo $control["inicio"] ?>" max="<?php echo $control["fin"] ?>" class="form-control input-lg" name="fechaFin" id="fechaFin" required>

                            </div>

                        </div>

                        <div class="form-group col-lg-6" style="padding-top:15px">
                            <div class="form-check">
                                <input class="form-check-inputCta" type="checkbox" value="0" id="ctaNota" name="cta" unchecked>
                                <label class="form-check-labelCta" for="ctaNota">
                                    Agregar Notas al reporte
                                </label>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="button" id="generarCredipagos" name="generarCredipagos" class="btn btn-primary btnReporteCrediPagos">GENERAR</button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php if (function_exists("usuarioPuedeVerModulo") && usuarioPuedeVerModulo("gestion_comercial", "estado_cuenta_gerencia")) : ?>
<div class="modal fade" id="modalClasificacionMorosidad" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc; color:white">
                <button type="button" class="close" data-dismiss="modal" style="color:white">&times;</button>
                <h4 class="modal-title"><i class="fa fa-file-excel-o"></i> Clasificación de morosidad</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Excel con todos los clientes según cómo pagan (últimos 2 años, 8 días de gracia).
                    Hojas <strong>Resumen</strong>, <strong>Clientes</strong> y <strong>Metadatos</strong>.
                    Debe indicar quién solicita el archivo.
                </p>
                <div class="form-group">
                    <label for="cmSolicitadoPor">Solicitado por <span class="text-danger">*</span></label>
                    <select class="form-control selectpicker" id="cmSolicitadoPor" data-live-search="true" data-size="8" required>
                        <option value="">Seleccione responsable…</option>
                        <?php foreach ($usuariosActivosCxc as $usuarioCxc) : ?>
                            <option value="<?php echo (int) $usuarioCxc["id"]; ?>"
                                <?php echo ((int) $usuarioCxc["id"] === $idUsuarioSesionCxc) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuarioCxc["nombre"], ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <form id="cmFormExport" method="GET" action="vistas/reportes_excel/rpt_clasificacion_morosidad.php" target="_blank" style="display:inline;">
                    <input type="hidden" name="solicitud_por" id="cmSolicitadoPorHidden" value="">
                    <button type="submit" class="btn btn-success" id="btnCmConfirmarExport">
                        <i class="fa fa-download"></i> Descargar Excel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php

$eliminarCuenta = new ControladorCuentas();
$eliminarCuenta->ctrEliminarCuenta();

?>

<script src="vistas/modulos/cuentas-corrientes/cuentas.js"></script>
<script>
    window.document.title = "Cuentas";
    // Configuración de ruta según TIPO_PAGINACION_CUENTAS
    window.RUTA_CUENTAS = "<?php echo obtenerRutaCuentas(); ?>";
</script>