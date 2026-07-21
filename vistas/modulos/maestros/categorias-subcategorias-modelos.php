<?php
if (!isset($_SESSION["maestros"]) || (int) $_SESSION["maestros"] !== 1) {
    denegarAccesoModulo();
    return;
}
$puedeEditarCatSubModelos = true;
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Categorías y subcategorías de modelos</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li>Maestros</li>
            <li class="active">Categorías / subcategorías</li>
        </ol>
    </section>

    <section class="content">
        <div class="alert alert-info">
            Catálogo usado por la <a href="index.php?ruta=categorias-modelos">clasificación de modelos</a>
            y el ranking de la ficha gerencial. El <strong>código se genera solo</strong> al crear.
            No se puede desactivar si hay modelos activos asignados.
            Eliminar subcategoría solo si no tiene modelos asignados ni historial; si ya se usó, desactívela.
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tabCatModelos" data-toggle="tab">Categorías</a></li>
                        <li><a href="#tabSubCatModelos" data-toggle="tab">Subcategorías</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tabCatModelos">
                            <div class="box box-solid" style="border:0;box-shadow:none;margin:0;">
                                <div class="box-header with-border" style="padding-left:0;">
                                    <?php if ($puedeEditarCatSubModelos) { ?>
                                    <button type="button" class="btn btn-primary" id="btnNuevaCategoriaModelo">
                                        <i class="fa fa-plus"></i> Nueva categoría
                                    </button>
                                    <?php } ?>
                                </div>
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-bordered table-striped" id="tablaCategoriasModelo" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Nombre</th>
                                                <th>Orden</th>
                                                <th>Subcategorías</th>
                                                <th>Modelos activos</th>
                                                <th>Estado</th>
                                                <th style="width:110px;white-space:nowrap;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="tabSubCatModelos">
                            <div class="box box-solid" style="border:0;box-shadow:none;margin:0;">
                                <div class="box-header with-border" style="padding-left:0;">
                                    <?php if ($puedeEditarCatSubModelos) { ?>
                                    <button type="button" class="btn btn-primary" id="btnNuevaSubcategoriaModelo">
                                        <i class="fa fa-plus"></i> Nueva subcategoría
                                    </button>
                                    <?php } ?>
                                    <div class="form-inline pull-right">
                                        <label for="filtroCatSubAdmin">Categoría</label>
                                        <select class="form-control input-sm" id="filtroCatSubAdmin">
                                            <option value="">Todas</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-bordered table-striped" id="tablaSubcategoriasModelo" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Nombre</th>
                                                <th>Categoría</th>
                                                <th>Orden</th>
                                                <th>Modelos activos</th>
                                                <th>Estado</th>
                                                <th style="width:110px;white-space:nowrap;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4" id="catSubModelosGraficos">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Por categoría</h3>
                    </div>
                    <div class="box-body" style="height:240px;">
                        <canvas id="chartCatModelos" height="200"></canvas>
                        <p class="text-muted text-center" id="chartCatModelosVacio" style="display:none;margin-top:40px;">Sin datos aún</p>
                    </div>
                    <div class="box-footer text-center">
                        <span id="catSubResumenConteos" class="text-muted" style="font-size:12px;">—</span>
                    </div>
                </div>
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Por subcategoría</h3>
                    </div>
                    <div class="box-body" style="min-height:300px;">
                        <div style="height:200px;position:relative;">
                            <canvas id="chartSubModelos" height="180"></canvas>
                        </div>
                        <p class="text-muted text-center" id="chartSubModelosVacio" style="display:none;margin-top:40px;">Sin datos aún</p>
                        <ul id="leyendaSubModelos" class="list-unstyled" style="margin:10px 0 0;font-size:11px;max-height:140px;overflow-y:auto;"></ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php if ($puedeEditarCatSubModelos) { ?>
<div class="modal fade" id="modalCategoriaModelo" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCategoriaModelo">
                <div class="modal-header" style="background:#3c8dbc;color:#fff;">
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                    <h4 class="modal-title" id="tituloModalCategoriaModelo">Categoría</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="catModeloId" name="id" value="0">
                    <div class="form-group">
                        <label for="catModeloNombre">Nombre</label>
                        <input type="text" class="form-control" id="catModeloNombre" name="nombre" maxlength="100" required>
                    </div>
                    <div class="form-group" id="grupoCatModeloCodigo">
                        <label>Código</label>
                        <input type="text" class="form-control" id="catModeloCodigo" readonly>
                        <p class="help-block" id="ayudaCatModeloCodigo">Se genera automáticamente al guardar.</p>
                    </div>
                    <div class="form-group">
                        <label for="catModeloOrden">Orden</label>
                        <input type="number" class="form-control" id="catModeloOrden" name="orden" min="0" max="9999" value="0">
                    </div>
                    <div class="form-group">
                        <label for="catModeloEstado">Estado</label>
                        <select class="form-control" id="catModeloEstado" name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSubcategoriaModelo" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formSubcategoriaModelo">
                <div class="modal-header" style="background:#00a65a;color:#fff;">
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                    <h4 class="modal-title" id="tituloModalSubcategoriaModelo">Subcategoría</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="subModeloId" name="id" value="0">
                    <div class="form-group">
                        <label for="subModeloCategoria">Categoría</label>
                        <select class="form-control" id="subModeloCategoria" name="id_categoria" required></select>
                    </div>
                    <div class="form-group">
                        <label for="subModeloNombre">Nombre</label>
                        <input type="text" class="form-control" id="subModeloNombre" name="nombre" maxlength="100" required>
                    </div>
                    <div class="form-group" id="grupoSubModeloCodigo">
                        <label>Código</label>
                        <input type="text" class="form-control" id="subModeloCodigo" readonly>
                        <p class="help-block" id="ayudaSubModeloCodigo">Se genera automáticamente al guardar (categoría + nombre).</p>
                    </div>
                    <div class="form-group">
                        <label for="subModeloOrden">Orden</label>
                        <input type="number" class="form-control" id="subModeloOrden" name="orden" min="0" max="9999" value="0">
                    </div>
                    <div class="form-group">
                        <label for="subModeloEstado">Estado</label>
                        <select class="form-control" id="subModeloEstado" name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<div class="modal fade" id="modalModelosSubcategoria" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#605ca8;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title">
                    Modelos en <span id="tituloModelosSubcategoria">—</span>
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="resumenModelosSubcategoria" style="margin-top:0;">—</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed" id="tablaModelosSubcategoria">
                        <thead>
                            <tr>
                                <th style="width:50px;"></th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Marca</th>
                                <th>Tipo</th>
                                <th>Línea</th>
                                <th>Estado</th>
                                <th>Asignado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="8" class="text-center text-muted">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-default" id="linkClasificarDesdeSub" target="_self">Ir a clasificación</a>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
window.CAT_SUB_MODELOS_PUEDE_EDITAR = <?php echo $puedeEditarCatSubModelos ? "true" : "false"; ?>;
</script>
