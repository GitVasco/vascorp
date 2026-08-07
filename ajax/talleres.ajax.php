<?php
session_start();
require_once "../controladores/talleres.controlador.php";
require_once "../modelos/talleres.modelo.php";
require_once "../modelos/ingresos.modelo.php";
require_once "../controladores/operaciones.controlador.php";
require_once "../modelos/operaciones.modelo.php";
require_once "../controladores/usuarios.controlador.php";
require_once "../modelos/usuarios.modelo.php";
require_once "../controladores/trabajador.controlador.php";
require_once "../modelos/trabajador.modelo.php";
require_once "../modelos/cortes.modelo.php";
class AjaxTalleres{
/*=============================================
  EDITAR CANTIDAD DE TALLER
  =============================================*/ 

  public $idTaller;

  public function ajaxEditarCantidad(){

    $valor = $this->idTaller;

    $respuesta = ControladorTalleres::ctrMostrarTalleresG($valor);

    echo json_encode($respuesta);

    }
    public $idTallerT;
    public function ajaxVerTallerT(){

      $valor = $this->idTallerT;
  
      $respuesta = ModeloTalleres::mdlVerTalleresTerminado($valor);
  
      echo json_encode($respuesta);
  
      }

    public $fecha;
    public function ajaxSelectTaller(){

      $valor = $this->fecha;
  
      $respuesta = ModeloTalleres::mdlMostrarSelectTaller($valor);
  
      echo json_encode($respuesta);
  
    }

    public $ingreso;
	  public function ajaxUltimoServicio(){
        $ingreso=$this->ingreso;
		    $respuesta=ModeloIngresos::mdlUltimoIngreso("movimientos_cabecerajf");
        echo json_encode($respuesta);
        
    }
    public $modelo;
	  public function ajaxSelectOperacionModelo(){
        $modelo=$this->modelo;
		    $respuesta=ControladorOperaciones::ctrVisualizarOperacionDetalle("modelo",$modelo);
        echo json_encode($respuesta);
        
    }

    /* 
	* Reiniciar TallerT
	*/
	public $activarId;
	public $activarEstado;

	public function ajaxReiniciarTallerT(){

		$valor1=$this->activarEstado;

		$valor2=$this->activarId;
		$usuario= $_SESSION["nombre"];
    date_default_timezone_set('America/Lima');
    $fecha = new DateTime();
    $para      = 'notificacionesvascorp@gmail.com';
    if($valor1 == '4'){
      $asunto    = 'Se cerro un taller';
      $descripcion   = 'El usuario '.$usuario.' cerro el taller con el codigo '.$valor2;
    }else{
      $asunto    = 'Se reinicio un taller';
      $descripcion   = 'El usuario '.$usuario.' reinicio el taller con el codigo '.$valor2;
    }
    
    $de = 'From: notificacionesvascorp@gmail.com';
    if($_SESSION["correo"] == 1){
      mail($para, $asunto, $descripcion, $de);
    }
    if($_SESSION["datos"] == 1){
      $datos2= array( "usuario" => $usuario,
              "concepto" => $descripcion,
              "fecha" => $fecha->format("Y-m-d H:i:s"));
      $auditoria=ModeloUsuarios::mdlIngresarAuditoria("auditoriajf",$datos2);
    }
		
		$respuesta=ControladorTalleres::ctrActualizarTallerT($valor1, $valor2);

		echo $respuesta;
	}

  public function ajaxActualizarAyer(){

    $respuesta = ModeloTalleres::mdlActualizarAyer();

    echo $respuesta;

  }

  public $sectorFeriado;

  public function ajaxTrabajadoresFeriado(){

    $sector = trim((string) $this->sectorFeriado);
    if ($sector === "") {
      echo json_encode(array());
      return;
    }

    $respuesta = ModeloTrabajador::mdlTrabajadoresActivosPorSector($sector);
    echo json_encode($respuesta ? $respuesta : array());
  }

  public $trabajadoresFeriado;
  public $fechaFeriado;

  public function ajaxCrearCompensacionFeriado(){

    if (!isset($_SESSION["id"])) {
      echo json_encode(array("ok" => false, "mensaje" => "Sesión no válida"));
      return;
    }

    $trabajadores = $this->trabajadoresFeriado;
    if (!is_array($trabajadores)) {
      $trabajadores = array();
    }

    $respuesta = ControladorTalleres::ctrCrearCompensacionFeriado(
      $trabajadores,
      $this->fechaFeriado,
      $_SESSION["id"]
    );

    echo json_encode($respuesta);
  }

}
/*=============================================
EDITAR CANTIDAD DE TALLER
=============================================*/	
if(isset($_POST["idTaller"])){

	$taller = new AjaxTalleres();
	$taller -> idTaller = $_POST["idTaller"];
	$taller -> ajaxEditarCantidad();
}

/*=============================================
VER TALLER T
=============================================*/	
if(isset($_POST["idTallerT"])){

	$verTallerT = new AjaxTalleres();
	$verTallerT -> idTallerT = $_POST["idTallerT"];
	$verTallerT -> ajaxVerTallerT();
}

/*=============================================
SELECT TALLER
=============================================*/	
if(isset($_POST["fecha"])){

	$selectTaller = new AjaxTalleres();
	$selectTaller -> fecha = $_POST["fecha"];
	$selectTaller -> ajaxSelectTaller();
}

/*=============================================
SELECT ingreso
=============================================*/	
if(isset($_POST["ingreso"])){

	$ultimoServicio = new AjaxTalleres();
	$ultimoServicio -> ingreso =$_POST["ingreso"];
  $ultimoServicio -> ajaxUltimoServicio();
    
}

/*=============================================
SELECT operacion modelo
=============================================*/	
if(isset($_POST["modelo"])){
  
	$selectModelo = new AjaxTalleres();
	$selectModelo -> modelo =$_POST["modelo"];
  $selectModelo -> ajaxSelectOperacionModelo();
    
}
/*=============================================
REINICIAR TALLERT
=============================================*/
if(isset($_POST["activarId"])){
	$activar=new AjaxTalleres();
	$activar->activarId=$_POST["activarId"];
	$activar->activarEstado=$_POST["activarEstado"];
	$activar->ajaxReiniciarTallerT();
}

/*=============================================
ACTUALIZAR FECHA AYER
=============================================*/	
if(isset($_POST["actualizarFecha"])){
  
	$actualizarAyer = new AjaxTalleres();
  $actualizarAyer -> ajaxActualizarAyer();
    
}

/*=============================================
TRABAJADORES ACTIVOS POR SECTOR (FERIADO)
=============================================*/
if (isset($_POST["sectorFeriado"])) {

	$listar = new AjaxTalleres();
	$listar->sectorFeriado = $_POST["sectorFeriado"];
	$listar->ajaxTrabajadoresFeriado();

}

/*=============================================
CREAR COMPENSACIÓN FERIADO GLOBAL
=============================================*/
if (isset($_POST["crearFeriado"]) && isset($_POST["trabajadoresFeriado"])) {

	$crear = new AjaxTalleres();
	$crear->trabajadoresFeriado = $_POST["trabajadoresFeriado"];
	$crear->fechaFeriado = isset($_POST["fechaFeriado"]) ? $_POST["fechaFeriado"] : "";
	$crear->ajaxCrearCompensacionFeriado();

}