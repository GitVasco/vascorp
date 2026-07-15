<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/metas-retos.controlador.php";
require_once "../controladores/metricas-comerciales.controlador.php";
require_once "../modelos/metricas-comerciales.modelo.php";
require_once "../modelos/metas-retos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "metas_vendedor")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "conciliacionCobertura") {
	$anio = isset($_POST["anio"]) ? (int) $_POST["anio"] : 0;
	$mes = isset($_POST["mes"]) ? (int) $_POST["mes"] : 0;
	$lista = ControladorMetricasComerciales::ctrConciliacionCobertura($anio, $mes);
	echo json_encode(array("ok" => true, "data" => $lista));
	return;
}

if ($accion === "detalleMarca") {
	$cod = isset($_POST["cod_vendedor"]) ? $_POST["cod_vendedor"] : "";
	$anio = isset($_POST["anio"]) ? (int) $_POST["anio"] : 0;
	$mes = isset($_POST["mes"]) ? (int) $_POST["mes"] : 0;
	$detalle = ControladorMetricasComerciales::ctrDetalleMarcaVendedor($cod, $anio, $mes);
	echo json_encode(array("ok" => true, "data" => $detalle));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
