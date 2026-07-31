<?php

class ControladorTipoTrabajador{
    /*=============================================
	MOSTRAR OPERACIONES
	=============================================*/

	static public function ctrMostrarTipoTrabajador($item = null, $valor = null){
        $tabla="tipo_trabajadorjf";
		$respuesta = ModeloTipoTrabajador::mdlMostrarTipoTrabajador($tabla,$item,$valor);

		return $respuesta;

	}
	
	/*=============================================
	CREAR TIPO DE TRABAJADOR
	=============================================*/

	static public function ctrCrearTipoTrabajador(){

		if(isset($_POST["nuevoSectorTrabajador"])){

			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoSectorTrabajador"]) &&
			   preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoTipoTrabajador"])){
				$tabla="tipo_trabajadorjf";
			   	$datos = array("nom_tip_trabajador"=>$_POST["nuevoTipoTrabajador"],
					           "detalle"=>$_POST["nuevoSectorTrabajador"]);

			   	$respuesta = ModeloTipoTrabajador::mdlIngresarTipoTrabajador($tabla,$datos);
				//var_dump($respuesta);
			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El tipo trabajador ha sido guardada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "tipotrabajador";

									}
								})

					</script>';

				}

			}else{

				echo'<script>

					swal({
						  type: "error",
						  title: "¡El tipo trabajador no puede ir vacío o llevar caracteres especiales!",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
							if (result.value) {

							window.location = "tipotrabajador";

							}
						})

			  	</script>';



			}

		}

	}
	
	/*=============================================
	ELIMINAR TIPO DE TRABAJADOR
	=============================================*/

	static public function ctrEliminarTipoTrabajador(){

		if(isset($_GET["idTipoTrabajador"])){
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$tabla ="tipo_trabajadorjf";
			$datos = $_GET["idTipoTrabajador"];
			$tipo = ControladorTipoTrabajador::ctrMostrarTipoTrabajador("cod_tip_tra", $datos);
			$nomTipo = (is_array($tipo) && isset($tipo["nom_tip_trabajador"])) ? $tipo["nom_tip_trabajador"] : (string) $datos;
			$detTipo = (is_array($tipo) && isset($tipo["detalle"])) ? $tipo["detalle"] : "";
			$usuario= $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino un tipo de trabajador';
			$descripcion   = 'El usuario '.$usuario.' elimino el tipo de trabajador '.$nomTipo.' - '.$detTipo;
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
			$respuesta = ModeloTipoTrabajador::mdlEliminarTipoTrabajador($tabla,$datos);

			if($respuesta == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "El tipo de trabajador ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "tipotrabajador";

								}
							})

				</script>';

			}		

		}

	}    


	/*=============================================
	EDITAR TIPO DE TRABAJADOR
	=============================================*/

	static public function ctrEditarTipoTrabajador(){

		if(isset($_POST["editarTipoTrabajador"])){

			// var_dump("editarTipoTrabajador", $_POST["editarTipoTrabajador"]);

			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarTipoTrabajador"]) &&
			   preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarSectorTrabajador"])
			   ){

			   	$datos = array("cod_tip_tra"=>$_POST["idTipoTrabajador"],
							   "nom_tip_trabajador"=>$_POST["editarTipoTrabajador"],
							   "detalle"=>$_POST["editarSectorTrabajador"]);

				$tabla="tipo_trabajadorjf";
				
			   	$respuesta = ModeloTipoTrabajador::mdlEditarTipoTrabajador($tabla,$datos);

			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El tipo de trabajador ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "tipotrabajador";

									}
								})

					</script>';

				}

			}else{

				echo'<script>

					swal({
						  type: "error",
						  title: "¡El tipo de trabajador no puede ir vacío o llevar caracteres especiales!",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
							if (result.value) {

							window.location = "tipotrabajador";

							}
						})

			  	</script>';



			}

		}

    }
}