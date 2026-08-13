<?php

if (!isset($_SESSION)) {
	session_start();
}
date_default_timezone_set("America/Lima");

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/proyeccion-comercial-modelos.controlador.php";
require_once "../modelos/proyeccion-comercial-modelos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
	http_response_code(405);
	echo json_encode(array("ok" => false, "mensaje" => "Método no permitido"));
	return;
}

if (!function_exists("usuarioPuedeVerModulo")
	|| !usuarioPuedeVerModulo("gestion_comercial", "proyeccion_comercial_modelos")
) {
	http_response_code(403);
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

if (session_status() === PHP_SESSION_ACTIVE) {
	session_write_close();
}

$accion = isset($_POST["accion"]) ? trim($_POST["accion"]) : "";
$acciones = array(
	"catalogo" => "ctrCatalogo",
	"matriz" => "ctrMatriz",
	"contextoModelo" => "ctrContextoModelo",
	"conciliar" => "ctrConciliar",
	"listarPlanes" => "ctrListarPlanes",
	"crearPlan" => "ctrCrearPlan",
	"eliminarPlan" => "ctrEliminarPlan",
	"generarLineas" => "ctrGenerarLineas",
	"cargarPlan" => "ctrCargarPlan",
	"guardarLineas" => "ctrGuardarLineas",
	"publicar" => "ctrPublicar",
	"auditoria" => "ctrAuditoria",
	"consultaOficial" => "ctrConsultaOficial",
	"tiposFactor" => "ctrTiposFactor",
	"listarFactores" => "ctrListarFactores",
	"guardarFactor" => "ctrGuardarFactor",
	"eliminarFactor" => "ctrEliminarFactor",
	"aplicarSugMasAjustes" => "ctrAplicarSugMasAjustes",
	"espacioModelo" => "ctrEspacioModelo",
	"buscarModelos" => "ctrBuscarModelos",
	"modelosPendientes" => "ctrModelosPendientes",
	"listarCatalogo" => "ctrListarCatalogo",
	"guardarCatalogo" => "ctrGuardarCatalogo",
	"desactivarCatalogo" => "ctrDesactivarCatalogo",
	"catalogoLinea" => "ctrCatalogoLinea",
	"toggleCatalogoLinea" => "ctrToggleCatalogoLinea",
	"resumenFactoresModelo" => "ctrResumenFactoresModelo"
);

if (!isset($acciones[$accion])) {
	http_response_code(400);
	echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
	return;
}

try {
	$metodo = $acciones[$accion];
	echo json_encode(ControladorProyeccionComercialModelos::$metodo($_POST));
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode(array("ok" => false, "mensaje" => "Error interno al procesar la solicitud"));
}
