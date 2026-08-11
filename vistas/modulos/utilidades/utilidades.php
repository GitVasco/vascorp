<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("utilidades", "utilidades")) {
    denegarAccesoModulo();
    return;
}
$puedeEjecutar = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("utilidades", "utilidades", "ejecutar");
date_default_timezone_set("America/Lima");
$anioMov = (int) date("Y");

$infoStock01 = "Movimientos {$anioMov}, solo almacén 01.\n"
    . "Suma ingresos (E*) y resta salidas (S*).\n"
    . "Compara con la columna stock y muestra los que no cuadran.\n\n"
    . "• No toma S01 (guías de remisión).\n"
    . "• E05 (devolución) = cantidad × -1.\n"
    . "• Excluye marca ELASTICOS.\n"
    . "• Excluye modelos que inician con D0.\n"
    . "• Al confirmar, actualiza stock al saldo calculado.";

$infoServicio = "Compara articulojf.servicio con:\n"
    . "suma de saldos abiertos (servicios_detallejf, cerrar=0)\n"
    . "+ suma de cantidades en cierres_detallejf.\n\n"
    . "• Solo muestra los que no cuadran.\n"
    . "• Al confirmar, deja servicio = servicio abierto + cierre.";
?>
<div class="content-wrapper ut-page">

    <section class="content-header">
        <div class="ut-header">
            <div>
                <h1 class="ut-header__title">Utilidades</h1>
                <p class="ut-header__sub">Tareas rápidas. La ⓘ muestra el detalle de cada una.</p>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="ut-grid">

            <article class="ut-card">
                <div class="ut-card__top">
                    <h2 class="ut-card__title">Cuadrar stock almacén 01</h2>
                    <button type="button"
                        class="ut-info"
                        tabindex="0"
                        data-toggle="popover"
                        data-trigger="hover focus"
                        data-placement="left"
                        title="Detalle"
                        data-content="<?php echo htmlspecialchars($infoStock01, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa fa-info-circle"></i>
                    </button>
                </div>
                <p class="ut-card__desc">
                    Compara movimientos del almacén 01 con el stock y actualiza los que no cuadran.
                </p>
                <div class="ut-card__actions">
                    <?php if ($puedeEjecutar) { ?>
                    <button type="button" class="btn btn-primary" id="btnUtCuadrarStock01">
                        <i class="fa fa-balance-scale"></i> Cuadrar
                    </button>
                    <?php } else { ?>
                    <span class="text-muted">Sin permiso</span>
                    <?php } ?>
                </div>
            </article>

            <article class="ut-card">
                <div class="ut-card__top">
                    <h2 class="ut-card__title">Cuadrar servicio / cierre</h2>
                    <button type="button"
                        class="ut-info"
                        tabindex="0"
                        data-toggle="popover"
                        data-trigger="hover focus"
                        data-placement="left"
                        title="Detalle"
                        data-content="<?php echo htmlspecialchars($infoServicio, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa fa-info-circle"></i>
                    </button>
                </div>
                <p class="ut-card__desc">
                    Compara el servicio del artículo con servicios abiertos + cierres y corrige los descuadres.
                </p>
                <div class="ut-card__actions">
                    <?php if ($puedeEjecutar) { ?>
                    <button type="button" class="btn btn-primary" id="btnUtCuadrarServicio">
                        <i class="fa fa-wrench"></i> Cuadrar
                    </button>
                    <?php } else { ?>
                    <span class="text-muted">Sin permiso</span>
                    <?php } ?>
                </div>
            </article>

        </div>
    </section>

</div>

<div id="utOverlay" class="ut-overlay" aria-hidden="true">
    <div class="ut-overlay__box">
        <i class="fa fa-spinner fa-spin"></i>
        <p id="utOverlayMsg">Procesando…</p>
    </div>
</div>

<?php if ($puedeEjecutar) { ?>
<div id="modalUtStock01" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Descuadres stock almacén 01
                    <small id="utStock01Meta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utStock01Loading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Calculando…
                </div>
                <div id="utStock01Empty" class="ut-empty" style="display:none;">
                    No hay descuadres. Todo cuadra.
                </div>
                <div id="utStock01TableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utStock01CheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utStock01Count" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utStock01Table">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Artículo</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th>Nombre</th>
                                    <th class="text-right">Ingresos</th>
                                    <th class="text-right">Salidas</th>
                                    <th class="text-right">Calculado</th>
                                    <th class="text-right">Stock</th>
                                    <th class="text-right">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtActualizarStock01" disabled>
                    <i class="fa fa-check"></i> Actualizar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtServicio" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Descuadres servicio / cierre
                    <small id="utServicioMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utServicioLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Calculando…
                </div>
                <div id="utServicioEmpty" class="ut-empty" style="display:none;">
                    No hay descuadres. Todo cuadra.
                </div>
                <div id="utServicioTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utServicioCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utServicioCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utServicioTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Artículo</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th>Nombre</th>
                                    <th class="text-right">Servicio art.</th>
                                    <th class="text-right">Servicio ab.</th>
                                    <th class="text-right">Cierre</th>
                                    <th class="text-right">Calculado</th>
                                    <th class="text-right">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtActualizarServicio" disabled>
                    <i class="fa fa-check"></i> Actualizar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>
<?php } ?>
