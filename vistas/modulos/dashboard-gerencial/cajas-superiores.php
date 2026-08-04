<?php
if (!isset($kpis)) {
    return;
}

$k = $kpis;
$periodoAnual = !empty($k['periodo_anual']);

if (!function_exists('dgFormatoVariacion')) {
    function dgFormatoVariacion($valor)
    {
        if ($valor === null) {
            return '—';
        }
        $signo = $valor > 0 ? '+' : '';
        return $signo . number_format((float) $valor, 1) . '%';
    }

    function dgClaseVariacion($valor)
    {
        if ($valor === null) {
            return 'dg-trend-neutro';
        }
        if ((float) $valor > 0) {
            return 'dg-trend-bueno';
        }
        if ((float) $valor < 0) {
            return 'dg-trend-malo';
        }
        return 'dg-trend-neutro';
    }

    function dgIconoVariacion($valor)
    {
        if ($valor === null || (float) $valor == 0) {
            return 'fa-minus';
        }
        return ((float) $valor > 0) ? 'fa-arrow-up' : 'fa-arrow-down';
    }

    function dgFormatoMonto($valor)
    {
        if ($valor === null) {
            return '—';
        }
        return 'S/ ' . number_format((float) $valor, 0);
    }
}

$cajas = array(
    array(
        'id' => 'dgKpiVentaMes',
        'clase' => 'dg-kpi-card--green',
        'icono' => 'fa-shopping-cart',
        'label' => $periodoAnual ? 'Venta del año' : 'Venta del mes',
        'valor' => dgFormatoMonto($k['venta_mes']),
        'var' => $k['venta_mes_var'],
        'hint' => 'Vs mismo período N-1',
    ),
    array(
        'id' => 'dgKpiVentaYtd',
        'clase' => 'dg-kpi-card--teal',
        'icono' => 'fa-line-chart',
        'label' => 'Venta YTD',
        'valor' => dgFormatoMonto($k['venta_ytd']),
        'var' => $k['venta_ytd_var'],
        'hint' => 'Acumulado vs mismo tramo N-1',
    ),
    array(
        'id' => 'dgKpiCobranzaMes',
        'clase' => 'dg-kpi-card--blue',
        'icono' => 'fa-money',
        'label' => $periodoAnual ? 'Cobranza del año' : 'Cobranza del mes',
        'valor' => dgFormatoMonto($k['cobranza_mes']),
        'var' => $k['cobranza_mes_var'],
        'hint' => 'Sin IGV · vs mismo período N-1',
    ),
    array(
        'id' => 'dgKpiCobranzaYtd',
        'clase' => 'dg-kpi-card--indigo',
        'icono' => 'fa-bank',
        'label' => 'Cobranza YTD',
        'valor' => dgFormatoMonto($k['cobranza_ytd']),
        'var' => $k['cobranza_ytd_var'],
        'hint' => 'Sin IGV · acumulado vs N-1',
    ),
    array(
        'id' => 'dgKpiRecuperacion',
        'clase' => 'dg-kpi-card--orange',
        'icono' => 'fa-percent',
        'label' => '% Recuperación',
        'valor' => isset($k['pct_recuperacion']) && $k['pct_recuperacion'] !== null
            ? number_format((float) $k['pct_recuperacion'], 1) . '%'
            : '—',
        'var' => null,
        'hint' => 'Origen = mes del pago',
        'pendiente' => false,
        'sin_var' => true,
    ),
    array(
        'id' => 'dgKpiProyeccion',
        'clase' => 'dg-kpi-card--purple',
        'icono' => 'fa-calendar',
        'label' => '% A tiempo',
        'valor' => isset($k['proyeccion_vs_real']) && $k['proyeccion_vs_real'] !== null
            ? number_format((float) $k['proyeccion_vs_real'], 1) . '%'
            : '—',
        'var' => null,
        'hint' => 'Pago ≤ vencimiento',
        'pendiente' => false,
        'sin_var' => true,
    ),
);
?>

<div class="row dg-kpi-row">
    <?php foreach ($cajas as $caja) : ?>
        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
            <div class="dg-kpi-card <?php echo htmlspecialchars($caja['clase']); ?>" id="<?php echo htmlspecialchars($caja['id']); ?>">
                <div class="dg-kpi-icon"><i class="fa <?php echo htmlspecialchars($caja['icono']); ?>"></i></div>
                <div class="dg-kpi-body">
                    <div class="dg-kpi-label"><?php echo htmlspecialchars($caja['label']); ?></div>
                    <div class="dg-kpi-valor" data-role="valor"><?php echo htmlspecialchars($caja['valor']); ?></div>
                    <?php if (!empty($caja['pendiente']) || !empty($caja['sin_var'])) : ?>
                        <div class="dg-kpi-trend dg-trend-neutro" data-role="trend">
                            <span><?php echo htmlspecialchars($caja['hint']); ?></span>
                        </div>
                    <?php else : ?>
                        <div class="dg-kpi-trend <?php echo dgClaseVariacion($caja['var']); ?>" data-role="trend">
                            <i class="fa <?php echo dgIconoVariacion($caja['var']); ?>"></i>
                            <span><?php echo htmlspecialchars(dgFormatoVariacion($caja['var'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
