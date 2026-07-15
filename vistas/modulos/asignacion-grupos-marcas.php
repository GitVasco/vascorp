<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "asignacion_grupos_marcas")) {
    denegarAccesoModulo();
    return;
}
$puedeEditar = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "asignacion_grupos_marcas", "editar");
$fechaHoy = date("Y-m-d");
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Asignación de grupos de marcas</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Asignación de grupos</li>
        </ol>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-header with-border">
                <?php if ($puedeEditar) { ?>
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarAsignacionGrupo">
                    Nueva asignación
                </button>
                <?php } ?>
                <p class="help-block" style="margin-top:10px;margin-bottom:0;">
                    Vigencia por vendedor y grupo. Cerrar una asignación con fecha de fin; no se borra el historial.
                    Grupos en <a href="index.php?ruta=grupos-marcas">Grupos de marcas</a>.
                </p>
            </div>
            <div class="box-body">
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-sm-2">
                        <label>Vendedor</label>
                        <select class="form-control filtroAsignacionGrupo" id="filtroVendedorAsignacion">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>Grupo</label>
                        <select class="form-control filtroAsignacionGrupo" id="filtroGrupoAsignacion">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>Marca incluida</label>
                        <select class="form-control filtroAsignacionGrupo" id="filtroMarcaAsignacion">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>Vigente al día</label>
                        <input type="date" class="form-control filtroAsignacionGrupo" id="filtroFechaAsignacion" value="<?php echo htmlspecialchars($fechaHoy, ENT_QUOTES, "UTF-8"); ?>">
                    </div>
                    <div class="col-sm-2">
                        <label>Estado</label>
                        <select class="form-control filtroAsignacionGrupo" id="filtroVigenteAsignacion">
                            <option value="">Todos</option>
                            <option value="1">Solo vigentes</option>
                            <option value="0">No vigentes</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-default btn-block" id="btnRefrescarAsignacionGrupo">
                            <i class="fa fa-refresh"></i> Actualizar
                        </button>
                    </div>
                </div>

                <table class="table table-bordered table-striped dt-responsive tablaAsignacionGruposMarcas" width="100%">
                    <thead>
                        <tr>
                            <th>Vendedor</th>
                            <th>Grupo</th>
                            <th>Marcas</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Vigencia</th>
                            <th>Observación</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

</div>

<?php if ($puedeEditar) { ?>
<div id="modalAgregarAsignacionGrupo" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAgregarAsignacionGrupo" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Nueva asignación vendedor–grupo</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Vendedor</label>
                        <select class="form-control" name="cod_vendedor" id="nuevoVendedorAsignacion" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Grupos (se crea una fila por grupo)</label>
                        <select class="form-control" name="ids_grupos[]" id="nuevoGruposAsignacion" multiple required size="6" style="height:auto;">
                        </select>
                        <p class="help-block">Mantén Ctrl/Cmd para seleccionar varios.</p>
                    </div>
                    <div class="form-group">
                        <label>Fecha de inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio" id="nuevaFechaInicioAsignacion" required
                            value="<?php echo date("Y-m-01"); ?>">
                    </div>
                    <div class="form-group">
                        <label>Observación</label>
                        <input type="text" class="form-control" name="observacion" maxlength="255" placeholder="Opcional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Crear asignaciones</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalCerrarAsignacionGrupo" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="formCerrarAsignacionGrupo" role="form">
                <div class="modal-header" style="background:#dd4b39; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Cerrar asignación</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cerrarIdAsignacion">
                    <p id="cerrarTextoAsignacion" class="text-muted"></p>
                    <div class="form-group">
                        <label>Fecha de fin (inclusive)</label>
                        <input type="date" class="form-control" name="fecha_fin" id="cerrarFechaFinAsignacion" required
                            value="<?php echo htmlspecialchars($fechaHoy, ENT_QUOTES, "UTF-8"); ?>">
                    </div>
                    <p class="help-block">La venta cuenta hasta esta fecha inclusive. Desde el día siguiente deja de aplicar.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Cerrar vigencia</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<script>
window.document.title = "Asignación de grupos de marcas";
window.asignacionGruposPuedeEditar = <?php echo $puedeEditar ? "true" : "false"; ?>;
</script>
