<?php
session_start();

require_once "../controladores/mantenimiento.controlador.php";
require_once "../modelos/mantenimiento.modelo.php";

class AjaxMantenimiento
{

	//*TRAER EL UTIMO CODIGO 
	public function ajaxTraerUltCod()
	{

		$valor = $this->tipoM;

		$respuesta = ControladorMantenimiento::ctrTraerUltCod($valor);

		echo json_encode($respuesta);
	}

	//*EDITAR EQUIPO
	public function ajaxEditarEquipo()
	{

		$valor = $this->equipo;

		$respuesta = ControladorMantenimiento::ctrMostrarEquipos($valor);

		echo json_encode($respuesta);
	}

	//*EDITAR MANTENIMIENTO
	public function ajaxEditarMantenimiento()
	{

		$valor = $this->idMantenimiento;

		$respuesta = ControladorMantenimiento::ctrMostrarMantenimiento($valor);

		echo json_encode($respuesta);
	}

	//*TRAER UBICACION
	public function ajaxTraerUbicacion()
	{

		$valor = $this->maq;

		$respuesta = ControladorMantenimiento::ctrTraerUbicacion($valor);

		echo json_encode($respuesta);
	}

	//*AGREGAR A DETALLE - REPUESTO
	public function ajaxAgregarRepuesto()
	{

		# traemos la fecha y la pc
		date_default_timezone_set('America/Lima');
		$usureg = $_SESSION["nombre"];
		$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$fecreg = new DateTime();

		$codInterno = $this->codInterno;
		$codpro = $this->codpro;
		$cospro = $this->cospro;



		$respuesta = ModeloMantenimiento::mdlAgregarRepuesto($codInterno, $codpro, "1", $cospro, $usureg, $pcreg, $fecreg->format("Y-m-d H:i:s"));

		echo json_encode($respuesta);
	}

	//*EDITAR MANTENIMIENTO DETALLE
	public function ajaxEditarDetalleMantenimiento()
	{

		$valor = $this->idDetMante;

		$respuesta = ControladorMantenimiento::ctrMostrarMantenimientoDetalleEditar($valor);

		echo json_encode($respuesta);
	}

	//*TRAER CALENDARIO
	public function ajaxTraerCalendario()
	{

		$valor = $this->idCalendario;

		$respuesta = ControladorMantenimiento::ctrTraerCalendario($valor);

		echo json_encode($respuesta);
	}
}

//*TRAER EL UTIMO CODIGO 
if (isset($_POST["tipoM"])) {

	$tipo = new AjaxMantenimiento();
	$tipo->tipoM = $_POST["tipoM"];
	$tipo->ajaxTraerUltCod();
}

//*EDITAR EQUIPO
if (isset($_POST["equipo"])) {

	$editarEquipo = new AjaxMantenimiento();
	$editarEquipo->equipo = $_POST["equipo"];
	$editarEquipo->ajaxEditarEquipo();
}

//*TRAER UBICACION
if (isset($_POST["maq"])) {

	$traerUbicacion = new AjaxMantenimiento();
	$traerUbicacion->maq = $_POST["maq"];
	$traerUbicacion->ajaxTraerUbicacion();
}

//*AGREGAR A DETALLE - REPUESTO
if (isset($_POST["codInterno"])) {

	$traerUbicacion = new AjaxMantenimiento();
	$traerUbicacion->codInterno = $_POST["codInterno"];
	$traerUbicacion->codpro = $_POST["codpro"];
	$traerUbicacion->cospro = $_POST["cospro"];
	$traerUbicacion->ajaxAgregarRepuesto();
}

//*EDITAR MANTENIMIENTO
if (isset($_POST["idMantenimiento"])) {

	$editarMantenimiento = new AjaxMantenimiento();
	$editarMantenimiento->idMantenimiento = $_POST["idMantenimiento"];
	$editarMantenimiento->ajaxEditarMantenimiento();
}

//*EDITAR MANTENIMIENTO DETALLE
if (isset($_POST["idDetMante"])) {

	$editarDetMantenimiento = new AjaxMantenimiento();
	$editarDetMantenimiento->idDetMante = $_POST["idDetMante"];
	$editarDetMantenimiento->ajaxEditarDetalleMantenimiento();
}

//*TRAER CALENDARIO
if (isset($_POST["idCalendario"])) {

	$editarDetMantenimiento = new AjaxMantenimiento();
	$editarDetMantenimiento->idCalendario = $_POST["idCalendario"];
	$editarDetMantenimiento->ajaxTraerCalendario();
}
