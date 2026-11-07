<div class="content-wrapper">

    <section class="content-header">
        <h1>Liquidaciones de servicios de producción</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Liquidaciones de servicios</li>
        </ol>
    </section>

    <section class="content">

        <div class="box">
            <div class="box-header with-border">
                <button class="btn btn-primary" id="lspBtnNueva" data-toggle="modal" data-target="#lspModalNueva">
                    <i class="fa fa-plus"></i> Nueva liquidación
                </button>
                <div class="form-inline pull-right">
                    <label>Periodo:</label>
                    <input type="date" id="lspFiltroDesde" class="form-control input-sm" value="<?= date('Y-m-01'); ?>">
                    <input type="date" id="lspFiltroHasta" class="form-control input-sm" value="<?= date('Y-m-d'); ?>">
                    <select id="lspFiltroTaller" class="form-control input-sm selectpicker" data-live-search="true" data-width="170px">
                        <option value="">Todos los talleres</option>
                    </select>
                    <select id="lspFiltroEstado" class="form-control input-sm selectpicker" data-width="130px">
                        <option value="">Todos</option>
                        <option value="LIQUIDADA">Por pagar</option>
                        <option value="PAGADA">Pagadas</option>
                        <option value="ANULADA">Anuladas</option>
                    </select>
                    <button class="btn btn-default btn-sm" id="lspBtnFiltrar"><i class="fa fa-search"></i> Buscar</button>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped" id="lspTabla" width="100%">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Taller</th>
                            <th>RUC</th>
                            <th>Periodo cierres</th>
                            <th>Cierres</th>
                            <th>Unidades</th>
                            <th>Importe</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </section>
</div>

<!--=====================================
MODAL NUEVA LIQUIDACIÓN
======================================-->
<div id="lspModalNueva" class="modal fade" role="dialog">
    <div class="modal-dialog" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc;color:#fff">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Nueva liquidación de servicios</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Taller</label>
                        <select id="lspTaller" class="form-control selectpicker" data-live-search="true" data-width="100%"></select>
                    </div>
                    <div class="col-md-2">
                        <label>Cierres desde</label>
                        <input type="date" id="lspDesde" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>Cierres hasta</label>
                        <input type="date" id="lspHasta" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label><br>
                        <button class="btn btn-info" id="lspBtnPrevia"><i class="fa fa-eye"></i> Previsualizar</button>
                    </div>
                </div>

                <div id="lspAvisos" style="margin-top:12px"></div>
                <div id="lspPrevia" style="margin-top:12px; max-height:380px; overflow:auto"></div>

                <div id="lspPie" style="display:none; margin-top:12px">
                    <label>Observaciones (opcional)</label>
                    <textarea id="lspObs" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="lspBtnEmitir" disabled><i class="fa fa-check"></i> Liquidar e imprimir</button>
            </div>
        </div>
    </div>
</div>

<!--=====================================
MODAL PAGAR
======================================-->
<div id="lspModalPagar" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#00a65a;color:#fff">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Registrar pago <span id="lspPagarNum"></span></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="lspPagarId">
                <div class="form-group">
                    <label>Fecha de pago</label>
                    <input type="date" id="lspPagarFecha" class="form-control">
                </div>
                <div class="form-group">
                    <label>N° de operación / referencia (opcional)</label>
                    <input type="text" id="lspPagarRef" class="form-control" maxlength="100">
                </div>
                <p class="text-muted">Los cierres de esta liquidación pasarán a <b>PAGADO</b>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="lspBtnPagar"><i class="fa fa-money"></i> Confirmar pago</button>
            </div>
        </div>
    </div>
</div>
