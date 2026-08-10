<?php

require_once "../controladores/abonos.controlador.php";
require_once "../modelos/abonos.modelo.php";

if (!isset($_SESSION)) {
	session_start();
}

class AjaxAbonos
{

	/*=============================================
	EDITAR ABONO
	=============================================*/

	public $idAbono;

	public function ajaxEditarAbono()
	{

		$valor = $this->idAbono;

		$respuesta = ControladorAbonos::ctrMostrarAbonos("id", $valor);

		echo json_encode($respuesta);
	}

	/*=============================================
	GUARDAR MOTIVO PENDIENTE
	=============================================*/

	public function ajaxGuardarMotivoPendiente()
	{
		header("Content-Type: application/json; charset=utf-8");

		$idAbono = isset($_POST["idAbonoMotivo"]) ? $_POST["idAbonoMotivo"] : 0;
		$motivo = isset($_POST["motivoPendiente"]) ? $_POST["motivoPendiente"] : "";
		$observacion = isset($_POST["observacionPendiente"]) ? $_POST["observacionPendiente"] : "";

		$respuesta = ControladorAbonos::ctrGuardarMotivoPendiente($idAbono, $motivo, $observacion);
		echo json_encode($respuesta);
	}

	/*=============================================
	ESTADÍSTICAS MENSUALES
	=============================================*/

	public function ajaxEstadisticasMensuales()
	{
		header("Content-Type: application/json; charset=utf-8");
		$anio = isset($_POST["anioEstadistica"]) ? $_POST["anioEstadistica"] : null;
		$mes = isset($_POST["mesEstadistica"]) ? $_POST["mesEstadistica"] : null;
		$respuesta = ControladorAbonos::ctrEstadisticasMensuales($anio, $mes);
		echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
	}
}

/*=============================================
EDITAR ABONO
=============================================*/

if (isset($_POST["idAbono"]) && !isset($_POST["guardarMotivoPendiente"])) {

	$abono = new AjaxAbonos();
	$abono->idAbono = $_POST["idAbono"];
	$abono->ajaxEditarAbono();
}

/*=============================================
GUARDAR MOTIVO PENDIENTE
=============================================*/

if (isset($_POST["guardarMotivoPendiente"])) {

	$abono = new AjaxAbonos();
	$abono->ajaxGuardarMotivoPendiente();
}

/*=============================================
ESTADÍSTICAS MENSUALES
=============================================*/

if (isset($_POST["estadisticasMensualesAbonos"])) {

	$abono = new AjaxAbonos();
	$abono->ajaxEstadisticasMensuales();
}
