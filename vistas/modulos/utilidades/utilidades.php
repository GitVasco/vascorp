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

$infoFechaVen = "Lista cargos (tip_mov = +) sin fecha de vencimiento\n"
    . "(NULL, vacío o 0000-00-00) y con fecha de documento válida.\n\n"
    . "Al confirmar, deja fecha_ven = fecha del documento.\n"
    . "Útil para notas de débito y otros cargos que quedaron sin vencimiento\n"
    . "y afectan canje / transferencia a SISCONT.\n\n"
    . "• Solo actualiza fecha_ven.\n"
    . "• No cambia montos ni saldos.";

$infoFechaOri = "Lista abonos (tip_mov = −) de los últimos 60 días\n"
    . "sin fecha_ori o fecha_ori_ven, y busca el cargo (+) del mismo\n"
    . "tipo_doc + num_cta.\n\n"
    . "Al confirmar:\n"
    . "• fecha_ori = fecha del cargo\n"
    . "• fecha_ori_ven = fecha_ven del cargo\n\n"
    . "• Solo actualiza esas dos columnas.\n"
    . "• Si no hay cargo de referencia, no aparece en la lista.";

$infoTipCambio = "Lista cuentas del año {$anioMov} con tip_cambio en 0 o NULL\n"
    . "y busca el cambio_venta de totalesjf de la misma fecha.\n\n"
    . "Al confirmar: tip_cambio = cambio_venta del día.\n\n"
    . "• Solo actualiza tip_cambio.\n"
    . "• Si ese día no tiene tipo de cambio en totales, no aparece.";

$infoCtePipeline = "Ejecuta en orden las correcciones de cuenta corriente:\n"
    . "0) Completar T/C en totales (datos-día)\n"
    . "1) Fecha de vencimiento en cargos\n"
    . "2) Fecha de origen en abonos (60 días)\n"
    . "3) Tipo de cambio en cte. del año\n\n"
    . "En cada paso completa todos los pendientes detectados.\n"
    . "Muestra el avance paso a paso.\n"
    . "Los botones individuales siguen disponibles para revisar a mano.";

$infoVentaPipeline = "Ejecuta en orden el circuito de ventas del periodo:\n"
    . "0) Completar T/C en totales (datos-día)\n"
    . "1) Tipo de cambio en ventas (año, hasta ayer)\n"
    . "2) Cuenta facturas/boletas\n"
    . "3) Cuenta POS showroom\n"
    . "4) Cuenta Culqi\n"
    . "5) Cuenta NC devolución\n"
    . "6) Cuenta NC descuento\n"
    . "7) Cuenta ND flete\n"
    . "8) Cuenta ND protesto\n\n"
    . "El periodo aplica a los pasos 2–8.\n"
    . "Los botones individuales siguen disponibles.";

$infoTotalesTipCambio = "Días del año en totales (datos-día) sin cambio de venta.\n"
    . "Consulta la misma fuente que /datos-dia (API SUNAT)\n"
    . "y graba cambio_compra / cambio_venta.\n\n"
    . "Si un día no tiene TC en la API (finde/feriado),\n"
    . "reusa el último día previo que sí tenga.\n\n"
    . "Necesario antes de completar T/C en cte. o ventas.";

$infoVentaTipCambio = "Lista ventas del año {$anioMov} con tipo_cambio en 0 o NULL,\n"
    . "solo fechas anteriores a hoy, y busca el cambio_venta de totales\n"
    . "de la misma fecha.\n\n"
    . "Al confirmar: tipo_cambio = cambio_venta del día.\n\n"
    . "• Solo actualiza tipo_cambio.\n"
    . "• Si ese día no tiene tipo de cambio en totales, no aparece.";

$infoVentaCuenta = "Completa la cuenta contable en facturas (S02) y boletas (S03)\n"
    . "del periodo elegido (por defecto el mes actual).\n\n"
    . "Según ubigeo del cliente:\n"
    . "• Lima (ubigeo 15… o L…) → 702211\n"
    . "• Provincia → 702212\n\n"
    . "• Solo filas con cuenta vacía.\n"
    . "• No toca NC/ND ni otros tipos (después se amplía).";

