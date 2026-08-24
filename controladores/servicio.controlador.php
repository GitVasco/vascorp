<?php

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ControladorServicios
{

	/*=============================================
	MOSTRAR SERVICIOS
	=============================================*/

	static public function ctrMostrarServicios($item, $valor)
	{

		$tabla = "serviciosjf";

		$respuesta = ModeloServicios::mdlMostrarServicios($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR SERVICIOS
	=============================================*/

	static public function ctrMostrarDetallesServicios($item, $valor)
	{

		$tabla = "servicios_detallejf";

		$respuesta = ModeloServicios::mdlMostraDetallesServicios($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR SERVICIOS
	=============================================*/

	static public function ctrMostrarDetallesServicioUnico($item, $valor)
	{

		$tabla = "servicios_detallejf";

		$respuesta = ModeloServicios::mdlMostraDetallesServicioUnico($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	CREAR SERVICIO
	=============================================*/

	static public function ctrCrearServicio()
	{

		/* veriaficamos que venta traiga datos */

		if (
			isset($_POST["nuevoServicio"]) &&
			isset($_POST["seleccionarSector"]) &&
			isset($_POST["listaProductos"])
		) {

			/* alerta  si la lista de productos viene vacia  */

			if ($_POST["listaProductos"] == "") {
				# Mostramos una alerta suave
				echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡No se seleccionó ningún articulo. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="crear-servicio";}
						});
					</script>';
			} else {

				# Modificamos la información de los productos comprados en un array

				$listaProductos = json_decode($_POST["listaProductos"], true);

				$comprasTotales = 0;

				foreach ($listaProductos as $key => $value) {

					$tabla = "articulojf";
					$valor = $value["articulo"];
					$respuestaProducto = ModeloArticulos::mdlMostrarArticulos($valor);
					$item1 = "taller";
					$valor1 = $respuestaProducto["taller"] - $value["cantidad"];
					ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);
					$item2 = "servicio";
					$valor2 = $respuestaProducto["servicio"] + $value["cantidad"];
					ModeloArticulos::mdlActualizarUnDato($tabla, $item2, $valor2, $valor);
				}



				# Actualizamos ultima_compra en la tabla Clientes
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();

				/* ==============================================
				GUARDAMOS LA VENTA
				============================================== */

				$datos = array(
					"codigo" => $_POST["nuevoServicio"],
					"taller" => $_POST["seleccionarSector"],
					"usuario" => $_POST["idVendedor"],
					"total" => $_POST["totalVenta"],
					"fecha" => $fecha->format("Y-m-d H:i:s"),
					"estado" => "ACTIVO"
				);

				$respuesta = ModeloServicios::mdlGuardarServicios("serviciosjf", $datos);

				if ($respuesta == "ok") {


					foreach ($listaProductos as $key => $value) {

						$datos = array(
							"articulo" => $value["articulo"],
							"cantidad" => $value["cantidad"],
							"codigo" => $_POST["nuevoServicio"],
							"saldo" => $value["cantidad"]
						);

						ModeloServicios::mdlGuardarDetallesServicios("servicios_detallejf", $datos);
					}

					# Mostramos una alerta suave
					echo '<script>
							swal({
								type: "success",
								title: "Felicitaciones",
								text: "¡La información fue registrada con éxito!",
								showConfirmButton: true,
								confirmButtonText: "Cerrar"
							}).then((result)=>{
								if(result.value){
									window.location="servicios";}
							});
						</script>';
				} else {

					# Mostramos una alerta suave
					echo '<script>
							swal({
								type: "error",
								title: "Error",
								text: "¡La información presento problemas y no se registro adecuadamente. Por favor, intenteló de nuevo!",
								showConfirmButton: true,
								confirmButtonText: "Cerrar"
							}).then((result)=>{
								if(result.value){
									window.location="crear-servicio";}
							});
						</script>';
				}
			}
		}
	}

	/*=============================================
	EDITAR VENTA
	=============================================*/

	public function ctrEditarServicios()
	{

		if (isset($_POST["editarServicio"]) && isset($_POST["idSectorVenta"]) && isset($_POST["listaProductos"])) {

			# Formateamos la tabla de Productos y de Clientes
			# Traemos los detalles asociados a la venta a editar

			$detaProductos = ModeloServicios::mdlMostraDetallesServicios("servicios_detallejf", "codigo", $_POST["editarServicio"]);

			# Cambiamos los id de la lista por los id de los Productos
			foreach ($detaProductos as $key => $value) {

				$infoPro = controladorArticulos::ctrMostrarArticulos($value["articulo"]);
				$detaProductos[$key]["articulo"] = $infoPro["articulo"];
			}

			if ($_POST["listaProductos"] == "") {

				$listaProductos = $detaProductos;
				$validarCambio = false;
			} else {

				$listaProductos = json_decode($_POST["listaProductos"], true);
				$validarCambio = true;
			}

			if ($validarCambio) {


				foreach ($listaProductos as $key => $value) {
					# Traemos los productos por ID en cada interacción
					$valor = $value["articulo"];

					$respuestaProducto = ModeloArticulos::mdlMostrarArticulos($valor);


					# Actualizamos las ventas en la tabla productos
					$item1 = "taller";
					$valor1 = $value["taller"] - $value["cantidad"];

					ModeloArticulos::mdlActualizarUnDato("articulojf", $item1, $valor1, $valor);
					$item2 = "servicio";
					$valor2 = $value["servicio"] + $value["cantidad"];
					ModeloArticulos::mdlActualizarUnDato("articulojf", $item2, $valor2, $valor);
				}


				# Actualizamos ultima_compra en la tabla Clientes
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();
			}

			/* ==============================================
			EDITAMOS LOS CAMBIOS DE LA VENTA listaMetodoPago
			============================================== */
			$datos = array(
				"codigo" => $_POST["editarServicio"],
				"usuario" => $_POST["idVendedor"],
				"taller" => $_POST["idSectorVenta"],
				"total" => $_POST["totalVenta"],
				"fecha" => $fecha->format("Y-m-d H:i:s")
			);


			$respuesta = ModeloServicios::mdlEditarServicios("serviciosjf", $datos);


			/* var_dump("datos", $datos); */

			if ($respuesta == "ok") {

				# Eliminamos los detalles de la venta
				$eliminarDeta = ModeloServicios::mdlEliminarDato("servicios_detallejf", "codigo", $_POST["editarServicio"]);

				if ($eliminarDeta == "ok") {

					# Guardamos los nuevos detalles de la venta
					foreach ($listaProductos as $key => $value) {

						$datos = array(
							"codigo" => $_POST["editarServicio"],
							"articulo" => $value["articulo"],
							"cantidad" => $value["cantidad"],
							"saldo" => $value["cantidad"]
						);

						ModeloServicios::mdlGuardarDetallesServicios("servicios_detallejf", $datos);
					}
					# Mostramos una alerta suave
					echo '<script>
							swal({
								type: "success",
								title: "Felicitaciones",
								text: "¡La información fue Actualizada con éxito!",
								showConfirmButton: true,
								confirmButtonText: "Cerrar"
							}).then((result)=>{
								if(result.value){
									window.location="servicios";}
							});
						</script>';
				} else {
					# Mostramos una alerta suave
					echo '<script>
							swal({
								type: "error",
								title: "Error",
								text: "¡La información presento problemas al actualizar los Detalles. Por favor, comunicarse con el Administrador de la Base de Datos!",
								showConfirmButton: true,
								confirmButtonText: "Cerrar"
							}).then((result)=>{
								if(result.value){
									window.location="servicios";}
							});
						</script>';
				}
			} else {
				# Mostramos una alerta suave
				echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡La información presento problemas y no se actualizó adecuadamente. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="servicios";}
						});
					</script>';
			}
		}
	}


	/*=============================================
	ELIMINAR SERVICIO
	=============================================*/

	static public function ctrEliminarServicio($codigo)
	{

		$item = "codigo";
		$infoServicio = ModeloServicios::mdlMostrarServicios("serviciosjf", $item, $codigo);


		$detaServicio = ModeloServicios::mdlMostraDetallesServicios("servicios_detallejf", "codigo", $codigo);


		/* 
        todo: Actualizamos orden de corte en Articulojf
        */
		foreach ($detaServicio as $key => $value) {

			$valorA = $value["articulo"];

			$infoA = ModeloArticulos::mdlMostrarArticulos($valorA);
			// var_dump("infoA", $infoA);
			#var_dump("infoA", $infoA["ord_corte"]);
			#var_dump("cantidad", $value["cantidad"]);

			$taller = $infoA["taller"] + $value["cantidad"];
			#var_dump("ord_corte", $ord_corte);

			ModeloArticulos::mdlActualizarUnDato("articulojf", "taller", $taller, $value["articulo"]);

			$servicio = $infoA["servicio"] - $value["cantidad"];
			ModeloArticulos::mdlActualizarUnDato("articulojf", "servicio", $servicio, $value["articulo"]);
		}

		/* 
        todo: Eliminamos la cabecera de Orden de corte
        */
		$tablaServicio = "serviciosjf";
		$itemServicio = "codigo";
		$valorServicio = $codigo;

		$respuesta = ModeloServicios::mdlEliminarDato($tablaServicio, $itemServicio, $valorServicio);

		if ($respuesta == "ok") {

			/* 
            todo: Eliminamos el detalle de Orden de corte
            */
			$tablaDSer = "servicios_detallejf";
			$itemDSer = "codigo";
			$valorDSer = $codigo;

			ModeloServicios::mdlEliminarDato($tablaDSer, $itemDSer, $valorDSer);
		}

		return $respuesta;
	}

	/*=============================================
	SUMA TOTAL VENTAS
	=============================================*/

	public function ctrSumaTotalVentas()
	{

		$tabla = "serviciosjf";

		$respuesta = ModeloServicios::mdlSumaTotalServicios($tabla);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR ULTIMO SERVICIOS
	=============================================*/

	static public function ctrMostrarUltimoServicio()
	{

		$tabla = "serviciosjf";

		$respuesta = ModeloServicios::mdlUltimoServicio($tabla);

		return $respuesta;
	}


	/*=============================================
	CREAR PRECIO SERVICIO
	=============================================*/

	static public function ctrCrearPrecioServicio()
	{

		if (isset($_POST["nuevoPrecioDocenaServicio"])) {

			$tabla = "precio_serviciojf";
			$datos = array(
				"taller" => $_POST["nuevoTallerPrecio"],
				"modelo" => $_POST["nuevoModeloPrecio"],
				"precio_doc" => $_POST["nuevoPrecioDocenaServicio"]
			);

			$respuesta = ModeloServicios::mdlIngresarPrecioServicio($tabla, $datos);

			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "El precio servicio ha sido guardado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "precio-servicio";

									}
								})

					</script>';
			}
		}
	}


	/*=============================================
	MOSTRAR PRECIO DE SERVICIOS
	=============================================*/

	static public function ctrMostrarPrecioServicios($item, $valor)
	{
		$tabla = "precio_serviciojf";
		$respuesta = ModeloServicios::mdlMostrarPrecioServicios($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	EDITAR PRECIO SERVICIO
	=============================================*/

	static public function ctrEditarPrecioServicio()
	{

		if (isset($_POST["editarPrecioDocenaServicio"])) {

			$tabla = "precio_serviciojf";

			$datos = array(
				"id" => $_POST["idPrecioServicio"],
				"taller" => $_POST["editarTallerPrecio"],
				"modelo" => $_POST["editarModeloPrecio"],
				"precio_doc" => $_POST["editarPrecioDocenaServicio"]
			);

			$respuesta = ModeloServicios::mdlEditarPrecioServicio($tabla, $datos);

			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "El precio servicio ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "precio-servicio";

									}
								})

					</script>';
			}
		}
	}

	/*=============================================
	ELIMINAR PRECIO SERVICIO
	=============================================*/

	static public function ctrEliminarPrecioServicio()
	{

		if (isset($_GET["idPrecioServicio"])) {

			$datos = $_GET["idPrecioServicio"];
			$tabla = "precio_serviciojf";
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$precios = ControladorServicios::ctrMostrarPrecioServicios("id", $datos);
			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino un precio servicio';
			$descripcion   = 'El usuario ' . $usuario . ' elimino el precio servicio ' . $precios["taller"] . ' - ' . $precios["modelo"];
			$de = 'From: notificacionesvascorp@gmail.com';
			if ($_SESSION["correo"] == 1) {
				mail($para, $asunto, $descripcion, $de);
			}
			if ($_SESSION["datos"] == 1) {
				$datos2 = array(
					"usuario" => $usuario,
					"concepto" => $descripcion,
					"fecha" => $fecha->format("Y-m-d H:i:s")
				);
				$auditoria = ModeloUsuarios::mdlIngresarAuditoria("auditoriajf", $datos2);
			}

			$respuesta = ModeloServicios::mdlEliminarPrecioServicio($tabla, $datos);
			if ($respuesta == "ok") {


				echo '<script>

				swal({
					  type: "success",
					  title: "El precio servicio ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "precio-servicio";

								}
							})

				</script>';
			}
		}
	}


	// VISUALIZAR CIERRE DETALLE
	static public function ctrVisualizarServicioDetalle($valor)
	{

		$respuesta = ModeloServicios::mdlVisualizarServicioDetalle($valor);

		return $respuesta;
	}

	/* 
    *MOSTRAR PAGO SERVICIOS SEMANALES
    */
	static public function ctrMostrarPagoServicios($valor)
	{

		$respuesta = ModeloServicios::mdlMostrarPagoServicios($valor);

		return $respuesta;
	}

	/* 
    *MOSTRAR PAGO SERVICIOS SEMANALES
    */
	static public function ctrMostrarEtiquetas($id, $sector)
	{

		$respuesta = ModeloServicios::mdlMostrarEtiquetas($id, $sector);

		return $respuesta;
	}

	static public function ctrVerPagoServicios($inicio, $fin)
	{

		$respuesta = ModeloServicios::mdlVerPagoServicios($inicio, $fin);

		return $respuesta;
	}

	static public function ctrVerSectores($inicio, $fin)
	{

		$respuesta = ModeloServicios::mdlVerSectores($inicio, $fin);

		return $respuesta;
	}

	static public function ctrVerTotalPagar($inicio, $fin, $sector)
	{

		$respuesta = ModeloServicios::mdlVerTotalPagar($inicio, $fin, $sector);

		return $respuesta;
	}

	static public function ctrVerPagoServicioSector($inicio, $fin, $sector)
	{

		$respuesta = ModeloServicios::mdlVerPagoServicioSector($inicio, $fin, $sector);

		return $respuesta;
	}

	static public function ctrVerSumaPagos($inicio, $fin, $sector)
	{

		$respuesta = ModeloServicios::mdlVerSumaPagos($inicio, $fin, $sector);

		return $respuesta;
	}

	/* 
	* CREAR QUINCENA
	*/
	static public function ctrCrearPagoServicios()
	{

		if (isset($_POST["mes"])) {

			$datos = array(
				"ano" => $_POST["año"],
				"mes" => $_POST["mes"],
				"inicio" => $_POST["inicio"],
				"fin" => $_POST["fin"],
				"usuario" => $_POST["usuario"]
			);
			//var_dump($datos);

			$respuesta = ModeloServicios::mdlCrearPagoServicio($datos);

			if ($respuesta == "ok") {

				echo '<script>

                    swal({
                          type: "success",
                          title: "El pago servicio ha sido guardada correctamente",
                          showConfirmButton: true,
                          confirmButtonText: "Cerrar"
                          }).then(function(result){
                                    if (result.value) {

                                    window.location = "pago-servicio";

                                    }
                                })

                    </script>';
			}
		}
	}

	/* 
    *EDITAR QUINCENA
    */

	static public function ctrEditarPagoServicio()
	{

		if (isset($_POST["editarMes"])) {

			$datos = array(
				"id" => $_POST["id"],
				"ano" => $_POST["editarAño"],
				"mes" => $_POST["editarMes"],
				"inicio" => $_POST["editarInicio"],
				"fin" => $_POST["editarFin"],
				"usuario" => $_POST["editarUsuario"]
			);
			// var_dump($datos);

			$respuesta = ModeloServicios::mdlEditarPagoServicio($datos);

			if ($respuesta == "ok") {

				echo '<script>

                swal({
                      type: "success",
                      title: "El pago servicio ha sido cambiado correctamente",
                      showConfirmButton: true,
                      confirmButtonText: "Cerrar"
                      }).then(function(result){
                                if (result.value) {

                                window.location = "pago-servicio";

                                }
                            })

                </script>';
			}
		}
	}

	static public function ctrEliminarPagoServicio()
	{

		if (isset($_GET["idPagoServicio"])) {

			//var_dump($_GET["idQuincena"]);

			$id = $_GET["idPagoServicio"];

			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$precios = ControladorServicios::ctrMostrarPagoServicios("id", $id);
			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino un precio servicio';
			$descripcion   = 'El usuario ' . $usuario . ' elimino el pago servicio ' . $precios["ano"] . ' - ' . $precios["mes"];
			$de = 'From: notificacionesvascorp@gmail.com';
			if ($_SESSION["correo"] == 1) {
				mail($para, $asunto, $descripcion, $de);
			}
			if ($_SESSION["datos"] == 1) {
				$datos2 = array(
					"usuario" => $usuario,
					"concepto" => $descripcion,
					"fecha" => $fecha->format("Y-m-d H:i:s")
				);
				$auditoria = ModeloUsuarios::mdlIngresarAuditoria("auditoriajf", $datos2);
			}

			$respuesta = ModeloServicios::mdlEliminarPagoServicio($id);

			if ($respuesta == "ok") {

				//var_dump($respuesta);

				echo '<script>

				swal({
					  type: "success",
					  title: "La pago servicio ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "pago-servicio";

								}
							})

				</script>';
			}
		}
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function ctrRangoFechasServicios($fechaInicial, $fechaFinal)
	{

		$tabla = "serviciosjf";

		$respuesta = ModeloServicios::mdlRangoFechasServicios($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function ctrRangoFechasVerServicios($fechaInicial, $fechaFinal)
	{

		$tabla = "serviciosjf";

		$respuesta = ModeloServicios::mdlRangoFechasVerServicios($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
	}

	/*=============================================
	EDITAR ETIQUETA
	=============================================*/

	static public function ctrEditarEtiqueta()
	{

		if (isset($_POST["id2"])) {

			$datos = array(
				"id" => $_POST["id2"],
				"taller1" => $_POST["taller1"],
				"taller2" => $_POST["taller2"],
				"taller3" => $_POST["taller3"],
				"taller4" => $_POST["taller4"],
				"taller5" => $_POST["taller5"],
				"taller6" => $_POST["taller6"],
				"taller7" => $_POST["taller7"],
				"taller8" => $_POST["taller8"],
				"taller9" => $_POST["taller9"],
				"taller10" => $_POST["taller10"],
				"taller11" => $_POST["taller11"],
				"taller12" => $_POST["taller12"],
				"taller13" => $_POST["taller13"],
				"taller14" => $_POST["taller14"],
				"taller15" => $_POST["taller15"]
			);

			$respuesta = ModeloServicios::mdlEditarEtiqueta($datos);

			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "La etiqueta del pago ha sido guardada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "pago-servicio";

									}
								})

					</script>';
			}
		}
	}

	/***************************************
	 * Actualizar guia en Servicios
	 ***************************************/
	static public function ctrActualizarGuiaServicio()
	{

		if (isset($_POST["servicio"])) {

			$servicio = $_POST["servicio"];
			$guia = $_POST["guia"];

			$datos = array(
				"codigo" => $servicio,
				"guia" => $guia
			);

			$respuesta = ModeloServicios::mdlActualizarGuia($datos);

			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "La guia del servicio ha sido guardada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "servicios";

									}
								})

					</script>';
			}
		}
	}

	static public function ctrAniosHistorialServicios()
	{
		$anios = ModeloServicios::mdlAniosHistorialServicios();
		if (!is_array($anios)) {
			$anios = array();
		}

		$actual = (int) date("Y");
		if (!in_array($actual, $anios, true)) {
			array_unshift($anios, $actual);
		}

		if (count($anios) === 0) {
			return array($actual);
		}

		rsort($anios, SORT_NUMERIC);

		return $anios;
	}

	static public function ctrHistorialAnualServicios($anio)
	{
		return ModeloServicios::mdlHistorialAnualServicios($anio);
	}

	static public function ctrHistorialCabeceraServicios($anio)
	{
		$data = ModeloServicios::mdlHistorialAnualServicios($anio);
		return $data["cabeceras"];
	}

	static public function ctrHistorialDetalleServicios($anio)
	{
		$data = ModeloServicios::mdlHistorialAnualServicios($anio);
		return $data["detalles"];
	}
}
