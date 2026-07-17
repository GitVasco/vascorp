<?php

if (!isset($_SESSION)) {
	session_start();
}
date_default_timezone_set("America/Lima");

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/ficha-gerencial-modelos.controlador.php";
require_once "../modelos/ficha-gerencial-modelos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
	http_response_code(405);
	echo json_encode(array("ok" => false, "mensaje" => "Método no permitido"));
	return;
}

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos")) {
	http_response_code(403);
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? trim($_POST["accion"]) : "";
$acciones = array(
	"catalogo" => "ctrCatalogo",
	"resumen" => "ctrResumen",
	"resumenComparativo" => "ctrResumenComparativo",
	"variantes" => "ctrVariantes",
	"rankings" => "ctrRankings",
	"evolucion" => "ctrEvolucion",
	"detalle" => "ctrDetalle",
	"conciliacion" => "ctrConciliacion"
);

if (!isset($acciones[$accion])) {
	http_response_code(400);
	echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
	return;
}

$metodo = $acciones[$accion];
echo json_encode(ControladorFichaGerencialModelos::$metodo($_POST));
