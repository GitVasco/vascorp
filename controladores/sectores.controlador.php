<?php

class ControladorSectores{

	/*=============================================
	CREAR SECTORES
	=============================================*/

	static public function ctrCrearSector(){

		if(isset($_POST["nuevoSector"])){

			   	$tipo = (isset($_POST["nuevoTipo"]) && (string)$_POST["nuevoTipo"] === "0") ? 0 : 1;
			   	$estado = (isset($_POST["nuevoEstado"]) && (string)$_POST["nuevoEstado"] === "0") ? 0 : 1;
			   	$color = isset($_POST["nuevoColor"]) ? $_POST["nuevoColor"] : "";

			   	$datos = array("sector"=>$_POST["nuevoSector"],
					           "codigo"=>$_POST["nuevoCodigo"],
					           "tipo"=>$tipo,
					           "estado"=>$estado,
					           "color"=>$color);

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
	HELPERS TIPO (interno / externo) — Fase 0 refactor sectores
	=============================================*/

	static public function ctrEsInterno($codSector){

		return ModeloSectores::mdlEsInterno($codSector);

	}

	static public function ctrSectoresPorTipo($tipo){

		return ModeloSectores::mdlSectoresPorTipo($tipo);

	}

	/** Lista de cod_sector por tipo (0 interno, 1 externo). */
	static public function ctrCodigosPorTipo($tipo){

		$filas = self::ctrSectoresPorTipo($tipo);
		$codigos = array();

		foreach ($filas as $fila) {
			if (isset($fila["cod_sector"]) && $fila["cod_sector"] !== "") {
				$codigos[] = $fila["cod_sector"];
			}
		}

		return $codigos;

	}

	static public function ctrDebeImprimirTickets($codSector){

		return ModeloSectores::mdlDebeImprimirTickets($codSector);

	}
    
	/*=============================================
	EDITAR SECTORES
	=============================================*/

	static public function ctrEditarSector(){

		if(isset($_POST["editarSector"])){

			

			   	$tipo = (isset($_POST["editarTipo"]) && (string)$_POST["editarTipo"] === "0") ? 0 : 1;
			   	$estado = (isset($_POST["editarEstado"]) && (string)$_POST["editarEstado"] === "0") ? 0 : 1;
			   	$color = isset($_POST["editarColor"]) ? $_POST["editarColor"] : "";

			   	$datos = array("id"=>$_POST["idSector"],
                               "sector"=>$_POST["editarSector"],
					           "codigo"=>$_POST["editarCodigo"],
					           "tipo"=>$tipo,
					           "estado"=>$estado,
					           "color"=>$color);

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
		}

    }
    
	/*=============================================
	ELIMINAR SECTOR
	=============================================*/

	static public function ctrEliminarSector(){

		if(isset($_GET["idSector"])){
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$datos = $_GET["idSector"];
			$sector=ControladorSectores::ctrMostrarSectores($datos);
			$usuario= $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino un sector';
			$descripcion   = 'El usuario '.$usuario.' elimino el sector '.$sector["cod_sector"].' - '.$sector["nom_sector"];
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
