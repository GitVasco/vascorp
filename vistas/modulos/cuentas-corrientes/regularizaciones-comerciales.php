<div class="content-wrapper" id="panelRegularizacionesComerciales">

    <section class="content-header">
        <h1>
            Regularizaciones comerciales
            <small>Excepcional — solo efecto en VascoPro</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Vasco Online</li>
            <li class="active">Regularizaciones</li>
        </ol>
    </section>

    <section class="content">

        <div class="callout callout-warning rc-aviso">
            <h4><i class="fa fa-exclamation-triangle"></i> No es cobranza ni asiento contable</h4>
            <p>
                Esta pantalla <strong>no modifica</strong> la cartera oficial (<code>cuenta_ctejf</code>),
                caja ni contabilidad. Solo ajusta el saldo comercial enviado a VascoPro.
            </p>
            <ul class="rc-aviso-lista">
                <li><strong>Saldo oficial</strong> — no se modifica</li>
                <li><strong>Regularizaciones activas</strong> — pagos comprobados no ingresados al ERP</li>
                <li><strong>Saldo comercial</strong> — lo que ve VascoPro tras el sync</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> Buscar cargo oficial</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="rcBuscarQ">Cliente, documento o número</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="rcBuscarQ"
                                       placeholder="Código, nombre, tipo_doc o num_cta">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" id="rcBtnBuscarCargos">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div class="table-responsive rc-tabla-wrap">
                            <table class="table table-condensed table-hover" id="rcTablaCargos">
                                <thead>
                                    <tr>
                                        <th>Doc</th>
                                        <th>Cliente</th>
                                        <th class="text-right">Oficial</th>
                                        <th class="text-right">Comercial</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="rc-vacio">
                                        <td colspan="5" class="text-muted text-center">Busque un cargo para comenzar.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="box box-success" id="rcBoxAlta" style="display:none;">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-plus-circle"></i> Nueva regularización</h3>
                    </div>
                    <div class="box-body">
                        <div class="rc-cargo-sel well well-sm" id="rcCargoSeleccionado"></div>
                        <form id="rcFormAlta" autocomplete="off">
                            <input type="hidden" id="rcCuentaCteId" value="">
                            <div class="form-group">
                                <label for="rcMonto">Monto del pago comprobado</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="rcMonto" required>
                                <p class="help-block">No puede superar el saldo comercial disponible.</p>
                            </div>
                            <div class="form-group">
                                <label for="rcFechaPago">Fecha en que pagó el cliente</label>
                                <input type="date" class="form-control" id="rcFechaPago" required>
                            </div>
                            <div class="form-group">
                                <label for="rcSustento">OP / nro. de recibo</label>
                                <input type="text" class="form-control" id="rcSustento"
                                       maxlength="100" required placeholder="Ej. OP 12345 / Recibo 987">
                            </div>
                            <div class="form-group">
                                <label for="rcMotivo">Motivo</label>
                                <input type="text" class="form-control" id="rcMotivo"
                                       maxlength="255" required placeholder="Ej. Pago no ingresado — fraude junio">
                            </div>
                            <div class="form-group">
                                <label for="rcObservacion">Observación <span class="text-muted">(opcional)</span></label>
                                <textarea class="form-control" id="rcObservacion" rows="2" maxlength="500"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success" id="rcBtnCrear">
                                <i class="fa fa-check"></i> Registrar regularización
                            </button>
                            <button type="button" class="btn btn-default" id="rcBtnCancelarAlta">Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> Regularizaciones</h3>
                        <div class="box-tools pull-right">
                            <select class="form-control input-sm" id="rcFiltroEstado" style="width:auto;display:inline-block;">
                                <option value="">Todas</option>
                                <option value="ACTIVA" selected>Activas</option>
                                <option value="REQUIERE_REVISION">Requiere revisión</option>
                                <option value="RESUELTA_AUTOMATICA">Resueltas</option>
                                <option value="ANULADA">Anuladas</option>
                            </select>
                            <button type="button" class="btn btn-default btn-sm" id="rcBtnRefrescarLista" title="Actualizar">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body table-responsive rc-tabla-wrap">
                        <table class="table table-striped table-condensed" id="rcTablaLista">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th class="text-right">Aplicable</th>
                                    <th>Estado</th>
                                    <th>Sustento</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="rc-vacio">
                                    <td colspan="7" class="text-muted text-center">Cargando…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="box box-info" id="rcBoxDetalle" style="display:none;">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-info-circle"></i> Detalle / historial</h3>
                    </div>
                    <div class="box-body" id="rcDetalleCuerpo"></div>
                </div>
            </div>
        </div>

    </section>
</div>

<div class="modal fade" id="rcModalAnular" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Anular regularización</h4>
            </div>
            <div class="modal-body">
                <p>La anulación es lógica: deja de afectar VascoPro y queda en el historial.</p>
                <input type="hidden" id="rcAnularId" value="">
                <div class="form-group">
                    <label for="rcAnularMotivo">Motivo de anulación</label>
                    <textarea class="form-control" id="rcAnularMotivo" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="rcBtnConfirmarAnular">
                    <i class="fa fa-ban"></i> Anular
                </button>
            </div>
        </div>
    </div>
</div>
