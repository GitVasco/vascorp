<div class="content-wrapper">

    <section class="content-header">

        <h1>

            Pedidos General

        </h1>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Pedidos General</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border pedidos-cv-listado-header">

                <div class="pedidos-cv-header-toolbar">

                    <div class="pedidos-cv-acciones-principales" role="toolbar" aria-label="Acciones de pedidos">
                        <?php
                        $pedido = "";
                        echo '<button type="button" class="btn btn-primary btnCrearPedido pedidos-cv-btn-accion" pedido="' . $pedido . '" title="Abrir formulario para armar un pedido nuevo">
                            <i class="fa fa-plus"></i> Crear pedido
                        </button>';
                        ?>
                        <a href="escaneo-barcode-pedidocv" class="btn btn-default pedidos-cv-btn-accion pedidos-cv-btn-barcode" title="Alta rápida escaneando códigos de barras">
                            <i class="fa fa-barcode"></i> Código de barras
                        </a>
                        <?php
                        if (
                            $_SESSION["id"] == "6" ||
                            $_SESSION["id"] == "53" ||
                            $_SESSION["id"] == "54" ||
                            $_SESSION["id"] == "55" ||
                            $_SESSION["id"] == "74"
                        ) {
                            echo '<button type="button" class="btn btn-success btnEnviarPedido pedidos-cv-btn-accion pedidos-cv-solo-desktop" data-toggle="modal" data-target="#modalEnviarPedido" title="Enviar pedidos al siguiente estado">
                                <i class="fa fa-paper-plane"></i> Enviar pedidos
                            </button>';
                        }
                        ?>
                    </div>

                    <?php
                    if (
                        $_SESSION["id"] == "6" ||
                        $_SESSION["id"] == "53" ||
                        $_SESSION["id"] == "54" ||
                        $_SESSION["id"] == "55" ||
                        $_SESSION["id"] == "74"
                    ) {
                        echo '<div class="pedidos-cv-import-inline pedidos-cv-solo-desktop">
                            <form role="form" class="pedidos-cv-import-form" method="POST" enctype="multipart/form-data">
                                <span class="pedidos-cv-import-hint"><i class="fa fa-file-text-o"></i> Importar .txt</span>
                                <div class="input-group input-group-sm pedidos-cv-import-input">
                                    <input type="file" name="archivoPedTxt" id="archivoPedTxt" class="form-control" accept="text/plain" title="Seleccionar archivo de texto">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-info pedidos-cv-btn-accion" name="importPedTxt" title="Cargar archivo">
                                            <i class="fa fa-upload"></i> Subir
                                        </button>
                                    </span>
                                </div>
                            </form>';

                        $activar = new ControladorPedidos();
                        $activar->ctrLeerPedido();

                        echo '</div>';
                    }
                    ?>

                <nav class="pedidos-cv-estados-nav" aria-label="Filtrar por estado">
                    <button type="button" class="btn btn-default pedidos-cv-filtro-estado btnInicioPed" title="Pedidos General — todos los estados">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        <span class="pedidos-cv-filtro-label">Inicio</span>
                    </button>
                    <button type="button" class="btn btn-default pedidos-cv-filtro-estado btnGenerados" title="Ver pedidos generados">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                        <span class="pedidos-cv-filtro-label">Generados</span>
                    </button>
                    <button type="button" class="btn btn-warning pedidos-cv-filtro-estado btnAprobados" title="Ver pedidos aprobados">
                        <i class="fa fa-check-circle" aria-hidden="true"></i>
                        <span class="pedidos-cv-filtro-label">Aprobados</span>
                    </button>
                    <button type="button" class="btn btn-default pedidos-cv-filtro-estado btnAPT" title="Ver pedidos en APT">
                        <i class="fa fa-cubes" aria-hidden="true"></i>
                        <span class="pedidos-cv-filtro-label">En APT</span>
                    </button>
                    <button type="button" class="btn btn-info pedidos-cv-filtro-estado btnConfirmados" title="Ver pedidos confirmados">
                        <i class="fa fa-flag-checkered" aria-hidden="true"></i>
                        <span class="pedidos-cv-filtro-label">Confirmados</span>
                    </button>
                    <button type="button" class="btn btn-success pedidos-cv-filtro-estado btnFacturados" title="Ver pedidos facturados">
                        <i class="fa fa-money" aria-hidden="true"></i>
                        <span class="pedidos-cv-filtro-label">Facturados</span>
                    </button>
                </nav>

                </div><!-- .pedidos-cv-header-toolbar -->

            </div>

            <div class="box-body pedidos-cv-tabla-wrap">

                <div class="pedidos-cv-tabla-scroll">

                <table class="table table-bordered table-striped tablaPedidosCV" width="100%">

                    <thead>
                        <tr>
                            <th title="Código del pedido">Pedido</th>
                            <th title="Código de cliente">Cli.</th>
                            <th title="Nombre del cliente">Cliente</th>
                            <th title="Código de vendedor">Vend.</th>
                            <th class="text-right" title="Total del pedido">Total</th>
                            <th title="Condición de venta">Condición</th>
                            <th title="Estado actual">Estado</th>
                            <th title="Usuario que registró">Usuario</th>
                            <th title="Fecha del pedido">Fecha</th>
                            <th title="Fecha de aprobación">F.aprob.</th>
                            <th title="Acciones disponibles">Acciones</th>
                        </tr>
                    </thead>

                </table>

                </div><!-- .pedidos-cv-tabla-scroll -->

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


                    <section class="mf-section mf-wrap-nc hidden" id="wrapNotaCredito">
                        <h5 class="mf-section-title">Documento origen (nota de crédito)</h5>
                        <div class="mf-grid">
                            <div class="mf-field campoTipOrigen">
                                <label for="tdocorigen">Tipo doc. origen</label>
                                <select class="form-control input-sm selectpicker" name="tdocorigen" id="tdocorigen" data-live-search="true" data-container="body" data-size="8" title="Seleccionar tipo">
                                    <option value="">Seleccionar tipo</option>
                                    <?php
                                    $item = "tipo_dato";
                                    $valor = "tdoc";
                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);
                                    foreach ($documentos as $key => $value) {
                                        if ($value["codigo"] == "01" || $value["codigo"] == "03") {
                                            echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mf-field campoDocOrigen">
                                <label for="serieOrigen">Doc. origen</label>
                                <input type="text" class="form-control input-sm" name="serieOrigen" id="serieOrigen" placeholder="Ej. F00100012345" autocomplete="off">
                                <p class="mf-help" id="serieOrigenHelp"></p>
                            </div>
                            <div class="mf-field campoFecOrigen">
                                <label for="fechaOrigen">Fecha emisión</label>
                                <input type="date" class="form-control input-sm" name="fechaOrigen" id="fechaOrigen" readonly>
                            </div>
                            <div class="mf-field campoMotOrigen">
                                <label for="notaMotivo">Motivo</label>
                                <select class="form-control input-sm selectpicker" name="notaMotivo" id="notaMotivo" data-live-search="true" data-container="body" data-size="8" title="Seleccionar motivo">
                                    <option value="">Seleccionar motivo</option>
                                    <?php
                                    $item = "tipo_dato";
                                    $valor = "TMOT";
                                    $documentos = ControladorCuentas::ctrMostrarPagos($item, $valor);
                                    foreach ($documentos as $key => $value) {
                                        echo '<option value="' . $value["codigo"] . '">' . $value["codigo"] . " - " . $value["descripcion"] . '</option>';
                                    }
                                    ?>
                                </select>
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
                    <div id="mfAlertPreciosCero" class="mf-alert-precios hidden" role="alert">
                        <div class="mf-alert-precios__head">
                            <strong id="mfAlertPreciosCeroTitulo">Hay modelos sin precio</strong>
                            <a href="#" id="mfAlertPreciosCeroEditar" class="btn btn-xs btn-warning">Ir a editar el pedido</a>
                        </div>
                        <p class="mf-alert-precios__hint">Corrige el precio del modelo antes de generar factura o boleta.</p>
                        <ul id="mfAlertPreciosCeroLista" class="mf-alert-precios__lista"></ul>
                    </div>
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

<!--=====================================
MODAL DIVIDIR
======================================-->

<div id="modalDividir" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 40% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Pasar Pedido a:</h4>

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

                            <!-- ENTRADA PARA EL CODIGO DEL PEDIDO-->

                            <div class="form-group col-lg-3">

                                <label>Cod. Pedido</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="codPedidoD" name="codPedidoD" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NOMBRE DEL CLIENTE-->

                            <div class="form-group col-lg-9">

                                <label>Cliente</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="nomCliD" name="nomCliD" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL codigo DEL CLIENTE-->

                            <div class="form-group col-lg-3">

                                <label>Cod. Cliente</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="codCliD" name="codCliD" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL codigo DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Total S/.</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="totalD" name="totalD" readonly>

                                </div>

                            </div>

                            <div class="form-group col-lg-4">

                                <label>Porcentaje Aprobado</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-percent"></i></span>

                                    <select class="form-control input-sm" id="perPed" name="perPed" required>

                                        <option value="">Porcentaje</option>

                                        <option value="0.9">90 %</option>

                                        <option value="0.8">80 %</option>

                                        <option value="0.7">70 %</option>

                                        <option value="0.6">60 %</option>

                                        <option value="0.5">50 %</option>

                                        <option value="0.4">40 %</option>

                                        <option value="0.3">30 %</option>

                                        <option value="0.2">20 %</option>

                                        <option value="0.1">10 %</option>

                                    </select>

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

                    <button type="submit" class="btn btn-primary">Dividir Pedido</button>

                </div>

            </form>

            <?php

            $dividir = new ControladorPedidos();
            $dividir->ctrDividirPedido();

            ?>

        </div>

    </div>

</div>

<!--=====================================
MODAL ENVIAR PEDIDOS
======================================-->

<div id="modalEnviarPedido" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 20% !important;">

        <div class="modal-content">

            <form role="form" method="post">

                <!--=====================================
        CABEZA DEL MODAL
        ======================================-->

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Enviar Pedidos</h4>

                </div>

                <!--=====================================
        CUERPO DEL MODAL
        ======================================-->

                <div class="modal-body">

                    <div class="box-body">

                        <div class="box box-primary col-lg-12 ">

                            <div class="box-header">

                                <b>Seleccionar Fecha</b>

                            </div>

                            <!-- ENTRADA PARA EL CODIGO DEL PEDIDO-->

                            <div class="form-group col-lg-12">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>

                                    <?php

                                    date_default_timezone_set('America/Lima');
                                    $fecha = new DateTime();

                                    ?>

                                    <input type="date" class="form-control input-sm" id="fechaEnvio" name="fechaEnvio" value="<?php echo $fecha->format("Y-m-d"); ?>">

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

                    <button type="submit" class="btn btn-primary">Enviar Pedido</button>

                </div>

            </form>

            <?php

            $enviar = new ControladorPedidos();
            $enviar->ctrEnviarPedido();

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
</script>