<?php
$modoPeriodos = isset($filtros['modo']) && $filtros['modo'] === 'periodos';
?>
<div class="box box-info dg-bloque dg-bloque-cobranzas-periodos <?php echo $modoPeriodos ? 'dg-bloque--destacado' : ''; ?>">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-calendar"></i>
            Cobranzas períodos A vs B
            <small>sin IGV</small>
            <small id="dgCobranzasPeriodosLabel"></small>
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
                        <div class="dg-resumen-card dg-resumen-card--a-blue">
                            <div class="dg-resumen-label">Período A</div>
                            <div class="dg-resumen-rango" id="dgCobPeriodoARango">—</div>
                            <div class="dg-resumen-valor" id="dgCobPeriodoATotal">—</div>
                        </div>
                    </div>
                    <div class="dg-periodos-resumen__item">
                        <div class="dg-resumen-card dg-resumen-card--b">
                            <div class="dg-resumen-label">Período B</div>
                            <div class="dg-resumen-rango" id="dgCobPeriodoBRango">—</div>
                            <div class="dg-resumen-valor" id="dgCobPeriodoBTotal">—</div>
                        </div>
                    </div>
                    <div class="dg-periodos-resumen__item">
                        <div class="dg-resumen-card dg-resumen-card--delta">
                            <div class="dg-resumen-label">A − B</div>
                            <div class="dg-resumen-rango">Variación</div>
                            <div class="dg-resumen-valor" id="dgCobPeriodosDelta">—</div>
                            <div class="dg-resumen-sub" id="dgCobPeriodosDeltaPct">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="dg-chart-wrap dg-chart-wrap--periodos">
                    <canvas id="dgGraficoCobranzasPeriodosMes"></canvas>
                    <p class="dg-chart-empty text-muted is-hidden" id="dgCobranzasPeriodosEmpty">Sin cobranzas en los períodos</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="table-responsive">
                    <table class="table table-condensed table-hover dg-tabla" id="dgTablaCobranzasPeriodos">
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
                                <th class="text-right" id="dgCobPeriodosFootA">—</th>
                                <th class="text-right" id="dgCobPeriodosFootB">—</th>
                                <th class="text-right" id="dgCobPeriodosFootDelta">—</th>
                                <th class="text-right" id="dgCobPeriodosFootDeltaPct">—</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
