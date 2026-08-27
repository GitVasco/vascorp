<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "informe_semanal_vendedor")) {
    denegarAccesoModulo();
    return;
}

$filtros = ControladorInformeSemanalVendedor::ctrParseFiltros(array(
    "vendedor" => isset($_GET["vendedor"]) ? $_GET["vendedor"] : "",
    "semana" => isset($_GET["semana"]) ? $_GET["semana"] : "",
));
$vendedores = ControladorInformeSemanalVendedor::ctrVendedoresFiltro();
if ($filtros["vendedor"] === "" && count($vendedores) > 0) {
    $filtros["vendedor"] = $vendedores[0]["codigo"];
}
?>

<div class="content-wrapper isv-page">
    <section class="content-header isv-no-print">
        <h1>
            Informe semanal de gestión comercial
            <small>Por vendedor · A4 para imprimir o guardar PDF</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Informe semanal</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-primary isv-no-print">
            <div class="box-body">
                <form class="form-inline isv-filtros" id="isvFormFiltros">
                    <div class="form-group">
                        <label for="isvSemana">Semana</label>
                        <input type="week" class="form-control input-sm" id="isvSemana" name="semana" value="<?php echo htmlspecialchars($filtros['semana_iso']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="isvVendedor">Vendedor</label>
                        <select class="form-control input-sm" id="isvVendedor" name="vendedor">
                            <?php foreach ($vendedores as $v) : ?>
                            <option value="<?php echo htmlspecialchars($v['codigo']); ?>" <?php echo ($filtros['vendedor'] === $v['codigo']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['codigo'] . ' — ' . $v['descripcion']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-search"></i> Ver informe
                    </button>
                    <button type="button" class="btn btn-default btn-sm" id="isvBtnImprimir">
                        <i class="fa fa-print"></i> Imprimir / PDF
                    </button>
                </form>
                <p class="help-block" style="margin:8px 0 0;">
                    En el diálogo: A4 vertical, márgenes mínimos y active <b>Gráficos de fondo</b> para ver colores.
                </p>
            </div>
        </div>

        <div id="isvEstado" class="alert alert-info isv-no-print">Cargando informe…</div>

        <div id="isvHoja" class="isv-hoja" hidden>
            <header class="isv-header">
                <div>
                    <div class="isv-logo">VASCO</div>
                </div>
                <div class="isv-titulo">
                    <h2>INFORME SEMANAL DE GESTIÓN COMERCIAL</h2>
                    <p>REPORTE POR VENDEDOR</p>
                </div>
                <div class="isv-meta">
                    <div><span>Semana evaluada</span><strong id="isvMetaSemana">—</strong></div>
                    <div><span>Fecha de emisión</span><strong id="isvMetaEmision">—</strong></div>
                </div>
            </header>

            <div class="isv-kpis">
                <article class="isv-kpi">
                    <div class="isv-kpi-ico"><i class="fa fa-user"></i></div>
                    <div>
                        <small>Vendedor</small>
                        <strong id="isvNomVendedor">—</strong>
                        <em id="isvZonaVendedor">—</em>
                    </div>
                </article>
                <article class="isv-kpi">
                    <div class="isv-kpi-ico isv-kpi-ico--green"><i class="fa fa-usd"></i></div>
                    <div>
                        <small>Ventas de la semana</small>
                        <strong id="isvKpiVenta">—</strong>
                        <em id="isvKpiPedidos">—</em>
                    </div>
                </article>
                <article class="isv-kpi">
                    <div class="isv-kpi-ico isv-kpi-ico--purple"><i class="fa fa-users"></i></div>
                    <div>
                        <small>Clientes con compra</small>
                        <strong id="isvKpiClientes">—</strong>
                        <em id="isvKpiCartera">—</em>
                    </div>
                </article>
                <article class="isv-kpi">
                    <div class="isv-kpi-ico isv-kpi-ico--orange"><i class="fa fa-user-plus"></i></div>
                    <div>
                        <small>Nuevos clientes</small>
                        <strong id="isvKpiNuevos">—</strong>
                        <em>Primera compra</em>
                    </div>
                </article>
                <article class="isv-kpi">
                    <div class="isv-kpi-ico isv-kpi-ico--blue"><i class="fa fa-money"></i></div>
                    <div>
                        <small>Cobranza realizada</small>
                        <strong id="isvKpiCobranza">—</strong>
                        <em>Sin IGV</em>
                    </div>
                </article>
                <article class="isv-kpi">
                    <div class="isv-kpi-ico isv-kpi-ico--teal"><i class="fa fa-file-text-o"></i></div>
                    <div>
                        <small>Por facturar</small>
                        <strong id="isvKpiPorFacturar">—</strong>
                        <em id="isvKpiPorFacturarN">Pedidos aprobados</em>
                    </div>
                </article>
            </div>

            <div class="isv-grid-2 isv-grid-charts">
                <section class="isv-card">
                    <h3>Evolución diaria de ventas</h3>
                    <div class="isv-chart-line">
                        <canvas id="isvChartVentas"></canvas>
                    </div>
                    <div class="isv-chart-legend">
                        <span class="isv-leg-item isv-leg-ventas-act"><i></i> Semana actual</span>
                        <span class="isv-leg-item isv-leg-ventas-ant"><i></i> Semana ant.</span>
                    </div>
                    <table class="isv-tabla isv-tabla-dias">
                        <thead>
                            <tr id="isvDiasHead"></tr>
                        </thead>
                        <tbody>
                            <tr id="isvDiasActual"></tr>
                            <tr id="isvDiasAnterior"></tr>
                        </tbody>
                    </table>
                    <p class="isv-chart-nota">Venta neta lun–dom · tipos S02/S03/S70/E05/S05</p>
                    <div class="isv-resumen-venta">
                        <div>Venta actual<br><b id="isvResVenta">—</b></div>
                        <div>Promedio últimas 4 semanas<br><b id="isvResProm">—</b></div>
                        <div>Variación vs promedio<br><b id="isvResVar">—</b></div>
                    </div>
                </section>
                <section class="isv-card">
                    <h3>Evolución diaria de cobranza</h3>
                    <div class="isv-chart-line">
                        <canvas id="isvChartCobranzas"></canvas>
                    </div>
                    <div class="isv-chart-legend">
                        <span class="isv-leg-item isv-leg-cob-act"><i></i> Semana actual</span>
                        <span class="isv-leg-item isv-leg-cob-ant"><i></i> Semana ant.</span>
                    </div>
                    <table class="isv-tabla isv-tabla-dias">
                        <thead>
                            <tr id="isvDiasCobHead"></tr>
                        </thead>
                        <tbody>
                            <tr id="isvDiasCobActual"></tr>
                            <tr id="isvDiasCobAnterior"></tr>
                        </tbody>
                    </table>
                    <p class="isv-chart-nota">Cobranza efectiva lun–dom · sin IGV · incluye códigos anteriores del vendedor</p>
                </section>
            </div>

            <section class="isv-card">
                <h3>Comparativo desempeño</h3>
                <table class="isv-tabla">
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th>Esta semana</th>
                            <th>Semana anterior</th>
                            <th>Variación</th>
                        </tr>
                    </thead>
                    <tbody id="isvTablaComp"></tbody>
                </table>
            </section>

            <section class="isv-card">
                <h3>Estado de cartera del vendedor</h3>
                <div class="isv-cartera-body">
                    <div class="isv-donut-wrap">
                        <canvas id="isvChartCartera"></canvas>
                        <div class="isv-donut-center">
                            <span>TOTAL CARTERA</span>
                            <strong id="isvCarteraTotal">—</strong>
                        </div>
                    </div>
                    <table class="isv-tabla">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Tramo</th>
                                <th>S/.</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody id="isvTablaCartera"></tbody>
                    </table>
                </div>
                <div class="isv-cartera-foot" id="isvCarteraFoot"></div>
            </section>

            <section class="isv-card">
                <h3>Pedidos por facturar</h3>
                <p class="isv-chart-nota">Montos abiertos al día de hoy (Aprobado / APT / Confirmado). Op. gravada en soles.</p>
                <div class="isv-cartera-foot" id="isvPfResumen"></div>
            </section>

            <div class="isv-grid-2">
                <section class="isv-card">
                    <h3>Top 5 clientes por venta de la semana</h3>
                    <table class="isv-tabla">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Venta S/.</th>
                            </tr>
                        </thead>
                        <tbody id="isvTablaTop"></tbody>
                    </table>
                </section>
                <section class="isv-card">
                    <h3>Lectura de la semana</h3>
                    <ul class="isv-lista isv-lista--ok" id="isvLectura"></ul>
                </section>
            </div>

            <div class="isv-grid-2">
                <section class="isv-card">
                    <h3><i class="fa fa-bullseye"></i> Plan de acción / próxima semana</h3>
                    <ul class="isv-lista" id="isvPlan"></ul>
                </section>
                <section class="isv-card">
                    <h3><i class="fa fa-clipboard"></i> Observaciones</h3>
                    <p id="isvObs" class="isv-obs"></p>
                </section>
            </div>
        </div>
    </section>
</div>
