<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/zonas-comerciales.controlador.php";
require_once "../modelos/zonas-comerciales.modelo.php";
require_once "../modelos/conexion.php";

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

$filas = ControladorZonasComerciales::ctrClientesZonaPorRevisar(300);
$data = array();

if (is_array($filas)) {
	foreach ($filas as $f) {
		$distrito = isset($f["distrito"]) ? $f["distrito"] : "";
		$esVictoria = (stripos($distrito, "VICTORIA") !== false);

		$motivo = "Sin zona activa resoluble";
		if ($esVictoria && empty($f["id_zona_cliente"])) {
			$motivo = "La Victoria — ¿Gamarra? (Zona Económica)";
		} elseif (!empty($f["zona_cliente_inactiva_codigo"])) {
			$motivo = "Cliente en zona inactiva (" . $f["zona_cliente_inactiva_codigo"] . ")";
		} elseif (!empty($f["zona_grupo_inactiva_codigo"])) {
			$motivo = "Grupo en zona inactiva (" . $f["zona_grupo_inactiva_codigo"] . ")";
		} elseif (!empty($f["zona_ubigeo_inactiva_codigo"])) {
			$motivo = "Distrito en zona inactiva (" . $f["zona_ubigeo_inactiva_codigo"] . ")";
		}

		$zonaAuto = isset($f["zona_ubigeo_nombre"]) && $f["zona_ubigeo_nombre"] !== ""
			? $f["zona_ubigeo_nombre"]
			: "—";
		$zonaGrupo = isset($f["zona_grupo_nombre"]) && $f["zona_grupo_nombre"] !== ""
			? $f["zona_grupo_nombre"]
			: "—";

		$acciones = "";
		if ($puedeEditar) {
			$opciones = "<option value=''>Auto</option>";
			foreach ($zonas as $z) {
				$opciones .= "<option value='" . (int) $z["id"] . "'>"
					. htmlspecialchars($z["nombre"], ENT_QUOTES, "UTF-8")
					. "</option>";
			}
			$acciones = "<div class='input-group input-group-sm' style='min-width:220px;'>"
				. "<select class='form-control selectZonaRevisar'>" . $opciones . "</select>"
				. "<span class='input-group-btn'>"
				. "<button type='button' class='btn btn-primary btnGuardarZonaRevisar' codigoCliente='"
				. htmlspecialchars($f["codigo"], ENT_QUOTES, "UTF-8")
				. "' title='Guardar'><i class='fa fa-save'></i></button>"
				. "</span></div>";
		}

		$data[] = array(
			htmlspecialchars($f["codigo"], ENT_QUOTES, "UTF-8"),
			htmlspecialchars($f["nombre"], ENT_QUOTES, "UTF-8"),
			htmlspecialchars($distrito, ENT_QUOTES, "UTF-8"),
			htmlspecialchars(isset($f["nombre_grupo"]) ? $f["nombre_grupo"] : "", ENT_QUOTES, "UTF-8"),
			htmlspecialchars($zonaGrupo, ENT_QUOTES, "UTF-8"),
			htmlspecialchars($zonaAuto, ENT_QUOTES, "UTF-8"),
			htmlspecialchars($motivo, ENT_QUOTES, "UTF-8"),
			$acciones
		);
	}
}

echo json_encode(array("data" => $data));
