<?php

class ControladorMateriaPrima
{

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarMateriaPrima($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarMateriaPrima($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarMateriaPrima2($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarMateriaPrima2($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarMateriaPrima3()
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarMateriaPrima3();

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA PARA ORDEN DE COMPRA
	*/
	static public function ctrMostrarMateriaOrdenCompra($valor1, $valor2)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarMateriaOrdenCompra($valor1, $valor2);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR MATERIA PRIMA CON PAGINACIÓN SERVIDOR
	=============================================*/
	static public function ctrMostrarMateriaPrimaPaginado($start, $length, $search, $orderColumn, $orderDir)
	{
		$respuesta = ModeloMateriaPrima::mdlMostrarMateriaPrimaPaginado($start, $length, $search, $orderColumn, $orderDir);

		return $respuesta;
	}


	/* 
	* VALIDAR CODIGO DE FABRICA EN MATERIA PRIMA
	*/
	static public function ctrMostrarMateriaFabrica($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarMateriaFabrica($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarLineas()
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarLineas();

		return $respuesta;
	}

	/* 
	* MOSTRAR SUBLINEAS SEGUN LINEA DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarSubLineas($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarSubLineas($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR SUBLINEAS DE UNO DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarSubLineas2($valor, $valor2)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarSubLineas2($valor, $valor2);

		return $respuesta;
	}


	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarTallas()
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarTallas();

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarColores()
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarColores();

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA
	*/
	static public function ctrMostrarUndMedida()
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarUndMedida();

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA POR ARTICULO
	*/
	static public function ctrMostrarMateriaArticulo($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarMateriaArticulo($valor);

		return $respuesta;
	}

	/* 
	*CREAR MATERIA PRIMA
	*/
	static public function ctrCrearMateriaPrima()
	{

		if (isset($_POST["nuevaDescripcion"])) {

			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			$ultimoCod = ModeloMateriaPrima::mdlMostrarUltimoCodPro();

			$suma = $ultimoCod["CodPro"] + 1;
			$codigoPro = str_pad($suma, strlen($ultimoCod["CodPro"]), '0', STR_PAD_LEFT);

			$datos = array(
				"Cod_Local" => '01',
				"Cod_Entidad" => '01',
				"CodPro" => $codigoPro,
				"CodProv1" => $_POST["nuevoProveedor"],
				"PreProv1" => $_POST["nuevoPrecioSIGV"],
				"MonProv1" => $_POST["nuevaMoneda"],
				"ObsProv1" => $_POST["nuevaObservacion1"],
				"CodProv2" => $_POST["nuevoProveedor1"],
				"PreProv2" => $_POST["nuevoPrecioSIGV1"],
				"MonProv2" => $_POST["nuevaMoneda1"],
				"ObsProv2" => $_POST["nuevaObservacion2"],
				"CodProv3" => $_POST["nuevoProveedor2"],
				"PreProv3" => $_POST["nuevoPrecioSIGV2"],
				"MonProv3" => $_POST["nuevaMoneda2"],
				"ObsProv3" => $_POST["nuevaObservacion3"],
				"FecReg" => $fecha->format("Y-m-d H:i:s"),
				"PcReg" => $PcReg,
				"UsuReg" => $_SESSION["nombre"]
			);

			$respuesta = ModeloMateriaPrima::mdlIngresarPrecioMP("preciomp", $datos);
			// var_dump($datos);
			// var_dump($respuesta);

			$FamPro = $_POST["nuevaLinea"] . $_POST["nuevaSubLinea"];

			// quiero un array con caracteres especial para en la siguietne linea eliminarla de nuevaDescripcion
			$caracteresEspeciales = array("'", '"', ',', '.');
			// elimino los caracteres especiales de nuevaDescripcion
			$_POST["nuevaDescripcion"] = str_replace($caracteresEspeciales, '', $_POST["nuevaDescripcion"]);

			$datos2 = array(
				"CodAlt" => $_POST["nuevoCodigoAlt"],
				"Cod_Local" => '01',
				"Cod_Entidad" => '01',
				"CodPro" => $codigoPro,
				"CodFab" => $_POST["nuevoCodigoFab"],
				"DesPro" => $_POST["nuevaDescripcion"],
				"ColPro" => $_POST["nuevoColorMateria"],
				"UndPro" => $_POST["nuevaUnidadMedida"],
				"Mo" => '',
				"PaiPro" => '',
				"PrePro" => '',
				"PreFob" => '',
				"CosPro" => '',
				"Por_AdVal" => $_POST["nuevoAdVal"],
				"Por_Seg" => $_POST["nuevoSeguro"],
				"PesPro" => $_POST["nuevoPeso"],
				"Stk_Act" => $_POST["nuevoStockActual"],
				"Stk_Min" => $_POST["nuevoStockMinimo"],
				"Stk_Max" => $_POST["nuevoStockMaximo"],
				"EstPro" => '1',
				"TalPro" => $_POST["nuevaTallaMateria"],
				"FamPro" => $FamPro,
				"Proveedor" => '',
				"CodAlm01" => '0',
				"FecReg" => $fecha->format("Y-m-d H:i:s"),
				"PcReg" => $PcReg,
				"UsuReg" => $_SESSION["nombre"]
			);

			$respuesta2 = ModeloMateriaPrima::mdlIngresarMateriaPrima("producto", $datos2);
			// var_dump($datos2);
			// var_dump($respuesta2);

			if ($respuesta == "ok" && $respuesta2 == "ok") {

				echo '<script>

					window.location = "materiaprima";

						</script>';
			} else {
				echo '<script>

						swal({
							type: "warning",
							title: "La materia prima no fue creada con exito",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
										if (result.value) {

										window.location = "materiaprima";

										}
									})

						</script>';
			}
		}
	}

	/* 
	*EDITAR NOMBRE DE MATERIA PRIMA
	*/
	static public function ctrEditarMateriaPrima()
	{

		if (isset($_POST["editarDescripcion"])) {
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$PcMod = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			$datos = array(
				"CodPro" => $_POST["editarCodigoPro"],
				"CodProv1" => $_POST["editarProveedor"],
				"PreProv1" => $_POST["editarPrecioSIGV"],
				"MonProv1" => $_POST["editarMoneda"],
				"ObsProv1" => $_POST["editarObservacion1"],
				"CodProv2" => $_POST["editarProveedor1"],
				"PreProv2" => $_POST["editarPrecioSIGV1"],
				"MonProv2" => $_POST["editarMoneda1"],
				"ObsProv2" => $_POST["editarObservacion2"],
				"CodProv3" => $_POST["editarProveedor2"],
				"PreProv3" => $_POST["editarPrecioSIGV2"],
				"MonProv3" => $_POST["editarMoneda2"],
				"ObsProv3" => $_POST["editarObservacion3"],
				"FecMod" => $fecha->format("Y-m-d H:i:s"),
				"PcMod" => $PcMod,
				"UsuMod" => $_SESSION["nombre"]
			);

			$respuesta = ModeloMateriaPrima::mdlEditarPrecioMP("preciomp", $datos);


			$datos2 = array(
				"CodAlt" => $_POST["editarCodigoAlt"],
				"CodPro" => $_POST["editarCodigoPro"],
				"DesPro" => $_POST["editarDescripcion"],
				"UndPro" => $_POST["editarUnidadMedida"],
				"Por_AdVal" => $_POST["editarAdVal"],
				"Por_Seg" => $_POST["editarSeguro"],
				"PesPro" => $_POST["editarPeso"],
				"Stk_Min" => $_POST["editarStockMinimo"],
				"Stk_Max" => $_POST["editarStockMaximo"],
				"FecMod" => $fecha->format("Y-m-d H:i:s"),
				"PcMod" => $PcMod,
				"UsuMod" => $_SESSION["nombre"]
			);


			$respuesta2 = ModeloMateriaPrima::mdlEditarMateriaPrima($datos2);
			// var_dump($respuesta2);
			// var_dump($datos2);

			if ($respuesta == "ok") {

				echo '<script>

					Command: toastr["success"]("Se edito exitosamente la materia prima!");

					</script>';
			}
		}
	}

	/* 
	* VISUALIZAR DATOS DE LA MATERIA PRIMA DETALLE
	*/
	static public function ctrVisualizarMateriaPrimaDetalle($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlVisualizarMateriaPrimaDetalle($valor);

		return $respuesta;
	}

	/* 
	* VISUALIZAR DATOS DE LA TABLA DETALLE
	*/
	static public function ctrGlobalMaestra($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlGlobalMaestra($valor);

		return $respuesta;
	}

	/* 
	*EDITAR COSTO DE MATERIA PRIMA
	*/
	static public function ctrEditarMateriaPrimaCosto()
	{

		if (isset($_POST["codigo"])) {


			$tabla = "producto";

			$datos = array(
				"codpro" => $_POST["codigo"],
				"cospro" => $_POST["costo"]
			);

			$respuesta = ModeloMateriaPrima::mdlEditarMateriaPrimaCosto($tabla, $datos);

			if ($respuesta == "ok") {

				echo '<script>

					swal({
						type: "success",
						title: "La materia prima ha sido editada correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
									if (result.value) {

									window.location = "materiaprima";

									}
								})

					</script>';
			} else {

				echo '<script>

				swal({
					type: "danger",
					title: "La materia prima no ha sido editada correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "materiaprima";

								}
							})

				</script>';
			}
		}
	}

	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA URGENCIA
	*/
	static public function ctrMostrarUrgenciaAMP($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarUrgenciaAMP($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR EL DETALLE DE LAS URGENCIAS TABLA ORDEN DE COMPRA
	*/
	static public function ctrVisualizarUrgenciasAMPDetalleOC($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlVisualizarUrgenciasAMPDetalleOC($valor);

		return $respuesta;
	}

	/* 
	* MOSTRAR EL DETALLE DE LAS URGENCIAS TABLA ORDEN DE COMPRA
	*/
	static public function ctrVisualizarUrgenciasAMPDetalleART($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlVisualizarUrgenciasAMPDetalleART($valor);

		return $respuesta;
	}

	/* 
    * MOSTRAR LAS SALIDAS POR MATERIA PRIMA
    */
	static public function ctrProyMp($mp)
	{

		$respuesta = ModeloMateriaPrima::mdlProyMp($mp);

		return $respuesta;
	}


	/* 
    * DUPLICAR MATERIA PRIMA PARA CREAR NUEVO COLOR
    */

	static public function ctrDuplicarMateriaPrima()
	{

		if (isset($_POST["duplicarDescripcion"])) {

			$existe = ModeloMateriaPrima::mdlMostrarExisteMateria($_POST["duplicarCodigoFab"]);
			if ($existe) {
				echo '<script>

				swal({
					type: "error",
					title: "La materia prima ya se encuentra registrada",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "materiaprima";

								}
							})

				</script>';
			} else {
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();
				$PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

				$ultimoCod = ModeloMateriaPrima::mdlMostrarUltimoCodPro();

				$suma = $ultimoCod["CodPro"] + 1;
				$codigoPro = str_pad($suma, strlen($ultimoCod["CodPro"]), '0', STR_PAD_LEFT);



				$datos = array(
					"Cod_Local" => '01',
					"Cod_Entidad" => '01',
					"CodPro" => $codigoPro,
					"CodProv1" => $_POST["duplicarProveedor"],
					"PreProv1" => $_POST["duplicarPrecioSIGV"],
					"MonProv1" => $_POST["duplicarMoneda"],
					"ObsProv1" => $_POST["duplicarObservacion1"],
					"CodProv2" => $_POST["duplicarProveedor1"],
					"PreProv2" => $_POST["duplicarPrecioSIGV1"],
					"MonProv2" => $_POST["duplicarMoneda1"],
					"ObsProv2" => $_POST["duplicarObservacion2"],
					"CodProv3" => $_POST["duplicarProveedor2"],
					"PreProv3" => $_POST["duplicarPrecioSIGV2"],
					"MonProv3" => $_POST["duplicarMoneda2"],
					"ObsProv3" => $_POST["duplicarObservacion3"],
					"FecReg" => $fecha->format("Y-m-d H:i:s"),
					"PcReg" => $PcReg,
					"UsuReg" => $_SESSION["nombre"]
				);

				$respuesta = ModeloMateriaPrima::mdlIngresarPrecioMP("preciomp", $datos);
				// var_dump($datos);
				// var_dump($respuesta);


				$datos2 = array(
					"CodAlt" => $_POST["duplicarCodigoAlt"],
					"Cod_Local" => '01',
					"Cod_Entidad" => '01',
					"CodPro" => $codigoPro,
					"CodFab" => $_POST["duplicarCodigoFab"],
					"DesPro" => $_POST["duplicarDescripcion"],
					"ColPro" => $_POST["duplicarColorMateria"],
					"UndPro" => $_POST["duplicarUnidadMedida"],
					"Mo" => '',
					"PaiPro" => '',
					"PrePro" => '',
					"PreFob" => '',
					"CosPro" => '',
					"Por_AdVal" => $_POST["duplicarAdVal"],
					"Por_Seg" => $_POST["duplicarSeguro"],
					"PesPro" => $_POST["duplicarPeso"],
					"Stk_Act" => $_POST["duplicarStockActual"],
					"Stk_Min" => $_POST["duplicarStockMinimo"],
					"Stk_Max" => $_POST["duplicarStockMaximo"],
					"EstPro" => '1',
					"TalPro" => $_POST["duplicarTallaMateria"],
					"FamPro" => $_POST["duplicarFamPro"],
					"Proveedor" => '',
					"CodAlm01" => '0',
					"FecReg" => $fecha->format("Y-m-d H:i:s"),
					"PcReg" => $PcReg,
					"UsuReg" => $_SESSION["nombre"]
				);

				$respuesta2 = ModeloMateriaPrima::mdlIngresarMateriaPrima("producto", $datos2);


				if ($respuesta == "ok" && $respuesta2 == "ok") {

					echo '<script>

							window.location = "materiaprima";

						</script>';
				} else {
					echo '<script>

						swal({
							type: "warning",
							title: "La materia prima no fue creada con exito",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
										if (result.value) {

										window.location = "materiaprima";

										}
									})

						</script>';
				}
			}
		}
	}

	/* 
	*ANULAR MATERIA PRIMA
	*/
	static public function ctrAnularMateriaPrima()
	{

		if (isset($_GET["idMateriaPrima"])) {
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$PcAnu = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			$datos = array(
				"CodPro" => $_GET["idMateriaPrima"],
				"EstPro" => '0',
				"FecAnu" => $fecha->format("Y-m-d H:i:s"),
				"PcAnu" => $PcAnu,
				"UsuAnu" => $_SESSION["nombre"]
			);

			$respuesta = ModeloMateriaPrima::mdlAnularMateriaPrima($datos);


			if ($respuesta == "ok") {

				echo '<script>

					swal({
						type: "success",
						title: "La materia prima ha sido anulada correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
									if (result.value) {

									window.location = "materiaprima";

									}
								})

					</script>';
			}
		}
	}

	/* 
	*ANULAR MATERIA PRIMA
	*/
	static public function ctrMostrarKardexMP($codigo, $ano, $ano_ant)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarKardexMP($codigo, $ano, $ano_ant);

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA ALMACEN 01
	*/
	static public function ctrMostrarAlmacen01($tipo)
	{

		$respuesta = ModeloMateriaPrima::mdlMostrarAlmacen01($tipo);

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA ALMACEN 01 PARA AGREGAR
	*/
	static public function ctrAlmacen01Agregar($codpro)
	{

		$respuesta = ModeloMateriaPrima::mdlAlmacen01Agregar($codpro);

		return $respuesta;
	}

	/* 
	* MOSTRAR DATOS DE LA MATERIA PRIMA ALMACEN 01 PARA QUITAR
	*/
	static public function ctrAlmacen01Quitar($codpro)
	{

		$respuesta = ModeloMateriaPrima::mdlAlmacen01Quitar($codpro);

		return $respuesta;
	}

	/* 
	* MOSTRAR CORRELATIVO DEPENDE DEL TIPO
	*/
	static public function ctrCorrelativoNuevo($tipo)
	{

		$respuesta = ModeloMateriaPrima::mdlCorrelativoNuevo($tipo);

		return $respuesta;
	}

	/* 
	* MOSTAR MP DE ALMACEN01 
	*/
	static public function ctrSelectAlmacen01($valor)
	{

		$respuesta = ModeloMateriaPrima::mdlSelectAlmacen01($valor);

		return $respuesta;
	}

	/* 
	! ESPACIO PARA CREAR CUADROS
	* ESPACIO PARA CREAR CUADROS
	? ESPACIO PARA CREAR CUADROS
	*/
	static public function ctrCrearCuadrosProd()
	{

		if (
			isset($_POST["correlativo"]) &&
			isset($_POST["listaCuaMp"])
		) {
			#var_dump($_POST["correlativo"]);

			if ($_POST["listaCuaMp"] == "") {

				#mostrar alerta suave
				echo '<script>
				swal({
					type: "error",
					title: "Error",
					text: "¡No se seleccionó ningun cuadro. Por favor, intenteló de nuevo!",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then((result)=>{
					if(result.value){
						window.location="crear-cuadros-prod";}
				});
			</script>';
			} else {

				# Modificamos la lista en un array
				$listaCuadros = json_decode($_POST["listaCuaMp"], true);
				#var_dump($listaCuadros);
				# traemos la fecha y la pc
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();
				$PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

				#1. Creamos la cabecera
				$datosCab = array(
					"tipo" 		=> 'PCUA',
					"documento"	=> $_POST["correlativo"],
					"valor1"	=> $_POST["nuevoTotal"],
					"valor2"	=> '0',
					"valor3"	=> '0',
					"valor4"	=> '0',
					"valor5"	=> '0',
					"fecreg"	=> $_POST["fecha"],
					"usureg"	=> $_SESSION["nombre"],
					"pcreg" 	=> $PcReg
				);
				#var_dump($datosCab);
				$respuestaCab = ModeloMateriaPrima::mdlGuardarProduccionCab($datosCab);
				#var_dump($respuestaCab);
				#$respuestaCab = "ok";

				#2. Creamos el detalle
				if ($respuestaCab == "ok") {

					foreach ($listaCuadros as $key => $value) {

						$datosDet = array(
							"tipo" 		=> 'PCUA',
							"documento"	=> $_POST["correlativo"],
							"codigo"	=> $value["codpro"],
							"valor1"	=> $value["cantidadRe"],
							"valor2"	=> '0',
							"valor3"	=> '0',
							"valor4"	=> '0',
							"valor5"	=> '0',
							"fecreg"	=> $fecha->format("Y-m-d H:i:s"),
							"usureg"	=> $_SESSION["nombre"],
							"pcreg" 	=> $PcReg,
							"condicion"	=> '+'
						);
						#var_dump($datosDet);
						$respuestaDet = ModeloMateriaPrima::mdlGuardarProduccionDet($datosDet);
						$respuestaStock = ModeloMateriaPrima::mdlActualizarStockMP($value["codpro"], $value["cantidadRe"]);

						#var_dump("guardo detalle: ",$respuestaDet);
						#var_dump("guardo stock: ",$respuestaStock);
						#$respuestaDet = "ok";
					}
				}

				# Mostramos una alerta suave
				echo '<script>
						swal({
							type: "success",
							title: "Felicitaciones",
							text: "¡La produccion de cuadros fue actualizada con éxito!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="tabla-produccion";}
						});
					</script>';
			}
		}
	}

	/* 
	! ESPACIO PARA CREAR COPAS
	* ESPACIO PARA CREAR COPAS
	? ESPACIO PARA CREAR COPAS
	*/
	static public function ctrCrearCopasProd()
	{

		if (
			isset($_POST["correlativo"]) &&
			isset($_POST["listaCopaMp"])
		) {
			#var_dump($_POST["correlativo"]);

			if ($_POST["listaCopaMp"] == "") {

				#mostrar alerta suave
				echo '<script>
				swal({
					type: "error",
					title: "Error",
					text: "¡No se seleccionó ninguna copa. Por favor, intenteló de nuevo!",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then((result)=>{
					if(result.value){
						window.location="crear-copas-prod";}
				});
			</script>';
			} else {

				# Modificamos la lista en un array
				$listaCopas = json_decode($_POST["listaCopaMp"], true);
				#var_dump($listaCopas);
				# traemos la fecha y la pc
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();
				$PcReg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

				#1. Creamos la cabecera
				$datosCab = array(
					"tipo" 		=> 'PCOP',
					"documento"	=> $_POST["correlativo"],
					"valor1"	=> $_POST["nuevoTotal"],
					"valor2"	=> '0',
					"valor3"	=> '0',
					"valor4"	=> '0',
					"valor5"	=> '0',
					"fecreg"	=> $_POST["fecha"],
					"usureg"	=> $_SESSION["nombre"],
					"pcreg" 	=> $PcReg
				);
				#var_dump($datosCab);
				$respuestaCab = ModeloMateriaPrima::mdlGuardarProduccionCab($datosCab);
				// var_dump($respuestaCab);
				// $respuestaCab = "ok";

				#2. Creamos el detalle
				if ($respuestaCab == "ok") {

					/*
                    * array con los cuadros unicos sin repetir
                    */
					$cuadros_array = [];
					foreach ($listaCopas as $valor) {

						$cuadro = $valor["cuadro"];

						if (!in_array($cuadro, $cuadros_array)) {

							$cuadros_array[] = $cuadro;
						}
					}
					#var_dump("articulos_array", $articulos_array);

					/*
                    * crear un array con la lista unica
                    */
					$resultado = [];
					foreach ($cuadros_array as $unico_id) {

						$temporal = [];
						$cantidad = 0;
						foreach ($listaCopas as $valor) {

							$id = $valor["cuadro"];

							if ($id === $unico_id) {

								$temporal[] = $valor;
							}
						}

						$producto = $temporal[0];

						$producto["cantidadRe"] = 0;
						foreach ($temporal as $producto_temporal) {

							$producto["cantidadRe"] = $producto["cantidadRe"] + $producto_temporal["cantidadRe"];
						}
						// dx($producto["cantidad"]); // trace

						// store unique productoo with updated quantity
						$resultado[] = $producto;
					}
					#var_dump("resultado", $resultado);

					/*
                    todo: GUARDAMOS LOS TOTALES DEL MAESTRA PRODUCCION EN CUADROS
                    */
					foreach ($resultado as $value) {

						//GUARDAR DETALLE DE CUADRO
						$datosCuadro = array(
							"tipo" 		=> 'PCOP',
							"documento"	=> $_POST["correlativo"],
							"codigo"	=> $value["cuadro"],
							"valor1"	=> $value["cantidadRe"],
							"valor2"	=> '0',
							"valor3"	=> '0',
							"valor4"	=> '0',
							"valor5"	=> '0',
							"fecreg"	=> $fecha->format("Y-m-d H:i:s"),
							"usureg"	=> $_SESSION["nombre"],
							"pcreg" 	=> $PcReg,
							"condicion"	=> '-'
						);

						$respuestaCuadro = ModeloMateriaPrima::mdlGuardarProduccionDet($datosCuadro);
					}

					foreach ($listaCopas as $key => $value) {

						$datosDet = array(
							"tipo" 		=> 'PCOP',
							"documento"	=> $_POST["correlativo"],
							"codigo"	=> $value["codpro"],
							"valor1"	=> $value["cantidadRe"],
							"valor2"	=> '0',
							"valor3"	=> '0',
							"valor4"	=> '0',
							"valor5"	=> '0',
							"fecreg"	=> $fecha->format("Y-m-d H:i:s"),
							"usureg"	=> $_SESSION["nombre"],
							"pcreg" 	=> $PcReg,
							"condicion"	=> '+'
						);
						// var_dump($value["cuadro"]);
						//GUARDAR DETALLE DE COPA
						$respuestaDet = ModeloMateriaPrima::mdlGuardarProduccionDet($datosDet);

						//AUMENTO DE COPA
						$respuestaStock = ModeloMateriaPrima::mdlActualizarStockMP($value["codpro"], $value["cantidadRe"]);
						//DESCUENTO DE CUADRO
						$respuestaStock2 = ModeloMateriaPrima::mdlDescontarStockMP($value["cuadro"], $value["cantidadRe"]);
						// var_dump("guardo detalle: ",$respuestaDet);
						// var_dump("guardo stock: ",$respuestaStock);
						// var_dump("desconto cuadro: ",$respuestaStock2);
						#$respuestaDet = "ok";
					}
				}

				# Mostramos una alerta suave
				echo '<script>
						swal({
							type: "success",
							title: "Felicitaciones",
							text: "¡La produccion de copa fue actualizada con éxito!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="tabla-produccion";}
						});
					</script>';
			}
		}
	}
}
