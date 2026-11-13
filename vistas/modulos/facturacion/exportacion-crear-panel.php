<?php
/**
 * Panel del tipo «Exportación» en Comprobantes especiales → Nuevo comprobante.
 * Lo muestra/oculta vistas/js/exportacion-crear.js al elegir el tipo.
 * Emite con las funciones de facturación existentes (ventajf, movimientos, cuenta corriente, stock 01).
 */
?>
<input type="hidden" id="expSoloExportacion" value="<?= ControladorComprobantesEspeciales::puedeUsar() ? "0" : "1"; ?>">
<style>
    #expCrear .exp-bandera { width: 20px; height: 14px; margin-right: 6px; vertical-align: -2px; box-shadow: 0 0 1px rgba(0,0,0,.5); }
    #expCrear .exp-cab { display: flex; flex-wrap: wrap; gap: 4px 28px; margin: 4px 0 10px; }
    #expCrear .exp-cab label { margin: 0 4px 0 0; color: #555; }
    #expCrear table { font-size: 12px; }
</style>
<div class="box box-primary" id="expCrear" style="display:none">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-globe"></i> Factura de exportación desde pedido</h3>
        <span class="pull-right text-muted" style="font-size:12px">Serie <b id="expCSerie">—</b> · USD · almacén 01</span>
    </div>
    <div class="box-body">
        <div class="alert alert-danger" id="expCFaltan" style="display:none"></div>
        <div class="row">
            <div class="form-group col-md-8">
                <label>Pedido <small class="text-muted">(clientes del exterior · aprobados, APT o confirmados)</small></label>
                <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" data-size="10" id="expCPedido">
                    <option value="">Seleccionar pedido</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Tipo de cambio (venta)</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="expCTc" placeholder="3.7520" maxlength="8">
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" id="expCTcRefrescar" title="Traer de totales o de la API"><i class="fa fa-refresh"></i></button>
                    </span>
                </div>
                <small class="text-muted" id="expCTcFuente"></small>
            </div>
        </div>

        <div id="expCInfo" style="display:none">
            <div class="exp-cab">
                <span><label>Cliente</label> <span id="expCCliente"></span></span>
                <span><label>País</label> <span id="expCPais"></span></span>
                <span><label>Documento</label> <span id="expCDoc"></span></span>
                <span><label>Dirección</label> <span id="expCDir"></span></span>
                <span><label>Condición</label> <span id="expCCond"></span></span>
                <span><label>Estado</label> <span id="expCEstado"></span></span>
            </div>
            <div class="alert alert-danger" id="expCErrores" style="display:none;padding:8px 12px"></div>
            <div class="alert alert-warning" id="expCAvisos" style="display:none;padding:8px 12px"></div>
            <table class="table table-bordered table-condensed" id="expCDetalle">
                <thead><tr><th>Modelo</th><th>Descripción</th><th>Línea</th><th class="text-center">Cant.</th><th class="text-right">Precio USD</th><th class="text-right">Total USD</th></tr></thead>
                <tbody></tbody>
                <tfoot>
                    <tr><th colspan="5" class="text-right">Descuento</th><th class="text-right" id="expCDscto"></th></tr>
                    <tr><th colspan="5" class="text-right">Total USD</th><th class="text-right" id="expCTotal"></th></tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="box-footer">
        <a href="comprobantes-especiales-crear" class="btn btn-default pull-left">Salir</a>
        <a href="comprobantes-especiales#exportacion" class="btn btn-link pull-left"><i class="fa fa-list"></i> Listado y envío a eFact</a>
        <button type="button" class="btn btn-primary pull-right" id="expCEmitir" disabled><i class="fa fa-file-text-o"></i> Emitir factura de exportación</button>
    </div>
</div>
