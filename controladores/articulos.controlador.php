<?php

class controladorArticulos
{

	/* 
	* MOSTRAR ARTICULOS
	*/
	static public function ctrMostrarArticulos($valor)
	{

		$tabla = "articulojf";

		$respuesta = ModeloArticulos::mdlMostrarArticulos($valor);

		return $respuesta;
	}

	/**
	 * Estadísticas de cabecera del módulo artículos.
	 */
	static public function ctrEstadisticasArticulos()
	{
		return ModeloArticulos::mdlEstadisticasArticulos();
	}

	/* 
	* MOSTRAR SIN TARJETA
	*/
	static public function ctrMostrarSinTarjeta()
	{

		$respuesta = ModeloArticulos::mdlMostrarSinTarjeta();

		return $respuesta;
	}

	/* 
	* MOSTRAR CANTIDAD DE PEDIDOS
	*/
	static public function ctrArticulosPedidos()
	{

		$respuesta = ModeloArticulos::mdlArticulosPedidos();

		return $respuesta;
	}

	/* 
	* MOSTRAR CANTIDAD DE FALTANTES
	*/
	static public function ctrArticulosFaltantes()
	{

		$tabla = "articulojf";

		$respuesta = ModeloArticulos::mdlArticulosFaltantes($tabla);

		return $respuesta;
	}

	/* 
	* MOSTRAR ARTICULOS
	*/
	static public function ctrMostrarArticulosServicio()
	{


		$respuesta = ModeloArticulos::mdlMostrarArticulosServicio();

		return $respuesta;
	}

	/* 
	* MOSTRAR ARTICULOS
	*/
	static public function ctrMostrarMPOC()
	{


		$respuesta = ModeloArticulos::mdlMostrarMPOC();

		return $respuesta;
	}

