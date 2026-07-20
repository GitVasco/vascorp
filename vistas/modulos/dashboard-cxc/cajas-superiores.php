<?php

if (!isset($kpisCabecera)) {
    return;
}

$k = $kpisCabecera;

$mesesCortos = array(
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
);

$labelMesAnt = isset($mesesCortos[$k['mes_anterior']]) ? $mesesCortos[$k['mes_anterior']] : '';
$textoMesAnt = trim($labelMesAnt . ' ' . $k['anio_anterior']);

if (!function_exists('cxcFormatoVariacion')) {
    function cxcFormatoVariacion($valor)
    {
        $signo = $valor > 0 ? '+' : '';
        return $signo . number_format($valor, 1) . '%';
    }

    function cxcClaseVariacion($valor, $invertir = false)
    {
        $subio = ((float) $valor) > 0;
        $bajo = ((float) $valor) < 0;

        if ($invertir) {
            if ($subio) {
                return 'cxc-trend-malo';
            }
            if ($bajo) {
                return 'cxc-trend-bueno';
            }
            return 'cxc-trend-neutro';
        }

        if ($subio) {
            return 'cxc-trend-bueno';
        }
        if ($bajo) {
            return 'cxc-trend-malo';
        }

        return 'cxc-trend-neutro';
    }

    function cxcIconoVariacion($valor)
    {
        if ($valor > 0) {
            return 'fa-arrow-up';
        }
        if ($valor < 0) {
            return 'fa-arrow-down';
        }
        return 'fa-minus';
    }
}

$pctMetaBarra = max(0, min(100, (float) $k['pct_meta']));

$cajas = array(
    array(
        'id' => 'cxcKpiVentaMes',
        'clase' => 'cxc-kpi-card--green',
        'icono' => 'fa-shopping-cart',
        'label' => 'Venta del mes',
        'valor' => 'S/ ' . number_format($k['venta_mes'], 0),
        'trend' => true,
        'var' => $k['venta_mes_var'],
        'invertir' => false,
    ),
    array(
        'id' => 'cxcKpiMetaMes',
        'clase' => 'cxc-kpi-card--purple',
        'icono' => 'fa-bullseye',
        'label' => 'Meta mensual',
        'valor' => 'S/ ' . number_format($k['meta_mes'], 0),
        'meta' => true,
        'pct_meta' => $pctMetaBarra,
    ),
    array(
        'id' => 'cxcKpiCobranzaMes',
        'clase' => 'cxc-kpi-card--teal',
        'icono' => 'fa-money',
        'label' => 'Cobranza del mes',
        'valor' => 'S/ ' . number_format($k['cobranza_mes'], 0),
        'trend' => true,
        'var' => $k['cobranza_mes_var'],
        'invertir' => false,
    ),
    array(
        'id' => 'cxcKpiTotalCxc',
        'clase' => 'cxc-kpi-card--blue',
        'icono' => 'fa-briefcase',
        'label' => 'Cuentas por cobrar',
        'valor' => 'S/ ' . number_format($k['total_por_cobrar'], 0),
        'sub' => 'Cartera pendiente al corte',
    ),
    array(
        'id' => 'cxcKpiVencido',
        'clase' => 'cxc-kpi-card--orange',
        'icono' => 'fa-exclamation-triangle',
        'label' => 'Monto vencido',
        'valor' => 'S/ ' . number_format($k['monto_vencido'], 0),
        'sub' => number_format($k['pct_vencido_cxc'], 1) . '% del total CxC',
        'alerta' => true,
    ),
    array(
        'id' => 'cxcKpiIncobrable',
        'clase' => 'cxc-kpi-card--red',
        'icono' => 'fa-ban',
        'label' => 'Cuentas incobrables',
        'valor' => 'S/ ' . number_format($k['monto_incobrable'], 0),
        'sub' => number_format($k['pct_incobrable_cxc'], 1) . '% del total CxC',
        'alerta' => true,
    ),
);
?>

<div class="cxc-resumen-ejecutivo">
    <h4 class="cxc-seccion-titulo">Resumen ejecutivo</h4>

    <div class="row cxc-kpis-row">
        <?php foreach ($cajas as $caja) : ?>
        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
            <div class="cxc-kpi-card <?php echo htmlspecialchars($caja['clase']); ?>" id="<?php echo htmlspecialchars($caja['id']); ?>">
                <div class="cxc-kpi-card__icon">
                    <i class="fa <?php echo htmlspecialchars($caja['icono']); ?>"></i>
                </div>
                <div class="cxc-kpi-card__label"><?php echo htmlspecialchars($caja['label']); ?></div>
                <div class="cxc-kpi-card__value"><?php echo $caja['valor']; ?></div>

                <?php if (!empty($caja['trend'])) : ?>
                <div class="cxc-kpi-card__trend <?php echo cxcClaseVariacion($caja['var'], !empty($caja['invertir'])); ?>">
                    <i class="fa <?php echo cxcIconoVariacion($caja['var']); ?>"></i>
                    <?php echo cxcFormatoVariacion($caja['var']); ?> vs. <?php echo htmlspecialchars($textoMesAnt); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($caja['meta'])) : ?>
                <div class="cxc-kpi-card__progress-wrap">
                    <div class="progress cxc-kpi-card__progress">
                        <div class="progress-bar cxc-kpi-card__progress-bar" style="width: <?php echo $pctMetaBarra; ?>%;"></div>
                    </div>
                    <div class="cxc-kpi-card__progress-label">
                        <?php echo number_format($k['pct_meta'], 1); ?>% de cumplimiento
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($caja['sub'])) : ?>
                <div class="cxc-kpi-card__sub<?php echo !empty($caja['alerta']) ? ' cxc-kpi-card__sub--alerta' : ''; ?>">
                    <?php echo htmlspecialchars($caja['sub']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
