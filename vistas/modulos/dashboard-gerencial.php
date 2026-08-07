<?php
if (!function_exists('usuarioPuedeVerModulo') || !usuarioPuedeVerModulo('gestion_comercial', 'dashboard_gerencial')) {
    denegarAccesoModulo();
    return;
}
?>

<div class="content-wrapper dg-dashboard">
    <section class="content-header">
        <h1>
            Dashboard Gerencial
            <small>Ventas y cobranzas</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Dashboard Gerencial</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
            <?php
            if (!isset($_GET['año']) && !isset($_GET['anio']) && !isset($_GET['mes'])) {
                $anioRedirect = date('Y');
                $mesRedirect = date('n');
                $url = 'index.php?ruta=dashboard-gerencial&año=' . $anioRedirect . '&mes=' . $mesRedirect;
                echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
                exit;
            }

            $filtros = ControladorDashboardGerencial::ctrParseFiltros(array(
                'anio' => isset($_GET['año']) ? $_GET['año'] : (isset($_GET['anio']) ? $_GET['anio'] : null),
                'mes' => isset($_GET['mes']) ? $_GET['mes'] : null,
                'vendedor' => isset($_GET['vendedor']) ? $_GET['vendedor'] : '',
                'modo' => isset($_GET['modo']) ? $_GET['modo'] : 'vs_anio_ant',
                'periodo_a_desde' => isset($_GET['periodo_a_desde']) ? $_GET['periodo_a_desde'] : null,
                'periodo_a_hasta' => isset($_GET['periodo_a_hasta']) ? $_GET['periodo_a_hasta'] : null,
                'periodo_b_desde' => isset($_GET['periodo_b_desde']) ? $_GET['periodo_b_desde'] : null,
                'periodo_b_hasta' => isset($_GET['periodo_b_hasta']) ? $_GET['periodo_b_hasta'] : null,
            ));

            $meses = ControladorDashboardGerencial::ctrMesesEtiqueta();
            $annosPermitidos = ControladorDashboardGerencial::ctrAnnosPermitidos();
            $vendedores = ControladorDashboardGerencial::ctrVendedoresFiltro($filtros['anio']);
            $kpis = ControladorDashboardGerencial::ctrKpis($filtros);

            $nomMes = ($filtros['mes'] === 0)
                ? 'AÑO COMPLETO'
                : (isset($meses[$filtros['mes']]) ? $meses[$filtros['mes']] : '');
            $labelVendedor = 'TODOS';

            foreach ($vendedores as $v) {
                if ($filtros['vendedor'] !== '' && $v['codigo'] === $filtros['vendedor']) {
                    $labelVendedor = $v['codigo'] . ' - ' . $v['descripcion'];
                    break;
                }
            }

            if ($filtros['vendedor'] !== '' && $labelVendedor === 'TODOS') {
                $labelVendedor = $filtros['vendedor'];
            }
            ?>

            <?php include __DIR__ . '/dashboard-gerencial/filtros.php'; ?>

            <div id="dashboardGerencialData"
                data-anio="<?php echo (int) $filtros['anio']; ?>"
                data-mes="<?php echo (int) $filtros['mes']; ?>"
                data-vendedor="<?php echo htmlspecialchars($filtros['vendedor']); ?>"
                data-modo="<?php echo htmlspecialchars($filtros['modo']); ?>"
                data-periodo-a-desde="<?php echo htmlspecialchars($filtros['periodo_a_desde']); ?>"
                data-periodo-a-hasta="<?php echo htmlspecialchars($filtros['periodo_a_hasta']); ?>"
                data-periodo-b-desde="<?php echo htmlspecialchars($filtros['periodo_b_desde']); ?>"
                data-periodo-b-hasta="<?php echo htmlspecialchars($filtros['periodo_b_hasta']); ?>">
            </div>

            <p class="dg-periodo-label">
                Período: <b><?php echo (int) $filtros['anio']; ?></b>
                — <b><?php echo htmlspecialchars($nomMes); ?></b>
                · Vendedor: <b><?php echo htmlspecialchars($labelVendedor); ?></b>
            </p>

            <?php include __DIR__ . '/dashboard-gerencial/cajas-superiores.php'; ?>

            <div class="row dg-fila-doble">
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-gerencial/ventas-mensual.php'; ?>
                </div>
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-gerencial/ventas-vs-anio.php'; ?>
                </div>
            </div>

            <?php include __DIR__ . '/dashboard-gerencial/ventas-periodos.php'; ?>

            <div class="row dg-fila-doble">
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-gerencial/cobranzas-mensual.php'; ?>
                </div>
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-gerencial/cobranzas-vs-anio.php'; ?>
                </div>
            </div>

            <?php include __DIR__ . '/dashboard-gerencial/cobranzas-periodos.php'; ?>
            <?php include __DIR__ . '/dashboard-gerencial/origen-cobranza.php'; ?>
            <?php include __DIR__ . '/dashboard-gerencial/cumplimiento-vencimientos.php'; ?>
            <?php include __DIR__ . '/dashboard-gerencial/proyeccion-cobranzas.php'; ?>
            </div>
        </div>
    </section>
</div>

<div class="modal fade dg-dashboard" id="modalDgPendienteCxc" tabindex="-1" role="dialog" aria-labelledby="modalDgPendienteCxcTitulo">
    <div class="modal-dialog modal-lg" role="document" style="width: 92%; max-width: 1100px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalDgPendienteCxcTitulo">
                    <i class="fa fa-file-text-o"></i> CxC pendientes de recuperación
                </h4>
                <p class="dg-origen-hint text-muted" id="dgPendienteCxcSubtitulo" style="margin: 4px 0 0;"></p>
            </div>
            <div class="modal-body">
                <div class="dg-pendiente-cxc-resumen" id="dgPendienteCxcResumen">
                    <span><strong>Pendiente cuadro:</strong> <span id="dgPendienteCxcKpi">—</span></span>
                    <span><strong>Cartera listada:</strong> <span id="dgPendienteCxcSaldo">—</span></span>
                    <span><strong>Diferencia:</strong> <span id="dgPendienteCxcDiff">—</span></span>
                    <span><strong>Docs:</strong> <span id="dgPendienteCxcDocs">—</span></span>
                </div>
                <p class="dg-origen-hint text-muted" id="dgPendienteCxcNota"></p>
                <div class="table-responsive dg-pendiente-cxc-wrap">
                    <table class="table table-condensed table-hover dg-tabla" id="dgTablaPendienteCxc">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Fecha</th>
                                <th>Vence</th>
                                <th class="text-right">Días</th>
                                <th>Vendedor</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-muted text-center">Cargando…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="dg-pendiente-cxc-pager" id="dgPendienteCxcPager"></div>
                <div class="dg-pendiente-cxc-chart-box">
                    <h5 class="dg-subtitulo" style="margin-top: 14px;">Deuda por vendedor</h5>
                    <p class="dg-origen-hint text-muted">Saldo abierto de la cartera listada (sin IGV)</p>
                    <div class="dg-chart-wrap dg-chart-wrap--pendiente-vend">
                        <canvas id="dgGraficoPendienteVendedor"></canvas>
                        <p class="dg-chart-empty text-muted is-hidden" id="dgPendienteVendedorEmpty">Sin datos por vendedor</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="dgBtnExportPendienteCxc">
                    <i class="fa fa-file-excel-o"></i> Exportar Excel
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
