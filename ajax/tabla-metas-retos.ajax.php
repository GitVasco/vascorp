<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/metas-retos.controlador.php";
require_once "../modelos/metas-retos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor")) {
	echo json_encode(array("data" => array()));
	return;
}

$anio = isset($_POST["anio"]) ? (int) $_POST["anio"] : (int) date("Y");
$mes = isset($_POST["mes"]) ? (int) $_POST["mes"] : (int) date("n");
$puedeEditar = ControladorMetasRetos::ctrPuedeEditar();

$filas = ControladorMetasRetos::ctrListarAvance($anio, $mes);
$data = array();

function mrFmt($n, $dec = 0)
{
	if ($n === null || $n === "") {
		return "<span class='text-muted'>—</span>";
	}
	return number_format((float) $n, $dec, ".", ",");
}

function mrBarra($pct)
{
	if ($pct === null) {
		return "<span class='text-muted'>—</span>";
	}
	$cls = $pct >= 100 ? "success" : ($pct >= 70 ? "warning" : "danger");
	$ancho = min(100, max(0, $pct));
	return "<div style='min-width:90px;'><div class='progress' style='margin:0;height:16px;'>"
		. "<div class='progress-bar progress-bar-{$cls}' style='width:{$ancho}%;line-height:16px;font-size:11px;'>"
		. $pct . "%</div></div></div>";
}

if (is_array($filas)) {
	foreach ($filas as $f) {
		$reto = isset($f["reto"]) && is_array($f["reto"]) ? $f["reto"] : array();
		$metaMonto = isset($reto["meta_monto"]) ? $reto["meta_monto"] : null;
		$metaCli = isset($reto["meta_clientes"]) ? $reto["meta_clientes"] : null;
		$metaMod = isset($reto["meta_modelos"]) ? $reto["meta_modelos"] : null;

		$pctMonto = ControladorMetasRetos::ctrPctAvance($f["venta_real"], $metaMonto);
		$pctCli = ControladorMetasRetos::ctrPctAvance($f["clientes_nuevos"], $metaCli);
		$pctMod = ControladorMetasRetos::ctrPctAvance($f["modelos_activos"], $metaMod);

		$acciones = "";
		if ($puedeEditar) {
			$acciones = "<button class='btn btn-xs btn-warning btnEditarMetasRetos' "
				. "codVendedor='" . htmlspecialchars($f["cod_vendedor"], ENT_QUOTES, "UTF-8") . "' "
				. "nombreVendedor='" . htmlspecialchars($f["nombre_vendedor"], ENT_QUOTES, "UTF-8") . "' "
				. "title='Configurar'><i class='fa fa-pencil'></i></button>";
		}

		$data[] = array(
			htmlspecialchars($f["cod_vendedor"] . " — " . $f["nombre_vendedor"], ENT_QUOTES, "UTF-8"),
			mrFmt($metaMonto, 2),
			mrFmt($f["venta_real"], 2),
			mrBarra($pctMonto),
			mrFmt($metaCli, 0),
			(string) (int) $f["clientes_nuevos"],
			mrBarra($pctCli),
			mrFmt($metaMod, 0),
			(string) (int) $f["modelos_activos"],
			mrBarra($pctMod),
			$acciones
		);
	}
}

echo json_encode(array("data" => $data));
