<?php
if (!function_exists("usuarioPuedeVerModulo")
	|| !usuarioPuedeVerModulo("gestion_comercial", "proyeccion_comercial_modelos")
) {
	denegarAccesoModulo();
	return;
}

$puedeEditar = function_exists("usuarioPuedeModulo")
	&& usuarioPuedeModulo("gestion_comercial", "proyeccion_comercial_modelos", "editar");
$puedePublicar = function_exists("usuarioPuedeModulo")
	&& usuarioPuedeModulo("gestion_comercial", "proyeccion_comercial_modelos", "publicar");

$planInicial = isset($_GET["plan"]) ? (int) $_GET["plan"] : 0;
$modeloInicial = isset($_GET["modelo"]) ? trim((string) $_GET["modelo"]) : "";
if ($modeloInicial !== "" && !preg_match('/^[A-Za-z0-9._-]+$/', $modeloInicial)) {
	$modeloInicial = "";
}
$paletaColoresProy = array();
$archivoPaletaProy = dirname(__FILE__) . "/../js/data/colores-modelos.json";
if (is_readable($archivoPaletaProy)) {
	$paletaDecodificadaProy = json_decode(file_get_contents($archivoPaletaProy), true);
	if (is_array($paletaDecodificadaProy)) {
		$paletaColoresProy = $paletaDecodificadaProy;
	}
}
?>

