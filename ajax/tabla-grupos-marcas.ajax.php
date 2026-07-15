<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/grupos-marcas-comercial.controlador.php";
require_once "../modelos/grupos-marcas-comercial.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "grupos_marcas")) {
	echo json_encode(array("data" => array()));
	return;
}

$puedeEditar = function_exists("usuarioPuedeModulo")
	&& usuarioPuedeModulo("gestion_comercial", "grupos_marcas", "editar");

$grupos = ControladorGruposMarcasComercial::ctrListarGrupos(false);
$data = array();

if (is_array($grupos)) {
	foreach ($grupos as $g) {
		$id = (int) $g["id"];
		$estadoHtml = ((int) $g["estado"] === 1)
			? "<span class='label label-success'>Activo</span>"
			: "<span class='label label-danger'>Inactivo</span>";

		$marcas = isset($g["marcas_texto"]) && $g["marcas_texto"] !== ""
			? htmlspecialchars($g["marcas_texto"], ENT_QUOTES, "UTF-8")
			: "<span class='text-muted'>Sin marcas</span>";

		$botones = "<div class='btn-group'>"
			. "<button class='btn btn-xs btn-info btnVerMarcasGrupo' idGrupo='" . $id . "' nombreGrupo='"
			. htmlspecialchars($g["nombre"], ENT_QUOTES, "UTF-8")
			. "' codigoGrupo='" . htmlspecialchars($g["codigo"], ENT_QUOTES, "UTF-8")
			. "' title='Marcas'><i class='fa fa-tags'></i></button>";

		if ($puedeEditar) {
			$estadoActual = (int) $g["estado"];
			$nuevoEstado = $estadoActual === 1 ? 0 : 1;
			$iconoEstado = $estadoActual === 1 ? "fa-toggle-on" : "fa-toggle-off";
			$claseEstado = $estadoActual === 1 ? "btn-success" : "btn-default";
			$botones .= "<button class='btn btn-xs btn-warning btnEditarGrupoMarcas' idGrupo='" . $id . "' title='Editar'><i class='fa fa-pencil'></i></button>"
				. "<button class='btn btn-xs " . $claseEstado . " btnToggleEstadoGrupoMarcas' idGrupo='" . $id . "' nuevoEstado='" . $nuevoEstado . "' title='Cambiar estado'><i class='fa " . $iconoEstado . "'></i></button>";
		}

		$botones .= "</div>";

		$data[] = array(
			isset($g["codigo"]) ? $g["codigo"] : "",
			htmlspecialchars($g["nombre"], ENT_QUOTES, "UTF-8"),
			isset($g["descripcion"]) && $g["descripcion"] !== ""
				? htmlspecialchars($g["descripcion"], ENT_QUOTES, "UTF-8")
				: "—",
			$marcas,
			isset($g["total_marcas"]) ? (string) (int) $g["total_marcas"] : "0",
			isset($g["total_modelos"]) ? (string) (int) $g["total_modelos"] : "0",
			$estadoHtml,
			$botones
		);
	}
}

echo json_encode(array("data" => $data));
