<?php

class ControladorClientes{

	/*=============================================
	CREAR CLIENTES
	=============================================*/

	static public function ctrCrearCliente(){

		if(isset($_POST["codigoCliente"])){
			

			$tabla = "clientesjf";
			$interruptores= array("'",'"',"´");
			$codigo = str_replace($interruptores, "", $_POST["codigoCliente"]);
			$nombre = str_replace($interruptores, "", $_POST["nombre"]);
			$ape_pat = str_replace($interruptores, "", $_POST["ape_paterno"]);
			$ape_mat = str_replace($interruptores, "", $_POST["ape_materno"]);
			$nombres = str_replace($interruptores, "", $_POST["nombres"]);
			$direccion = str_replace($interruptores, "", $_POST["direccion"]);
			$telefono1 = str_replace($interruptores, "", $_POST["telefono"]);
			$telefono2 = str_replace($interruptores, "", $_POST["telefono2"]);
			$email = str_replace($interruptores, "", $_POST["email"]);
			$contacto = str_replace($interruptores, "", $_POST["contacto"]);

			$datos = array("codigoCliente"=>$codigo,
						"nombre"=>$nombre,
						"tipo_documento"=>$_POST["tipo_documento"],
						"documento"=>$_POST["documento"],
						"tipo_persona"=>$_POST["tipo_persona"],
						"ape_paterno"=>$ape_pat,
						"ape_materno"=>$ape_mat,
						"nombres"=>$nombres,
						"direccion"=>$direccion,
						"ubigeo"=>$_POST["ubigeo"],
						"telefono"=>$telefono1,
						"telefono2"=>$telefono2,
						"email"=>$email,
						"contacto"=>$contacto,
						"vendedor"=>$_POST["vendedor"],
						"grupo"=>$_POST["grupo"],
						"lista_precios"=>$_POST["lista_precios"]);
			#var_dump("datos", $datos);

			$respuesta = ModeloClientes::mdlIngresarCliente($tabla, $datos);

			if($respuesta == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "La marca ha sido guardada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "clientes";

								}
							})

