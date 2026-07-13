<?php
if (!function_exists("usuarioPuedeDashboardCobranzas") || !usuarioPuedeDashboardCobranzas()) {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$categoriasComercialesActivas = ControladorCategoriasClientes::ctrListarCategoriasActivas();
if (!is_array($categoriasComercialesActivas)) {
    $categoriasComercialesActivas = array();
}
$resumenBandeja = ControladorCategoriasClientes::ctrResumenBandejaRevision();
$resumenPorCategoria = isset($resumenBandeja["por_categoria"]) && is_array($resumenBandeja["por_categoria"])
    ? $resumenBandeja["por_categoria"]
    : array();
?>
<div class="content-wrapper">

    <section class="content-header">
        <h1>Categorías por revisar</h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Categorías por revisar</li>
        </ol>
    </section>

    <section class="content">

        <div class="row">
            <?php foreach ($resumenPorCategoria as $idx => $resCat) :
                $hexResumen = ControladorCategoriasClientes::ctrResolverColorCategoria(
                    isset($resCat["color"]) ? $resCat["color"] : "",
                    isset($resCat["codigo"]) ? $resCat["codigo"] : ""
                );
                $totalClientes = isset($resCat["total_clientes"]) ? (int) $resCat["total_clientes"] : 0;
                $totalGrupos = isset($resCat["total_grupos"]) ? (int) $resCat["total_grupos"] : 0;
            ?>
            <div class="col-md-4">
                <div class="info-box" style="background-color:<?php echo htmlspecialchars($hexResumen, ENT_QUOTES, 'UTF-8'); ?>; color:#fff;">
                    <span class="info-box-icon" style="background:rgba(0,0,0,0.12);"><i class="fa fa-tags"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text"><?php echo htmlspecialchars($resCat["nombre"]); ?></span>
                        <span class="info-box-number">
                            <?php echo $totalClientes; ?> <small>clientes</small>
                            ·
                            <?php echo $totalGrupos; ?> <small>grupos</small>
                        </span>
                        <span class="progress-description">
                            Asignaciones vigentes de <?php echo htmlspecialchars($resCat["codigo"]); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Bandeja de revisión</h3>
                <p class="help-block" style="margin:8px 0 0;">
                    Solo Distribuidor, Mayorista y Minorista. Facturado 12m (S02/S03/S70) es referencial; la evaluación sigue siendo manual.
                </p>
            </div>
            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive tablaCategoriasPorRevisar" width="100%">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th class="text-right">Facturado 12m</th>
                            <th class="text-right">Requisito</th>
                            <th class="text-center">Vs requisito</th>
                            <th>Motivo bandeja</th>
                            <th>Cumplimiento</th>
                            <th>Origen</th>
                            <th>Vence</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- MODAL REVISAR -->
<div id="modalRevisarCategoriaBandeja" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formRevisarCategoriaBandeja">
                <div class="modal-header" style="background:#f39c12; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Revisar categoría</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="revTipoEntidad" name="tipo_entidad">
                    <input type="hidden" id="revCodigoEntidad" name="codigo_entidad">
                    <input type="hidden" id="revIdAsignacion" name="id_asignacion">

                    <p>
                        <strong id="revTituloEntidad"></strong>
                    </p>

                    <div class="form-group">
                        <label>Categoría</label>
                        <select class="form-control selectpicker" id="revIdCategoria" name="id_categoria" data-live-search="true" title="Categoría">
                            <option value="">Sin categoría / pendiente</option>
                            <?php foreach ($categoriasComercialesActivas as $catItem) : ?>
                                <option value="<?php echo (int) $catItem["id"]; ?>">
                                    <?php echo htmlspecialchars($catItem["nombre"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cumplimiento</label>
                        <select class="form-control" id="revCumplimiento" name="cumplimiento">
                            <option value="pendiente">Pendiente</option>
                            <option value="cumple">Cumple</option>
                            <option value="no_cumple">No cumple</option>
                            <option value="por_revisar">Por revisar</option>
                        </select>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="revEsExcepcion" name="es_excepcion" value="1">
                            Es excepción manual
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Motivo</label>
                        <input type="text" class="form-control" id="revMotivo" name="motivo" maxlength="255" placeholder="Obligatorio si es excepción">
                    </div>

                    <div class="form-group" id="bloqueVigenciaExcepcion" style="display:none;">
                        <label>Vencimiento de la excepción</label>
                        <input type="date" class="form-control" id="revVigenciaHasta" name="vigencia_hasta">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-warning" id="btnGuardarRevisionBandeja">Guardar revisión</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.document.title = "Categorías por revisar";
</script>
