<?php

class ControladorVendedores{

	/*=============================================
	CREAR TIPO DE PAGO
	=============================================*/

	static public function ctrCrearVendedor(){

		if(isset($_POST["nuevaDescripcion"])){

				$tabla="maestrajf";
			   	$datos = array("codigo"=>$_POST["nuevoCodigo"],
							   "descripcion"=>$_POST["nuevaDescripcion"],
								"tipo_dato"=>"TVEND",
								"estado_decisiones" => isset($_POST["nuevoEstadoDecisiones"]) ? (int) $_POST["nuevoEstadoDecisiones"] : 0);

			   	$respuesta = ModeloVendedores::mdlIngresarVendedor($tabla,$datos);

			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El vendedor ha sido guardado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "vendedor";

									}
								})

					</script>';

				}

			

		}

    }
    

	/*=============================================
	MOSTRAR TIPO DE PAGO
	=============================================*/

	static public function ctrMostrarVendedores($item,$valor){
		$tabla="maestrajf";
		$respuesta = ModeloVendedores::mdlMostrarVendedores($tabla,$item,$valor);

		return $respuesta;

    }
    
	/*=============================================
	EDITAR TIPO DE PAGO
	=============================================*/

	static public function ctrToggleEstadoDecisiones($id, $estado){

		$tabla = "maestrajf";
		$datos = array(
			"id" => (int) $id,
			"estado_decisiones" => (int) $estado ? 1 : 0
		);

		return ModeloVendedores::mdlToggleEstadoDecisiones($tabla, $datos);

	}

	static public function ctrEditarVendedor(){

		if(isset($_POST["editarDescripcion"])){

				$tabla="maestrajf";
				   $datos = array("id"=>$_POST["idVendedor"],
				   				"codigo"=> $_POST["editarCodigo"],
                               "descripcion"=>$_POST["editarDescripcion"],
                               "estado_decisiones" => isset($_POST["editarEstadoDecisiones"]) ? (int) $_POST["editarEstadoDecisiones"] : 0);

			   	$respuesta = ModeloVendedores::mdlEditarVendedor($tabla,$datos);

			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El vendedor ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "vendedor";

									}
								})

					</script>';


			}
		}

    }
    
	/*=============================================
	ELIMINAR TIPO DE PAGO
	=============================================*/

	static public function ctrEliminarVendedor(){

		if(isset($_GET["idVendedor"])){

			$datos = $_GET["idVendedor"];
			$tabla="maestrajf";
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$vendedor=ControladorVendedores::ctrMostrarVendedores("id",$datos);
			$usuario= $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino un vendedor';
			$descripcion   = 'El usuario '.$usuario.' elimino el vendedor '.$vendedor["codigo"].' - '.$vendedor["nombre"];
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
			
			$respuesta = ModeloVendedores::mdlEliminarVendedor($tabla,$datos);
			if($respuesta == "ok"){
				
				
				echo'<script>

				swal({
					  type: "success",
					  title: "El vendedor ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "vendedor";

								}
							})

				</script>';

			}		

		}

	}    

}
