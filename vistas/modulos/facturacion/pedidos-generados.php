<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Pedidos Generados

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Pedidos Generados</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">

                <div class="btn-group pull-left">

                    <?php

                    #$numero = ControladorMovimientos::ctrMostrarTalonario();

                    $pedido = "";
                    #$pedido = $numero["pedido"] + 1;
                    #var_dump("pedido", $pedido);

                    echo '<button class="btn btn-primary  btnCrearPedido" pedido="' . $pedido . '" title="Crear Pedido">

                  Crear Pedido

                </button>';


                    ?>

                </div>

                <div class="btn-group pull-right" style="margin: 13px;">

                    <button class="btn btn-success  btnFacturados" title="Ver Pedidos FACTURADOS">

                        FACTURADOS

                    </button>

                </div>

                <div class="btn-group pull-right" style="margin: 13px;">

                    <button class="btn btn-info  btnConfirmados" title="Ver Pedidos CONFIRMADOS">

                        CONFIRMADOS

                    </button>

                </div>

                <div class="btn-group pull-right" style="margin: 13px;">

                    <button class="btn btn-default  btnAPT" title="Ver Pedidos EN APT">

                        EN APT

                    </button>

                </div>

                <div class="btn-group pull-right" style="margin: 13px;">

                    <button class="btn btn-warning  btnAprobados" title="Ver Pedidos APROBADOS">

                        APROBADOS

                    </button>

                </div>

                <div class="btn-group pull-right" style="margin: 13px;">

                    <button class="btn btn-basic  btnGenerados" title="Ver Pedidos Generados">

                        GENERADOS

                    </button>

                </div>

                <div class="btn-group pull-right" style="margin: 13px;">

                    <button class='btn btnInicioPed' style='background-color:DarkSlateGray' title='inicio'>
                        <i style='color:white' class='fa fa-home'></i>
                    </button>

                </div>

            </div>

            <div class="box-body">

                <table class="table table-bordered table-striped dt-responsive tablaPedidosGenerados" width="100%">

                    <thead>

                        <tr>
                            <th>Id</th>
                            <th>Código</th>
                            <th>Cod. Cliente</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Total</th>
                            <th>Condición de Venta</th>
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
    <div class="modal-dialog mf-dialog">
        <div class="modal-content mf-content">
            <form role="form" method="post" onsubmit="return checkSubmit();">
                <div class="modal-header mf-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Pasar pedido a documento</h4>
                </div>
                <div class="modal-body mf-body">
                    <section class="mf-section">
                        <h5 class="mf-section-title">Pedido</h5>
                        <div class="mf-grid">
                            <div class="mf-field mf-col-code">
                                <label for="codPedido">Código</label>
                                <input type="text" class="form-control input-sm" id="codPedido" name="codPedido" readonly>
                            </div>
                            <div class="mf-field mf-col-client">
                                <label for="nomCli">Cliente</label>
                                <input type="text" class="form-control input-sm" id="nomCli" name="nomCli" readonly>
                            </div>
                            <div class="mf-field mf-col-4">
                                <label for="codCli">Cód. cliente</label>
                                <input type="text" class="form-control input-sm" id="codCli" name="codCli" readonly>
                            </div>
                            <div class="mf-field mf-col-8">
                                <label>Documento del cliente</label>
                                <div class="mf-doc-row">
                                    <input type="text" class="form-control input-sm" id="tipDoc" name="tipDoc" readonly title="Tipo de documento">
                                    <input type="text" class="form-control input-sm" id="nroDoc" name="nroDoc" readonly title="Número de documento">
                                </div>
                                <input type="hidden" name="dscto" id="dscto" value="0">
                                <input type="hidden" name="formapago" id="formapago">
                                <input type="hidden" name="codVen" id="codVen">
                                <input type="hidden" name="idUsuario" id="idUsuario" value="<?php echo $_SESSION["id"]; ?>">
                            </div>
                        </div>
                    </section>

                    <section class="mf-section mf-destino">
                        <h5 class="mf-section-title">Documento a generar</h5>
                        <div class="mf-grid">
                            <div class="mf-field mf-col-6">
                                <label for="tdoc">Tipo</label>
                                <select class="form-control input-sm selectpicker" name="tdoc" id="tdoc" data-live-search="true" data-container="body" data-size="8" required>
                                    <option value="">Seleccionar tipo</option>
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
                            <div class="mf-field mf-col-6">
                                <label for="serie">Serie</label>
                                <select class="form-control input-sm selectpicker" name="serie" id="serie" data-live-search="true" data-container="body" data-size="8" title="Seleccionar serie" required>
                                    <option value="">Seleccionar serie</option>
                                </select>
                                <p class="mf-preview" id="seriePreview">El número final se asigna al generar.</p>
                            </div>
                        </div>

                        <div class="mf-separar hidden" id="wrapSepararDoc">
                            <div class="mf-separar-row">
                                <div class="mf-separar-ops">
                                    <span>También generar</span>
                                    <div class="mf-separar-checks">
                                        <label class="mf-check">
                                            <input class="chkFactura" type="checkbox" id="chkFactura" name="chkFactura" disabled>
                                            Factura
                                        </label>
                                        <label class="mf-check">
                                            <input class="chkBoleta" type="checkbox" id="chkBoleta" name="chkBoleta" disabled>
                                            Boleta
                                        </label>
                                    </div>
                                </div>
                                <div class="mf-field mf-separar-serie hidden" id="wrapSerieSeparado">
                                    <label for="serieSeparado">Serie</label>
                                    <select class="form-control input-sm selectpicker" name="serieSeparado" id="serieSeparado" data-live-search="true" data-container="body" data-size="8" title="Seleccionar serie" disabled>
                                        <option value="">Seleccionar serie</option>
                                    </select>
                                    <p class="mf-preview" id="serieSeparadoPreview"></p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="mf-section" id="wrapOrdenCompra"<?php echo (function_exists('feCsvOrdenCompraActiva') && !feCsvOrdenCompraActiva()) ? ' style="display:none"' : ''; ?>>
                        <div class="mf-grid">
                            <div class="mf-field mf-col-6">
                                <label for="orden_compra">Orden de compra <span style="font-weight:normal;color:#888;">(opcional)</span></label>
                                <input type="text" class="form-control input-sm" id="orden_compra" name="orden_compra" maxlength="20" placeholder="Ej. OC12345 (sin espacios ni guiones)" autocomplete="off"<?php echo (function_exists('feCsvOrdenCompraActiva') && !feCsvOrdenCompraActiva()) ? ' disabled' : ''; ?>>
                            </div>
                        </div>
                    </section>

                    <section class="mf-section mf-wrap-guia hidden" id="GuiasDiv">
                        <h5 class="mf-section-title">Guía de remisión</h5>
                        <div class="mf-grid">
                            <div class="mf-field mf-col-4">
                                <label for="chofer">Chofer</label>
                                <select class="form-control input-sm" name="chofer" id="chofer">
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $valor = "tcho";
                                    $documentos = ModeloPedidos::MostrarDatos($valor);
                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . " - " . $value["Des_Larga"] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mf-field mf-col-4">
                                <label for="carro">Movilidad</label>
                                <select class="form-control input-sm" name="carro" id="carro">
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $valor = "tcar";
                                    $documentos = ModeloPedidos::MostrarDatos($valor);
                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . " - " . $value["Des_Larga"] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mf-field mf-col-2">
                                <label for="peso">Peso kg</label>
                                <input type="text" class="form-control input-sm" id="peso" name="peso" placeholder="0.00">
                            </div>
                            <div class="mf-field mf-col-2">
                                <label for="bultos">Bultos</label>
                                <input type="text" class="form-control input-sm" id="bultos" name="bultos" placeholder="0">
                            </div>
                        </div>
                    </section>
                </div>
                <div class="modal-footer mf-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" id="btnGenerarDoc" class="btn btn-primary">Generar documento</button>
                </div>
            </form>
            <?php
            $facturar = new controladorFacturacion();
            $facturar->ctrFacturarN();
            ?>
        </div>
    </div>
</div>

<?php

$anularPedido = new ControladorPedidos();
$anularPedido->ctrAnularPedido();

?>

<script>
    window.document.title = "Pedidos"
</script>