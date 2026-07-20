<?php
if (!function_exists('usuarioPuedeVerModulo') || !usuarioPuedeVerModulo('gestion_comercial', 'dashboard_cxc')) {
    denegarAccesoModulo();
    return;
}
?>

<div class="content-wrapper cxc-dashboard">
    <section class="content-header">
        <h1>
            Centro de Control de Cuentas por Cobrar
            <small>Cartera y antigüedad</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Dashboard CxC</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
            <?php
            if (!isset($_GET['año']) && !isset($_GET['anio']) && !isset($_GET['mes'])) {
                $anioRedirect = date('Y');
                $mesRedirect = date('n');
                $url = 'index.php?ruta=dashboard-cxc&año=' . $anioRedirect . '&mes=' . $mesRedirect;
                echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
                exit;
            }

            $filtros = ControladorDashboardCxc::ctrParseFiltros(array(
                'anio' => isset($_GET['año']) ? $_GET['año'] : (isset($_GET['anio']) ? $_GET['anio'] : null),
                'mes' => isset($_GET['mes']) ? $_GET['mes'] : null,
                'vendedor' => isset($_GET['vendedor']) ? $_GET['vendedor'] : '',
                'cliente' => isset($_GET['cliente']) ? $_GET['cliente'] : '',
                'zona' => isset($_GET['zona']) ? $_GET['zona'] : 0,
                'rango' => isset($_GET['rango']) ? $_GET['rango'] : '',
                'todos_vendedores' => isset($_GET['todos_vendedores']) ? $_GET['todos_vendedores'] : '',
            ));

            $anioActual = $filtros['anio'];
            $mesActual = $filtros['mes'];
            $vendedorActual = $filtros['vendedor'];
            $clienteActual = $filtros['cliente'];
            $rangoActual = $filtros['rango'];
            $fechaCorte = $filtros['fecha_corte'];
            $todosVendedores = !empty($filtros['todos_vendedores']);

            $meses = ControladorDashboardCxc::ctrMesesEtiqueta();
            $annosPermitidos = ControladorDashboardCxc::ctrAnnosPermitidos();
            $nomMes = isset($meses[$mesActual]) ? $meses[$mesActual] : '';
            $labelVendedor = 'TODOS';
            $vendedores = ControladorDashboardCxc::ctrVendedoresFiltro();

            foreach ($vendedores as $v) {
                if ($vendedorActual !== '' && $v['codigo'] === $vendedorActual) {
                    $labelVendedor = $v['codigo'] . ' - ' . $v['descripcion'];
                    break;
                }
            }

            if ($vendedorActual !== '' && $labelVendedor === 'TODOS') {
                $labelVendedor = $vendedorActual;
            }

            $paginaDetalle = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;

            $kpisCabecera = ControladorDashboardCxc::ctrKpisCabecera($filtros);
            $ventasDashboard = ControladorDashboardCxc::ctrDatosVentas($filtros);
            $resumenCobranza = ControladorDashboardCxc::ctrResumenCobranza($filtros);
            $antiguedadCxc = ControladorDashboardCxc::ctrAntiguedad($filtros);
            ?>

            <div class="box box-primary cxc-filtros-box">
                <div class="box-body cxc-filtros-body">
                    <div class="cxc-filtros-toolbar">
                        <div class="cxc-filtro-item cxc-filtro-item--anio">
                            <label for="anioCxc">Año</label>
                            <select class="form-control input-sm selectpicker" id="anioCxc" data-live-search="true" data-style="btn-default btn-sm" title="Año">
                                <?php foreach ($annosPermitidos as $anio) : ?>
                                    <option value="<?php echo (int) $anio; ?>" <?php echo ($anioActual == $anio) ? 'selected' : ''; ?>><?php echo (int) $anio; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cxc-filtro-item cxc-filtro-item--mes">
                            <label for="mesCxc">Mes</label>
                            <select class="form-control input-sm selectpicker" id="mesCxc" data-live-search="true" data-style="btn-default btn-sm" title="Mes">
                                <option value="0" <?php echo ($mesActual === 0) ? 'selected' : ''; ?>>Año completo</option>
                                <?php foreach ($meses as $num => $nombre) : ?>
                                    <option value="<?php echo (int) $num; ?>" <?php echo ($mesActual == $num) ? 'selected' : ''; ?>><?php echo htmlspecialchars($nombre); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cxc-filtro-item cxc-filtro-item--wide">
                            <label for="vendedorCxc">Vendedor</label>
                            <select class="form-control input-sm selectpicker" id="vendedorCxc" data-live-search="true" data-style="btn-default btn-sm" title="Todos">
                                <option value="">Todos</option>
                                <?php foreach ($vendedores as $v) : ?>
                                    <option value="<?php echo htmlspecialchars($v['codigo']); ?>" <?php echo ($vendedorActual === $v['codigo']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['codigo'] . ' - ' . $v['descripcion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <label class="cxc-check-todos-vendedores" for="cxcIncluirTodosVendedores"
                            title="Aplica a cartera/ventas por vendedor, detalle de documentos y visión de morosidad">
                            <input type="checkbox"
                                id="cxcIncluirTodosVendedores"
                                <?php echo $todosVendedores ? 'checked' : ''; ?>>
                            <span>Inactivos</span>
                        </label>
                        <div class="cxc-filtros-meta-inline">
                            <span class="cxc-filtros-chip">
                                <i class="fa fa-calendar"></i>
                                <?php echo htmlspecialchars($fechaCorte); ?>
                            </span>
        <?php if (empty($filtros['todos_vendedores'])) { ?>
        <span class="cxc-filtros-chip cxc-filtros-chip--muted">Activos</span>
        <?php } else { ?>
        <span class="cxc-filtros-chip cxc-filtros-chip--muted">Incluye inactivos</span>
        <?php } ?>
                            <?php if ($rangoActual !== '') { ?>
                            <span class="cxc-filtros-chip cxc-filtros-chip--warn"><?php echo htmlspecialchars($rangoActual); ?></span>
                            <?php } ?>
                            <?php if ($clienteActual !== '') { ?>
                            <span class="cxc-filtros-chip cxc-filtros-chip--info"><?php echo htmlspecialchars($clienteActual); ?></span>
                            <?php } ?>
                            <?php if ($rangoActual !== '' || $clienteActual !== '') { ?>
                            <a href="#" class="cxc-limpiar-filtros-detalle">Limpiar</a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="dashboardCxcData"
                data-anio="<?php echo (int) $anioActual; ?>"
                data-mes="<?php echo (int) $mesActual; ?>"
                data-vendedor="<?php echo htmlspecialchars($vendedorActual); ?>"
                data-cliente="<?php echo htmlspecialchars($clienteActual); ?>"
                data-rango="<?php echo htmlspecialchars($rangoActual); ?>"
                data-todos-vendedores="<?php echo $todosVendedores ? '1' : '0'; ?>"
                data-pagina="<?php echo (int) $paginaDetalle; ?>"
                data-fecha-corte="<?php echo htmlspecialchars($fechaCorte); ?>">
            </div>

            <?php
            include __DIR__ . '/dashboard-cxc/cajas-superiores.php';
            ?>

            <div class="row cxc-fila-principal">
                <div class="col-md-6 col-sm-12">
                    <div class="cxc-seccion-ventas cxc-seccion-ventas--media">
                        <h4 class="cxc-seccion-titulo">Ventas</h4>
                        <?php include __DIR__ . '/dashboard-cxc/ventas-panel.php'; ?>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="cxc-seccion-cobranza cxc-seccion-cobranza--media">
                        <h4 class="cxc-seccion-titulo">Cobranza</h4>
                        <?php include __DIR__ . '/dashboard-cxc/cobranza-resumen.php'; ?>
                    </div>
                </div>
            </div>

            <div class="cxc-seccion-cxc">
                <h4 class="cxc-seccion-titulo">Detalle de cartera</h4>

            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-cxc/tabla-cxc-vendedor.php'; ?>
                </div>
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-cxc/tabla-proyeccion-pagos.php'; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-cxc/tabla-top-clientes.php'; ?>
                </div>
                <div class="col-md-6 col-sm-12">
                    <?php include __DIR__ . '/dashboard-cxc/tabla-detalle-documentos.php'; ?>
                </div>
            </div>

            <?php include __DIR__ . '/dashboard-cxc/vision-morosidad.php'; ?>

            </div><!-- /.cxc-seccion-cxc -->

            </div>
        </div>
    </section>
</div>
