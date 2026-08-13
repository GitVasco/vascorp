<?php
if (!isset($_SESSION["tarjetas"]) || (int) $_SESSION["tarjetas"] !== 1) {
	if (function_exists("denegarAccesoModulo")) {
		denegarAccesoModulo();
	} else {
		echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Sin permiso</div></section></div>';
	}
	return;
}

$idReceta = isset($_GET["idReceta"]) ? (int) $_GET["idReceta"] : 0;
if ($idReceta <= 0) {
	echo '<div class="content-wrapper"><section class="content"><div class="alert alert-warning">Falta idReceta. <a href="recetas-modelo">Volver al listado</a></div></section></div>';
	return;
}
?>
<style>
/* —— Card única: cabecera + sublíneas —— */
.rm2-card { margin-bottom:10px; }
.rm2-card > .box-header {
	display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between;
	gap:8px 12px; padding:8px 12px;
}
.rm2-card > .box-header .box-title { font-size:14px; margin:0; line-height:1.3; }
.rm2-meta {
	display:flex; flex-wrap:wrap; align-items:center; gap:4px 12px;
	font-size:13px; line-height:1.3; min-width:0; flex:1;
}
.rm2-meta .item { white-space:nowrap; }
.rm2-meta .k { color:#999; font-size:11px; font-weight:400; margin-right:3px; }
.rm2-meta .v { font-weight:600; color:#333; }
.rm2-actions { display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
.rm2-actions .btn { margin:0; }
.rm2-dirty { color:#f39c12; font-weight:600; font-size:12px; display:none; }

.rm2-box-slim > .box-body { padding:10px 12px; }
.rm2-sub-row {
	display:flex; flex-wrap:wrap; align-items:center; gap:8px 10px;
}
.rm2-sub-pick {
	display:flex; align-items:stretch; flex:1 1 280px; max-width:640px;
	border:1px solid #d2d6de; border-radius:3px; background:#fff; overflow:hidden;
}
.rm2-sub-pick .rm2-sub-info {
	flex:1; min-width:0; padding:6px 10px; cursor:pointer;
}
.rm2-sub-pick .rm2-sub-info:hover { background:#f7fafc; }
.rm2-sub-pick .cod {
	font-weight:700; font-size:13px; color:#333; line-height:1.2;
}
.rm2-sub-pick .cod.empty { color:#aaa; font-weight:500; }
.rm2-sub-pick .nom {
	font-size:11px; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.rm2-sub-pick .rm2-sub-search {
	border:0; border-left:1px solid #d2d6de; border-radius:0; padding:0 12px; background:#fafafa;
}
.rm2-sub-pick .rm2-sub-change {
	border:0; border-left:1px solid #c87f0a; border-radius:0; padding:0 14px;
	background:#f39c12; color:#fff; font-weight:600; white-space:nowrap; display:none;
}
.rm2-sub-pick .rm2-sub-change:hover,
.rm2-sub-pick .rm2-sub-change:focus { background:#e08e0b; color:#fff; }
.rm2-sub-pick .rm2-sub-add {
	border:0; border-left:1px solid #2e6da4; border-radius:0; padding:0 14px;
	background:#3c8dbc; color:#fff; font-weight:600; white-space:nowrap;
}
.rm2-sub-pick .rm2-sub-add:hover,
.rm2-sub-pick .rm2-sub-add:focus { background:#367fa9; color:#fff; }
.rm2-insumos {
	display:none; flex-wrap:wrap; gap:6px; margin:8px 0 0; min-height:0;
	padding-top:8px; border-top:1px solid #f0f0f0;
}
.rm2-insumos.has-chips { display:flex; }
.rm2-chip {
	border:1px solid #ccc; border-radius:16px; padding:4px 10px; background:#fff; cursor:pointer;
	display:inline-flex; align-items:center; gap:6px; font-size:12px;
}
.rm2-chip.active { border-color:#3c8dbc; background:#eaf5fb; box-shadow:0 0 0 2px rgba(60,141,188,.2); }
.rm2-chip:not(.active) { opacity:.78; background:#f7f7f7; }
.rm2-chip .rm2-chip-editando {
	background:#3c8dbc; color:#fff; border-radius:10px; padding:1px 7px;
	font-size:10px; font-weight:700; letter-spacing:.02em;
}
.rm2-sel-hint {
	margin-top:8px; font-size:12px; color:#555;
}
.rm2-ctx {
	margin:0 0 12px; padding:12px 14px; border-radius:4px;
	background:linear-gradient(180deg, #eef6fb 0%, #f7fbfe 100%);
	border:1px solid #b8d4e8;
}
.rm2-ctx-flash {
	display:none; margin:0 0 10px; padding:8px 10px; border-radius:3px;
	background:#dff0d8; border:1px solid #b2dba1; color:#3c763d;
	font-size:13px; font-weight:600;
	align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;
}
.rm2-ctx-flash.visible { display:flex; }
.rm2-ctx-kicker {
	font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
	color:#3c8dbc; margin-bottom:2px;
}
.rm2-ctx-nombre {
	font-size:20px; font-weight:700; color:#222; line-height:1.25; margin-bottom:2px;
}
.rm2-ctx-meta { font-size:12px; color:#666; }
.rm2-chip .rm2-chip-x { color:#dd4b39; margin-left:2px; font-size:14px; line-height:1; }
.rm2-chip .label { margin:0; }
.rm2-chip .rm2-btn-tela {
	border:1px solid #ddd; background:#fff; color:#888; border-radius:10px;
	padding:1px 8px; font-size:11px; line-height:1.4; cursor:pointer;
}
.rm2-chip .rm2-btn-tela:hover { border-color:#dd4b39; color:#dd4b39; }
.rm2-chip .rm2-btn-tela.on {
	border-color:#dd4b39; background:#dd4b39; color:#fff;
}

.rm2-mp-activa {
	display:none; margin-bottom:10px; padding:8px 12px; border-radius:4px;
	background:#dff0d8; border:1px solid #b2dba1; font-size:13px;
}
.rm2-mp-activa.visible { display:block; }
.rm2-matriz { width:100%; border-collapse:separate; border-spacing:0; }
.rm2-matriz th, .rm2-matriz td { border:1px solid #ddd; padding:6px 8px; vertical-align:middle; }
.rm2-matriz thead th { background:#f4f4f4; text-align:center; position:sticky; top:0; z-index:2; }
.rm2-matriz .rm2-color-th {
	background:#eee; text-align:left; min-width:120px; white-space:nowrap;
	position:sticky; left:0; z-index:1;
}
.rm2-matriz thead .rm2-color-th { z-index:3; }
.rm2-celda {
	min-width:80px; text-align:center; cursor:pointer; background:#fff;
	transition: background .12s;
}
.rm2-celda:hover { background:#e8f4fc; }
.rm2-celda.ok { background:#e8f8ef; }
.rm2-celda.falta { background:#fff8e6; }
.rm2-celda .rm2-celda-color { font-weight:700; font-size:12px; line-height:1.2; }
.rm2-celda .rm2-celda-art { font-size:10px; color:#999; margin-top:2px; }
.rm2-scroll { max-height:480px; overflow:auto; border:1px solid #e5e5e5; border-radius:4px; }
.rm2-mp-list { max-height:360px; overflow:auto; border:1px solid #e5e5e5; border-radius:4px; }
.rm2-mp-list table { table-layout:fixed; width:100%; }
.rm2-mp-list thead th { background:#3c8dbc; color:#fff; font-size:11px; white-space:nowrap; }
.rm2-mp-list th:nth-child(1), .rm2-mp-list td:nth-child(1) { width:26%; }
.rm2-mp-list th:nth-child(2), .rm2-mp-list td:nth-child(2) { width:12%; text-align:center; }
.rm2-mp-list th:nth-child(3), .rm2-mp-list td:nth-child(3) { width:46%; }
.rm2-mp-list th:nth-child(4), .rm2-mp-list td:nth-child(4) { width:16%; text-align:center; }
.rm2-mp-list .rm2-mp-color { font-weight:700; font-size:12px; line-height:1.2; }
.rm2-mp-list .rm2-mp-cod { font-size:10px; color:#999; }
.rm2-mp-list .rm2-mp-desc {
	font-size:11px; color:#555; line-height:1.25;
	display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.rm2-mp-list .rm2-mp-und { font-size:11px; font-weight:600; color:#333; }
.rm2-mp-list tr.activa { background:#e8f4fc; }
.rm2-tabla-tarjetas { font-size:12px; width:100%; }
.rm2-tabla-tarjetas thead th {
	background:#3c8dbc; color:#fff; white-space:nowrap; position:sticky; top:0; z-index:2;
}
.rm2-tabla-tarjetas td, .rm2-tabla-tarjetas th { vertical-align:middle !important; }
.rm2-tabla-tarjetas .rm2-art-cell {
	background:#f7f7f7; font-weight:700; white-space:nowrap;
}
.rm2-tabla-tarjetas .rm2-meta-cell { background:#fafafa; white-space:nowrap; }
.rm2-tabla-tarjetas tr.rm2-sep-talla > td { border-top:1px solid #a8b8c4 !important; }
.rm2-tabla-tarjetas tr.rm2-sep-color > td { border-top:3px solid #4a6572 !important; }
.rm2-tabla-tarjetas tr.falta td { background:#fff8e6; }
.rm2-tabla-tarjetas tr.ok td { background:#e8f8ef; }
.rm2-tabla-tarjetas td.rm2-art-cell { background:#f7f7f7 !important; }
.rm2-tabla-tarjetas td.rm2-meta-cell { background:#fafafa !important; }
.rm2-asignados {
	display:flex; flex-wrap:wrap; gap:6px; min-height:28px;
	max-height:72px; overflow:auto; padding:4px 0;
}
.rm2-asig-chip {
	display:inline-flex; align-items:center; gap:6px; padding:4px 10px;
	border:1px solid #c5e0b4; background:#f3f9f0; border-radius:14px; font-size:12px;
	cursor:pointer;
}
.rm2-asig-chip:hover { border-color:#3c8dbc; background:#eaf5fb; }
.rm2-asig-chip.activa { border-color:#3c8dbc; box-shadow:0 0 0 2px rgba(60,141,188,.25); }
.rm2-asig-chip .n { background:#00a65a; color:#fff; border-radius:10px; padding:0 6px; font-size:11px; }
.rm2-matriz .rm2-atajos .btn { margin:1px; }
.rm2-mp-activa kbd {
	padding:1px 4px; font-size:11px; background:#eee; border:1px solid #ccc; border-radius:3px;
}
.rm2-panel-title {
	font-size:13px; font-weight:600; margin:0 0 8px; color:#555;
	border-bottom:1px solid #eee; padding-bottom:6px;
}
.rm2-col-mp { border-right:1px solid #eee; padding-right:12px; margin-bottom:12px; }
.rm2-col-matriz { padding-left:4px; }
@media (min-width:992px) {
	.rm2-col-mp { margin-bottom:0; }
	.rm2-col-matriz { padding-left:12px; }
}
</style>

<div class="content-wrapper">
	<section class="content-header" style="padding-bottom:0;">
		<h1 style="margin:0 0 6px; font-size:20px;">
			Editor de receta
			<small id="rmTituloCabecera">#<?php echo $idReceta; ?></small>
		</h1>
		<ol class="breadcrumb">
			<li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
			<li><a href="recetas-modelo">Recetas por modelo</a></li>
			<li class="active">Editar</li>
		</ol>
	</section>

	<section class="content" style="padding-top:10px;">
		<input type="hidden" id="rmIdReceta" value="<?php echo $idReceta; ?>">

		<div class="box box-primary rm2-box-slim rm2-card">
			<div class="box-header with-border">
				<div class="rm2-meta">
					<span class="item"><span class="k">Modelo</span><span class="v" id="rmModelo">—</span></span>
					<span class="item"><span class="k">Nombre</span><span class="v" id="rmNombreModelo">—</span></span>
					<span class="item"><span class="k">Ver</span><span class="v" id="rmVersion">—</span></span>
					<span class="item"><span class="k">Estado</span><span class="v" id="rmEstado">—</span></span>
					<span class="item"><span class="k">Arts</span><span class="v" id="rmArtActivos">—</span></span>
					<span class="text-muted" id="rmMsgEstado" style="font-size:12px;"></span>
					<span class="rm2-dirty" id="rmDirtyFlag"><i class="fa fa-pencil"></i> Sin guardar</span>
				</div>
				<div class="rm2-actions">
					<button type="button" class="btn btn-primary btn-sm" id="rmBtnGuardar" disabled>
						<i class="fa fa-save"></i> Guardar
					</button>
					<button type="button" class="btn btn-success btn-sm" id="rmBtnPublicar" disabled>
						<i class="fa fa-check"></i> Publicar
					</button>
					<button type="button" class="btn btn-warning btn-sm" id="rmBtnDuplicar">
						<i class="fa fa-copy"></i> Nueva versión
					</button>
					<a href="recetas-modelo" class="btn btn-default btn-sm" title="Listado"><i class="fa fa-arrow-left"></i></a>
				</div>
			</div>
			<div class="box-body">
				<div class="rm2-sub-row">
					<div class="rm2-sub-pick">
						<input type="hidden" id="rmNuevaSublinea" value="">
						<div class="rm2-sub-info" id="rmBtnBuscarSublineaTop" title="Buscar sublínea" role="button">
							<div class="cod empty" id="rmNuevaSublineaCod">Buscar y agregar otra sublínea…</div>
							<div class="nom" id="rmNuevaSublineaNom">Solo agrega; la edición es del chip seleccionado abajo</div>
						</div>
						<button type="button" class="btn rm2-sub-search" id="rmBtnBuscarSublineaIcon" title="Buscar">
							<i class="fa fa-search"></i>
						</button>
						<button type="button" class="btn rm2-sub-change" id="rmBtnCambiarSublinea" title="Reemplazar la sublínea actual (aún no tiene MP)">
							<i class="fa fa-exchange"></i> Cambiar
						</button>
						<button type="button" class="btn rm2-sub-add" id="rmBtnAgregarSublinea" title="Agregar sublínea">
							<i class="fa fa-plus"></i> Agregar
						</button>
					</div>
				</div>
				<div class="rm2-insumos" id="rmChipsInsumos"></div>
				<div class="rm2-sel-hint" id="rmSublineaSeleccionadaHint" style="display:none;"></div>
			</div>
		</div>

		<div class="box box-info rm2-box-slim">
			<div class="box-header with-border">
				<h3 class="box-title" id="rmTituloArticulos">2. Asignar materia prima</h3>
			</div>
			<div class="box-body">
				<div id="rmAyudaPaso2" class="callout callout-info" style="margin-top:0;">
					<p>Agrega una sublínea arriba y selecciónala para asignar MP.</p>
				</div>
				<div id="rmPanelPaso2" style="display:none;">
					<div class="rm2-ctx" id="rmLineaActivaContexto">
						<div class="rm2-ctx-flash" id="rmCtxFlash">
							<span id="rmCtxFlashTxt"></span>
							<button type="button" class="btn btn-xs btn-default" id="rmBtnVolverLineaAnterior" style="display:none;">
								Volver a la anterior
							</button>
						</div>
						<div class="rm2-ctx-kicker">Editando ahora</div>
						<div class="rm2-ctx-nombre" id="rmCtxNombre">—</div>
						<div class="rm2-ctx-meta" id="rmCtxMeta"></div>
					</div>
					<div class="row" style="margin-bottom:10px;">
						<div class="col-sm-3">
							<label id="rmConsumoLineaLabel">Consumo</label>
							<div class="input-group input-group-sm">
								<input type="number" step="any" class="form-control" id="rmConsumoLinea" min="0">
								<span class="input-group-addon" id="rmUnidadLineaAddon">—</span>
							</div>
							<small class="text-muted">Igual para todos los artículos de esta sublínea.</small>
						</div>
						<div class="col-sm-9">
							<label>MPs ya usadas <small class="text-muted">(color MP · cuántas celdas)</small></label>
							<div class="rm2-asignados" id="rmMpsAsignadas">
								<span class="text-muted">Ninguna aún</span>
							</div>
						</div>
					</div>

					<div class="rm2-mp-activa" id="rmMpActivaBox">
						<strong>MP en mano:</strong>
						<span id="rmMpActivaTxt">—</span>
						<span class="text-muted" id="rmMpActivaUndTxt"></span>
						<button type="button" class="btn btn-xs btn-default pull-right" id="rmBtnLimpiarMpActiva">Soltar MP</button>
						<div id="rmMpActivaAcciones" style="margin-top:8px; display:none;">
							<button type="button" class="btn btn-xs btn-success rmAplicarTodos">
								<i class="fa fa-th"></i> Aplicar a todos
							</button>
							<button type="button" class="btn btn-xs btn-danger rmQuitarTodos">
								<i class="fa fa-eraser"></i> Quitar de todos
							</button>
						</div>
						<div class="text-muted" style="margin-top:6px; font-size:12px;">
							Clic celda = asignar · <kbd>Alt</kbd>+clic = quitar · columna/fila = alcance · chips de «MPs ya usadas» = poner en mano.
						</div>
					</div>

					<div class="row">
						<div class="col-md-4 rm2-col-mp">
							<div class="rm2-panel-title">Catálogo MP (por sublínea)</div>
							<div class="input-group input-group-sm" style="margin-bottom:8px;">
								<input type="text" class="form-control" id="rmFiltroMp" placeholder="Buscar por color, descripción o código…">
								<span class="input-group-btn">
									<button type="button" class="btn btn-default" id="rmBtnFiltroMp"><i class="fa fa-search"></i></button>
								</span>
							</div>
							<div class="rm2-mp-list">
								<table class="table table-condensed table-hover" id="rmTablaMp" style="margin:0;">
									<thead>
										<tr>
											<th>Color</th>
											<th>Und</th>
											<th>Detalle</th>
											<th></th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
						<div class="col-md-8 rm2-col-matriz">
							<div class="rm2-panel-title" id="rmMatrizContexto">Asignar · color artículo × talla</div>
							<div class="rm2-scroll">
								<table class="rm2-matriz" id="rmMatriz">
									<thead></thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
					</div>

					<div style="margin-top:16px;">
						<div class="rm2-panel-title">
							Tarjetas por artículo
							<small class="text-muted" style="font-weight:400;">— cómo quedaría cada tarjeta con esta receta</small>
						</div>
						<div class="rm2-scroll" style="max-height:360px;">
							<table class="table table-condensed table-bordered rm2-tabla-tarjetas" id="rmTablaTarjetasArticulo" style="margin:0;">
								<thead>
									<tr>
										<th>Artículo</th>
										<th>Color</th>
										<th>Talla</th>
										<th>MP (nombre)</th>
										<th>Sublínea</th>
										<th>Cód. MP</th>
										<th>Color MP</th>
										<th>Consumo</th>
										<th>Und</th>
										<th></th>
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="box box-warning rm2-box-slim">
			<div class="box-header with-border">
				<h3 class="box-title">3. ¿Listo para publicar?</h3>
				<div class="box-tools">
					<button type="button" class="btn btn-xs btn-default" id="rmBtnRefrescarCobertura">
						<i class="fa fa-refresh"></i> Revisar
					</button>
				</div>
			</div>
			<div class="box-body">
				<div id="rmResumenCobertura" class="row"></div>
			</div>
		</div>
	</section>
</div>

<div class="modal fade" id="modalSublineasReceta" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Elegir sublínea</h4>
			</div>
			<div class="modal-body">
				<div class="input-group">
					<input type="text" class="form-control" id="rmBuscarSublineaQ" placeholder="Código o nombre">
					<span class="input-group-btn">
						<button type="button" class="btn btn-primary" id="rmBtnBuscarSublinea"><i class="fa fa-search"></i></button>
					</span>
				</div>
				<div class="callout callout-warning" id="rmHintCambiarSublinea" style="display:none; margin:10px 0 0;">
					<p style="margin:0;">
						La sublínea actual aún no tiene materia prima.
						Usa <strong>Cambiar</strong> para reemplazarla en las tarjetas,
						o <strong>Elegir</strong> y luego <strong>Agregar</strong> si quieres sumar otra.
					</p>
				</div>
				<div class="table-responsive" style="max-height:400px; overflow:auto; margin-top:10px;">
					<table class="table table-hover table-condensed" id="rmTablaSublineas">
						<thead>
							<tr><th>Código</th><th>Línea</th><th>Nombre</th><th></th></tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
