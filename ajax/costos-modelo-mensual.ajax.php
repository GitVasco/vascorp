<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/costos-modelo-mensual.controlador.php";
require_once "../modelos/costos-modelo-mensual.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "costos_modelo")) {
	http_response_code(403);
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? trim($_POST["accion"]) : "";

if ($accion === "listar") {
	echo json_encode(ControladorCostosModeloMensual::ctrListar($_POST));
	return;
}

if ($accion === "listarMarcas") {
	echo json_encode(ControladorCostosModeloMensual::ctrListarMarcas());
	return;
}

if ($accion === "guardarBorrador") {
	echo json_encode(ControladorCostosModeloMensual::ctrGuardarBorrador($_POST));
	return;
}

if ($accion === "cambiarEstado") {
	echo json_encode(ControladorCostosModeloMensual::ctrCambiarEstado($_POST));
	return;
}

if ($accion === "aprobarPeriodo") {
	echo json_encode(ControladorCostosModeloMensual::ctrAprobarPeriodo($_POST));
	return;
}

if ($accion === "costoAprobado") {
	echo json_encode(ControladorCostosModeloMensual::ctrCostoAprobado($_POST));
	return;
}

if ($accion === "calcularRentabilidad") {
	echo json_encode(ControladorCostosModeloMensual::ctrCalcularRentabilidad($_POST));
	return;
}

if ($accion === "importarCsv") {
	echo json_encode(ControladorCostosModeloMensual::ctrImportarCsv($_POST, $_FILES));
	return;
}

if ($accion === "historial") {
	echo json_encode(ControladorCostosModeloMensual::ctrHistorial($_POST));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
