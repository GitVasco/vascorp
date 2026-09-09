<?php


class ControladorNotasIngresos{

    /*=============================================
	MOSTRAR NOTAS DE INGRESO
	=============================================*/	

	static public function ctrRangoFechasNotasIngresos($fechaInicial, $fechaFinal){

		$respuesta = ModeloNotasIngresos::mdlRangoFechasNotasIngresos($fechaInicial, $fechaFinal);

		return $respuesta;
		
	}

	/* 
	*MOSTRAR MP PARA NOTA DE INGRESO CON O SIN OC
	*/
	static public function ctrMostrarMPOC($empresa, $oc){

		$respuesta = ModeloNotasIngresos::mdlMostrarMPOC($empresa, $oc);

		return $respuesta;
		
	}

	/* 
	* TIPOS DE DOC PARA NI
	*/
	static public function ctrDocNI(){

		$respuesta = ModeloNotasIngresos::mdlDocNI();

		return $respuesta;
		
	}	

	/* 
	* OC por Proveedor
	*/
	static public function ctrOCProv($empresa){

		$respuesta = ModeloNotasIngresos::mdlOCProv($empresa);

		return $respuesta;
		
	}
	

	/* 
	* MP EN OC O SUELTA
	*/
	static public function ctrTraerMpOC($codpro, $orden, $codruc){

		$respuesta = ModeloNotasIngresos::mdlTraerMpOC($codpro, $orden, $codruc);

		return $respuesta;
		
	}
	
