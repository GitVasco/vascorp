<?php
$siguienteCodigoGrupo = ControladorGruposEmpresariales::ctrSiguienteCodigoGrupo();
?>
<div class="content-wrapper">

    <section class="content-header">

        <h1>Administrar grupos empresariales</h1>

        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Grupos empresariales</li>
        </ol>

    </section>

    <section class="content">

        <div class="box">

            <div class="box-header with-border">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarGrupo">
                    Agregar grupo
                </button>
                <p class="help-block" style="margin-top:10px;margin-bottom:0;">
                    Agrupa clientes que compran con distintos nombres o RUC bajo una misma empresa.
                </p>
            </div>

            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive tablaGruposEmpresariales" width="100%">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Clientes</th>
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

<!-- MODAL AGREGAR -->
<div id="modalAgregarGrupo" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Agregar grupo empresarial</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                <input type="text" class="form-control input-lg" id="nuevoCodigoGrupo" value="<?php echo htmlspecialchars($siguienteCodigoGrupo); ?>" readonly>
                            </div>
                            <p class="help-block">El código se genera automáticamente al guardar.</p>
                        </div>
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-building"></i></span>
                                <input type="text" class="form-control input-lg" name="nuevoNombreGrupo" placeholder="Nombre del grupo empresarial" required maxlength="150">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <textarea class="form-control" name="nuevaDescripcionGrupo" rows="3" placeholder="Notas sobre el grupo"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar grupo</button>
                </div>
            </form>
            <?php
            $crearGrupo = new ControladorGruposEmpresariales();
            $crearGrupo->ctrCrearGrupo();
            ?>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditarGrupo" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form role="form" method="post">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar grupo empresarial</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <input type="hidden" id="idGrupo" name="idGrupo">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                <input type="text" class="form-control input-lg" id="editarCodigoGrupo" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-building"></i></span>
                                <input type="text" class="form-control input-lg" name="editarNombreGrupo" id="editarNombreGrupo" required maxlength="150">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" name="editarDescripcionGrupo" id="editarDescripcionGrupo" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select class="form-control" name="editarEstadoGrupo" id="editarEstadoGrupo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
            <?php
            $editarGrupo = new ControladorGruposEmpresariales();
            $editarGrupo->ctrEditarGrupo();
            ?>
        </div>
    </div>
</div>

<!-- MODAL CLIENTES DEL GRUPO -->
<div id="modalClientesGrupo" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#00a65a; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Clientes del grupo: <span id="tituloGrupoClientes"></span></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="codigoGrupoActivo">

                <div class="row" style="margin-bottom:15px;">
                    <div class="col-md-8">
                        <select class="form-control" id="selectClienteAsignar">
                            <option value="">Seleccionar cliente sin grupo...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-success btn-block" id="btnAsignarClienteGrupo">
                            <i class="fa fa-plus"></i> Agregar al grupo
                        </button>
                    </div>
                </div>

                <table class="table table-bordered table-striped" id="tablaClientesGrupo">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Teléfono</th>
                            <th width="80">Quitar</th>
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

<?php
$eliminarGrupo = new ControladorGruposEmpresariales();
$eliminarGrupo->ctrEliminarGrupo();
?>

<script>
window.document.title = "Grupos empresariales";
</script>
