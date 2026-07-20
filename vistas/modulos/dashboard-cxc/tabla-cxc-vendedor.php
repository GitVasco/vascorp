<?php

if (!isset($filtros)) {
    return;
}

$porVendedor = ControladorDashboardCxc::ctrPorVendedor($filtros);
$totales = array(
    'clientes' => 0,
    'por_vencer' => 0,
    'vencido' => 0,
    'incobrable' => 0,
    'total' => 0,
);

foreach ($porVendedor as $fila) {
    $totales['clientes'] += (int) $fila['clientes'];
    $totales['por_vencer'] += (float) $fila['por_vencer'];
    $totales['vencido'] += (float) $fila['vencido'];
    $totales['incobrable'] += (float) $fila['incobrable'];
    $totales['total'] += (float) $fila['total'];
}

$pctTotalVencido = $totales['total'] > 0
    ? round(($totales['vencido'] / $totales['total']) * 100, 1)
    : 0;

$cxcClasePctVencido = function ($pct) {
    if ($pct >= 50) {
        return 'cxc-tabla-vendedor__pct--alto';
    }
    if ($pct >= 25) {
        return 'cxc-tabla-vendedor__pct--medio';
    }

    return 'cxc-tabla-vendedor__pct--bajo';
};

?>

<div class="box box-default cxc-panel cxc-panel-cartera-vendedor">
    <div class="box-header with-border">
        <h3 class="box-title">Cartera por vendedor</h3>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-condensed cxc-tabla-vendedor" id="tablaCxcVendedor">
            <thead>
                <tr>
                    <th class="cxc-tabla-vendedor__rank">#</th>
                    <th>Vendedor</th>
                    <th class="text-right">Clientes</th>
                    <th class="text-right cxc-tabla-vendedor__th-por-vencer">Por vencer</th>
                    <th class="text-right cxc-tabla-vendedor__th-vencido">Vencido</th>
                    <th class="text-right cxc-tabla-vendedor__th-incobrable">Incob.</th>
                    <th class="text-right cxc-tabla-vendedor__th-total">Total</th>
                    <th class="text-right cxc-tabla-vendedor__th-pct">% Venc.</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($porVendedor) === 0) { ?>
                <tr><td colspan="8" class="text-muted">Sin datos</td></tr>
                <?php } else { ?>
                    <?php foreach ($porVendedor as $i => $fila) : ?>
                    <?php $clasePct = $cxcClasePctVencido((float) $fila['pct_vencido']); ?>
                    <tr class="cxc-fila-vendedor" data-vendedor="<?php echo htmlspecialchars($fila['vendedor']); ?>">
                        <td class="text-muted cxc-tabla-vendedor__rank"><?php echo $i + 1; ?></td>
                        <td>
                            <a href="#" class="cxc-link-vendedor" title="Filtrar por este vendedor">
                                <?php echo htmlspecialchars($fila['vendedor'] . ' - ' . $fila['nom_vendedor']); ?>
                            </a>
                        </td>
                        <td class="text-right"><?php echo number_format($fila['clientes'], 0); ?></td>
                        <td class="text-right cxc-tabla-vendedor__por-vencer">S/ <?php echo number_format($fila['por_vencer'], 0); ?></td>
                        <td class="text-right cxc-tabla-vendedor__vencido">S/ <?php echo number_format($fila['vencido'], 0); ?></td>
                        <td class="text-right cxc-tabla-vendedor__incobrable">S/ <?php echo number_format($fila['incobrable'], 0); ?></td>
                        <td class="text-right cxc-tabla-vendedor__total-celda">S/ <?php echo number_format($fila['total'], 0); ?></td>
                        <td class="text-right cxc-tabla-vendedor__pct <?php echo $clasePct; ?>"><?php echo number_format($fila['pct_vencido'], 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
            <?php if (count($porVendedor) > 0) { ?>
            <tfoot>
                <tr class="cxc-tabla-vendedor__total">
                    <td></td>
                    <td><strong>Total</strong></td>
                    <td class="text-right"><strong><?php echo number_format($totales['clientes'], 0); ?></strong></td>
                    <td class="text-right cxc-tabla-vendedor__por-vencer"><strong>S/ <?php echo number_format($totales['por_vencer'], 0); ?></strong></td>
                    <td class="text-right cxc-tabla-vendedor__vencido"><strong>S/ <?php echo number_format($totales['vencido'], 0); ?></strong></td>
                    <td class="text-right cxc-tabla-vendedor__incobrable"><strong>S/ <?php echo number_format($totales['incobrable'], 0); ?></strong></td>
                    <td class="text-right cxc-tabla-vendedor__total-celda"><strong>S/ <?php echo number_format($totales['total'], 0); ?></strong></td>
                    <td class="text-right cxc-tabla-vendedor__pct <?php echo $cxcClasePctVencido($pctTotalVencido); ?>"><strong><?php echo number_format($pctTotalVencido, 1); ?>%</strong></td>
                </tr>
            </tfoot>
            <?php } ?>
        </table>
    </div>
    <div class="box-footer cxc-proyeccion-nota text-muted">
        Por vencer + Vencido + Incob. = Total. Vencido coincide con Cobranza — Resumen (sin incobrables).
    </div>
</div>
