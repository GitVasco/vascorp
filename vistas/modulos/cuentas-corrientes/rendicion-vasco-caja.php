<div class="content-wrapper" id="panelRendicionVascoCaja">

    <section class="content-header">
        <h1>
            Rendición Vasco
            <small>Caja — cobranzas en efectivo de campo</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Cuentas corrientes</li>
            <li class="active">Rendición Vasco</li>
        </ol>
    </section>

    <section class="content">

        <p class="vasco-caja-intro text-muted">
            Cobranzas registradas por vendedores en <strong>Vasco</strong> con estado
            <span class="label label-warning">Pendiente de entrega</span>.
            Valide el efectivo recibido y confirme para informar a Vasco que la empresa ya lo recibió.
            La imputación a documentos se hace aparte en vascorp.
        </p>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-search"></i> Buscar pendientes</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroVendedorVasco">Vendedor (RUC/DNI)</label>
                            <input type="text" class="form-control" id="filtroVendedorVasco" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroDesdeVasco">Desde</label>
                            <input type="date" class="form-control" id="filtroDesdeVasco">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filtroLimiteVasco">Límite</label>
                            <select class="form-control" id="filtroLimiteVasco">
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary" id="btnBuscarCobranzasVasco">
                                    <i class="fa fa-refresh"></i> Consultar pendientes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-default" id="boxResumenCobranzasVasco" style="display:none;">
            <div class="box-body">
                <div class="row text-center">
                    <div class="col-sm-3">
                        <small class="text-muted">Pendientes</small>
                        <div><strong id="resumenCountVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Monto total</small>
                        <div><strong id="resumenMontoVasco">S/ 0.00</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Seleccionados</small>
                        <div><strong id="resumenSeleccionVasco">0</strong></div>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted">Trace ID</small>
                        <div><code id="resumenTraceVasco" class="vasco-caja-trace">—</code></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <button type="button" class="btn btn-default btn-sm" id="btnMarcarTodosVasco">
                    <i class="fa fa-check-square-o"></i> Seleccionar todos
                </button>
                <button type="button" class="btn btn-default btn-sm" id="btnDesmarcarTodosVasco">
                    <i class="fa fa-square-o"></i> Ninguno
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmarSeleccionVasco" disabled>
                    <i class="fa fa-check"></i> Confirmar seleccionadas
                </button>
                <div class="pull-right" style="margin-top:4px;">
                    <label class="checkbox-inline" style="font-weight:normal;">
                        <input type="checkbox" id="chkUsarRefGlobalVasco"> Usar referencia global
                    </label>
                    <input type="text" class="form-control input-sm" id="refGlobalVasco"
                        placeholder="Nº rendición ERP (opcional)" style="display:inline-block;width:200px;margin-left:8px;" disabled>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tablaCobranzasVasco" width="100%">
                        <thead>
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Código</th>
                                <th>Fecha cobro</th>
                                <th>Cliente</th>
                                <th>Doc. cliente</th>
                                <th>Vendedor</th>
                                <th>Monto</th>
                                <th>Ticket</th>
                                <th>Notas</th>
                                <th style="width:110px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoCobranzasVasco">
                            <tr class="vasco-caja-empty">
                                <td colspan="10" class="text-center text-muted">
                                    Use <strong>Consultar pendientes</strong> para cargar cobranzas desde Vasco.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list-alt"></i> Registro de operación</h3>
            </div>
            <div class="box-body">
                <pre id="logRendicionVasco" class="vasco-caja-log">Sin operaciones aún.</pre>
            </div>
        </div>

    </section>
</div>

<script>
    window.document.title = "Rendición Vasco — Caja";
    window.vascoCajaUsuario = <?php echo json_encode(isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "caja.vascorp"); ?>;
</script>
