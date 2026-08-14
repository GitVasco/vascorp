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
										La sugerencia ya trae el patrón histórico del mismo mes (p. ej. subida de fin de año).
										Si la campaña de este año será parecida, no hace falta factor; si será más fuerte o más débil, ajústalo en Factores.
									</p>
									<p class="proy-hint" id="lblTendenciaHist" style="margin-top:-4px;"></p>
									<p class="proy-hint" id="lblGlobalHist" style="margin-top:-4px;"></p>
									<div class="table-responsive">
										<table class="table table-hover table-condensed proy-table" id="tablaHistEstacional">
											<thead>
												<tr>
													<th>Mes</th>
													<th title="Histórico + Δ vs hace 2 años">Hace 1 año</th>
													<th title="Histórico + Δ vs hace 3 años">Hace 2 años</th>
													<th title="Sin comparación (no hay hace 4 años)">Hace 3 años</th>
													<th>Promedio</th>
													<th>Sugerencia</th>
													<th>Δ vs mes ant.</th>
												</tr>
											</thead>
											<tbody>
												<tr><td colspan="7" class="text-muted text-center">—</td></tr>
											</tbody>
											<tfoot id="tablaHistEstacionalFoot"></tfoot>
										</table>
									</div>
									<p class="proy-hint">
										<span class="proy-legenda proy-legenda-parcial"></span> Mes aún abierto: se muestra como referencia y no entra al promedio/sugerencia.
									</p>
								</div>

								<div class="tab-pane" id="tabFact">
									<p class="proy-hint">
										Catálogo en
										<a href="index.php?ruta=proyeccion-comercial-factores">Factores</a>
										· elige mes, marca checks y mira el resultado estimado antes de ir a Cantidades.
									</p>
									<div class="row proy-fact-bar">
										<div class="col-sm-4">
											<label class="proy-lbl">Mes</label>
											<select class="form-control selectpicker" id="mesFactorSelect"
												data-live-search="true" data-width="100%" data-size="8"
												title="Seleccionar mes"></select>
										</div>
										<div class="col-sm-8">
											<div class="proy-factor-preview" id="proyFactorPreview">
												<div class="proy-fp-item"><em>Sugerencia</em><b id="fpSug">—</b></div>
												<div class="proy-fp-item"><em>Ajuste factores</em><b id="fpAj">—</b></div>
												<div class="proy-fp-item proy-fp-result"><em>Resultado estimado</em><b id="fpRes">—</b></div>
												<div class="proy-fp-item"><em>Oficial actual</em><b id="fpOfi">—</b></div>
											</div>
										</div>
									</div>
									<div id="resumenFactorLinea" class="proy-hint" style="margin-bottom:6px;"></div>
									<div id="listaCatalogoChecks" class="proy-catalogo-checks">
										<p class="text-muted">Sin factores en el catálogo.</p>
									</div>
									<hr class="proy-sep">
									<h5 class="proy-subttl">Factores agregados por mes</h5>
									<p class="proy-hint">Resumen del modelo. Usa <em>Editar</em> para cambiar los checks de ese mes.</p>
									<div class="table-responsive">
										<table class="table table-hover table-condensed proy-table" id="tablaFactoresPorMes">
											<thead>
												<tr>
													<th>Mes</th>
													<th>Factores</th>
													<th>Ajuste</th>
													<th>Sug. + aj.</th>
													<th>Oficial</th>
													<th></th>
												</tr>
											</thead>
											<tbody>
												<tr><td colspan="6" class="text-muted text-center">Sin datos</td></tr>
											</tbody>
										</table>
									</div>
								</div>

								<div class="tab-pane" id="tabCant">
									<p class="proy-hint">
										<strong>Historial</strong> = ventas del mismo mes en los 3 años previos.
										<strong>Desv.</strong> = diferencia del oficial vs sug.+factores.
										Si supera 10% sin factor, elige un <strong>motivo</strong> en la fila.
									</p>
									<div class="table-responsive">
										<table class="table table-hover table-condensed proy-table" id="tablaMesesModelo">
											<thead>
												<tr>
													<th>Mes</th>
													<th title="Mismo mes · 3 años atrás">Historial</th>
													<th>Sug.</th>
													<th>Factores</th>
													<th>% mes</th>
													<th>Oficial</th>
													<th title="Oficial vs sug.+factores">Desv.</th>
													<th>Motivo</th>
													<th>Estado</th>
												</tr>
											</thead>
											<tbody>
												<tr><td colspan="9" class="text-muted text-center">Sin meses</td></tr>
											</tbody>
										</table>
									</div>
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
	</section>
</div>
