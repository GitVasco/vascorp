<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos")) {
    denegarAccesoModulo();
    return;
}

$puedeConciliarFicha = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "ficha_modelos", "conciliar");
$puedeEditarCategoriasModelos = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "categorias_modelos", "editar");
$modeloInicialFicha = isset($_GET["modelo"]) ? trim($_GET["modelo"]) : "";
$anioActualFicha = (int) date("Y");
$mesActualFicha = (int) date("n");
$desdeInicialFicha = isset($_GET["desde"]) ? trim($_GET["desde"]) : "";
$hastaInicialFicha = isset($_GET["hasta"]) ? trim($_GET["hasta"]) : "";
if ($desdeInicialFicha === "" || $hastaInicialFicha === "") {
    $anioInicialFicha = isset($_GET["anio"]) ? (int) $_GET["anio"] : $anioActualFicha;
    $mesInicialFicha = isset($_GET["mes"]) ? (int) $_GET["mes"] : $mesActualFicha;
    if ($anioInicialFicha < 2021 || $anioInicialFicha > $anioActualFicha) {
        $anioInicialFicha = $anioActualFicha;
    }
    if ($mesInicialFicha < 1 || $mesInicialFicha > 12) {
        $mesInicialFicha = $mesActualFicha;
    }
    if ($anioInicialFicha === $anioActualFicha && $mesInicialFicha > $mesActualFicha) {
        $mesInicialFicha = $mesActualFicha;
    }
    $desdeInicialFicha = sprintf("%04d-%02d", $anioInicialFicha, $mesInicialFicha);
    $hastaInicialFicha = $desdeInicialFicha;
}
$mesesNombreFicha = array(
    1 => "Ene", 2 => "Feb", 3 => "Mar", 4 => "Abr",
    5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Ago",
    9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dic"
);
$etiquetaPeriodoInicialFicha = $desdeInicialFicha === $hastaInicialFicha
    ? ($mesesNombreFicha[(int) substr($hastaInicialFicha, 5, 2)] . " " . substr($hastaInicialFicha, 0, 4))
    : ($mesesNombreFicha[(int) substr($desdeInicialFicha, 5, 2)] . " " . substr($desdeInicialFicha, 0, 4)
        . " – " . $mesesNombreFicha[(int) substr($hastaInicialFicha, 5, 2)] . " " . substr($hastaInicialFicha, 0, 4));
$paletaColoresFicha = array();
$archivoPaletaFicha = dirname(__FILE__) . "/../js/data/colores-modelos.json";
if (is_readable($archivoPaletaFicha)) {
    $paletaDecodificada = json_decode(file_get_contents($archivoPaletaFicha), true);
    if (is_array($paletaDecodificada)) {
        $paletaColoresFicha = $paletaDecodificada;
    }
}
?>

