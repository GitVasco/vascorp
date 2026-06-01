<?php

require_once '../controladores/movimientos.controlador.php';
require_once '../modelos/movimientos.modelo.php';
require_once '../helpers/jsonpe.api.php';

class AjaxMovimientos{

    /* 
    * 
    */
	public $activarId;
    public $activarTarjeta;
    
	public function ajaxActualizarMovimientos(){
		
		$valor1=$this->año;
		
		$valor2=$this->mes;


		/* var_dump($tabla,$valor1,$valor2); */


		$respuesta=ModeloMovimientos::mdlActualizarMovimientos($valor1,$valor2);

		echo $respuesta;
	}


	public function ajaxActualizarTC(){

		$fecha=$this->fecha;
		$tipoCambioSunat = JsonPeApi::consultarTipoCambio($fecha);

		if($tipoCambioSunat["venta"] == "Fuera de plazo permitido"){
			$respuesta = "no";
		}else{
			$respuesta = ModeloMovimientos::mdlActualizarTipoCambio($tipoCambioSunat["compra"], $tipoCambioSunat["venta"], $fecha);
		}

		echo $respuesta;
	}


}

if(isset($_POST["año"])){
	$actualizar=new AjaxMovimientos();
	$actualizar->año=$_POST["año"];
	$actualizar->mes=$_POST["mes"];
	$actualizar->ajaxActualizarMovimientos();
}

if(isset($_POST["fecha"])){
	$actualizar=new AjaxMovimientos();
	$actualizar->fecha=$_POST["fecha"];
	$actualizar->ajaxActualizarTC();
}