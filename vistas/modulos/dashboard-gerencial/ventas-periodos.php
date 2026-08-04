<?php
$modoPeriodos = isset($filtros['modo']) && $filtros['modo'] === 'periodos';
?>
<div class="box box-primary dg-bloque dg-bloque-ventas-periodos <?php echo $modoPeriodos ? 'dg-bloque--destacado' : ''; ?>" id="dgBloqueVentasPeriodos">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-calendar"></i>
            Ventas períodos A vs B
            <small id="dgVentasPeriodosLabel"></small>
        </h3>
        <?php if (!$modoPeriodos) { ?>
        <span class="dg-bloque-hint text-muted">Elegí «Períodos A / B» en Comparar y Aplicar</span>
        <?php } ?>
    </div>
    <div class="box-body">
        <div class="row dg-periodos-cuerpo dg-periodos-3col">
            <div class="col-md-4 col-sm-12">
                <div class="dg-periodos-resumen dg-periodos-resumen--stack">
                    <div class="dg-periodos-resumen__item">
                        <div class="dg-resumen-card dg-resumen-card--a">
                            <div class="dg-resumen-label">Período A</div>
                            <div class="dg-resumen-rango" id="dgPeriodoARango">—</div>
                            <div class="dg-resumen-valor" id="dgPeriodoATotal">—</div>
                        </div>
                    </div>
                    <div class="dg-periodos-resumen__item">
                        <div class="dg-resumen-card dg-resumen-card--b">
                            <div class="dg-resumen-label">Período B</div>
                            <div class="dg-resumen-rango" id="dgPeriodoBRango">—</div>
                            <div class="dg-resumen-valor" id="dgPeriodoBTotal">—</div>
                        </div>
                    </div>
                    <div class="dg-periodos-resumen__item">
                        <div class="dg-resumen-card dg-resumen-card--delta">
                            <div class="dg-resumen-label">A − B</div>
                            <div class="dg-resumen-rango">Variación</div>
                            <div class="dg-resumen-valor" id="dgPeriodosDelta">—</div>
                            <div class="dg-resumen-sub" id="dgPeriodosDeltaPct">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="dg-chart-wrap dg-chart-wrap--periodos">
                    <canvas id="dgGraficoVentasPeriodosMes"></canvas>
                    <p class="dg-chart-empty text-muted is-hidden" id="dgVentasPeriodosEmpty">Sin ventas en los períodos</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="table-responsive">
                    <table class="table table-condensed table-hover dg-tabla" id="dgTablaVentasPeriodos">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="text-right">Período A</th>
                                <th class="text-right">Período B</th>
                                <th class="text-right">Δ</th>
                                <th class="text-right">Δ%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-muted">Cargando…</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="dg-fila-total">
                                <th>Total</th>
                                <th class="text-right" id="dgPeriodosFootA">—</th>
                                <th class="text-right" id="dgPeriodosFootB">—</th>
                                <th class="text-right" id="dgPeriodosFootDelta">—</th>
                                <th class="text-right" id="dgPeriodosFootDeltaPct">—</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
