<?php

if (!isset($antiguedadCxc)) {
    return;
}

$rangos = isset($antiguedadCxc['rangos']) ? $antiguedadCxc['rangos'] : array();
$total = isset($antiguedadCxc['total']) ? (float) $antiguedadCxc['total'] : 0;
?>

<div class="box box-default cxc-panel">
    <div class="box-header with-border">
        <h3 class="box-title">Resumen por antigüedad</h3>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-condensed cxc-tabla-resumen" id="tablaResumenAntiguedad">
            <thead>
                <tr>
                    <th>Rango</th>
                    <th class="text-right">Monto</th>
                    <th class="text-right">%</th>
                    <th class="text-right">Clientes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rangos) === 0) { ?>
                <tr><td colspan="4" class="text-muted">Sin datos</td></tr>
                <?php } else { ?>
                    <?php foreach ($rangos as $rango) : ?>
                    <tr class="cxc-fila-rango" data-rango="<?php echo htmlspecialchars($rango['id']); ?>">
                        <td>
                            <span class="cxc-rango-dot" style="background: <?php echo htmlspecialchars($rango['color']); ?>;"></span>
                            <?php echo htmlspecialchars($rango['label']); ?>
                        </td>
                        <td class="text-right">S/ <?php echo number_format($rango['monto'], 0); ?></td>
                        <td class="text-right"><?php echo number_format($rango['porcentaje'], 1); ?>%</td>
                        <td class="text-right"><?php echo number_format($rango['clientes'], 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="cxc-fila-total">
                        <td><strong>Total cartera</strong></td>
                        <td class="text-right"><strong>S/ <?php echo number_format($total, 0); ?></strong></td>
                        <td class="text-right"><strong>100%</strong></td>
                        <td></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php if (!empty($antiguedadCxc['nota'])) { ?>
        <p class="cxc-nota-antiguedad text-muted"><?php echo htmlspecialchars($antiguedadCxc['nota']); ?></p>
        <?php } ?>
    </div>
</div>
