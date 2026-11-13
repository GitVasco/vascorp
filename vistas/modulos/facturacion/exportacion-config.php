<div class="content-wrapper" id="expConfig">
<style>
    #expConfig td, #expConfig th { vertical-align: middle !important; }
    #expConfig .exp-nowrap { white-space: nowrap; }
    #expConfig .exp-cfg-linea { display: flex; flex-wrap: wrap; gap: 8px 32px; align-items: baseline; }
    #expConfig .exp-cfg-linea label { margin: 0 4px 0 0; color: #555; }
    #expTablaModelos, #expTablaFx, #expTablaDiff { font-size: 12px; }
    #expConfig .exp-csv { font-size: 11px; max-height: 360px; overflow: auto; white-space: pre; }
    #expConfig .exp-csv .exp-dif { background: #fff3cd; display: block; }
    #expTablaDiff td { font-family: monospace; word-break: break-all; }
    #expTablaModelos td { padding: 2px 6px !important; }
    #expConfig .exp-precio { width: 90px; height: 24px; padding: 2px 6px; font-size: 12px; display: inline-block; text-align: right; }
    #expConfig .exp-precio.exp-ok { border-color: #00a65a; background: #eafaf1; }
    #expConfig .exp-precio.exp-error { border-color: #dd4b39; background: #fdecea; }
    #expConfig .exp-img { width: 36px; height: 36px; object-fit: cover; border-radius: 3px; }
    #expConfig .exp-bandera { width: 20px; height: 14px; margin-right: 6px; vertical-align: -2px; box-shadow: 0 0 1px rgba(0,0,0,.5); }
