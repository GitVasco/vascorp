<div class="content-wrapper smp-page">

    <section class="content-header">
        <h1>
            Sublíneas MP
            <small>Catálogo TSUB por línea</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Materia Prima</li>
            <li class="active">Sublíneas MP</li>
        </ol>
    </section>

    <section class="content">

        <div class="box box-solid smp-box">
            <div class="box-header with-border smp-toolbar">
                <div class="smp-toolbar-linea">
                    <label for="smpSelLinea">Línea</label>
                    <select class="form-control selectpicker" id="smpSelLinea" data-live-search="true" data-width="100%" title="Seleccionar línea…">
                        <option value="">Seleccionar línea…</option>
                    </select>
                </div>
                <div class="smp-stats" id="smpStats">
                    <div class="smp-stat">
                        <span class="smp-stat-label">Sublíneas</span>
                        <strong id="smpStatTotal">0</strong>
                    </div>
                    <div class="smp-stat">
                        <span class="smp-stat-label">Con MP</span>
                        <strong id="smpStatConMp">0</strong>
                    </div>
                    <div class="smp-stat">
                        <span class="smp-stat-label">Sin uso</span>
                        <strong id="smpStatSinUso">0</strong>
                    </div>
                    <div class="smp-stat">
                        <span class="smp-stat-label">MP activas</span>
                        <strong id="smpStatMp">0</strong>
                    </div>
                    <div class="smp-stat smp-stat-code">
                        <span class="smp-stat-label">Próximo</span>
                        <strong id="smpStatProximo">—</strong>
                    </div>
                </div>
                <div class="smp-toolbar-actions">
                    <div class="input-group input-group-sm smp-filter-search" id="smpFiltroWrap" style="display:none;">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="smpFiltroTexto" placeholder="Buscar…">
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="smpBtnNuevo" disabled>
                        <i class="fa fa-plus"></i> Nueva
                    </button>
                </div>
            </div>

            <div class="box-body smp-body">
                <div class="smp-placeholder" id="smpPlaceholder">
                    <p>Elige una línea para ver sus sublíneas.</p>
                </div>

                <div id="smpContenido" style="display:none;">
                    <div class="row smp-split">
                        <div class="col-md-6 smp-col smp-col-sub">
                            <div class="smp-pane-head">Sublíneas</div>
                            <div class="smp-tabla-wrap">
                                <table class="table smp-tabla">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th class="text-center">MP</th>
                                            <th class="smp-col-acc"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="smpTablaBody"></tbody>
                                </table>
                            </div>
                            <div class="smp-empty" id="smpEmpty" style="display:none;">
                                <p>Esta línea aún no tiene sublíneas.</p>
                            </div>
                            <div class="smp-no-match" id="smpNoMatch" style="display:none;">
                                <p class="text-muted">Sin coincidencias con el filtro.</p>
                            </div>
                        </div>
                        <div class="col-md-6 smp-col smp-col-mp">
                            <div class="smp-pane-head">
                                <span id="smpMpTitulo">Materias primas</span>
                                <span class="badge bg-aqua" id="smpMpCount" style="display:none;">0</span>
                                <button type="button" class="btn btn-primary btn-xs smp-btn-nueva-mp" id="smpBtnNuevaMp" disabled>
                                    <i class="fa fa-plus"></i> Nueva MP
                                </button>
                            </div>
                            <div class="smp-mp-placeholder" id="smpMpPlaceholder">
                                <p>Haz clic en una sublínea para ver sus materias primas.</p>
                            </div>
                            <div id="smpMpContenido" style="display:none;">
                                <div class="smp-tabla-wrap">
                                    <table class="table smp-tabla">
                                        <thead>
                                            <tr>
                                                <th>Cód. fábrica</th>
                                                <th>Descripción</th>
                                                <th>Color</th>
                                                <th class="text-right">Stock</th>
                                                <th>OC / OS</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="smpMpBody"></tbody>
                                    </table>
                                </div>
                                <div class="smp-empty" id="smpMpEmpty" style="display:none;">
                                    <p>Esta sublínea no tiene materias primas activas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<div class="modal fade" id="modalSublineaMp" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content smp-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="smpTituloForm">Nueva sublínea</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="smpCodArgumento" value="">
                <input type="hidden" id="smpModo" value="crear">

                <p class="help-block smp-help" id="smpHelp"></p>

                <div class="smp-preview">
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Código sublínea</span>
                        <strong id="smpCodigoPreview">—</strong>
                    </div>
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Correlativo</span>
                        <strong id="smpSubcodigoPreview">—</strong>
                    </div>
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Arg. interno</span>
                        <strong id="smpArgPreview">—</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label for="smpNombre">Nombre</label>
                    <input type="text" class="form-control" id="smpNombre" maxlength="120" placeholder="Descripción larga de la sublínea" autocomplete="off">
                </div>

                <a href="#smpCamposExtra" class="smp-toggle-extra" data-toggle="collapse">
                    Campos adicionales <i class="fa fa-angle-down"></i>
                </a>
                <div id="smpCamposExtra" class="collapse">
                    <div class="row smp-extra-row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="smpValor1">Valor 1</label>
                                <input type="text" class="form-control input-sm" id="smpValor1" maxlength="50">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="smpValor2">Valor 2</label>
                                <input type="text" class="form-control input-sm" id="smpValor2" maxlength="50">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="smpValor4">Valor 4</label>
                                <input type="text" class="form-control input-sm" id="smpValor4" maxlength="50">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="smpValor5">Valor 5</label>
                                <input type="text" class="form-control input-sm" id="smpValor5" maxlength="50">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                <button type="button" class="btn btn-primary" id="smpBtnGuardar">
                    <i class="fa fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMpNueva" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content smp-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="smpMpTituloForm">Nueva materia prima</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="smpMpOrigenCodpro" value="">
                <div class="smp-preview">
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Línea</span>
                        <strong id="smpMpLineaTxt">—</strong>
                    </div>
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Sublínea</span>
                        <strong id="smpMpSubTxt">—</strong>
                    </div>
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Cód. fábrica</span>
                        <strong id="smpMpCodFab">—</strong>
                    </div>
                </div>
                <p class="help-block smp-help" id="smpMpFabHint">El código se arma con línea + sublínea + color + talla.</p>
                <div class="alert alert-danger smp-alert-fab" id="smpMpFabError" style="display:none; padding:8px 12px; margin-bottom:12px;">
                    Ese código de fábrica ya existe.
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="smpMpColor">Color</label>
                            <select class="form-control selectpicker" id="smpMpColor" data-live-search="true" data-width="100%" title="Seleccionar color…">
                                <option value="">Seleccionar color…</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="smpMpTalla">Talla</label>
                            <select class="form-control selectpicker" id="smpMpTalla" data-live-search="true" data-width="100%" title="Seleccionar talla…">
                                <option value="">Seleccionar talla…</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="smpMpUnidad">Unidad</label>
                    <select class="form-control selectpicker" id="smpMpUnidad" data-live-search="true" data-width="100%" title="Seleccionar unidad…">
                        <option value="">Seleccionar unidad…</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="smpMpNombre">Descripción</label>
                    <input type="text" class="form-control" id="smpMpNombre" maxlength="120" placeholder="Descripción de la materia prima" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="smpMpCodAlt">Código alterno <small class="text-muted">(opcional)</small></label>
                    <input type="text" class="form-control" id="smpMpCodAlt" maxlength="30" style="text-transform:uppercase" autocomplete="off">
                </div>

                <a href="#smpMpExtra" class="smp-toggle-extra" data-toggle="collapse">
                    Peso, ad valórem y stocks <i class="fa fa-angle-down"></i>
                </a>
                <div id="smpMpExtra" class="collapse">
                    <div class="row smp-extra-row">
                        <div class="col-xs-4">
                            <div class="form-group">
                                <label for="smpMpPeso">Peso</label>
                                <input type="number" min="0" step="any" class="form-control input-sm" id="smpMpPeso" value="0">
                            </div>
                        </div>
                        <div class="col-xs-4">
                            <div class="form-group">
                                <label for="smpMpAdval">% Ad val</label>
                                <input type="number" min="0" step="any" class="form-control input-sm" id="smpMpAdval" value="0">
                            </div>
                        </div>
                        <div class="col-xs-4">
                            <div class="form-group">
                                <label for="smpMpSeguro">% Seguro</label>
                                <input type="number" min="0" step="any" class="form-control input-sm" id="smpMpSeguro" value="0">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="smpMpStkMin">Stock mín.</label>
                                <input type="number" min="0" step="any" class="form-control input-sm" id="smpMpStkMin" value="0">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="smpMpStkMax">Stock máx.</label>
                                <input type="number" min="0" step="any" class="form-control input-sm" id="smpMpStkMax" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                <button type="button" class="btn btn-primary" id="smpBtnGuardarMp">
                    <i class="fa fa-save"></i> Guardar MP
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMpOrdenes" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content smp-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Órdenes de compra y servicio</h4>
            </div>
            <div class="modal-body">
                <div class="smp-preview">
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Cód. fábrica</span>
                        <strong id="smpOcCodFab">—</strong>
                    </div>
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Descripción</span>
                        <strong id="smpOcDespro">—</strong>
                    </div>
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Color</span>
                        <strong id="smpOcColor">—</strong>
                    </div>
                    <div class="smp-preview-item">
                        <span class="smp-preview-label">Stock</span>
                        <strong id="smpOcStock">—</strong>
                    </div>
                </div>

                <h5 class="smp-ocos-title">
                    Órdenes de compra pendientes
                    <span class="label label-primary" id="smpOcCount">0</span>
                </h5>
                <div class="table-responsive">
                    <table class="table table-condensed smp-tabla smp-tabla-ocos">
                        <thead>
                            <tr>
                                <th>Nro OC</th>
                                <th>Emisión</th>
                                <th>Llegada</th>
                                <th>Proveedor</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Saldo</th>
                                <th>Estado</th>
                                <th class="text-right">Precio</th>
                            </tr>
                        </thead>
                        <tbody id="smpOcBody"></tbody>
                    </table>
                </div>

                <h5 class="smp-ocos-title">
                    Órdenes de servicio pendientes
                    <span class="label label-warning" id="smpOsCount">0</span>
                </h5>
                <div class="table-responsive">
                    <table class="table table-condensed smp-tabla smp-tabla-ocos">
                        <thead>
                            <tr>
                                <th>Nro OS</th>
                                <th>Emisión</th>
                                <th>Entrega</th>
                                <th>Rol</th>
                                <th>MP origen</th>
                                <th>MP destino</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Saldo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="smpOsBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>
