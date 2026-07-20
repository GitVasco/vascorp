<?php

if (!isset($filtros)) {
    return;
}

$detalle = ControladorDashboardCxc::ctrDetalleDocumentos(
    $filtros,
    isset($paginaDetalle) ? $paginaDetalle : 1,
    25
);
$filas = isset($detalle['filas']) ? $detalle['filas'] : array();
$pag = isset($detalle['paginacion']) ? $detalle['paginacion'] : array();
$saldoTotal = isset($detalle['saldo_total']) ? (float) $detalle['saldo_total'] : 0;

$labelsClasificacion = array(
    'regular' => 'Regular',
    'vencido' => 'Vencido',
    '180+' => '+180',
    'incobrable' => 'Incobrable',
);
?>

<div class="box box-default cxc-panel">
    <div class="box-header with-border">
        <h3 class="box-title">Detalle de documentos</h3>
        <div class="box-tools pull-right cxc-detalle-tools">
            <input type="text" class="form-control input-sm cxc-buscar-cliente" placeholder="Buscar cliente…" value="<?php echo htmlspecialchars($filtros['cliente']); ?>">
        </div>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-condensed cxc-tabla-detalle" id="tablaDetalleDocumentosCxc">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Documento</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th class="text-right">Saldo</th>
                    <th class="text-right">Días</th>
                    <th>Rango</th>
                    <th>Clasif.</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($filas) === 0) { ?>
                <tr><td colspan="9" class="text-muted">Sin documentos para los filtros actuales</td></tr>
                <?php } else { ?>
                    <?php foreach ($filas as $fila) :
                        $doc = trim($fila['tipo_doc'] . '-' . $fila['num_cta'], '-');
                        $clasif = isset($labelsClasificacion[$fila['clasificacion']]) ? $labelsClasificacion[$fila['clasificacion']] : $fila['clasificacion'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila['nombre_cliente']); ?></td>
                        <td><?php echo htmlspecialchars($fila['vendedor']); ?></td>
                        <td><?php echo htmlspecialchars($doc); ?></td>
                        <td><?php echo htmlspecialchars(substr($fila['fecha'], 0, 10)); ?></td>
                        <td><?php echo htmlspecialchars(substr($fila['fecha_ven'], 0, 10)); ?></td>
                        <td class="text-right">S/ <?php echo number_format($fila['saldo'], 2); ?></td>
                        <td class="text-right"><?php echo (int) $fila['dias_antiguedad']; ?></td>
                        <td><?php echo htmlspecialchars($fila['rango']); ?></td>
                        <td><?php echo htmlspecialchars($clasif); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php } ?>
            </tbody>
            <?php if (count($filas) > 0) { ?>
            <tfoot>
                <tr class="cxc-fila-total">
                    <td colspan="5"><strong>Total filtrado (<?php echo number_format(isset($pag['total_registros']) ? $pag['total_registros'] : 0, 0); ?> docs)</strong></td>
                    <td class="text-right"><strong>S/ <?php echo number_format($saldoTotal, 2); ?></strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php } ?>
        </table>
    </div>
    <div class="box-footer cxc-detalle-footer">
        <div class="cxc-detalle-paginacion" id="cxcDetallePaginacion"
            data-pagina="<?php echo (int) (isset($pag['pagina']) ? $pag['pagina'] : 1); ?>"
            data-total-paginas="<?php echo (int) (isset($pag['total_paginas']) ? $pag['total_paginas'] : 0); ?>">
            <?php if (!empty($pag['total_paginas']) && $pag['total_paginas'] > 1) { ?>
                <button type="button" class="btn btn-default btn-sm cxc-detalle-prev" <?php echo ($pag['pagina'] <= 1) ? 'disabled' : ''; ?>>
                    <i class="fa fa-chevron-left"></i> Anterior
                </button>
                <span class="cxc-detalle-pagina-label">
                    Página <?php echo (int) $pag['pagina']; ?> de <?php echo (int) $pag['total_paginas']; ?>
                </span>
                <button type="button" class="btn btn-default btn-sm cxc-detalle-next" <?php echo ($pag['pagina'] >= $pag['total_paginas']) ? 'disabled' : ''; ?>>
                    Siguiente <i class="fa fa-chevron-right"></i>
                </button>
            <?php } ?>
        </div>
    </div>
</div>
