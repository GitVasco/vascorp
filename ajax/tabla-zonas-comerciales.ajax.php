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

$puedeEditar = function_exists("usuarioPuedeModulo")
	&& usuarioPuedeModulo("gestion_comercial", "zonas_comerciales", "editar");

$zonas = ControladorZonasComerciales::ctrListarZonas(false);
$data = array();

if (is_array($zonas)) {
	foreach ($zonas as $z) {
		$id = (int) $z["id"];
		$color = isset($z["color"]) && $z["color"] !== "" ? $z["color"] : "#777777";
		$nombreHtml = "<span class='label' style='background-color:" . htmlspecialchars($color, ENT_QUOTES, "UTF-8") . ";'>"
			. htmlspecialchars($z["nombre"], ENT_QUOTES, "UTF-8")
			. "</span>";

		$macro = ControladorZonasComerciales::ctrEtiquetaMacrozona(
			isset($z["macrozona"]) ? $z["macrozona"] : ""
		);

		$estadoHtml = ((int) $z["estado"] === 1)
			? "<span class='label label-success'>Activa</span>"
			: "<span class='label label-danger'>Inactiva</span>";

		$botones = "<div class='btn-group'>"
			. "<button class='btn btn-xs btn-info btnVerUbigeosZona' idZona='" . $id . "' nombreZona='"
			. htmlspecialchars($z["nombre"], ENT_QUOTES, "UTF-8")
			. "' title='Ubigeos'><i class='fa fa-map-marker'></i></button>"
			. "<button class='btn btn-xs btn-success btnVerVendedoresZona' idZona='" . $id . "' nombreZona='"
			. htmlspecialchars($z["nombre"], ENT_QUOTES, "UTF-8")
			. "' title='Vendedores'><i class='fa fa-users'></i></button>";

		if ($puedeEditar) {
			$estadoActual = (int) $z["estado"];
			$nuevoEstado = $estadoActual === 1 ? 0 : 1;
			$iconoEstado = $estadoActual === 1 ? "fa-toggle-on" : "fa-toggle-off";
			$claseEstado = $estadoActual === 1 ? "btn-success" : "btn-default";
			$botones .= "<button class='btn btn-xs btn-warning btnEditarZonaComercial' idZona='" . $id . "' title='Editar'><i class='fa fa-pencil'></i></button>"
				. "<button class='btn btn-xs " . $claseEstado . " btnToggleEstadoZona' idZona='" . $id . "' nuevoEstado='" . $nuevoEstado . "' title='Cambiar estado'><i class='fa " . $iconoEstado . "'></i></button>";
		}

		$botones .= "</div>";

		$nota = "";
		if (isset($z["codigo"]) && $z["codigo"] === "LIM_ECONOMICA") {
			$nota = " <small class='text-muted'>(solo manual / Gamarra)</small>";
		}

		$data[] = array(
			(string) (int) $z["orden"],
			isset($z["codigo"]) ? $z["codigo"] : "",
			$nombreHtml . $nota,
			htmlspecialchars($macro, ENT_QUOTES, "UTF-8"),
			isset($z["total_ubigeos"]) ? (string) (int) $z["total_ubigeos"] : "0",
			isset($z["total_vendedores"]) ? (string) (int) $z["total_vendedores"] : "0",
			$estadoHtml,
			$botones
		);
	}
}

echo json_encode(array("data" => $data));
