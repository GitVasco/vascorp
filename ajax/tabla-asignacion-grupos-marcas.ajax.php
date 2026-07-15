<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/grupos-marcas-comercial.controlador.php";
require_once "../modelos/grupos-marcas-comercial.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "asignacion_grupos_marcas")) {
	echo json_encode(array("data" => array()));
	return;
}

$puedeEditar = function_exists("usuarioPuedeModulo")
	&& usuarioPuedeModulo("gestion_comercial", "asignacion_grupos_marcas", "editar");

$filtros = array(
	"cod_vendedor" => isset($_POST["cod_vendedor"]) ? $_POST["cod_vendedor"] : "",
	"id_grupo" => isset($_POST["id_grupo"]) ? (int) $_POST["id_grupo"] : 0,
	"id_marca" => isset($_POST["id_marca"]) ? (int) $_POST["id_marca"] : 0,
	"fecha_ref" => isset($_POST["fecha_ref"]) ? $_POST["fecha_ref"] : date("Y-m-d"),
	"vigente" => isset($_POST["vigente"]) ? $_POST["vigente"] : ""
);

$lista = ControladorGruposMarcasComercial::ctrListarAsignaciones($filtros);
$data = array();

if (is_array($lista)) {
	foreach ($lista as $a) {
		$id = (int) $a["id"];
		$vendedorHtml = htmlspecialchars($a["cod_vendedor"], ENT_QUOTES, "UTF-8")
			. " — " . htmlspecialchars($a["nombre_vendedor"], ENT_QUOTES, "UTF-8");
		$grupoHtml = htmlspecialchars($a["codigo_grupo"], ENT_QUOTES, "UTF-8")
			. " — " . htmlspecialchars($a["nombre_grupo"], ENT_QUOTES, "UTF-8");
		$marcas = isset($a["marcas_texto"]) && $a["marcas_texto"] !== ""
			? htmlspecialchars($a["marcas_texto"], ENT_QUOTES, "UTF-8")
			: "<span class='text-muted'>—</span>";

		$fin = isset($a["fecha_fin"]) && $a["fecha_fin"] !== null && $a["fecha_fin"] !== ""
			? $a["fecha_fin"] : "—";

		$esVigente = (int) $a["es_vigente"] === 1;
		$vigenciaHtml = $esVigente
			? "<span class='label label-success'>Vigente</span>"
			: "<span class='label label-default'>No vigente</span>";

		if ((int) $a["estado"] !== 1) {
			$vigenciaHtml .= " <span class='label label-danger'>Anulada</span>";
		}

		$obs = isset($a["observacion"]) && $a["observacion"] !== ""
			? htmlspecialchars($a["observacion"], ENT_QUOTES, "UTF-8")
			: "—";

		$usuario = isset($a["usureg"]) ? htmlspecialchars($a["usureg"], ENT_QUOTES, "UTF-8") : "—";

		$botones = "";
		if ($puedeEditar && ($a["fecha_fin"] === null || $a["fecha_fin"] === "")) {
			$botones = "<button class='btn btn-xs btn-danger btnCerrarAsignacionGrupo' idAsignacion='" . $id
				. "' vendedor='" . htmlspecialchars($a["cod_vendedor"] . " — " . $a["nombre_grupo"], ENT_QUOTES, "UTF-8")
				. "' title='Cerrar asignación'><i class='fa fa-calendar-times-o'></i> Cerrar</button>";
		} else {
			$botones = "<span class='text-muted'>—</span>";
		}

		$data[] = array(
			$vendedorHtml,
			$grupoHtml,
			$marcas,
			$a["fecha_inicio"],
			$fin,
			$vigenciaHtml,
			$obs,
			$usuario,
			$botones
		);
	}
}

echo json_encode(array("data" => $data));
