<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales")) {
    denegarAccesoModulo();
    return;
}
$puedeEditarZonas = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "zonas_comerciales", "editar");
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Zonas comerciales</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Zonas comerciales</li>
        </ol>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-header with-border">
                <?php if ($puedeEditarZonas) { ?>
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarZonaComercial">
                    Agregar zona
                </button>
                <?php } ?>
                <p class="help-block" style="margin-top:10px;margin-bottom:0;">
                    Catálogo, ubigeos, override cliente/grupo y <strong>vendedores por zona</strong> (varios por zona; un vendedor en varias).
                    Botón verde <i class="fa fa-users"></i> = cobertura. SQL Fase 2: <code>docs/sql/zonas-comerciales-fase2.sql</code>.
                </p>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive tablaZonasComerciales" width="100%">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Macrozona</th>
                            <th>Ubigeos</th>
                            <th>Vendedores</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Clientes por revisar zona</h3>
                <p class="help-block" style="margin:8px 0 0;">
                    Sin zona automática, o distrito La Victoria (candidatos a <strong>Zona Económica / Gamarra</strong>).
                    Cascada: override cliente → zona del grupo → ubigeo. Edición solo usuarios autorizados.
                </p>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive tablaZonasPorRevisar" width="100%">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Distrito</th>
                            <th>Grupo</th>
                            <th>Zona grupo</th>
                            <th>Zona ubigeo</th>
                            <th>Motivo</th>
                            <th>Asignar</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

</div>

<?php if ($puedeEditarZonas) { ?>
<!-- MODAL AGREGAR -->
<div id="modalAgregarZonaComercial" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAgregarZonaComercial" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Agregar zona comercial</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" class="form-control input-lg" name="codigo" required maxlength="30" placeholder="Ej: LIM_NORTE" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control input-lg" name="nombre" required maxlength="120" placeholder="Nombre de la zona">
                        </div>
                        <div class="form-group">
                            <label>Macrozona</label>
                            <select class="form-control" name="macrozona">
                                <option value="lima" selected>Lima y alrededores</option>
                                <option value="peru_norte">Norte del Perú</option>
                                <option value="peru_sur">Sur del Perú</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Orden</label>
                                    <input type="number" class="form-control" name="orden" value="0" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Color</label>
                                    <input type="color" class="form-control" name="color" value="#3c8dbc" style="height:34px;padding:2px;">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select class="form-control" name="estado">
                                        <option value="1" selected>Activa</option>
                                        <option value="0">Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>
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

<!-- MODAL EDITAR -->
<div id="modalEditarZonaComercial" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarZonaComercial" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar zona comercial</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <input type="hidden" name="id" id="editarIdZona">
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" class="form-control input-lg" id="editarCodigoZona" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control input-lg" name="nombre" id="editarNombreZona" required maxlength="120">
                        </div>
                        <div class="form-group">
                            <label>Macrozona</label>
                            <select class="form-control" name="macrozona" id="editarMacrozonaZona">
                                <option value="lima">Lima y alrededores</option>
                                <option value="peru_norte">Norte del Perú</option>
                                <option value="peru_sur">Sur del Perú</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" name="descripcion" id="editarDescripcionZona" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Orden</label>
                                    <input type="number" class="form-control" name="orden" id="editarOrdenZona" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Color</label>
                                    <input type="color" class="form-control" name="color" id="editarColorZona" style="height:34px;padding:2px;">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select class="form-control" name="estado" id="editarEstadoZona">
                                        <option value="1">Activa</option>
                                        <option value="0">Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>
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

<!-- MODAL UBIGEOS -->
<div id="modalUbigeosZona" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Ubigeos de <span id="tituloUbigeosZona"></span></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="idZonaUbigeos">
                <?php if ($puedeEditarZonas) { ?>
                <div class="form-group">
                    <label>Buscar ubigeo para asignar</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="buscarUbigeoZona" placeholder="Distrito, provincia o código...">
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button" id="btnBuscarUbigeoZona"><i class="fa fa-search"></i></button>
                        </span>
                    </div>
                    <div id="resultadosBusquedaUbigeo" style="margin-top:8px;max-height:160px;overflow:auto;"></div>
                </div>
                <hr>
                <?php } ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed" id="tablaUbigeosAsignados">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Departamento</th>
                                <th>Provincia</th>
                                <th>Distrito</th>
                                <?php if ($puedeEditarZonas) { ?><th></th><?php } ?>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VENDEDORES -->
<div id="modalVendedoresZona" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#00a65a; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Vendedores de <span id="tituloVendedoresZona"></span></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="idZonaVendedores">
                <?php if ($puedeEditarZonas) { ?>
                <div class="form-group">
                    <label>Agregar vendedor</label>
                    <div class="input-group">
                        <select class="form-control selectpicker" id="selectVendedorZonaDisponible"
                                data-live-search="true" data-size="8" title="Seleccionar vendedor activo…">
                            <option value="">Seleccionar…</option>
                        </select>
                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="button" id="btnAsignarVendedorZona">
                                <i class="fa fa-plus"></i> Asignar
                            </button>
                        </span>
                    </div>
                    <p class="help-block">Solo vendedores activos (<code>estado_decisiones = 1</code>), ordenados por código. El mismo puede estar en varias zonas.</p>
                </div>
                <hr>
                <?php } ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed" id="tablaVendedoresAsignados">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <?php if ($puedeEditarZonas) { ?><th></th><?php } ?>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
