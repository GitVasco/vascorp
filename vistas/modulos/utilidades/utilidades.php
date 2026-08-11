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

$infoVtaOfic = "Elimina TODOS los movimientos de cuenta corriente\n"
    . "del cliente VTAOFIC21 (venta oficina / boleto).\n\n"
    . "Equivalente a:\n"
    . "DELETE FROM cuenta_ctejf WHERE cliente = 'VTAOFIC21'\n\n"
    . "• No se puede deshacer.\n"
    . "• Solo afecta ese cliente.";

$infoTracking = "Ingresa el código de modelo (ej. 10400) y analiza\n"
    . "el flujo: orden de corte → corte → taller/servicio → cierre → ingresos.\n\n"
    . "Compara saldos del artículo con documentos y detecta:\n"
    . "• Descuadres de ord_corte, alm_corte, taller, servicio\n"
    . "• Brecha inicio corte vs en proceso + ingresos E20\n"
    . "• Cortes sin orden / envíos o servicios sin vínculo\n\n"
    . "• Corregir saldos: solo actualiza columnas de articulojf.\n"
    . "• No toca documentos ni ingresos E20.";
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

            <article class="ut-card">
                <div class="ut-card__top">
                    <h2 class="ut-card__title">Tracking modelo (producción)</h2>
                    <button type="button"
                        class="ut-info"
                        tabindex="0"
                        data-toggle="popover"
                        data-trigger="hover focus"
                        data-placement="left"
                        title="Detalle"
                        data-content="<?php echo htmlspecialchars($infoTracking, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa fa-info-circle"></i>
                    </button>
                </div>
                <p class="ut-card__desc">
                    Analiza un modelo en el flujo de producción y muestra inconsistencias (solo lectura).
                </p>
                <div class="ut-card__actions ut-card__actions--row">
                    <input type="text"
                        id="utTrackingModelo"
                        class="form-control ut-input-modelo"
                        placeholder="Ej. 10400"
                        maxlength="20"
                        autocomplete="off">
                    <button type="button" class="btn btn-primary" id="btnUtTrackingModelo">
                        <i class="fa fa-search"></i> Analizar
                    </button>
                </div>
            </article>

            <article class="ut-card">
                <div class="ut-card__top">
                    <h2 class="ut-card__title">Limpiar cte. VTAOFIC21</h2>
                    <button type="button"
                        class="ut-info"
                        tabindex="0"
                        data-toggle="popover"
                        data-trigger="hover focus"
                        data-placement="left"
                        title="Detalle"
                        data-content="<?php echo htmlspecialchars($infoVtaOfic, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa fa-info-circle"></i>
                    </button>
                </div>
                <p class="ut-card__desc">
                    Borra toda la cuenta corriente del cliente venta oficina (VTAOFIC21).
                </p>
                <div class="ut-card__actions">
                    <?php if ($puedeEjecutar) { ?>
                    <button type="button" class="btn btn-danger" id="btnUtLimpiarVtaOfic">
                        <i class="fa fa-trash"></i> Eliminar
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

<div id="modalUtTracking" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1200px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Tracking modelo
                    <small id="utTrackingMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utTrackingResumen" class="ut-tracking-resumen" style="display:none;"></div>
                <p id="utTrackingLeyenda" class="ut-tracking-leyenda" style="display:none;">
                    Naranja = lo que <strong>Corregir saldos</strong> cambiará.
                    Incluye columnas del artículo y saldo de envíos a <strong>servicio externo</strong> (Ent.ext → servicio abierto ligado).
                </p>

                <h5 class="ut-section-title">Detalle por artículo</h5>
                <div id="utTrackingDetalleWrap">
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utTrackingDetalleTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Artículo</th>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th class="text-right">OC</th>
                                    <th class="text-right">OC calc</th>
                                    <th class="text-right">Alm.corte</th>
                                    <th class="text-right">Alm calc</th>
                                    <th class="text-right">Taller</th>
                                    <th class="text-right">Taller calc</th>
                                    <th class="text-right">Servicio</th>
                                    <th class="text-right">Serv calc</th>
                                    <th class="text-right">Ent.ext</th>
                                    <th class="text-right">Ent.ext calc</th>
                                    <th class="text-right">Inicio corte</th>
                                    <th class="text-right">Ingresos E20</th>
                                    <th class="text-right">Brecha</th>
                                    <th class="text-right">Stock</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <h5 class="ut-section-title">Cadena servicio → cierre → ingreso</h5>
                <p class="ut-tracking-leyenda">
                    Naranja = lo que corregirá el botón:
                    <strong>Cierre ini</strong> → pend + E20;
                    <strong>Serv.ab</strong> → max(0, Serv.orig − Cierre ini) (quita pendiente fantasma; no infla origen).
                    No crea ni borra ingresos E20 ni cambia stock.
                </p>
                <div id="utTrackingCadenaWrap">
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utTrackingCadenaTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Artículo</th>
                                    <th>Color</th>
                                    <th>Talla</th>
                                    <th class="text-right">Serv.orig</th>
                                    <th class="text-right">Serv.ab</th>
                                    <th class="text-right">Cierre ini</th>
                                    <th class="text-right">Δ Serv→Cierre</th>
                                    <th class="text-right">Cierre pend</th>
                                    <th class="text-right">E20 cierre</th>
                                    <th class="text-right">Δ Cierre→Ing</th>
                                    <th class="text-right">Δ Cadena</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <?php if ($puedeEjecutar) { ?>
                <button type="button" class="btn btn-success" id="btnUtCorregirSaldosModelo">
                    <i class="fa fa-check"></i> Corregir saldos artículo
                </button>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
