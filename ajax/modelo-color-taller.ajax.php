<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/modelo-color-taller.controlador.php";
require_once "../modelos/modelo-color-taller.modelo.php";
require_once "../controladores/modelos.controlador.php";
require_once "../modelos/modelos.modelo.php";
require_once "../controladores/sectores.controlador.php";
require_once "../modelos/sectores.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!ControladorModeloColorTaller::ctrPuedeProduccion()) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso de producción"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "listarModelos") {
	$lista = ControladorModelos::ctrMostrarModelosActivos();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "listarColores") {
	$lista = ControladorModeloColorTaller::ctrListarColoresCatalogo();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "coloresModelo") {
	$modelo = isset($_POST["modelo"]) ? trim((string) $_POST["modelo"]) : "";
	$conAsignacion = !empty($_POST["con_asignacion"]);
	echo json_encode(ControladorModeloColorTaller::ctrColoresModeloDetalle($modelo, $conAsignacion));
	return;
}

if ($accion === "listarSectores") {
	$lista = ControladorSectores::ctrMostrarSectores(null);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "resumenArticulosPorTaller") {
	echo json_encode(ControladorModeloColorTaller::ctrResumenArticulosPorTaller());
	return;
}

if ($accion === "mostrar") {
	$id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
	$row = ControladorModeloColorTaller::ctrMostrar($id);
	if (!$row) {
		echo json_encode(array("ok" => false, "mensaje" => "No encontrado"));
		return;
	}
	echo json_encode(array("ok" => true, "data" => $row));
	return;
}

if ($accion === "crear") {
	echo json_encode(ControladorModeloColorTaller::ctrCrearAjax($_POST));
	return;
}

if ($accion === "crearMasivo") {
	echo json_encode(ControladorModeloColorTaller::ctrCrearMasivoAjax($_POST));
	return;
}

if ($accion === "editar") {
	echo json_encode(ControladorModeloColorTaller::ctrEditarAjax($_POST));
	return;
}

if ($accion === "eliminar") {
	echo json_encode(ControladorModeloColorTaller::ctrEliminarAjax($_POST));
	return;
}

if ($accion === "importar") {
	echo json_encode(ControladorModeloColorTaller::ctrImportarArchivo($_POST, $_FILES));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
