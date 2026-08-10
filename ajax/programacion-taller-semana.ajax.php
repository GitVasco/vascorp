<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/programacion-taller-semana.controlador.php";
require_once "../modelos/programacion-taller-semana.modelo.php";
require_once "../controladores/sectores.controlador.php";
require_once "../modelos/sectores.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!ControladorProgramacionTallerSemana::ctrPuedeProduccion()) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso de producción"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "niveles") {
	echo json_encode(array("ok" => true, "data" => ControladorProgramacionTallerSemana::ctrNiveles()));
	return;
}

if ($accion === "semanaActual") {
	$act = ModeloProgramacionTallerSemana::mdlSemanaActual();
	$info = ControladorProgramacionTallerSemana::ctrInfoSemana($act["anio"], $act["semana"]);
	echo json_encode(array("ok" => true, "data" => $info));
	return;
}

if ($accion === "infoSemana") {
	$anio = isset($_POST["anio"]) ? (int) $_POST["anio"] : 0;
	$semana = isset($_POST["semana"]) ? (int) $_POST["semana"] : 0;
	$info = ControladorProgramacionTallerSemana::ctrInfoSemana($anio, $semana);
	echo json_encode(array("ok" => true, "data" => $info));
	return;
}

if ($accion === "listarSectores") {
	$lista = ControladorSectores::ctrMostrarSectores(null);
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "listarModelos") {
	$lista = ModeloProgramacionTallerSemana::mdlListarModelosCandidatos();
	echo json_encode(array("ok" => true, "data" => $lista ? $lista : array()));
	return;
}

if ($accion === "candidatos") {
	$filtros = array(
		"cod_sector" => isset($_POST["cod_sector"]) ? $_POST["cod_sector"] : "",
		"modelo" => isset($_POST["modelo"]) ? $_POST["modelo"] : ""
	);
	// En esta etapa el nivel lo define quien prioriza; la cola no clasifica sola
	$lista = ControladorProgramacionTallerSemana::ctrListarCandidatos($filtros);
	echo json_encode(array("ok" => true, "data" => $lista));
	return;
}

if ($accion === "priorizados") {
	$filtros = array(
		"cod_sector" => isset($_POST["cod_sector"]) ? $_POST["cod_sector"] : "",
		"nivel" => isset($_POST["nivel"]) ? $_POST["nivel"] : "",
		"modelo" => isset($_POST["modelo"]) ? $_POST["modelo"] : ""
	);
	$lista = ControladorProgramacionTallerSemana::ctrListarPriorizados($filtros);
	echo json_encode(array("ok" => true, "data" => $lista));
	return;
}

if ($accion === "priorizar") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrPriorizarAjax($_POST));
	return;
}

if ($accion === "priorizarLote") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrPriorizarLoteAjax($_POST));
	return;
}

if ($accion === "editarPrioridad") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrEditarPrioridadAjax($_POST));
	return;
}

if ($accion === "eliminarPrioridad") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrEliminarPrioridadAjax($_POST));
	return;
}

if ($accion === "destinarSemana") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrDestinarSemanaAjax($_POST));
	return;
}

if ($accion === "destinarLote") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrDestinarLoteAjax($_POST));
	return;
}

if ($accion === "estadisticas") {
	$anio = isset($_POST["anio"]) ? (int) $_POST["anio"] : 0;
	$semana = isset($_POST["semana"]) ? (int) $_POST["semana"] : 0;
	$stats = ControladorProgramacionTallerSemana::ctrEstadisticasSemana($anio, $semana);
	echo json_encode(array(
		"ok" => true,
		"estadisticas" => $stats,
		"resumen" => !empty($stats["por_taller"]) ? $stats["por_taller"] : array()
	));
	return;
}

if ($accion === "programados") {
	$filtros = array(
		"anio" => isset($_POST["anio"]) ? (int) $_POST["anio"] : 0,
		"semana" => isset($_POST["semana"]) ? (int) $_POST["semana"] : 0,
		"cod_sector" => isset($_POST["cod_sector"]) ? $_POST["cod_sector"] : "",
		"nivel" => isset($_POST["nivel"]) ? $_POST["nivel"] : "",
		"modelo" => isset($_POST["modelo"]) ? $_POST["modelo"] : ""
	);
	$lista = ControladorProgramacionTallerSemana::ctrListarProgramados($filtros);
	$incluirStats = !isset($_POST["incluir_stats"]) || (string) $_POST["incluir_stats"] !== "0";
	$out = array(
		"ok" => true,
		"data" => $lista
	);
	if ($incluirStats) {
		$stats = ControladorProgramacionTallerSemana::ctrEstadisticasSemana($filtros["anio"], $filtros["semana"]);
		$out["resumen"] = !empty($stats["por_taller"]) ? $stats["por_taller"] : array();
		$out["estadisticas"] = $stats;
	}
	echo json_encode($out);
	return;
}

if ($accion === "programar") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrProgramarAjax($_POST));
	return;
}

if ($accion === "programarLote") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrProgramarLoteAjax($_POST));
	return;
}

if ($accion === "editar") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrEditarAjax($_POST));
	return;
}

if ($accion === "eliminar") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrEliminarAjax($_POST));
	return;
}

if ($accion === "noEjecutados") {
	$filtros = array(
		"cod_sector" => isset($_POST["cod_sector"]) ? $_POST["cod_sector"] : "",
		"nivel" => isset($_POST["nivel"]) ? $_POST["nivel"] : "",
		"modelo" => isset($_POST["modelo"]) ? $_POST["modelo"] : ""
	);
	$lista = ControladorProgramacionTallerSemana::ctrListarNoEjecutados($filtros);
	echo json_encode(array(
		"ok" => true,
		"data" => $lista,
		"total" => count($lista)
	));
	return;
}

if ($accion === "contarNoEjecutados") {
	echo json_encode(array(
		"ok" => true,
		"total" => ControladorProgramacionTallerSemana::ctrContarNoEjecutados()
	));
	return;
}

if ($accion === "moverNoEjecutado") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrMoverNoEjecutadoAjax($_POST));
	return;
}

if ($accion === "moverNoEjecutadoLote") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrMoverNoEjecutadoLoteAjax($_POST));
	return;
}

if ($accion === "devolverPrioridad") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrDevolverPrioridadAjax($_POST));
	return;
}

if ($accion === "devolverPrioridadLote") {
	echo json_encode(ControladorProgramacionTallerSemana::ctrDevolverPrioridadLoteAjax($_POST));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