	static public function ctrCrearNotaIngreso(){

		/* 
		* revisamos que vengan datos
		*/
		if(	isset($_POST["nuevoProveedor"]) && 
			isset($_POST["listaNI"])){
		//var_dump($_POST["nuevoProveedor"], $_POST["listaNI"]);
				
			/* 
			*alerta si la lista de mp viene vacia
			*/	
			if($_POST["listaNI"] == ""){
				# Mostramos una alerta suave
				echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡No se seleccionó ninguna materia prima. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="crear-nota-ingreso";}
						});
					</script>';

			}else{

				# Modificamos la lista en un array
				$listaNotaIngreso = json_decode($_POST["listaNI"],true);
				#var_dump($listaNotaIngreso);
				# traemos la fecha y la pc
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();
				$PcReg= gethostbyaddr($_SERVER['REMOTE_ADDR']);

				$igv = $_POST["nuevoIGV"] * 100;

				#1. Creamos la cabecera

				$ultimoNro = ModeloNotasIngresos::mdlMostrarCorrelativoNotaIngreso();
				//var_dump($ultimoNro);

				$datosCab = array(	"cod_local" 	=> '01',
									"cod_entidad" 	=> '01',
									"codruc" 		=> $_POST["nuevoProveedor"],
									"tnea" 			=> 'NE',
									"snea" 			=> '001',
									"nnea"			=> $ultimoNro["correlativo"],
									"trguia"		=> $_POST["tipDocS"],
									"serguia"		=> $_POST["nuevaSerieS"],
									"nroguia"		=> $_POST["nuevoNroS"],
									"fecemi" 		=> $_POST["fecS"],
									"mo"			=> $_POST["nuevaMoneda"],
									"obser"			=> $_POST["nuevaObservacion"],
									"pigv"			=> $igv,
									"subtotal"		=> $_POST["subTotalNi"],
									"igv"			=> $_POST["impuestoNi"],
									"total"			=> $_POST["totalNi"],
									"trdcto"		=> $_POST["tipDocP"],
									"srdcto"		=> $_POST["nuevaSerieP"],
									"nrdcto"		=> $_POST["nuevoNroP"],
									"fecven" 		=> $_POST["fecP"],
									"tipoc"			=> 'OC',
									"seroc"			=> '001',
									"nrooc"			=> $_POST["nuevaOc"],
									"codalm"		=> '01',
									"estreg"		=> 'P',
									"fecreg"		=> $fecha->format("Y-m-d H:i:s"),
									"usureg"		=> $_SESSION["nombre"],
									"pcreg" 		=> $PcReg);

				#var_dump($datosCab);
				$respuestaCab = ModeloNotasIngresos::mdlGuardarNotaIngresoCab($datosCab);
				#var_dump("guardocabecera: ", $respuestaCab);
				#$respuestaCab = "ok";

				#2. Creamos el detalle
				if($respuestaCab == "ok"){

					foreach($listaNotaIngreso as $key=>$value){

						$datosDet = array(	"cod_local" 	=> '01',
											"cod_entidad" 	=> '01',
											"item"			=> $key+1,
											"codruc"		=> $_POST["nuevoProveedor"],
											"tnea"			=> 'NE',
											"snea" 			=> '001',
											"nnea"			=> $ultimoNro["correlativo"],
											"ndoc"			=> $value["oc"],
											"cansol"		=> $value["cantidadRe"],
											"presol"		=> $value["precio"],
											"codpro"		=> $value["codpro"],
											"codalm"		=> '01',
											"p_unitario"	=> $value["precio"],
											"coscompra"		=> $value["precio"],
											"total"			=> $value["total"],
											"estreg"		=> 'P',
											"fecreg"		=> $fecha->format("Y-m-d H:i:s"),
											"usureg"		=> $_SESSION["nombre"],
											"pcreg" 		=> $PcReg,
											"salpro"		=> $value["saldo"],
											"excpro"		=> $value["exceso"],
											"cantni"		=> $value["cantidadOc"],
											"fecemi"		=> $fecha->format("Y-m-d H:i:s"));

						#var_dump($datosDet);
						$respuestaDet = ModeloNotasIngresos::mdlGuardarNotaIngresoDet($datosDet);
						#var_dump("guardo detalle: ",$respuestaDet);
						#$respuestaDet = "ok";

					}

					#3. Aumentamos el stock
					if($respuestaDet == "ok"){

						foreach($listaNotaIngreso as $key=>$value){
							
							$codpro = $value["codpro"];
							$infoMp = ModeloMateriaPrima::mdlMostrarMateriaPrima($codpro);

							$stock = $infoMp["stock"] + $value["cantidadRe"];
							//var_dump($value["codpro"],$stock);
							$respuestaStock = ModeloNotasIngresos::mdlActualizarStock($codpro,$stock);
							#var_dump("sumo stock: ",$respuestaStock);
							#$respuestaStock = "ok";
													
							#4. Bajar saldo en Oc
							if($respuestaStock == "ok" && $value["oc"] != ""){

								$oc = $value["oc"];
								$codpro = $value["codpro"];
								$estado = $value["cerrar"];
								$cantidadRe =$value["cantidadRe"];
								#var_dump($oc, $codpro,$estado, $cantidadRe);
								$respuestaOc = ModeloNotasIngresos::mdlActualizarCantOc($oc,$codpro, $estado, $cantidadRe);
								#var_dump("desconto del stock: ",$respuestaOc);

								#5. Actualizamos estado de la cabecera
								if($respuestaOc == "ok"){

									$oc = $value["oc"];

									$respuestaOcCab = ModeloNotasIngresos::mdlActualizarEstCab($oc);
									#var_dump("actualizo cab oc: ",$respuestaOcCab);


								}									

							}

						}

						if($_POST["nuevaOc"] != "" and $_POST["nuevoCerrar"] == "SI"){

							#var_dump("se cumplio", $_POST["nuevaOc"] );
							$datosOc = array( 	"oc"		=> $_POST["nuevaOc"],
												"feccer" 	=> $fecha->format("Y-m-d H:i:s"),
												"usucer" 	=> $_SESSION["nombre"],
												"pccer"		=> $PcReg	);

							$respuestaOc= ModeloNotasIngresos::mdlCerrarOC($datosOc);
							#var_dump($respuestaOc);

						}

						self::ctrSincronizarCostosMpDesdeLista($listaNotaIngreso, "codpro");

						# Mostramos una alerta suave
						echo '<script>
								swal({
									type: "success",
									title: "Felicitaciones",
									text: "¡La nota de ingreso fue actualizada con éxito!",
									showConfirmButton: true,
									confirmButtonText: "Cerrar"
								}).then((result)=>{
									if(result.value){
										window.location="notas-ingresos";}
								});
							</script>';	


					}		

				}else{

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
									window.location="crear-nota-ingreso";}
							});
						</script>';

				}

			}

		}

	}

	/* 
	*Traer cabecera nota ingreso
	*/
	static public function ctrTraerNiCab($valor){

		$respuesta = ModeloNotasIngresos::mdlTraerNiCab($valor);

		return $respuesta;
		
	}	

	/* 
	*Traer detalle nota ingreso
	*/
	static public function ctrTraerNiDet($valor){

		$respuesta = ModeloNotasIngresos::mdlTraerNiDet($valor);

		return $respuesta;
		
	}	

	/* 
	* Actualizar documentos de nota de ingreso
	*/
	static public function ctrEditarNotaIngreso(){

		/* 
		* Revisamos si viene el codigo de la nota de ingreso
		*/
		if(isset($_POST["NotaIngreso"])){
			
			$datos= array( 	"nnea" 		=> $_POST["NotaIngreso"],
							"trdcto"	=> $_POST["tipDocP"],
							"srdcto"	=> $_POST["nuevaSerieP"],
							"nrdcto"	=> $_POST["nuevoNroP"],
							"fecven" 	=> $_POST["fecP"],
							"trguia"	=> $_POST["tipDocS"],
							"serguia"	=> $_POST["nuevaSerieS"],
							"nroguia"	=> $_POST["nuevoNroS"],
							"fecemi" 	=> $_POST["fecS"],
							"obser"		=> $_POST["nuevaObservacion"]);
			var_dump($datos);

			$respuesta = ModeloNotasIngresos::mdlEditarNotaIngreso($datos);
			var_dump($respuesta);

			if($respuesta == "ok"){

				# Mostramos una alerta suave
				echo '<script>
						swal({
							type: "success",
							title: "Felicitaciones",
							text: "¡La nota de ingreso fue actualizada con éxito!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="notas-ingresos";}
						});
					</script>';				


			}else{

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
						window.location="notas-ingresos";}
				});
			</script>';

			}			

		}

	}

	/* 
	* AQUI INICIA NOTA DE INGRESO POR SERVICIO
	! AQUI INICIA NOTA DE INGRESO POR SERVICIO
	? AQUI INICIA NOTA DE INGRESO POR SERVICIO
	*/
	static public function ctrRangoFechasNotasIngresosOS($fechaInicial, $fechaFinal){

		$respuesta = ModeloNotasIngresos::mdlRangoFechasNotasIngresosOS($fechaInicial, $fechaFinal);

		return $respuesta;
		
	}
	
	/* 
	*MOSTRAR MP PARA NOTA DE INGRESO CON O SIN OC
	*/
	static public function ctrMostrarMPOS(){

		$respuesta = ModeloNotasIngresos::mdlMostrarMPOS();

		return $respuesta;
		
	}

	/* 
	*MOSTRAR MP PARA NOTA DE INGRESO CON O SIN OS
	*/
	static public function ctrTraerMPOS($ordser, $codori, $coddes){

		$respuesta = ModeloNotasIngresos::mdlTraerMPOS($ordser, $codori, $coddes);

		return $respuesta;
		
	}
	
	static public function ctrCrearNotaIngresoServicio(){

		#revisamos q vengan datos
		if(isset($_POST["listaOS"])){

			if($_POST["listaOS"] == ""){

				echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡No se seleccionó ninguna materia prima. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="crear-nota-ingreso-os";}
						});
					</script>';					

			}else{

				# Modificamos la lista en un array
				$listaNotaIngreso = json_decode($_POST["listaOS"],true);
				#var_dump($listaNotaIngreso);
				# traemos la fecha y la pc
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();
				$PcReg= gethostbyaddr($_SERVER['REMOTE_ADDR']);
				
				$ultimoNro = ModeloNotasIngresos::mdlMostrarCorrelativoNotaIngresoServicio();
				//var_dump($ultimoNro);

				$datosCab = array(	"cod_local" 	=> '01',
									"cod_entidad" 	=> '01',
									"codruc" 		=> '000097',
									"tneaos"		=> 'NE',
									"sneaos"		=> '001',
									"nneaos"		=> $ultimoNro["correlativo"],
									"fecemi"		=> $_POST["fecP"],
									"serdcto"		=> $_POST["nuevaSerieP"],
									"nrodcto"		=> $_POST["nuevoNroP"],
									"estreg"		=> 'P',
									"fecreg"		=> $fecha->format("Y-m-d H:i:s"),
									"usureg"		=> $_SESSION["nombre"],
									"pcreg" 		=> $PcReg);
			
				#var_dump($datosCab);
				$respuestaCab = ModeloNotasIngresos::mdlGuardarNotaIngresoCabServicio($datosCab);
				#var_dump("guardocabecera: ", $respuestaCab);
				#$respuestaCab = "ok";

				#2. Creamos el detalle
				if($respuestaCab == "ok"){

					foreach($listaNotaIngreso as $key=>$value){

						$datosDet = array(	"cod_local" 	=> '01',
											"cod_entidad" 	=> '01',
											"tneaos"		=> 'NE',
											"sneaos" 		=> '001',
											"nneaos"		=> $ultimoNro["correlativo"],
											"nroos"			=> $value["ordser"],
											"fecemi"		=> $_POST["fecP"],
											"item"			=> $key+1,
											"codruc" 		=> '000097',
											"codproorigen"	=> $value["codori"],
											"codprodestino"	=> $value["coddes"],
											"cansol" 		=> $value["cantidadRe"],
											"estreg"		=> 'P',
											"fecreg"		=> $fecha->format("Y-m-d H:i:s"),
											"usureg"		=> $_SESSION["nombre"],
											"pcreg" 		=> $PcReg);

						#var_dump($datosDet);
						$respuestaDet = ModeloNotasIngresos::mdlGuardarNotaIngresoDetServicio($datosDet);
						#var_dump("guardo detalle: ",$respuestaDet);
						#$respuestaDet = "ok";

					}

				}

				#3. Bajamos el stock de ORIGEN y subimos el stock de DESTINO
				if($respuestaDet == "ok"){

					foreach($listaNotaIngreso as $key=>$value){

						$nro = $value["ordser"];
						$codori = $value["codori"];
						$coddes = $value["coddes"];

						$mpos = ModeloNotasIngresos::mdlMpServicio($nro, $codori, $coddes);
						#var_dump($mpos);

						if($mpos["desstk"] == "NO"){

							#bajar stock origen 
							$codpro = $value["codori"];
							$infoMp = ModeloMateriaPrima::mdlMostrarMateriaPrima($codpro);
							#var_dump("subir ",$infoMp);
							$stock = $infoMp["stock"] - $value["cantidadRe"];
							#var_dump($value["codori"],$infoMp["stock"],$stock);
							$respuestaStockO = ModeloNotasIngresos::mdlActualizarStock($codpro,$stock);
							#var_dump("sumo stock: ",$respuestaStock);
							#$respuestaStockO = "ok";	

							#subimos el stock al coddestino
							$codpro = $value["coddes"];
							$infoMp = ModeloMateriaPrima::mdlMostrarMateriaPrima($codpro);
							#var_dump("subir ",$infoMp);
							$stock = $infoMp["stock"] + $value["cantidadRe"];
							#var_dump($value["codori"],$infoMp["stock"],$stock);
							$respuestaStockD = ModeloNotasIngresos::mdlActualizarStock($codpro,$stock);
							#var_dump("sumo stock: ",$respuestaStock);
							#$respuestaStockD = "ok";							

						}else{

							#subimos el stock al coddestino
							$codpro = $value["coddes"];
							$infoMp = ModeloMateriaPrima::mdlMostrarMateriaPrima($codpro);
							#var_dump("subir ",$infoMp);
							$stock = $infoMp["stock"] + $value["cantidadRe"];
							#var_dump($value["codori"],$infoMp["stock"],$stock);
							$respuestaStockD = ModeloNotasIngresos::mdlActualizarStock($codpro,$stock);
							#var_dump("sumo stock: ",$respuestaStock);
							#$respuestaStockD = "ok";

						}

					}

				}

				#bajamos el saldo a la orden de servicio
				if($respuestaStockD == "ok"){

					foreach($listaNotaIngreso as $key=>$value){

						$nro = $value["ordser"];
						$codori = $value["codori"];
						$coddes = $value["coddes"];
						$cantidad = $value["cantidadRe"];
						$cerrar = $value["cerrar"];

						$mpos = ModeloNotasIngresos::mdlMpServicio($nro, $codori, $coddes);
						#var_dump($mpos);

						$saldo = $mpos["saldo"] - $cantidad;
						#var_dump($saldo);
						$despacho = $mpos["despacho"] + $cantidad;
						#var_dump($despacho);

						$respuestaOS = ModeloNotasIngresos::mdlActualizarServicio($nro, $codori, $coddes, $saldo, $despacho, $cerrar);
						#var_dump($respuestaOS);

						ModeloNotasIngresos::mdlActualizarCabOrdServicio($nro);

					}

						self::ctrAplicarCostoOsSiValido($listaNotaIngreso);

						# Mostramos una alerta suave
						echo '<script>
								swal({
									type: "success",
									title: "Felicitaciones",
									text: "¡La nota de ingreso fue actualizada con éxito!",
									showConfirmButton: true,
									confirmButtonText: "Cerrar"
								}).then((result)=>{
									if(result.value){
										window.location="notas-ingresos-os";}
								});
							</script>';						

				}				

			}


		}
	}	

	/* 
	*Traer cabecera nota ingreso
	*/
	static public function ctrTraerNiSCab($valor){

		$respuesta = ModeloNotasIngresos::mdlTraerNiSCab($valor);

		return $respuesta;
		
	}		

	/* 
	*Traer detalle nota ingreso
	*/
	static public function ctrTraerNiSDet($valor){

		$respuesta = ModeloNotasIngresos::mdlTraerNiSDet($valor);

		return $respuesta;
		
	}

	/**
	 * Recalcula el costo de ficha de las MP con la última nota de ingreso válida.
	 */
	static public function ctrSincronizarCostosMp($codpros)
	{
		if (!is_array($codpros) || count($codpros) < 1) {
			return array("ok" => true, "actualizados" => 0);
		}

		$filas = ModeloNotasIngresos::mdlUltimosCostosNotaIngreso($codpros);
		if ($filas === false) {
			return array("ok" => false, "actualizados" => 0);
		}

		$actualizados = 0;
		foreach ($filas as $f) {
			$codpro = isset($f["codpro"]) ? trim((string) $f["codpro"]) : "";
			$costo = isset($f["costo_propuesto"]) ? (float) $f["costo_propuesto"] : 0;
			if ($codpro === "" || $costo <= 0) {
				continue;
			}
			$ok = ModeloMateriaPrima::mdlActualizarCostoFicha(
				$codpro,
				number_format($costo, 6, ".", "")
			);
			if ($ok === "ok") {
				$actualizados++;
			}
		}

		return array("ok" => true, "actualizados" => $actualizados);
	}

	static private function ctrSincronizarCostosMpDesdeLista($lista, $campoCod)
	{
		$codigos = array();
		if (!is_array($lista)) {
			return;
		}
		foreach ($lista as $value) {
			if (!is_array($value) || !isset($value[$campoCod])) {
				continue;
			}
			$cod = trim((string) $value[$campoCod]);
			if ($cod !== "") {
				$codigos[$cod] = $cod;
			}
		}
		self::ctrSincronizarCostosMp(array_values($codigos));
	}

	/**
	 * La nota de servicio no guarda precio. Si la línea trae uno válido, se toma
	 * como costo de la MP destino (promedio ponderado de esa nota).
	 */
	static private function ctrAplicarCostoOsSiValido($lista)
	{
		if (!is_array($lista)) {
			return;
		}

		$porDestino = array();
		foreach ($lista as $value) {
			if (!is_array($value)) {
				continue;
			}
			$dest = isset($value["coddes"]) ? trim((string) $value["coddes"]) : "";
			$precio = isset($value["precio"]) ? (float) $value["precio"] : 0;
			$cant = isset($value["cantidadRe"]) ? (float) $value["cantidadRe"] : 0;
			if ($dest === "" || $precio <= 0 || $cant <= 0) {
				continue;
			}
			if (!isset($porDestino[$dest])) {
				$porDestino[$dest] = array("cant" => 0.0, "imp" => 0.0);
			}
			$porDestino[$dest]["cant"] += $cant;
			$porDestino[$dest]["imp"] += $cant * $precio;
		}

		foreach ($porDestino as $codpro => $t) {
			if ($t["cant"] <= 0) {
				continue;
			}
			$costo = $t["imp"] / $t["cant"];
			ModeloMateriaPrima::mdlActualizarCostoFicha(
				$codpro,
				number_format($costo, 6, ".", "")
			);
		}
	}

}