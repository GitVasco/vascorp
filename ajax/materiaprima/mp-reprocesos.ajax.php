<?php

if (!isset($_SESSION)) {
	session_start();
}

date_default_timezone_set("America/Lima");

require_once "../../controladores/mp-reprocesos.controlador.php";
require_once "../../modelos/mp-reprocesos.modelo.php";

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

if ($accion === "procesos") {
	echo json_encode(ControladorMpReprocesos::ctrProcesos());
	return;
}

if ($accion === "listar") {
	echo json_encode(ControladorMpReprocesos::ctrListar());
	return;
}

if ($accion === "buscarMp") {
	echo json_encode(ControladorMpReprocesos::ctrBuscarMp($_POST));
	return;
}

if ($accion === "guardar") {
	echo json_encode(ControladorMpReprocesos::ctrGuardar($_POST));
	return;
}

if ($accion === "guardarLote") {
	echo json_encode(ControladorMpReprocesos::ctrGuardarLote($_POST));
	return;
}

if ($accion === "eliminar") {
	echo json_encode(ControladorMpReprocesos::ctrEliminar($_POST));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
