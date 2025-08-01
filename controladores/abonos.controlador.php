<?php

class ControladorAbonos
{

	/*=============================================
	CREAR TIPO DE PAGO
	=============================================*/

	static public function ctrCrearAbono()
	{

		if (isset($_POST["nuevaDescripcion"])) {

			$tabla = "abonosjf";
			$datos = array(
				"fecha" => $_POST["nuevaFecha"],
				"descripcion" => $_POST["nuevaDescripcion"],
				"monto" => $_POST["nuevoMonto"],
				"agencia" => $_POST["nuevaAgencia"],
				"num_ope" => $_POST["nuevoOpe"]
			);

			$respuesta = ModeloAbonos::mdlIngresarAbono($tabla, $datos);

			if ($respuesta == "ok") {

				echo '<script>

						swal({
							type: "success",
							title: "El abono ha sido guardado correctamente",
							showConfirmButton: true,
							confirmButtonText: "Cerrar"
							}).then(function(result){
								if (result.value) {

								window.location = "abonos";

								}
						})

				</script>';
			}
		}
	}


	/*=============================================
	MOSTRAR TIPO DE PAGO
	=============================================*/

	static public function ctrMostrarAbonos($item, $valor)
	{
		$tabla = "abonosjf";
		$respuesta = ModeloAbonos::mdlMostrarAbonos($tabla, $item, $valor);

		return $respuesta;
	}

	/*=============================================
	EDITAR TIPO DE PAGO
	=============================================*/

	static public function ctrEditarAbono()
	{

		if (isset($_POST["editarDescripcion"])) {

			$tabla = "abonosjf";
			$datos = array(
				"id" => $_POST["idAbono"],
				"fecha" => $_POST["editarFecha"],
				"descripcion" => $_POST["editarDescripcion"],
				"monto" => $_POST["editarMonto"],
				"agencia" => $_POST["editarAgencia"],
				"num_ope" => $_POST["editarOpe"]
			);

			$respuesta = ModeloAbonos::mdlEditarAbono($tabla, $datos);

			if ($respuesta == "ok") {

				echo '<script>

					swal({
						  type: "success",
						  title: "El abono ha sido cambiado correctamente",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
						  }).then(function(result){
									if (result.value) {

									window.location = "abonos";

									}
								})

					</script>';
			}
		}
	}

	/*=============================================
	ELIMINAR TIPO DE PAGO
	=============================================*/