				</script>';

			}


		}

    }   
    
	/*=============================================
	MOSTRAR CLIENTES
	=============================================*/

	static public function ctrMostrarClientes($item, $valor){

		$tabla = "clientesjf";

		$respuesta = ModeloClientes::mdlMostrarClientes($tabla, $item, $valor);

		return $respuesta;

	}

	/*=============================================
	MOSTRAR CLIENTES CUENTAS
	=============================================*/

	static public function ctrMostrarClientesCuentas($item, $valor){

		$tabla = "clientesjf";

		$respuesta = ModeloClientes::mdlMostrarClientesCuentas($tabla, $item, $valor);

		return $respuesta;

	}

	/*=============================================
	MOSTRAR CLIENTES P
	=============================================*/

	static public function ctrMostrarClientesP($item, $valor){

		$tabla = "clientesjf";

		$respuesta = ModeloClientes::mdlMostrarClientesP($tabla, $item, $valor);

		return $respuesta;

	}	

	/*=============================================
	SACAR LISTA
	=============================================*/

	static public function ctrVerLista($valor){

		$respuesta = ModeloClientes::mdlVerLista($valor);

		return $respuesta;

	}		

	/*=============================================
	EDITAR CLIENTE
	=============================================*/

	static public function ctrEditarCliente(){

		if(isset($_POST["editarCodigoCliente"])){

			

			   	$tabla = "clientesjf";
				$interruptores= array("'",'"',"´");
				$codigo = str_replace($interruptores, "", $_POST["editarCodigoCliente"]);
				$nombre = str_replace($interruptores, "", $_POST["editarNombre"]);
				$ape_pat = str_replace($interruptores, "", $_POST["editarApe_paterno"]);
				$ape_mat = str_replace($interruptores, "", $_POST["editarApe_materno"]);
				$nombres = str_replace($interruptores, "", $_POST["editarNombres"]);
				$direccion = str_replace($interruptores, "", $_POST["editarDireccion"]);
				$telefono1 = str_replace($interruptores, "", $_POST["editarTelefono"]);
				$telefono2 = str_replace($interruptores, "", $_POST["editarTelefono2"]);
				$email = str_replace($interruptores, "", $_POST["editarEmail"]);
				$contacto = str_replace($interruptores, "", $_POST["editarContacto"]);


				$datos = array(	"codigoCliente"=>$codigo,
								"nombre"=>$nombre,
								"tipo_documento"=>$_POST["editarTipo_documento"],
								"documento"=>$_POST["editarDocumento"],
								"tipo_persona"=>$_POST["editarTipo_persona"],
								"ape_paterno"=>$ape_pat,
								"ape_materno"=>$ape_mat,
								"nombres"=>$nombres,
								"direccion"=>$direccion,
								"ubigeo"=>$_POST["editarUbigeo"],
								"telefono"=>$telefono1,
								"telefono2"=>$telefono2,
								"email"=>$email,
								"contacto"=>$contacto,
								"vendedor"=>$_POST["editarVendedor"],
								"grupo"=>$_POST["editarGrupo"],
								"lista_precios"=>$_POST["editarLista_precios"]);
				#var_dump("datos", $datos);

			   	$respuesta = ModeloClientes::mdlEditarCliente($tabla, $datos);
				#$respuesta = "false";

			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El cliente ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "clientes";

									}
								})

					</script>';

				}

			

		}

	}	

	/*=============================================
	ELIMINAR CLIENTE
	=============================================*/

	static public function ctrEliminarCliente(){

		if(isset($_GET["idCliente"])){

			$tabla ="clientesjf";
			$datos = $_GET["idCliente"];

			$respuesta = ModeloClientes::mdlEliminarCliente($tabla, $datos);

			if($respuesta == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "El cliente ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "clientes";

								}
							})

				</script>';

			}		

		}

	}
	
	/* 
	* MOSTRAR UBIGEOS
	*/
	static public function ctrMostrarUbigeos(){

		$tabla = "ubigeo";

		$respuesta = ModeloClientes::mdlMostrarUbigeos($tabla);

		return $respuesta;

	}
	
	/*=============================================
	CREAR CLIENTES PARA PEDIDOS
	=============================================*/

	static public function ctrCrearClienteP(){

		if(isset($_POST["codigoCliente"])){
			
			if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚüÜ ]+$/', $_POST["nombre"])){

			   $tabla = "clientesjf";

			   $datos = array("codigoCliente"=>$_POST["codigoCliente"],
						   "nombre"=>$_POST["nombre"],
						   "tipo_documento"=>$_POST["tipo_documento"],
						   "documento"=>$_POST["documento"],
						   "tipo_persona"=>$_POST["tipo_persona"],
						   "ape_paterno"=>$_POST["ape_paterno"],
						   "ape_materno"=>$_POST["ape_materno"],
						   "nombres"=>$_POST["nombres"],
						   "direccion"=>$_POST["direccion"],
						   "ubigeo"=>$_POST["ubigeo"],
						   "telefono"=>$_POST["telefono"],
						   "telefono2"=>$_POST["telefono2"],
						   "email"=>$_POST["email"],
						   "contacto"=>$_POST["contacto"],
						   "vendedor"=>$_POST["vendedor"],
						   "grupo"=>$_POST["grupo"],
						   "lista_precios"=>$_POST["lista_precios"]);
			#var_dump("datos", $datos);

			$respuesta = ModeloClientes::mdlIngresarCliente($tabla, $datos);

			if($respuesta == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "La marca ha sido guardada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "crear-pedidocv";

								}
							})

				</script>';

			}



			}else{

				echo'<script>

					swal({
						type: "error",
						title: "¡El cliente no puede ir vacío o llevar caracteres especiales!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
							if (result.value) {

							window.location = "crear-pedidocv";

							}
						})

				</script>';



			}


		}

    } 	

    /*=============================================
	EDITAR TIPO DE PAGO
	=============================================*/

	static public function ctrEditarAval(){

		if(isset($_POST["editarAvalNombre"])){

				$tabla="clientesjf";
				   $datos = array("codigo"=>$_POST["avalCliente"],
				   				"aval_nombre"=> $_POST["editarAvalNombre"],
                               "aval_dir"=>$_POST["editarAvalDir"],
							   "aval_postal"=> $_POST["editarAvalPostal"],
                               "aval_telf"=>$_POST["editarAvalTelf"],
							   "aval_ruc"=> $_POST["editarAvalRuc"],
                               "aval_libreta"=>$_POST["editarAvalLibreta"]);
			   	$respuesta = ModeloClientes::mdlEditarAval($tabla,$datos);
				// var_dump($datos);
				// var_dump($respuesta);

			   	if($respuesta == "ok"){

					echo'<script>

					swal({
						  type: "success",
						  title: "El aval ha sido guardado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "clientes";

									}
								})

					</script>';


			}
		}

    }

}    