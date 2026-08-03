<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/zonas-comerciales.controlador.php";
require_once "../modelos/zonas-comerciales.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales")) {
	echo json_encode(array("data" => array()));
	return;
}

$puedeEditar = ControladorZonasComerciales::ctrPuedeEditarZonaAsignacion();
$zonas = ControladorZonasComerciales::ctrListarZonas(true);
if (!is_array($zonas)) {
	$zonas = array();
}

$zonasUbigeo = array();
foreach ($zonas as $z) {
	$cod = isset($z["codigo"]) ? $z["codigo"] : "";
	if ($cod === "LIM_ECONOMICA" || $cod === "LIM_DISTRIBUIDORES") {
		continue;
	}
	$zonasUbigeo[] = $z;
}

$filas = ControladorZonasComerciales::ctrDistritosZonaPendientes();
$data = array();

if (is_array($filas)) {
	foreach ($filas as $f) {
		$tieneRegla = !empty($f["id_zona"]);
		$situacion = "Libre";
		if ($tieneRegla) {
			$zonaVieja = isset($f["zona_nombre"]) ? $f["zona_nombre"] : "";
			$codViejo = isset($f["zona_codigo"]) ? $f["zona_codigo"] : "";
			$situacion = "Zona inactiva: " . ($zonaVieja !== "" ? $zonaVieja : $codViejo);
		}

		$situacionHtml = $tieneRegla
			? "<span class='label label-danger'>" . htmlspecialchars($situacion, ENT_QUOTES, "UTF-8") . "</span>"
			: "<span class='label label-warning'>" . htmlspecialchars($situacion, ENT_QUOTES, "UTF-8") . "</span>";

		$acciones = "—";
		if ($puedeEditar) {
			$opciones = "<option value=''>Elegir zona…</option>";
			foreach ($zonasUbigeo as $z) {
				$opciones .= "<option value='" . (int) $z["id"] . "'>"
					. htmlspecialchars($z["nombre"], ENT_QUOTES, "UTF-8")
					. "</option>";
			}
			$acciones = "<div class='input-group input-group-sm' style='min-width:220px;'>"
				. "<select class='form-control selectDistritoZona'>" . $opciones . "</select>"
				. "<span class='input-group-btn'>"
				. "<button type='button' class='btn btn-primary btnAsignarDistritoZona' codUbi='"
				. htmlspecialchars($f["cod_ubi"], ENT_QUOTES, "UTF-8")
				. "' title='Asignar'><i class='fa fa-save'></i></button>"
				. "</span></div>";
		}

		$data[] = array(
			htmlspecialchars($f["cod_ubi"], ENT_QUOTES, "UTF-8"),
			htmlspecialchars(isset($f["distrito"]) ? $f["distrito"] : "", ENT_QUOTES, "UTF-8"),
			htmlspecialchars(trim((isset($f["provincia"]) ? $f["provincia"] : "") . " / " . (isset($f["departamento"]) ? $f["departamento"] : "")), ENT_QUOTES, "UTF-8"),
			$situacionHtml,
			(string) (int) (isset($f["clientes_activos"]) ? $f["clientes_activos"] : 0),
			$acciones
		);
	}
}

echo json_encode(array("data" => $data));
