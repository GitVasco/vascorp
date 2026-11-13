<?php
/**
 * Pestaña «Exportación» de Comprobantes especiales → Listado y envío (docs/efact/EXPORTACION-diseno.md, Fase 3B).
 * Facturas FX emitidas desde Nuevo comprobante → Exportación: tipo de cambio, ver archivo, imprimir y enviar a eFact.
 * Lógica en vistas/js/exportacion-emitir.js.
 */
?>
<div id="expEmitir">
<style>
    #expEmitir td, #expEmitir th { vertical-align: middle !important; }
    #expEmitir .exp-nowrap { white-space: nowrap; }
    #expEmitir table { font-size: 12px; }
    #expEmitir .exp-bandera { width: 20px; height: 14px; margin-right: 6px; vertical-align: -2px; box-shadow: 0 0 1px rgba(0,0,0,.5); }
    #expEmitir .exp-tc { width: 90px; height: 24px; padding: 2px 6px; font-size: 12px; display: inline-block; text-align: right; }
    #expEmitir .exp-tc.exp-ok { border-color: #00a65a; background: #eafaf1; }
    #expEmitir .exp-tc.exp-error { border-color: #dd4b39; background: #fdecea; }
</style>
    <div class="alert alert-danger" id="expFaltan" style="display:none"></div>
    <div class="form-inline" style="margin-bottom:8px">
        <label>Desde</label> <input type="date" class="form-control input-sm" id="expEmDesde" value="<?= date("Y-m-01"); ?>">
        <label style="margin-left:8px">Hasta</label> <input type="date" class="form-control input-sm" id="expEmHasta" value="<?= date("Y-m-d"); ?>">
        <button type="button" class="btn btn-default btn-sm" id="expEmBuscar"><i class="fa fa-search"></i> Buscar</button>
        <a href="comprobantes-especiales-crear#exportacion" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Nueva exportación</a>
    </div>
    <table class="table table-bordered table-striped table-condensed" id="expTablaEmitidas" width="100%">
        <thead>
            <tr>
                <th>Factura</th><th class="text-center">Emitida</th><th>Pedido</th><th>Cliente</th><th>País</th>
                <th class="text-right">Total USD</th><th class="text-right" style="width:140px">Tipo cambio</th>
                <th class="text-center">eFact</th><th class="text-center">Acciones</th>
            </tr>
        </thead>
    </table>
    <p class="text-muted" style="font-size:12px;margin:8px 0 0">
        <b>Enviar</b> deja el archivo de exportación en la carpeta de eFact. El tipo de cambio se guarda también en la venta: escríbalo y pulse Enter, o use <i class="fa fa-refresh"></i> para traer el del día de emisión (totales o API).
    </p>

    <div class="modal fade" id="expModalArchivo" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Archivo para eFact · <span id="expArchNombre"></span></h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" id="expArchAvisos" style="display:none;padding:8px 12px"></div>
                    <pre id="expArchContenido" style="font-size:11px;max-height:420px;overflow:auto;white-space:pre"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
