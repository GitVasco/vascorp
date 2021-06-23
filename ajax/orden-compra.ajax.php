<?php
session_start();
require_once "../controladores/orden-compra.controlador.php";
require_once "../modelos/orden-compra.modelo.php";
require_once "../controladores/maestras.controlador.php";
require_once "../modelos/maestras.modelo.php";

class AjaxOrdenCompra{

	

	/*=============================================
	CONSULTAR API TIPO DE CAMBIO
	=============================================*/	
	public function ajaxConsultarTipoCambio(){


		$ws = file_get_contents("https://api.apis.net.pe/v1/tipo-cambio-sunat");


		echo $ws;

	}

	public function ajaxSelectColores(){
		$valor="TCOL";

		$respuesta = ControladorMaestras::ctrMostrarMaestrasDetalle($valor);
		
		echo json_encode($respuesta);
	}

	public function ajaxCerrarOrdenCompra(){
		$idCerrar=$this->cerrarId;
		date_default_timezone_set('America/Lima');
		$fecha = new DateTime();
		$PcCer= gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$datos = array ("Nro" => $idCerrar,
						"estac"=>"CER",
						"UsuCer"=>$_SESSION["nombre"],
						"PcCer"=>$PcCer,
						"FecCer"=>$fecha->format("Y-m-d H:i:s"));
		$respuesta = ModeloOrdenCompra::mdlCerrarOrdenCompra($datos);

		$datos2 = array ("Nro" => $idCerrar,
						 "estac"=>"CER");
		
		$respuesta2 = ModeloOrdenCompra::mdlCerrarDetalleOrdenCompra($datos2);
		
		echo $respuesta;
	}

}

/*=============================================
CONSULTAR RUC PROVEEDOR O CLIENTE
=============================================*/	

if(isset($_POST["ApiCambio"])){

	$consultarTipoCambio = new AjaxOrdenCompra();
	$consultarTipoCambio -> ApiCambio = $_POST["ApiCambio"];
	$consultarTipoCambio -> ajaxConsultarTipoCambio();

}

/*=============================================
CONSULTAR RUC PROVEEDOR O CLIENTE
=============================================*/	

if(isset($_POST["ColorCompra"])){

	$consultarTipoCambio = new AjaxOrdenCompra();
	$consultarTipoCambio -> ajaxSelectColores();

}

/*=============================================
CONSULTAR RUC PROVEEDOR O CLIENTE
=============================================*/	

if(isset($_POST["cerrarId"])){

	$cerrarOrdenCompra = new AjaxOrdenCompra();
	$cerrarOrdenCompra -> cerrarId = $_POST["cerrarId"];
	$cerrarOrdenCompra -> ajaxCerrarOrdenCompra();

}
