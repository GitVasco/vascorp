<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/categorias-clientes.controlador.php";
require_once "../modelos/categorias-clientes.modelo.php";

header("Content-Type: application/json; charset=utf-8");

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "detalle") {
	$id = isset($_POST["idCategoria"]) ? (int) $_POST["idCategoria"] : 0;
	$detalle = ControladorCategoriasClientes::ctrDetalleCategoria($id);

	if (!$detalle) {
		echo json_encode(array("ok" => false, "mensaje" => "Categoría no encontrada"));
		return;
	}

	echo json_encode(array("ok" => true, "data" => $detalle));
	return;
}

if ($accion === "crear") {
	$respuesta = ControladorCategoriasClientes::ctrCrearCategoriaAjax($_POST);
	echo json_encode($respuesta);
	return;
}

if ($accion === "editar") {
	$respuesta = ControladorCategoriasClientes::ctrEditarCategoriaAjax($_POST);
	echo json_encode($respuesta);
	return;
}

if ($accion === "cambiarEstado") {
	$id = isset($_POST["idCategoria"]) ? (int) $_POST["idCategoria"] : 0;
	$estado = isset($_POST["estado"]) ? (int) $_POST["estado"] : 0;
	$respuesta = ControladorCategoriasClientes::ctrCambiarEstadoCategoriaAjax($id, $estado);
	echo json_encode($respuesta);
	return;
}

if ($accion === "listarActivas") {
	$lista = ControladorCategoriasClientes::ctrListarCategoriasActivas();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "efectivaCliente") {
	$codigo = isset($_POST["codigoCliente"]) ? $_POST["codigoCliente"] : "";
	$respuesta = ControladorCategoriasClientes::ctrObtenerCategoriaEfectivaCliente($codigo);
	echo json_encode($respuesta);
	return;
}

if ($accion === "categoriaGrupo") {
	$codigoGrupo = isset($_POST["codigoGrupo"]) ? $_POST["codigoGrupo"] : "";
	$respuesta = ControladorCategoriasClientes::ctrCategoriaVigenteGrupo($codigoGrupo);
	echo json_encode($respuesta);
	return;
}

if ($accion === "asignarCliente") {
	$respuesta = ControladorCategoriasClientes::ctrAsignarCategoriaEntidad(array(
		"tipo_entidad" => "cliente",
		"codigo_entidad" => isset($_POST["codigoCliente"]) ? $_POST["codigoCliente"] : "",
		"id_categoria" => isset($_POST["idCategoria"]) ? $_POST["idCategoria"] : 0,
		"motivo" => isset($_POST["motivo"]) ? $_POST["motivo"] : "",
		"es_excepcion" => isset($_POST["es_excepcion"]) ? $_POST["es_excepcion"] : 0
	));
	echo json_encode($respuesta);
	return;
}

if ($accion === "asignarGrupo") {
	$respuesta = ControladorCategoriasClientes::ctrAsignarCategoriaEntidad(array(
		"tipo_entidad" => "grupo",
		"codigo_entidad" => isset($_POST["codigoGrupo"]) ? $_POST["codigoGrupo"] : "",
		"id_categoria" => isset($_POST["idCategoria"]) ? $_POST["idCategoria"] : 0,
		"motivo" => isset($_POST["motivo"]) ? $_POST["motivo"] : "Asignación desde grupos empresariales",
		"es_excepcion" => isset($_POST["es_excepcion"]) ? $_POST["es_excepcion"] : 0,
		"cumplimiento" => isset($_POST["cumplimiento"]) ? $_POST["cumplimiento"] : "pendiente",
		"vigencia_hasta" => isset($_POST["vigencia_hasta"]) ? $_POST["vigencia_hasta"] : ""
	));
	echo json_encode($respuesta);
	return;
}

if ($accion === "resolverBandeja") {
	$respuesta = ControladorCategoriasClientes::ctrResolverBandejaAjax(array(
		"tipo_entidad" => isset($_POST["tipo_entidad"]) ? $_POST["tipo_entidad"] : "",
		"codigo_entidad" => isset($_POST["codigo_entidad"]) ? $_POST["codigo_entidad"] : "",
		"id_asignacion" => isset($_POST["id_asignacion"]) ? $_POST["id_asignacion"] : 0,
		"id_categoria" => isset($_POST["id_categoria"]) ? $_POST["id_categoria"] : 0,
		"cumplimiento" => isset($_POST["cumplimiento"]) ? $_POST["cumplimiento"] : "pendiente",
		"motivo" => isset($_POST["motivo"]) ? $_POST["motivo"] : "",
		"es_excepcion" => isset($_POST["es_excepcion"]) ? $_POST["es_excepcion"] : 0,
		"vigencia_hasta" => isset($_POST["vigencia_hasta"]) ? $_POST["vigencia_hasta"] : ""
	));
	echo json_encode($respuesta);
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
