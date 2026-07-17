<?php
if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos")) {
    denegarAccesoModulo();
    return;
}

$puedeConciliarFicha = function_exists("usuarioPuedeModulo")
    && usuarioPuedeModulo("gestion_comercial", "ficha_modelos", "conciliar");
$modeloInicialFicha = isset($_GET["modelo"]) ? trim($_GET["modelo"]) : "";
$anioInicialFicha = isset($_GET["anio"]) ? (int) $_GET["anio"] : (int) date("Y");
$mesInicialFicha = isset($_GET["mes"]) ? (int) $_GET["mes"] : (int) date("n");
if ($anioInicialFicha < 2021 || $anioInicialFicha > (int) date("Y")) {
    $anioInicialFicha = (int) date("Y");
}
if ($mesInicialFicha < 1 || $mesInicialFicha > 12) {
    $mesInicialFicha = (int) date("n");
}
$mesesFicha = array(
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
);
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
                <div class="row">
                    <div class="col-md-2 col-sm-4">
                        <label>Marca</label>
                        <select class="form-control" id="fichaFiltroMarca">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-8">
                        <label>Modelo</label>
                        <select class="form-control selectpicker" id="fichaFiltroModelo" data-live-search="true" data-width="100%">
                            <option value="">Cargando modelos...</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label>Año</label>
                        <select class="form-control" id="fichaFiltroAnio">
                            <?php for ($anio = (int) date("Y"); $anio >= 2021; $anio--) { ?>
                            <option value="<?php echo $anio; ?>" <?php echo $anio === $anioInicialFicha ? "selected" : ""; ?>>
                                <?php echo $anio; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <label>Mes</label>
                        <select class="form-control" id="fichaFiltroMes">
                            <?php foreach ($mesesFicha as $numero => $nombre) { ?>
                            <option value="<?php echo $numero; ?>" <?php echo $numero === $mesInicialFicha ? "selected" : ""; ?>>
                                <?php echo $nombre; ?>
                            </option>
                            <?php } ?>
                        </select>
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
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Ranking del grupo</span><strong id="kpiRankingGeneral">—</strong><small id="kpiRankingTotal">—</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Ventas acumuladas</span><strong id="kpiVentasAcumuladas">—</strong><small id="kpiPrecioLista9">Lista 9: —</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Unidades vendidas</span><strong id="kpiUnidades">—</strong><small>Unidades netas</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Utilidad</span><strong id="kpiUtilidad">—</strong><small id="kpiCostoVenta">—</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Margen promedio</span><strong id="kpiMargen">—</strong><small id="kpiCostoEstado">—</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Stock disponible</span><strong id="kpiStockDisponible">—</strong><small>Stock − pedidos</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Rotación promedio</span><strong id="kpiRotacionPromedio">—</strong><small>Veces en el período</small></div>
                <div class="ficha-kpi-card"><span class="ficha-kpi-label">Días de inventario</span><strong id="kpiDiasInventario">—</strong><small>Cobertura estimada</small></div>
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
                                    <span>Tipo: <strong id="fichaModeloTipo">—</strong></span>
                                    <span>Línea: <strong id="fichaModeloLinea">—</strong></span>
                                    <span>Período: <strong id="fichaModeloPeriodo">—</strong></span>
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
                                                    <th>Ventas del mes</th>
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
    puedeConciliar: <?php echo $puedeConciliarFicha ? "true" : "false"; ?>,
    paletaColores: <?php echo json_encode($paletaColoresFicha, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};
</script>
