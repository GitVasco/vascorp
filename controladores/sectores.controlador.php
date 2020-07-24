<?php

class ControladorSectores{

	/*=============================================
	CREAR SECTORES
	=============================================*/

	static public function ctrCrearSector(){

		if(isset($_POST["nuevoSector"])){

			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoSector"]) &&
			   preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoCodigo"])){

			   	$datos = array("sector"=>$_POST["nuevoSector"],
					           "codigo"=>$_POST["nuevoCodigo"]);

			   	$respuesta = ModeloSectores::mdlIngresarSector($datos);

			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El sector ha sido guardado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "sectores";

									}
								})

					</script>';

				}

			}else{

				echo'<script>

					swal({
						  type: "error",
						  title: "¡El sector no puede ir vacío o llevar caracteres especiales!",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
							if (result.value) {

							window.location = "sectores";

							}
						})

			  	</script>';



			}

		}

    }
    

	/*=============================================
	MOSTRAR SECTORES
	=============================================*/

	static public function ctrMostrarSectores($valor){

		$respuesta = ModeloSectores::mdlMostrarSectores($valor);

		return $respuesta;

    }
    
	/*=============================================
	EDITAR SECTORES
	=============================================*/

	static public function ctrEditarSector(){

		if(isset($_POST["editarSector"])){

			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarSector"]) &&
			   preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarCodigo"])){

			   	$datos = array("id"=>$_POST["idSector"],
                               "sector"=>$_POST["editarSector"],
					           "codigo"=>$_POST["editarCodigo"]);

			   	$respuesta = ModeloSectores::mdlEditarSector($datos);

			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El sector ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "sectores";

									}
								})

					</script>';

				}

			}else{

				echo'<script>

					swal({
						  type: "error",
						  title: "¡El sector no puede ir vacío o llevar caracteres especiales!",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
							if (result.value) {

							window.location = "sectores";

							}
						})

			  	</script>';



			}

		}

    }
    
	/*=============================================
	ELIMINAR SECTOR
	=============================================*/

	static public function ctrEliminarSector(){

		if(isset($_GET["idSector"])){

			$datos = $_GET["idSector"];

			$respuesta = ModeloSectores::mdlEliminarSector($datos);

			if($respuesta == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "El sector ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "sectores";

								}
							})

				</script>';

			}		

		}

	}    

}