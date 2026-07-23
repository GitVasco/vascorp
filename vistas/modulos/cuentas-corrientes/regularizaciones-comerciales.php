<div class="content-wrapper" id="panelRegularizacionesComerciales">

    <section class="content-header">
        <h1>
            Regularizaciones comerciales
            <small>Solo afecta lo que ve el vendedor en VascoPro</small>
            <button type="button" class="btn btn-default btn-xs rc-btn-ayuda" id="rcBtnAyuda"
                    title="¿Qué hace esta pantalla?" data-toggle="modal" data-target="#rcModalAyuda">
                <i class="fa fa-question"></i>
            </button>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Vasco Online</li>
            <li class="active">Regularizaciones</li>
        </ol>
    </section>

    <section class="content">

        <div class="row">
            <div class="col-md-5">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> Buscar documento</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="rcBuscarQ">Cliente o número de documento</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="rcBuscarQ"
                                       placeholder="Ej. código cliente, nombre o factura">
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
                                        <th>Documento</th>
                                        <th>Cliente</th>
                                        <th class="text-right">En Vascorp</th>
                                        <th class="text-right">En VascoPro</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="rc-vacio">
                                        <td colspan="5" class="text-muted text-center">Busque un documento para comenzar.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="box box-success" id="rcBoxAlta" style="display:none;">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-plus-circle"></i> Registrar pago comprobado</h3>
                    </div>
                    <div class="box-body">
                        <div class="rc-cargo-sel" id="rcCargoSeleccionado"></div>
                        <form id="rcFormAlta" autocomplete="off">
                            <input type="hidden" id="rcCuentaCteId" value="">
                            <div class="form-group">
                                <label for="rcMonto">Monto que pagó el cliente</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="rcMonto" required>
                                <p class="help-block">No puede ser mayor al saldo que aún se muestra en VascoPro.</p>
                            </div>
                            <div class="form-group">
                                <label for="rcFechaPago">Fecha del pago</label>
                                <input type="date" class="form-control" id="rcFechaPago" required>
                            </div>
                            <div class="form-group">
                                <label for="rcSustento">Nro. de OP o recibo</label>
                                <input type="text" class="form-control" id="rcSustento"
                                       maxlength="100" required placeholder="Ej. OP 12345 / Recibo 987">
                            </div>
                            <div class="form-group">
                                <label for="rcMotivo">Motivo</label>
                                <input type="text" class="form-control" id="rcMotivo"
                                       maxlength="255" required placeholder="Ej. Pago no registrado en su momento">
                            </div>
                            <div class="form-group">
                                <label for="rcObservacion">Nota <span class="text-muted">(opcional)</span></label>
                                <textarea class="form-control" id="rcObservacion" rows="2" maxlength="500"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success" id="rcBtnCrear">
                                <i class="fa fa-check"></i> Guardar
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
                                <option value="REQUIERE_REVISION">Por revisar</option>
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
                                    <th class="text-right">Monto</th>
                                    <th>Estado</th>
                                    <th>Recibo / OP</th>
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
                        <h3 class="box-title"><i class="fa fa-info-circle"></i> Detalle</h3>
                    </div>
                    <div class="box-body" id="rcDetalleCuerpo"></div>
                </div>
            </div>
        </div>

    </section>
</div>

<div class="modal fade" id="rcModalAyuda" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-question-circle"></i> ¿Para qué sirve esta pantalla?</h4>
            </div>
            <div class="modal-body rc-ayuda-body">
                <p>
                    Algunos clientes <strong>ya pagaron</strong>, pero ese pago no quedó registrado
                    en Vascorp. Mientras tanto, en la app del vendedor (VascoPro) sigue apareciendo
                    la deuda.
                </p>
                <p>
                    Aquí puedes indicar ese pago comprobado (con OP o recibo) para que
                    <strong>VascoPro deje de mostrar esa deuda</strong> al sincronizar.
                </p>
                <hr>
                <p><strong>Qué sí hace</strong></p>
                <ul>
                    <li>Baja o quita el saldo que ve el vendedor en VascoPro.</li>
                    <li>Deja un historial con el recibo/OP y quién lo registró.</li>
                </ul>
                <p><strong>Qué no hace</strong></p>
                <ul>
                    <li>No registra un cobro en caja.</li>
                    <li>No cambia la contabilidad ni el saldo interno de Vascorp.</li>
                    <li>No reemplaza el proceso normal de cobranza.</li>
                </ul>
                <p class="text-muted" style="margin-bottom:0;">
                    Cuando el pago se registre por el camino normal, esta regularización
                    se puede resolver sola o anularse si ya no aplica.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rcModalAnular" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Anular regularización</h4>
            </div>
            <div class="modal-body">
                <p>Al anular, VascoPro volverá a mostrar esa deuda en el próximo sync. Queda guardado en el historial.</p>
                <input type="hidden" id="rcAnularId" value="">
                <div class="form-group">
                    <label for="rcAnularMotivo">¿Por qué la anulas?</label>
                    <textarea class="form-control" id="rcAnularMotivo" rows="3" required
                              placeholder="Ej. Se cargó por error / el pago ya se registró"></textarea>
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
