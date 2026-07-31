<div class="content-wrapper">

    <section class="content-header">

        <?php

        if ($_SESSION["id"] == "70" || $_SESSION["id"] == "6") {

            echo '<h1>

                        Administrar Guias de Remisión <button class="btn btn-primary btn-xs btnActualizarTalonarios" title="Correlativo">Cambiar Correlativo</button>
            
                    </h1>';
        } else {

            echo '<h1>

                        Administrar Guias de Remisión
            
                    </h1>';
        }


        ?>

        <ol class="breadcrumb">

            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

            <li class="active">Administrar Guias de Remisión</li>

        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">
                <?php
                $filtroSerieGuia = isset($_GET["serie"]) ? trim((string) $_GET["serie"]) : "";
                $filtroVendedorGuia = isset($_GET["vendedor"]) ? trim((string) $_GET["vendedor"]) : "";
                $seriesGuia = ControladorTalonarios::ctrMostrarTalonarios("00");
                $vendedoresGuia = ControladorVendedores::ctrMostrarVendedores(null, null);
                if (is_array($vendedoresGuia)) {
                    usort($vendedoresGuia, function ($a, $b) {
                        return strcmp($a["codigo"], $b["codigo"]);
                    });
                }
                ?>
                <div class="form-inline" style="display:inline-block; margin-right:10px;">
                    <label for="filtroSerieGuia" style="margin-right:4px;">Serie</label>
                    <select class="form-control input-sm selectpicker" id="filtroSerieGuia" data-live-search="true" data-width="120px" title="Todas">
                        <option value="">Todas</option>
                        <?php
                        if (is_array($seriesGuia)) {
                            foreach ($seriesGuia as $serieItem) {
                                $serieVal = $serieItem["serie_guias"];
                                $sel = ($filtroSerieGuia !== "" && $filtroSerieGuia === $serieVal) ? " selected" : "";
                                echo '<option value="' . htmlspecialchars($serieVal, ENT_QUOTES, "UTF-8") . '"' . $sel . '>'
                                    . htmlspecialchars($serieVal, ENT_QUOTES, "UTF-8") . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <label for="filtroVendedorGuia" style="margin-left:10px; margin-right:4px;">Vendedor</label>
                    <select class="form-control input-sm selectpicker" id="filtroVendedorGuia" data-live-search="true" data-width="220px" title="Todos">
                        <option value="">Todos</option>
                        <?php
                        if (is_array($vendedoresGuia)) {
                            foreach ($vendedoresGuia as $vendItem) {
                                $codVend = $vendItem["codigo"];
                                $sel = ($filtroVendedorGuia !== "" && $filtroVendedorGuia === $codVend) ? " selected" : "";
                                echo '<option value="' . htmlspecialchars($codVend, ENT_QUOTES, "UTF-8") . '"' . $sel . '>'
                                    . htmlspecialchars($codVend . " - " . $vendItem["descripcion"], ENT_QUOTES, "UTF-8") . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <button type="button" class="btn btn-default btn-sm" id="btnLimpiarFiltrosGuia" style="margin-left:8px;" title="Limpiar filtros">
                        <i class="fa fa-eraser"></i> Limpiar
                    </button>
                </div>
                <button type="button" class="btn btn-default pull-right" id="daterange-btnGuiaRem">
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

                <table class="table table-bordered table-striped dt-responsive tablaGuiasRemision" width="100%">

                    <thead>

                        <tr>

                            <th>Documento</th>
                            <th>Total</th>
                            <th>Cod. Cliente</th>
                            <th>Nombre</th>
                            <th>Vendedor</th>
                            <th>Fec. Emisión</th>
                            <th>Doc. Destino</th>
                            <th>Estado</th>
                            <th>Agencia</th>
                            <th>Destino</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </section>

</div>

<!--=====================================
MODAL FACTURAR A
======================================-->

<div id="modalFacturarA" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 50% !important;">

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

                                <label>Guia de Remisión</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="codPedido" name="codPedido" readonly>

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
                                    <input type="hidden" class="form-control input-sm" name="codVen" id="codVen" readonly>
                                    <input type="hidden" name="idUsuario" id="idUsuario" value="<?php echo $_SESSION["id"]; ?>">

                                </div>

                            </div>

                        </div>

                        <div class="box box-success col-lg-12 ">

                            <div class="box-header">

                                <b>Documento Destino</b>

                            </div>
                            <!-- ENTRADA PARA EL CODIGO DEL PEDIDO-->

                            <div class="form-group col-lg-3">

                                <label>Serie</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="serieDest" name="serieDest" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NOMBRE DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Número</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="docDest" name="docDest" readonly>

                                </div>

                            </div>

                            <?php if (!function_exists('feCsvOrdenCompraActiva') || feCsvOrdenCompraActiva()): ?>
                            <div class="form-group col-lg-5">

                                <label>Orden de compra <small>(opcional)</small></label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-file-text-o"></i></span>

                                    <input type="text" class="form-control input-sm" id="orden_compra" name="orden_compra" maxlength="20" placeholder="Sin espacios ni guiones" autocomplete="off">

                                </div>

                            </div>
                            <?php endif; ?>

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

            $guiaRemision = new controladorFacturacion();
            $guiaRemision->ctrFacturarGuia();

            ?>

        </div>

    </div>

</div>

<!--=====================================
MODAL FACTURAR B
======================================-->
<div id="modalFacturarB" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 50% !important;">

        <div class="modal-content">

            <form role="form" method="post" onsubmit="return checkSubmit();">

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

                                    <input type="text" class="form-control input-sm" id="codPedidoB" name="codPedidoB" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NOMBRE DEL CLIENTE-->

                            <div class="form-group col-lg-9">

                                <label>Cliente</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="nomCliB" name="nomCliB" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL codigo DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Cod. Cliente</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="codCliB" name="codCliB" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL TIPO DOCUMENTO DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Tipo Documento</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="tipDocB" name="tipDocB" readonly>

                                </div>

                            </div>

                            <!-- ENTRADA PARA EL NUMERO DOCUMENTO DEL CLIENTE-->

                            <div class="form-group col-lg-4">

                                <label>Nro. Documento</label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>

                                    <input type="text" class="form-control input-sm" id="nroDocB" name="nroDocB" readonly>
                                    <input type="hidden" class="form-control input-sm" name="codVenB" id="codVenB" readonly>
                                    <input type="hidden" name="idUsuarioB" id="idUsuarioB" value="<?php echo $_SESSION["id"]; ?>">

                                </div>

                            </div>

                        </div>

                        <div class="box box-success col-lg-12 ">

                            <div class="box-header">

                                <b>Documento Destino</b>

                            </div>

                            <!-- CHECKBOX PARA SEPARAR DOCUMENTO -->

                            <div class="form-group col-lg-6">

                                <div class="form-group">

                                    <label>
                                        <input class="chkFacturaB" type="checkbox" id="chkFacturaB" name="chkFacturaB">
                                        Separar Factura
                                    </label>

                                    <label>
                                        <input class="chkBoletaB" type="checkbox" id="chkBoletaB" name="chkBoletaB">
                                        Separar Boleta
                                    </label>

                                </div>

                            </div>

                            <!-- ENTRADA PARA NUMERO DE SERIE DEL DOCUMENTO SEPARADO-->

                            <div class="form-group col-lg-6">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                    <select type="text" class="form-control input-md" name="serieSeparadoB" id="serieSeparadoB" required disabled>
                                        <option value="">Seleccionar Serie</option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="box box-warning col-lg-12 ">

                            <div class="box-header">

                                <b>Forma de Pago</b>

                            </div>

                            <!-- ENTRADA PARA TIPO DE DOCUMENTO -->

                            <div class="form-group col-lg-6">

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-share-square-o"></i></span>
                                    <select type="text" class="form-control input-sm" name="formaPago" id="formaPago" disabled>
                                        <option value="">Seleccionar Forma de Pago</option>


                                    </select>

                                </div>

                            </div>

                            <?php if (!function_exists('feCsvOrdenCompraActiva') || feCsvOrdenCompraActiva()): ?>
                            <div class="form-group col-lg-6">

                                <label>Orden de compra <small>(opcional)</small></label>

                                <div class="input-group">

                                    <span class="input-group-addon"><i class="fa fa-file-text-o"></i></span>

                                    <input type="text" class="form-control input-sm" id="orden_compraB" name="orden_compraB" maxlength="20" placeholder="Sin espacios ni guiones" autocomplete="off">

                                </div>

                            </div>
                            <?php endif; ?>


                        </div>

                    </div>

                </div>

                <!--=====================================
        PIE DEL MODAL
        ======================================-->

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" id="btnGenerarDoc" class="btn btn-primary">Generar Documento</button>

                </div>

            </form>

            <?php

            $facturarB = new controladorFacturacion();
            $facturarB->ctrFacturarB();

            ?>

        </div>

    </div>

</div>

<!--=====================================
EDITAR GUIA
======================================-->
<div id="modalGremision" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 720px; max-width: 96%;">

        <div class="modal-content">

            <form role="form" method="post" onsubmit="return checkSubmit();">

                <div class="modal-header" style="background:#3c8dbc; color:white">

                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                    <h4 class="modal-title">Editar guía de remisión</h4>

                </div>

                <div class="modal-body" style="padding:15px;">

                    <div class="box box-primary" style="margin-bottom:12px;">
                        <div class="box-header with-border">
                            <b>Datos Principales</b>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-sm-4 col-lg-3">
                                    <label>Guía de remisión</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-file-text-o"></i></span>
                                        <input type="text" class="form-control input-sm" id="codPedidoC" name="codPedidoC" readonly>
                                    </div>
                                </div>
                                <div class="form-group col-sm-8 col-lg-9">
                                    <label>Cliente</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                        <input type="text" class="form-control input-sm" id="nomCliC" name="nomCliC" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-4">
                                    <label>Cod. Cliente</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-hashtag"></i></span>
                                        <input type="text" class="form-control input-sm" id="codCliC" name="codCliC" readonly>
                                    </div>
                                </div>
                                <div class="form-group col-sm-4">
                                    <label>Tipo documento</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-id-card-o"></i></span>
                                        <input type="text" class="form-control input-sm" id="tipDocC" name="tipDocC" readonly>
                                    </div>
                                </div>
                                <div class="form-group col-sm-4">
                                    <label>Nro. documento</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-id-card"></i></span>
                                        <input type="text" class="form-control input-sm" id="nroDocC" name="nroDocC" readonly>
                                        <input type="hidden" name="codVenC" id="codVenC">
                                        <input type="hidden" name="idUsuarioC" id="idUsuarioC" value="<?php echo $_SESSION["id"]; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box box-warning" style="margin-bottom:12px;">
                        <div class="box-header with-border">
                            <b>Datos para la Guía de Remisión</b>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-sm-6">
                                    <label>Chofer</label>
                                    <select class="form-control input-sm selectpicker" name="chofer" id="chofer" data-live-search="true" data-size="10" data-container="body" title="Seleccionar chofer">
                                        <option value="">Seleccionar chofer</option>
                                        <?php
                                        $valor = "tcho";
                                        $documentos = ModeloPedidos::MostrarDatos($valor);
                                        foreach ($documentos as $key => $value) {
                                            echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . " - " . $value["Des_Larga"] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-sm-6">
                                    <label>Movilidad</label>
                                    <select class="form-control input-sm selectpicker" name="carro" id="carro" data-live-search="true" data-size="10" data-container="body" title="Seleccionar movilidad">
                                        <option value="">Seleccionar movilidad</option>
                                        <?php
                                        $valor = "tcar";
                                        $documentos = ModeloPedidos::MostrarDatos($valor);
                                        foreach ($documentos as $key => $value) {
                                            echo '<option value="' . $value["Cod_Argumento"] . '">' . $value["Cod_Argumento"] . " - " . $value["Des_Larga"] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-4 col-lg-3">
                                    <label>Peso bruto (kg)</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-balance-scale"></i></span>
                                        <input type="text" class="form-control input-sm" id="peso" name="peso" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="form-group col-sm-4 col-lg-3">
                                    <label>N° bultos</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-cubes"></i></span>
                                        <input type="text" class="form-control input-sm" id="bultos" name="bultos" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box box-default" style="margin-bottom:0;">
                        <div class="box-header with-border">
                            <b>Agencia de transporte</b>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-lg-12" style="margin-bottom:0;">
                                    <label>Agencia</label>
                                    <select class="form-control input-sm selectpicker" name="agenciaGuia" id="agenciaGuiaEdit" data-live-search="true" data-size="10" data-container="body" title="Seleccionar agencia">
                                        <option value="">Seleccionar agencia</option>
                                        <?php
                                        $agenciasGuiaEdit = ControladorAgencias::ctrMostrarAgencias(null, null);
                                        if (is_array($agenciasGuiaEdit)) {
                                            foreach ($agenciasGuiaEdit as $agenciaItem) {
                                                echo '<option value="' . htmlspecialchars($agenciaItem["id"], ENT_QUOTES, "UTF-8") . '">'
                                                    . htmlspecialchars($agenciaItem["id"] . " - " . $agenciaItem["nombre"], ENT_QUOTES, "UTF-8")
                                                    . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>

                    <button type="submit" class="btn btn-primary">Actualizar documento</button>

                </div>

            </form>

            <?php

            $facturarB = new controladorFacturacion();
            $facturarB->ctrActualizarGuiaRemision();

            ?>

        </div>

    </div>

</div>

<!--=====================================
RELACIONAR DOCUMENTOS A GUIA GENERADO
======================================-->
<div id="modalRelacionarDocGuia" class="modal fade" role="dialog">

    <div class="modal-dialog" style="width: 560px;">

        <div class="modal-content">

            <div class="modal-header" style="background:#3c8dbc; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Relacionar documentos</h4>
            </div>

            <div class="modal-body" style="padding-top:15px;">

                <div style="margin-bottom:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:2px;">Guía</div>
                    <div id="guiaRelacionarDocLabel" style="font-size:18px; font-weight:700; letter-spacing:0.3px;"></div>
                    <div id="clienteGuiaRelacionarLabel" style="font-size:13px; color:#555; margin-top:4px;"></div>
                    <input type="hidden" id="guiaRelacionarDoc">
                    <input type="hidden" id="docsDestinoActualGuiaRaw">
                    <input type="hidden" id="clienteGuiaRelacionar">
                </div>

                <div style="margin-bottom:14px;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">Ya relacionados</div>
                    <div id="listaDocsDestinoActualGuia" style="min-height:28px;"></div>
                </div>

                <div class="form-group" style="margin-bottom:10px;">
                    <label style="font-weight:600;">Agregar factura / boleta</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="docRelacionarGuia" placeholder="Uno o varios: B001-00076670, B001-00076671">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default" id="btnAgregarDocRelGuia">Agregar</button>
                        </span>
                    </div>
                </div>

                <div style="margin-bottom:0;">
                    <div style="font-size:12px; color:#777; margin-bottom:6px;">
                        Por relacionar (<span id="cantDocsPendientesGuia">0</span>)
                    </div>
                    <ul id="listaDocsPendientesGuia" class="list-group" style="margin-bottom:0; max-height:220px; overflow:auto;"></ul>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarRelacionarDocGuia">Relacionar</button>
            </div>

        </div>

    </div>

</div>

<?php

$anularDocumento = new ControladorFacturacion();
$anularDocumento->ctrAnularDocumento();

$eliminarDocumento = new ControladorFacturacion();
$eliminarDocumento->ctrEliminarDocumento();
?>

<script>
    window.document.title = "Guias de Remisión"
</script>
<style>
    #modalGremision .bootstrap-select {
        width: 100% !important;
    }
    #modalGremision .box {
        box-shadow: none;
    }
</style>