<?php

if (!isset($filtros)) {
    return;
}

$porRango = ControladorDashboardCxc::ctrPorRango($filtros);

$labelsRango = array(
    '0-30' => '0–30 días',
    '31-60' => '31–60 días',
    '61-90' => '61–90 días',
    '91-180' => '91–180 días',
    '180+' => '+180 días',
    'incobrable' => 'Incobrables',
);
?>

<div class="box box-default cxc-panel">
    <div class="box-header with-border">
        <h3 class="box-title">Saldos por rango de vencimiento</h3>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-condensed cxc-tabla-rangos" id="tablaVencimientoRangos">
            <thead>
                <tr>
                    <th>Rango</th>
                    <th class="text-right">Monto</th>
                    <th class="text-right">%</th>
                    <th class="text-right">Clientes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($porRango) === 0) { ?>
                <tr><td colspan="4" class="text-muted">Sin datos</td></tr>
                <?php } else { ?>
                    <?php foreach ($porRango as $fila) :
                        $idRango = $fila['rango'];
                        $label = isset($labelsRango[$idRango]) ? $labelsRango[$idRango] : $idRango;
                    ?>
                    <tr class="cxc-fila-rango-detalle" data-rango="<?php echo htmlspecialchars($idRango); ?>">
                        <td>
                            <a href="#" class="cxc-link-rango" title="Filtrar detalle por rango">
                                <?php echo htmlspecialchars($label); ?>
                            </a>
                        </td>
                        <td class="text-right">S/ <?php echo number_format($fila['monto'], 0); ?></td>
                        <td class="text-right"><?php echo number_format($fila['porcentaje'], 1); ?>%</td>
                        <td class="text-right"><?php echo number_format($fila['clientes'], 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
