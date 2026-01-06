<?php

class ControladorCuentas
{

	/*=============================================
	CREAR TIPO DE PAGO
	=============================================*/

	static public function ctrCrearCuenta()
	{

		if (isset($_POST["nuevoCodigo"])) {

			$tabla = "cuenta_ctejf";
			if (empty($_POST["renovacion"])) {
				$renovacion = 0;
			} else {
				$renovacion = $_POST["renovacion"];
			}
			if (empty($_POST["protestado"])) {
				$protestado = 0;
			} else {
				$protestado = $_POST["protestado"];
			}

			$usureg = $_SESSION["nombre"];
			$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			$datos = array(
				"tipo_doc" => $_POST["nuevoCodigo"],
				"num_cta" => $_POST["nuevoDocumento"],
				"cliente" => $_POST["nuevoCliente"],
				"vendedor" => $_POST["nuevoVendedor"],
				"fecha" => $_POST["nuevaFecha"],
				"fecha_ven" => $_POST["nuevaFechaVenc"],
				"fecha_cep" => $_POST["nuevaFechaAcep"],
				"tip_mon" => $_POST["nuevaMoneda"],
				"monto" => $_POST["nuevoMonto"],
				"tip_cambio" => $_POST["nuevoTipoCambio"],
				"estado" => $_POST["nuevoEstado1"],
				"notas" => $_POST["nuevaNota"],
				"cod_pago" => $_POST["nuevoTipoDocumento"],
				"doc_origen" => $_POST["nuevoOrigen"],
				"renovacion" => $renovacion,
				"protesta" => $protestado,
				"usuario" => $_POST["nuevoUsuario"],
				"saldo" => $_POST["nuevoSaldo"],
				"ult_pago" => $_POST["nuevaFechaUltima"],
				"estado_doc" => $_POST["nuevoEstado"],
				"banco" => $_POST["nuevoBanco"],
				"num_unico" => $_POST["nuevoUnico"],
				"fecha_envio" => $_POST["nuevaFechaEnvio"],
				"fecha_abono" => $_POST["nuevaFechaAbono"],
				"tip_mov" => "+",
				"usureg" => $usureg,
				"pcreg" => $pcreg
			);



			$respuesta = ModeloCuentas::mdlIngresarCuenta($tabla, $datos);

			if ($respuesta == "ok") {
				if ($_POST["ruta"] == "cuentas-pendientes") {
					echo '<script>

						swal({
							type: "success",
							title: "La cuenta ha sido guardada correctamente",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
										if (result.value) {

										window.location = "cuentas-pendientes";

										}
									})

						</script>';
				} else if ($_POST["ruta"] == "cuentas-canceladas") {
					echo '<script>

						swal({
							  type: "success",
							  title: "La cuenta ha sido guardada correctamente",
							  showConfirmButton: true,
							  confirmButtonText: "Cerrar"
							  }).then(function(result){
										if (result.value) {
	
										window.location = "cuentas-canceladas";
	
										}
									})
	
						</script>';
				} else {
					echo '<script>

						swal({
							  type: "success",
							  title: "La cuenta ha sido guardada correctamente",
							  showConfirmButton: true,
							  confirmButtonText: "Cerrar"
							  }).then(function(result){
										if (result.value) {
	
										window.location = "' . obtenerRutaCuentas() . '";
	
										}
									})
	
						</script>';
				}
			}
		}
	}


	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function ctrMostrarCuentas($item, $valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentas($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS V2
	=============================================*/

	static public function ctrMostrarCuentasV2($numCta, $tipoDocr)
	{

		$respuesta = ModeloCuentas::mdlMostrarCuentasV2($numCta, $tipoDocr);

		return $respuesta;
	}

	static public function ctrNotificacionesPendientes()
	{
		$respuesta = ModeloCuentas::mdlNotificacionesPendientes();

		return $respuesta;
	}


	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function ctrMostrarCuentaCredito($valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentaCredito($tabla, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function ctrMostrarCuentaDeuda($valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentaDeuda($tabla, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function ctrMostrarCuentaDeudaVencida($valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentaDeudaVencida($tabla, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function ctrMostrarCuentasUnicos($item, $valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentasUnicos($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function ctrMostrarTipoCuentas($item, $valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarTipoCuentas($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS PENDIENTES
	=============================================*/

	static public function ctrMostrarCuentasPendientes($saldo)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentasPendientes($tabla, $saldo);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS IMPRESION DE LETRAS
	=============================================*/

	static public function ctrMostrarCuentasLetras($item, $valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentasLetras($tabla, $item, $valor);

		return $respuesta;
	}
	/*=============================================
	MOSTRAR CUENTAS GENERADOS DE LETRAS
	=============================================*/

	static public function ctrMostrarCuentasGeneradosLetras($item, $valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCuentasGeneradosLetras($tabla, $item, $valor);

		return $respuesta;
	}


	/*=============================================
	MOSTRAR  DOCUMENTOS DE PAGOS
	=============================================*/

	static public function ctrMostrarPagos($item, $valor)
	{
		$tabla = "maestrajf";
		$respuesta = ModeloCuentas::mdlMostrarPagos($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CANCELACIONES
	=============================================*/

	static public function ctrMostrarCancelaciones($item, $valor)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarCancelaciones($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CANCELACIONES V2
	=============================================*/

	static public function ctrMostrarCancelacionesV2($numCta, $codCta)
	{

		$respuesta = ModeloCuentas::mdlMostrarCancelacionesV2($numCta, $codCta);

		return $respuesta;
	}

	/*=============================================
	VALIDAR CUENTA
	=============================================*/

	static public function ctrValidarCuenta($item, $valor, $item2, $valor2)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlValidarCuenta($tabla, $item, $valor, $item2, $valor2);

		return $respuesta;
	}
	/*=============================================
	EDITAR CUENTAS
	=============================================*/

	static public function ctrEditarCuenta()
	{

		if (isset($_POST["editarCodigo"])) {

			$tabla = "cuenta_ctejf";
			if (empty($_POST["editarRenovacion"])) {
				$renovacion = 0;
			} else {
				$renovacion = $_POST["editarRenovacion"];
			}
			if (empty($_POST["editarProtestado"])) {
				$protestado = 0;
			} else {
				$protestado = $_POST["editarProtestado"];
			}
			$datos = array(
				"id" => $_POST["idCuenta"],
				"tipo_doc" => $_POST["editarCodigo"],
				"num_cta" => $_POST["editarDocumento"],
				"cliente" => $_POST["editarCliente"],
				"vendedor" => $_POST["editarVendedor"],
				"fecha" => $_POST["editarFecha"],
				"fecha_ven" => $_POST["editarFechaVenc"],
				"fecha_cep" => $_POST["editarFechaAcep"],
				"tip_mon" => $_POST["editarMoneda"],
				"monto" => $_POST["editarMonto"],
				"tip_cambio" => $_POST["editarTipoCambio"],
				"estado" => $_POST["editarEstado1"],
				"notas" => $_POST["editarNota"],
				"cod_pago" => $_POST["editarTipoDocumento"],
				"doc_origen" => $_POST["editarOrigen"],
				"renovacion" => $renovacion,
				"protesta" => $protestado,
				"usuario" => $_POST["editarUsuario"],
				"saldo" => $_POST["editarSaldo"],
				"ult_pago" => $_POST["editarFechaUltima"],
				"estado_doc" => $_POST["editarEstado"],
				"banco" => $_POST["editarBanco"],
				"num_unico" => $_POST["editarUnico"],
				"fecha_envio" => $_POST["editarFechaEnvio"],
				"fecha_abono" => $_POST["editarFechaAbono"]
			);


			$respuesta = ModeloCuentas::mdlEditarCuenta($tabla, $datos);

			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$cuentas = ControladorCuentas::ctrMostrarCuentas("id", $_POST["idCuenta"]);
			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se edito una cuenta';
			$descripcion   = 'El usuario ' . $usuario . ' edito la cuenta ' . $cuentas["tipo_doc"] . ' - ' . $cuentas["num_cta"];
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
			// var_dump($auditoria);

			if ($respuesta == "ok") {

				if ($_POST["editarRuta"] == "cuentas-pendientes") {
					echo '<script>

						swal({
							type: "success",
							title: "La cuenta ha sido guardada correctamente",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
										if (result.value) {

										window.location = "cuentas-pendientes";

										}
									})

						</script>';
				} else if ($_POST["editarRuta"] == "cuentas-canceladas") {
					echo '<script>

						swal({
							  type: "success",
							  title: "La cuenta ha sido guardada correctamente",
							  showConfirmButton: true,
							  confirmButtonText: "Cerrar"
							  }).then(function(result){
										if (result.value) {
	
										window.location = "cuentas-canceladas";
	
										}
									})
	
						</script>';
				} else {
					echo '<script>

						swal({
							  type: "success",
							  title: "La cuenta ha sido guardada correctamente",
							  showConfirmButton: true,
							  confirmButtonText: "Cerrar"
							  }).then(function(result){
										if (result.value) {
	
										window.location = "' . obtenerRutaCuentas() . '";
	
										}
									})
	
						</script>';
				}
			}
		}
	}

	/*=============================================
	CANCELAR CUENTAS  -YA NOSE USA
	=============================================*/

	static public function ctrCancelarCuenta()
	{

		if (isset($_POST["cancelarDocumento"])) {

			$tabla = "cuenta_ctejf";
			$usureg = $_SESSION["nombre"];
			$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			$datos = array(
				"id" => $_POST["idCuenta2"],
				"tipo_doc" => $_POST["cancelarTipoDocumento"],
				"num_cta" => $_POST["cancelarDocumentoOriginal"],
				"cliente" => $_POST["cancelarCliente"],
				"vendedor" => $_POST["cancelarVendedor"],
				"monto" => $_POST["cancelarMonto"],
				"notas" => $_POST["cancelarNota"],
				"usuario" => $_POST["cancelarUsuario"],
				"fecha" => $_POST["cancelarFechaUltima"],
				"fecha_ven" => $_POST["cancelarVencimientoOrigen"],
				"cod_pago" => $_POST["cancelarCodigo"],
				"doc_origen" => $_POST["cancelarDocumento"],
				"saldo" => 0,
				"tip_mov" => "-",
				"usureg" => $usureg,
				"pcreg" => $pcreg
			);

			$cuenta = ControladorCuentas::ctrMostrarCuentas("id", $_POST["idCuenta2"]);
			$saldoNuevo = $cuenta["saldo"] - $_POST["cancelarMonto"];
			if ($saldoNuevo >= -0.5 && $saldoNuevo <= 0.5) {
				$estado = ModeloCuentas::mdlActualizarUnDato($tabla, "estado", "CANCELADO", $_POST["idCuenta2"]);
			}
			$ultimo_pago = ModeloCuentas::mdlActualizarUnDato($tabla, "ult_pago", $_POST["cancelarFechaUltima"], $_POST["idCuenta2"]);
			$actualizado = ModeloCuentas::mdlActualizarUnDato($tabla, "saldo", $saldoNuevo, $_POST["idCuenta2"]);
			$respuesta = ModeloCuentas::mdlIngresarCuenta($tabla, $datos);
			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "La cuenta ha sido cancelada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "' . obtenerRutaCuentas() . '";

									}
								})

					</script>';
			}
		}
	}

	/*=============================================
	ELIMINAR CUENTAS
	=============================================*/

	static public function ctrEliminarCuenta()
	{

		if (isset($_GET["idCuenta"])) {

			$datos = $_GET["idCuenta"];
			$tabla = "cuenta_ctejf";
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$cuentas = ControladorCuentas::ctrMostrarCuentas("id", $datos);
			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino una cuenta';
			$descripcion   = 'El usuario ' . $usuario . ' elimino la cuenta ' . $cuentas["codigo"] . ' - ' . $cuentas["num_cta"];
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

			$datos3 = array(
				"num_cta" => $cuentas["num_cta"],
				"usuario_bkp" => $_SESSION["id"],
				"fecha_bkp" => $fecha->format("Y-m-d H:i:s")
			);
			$ingreso_bkp = ModeloCuentas::mdlIngresarCuentaBckp("cuenta_cte_bkpjf", $datos3);
			// var_dump($ingreso_bkp);
			$respuesta = ModeloCuentas::mdlEliminarCuentaCancelacion($tabla, $cuentas["num_cta"]);
			if ($respuesta == "ok") {
				if ($_GET["rutas"] == "cuentas-pendientes") {
					echo '<script>

					swal({
						type: "success",
						title: "La cuenta ha sido guardada correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
									if (result.value) {

									window.location = "cuentas-pendientes";

									}
								})

					</script>';
				} else if ($_GET["rutas"] == "cuentas-canceladas") {
					echo '<script>

					swal({
						  type: "success",
						  title: "La cuenta ha sido guardada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "cuentas-canceladas";

									}
								})

					</script>';
				} else {
					echo '<script>

					swal({
						  type: "success",
						  title: "La cuenta ha sido guardada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "' . obtenerRutaCuentas() . '";

									}
								})

					</script>';
				}
			}
		}
	}

	/*=============================================
	EDITAR CANCELACION
	=============================================*/

	static public function ctrEditarCancelacion()
	{

		if (isset($_POST["cancelarDocumento"])) {

			$tabla = "cuenta_ctejf";
			$usureg = $_SESSION["nombre"];
			$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
			$rutas = $_POST["rutas"];

			$datos = array(
				"id" => $_POST["idCuenta2"],
				"tipo_doc" => $_POST["tipEditar"],
				"cod_pago" => $_POST["cancelarCodigo"],
				"num_cta" => $_POST["docEditar"],
				"doc_origen" => $_POST["cancelarDocumento"],
				"cliente" => $_POST["cliEditar"],
				"vendedor" => $_POST["cancelarVendedor"],
				"fecha" => $_POST["cancelarCliente"],
				"monto" => $_POST["cancelarMonto2"],
				"notas" => $_POST["cancelarNota"],
				"usuario" => $_POST["cancelarUsuario"],
				"fecha" => $_POST["cancelarFechaUltima"],
				"usureg" => $usureg,
				"pcreg" => $pcreg
			);
			#var_dump($datos);

			$origen = ControladorCuentas::ctrMostrarCuentasv2($_POST["docEditar"], $_POST["tipEditar"]);
			$idOrigen = $origen["id"];
			#var_dump($idOrigen);

			$saldoNuevo = $_POST["cancelarSaldoAntiguo"] + $_POST["cancelarMontoAntiguo"] - $_POST["cancelarMonto2"];
			#var_dump($saldoNuevo);

			if ($saldoNuevo > 0) {
				$estado = ModeloCuentas::mdlActualizarUnDato($tabla, "estado", "PENDIENTE", $idOrigen);
			} else {

				$estado = ModeloCuentas::mdlActualizarUnDato($tabla, "estado", "CANCELADO", $idOrigen);
			}

			$actualizacion = ModeloCuentas::mdlActualizarUnDato($tabla, "saldo", $saldoNuevo, $idOrigen);

			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$cancelacion = ModeloCuentas::mdlMostrarCancelacion("cuenta_ctejf", "id", $_POST["idCuenta2"]);
			#var_dump($cancelacion);
			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se edito una cancelación';
			$descripcion   = 'El usuario ' . $usuario . ' edito una cancelacion de la cuenta de ' . $cancelacion["tipo_doc"] . ' - ' . $cancelacion["num_cta"];
			#var_dump($descripcion);
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

			$respuesta = ModeloCuentas::mdlEditarCancelacion($tabla, $datos);



			if ($respuesta == "ok") {

				$ultimo_pago = ModeloCuentas::mdlEditarUltPago($_POST["docEditar"], $_POST["tipEditar"]);

				echo '<script>

					swal({
						  type: "success",
						  title: "La cancelación ha sido cambiada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "index.php?ruta=ver-cuentas&numCta=' . $_POST["docEditar"] . '&codCuenta=' . $_POST["tipEditar"] . '&rutas=' . $rutas . '";

									}
								})

					</script>';
			}
		}
	}

	/*=============================================
	ELIMINAR CANCELACION
	=============================================*/

	static public function ctrEliminarCancelacion()
	{

		if (isset($_GET["idCancelacion"])) {

			$datos = $_GET["idCancelacion"];
			$tabla = "cuenta_ctejf";
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$cancelacion = ModeloCuentas::mdlMostrarCancelacion($tabla, "id", $datos);

			$rutas = $_GET["rutas"];

			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino una cancelación';
			$descripcion   = 'El usuario ' . $usuario . ' elimino una cancelacion de la cuenta de ' . $cancelacion["tipo_doc"] . ' - ' . $cancelacion["num_cta"];
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
			$origen = ControladorCuentas::ctrMostrarCuentasV2($cancelacion["num_cta"], $cancelacion["tipo_doc"]);
			#var_dump($origen);
			$idOrigen = $origen["id"];
			#var_dump($idOrigen);
			$saldoNuevo = $cancelacion["monto"] + $origen["saldo"];
			#var_dump($saldoNuevo);

			if ($saldoNuevo > 0) {

				$estado = ModeloCuentas::mdlActualizarEstado($idOrigen);
				#var_dump($estado);

			}

			$actualizacion = ModeloCuentas::mdlActualizarUnDato($tabla, "saldo", $saldoNuevo, $idOrigen);
			var_dump($actualizacion);

			$datos3 = array(
				"id" => $cancelacion["id"],
				"usuario_bkp" => $_SESSION["nombre"],
				"fecha_bkp" => $fecha->format("Y-m-d H:i:s"),
				"pc_bkp" => gethostbyaddr($_SERVER['REMOTE_ADDR'])
			);

			$ingreso_bkp = ModeloCuentas::mdlIngresarCuentaBckp2("cuenta_cte_bkpjf", $datos3);
			#var_dump($ingreso_bkp);

			//Despues de realizar el bkp eliminamos
			$respuesta = ModeloCuentas::mdlEliminarCuenta($tabla, $datos);


			if ($respuesta == "ok") {

				$ultimo_pago = ModeloCuentas::mdlEditarUltPago($origen["num_cta"], $cancelacion["tipo_doc"]);

				echo '<script>

				swal({
					  type: "success",
					  title: "La cancelación ha sido borrada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "index.php?ruta=ver-cuentas&numCta=' . $origen["num_cta"] . '&codCuenta=' . $cancelacion["tipo_doc"] . '&rutas=' . $rutas . '";

								}
							})

				</script>';
			}
		}
	}

	/*=============================================
	CANCELAR CUENTAS
	=============================================*/

	static public function ctrAgregarLetra()
	{

		if (isset($_POST["letraDocumento"])) {

			$tabla = "cuenta_ctejf";
			$fechasInput = $_POST["fechaVenc"];
			$doc1 = substr($_POST["letraDocumento"], 0, 4);
			$doc2 = substr($_POST["letraDocumento"], -5);
			$documento = $doc1 . $doc2 . "-";

			$usureg = $_SESSION["nombre"];
			$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			for ($i = 0; $i < count($fechasInput); $i++) {

				$datos = array(
					"tipo_doc" => "85",
					"num_cta" => $documento . ($i + 1),
					"cliente" => $_POST["letraCli"],
					"vendedor" => $_POST["letraVendedor"],
					"tip_mon" => $_POST["letraMoneda"],
					"monto" => $_POST["monto" . $i],
					"saldo" => $_POST["monto" . $i],
					"notas" => $_POST["obs" . $i],
					"estado" => "PENDIENTE",
					"usuario" => $_POST["letraUsuario"],
					"fecha" => $_POST["letraFecha"],
					"fecha_ven" => $fechasInput[$i],
					"cod_pago" => $_POST["letraCodigo"],
					"doc_origen" => $_POST["letraDocumento"],
					"renovacion" => 0,
					"protesta" => 0,
					"tip_mov" => '+',
					"usureg" => $usureg,
					"pcreg" => $pcreg
				);


				$respuesta = ModeloCuentas::mdlIngresarCuenta($tabla, $datos);
			}
			$eliminado = ModeloCuentas::mdlEliminarCuenta($tabla, $_POST["idCuenta3"]);
			if ($respuesta == "ok") {
				$numCuenta = $_POST["letraDocumento"];


				echo '<script>

					swal({
						  type: "success",
						  title: "La cuenta ha sido cancelada a letras correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "' . obtenerRutaCuentas() . '";

									}
								})

					</script>';
			}
		}
	}

	//Cancelar Letras 
	static public function ctrImportarCuenta()
	{

		if (isset($_POST["importBanco"])) {

			include "/../vistas/reportes_excel/Excel/reader.php";
			$directorio = "vistas/cuentas/" . $_FILES["nuevaImportacion"]["name"];
			$archivo = move_uploaded_file($_FILES["nuevaImportacion"]['tmp_name'], $directorio);
			$data = new Spreadsheet_Excel_Reader();
			$data->setOutputEncoding('CP1251');
			$data->read("vistas/cuentas/" . $_FILES["nuevaImportacion"]["name"]);
			$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
			$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
			mysql_select_db($con["db"], $conexion);

			$intoA = "";
			for ($i = 6; $i <= $data->sheets[0]['numRows']; $i++) {

				if ($data->sheets[0]['cells'][$i][8] == "Cancelado" || $data->sheets[0]['cells'][$i][8] == "Amortizado") {

					$documento = $data->sheets[0]['cells'][$i][1];
					$unico = $data->sheets[0]['cells'][$i][2];
					$fecha_ven = $data->sheets[0]['cells'][$i][5];
					$monto = $data->sheets[0]['cells'][$i][6];
					$montoConv = str_replace(",", "", $monto);
					$montoReducido = substr($montoConv, 3);
					$estado = $data->sheets[0]['cells'][$i][8];
					$fecha = $data->sheets[0]['cells'][$i][10];
					$tipo_mon = "Soles";
					$usuario = $_SESSION["id"];
					$saldo = 0;
					$usureg = $_SESSION["nombre"];
					$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

					$existe = ControladorCuentas::ctrMostrarCuentas("num_unico", $unico);
					#var_dump($existe["num_cta"]);

					if ($existe["saldo"] > 0) {

						$intoA .= "('85'" . ",'" . $existe["num_cta"] . "','" . $existe["cliente"] . "','" . $existe["vendedor"] . "','" . substr($fecha, 6, 4) . "-" . substr($fecha, 3, 2) . "-" . substr($fecha, 0, 2) . "','" . substr($fecha_ven, 6, 4) . "-" . substr($fecha_ven, 3, 2) . "-" . substr($fecha_ven, 0, 2) . "','Soles'," . $montoReducido . ",'NO-" . $unico . "','PENDIENTE','00','" . $existe["doc_origen"] . "','" . $usuario . "'," . $saldo . ",'" . $unico . "','-','" . $usureg . "','" . $pcreg . "'),";

						$saldoImportar = $existe["saldo"] - $montoReducido;
						$actualizarSaldo = ModeloCuentas::mdlActualizarUnDato("cuenta_ctejf", "saldo", $saldoImportar, $existe["id"]);

						$actualizarPago = ModeloCuentas::mdlActualizarUnDato("cuenta_ctejf", "ult_pago", substr($fecha, 6, 4) . "-" . substr($fecha, 3, 2) . "-" . substr($fecha, 0, 2), $existe["id"]);

						if ($saldoImportar == 0) {
							$actualizarEstado = ModeloCuentas::mdlActualizarUnDato("cuenta_ctejf", "estado", 'CANCELADO', $existe["id"]);
						}
					}
				}
			}

			$detalle = substr($intoA, 0, -1);
			#var_dump("detalle", $detalle);
			$respuestaMovimientos = ModeloCuentas::mdlRegistrarCancelacionLetras($detalle);
			#var_dump($respuestaMovimientos);  

			echo '<script>

				swal({
					type: "success",
					title: "Las cuentas han sido canceladas correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "cuentas";

								}
							})

				</script>';
		}
	}

	//Actualizar numero unico
	static public function ctrImportarLetra()
	{

		if (isset($_POST["importLetra"])) {

			include "/../vistas/reportes_excel/Excel/reader.php";
			$directorio = "vistas/cuentas/" . $_FILES["nuevaUnico"]["name"];
			$archivo = move_uploaded_file($_FILES["nuevaUnico"]['tmp_name'], $directorio);
			$data = new Spreadsheet_Excel_Reader();
			$data->setOutputEncoding('CP1251');
			$data->read("vistas/cuentas/" . $_FILES["nuevaUnico"]["name"]);
			$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
			$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
			mysql_select_db($con["db"], $conexion);
			for ($i = 6; $i <= $data->sheets[0]['numRows']; $i++) {
				for ($j = 1; $j <= 1; $j++) {
					$documento = $data->sheets[0]['cells'][$i][1];
					$unico = $data->sheets[0]['cells'][$i][2];

					$sqlInsertar = mysql_query("UPDATE 
							cuenta_ctejf 
						  SET
							num_unico = '" . $unico . "' 
						  WHERE REPLACE(num_cta, '-', '') = '" . $documento . "' 
							AND tipo_doc = '85' 
							AND tip_mov = '+' 
							AND YEAR(fecha) >= 2019 ") or die(mysql_error());
				}
			}
			echo '<script>

				swal({
					type: "success",
					title: "Las cuentas han sido canceladas correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "cuentas";

								}
							})

				</script>';
		}
	}

	/*=============================================
	RANGO FECHAS CUENTAS
	=============================================*/

	static public function ctrRangoFechasCuentas($ano)
	{

		$tabla = "cuenta_ctejf";

		$respuesta = ModeloCuentas::mdlRangoFechasCuentas($tabla, $ano);

		return $respuesta;
	}

	/*=============================================
	RANGO FECHAS CUENTAS CON PAGINACIÓN SERVIDOR
	=============================================*/

	static public function ctrRangoFechasCuentasPaginado($ano, $start, $length, $search, $orderColumn, $orderDir)
	{

		$tabla = "cuenta_ctejf";

		$respuesta = ModeloCuentas::mdlRangoFechasCuentasPaginado($tabla, $ano, $start, $length, $search, $orderColumn, $orderDir);

		return $respuesta;
	}

	/*=============================================
	RANGO FECHAS CUENTAS
	=============================================*/

	static public function ctrRangoFechasCuentasPendientes($ano)
	{

		$tabla = "cuenta_ctejf";

		$respuesta = ModeloCuentas::mdlRangoFechasCuentasPendientes($tabla, $ano);

		return $respuesta;
	}

	/*=============================================
	RANGO FECHAS CUENTAS
	=============================================*/

	static public function ctrRangoFechasCuentasAprobadas($ano)
	{

		$tabla = "cuenta_ctejf";

		$respuesta = ModeloCuentas::mdlRangoFechasCuentasAprobadas($tabla, $ano);

		return $respuesta;
	}

	/*=============================================
	RANGO FECHAS ENVIO CUENTAS
	=============================================*/

	static public function ctrRangoFechaEnvioCuentas($fechaInicial, $fechaFinal)
	{

		$tabla = "envio_letra_cabecerajf";

		$respuesta = ModeloCuentas::mdlRangoFechaEnvioCuentas($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
	}

	/*=============================================
	CANCELAR CUENTAS
	=============================================*/

	static public function ctrCancelarCuenta2()
	{

		if (isset($_POST["cancelarDocumento2"])) {

			$tabla = "cuenta_ctejf";
			$usureg = $_SESSION["nombre"];
			$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);
			$rutas = $_POST["rutas"];

			$datos = array(
				"id" 		=> $_POST["idCuenta3"],
				"tipo_doc"	=> $_POST["cancelarTipoDocumento2"],
				"num_cta"	=> $_POST["cancelarDocumentoOriginal2"],
				"cliente"	=> $_POST["cancelarCliente2"],
				"vendedor"	=> $_POST["cancelarVendedor2"],
				"monto"		=> $_POST["cancelarMonto3"],
				"notas"		=> $_POST["cancelarNota2"],
				"usuario"	=> $_POST["cancelarUsuario2"],
				"fecha"		=> $_POST["cancelarFechaUltima2"],
				"fecha_ven"	=> $_POST["cancelarVencimientoOrigen2"],
				"cod_pago" 	=> $_POST["cancelarCodigo2"],
				"doc_origen" => $_POST["cancelarDocumento2"],
				"saldo"		=> 0,
				"tip_mov" 	=> "-",
				"usureg" 	=> $usureg,
				"pcreg" 	=> $pcreg,
				"fecha_ori" => 	$_POST["cancelarFechaOrigen2"]
			);

			$saldoNuevo = $_POST["cancelarSaldo2"];
			$estado = $saldoNuevo >= -0.5 && $saldoNuevo <= 0.5 ? "CANCELADO" : "PENDIENTE";
			$ult_pago = $_POST["cancelarFechaUltima2"];
			$id = $_POST["idCuenta3"];

			$actualizado = ModeloCuentas::mdlActualizarCuenta($saldoNuevo, $estado, $ult_pago, $id);

			$respuesta = ModeloCuentas::mdlIngresarCuenta($tabla, $datos);

			if ($respuesta == "ok") {

				$ultimo_pago = ModeloCuentas::mdlEditarUltPago($_POST["cancelarDocumentoOriginal2"], $_POST["cancelarTipoDocumento2"]);

				echo '<script>	

					swal({
						  type: "success",
						  title: "La cuenta ha sido cancelada correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "index.php?ruta=ver-cuentas&numCta=' . $_POST["cancelarDocumentoOriginal2"] . '&codCuenta=' . $_POST["cancelarTipoDocumento2"] . '&rutas=' . $rutas . '";

									}
								})

					</script>';
			}
		}
	}

	/*=============================================
	CANCELAR CUENTAS
	=============================================*/

	static public function ctrDividirLetra()
	{

		if (isset($_POST["dividirNroDocumento2"])) {

			$tabla = "cuenta_ctejf";
			// Ingresar nueva cuenta de letra
			$datos = array(
				"tipo_doc"		=> $_POST["dividirDocumento"],
				"num_cta"		=> $_POST["dividirNroDocumento2"],
				"cliente"		=> $_POST["dividirCliente"],
				"vendedor"		=> $_POST["dividirVendedor"],
				"monto"			=> $_POST["dividirMonto"],
				"saldo"			=> $_POST["dividirMonto"],
				"usuario"		=> $_POST["dividirUsuario"],
				"fecha"			=> $_POST["dividirFecha2"],
				"estado"		=> "PENDIENTE",
				"notas"			=> "Renovación",
				"fecha_ven"		=> $_POST["dividirFechaVencimiento2"],
				"fecha_cep"		=> $_POST["dividirNumUnico"],
				"num_unico"		=> $_POST["dividirNumUnico"],
				"cod_pago" 		=> $_POST["dividirDocumento"],
				"doc_origen" 	=> $_POST["dividirNroDocumento"],
				"tip_mon" 		=> "Soles",
				"renovacion" 	=> 1,
				"protesta" 		=> 0,
				"estado_doc"	=> "01",
				"tip_mov" 		=> "+",
				"fecha_envio"	=> $_POST["dividirFecha2"],
				"fecha_cep"		=> $_POST["dividirFecha2"],
				"banco"			=> "02"
			);

			$saldoNuevo = $_POST["dividirSaldo"] - $_POST["dividirMonto"];
			$actualizado = ModeloCuentas::mdlActualizarUnDato($tabla, "saldo", $saldoNuevo, $_POST["idCuenta4"]);
			$respuesta = ModeloCuentas::mdlIngresarCuenta($tabla, $datos);

			// ingresar Cancelación
			$usureg = $_SESSION["nombre"];
			$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			$datos2 = array(
				"tipo_doc" => $_POST["dividirDocumento"],
				"num_cta" => $_POST["dividirNroDocumento"],
				"cliente" => $_POST["dividirCliente"],
				"vendedor" => $_POST["dividirVendedor"],
				"monto" => $_POST["dividirMonto"],
				"notas" => "Renovación",
				"usuario" => $_POST["dividirUsuario"],
				"fecha" => $_POST["dividirFecha2"],
				"fecha_ven" => $_POST["dividirFechaVencimiento2"],
				"cod_pago" => $_POST["dividirDocumento"],
				"doc_origen" => $_POST["dividirNroDocumento2"],
				"saldo" => 0,
				"tip_mov" => "-",
				"usureg" => $usureg,
				"pcreg" => $pcreg,
				"fecha_ori" => $_POST["dividirFecha"]
			);
			$respuesta2 = ModeloCuentas::mdlIngresarCuenta($tabla, $datos2);

			if ($respuesta == "ok") {

				echo '<script>	

					swal({
						  type: "success",
						  title: "La letra ha sido dividida correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "index.php?ruta=' . obtenerRutaCuentas() . '";

									}
								})

					</script>';
			}
		}
	}

	static public function ctrCrearEnvioLetras()
	{
		if (isset($_POST["nuevoCodigoEnvio"])) {

			if ($_POST["listaEnvioLetra"] == "") {
				# Mostramos una alerta suave
				echo '<script>
						swal({
							type: "error",
							title: "Error",
							text: "¡No se seleccionó ninguna cuenta. Por favor, intenteló de nuevo!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
						}).then((result)=>{
							if(result.value){
								window.location="ver-envio-letras";}
						});
					</script>';
			} else {

				$tabla = "envio_letra_cabecerajf";
				date_default_timezone_set('America/Lima');
				$fecha = new DateTime();
				$ruta = "vistas/letras/L" . $fecha->format("Y") . $fecha->format("m") . $fecha->format("d") . $fecha->format("H") . $fecha->format("i") . ".txt";
				$file = fopen($ruta, "w");
				$datos = array(
					"codigo"	=> 	$_POST["nuevoCodigoEnvio"],
					"fecha"		=>	$fecha->format("Y-m-d"),
					"usuario"	=>	$_POST["idUsuario"],
					"cantidad"	=>	$_POST["nuevoTotalCuentaEnvio"],
					"archivo"	=>	$ruta
				);

				$respuesta = ModeloCuentas::mdlIngresarEnvioLetra($tabla, $datos);

				//DETALLE

				$cuentas = json_decode($_POST["listaEnvioLetra"], true);

				//Creo nombre del archivo txt y asigno ruta


				foreach ($cuentas as $key => $value) {

					$tabla2 = "envio_letrasjf";
					$datos2 = array(
						"num_cta" => $value["numcta"],
						"codigo" => $_POST["nuevoCodigoEnvio"]
					);

					$respuesta2 = ModeloCuentas::mdlIngresarEnvioLetraDetalle($tabla2, $datos2);
					$actualizarEnvio = ModeloCuentas::mdlActualizarUnDato("cuenta_ctejf", "fecha_envio", $fecha->format("Y-m-d"), $value["idcuenta"]);
					$actualizarAceptacion = ModeloCuentas::mdlActualizarUnDato("cuenta_ctejf", "fecha_cep", $fecha->format("Y-m-d"), $value["idcuenta"]);
					$actualizarEstadoDoc = ModeloCuentas::mdlActualizarUnDato("cuenta_ctejf", "estado_doc", "01", $value["idcuenta"]);
					$actualizarBanco = ModeloCuentas::mdlActualizarUnDato("cuenta_ctejf", "banco", "02", $value["idcuenta"]);

					if (strlen($value["cliente_doc"]) == "11") {

						$cliente_doc = "6" . $value["cliente_doc"];
					} else {

						$cliente_doc = "1" . $value["cliente_doc"];
					}

					//saltos para el txt
					$salto1 = 72;
					$salto2 = 24;
					$salto3 = 24;
					$salto4 = 16;
					$salto5 = 12;

					if (strlen($value["monto"]) == 7) {

						$salto6 = 13;
					} else {

						$salto6 = 14;
					}


					//$salto6= 14 ;

					$cliente_nom = ControladorContabilidad::eliminar_tildes($value["cliente_nom"]);
					$cliente_pat = ControladorContabilidad::eliminar_tildes($value["cliente_pat"]);
					$cliente_mat = ControladorContabilidad::eliminar_tildes($value["cliente_mat"]);

					fwrite($file,	str_pad($cliente_nom, $salto1) .
						str_pad($cliente_pat, $salto2) .
						str_pad($cliente_mat, $salto3) .
						str_pad($cliente_doc, $salto4) .
						str_pad($value["numcta"], $salto5) .
						str_pad($value["fecha"], $salto6) . $value["monto"] .
						PHP_EOL);
				}
				fclose($file);
				if ($respuesta == "ok"  && $respuesta2 == "ok") {

					echo '<script>

					swal({
						  type: "success",
						  title: "El envio de letra ha sido guardado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "ver-envio-letras";

									}
								})

					</script>';
				} else {

					echo '<script>

						swal({
							type: "error",
							title: "¡El envio no puede ir vacío o llevar caracteres especiales!",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
								if (result.value) {

								window.location = "ver-envio-letras";

								}
							})

					</script>';
				}
			}
		}
	}


	/*=============================================
	MOSTRAR REPORTES COBRAR
	=============================================*/

	static public function ctrMostrarReporteCobrar($orden1, $orden2, $tip_doc, $cli, $vend, $banco, $fin)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteCobrar($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco, $fin);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES VENCIDOS
	=============================================*/

	static public function ctrMostrarReporteVencidos($orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco);

		return $respuesta;
	}

