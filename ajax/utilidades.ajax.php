<?php

if (!isset($_SESSION)) {
	session_start();
}

date_default_timezone_set("America/Lima");

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/utilidades.controlador.php";
require_once "../modelos/utilidades.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("utilidades", "utilidades")) {
	echo json_encode(array("ok" => false, "mensaje" => "Sin permiso"));
	return;
}

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

if ($accion === "descuadresStock01") {
	echo json_encode(ControladorUtilidades::ctrDescuadresStock01());
	return;
}

if ($accion === "actualizarStock01") {
	echo json_encode(ControladorUtilidades::ctrActualizarStock01($_POST));
	return;
}

if ($accion === "descuadresServicio") {
	echo json_encode(ControladorUtilidades::ctrDescuadresServicio());
	return;
}

if ($accion === "actualizarServicio") {
	echo json_encode(ControladorUtilidades::ctrActualizarServicio($_POST));
	return;
}

if ($accion === "contarCuentaVtaOficina") {
	echo json_encode(ControladorUtilidades::ctrContarCuentaVtaOficina());
	return;
}

if ($accion === "eliminarCuentaVtaOficina") {
	echo json_encode(ControladorUtilidades::ctrEliminarCuentaVtaOficina());
	return;
}

if ($accion === "trackingModelo") {
	echo json_encode(ControladorUtilidades::ctrTrackingModelo($_POST));
	return;
}

if ($accion === "corregirSaldosModelo") {
	echo json_encode(ControladorUtilidades::ctrCorregirSaldosModelo($_POST));
	return;
}

if ($accion === "cteSinFechaVen") {
	echo json_encode(ControladorUtilidades::ctrCteSinFechaVen());
	return;
}

if ($accion === "completarFechaVenCte") {
	echo json_encode(ControladorUtilidades::ctrCompletarFechaVenCte($_POST));
	return;
}

if ($accion === "cteSinFechaOri") {
	echo json_encode(ControladorUtilidades::ctrCteSinFechaOri());
	return;
}

if ($accion === "completarFechaOriCte") {
	echo json_encode(ControladorUtilidades::ctrCompletarFechaOriCte($_POST));
	return;
}

if ($accion === "cteSinTipCambio") {
	echo json_encode(ControladorUtilidades::ctrCteSinTipCambio());
	return;
}

if ($accion === "completarTipCambioCte") {
	echo json_encode(ControladorUtilidades::ctrCompletarTipCambioCte($_POST));
	return;
}

if ($accion === "ventasSinTipCambio") {
	echo json_encode(ControladorUtilidades::ctrVentasSinTipCambio());
	return;
}

if ($accion === "completarTipCambioVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarTipCambioVentas($_POST));
	return;
}

if ($accion === "ventasSinCuenta") {
	echo json_encode(ControladorUtilidades::ctrVentasSinCuenta($_POST));
	return;
}

if ($accion === "completarCuentaVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarCuentaVentas($_POST));
	return;
}

if ($accion === "ventasCuentaPos") {
	echo json_encode(ControladorUtilidades::ctrVentasCuentaPos($_POST));
	return;
}

if ($accion === "completarCuentaPosVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarCuentaPosVentas($_POST));
	return;
}

if ($accion === "ventasCuentaCulqi") {
	echo json_encode(ControladorUtilidades::ctrVentasCuentaCulqi($_POST));
	return;
}

if ($accion === "completarCuentaCulqiVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarCuentaCulqiVentas($_POST));
	return;
}

if ($accion === "ventasCuentaNcDev") {
	echo json_encode(ControladorUtilidades::ctrVentasCuentaNcDev($_POST));
	return;
}

if ($accion === "completarCuentaNcDevVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarCuentaNcDevVentas($_POST));
	return;
}

if ($accion === "ventasCuentaNcDscto") {
	echo json_encode(ControladorUtilidades::ctrVentasCuentaNcDscto($_POST));
	return;
}

if ($accion === "completarCuentaNcDsctoVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarCuentaNcDsctoVentas($_POST));
	return;
}

if ($accion === "ventasCuentaNdFlete") {
	echo json_encode(ControladorUtilidades::ctrVentasCuentaNdFlete($_POST));
	return;
}

if ($accion === "completarCuentaNdFleteVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarCuentaNdFleteVentas($_POST));
	return;
}

if ($accion === "ventasCuentaNdProtesto") {
	echo json_encode(ControladorUtilidades::ctrVentasCuentaNdProtesto($_POST));
	return;
}

if ($accion === "completarCuentaNdProtestoVentas") {
	echo json_encode(ControladorUtilidades::ctrCompletarCuentaNdProtestoVentas($_POST));
	return;
}

if ($accion === "totalesSinTipCambio") {
	echo json_encode(ControladorUtilidades::ctrTotalesSinTipCambio());
	return;
}

if ($accion === "completarTipCambioTotales") {
	echo json_encode(ControladorUtilidades::ctrCompletarTipCambioTotales($_POST));
	return;
}

if ($accion === "clientesVendedorUltimaVenta") {
	echo json_encode(ControladorUtilidades::ctrClientesVendedorUltimaVenta());
	return;
}

if ($accion === "actualizarVendedorUltimaVenta") {
	echo json_encode(ControladorUtilidades::ctrActualizarVendedorUltimaVenta($_POST));
	return;
}

echo json_encode(array("ok" => false, "mensaje" => "Acción no reconocida"));
