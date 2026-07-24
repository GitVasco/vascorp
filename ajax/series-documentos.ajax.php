<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/series-documentos.controlador.php";
require_once "../modelos/series-documentos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "series_documentos")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "detalle") {
	$id = isset($_POST["id_talonario"]) ? (int) $_POST["id_talonario"] : 0;
	$tipo = isset($_POST["tipo_documento"]) ? $_POST["tipo_documento"] : "";
	$detalle = ControladorSeriesDocumentos::ctrDetalleSerie($id, $tipo);
	if (!$detalle) {
		echo json_encode(array("ok" => false, "mensaje" => "Serie no encontrada"));
		return;
	}
	echo json_encode(array("ok" => true, "data" => $detalle));
	return;
}

if ($accion === "crear") {
	echo json_encode(ControladorSeriesDocumentos::ctrCrearSerieAjax($_POST));
	return;
}

if ($accion === "editar") {
	echo json_encode(ControladorSeriesDocumentos::ctrEditarSerieAjax($_POST));
	return;
}

if ($accion === "matriz") {
	echo json_encode(ControladorSeriesDocumentos::ctrMatrizAjax());
	return;
}

if ($accion === "toggleMarca") {
	echo json_encode(ControladorSeriesDocumentos::ctrToggleMarcaAjax($_POST));
	return;
}

if ($accion === "catalogoMarcas") {
	$lista = ControladorSeriesDocumentos::ctrListarMarcasCatalogo();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
