<?php

// Requerimos el controlador y el modelo
require_once '../controladores/materiaprima.controlador.php';
require_once '../modelos/materiaprima.modelo.php';

class AjaxMateriaPrima{


	/* 
	* EDITAR NOMBRE DE LA MATERIA PRIMA
	*/

	public $idMateriaPrima;

	public function ajaxEditarMateriaPrima(){

		$valor = $this->idMateriaPrima;

		$respuesta = ControladorMateriaPrima::ctrMostrarMateriaPrima($valor);

		echo json_encode($respuesta);

	}

	/* 
	* EDITAR EL COSTO DE LA MATERIA PRIMA
	*/

	public $materiaPrima;

	public function ajaxEditarMateriaPrimaCostos(){

		$valor = $this->materiaPrima;

		$respuesta = ControladorMateriaPrima::ctrMostrarMateriaPrima($valor);

		echo json_encode($respuesta);

	}

	/* 
	* VISUALIZAR LA CABECERA DE LA MATERIA PRIMA
	*/
	public $articuloMP;
	public function ajaxVisualizarMateriaPrima(){

		$valor = $this->articuloMP;

		$respuesta = ControladorMateriaPrima::ctrMostrarMateriaPrima($valor);

		echo json_encode($respuesta);
	
	}

	/* 
	* VISUALIZAR DETALLE DE LA MATERIA PRIMA
	*/
	public function ajaxVisualizarMateriaPrimaDetalle(){

			$valor = $this->articuloMPDetalle;

			$respuestaDetalle = ControladorMateriaPrima::ctrVisualizarMateriaPrimaDetalle($valor);

			echo json_encode($respuestaDetalle);
	}	

	/* 
	* VISUALIZAR  MATERIA PRIMA DE ARTICULO
	*/
	public $articuloSublimado;
	public function ajaxVisualizarMateriaArticulo(){

		$valor = $this->articuloSublimado;

		$respuestaDetalle = ControladorMateriaPrima::ctrMostrarMateriaArticulo($valor);

		echo json_encode($respuestaDetalle);
	}	

	/* 
	* SELECT SUBLINEA PARA CREAR MATERIA PRIMA
	*/
	public $linea;
	public function ajaxSelectSubLineas(){

		$valor = $this->linea;

		$respuestaDetalle = ControladorMateriaPrima::ctrMostrarSubLineas($valor);

		echo json_encode($respuestaDetalle);
	}	

	/* 
	* EDITAR NOMBRE DE LA MATERIA PRIMA
	*/

	public $CodigoFab;

	public function ajaxValidarCodFab(){

		$valor = $this->CodigoFab;

		$respuesta = ControladorMateriaPrima::ctrMostrarMateriaFabrica($valor);

		echo json_encode($respuesta);

	}

}


/* 
* EDITAR NOMBRE DE MATERIA PRIMA
*/

if(isset($_POST["idMateriaPrima"])){

	$editarMateriaPrima = new AjaxMateriaPrima();
	$editarMateriaPrima -> idMateriaPrima = $_POST["idMateriaPrima"];
	$editarMateriaPrima -> ajaxEditarMateriaPrima();
  
}

/* 
* EDITAR COSTO DE MATERIA PRIMA
*/

if(isset($_POST["materiaPrima"])){

	$editarMateriaPrimaCostos = new AjaxMateriaPrima();
	$editarMateriaPrimaCostos -> materiaPrima = $_POST["materiaPrima"];
	$editarMateriaPrimaCostos -> ajaxEditarMateriaPrimaCostos();
  
}

/* 
 * VISUALIZAR LA CABECERA DE LA MATERIA PRIMA
*/
if(isset($_POST["articuloMP"])){

	$visualizarMateriaPrima = new AjaxMateriaPrima();
	$visualizarMateriaPrima -> articuloMP = $_POST["articuloMP"];
	$visualizarMateriaPrima -> ajaxVisualizarMateriaPrima();
  
}

/* 
 * VISUALIZAR DETALLE DE LA MATERIA PRIMA
*/
if(isset($_POST["articuloMPDetalle"])){

  	$visualizarMateriaPrimaDetalle = new AjaxMateriaPrima();
	$visualizarMateriaPrimaDetalle -> articuloMPDetalle = $_POST["articuloMPDetalle"];
	$visualizarMateriaPrimaDetalle -> ajaxVisualizarMateriaPrimaDetalle();
  
}

/* 
 * MOSTRAR ARTICULO MATERIA PRIMA SUBLIMADO
*/
if(isset($_POST["articuloSublimado"])){

  $visualizarMateriaSublimado = new AjaxMateriaPrima();
  $visualizarMateriaSublimado -> articuloSublimado = $_POST["articuloSublimado"];
  $visualizarMateriaSublimado -> ajaxVisualizarMateriaArticulo();

}

/* 
* SELECT PARA MOSTRAR LAS SUBLINEAS AL CREAR MATERIAPRIMA
*/

if(isset($_POST["linea"])){

	$selectSubLineas = new AjaxMateriaPrima();
	$selectSubLineas -> linea = $_POST["linea"];
	$selectSubLineas -> ajaxSelectSubLineas();
  
}

/* 
* VALIDAR CODIGO DE FABRICA AL CREAR MATERIA PRIMA
*/

if(isset($_POST["CodigoFab"])){

	$validarMateriaPrima = new AjaxMateriaPrima();
	$validarMateriaPrima -> CodigoFab = $_POST["CodigoFab"];
	$validarMateriaPrima -> ajaxValidarCodFab();
  
}