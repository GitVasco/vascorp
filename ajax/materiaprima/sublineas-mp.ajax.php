<?php

if (!isset($_SESSION)) {
	session_start();
}

date_default_timezone_set("America/Lima");

require_once "../../controladores/sublineas-mp.controlador.php";
require_once "../../modelos/sublineas-mp.modelo.php";
require_once "../../modelos/materiaprima.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
	echo json_encode(array("ok" => false, "mensaje" => "Método no permitido"));
	return;
}

if (!isset($_SESSION["materiaprima"]) || (int) $_SESSION["materiaprima"] !== 1) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? trim((string) $_POST["accion"]) : "";

if ($accion === "lineas") {
	echo json_encode(ControladorSublineasMp::ctrLineas());
	return;
}

if ($accion === "listar") {
	echo json_encode(ControladorSublineasMp::ctrListar());
	return;
}

if ($accion === "preview") {
	echo json_encode(ControladorSublineasMp::ctrPreview($_POST));
	return;
}

if ($accion === "crear") {
	echo json_encode(ControladorSublineasMp::ctrCrear($_POST));
	return;
}

if ($accion === "editar") {
	echo json_encode(ControladorSublineasMp::ctrEditar($_POST));
	return;
}

if ($accion === "mps") {
	echo json_encode(ControladorSublineasMp::ctrListarMp($_POST));
	return;
}

if ($accion === "catalogos") {
	echo json_encode(ControladorSublineasMp::ctrCatalogos());
	return;
}

if ($accion === "validarCodFab") {
	echo json_encode(ControladorSublineasMp::ctrValidarCodFab($_POST));
	return;
}

if ($accion === "crearMp") {
	echo json_encode(ControladorSublineasMp::ctrCrearMp($_POST));
	return;
}

if ($accion === "ordenes") {
	echo json_encode(ControladorSublineasMp::ctrOrdenes($_POST));
	return;
}

if ($accion === "detalleMp") {
	echo json_encode(ControladorSublineasMp::ctrDetalleMp($_POST));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
