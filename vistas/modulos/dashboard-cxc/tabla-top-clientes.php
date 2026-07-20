<?php

if (!isset($filtros)) {
    return;
}

$topClientes = ControladorDashboardCxc::ctrTopClientes($filtros, 10);

$labelsRiesgo = array(
    'bajo' => 'Bajo',
    'medio' => 'Medio',
    'alto' => 'Alto',
    '180+' => '+180 días',
    'incobrable' => 'Incobrable',
);

$clasesRiesgo = array(
    'bajo' => 'label-success',
    'medio' => 'label-warning',
    'alto' => 'label-danger',
    '180+' => 'label-danger',
    'incobrable' => 'label-purple',
);

$cxcEtiquetaEntidad = function ($codigo, $nombre) {
    $codigo = trim((string) $codigo);
    $nombre = trim((string) $nombre);

    if ($codigo === '') {
        return $nombre;
    }
    if ($nombre === '' || $nombre === $codigo) {
        return $codigo;
    }

    return $codigo . ' - ' . $nombre;
};
?>

<div class="box box-default cxc-panel">
    <div class="box-header with-border">
        <h3 class="box-title">Top 10 por deuda vencida</h3>
        <div class="box-tools">
            <span class="label label-default cxc-top-clientes-leyenda">Clientes y grupos comerciales</span>
        </div>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-condensed cxc-tabla-top-clientes" id="tablaTopClientesCxc">
            <thead>
                <tr>
                    <th>Cliente / Grupo</th>
                    <th class="text-right cxc-top-clientes-th-linea">Línea créd.</th>
                    <th class="text-right">Saldo</th>
                    <th class="text-right">Vencido</th>
                    <th class="text-right">Días</th>
                    <th>Vendedor</th>
                    <th>Riesgo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($topClientes) === 0) { ?>
                <tr><td colspan="7" class="text-muted">Sin datos</td></tr>
                <?php } else { ?>
                    <?php foreach ($topClientes as $fila) :
                        $riesgo = isset($fila['riesgo']) ? $fila['riesgo'] : 'bajo';
                        $labelRiesgo = isset($labelsRiesgo[$riesgo]) ? $labelsRiesgo[$riesgo] : $riesgo;
                        $claseRiesgo = isset($clasesRiesgo[$riesgo]) ? $clasesRiesgo[$riesgo] : 'label-default';
                        $tipoEntidad = isset($fila['tipo_entidad']) ? $fila['tipo_entidad'] : 'cliente';
                        $esGrupo = $tipoEntidad === 'grupo';
                        $codigo = isset($fila['codigo']) ? $fila['codigo'] : '';
                        $nombre = isset($fila['nombre_cliente']) ? $fila['nombre_cliente'] : '';
                        $etiqueta = $cxcEtiquetaEntidad($codigo, $nombre);
                        $numClientes = isset($fila['num_clientes']) ? (int) $fila['num_clientes'] : 1;
                        $dataCliente = $esGrupo ? '' : (isset($fila['cliente']) ? $fila['cliente'] : $codigo);
                        $lineaCredito = isset($fila['linea_credito']) ? $fila['linea_credito'] : null;
                        $lineaEtiqueta = isset($fila['linea_credito_etiqueta']) ? trim((string) $fila['linea_credito_etiqueta']) : '';
                        $sinLinea = $lineaCredito === null || (float) $lineaCredito <= 0;
                        $titleLinea = $lineaEtiqueta !== '' ? $lineaEtiqueta : 'Sin línea registrada';
                    ?>
                    <tr class="cxc-fila-cliente<?php echo $esGrupo ? ' cxc-fila-cliente--grupo' : ''; ?>"
                        data-tipo="<?php echo htmlspecialchars($tipoEntidad); ?>"
                        data-cliente="<?php echo htmlspecialchars($dataCliente); ?>"
                        data-grupo="<?php echo $esGrupo ? htmlspecialchars($codigo) : ''; ?>">
                        <td>
                            <?php if ($esGrupo) {
                                $etiquetaGrupo = $etiqueta . ' (' . $numClientes . ' cliente' . ($numClientes === 1 ? '' : 's') . ')';
                            ?>
                                <span class="label label-info cxc-top-clientes-tipo">Grupo</span>
                                <strong><?php echo htmlspecialchars($etiquetaGrupo); ?></strong>
                            <?php } else { ?>
                                <a href="#" class="cxc-link-cliente" title="Filtrar detalle por cliente">
                                    <?php echo htmlspecialchars($etiqueta); ?>
                                </a>
                            <?php } ?>
                        </td>
                        <td class="text-right cxc-top-clientes-linea" title="<?php echo htmlspecialchars($titleLinea); ?>">
                            <?php if ($sinLinea) { ?>
                                <span class="text-muted">—</span>
                            <?php } else { ?>
                                S/ <?php echo number_format((float) $lineaCredito, 0); ?>
                            <?php } ?>
                        </td>
                        <td class="text-right">S/ <?php echo number_format($fila['saldo'], 0); ?></td>
                        <td class="text-right">S/ <?php echo number_format($fila['vencido'], 0); ?></td>
                        <td class="text-right"><?php echo (int) $fila['antiguedad_max']; ?></td>
                        <td><?php echo htmlspecialchars($fila['vendedor']); ?></td>
                        <td><span class="label <?php echo htmlspecialchars($claseRiesgo); ?>"><?php echo htmlspecialchars($labelRiesgo); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="box-footer cxc-proyeccion-nota text-muted">
        Clientes con grupo empresarial activo se consolidan. Línea créd. = referencia del módulo Línea de crédito (aprobada, recomendada IC u operativa).
    </div>
</div>
