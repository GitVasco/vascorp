<?php
$letras = ControladorCuentas::ctrLetrasPlazoProtesto();
$totalLetras = count($letras);
$enPlazo = 0;
$urgentes = 0;
$ultimoDia = 0;

foreach ($letras as $letra) {
	$plazo = ModeloCuentas::calcularPlazoProtesto($letra["fecha_ven"]);

	switch ($plazo["estado"]) {
		case 'ULTIMO DIA':
			$ultimoDia++;
			break;
		case 'URGENTE':
			$urgentes++;
			break;
		default:
			$enPlazo++;
			break;
	}
}
?>

<div class="content-wrapper">

	<section class="content-header">

		<h1>
			Letras a informar hoy
			<small>Plazo de pago vigente (hasta el 8.º día hábil)</small>
		</h1>

		<ol class="breadcrumb">

			<li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>

			<li class="active">Letras a informar hoy</li>

		</ol>

	</section>

	<section class="content">

		<div class="box box-info">
			<div class="box-header with-border">
				<h3 class="box-title"><i class="fa fa-info-circle"></i> Información</h3>
			</div>
			<div class="box-body">
				<p>
					Listado para que los vendedores avisen a sus clientes. Solo incluye letras que
					<strong>hoy pueden pagarse</strong>: desde su vencimiento hasta el
					<strong>8.º día hábil</strong> (sin contar sábados ni domingos).
					No se muestran letras con nro. único <strong>CARTERA</strong>, ni las que aún no vencen o ya pasaron el plazo.
				</p>
				<div class="row">
					<div class="col-md-3 col-sm-6 col-xs-12">
						<div class="info-box">
							<span class="info-box-icon bg-aqua"><i class="fa fa-file-text-o"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Total a informar</span>
								<span class="info-box-number"><?= $totalLetras ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-xs-12">
						<div class="info-box">
							<span class="info-box-icon bg-green"><i class="fa fa-clock-o"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">En plazo</span>
								<span class="info-box-number"><?= $enPlazo ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-xs-12">
						<div class="info-box">
							<span class="info-box-icon bg-yellow"><i class="fa fa-exclamation-triangle"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Urgentes</span>
								<span class="info-box-number"><?= $urgentes ?></span>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 col-xs-12">
						<div class="info-box">
							<span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Último día (8.º hábil)</span>
								<span class="info-box-number"><?= $ultimoDia ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="box">
			<div class="box-header with-border">
				<button type="button" class="btn btn-outline-success btnReporteLetrasUrgentes" style="border:green 1px solid">
					<img src="vistas/img/plantilla/excel.png" width="20px"> Exportar urgentes y último día
				</button>
			</div>
			<div class="box-body">
				<table class="table table-bordered table-striped dt-responsive tablaLetrasPlazoProtesto" width="100%">

					<thead>

						<tr>
							<th>Nro. Letra</th>
							<th>Cliente</th>
							<th>Teléfono</th>
							<th>Vend.</th>
							<th>Fec. Emisión</th>
							<th>Fec. Vencimiento</th>
							<th>Fec. Límite Protesto</th>
							<th>Días háb. trans.</th>
							<th>Días háb. rest.</th>
							<th>Saldo</th>
							<th>Nro. Único</th>
							<th>Estado</th>
						</tr>

					</thead>

					<tbody>

					</tbody>

				</table>
			</div>
		</div>

	</section>

</div>

<script>
	window.document.title = "Letras a informar hoy"
</script>
