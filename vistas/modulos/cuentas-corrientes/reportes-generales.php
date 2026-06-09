<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Administrar agencias

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar agencias</li>

        </ol>

    </section>

    <section class="content col-lg-2">
        <label for="">Tipo consulta / reporte</label>
        <div class="box">
            <div class="box-body">
                <form method="post">
                    <div class="form-check">
                        <label class="form-check-label" for="radio1">
                            <input type="radio" class="form-check-input optradio" id="radio1" name="optradio" value="pendiente" checked>Doc. por cobrar
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio2">
                            <input type="radio" class="form-check-input optradio" id="radio2" name="optradio" value="pendienteVencidoMenor">Doc. vencidos
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio3">
                            <input type="radio" class="form-check-input optradio" id="radio3" name="optradio" value="pendienteVencidoMayor">Doc. no vencidos
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio4">
                            <input type="radio" class="form-check-input optradio" id="radio4" name="optradio" value="protestado">Doc. protestados
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio5">
                            <input type="radio" class="form-check-input optradio" id="radio5" name="optradio" value="option5">Letras por imprimir
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio6">
                            <input type="radio" class="form-check-input optradio" id="radio6" name="optradio" value="estadoEnvioVacio">Letras por aceptar
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio7">
                            <input type="radio" class="form-check-input optradio" id="radio7" name="optradio" value="unicoCartera">Letras en cartera
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio8">
                            <input type="radio" class="form-check-input optradio" id="radio8" name="optradio" value="option8">Doc. por banco/estado
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio9">
                            <input type="radio" class="form-check-input optradio" id="radio9" name="optradio" value="option9">Doc. por estado/banco
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio10">
                            <input type="radio" class="form-check-input optradio" id="radio10" name="optradio" value="cancelado">Doc. Cancelados
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio11">
                            <input type="radio" class="form-check-input optradio" id="radio11" name="optradio" value="option11">Movimientos en Ctas.ctes.
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio12">
                            <input type="radio" class="form-check-input optradio" id="radio12" name="optradio" value="fechaSaldo">Saldos a una fecha
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio13">
                            <input type="radio" class="form-check-input optradio" id="radio13" name="optradio" value="pagos">Pagos
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio14">
                            <input type="radio" class="form-check-input optradio" id="radio14" name="optradio" value="fechaActualSaldo">Estado de cuenta
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio15">
                            <input type="radio" class="form-check-input optradio" id="radio15" name="optradio" value="option15">Rsm saldos a una fecha (S/)
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label" for="radio16">
                            <input type="radio" class="form-check-input optradio" id="radio16" name="optradio" value="option16" disabled>Pagos-comisiones
                        </label>
                    </div>
                </form>
            </div>

        </div>

        <label for="">Ordenado(Primario)</label>
        <div class="box">
            <div class="box-body">
                <div class="form-check">
                    <label for="radioOrd1">
                        <input type="radio" class="form-check-input radioOrd1 " id="radioOrd1" name="radioOrd1" value="tipo" checked>Por Tipo/Numero
                    </label>
                </div>

                <div class="form-check">
                    <label for="radioOrd2">
                        <input type="radio" class="form-check-input radioOrd1" id="radioOrd2" name="radioOrd1" value="cliente">Por Cliente
                    </label>
                </div>

                <div class="form-check">
                    <label for="radioOrd3">
                        <input type="radio" class="form-check-input radioOrd1" id="radioOrd3" name="radioOrd1" value="vendedor">Por Vendedor
                    </label>
                </div>

                <div class="form-check">
                    <label for="radioOrd4">
                        <input type="radio" class="form-check-input radioOrd1" id="radioOrd4" name="radioOrd1" value="fecha_ven">Por Fch. vencimiento
                    </label>
                    <div class="form-check">
                        <label for="radioOrd5">
                            <input type="radio" class="form-check-input radioOrd1" id="radioOrd5" name="radioOrd1" value="fecha_pag">Por Fch. Pago
                        </label>
                    </div>
                </div>
            </div>

        </div>

    </section>

    <section class="content col-lg-2">
        <label for="">Ordenado(Secundario)</label>
        <div class="box">
            <div class="box-body">
                <div class="form-check">
                    <label class="form-check-label" for="radioOrd21">
                        <input type="radio" class="form-check-input radioOrd2" id="radioOrd21" name="radioOrd2" value="ordNumCuenta" checked>Por Tipo/Numero
                    </label>
                </div>
                <div class="form-check">
                    <label class="form-check-label" for="radioOrd22">
                        <input type="radio" class="form-check-input radioOrd2" id="radioOrd22" name="radioOrd2" value="ordVencimiento">Por fecha de vencimiento
                    </label>
                </div>
                <div class="form-check">
                    <label class="form-check-label" for="radioOrd23">
                        <input type="radio" class="form-check-input radioOrd2" id="radioOrd23" name="radioOrd2" value="ordCliente">Por cliente
                    </label>
                </div>
            </div>

        </div>
        <div class="campoDocumento ">
            <label for="">Tipo documento</label>
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <select class="form-control input-lg selectpicker" id="tipoDocumentoReporte" name="tipoDocumentoReporte" data-live-search="true" data-size="10">
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
            </div>
        </div>
        <div class="campoCancelacion hidden">
            <label for="">Tipo cancelacion</label>
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <select class="form-control input-lg selectpicker" id="tipoCancelacionReporte" name="tipoCancelacionReporte" data-live-search="true">
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
                    </div>
                </div>
            </div>

        </div>

        <div class="campoCliente hidden">
            <label for="">Clientes</label>
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <select class="form-control input-lg selectpicker" id="tipoClienteReporte" name="tipoClienteReporte" data-live-search="true" data-size="10">
                            <option value="">Seleccionar clientes</option>
                            <?php

                            $clientes = ControladorClientes::ctrMostrarClientes(null, null);
                            foreach ($clientes as $key => $value) {
                                echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["nombre"] . '</option>';
                            }

                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="campoVendedor hidden">
            <label for="">Vendedores</label>
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <select class="form-control input-lg selectpicker" id="tipoVendedorReporte" name="tipoVendedorReporte" data-live-search="true" data-size="10">
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
            </div>
        </div>

        <label for="">Bancos</label>
        <div class="box">
            <div class="box-body">
                <div class="form-group">
                    <select class="form-control input-lg selectpicker" id="tipoBancoReporte" name="tipoBancoReporte" data-live-search="true" data-size="10">
                        <option value="">Seleccionar bancos</option>
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
        </div>
    </section>

    <section class="content col-lg-2">
        <label for="">Fechas</label>
        <div class="box">
            <div class="box-body">
                <div class="form-check">
                    <label class="form-check-label" for="">
                        Inicio: <input type="date" class="form-check-input" id="fechaCuentaInicio" name="fechaCuentaInicio" disabled>
                    </label>
                </div>
                <div class="form-check">
                    <label class="form-check-label" for="radioOr2">
                        Fin: <input type="date" class="form-check-input" id="fechaCuentaFin" name="fechaCuentaFin" disabled>
                    </label>
                </div>
            </div>

        </div>

        <div class="box">
            <div class="box-body">
                <div class="form-check">
                    <label class="form-check-label" for="radioImpresion1">
                        <input type="radio" class="form-check-input radioImpresion" id="radioImpresion1" name="radioImpresion" value="pantalla" checked>Por pantalla
                    </label>
                </div>


                <div class="form-check">
                    <label class="form-check-label" for="radioImpresion2">
                        <input type="radio" class="form-check-input radioImpresion" id="radioImpresion2" name="radioImpresion" value="excel">Por archivo CSV (Excel)
                    </label>
                </div>
            </div>

        </div>
        <div align="center">
            <button class="btn btn-success btnGenerarReporteCuenta" consulta="pendiente" orden1="tipo" orden2="ordNumCuenta" tip_doc="" cli="" vend="" banco="" canc="todo" inicio="" fin="" impresion="pantalla"><i class="fa fa-check"></i> Aceptar</button>

        </div>


    </section>

    <section class="content col-lg-6 text-center">
        <div class="box">
            <div class="box-body">
                <div class="form-group" style="border:3px solid darkred">
                    <img src="vistas/img/plantilla/jackyform_paloma.png" width="600px" height="400px">
                </div>
            </div>
        </div>
    </section>

</div>


<script>
    window.document.title = "Reportes generales"
</script>