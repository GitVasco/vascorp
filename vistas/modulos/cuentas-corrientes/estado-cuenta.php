<?php
if (!isset($_SESSION["cuenta"]) || (int) $_SESSION["cuenta"] !== 1) {
    if (function_exists("denegarAccesoModulo")) {
        denegarAccesoModulo();
    } else {
        echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Sin permiso</div></section></div>';
    }
    return;
}

date_default_timezone_set("America/Lima");
$gruposActivos = ControladorGruposEmpresariales::ctrMostrarGruposActivos();
?>

<div class="content-wrapper ec-page" id="ecPage">
    <section class="content-header">
        <div class="ec-header-row">
            <div>
                <h1>Estado de cuenta</h1>
            </div>
            <a href="consultar-cuentas" class="btn btn-default btn-sm">
                <i class="fa fa-list"></i> Vista clásica
            </a>
        </div>
    </section>

    <section class="content">
        <div class="box box-primary ec-box">
            <div class="box-body">
                <div class="ec-filtros-bar">
                    <div class="ec-filtros-bar__campo ec-filtros-bar__campo--cliente">
                        <label class="ec-field-lbl" for="ecFiltroCliente">Cliente</label>
                        <select class="form-control input-sm selectpicker" id="ecFiltroCliente"
                                data-live-search="true" data-size="10" title="Buscar cliente…">
                            <option value="">Seleccionar cliente</option>
                        </select>
                    </div>
                    <div class="ec-filtros-bar__campo ec-filtros-bar__campo--grupo">
                        <label class="ec-field-lbl" for="ecFiltroGrupo">Grupo empresarial</label>
                        <select class="form-control input-sm selectpicker" id="ecFiltroGrupo"
                                data-live-search="true" data-size="10" title="Todos / sin filtro de grupo">
                            <option value="">— Sin filtro de grupo —</option>
                            <?php foreach ($gruposActivos as $grupoItem) : ?>
                                <option value="<?php echo htmlspecialchars($grupoItem["codigo"], ENT_QUOTES, "UTF-8"); ?>"
                                    data-nombre="<?php echo htmlspecialchars($grupoItem["nombre"], ENT_QUOTES, "UTF-8"); ?>">
                                    <?php echo htmlspecialchars($grupoItem["nombre"], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ec-filtros-bar__acciones">
                        <button type="button" class="btn btn-default btn-sm" id="btnEcLimpiar" title="Limpiar selección">
                            <i class="fa fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>

                <div class="ec-empty" id="ecEmpty">
                    <i class="fa fa-search"></i>
                    <p>Busque un <strong>cliente</strong> o un <strong>grupo empresarial</strong>.</p>
                    <p class="ec-empty-hint">Si el cliente está en un grupo, verá de inmediato la deuda consolidada y todos los documentos.</p>
                </div>

                <div id="ecContenido" class="ec-contenido hidden">
                    <div class="ec-hero" id="ecSnap">
                        <div class="ec-hero__head">
                            <div class="ec-hero__id">
                                <div class="ec-hero__tags">
                                    <span class="ec-hero__modo" id="ecSnapModo"><i class="fa fa-user"></i> Cliente</span>
                                    <span class="ec-riesgo-badge ec-riesgo-badge--sin_dato" id="ecRiesgoBadge">—</span>
                                </div>
                                <h3 class="ec-hero__titulo" id="ecSnapTitulo">—</h3>
                                <div class="ec-hero__meta" id="ecSnapMeta"></div>
                            </div>
                            <div class="ec-hero__acciones">
                                <a href="linea-credito" class="ec-hero-btn" id="btnEcIrLinea" title="Abrir línea de crédito">
                                    <i class="fa fa-credit-card"></i> Línea
                                </a>
                                <button type="button" class="ec-hero-btn hidden" id="btnEcSoloLocal" title="Ver solo este local">
                                    <i class="fa fa-user"></i> Solo local
                                </button>
                                <button type="button" class="ec-hero-btn hidden" id="btnEcTodoGrupo" title="Volver al grupo">
                                    <i class="fa fa-sitemap"></i> Grupo
                                </button>
                                <button type="button" class="ec-hero-btn ec-hero-btn--accent" id="btnEcPagos" disabled>
                                    <i class="fa fa-money"></i> Pagos
                                </button>
                            </div>
                        </div>

                        <div class="ec-strip" id="ecKpis">
                            <div class="ec-chip ec-chip--deuda">
                                <span class="ec-chip__lbl">Deuda</span>
                                <strong id="ecKpiDeuda">S/ 0.00</strong>
                            </div>
                            <div class="ec-chip ec-chip--vencido">
                                <span class="ec-chip__lbl">Vencido</span>
                                <strong id="ecKpiVencido">S/ 0.00</strong>
                                <em id="ecKpiMoraPct"></em>
                            </div>
                            <div class="ec-chip ec-chip--linea">
                                <span class="ec-chip__lbl">Línea</span>
                                <strong id="ecKpiLinea">—</strong>
                                <em id="ecKpiLineaEtiqueta"></em>
                            </div>
                            <div class="ec-chip ec-chip--cupo">
                                <span class="ec-chip__lbl">Cupo</span>
                                <strong id="ecKpiCupo">S/ 0.00</strong>
                            </div>
                            <div class="ec-chip ec-chip--uso">
                                <span class="ec-chip__lbl">Uso</span>
                                <strong id="ecKpiUso">0%</strong>
                                <span class="ec-chip__bar"><i id="ecKpiUsoBar" style="width:0%"></i></span>
                            </div>
                            <div class="ec-chip ec-chip--docs">
                                <span class="ec-chip__lbl">Docs</span>
                                <strong id="ecKpiDocs">0</strong>
                                <em id="ecKpiDocsSub"></em>
                            </div>
                            <div class="ec-chip ec-chip--venta">
                                <span class="ec-chip__lbl">Venta</span>
                                <strong id="ecKpiVenta">S/ 0.00</strong>
                            </div>
                            <div class="ec-chip ec-chip--protesta">
                                <span class="ec-chip__lbl">Protesta</span>
                                <strong id="ecKpiProtesta">0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="ec-panel-docs hidden" id="ecPanelDocs">
                        <div class="ec-docs-toolbar">
                            <div class="ec-seccion-titulo">
                                <h4><i class="fa fa-file-text-o"></i> Documentos</h4>
                                <span class="text-muted" id="ecDocsContador"></span>
                            </div>
                            <div class="ec-docs-filtros" id="ecDocsFiltros">
                                <button type="button" class="btn btn-xs btn-default" data-filtro="">Todos</button>
                                <button type="button" class="btn btn-xs btn-default active" data-filtro="pendiente">Pendientes</button>
                                <button type="button" class="btn btn-xs btn-default" data-filtro="cancelado">Cancelados</button>
                                <button type="button" class="btn btn-xs btn-default" data-filtro="vencido">Vencidos</button>
                            </div>
                        </div>
                        <div class="table-responsive" id="ecTablaWrap">
                            <table class="table table-hover table-condensed ec-tabla-docs" id="ecTablaDocs" width="100%">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal pagos -->
<div class="modal fade" id="modalEcPagos" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header ec-modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-money"></i> Pagos últimos 6 meses</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="modalEcPagosSub"></p>
                <div class="table-responsive">
                    <table class="table table-condensed table-striped" id="ecTablaPagos">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="ec-monto">Total</th>
                                <th class="ec-monto">Jackyform</th>
                                <th class="ec-monto">RosaFlor</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal cancelaciones -->
<div class="modal fade" id="modalEcCancelaciones" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header ec-modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Cancelaciones del documento</h4>
            </div>
            <div class="modal-body">
                <p class="ec-modal-doc" id="modalEcCancelDoc">—</p>
                <div class="table-responsive">
                    <table class="table table-condensed table-striped" id="ecTablaCancel">
                        <thead>
                            <tr>
                                <th>Tipo pago</th>
                                <th>Doc. origen</th>
                                <th>Fecha</th>
                                <th>Notas</th>
                                <th class="ec-monto">Monto</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
