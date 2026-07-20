<?php

if (!isset($filtros)) {
    return;
}

$proyeccion = ControladorDashboardCxc::ctrProyeccionPagos($filtros, 6);
$vencido = isset($proyeccion['vencido']) ? $proyeccion['vencido'] : array();
$incobrable = isset($proyeccion['incobrable']) ? $proyeccion['incobrable'] : array();
$posterior = isset($proyeccion['posterior']) ? $proyeccion['posterior'] : null;
$meses = isset($proyeccion['meses']) ? $proyeccion['meses'] : array();
$totales = isset($proyeccion['totales']) ? $proyeccion['totales'] : array();
$totalGeneral = isset($totales['general']) ? (float) $totales['general'] : 0;
$totalProyeccion = isset($totales['proyeccion']) ? (float) $totales['proyeccion'] : 0;
?>

<div class="box box-default cxc-panel cxc-panel-proyeccion-pagos">
    <div class="box-header with-border">
        <h3 class="box-title">Proyección de cobranza</h3>
        <div class="box-tools">
            <span class="label label-primary cxc-proyeccion-badge">
                Próx. <?php echo (int) $proyeccion['limite_meses']; ?> meses: S/ <?php echo number_format($totalProyeccion, 0); ?>
            </span>
        </div>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-condensed cxc-tabla-proyeccion" id="tablaProyeccionPagos">
            <thead>
                <tr>
                    <th>Periodo</th>
                    <th class="text-right cxc-proyeccion-th-facturas">Fact. / Guías</th>
                    <th class="text-right cxc-proyeccion-th-letras">Letras</th>
                    <th class="text-right cxc-proyeccion-th-otros">Otros</th>
                    <th class="text-right cxc-proyeccion-th-total">Total</th>
                    <th class="text-right">Docs.</th>
                    <th class="text-right">Clientes</th>
                    <th class="text-right cxc-proyeccion-th-pct">%</th>
                </tr>
            </thead>
            <tbody>
                <?php if ((float) $vencido['total'] > 0) { ?>
                <?php
                $fila = $vencido;
                $fila['label'] = 'Vencido (pendiente)';
                ?>
                <tr class="cxc-proyeccion-fila-vencido">
                    <td><strong><?php echo htmlspecialchars($fila['label']); ?></strong></td>
                    <td class="text-right">S/ <?php echo number_format($fila['facturas_guias'], 0); ?></td>
                    <td class="text-right">S/ <?php echo number_format($fila['letras'], 0); ?></td>
                    <td class="text-right">S/ <?php echo number_format($fila['otros'], 0); ?></td>
                    <td class="text-right cxc-proyeccion-total"><strong>S/ <?php echo number_format($fila['total'], 0); ?></strong></td>
                    <td class="text-right"><?php echo number_format($fila['documentos'], 0); ?></td>
                    <td class="text-right"><?php echo number_format($fila['clientes'], 0); ?></td>
                    <td class="text-right cxc-proyeccion-pct"><?php echo number_format($fila['pct'], 1); ?>%</td>
                </tr>
                <?php } ?>

                <?php if ((float) $incobrable['total'] > 0) { ?>
                <tr class="cxc-proyeccion-fila-incobrable">
                    <td><strong>Incobrables</strong></td>
                    <td class="text-right">S/ <?php echo number_format($incobrable['facturas_guias'], 0); ?></td>
                    <td class="text-right">S/ <?php echo number_format($incobrable['letras'], 0); ?></td>
                    <td class="text-right">S/ <?php echo number_format($incobrable['otros'], 0); ?></td>
                    <td class="text-right cxc-proyeccion-total"><strong>S/ <?php echo number_format($incobrable['total'], 0); ?></strong></td>
                    <td class="text-right"><?php echo number_format($incobrable['documentos'], 0); ?></td>
                    <td class="text-right"><?php echo number_format($incobrable['clientes'], 0); ?></td>
                    <td class="text-right cxc-proyeccion-pct"><?php echo number_format($incobrable['pct'], 1); ?>%</td>
                </tr>
                <?php } ?>

                <?php if (count($meses) === 0 && (float) $vencido['total'] <= 0 && (float) $incobrable['total'] <= 0) { ?>
                <tr><td colspan="8" class="text-muted">Sin vencimientos proyectados en el horizonte</td></tr>
                <?php } else { ?>
                    <?php foreach ($meses as $fila) : ?>
                    <tr class="cxc-proyeccion-fila-mes">
                        <td><?php echo htmlspecialchars($fila['label']); ?></td>
                        <td class="text-right">S/ <?php echo number_format($fila['facturas_guias'], 0); ?></td>
                        <td class="text-right">S/ <?php echo number_format($fila['letras'], 0); ?></td>
                        <td class="text-right">S/ <?php echo number_format($fila['otros'], 0); ?></td>
                        <td class="text-right cxc-proyeccion-total"><strong>S/ <?php echo number_format($fila['total'], 0); ?></strong></td>
                        <td class="text-right"><?php echo number_format($fila['documentos'], 0); ?></td>
                        <td class="text-right"><?php echo number_format($fila['clientes'], 0); ?></td>
                        <td class="text-right cxc-proyeccion-pct"><?php echo number_format($fila['pct'], 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                <?php } ?>

                <?php if (is_array($posterior) && (float) $posterior['total'] > 0) { ?>
                <tr class="cxc-proyeccion-fila-posterior">
                    <td><?php echo htmlspecialchars($posterior['label']); ?></td>
                    <td class="text-right">S/ <?php echo number_format($posterior['facturas_guias'], 0); ?></td>
                    <td class="text-right">S/ <?php echo number_format($posterior['letras'], 0); ?></td>
                    <td class="text-right">S/ <?php echo number_format($posterior['otros'], 0); ?></td>
                    <td class="text-right cxc-proyeccion-total"><strong>S/ <?php echo number_format($posterior['total'], 0); ?></strong></td>
                    <td class="text-right"><?php echo number_format($posterior['documentos'], 0); ?></td>
                    <td class="text-right"><?php echo number_format($posterior['clientes'], 0); ?></td>
                    <td class="text-right cxc-proyeccion-pct"><?php echo number_format($posterior['pct'], 1); ?>%</td>
                </tr>
                <?php } ?>
            </tbody>
            <?php if ($totalGeneral > 0) { ?>
            <tfoot>
                <tr class="cxc-proyeccion-total-row">
                    <td><strong>Total cartera</strong></td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right cxc-proyeccion-total"><strong>S/ <?php echo number_format($totalGeneral, 0); ?></strong></td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right"><strong>100%</strong></td>
                </tr>
            </tfoot>
            <?php } ?>
        </table>
    </div>
    <?php if (!empty($proyeccion['nota'])) { ?>
    <div class="box-footer cxc-proyeccion-nota text-muted">
        <?php echo htmlspecialchars($proyeccion['nota']); ?>
    </div>
    <?php } ?>
</div>
