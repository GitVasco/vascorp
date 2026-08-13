<?php
if (!function_exists("usuarioPuedeVerModulo")
	|| !usuarioPuedeVerModulo("gestion_comercial", "proyeccion_comercial_modelos")
) {
	denegarAccesoModulo();
	return;
}

$puedeEditar = function_exists("usuarioPuedeModulo")
	&& usuarioPuedeModulo("gestion_comercial", "proyeccion_comercial_modelos", "editar");
?>

<div class="content-wrapper proyeccion-factores-wrap">
	<section class="content-header">
		<h1>Factores de proyección <small>catálogo</small></h1>
		<ol class="breadcrumb">
			<li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
			<li><a href="index.php?ruta=proyeccion-comercial-modelos">Proyección</a></li>
			<li class="active">Factores</li>
		</ol>
	</section>

	<section class="content" style="padding-top:8px;">
		<div class="box box-solid">
			<div class="box-header with-border">
				<h3 class="box-title"><i class="fa fa-tags"></i> Catálogo</h3>
				<div class="box-tools pull-right">
					<a href="index.php?ruta=proyeccion-comercial-modelos" class="btn btn-box-tool">
						<i class="fa fa-line-chart"></i> Proyecciones
					</a>
				</div>
			</div>
			<div class="box-body">
				<p class="help-block">Define campañas, precios, eventos… En la proyección solo marcas el check del mes.</p>
				<div class="table-responsive">
					<table class="table table-hover table-condensed" id="tablaCatalogoFactores">
						<thead>
							<tr>
								<th>Tipo</th>
								<th>Título</th>
								<th>Ajuste</th>
								<th>%</th>
								<th>Notas</th>
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

		<?php if ($puedeEditar) { ?>
		<div class="box box-solid">
			<div class="box-header with-border">
				<h3 class="box-title" id="tituloFormFactor">Nuevo factor</h3>
			</div>
			<div class="box-body">
				<form id="formCatalogoFactor" onsubmit="return false;">
					<input type="hidden" id="catId" value="">
					<div class="row">
						<div class="col-sm-3">
							<label class="text-muted" style="font-size:11px;">Tipo</label>
							<select class="form-control input-sm" id="catTipo" required></select>
						</div>
						<div class="col-sm-4">
							<label class="text-muted" style="font-size:11px;">Título</label>
							<input type="text" class="form-control input-sm" id="catTitulo" maxlength="120" required placeholder="Ej. Campaña Fiestas Patrias">
						</div>
						<div class="col-sm-2">
							<label class="text-muted" style="font-size:11px;">Ajuste uds</label>
							<input type="number" class="form-control input-sm" id="catAjuste" step="1" value="0">
						</div>
						<div class="col-sm-3">
							<label class="text-muted" style="font-size:11px;">% default</label>
							<input type="number" class="form-control input-sm" id="catPct" step="0.01" placeholder="opcional">
						</div>
					</div>
					<div class="row" style="margin-top:8px;">
						<div class="col-sm-9">
							<label class="text-muted" style="font-size:11px;">Notas</label>
							<textarea class="form-control input-sm" id="catDesc" rows="2" maxlength="2000" placeholder="Cuándo usarlo"></textarea>
						</div>
						<div class="col-sm-3" style="padding-top:18px;">
							<button type="button" class="btn btn-success btn-sm btn-block" id="btnGuardarCatalogoFactor">
								<i class="fa fa-save"></i> Guardar
							</button>
							<button type="button" class="btn btn-default btn-sm btn-block" id="btnLimpiarCatalogoFactor">Limpiar</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<?php } ?>

		<input type="hidden" id="factPuedeEditar" value="<?php echo $puedeEditar ? '1' : '0'; ?>">
	</section>
</div>
