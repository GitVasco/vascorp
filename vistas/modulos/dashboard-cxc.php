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
                <div class="box-header with-border cxc-filtros-header">
                    <div class="cxc-filtros-resumen">
                        <h3 class="cxc-filtros-titulo">
                            Corte: <b><?php echo htmlspecialchars($fechaCorte); ?></b>
                            — <b><?php echo (int) $anioActual; ?></b> / <b><?php echo htmlspecialchars($nomMes); ?></b>
                            <span class="cxc-filtros-sub"> · Vendedor: <b><?php echo htmlspecialchars($labelVendedor); ?></b></span>
                        </h3>
                        <?php if ($rangoActual !== '' || $clienteActual !== '') { ?>
                        <p class="cxc-filtros-activos">
                            Filtros activos:
                            <?php if ($rangoActual !== '') { ?>
                                <span class="label label-warning">Rango: <?php echo htmlspecialchars($rangoActual); ?></span>
                            <?php } ?>
                            <?php if ($clienteActual !== '') { ?>
                                <span class="label label-info">Cliente: <?php echo htmlspecialchars($clienteActual); ?></span>
                            <?php } ?>
                            <a href="#" class="cxc-limpiar-filtros-detalle">Limpiar detalle</a>
                        </p>
                        <?php } ?>
                    </div>

                    <div class="cxc-filtros-controles">
                        <div class="cxc-filtro-item">
                            <label for="anioCxc">Año</label>
                            <select class="form-control selectpicker" id="anioCxc" data-live-search="true">
                                <option value="">Seleccionar año</option>
                                <?php foreach ($annosPermitidos as $anio) : ?>
                                    <option value="<?php echo (int) $anio; ?>" <?php echo ($anioActual == $anio) ? 'selected' : ''; ?>><?php echo (int) $anio; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="cxc-filtro-item">
                            <label for="mesCxc">Mes</label>
                            <select class="form-control selectpicker" id="mesCxc" data-live-search="true">
                                <option value="">Seleccionar mes</option>
                                <?php foreach ($meses as $num => $nombre) : ?>
                                    <option value="<?php echo (int) $num; ?>" <?php echo ($mesActual == $num) ? 'selected' : ''; ?>><?php echo htmlspecialchars($nombre); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="cxc-filtro-item cxc-filtro-item--wide">
                            <label for="vendedorCxc">Vendedor</label>
                            <select class="form-control selectpicker" id="vendedorCxc" data-live-search="true">
                                <option value="">TODOS</option>
                                <?php foreach ($vendedores as $v) : ?>
                                    <option value="<?php echo htmlspecialchars($v['codigo']); ?>" <?php echo ($vendedorActual === $v['codigo']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['codigo'] . ' - ' . $v['descripcion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="cxc-filtro-item cxc-filtro-item--check">
                            <label class="cxc-check-todos-vendedores" for="cxcIncluirTodosVendedores"
                                title="Solo aplica a las tablas Cartera por vendedor y Ventas por vendedor">
                                <input type="checkbox"
                                    id="cxcIncluirTodosVendedores"
                                    <?php echo $todosVendedores ? 'checked' : ''; ?>>
                                Incluir todos los vendedores
                                <small class="text-muted">(tablas por vendedor)</small>
                            </label>
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

            </div><!-- /.cxc-seccion-cxc -->

            </div>
        </div>
    </section>
</div>
