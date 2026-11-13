<div class="content-wrapper" id="expConfig">
<style>
    #expConfig td, #expConfig th { vertical-align: middle !important; }
    #expConfig .exp-nowrap { white-space: nowrap; }
    #expConfig .exp-cfg-linea { display: flex; flex-wrap: wrap; gap: 8px 32px; align-items: baseline; }
    #expConfig .exp-cfg-linea label { margin: 0 4px 0 0; color: #555; }
    #expTablaModelos { font-size: 12px; }
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
                <li><a href="#expTabPartidas" data-toggle="tab"><i class="fa fa-barcode"></i> Partidas arancelarias</a></li>
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
                        Opcional. Si una línea no tiene partida, la factura sale igual sin ese dato.
                    </p>
                    <table class="table table-bordered table-striped" id="expTablaPartidas" width="100%">
                        <thead>
                            <tr><th>Línea</th><th>Partida</th><th>Descripción</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr>
                        </thead>
                    </table>
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
                <h4 class="modal-title">Partida de <b id="expPLineaTxt"></b></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="expPLinea">
                <div class="form-group">
                    <label>Partida arancelaria (10 dígitos)</label>
                    <input type="text" class="form-control input-sm" id="expPPartida" maxlength="13" placeholder="6109100000">
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
