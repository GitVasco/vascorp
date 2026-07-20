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
    'regular' => 'Por vencer',
    'vencido' => 'Vencido',
    '180+' => '+180',
    'incobrable' => 'Incobrable',
);

$clasesClasificacion = array(
    'regular' => 'label-success',
    'vencido' => 'label-warning',
    '180+' => 'label-danger',
    'incobrable' => 'label-purple',
);

$labelsRango = array(
    'por-vencer' => 'Por vencer',
    '0-30' => '0–30',
    '31-60' => '31–60',
    '61-90' => '61–90',
    '91-180' => '91–180',
    '180+' => '+180',
    'incobrable' => 'Incobrable',
);

$clienteFiltro = isset($filtros['cliente']) ? trim((string) $filtros['cliente']) : '';
$rangoFiltro = isset($filtros['rango']) ? trim((string) $filtros['rango']) : '';
$tieneFiltrosDetalle = ($clienteFiltro !== '' || $rangoFiltro !== '');
$excluyeIncobrables = ($rangoFiltro !== 'incobrable');
?>

<div class="box box-default cxc-panel cxc-panel-detalle-docs">
    <div class="box-header with-border">
        <h3 class="box-title">Detalle de documentos</h3>
        <div class="box-tools pull-right cxc-detalle-tools">
            <input type="text" class="form-control input-sm cxc-buscar-cliente" placeholder="Buscar cliente…" value="<?php echo htmlspecialchars($clienteFiltro); ?>">
        </div>
    </div>
    <?php if ($tieneFiltrosDetalle) { ?>
    <div class="box-body cxc-detalle-filtros-activos">
        <span class="text-muted">Filtros:</span>
        <?php if ($clienteFiltro !== '') { ?>
            <span class="label label-info">Cliente: <?php echo htmlspecialchars($clienteFiltro); ?></span>
        <?php } ?>
        <?php if ($rangoFiltro !== '') { ?>
            <span class="label label-warning">Rango: <?php echo htmlspecialchars(isset($labelsRango[$rangoFiltro]) ? $labelsRango[$rangoFiltro] : $rangoFiltro); ?></span>
        <?php } ?>
        <a href="#" class="cxc-limpiar-filtros-detalle">Limpiar</a>
    </div>
    <?php } ?>
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
                        $clasifKey = isset($fila['clasificacion']) ? $fila['clasificacion'] : '';
                        $clasif = isset($labelsClasificacion[$clasifKey]) ? $labelsClasificacion[$clasifKey] : $clasifKey;
                        $claseClasif = isset($clasesClasificacion[$clasifKey]) ? $clasesClasificacion[$clasifKey] : 'label-default';
                        $rangoKey = isset($fila['rango']) ? $fila['rango'] : '';
                        $rangoLabel = isset($labelsRango[$rangoKey]) ? $labelsRango[$rangoKey] : $rangoKey;
                        $dias = (int) $fila['dias_antiguedad'];
                        $esVencido = ($clasifKey === 'vencido' || $clasifKey === '180+' || $clasifKey === 'incobrable');
                        $claseFila = $esVencido ? 'cxc-detalle-fila--vencido' : 'cxc-detalle-fila--vigente';
                        if ($clasifKey === '180+') {
                            $claseFila = 'cxc-detalle-fila--critico';
                        }
                    ?>
                    <tr class="<?php echo $claseFila; ?>">
                        <td>
                            <?php
                            $codCli = trim((string) $fila['cliente']);
                            $nomCli = trim((string) $fila['nombre_cliente']);
                            if ($nomCli === '' || $nomCli === $codCli) {
                                $etiquetaCli = $codCli;
                            } else {
                                $etiquetaCli = $codCli . ' - ' . $nomCli;
                            }
                            ?>
                            <?php echo '<span class="cxc-detalle-cliente">' . htmlspecialchars($etiquetaCli) . '</span>'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($fila['vendedor']); ?></td>
                        <td><code class="cxc-detalle-doc"><?php echo htmlspecialchars($doc); ?></code></td>
                        <td><?php echo htmlspecialchars(substr($fila['fecha'], 0, 10)); ?></td>
                        <td><?php echo htmlspecialchars(substr($fila['fecha_ven'], 0, 10)); ?></td>
                        <td class="text-right cxc-detalle-saldo">S/ <?php echo number_format($fila['saldo'], 2); ?></td>
                        <td class="text-right <?php echo $esVencido ? 'cxc-detalle-dias--vencido' : 'text-muted'; ?>">
                            <?php echo $dias; ?>
                        </td>
                        <td><span class="label label-default cxc-detalle-rango"><?php echo htmlspecialchars($rangoLabel); ?></span></td>
                        <td><span class="label <?php echo htmlspecialchars($claseClasif); ?>"><?php echo htmlspecialchars($clasif); ?></span></td>
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
        <?php if ($excluyeIncobrables) { ?>
        <span class="cxc-detalle-nota text-muted">
            Sin Incobrables · Por vencer y vencidos (0–30 … +180)
            <?php if (empty($filtros['todos_vendedores'])) { ?>
                · Solo vendedores activos
            <?php } ?>
        </span>
        <?php } elseif (empty($filtros['todos_vendedores'])) { ?>
        <span class="cxc-detalle-nota text-muted">Solo vendedores activos</span>
        <?php } ?>
    </div>
</div>
