<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/grupos-marcas-comercial.controlador.php";
require_once "../modelos/grupos-marcas-comercial.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "asignacion_grupos_marcas")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "listarGruposActivos") {
	$lista = ControladorGruposMarcasComercial::ctrListarGrupos(true);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "listarVendedores") {
	$lista = ControladorGruposMarcasComercial::ctrListarVendedoresTvend();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "catalogoMarcas") {
	$lista = ControladorGruposMarcasComercial::ctrListarMarcasCatalogo();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "crear") {
	echo json_encode(ControladorGruposMarcasComercial::ctrCrearAsignacionesAjax($_POST));
	return;
}

if ($accion === "cerrar") {
	$id = isset($_POST["idAsignacion"]) ? (int) $_POST["idAsignacion"] : 0;
	$fechaFin = isset($_POST["fecha_fin"]) ? $_POST["fecha_fin"] : "";
	echo json_encode(ControladorGruposMarcasComercial::ctrCerrarAsignacionAjax($id, $fechaFin));
	return;
}

if ($accion === "marcasVigentes") {
	$cod = isset($_POST["cod_vendedor"]) ? $_POST["cod_vendedor"] : "";
	$fecha = isset($_POST["fecha_ref"]) ? $_POST["fecha_ref"] : date("Y-m-d");
	$lista = ControladorGruposMarcasComercial::ctrMarcasVigentesPorVendedor($cod, $fecha);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "verificarCoberturaModelo") {
	$cod = isset($_POST["cod_vendedor"]) ? $_POST["cod_vendedor"] : "";
	$modelo = isset($_POST["modelo"]) ? $_POST["modelo"] : "";
	$fecha = isset($_POST["fecha_ref"]) ? $_POST["fecha_ref"] : date("Y-m-d");
	$res = ControladorGruposMarcasComercial::ctrVerificarCoberturaModelo($cod, $modelo, $fecha);
	echo json_encode(array("ok" => true, "cobertura" => $res));
	return;
}

if ($accion === "verificarCoberturaArticulo") {
	$cod = isset($_POST["cod_vendedor"]) ? $_POST["cod_vendedor"] : "";
	$articulo = isset($_POST["articulo"]) ? $_POST["articulo"] : "";
	$fecha = isset($_POST["fecha_ref"]) ? $_POST["fecha_ref"] : date("Y-m-d");
	$res = ControladorGruposMarcasComercial::ctrVerificarCoberturaArticulo($cod, $articulo, $fecha);
	echo json_encode(array("ok" => true, "cobertura" => $res));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
