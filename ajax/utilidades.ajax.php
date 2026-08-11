<?php

if (!isset($_SESSION)) {
	session_start();
}

date_default_timezone_set("America/Lima");

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/utilidades.controlador.php";
require_once "../modelos/utilidades.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("utilidades", "utilidades")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "descuadresStock01") {
	echo json_encode(ControladorUtilidades::ctrDescuadresStock01());
	return;
}

if ($accion === "actualizarStock01") {
	echo json_encode(ControladorUtilidades::ctrActualizarStock01($_POST));
	return;
}

if ($accion === "descuadresServicio") {
	echo json_encode(ControladorUtilidades::ctrDescuadresServicio());
	return;
}

if ($accion === "actualizarServicio") {
	echo json_encode(ControladorUtilidades::ctrActualizarServicio($_POST));
	return;
}

if ($accion === "contarCuentaVtaOficina") {
	echo json_encode(ControladorUtilidades::ctrContarCuentaVtaOficina());
	return;
}

if ($accion === "eliminarCuentaVtaOficina") {
	echo json_encode(ControladorUtilidades::ctrEliminarCuentaVtaOficina());
	return;
}

if ($accion === "trackingModelo") {
	echo json_encode(ControladorUtilidades::ctrTrackingModelo($_POST));
	return;
}

if ($accion === "corregirSaldosModelo") {
	echo json_encode(ControladorUtilidades::ctrCorregirSaldosModelo($_POST));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
