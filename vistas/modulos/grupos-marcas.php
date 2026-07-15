<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "grupos_marcas")) {
    denegarAccesoModulo();
    return;
}
$puedeEditar = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "grupos_marcas", "editar");
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Grupos de marcas</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Grupos de marcas</li>
        </ol>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-header with-border">
                <?php if ($puedeEditar) { ?>
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarGrupoMarcas">
                    Agregar grupo
                </button>
                <?php } ?>
                <p class="help-block" style="margin-top:10px;margin-bottom:0;">
                    Catálogo de grupos comerciales de marcas. Cada grupo agrupa marcas que un vendedor puede atender.
                    La columna <strong># Modelos</strong> cuenta solo modelos con estado <strong>Activo</strong> en el maestro.
                    La asignación a vendedores se administra en <a href="index.php?ruta=asignacion-grupos-marcas">Asignación de grupos</a>.
                    SQL: <code>docs/sql/asignacion-grupos-marcas-vendedores.sql</code>.
                </p>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive tablaGruposMarcas" width="100%">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Marcas</th>
                            <th># Marcas</th>
                            <th># Modelos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

</div>

<div id="modalMarcasGrupo" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#00a65a; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Marcas del grupo <span id="tituloMarcasGrupo"></span></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="idGrupoMarcasModal">
                <?php if ($puedeEditar) { ?>
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-sm-8">
                        <select class="form-control" id="selectMarcaAgregarGrupo">
                            <option value="">— Seleccionar marca —</option>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <button type="button" class="btn btn-success btn-block" id="btnAgregarMarcaGrupo">
                            <i class="fa fa-plus"></i> Agregar marca
                        </button>
                    </div>
                </div>
                <?php } ?>
                <table class="table table-bordered table-striped" id="tablaMarcasGrupo">
                    <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Desde</th>
                            <?php if ($puedeEditar) { ?><th style="width:80px;">Quitar</th><?php } ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php if ($puedeEditar) { ?>
<div id="modalAgregarGrupoMarcas" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAgregarGrupoMarcas" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Agregar grupo de marcas</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" class="form-control input-lg" name="codigo" required maxlength="30" placeholder="Ej: X" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control input-lg" name="nombre" required maxlength="100" placeholder="Nombre del grupo">
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2" placeholder="Opcional"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" name="estado">
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalEditarGrupoMarcas" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarGrupoMarcas" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar grupo de marcas</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editarIdGrupoMarcas">
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" class="form-control input-lg" id="editarCodigoGrupoMarcas" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control input-lg" name="nombre" id="editarNombreGrupoMarcas" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="descripcion" id="editarDescripcionGrupoMarcas" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" name="estado" id="editarEstadoGrupoMarcas">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<script>
window.document.title = "Grupos de marcas";
window.gruposMarcasPuedeEditar = <?php echo $puedeEditar ? "true" : "false"; ?>;
</script>
