<?php
session_start();
require_once "../controladores/orden-servicio.controlador.php";
require_once "../modelos/orden-servicio.modelo.php";

class AjaxOrdenServicio{

  /*=============================================
  VISUALIZAR ORDEN DE SERVICIO CABECERA
  =============================================*/ 
  public function ajaxVisualizarCabecera(){
    $item="Nro";
	$valor = $this->idOrdenServicio;
    $respuesta = ControladorOrdenServicio::ctrMostrarOrdenServicio($item,$valor);

    echo json_encode($respuesta);

  }

  /*=============================================
  VISUALIZAR ORDEN DE SERVICIO DETALLE
  =============================================*/ 
  public function ajaxVisualizarDetalle(){
    $item="Nro";
	$valor = $this->idOrdenServicioDetalle;
    $respuesta = ControladorOrdenServicio::ctrMostrarDetallesOrdenServicio($item,$valor);

    echo json_encode($respuesta);

  }

  /*=============================================
  CERRAR ORDEN DE SERVICIO 
  =============================================*/ 

  public function ajaxCerrarOrdenServicio(){
		$idCerrar=$this->cerrarId;
		date_default_timezone_set('America/Lima');
		$fecha = new DateTime();
		$PcCer= gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$datos = array("Nro" => $idCerrar,
						"EstOS"=>"CER",
						"UsuMod"=>$_SESSION["nombre"],
						"PcMod"=>$PcCer,
						"FecMod"=>$fecha->format("Y-m-d H:i:s"));

		$tabla= "oservicio";						
		$respuesta = ModeloOrdenServicio::mdlCerrarOrdenServicio($tabla,$datos);

		$tabla2 ="oserviciodet";
		$respuesta2 = ModeloOrdenServicio::mdlCerrarOrdenServicio($tabla2,$datos);
		
		echo $respuesta;
	}

  /*=============================================
  CERRAR UN ITEM DE LA ORDEN DE SERVICIO
  =============================================*/

  public function ajaxCerrarItemOrdenServicio(){
		$nro = $this->cerrarItemNro;
		$item = $this->cerrarItem;
		date_default_timezone_set('America/Lima');
		$fecha = new DateTime();
		$PcCer = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$datos = array("Nro" => $nro,
						"Item" => $item,
						"EstOS" => "CER",
						"UsuMod" => $_SESSION["nombre"],
						"PcMod" => $PcCer,
						"FecMod" => $fecha->format("Y-m-d H:i:s"));

		$respuesta = ModeloOrdenServicio::mdlCerrarItemOrdenServicio($datos);

		if($respuesta != "ok"){
			echo "error";
			return;
		}

		$abiertos = ModeloOrdenServicio::mdlContarItemsAbiertosOrdenServicio($nro);

		if($abiertos === 0){
			ModeloOrdenServicio::mdlCerrarOrdenServicio("oservicio", $datos);
			echo "ok-orden";
			return;
		}

		echo "ok";
	}


}


/*=============================================
VISUALIZAR ORDEN DE SERVICIO CABECERA
=============================================*/	
if(isset($_POST["idOrdenServicio"])){

	$cabecera = new AjaxOrdenServicio();
	$cabecera -> idOrdenServicio = $_POST["idOrdenServicio"];
	$cabecera -> ajaxVisualizarCabecera();
}

/*=============================================
VISUALIZAR ORDEN SERVICIO  DETALLE
=============================================*/	
if(isset($_POST["idOrdenServicioDetalle"])){

	$detalle = new AjaxOrdenServicio();
	$detalle -> idOrdenServicioDetalle = $_POST["idOrdenServicioDetalle"];
	$detalle -> ajaxVisualizarDetalle();
}

/*=============================================
CERRAR ORDEN SERVICIO
=============================================*/	

if(isset($_POST["cerrarId"])){

	$cerrarOrdenServicio = new AjaxOrdenServicio();
	$cerrarOrdenServicio -> cerrarId = $_POST["cerrarId"];
	$cerrarOrdenServicio -> ajaxCerrarOrdenServicio();

}

/*=============================================
CERRAR UN ITEM DE LA ORDEN DE SERVICIO
=============================================*/

if(isset($_POST["cerrarItemNro"])){

	$cerrarItem = new AjaxOrdenServicio();
	$cerrarItem -> cerrarItemNro = $_POST["cerrarItemNro"];
	$cerrarItem -> cerrarItem = $_POST["cerrarItem"];
	$cerrarItem -> ajaxCerrarItemOrdenServicio();

}
