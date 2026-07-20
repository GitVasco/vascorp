<?php
session_start();
// Requerimos el controlador y el modelo
require_once '../controladores/materiaprima.controlador.php';
require_once '../modelos/materiaprima.modelo.php';
require_once '../modelos/maestras.modelo.php';

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
	* ÓRDENES DE COMPRA / SERVICIO POR MATERIA PRIMA
	*/
	public $ordenesMp;
	public function ajaxOrdenesMp(){

		$valor = $this->ordenesMp;

		$respuesta = ControladorMateriaPrima::ctrOrdenesMp($valor);

		echo json_encode($respuesta);
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

	/* 
	* EDITAR NOMBRE DE LA MATERIA PRIMA
	*/

	public $idMateriaPrima2;

	public function ajaxAgregarMateriaPrima2(){

		$valor = $this->idMateriaPrima2;

		$respuesta = ControladorMateriaPrima::ctrMostrarMateriaPrima2($valor);

		echo json_encode($respuesta);

	}

	/* 
	* AGREGAR LA MATERIA PRIMA PARA ORDEN DE COMRPA
	*/

	public function ajaxAgregarMateriaCompra(){

		$valor1 = $this->idMateriaCompra;
		$valor2 = $this->CodRuc;

		$respuesta = ControladorMateriaPrima::ctrMostrarMateriaOrdenCompra($valor1,$valor2);

		echo json_encode($respuesta);

	}

	/* 
	* EDITAR NOMBRE DE LA MATERIA PRIMA
	*/
	public function ajaxAgregarCuadro(){

		$cuadro = $this->cuadro;
		$codpro = $this->codpro;

		$respuesta = ModeloMateriaPrima::mdlAgregarCuadro($cuadro, $codpro);

		echo json_encode($respuesta);

	}	

	/* 
	* EDITAR NOMBRE DE LA MATERIA PRIMA
	*/
	public function ajaxQuitarCuadro(){

		$codpro = $this->codproQ;

		$respuesta = ModeloMateriaPrima::mdlQuitarCuadro($codpro);

		echo json_encode($respuesta);

	}		


	/*=============================================
	EDITAR DOCUMENTO DE VENTA
	=============================================*/	
	public $datosMateria;
	public function ajaxCambiosMateria(){
        $valor = $this->datosMateria;
        $datos = json_decode($valor);
        foreach ($datos->{"datosMateria"} as  $value) {
          $codpro = $value->{"codpro"};
          $codalt = $value->{"codalt"};
          $despro = $value->{"despro"};
          $undpro = $value->{"undpro"};
          $padval = $value->{"padval"};
          $pseg = $value->{"pseg"};
          $pespro = $value->{"pespro"};
          $stkmin = $value->{"stkmin"};
          $stkmax = $value->{"stkmax"};
          $codprov1 = $value->{"codprov1"};
          $preprov1 = $value->{"preprov1"};
          $monprov1 = $value->{"monprov1"};
          $obsprov1 = $value->{"obsprov1"};
          $codprov2 = $value->{"codprov2"};
          $preprov2 = $value->{"preprov2"};
		  $monprov2 = $value->{"monprov2"};
          $obsprov2 = $value->{"obsprov2"};
		  $codprov3 = $value->{"codprov3"};
          $preprov3 = $value->{"preprov3"};
		  $monprov3 = $value->{"monprov3"};
          $obsprov3 = $value->{"obsprov3"};
		  date_default_timezone_set('America/Lima');
		  $fecha = new DateTime();
		  $PcMod= gethostbyaddr($_SERVER['REMOTE_ADDR']);

		  $datos1 = array("CodPro"=>$codpro,
						  "CodProv1"=>$codprov1,
						  "PreProv1"=>$preprov1,
						  "MonProv1"=>$monprov1,
						  "ObsProv1"=>$obsprov1,
						  "CodProv2"=>$codprov2,
						  "PreProv2"=>$preprov2,
						  "MonProv2"=>$monprov2,
						  "ObsProv2"=>$obsprov2,
						  "CodProv3"=>$codprov3,
						  "PreProv3"=>$preprov3,
						  "MonProv3"=>$monprov3,
						  "ObsProv3"=>$obsprov3,
						  "FecMod"=>$fecha->format("Y-m-d H:i:s"),
						  "PcMod"=>$PcMod,
						  "UsuMod"=>$_SESSION["nombre"]);

		  $respuesta = ModeloMateriaPrima::mdlEditarPrecioMP("preciomp",$datos1);


		  $datos2 = array("CodAlt"=>$codalt,
						  "CodPro"=>$codpro,
						  "DesPro"=>$despro,
						  "UndPro"=>$undpro,
						  "Por_AdVal"=>$padval,
						  "Por_Seg"=>$pseg,
						  "PesPro"=>$pespro,
						  "Stk_Min"=>$stkmin,
						  "Stk_Max"=>$stkmax,
						  "FecMod"=>$fecha->format("Y-m-d H:i:s"),
						  "PcMod"=>$PcMod,
						  "UsuMod"=>$_SESSION["nombre"]);


		  $respuesta2 = ModeloMateriaPrima::mdlEditarMateriaPrima($datos2);

        }
        
    
        echo $respuesta;
    
      }

	/* 
	* SELECT SUBLINEA 2 PARA UNO PARA CREAR MATERIA PRIMA
	*/
	public function ajaxSelectSubLineas2(){

		$valor = $this->linea2;
		$valor2 = $this->sublinea;

		$respuesta2 = ControladorMateriaPrima::ctrMostrarSubLineas2($valor,$valor2);

		echo json_encode($respuesta2);
	}
	
	/*=============================================
	EDITAR DOCUMENTO DE VENTA
	=============================================*/	
	public $datosMateriaDuplicar;
	public function ajaxDuplicarMateria(){
        $valor = $this->datosMateriaDuplicar;
        $datos = json_decode($valor);
        foreach ($datos->{"datosMateria"} as  $value) {
          $codfab = $value->{"codfab"};
          $codalt = $value->{"codalt"};
		  $fampro = $value->{"fampro"};
		  $color = $value->{"color"};
		  $talla = $value->{"talla"};
          $despro = $value->{"despro"};
          $undpro = $value->{"undpro"};
          $padval = $value->{"padval"};
          $pseg = $value->{"pseg"};
          $pespro = $value->{"pespro"};
		  $stkactual = $value->{"stkactual"}; 
		  $stkmin = $value->{"stkmin"};
          $stkmax = $value->{"stkmax"};
          $codprov1 = $value->{"codprov1"};
          $preprov1 = $value->{"preprov1"};
          $monprov1 = $value->{"monprov1"};
          $obsprov1 = $value->{"obsprov1"};
          $codprov2 = $value->{"codprov2"};
          $preprov2 = $value->{"preprov2"};
		  $monprov2 = $value->{"monprov2"};
          $obsprov2 = $value->{"obsprov2"};
		  $codprov3 = $value->{"codprov3"};
          $preprov3 = $value->{"preprov3"};
		  $monprov3 = $value->{"monprov3"};
          $obsprov3 = $value->{"obsprov3"};
		  date_default_timezone_set('America/Lima');

		$existe= ModeloMateriaPrima::mdlMostrarExisteMateria($codfab);

		if ($existe){
			$respuesta = "error";
		}else{
			$fecha = new DateTime();
			$PcReg= gethostbyaddr($_SERVER['REMOTE_ADDR']);

			$ultimoCod = ModeloMateriaPrima::mdlMostrarUltimoCodPro();
            $suma = intval($ultimoCod["CodPro"]) + 1;
            $lenCod = max(intval($ultimoCod["LenCod"]), strlen((string) $suma));
            $codigoPro = str_pad($suma, $lenCod, '0', STR_PAD_LEFT);



			$datos = array(	"Cod_Local"=>'01',
							"Cod_Entidad"=>'01',
							"CodPro"=>$codigoPro,
							"CodProv1"=>$codprov1,
							"PreProv1"=>$preprov1,
							"MonProv1"=>$monprov1,
							"ObsProv1"=>$obsprov1,
							"CodProv2"=>$codprov2,
							"PreProv2"=>$preprov2,
							"MonProv2"=>$monprov2,
							"ObsProv2"=>$obsprov2,
							"CodProv3"=>$codprov3,
							"PreProv3"=>$preprov3,
							"MonProv3"=>$monprov3,
							"ObsProv3"=>$obsprov3,
							"FecReg"=>$fecha->format("Y-m-d H:i:s"),
							"PcReg"=>$PcReg,
							"UsuReg"=>$_SESSION["nombre"]);

			$respuesta = ModeloMateriaPrima::mdlIngresarPrecioMP("preciomp",$datos);

			if ($respuesta != "ok") {
				echo "error";
				return;
			}

			$datos2 = array("CodAlt"=>$codalt,
							"Cod_Local"=>'01',
							"Cod_Entidad"=>'01',
							"CodPro"=>$codigoPro,
							"CodFab"=>$codfab,
							"DesPro"=>$despro,
							"ColPro"=>$color,
							"UndPro"=>$undpro,
							"Mo"=>'',
							"PaiPro"=>'',
							"PrePro"=>'',
							"PreFob"=>'',
							"CosPro"=>'',
							"Por_AdVal"=>$padval,
							"Por_Seg"=>$pseg,
							"PesPro"=>$pespro,
							"Stk_Act"=>$stkactual,
							"Stk_Min"=>$stkmin,
							"Stk_Max"=>$stkmax,
							"EstPro"=>'1',
							"TalPro"=>$talla,
							"FamPro"=>$fampro,
							"Proveedor"=>'',
							"CodAlm01"=>'0',
							"FecReg"=>$fecha->format("Y-m-d H:i:s"),
							"PcReg"=>$PcReg,
							"UsuReg"=>$_SESSION["nombre"]);

				$respuesta2 = ModeloMateriaPrima::mdlIngresarMateriaPrima("producto",$datos2);

				if ($respuesta2 != "ok") {
					ModeloMateriaPrima::mdlEliminarPrecioMP($codigoPro);
					$respuesta = "error";
				} else {
					$respuesta = "ok";
				}

			}
		}
			
    
        echo $respuesta;
    
      }

	  /* 
	 * MOSTAR MP DE ALMACEN01 
	  */
	  public function ajaxSelectAlmacen01(){

		$valor = $this->codproCua;

		$respuesta = ControladorMateriaPrima::ctrSelectAlmacen01($valor);

		echo json_encode($respuesta);

	}	  

	 /* 
	 * MOSTAR MP DE ALMACEN01 
	  */
	  public function ajaxSelectMateriaTipo(){

		$valor = $this->selectTipo;
		$valor2 = $this->selectDocumento;

		$respuesta = ModeloMateriaPrima::mdlSelectMateriaTipo($valor,$valor2);

		echo json_encode($respuesta);

	}	 
	
	/* 
	 * MOSTAR MP DE ALMACEN01 
	  */
	  public function ajaxSelectTipoProdMP(){

		$valor = $this->tipoAlmacen01;

		$respuesta = ControladorMateriaPrima::ctrMostrarAlmacen01($valor);

		echo json_encode($respuesta);

	}	

	/*=============================================
	GUARDAR CAMBIOS DE DETALLE MP PRODUCCION
	=============================================*/	
	public $detalleMP;
	public function ajaxEditarDetalleMP(){
        $valor = $this->detalleMP;
        $datos = json_decode($valor);
        foreach ($datos->{"datosdetalleMP"} as  $value) {
          $codigo = $value->{"codigo"};
          $tipo = $value->{"tipo"};
          $documento = $value->{"documento"};
          $cantidad = $value->{"cantidad"};
          $cantidadAnt = $value->{"cantidadAnt"};
		  date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$PcMod= gethostbyaddr($_SERVER['REMOTE_ADDR']);


			$datos = array(	"cantidad"	=>	$cantidad,
							"documento"	=>	$documento,
							"tipo"		=>	$tipo,
							"codigo"	=>	$codigo,
							"usumod"	=>	$_SESSION["nombre"],
							"fecmod"	=>	$fecha->format("Y-m-d H:i:s"),
							"pcmod"		=>	$PcMod);
			
			$respuesta = ModeloMateriaPrima::mdlEditarDetalleMP($datos);
			
			if($tipo == 'PCOP'){
				$infoMateria=ControladorMateriaPrima::ctrSelectAlmacen01($codigo);
				//AUMENTO DE CUADRO CON CANTIDAD ANTIGUA
				$sumaStock = ModeloMateriaPrima::mdlActualizarStockMP($infoMateria["cuadro"],$cantidadAnt);
				
				//DESCUENTO DE CUADRO CON CANTIDAD nueva
				$restaStock = ModeloMateriaPrima::mdlDescontarStockMP($infoMateria["cuadro"],$cantidad);
				

				//DESCUENTO DE COPA CON CANTIDAD ANTIGUA
				$restaStock = ModeloMateriaPrima::mdlDescontarStockMP($codigo,$cantidadAnt);
				//AUMENTO DE COPA CON CANTIDAD NUEVA
				$sumaStock = ModeloMateriaPrima::mdlActualizarStockMP($codigo,$cantidad);
				
				$infoDetalle = ModeloMaestras::mdlMostrarProdDetalle2($infoMateria["cuadro"],$documento,$tipo);
				$nuevaCantidad =($infoDetalle["cantidad"] - $cantidadAnt)+$cantidad;

				$datosCuadro = array(	"cantidad"	=>	$nuevaCantidad,
										"documento"	=>	$documento,
										"tipo"		=>	$tipo,
										"codigo"	=>	$infoMateria["cuadro"],
										"usumod"	=>	$_SESSION["nombre"],
										"fecmod"	=>	$fecha->format("Y-m-d H:i:s"),
										"pcmod"		=>	$PcMod);
				
			
				$respuesta2 = ModeloMateriaPrima::mdlEditarDetalleMP($datosCuadro);
				
			}else{
				//AUMENTO DE CUADRO CON CANTIDAD ANTIGUA
				$sumaStock = ModeloMateriaPrima::mdlActualizarStockMP($codigo,$cantidadAnt);
				//DESCUENTO DE CUADRO CON CANTIDAD NUEVA
				$restaStock = ModeloMateriaPrima::mdlDescontarStockMP($codigo,$cantidad);
			}

		}

			echo $respuesta;
    
      }

	/*=============================================
	OCULTAR Y CAMBIAR DE ESTADO DE DETALLE MP PRODUCCION
	=============================================*/	
	public $eliminarDoc;
	public $eliminarTipo;
	public $eliminarCod;
	public function ajaxEliminarDetalleMP(){
        $valor1 = $this->eliminarDoc;
		$valor2 = $this->eliminarTipo;
		$valor3 = $this->eliminarCod;
		date_default_timezone_set('America/Lima');
		$fecha = new DateTime();
		$PcMod= gethostbyaddr($_SERVER['REMOTE_ADDR']);


		$datos = array("documento"	=>	$valor1,
						"tipo"		=>	$valor2,
						"codigo"	=>	$valor3,
						"estado"    =>  '0',
						"visible"   =>  '0',
						"usumod"	=>	$_SESSION["nombre"],
						"fecmod"	=>	$fecha->format("Y-m-d H:i:s"),
						"pcmod"		=>	$PcMod);
		//ACTUALIZAMOS EL ESTADO DEL DETALLE Y LO OCULTAMOS CON 0
		$respuesta = ModeloMateriaPrima::mdlAnularDetalleMP($datos);
		
		if($valor2 == 'PCOP'){
			//TRAEMOS LA INFORMACION DE LA COPA
			$infoMateria=ControladorMateriaPrima::ctrSelectAlmacen01($valor3);

			//TRAEMOS EL DETALLE DE LA COPA
			$infoDetalle = ModeloMaestras::mdlMostrarProdDetalle2($valor3,$valor1,$valor2);
			
			//DESCUENTO DE CUADRO CON CANTIDAD 
			$restaStock = ModeloMateriaPrima::mdlActualizarStockMP($infoMateria["cuadro"],$infoDetalle["cantidad"]);
			

			//DESCUENTO DE COPA CON CANTIDAD 
			$restaStock = ModeloMateriaPrima::mdlDescontarStockMP($valor3,$infoDetalle["cantidad"]);
			
			//TRAEMOS EL DETALLE DEL CUADRO DE LA COPA
			$infoDetalle2 = ModeloMaestras::mdlMostrarProdDetalle2($infoMateria["cuadro"],$valor1,$valor2);

			//NUEVA CANTIDAD DEL DETALLE CON LA RESTA DE LA COPA
			$nuevaCantidad =$infoDetalle2["cantidad"]-$infoDetalle["cantidad"];

			$datosCuadro = array(	"cantidad"	=>	$nuevaCantidad,
									"documento"	=>	$valor1,
									"tipo"		=>	$valor2,
									"codigo"	=>	$infoMateria["cuadro"],
									"usumod"	=>	$_SESSION["nombre"],
									"fecmod"	=>	$fecha->format("Y-m-d H:i:s"),
									"pcmod"		=>	$PcMod);
			
		
			//ACTUALIZAMOS DATOS DEL DETALLE DEL CUADRO
			$respuesta2 = ModeloMateriaPrima::mdlEditarDetalleMP($datosCuadro);
			
		}else{
			//TRAEMOS EL DETALLE DEL CUADRO
			$infoDetalle = ModeloMaestras::mdlMostrarProdDetalle2($valor3,$valor1,$valor2);
			//DESCUENTO DE CUADRO CON CANTIDAD 
			$restaStock = ModeloMateriaPrima::mdlDescontarStockMP($valor3,$infoDetalle["cantidad"]);
		}


		echo $respuesta;

	}

	/*=============================================
	GUARDAR NUEVO DETALLE MP PRODUCCION
	=============================================*/	
	public $prodMP;
	public function ajaxAgregarDetalleMP(){
        $valor = $this->prodMP;
        $datos = json_decode($valor);
        foreach ($datos->{"datosProdMP"} as  $value) {
			$codigo = $value->{"codigo"};
			$tipo = $value->{"tipo"};
			$documento = $value->{"documento"};
			$cantidad = $value->{"cantidad"};
			$cuadro = $value->{"cuadro"};
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$PcReg= gethostbyaddr($_SERVER['REMOTE_ADDR']);


			
			
			if($tipo == 'PCOP'){
				//AUMENTO DE COPA
				$respuestaStock = ModeloMateriaPrima::mdlActualizarStockMP($codigo,$cantidad);
				//DESCUENTO DE CUADRO
				$respuestaStock2 = ModeloMateriaPrima::mdlDescontarStockMP($cuadro,$cantidad);
				
				$infoDetalle = ModeloMaestras::mdlMostrarProdDetalle2($cuadro,$documento,$tipo);
				// var_dump($infoDetalle);
				 if($infoDetalle){
					
					$nuevaCantidad = $infoDetalle["cantidad"] + $cantidad;
					$datosCuadro = array(	"cantidad"	=>	$nuevaCantidad,
											"documento"	=>	$documento,
											"tipo"		=>	$tipo,
											"codigo"	=>	$cuadro,
											"usumod"	=>	$_SESSION["nombre"],
											"fecmod"	=>	$fecha->format("Y-m-d H:i:s"),
											"pcmod"		=>	$PcReg);
					
			
					$respuestaCuadro = ModeloMateriaPrima::mdlEditarDetalleMP($datosCuadro);

				 }else{
					
				//GUARDAR DETALLE DE CUADRO
					$datosCuadro = array(	"tipo" 		=> $tipo,
											"documento"	=> $documento,
											"codigo"	=> $cuadro,
											"valor1"	=> $cantidad, 
											"valor2"	=> '0',
											"valor3"	=> '0',
											"valor4"	=> '0',
											"valor5"	=> '0',
											"fecreg"	=> $fecha->format("Y-m-d H:i:s"),
											"usureg"	=> $_SESSION["nombre"],
											"pcreg" 	=> $PcReg,
											"condicion"	=> '-');

					$respuestaCuadro = ModeloMateriaPrima::mdlGuardarProduccionDet($datosCuadro);
				}
				
			
				
			}else{
				//AUMENTO DE CUADRO CON CANTIDAD ANTIGUA
				$sumaStock = ModeloMateriaPrima::mdlActualizarStockMP($codigo,$cantidad);
				
			}


			$datos = array(	"tipo" 		=> $tipo,
							"documento"	=> $documento,
							"codigo"	=> $codigo,
							"valor1"	=> $cantidad, 
							"valor2"	=> '0',
							"valor3"	=> '0',
							"valor4"	=> '0',
							"valor5"	=> '0',
							"fecreg"	=> $fecha->format("Y-m-d H:i:s"),
							"usureg"	=> $_SESSION["nombre"],
							"pcreg" 	=> $PcReg,
							"condicion"	=> '+');
			
			$respuesta = ModeloMateriaPrima::mdlGuardarProduccionDet($datos);

		}

			echo $respuesta;
    
      }


	/*=============================================
	EDITAR COPA DE MP
	=============================================*/	
	public $editarCopaMP;
	public function ajaxEditarCopaMP(){
        $valor = $this->editarCopaMP;
        $datos = json_decode($valor);
        foreach ($datos->{"datosEditarCopa"} as  $value) {
          $codigo = $value->{"codigo"};
          $cuadro = $value->{"cuadro"};
		  date_default_timezone_set('America/Lima');
		  $fecha = new DateTime();
		  $PcMod= gethostbyaddr($_SERVER['REMOTE_ADDR']);

		  $datos = array("codigo"=>$codigo,
						  "cuadro"=>$cuadro,
						  "FecMod"=>$fecha->format("Y-m-d H:i:s"),
						  "PcMod"=>$PcMod,
						  "UsuMod"=>$_SESSION["nombre"]);

			$respuesta = ModeloMateriaPrima::mdlEditarCopaMP($datos);

        }
        
    
        echo $respuesta;
    
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
 * ÓRDENES DE COMPRA / SERVICIO POR MATERIA PRIMA
*/
if(isset($_POST["ordenesMp"])){

	$ordenesMp = new AjaxMateriaPrima();
	$ordenesMp -> ordenesMp = $_POST["ordenesMp"];
	$ordenesMp -> ajaxOrdenesMp();

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

/* 
* AGREGAR NOMBRE DE MATERIA PRIMA PARA OCOMPRA Y NS
*/

if(isset($_POST["idMateriaPrima2"])){

	$agregarMateriaPrima = new AjaxMateriaPrima();
	$agregarMateriaPrima -> idMateriaPrima2 = $_POST["idMateriaPrima2"];
	$agregarMateriaPrima -> ajaxAgregarMateriaPrima2();
  
}

/*=============================================
GUARDAR CAMBIOS DE MATERIA PRIMA
=============================================*/	
if(isset($_POST["jsonMateria"])){
	
	$editarVentaCambios = new AjaxMateriaPrima();
	$editarVentaCambios -> datosMateria = $_POST["jsonMateria"];
	$editarVentaCambios -> ajaxCambiosMateria();
}

/* 
* SELECT PARA MOSTRAR LAS SUBLINEAS AL CREAR MATERIAPRIMA
*/

if(isset($_POST["sublinea"])){

	$selectSubLineas2 = new AjaxMateriaPrima();
	$selectSubLineas2 -> linea2 = $_POST["linea2"];
	$selectSubLineas2 -> sublinea = $_POST["sublinea"];
	$selectSubLineas2 -> ajaxSelectSubLineas2();
  
}


/*=============================================
GUARDAR CAMBIOS DE MATERIA PRIMA
=============================================*/	
if(isset($_POST["jsonMateriaDuplicar"])){
	
	$duplicarNuevoColor = new AjaxMateriaPrima();
	$duplicarNuevoColor -> datosMateriaDuplicar = $_POST["jsonMateriaDuplicar"];
	$duplicarNuevoColor -> ajaxDuplicarMateria();
}

/* 
* AGREMAR LA COPA AL CUADRO
*/

if(isset($_POST["cuadro"])){

	$agregarCuadro = new AjaxMateriaPrima();
	$agregarCuadro -> cuadro = $_POST["cuadro"];
	$agregarCuadro -> codpro = $_POST["codpro"];
	$agregarCuadro -> ajaxAgregarCuadro();
  
}

/* 
* QUITAR LA COPA AL CUADRO
*/

if(isset($_POST["codproQ"])){

	$agregarCuadro = new AjaxMateriaPrima();
	$agregarCuadro -> codproQ = $_POST["codproQ"];
	$agregarCuadro -> ajaxQuitarCuadro();
  
}

/* 
* QUITAR LA COPA AL CUADRO
*/

if(isset($_POST["codproCua"])){

	$agregarCuadro = new AjaxMateriaPrima();
	$agregarCuadro -> codproCua = $_POST["codproCua"];
	$agregarCuadro -> ajaxSelectAlmacen01();
  
}

/* 
* AGREGAR MATERIA PARA ORDEN DE COMPRA
*/

if(isset($_POST["idMateriaCompra"])){

	$agregarMateriaCompra = new AjaxMateriaPrima();
	$agregarMateriaCompra -> idMateriaCompra = $_POST["idMateriaCompra"];
	$agregarMateriaCompra -> CodRuc = $_POST["CodRuc"];
	$agregarMateriaCompra -> ajaxAgregarMateriaCompra();
  
}

/*=============================================
GUARDAR CAMBIOS DE DETALLE MP PRODUCCION
=============================================*/	
if(isset($_POST["jsonDetalleMP"])){
	
	$editarMP = new AjaxMateriaPrima();
	$editarMP -> detalleMP = $_POST["jsonDetalleMP"];
	$editarMP -> ajaxEditarDetalleMP();
}

/* 
* OCULTAR DETALLE MP Y CAMBIAR ESTADO
*/

if(isset($_POST["eliminarDoc"])){

	$eliminarDetalleMP = new AjaxMateriaPrima();
	$eliminarDetalleMP -> eliminarDoc = $_POST["eliminarDoc"];
	$eliminarDetalleMP -> eliminarTipo = $_POST["eliminarTipo"];
	$eliminarDetalleMP -> eliminarCod = $_POST["eliminarCod"];
	$eliminarDetalleMP -> ajaxEliminarDetalleMP();
  
}

/* 
* QUITAR LA COPA AL CUADRO
*/

if(isset($_POST["selectTipo"])){

	$agregarCuadro = new AjaxMateriaPrima();
	$agregarCuadro -> selectTipo = $_POST["selectTipo"];
	$agregarCuadro -> selectDocumento = $_POST["selectDocumento"];
	$agregarCuadro -> ajaxSelectMateriaTipo();
  
}

/*=============================================
GUARDAR NUEVO DETALLE MP PRODUCCION
=============================================*/	
if(isset($_POST["jsonProdMP"])){
	
	$agregarMP = new AjaxMateriaPrima();
	$agregarMP -> prodMP = $_POST["jsonProdMP"];
	$agregarMP -> ajaxAgregarDetalleMP();
}

/*=============================================
SELECT PARA MOSTRAR ALMACEN SEGUN SU TIPO
=============================================*/	
if(isset($_POST["tipoAlmacen01"])){
	
	$selectTipoProdMP = new AjaxMateriaPrima();
	$selectTipoProdMP -> tipoAlmacen01 = $_POST["tipoAlmacen01"];
	$selectTipoProdMP -> ajaxSelectTipoProdMP();
}

/*=============================================
EDITAR COPA PARA EL CAMPO CUADRO
=============================================*/	
if(isset($_POST["jsonEditarCopa"])){
	
	$editarCopaMP = new AjaxMateriaPrima();
	$editarCopaMP -> editarCopaMP = $_POST["jsonEditarCopa"];
	$editarCopaMP -> ajaxEditarCopaMP();
}