	/* 
	* MOSTRAR ARTICULOS
	*/
	static public function ctrMostrarArticulosTicket()
	{


		$respuesta = ModeloArticulos::mdlMostrarArticulosTicket();

		return $respuesta;
	}
	/* 
	* CREAR ARTICULO
	*/
	static public function ctrCrearArticulo()
	{

		if (isset($_POST["nuevoModelo"])) {

			if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoModelo"])) {

				$tabla = "articulojf";
				$codigo = "";
				$datos = array(
					"id_marca" => $_POST["nuevaMarca"],
					"modelo" => $_POST["nuevoModelo"],
					"descripcion" => $_POST["nuevaDescripcion"],
					"cod_color" => $_POST["nuevoColor"],
					"cod_talla" => $_POST["nuevaTalla"],
					"tipo" => $_POST["nuevoTipo"],
					"articulo" => $codigo,
					"color" => $_POST["color"],
					"talla" => $_POST["talla"],
					"imagen" => null
				);

				$respuesta = ModeloArticulos::mdlIngresarArticulo($tabla, $datos);

				if ($respuesta == "ok") {

					echo '<script>

						swal({
							  type: "success",
							  title: "El articulo ha sido guardado correctamente",
							  showConfirmButton: true,
							  confirmButtonText: "Cerrar"
							  }).then(function(result){
										if (result.value) {

										window.location = "articulos";

										}
									})

						</script>';
				}
			} else {

				echo '<script>

					swal({
						  type: "error",
						  title: "¡El articulo no puede ir con los campos vacíos o llevar caracteres especiales!",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
							if (result.value) {

							window.location = "articulos";

							}
						})

			  	</script>';
			}
		}
	}

	/* 
	* CREAR ARTICULO x MODELO
	*/
	static public function ctrCrearArticuloModelo()
	{

		if (isset($_POST["nuevoModelo"])) {

			if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoModelo"])) {
				$tablaModelo = "modelojf";
				$valorModelo = $_SESSION["modelos"];
				if (empty($_POST["descuentos"])) {
					$descuentos = 0;
				} else {
					$descuentos = $_POST["descuentos"];
				}
				if (empty($_POST["precios"])) {
					$precios = 0;
				} else {
					$precios = $_POST["precios"];
				}
				if (empty($_POST["efectosDesc"])) {
					$efectosDesc = 0;
				} else {
					$efectosDesc = $_POST["efectosDesc"];
				}
				if (empty($_POST["efectosIGV"])) {
					$efectosIGV = 0;
				} else {
					$efectosIGV = $_POST["efectosIGV"];
				}
				$datosModelo = array(
					"modelo" => $valorModelo,
					"descuentos" => $descuentos,
					"precios" => $precios,
					"efectos_desc" => $efectosDesc,
					"efectos_igv" => $efectosIGV,
					"articulos" => 1
				);
				ModeloModelos::mdlModeloPrecios($tablaModelo, $datosModelo);

				$variantes = json_decode(isset($_POST["listaVariantes"]) ? $_POST["listaVariantes"] : "[]", true);
				if (!is_array($variantes) || count($variantes) === 0) {
					// Compatibilidad: colores × tallas del formulario clásico
					$colores = json_decode(isset($_POST["listaColores"]) ? $_POST["listaColores"] : "[]", true);
					$arregloCHK = isset($_POST["chk"]) ? $_POST["chk"] : array();
					$variantes = array();
					if (is_array($colores) && is_array($arregloCHK)) {
						foreach ($arregloCHK as $codTalla) {
							$talla = ModeloModelos::mdlMostrarTallaGrupo("tallajf", "cod_talla", $codTalla, $_POST["nuevoGrupoTalla"]);
							if (empty($talla)) {
								continue;
							}
							foreach ($colores as $value) {
								$variantes[] = array(
									"codigo" => $value["codigo"],
									"descripcion" => $value["descripcion"],
									"cod_talla" => $talla["cod_talla"],
									"talla" => $talla["talla"]
								);
							}
						}
					}
				}

				$creados = 0;
				$omitidos = 0;
				$errores = 0;

				foreach ($variantes as $value) {
					if (empty($value["codigo"]) || empty($value["cod_talla"])) {
						continue;
					}
					$codigo = $_POST["nuevoModelo"] . $value["codigo"] . $value["cod_talla"];
					$datos = array(
						"id_marca" => $_POST["nuevaMarca"],
						"marca" => $_POST["nuevaDescripcionMarca"],
						"modelo" => $_POST["nuevoModelo"],
						"descripcion" => $_POST["nuevaDescripcion"],
						"cod_color" => $value["codigo"],
						"cod_talla" => $value["cod_talla"],
						"articulo" => $codigo,
						"color" => $value["descripcion"],
						"talla" => $value["talla"]
					);
					$existeArticulo = ModeloArticulos::mdlMostrarArticulos($codigo);

					if (!empty($existeArticulo)) {
						$omitidos++;
						continue;
					}

					$respuesta = ModeloArticulos::mdlIngresarArticulo("articulojf", $datos);
					if ($respuesta == "ok") {
						$creados++;
					} else {
						$errores++;
					}
				}

				if ($creados > 0 && $errores === 0) {
					$titulo = "Se crearon " . $creados . " artículo(s).";
					if ($omitidos > 0) {
						$titulo .= " " . $omitidos . " ya existían y se omitieron.";
					}
					$tipo = "success";
				} elseif ($creados === 0 && $omitidos > 0 && $errores === 0) {
					$titulo = "No había nada nuevo: " . $omitidos . " combinación(es) ya existían.";
					$tipo = "info";
				} elseif ($creados === 0 && $omitidos === 0) {
					$titulo = "Selecciona al menos un color o talla nueva para crear.";
					$tipo = "warning";
				} else {
					$titulo = "Se crearon " . $creados . ". Omitidos: " . $omitidos . ". Errores: " . $errores . ".";
					$tipo = $creados > 0 ? "warning" : "error";
				}

				echo '<script>
					swal({
						icon: "' . $tipo . '",
						title: "' . addslashes($titulo) . '",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then(function(result){
						if (result.value) {
							window.location = "modelosjf";
						}
					});
				</script>';
			} else {

				echo '<script>

					swal({
						  icon: "error",
						  title: "¡Datos del modelo inválidos!",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
							if (result.value) {

							window.location = "modelosjf";

							}
						})

			  	</script>';
			}
		}
	}

	/* 
	* EDITAR ARTICULO
	*/
	static public function ctrEditarArticulo()
	{

		if (isset($_POST["editarDescripcion"])) {

			if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarDescripcion"])) {

				/*=============================================
				VALIDAR IMAGEN
				=============================================*/

				$ruta = $_POST["imagenActual"];

				if (isset($_FILES["editarImagen"]["tmp_name"]) && !empty($_FILES["editarImagen"]["tmp_name"])) {

					list($ancho, $alto) = getimagesize($_FILES["editarImagen"]["tmp_name"]);

					$nuevoAncho = 500;
					$nuevoAlto = 500;

					/*=============================================
					CREAMOS EL DIRECTORIO DONDE VAMOS A GUARDAR LA FOTO DEL USUARIO
					=============================================*/

					$directorio = "vistas/img/articulos/" . $_POST["editarCodigo"];

					/*=============================================
					PRIMERO PREGUNTAMOS SI EXISTE OTRA IMAGEN EN LA BD
					=============================================*/

					if (!empty($_POST["imagenActual"]) && $_POST["imagenActual"] != "vistas/img/articulos/default/anonymous.png") {

						unlink($_POST["imagenActual"]);
					} else {

						mkdir($directorio, 0755);
					}

					/*=============================================
					DE ACUERDO AL TIPO DE IMAGEN APLICAMOS LAS FUNCIONES POR DEFECTO DE PHP
					=============================================*/

					if ($_FILES["editarImagen"]["type"] == "image/jpeg") {

						/*=============================================
						GUARDAMOS LA IMAGEN EN EL DIRECTORIO
						=============================================*/

						$aleatorio = mt_rand(100, 999);

						$ruta = "vistas/img/articulos/" . $_POST["editarCodigo"] . "/" . $aleatorio . ".jpg";

						$origen = imagecreatefromjpeg($_FILES["editarImagen"]["tmp_name"]);

						$destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

						imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

						imagejpeg($destino, $ruta);
					}

					if ($_FILES["editarImagen"]["type"] == "image/png") {

						/*=============================================
						GUARDAMOS LA IMAGEN EN EL DIRECTORIO
						=============================================*/

						$aleatorio = mt_rand(100, 999);

						$ruta = "vistas/img/articulos/" . $_POST["editarCodigo"] . "/" . $aleatorio . ".png";

						$origen = imagecreatefrompng($_FILES["editarImagen"]["tmp_name"]);

						$destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

						imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

						imagepng($destino, $ruta);
					}
				}

				$datos = array(
					"descripcion" => $_POST["editarDescripcion"],
					"articulo" => $_POST["editarCodigo"],
					"imagen" => $ruta
				);

				$respuesta = ModeloArticulos::mdlEditarArticulo($datos);

				if ($respuesta == "ok") {

					echo '<script>

						swal({
							type: "success",
							title: "El articulo ha sido editado correctamente",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
										if (result.value) {

										window.location = "articulos";

										}
									})

						</script>';
				}
			} else {

				echo '<script>

					swal({
						type: "error",
						title: "¡El articulo no puede ir con los campos vacíos o llevar caracteres especiales!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
							if (result.value) {

							window.location = "articulos";

							}
						})

				</script>';
			}
		}
	}

	/* 
	* BORRAR ARTICULO
	*/
	static public function ctrEliminarArticulo()
	{

		if (isset($_GET["idArticulo"])) {

			$datos = $_GET["idArticulo"];

			if ($_GET["imagen"] != "" && $_GET["imagen"] != "vistas/img/articulos/default/anonymous.png") {

				unlink($_GET["imagen"]);
				rmdir('vistas/img/articulos/' . $_GET["articulo"]);
			}

			$respuesta = ModeloArticulos::mdlEliminarArticulo($datos);

			if ($respuesta == "ok") {

				echo '<script>

				swal({
					  type: "success",
					  title: "El articulo ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "articulos";

								}
							})

				</script>';
			}
		}
	}

	/* 
	* SACAR CONFIGURACION DE URGENCIAS
	*/
	static public function ctrConfiguracion()
	{

		$respuesta = ModeloArticulos::mdlConfiguracion();

		return $respuesta;
	}

	/* 
    * CONFIGURAR PORCENTAJE SALTA A CREAR OC
    */
	static public function ctrConfigurarUrgencia()
	{

		if (isset($_POST["urgencia"])) {

			$dato = $_POST["urgencia"];

			// var_dump("dato", $dato);

			$respuesta = ModeloArticulos::mdlConfigurarUrgencia($dato);

			if ($respuesta == "ok") {

				echo	'<script>

							swal({
								type: "success",
								title: "El porcentaje de urgencias ha sido configurado correctamente",
								showConfirmButton: true,
								confirmButtonText: "Cerrar"
								}).then(function(result){
											if (result.value) {

											window.location = "crear-ordencorte";

											}
										})

						</script>';
			}
		}
	}

	/* 
    * CONFIGURAR PORCENTAJE SALTA A CREAR OC
    */
	static public function ctrConfigurarUrgenciaLista()
	{

		if (isset($_POST["urgencia"])) {

			$dato = $_POST["urgencia"];

			// var_dump("dato", $dato);

			$respuesta = ModeloArticulos::mdlConfigurarUrgencia($dato);

			if ($respuesta == "ok") {

				echo	'<script>

							swal({
								type: "success",
								title: "El porcentaje de urgencias ha sido configurado correctamente",
								showConfirmButton: true,
								confirmButtonText: "Cerrar"
								}).then(function(result){
											if (result.value) {

											window.location = "urgencias";

											}
										})

						</script>';
			}
		}
	}

	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA DE ORDENES DE CORTE
	*/
	static public function ctrMostrarArticulosUrgencia()
	{

		$respuesta = ModeloArticulos::mdlMostrarArticulosUrgencia();

		return $respuesta;
	}
	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA DE ORDENES DE CORTE
	*/
	static public function ctrMostrarArticulosTaller($sectorIngreso)
	{

		$respuesta = ModeloArticulos::mdlMostrarArticulosTaller($sectorIngreso);

		return $respuesta;
	}

	/**
	 * Ingresos multi (vista prueba): internos en una query con modelojf.
	 */
	static public function ctrIngresosMultiArticulosInternos()
	{
		return ModeloArticulos::mdlIngresosMultiArticulosInternos();
	}

	/**
	 * Ingresos multi: externos — cierres en IN + articulojf si aplica.
	 */
	static public function ctrIngresosMultiArticulosExternos($sectoresExternos)
	{
		return ModeloArticulos::mdlIngresosMultiArticulosExternos($sectoresExternos);
	}


	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA DE ORDENES DE CORTE - PRODUCCION
	*/
	static public function ctrMostrarProduccion($valor)
	{

		$respuesta = ModeloArticulos::mdlMostrarProduccion($valor);

		return $respuesta;
	}


	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA DE ORDENES DE CORTE - VENTAS
	*/
	static public function ctrMostrarVentas($valor)
	{

		$respuesta = ModeloArticulos::mdlMostrarVentas($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA URGENCIA
	*/
	static public function ctrMostrarUrgencia($valor, $modelo)
	{

		$tabla = "articulojf";

		$respuesta = ModeloArticulos::mdlMostrarUrgencia($tabla, $valor, $modelo);

		return $respuesta;
	}


	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA URGENCIA
	*/
	static public function ctrMostrarUrgenciaMaestro($tipo, $mes)
	{

		$tabla = "articulojf";

		$respuesta = ModeloArticulos::mdlMostrarUrgenciaMaestro($tipo, $mes);

		return $respuesta;
	}

	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA URGENCIA
	*/
	static public function ctrMostrarUrgenciaMaestroTotal($tipo, $mes)
	{

		$tabla = "articulojf";

		$respuesta = ModeloArticulos::mdlMostrarUrgenciaMaestroTotal($tipo, $mes);

		return $respuesta;
	}


	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA SEGUIMIENTO
	*/
	static public function ctrMostrarSeguimiento($valor)
	{

		$respuesta = ModeloArticulos::mdlMostrarSeguimiento($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR MP DETALLE PARA LA TABLA URGENCIA
	*/
	static public function ctrVisualizarUrgenciasDetalle($valor)
	{

		$respuesta = ModeloArticulos::mdlVisualizarUrgenciasDetalle($valor);

		return $respuesta;
	}


	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA URGENCIA - DETALLE
	*/
	static public function ctrListaArticulosPedidos()
	{

		$respuesta = ModeloArticulos::mdlListaArticulosPedidos();

		return $respuesta;
	}

	/* 
	* MOSTRAR  COLORES
	*/
	static public function ctrVerColores($valor)
	{

		$respuesta = ModeloArticulos::mdlVerColores($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR  COLORES y cantidades
	*/
	static public function ctrVerColoresCantidades($pedido, $modelo)
	{

		$respuesta = ModeloArticulos::mdlVerColoresCantidades($pedido, $modelo);

		return $respuesta;
	}



	/* 
	* MOSTRAR  COLORES y cantidades
	*/
	static public function ctrVerColoresCantidadesB($pedido, $modelo)
	{

		$respuesta = ModeloArticulos::mdlVerColoresCantidadesB($pedido, $modelo);

		return $respuesta;
	}

	/* 
	* MOSTRAR  ARTICULOS
	*/
	static public function ctrVerArticulos($valor)
	{

		$respuesta = ModeloArticulos::mdlVerArticulos($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR  ARTICULOS
	*/
	static public function ctrVerArticulosB($valor)
	{

		$respuesta = ModeloArticulos::mdlVerArticulosB($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR  ARTICULOS
	*/
	static public function ctrVerPrecios($modelo, $lista)
	{

		$respuesta = ModeloArticulos::mdlVerPrecios($modelo, $lista);

		return $respuesta;
	}

	static public function ctrCambiarStock()
	{

		if (isset($_POST["import"])) {
			$nombre = "STOCK";
			if (strncmp($nombre, $_FILES["archivoxls"]["name"], 5) === 0) {

				include "/../vistas/reportes_excel/Excel/reader.php";
				$directorio = "vistas/cargas/" . $_FILES["archivoxls"]["name"];
				$archivo = move_uploaded_file($_FILES["archivoxls"]['tmp_name'], $directorio);
				$data = new Spreadsheet_Excel_Reader();
				$data->setOutputEncoding('CP1251');
				$data->read("vistas/cargas/" . $_FILES["archivoxls"]["name"]);
				$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
				$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
				mysql_select_db($con["db"], $conexion);
				for ($i = 2; $i <= $data->sheets[0]['numRows']; $i++) {
					for ($j = 1; $j <= 1; $j++) {
						if (substr($data->sheets[0]['cells'][$i][1], 0, 1) == '0') {
							$sqlDetalle = mysql_query("UPDATE articulojf SET stock=" . $data->sheets[0]['cells'][$i][11] .
								" WHERE articulo='" . "1" . $data->sheets[0]['cells'][$i][1] . "'") or die(mysql_error());
						} else {
							$sqlDetalle = mysql_query("UPDATE articulojf SET stock=" . $data->sheets[0]['cells'][$i][11] .
								" WHERE articulo='" . $data->sheets[0]['cells'][$i][1] . "'") or die(mysql_error());
						}
					}
				}
				echo '<script>

				swal({
					type: "success",
					title: "El articulo ha sido editado correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "cargas-automaticas";

								}
							})

				</script>';
			} else {

				echo '<script>

					swal({
						type: "error",
						title: "¡El nombre de archivo no es correcto, debe ser STOCK.xls!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
							if (result.value) {

							window.location = "cargas-automaticas";

							}
						})

				</script>';
			}
		}
	}

	static public function ctrCambiarMovimientos()
	{

		if (isset($_POST["importmovimiento"])) {

			$nombre = "MOV";
			if (strncmp($nombre, $_FILES["archivoxlsmovimiento"]["name"], 3) === 0) {

				include "/../vistas/reportes_excel/Excel/reader.php";
				$directorio = "vistas/cargas/" . $_FILES["archivoxlsmovimiento"]["name"];
				$archivo = move_uploaded_file($_FILES["archivoxlsmovimiento"]['tmp_name'], $directorio);
				$data = new Spreadsheet_Excel_Reader();
				$data->setOutputEncoding('CP1251');
				$data->read("vistas/cargas/" . $_FILES["archivoxlsmovimiento"]["name"]);
				$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
				$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
				mysql_select_db($con["db"], $conexion);
				$sqlEliminar = mysql_query("DELETE FROM movimientosjf WHERE fecha = DATE(NOW()) OR fecha = DATE(NOW()) - INTERVAL 1 DAY");
				for ($i = 2; $i <= $data->sheets[0]['numRows']; $i++) {
					for ($j = 1; $j <= 1; $j++) {

						$tipo = $data->sheets[0]['cells'][$i][1];
						$documento = $data->sheets[0]['cells'][$i][2];
						$mes = substr($data->sheets[0]['cells'][$i][3], 3, 3);
						if ($mes == "Jan") {
							$num = "01";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Feb") {
							$num = "02";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Mar") {
							$num = "03";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Apr") {
							$num = "04";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "May") {
							$num = "05";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Jun") {
							$num = "06";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Jul") {
							$num = "07";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Aug") {
							$num = "08";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Sep") {
							$num = "09";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Oct") {
							$num = "10";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Nov") {
							$num = "11";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						} else if ($mes == "Dec") {
							$num = "12";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][3]);
						}
						if (substr($data->sheets[0]['cells'][$i][4], 0, 1) == '0') {
							$codArt = "1" . $data->sheets[0]['cells'][$i][4];
						} else {
							$codArt = $data->sheets[0]['cells'][$i][4];
						}
						if ($data->sheets[0]['cells'][$i][1] == "E20") {
							$taller = substr($data->sheets[0]['cells'][$i][2], 0, 2);
						} else {
							$taller = 0;
						}
						$linea = $data->sheets[0]['cells'][$i][6];
						$almacen = $data->sheets[0]['cells'][$i][7];
						$cliente = $data->sheets[0]['cells'][$i][8];
						$vendedor = $data->sheets[0]['cells'][$i][9];
						$cantidad = $data->sheets[0]['cells'][$i][10];
						$precio = $data->sheets[0]['cells'][$i][15];
						$dscto1 = $data->sheets[0]['cells'][$i][18];
						$dscto2 = $data->sheets[0]['cells'][$i][19];
						$nombre = $data->sheets[0]['cells'][$i][28];
						$total = ($data->sheets[0]['cells'][$i][10] * $data->sheets[0]['cells'][$i][15] * ((100 - $data->sheets[0]['cells'][$i][18]) / 100)) * ((100 - $data->sheets[0]['cells'][$i][19]) / 100);
						$sqlInsertar = mysql_query("INSERT INTO movimientosjf (tipo,documento,taller,fecha,articulo,linea,cliente,vendedor,cantidad,precio,dscto1,dscto2,total,nombre_tipo,almacen)  values('" . $tipo . "','" . $documento . "','" . $taller . "','" . substr($fecha, 6, 2) . "-" . substr($fecha, 3, 2) . "-" . substr($fecha, 0, 2) . "','" . $codArt . "','" . $linea . "','" . $cliente . "','" . $vendedor . "'," . $cantidad . "," . $precio . "," . $dscto1 . "," . $dscto2 . "," . $total . ",'" . $nombre . "','" . $almacen . "')");
					}
				}
				echo '<script>

				swal({
					type: "success",
					title: "Los movimientos ha sido editados correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "cargas-automaticas";

								}
							})

				</script>';
			} else {

				echo '<script>

					swal({
						type: "error",
						title: "¡El nombre de archivo no es correcto, debe ser MOV.xls!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
							if (result.value) {

							window.location = "cargas-automaticas";

							}
						})

				</script>';
			}
		}
	}

	static public function ctrCargarVentas()
	{

		if (isset($_POST["importventa"])) {

			$nombre = "VENTA";
			if (strncmp($nombre, $_FILES["archivoxlsventa"]["name"], 5) === 0) {

				include "/../vistas/reportes_excel/Excel/reader.php";
				$directorio = "vistas/cargas/" . $_FILES["archivoxlsventa"]["name"];
				$archivo = move_uploaded_file($_FILES["archivoxlsventa"]['tmp_name'], $directorio);
				$data = new Spreadsheet_Excel_Reader();
				$data->setOutputEncoding('CP1251');
				$data->read("vistas/cargas/" . $_FILES["archivoxlsventa"]["name"]);
				$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
				$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
				mysql_select_db($con["db"], $conexion);
				$sqlEliminar = mysql_query("DELETE FROM ventajf WHERE YEAR(fecha) = YEAR(NOW()) AND MONTH(fecha) = MONTH (NOW()) ");
				for ($i = 2; $i <= $data->sheets[0]['numRows']; $i++) {
					for ($j = 1; $j <= 1; $j++) {

						$tipo = $data->sheets[0]['cells'][$i][1];
						$documento = $data->sheets[0]['cells'][$i][3];
						$neto = $data->sheets[0]['cells'][$i][6];
						$igv = $data->sheets[0]['cells'][$i][7];
						$dscto = $data->sheets[0]['cells'][$i][8];
						$total = $data->sheets[0]['cells'][$i][14];
						$cliente = $data->sheets[0]['cells'][$i][15];
						$vendedor = $data->sheets[0]['cells'][$i][17];
						$agencia = $data->sheets[0]['cells'][$i][18];
						$mes = substr($data->sheets[0]['cells'][$i][19], 3, 3);
						if ($mes == "Jan") {
							$num = "01";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Feb") {
							$num = "02";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Mar") {
							$num = "03";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Apr") {
							$num = "04";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "May") {
							$num = "05";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Jun") {
							$num = "06";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Jul") {
							$num = "07";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Aug") {
							$num = "08";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Sep") {
							$num = "09";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Oct") {
							$num = "10";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Nov") {
							$num = "11";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						} else if ($mes == "Dec") {
							$num = "12";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][19]);
						}

						$tipo_doc = $data->sheets[0]['cells'][$i][28];

						$sqlInsertar = mysql_query("INSERT INTO ventajf (tipo,documento,neto,igv,dscto,total,cliente,vendedor,agencia,fecha,tipo_documento)  values('" . $tipo . "','" . $documento . "'," . $neto . "," . $igv . "," . $dscto . "," . $total . ",'" . $cliente . "','" . $vendedor . "','" . $agencia . "','" . substr($fecha, 6, 2) . "-" . substr($fecha, 3, 2) . "-" . substr($fecha, 0, 2) . "','" . $tipo_doc . "')");
					}
				}
				echo '<script>

				swal({
					type: "success",
					title: "Las ventas han sido editadas correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "cargas-automaticas";

								}
							})

				</script>';
			} else {

				echo '<script>

					swal({
						type: "error",
						title: "¡El nombre de archivo no es correcto, debe ser VENTA.xls!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
							if (result.value) {

							window.location = "cargas-automaticas";

							}
						})

				</script>';
			}
		}
	}

	static public function ctrCargarPedidos()
	{

		if (isset($_POST["importpedido"])) {

			$nombre = "PEDIDOS";
			if (strncmp($nombre, $_FILES["archivoxlspedido"]["name"], 7) === 0) {

				include "/../vistas/reportes_excel/Excel/reader.php";
				$directorio = "vistas/cargas/" . $_FILES["archivoxlspedido"]["name"];
				$archivo = move_uploaded_file($_FILES["archivoxlspedido"]['tmp_name'], $directorio);
				$data = new Spreadsheet_Excel_Reader();
				$data->setOutputEncoding('CP1251');
				$data->read("vistas/cargas/" . $_FILES["archivoxlspedido"]["name"]);
				$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
				$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
				mysql_select_db($con["db"], $conexion);
				$sqlEliminar = mysql_query("DELETE FROM pedidojf ");
				for ($i = 2; $i <= $data->sheets[0]['numRows']; $i++) {
					for ($j = 1; $j <= 1; $j++) {

						$tipo = $data->sheets[0]['cells'][$i][1];
						$documento = $data->sheets[0]['cells'][$i][3];
						$neto = $data->sheets[0]['cells'][$i][6];
						$igv = $data->sheets[0]['cells'][$i][7];
						$dscto = $data->sheets[0]['cells'][$i][8];
						$total = $data->sheets[0]['cells'][$i][14];
						$cliente = $data->sheets[0]['cells'][$i][15];
						$vendedor = $data->sheets[0]['cells'][$i][16];
						$mes = substr($data->sheets[0]['cells'][$i][17], 3, 3);
						if ($mes == "Jan") {
							$num = "01";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Feb") {
							$num = "02";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Mar") {
							$num = "03";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Apr") {
							$num = "04";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "May") {
							$num = "05";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Jun") {
							$num = "06";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Jul") {
							$num = "07";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Aug") {
							$num = "08";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Sep") {
							$num = "09";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Oct") {
							$num = "10";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Nov") {
							$num = "11";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						} else if ($mes == "Dec") {
							$num = "12";
							$fecha = str_replace($mes, $num, $data->sheets[0]['cells'][$i][17]);
						}
						$tipo_doc = $data->sheets[0]['cells'][$i][25];
						$sqlInsertar = mysql_query("INSERT INTO pedidojf (tipo,documento,neto,igv,dscto,total,cliente,vendedor,fecha,tipo_documento,atraso)  VALUES('" . $tipo . "','" . $documento . "'," . $neto . "," . $igv . "," . $dscto . "," . $total . ",'" . $cliente . "','" . $vendedor . "','" . substr($fecha, 6, 2) . "-" . substr($fecha, 3, 2) . "-" . substr($fecha, 0, 2) . "','" . $tipo_doc . "', DATEDIFF(DATE(NOW()),DATE('" . substr($fecha, 6, 2) . "-" . substr($fecha, 3, 2) . "-" . substr($fecha, 0, 2) . "')))");
					}
				}
				echo '<script>

				swal({
					type: "success",
					title: "Los pedidos han sido editados correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "cargas-automaticas";

								}
							})

				</script>';
			} else {

				echo '<script>

					swal({
						type: "error",
						title: "¡El nombre de archivo no es correcto, debe ser PEDIDO.xls!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
							if (result.value) {

							window.location = "cargas-automaticas";

							}
						})

				</script>';
			}
		}
	}
	static public function ctrCargarArticuloPedido()
	{

		if (isset($_POST["importarticulopedido"])) {
			$nombre = "ARTICULO";
			if (strncmp($nombre, $_FILES["archivoxls"]["name"], 8) === 0) {

				include "/../vistas/reportes_excel/Excel/reader.php";
				$directorio = "vistas/cargas/" . $_FILES["archivoxlsarticulopedido"]["name"];
				$archivo = move_uploaded_file($_FILES["archivoxlsarticulopedido"]['tmp_name'], $directorio);
				$data = new Spreadsheet_Excel_Reader();
				$data->setOutputEncoding('CP1251');
				$data->read("vistas/cargas/" . $_FILES["archivoxlsarticulopedido"]["name"]);
				$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
				$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
				mysql_select_db($con["db"], $conexion);
				for ($i = 2; $i <= $data->sheets[0]['numRows']; $i++) {
					for ($j = 1; $j <= 1; $j++) {
						if (substr($data->sheets[0]['cells'][$i][1], 0, 1) == '0') {
							$sqlDetalle = mysql_query("UPDATE articulojf SET pedidos=" . $data->sheets[0]['cells'][$i][5] .
								" WHERE articulo='" . "1" . $data->sheets[0]['cells'][$i][1] . "'") or die(mysql_error());
						} else {
							$sqlDetalle = mysql_query("UPDATE articulojf SET pedidos=" . $data->sheets[0]['cells'][$i][5] .
								" WHERE articulo='" . $data->sheets[0]['cells'][$i][1] . "'") or die(mysql_error());
						}
					}
				}
				echo '<script>

				swal({
					type: "success",
					title: "El articulo ha sido editado correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "cargas-automaticas";

								}
							})

				</script>';
			} else {

				echo '<script>

					swal({
						type: "error",
						title: "¡El nombre de archivo no es correcto, debe ser STOCK.xls!",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
							if (result.value) {

							window.location = "cargas-automaticas";

							}
						})

				</script>';
			}
		}
	}

	/* 
	* MOSTRAR ARTICULOS - para el select de editar ORDEN DE CORTE
	*/
	static public function ctrMostrarArticulosSimple($orden)
	{

		$respuesta = ModeloArticulos::mdlMostrarArticulosSimple($orden);

		return $respuesta;
	}

	//mdlMostrarArticulosTallerP

	/* 
	* MOSTRAR ARTICULOS DE TALLER GENERADO
	*/
	static public function ctrMostrarArticulosTallerP()
	{

		$respuesta = ModeloArticulos::mdlMostrarArticulosTallerP();

		return $respuesta;
	}

	/*
	* CONFIGURAR MP FALTANTE
	*/
	static public function ctrMpFaltante()
	{

		if (isset($_POST["articuloA"])) {

			$modelo = '%' . $_POST["modeloA"] . $_POST["cod_color"] . '%';
			$faltante = $_POST["mpFaltante"];

			$respuesta = ModeloArticulos::mdlMpFaltante($modelo, $faltante);

			//var_dump($modelo, $faltante);
			//var_dump($respuesta);

			if ($respuesta == "ok") {

				echo '<script>

					swal({
						type: "success",
						title: "La materia prima ha sido editada correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						})

					</script>';
			}
		}
	}

	/* 
	* MOSTRAR  COLORES y cantidades
	*/
	static public function ctrVerColoresCantidades2($salida, $modelo)
	{

		$respuesta = ModeloArticulos::mdlVerColoresCantidades2($salida, $modelo);

		return $respuesta;
	}

	//* articulos en urgencia
	static public function ctrArticulosUrgencia()
	{

		$respuesta = ModeloArticulos::mdlArticulosUrgencia();

		return $respuesta;
	}

	static public function ctrConfigurarMesesUrgencia()
	{
		if (isset($_POST["prod"])) {

			$rptProd = ModeloArticulos::mdlConfigurarMesesUrgencia("prod", $_POST["prod"]);

			$rptAlm = ModeloArticulos::mdlConfigurarMesesUrgencia("alm", $_POST["alm"]);

			$rptCorte = ModeloArticulos::mdlConfigurarMesesUrgencia("corte", $_POST["corte"]);

			$rptPlan = ModeloArticulos::mdlConfigurarMesesUrgencia("plan", $_POST["plan"]);

			if ($rptPlan == "ok") {
				echo '<script>	

				swal({
					  type: "success",
					  title: "Se guardo la configuración correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "urgencias-maestro";

								}
							})

				</script>';
			}
		}
	}
}
