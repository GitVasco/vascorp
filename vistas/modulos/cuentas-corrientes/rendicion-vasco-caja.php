<div class="content-wrapper" id="panelRendicionVascoCaja">

    <section class="content-header">
        <h1>
            Rendición Vasco
            <small>Caja — efectivo a rendir (puede haber cobro mixto)</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Cuentas corrientes</li>
            <li class="active">Rendición Vasco</li>
        </ol>
    </section>

    <section class="content">

        <div class="vasco-caja-intro callout callout-info">
            <p>
                Cobranzas de <strong>Vasco</strong> en
                <span class="label label-warning">Pendiente de entrega</span>.
                Solo aparece lo que tiene <strong>efectivo</strong> en mano del vendedor
                (puede ser cobro mixto: efectivo + billetera/transferencia).
                En la grilla verá <strong>Efectivo</strong> (a rendir en caja) y
                <strong>Otros medios</strong> (billetera/transfer, informativo).
                Confirme solo el efectivo.
                Si un cobro es erróneo, <strong>anúlelo</strong> con motivo (no confirmar).
                La imputación a documentos se hace aparte en vascorp.
            </p>
        </div>

        <div class="box box-primary vasco-caja-filtros">
            <div class="box-body">
                <div class="vasco-caja-filtros-grid">
                    <div class="form-group">
                        <label for="filtroDesdeVasco">Desde</label>
                        <input type="date" class="form-control" id="filtroDesdeVasco">
                    </div>
                    <div class="form-group">
                        <label for="filtroLimiteVasco">Límite</label>
                        <select class="form-control" id="filtroLimiteVasco">
                            <option value="50">50</option>
                            <option value="100" selected>100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="form-group vasco-caja-filtro-vendedor">
                        <label for="filtroVendedorVasco">Vendedor</label>
                        <select class="form-control" id="filtroVendedorVasco" disabled>
                            <option value="">Consulte para listar vendedores</option>
                        </select>
                    </div>
                    <div class="form-group vasco-caja-filtro-accion">
                        <label class="vasco-caja-label-spacer">&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-block" id="btnBuscarCobranzasVasco">
                            <i class="fa fa-refresh"></i> Consultar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="boxResumenCobranzasVasco" class="vasco-caja-resumen" style="display:none;">
            <div class="vasco-caja-kpis">
                <div class="vasco-caja-kpi">
                    <span class="vasco-caja-kpi-label">Pendientes</span>
                    <strong id="resumenCountVasco" class="vasco-caja-kpi-value">0</strong>
                </div>
                <div class="vasco-caja-kpi vasco-caja-kpi--money">
                    <span class="vasco-caja-kpi-label">Efectivo</span>
                    <strong id="resumenMontoVasco" class="vasco-caja-kpi-value">S/ 0.00</strong>
                </div>
                <div class="vasco-caja-kpi vasco-caja-kpi--sel">
                    <span class="vasco-caja-kpi-label">Seleccionados</span>
                    <strong id="resumenSeleccionVasco" class="vasco-caja-kpi-value">0</strong>
                </div>
                <div class="vasco-caja-kpi vasco-caja-kpi--hint">
                    <span class="vasco-caja-kpi-label">Filtro activo</span>
                    <strong id="resumenFiltroVasco" class="vasco-caja-kpi-value vasco-caja-kpi-value--sm">Todos</strong>
                </div>
            </div>

            <div id="resumenPorVendedorVasco" class="vasco-caja-totales-vendedor" style="display:none;">
                <div class="vasco-caja-totales-head">
                    <h4 class="vasco-caja-totales-titulo">
                        <i class="fa fa-users"></i> Totales por vendedor
                    </h4>
                    <span class="text-muted vasco-caja-totales-hint">Clic en un vendedor para filtrar la lista</span>
                </div>
                <div class="vasco-caja-seller-grid" id="cuerpoTotalesVendedorVasco"></div>
            </div>
        </div>

        <div class="box vasco-caja-lista">
            <div class="box-header with-border vasco-caja-toolbar">
                <div class="vasco-caja-toolbar-left">
                    <button type="button" class="btn btn-default btn-sm" id="btnMarcarTodosVasco">
                        <i class="fa fa-check-square-o"></i> Todos
                    </button>
                    <button type="button" class="btn btn-default btn-sm" id="btnDesmarcarTodosVasco">
                        <i class="fa fa-square-o"></i> Ninguno
                    </button>
                    <button type="button" class="btn btn-success" id="btnConfirmarSeleccionVasco" disabled>
                        <i class="fa fa-check"></i> Confirmar seleccionadas
                    </button>
                    <button type="button" class="btn btn-danger" id="btnAnularSeleccionVasco" disabled>
                        <i class="fa fa-ban"></i> Anular seleccionadas
                    </button>
                </div>
                <div class="vasco-caja-toolbar-right">
                    <label class="checkbox-inline vasco-caja-ref-check">
                        <input type="checkbox" id="chkUsarRefGlobalVasco"> Referencia global
                    </label>
                    <input type="text" class="form-control input-sm" id="refGlobalVasco"
                        placeholder="Nº rendición ERP" disabled>
                </div>
            </div>
            <div class="box-body no-padding">
                <div class="table-responsive vasco-caja-tabla-wrap">
                    <table class="table table-hover" id="tablaCobranzasVasco" width="100%">
                        <thead>
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Código</th>
                                <th>Fecha cobro</th>
                                <th>Cliente</th>
                                <th>Doc. cliente</th>
                                <th>Vendedor</th>
                                <th class="text-right" title="Solo efectivo en custodia del vendedor">Efectivo</th>
                                <th class="text-right" title="Billetera / transferencia (no se rinde en caja)">Otros medios</th>
                                <th>Ticket</th>
                                <th>Notas</th>
                                <th style="width:170px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoCobranzasVasco">
                            <tr class="vasco-caja-empty">
                                <td colspan="11" class="text-center text-muted">
                                    Use <strong>Consultar</strong> para cargar cobranzas desde Vasco.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="box box-default collapsed-box vasco-caja-log-box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-terminal"></i> Registro de operación</h3>
                <div class="box-tools pull-right">
                    <span class="vasco-caja-trace-wrap" title="Trace ID de la consulta">
                        <code id="resumenTraceVasco" class="vasco-caja-trace">—</code>
                    </span>
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body" style="display:none;">
                <pre id="logRendicionVasco" class="vasco-caja-log">Sin operaciones aún.</pre>
            </div>
        </div>

    </section>
</div>

<script>
    window.document.title = "Rendición Vasco — Caja";
    window.vascoCajaUsuario = <?php echo json_encode(isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "caja.vascorp"); ?>;
</script>
