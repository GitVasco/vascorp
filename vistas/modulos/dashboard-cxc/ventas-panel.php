<?php

if (!isset($ventasDashboard)) {
    return;
}

$porVendedor = isset($ventasDashboard['por_vendedor']) ? $ventasDashboard['por_vendedor'] : array();
$tipoDoc = isset($ventasDashboard['por_tipo_documento']) ? $ventasDashboard['por_tipo_documento'] : array('filas' => array(), 'total' => 0);
$filasTipo = isset($tipoDoc['filas']) ? $tipoDoc['filas'] : array();
$porZona = isset($ventasDashboard['por_zona']) ? $ventasDashboard['por_zona'] : array('filas' => array(), 'total' => 0);
$filasZona = isset($porZona['filas']) ? $porZona['filas'] : array();
$tendencia = isset($ventasDashboard['tendencia']) ? $ventasDashboard['tendencia'] : array();
$labelAct = isset($tendencia['mes_actual']['label']) ? $tendencia['mes_actual']['label'] : 'Mes actual';
$labelAnt = isset($tendencia['mes_anterior']['label']) ? $tendencia['mes_anterior']['label'] : 'Mes anterior';
$totalAct = isset($tendencia['mes_actual']['total']) ? (float) $tendencia['mes_actual']['total'] : 0;
$totalAnt = isset($tendencia['mes_anterior']['total']) ? (float) $tendencia['mes_anterior']['total'] : 0;
$variacionTendencia = $totalAnt > 0 ? round((($totalAct - $totalAnt) / $totalAnt) * 100, 1) : 0;
$claseVariacion = $variacionTendencia > 0 ? 'cxc-variacion--sube' : ($variacionTendencia < 0 ? 'cxc-variacion--baja' : 'cxc-variacion--neutro');
$signoVariacion = $variacionTendencia > 0 ? '+' : '';
?>

