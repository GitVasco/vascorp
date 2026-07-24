<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "series_documentos")) {
    denegarAccesoModulo();
    return;
}
$puedeEditar = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "series_documentos", "editar");
$mapaTipos = ControladorSeriesDocumentos::ctrMapaTipos();
$primerTipo = "";
foreach ($mapaTipos as $cod => $cfg) {
    $primerTipo = $cod;
    break;
}
?>
<div class="content-wrapper sd-page">

    <section class="content-header">
        <div class="sd-header">
            <div>
                <h1 class="sd-header__title">Series de documentos</h1>
                <p class="sd-header__sub">
                    Matriz <strong>serie × marca</strong> por tipo de documento. Marca la celda para amarrar.
                    La numeración se edita en cada fila.
                </p>
            </div>
            <div class="sd-header__actions">
                <button type="button" class="btn btn-default" id="btnRefrescarMatrizSerie">
                    <i class="fa fa-refresh"></i> Actualizar
                </button>
                <?php if ($puedeEditar) { ?>
                <button type="button" class="btn btn-success" id="btnNuevaSerieDocumento">
                    <i class="fa fa-plus"></i> Nueva serie
                </button>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="box sd-box">
            <div class="box-body">
                <div class="sd-kpis">
                    <div class="sd-kpi">
                        <span class="sd-kpi__lbl">Series (tipo)</span>
                        <span class="sd-kpi__val" id="sdKpiTotal">—</span>
                    </div>
                    <div class="sd-kpi sd-kpi--ok">
                        <span class="sd-kpi__lbl">Amarres activos</span>
                        <span class="sd-kpi__val" id="sdKpiAmarres">—</span>
                    </div>
                    <div class="sd-kpi sd-kpi--warn">
                        <span class="sd-kpi__lbl">Series sin marca</span>
                        <span class="sd-kpi__val" id="sdKpiSinMarcas">—</span>
                    </div>
                </div>

                <div class="sd-toolbar">
                    <div class="sd-tabs" id="sdTabsTipo">
                        <?php foreach ($mapaTipos as $cod => $cfg) { ?>
                        <button type="button"
                            class="sd-tab<?php echo $cod === $primerTipo ? ' is-active' : ''; ?>"
                            data-tipo="<?php echo htmlspecialchars($cod, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($cfg["etiqueta"], ENT_QUOTES, "UTF-8"); ?>
                        </button>
                        <?php } ?>
                    </div>
                    <div class="sd-search">
                        <input type="text" class="form-control" id="sdFiltrarMarcaCol" placeholder="Filtrar columnas de marca…">
                    </div>
                </div>

                <div class="sd-matrix-wrap">
                    <div id="sdMatrixLoading" class="sd-empty">Cargando matriz…</div>
                    <div id="sdMatrixEmpty" class="sd-empty" style="display:none;">
                        No hay series para este tipo.
                        <?php if ($puedeEditar) { ?>Crea una con <strong>Nueva serie</strong>.<?php } ?>
                    </div>
                    <div id="sdMatrixScroll" class="sd-matrix-scroll" style="display:none;">
                        <table class="sd-matrix" id="sdMatrixTable">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <p class="sd-hint">
                    Tip: las columnas de la izquierda quedan fijas; desplázate horizontalmente para ver todas las marcas.
                    <?php if ($puedeEditar) { ?>El check se guarda al instante.<?php } ?>
                </p>
            </div>
        </div>
    </section>

</div>

<?php if ($puedeEditar) { ?>
<div id="modalAgregarSerieDocumento" class="modal fade sd-modal" role="dialog">
    <div class="modal-dialog modal-sm" style="width:420px;max-width:94vw;">
        <div class="modal-content">
            <form id="formAgregarSerieDocumento" role="form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Nueva serie</h4>
                </div>
                <div class="modal-body">
                    <div class="sd-panel" style="padding:0;border:0;background:transparent;">
                        <div class="form-group">
                            <label>Tipo de documento</label>
                            <select class="form-control" name="tipo_documento" id="agregarTipoDocumentoSerie" required>
                                <?php foreach ($mapaTipos as $cod => $cfg) { ?>
                                <option value="<?php echo htmlspecialchars($cod, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($cfg["etiqueta"], ENT_QUOTES, "UTF-8"); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Serie</label>
                            <input type="text" class="form-control" name="serie" id="agregarSerieDocumento"
                                required maxlength="4" placeholder="Ej: F003" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group">
                            <label>Correlativo actual</label>
                            <input type="number" class="form-control" name="correlativo" id="agregarCorrelativoSerie"
                                required min="0" value="0">
                        </div>
                        <div class="sd-preview">
                            <span class="sd-preview__lbl">Próximo documento</span>
                            <span class="sd-preview__val" id="agregarPreviewProximo">—</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<div id="modalEditarSerieDocumento" class="modal fade sd-modal" role="dialog">
    <div class="modal-dialog modal-sm" style="width:420px;max-width:94vw;">
        <div class="modal-content">
            <form id="formEditarSerieDocumento" role="form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?php echo $puedeEditar ? "Editar numeración" : "Numeración"; ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_talonario" id="editarIdTalonarioSerie">
                    <input type="hidden" name="tipo_documento" id="editarTipoDocumentoSerie">
                    <div class="form-group">
                        <label>Tipo</label>
                        <input type="text" class="form-control" id="editarTipoEtiquetaSerie" readonly>
                    </div>
                    <div class="form-group">
                        <label>Serie</label>
                        <input type="text" class="form-control" name="serie" id="editarSerieDocumento"
                            required maxlength="4" style="text-transform:uppercase;"
                            <?php echo $puedeEditar ? "" : "readonly"; ?>>
                    </div>
                    <div class="form-group">
                        <label>Correlativo actual</label>
                        <input type="number" class="form-control" name="correlativo" id="editarCorrelativoSerie"
                            required min="0" <?php echo $puedeEditar ? "" : "readonly"; ?>>
                    </div>
                    <div class="sd-preview">
                        <span class="sd-preview__lbl">Próximo documento</span>
                        <span class="sd-preview__val" id="editarPreviewProximo">—</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">
                        <?php echo $puedeEditar ? "Cancelar" : "Cerrar"; ?>
                    </button>
                    <?php if ($puedeEditar) { ?>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Guardar</button>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.seriesDocumentosPuedeEditar = <?php echo $puedeEditar ? "true" : "false"; ?>;
window.seriesDocumentosTipoActivo = <?php echo json_encode($primerTipo); ?>;
</script>