	static public function ctrMostrarSaldosFecha($fin)
	{

		$respuesta = ModeloCuentas::mdlMostrarSaldosFecha($fin);
		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES NO VENCIDOS
	=============================================*/

	static public function ctrMostrarReporteNoVencidos($orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteNoVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES PROTESTADOS
	=============================================*/

	static public function ctrMostrarReporteProtestados($orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteProtestados($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES PAGOS
	=============================================*/

	static public function ctrMostrarReportePagos($orden1, $orden2, $canc, $vend, $inicio, $fin)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReportePagos($tabla, $orden1, $orden2, $canc, $vend, $inicio, $fin);

		return $respuesta;
	}
	/*=============================================
	MOSTRAR REPORTES TOTAL COBRAR
	=============================================*/

	static public function ctrMostrarReporteTotalCobrar($orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteTotalCobrar($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco);

		return $respuesta;
	}


	/*=============================================
	MOSTRAR REPORTES TOTAL COBRAR 8VO DIA
	=============================================*/

	static public function ctrMostrarReporteTotalOct($tip_doc, $banco, $fin)
	{

		$respuesta = ModeloCuentas::mdlMostrarReporteTotalOct($tip_doc, $banco, $fin);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES TOTAL VENCIDOS
	=============================================*/

	static public function ctrMostrarReporteTotalVencidos($orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteTotalVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES TOTAL NO VENCIDOS
	=============================================*/

	static public function ctrMostrarReporteTotalNoVencidos($orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteTotalNoVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES TOTAL PROTESTADOS
	=============================================*/

	static public function ctrMostrarReporteTotalProtestados($orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteTotalProtestados($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES TOTAL PAGOS
	=============================================*/

	static public function ctrMostrarReporteTotalPagos($orden1, $orden2, $canc, $vend, $inicio, $fin)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteTotalPagos($tabla, $orden1, $orden2, $canc, $vend, $inicio, $fin);

		return $respuesta;
	}


	/*=============================================
	MOSTRAR REPORTES NOMBRE
	=============================================*/

	static public function ctrMostrarReporteNombre($cli, $vend)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteNombre($tabla, $cli, $vend);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES NOMBRE VENCIDOS
	=============================================*/
	static public function ctrMostrarReporteNombreVencidos($cli, $vend)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteNombreVencidos($tabla, $cli, $vend);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES NOMBRE NO VENCIDOS
	=============================================*/
	static public function ctrMostrarReporteNombreNoVencidos($cli, $vend)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteNombreNoVencidos($tabla, $cli, $vend);

		return $respuesta;
	}

	/*=============================================
	MOSTRAR REPORTES NOMBRE PROTESTADOS
	=============================================*/
	static public function ctrMostrarReporteNombreProtestados($cli, $vend)
	{
		$tabla = "cuenta_ctejf";
		$respuesta = ModeloCuentas::mdlMostrarReporteNombreProtestados($tabla, $cli, $vend);

		return $respuesta;
	}

	/*=============================================
	ESTADO DE CUENTA CABECERA
	=============================================*/
	static public function ctrEstadoCuentaCab($cliente, $vendedor)
	{

		$respuesta = ModeloCuentas::ctrEstadoCuentaCab($cliente, $vendedor);

		return $respuesta;
	}

	/*=============================================
	DOCUMENTOS CONTADO PENDIENTES
	=============================================*/
	static public function ctrContadoPendientes()
	{

		$respuesta = ModeloCuentas::mdlContadoPendientes();

		return $respuesta;
	}

	/*=============================================
	DOCUMENTOS CONTADO PENDIENTES
	=============================================*/
	static public function ctrLetrasAceptar($vendedor, $mes, $ini, $fin)
	{

		$respuesta = ModeloCuentas::mdlLetrasAceptar($vendedor, $mes, $ini, $fin);

		return $respuesta;
	}

	/*=============================================
	DOCUMENTOS CONTADO PENDIENTES
	=============================================*/
	static public function ctrLetrasAceptarTotal($vendedor, $mes)
	{

		$respuesta = ModeloCuentas::mdlLetrasAceptarTotal($vendedor, $mes);

		return $respuesta;
	}

	/*=============================================
	ESTADO DE CUENTA DETALLE
	=============================================*/
	static public function ctrEstadoCuentaDet($cliente, $vendedor)
	{

		$respuesta = ModeloCuentas::ctrEstadoCuentaDet($cliente, $vendedor);

		return $respuesta;
	}

	/*=============================================
	ESTADO DE CUENTA DETALLE
	=============================================*/
	static public function ctrProyeccionPagos()
	{

		$respuesta = ModeloCuentas::mdlProyeccionPagos();

		return $respuesta;
	}

	/*=============================================
	ESTADO DE CUENTA DETALLE PROTESTO
	=============================================*/
	static public function ctrEstadoCuentaProt($cliente)
	{

		$respuesta = ModeloCuentas::mdlEstadoCuentaProt($cliente);

		return $respuesta;
	}

	//* ESTADO DE CUENTA GRAL
	static public function ctrEstadoCtaVdor($vendedor)
	{

		$respuesta = ModeloCuentas::mdlEstadoCtaVdor($vendedor);

		return $respuesta;
	}

	//DOCUMENTOS VENCIDOS POR ZONA
	static public function ctrEstadoCtaVdorVdos($vendedor)
	{

		$respuesta = ModeloCuentas::mdlEstadoCtaVdorVdos($vendedor);

		return $respuesta;
	}

	//DOCUMENTOS NO VENCIDOS POR ZONA
	static public function ctrEstadoCtaVdorNoVdos($vendedor)
	{

		$respuesta = ModeloCuentas::mdlEstadoCtaVdorNoVdos($vendedor);

		return $respuesta;
	}

	//ESTADO DE CUENTA
	static public function ctrEstadoCuenta($fin)
	{

		$respuesta = ModeloCuentas::mdlEstadoCuenta($fin);

		return $respuesta;
	}

	//ESTADO DE CUENTA
	static public function ctrControlFechas()
	{

		$respuesta = ModeloCuentas::mdlControlFechas();

		return $respuesta;
	}

	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function ctrUltPagos($cliente)
	{

		$respuesta = ModeloCuentas::mdlUltPagos($cliente);

		return $respuesta;
	}

	/*=============================================
	SALDOS A UNA FECHA
	=============================================*/

	static public function ctrSaldoFecha($inicio, $fin)
	{

		$respuesta = ModeloCuentas::mdlSaldoFecha($inicio, $fin);

		return $respuesta;
	}

	static public function ctrVerCredipagos()
	{

		$respuesta = ModeloCuentas::ctrVerCredipagos();

		return $respuesta;
	}
}
