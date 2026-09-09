<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../modelos/recetas-modelo.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["id"])) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin sesión"));
	return;
}

$accion = isset($_POST["accion"]) ? trim((string) $_POST["accion"]) : "";

if ($accion === "sublineas") {
	echo json_encode(array("ok" => true, "data" => ModeloRecetasModelo::mdlFiltrosSeguimientoSublineas()));
	return;
}

if ($accion === "mps") {
	$sublinea = isset($_POST["sublinea"]) ? $_POST["sublinea"] : "";
	echo json_encode(array("ok" => true, "data" => ModeloRecetasModelo::mdlFiltrosSeguimientoMps($sublinea)));
	return;
}

if ($accion === "explosionMp") {
	require_once "../controladores/articulos.controlador.php";
	require_once "../modelos/articulos.modelo.php";
	$sublinea = isset($_POST["sublinea"]) ? $_POST["sublinea"] : "";
	$mp = isset($_POST["mp"]) ? $_POST["mp"] : "";
	echo json_encode(controladorArticulos::ctrExplosionMpOrdCorteSeguimientoReceta($sublinea, $mp));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no válida"));
