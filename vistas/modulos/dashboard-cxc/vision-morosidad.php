<?php

if (!isset($filtros)) {
    return;
}

$morosidad = ControladorDashboardCxc::ctrVisionMorosidad($filtros);

$indice = (float) $morosidad['indice'];
$nivel = $morosidad['nivel'];
$severidad = $morosidad['severidad'];
$ranking = $morosidad['ranking_vendedores'];
$criticos = $morosidad['clientes_criticos'];
$acciones = $morosidad['acciones'];

$nivelesLabel = array(
    'bajo' => 'Controlada',
    'medio' => 'Atención',
    'alto' => 'Alta',
    'critico' => 'Crítica',
);
$nivelesClase = array(
    'bajo' => 'cxc-moro-nivel--bajo',
    'medio' => 'cxc-moro-nivel--medio',
    'alto' => 'cxc-moro-nivel--alto',
    'critico' => 'cxc-moro-nivel--critico',
);

$labelNivel = isset($nivelesLabel[$nivel]) ? $nivelesLabel[$nivel] : $nivel;
$claseNivel = isset($nivelesClase[$nivel]) ? $nivelesClase[$nivel] : 'cxc-moro-nivel--medio';

$cxcEtiquetaMoroso = function ($fila) {
    $codigo = trim((string) $fila['codigo']);
    $nombre = trim((string) $fila['nombre']);
    if ($codigo === '') {
        return $nombre;
    }
    if ($nombre === '' || $nombre === $codigo) {
        return $codigo;
    }
    return $codigo . ' - ' . $nombre;
};

$maxRango = 0.0;
foreach ($severidad['rangos'] as $rango) {
    if ((float) $rango['monto'] > $maxRango) {
        $maxRango = (float) $rango['monto'];
    }
}
?>

