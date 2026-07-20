<?php

if (!isset($resumenCobranza)) {
    return;
}

$r = $resumenCobranza;
$antiguedad = isset($r['antiguedad']) ? $r['antiguedad'] : array('rangos' => array(), 'total' => 0);
$rangos = isset($antiguedad['rangos']) ? $antiguedad['rangos'] : array();
$totalAnt = isset($antiguedad['total']) ? (float) $antiguedad['total'] : 0;

$varProy = (float) $r['cobranza_proyectada_30_var'];
$claseVarProy = $varProy > 0 ? 'cxc-variacion--sube' : ($varProy < 0 ? 'cxc-variacion--baja' : 'cxc-variacion--neutro');
$signoVarProy = $varProy > 0 ? '+' : '';
?>

<div class="box box-default cxc-panel cxc-cobranza-resumen">
    <div class="box-header with-border cxc-cobranza-resumen__header">
        <h3 class="box-title">Cobranza — Resumen</h3>
    </div>
    <div class="box-body cxc-cobranza-resumen__body">
        <div class="row cxc-cobranza-resumen__row">
            <div class="col-md-6 col-sm-6 col-xs-12 cxc-cobranza-resumen__metricas">
                <div class="cxc-cobranza-metrica cxc-cobranza-metrica--total">
                    <span class="cxc-cobranza-metrica__label">Total por cobrar</span>
                    <span class="cxc-cobranza-metrica__valor">S/ <?php echo number_format($r['total_por_cobrar'], 0); ?></span>
                </div>

                <div class="cxc-cobranza-metrica">
                    <span class="cxc-cobranza-metrica__label">Monto vencido</span>
                    <div class="cxc-cobranza-metrica__fila cxc-cobranza-metrica__fila--valores">
                        <span class="cxc-cobranza-metrica__valor cxc-cobranza-metrica__valor--sm">S/ <?php echo number_format($r['monto_vencido'], 0); ?></span>
                        <span class="cxc-cobranza-metrica__pct cxc-pct--vencido"><?php echo number_format($r['pct_vencido'], 1); ?>%</span>
                    </div>
                </div>

                <div class="cxc-cobranza-metrica">
                    <span class="cxc-cobranza-metrica__label">Monto por vencer</span>
                    <div class="cxc-cobranza-metrica__fila cxc-cobranza-metrica__fila--valores">
                        <span class="cxc-cobranza-metrica__valor cxc-cobranza-metrica__valor--sm">S/ <?php echo number_format($r['monto_por_vencer'], 0); ?></span>
                        <span class="cxc-cobranza-metrica__pct cxc-pct--vigente"><?php echo number_format($r['pct_por_vencer'], 1); ?>%</span>
                    </div>
                </div>

                <div class="cxc-cobranza-metrica">
                    <span class="cxc-cobranza-metrica__label">Cuentas incobrables</span>
                    <div class="cxc-cobranza-metrica__fila cxc-cobranza-metrica__fila--valores">
                        <span class="cxc-cobranza-metrica__valor cxc-cobranza-metrica__valor--sm">S/ <?php echo number_format($r['monto_incobrable'], 0); ?></span>
                        <span class="cxc-cobranza-metrica__pct cxc-pct--incobrable"><?php echo number_format($r['pct_incobrable'], 1); ?>%</span>
                    </div>
                </div>

                <div class="cxc-cobranza-metrica cxc-cobranza-metrica--proy">
                    <span class="cxc-cobranza-metrica__label">Cobranza proyectada (30 días)</span>
                    <span class="cxc-cobranza-metrica__proy">
                        <span class="cxc-cobranza-metrica__valor cxc-cobranza-metrica__valor--sm">S/ <?php echo number_format($r['cobranza_proyectada_30'], 0); ?></span>
                        <?php if ($r['cobranza_proyectada_30_ant'] > 0 || $r['cobranza_proyectada_30'] > 0) { ?>
                        <span class="cxc-cobranza-metrica__variacion <?php echo htmlspecialchars($claseVarProy); ?>">
                            <?php echo htmlspecialchars($signoVarProy . number_format($varProy, 1) . '%'); ?> vs 30 días anteriores
                        </span>
                        <?php } ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-sm-6 col-xs-12 cxc-cobranza-resumen__grafico">
                <h4 class="cxc-cobranza-grafico__titulo">Antigüedad de saldos vencidos</h4>
                <div class="cxc-cobranza-grafico__total" id="cxcDonutTotalVencido">
                    <span class="cxc-cobranza-grafico__total-label">Total vencido</span>
                    <span class="cxc-cobranza-grafico__total-valor">S/ <?php echo number_format($totalAnt, 0); ?></span>
                </div>
                <div class="cxc-cobranza-grafico__contenido">
                    <div class="cxc-cobranza-donut-wrap">
                        <div class="chart-responsive cxc-grafico-wrap cxc-grafico-wrap--donut" id="wrapGraficoAntiguedadCxc">
                            <canvas id="graficoAntiguedadCxc"></canvas>
                        </div>
                        <div id="graficoAntiguedadCxcEmpty" class="cxc-empty-state cxc-empty-state--donut" style="display:none;">
                            Sin saldos pendientes.
                        </div>
                    </div>
                    <ul class="cxc-donut-leyenda">
                        <?php if (count($rangos) === 0) { ?>
                        <li class="text-muted">Sin datos</li>
                        <?php } else { ?>
                            <?php foreach ($rangos as $rango) : ?>
                            <li class="cxc-donut-leyenda__item">
                                <span class="cxc-donut-leyenda__dot" style="background: <?php echo htmlspecialchars($rango['color']); ?>;"></span>
                                <span class="cxc-donut-leyenda__label"><?php echo htmlspecialchars($rango['label']); ?></span>
                                <span class="cxc-donut-leyenda__monto">S/ <?php echo number_format($rango['monto'], 0); ?></span>
                                <span class="cxc-donut-leyenda__pct"><?php echo number_format($rango['porcentaje'], 1); ?>%</span>
                            </li>
                            <?php endforeach; ?>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="cxcAntiguedadInitialData"><?php echo json_encode($antiguedad, JSON_UNESCAPED_UNICODE); ?></script>
