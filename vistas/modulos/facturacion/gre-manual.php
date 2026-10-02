<div class="content-wrapper" id="greListado">

    <section class="content-header">
        <h1>
            Guías de remisión manuales
            <small>Traslados, compras y otros motivos</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Guías manuales</li>
        </ol>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-header with-border">
                <div class="form-inline">
                    <label>Desde</label> <input type="date" class="form-control input-sm" id="greDesde" value="<?= date("Y-m-01"); ?>">
                    <label style="margin-left:8px">Hasta</label> <input type="date" class="form-control input-sm" id="greHasta" value="<?= date("Y-m-d"); ?>">
                    <select class="form-control input-sm selectpicker" id="greTipoFiltro" data-width="150px" style="margin-left:8px">
                        <option value="">Internas y electrónicas</option>
                        <option value="ELECTRONICA">Electrónicas</option>
                        <option value="INTERNA">Internas</option>
                    </select>
                    <select class="form-control input-sm selectpicker" id="greEstadoFiltro" data-width="150px" style="margin-left:8px">
                        <option value="">Todos los estados</option>
                        <option value="GENERADO">GENERADO</option>
                        <option value="ENVIADO">ENVIADO</option>
                        <option value="ANULADO">ANULADO</option>
                    </select>
                    <button type="button" class="btn btn-default btn-sm" id="greBuscar"><i class="fa fa-search"></i> Buscar</button>
                    <a href="gre-manual-crear" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Nueva guía</a>
                    <?php if (ControladorGreManual::puedeCorregirCorrelativo()) { ?>
                        <button type="button" class="btn btn-default btn-sm pull-right" id="greBtnCorrelativo" style="margin-right:6px"><i class="fa fa-sort-numeric-asc"></i> Corregir correlativo</button>
                    <?php } ?>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive" id="greTabla" width="100%">
                    <thead>
                        <tr>
                            <th>Documento</th><th>Tipo</th><th>Emisión</th><th>Traslado</th><th>Motivo</th><th>Destinatario</th>
                            <th>Llegada</th><th>Ítems</th><th>Peso kg</th><th>Estado</th><th>Usuario</th><th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalGreCorrelativo" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc;color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Corregir correlativo</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Serie</label>
                    <select class="form-control selectpicker" id="greCorSerie" data-width="100%"></select>
                </div>
                <p class="text-muted" id="greCorInfo" style="font-size:12px"></p>
                <div class="form-group">
                    <label>Último número usado</label>
                    <input type="number" min="0" step="1" class="form-control" id="greCorNumero">
                    <span class="help-block" id="greCorProximo"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                <button type="button" class="btn btn-primary" id="greCorGuardar">Guardar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGreConvertir" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc;color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Convertir a electrónica</h4>
            </div>
            <div class="modal-body">
                <p id="greConvTexto"></p>
                <div class="form-group">
                    <label>Serie electrónica</label>
                    <select class="form-control selectpicker" id="greConvSerie" data-width="100%"></select>
                </div>
                <p class="text-muted" style="font-size:12px">Toma el siguiente número de esa serie. Después se envía a EFACT desde el listado.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                <button type="button" class="btn btn-primary" id="greConvGuardar">Convertir</button>
            </div>
        </div>
    </div>
</div>
