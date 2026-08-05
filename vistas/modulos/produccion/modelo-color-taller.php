<?php
if (!isset($_SESSION["produccion"]) || (int) $_SESSION["produccion"] !== 1) {
    echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Sin permiso de producción.</div></section></div>';
    return;
}
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>
            Taller por modelo / color
            <small>Configuración</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Producción</li>
            <li class="active">Taller por modelo / color</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-solid" style="margin-bottom:10px;">
            <div class="box-body" style="padding:10px 15px;">
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
                    <strong style="margin-right:4px;">Artículos por taller:</strong>
                    <span id="resumenArticulosPorTallerMct" class="text-muted">Cargando…</span>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarModeloColorTaller">
                    Nueva asignación
                </button>
                <a class="btn btn-default" href="ajax/plantilla-modelo-color-taller.csv.php">
                    <i class="fa fa-download"></i> Plantilla Excel
                </a>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalImportarModeloColorTaller">
                    <i class="fa fa-upload"></i> Subir Excel
                </button>
                <p class="help-block" style="margin-top:10px;margin-bottom:0;">
                    Aquí configuras a qué taller va cada modelo y, si hace falta, cada color.
                    Puedes agregar uno por uno, o descargar la plantilla, completarla en Excel y subirla.
                    Si ya existe esa combinación, se actualiza el taller.
                </p>
            </div>
            <div class="box-body">
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-sm-3">
                        <label>Modelo</label>
                        <select class="form-control selectpicker filtroModeloColorTaller" id="filtroModeloMct"
                            data-live-search="true" data-size="10" title="Todos">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Color</label>
                        <select class="form-control selectpicker filtroModeloColorTaller" id="filtroColorMct"
                            data-live-search="true" data-size="10" title="Todos">
                            <option value="">Todos</option>
                            <option value="__SIN_COLOR__">Solo generales (sin color)</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>Taller</label>
                        <select class="form-control selectpicker filtroModeloColorTaller" id="filtroSectorMct"
                            data-live-search="true" data-size="10" title="Todos">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>Estado</label>
                        <select class="form-control selectpicker filtroModeloColorTaller" id="filtroEstadoMct"
                            data-size="5" title="Todos">
                            <option value="">Todos</option>
                            <option value="1" selected>Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-default btn-block" id="btnRefrescarMct">
                            <i class="fa fa-refresh"></i> Actualizar
                        </button>
                    </div>
                </div>

                <table class="table table-bordered table-striped dt-responsive tablaModeloColorTaller" width="100%">
                    <thead>
                        <tr>
                            <th>Modelo</th>
                            <th>Color</th>
                            <th>Taller</th>
                            <th>Estado</th>
                            <th>Observación</th>
                            <th style="width:80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

</div>

