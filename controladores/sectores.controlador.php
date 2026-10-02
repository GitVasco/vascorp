<?php

class ControladorSectores{


	/*=============================================
	DATOS FISCALES PARA GUÍAS DE REMISIÓN (solo externos)
	$p = "nuevo" | "editar". Devuelve vacio / ok / msg / datos.
	=============================================*/

	static private function greDatosPost($p){

		$g = function($k) use ($p){
			$v = isset($_POST[$p . $k]) ? trim((string) $_POST[$p . $k]) : "";
			return trim(preg_replace('/[\r\n\t,]+/', ' ', $v));
		};

		$d = array(
			"razon_social" => $g("GreRazon"), "tipo_doc" => $g("GreTipoDoc"), "doc" => strtoupper($g("GreDoc")),
			"email" => $g("GreEmail"), "direccion" => $g("GreDireccion"), "ubigeo" => $g("GreUbigeo"),
			"dpto" => $g("GreDpto"), "prov" => $g("GreProv"), "dist" => $g("GreDist")
		);

		if ($d["razon_social"] === "" && $d["doc"] === "" && $d["direccion"] === "" && $d["ubigeo"] === "") {
			return array("vacio" => true, "ok" => true, "msg" => "", "datos" => $d);
		}

		$msg = "";
		$td = $d["tipo_doc"];
		if ($d["razon_social"] === "" || $d["doc"] === "" || $d["direccion"] === "" || $d["dpto"] === "" || $d["prov"] === "" || $d["dist"] === "") {
			$msg = "Completa razón social, documento, dirección, departamento, provincia y distrito (o deja todo vacío).";
		} elseif (!preg_match('/^\d{6}$/', $d["ubigeo"])) {
			$msg = "El ubigeo debe tener 6 dígitos.";
		} elseif (($td === "6" && !preg_match('/^\d{11}$/', $d["doc"])) || ($td === "1" && !preg_match('/^\d{8}$/', $d["doc"]))
			|| (!in_array($td, array("0", "1", "4", "6", "7", "A"), true))
			|| (in_array($td, array("0", "4", "7", "A"), true) && !preg_match('/^[A-Z0-9]{1,15}$/', $d["doc"]))) {
			$msg = "El número de documento no coincide con su tipo (RUC 11 dígitos, DNI 8).";
		}

		return array("vacio" => false, "ok" => $msg === "", "msg" => $msg, "datos" => $d);

	}

	static private function greAvisoError($msg){

		echo '<script>swal({type: "error", title: "Datos para guía de remisión", text: ' . json_encode($msg) . ', showConfirmButton: true, confirmButtonText: "Cerrar"});</script>';

	}

	static private function greGuardarDatos($cod, $g){

		if (!$g["vacio"] && $g["ok"]) {
			require_once __DIR__ . "/../modelos/gre-manual.modelo.php";
			$usuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";
			ModeloGreManual::mdlGuardarDatosTaller($cod, $g["datos"], $usuario);
		}

	}

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

			   	$gre = array("vacio" => true, "ok" => true);
			   	if($tipo === 1){
			   		$gre = self::greDatosPost("nuevo");
			   		if(!$gre["ok"]){ self::greAvisoError($gre["msg"]); return; }
			   	}

			   	$respuesta = ModeloSectores::mdlIngresarSector($datos);

			   	if($respuesta == "ok" && $tipo === 1){ self::greGuardarDatos($_POST["nuevoCodigo"], $gre); }

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

			   	$gre = array("vacio" => true, "ok" => true);
			   	if($tipo === 1){
			   		$gre = self::greDatosPost("editar");
			   		if(!$gre["ok"]){ self::greAvisoError($gre["msg"]); return; }
			   	}

			   	$respuesta = ModeloSectores::mdlEditarSector($datos);

			   	if($respuesta == "ok" && $tipo === 1){ self::greGuardarDatos($_POST["editarCodigo"], $gre); }

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
