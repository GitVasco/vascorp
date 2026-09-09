<?php
if (!isset($_SESSION["materiaprima"]) || (int) $_SESSION["materiaprima"] !== 1) {
	if (function_exists("denegarAccesoModulo")) {
		denegarAccesoModulo();
	} else {
		echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Sin permiso</div></section></div>';
	}
	return;
}
$puedeVerReceta = isset($_SESSION["tarjetas"]) && (int) $_SESSION["tarjetas"] === 1;
?>
<div class="content-wrapper mpr-rec-page">

    <section class="content-header">
        <h1>
            MP en recetas
            <small>Materias primas usadas en explosión de materiales</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Materia Prima</li>
            <li class="active">MP en recetas</li>
        </ol>
    </section>

    <section class="content">

        <div class="box box-solid">
            <div class="box-header with-border mpr-rec-toolbar">
                <div class="mpr-rec-filtros">
                    <div class="mpr-rec-filtro">
                        <label for="mprRecLinea">Línea</label>
                        <select class="form-control input-sm selectpicker" id="mprRecLinea" data-live-search="true" data-width="100%" title="Todas">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="mpr-rec-filtro mpr-rec-filtro-sub">
                        <label for="mprRecSublinea">Sublínea</label>
                        <select class="form-control input-sm selectpicker" id="mprRecSublinea" data-live-search="true" data-width="100%" title="Elegí una línea" disabled>
                            <option value="">Elegí una línea</option>
                        </select>
                    </div>
                    <div class="mpr-rec-filtro mpr-rec-filtro-mp">
                        <label for="mprRecMp">Materia prima</label>
                        <select class="form-control input-sm selectpicker" id="mprRecMp" data-live-search="true" data-width="100%" title="Elegí línea y sublínea" disabled>
                            <option value="">Elegí línea y sublínea</option>
                        </select>
                    </div>
                    <div class="mpr-rec-filtro mpr-rec-filtro-costo">
                        <label for="mprRecCosto">Costo</label>
                        <select class="form-control input-sm" id="mprRecCosto">
                            <option value="">Todos</option>
                            <option value="con">Con costo</option>
                            <option value="sin">Sin costo</option>
                        </select>
                    </div>
                    <div class="mpr-rec-acciones">
                        <label>&nbsp;</label>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm" id="mprRecBtnPlantilla" title="Descarga la lista filtrada para completar costos">
                                <i class="fa fa-download"></i> Plantilla
                            </button>
                            <button type="button" class="btn btn-success btn-sm" id="mprRecBtnSubir" data-toggle="modal" data-target="#modalMpRecetasImportar">
                                <i class="fa fa-upload"></i> Subir costos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive mpr-rec-tabla-wrap">
                <table class="table table-bordered table-striped table-condensed" id="tablaMpRecetas" width="100%">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fábrica</th>
                            <th>Descripción</th>
                            <th>Color</th>
                            <th>Unidad</th>
                            <th class="text-right">Stock</th>
                            <th class="text-right">Costo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>
        </div>

    </section>
</div>

<div id="modalMpRecetasDetalle" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Recetas de <span id="mprRecDetCodigo"></span>
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="mprRecDetNombre"></p>
                <div id="mprRecDetLoading" class="text-center text-muted" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Cargando…
                </div>
                <div id="mprRecDetEmpty" class="text-muted" style="display:none;">
                    No hay recetas activas con esta MP.
                </div>
                <div class="table-responsive" id="mprRecDetWrap" style="display:none;">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Modelo</th>
                                <th>Nombre</th>
                                <th>Versión</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="mprRecDetBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="modalMpRecetasImportar" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Subir costos desde Excel</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Bajá la plantilla (se abre con Excel), completá la columna <strong>costo</strong>
                    y subí el mismo archivo. No borres la columna <strong>codigo</strong>.
                    Las filas sin costo o en 0 se omiten; no vuelven a dejar la ficha en 0.
                </p>
                <div class="form-group">
                    <label for="mprRecArchivo">Archivo Excel / CSV</label>
                    <input type="file" class="form-control" id="mprRecArchivo" accept=".csv,.xls,.xlsx">
                </div>
                <div id="mprRecImportMsg" class="alert" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="mprRecBtnImportar">
                    <i class="fa fa-check"></i> Actualizar costos
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalMpRecetasCosto" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.9;">&times;</button>
                <h4 class="modal-title">Editar costo</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mprRecCostoCodpro" value="">
                <div class="form-group">
                    <label>Código</label>
                    <input type="text" class="form-control" id="mprRecCostoCodigo" readonly>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" class="form-control" id="mprRecCostoDespro" readonly>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" class="form-control" id="mprRecCostoColor" readonly>
                </div>
                <div class="form-group">
                    <label>Costo actual</label>
                    <input type="text" class="form-control" id="mprRecCostoActual" readonly>
                </div>
                <div class="form-group">
                    <label for="mprRecCostoNuevo">Nuevo costo</label>
                    <input type="number" class="form-control" id="mprRecCostoNuevo" min="0" step="any" placeholder="0.0000">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="mprRecBtnGuardarCosto">
                    <i class="fa fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="mprRecPuedeVerReceta" value="<?php echo $puedeVerReceta ? '1' : '0'; ?>">