$infoVentaPos = "Busca abonos POS showroom en cte. del periodo:\n"
    . "• tip_mov = −\n"
    . "• vendedor contiene 08\n"
    . "• cod_pago 06 o 17\n\n"
    . "Mapea tipo_doc cte → tipo venta:\n"
    . "01→S03, 03→S02, 07→E05, 08→S05\n"
    . "y asigna cuenta 702213 en ventajf.\n\n"
    . "• Solo ventas que aún no tienen 702213.";

$infoVentaCulqi = "Busca abonos Culqi showroom en cte. del periodo:\n"
    . "• tip_mov = −\n"
    . "• vendedor contiene 08\n"
    . "• cod_pago = 14\n"
    . "• tipo_doc 01 o 03\n\n"
    . "Mapea 01→S03, 03→S02 y asigna:\n"
    . "• Lima → 702215\n"
    . "• Provincia → 702216\n\n"
    . "• Solo ventas cuya cuenta aún no es la propuesta.";

$infoVentaNcDev = "Notas de crédito (E05) por devolución del periodo:\n"
    . "• motivos C1 o C7 en notascd\n\n"
    . "Asigna cuenta según ubigeo del cliente:\n"
    . "• Lima → 709411\n"
    . "• Provincia → 709412\n\n"
    . "• Solo documentos cuya cuenta aún no es la propuesta.";

$infoVentaNcDscto = "Notas de crédito (E05) por descuento del periodo:\n"
    . "• motivos distintos de C1 y C7\n\n"
    . "Asigna cuenta según ubigeo del cliente:\n"
    . "• Lima → 741101\n"
    . "• Provincia → 741102\n\n"
    . "• Solo documentos cuya cuenta aún no es la propuesta.";

$infoVentaNdFlete = "Notas de débito (S05) por flete del showroom:\n"
    . "• vendedor contiene 08\n\n"
    . "Asigna cuenta según ubigeo del cliente:\n"
    . "• Lima → 75995\n"
    . "• Provincia → 75996\n\n"
    . "• Solo documentos cuya cuenta aún no es la propuesta.";

