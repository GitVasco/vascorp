<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "costos_modelo")) {
    denegarAccesoModulo();
    return;
}

$puedeEditarCostos = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "costos_modelo", "editar");
$puedeAprobarCostos = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "costos_modelo", "aprobar");
$mesesCostos = array(
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
);
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Costos mensuales por modelo</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Costos mensuales</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-sm-4">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3 id="resumenModelosCostos">0</h3>
                        <p>Modelos mostrados</p>
                    </div>
                    <div class="icon"><i class="fa fa-cubes"></i></div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3 id="resumenBorradoresCostos">0</h3>
                        <p>Costos en borrador</p>
                    </div>
                    <div class="icon"><i class="fa fa-pencil-square-o"></i></div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3 id="resumenPendientesCostos">0</h3>
                        <p>Modelos sin costo</p>
                    </div>
                    <div class="icon"><i class="fa fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Carga manual y revisión</h3>
                <p class="help-block" style="margin-bottom:0;">
                    Los importes se registran en soles, sin IGV y con hasta cuatro decimales.
                    Solo los costos aprobados serán utilizados para calcular rentabilidad.
                </p>
            </div>
            <div class="box-body">
                <div class="btn-toolbar" style="margin-bottom:15px;">
                    <?php if ($puedeEditarCostos) { ?>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalImportarCostosModelo">
                        <i class="fa fa-upload"></i> Importar CSV
                    </button>
                    <a class="btn btn-default" href="ajax/plantilla-costos-modelo.csv.php">
                        <i class="fa fa-download"></i> Plantilla CSV
                    </a>
                    <?php } ?>
                    <?php if ($puedeAprobarCostos) { ?>
                    <button type="button" class="btn btn-success" id="btnAprobarPeriodoCostos">
                        <i class="fa fa-check-circle"></i> Aprobar período
                    </button>
                    <button type="button" class="btn btn-success btnCambioEstadoCostosSeleccionados" data-accion="aprobar">
                        <i class="fa fa-check"></i> Aprobar seleccionados
                    </button>
                    <button type="button" class="btn btn-danger btnCambioEstadoCostosSeleccionados" data-accion="anular">
                        <i class="fa fa-ban"></i> Anular seleccionados
                    </button>
                    <button type="button" class="btn btn-warning btnCambioEstadoCostosSeleccionados" data-accion="reabrir">
                        <i class="fa fa-unlock"></i> Reabrir seleccionados
                    </button>
                    <?php } ?>
                    <button type="button" class="btn btn-default" id="btnReportePendientesCostos">
                        <i class="fa fa-file-text-o"></i> Descargar pendientes
                    </button>
                </div>
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-sm-2">
                        <label>Año</label>
                        <input type="number" class="form-control filtroCostoModelo" id="filtroAnioCostoModelo"
                            min="2000" max="2100" value="<?php echo (int) date("Y"); ?>">
                    </div>
                    <div class="col-sm-2">
                        <label>Mes</label>
                        <select class="form-control filtroCostoModelo" id="filtroMesCostoModelo">
                            <?php foreach ($mesesCostos as $numeroMes => $nombreMes) { ?>
                            <option value="<?php echo $numeroMes; ?>" <?php echo (int) date("n") === $numeroMes ? "selected" : ""; ?>>
                                <?php echo $nombreMes; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Marca</label>
                        <select class="form-control filtroCostoModelo" id="filtroMarcaCostoModelo">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Estado</label>
                        <select class="form-control filtroCostoModelo" id="filtroEstadoCostoModelo">
                            <option value="">Todos</option>
                            <option value="sin_costo">Sin costo</option>
                            <option value="borrador">Borrador</option>
                            <option value="aprobado">Aprobado</option>
                            <option value="anulado">Anulado</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-default btn-block" id="btnActualizarCostosModelo">
                            <i class="fa fa-refresh"></i> Actualizar
                        </button>
                    </div>
                </div>

                <table class="table table-bordered table-striped dt-responsive" id="tablaCostosModeloMensual" width="100%">
                    <thead>
                        <tr>
                            <th style="width:28px;">
                                <input type="checkbox" id="seleccionarTodosCostosModelo" title="Seleccionar visibles">
                            </th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Nombre</th>
                            <th>Costo unitario</th>
                            <th>Fuente</th>
                            <th>Estado</th>
                            <th>Actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php if ($puedeEditarCostos) { ?>
<div id="modalCostoModeloMensual" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCostoModeloMensual">
                <div class="modal-header" style="background:#3c8dbc;color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Registrar costo mensual</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="costoModeloCodigo" name="modelo">
                    <input type="hidden" id="costoModeloAnio" name="anio">
                    <input type="hidden" id="costoModeloMes" name="mes">
                    <div class="form-group">
                        <label>Modelo</label>
                        <p class="form-control-static" id="costoModeloDescripcion"></p>
                    </div>
                    <div class="form-group">
                        <label>Costo unitario directo sin IGV (S/)</label>
                        <input type="number" class="form-control" id="costoModeloImporte" name="costo_unitario"
                            min="0" max="9999999999.9999" step="0.0001" required>
                    </div>
                    <div class="form-group">
                        <label>Fuente</label>
                        <input type="text" class="form-control" id="costoModeloFuente" name="fuente"
                            maxlength="100" placeholder="Ej. Costeo julio 2026">
                    </div>
                    <div class="form-group">
                        <label>Observación</label>
                        <textarea class="form-control" id="costoModeloObservacion" name="observacion"
                            maxlength="500" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Guardar borrador
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php if ($puedeEditarCostos) { ?>
<div id="modalImportarCostosModelo" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formImportarCostosModelo" enctype="multipart/form-data">
                <div class="modal-header" style="background:#3c8dbc;color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Importar costos desde CSV</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Archivo CSV</label>
                        <input type="file" class="form-control" id="archivoCostosModelo" name="archivo" accept=".csv,text/csv" required>
                        <p class="help-block">
                            Columnas requeridas: modelo, costo_unitario. Opcionales: fuente, observacion.
                            El período se toma de los filtros de la pantalla.
                        </p>
                    </div>
                    <div id="resumenImportacionCostos" class="alert" style="display:none;"></div>
                    <div class="table-responsive" id="contenedorPreviewCostos" style="display:none;max-height:350px;overflow:auto;">
                        <table class="table table-bordered table-condensed">
                            <thead>
                                <tr>
                                    <th>Fila</th>
                                    <th>Modelo</th>
                                    <th>Costo</th>
                                    <th>Fuente</th>
                                    <th>Resultado</th>
                                </tr>
                            </thead>
                            <tbody id="previewImportacionCostosBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="btnPrevisualizarCostos">
                        <i class="fa fa-search"></i> Previsualizar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarImportacionCostos" disabled>
                        <i class="fa fa-upload"></i> Importar borradores
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php if ($puedeAprobarCostos) { ?>
<div id="modalCambioEstadoCostoModelo" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCambioEstadoCostoModelo">
                <div class="modal-header" style="background:#605ca8;color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="tituloCambioEstadoCostoModelo">Cambiar estado</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="accionCambioEstadoCostoModelo">
                    <input type="hidden" id="idsCambioEstadoCostoModelo">
                    <p id="textoCambioEstadoCostoModelo"></p>
                    <div class="form-group" id="grupoMotivoCambioEstadoCosto">
                        <label>Motivo</label>
                        <textarea class="form-control" id="motivoCambioEstadoCostoModelo" maxlength="500" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<div id="modalHistorialCostoModelo" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#00a65a;color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Historial del costo</h4>
            </div>
            <div class="modal-body">
                <p id="historialCostoModeloTitulo" class="text-muted"></p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Acción</th>
                                <th>Costo</th>
                                <th>Fuente</th>
                                <th>Observación</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody id="historialCostoModeloBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
window.costosModeloPuedeEditar = <?php echo $puedeEditarCostos ? "true" : "false"; ?>;
window.costosModeloPuedeAprobar = <?php echo $puedeAprobarCostos ? "true" : "false"; ?>;
</script>
