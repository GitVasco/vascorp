<?php
if (!isset($_SESSION["tarjetas"]) || (int) $_SESSION["tarjetas"] !== 1) {
	if (function_exists("denegarAccesoModulo")) {
		denegarAccesoModulo();
	} else {
		echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Sin permiso</div></section></div>';
	}
	return;
}
?>
<div class="content-wrapper">
	<section class="content-header">
		<h1>
			Recetas por modelo
			<small>Explosión de materiales</small>
		</h1>
		<ol class="breadcrumb">
			<li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
			<li class="active">Recetas por modelo</li>
		</ol>
	</section>

	<section class="content">
		<div class="box box-primary">
			<div class="box-header with-border">
				<button type="button" class="btn btn-primary" id="btnNuevaRecetaModelo" data-toggle="modal" data-target="#modalNuevaRecetaModelo">
					<i class="fa fa-plus"></i> Nueva receta
				</button>
				<button type="button" class="btn btn-success" id="btnImportarTarjetasReceta" data-toggle="modal" data-target="#modalImportarTarjetasReceta">
					<i class="fa fa-download"></i> Importar desde tarjetas
				</button>
				<button type="button" class="btn btn-warning" id="btnModelosSinReceta" data-toggle="modal" data-target="#modalModelosSinReceta">
					<i class="fa fa-warning"></i> Sin receta
					<span class="badge" id="badgeModelosSinReceta" style="display:none;">0</span>
				</button>
				<div class="box-tools pull-right" style="display:flex; gap:8px; align-items:center;">
					<input type="text" class="form-control input-sm" id="filtroModeloReceta" placeholder="Modelo" style="width:120px;">
					<select class="form-control input-sm" id="filtroEstadoReceta" style="width:140px;">
						<option value="">Todos los estados</option>
						<option value="BORRADOR">Borrador</option>
						<option value="PUBLICADA">Publicada</option>
						<option value="ARCHIVADA">Archivada</option>
					</select>
					<button type="button" class="btn btn-default btn-sm" id="btnFiltrarRecetasModelo">
						<i class="fa fa-filter"></i> Filtrar
					</button>
				</div>
			</div>
			<div class="box-body">
				<div class="table-responsive">
					<table class="table table-bordered table-striped" id="tablaRecetasModelo" width="100%">
						<thead>
							<tr>
								<th>Modelo</th>
								<th>Nombre</th>
								<th>Versión</th>
								<th>Estado</th>
								<th>Art. activos</th>
								<th>Líneas</th>
								<th>Tela principal</th>
								<th>Alerta</th>
								<th>Actualizado</th>
								<th style="width:220px;">Acciones</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</section>
</div>

<div class="modal fade" id="modalNuevaRecetaModelo" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Nueva receta (borrador)</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="nuevoModeloReceta">Código de modelo</label>
					<input type="text" class="form-control" id="nuevoModeloReceta" maxlength="10" placeholder="Ej. 10040" autocomplete="off">
					<p class="help-block">Se creará la siguiente versión en estado BORRADOR, con una línea de tela principal sugerida.</p>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" id="btnCrearRecetaModelo">Crear</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalImportarTarjetasReceta" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header bg-green">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Importar desde tarjetas</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="importModeloReceta">Modelo</label>
					<select class="form-control" id="importModeloReceta">
						<option value="">Cargando…</option>
					</select>
					<p class="help-block" style="margin-bottom:0;">
						Solo aparecen modelos con tarjetas que <strong>aún no tienen receta</strong>.
						Se crea un <strong>BORRADOR</strong> con MP/consumos agrupados por sublínea.
					</p>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success" id="btnEjecutarImportTarjetas">
					<i class="fa fa-download"></i> Importar borrador
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalModelosSinReceta" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header bg-yellow">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Modelos con tarjetas sin receta</h4>
			</div>
			<div class="modal-body">
				<p class="text-muted">Artículos activos que tienen detalle en tarjetas, pero el modelo aún no tiene ninguna versión de receta.</p>
				<div class="table-responsive" style="max-height:420px; overflow:auto;">
					<table class="table table-condensed table-bordered" id="tablaModelosSinReceta" style="margin:0;">
						<thead>
							<tr>
								<th>Modelo</th>
								<th>Nombre</th>
								<th>Arts. con tarjeta</th>
								<th></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalPreviewExplosionReceta" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Previsualizar explosión</h4>
			</div>
			<div class="modal-body">
				<input type="hidden" id="previewIdReceta">
				<input type="hidden" id="previewModeloReceta">
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label>Artículo del modelo</label>
							<select class="form-control selectpicker" id="previewArticulo"
								data-live-search="true" data-size="10" data-width="100%"
								title="Elegir artículo…">
							</select>
						</div>
					</div>
					<div class="col-sm-3">
						<div class="form-group">
							<label>Cantidad</label>
							<input type="number" class="form-control" id="previewCantidad" value="1" min="0" step="any">
						</div>
					</div>
					<div class="col-sm-3">
						<div class="form-group">
							<label>&nbsp;</label>
							<button type="button" class="btn btn-info btn-block" id="btnEjecutarPreviewReceta">
								<i class="fa fa-eye"></i> Calcular
							</button>
						</div>
					</div>
				</div>
				<div id="previewExplosionResultado"></div>
			</div>
		</div>
	</div>
</div>
