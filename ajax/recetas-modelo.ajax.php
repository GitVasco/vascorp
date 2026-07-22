<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/recetas-modelo.controlador.php";
require_once "../modelos/recetas-modelo.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["id"])) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin sesión"));
	return;
}

// Mismos permisos que tarjetas (valor 8 / sesión tarjetas). Hasta cablear menú, si aún no existe la clave no bloquea.
if (isset($_SESSION["tarjetas"]) && (int) $_SESSION["tarjetas"] !== 1) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso de tarjetas"));
	return;
}

$accion = isset($_POST["accion"]) ? trim((string) $_POST["accion"]) : "";

if ($accion === "listar") {
	$modelo = isset($_POST["modelo"]) ? $_POST["modelo"] : "";
	$estado = isset($_POST["estado"]) ? $_POST["estado"] : "";
	echo json_encode(ControladorRecetasModelo::ctrListar($modelo, $estado));
	return;
}

if ($accion === "detalle") {
	$id = isset($_POST["id_receta"]) ? (int) $_POST["id_receta"] : 0;
	echo json_encode(ControladorRecetasModelo::ctrDetalle($id));
	return;
}

if ($accion === "articulosModelo") {
	$modelo = isset($_POST["modelo"]) ? $_POST["modelo"] : "";
	echo json_encode(ControladorRecetasModelo::ctrArticulosModelo($modelo));
	return;
}

if ($accion === "buscarMp") {
	$q = isset($_POST["q"]) ? $_POST["q"] : "";
	$sublinea = isset($_POST["codigo_sublinea"]) ? $_POST["codigo_sublinea"] : "";
	$limit = isset($_POST["limit"]) ? (int) $_POST["limit"] : 30;
	echo json_encode(ControladorRecetasModelo::ctrBuscarMp($q, $sublinea, $limit));
	return;
}

if ($accion === "infoMps") {
	$codigos = isset($_POST["codigos"]) ? $_POST["codigos"] : array();
	echo json_encode(ControladorRecetasModelo::ctrInfoMps($codigos));
	return;
}

if ($accion === "listarSublineas") {
	$q = isset($_POST["q"]) ? $_POST["q"] : "";
	$limit = isset($_POST["limit"]) ? (int) $_POST["limit"] : 200;
	echo json_encode(ControladorRecetasModelo::ctrListarSublineas($q, $limit));
	return;
}

if ($accion === "crearBorrador") {
	echo json_encode(ControladorRecetasModelo::ctrCrearBorrador($_POST));
	return;
}

if ($accion === "guardarLineas") {
	$id = isset($_POST["id_receta"]) ? (int) $_POST["id_receta"] : 0;
	$lineas = isset($_POST["lineas"]) ? $_POST["lineas"] : array();
	echo json_encode(ControladorRecetasModelo::ctrGuardarLineas($id, $lineas));
	return;
}

if ($accion === "validarCobertura") {
	$id = isset($_POST["id_receta"]) ? (int) $_POST["id_receta"] : 0;
	$bloquear = !isset($_POST["bloquear_complementarios"]) || (int) $_POST["bloquear_complementarios"] === 1;
	echo json_encode(ControladorRecetasModelo::ctrValidarCobertura($id, $bloquear));
	return;
}

if ($accion === "previsualizarExplosion") {
	echo json_encode(ControladorRecetasModelo::ctrPrevisualizarExplosion($_POST));
	return;
}

if ($accion === "publicar") {
	$id = isset($_POST["id_receta"]) ? (int) $_POST["id_receta"] : 0;
	$bloquear = !isset($_POST["bloquear_complementarios"]) || (int) $_POST["bloquear_complementarios"] === 1;
	echo json_encode(ControladorRecetasModelo::ctrPublicar($id, $bloquear));
	return;
}

if ($accion === "duplicarVersion") {
	$id = isset($_POST["id_receta"]) ? (int) $_POST["id_receta"] : 0;
	echo json_encode(ControladorRecetasModelo::ctrDuplicarVersion($id));
	return;
}

if ($accion === "eliminarBorrador") {
	$id = isset($_POST["id_receta"]) ? (int) $_POST["id_receta"] : 0;
	echo json_encode(ControladorRecetasModelo::ctrEliminarBorrador($id));
	return;
}

if ($accion === "importarDesdeTarjetas") {
	$modelo = isset($_POST["modelo"]) ? $_POST["modelo"] : "";
	echo json_encode(ControladorRecetasModelo::ctrImportarDesdeTarjetas($modelo));
	return;
}

if ($accion === "listarModelosImportTarjetas") {
	echo json_encode(ControladorRecetasModelo::ctrListarModelosImportTarjetas());
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
