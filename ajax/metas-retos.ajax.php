<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/metas-retos.controlador.php";
require_once "../modelos/metas-retos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "detalle") {
	echo json_encode(ControladorMetasRetos::ctrDetalleAjax(
		isset($_POST["cod_vendedor"]) ? $_POST["cod_vendedor"] : "",
		isset($_POST["anio"]) ? $_POST["anio"] : 0,
		isset($_POST["mes"]) ? $_POST["mes"] : 0
	));
	return;
}

if ($accion === "guardar") {
	echo json_encode(ControladorMetasRetos::ctrGuardarAjax($_POST));
	return;
}

if ($accion === "listarModelos") {
	echo json_encode(ControladorMetasRetos::ctrListarModelosAjax(
		isset($_POST["q"]) ? $_POST["q"] : ""
	));
	return;
}

if ($accion === "universoModelos") {
	echo json_encode(ControladorMetasRetos::ctrUniversoModelosAjax(
		isset($_POST["cod_vendedor"]) ? $_POST["cod_vendedor"] : "",
		isset($_POST["anio"]) ? $_POST["anio"] : 0,
		isset($_POST["mes"]) ? $_POST["mes"] : 0
	));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
