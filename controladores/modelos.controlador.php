<?php

class ControladorModelos
{

	/* 
	* MOSTRAR MODELOS
	*/
	static public function ctrMostrarModelos($item, $valor)
	{

		$tabla = "modelojf";

		$respuesta = ModeloModelos::mdlMostrarModelos($tabla, $item, $valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR MODELOS Activos
	*/
	static public function ctrMostrarModelosActivos()
	{


		$respuesta = ModeloModelos::mdlMostrarModelosActivos();

		return $respuesta;
	}

	/* 
	* MOSTRAR COLORES DE MODELOS
	*/
	static public function ctrMostrarColorModelo($valor)
	{


		$respuesta = ModeloModelos::mdlMostrarColorModelo($valor);

		return $respuesta;
	}

	/*
	* COLORES Y TALLAS YA CREADOS EN UN MODELO
	*/
	static public function ctrMostrarVariantesModelo($valor)
	{

		return ModeloModelos::mdlMostrarVariantesModelo($valor);
	}

	/*
	* Código SUNAT de unidad; si falta, C62 (piezas).
	*/
	static public function ctrCodUnidadModelo($valorModeloOFila)
	{
		if (is_array($valorModeloOFila)) {
			$cod = isset($valorModeloOFila["cod_unidad"]) ? $valorModeloOFila["cod_unidad"] : "";
		} else {
			$modelo = self::ctrMostrarModelos("modelo", $valorModeloOFila);
			$cod = (!empty($modelo) && isset($modelo["cod_unidad"])) ? $modelo["cod_unidad"] : "";
		}

		$cod = trim((string) $cod);
		return $cod !== "" ? $cod : "C62";
	}

	/* 
	* CREAR ARTICULO
	*/
	static public function ctrCrearModelo()
	{
		if (isset($_POST["nuevaDescripcion"])) {


			if (
				preg_match('/^[-\\(\\)\\=\\%\\&\\$\\;\\_\\*\\/\\#\\?\\¿\\!\\¡\\:\\,\\.\\0-9a-zA-ZñÑáéíóúüÁÉÍÓÚÜ ]{1,}$/', $_POST["nuevaDescripcion"]) &&
				preg_match('/^[-\\(\\)\\=\\%\\&\\$\\;\\_\\*\\/\\#\\?\\¿\\!\\¡\\:\\,\\.\\0-9a-zA-ZñÑáéíóúüÁÉÍÓÚÜ ]{1,}$/', $_POST["nuevoModelo"])
			) {

				/*=============================================
				VALIDAR IMAGEN
				=============================================*/

				$ruta = "vistas/img/modelos/default/anonymous.png";

				if (isset($_FILES["nuevaImagen"]["tmp_name"]) && !empty($_FILES["nuevaImagen"]["tmp_name"])) {

					list($ancho, $alto) = getimagesize($_FILES["nuevaImagen"]["tmp_name"]);

					$nuevoAncho = 500;
					$nuevoAlto = 500;

					/*=============================================
					CREAMOS EL DIRECTORIO DONDE VAMOS A GUARDAR LA FOTO DEL USUARIO
					=============================================*/

					$directorio = "vistas/img/modelos/" . $_POST["nuevoModelo"];

					if (!file_exists($directorio)) {
						mkdir($directorio, 0755);
					}

					/*=============================================
					DE ACUERDO AL TIPO DE IMAGEN APLICAMOS LAS FUNCIONES POR DEFECTO DE PHP
					=============================================*/

					if ($_FILES["nuevaImagen"]["type"] == "image/jpeg") {

						/*=============================================
						GUARDAMOS LA IMAGEN EN EL DIRECTORIO
						=============================================*/

						$aleatorio = mt_rand(100, 999);

						$ruta = "vistas/img/modelos/" . $_POST["nuevoModelo"] . "/" . $aleatorio . ".jpg";

						$origen = imagecreatefromjpeg($_FILES["nuevaImagen"]["tmp_name"]);

						$destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

						imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

						imagejpeg($destino, $ruta);
					}

					if ($_FILES["nuevaImagen"]["type"] == "image/png") {

						/*=============================================
						GUARDAMOS LA IMAGEN EN EL DIRECTORIO
						=============================================*/

						$aleatorio = mt_rand(100, 999);

						$ruta = "vistas/img/modelos/" . $_POST["nuevoModelo"] . "/" . $aleatorio . ".png";

						$origen = imagecreatefrompng($_FILES["nuevaImagen"]["tmp_name"]);

						$destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

						imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

						imagepng($destino, $ruta);
					}
				}

				$tabla2 = "preciojf";
				$datosPrecio = array(
					"modelo" => $_POST["nuevoModelo"],
					"precio1" => 0,
					"precio2" => 0,
					"precio3" => 0,
					"precio4" => 0,
					"precio5" => 0,
					"precio6" => 0,
					"precio7" => 0,
					"precio8" => 0,
					"precio9" => 0,
					"precio10" => 0
				);

				$precio = ModeloModelos::mdlIngresarPrecio($tabla2, $datosPrecio);
				$tabla = "modelojf";
				$codUnidad = isset($_POST["nuevaUnidadMedida"]) ? trim($_POST["nuevaUnidadMedida"]) : "";
				if ($codUnidad === "") {
					$codUnidad = "C62";
				}

				$datos = array(
					"id_marca" => $_POST["nuevaMarca"],
					"modelo" => $_POST["nuevoModelo"],
					"descripcion" => $_POST["nuevaDescripcion"],
					"tipo" => $_POST["nuevoTipo"],
					"cod_unidad" => $codUnidad,
					"imagen" => $ruta,
					"estado" => "ACTIVO"
				);

				$respuesta = ModeloModelos::mdlIngresarModelo($tabla, $datos);

				if ($respuesta == "ok") {

					echo '<script>
	
						swal({
							  type: "success",
							  title: "El modelo ha sido guardado correctamente",
							  showConfirmButton: true,
							  confirmButtonText: "Cerrar"
							  }).then(function(result){
										if (result.value) {
	
										window.location = "modelosjf";
	
										}
									})
	
						</script>';
				} else {
					echo '<script>
	
						swal({
							  type: "error",
							  title: "¡El modelo no puede ir con los campos vacíos o llevar caracteres especiales!",
							  showConfirmButton: true,
							  confirmButtonText: "Cerrar"
							  }).then(function(result){
										if (result.value) {
	
										window.location = "modelosjf";
	
										}
									})
	
						</script>';
				}
			} else {

				echo '<script>
	
					swal({
						  type: "error",
						  title: "¡El modelo no puede ir con los campos vacíos o llevar caracteres especiales!",
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
	* EDITAR MODELO
	*/
	static public function ctrEditarModelo()
	{

		if (isset($_POST["editarDescripcion"])) {

			if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarDescripcion"])) {

				/*=============================================
				VALIDAR IMAGEN
				=============================================*/

				$ruta = $_POST["imagenActual"];

				if (
					isset($_FILES["editarImagen"]["tmp_name"]) &&
					!empty($_FILES["editarImagen"]["tmp_name"])
				) {

					list($ancho, $alto) = getimagesize($_FILES["editarImagen"]["tmp_name"]);

					$nuevoAncho = 500;
					$nuevoAlto = 500;

					/*=============================================
					CREAMOS EL DIRECTORIO DONDE VAMOS A GUARDAR LA FOTO DEL USUARIO
					=============================================*/

					$directorio = "vistas/img/modelos/" . $_POST["editarModelo"];

					/*=============================================
					PRIMERO PREGUNTAMOS SI EXISTE OTRA IMAGEN EN LA BD
					=============================================*/

					if (!empty($_POST["imagenActual"]) && $_POST["imagenActual"] != "vistas/img/modelos/default/anonymous.png") {

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

						$ruta = "vistas/img/modelos/" . $_POST["editarModelo"] . "/" . $aleatorio . ".jpg";

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

						$ruta = "vistas/img/modelos/" . $_POST["editarModelo"] . "/" . $aleatorio . ".png";

						$origen = imagecreatefrompng($_FILES["editarImagen"]["tmp_name"]);

						$destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

						imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

						imagepng($destino, $ruta);
					}
				}
				$tabla = "modelojf";
				$codUnidad = isset($_POST["editarUnidadMedida"]) ? trim($_POST["editarUnidadMedida"]) : "";
				if ($codUnidad === "") {
					$codUnidad = "C62";
				}
				$datos = array(
					"descripcion" => $_POST["editarDescripcion"],
					"modelo" => $_POST["editarModelo"],
					"id_marca" => $_POST["editarMarca"],
					"tipo" => $_POST["editarTipo"],
					"cod_unidad" => $codUnidad,
					"imagen" => $ruta
				);

				$respuesta = ModeloModelos::mdlEditarModelo($tabla, $datos);
				if ($respuesta == "ok") {

					echo '<script>

						swal({
							type: "success",
							title: "El modelo ha sido editado correctamente",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
										if (result.value) {

										window.location = "modelosjf";

										}
									})

						</script>';
				}
			} else {

				echo '<script>

					swal({
						type: "error",
						title: "¡El modelo no puede ir con los campos vacíos o llevar caracteres especiales!",
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
	* BORRAR MODELO
	*/
	static public function ctrEliminarModelo()
	{

		if (isset($_GET["idModelo"])) {
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$datos = $_GET["idModelo"];
			$tabla = "modelojf";
			$modelo = ControladorModelos::ctrMostrarModelos("id_modelo", $datos);
			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino un modelo';
			$descripcion   = 'El usuario ' . $usuario . ' elimino el modelo ' . $modelo["modelo"] . ' - ' . $modelo["nombre"];
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
			$respuesta = ModeloModelos::mdlEliminarModelo($tabla, $datos);
			if ($respuesta == "ok") {

				echo '<script>

				swal({
					  type: "success",
					  title: "El modelo ha sido borrado correctamente",
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
	* MOSTRAR MODELOS x ARTICULO
	*/
	static public function ctrMostrarModeloArticulo($item, $valor)
	{

		$tabla = "modelojf";

		$respuesta = ModeloModelos::mdlMostrarModeloArticulo($tabla, $item, $valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR TALLAS
	*/
	static public function ctrMostrarTallas($item, $valor)
	{

		$tabla = "tallajf";

		$respuesta = ModeloModelos::mdlMostrarTallas($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	CREAR PRECIO
	=============================================*/

	static public function ctrCrearPrecio()
	{

		if (isset($_POST["modelo"])) {

			$tabla = "preciojf";
			$datos = array(
				"modelo" => $_POST["modelo"],
				"precio1" => $_POST["precio1"],
				"precio2" => $_POST["precio2"],
				"precio3" => $_POST["precio3"],
				"precio4" => $_POST["precio4"],
				"precio5" => $_POST["precio5"],
				"precio6" => $_POST["precio6"],
				"precio7" => $_POST["precio7"],
				"precio8" => $_POST["precio8"],
				"precio9" => $_POST["precio9"],
				"precio10" => $_POST["precio10"]
			);

			$respuesta = ModeloModelos::mdlIngresarPrecio($tabla, $datos);


			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "El precio del modelo ha sido guardado correctamente",
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

	/*=============================================
	EDITAR PRECIO
	=============================================*/

	static public function ctrEditarPrecio()
	{

		if (isset($_POST["modelo"])) {

			$tabla = "preciojf";
			$datos = array(
				"modelo" => $_POST["modelo"],
				"precio1" => $_POST["precio1"],
				"precio2" => $_POST["precio2"],
				"precio3" => $_POST["precio3"],
				"precio4" => $_POST["precio4"],
				"precio5" => $_POST["precio5"],
				"precio6" => $_POST["precio6"],
				"precio7" => $_POST["precio7"],
				"precio8" => $_POST["precio8"],
				"precio9" => $_POST["precio9"],
				"precio10" => $_POST["precio10"],
				"precio11" => $_POST["precio11"]
			);

			$respuesta = ModeloModelos::mdlEditarPrecio($tabla, $datos);


			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "El precio del modelo ha sido guardado correctamente",
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
	* MOSTRAR PRECIOS
	*/
	static public function ctrMostrarPrecios($item, $valor)
	{

		$tabla = "preciojf";

		$respuesta = ModeloModelos::mdlMostrarPrecios($tabla, $item, $valor);

		return $respuesta;
	}
}
