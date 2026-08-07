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
	// En esta etapa el nivel lo define quien programa; la cola no clasifica sola
	$lista = ControladorProgramacionTallerSemana::ctrListarCandidatos($filtros);
	echo json_encode(array("ok" => true, "data" => $lista));
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

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
