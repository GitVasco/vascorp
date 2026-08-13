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
if ($planInicial <= 0) {
	echo '<script>window.location.href="index.php?ruta=proyeccion-comercial-modelos";</script>';
	echo '<p class="text-muted" style="padding:20px;">Elige una proyección y pulsa <strong>Masiva</strong>.</p>';
	return;
}
?>

<div class="content-wrapper proyeccion-comercial-wrap proyeccion-masiva-wrap">
	<section class="content-header proy-header">
		<h1>Proyección comercial <small>vista masiva</small></h1>
		<ol class="breadcrumb">
			<li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
			<li><a href="index.php?ruta=proyeccion-comercial-modelos">Proyecciones</a></li>
			<li class="active">Vista masiva</li>
		</ol>
	</section>

	<section class="content proy-content">

		<div id="pantallaMasiva">
			<div class="box box-solid proy-toolbar-box">
				<div class="box-body proy-plan-toolbar">
					<a href="index.php?ruta=proyeccion-comercial-modelos" class="btn btn-default btn-sm">
						<i class="fa fa-arrow-left"></i> Proyecciones
					</a>
					<span id="lblPlanMasivaBarra" class="proy-plan-meta"></span>
					<a href="index.php?ruta=proyeccion-comercial-modelos&plan=<?php echo (int) $planInicial; ?>"
						class="btn btn-default btn-sm" id="btnIrPorModelo">
						<i class="fa fa-cube"></i> Por modelo
					</a>
					<a href="index.php?ruta=proyeccion-comercial-factores" class="btn btn-default btn-sm">
						<i class="fa fa-tags"></i> Factores
					</a>
				</div>
			</div>

			<div class="box box-solid">
				<div class="box-header with-border">
					<h3 class="box-title"><i class="fa fa-th"></i> Grilla masiva</h3>
				</div>
				<div class="box-body" id="boxPlanActivo">
					<p class="proy-hint">Edición en lote de todos los modelos del plan. Para trabajar modelo a modelo usa <em>Por modelo</em>.</p>
					<div class="row proy-busca-row">
						<div class="col-sm-2">
							<label class="proy-lbl">Marca</label>
							<select class="form-control input-sm" id="proyMarca">
								<option value="0">Todas</option>
							</select>
						</div>
						<div class="col-sm-3">
							<label class="proy-lbl">Buscar</label>
							<input type="text" class="form-control input-sm" id="proyQ" maxlength="100" placeholder="Modelo / nombre">
						</div>
						<div class="col-sm-2">
							<label class="proy-lbl">Mes</label>
							<select class="form-control input-sm" id="filtroMesPlan">
								<option value="">Todos los meses</option>
							</select>
						</div>
						<div class="col-sm-5 proy-busca-actions" style="padding-top:22px;">
							<?php if ($puedeEditar) { ?>
							<button type="button" class="btn btn-primary btn-sm" id="btnGenerarLineas">
								<i class="fa fa-magic"></i> Generar lote
							</button>
							<button type="button" class="btn btn-default btn-sm" id="btnGuardarLineas">
								<i class="fa fa-save"></i> Guardar
							</button>
							<?php } ?>
							<?php if ($puedePublicar) { ?>
							<button type="button" class="btn btn-warning btn-sm" id="btnPublicarMes">Pub. mes</button>
							<button type="button" class="btn btn-danger btn-sm" id="btnPublicarTodo">Pub. borradores</button>
							<?php } ?>
							<button type="button" class="btn btn-default btn-sm" id="btnRecargarPlan">Recargar</button>
						</div>
					</div>
					<div class="row proy-mini-kpis">
						<div class="col-xs-6 col-sm-3"><div class="proy-mini bg-aqua"><b id="proyKpiLineas">0</b><span>Líneas</span></div></div>
						<div class="col-xs-6 col-sm-3"><div class="proy-mini bg-yellow"><b id="proyKpiBorrador">0</b><span>Borrador</span></div></div>
						<div class="col-xs-6 col-sm-3"><div class="proy-mini bg-green"><b id="proyKpiPublicado">0</b><span>Publicadas</span></div></div>
						<div class="col-xs-6 col-sm-3"><div class="proy-mini bg-red"><b id="proyKpiSinLista9">0</b><span>Sin lista 9</span></div></div>
					</div>
					<div class="table-responsive">
						<table class="table table-hover table-condensed proy-table" id="tablaLineasPlan">
							<thead>
								<tr>
									<th>Mes</th><th>Modelo</th><th>Marca</th><th>Sug.</th><th>Aj.</th>
									<th>Oficial</th><th>L9</th><th>S/</th><th>Stock</th>
									<th>Proc.</th><th>Brecha</th><th>Est.</th><th></th>
								</tr>
							</thead>
							<tbody>
								<tr><td colspan="13" class="text-muted text-center">Cargando…</td></tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" id="proyPuedeEditar" value="<?php echo $puedeEditar ? '1' : '0'; ?>">
		<input type="hidden" id="proyPuedePublicar" value="<?php echo $puedePublicar ? '1' : '0'; ?>">
		<input type="hidden" id="proyIdPeriodo" value="<?php echo (int) $planInicial; ?>">
		<input type="hidden" id="proyPlanInicial" value="<?php echo (int) $planInicial; ?>">
	</section>
</div>
