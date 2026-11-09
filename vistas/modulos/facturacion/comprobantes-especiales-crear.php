<div class="content-wrapper" id="ceForm">

    <section class="content-header">
        <h1>
            Nuevo comprobante especial
            <small>Elige el tipo · un comprobante por receptor · no mueve stock</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="comprobantes-especiales">Comprobantes especiales</a></li>
            <li class="active">Nuevo</li>
        </ol>
    </section>

    <section class="content">
        <div class="alert alert-danger" id="ceFaltan" style="display:none"></div>

        <div class="row">
        <div class="col-md-9">

        <!-- DATOS -->
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> Datos del comprobante</h3></div>
            <div class="box-body">
                <div class="row" id="ceTipoWrap">
                    <div class="form-group col-md-6">
                        <label>Tipo de comprobante especial</label>
                        <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="ceTipo"></select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2 col-sm-6">
                        <label>Emisión</label>
                        <input type="date" class="form-control input-sm" id="ceFecha" value="<?= date("Y-m-d"); ?>">
                    </div>
                    <div class="form-group col-md-3 col-sm-6">
                        <label>Destinatarios</label>
                        <select class="form-control input-sm selectpicker" data-width="100%" data-container="body" id="ceCategoria"></select>
                    </div>
                    <div class="form-group col-md-7 col-sm-12">
                        <label>Afectación IGV (Catálogo 07)</label>
                        <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="ceAfectacion"></select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-5">
                        <label>Motivo <span class="text-red">*</span></label>
                        <input type="text" class="form-control input-sm" id="ceMotivo" maxlength="150" placeholder="Ej. ENTREGA A TRABAJADORES">
                    </div>
                    <div class="form-group col-md-7">
                        <label>Observaciones (opcional)</label>
                        <input type="text" class="form-control input-sm" id="ceObs" maxlength="200">
                    </div>
                </div>
                <p class="text-muted" style="font-size:12px;margin:0">
                    11–16 = retiro gravado: el comprobante muestra el IGV 18 % solo como <b>informativo</b> y el importe total es 0.00.
                    La afectación inafecta (sin IGV) está desactivada hasta que Contabilidad lo confirme.
                </p>
            </div>
        </div>

        <!-- ÍTEMS -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list"></i> Ítems (iguales para todos los receptores)</h3>
                <button type="button" class="btn btn-xs btn-success pull-right" id="ceAddItem"><i class="fa fa-plus"></i> Ítem</button>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-condensed" id="ceItems">
                    <thead>
                        <tr><th style="width:110px">Código</th><th>Descripción</th><th style="width:130px">Unidad</th><th style="width:90px">Cantidad</th>
                            <th style="width:130px">Valor ref. unit. (sin IGV)</th><th style="width:100px" class="text-right">Valor</th><th style="width:40px"></th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="text-right">
                    Valor gratuito <b id="ceTotGrat">0.00</b> &nbsp;·&nbsp; IGV informativo <b id="ceTotIgv">0.00</b> &nbsp;·&nbsp; <b>Importe total 0.00</b>
                    <small class="text-muted">(por comprobante)</small>
                </div>
            </div>
        </div>

        <!-- RECEPTORES -->
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-users"></i> Receptores</h3></div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-5">
                        <label>Agregar por DNI / RUC <small class="text-muted">(busca solo al completar 8 u 11 dígitos)</small></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="ceDocBuscar" maxlength="11" inputmode="numeric" placeholder="DNI o RUC" autocomplete="off">
                            <span class="input-group-btn"><button type="button" class="btn btn-primary" id="ceDocBtn"><i class="fa fa-search"></i> Buscar</button></span>
                        </div>
                        <div id="ceDocEstado" style="min-height:20px;margin:4px 0 12px;font-size:12px"></div>
                        <label>O pegar una lista <small class="text-muted">(una por línea: DNI o RUC, nombre opcional; busca solos los que vengan sin nombre)</small></label>
                        <textarea class="form-control" rows="5" id="cePegar" placeholder="47099761&#9;PEREZ GOMEZ JUAN&#10;20293847038&#9;EMPRESA SAC&#10;70300893"></textarea>
                        <button type="button" class="btn btn-sm btn-default" id="ceAgregarLista" style="margin-top:6px"><i class="fa fa-plus"></i> Agregar a la lista</button>
                        <button type="button" class="btn btn-sm btn-info" id="ceConsultarTodos" style="margin-top:6px"><i class="fa fa-search"></i> Reintentar búsqueda</button>
                    </div>
                    <div class="col-md-7 table-responsive">
                        <table class="table table-condensed" id="ceReceptores">
                            <thead><tr><th style="width:120px">DNI / RUC</th><th>Nombre o razón social</th><th>Dirección</th><th style="width:90px">Comprob.</th><th style="width:40px"></th></tr></thead>
                            <tbody></tbody>
                        </table>
                        <div class="text-muted" id="ceResumenRec">Sin receptores.</div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <a href="comprobantes-especiales" class="btn btn-default pull-left">Salir</a>
                <button type="button" class="btn btn-primary pull-right" id="ceGuardar"><i class="fa fa-save"></i> Crear comprobantes (quedan GENERADOS)</button>
            </div>
        </div>
        </div><!-- /col-md-9 -->

        <!-- AYUDA (según el tipo elegido) -->
        <div class="col-md-3">
            <div class="box box-info" id="ceAyuda" style="position:sticky;top:10px">
                <div class="box-header with-border"><h3 class="box-title" id="ceAyudaTitulo"><i class="fa fa-lightbulb-o"></i> Guía rápida</h3></div>
                <div class="box-body" style="font-size:12px">
                    <ol id="ceAyudaPasos" style="padding-left:16px;margin-bottom:10px"></ol>
                    <b id="ceAyudaCodTitulo"></b>
                    <table class="table table-condensed table-bordered" style="margin:6px 0 10px"><tbody id="ceAyudaCodigos"></tbody></table>
                    <p class="text-muted" id="ceAyudaNota" style="margin:0"></p>
                </div>
            </div>
        </div>
        </div><!-- /row -->
    </section>
</div>
