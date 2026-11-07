<div class="content-wrapper">

    <section class="content-header">
        <h1>Órdenes de servicio de producción</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Órdenes de servicio</li>
        </ol>
    </section>

    <section class="content">

        <div class="box">
            <div class="box-header with-border">
                <button class="btn btn-primary" id="ospBtnNueva" data-toggle="modal" data-target="#ospModalNueva">
                    <i class="fa fa-plus"></i> Nueva orden de servicio
                </button>
                <div class="form-inline pull-right">
                    <label>Emisión:</label>
                    <input type="date" id="ospFiltroDesde" class="form-control input-sm" value="<?= date('Y-m-01'); ?>">
                    <input type="date" id="ospFiltroHasta" class="form-control input-sm" value="<?= date('Y-m-d'); ?>">
                    <select id="ospFiltroTaller" class="form-control input-sm selectpicker" data-live-search="true" data-width="170px">
                        <option value="">Todos los talleres</option>
                    </select>
                    <select id="ospFiltroEstado" class="form-control input-sm selectpicker" data-width="120px">
                        <option value="">Todos</option>
                        <option value="EMITIDA">Emitidas</option>
                        <option value="ANULADA">Anuladas</option>
                    </select>
                    <button class="btn btn-default btn-sm" id="ospBtnFiltrar"><i class="fa fa-search"></i> Buscar</button>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped" id="ospTabla" width="100%">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Taller</th>
                            <th>RUC</th>
                            <th>Periodo servicios</th>
                            <th>Emisión</th>
                            <th>Servicios</th>
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
MODAL NUEVA ORDEN
======================================-->
<div id="ospModalNueva" class="modal fade" role="dialog">
    <div class="modal-dialog" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc;color:#fff">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Nueva orden de servicio</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Taller</label>
                        <select id="ospTaller" class="form-control selectpicker" data-live-search="true" data-width="100%"></select>
                    </div>
                    <div class="col-md-2">
                        <label>Servicios desde</label>
                        <input type="date" id="ospDesde" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>Servicios hasta</label>
                        <input type="date" id="ospHasta" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label><br>
                        <button class="btn btn-info" id="ospBtnPrevia"><i class="fa fa-eye"></i> Previsualizar</button>
                    </div>
                </div>

                <div id="ospAvisos" style="margin-top:12px"></div>
                <div id="ospPrevia" style="margin-top:12px; max-height:380px; overflow:auto"></div>

                <div id="ospPie" style="display:none; margin-top:12px">
                    <label>Observaciones (opcional)</label>
                    <textarea id="ospObs" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="ospBtnEmitir" disabled><i class="fa fa-check"></i> Emitir orden e imprimir</button>
            </div>
        </div>
    </div>
</div>
