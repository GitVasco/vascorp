<?php

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ControladorCierres
{

	/*=============================================
	MOSTRAR CIERRES
	=============================================*/

	static public function ctrMostrarCierres($item, $valor)
	{

		$tabla = "cierresjf";

		$respuesta = ModeloCierres::mdlMostrarCierres($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CIERRES DETALLE
	=============================================*/

	static public function ctrMostrarDetallesCierres($item, $valor)
	{

		$tabla = "cierres_detallejf";

		$respuesta = ModeloCierres::mdlMostraDetallesCierres($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	CREAR CIERRE
	=============================================*/

	static public function ctrCrearCierre()
	{

		/* veriaficamos que venta traiga datos */

		if (
			isset($_POST["nuevoCierre"]) &&
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
								window.location="crear-cierre";}
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
					$item1 = "servicio";
					$valor1 = $respuestaProducto["servicio"] - $value["cantidad"];
					//ModeloArticulos::mdlActualizarUnDato($tabla, $item1, $valor1, $valor);
					$tabla2 = "servicios_detallejf";
					$item2 = "saldo";
					$valor2 =  $value["saldo"] < $value["cantidad"] ? 0 : $value["saldo"] - $value["cantidad"];
					$idServicio = $value["codServicio"];
					$saldoServicio = ModeloCierres::mdlActualizarUnDato($tabla2, $item2, $valor2, $idServicio);
				}



				# Actualizamos ultima_compra en la tabla Clientes
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();

				/* ==============================================
				GUARDAMOS LA VENTA
				============================================== */

				$datos = array(
					"codigo" => $_POST["nuevoCierre"],
					"guia" => $_POST["nuevaGuia"],
					"taller" => $_POST["seleccionarSector"],
					"usuario" => $_POST["idVendedor"],
					"total" => $_POST["totalVenta"],
					"fecha" => $_POST["nuevaFecha"],
					"estado" => "ACTIVO"
				);

				$respuesta = ModeloCierres::mdlGuardarCierres("cierresjf", $datos);

				if ($respuesta == "ok") {


					foreach ($listaProductos as $key => $value) {

						$datos = array(
							"articulo" => $value["articulo"],
							"cantidad" => $value["cantidad"],
							"inicio" => $value["cantidad"],
							"codigo" => $_POST["nuevoCierre"],
							"cod_servicio" => $value["codServicio"]
						);

						ModeloCierres::mdlGuardarDetallesCierres("cierres_detallejf", $datos);
					}

					ModeloCierres::mdlActualizarServicioTotal();

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
									window.location="cierres";}
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
									window.location="crear-cierre";}
							});
						</script>';
				}
			}
		}
	}

	/*=============================================
	EDITAR CIERRE
	=============================================*/

	public function ctrEditarCierres()
	{

		if (!(
			isset($_POST["editarCierre"]) &&
			isset($_POST["idSectorVenta"]) &&
			isset($_POST["listaProductos"])
		)) {
			return;
		}

		$codigo = $_POST["editarCierre"];
		$cierre = ModeloCierres::mdlMostrarCierres("cierresjf", "codigo", $codigo);

		if (!$cierre) {
			echo '<script>
					swal({
						type: "error",
						title: "Error",
						text: "¡No se encontró el cierre a editar!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then((result)=>{
						if(result.value){ window.location="cierres"; }
					});
				</script>';
			return;
		}

		if ($cierre["estado_pago"] == "PAGADO") {
			echo '<script>
					swal({
						type: "error",
						title: "Cierre pagado",
						text: "¡No se puede editar un cierre con estado PAGADO!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then((result)=>{
						if(result.value){ window.location="cierres"; }
					});
				</script>';
			return;
		}

		$listaProductos = json_decode($_POST["listaProductos"], true);

		if (!is_array($listaProductos) || count($listaProductos) == 0) {
			echo '<script>
					swal({
						type: "error",
						title: "Error",
						text: "¡Debe dejar al menos un artículo. Si desea quitarlos todos, elimine el cierre!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then((result)=>{
						if(result.value){
							window.location="index.php?ruta=editar-cierre&idCierre=' . $codigo . '";
						}
					});
				</script>';
			return;
		}

		foreach ($listaProductos as $value) {
			if ((int)$value["cantidad"] <= 0) {
				echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡Todas las cantidades deben ser mayores a cero!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="index.php?ruta=editar-cierre&idCierre=' . $codigo . '";
							}
						});
					</script>';
				return;
			}
		}

		$detaAnterior = ModeloCierres::mdlMostraDetallesCierres("cierres_detallejf", "codigo", $codigo);

		# Devolver saldos de las líneas anteriores
		foreach ($detaAnterior as $value) {
			$detaServ = ControladorServicios::ctrMostrarDetallesServicioUnico("id", $value["cod_servicio"]);
			if ($detaServ) {
				$nuevoSaldo = (int)$detaServ["saldo"] + (int)$value["cantidad"];
				ModeloCierres::mdlActualizarUnDato("servicios_detallejf", "saldo", $nuevoSaldo, $value["cod_servicio"]);
			}
		}

		$datos = array(
			"codigo" => $codigo,
			"guia" => $_POST["editarGuia"],
			"usuario" => $_POST["idVendedor"],
			"taller" => $_POST["idSectorVenta"],
			"total" => $_POST["totalVenta"],
			"fecha" => $cierre["fecha"]
		);

		$respuesta = ModeloCierres::mdlEditarCierres("cierresjf", $datos);

		if ($respuesta != "ok") {
			echo '<script>
					swal({
						type: "error",
						title: "Error",
						text: "¡No se pudo actualizar la cabecera del cierre. Inténtelo de nuevo!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then((result)=>{
						if(result.value){ window.location="cierres"; }
					});
				</script>';
			return;
		}

		$eliminarDeta = ModeloCierres::mdlEliminarDato("cierres_detallejf", "codigo", $codigo);

		if ($eliminarDeta != "ok") {
			echo '<script>
					swal({
						type: "error",
						title: "Error",
						text: "¡Problema al actualizar los detalles. Contacte al administrador!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then((result)=>{
						if(result.value){ window.location="cierres"; }
					});
				</script>';
			return;
		}

		foreach ($listaProductos as $value) {
			$cantidad = (int)$value["cantidad"];
			$idServicio = $value["codServicio"];

			$detaServ = ControladorServicios::ctrMostrarDetallesServicioUnico("id", $idServicio);
			$saldoActual = $detaServ ? (int)$detaServ["saldo"] : 0;
			$nuevoSaldo = $saldoActual < $cantidad ? 0 : $saldoActual - $cantidad;
			ModeloCierres::mdlActualizarUnDato("servicios_detallejf", "saldo", $nuevoSaldo, $idServicio);

			$inicio = $cantidad;
			foreach ($detaAnterior as $old) {
				if ($old["cod_servicio"] == $idServicio && isset($old["inicio"])) {
					$inicio = $old["inicio"];
					break;
				}
			}

			ModeloCierres::mdlGuardarDetallesCierres("cierres_detallejf", array(
				"codigo" => $codigo,
				"articulo" => $value["articulo"],
				"cantidad" => $cantidad,
				"inicio" => $inicio,
				"cod_servicio" => $idServicio
			));
		}

		ModeloCierres::mdlActualizarServicioTotal();

		echo '<script>
				swal({
					type: "success",
					title: "Listo",
					text: "¡El cierre se actualizó correctamente!",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then((result)=>{
					if(result.value){ window.location="cierres"; }
				});
			</script>';
	}


	/*=============================================
	ELIMINAR CIERRE
	=============================================*/

	static public function ctrEliminarCierre($codigo)
	{

		$detaCierre = ModeloCierres::mdlMostraDetallesCierres("cierres_detallejf", "codigo", $codigo);

		# Devolver cantidades al saldo del servicio detalle
		foreach ($detaCierre as $value) {
			$detaServ = ControladorServicios::ctrMostrarDetallesServicioUnico("id", $value["cod_servicio"]);
			if ($detaServ) {
				$nuevoSaldo = (int)$detaServ["saldo"] + (int)$value["cantidad"];
				ModeloCierres::mdlActualizarUnDato(
					"servicios_detallejf",
					"saldo",
					$nuevoSaldo,
					$value["cod_servicio"]
				);
			}
		}

		$respuesta = ModeloCierres::mdlEliminarDato("cierresjf", "codigo", $codigo);

		if ($respuesta == "ok") {
			ModeloCierres::mdlEliminarDato("cierres_detallejf", "codigo", $codigo);
			# Recalcula articulojf.servicio = saldos servicio + cantidades en cierres
			ModeloCierres::mdlActualizarServicioTotal();
		}

		return $respuesta;
	}

	/*=============================================
	SUMA TOTAL VENTAS
	=============================================*/

	public function ctrSumaTotalVentas()
	{

		$tabla = "cierresjf";

		$respuesta = ModeloCierres::mdlSumaTotalCierres($tabla);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR ULTIMO SERVICIOS
	=============================================*/

	static public function ctrMostrarUltimoCierre()
	{

		$tabla = "cierresjf";

		$respuesta = ModeloCierres::mdlUltimoCierre($tabla);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR ULTIMO SERVICIOS
	=============================================*/

	static public function ctrMostrarArticulosCierre($sectorCierre)
	{


		$respuesta = ModeloCierres::mdlMostrarArticulosCiere($sectorCierre);

		return $respuesta;
	}
	// VISUALIZAR CIERRE DETALLE
	static public function ctrVisualizarCierrreDetalle($valor)
	{

		$respuesta = ModeloCierres::mdlVisualizarCierreDetalle($valor);

		return $respuesta;
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function ctrRangoFechasCierres($fechaInicial, $fechaFinal)
	{

		$tabla = "cierresjf";

		$respuesta = ModeloCierres::mdlRangoFechasCierres($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function ctrRangoFechasVerCierres($fechaInicial, $fechaFinal)
	{

		$tabla = "cierresjf";

		$respuesta = ModeloCierres::mdlRangoFechasVerCierres($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
	}

	/*=============================================
	CAMBIAR FEHA CIERRE
	=============================================*/

	static public function ctrCambiarFechaCierre()
	{

		if (isset($_POST["idCierre"])) {

			$tabla = "cierresjf";
			$valor1 = $_POST["fecha"];
			$valor2 = $_POST["idCierre"];

			$respuesta = ModeloCierres::mdlActualizarUnDato($tabla, "fecha", $valor1, $valor2);


			echo '<script>

					swal({
						  type: "success",
						  title: "La fecha del cierre ha sido cambiada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "cierres";

									}
								})

					</script>';
		}
	}
}
