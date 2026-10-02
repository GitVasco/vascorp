<?php
$greId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$greServicio = isset($_GET["servicio"]) ? preg_replace('/[^A-Za-z0-9_-]/', '', $_GET["servicio"]) : "";
?>
<div class="content-wrapper" id="greForm" data-id="<?= $greId; ?>" data-servicio="<?= htmlspecialchars($greServicio, ENT_QUOTES, "UTF-8"); ?>">

    <section class="content-header">
        <h1>
            <?= $greId ? "Editar" : "Nueva"; ?> guía de remisión manual
            <small>Traslados, compras y otros · no mueve stock</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li><a href="gre-manual">Guías manuales</a></li>
            <li class="active"><?= $greId ? "Editar" : "Nueva"; ?></li>
        </ol>
    </section>

    <section class="content gre-compacto">

        <div class="alert alert-danger" id="greFaltan" style="display:none"></div>
        <div class="alert alert-info" id="greAvisoServicio" style="display:none"></div>

        <!-- TRASLADO -->
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-text-o"></i> Datos del traslado</h3></div>
            <div class="box-body">
                <div class="row">
                    <div class="form-group col-md-3 col-sm-6">
                        <label>Serie</label>
                        <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greSerie"></select>
                    </div>
                    <div class="form-group col-md-2 col-sm-6">
                        <label>Emisión</label>
                        <input type="date" class="form-control input-sm" id="greFechaEmision">
                    </div>
                    <div class="form-group col-md-3 col-sm-6">
                        <label>Inicio traslado</label>
                        <input type="date" class="form-control input-sm" id="greFechaTraslado">
                    </div>
                    <div class="form-group col-md-2 col-sm-6">
                        <label>Peso bruto (KGM) <span class="text-red">*</span></label>
                        <input type="number" step="0.001" min="0" class="form-control input-sm" id="grePeso">
                    </div>
                    <div class="form-group col-md-2 col-sm-6">
                        <label>Bultos <span class="text-red">*</span></label>
                        <input type="number" min="1" step="1" class="form-control input-sm" id="greBultos">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>Motivo</label>
                        <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greMotivo"></select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Descripción del motivo</label>
                        <input type="text" class="form-control input-sm" id="greMotivoDesc" maxlength="100">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Observaciones (opcional)</label>
                        <input type="text" class="form-control input-sm" id="greObs" maxlength="250">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- DESTINATARIO -->
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-user"></i> Destinatario</h3></div>
                    <div class="box-body">
                        <div id="greDestBuscar">
                            <div class="form-group" style="margin-bottom:6px">
                                <label class="radio-inline"><input type="radio" name="greDestOrigen" value="CLIENTE" checked> Cliente</label>
                                <label class="radio-inline"><input type="radio" name="greDestOrigen" value="PROVEEDOR"> Proveedor</label>
                                <label class="radio-inline"><input type="radio" name="greDestOrigen" value="TALLER"> Taller</label>
                                <label class="radio-inline"><input type="radio" name="greDestOrigen" value="MANUAL"> A mano</label>
                            </div>
                            <div class="form-group gre-buscador">
                                <input type="text" class="form-control input-sm" id="greDestBuscador" placeholder="Buscar por nombre, RUC/DNI o código..." autocomplete="off">
                                <ul class="list-group gre-resultados" id="greDestResultados"></ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <label>Razón social / nombre</label>
                                <input type="text" class="form-control input-sm" id="greDestNombre" maxlength="100">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-4">
                                <label>Tipo doc.</label>
                                <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greDestTipoDoc">
                                    <option value="6">RUC</option>
                                    <option value="1">DNI</option>
                                    <option value="4">Carnet extr.</option>
                                    <option value="7">Pasaporte</option>
                                    <option value="0">Sin RUC</option>
                                    <option value="A">Céd. diplom.</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-4">
                                <label>Número</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="greDestDoc" maxlength="15">
                                    <span class="input-group-btn"><button type="button" class="btn btn-info" id="greBuscarDoc" title="Buscar RUC o DNI"><i class="fa fa-search"></i></button></span>
                                </div>
                            </div>
                            <div class="form-group col-sm-4">
                                <label>Correo (opc.)</label>
                                <input type="text" class="form-control input-sm" id="greDestEmail" maxlength="100">
                            </div>
                        </div>
                        <div class="checkbox" id="greGuardarTallerWrap" style="display:none;margin:0 0 6px">
                            <label><input type="checkbox" id="greGuardarTaller" checked> Recordar estos datos (y la dirección de llegada) para este taller</label>
                        </div>
                        <p class="text-muted" id="greAvisoMotivo04" style="display:none;margin:0">
                            <i class="fa fa-info-circle"></i> Motivo 04: el destinatario es la misma empresa.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TRANSPORTE -->
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-truck"></i> Transporte</h3></div>
                    <div class="box-body">
                        <div class="form-group" style="margin-bottom:6px">
                            <label class="radio-inline"><input type="radio" name="greModalidad" value="02" checked> Privado (propio)</label>
                            <label class="radio-inline"><input type="radio" name="greModalidad" value="01"> Público (agencia)</label>
                        </div>

                        <div id="grePrivado">
                            <div class="row">
                                <div class="form-group col-sm-7">
                                    <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greChoferSel"><option value="">Conductor guardado…</option></select>
                                </div>
                                <div class="form-group col-sm-5">
                                    <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greVehiculoSel"><option value="">Vehículo guardado…</option></select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-3">
                                    <label>Tipo doc.</label>
                                    <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greChoferTipoDoc">
                                        <option value="1">DNI</option><option value="4">Carnet</option>
                                        <option value="7">Pasaporte</option><option value="A">Céd. dipl.</option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-4"><label>Documento</label><input type="text" class="form-control input-sm" id="greChoferDoc" maxlength="11"></div>
                                <div class="form-group col-sm-5"><label>Licencia</label><input type="text" class="form-control input-sm" id="greChoferLicencia" maxlength="20"></div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-4"><label>Nombres</label><input type="text" class="form-control input-sm" id="greChoferNombres" maxlength="50"></div>
                                <div class="form-group col-sm-5"><label>Apellidos</label><input type="text" class="form-control input-sm" id="greChoferApellidos" maxlength="50"></div>
                                <div class="form-group col-sm-3"><label>Placa</label><input type="text" class="form-control input-sm" id="grePlaca" maxlength="8" style="text-transform:uppercase"></div>
                            </div>
                        </div>

                        <div id="grePublico" style="display:none">
                            <div class="form-group">
                                <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greAgenciaSel"><option value="">Agencia guardada…</option></select>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-4"><label>RUC</label><input type="text" class="form-control input-sm" id="greTranspRuc" maxlength="11"></div>
                                <div class="form-group col-sm-5"><label>Razón social</label><input type="text" class="form-control input-sm" id="greTranspNombre" maxlength="100"></div>
                                <div class="form-group col-sm-3"><label>MTC (opc.)</label><input type="text" class="form-control input-sm" id="greTranspMtc" maxlength="20"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PARTIDA / LLEGADA -->
        <div class="row">
            <?php foreach (array("par" => array("Punto de partida", "fa-map-marker"), "lle" => array("Punto de llegada", "fa-flag-checkered")) as $p => $t) { ?>
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa <?= $t[1]; ?>"></i> <?= $t[0]; ?></h3>
                        <?php if ($p === "par") { ?>
                            <button type="button" class="btn btn-xs btn-default pull-right" id="greUsarVasco">Usar domicilio Vasco</button>
                        <?php } ?>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-sm-12 gre-buscador">
                                <input type="text" class="form-control input-sm gre-ubigeo-buscador" data-p="<?= $p; ?>" placeholder="Buscar distrito o código de ubigeo..." autocomplete="off">
                                <ul class="list-group gre-resultados"></ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-12"><label>Dirección</label><input type="text" class="form-control input-sm" id="gre_<?= $p; ?>_direccion" maxlength="100"></div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-3"><label>Ubigeo</label><input type="text" class="form-control input-sm" id="gre_<?= $p; ?>_ubigeo" maxlength="6"></div>
                            <div class="form-group col-sm-3"><label>Depto.</label><input type="text" class="form-control input-sm" id="gre_<?= $p; ?>_dpto" maxlength="30"></div>
                            <div class="form-group col-sm-3"><label>Provincia</label><input type="text" class="form-control input-sm" id="gre_<?= $p; ?>_prov" maxlength="30"></div>
                            <div class="form-group col-sm-3"><label>Distrito</label><input type="text" class="form-control input-sm" id="gre_<?= $p; ?>_dist" maxlength="30"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

        <!-- ITEMS -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-cubes"></i> Bienes a transportar</h3>
                <span class="pull-right text-muted" id="greContador">0 ítems</span>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-5">
                        <ul class="nav nav-pills nav-pills-sm" id="greItemTabs">
                            <li class="active"><a href="#" data-tipo="modelo">Modelo</a></li>
                            <li><a href="#" data-tipo="articulo">Artículo</a></li>
                            <li><a href="#" data-tipo="mp">Materia prima</a></li>
                            <li><a href="#" data-tipo="manual">A mano</a></li>
                        </ul>
                    </div>
                    <div class="col-md-7">
                        <div class="gre-buscador" id="greItemBuscarWrap">
                            <input type="text" class="form-control input-sm" id="greItemBuscador" placeholder="Código o nombre (mín. 2 letras)..." autocomplete="off">
                            <ul class="list-group gre-resultados" id="greItemResultados"></ul>
                        </div>
                        <button type="button" class="btn btn-default btn-sm" id="greAgregarManual" style="display:none">
                            <i class="fa fa-plus"></i> Agregar ítem
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="margin-top:10px">
                    <table class="table table-bordered table-condensed" id="greTablaItems" style="margin-bottom:0">
                        <thead>
                            <tr><th style="width:32px">#</th><th style="width:78px">Origen</th><th style="width:120px">Código</th><th>Descripción</th>
                                <th style="width:150px">Unidad</th><th style="width:100px">Cantidad</th><th style="width:34px"></th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <p style="margin:10px 0 0">
                    <a href="#" id="greDocsToggle"><i class="fa fa-plus-square-o"></i> Documentos relacionados (opcional)</a>
                </p>
                <div id="greDocsBox" style="display:none;margin-top:8px">
                    <div class="row">
                        <div class="col-sm-3">
                            <select class="form-control input-sm selectpicker" data-live-search="true" data-width="100%" data-container="body" id="greDocTipo">
                                <option value="06">Otros</option><option value="02">Orden de entrega</option>
                                <option value="03">SCOP</option><option value="05">Constancia detracción</option>
                                <option value="01">DAM</option><option value="04">Manifiesto de carga</option>
                            </select>
                        </div>
                        <div class="col-sm-5"><input type="text" class="form-control input-sm" id="greDocNumero" maxlength="20" placeholder="Número (máx. 20)"></div>
                        <div class="col-sm-2"><button type="button" class="btn btn-default btn-sm" id="greDocAgregar">Agregar</button></div>
                    </div>
                    <ul class="list-inline" id="greDocsLista" style="margin-top:8px"></ul>
                </div>
            </div>
        </div>

        <div class="gre-barra">
            <a href="gre-manual" class="btn btn-default">Cancelar</a>
            <button type="button" class="btn btn-primary" id="greGuardar"><i class="fa fa-save"></i> Guardar guía</button>
        </div>

    </section>
</div>