</style>

    <section class="content-header">
        <h1>
            Exportación
            <small>Configuración · clientes del exterior y partidas arancelarias</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Exportación · Configuración</li>
        </ol>
    </section>

    <section class="content">
        <div class="alert alert-danger" id="expFaltan" style="display:none"></div>

        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-cog"></i> Configuración vigente</h3></div>
            <div class="box-body">
                <div class="exp-cfg-linea">
                    <span><label>Serie de factura</label> <span id="expCfgSerie">—</span></span>
                    <span><label>Lista de precios</label> <span id="expCfgLista">—</span></span>
                    <span><label>Almacén</label> <span id="expCfgAlmacen">—</span></span>
                    <span><label>Moneda</label> <span id="expCfgMoneda">—</span></span>
                </div>
                <div class="alert alert-warning" id="expCfgAvisos" style="display:none;margin:10px 0 0"></div>
            </div>
        </div>

        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#expTabClientes" data-toggle="tab"><i class="fa fa-globe"></i> Clientes del exterior</a></li>
                <li><a href="#expTabModelos" data-toggle="tab"><i class="fa fa-tags"></i> Modelos y precio lista 1</a></li>
                <li><a href="#expTabPartidas" data-toggle="tab"><i class="fa fa-barcode"></i> Códigos SUNAT</a></li>
                <li><a href="#expTabArchivo" data-toggle="tab"><i class="fa fa-file-text-o"></i> Archivo eFact (prueba)</a></li>
            </ul>
            <div class="tab-content">

                <div class="tab-pane active" id="expTabClientes">
                    <div class="alert alert-warning" id="expSinPais" style="display:none"></div>
                    <p class="text-muted" style="font-size:12px">
                        El país se asigna en <a href="clientes"><b>Facturación → Clientes</b></a> (campo «País»).
                        El documento del cliente se imprime en la factura; a eFact se envía sin documento.
                    </p>
                    <table class="table table-bordered table-striped" id="expTablaClientes" width="100%">
                        <thead>
                            <tr>
                                <th>Código</th><th>Cliente</th><th>País</th><th>Documento</th><th>Dirección</th>
                                <th>Correo</th><th class="text-center">Lista</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="tab-pane" id="expTabModelos">
                    <p class="text-muted" style="font-size:12px" id="expModelosAyuda">
                        Solo modelos activos. Precio de la lista 1 en dólares (USD), sin IGV. Escriba el precio y pulse Enter o salga de la casilla para guardar.
                    </p>
                    <table class="table table-bordered table-striped table-condensed" id="expTablaModelos" width="100%">
                        <thead>
                            <tr>
                                <th>Modelo</th><th>Descripción</th><th>Marca</th><th>Línea</th>
                                <th>Tipo</th><th class="text-center">Unidad</th><th class="text-right" style="width:130px">Precio lista 1 (USD)</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="tab-pane" id="expTabPartidas">
                    <p class="text-muted" style="font-size:12px">
                        Código de producto SUNAT (catálogo 25, 8 dígitos) por línea de prenda. Opcional: si una línea no tiene código, la factura sale igual con ese dato vacío.
                    </p>
                    <table class="table table-bordered table-striped" id="expTablaPartidas" width="100%">
                        <thead>
                            <tr><th>Línea</th><th>Código SUNAT</th><th>Descripción</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr>
                        </thead>
                    </table>
                </div>

                <div class="tab-pane" id="expTabArchivo">
                    <p class="text-muted" style="font-size:12px">
                        Compara el archivo de eFact que genera hoy el sistema con el archivo nuevo, para una factura FX ya emitida.
                        <b>No envía nada a eFact</b> ni cambia la factura.
                    </p>
                    <div class="form-inline" style="margin-bottom:8px">
                        <label>Desde</label> <input type="date" class="form-control input-sm" id="expFxDesde" value="<?= date("Y-01-01"); ?>">
                        <label style="margin-left:8px">Hasta</label> <input type="date" class="form-control input-sm" id="expFxHasta" value="<?= date("Y-m-d"); ?>">
                        <button type="button" class="btn btn-default btn-sm" id="expFxBuscar"><i class="fa fa-search"></i> Buscar</button>
                    </div>
                    <table class="table table-bordered table-striped table-condensed" id="expTablaFx" width="100%">
                        <thead>
                            <tr><th>Factura</th><th class="text-center">Fecha</th><th>Cliente</th><th>País</th><th class="text-right">Total</th><th class="text-center">Líneas</th><th class="text-center">Acciones</th></tr>
                        </thead>
                    </table>

                    <div id="expCsvResultado" style="display:none;margin-top:12px">
                        <h4 style="margin-top:0">
                            <span id="expCsvTitulo"></span>
                            <span class="pull-right">
                                <a class="btn btn-default btn-xs" id="expCsvBajarActual" target="_blank"><i class="fa fa-download"></i> Archivo actual</a>
                                <a class="btn btn-primary btn-xs" id="expCsvBajarNuevo" target="_blank"><i class="fa fa-download"></i> Archivo nuevo</a>
                            </span>
                        </h4>
                        <div class="alert alert-warning" id="expCsvAvisos" style="display:none"></div>
                        <table class="table table-bordered table-condensed" id="expTablaDiff">
                            <thead><tr><th>Fila</th><th class="text-center">Columna</th><th>Actual</th><th>Nuevo</th></tr></thead>
                            <tbody></tbody>
                        </table>
                        <div class="row">
                            <div class="col-md-6"><label>Actual</label><pre class="exp-csv" id="expCsvActual"></pre></div>
                            <div class="col-md-6"><label>Nuevo</label><pre class="exp-csv" id="expCsvNuevo"></pre></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="expModalPartida" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Código SUNAT de <b id="expPLineaTxt"></b></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="expPLinea">
                <div class="form-group">
                    <label>Código de producto SUNAT (8 dígitos)</label>
                    <input type="text" class="form-control input-sm" id="expPPartida" maxlength="8" placeholder="53102303">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" class="form-control input-sm" id="expPDescripcion" maxlength="150">
                </div>
                <div class="checkbox" style="margin:0">
                    <label><input type="checkbox" id="expPActivo" checked> Activa</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="expGuardarPartida"><i class="fa fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>