<div class="box box-default cxc-panel cxc-panel-morosidad">
    <div class="box-header with-border">
        <h3 class="box-title">Visión de morosidad</h3>
        <div class="box-tools">
            <span class="cxc-moro-nivel <?php echo htmlspecialchars($claseNivel); ?>">
                <?php echo htmlspecialchars($labelNivel); ?>
            </span>
        </div>
    </div>
    <div class="box-body">
        <div class="cxc-moro-kpis">
            <div class="cxc-moro-kpi cxc-moro-kpi--indice">
                <span class="cxc-moro-kpi__label">Índice de morosidad</span>
                <strong class="cxc-moro-kpi__valor"><?php echo number_format($indice, 1); ?>%</strong>
                <span class="cxc-moro-kpi__sub">Vencido / CxC</span>
            </div>
            <div class="cxc-moro-kpi">
                <span class="cxc-moro-kpi__label">Monto vencido</span>
                <strong class="cxc-moro-kpi__valor">S/ <?php echo number_format($morosidad['monto_vencido'], 0); ?></strong>
                <span class="cxc-moro-kpi__sub"><?php echo number_format($morosidad['clientes_morosos'], 0); ?> clientes</span>
            </div>
            <div class="cxc-moro-kpi">
                <span class="cxc-moro-kpi__label">Severidad 61+</span>
                <strong class="cxc-moro-kpi__valor"><?php echo number_format($severidad['pct_61_mas'], 1); ?>%</strong>
                <span class="cxc-moro-kpi__sub">S/ <?php echo number_format($severidad['monto_61_mas'], 0); ?></span>
            </div>
            <div class="cxc-moro-kpi">
                <span class="cxc-moro-kpi__label">Crítico 91+ / +180</span>
                <strong class="cxc-moro-kpi__valor"><?php echo number_format($severidad['pct_91_mas'], 1); ?>%</strong>
                <span class="cxc-moro-kpi__sub">+180: <?php echo number_format($severidad['pct_180_mas'], 1); ?>%</span>
            </div>
            <div class="cxc-moro-kpi">
                <span class="cxc-moro-kpi__label">Concentración top</span>
                <strong class="cxc-moro-kpi__valor"><?php echo number_format($morosidad['concentracion_top'], 1); ?>%</strong>
                <span class="cxc-moro-kpi__sub">del vencido en el top listado</span>
            </div>
        </div>

        <div class="row cxc-moro-grid">
            <div class="col-md-5 col-sm-12">
                <h4 class="cxc-moro-subtitulo">Dónde está la morosidad (antigüedad)</h4>
                <div class="cxc-moro-barras">
                    <?php foreach ($severidad['rangos'] as $rango) :
                        $monto = (float) $rango['monto'];
                        $ancho = $maxRango > 0 ? round(($monto / $maxRango) * 100, 1) : 0;
                        $pct = $morosidad['monto_vencido'] > 0
                            ? round(($monto / $morosidad['monto_vencido']) * 100, 1)
                            : 0;
                        $claseBarra = 'cxc-moro-barra__fill--r' . preg_replace('/[^0-9a-z]+/i', '', $rango['id']);
                    ?>
                    <div class="cxc-moro-barra">
                        <div class="cxc-moro-barra__meta">
                            <span><?php echo htmlspecialchars($rango['label']); ?></span>
                            <span>S/ <?php echo number_format($monto, 0); ?> · <?php echo number_format($pct, 1); ?>%</span>
                        </div>
                        <div class="cxc-moro-barra__track">
                            <div class="cxc-moro-barra__fill <?php echo htmlspecialchars($claseBarra); ?>" style="width: <?php echo $ancho; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-7 col-sm-12">
                <h4 class="cxc-moro-subtitulo">Vendedores a priorizar</h4>
                <div class="table-responsive no-padding">
                    <table class="table table-condensed table-hover cxc-tabla-moro-vend">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th class="text-right">Vencido</th>
                                <th class="text-right">% cart.</th>
                                <th style="width:35%">Peso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($ranking) === 0) { ?>
                            <tr><td colspan="4" class="text-muted">Sin vencidos en el corte</td></tr>
                            <?php } else { ?>
                                <?php foreach ($ranking as $fila) : ?>
                                <tr class="cxc-fila-vendedor" data-vendedor="<?php echo htmlspecialchars($fila['vendedor']); ?>">
                                    <td>
                                        <a href="#" class="cxc-link-vendedor">
                                            <?php echo htmlspecialchars($fila['vendedor'] . ' - ' . $fila['nom_vendedor']); ?>
                                        </a>
                                    </td>
                                    <td class="text-right">S/ <?php echo number_format($fila['vencido'], 0); ?></td>
                                    <td class="text-right"><?php echo number_format($fila['pct_vencido'], 1); ?>%</td>
                                    <td>
                                        <div class="cxc-moro-mini-track">
                                            <div class="cxc-moro-mini-fill" style="width: <?php echo (float) $fila['barra_pct']; ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row cxc-moro-grid">
            <div class="col-md-8 col-sm-12">
                <h4 class="cxc-moro-subtitulo">Clientes / grupos críticos</h4>
                <div class="table-responsive no-padding">
                    <table class="table table-condensed table-hover cxc-tabla-moro-cli">
                        <thead>
                            <tr>
                                <th>Cliente / Grupo</th>
                                <th>Vendedor</th>
                                <th class="text-right">Vencido</th>
                                <th class="text-right">% venc.</th>
                                <th class="text-right">Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($criticos) === 0) { ?>
                            <tr><td colspan="5" class="text-muted">Sin clientes vencidos relevantes</td></tr>
                            <?php } else { ?>
                                <?php foreach ($criticos as $fila) :
                                    $esGrupo = ($fila['tipo_entidad'] === 'grupo');
                                    $dataCliente = $esGrupo ? '' : (isset($fila['cliente']) && $fila['cliente'] !== '' ? $fila['cliente'] : $fila['codigo']);
                                ?>
                                <tr class="cxc-fila-cliente<?php echo $esGrupo ? ' cxc-fila-cliente--grupo' : ''; ?>"
                                    data-tipo="<?php echo htmlspecialchars($fila['tipo_entidad']); ?>"
                                    data-cliente="<?php echo htmlspecialchars($dataCliente); ?>"
                                    data-grupo="<?php echo $esGrupo ? htmlspecialchars($fila['codigo']) : ''; ?>">
                                    <td>
                                        <?php if ($esGrupo) { ?>
                                            <span class="label label-info">Grupo</span>
                                        <?php } ?>
                                        <?php if (!$esGrupo) { ?>
                                            <a href="#" class="cxc-link-cliente"><?php echo htmlspecialchars($cxcEtiquetaMoroso($fila)); ?></a>
                                        <?php } else { ?>
                                            <strong><?php echo htmlspecialchars($cxcEtiquetaMoroso($fila)); ?></strong>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($fila['vendedor']); ?></td>
                                    <td class="text-right">S/ <?php echo number_format($fila['vencido'], 0); ?></td>
                                    <td class="text-right"><?php echo number_format($fila['pct_del_vencido'], 1); ?>%</td>
                                    <td class="text-right <?php echo ((int) $fila['antiguedad_max'] > 60) ? 'cxc-detalle-dias--vencido' : ''; ?>">
                                        <?php echo (int) $fila['antiguedad_max']; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <h4 class="cxc-moro-subtitulo">Qué hacer ahora</h4>
                <ul class="cxc-moro-acciones">
                    <?php foreach ($acciones as $accion) { ?>
                    <li><?php echo htmlspecialchars($accion); ?></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
    <?php if (!empty($morosidad['nota'])) { ?>
    <div class="box-footer cxc-proyeccion-nota text-muted">
        <?php echo htmlspecialchars($morosidad['nota']); ?>
    </div>
    <?php } ?>
</div>
