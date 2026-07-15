<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/zonas-comerciales.controlador.php";
require_once "../modelos/zonas-comerciales.modelo.php";

header("Content-Type: application/json; charset=utf-8");

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "resolverCliente") {
	if (!isset($_SESSION["id"])) {
		echo json_encode(array("ok" => false, "mensaje" => "Sin sesión"));
		return;
	}
	$codigo = isset($_POST["codigoCliente"]) ? $_POST["codigoCliente"] : "";
	$res = ControladorZonasComerciales::ctrResolverZonaCliente($codigo);
	if (isset($res["origen"])) {
		$res["origen_etiqueta"] = ControladorZonasComerciales::ctrEtiquetaOrigenZona($res["origen"]);
	}
	echo json_encode($res);
	return;
}

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

if ($accion === "detalle") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$zona = ControladorZonasComerciales::ctrDetalleZona($id);
	if (!$zona) {
		echo json_encode(array("ok" => false, "mensaje" => "Zona no encontrada"));
		return;
	}
	echo json_encode(array("ok" => true, "data" => $zona));
	return;
}

if ($accion === "crear") {
	echo json_encode(ControladorZonasComerciales::ctrCrearZonaAjax($_POST));
	return;
}

if ($accion === "editar") {
	echo json_encode(ControladorZonasComerciales::ctrEditarZonaAjax($_POST));
	return;
}

if ($accion === "cambiarEstado") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$estado = isset($_POST["estado"]) ? (int) $_POST["estado"] : 0;
	echo json_encode(ControladorZonasComerciales::ctrCambiarEstadoAjax($id, $estado));
	return;
}

if ($accion === "listarUbigeos") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$lista = ControladorZonasComerciales::ctrListarUbigeosZona($id);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "buscarUbigeos") {
	$q = isset($_POST["q"]) ? $_POST["q"] : "";
	$lista = ControladorZonasComerciales::ctrBuscarUbigeos($q);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "asignarUbigeo") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$cod = isset($_POST["codUbi"]) ? $_POST["codUbi"] : "";
	echo json_encode(ControladorZonasComerciales::ctrAsignarUbigeoAjax($id, $cod));
	return;
}

if ($accion === "quitarUbigeo") {
	$id = isset($_POST["idRegla"]) ? (int) $_POST["idRegla"] : 0;
	echo json_encode(ControladorZonasComerciales::ctrQuitarUbigeoAjax($id));
	return;
}

if ($accion === "listarActivas") {
	$lista = ControladorZonasComerciales::ctrListarZonas(true);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "resumenMapa") {
	$vista = isset($_POST["vista"]) ? $_POST["vista"] : "";
	$anio = isset($_POST["anio"]) ? $_POST["anio"] : null;
	$mes = isset($_POST["mes"]) ? $_POST["mes"] : null;
	echo json_encode(ControladorZonasComerciales::ctrResumenMapa($vista, $anio, $mes));
	return;
}

if ($accion === "clientesVentaZona") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$anio = isset($_POST["anio"]) ? $_POST["anio"] : null;
	$mes = isset($_POST["mes"]) ? $_POST["mes"] : null;
	echo json_encode(ControladorZonasComerciales::ctrClientesVentaZona($id, $anio, $mes));
	return;
}

if ($accion === "clientesNuevosZona") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$anio = isset($_POST["anio"]) ? $_POST["anio"] : null;
	$mes = isset($_POST["mes"]) ? $_POST["mes"] : null;
	echo json_encode(ControladorZonasComerciales::ctrClientesNuevosZona($id, $anio, $mes));
	return;
}

if ($accion === "listarVendedores") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$lista = ControladorZonasComerciales::ctrListarVendedoresZona($id);
	$disponibles = ControladorZonasComerciales::ctrListarVendedoresDisponibles($id);
	echo json_encode(array(
		"ok" => true,
		"data" => $lista ? $lista : array(),
		"disponibles" => $disponibles ? $disponibles : array()
	));
	return;
}

if ($accion === "asignarVendedor") {
	$id = isset($_POST["idZona"]) ? (int) $_POST["idZona"] : 0;
	$cod = isset($_POST["codVendedor"]) ? $_POST["codVendedor"] : "";
	echo json_encode(ControladorZonasComerciales::ctrAsignarVendedorAjax($id, $cod));
	return;
}

if ($accion === "quitarVendedor") {
	$id = isset($_POST["idRegla"]) ? (int) $_POST["idRegla"] : 0;
	echo json_encode(ControladorZonasComerciales::ctrQuitarVendedorAjax($id));
	return;
}

if ($accion === "asignarZonaCliente") {
	if (!ControladorZonasComerciales::ctrPuedeEditarZonaAsignacion()) {
		echo json_encode(array("ok" => false, "mensaje" => "Sin permiso para editar zona"));
		return;
	}
	$codigo = isset($_POST["codigoCliente"]) ? trim($_POST["codigoCliente"]) : "";
	$idZona = isset($_POST["idZona"]) ? trim($_POST["idZona"]) : "";
	if ($codigo === "") {
		echo json_encode(array("ok" => false, "mensaje" => "Cliente vacío"));
		return;
	}
	$idZonaVal = ($idZona === "" || $idZona === "0") ? null : (int) $idZona;
	$pdo = Conexion::conectar();
	$stmt = $pdo->prepare("UPDATE clientesjf SET id_zona = :id_zona WHERE codigo = :codigo");
	if ($idZonaVal === null) {
		$stmt->bindValue(":id_zona", null, PDO::PARAM_NULL);
	} else {
		$stmt->bindValue(":id_zona", $idZonaVal, PDO::PARAM_INT);
	}
	$stmt->bindValue(":codigo", $codigo, PDO::PARAM_STR);
	$ok = $stmt->execute();
	echo json_encode($ok
		? array("ok" => true, "mensaje" => "Zona actualizada")
		: array("ok" => false, "mensaje" => "No se pudo guardar"));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
