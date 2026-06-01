<?php
session_start();
require_once "../controladores/orden-compra.controlador.php";
require_once "../modelos/orden-compra.modelo.php";
require_once "../controladores/maestras.controlador.php";
require_once "../modelos/maestras.modelo.php";
require_once "../helpers/jsonpe.api.php";

class AjaxOrdenCompra{

	

	/*=============================================
	CONSULTAR API TIPO DE CAMBIO
	=============================================*/	
	public function ajaxConsultarTipoCambio(){
		$respuesta = JsonPeApi::consultarTipoCambio();
		echo json_encode($respuesta);
	}

	public function ajaxSelectColores(){
		$valor="TCOL";

		$respuesta = ControladorMaestras::ctrMostrarMaestrasDetalle($valor);
		
		echo json_encode($respuesta);
	}

	public function ajaxVisualizarOrdenCompra(){
		$valor=$this->idOrdenCompra;

		$respuesta = ControladorOrdenCompra::ctrMostrarOrdenCompra("Nro",$valor);
		
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

	public function ajaxCerrarDetalleOrdenCompra2(){
		$detalleCod=$this->detalleCerrarCod;
		$detalleNro=$this->detalleCerrarNro;
		$datos = array ("Nro" => $detalleNro,
						 "CodPro" => $detalleCod,
						 "estac"=>"CER");
		
		$respuestaCerrado = ModeloOrdenCompra::mdlCerrarDetalleOrdenCompra2($datos);
		
		echo $respuestaCerrado;
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
CERRAR ORDEN DE COMPRA
=============================================*/	

if(isset($_POST["cerrarId"])){

	$cerrarOrdenCompra = new AjaxOrdenCompra();
	$cerrarOrdenCompra -> cerrarId = $_POST["cerrarId"];
	$cerrarOrdenCompra -> ajaxCerrarOrdenCompra();

}

/*=============================================
VISUALIZAR ORDEN DE COMPRA
=============================================*/	

if(isset($_POST["idOrdenCompra"])){

	$visualizarOrdenCompra = new AjaxOrdenCompra();
	$visualizarOrdenCompra -> idOrdenCompra = $_POST["idOrdenCompra"];
	$visualizarOrdenCompra -> ajaxVisualizarOrdenCompra();

}

/*=============================================
CERRAR DETALLE ORDEN COMPRA
=============================================*/	

if(isset($_POST["detalleCerrarCod"])){

	$cerrarDetalleOrdenCompra = new AjaxOrdenCompra();
	$cerrarDetalleOrdenCompra -> detalleCerrarCod = $_POST["detalleCerrarCod"];
	$cerrarDetalleOrdenCompra -> detalleCerrarNro = $_POST["detalleCerrarNro"];
	$cerrarDetalleOrdenCompra -> ajaxCerrarDetalleOrdenCompra2();

}