<div class="content-wrapper ficha-modelos-page">
    <section class="content-header">
        <h1>Ficha gerencial de modelos <small>Análisis integral por período</small></h1>
        <ol class="breadcrumb">
            <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
            <li class="active">Análisis de modelos</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-primary ficha-filtros">
            <div class="box-body">
                <div class="row ficha-filtros-row">
                    <div class="col-md-2 col-sm-6">
                        <label>Marca</label>
                        <select class="form-control" id="fichaFiltroMarca">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label>Modelo</label>
                        <select class="form-control selectpicker" id="fichaFiltroModelo" data-live-search="true" data-width="100%">
                            <option value="">Cargando modelos...</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-8 ficha-periodo-wrap">
                        <label>Período</label>
                        <button type="button" class="btn btn-default btn-block ficha-periodo-btn" id="fichaFiltroPeriodo">
                            <i class="fa fa-calendar"></i>
                            <span id="fichaFiltroPeriodoTexto"><?php echo htmlspecialchars($etiquetaPeriodoInicialFicha); ?></span>
                            <i class="fa fa-caret-down pull-right"></i>
                        </button>
                        <div id="fichaPeriodoPanel" class="ficha-periodo-panel" style="display:none;">
                            <div class="ficha-periodo-presets">
                                <button type="button" class="ficha-periodo-preset" data-preset="mes_actual">Mes actual</button>
                                <button type="button" class="ficha-periodo-preset" data-preset="ultimos_3">Últimos 3 meses</button>
                                <button type="button" class="ficha-periodo-preset" data-preset="ultimos_6">Últimos 6 meses</button>
                                <button type="button" class="ficha-periodo-preset" data-preset="ultimos_12">Últimos 12 meses</button>
                                <button type="button" class="ficha-periodo-preset" data-preset="anio_actual">Año actual</button>
                                <button type="button" class="ficha-periodo-preset" data-preset="anio_anterior">Año anterior</button>
                            </div>
                            <div class="ficha-periodo-custom">
                                <p class="ficha-periodo-custom-titulo">Rango personalizado</p>
                                <label for="fichaPeriodoDesdeSelect">Desde</label>
                                <select class="form-control input-sm" id="fichaPeriodoDesdeSelect"></select>
                                <label for="fichaPeriodoHastaSelect">Hasta</label>
                                <select class="form-control input-sm" id="fichaPeriodoHastaSelect"></select>
                                <p class="help-block">Hasta 12 meses · stock siempre actual</p>
                                <div class="ficha-periodo-acciones">
                                    <button type="button" class="btn btn-default btn-sm" id="fichaPeriodoCancelar">Cancelar</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="fichaPeriodoAplicar">Aplicar</button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="fichaFiltroDesde" value="<?php echo htmlspecialchars($desdeInicialFicha); ?>">
                        <input type="hidden" id="fichaFiltroHasta" value="<?php echo htmlspecialchars($hastaInicialFicha); ?>">
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-block" id="btnCargarFichaModelo">
                            <i class="fa fa-search"></i> Analizar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="fichaMensajeGlobal" class="alert alert-info" style="display:none;"></div>

        <div id="fichaContenido" style="display:none;">
            <div class="ficha-cabecera-kpis">
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Ventas acumuladas</span><strong id="kpiVentasAcumuladas">—</strong><small id="kpiPrecioLista9">Lista 9: —</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Unidades vendidas</span><strong id="kpiUnidades">—</strong><small>Unidades netas</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Utilidad</span><strong id="kpiUtilidad">—</strong><small id="kpiCostoVenta">—</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Margen promedio</span><strong id="kpiMargen">—</strong><small id="kpiCostoEstado">—</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Stock disponible</span><strong id="kpiStockDisponible">—</strong><small>Actual · stock − pedidos</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Rotación estimada</span><strong id="kpiRotacionPromedio">—</strong><small>Prom. mensual / stock actual</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Días de inventario</span><strong id="kpiDiasInventario">—</strong><small>Cobertura vs ritmo del período</small></div>
            </div>

            <div class="ficha-cuerpo-grid">
                <div class="ficha-cuerpo-modelo">
                    <div class="box ficha-lateral-card">
                        <div class="box-header with-border"><h3 class="box-title">Modelo</h3></div>
                        <div class="ficha-modelo-superior">
                            <div class="box-body text-center ficha-imagen-body">
                                <img id="fichaModeloImagen" class="img-responsive ficha-modelo-imagen" alt="Imagen del modelo">
                            </div>
                            <div class="box-body ficha-info-modelo-body">
                                <h3 class="box-title">Información del modelo</h3>
                                <div class="ficha-titulo-linea">
                                    <h2 id="fichaModeloNombre">—</h2>
                                </div>
                                <div class="ficha-estado-linea"><span class="label label-success" id="fichaModeloEstado">Activo</span></div>
                                <p class="ficha-identidad">
                                    <span>Código: <strong id="fichaModeloCodigo">—</strong></span>
                                    <span>Marca: <strong id="fichaModeloMarca">—</strong></span>
                                    <span>Categoría: <strong id="fichaModeloCategoria">—</strong></span>
                                    <span>Subcategoría: <strong id="fichaModeloSubcategoria">—</strong></span>
                                    <span>Período: <strong id="fichaModeloPeriodo">—</strong></span>
                                </p>
                                <div class="ficha-rankings-modelo" id="fichaRankingsModelo">
                                    <div class="ficha-rank-item">
                                        <span class="ficha-rank-label">Ranking general</span>
                                        <strong id="fichaRankGeneral">—</strong>
                                    </div>
                                    <div class="ficha-rank-item">
                                        <span class="ficha-rank-label">Ranking categoría</span>
                                        <strong id="fichaRankCategoria">—</strong>
                                    </div>
                                    <div class="ficha-rank-item">
                                        <span class="ficha-rank-label">Ranking subcategoría</span>
                                        <strong id="fichaRankSubcategoria">—</strong>
                                    </div>
                                </div>
                                <p id="fichaClasificarLink" class="ficha-clasificar-link" style="display:none;margin-top:8px;">
                                    <a id="fichaClasificarHref" href="index.php?ruta=categorias-modelos">Clasificar este modelo</a>
                                </p>
                            </div>
                        </div>
                        <div class="box-body ficha-lateral-seccion">
                            <h3 class="box-title">Colores disponibles</h3>
                            <div id="fichaColoresDisponibles" class="ficha-colores-disponibles">—</div>
                        </div>
                        <div class="box-body ficha-lateral-seccion">
                            <h3 class="box-title">Tallas disponibles</h3>
                            <div id="fichaTallasDisponibles" class="ficha-tallas-disponibles">—</div>
                        </div>
                    </div>
                </div>
                <div class="ficha-cuerpo-lideres ficha-lideres-columna">
                            <div class="box ficha-lider-card ficha-lider-color">
                                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-trophy"></i> Color líder</h3></div>
                                <div class="box-body text-center">
                                    <strong class="ficha-lider-nombre" id="fichaColorLiderNombre">—</strong>
                                    <div class="ficha-lider-principal" id="fichaColorLiderParticipacion">—</div>
                                    <small>Participación en ventas</small>
                                    <div class="ficha-lider-datos">
                                        <div><strong id="fichaColorLiderVentas">—</strong><span>Ventas</span></div>
                                        <div><strong id="fichaColorLiderUtilidad">—</strong><span>Utilidad</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="box ficha-lider-card ficha-lider-talla">
                                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-trophy"></i> Talla líder</h3></div>
                                <div class="box-body text-center">
                                    <strong class="ficha-lider-nombre" id="fichaTallaLiderNombre">—</strong>
                                    <div class="ficha-lider-principal" id="fichaTallaLiderParticipacion">—</div>
                                    <small>Participación en ventas</small>
                                    <div class="ficha-lider-datos">
                                        <div><strong id="fichaTallaLiderRotacion">—</strong><span>Rotación</span></div>
                                        <div><strong id="fichaTallaLiderMargen">—</strong><span>Margen</span></div>
                                    </div>
                                </div>
                            </div>
                </div>
                <div class="ficha-cuerpo-zona">
                    <div class="box ficha-comercial-card">
                        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-map-marker"></i> Zona líder</h3></div>
                        <div class="box-body text-center">
                            <strong class="ficha-comercial-nombre" id="fichaZonaLiderNombre">—</strong>
                            <div class="ficha-comercial-principal" id="fichaZonaLiderParticipacion">—</div>
                            <small>Participación en ventas</small>
                            <div class="ficha-comercial-dato"><strong id="fichaZonaLiderVentas">—</strong><span>Ventas</span></div>
                            <div class="ficha-comercial-dato"><strong id="fichaZonaLiderUnidades">—</strong><span>Unidades</span></div>
                        </div>
                    </div>
                </div>
                <div class="ficha-cuerpo-vendedor">
                    <div class="box ficha-comercial-card">
                        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-user"></i> Vendedor líder</h3></div>
                        <div class="box-body text-center">
                            <strong class="ficha-comercial-nombre" id="fichaVendedorLiderNombre">—</strong>
                            <div class="ficha-comercial-principal" id="fichaVendedorLiderParticipacion">—</div>
                            <small>Participación en ventas</small>
                            <div class="ficha-comercial-dato"><strong id="fichaVendedorLiderVentas">—</strong><span>Ventas</span></div>
                            <div class="ficha-comercial-dato"><strong id="fichaVendedorLiderUtilidad">—</strong><span>Utilidad</span></div>
                        </div>
                    </div>
                </div>
                <div class="ficha-cuerpo-cliente">
                    <div class="box ficha-comercial-card">
                        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-briefcase"></i> Cliente líder</h3></div>
                        <div class="box-body text-center">
                            <strong class="ficha-comercial-nombre" id="fichaClienteLiderNombre">—</strong>
                            <div class="ficha-comercial-principal" id="fichaClienteLiderVentas">—</div>
                            <small>Ventas</small>
                            <div class="ficha-comercial-dato"><strong id="fichaClienteLiderUnidades">—</strong><span>Unidades vendidas</span></div>
                            <div class="ficha-comercial-dato"><strong id="fichaClienteLiderUltimaCompra">—</strong><span>Última compra</span></div>
                        </div>
                    </div>
                </div>
                <div class="ficha-cuerpo-preguntas">
                    <div class="box ficha-preguntas-card">
                        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-question-circle"></i> Preguntas rápidas</h3></div>
                        <div class="box-body">
                            <ul class="ficha-preguntas-lista">
                                <li><i class="fa fa-paint-brush ficha-pregunta-icono azul"></i><span>¿Cuál es el color más vendido?</span><strong id="preguntaColorMasVendido"></strong></li>
                                <li><i class="fa fa-tag ficha-pregunta-icono morado"></i><span>¿Qué talla rota más?</span><strong id="preguntaTallaRotaMas"></strong></li>
                                <li><i class="fa fa-bar-chart ficha-pregunta-icono azul"></i><span>¿Promedio mensual de venta?</span><strong id="preguntaPromedioMensual"></strong></li>
                                <li><i class="fa fa-line-chart ficha-pregunta-icono celeste"></i><span>¿Proyección del próximo mes?</span><strong id="preguntaProyeccionMes"></strong></li>
                                <li><i class="fa fa-money ficha-pregunta-icono verde"></i><span>¿Qué color deja mayor utilidad?</span><strong id="preguntaColorMayorUtilidad"></strong></li>
                                <li><i class="fa fa-exclamation-triangle ficha-pregunta-icono naranja"></i><span>¿Qué combinación está por agotarse?</span><strong id="preguntaCombinacionAgotarse"></strong></li>
                                <li><i class="fa fa-ban ficha-pregunta-icono rojo"></i><span>¿Qué combinación no vende?</span><strong id="preguntaCombinacionNoVende"></strong></li>
                                <li><i class="fa fa-map-marker ficha-pregunta-icono rojo"></i><span>¿Dónde se vende más?</span><strong id="preguntaZonaMayorVenta"></strong></li>
                                <li><i class="fa fa-user ficha-pregunta-icono azul"></i><span>¿Quién vende más este modelo?</span><strong id="preguntaVendedorMayorVenta"></strong></li>
                                <li><i class="fa fa-users ficha-pregunta-icono morado"></i><span>¿Cuántos clientes concentran el 80%?</span><strong id="preguntaClientesConcentracion"></strong></li>
                                <li><i class="fa fa-industry ficha-pregunta-icono naranja"></i><span>¿Qué talla debo producir más?</span><strong id="preguntaTallaProducir"></strong></li>
                                <li><i class="fa fa-arrow-down ficha-pregunta-icono rojo"></i><span>¿Qué talla debería dejar de producir?</span><strong id="preguntaTallaDejarProducir"></strong></li>
                                <li><i class="fa fa-percent ficha-pregunta-icono verde"></i><span>¿Cuál es el margen promedio?</span><strong id="preguntaMargenPromedio"></strong></li>
                                <li><i class="fa fa-calendar ficha-pregunta-icono celeste"></i><span>¿Cuál fue el mejor mes?</span><strong id="preguntaMejorMes"></strong></li>
                                <li><i class="fa fa-link ficha-pregunta-icono morado"></i><span>¿Con qué otros modelos se vende?</span><strong id="preguntaModelosRelacionados"></strong></li>
                                <li><i class="fa fa-trophy ficha-pregunta-icono dorado"></i><span>¿Cuál es su ranking?</span><strong id="preguntaRankingModelo"></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="ficha-cuerpo-matriz">
                            <div class="box box-info ficha-zona" id="zonaVariantes">
                                <div class="box-header with-border"><h3 class="box-title">Matriz color × talla</h3><span class="ficha-cargando pull-right"></span></div>
                                <div class="box-body">
                                    <div class="ficha-semaforo-leyenda" title="Desempeño = ventas de la combinación / ventas de la combinación líder">
                                        <span><i class="excelente"></i> Excelente</span>
                                        <span><i class="bueno"></i> Bueno</span>
                                        <span><i class="regular"></i> Regular</span>
                                        <span><i class="bajo"></i> Bajo</span>
                                        <span><i class="muy-bajo"></i> Muy bajo</span>
                                        <span><i class="sin-movimiento"></i> Sin movimiento</span>
                                    </div>
                                    <div class="table-responsive ficha-matriz-wrap">
                                        <table class="table table-bordered table-condensed" id="fichaMatrizVariantes"></table>
                                    </div>
                                </div>
                            </div>
                </div>
                <div class="ficha-cuerpo-combinaciones">
                            <div class="box box-success ficha-zona" id="zonaRankings">
                                <div class="box-header with-border"><h3 class="box-title">Combinaciones color × talla</h3><span class="ficha-cargando pull-right"></span></div>
                                <div class="box-body">
                                    <div class="table-responsive ficha-combinaciones-wrap">
                                        <table class="table table-condensed ficha-combinaciones-tabla">
                                            <thead>
                                                <tr>
                                                    <th>Combinación</th>
                                                    <th>Ventas del período</th>
                                                    <th>Prom. unidades/mes</th>
                                                    <th>Stock</th>
                                                    <th>Pedidos</th>
                                                    <th>Rotación</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaCombinacionesFicha"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                </div>
                <div class="ficha-cuerpo-ventas-zona">
                    <div class="box box-success ficha-zona">
                        <div class="box-header with-border"><h3 class="box-title">Ventas por zona comercial</h3></div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-condensed ficha-zonas-tabla">
                                    <thead>
                                        <tr>
                                            <th>Zona comercial</th>
                                            <th>Ventas</th>
                                            <th>Participación</th>
                                            <th>Unidades</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaVentasZonaFicha"></tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th id="totalVentasZonaFicha">—</th>
                                            <th>100%</th>
                                            <th id="totalUnidadesZonaFicha">—</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ficha-cuerpo-top-vendedores">
                    <div class="box box-primary ficha-zona">
                        <div class="box-header with-border"><h3 class="box-title">Top vendedores</h3></div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-condensed ficha-vendedores-tabla">
                                    <thead>
                                        <tr>
                                            <th>Vendedor</th>
                                            <th>Ventas (S/)</th>
                                            <th>Cantidad</th>
                                            <th>Participación</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaTopVendedoresFicha"></tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th id="totalVentasVendedoresFicha">—</th>
                                            <th id="totalCantidadVendedoresFicha">—</th>
                                            <th>100%</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ficha-cuerpo-top-clientes">
                    <div class="box box-info ficha-zona">
                        <div class="box-header with-border"><h3 class="box-title">Top clientes</h3></div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-condensed ficha-clientes-tabla">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Ventas (S/)</th>
                                            <th>Unidades</th>
                                            <th>Participación</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaTopClientesFicha"></tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th id="totalVentasClientesFicha">—</th>
                                            <th id="totalUnidadesClientesFicha">—</th>
                                            <th>100%</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-primary ficha-zona" id="zonaEvolucion">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-line-chart"></i> Evolución mensual comparativa</h3><span class="ficha-cargando pull-right"></span></div>
                <div class="box-body">
                    <div class="row ficha-evolucion-row">
                        <div class="col-md-6 ficha-evolucion-columna">
                            <div class="ficha-evolucion-leyenda">
                                <span><i class="anio-anterior"></i><b id="leyendaAnioAnteriorFicha">Año anterior</b></span>
                                <span><i class="anio-actual"></i><b id="leyendaAnioActualFicha">Año seleccionado</b></span>
                            </div>
                            <h5 class="text-center ficha-grafico-titulo"><i class="fa fa-cubes"></i> <strong id="tituloGraficoUnidadesFicha">Unidades netas</strong></h5>
                            <div class="chart ficha-grafico-compacto"><canvas id="graficoUnidadesFicha" height="105"></canvas></div>
                        </div>
                        <div class="col-md-6 ficha-evolucion-columna">
                            <div class="ficha-comparativo-anual">
                                <div class="ficha-comparativo-periodo">
                                    <i class="fa fa-calendar-o ficha-comparativo-icono"></i>
                                    <span id="comparativoAnioAnteriorFicha">Año anterior</span>
                                    <strong id="comparativoVentasAnteriorFicha">—</strong><small><i class="fa fa-money"></i> Ventas</small>
                                    <strong id="comparativoUnidadesAnteriorFicha">—</strong><small><i class="fa fa-cubes"></i> Unidades</small>
                                </div>
                                <div class="ficha-comparativo-periodo actual">
                                    <i class="fa fa-calendar-check-o ficha-comparativo-icono"></i>
                                    <span id="comparativoAnioActualFicha">Año seleccionado</span>
                                    <strong id="comparativoVentasActualFicha">—</strong><small><i class="fa fa-money"></i> Ventas</small>
                                    <strong id="comparativoUnidadesActualFicha">—</strong><small><i class="fa fa-cubes"></i> Unidades</small>
                                </div>
                                <div class="ficha-comparativo-tendencia" id="comparativoTendenciaFicha">
                                    <i class="fa fa-exchange ficha-comparativo-icono" id="comparativoIconoTendenciaFicha"></i>
                                    <span>Tendencia interanual de unidades</span>
                                    <strong id="comparativoDireccionFicha">—</strong>
                                    <small id="comparativoVariacionFicha">—</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($puedeConciliarFicha) { ?>
            <div class="box box-default collapsed-box ficha-zona" id="zonaConciliacion">
                <div class="box-header with-border">
                    <h3 class="box-title">Panel técnico de conciliación</h3>
                    <div class="box-tools pull-right"><button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button></div>
                </div>
                <div class="box-body">
                    <div id="fichaConciliacionContenido">Se cargará con el análisis.</div>
                </div>
            </div>
            <?php } ?>
        </div>
    </section>
</div>

<script>
window.fichaModelosConfig = {
    modeloInicial: <?php echo json_encode($modeloInicialFicha); ?>,
    desdeInicial: <?php echo json_encode($desdeInicialFicha); ?>,
    hastaInicial: <?php echo json_encode($hastaInicialFicha); ?>,
    puedeConciliar: <?php echo $puedeConciliarFicha ? "true" : "false"; ?>,
    puedeEditarCategoriasModelos: <?php echo $puedeEditarCategoriasModelos ? "true" : "false"; ?>,
    paletaColores: <?php echo json_encode($paletaColoresFicha, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};
</script>
