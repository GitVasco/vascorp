<?php
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo '<script>window.location = "inicio";</script>';
    return;
}
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Categorías comerciales</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Categorías comerciales</li>
        </ol>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-header with-border">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCategoriaComercial">
                    Agregar categoría
                </button>
                <p class="help-block" style="margin-top:10px;margin-bottom:0;">
                    Configure categorías, monto anual mínimo y descuentos. La asignación a clientes/grupos se hace aparte.
                </p>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive tablaCategoriasComerciales" width="100%">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Monto ventas</th>
                            <th>Dto. venta</th>
                            <th>Dto. pronto pago</th>
                            <th>Clientes</th>
                            <th>Grupos</th>
                            <th>Por revisar</th>
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
<div id="modalAgregarCategoriaComercial" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAgregarCategoriaComercial" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Agregar categoría comercial</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" class="form-control input-lg" id="nuevoCodigoCategoria" name="codigo" required maxlength="20" placeholder="Ej: DIST" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control input-lg" id="nuevoNombreCategoria" name="nombre" required maxlength="100" placeholder="Nombre de la categoría">
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" id="nuevaDescripcionCategoria" name="descripcion" rows="2" placeholder="Opcional"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Orden</label>
                                    <input type="number" class="form-control" id="nuevoOrdenCategoria" name="orden" value="0" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Color</label>
                                    <input type="color" class="form-control" id="nuevoColorCategoria" name="color" value="#3c8dbc" style="height:34px;padding:2px;">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select class="form-control" id="nuevoEstadoCategoria" name="estado">
                                        <option value="1" selected>Activa</option>
                                        <option value="0">Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h4 style="margin-top:0;">Requisito</h4>
                        <div class="form-group">
                            <label>Monto mínimo anual de compras (PEN)</label>
                            <input type="number" class="form-control" id="nuevoMontoAnualCategoria" name="monto_compras_anual" min="0" step="0.01" placeholder="Vacío = aún no definido">
                        </div>
                        <hr>
                        <h4 style="margin-top:0;">Beneficios</h4>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Descuento venta (%)</label>
                                    <input type="number" class="form-control" id="nuevoDtoVentaCategoria" name="descuento_venta_pct" min="0" max="100" step="0.01" placeholder="Vacío = no definido">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Descuento pronto pago (%)</label>
                                    <input type="number" class="form-control" id="nuevoDtoProntoCategoria" name="descuento_pronto_pago_pct" min="0" max="100" step="0.01" placeholder="Vacío = no definido">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarNuevaCategoria">Guardar categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditarCategoriaComercial" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarCategoriaComercial" role="form">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar categoría comercial</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <input type="hidden" id="idCategoriaComercial" name="id">
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" class="form-control input-lg" id="editarCodigoCategoria" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control input-lg" id="editarNombreCategoria" name="nombre" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" id="editarDescripcionCategoria" name="descripcion" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Orden</label>
                                    <input type="number" class="form-control" id="editarOrdenCategoria" name="orden" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Color</label>
                                    <input type="color" class="form-control" id="editarColorCategoria" name="color" value="#777777" style="height:34px;padding:2px;">
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select class="form-control" id="editarEstadoCategoria" name="estado">
                                        <option value="1">Activa</option>
                                        <option value="0">Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h4 style="margin-top:0;">Requisito</h4>
                        <div class="form-group">
                            <label>Monto mínimo anual de compras (PEN)</label>
                            <input type="number" class="form-control" id="editarMontoAnualCategoria" name="monto_compras_anual" min="0" step="0.01" placeholder="Vacío = aún no definido">
                        </div>
                        <hr>
                        <h4 style="margin-top:0;">Beneficios</h4>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Descuento venta (%)</label>
                                    <input type="number" class="form-control" id="editarDtoVentaCategoria" name="descuento_venta_pct" min="0" max="100" step="0.01">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Descuento pronto pago (%)</label>
                                    <input type="number" class="form-control" id="editarDtoProntoCategoria" name="descuento_pronto_pago_pct" min="0" max="100" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEditarCategoria">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.document.title = "Categorías comerciales";
</script>
