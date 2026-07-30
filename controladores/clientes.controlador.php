<?php

class ControladorClientes
{

	/*=============================================
	CREAR CLIENTES
	=============================================*/

	static public function ctrCrearCliente()
	{

		if (isset($_POST["codigoCliente"])) {


			$tabla = "clientesjf";
			$interruptores = array("'", '"', "´");
			$codigo 			= str_replace($interruptores, "", $_POST["codigoCliente"]);
			$nombre 			= str_replace($interruptores, "", $_POST["nombre"]);
			$ape_pat 			= str_replace($interruptores, "", $_POST["ape_paterno"]);
			$ape_mat 			= str_replace($interruptores, "", $_POST["ape_materno"]);
			$nombres 			= str_replace($interruptores, "", $_POST["nombres"]);
			$direccion 			= str_replace($interruptores, "", $_POST["direccion"]);
			$direccionDespacho 	= str_replace($interruptores, "", $_POST["direccionDespacho"]);
			$telefono1 			= str_replace($interruptores, "", $_POST["telefono"]);
			$telefono2 			= str_replace($interruptores, "", $_POST["telefono2"]);
			$email 				= str_replace($interruptores, "", $_POST["email"]);
			$contacto 			= str_replace($interruptores, "", $_POST["contacto"]);

			date_default_timezone_set('America/Lima');
			$fecreg 			= new DateTime();
			$pcreg				= gethostbyaddr($_SERVER['REMOTE_ADDR']);
			$usureg				= $_SESSION["nombre"];

			$datos = array(
				"codigoCliente"		=> trim($codigo),
				"nombre"			=> trim($nombre),
				"tipo_documento"	=> $_POST["tipo_documento"],
				"documento"			=> trim($_POST["documento"]),
				"tipo_persona"		=> $_POST["tipo_persona"],
				"ape_paterno"		=> trim($ape_pat),
				"ape_materno"		=> trim($ape_mat),
				"nombres"			=> trim($nombres),
				"direccion"			=> trim($direccion),
				"ubigeo"			=> $_POST["ubigeo"],
				"direccion_despacho" => trim($direccionDespacho),
				"ubigeo_despacho"	=> $_POST["ubigeoDespacho"],
				"telefono"			=> trim($telefono1),
				"telefono2"			=> trim($telefono2),
				"email"				=> trim($email),
				"contacto"			=> trim($contacto),
				"vendedor"			=> $_POST["vendedor"],
				"grupo"				=> $_POST["grupo"],
				"id_zona"			=> ControladorZonasComerciales::ctrIdZonaDesdePost("id_zona"),
				"lista_precios"		=> $_POST["lista_precios"],
				"agencia"			=> $_POST["agencia"],
				"agente_retencion"	=> (isset($_POST["agente_retencion"]) && (string) $_POST["agente_retencion"] === "1") ? 1 : 0,
				"usureg"            => $usureg,
				"pcreg"             => $pcreg,
				"fecreg"            => $fecreg->format("Y-m-d H:i:s")
			);
			#var_dump("datos", $datos);

			$respuesta = ModeloClientes::mdlIngresarCliente($tabla, $datos);
			#var_dump($respuesta);
			#$respuesta = "no";

			if ($respuesta == "ok") {

				$grupoAlta = isset($_POST["grupo"]) ? trim($_POST["grupo"]) : "";
				$idCategoriaAlta = isset($_POST["categoria_comercial"]) ? (int) $_POST["categoria_comercial"] : 0;

				if ($grupoAlta !== "") {
					ControladorCategoriasClientes::ctrCerrarAsignacionEntidad("cliente", trim($codigo));
				} elseif ($idCategoriaAlta > 0) {
					ControladorCategoriasClientes::ctrAsignarCategoriaEntidad(array(
						"tipo_entidad" => "cliente",
						"codigo_entidad" => trim($codigo),
						"id_categoria" => $idCategoriaAlta,
						"motivo" => "Asignación al crear cliente",
						"es_excepcion" => 0
					));
				}

				echo '<script>

				swal({
					  type: "success",
					  title: "El cliente fue creado correctamente",
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

	static public function ctrMostrarClientes($item, $valor)
	{

		$tabla = "clientesjf";

		$respuesta = ModeloClientes::mdlMostrarClientes($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CLIENTES CUENTAS
	=============================================*/

	static public function ctrMostrarClientesCuentas($item, $valor)
	{

		$tabla = "clientesjf";

		$respuesta = ModeloClientes::mdlMostrarClientesCuentas($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CLIENTES P
	=============================================*/

	static public function ctrMostrarClientesP($item, $valor)
	{

		$tabla = "clientesjf";

		$respuesta = ModeloClientes::mdlMostrarClientesP($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	SACAR LISTA
	=============================================*/

	static public function ctrVerLista($valor)
	{

		$respuesta = ModeloClientes::mdlVerLista($valor);

		return $respuesta;
	}

	/*=============================================
	EDITAR CLIENTE
	=============================================*/

	static public function ctrEditarCliente()
	{

		if (isset($_POST["editarCodigoCliente"])) {

			$tabla = "clientesjf";
			$interruptores = array("'", '"', "´");
			$codigo 				= str_replace($interruptores, "", $_POST["editarCodigoCliente"]);
			$nombre 				= str_replace($interruptores, "", $_POST["editarNombre"]);
			$ape_pat 				= str_replace($interruptores, "", $_POST["editarApe_paterno"]);
			$ape_mat 				= str_replace($interruptores, "", $_POST["editarApe_materno"]);
			$nombres 				= str_replace($interruptores, "", $_POST["editarNombres"]);
			$direccion 				= str_replace($interruptores, "", $_POST["editarDireccion"]);
			$direccionDespacho 		= str_replace($interruptores, "", $_POST["editarDireccionDespacho"]);
			$telefono1 				= str_replace($interruptores, "", $_POST["editarTelefono"]);
			$telefono2 				= str_replace($interruptores, "", $_POST["editarTelefono2"]);
			$email 					= str_replace($interruptores, "", $_POST["editarEmail"]);
			$contacto 				= str_replace($interruptores, "", $_POST["editarContacto"]);

			if (!isset($_POST["editarUbigeoDespacho"])) {
				$editarUbigeoDespacho = '';
			} else {
				$editarUbigeoDespacho = $_POST["editarUbigeoDespacho"];
			}


			$clienteActualZona = ModeloClientes::mdlMostrarClientes("clientesjf", "codigo", trim($codigo));
			$idZonaActual = (is_array($clienteActualZona) && isset($clienteActualZona["id_zona"]))
				? $clienteActualZona["id_zona"]
				: null;

			$datos = array(
				"codigoCliente"		=> trim($codigo),
				"nombre"			=> trim($nombre),
				"tipo_documento"	=> trim($_POST["editarTipo_documento"]),
				"documento"			=> trim($_POST["editarDocumento"]),
				"tipo_persona"		=> trim($_POST["editarTipo_persona"]),
				"ape_paterno"		=> trim($ape_pat),
				"ape_materno"		=> trim($ape_mat),
				"nombres"			=> trim($nombres),
				"direccion"			=> trim($direccion),
				"direccion_despacho" => trim($direccionDespacho),
				"ubigeo"			=> trim($_POST["editarUbigeo"]),
				"ubigeo_despacho"	=> trim($editarUbigeoDespacho),
				"telefono"			=> trim($telefono1),
				"telefono2"			=> trim($telefono2),
				"email"				=> trim($email),
				"contacto"			=> trim($contacto),
				"vendedor"			=> trim($_POST["editarVendedor"]),
				"grupo"				=> trim($_POST["editarGrupo"]),
				"id_zona"			=> ControladorZonasComerciales::ctrIdZonaDesdePost("editar_id_zona", $idZonaActual),
				"lista_precios"		=> trim($_POST["editarLista_precios"]),
				"agencia"			=> trim($_POST["editarAgencia"]),
				"agente_retencion"	=> (isset($_POST["editarAgente_retencion"]) && (string) $_POST["editarAgente_retencion"] === "1") ? 1 : 0
			);
			#var_dump("datos", $datos);

			$respuesta = ModeloClientes::mdlEditarCliente($tabla, $datos);
			#var_dump($respuesta);
			#$respuesta = "false";

			if ($respuesta == "ok") {

				$grupoEdit = isset($_POST["editarGrupo"]) ? trim($_POST["editarGrupo"]) : "";
				if ($grupoEdit !== "") {
					// Si entra/permanece en grupo, la categoría individual no aplica.
					ControladorCategoriasClientes::ctrCerrarAsignacionEntidad("cliente", trim($codigo));
				} else {
					$idCategoriaEdit = isset($_POST["editar_categoria_comercial"])
						? (int) $_POST["editar_categoria_comercial"]
						: 0;

					ControladorCategoriasClientes::ctrAsignarCategoriaEntidad(array(
						"tipo_entidad" => "cliente",
						"codigo_entidad" => trim($codigo),
						"id_categoria" => $idCategoriaEdit,
						"motivo" => "Asignación al editar cliente",
						"es_excepcion" => 0
					));
				}

				echo '<script>
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

	static public function ctrEliminarCliente()
	{

		if (isset($_GET["idCliente"])) {

			$tabla = "clientesjf";
			$datos = $_GET["idCliente"];

			$respuesta = ModeloClientes::mdlEliminarCliente($tabla, $datos);

			if ($respuesta == "ok") {

				echo '<script>

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
	static public function ctrMostrarUbigeos()
	{

		$tabla = "ubigeo";

		$respuesta = ModeloClientes::mdlMostrarUbigeos($tabla);

		return $respuesta;
	}

	/*=============================================
	CREAR CLIENTES PARA PEDIDOS
	=============================================*/

	static public function ctrCrearClienteP()
	{

		if (isset($_POST["codigoCliente"])) {

			if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚüÜ ]+$/', $_POST["nombre"])) {

				$tabla = "clientesjf";

				date_default_timezone_set('America/Lima');
				$fecregP = new DateTime();

				$datos = array(
					"codigoCliente" => $_POST["codigoCliente"],
					"nombre" => $_POST["nombre"],
					"tipo_documento" => $_POST["tipo_documento"],
					"documento" => $_POST["documento"],
					"tipo_persona" => $_POST["tipo_persona"],
					"ape_paterno" => $_POST["ape_paterno"],
					"ape_materno" => $_POST["ape_materno"],
					"nombres" => $_POST["nombres"],
					"direccion" => $_POST["direccion"],
					"ubigeo" => $_POST["ubigeo"],
					"telefono" => $_POST["telefono"],
					"telefono2" => $_POST["telefono2"],
					"email" => $_POST["email"],
					"contacto" => $_POST["contacto"],
					"vendedor" => $_POST["vendedor"],
					"grupo" => $_POST["grupo"],
					"id_zona" => null,
					"lista_precios" => $_POST["lista_precios"],
					"usureg" => isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "",
					"pcreg" => gethostbyaddr($_SERVER["REMOTE_ADDR"]),
					"fecreg" => $fecregP->format("Y-m-d H:i:s"),
					"direccion_despacho" => "",
					"ubigeo_despacho" => "",
					"agencia" => ""
				);
				#var_dump("datos", $datos);

				$respuesta = ModeloClientes::mdlIngresarCliente($tabla, $datos);

				if ($respuesta == "ok") {

					echo '<script>

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
			} else {

				echo '<script>

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

	static public function ctrEditarAval()
	{

		if (isset($_POST["editarAvalNombre"])) {

			$tabla = "clientesjf";
			$datos = array(
				"codigo" => $_POST["avalCliente"],
				"aval_nombre" => $_POST["editarAvalNombre"],
				"aval_dir" => $_POST["editarAvalDir"],
				"aval_postal" => $_POST["editarAvalPostal"],
				"aval_telf" => $_POST["editarAvalTelf"],
				"aval_ruc" => $_POST["editarAvalRuc"],
				"aval_libreta" => $_POST["editarAvalLibreta"]
			);
			$respuesta = ModeloClientes::mdlEditarAval($tabla, $datos);
			// var_dump($datos);
			// var_dump($respuesta);

			if ($respuesta == "ok") {

				echo '<script>

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

	//funcion pra enviar las notificaciones
	static public function ctrEnviarNotificaciones($instancia, $contenido, $token)
	{

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => "https://apiwsp.factiliza.com/v1/message/sendtext/{$instancia}",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "$contenido",
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer {$token}",
				"Content-Type: application/json"
			],
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			return "cURL Error #:" . $err;
		} else {
			return $response;
		}
	}
}