<div class="box box-default cxc-panel cxc-panel-ventas-unificado">
    <div class="box-body cxc-panel-ventas-unificado__body">
        <div class="row cxc-ventas-unificado-inner">
            <div class="col-md-6 col-sm-6 col-xs-12 cxc-ventas-col-tabla">
                <div class="cxc-ventas-tabs-header">
                    <ul class="nav nav-tabs cxc-ventas-tabs" role="tablist">
                        <li class="active">
                            <a href="#cxcVentasTabVendedor" data-toggle="tab" role="tab">Por vendedor</a>
                        </li>
                        <li>
                            <a href="#cxcVentasTabTipoDoc" data-toggle="tab" role="tab">Por tipo documento</a>
                        </li>
                        <li>
                            <a href="#cxcVentasTabZona" data-toggle="tab" role="tab">Por zona</a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content cxc-ventas-tab-content">
                    <div class="tab-pane active" id="cxcVentasTabVendedor" role="tabpanel">
                        <div class="table-responsive cxc-tabla-scroll">
                            <table class="table table-hover table-condensed cxc-tabla-ventas-vendedor" id="tablaVentasVendedor">
                                <thead>
                                    <tr>
                                        <th>Vendedor</th>
                                        <th class="text-right">Venta del mes</th>
                                        <th class="text-right">Venta del año</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($porVendedor) === 0) { ?>
                                    <tr><td colspan="3" class="text-muted">Sin ventas en el período</td></tr>
                                    <?php } else { ?>
                                        <?php foreach ($porVendedor as $fila) : ?>
                                        <tr class="cxc-fila-venta-vendedor" data-vendedor="<?php echo htmlspecialchars($fila['vendedor']); ?>">
                                            <td>
                                                <a href="#" class="cxc-link-vendedor" title="Filtrar por vendedor">
                                                    <?php echo htmlspecialchars($fila['vendedor'] . ' - ' . $fila['nom_vendedor']); ?>
                                                </a>
                                            </td>
                                            <td class="text-right">S/ <?php echo number_format($fila['venta_mes'], 0); ?></td>
                                            <td class="text-right">S/ <?php echo number_format($fila['venta_anio'], 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="cxcVentasTabTipoDoc" role="tabpanel">
                        <div class="table-responsive cxc-tabla-scroll">
                            <table class="table table-hover table-condensed cxc-tabla-ventas-tipo" id="tablaVentasTipoDoc">
                                <thead>
                                    <tr>
                                        <th>Tipo documento</th>
                                        <th class="text-right">Venta neta</th>
                                        <th class="text-right">%</th>
                                        <th class="text-right">Documentos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($filasTipo) === 0) { ?>
                                    <tr><td colspan="4" class="text-muted">Sin ventas en el período</td></tr>
                                    <?php } else { ?>
                                        <?php foreach ($filasTipo as $fila) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fila['tipo_documento']); ?></td>
                                            <td class="text-right">S/ <?php echo number_format($fila['venta'], 0); ?></td>
                                            <td class="text-right"><?php echo number_format($fila['porcentaje'], 1); ?>%</td>
                                            <td class="text-right"><?php echo number_format($fila['documentos'], 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="cxc-fila-total">
                                            <td><strong>Total</strong></td>
                                            <td class="text-right"><strong>S/ <?php echo number_format($tipoDoc['total'], 0); ?></strong></td>
                                            <td class="text-right"><strong>100%</strong></td>
                                            <td></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="cxcVentasTabZona" role="tabpanel">
                        <div class="table-responsive cxc-tabla-scroll">
                            <table class="table table-hover table-condensed cxc-tabla-ventas-zona" id="tablaVentasZona">
                                <thead>
                                    <tr>
                                        <th class="cxc-col-zona-color"></th>
                                        <th>Zona</th>
                                        <th class="text-right">Venta neta</th>
                                        <th class="text-right">%</th>
                                        <th class="text-right">Clientes</th>
                                        <th class="text-right">Docs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($filasZona) === 0) { ?>
                                    <tr><td colspan="6" class="text-muted">Sin ventas con zona asignada</td></tr>
                                    <?php } else { ?>
                                        <?php foreach ($filasZona as $fila) :
                                            $colorZona = isset($fila['color_zona']) ? $fila['color_zona'] : '#777777';
                                        ?>
                                        <tr class="cxc-fila-venta-zona" data-zona="<?php echo (int) $fila['id_zona']; ?>">
                                            <td class="cxc-col-zona-color">
                                                <span class="cxc-zona-color" style="background-color: <?php echo htmlspecialchars($colorZona); ?>;" title="<?php echo htmlspecialchars($colorZona); ?>"></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($fila['nombre_zona']); ?></td>
                                            <td class="text-right">S/ <?php echo number_format($fila['venta'], 0); ?></td>
                                            <td class="text-right"><?php echo number_format($fila['porcentaje'], 1); ?>%</td>
                                            <td class="text-right"><?php echo number_format($fila['clientes'], 0); ?></td>
                                            <td class="text-right"><?php echo number_format($fila['documentos'], 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="cxc-fila-total">
                                            <td></td>
                                            <td><strong>Total</strong></td>
                                            <td class="text-right"><strong>S/ <?php echo number_format($porZona['total'], 0); ?></strong></td>
                                            <td class="text-right"><strong>100%</strong></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-sm-6 col-xs-12 cxc-ventas-col-grafico">
                <div class="cxc-spark-ventas">
                    <div class="cxc-spark-ventas__header">
                        <h3 class="cxc-spark-ventas__titulo">Tendencia diaria</h3>
                        <div class="cxc-spark-ventas__metricas">
                            <span class="cxc-spark-ventas__monto">S/ <?php echo number_format($totalAct, 0); ?></span>
                            <?php if ($totalAnt > 0) { ?>
                            <span class="cxc-spark-ventas__variacion <?php echo htmlspecialchars($claseVariacion); ?>">
                                <?php echo htmlspecialchars($signoVariacion . number_format($variacionTendencia, 1) . '%'); ?>
                            </span>
                            <?php } ?>
                        </div>
                        <p class="cxc-spark-ventas__sub">
                            vs <?php echo htmlspecialchars($labelAnt); ?> · <?php echo htmlspecialchars($labelAct); ?>
                        </p>
                    </div>
                    <div class="cxc-spark-ventas__chart">
                        <div class="chart-responsive cxc-grafico-wrap cxc-grafico-wrap--sparkline" id="wrapGraficoVentasTendencia">
                            <canvas id="graficoVentasTendencia"></canvas>
                        </div>
                        <div id="graficoVentasTendenciaEmpty" class="cxc-empty-state cxc-empty-state--spark" style="display:none;">
                            Sin ventas diarias en el período.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="cxcVentasTendenciaData"><?php echo json_encode($tendencia, JSON_UNESCAPED_UNICODE); ?></script>