<div class="content-wrapper proyeccion-comercial-wrap">
	<section class="content-header proy-header">
		<h1 id="proyTituloPagina">Proyección comercial <small>por modelo</small></h1>
		<ol class="breadcrumb">
			<li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
			<li class="active">Proyección</li>
		</ol>
	</section>

	<section class="content proy-content">

		<div id="pantallaListado">
			<div class="box box-solid">
				<div class="box-header with-border">
					<h3 class="box-title"><i class="fa fa-calendar"></i> Proyecciones</h3>
					<div class="box-tools pull-right">
						<a href="index.php?ruta=proyeccion-comercial-factores" class="btn btn-box-tool" title="Factores">
							<i class="fa fa-tags"></i> Factores
						</a>
					</div>
				</div>
				<div class="box-body">
					<?php if ($puedeEditar) { ?>
					<form class="form-inline proy-crear-plan" id="formCrearPlan" onsubmit="return false;">
						<div class="form-group">
							<label>Desde</label>
							<input type="month" class="form-control input-sm" id="planDesde">
						</div>
						<div class="form-group">
							<label>Hasta</label>
							<input type="month" class="form-control input-sm" id="planHasta">
						</div>
						<div class="form-group">
							<input type="text" class="form-control input-sm" id="planNombre" maxlength="120" placeholder="Nombre (opcional)">
						</div>
						<button type="button" class="btn btn-success btn-sm" id="btnCrearPlan">
							<i class="fa fa-plus"></i> Nueva
						</button>
					</form>
					<?php } ?>
					<div class="table-responsive">
						<table class="table table-hover table-condensed proy-table" id="tablaPlanes">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nombre</th>
									<th>Rango</th>
									<th>Estado</th>
									<th>Líneas</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<tr><td colspan="6" class="text-muted text-center">Cargando…</td></tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<div id="pantallaPlan" style="display:none;">
			<div class="box box-solid proy-toolbar-box">
				<div class="box-body proy-plan-toolbar">
					<button type="button" class="btn btn-default btn-sm" id="btnVolverListado">
						<i class="fa fa-arrow-left"></i> Listado
					</button>
					<span id="lblPlanActivoBarra" class="proy-plan-meta"></span>
					<span id="proyCargaBarra" class="proy-carga-barra" style="display:none;">
						<i class="fa fa-circle-o-notch fa-spin"></i>
						<span id="proyCargaBarraTxt">Cargando…</span>
					</span>
					<a href="index.php?ruta=proyeccion-comercial-masiva" class="btn btn-default btn-sm" id="btnIrMasiva">
						<i class="fa fa-th"></i> Masiva
					</a>
					<a href="index.php?ruta=proyeccion-comercial-factores" class="btn btn-default btn-sm">
						<i class="fa fa-tags"></i> Factores
					</a>
				</div>
			</div>

			<div class="proy-stats" id="proyStats">
				<div class="proy-stat proy-stat--primary" id="cardAvance">
					<div class="proy-stat-label"><i class="fa fa-bullseye"></i> Avance</div>
					<div class="proy-stat-value" id="stAvance">0%</div>
					<div class="proy-stat-meta" id="stAvanceMeta">0 de 0 modelos</div>
					<div class="progress proy-progress">
						<div class="progress-bar" id="stBarra" style="width:0%"></div>
					</div>
				</div>
				<div class="proy-stat proy-stat--ok" id="cardProyectados">
					<div class="proy-stat-label"><i class="fa fa-cubes"></i> Proyectados</div>
					<div class="proy-stat-value" id="stProyectados">0</div>
					<div class="proy-stat-meta" id="stProyectadosMeta">modelos con meses cargados</div>
				</div>
				<div class="proy-stat proy-stat--warn" id="cardPendientes">
					<div class="proy-stat-label"><i class="fa fa-clock-o"></i> Pendientes</div>
					<div class="proy-stat-value" id="stPendientes">0</div>
					<div class="proy-stat-meta" id="stPendientesMeta">modelos activos sin proyectar</div>
				</div>
				<div class="proy-stat proy-stat--neutral" id="cardUds">
					<div class="proy-stat-label"><i class="fa fa-th"></i> Unidades proyectadas</div>
					<div class="proy-stat-value" id="stUds">0</div>
					<div class="proy-stat-meta" id="stUdsMeta">de 0 modelos ya proyectados</div>
				</div>
				<div class="proy-stat proy-stat--warn" id="cardBorrador">
					<div class="proy-stat-label"><i class="fa fa-pencil"></i> Borrador</div>
					<div class="proy-stat-value" id="stBorrador">0</div>
					<div class="proy-stat-meta" id="stBorradorMeta">líneas aún no publicadas</div>
				</div>
				<div class="proy-stat proy-stat--ok" id="cardPublicadas">
					<div class="proy-stat-label"><i class="fa fa-check"></i> Publicadas</div>
					<div class="proy-stat-value" id="stPublicadas">0</div>
					<div class="proy-stat-meta" id="stPublicadasMeta">líneas publicadas</div>
				</div>
			</div>

			<div class="box box-solid">
				<div class="box-header with-border">
					<h3 class="box-title"><i class="fa fa-cube"></i> Modelos</h3>
				</div>
				<div class="box-body proy-modelos-body">
					<div class="proy-modelos-layout">
						<aside class="proy-modelos-lista">
							<div class="proy-lista-filtros">
								<label class="proy-lbl">Marca</label>
								<select class="form-control input-sm" id="proyMarca">
									<option value="0">Todas</option>
								</select>
								<input type="text" class="form-control input-sm" id="proyQ" maxlength="100"
									placeholder="Buscar modelo…">
								<div class="proy-lista-chips">
									<button type="button" class="proy-lista-chip is-active" data-filtro="pendientes" id="chipPendientes">Pendientes</button>
									<button type="button" class="proy-lista-chip" data-filtro="proyectados" id="chipProyectados">Proyectados</button>
									<button type="button" class="proy-lista-chip" data-filtro="todos" id="chipTodos">Todos</button>
								</div>
							</div>
							<div class="proy-lista-items" id="listaModelos">
								<div class="proy-lista-vacio">Cargando modelos…</div>
							</div>
							<div class="proy-lista-foot">
								<span class="text-muted" id="lblListaCount"></span>
							</div>
						</aside>
						<div class="proy-modelos-detalle">
							<div id="panelModeloVacio" class="proy-empty">
								<span class="text-muted">Elige un modelo en la lista de la izquierda para abrirlo o reabrirlo.</span>
							</div>
							<div id="panelModeloCarga" class="proy-carga" style="display:none;">
								<div class="proy-carga-box">
									<i class="fa fa-circle-o-notch fa-spin"></i>
									<strong id="proyCargaTitulo">Cargando modelo…</strong>
									<span id="proyCargaMeta">Historial, receta y panorama. Puede tardar unos segundos.</span>
								</div>
							</div>

					<div id="panelModeloActivo" style="display:none;">
						<div class="proy-modelo-bar">
							<div>
								<strong id="mdlTitulo">—</strong>
								<span id="mdlMeta" class="text-muted proy-meta-inline"></span>
							</div>
							<div class="proy-kpis">
								<span class="proy-kpi"><em>Lista 9</em> <b id="kpiLista9">—</b></span>
								<span class="proy-kpi"><em>Stock</em> <b id="kpiStock">—</b></span>
								<span class="proy-kpi"><em>Proceso</em> <b id="kpiProceso">—</b></span>
								<?php if ($puedeEditar) { ?>
								<button type="button" class="btn btn-default btn-xs" id="btnAsegurarLineas" title="Crear los meses que falten en este modelo">
									<i class="fa fa-magic"></i> Preparar meses
								</button>
								<?php } ?>
							</div>
						</div>

						<div class="nav-tabs-custom proy-tabs">
							<ul class="nav nav-tabs">
								<li class="active">
									<a href="#tabHist" data-toggle="tab">
										<span class="proy-tab-n">1</span> Historial
									</a>
								</li>
								<li>
									<a href="#tabFact" data-toggle="tab">
										<span class="proy-tab-n">2</span> Factores
										<small id="mdlRangoFact" class="text-muted"></small>
									</a>
								</li>
								<li>
									<a href="#tabCant" data-toggle="tab">
										<span class="proy-tab-n">3</span> Cantidades
										<small id="mdlRangoPlan" class="text-muted"></small>
									</a>
								</li>
							</ul>
							<div class="tab-content">
								<div class="tab-pane active" id="tabHist">
									<p class="proy-hint">
										La sugerencia sigue lo que se vendió en el mismo mes de otros años
										(si en diciembre siempre sube, acá también).
										Si este año va a ser distinto, ajústalo en Factores.
										Clic en un mes para verlo a la derecha.
									</p>
									<p class="proy-hint proy-hist-resumen" id="lblTendenciaHist" style="margin-top:-4px;"></p>
									<p class="proy-hint proy-hist-resumen" id="lblGlobalHist" style="margin-top:-4px;"></p>
									<div class="proy-hist-cols">
										<div class="proy-hist-tabla">
											<div class="table-responsive">
												<table class="table table-hover table-condensed proy-table" id="tablaHistEstacional">
													<thead>
														<tr class="proy-hist-grupo">
															<th rowspan="2">Mes</th>
															<th colspan="4">Ventas de otros años</th>
															<th colspan="2">Propuesta de este plan</th>
														</tr>
														<tr>
															<th id="thHist1" title="Mismo mes del año anterior. Si aún no cerró, va en gris y no entra al total."></th>
															<th id="thHist2"></th>
															<th id="thHist3"></th>
															<th title="Promedio de ese mes en los años que ya cerraron">Promedio</th>
															<th id="thHistSug" class="proy-th-sug">Sugerencia</th>
															<th title="Cómo cambia la sugerencia respecto al mes de arriba">vs mes anterior</th>
														</tr>
													</thead>
													<tbody>
														<tr><td colspan="7" class="text-muted text-center">—</td></tr>
													</tbody>
													<tfoot id="tablaHistEstacionalFoot"></tfoot>
												</table>
											</div>
											<p class="proy-hint">
												<span class="proy-legenda proy-legenda-parcial"></span>
												Los números en gris son meses que todavía no cerraron: sirven de referencia y no entran al promedio, a la sugerencia ni al total.
												El porcentaje bajo cada año es la comparación con el año de al lado.
											</p>
										</div>
										<aside class="proy-modelos-decision" id="proyDecisionCol">
											<div class="proy-dec-head">
												<strong id="decTitulo">Panorama</strong>
												<span class="proy-dec-sub" id="decSub">Elige un modelo para ver tendencia y comparación.</span>
												<a href="#" class="proy-dec-back" id="decVerPlan" style="display:none;">Todo el plan</a>
											</div>
											<div class="proy-dec-body" id="decCarga" style="display:none;">
												<p class="proy-carga-mini"><i class="fa fa-circle-o-notch fa-spin"></i> Armando el panorama…</p>
											</div>
											<div class="proy-dec-body" id="decVacio">
												<p class="text-muted" style="margin:8px 0 0;">Elegí un modelo.</p>
											</div>
											<div class="proy-dec-body" id="decActivo" style="display:none;">
												<div class="proy-dec-line" id="decCompLine"></div>
												<div id="decRecoBox"></div>
												<div class="proy-dec-block">
													<div class="proy-dec-label" id="decVieneLabel">Cómo viene</div>
													<div class="proy-dec-kpis" id="decVieneKpis"></div>
												</div>
												<div class="proy-dec-block">
													<div class="proy-dec-label" id="decTendLabel">Jul–dic</div>
													<div class="proy-dec-kpis proy-dec-kpis--3" id="decTendKpis"></div>
													<div class="proy-dec-legend" id="decLegendComp"></div>
													<div id="decChartComp" class="proy-dec-chart"></div>
												</div>
											</div>
										</aside>
									</div>
									<div class="row proy-matriz-mp-row">
										<div class="col-sm-6">
											<div class="proy-matriz-sug" id="proyMatrizSugWrap">
												<div class="proy-dec-label" id="proyMatrizSugLabel">Sugerencia por color × talla</div>
												<div class="table-responsive">
													<table class="table table-condensed proy-table" id="tablaMatrizSug">
														<tbody>
															<tr><td class="text-muted">Elegí un modelo para ver el desglose.</td></tr>
														</tbody>
													</table>
												</div>
												<p class="proy-hint" id="proyMatrizSugHint"></p>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="proy-mp-riesgo" id="proyMpRiesgoWrap">
												<div class="proy-dec-label">Materia prima · alertas</div>
												<p class="proy-hint" id="proyMpRiesgoHint"></p>
												<div id="proyMpRiesgoBody"></div>
											</div>
										</div>
									</div>
								</div>

								<div class="tab-pane" id="tabFact">
									<p class="proy-hint">
										Marcá lo que va a pasar en cada mes de este plan
										(los mismos de Historial: campañas, falta de stock, precio, etc.).
										El sistema suma o resta unidades a la sugerencia. Después lo confirmás en Cantidades.
									</p>
									<div class="proy-fact-meses" id="proyFactMeses"></div>
									<select class="form-control" id="mesFactorSelect" style="display:none;" title="Mes"></select>
									<aside class="proy-fact-cuenta">
										<div class="proy-fact-cuenta-head">
											<div class="proy-dec-label">Cómo queda este mes</div>
											<strong id="proyFactMesTitulo">Elegí un mes</strong>
											<span class="proy-fact-estado" id="proyFactMesEstado"></span>
										</div>
										<div class="proy-factor-preview" id="proyFactorPreview">
											<div class="proy-fp-item"><em>Sugerencia</em><b id="fpSug">—</b></div>
											<div class="proy-fp-item"><em>Estos factores</em><b id="fpAj">—</b></div>
											<div class="proy-fp-item proy-fp-result"><em>Quedaría</em><b id="fpRes">—</b></div>
											<div class="proy-fp-item"><em>Oficial hoy</em><b id="fpOfi">—</b></div>
										</div>
										<p class="proy-hint" id="resumenFactorLinea"></p>
									</aside>
									<div class="proy-fact-layout">
										<div class="proy-fact-catalogo">
											<div class="proy-dec-label">Campañas y factores</div>
											<p class="proy-hint" id="proyFactCatHint">Clic en una tarjeta para marcarla o quitarla.</p>
											<div id="listaCatalogoChecks">
												<p class="text-muted">Elegí un mes para ver los factores.</p>
											</div>
										</div>
										<div class="proy-fact-todos">
											<div class="proy-dec-label">Todos los meses</div>
											<p class="proy-hint">Clic en un mes para editarlo.</p>
											<div class="table-responsive">
												<table class="table table-hover table-condensed proy-table" id="tablaFactoresPorMes">
													<thead>
														<tr>
															<th>Mes</th>
															<th>Aplica</th>
															<th>Ajuste</th>
															<th>Quedaría</th>
															<th>Oficial</th>
														</tr>
													</thead>
													<tbody>
														<tr><td colspan="5" class="text-muted text-center">Sin datos</td></tr>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>

								<div class="tab-pane" id="tabCant">
									<p class="proy-hint">
										El naranja es lo que publicás.
										Vendió es el mismo mes del año anterior: si todavía no cerró, va en gris y no entra al vs ni al total.
									</p>
									<div class="proy-cant-cols">
										<div class="proy-cant-tabla">
											<div class="table-responsive">
												<table class="table table-hover table-condensed proy-table" id="tablaMesesModelo">
													<thead>
														<tr class="proy-hist-grupo">
															<th rowspan="2">Mes</th>
															<th colspan="2">Para decidir</th>
															<th rowspan="2" class="proy-th-ofi">Oficial</th>
															<th colspan="2">Qué implica</th>
														</tr>
														<tr>
															<th id="thCantVendio">Vendió</th>
															<th>Sugerencia</th>
															<th id="thCantVs">vs año</th>
															<th>Soles</th>
														</tr>
													</thead>
													<tbody>
														<tr><td colspan="6" class="text-muted text-center">Sin meses</td></tr>
													</tbody>
													<tfoot id="tablaMesesModeloFoot"></tfoot>
												</table>
											</div>
											<p class="proy-hint">
												<span class="proy-legenda proy-legenda-parcial"></span>
												Los números en gris son meses que todavía no cerraron: sirven de referencia y no entran al vs ni al total.
											</p>
											<div class="proy-actions">
												<?php if ($puedeEditar) { ?>
												<button type="button" class="btn btn-default btn-sm" id="btnUsarBaseFactores">
													Oficial = sug. + factores
												</button>
												<button type="button" class="btn btn-primary btn-sm" id="btnGuardarModelo">
													<i class="fa fa-save"></i> Guardar
												</button>
												<?php } ?>
												<?php if ($puedePublicar) { ?>
												<button type="button" class="btn btn-warning btn-sm" id="btnPublicarModelo">
													<i class="fa fa-check"></i> Publicar modelo
												</button>
												<?php } ?>
											</div>
										</div>
										<aside class="proy-cant-lado">
											<div class="proy-dec-head">
												<strong>Este plan</strong>
												<span class="proy-dec-sub">Totales de lo que vas a publicar</span>
											</div>
											<div class="proy-cant-resumen" id="proyCantResumen"></div>
										</aside>
									</div>
									<div class="proy-cant-graf">
										<div class="proy-dec-label">Cómo se mueve mes a mes</div>
										<p class="proy-hint" id="proyCantGrafHint">
											La línea naranja es lo que publicás.
										</p>
										<div id="proyCantChartLegend" class="proy-dec-legend"></div>
										<div id="proyCantChart" class="proy-cant-chart"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" id="proyPuedeEditar" value="<?php echo $puedeEditar ? '1' : '0'; ?>">
		<input type="hidden" id="proyPuedePublicar" value="<?php echo $puedePublicar ? '1' : '0'; ?>">
		<input type="hidden" id="proyIdPeriodo" value="">
		<input type="hidden" id="proyModeloActivo" value="">
		<input type="hidden" id="proyPlanInicial" value="<?php echo $planInicial; ?>">
		<input type="hidden" id="proyModeloInicial" value="<?php echo htmlspecialchars($modeloInicial, ENT_QUOTES, 'UTF-8'); ?>">
		<script>window.proyPaletaColores = <?php echo json_encode($paletaColoresProy, JSON_UNESCAPED_UNICODE); ?>;</script>
	</section>
</div>
