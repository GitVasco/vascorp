<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/categorias-modelos.controlador.php";
require_once "../modelos/categorias-modelos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
	echo json_encode(array("ok" => false, "mensaje" => "Método no permitido"));
	return;
}

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "categorias_modelos")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? trim((string) $_POST["accion"]) : "";

if ($accion === "catalogo") {
	echo json_encode(ControladorCategoriasModelos::ctrCatalogo());
	return;
}

if ($accion === "listar") {
	echo json_encode(ControladorCategoriasModelos::ctrListar($_POST));
	return;
}

if ($accion === "asignar") {
	echo json_encode(ControladorCategoriasModelos::ctrAsignar($_POST));
	return;
}

if ($accion === "asignarLote") {
	echo json_encode(ControladorCategoriasModelos::ctrAsignarLote($_POST));
	return;
}

if ($accion === "quitarLote") {
	echo json_encode(ControladorCategoriasModelos::ctrQuitarLote($_POST));
	return;
}

if ($accion === "historial") {
	echo json_encode(ControladorCategoriasModelos::ctrHistorial($_POST));
	return;
}

if ($accion === "historialReciente") {
	echo json_encode(ControladorCategoriasModelos::ctrHistorialReciente($_POST));
	return;
}

if ($accion === "listarAdmin") {
	echo json_encode(ControladorCategoriasModelos::ctrListarAdmin());
	return;
}

if ($accion === "listarModelosSubcategoria") {
	echo json_encode(ControladorCategoriasModelos::ctrListarModelosSubcategoria($_POST));
	return;
}

if ($accion === "guardarCategoria") {
	echo json_encode(ControladorCategoriasModelos::ctrGuardarCategoria($_POST));
	return;
}

if ($accion === "guardarSubcategoria") {
	echo json_encode(ControladorCategoriasModelos::ctrGuardarSubcategoria($_POST));
	return;
}

if ($accion === "eliminarSubcategoria") {
	echo json_encode(ControladorCategoriasModelos::ctrEliminarSubcategoria($_POST));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