	static public function ctrEliminarAbono()
	{

		if (isset($_GET["idAbono"])) {

			$datos = $_GET["idAbono"];
			$tabla = "abonosjf";
			date_default_timezone_set('America/Lima');
			$fecha = new DateTime();
			$abonos = ControladorAbonos::ctrMostrarAbonos("id", $datos);
			$usuario = $_SESSION["nombre"];
			$para      = 'notificacionesvascorp@gmail.com';
			$asunto    = 'Se elimino un abono';
			$descripcion   = 'El usuario ' . $usuario . ' elimino el abono ' . $abonos["descripcion"];
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

			$respuesta = ModeloAbonos::mdlEliminarAbono($tabla, $datos);
			if ($respuesta == "ok") {


				echo '<script>

				swal({
					  type: "success",
					  title: "El abono ha sido borrado correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "abonos";

								}
							})

				</script>';
			}
		}
	}

	/*=============================================
	IMPORTAR ABONOS DE BANCO
    =============================================*/

	static public function ctrImportarAbono()
	{

		if (isset($_POST["importAbono"])) {

			include "/../vistas/reportes_excel/Excel/reader.php";
			$directorio = "vistas/abonos/" . $_FILES["nuevoAbono"]["name"];
			$archivo = move_uploaded_file($_FILES["nuevoAbono"]['tmp_name'], $directorio);
			$data = new Spreadsheet_Excel_Reader();
			$data->setOutputEncoding('CP1251');
			$data->read("vistas/abonos/" . $_FILES["nuevoAbono"]["name"]);
			$con = ControladorUsuarios::ctrMostrarConexiones("id", 1);
			$conexion = mysql_connect($con["ip"], $con["user"], $con["pwd"]) or die("No se pudo conectar: " . mysql_error());
			mysql_select_db($con["db"], $conexion);
			for ($i = 6; $i <= $data->sheets[0]['numRows']; $i++) {
				for ($j = 1; $j <= 1; $j++) {
					$fecha = $data->sheets[0]['cells'][$i][1];
					$descripcion = $data->sheets[0]['cells'][$i][3];
					$monto = $data->sheets[0]['cells'][$i][4];
					$montoConv = str_replace(",", "", $monto);
					$agencia = $data->sheets[0]['cells'][$i][6];
					$operacion = $data->sheets[0]['cells'][$i][7];
					if (substr($descripcion, 0, 3) != "LET") {
						$sqlInsertar = mysql_query("INSERT INTO abonosjf (fecha,descripcion,monto,agencia,num_ope)  values('" . substr($fecha, 6, 4) . "-" . substr($fecha, 3, 2) . "-" . substr($fecha, 0, 2) . "','" . $descripcion . "'," . $montoConv . ",'" . $agencia . "','" . $operacion . "')");
					}
				}
			}
			echo '<script>

				swal({
					type: "success",
					title: "Las abonos han sido importados correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
					}).then(function(result){
								if (result.value) {

								window.location = "abonos";

								}
							})

				</script>';
		}
	}

	/*=============================================
	CANCELAR ABONOS DE BANCO
    =============================================*/

	static public function ctrCancelarAbono()
	{
		if (isset($_POST["editarCuenta"])) {

			$tabla = "cuenta_ctejf";
			$tabla2 = "abonosjf";

			$usureg = $_SESSION["nombre"];
			$pcreg = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			if ($_POST["editarSaldo"] >= $_POST["editarAbono"]) {

				$abono = $_POST["editarAbono"];
			} else {

				$abono = $_POST["editarSaldo"];
			}

			$datos = array(
				"id" 		=> $_POST["idCuenta4"],
				"tipo_doc"	=> $_POST["editarTipo"],
				"num_cta"	=> $_POST["editarCuenta"],
				"cod_pago"	=> $_POST["editarOperacion"],
				"doc_origen"	=> $_POST["editarCuenta"],
				"cliente"	=> $_POST["editarCliente"],
				"vendedor"	=> $_POST["editarVendedor"],
				"monto"		=> $abono,
				"saldo"		=> 0,
				"tip_mov"	=> "-",
				"notas"		=> "OP-" . $_POST["opAbono"],
				"renovacion"	=> 0,
				"protesta"	=> 0,
				"estado"		=> "PENDIENTE",
				"usuario"	=> $_POST["editarUsuario"],
				"fecha"		=> $_POST["editarFecha"],
				"fecha_ven"	=> $_POST["fechaVen"],
				"usureg" 	=> $usureg,
				"pcreg" 		=> $pcreg
			);

			$respuesta = ModeloCuentas::mdlIngresarCuenta($tabla, $datos);
			$saldoNuevo = $_POST["editarSaldo"] - $_POST["editarAbono"];
			var_dump($saldoNuevo);

			if ($saldoNuevo < 0) { //*cuando el abono es mayor al monto

				$vuelto = $saldoNuevo * -1;
				$abono = ModeloCuentas::mdlActualizarUnDato($tabla2, "monto", $vuelto, $_POST["idAbono"]);
				$cuenta = ModeloCuentas::mdlActualizarUnDato($tabla, "saldo", 0, $_POST["idCuenta4"]);
				$cuenta = ModeloCuentas::mdlActualizarUnDato($tabla, "estado", "CANCELADO", $_POST["idCuenta4"]);
			} else if ($saldoNuevo == 0) {

				$cuenta = ModeloCuentas::mdlActualizarUnDato($tabla, "saldo", 0, $_POST["idCuenta4"]);
				$cuenta = ModeloCuentas::mdlActualizarUnDato($tabla, "estado", "CANCELADO", $_POST["idCuenta4"]);
				$abono = ModeloAbonos::mdlEliminarAbono($tabla2, $_POST["idAbono"]);
			} else {

				$cuenta = ModeloCuentas::mdlActualizarUnDato($tabla, "saldo", $saldoNuevo, $_POST["idCuenta4"]);
				$cuenta = ModeloCuentas::mdlActualizarUnDato($tabla, "estado", "PENDIENTE", $_POST["idCuenta4"]);
				$abono = ModeloAbonos::mdlEliminarAbono($tabla2, $_POST["idAbono"]);
			}

			if ($respuesta == "ok") {

				$ultimo_pago = ModeloCuentas::mdlEditarUltPago($_POST["editarCuenta"], $_POST["editarTipo"]);

				echo '<script>

				swal({
						type: "success",
						title: "El abono ha sido cancelado correctamente",
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
						}).then(function(result){
								if (result.value) {
	
								window.location = "cancelar-abonos";
	
								}
							})
	
				</script>';
			}
		}
	}
}
