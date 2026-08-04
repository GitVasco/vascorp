<?php
if (!isset($filtros) || !isset($meses) || !isset($annosPermitidos) || !isset($vendedores)) {
    return;
}

$anioActual = (int) $filtros['anio'];
$mesActual = (int) $filtros['mes'];
$vendedorActual = (string) $filtros['vendedor'];
$modoActual = (string) $filtros['modo'];
$mostrarPeriodos = ($modoActual === 'periodos');
?>

<div class="box box-primary dg-filtros-box">
    <div class="box-body dg-filtros-body">
        <div class="dg-filtros-toolbar">
            <div class="dg-filtro-item dg-filtro-item--anio">
                <label for="anioDg">Año</label>
                <select class="form-control input-sm selectpicker" id="anioDg" data-live-search="true" data-style="btn-default btn-sm" title="Año">
                    <?php foreach ($annosPermitidos as $anio) : ?>
                        <option value="<?php echo (int) $anio; ?>" <?php echo ($anioActual === (int) $anio) ? 'selected' : ''; ?>>
                            <?php echo (int) $anio; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dg-filtro-item dg-filtro-item--mes">
                <label for="mesDg">Mes</label>
                <select class="form-control input-sm selectpicker" id="mesDg" data-live-search="true" data-style="btn-default btn-sm" title="Mes">
                    <option value="0" <?php echo ($mesActual === 0) ? 'selected' : ''; ?>>Año completo</option>
                    <?php foreach ($meses as $num => $nombre) : ?>
                        <option value="<?php echo (int) $num; ?>" <?php echo ($mesActual === (int) $num) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dg-filtro-item dg-filtro-item--wide">
                <label for="vendedorDg">Vendedor</label>
                <select class="form-control input-sm selectpicker" id="vendedorDg" data-live-search="true" data-style="btn-default btn-sm" title="Todos">
                    <option value="">Todos</option>
                    <?php foreach ($vendedores as $v) : ?>
                        <option value="<?php echo htmlspecialchars($v['codigo']); ?>" <?php echo ($vendedorActual === $v['codigo']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['codigo'] . ' - ' . $v['descripcion']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dg-filtro-item dg-filtro-item--modo">
                <label for="modoDg">Comparar</label>
                <select class="form-control input-sm selectpicker" id="modoDg" data-style="btn-default btn-sm" title="Modo">
                    <option value="vs_anio_ant" <?php echo ($modoActual === 'vs_anio_ant') ? 'selected' : ''; ?>>Vs año pasado</option>
                    <option value="periodos" <?php echo ($modoActual === 'periodos') ? 'selected' : ''; ?>>Períodos A / B</option>
                </select>
            </div>

            <div class="dg-periodos <?php echo $mostrarPeriodos ? '' : 'is-hidden'; ?>" id="dgPeriodosBox">
                <div class="dg-filtro-item">
                    <label for="periodoADesdeDg">Período A desde</label>
                    <input type="date" class="form-control input-sm" id="periodoADesdeDg" value="<?php echo htmlspecialchars($filtros['periodo_a_desde']); ?>">
                </div>
                <div class="dg-filtro-item">
                    <label for="periodoAHastaDg">Período A hasta</label>
                    <input type="date" class="form-control input-sm" id="periodoAHastaDg" value="<?php echo htmlspecialchars($filtros['periodo_a_hasta']); ?>">
                </div>
                <div class="dg-filtro-item">
                    <label for="periodoBDesdeDg">Período B desde</label>
                    <input type="date" class="form-control input-sm" id="periodoBDesdeDg" value="<?php echo htmlspecialchars($filtros['periodo_b_desde']); ?>">
                </div>
                <div class="dg-filtro-item">
                    <label for="periodoBHastaDg">Período B hasta</label>
                    <input type="date" class="form-control input-sm" id="periodoBHastaDg" value="<?php echo htmlspecialchars($filtros['periodo_b_hasta']); ?>">
                </div>
            </div>

            <div class="dg-filtro-item dg-filtro-item--accion">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-primary btn-sm" id="btnAplicarFiltrosDg">
                    <i class="fa fa-refresh"></i> Aplicar
                </button>
            </div>
        </div>
    </div>
</div>