<div id="modalAgregarModeloColorTaller" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAgregarModeloColorTaller" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Nueva asignación — color por color</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Modelo</label>
                                <select class="form-control selectpicker" name="modelo" id="nuevoModeloMct"
                                    data-live-search="true" data-size="10" data-container="body"
                                    title="— Seleccionar —" required>
                                    <option value="">— Seleccionar —</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Taller por defecto <small class="text-muted">(opcional)</small></label>
                                <div class="input-group">
                                    <select class="form-control selectpicker" id="nuevoSectorDefaultMct"
                                        data-live-search="true" data-size="10" data-container="body"
                                        title="— Elegir taller —">
                                        <option value="">— Elegir taller —</option>
                                    </select>
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" id="btnAplicarTallerDefaultMct" title="Aplicar a colores sin taller">
                                            Aplicar
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Observación <small class="text-muted">(se aplica a las filas que guardes)</small></label>
                        <input type="text" class="form-control" name="observacion" id="nuevaObsMct" maxlength="255">
                    </div>

                    <div id="bloqueReglaGeneralMct" class="well well-sm" style="display:none;margin-bottom:12px;">
                        <label style="display:block;margin-bottom:6px;">
                            Regla general del modelo <small class="text-muted">(sin color específico)</small>
                        </label>
                        <div class="row">
                            <div class="col-sm-7">
                                <select class="form-control selectpicker" id="nuevoSectorGeneralMct"
                                    data-live-search="true" data-size="8" data-container="body"
                                    title="— Sin regla general —">
                                    <option value="">— Sin regla general —</option>
                                </select>
                            </div>
                            <div class="col-sm-5">
                                <p class="help-block" id="estadoReglaGeneralMct" style="margin:7px 0 0;">—</p>
                            </div>
                        </div>
                    </div>

                    <p class="help-block" id="ayudaColoresModeloMct">
                        Elige un modelo para ver sus colores uno por uno y asignar taller a cada color.
                    </p>
                    <div class="table-responsive" style="max-height:360px;overflow:auto;">
                        <table class="table table-bordered table-condensed" id="tablaColoresNuevoMct" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th style="width:90px;">Código</th>
                                    <th>Color</th>
                                    <th style="width:220px;">Taller</th>
                                    <th style="width:120px;">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="bodyColoresNuevoMct">
                                <tr class="text-muted">
                                    <td colspan="4" class="text-center">Selecciona un modelo…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarNuevoMct" disabled>
                        Guardar colores con taller
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalEditarModeloColorTaller" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarModeloColorTaller" role="form">
                <div class="modal-header" style="background:#f39c12; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar asignación</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editarIdMct">
                    <div class="form-group">
                        <label>Modelo</label>
                        <select class="form-control selectpicker" name="modelo" id="editarModeloMct"
                            data-live-search="true" data-size="10" data-container="body"
                            title="— Seleccionar —" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color a editar</label>
                        <p class="form-control-static" id="editarColorActualMct" style="font-weight:600;margin-bottom:6px;">—</p>
                        <select class="form-control selectpicker" name="cod_color" id="editarColorMct"
                            data-live-search="true" data-size="10" data-container="body"
                            title="— Todo el modelo —">
                            <option value="">— Todo el modelo —</option>
                        </select>
                        <p class="help-block" style="margin-bottom:0;">
                            Este es el color de la fila que estás editando. Solo cámbialo si quieres pasar la asignación a otro color.
                        </p>
                    </div>
                    <div class="form-group">
                        <label>Taller (sector)</label>
                        <select class="form-control selectpicker" name="cod_sector" id="editarSectorMct"
                            data-live-search="true" data-size="10" data-container="body"
                            title="— Seleccionar —" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control selectpicker" name="estado" id="editarEstadoMct"
                            data-container="body" data-size="5">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Observación</label>
                        <input type="text" class="form-control" name="observacion" id="editarObsMct" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalImportarModeloColorTaller" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formImportarModeloColorTaller" enctype="multipart/form-data">
                <div class="modal-header" style="background:#00a65a; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Importar taller por modelo / color</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Archivo Excel / CSV</label>
                        <input type="file" class="form-control" id="archivoModeloColorTaller" name="archivo"
                            accept=".csv,.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        <p class="help-block">
                            Usa la plantilla: indica el modelo, el taller y, si aplica, el color (ejemplo: 01, 02, 34).
                            Si dejas el color vacío, la regla vale para todo el modelo.
                            Antes de confirmar puedes previsualizar qué se va a crear o actualizar.
                        </p>
                    </div>
                    <div id="resumenImportacionMct" class="alert" style="display:none;"></div>
                    <div class="table-responsive" id="contenedorPreviewMct" style="display:none;max-height:350px;overflow:auto;">
                        <table class="table table-bordered table-condensed">
                            <thead>
                                <tr>
                                    <th>Fila</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Taller</th>
                                    <th>Acción</th>
                                    <th>Resultado</th>
                                </tr>
                            </thead>
                            <tbody id="previewImportacionMctBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="btnPrevisualizarMct">
                        <i class="fa fa-search"></i> Previsualizar
                    </button>
                    <button type="button" class="btn btn-success" id="btnConfirmarImportacionMct" disabled>
                        <i class="fa fa-check"></i> Confirmar importación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
body .bootstrap-select .dropdown-menu {
    z-index: 2060 !important;
}
#modalAgregarModeloColorTaller .bootstrap-select,
#modalEditarModeloColorTaller .bootstrap-select {
    width: 100% !important;
}
#modalAgregarModeloColorTaller .bootstrap-select > .dropdown-toggle,
#modalEditarModeloColorTaller .bootstrap-select > .dropdown-toggle {
    width: 100%;
}
#tablaColoresNuevoMct td {
    vertical-align: middle;
}
</style>
