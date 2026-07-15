<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/grupos-marcas-comercial.controlador.php";
require_once "../modelos/grupos-marcas-comercial.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "grupos_marcas")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "detalle") {
	$id = isset($_POST["idGrupo"]) ? (int) $_POST["idGrupo"] : 0;
	$grupo = ControladorGruposMarcasComercial::ctrDetalleGrupo($id);
	if (!$grupo) {
		echo json_encode(array("ok" => false, "mensaje" => "Grupo no encontrado"));
		return;
	}
	echo json_encode(array("ok" => true, "data" => $grupo));
	return;
}

if ($accion === "crear") {
	echo json_encode(ControladorGruposMarcasComercial::ctrCrearGrupoAjax($_POST));
	return;
}

if ($accion === "editar") {
	echo json_encode(ControladorGruposMarcasComercial::ctrEditarGrupoAjax($_POST));
	return;
}

if ($accion === "cambiarEstado") {
	$id = isset($_POST["idGrupo"]) ? (int) $_POST["idGrupo"] : 0;
	$estado = isset($_POST["estado"]) ? (int) $_POST["estado"] : 0;
	echo json_encode(ControladorGruposMarcasComercial::ctrCambiarEstadoGrupoAjax($id, $estado));
	return;
}

if ($accion === "listarMarcas") {
	$id = isset($_POST["idGrupo"]) ? (int) $_POST["idGrupo"] : 0;
	$lista = ControladorGruposMarcasComercial::ctrListarMarcasGrupo($id);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "catalogoMarcas") {
	$lista = ControladorGruposMarcasComercial::ctrListarMarcasCatalogo();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "agregarMarca") {
	$idGrupo = isset($_POST["idGrupo"]) ? (int) $_POST["idGrupo"] : 0;
	$idMarca = isset($_POST["idMarca"]) ? (int) $_POST["idMarca"] : 0;
	echo json_encode(ControladorGruposMarcasComercial::ctrAgregarMarcaGrupoAjax($idGrupo, $idMarca));
	return;
}

if ($accion === "quitarMarca") {
	$idDetalle = isset($_POST["idDetalle"]) ? (int) $_POST["idDetalle"] : 0;
	echo json_encode(ControladorGruposMarcasComercial::ctrQuitarMarcaGrupoAjax($idDetalle));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