$infoVentaNdProtesto = "Notas de débito (S05) por protesto:\n"
    . "• vendedor NO contiene 08\n\n"
    . "Asigna cuenta según ubigeo del cliente:\n"
    . "• Lima → 75991\n"
    . "• Provincia → 75992\n\n"
    . "• Solo documentos cuya cuenta aún no es la propuesta.";

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

        <div class="ut-block">
            <h2 class="ut-block__title">Almacén</h2>
            <div class="ut-grid">
                <article class="ut-card">
                    <div class="ut-card__top">
                        <h3 class="ut-card__title">Cuadrar stock almacén 01</h3>
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
            </div>
        </div>

        <div class="ut-block">
            <h2 class="ut-block__title">Producción</h2>
            <div class="ut-grid">
                <article class="ut-card">
                    <div class="ut-card__top">
                        <h3 class="ut-card__title">Cuadrar servicio / cierre</h3>
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
                        <h3 class="ut-card__title">Tracking modelo</h3>
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
            </div>
        </div>

        <div class="ut-block">
            <h2 class="ut-block__title">Datos diarios</h2>
            <div class="ut-grid">
                <article class="ut-card">
                    <div class="ut-card__top">
                        <h3 class="ut-card__title">Completar T/C en totales</h3>
                        <button type="button"
                            class="ut-info"
                            tabindex="0"
                            data-toggle="popover"
                            data-trigger="hover focus"
                            data-placement="left"
                            title="Detalle"
                            data-content="<?php echo htmlspecialchars($infoTotalesTipCambio, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fa fa-info-circle"></i>
                        </button>
                    </div>
                    <p class="ut-card__desc">
                        Rellena en totales los días del año sin tipo de cambio (misma fuente que Datos diarios).
                    </p>
                    <div class="ut-card__actions">
                        <?php if ($puedeEjecutar) { ?>
                        <button type="button" class="btn btn-primary" id="btnUtTotalesSinTipCambio">
                            <i class="fa fa-calendar"></i> Revisar
                        </button>
                        <?php } else { ?>
                        <span class="text-muted">Sin permiso</span>
                        <?php } ?>
                    </div>
                </article>
            </div>
        </div>

        <div class="ut-block">
            <h2 class="ut-block__title">Cuenta corriente</h2>

            <div class="ut-grid ut-grid--cte">
                <article class="ut-card ut-card--pipeline">
                    <div class="ut-card__top">
                        <div>
                            <h3 class="ut-card__title">Preparar cte. (secuencia)</h3>
                            <p class="ut-card__desc ut-card__desc--inline">
                                Corre en orden los 4 pasos (incluye T/C en totales). Ideal antes de transferir a SISCONT.
                            </p>
                        </div>
                        <div class="ut-card__top-actions">
                            <button type="button"
                                class="ut-info"
                                tabindex="0"
                                data-toggle="popover"
                                data-trigger="hover focus"
                                data-placement="left"
                                title="Detalle"
                                data-content="<?php echo htmlspecialchars($infoCtePipeline, ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fa fa-info-circle"></i>
                            </button>
                            <?php if ($puedeEjecutar) { ?>
                            <button type="button" class="btn btn-success" id="btnUtCtePipeline">
                                <i class="fa fa-play"></i> Ejecutar secuencia
                            </button>
                            <?php } else { ?>
                            <span class="text-muted">Sin permiso</span>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="ut-pipeline-tools">
                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">0</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>T/C totales</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoTotalesTipCambio, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Días sin TC en totales → API / día previo.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <span class="text-muted" style="font-size:12px;">En secuencia</span>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">1</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>Fecha vencimiento</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoFechaVen, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Cargos sin fecha_ven → usa la fecha del documento.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtCteSinFechaVen">
                                    <i class="fa fa-calendar-check-o"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">2</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>Fecha origen</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoFechaOri, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Abonos sin fecha_ori → copia del cargo del mismo documento.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtCteSinFechaOri">
                                    <i class="fa fa-link"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">3</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>Tipo de cambio</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoTipCambio, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Sin tip_cambio → toma el cambio de venta del día.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtCteSinTipCambio">
                                    <i class="fa fa-exchange"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="ut-card">
                    <div class="ut-card__top">
                        <h3 class="ut-card__title">Limpiar cte. VTAOFIC21</h3>
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
        </div>

        <div class="ut-block">
            <h2 class="ut-block__title">Ventas</h2>

            <div class="ut-grid ut-grid--cte">
                <article class="ut-card ut-card--pipeline">
                    <div class="ut-card__top">
                        <div>
                            <h3 class="ut-card__title">Preparar ventas (secuencia)</h3>
                            <p class="ut-card__desc ut-card__desc--inline">
                                Corre en orden T/C totales + 8 pasos del periodo. Ideal antes de transferir a SISCONT.
                            </p>
                        </div>
                        <div class="ut-card__top-actions">
                            <input type="month"
                                id="utVentaPipelinePeriodo"
                                class="form-control ut-input-periodo"
                                value="<?php echo htmlspecialchars(date('Y-m'), ENT_QUOTES, 'UTF-8'); ?>"
                                max="<?php echo htmlspecialchars(date('Y-m'), ENT_QUOTES, 'UTF-8'); ?>"
                                title="Periodo para pasos 2–8">
                            <button type="button"
                                class="ut-info"
                                tabindex="0"
                                data-toggle="popover"
                                data-trigger="hover focus"
                                data-placement="left"
                                title="Detalle"
                                data-content="<?php echo htmlspecialchars($infoVentaPipeline, ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fa fa-info-circle"></i>
                            </button>
                            <?php if ($puedeEjecutar) { ?>
                            <button type="button" class="btn btn-success" id="btnUtVentaPipeline">
                                <i class="fa fa-play"></i> Ejecutar secuencia
                            </button>
                            <?php } else { ?>
                            <span class="text-muted">Sin permiso</span>
                            <?php } ?>
                        </div>
                    </div>

                                        <div class="ut-pipeline-tools ut-pipeline-tools--ventas">
                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">0</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>T/C totales</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoTotalesTipCambio, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Días sin TC en totales → API / día previo.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <span class="text-muted" style="font-size:12px;">En secuencia</span>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">1</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>Tipo de cambio</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaTipCambio, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Año actual (hasta ayer), sin T/C → cambio del día.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasSinTipCambio">
                                    <i class="fa fa-exchange"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">2</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>Cuenta S02/S03</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaCuenta, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Facturas/boletas sin cuenta → 702211 / 702212.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <input type="hidden" id="utVentaCuentaPeriodo" value="">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasSinCuenta">
                                    <i class="fa fa-book"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">3</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>POS showroom</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaPos, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Abonos POS → cuenta 702213.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <input type="hidden" id="utVentaPosPeriodo" value="">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasCuentaPos">
                                    <i class="fa fa-credit-card"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">4</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>Culqi</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaCulqi, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>Abonos Culqi → 702215 / 702216.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <input type="hidden" id="utVentaCulqiPeriodo" value="">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasCuentaCulqi">
                                    <i class="fa fa-globe"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">5</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>NC devolución</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaNcDev, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>E05 C1/C7 → 709411 / 709412.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <input type="hidden" id="utVentaNcDevPeriodo" value="">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasCuentaNcDev">
                                    <i class="fa fa-undo"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">6</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>NC descuento</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaNcDscto, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>E05 otros → 741101 / 741102.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <input type="hidden" id="utVentaNcDsctoPeriodo" value="">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasCuentaNcDscto">
                                    <i class="fa fa-percent"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">7</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>ND flete</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaNdFlete, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>S05 vendedor 08 → 75995 / 75996.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <input type="hidden" id="utVentaNdFletePeriodo" value="">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasCuentaNdFlete">
                                    <i class="fa fa-truck"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ut-pipeline-tool">
                            <span class="ut-pipeline-tool__num">8</span>
                            <div class="ut-pipeline-tool__body">
                                <div class="ut-pipeline-tool__head">
                                    <strong>ND protesto</strong>
                                    <button type="button"
                                        class="ut-info"
                                        tabindex="0"
                                        data-toggle="popover"
                                        data-trigger="hover focus"
                                        data-placement="left"
                                        title="Detalle"
                                        data-content="<?php echo htmlspecialchars($infoVentaNdProtesto, ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </div>
                                <p>S05 sin 08 → 75991 / 75992.</p>
                            </div>
                            <div class="ut-pipeline-tool__action">
                                <input type="hidden" id="utVentaNdProtestoPeriodo" value="">
                                <?php if ($puedeEjecutar) { ?>
                                <button type="button" class="btn btn-primary btn-sm" id="btnUtVentasCuentaNdProtesto">
                                    <i class="fa fa-exclamation-triangle"></i> Revisar
                                </button>
                                <?php } else { ?>
                                <span class="text-muted">Sin permiso</span>
                                <?php } ?>
                            </div>
                        </div>
                    
                
                </article>
            </div>
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

<div id="modalUtFechaVen" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Cargos sin fecha de vencimiento
                    <small id="utFechaVenMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utFechaVenLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utFechaVenEmpty" class="ut-empty" style="display:none;">
                    No hay cargos sin fecha de vencimiento.
                </div>
                <div id="utFechaVenTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utFechaVenCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utFechaVenCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utFechaVenTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Fecha</th>
                                    <th>Venc. propuesta</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-right">Saldo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarFechaVen" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtFechaOri" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Abonos sin fecha de origen
                    <small id="utFechaOriMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utFechaOriLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utFechaOriEmpty" class="ut-empty" style="display:none;">
                    No hay abonos sin fecha de origen en la ventana consultada.
                </div>
                <div id="utFechaOriTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utFechaOriCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utFechaOriCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utFechaOriTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Fecha abono</th>
                                    <th>Ori. propuesta</th>
                                    <th>Ven. propuesta</th>
                                    <th class="text-right">Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarFechaOri" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtTipCambio" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Cuentas sin tipo de cambio
                    <small id="utTipCambioMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utTipCambioLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utTipCambioEmpty" class="ut-empty" style="display:none;">
                    No hay cuentas sin tipo de cambio en el año consultado.
                </div>
                <div id="utTipCambioTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utTipCambioCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utTipCambioCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utTipCambioTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Mov</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Fecha</th>
                                    <th class="text-right">T/C actual</th>
                                    <th class="text-right">T/C propuesto</th>
                                    <th class="text-right">Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarTipCambio" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>


<div id="modalUtTotalesTipCambio" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:780px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Días sin T/C en totales
                    <small id="utTotalesTipCambioMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utTotalesTipCambioLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utTotalesTipCambioEmpty" class="ut-empty" style="display:none;">
                    No hay días sin tipo de cambio en totales del año.
                </div>
                <div id="utTotalesTipCambioTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utTotalesTipCambioCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utTotalesTipCambioCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utTotalesTipCambioTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Fecha</th>
                                    <th>Día</th>
                                    <th class="text-right">TC compra</th>
                                    <th class="text-right">TC venta</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarTotalesTipCambio" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtCtePipeline" class="modal fade ut-modal" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:520px;max-width:96vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Preparar cuenta corriente</h4>
            </div>
            <div class="modal-body">
                <p class="ut-pipeline-intro" id="utPipelineIntro">Ejecutando correcciones en orden…</p>
                <ol class="ut-pipeline-steps" id="utPipelineSteps">
                    <li class="ut-pipeline-step" data-step="totalesTc">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>0. T/C en totales</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="fechaVen">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>1. Fecha vencimiento</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="fechaOri">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>2. Fecha origen</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="tipCambio">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>3. Tipo de cambio cte.</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="btnUtPipelineCerrar" data-dismiss="modal" disabled>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>


<div id="modalUtVentaPipeline" class="modal fade ut-modal" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:560px;max-width:96vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Preparar ventas</h4>
            </div>
            <div class="modal-body">
                <p class="ut-pipeline-intro" id="utVentaPipelineIntro">Ejecutando correcciones en orden…</p>
                <ol class="ut-pipeline-steps" id="utVentaPipelineSteps">
                    <li class="ut-pipeline-step" data-step="totalesTc">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>0. T/C en totales</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="tipCambio">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>1. Tipo de cambio ventas</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="cuenta">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>2. Cuenta S02/S03</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="pos">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>3. POS showroom</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="culqi">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>4. Culqi</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="ncDev">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>5. NC devolución</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="ncDscto">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>6. NC descuento</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="ndFlete">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>7. ND flete</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                    <li class="ut-pipeline-step" data-step="ndProtesto">
                        <span class="ut-pipeline-step__icon"><i class="fa fa-circle-o"></i></span>
                        <span class="ut-pipeline-step__body">
                            <strong>8. ND protesto</strong>
                            <span class="ut-pipeline-step__msg">En espera</span>
                        </span>
                    </li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="btnUtVentaPipelineCerrar" data-dismiss="modal" disabled>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaTipCambio" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Ventas sin tipo de cambio
                    <small id="utVentaTipCambioMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaTipCambioLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaTipCambioEmpty" class="ut-empty" style="display:none;">
                    No hay ventas sin tipo de cambio en el año consultado.
                </div>
                <div id="utVentaTipCambioTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaTipCambioCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaTipCambioCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaTipCambioTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Fecha</th>
                                    <th class="text-right">T/C actual</th>
                                    <th class="text-right">T/C propuesto</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaTipCambio" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaCuenta" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Facturas/boletas sin cuenta
                    <small id="utVentaCuentaMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaCuentaLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaCuentaEmpty" class="ut-empty" style="display:none;">
                    No hay facturas/boletas sin cuenta en el periodo.
                </div>
                <div id="utVentaCuentaTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaCuentaCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaCuentaCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaCuentaTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Ubigeo</th>
                                    <th>Zona</th>
                                    <th>Fecha</th>
                                    <th>Cuenta prop.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaCuenta" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaPos" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Ventas POS showroom → 702213
                    <small id="utVentaPosMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaPosLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaPosEmpty" class="ut-empty" style="display:none;">
                    No hay ventas POS showroom pendientes en el periodo.
                </div>
                <div id="utVentaPosTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaPosCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaPosCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaPosTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Doc cte</th>
                                    <th>Tipo cte</th>
                                    <th>Tipo venta</th>
                                    <th>Documento</th>
                                    <th>Pago</th>
                                    <th>Vendedor</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Fecha pago</th>
                                    <th>Cuenta act.</th>
                                    <th>Cuenta prop.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaPos" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaCulqi" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    Ventas Culqi → 702215 / 702216
                    <small id="utVentaCulqiMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaCulqiLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaCulqiEmpty" class="ut-empty" style="display:none;">
                    No hay ventas Culqi pendientes en el periodo.
                </div>
                <div id="utVentaCulqiTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaCulqiCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaCulqiCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaCulqiTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Doc cte</th>
                                    <th>Tipo cte</th>
                                    <th>Tipo venta</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Ubigeo</th>
                                    <th>Zona</th>
                                    <th>Fecha pago</th>
                                    <th>Cuenta act.</th>
                                    <th>Cuenta prop.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaCulqi" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaNcDev" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    NC devolución → 709411 / 709412
                    <small id="utVentaNcDevMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaNcDevLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaNcDevEmpty" class="ut-empty" style="display:none;">
                    No hay NC devolución pendientes en el periodo.
                </div>
                <div id="utVentaNcDevTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaNcDevCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaNcDevCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaNcDevTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Motivo</th>
                                    <th>Ubigeo</th>
                                    <th>Zona</th>
                                    <th>Fecha</th>
                                    <th>Cuenta act.</th>
                                    <th>Cuenta prop.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaNcDev" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaNcDscto" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    NC descuento → 741101 / 741102
                    <small id="utVentaNcDsctoMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaNcDsctoLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaNcDsctoEmpty" class="ut-empty" style="display:none;">
                    No hay NC descuento pendientes en el periodo.
                </div>
                <div id="utVentaNcDsctoTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaNcDsctoCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaNcDsctoCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaNcDsctoTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Motivo</th>
                                    <th>Ubigeo</th>
                                    <th>Zona</th>
                                    <th>Fecha</th>
                                    <th>Cuenta act.</th>
                                    <th>Cuenta prop.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaNcDscto" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaNdFlete" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    ND flete → 75995 / 75996
                    <small id="utVentaNdFleteMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaNdFleteLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaNdFleteEmpty" class="ut-empty" style="display:none;">
                    No hay ND flete pendientes en el periodo.
                </div>
                <div id="utVentaNdFleteTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaNdFleteCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaNdFleteCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaNdFleteTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Vendedor</th>
                                    <th>Motivo</th>
                                    <th>Ubigeo</th>
                                    <th>Zona</th>
                                    <th>Fecha</th>
                                    <th>Cuenta act.</th>
                                    <th>Cuenta prop.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaNdFlete" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modalUtVentaNdProtesto" class="modal fade ut-modal" role="dialog">
    <div class="modal-dialog modal-lg" style="width:1100px;max-width:98vw;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    ND protesto → 75991 / 75992
                    <small id="utVentaNdProtestoMeta" class="text-muted"></small>
                </h4>
            </div>
            <div class="modal-body">
                <div id="utVentaNdProtestoLoading" class="ut-empty" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i> Consultando…
                </div>
                <div id="utVentaNdProtestoEmpty" class="ut-empty" style="display:none;">
                    No hay ND protesto pendientes en el periodo.
                </div>
                <div id="utVentaNdProtestoTableWrap" style="display:none;">
                    <div class="ut-modal-toolbar">
                        <label class="ut-check-all">
                            <input type="checkbox" id="utVentaNdProtestoCheckAll" checked>
                            Seleccionar todos
                        </label>
                        <span id="utVentaNdProtestoCount" class="text-muted"></span>
                    </div>
                    <div class="table-responsive ut-table-scroll">
                        <table class="table table-bordered table-striped table-condensed" id="utVentaNdProtestoTable">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Tipo</th>
                                    <th>Documento</th>
                                    <th>Cliente</th>
                                    <th>Nombre</th>
                                    <th>Vendedor</th>
                                    <th>Motivo</th>
                                    <th>Ubigeo</th>
                                    <th>Zona</th>
                                    <th>Fecha</th>
                                    <th>Cuenta act.</th>
                                    <th>Cuenta prop.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnUtCompletarVentaNdProtesto" disabled>
                    <i class="fa fa-check"></i> Completar seleccionados
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
