<?php

class ControladorAbonos
{

	/*=============================================
	CATÁLOGO MOTIVOS PENDIENTE DE APLICAR
	=============================================*/

	static public function ctrMotivosPendiente()
	{
		return array(
			"no_identificado" => "No identificado",
			"referencia_incompleta" => "Referencia incompleta",
			"monto_no_coincide" => "Monto no coincide",
			"duplicado" => "Duplicado / ya aplicado",
			"pendiente_confirmacion" => "Pendiente de confirmación",
			"otro" => "Otro",
		);
	}

	static public function ctrEtiquetaMotivoPendiente($codigo)
	{
		if ($codigo === null || $codigo === "") {
			return "";
		}
		$motivos = self::ctrMotivosPendiente();
		return isset($motivos[$codigo]) ? $motivos[$codigo] : $codigo;
	}

	/*=============================================
	CATÁLOGO AGENCIAS / CANALES BCP
	=============================================*/

	static public function ctrCatalogoAgenciasBcp()
	{
		static $indice = null;
		if ($indice !== null) {
			return $indice;
		}

		$indice = array(
			"por_codigo" => array(),
			"por_numerico" => array(),
		);

		$ruta = __DIR__ . "/bcp-agencias.json";
		if (!is_readable($ruta)) {
			return $indice;
		}

		$raw = file_get_contents($ruta);
		$data = json_decode($raw, true);
		if (!is_array($data) || empty($data["codigos"]) || !is_array($data["codigos"])) {
			return $indice;
		}

		foreach ($data["codigos"] as $item) {
			if (!is_array($item)) {
				continue;
			}
			$codigo = isset($item["codigo"]) ? trim((string) $item["codigo"]) : "";
			$numerico = isset($item["codigo_numerico"])
				? preg_replace('/\D+/', '', (string) $item["codigo_numerico"])
				: "";
			if ($numerico === "" && $codigo !== "") {
				$numerico = preg_replace('/\D+/', '', $codigo);
			}

			$tipo = isset($item["tipo"]) ? strtoupper((string) $item["tipo"]) : "";
			if ($tipo === "AGENCIA") {
				$nombre = isset($item["agencia"]) ? trim((string) $item["agencia"]) : "";
			} else {
				$nombre = isset($item["descripcion"]) ? trim((string) $item["descripcion"]) : "";
			}
			if ($nombre === "") {
				continue;
			}

			$entrada = array(
				"codigo" => $codigo,
				"codigo_numerico" => $numerico,
				"tipo" => $tipo,
				"nombre" => $nombre,
			);

			if ($codigo !== "") {
				$indice["por_codigo"][strtoupper($codigo)] = $entrada;
			}
			if ($numerico !== "") {
				$indice["por_numerico"][$numerico] = $entrada;
			}
		}

		return $indice;
	}

	/**
	 * Resuelve el código de agencia del abono contra el catálogo BCP.
	 * @return array|null {codigo, codigo_numerico, tipo, nombre} o null
	 */
	static public function ctrResolverAgenciaBcp($agenciaRaw)
	{
		$agenciaRaw = is_string($agenciaRaw) ? trim($agenciaRaw) : "";
		if ($agenciaRaw === "") {
			return null;
		}

		$indice = self::ctrCatalogoAgenciasBcp();
		$claveExacta = strtoupper($agenciaRaw);
		if (isset($indice["por_codigo"][$claveExacta])) {
			return $indice["por_codigo"][$claveExacta];
		}

		$numerico = preg_replace('/\D+/', '', $agenciaRaw);
		if ($numerico !== "" && isset($indice["por_numerico"][$numerico])) {
			return $indice["por_numerico"][$numerico];
		}

		return null;
	}

	/** HTML para columna Agencia: código + nombre si hay match. */
	static public function ctrHtmlAgenciaAbono($agenciaRaw)
	{
		$agenciaRaw = is_string($agenciaRaw) ? trim($agenciaRaw) : "";
		if ($agenciaRaw === "") {
			return "<span class='text-muted'>—</span>";
		}

		$seguro = htmlspecialchars($agenciaRaw, ENT_QUOTES, "UTF-8");
		$match = self::ctrResolverAgenciaBcp($agenciaRaw);
		if ($match === null) {
			return $seguro;
		}

		$nombre = htmlspecialchars($match["nombre"], ENT_QUOTES, "UTF-8");
		return $seguro . " <small class='text-muted'>— " . $nombre . "</small>";
	}

	/*=============================================
	ESTADÍSTICAS MENSUALES: PENDIENTES VS APLICADOS
	=============================================*/

	static public function ctrAnioMinimoAbonos()
	{
		return 2026;
	}

	static public function ctrEstadisticasMensuales($anio = null, $mes = null)
	{
		date_default_timezone_set("America/Lima");
		$anioMin = self::ctrAnioMinimoAbonos();
		$anioHoy = (int) date("Y");
		$mesHoy = (int) date("n");

		$anio = $anio === null || $anio === "" ? max($anioHoy, $anioMin) : (int) $anio;
		if ($anio < $anioMin) {
			$anio = $anioMin;
		}
		if ($anio > $anioHoy) {
			$anio = $anioHoy;
		}

		$mesRaw = is_string($mes) ? trim($mes) : $mes;
		$periodoAnioCompleto = (
			$mesRaw === null
			|| $mesRaw === ""
			|| $mesRaw === "todos"
			|| (string) $mesRaw === "0"
		);

		$mesNum = null;
		if (!$periodoAnioCompleto) {
			$mesNum = (int) $mesRaw;
			if ($mesNum < 1 || $mesNum > 12) {
				$mesNum = $anio === $anioHoy ? $mesHoy : 1;
				$periodoAnioCompleto = false;
			}
		}

		$mesesNombre = array(
			1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
			5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
			9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre",
		);

		$vacio = function ($m, $nombre) {
			return array(
				"mes" => $m,
				"mes_nombre" => $nombre,
				"pendientes_cant" => 0,
				"pendientes_monto" => 0.0,
				"aplicados_cant" => 0,
				"aplicados_monto" => 0.0,
				"total_cant" => 0,
				"pct_pendiente" => null,
			);
		};

		$porMes = array();
		for ($m = 1; $m <= 12; $m++) {
			$porMes[$m] = $vacio($m, $mesesNombre[$m]);
		}

		$pendientes = ModeloAbonos::mdlPendientesPorMes($anio);
		if (is_array($pendientes)) {
			foreach ($pendientes as $fila) {
				$m = (int) $fila["mes"];
				if (!isset($porMes[$m])) {
					continue;
				}
				$porMes[$m]["pendientes_cant"] = (int) $fila["cantidad"];
				$porMes[$m]["pendientes_monto"] = (float) $fila["monto"];
			}
		}

		$aplicados = ModeloAbonos::mdlAplicadosPorMes($anio);
		if (is_array($aplicados)) {
			foreach ($aplicados as $fila) {
				$m = (int) $fila["mes"];
				if (!isset($porMes[$m])) {
					continue;
				}
				$porMes[$m]["aplicados_cant"] = (int) $fila["cantidad"];
				$porMes[$m]["aplicados_monto"] = (float) $fila["monto"];
			}
		}

		$totPendCant = 0;
		$totPendMonto = 0.0;
		$totAplCant = 0;
		$totAplMonto = 0.0;

		foreach ($porMes as $m => $fila) {
			$totalCant = $fila["pendientes_cant"] + $fila["aplicados_cant"];
			$porMes[$m]["total_cant"] = $totalCant;
			$porMes[$m]["pct_pendiente"] = $totalCant > 0
				? round(($fila["pendientes_cant"] * 100.0) / $totalCant, 1)
				: null;

			$totPendCant += $fila["pendientes_cant"];
			$totPendMonto += $fila["pendientes_monto"];
			$totAplCant += $fila["aplicados_cant"];
			$totAplMonto += $fila["aplicados_monto"];
		}

		$totalAnio = $totPendCant + $totAplCant;
		$acumulado = array(
			"pendientes_cant" => $totPendCant,
			"pendientes_monto" => $totPendMonto,
			"aplicados_cant" => $totAplCant,
			"aplicados_monto" => $totAplMonto,
			"total_cant" => $totalAnio,
			"pct_pendiente" => $totalAnio > 0
				? round(($totPendCant * 100.0) / $totalAnio, 1)
				: null,
		);

		if ($periodoAnioCompleto) {
			$mesStats = $acumulado;
			$mesStats["mes"] = null;
			$mesStats["mes_nombre"] = "Todo el año";
			$mesNombrePeriodo = "Todo el año";
			$mesRespuesta = "todos";
		} else {
			$mesStats = $porMes[$mesNum];
			$mesNombrePeriodo = $mesesNombre[$mesNum];
			$mesRespuesta = $mesNum;
		}

		return array(
			"anio" => $anio,
			"mes" => $mesRespuesta,
			"mes_nombre" => $mesNombrePeriodo,
			"periodo_anio_completo" => $periodoAnioCompleto,
			"anio_minimo" => $anioMin,
			"del_mes" => $mesStats,
			"acumulado_anio" => $acumulado,
			"nota" => "Aplicados: tip_mov '-', códigos 05/15 y notas OP-. Aproximación operativa.",
		);
	}

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

	static public function ctrMostrarAbonos($item, $valor, $filtroMotivo = null, $anio = null, $mes = null)
	{
		$tabla = "abonosjf";
		$anioDesde = self::ctrAnioMinimoAbonos();
		$respuesta = ModeloAbonos::mdlMostrarAbonos($tabla, $item, $valor, $filtroMotivo, $anio, $mes, $anioDesde);

		return $respuesta;
	}

	/*=============================================
	EDITAR MOTIVO / OBSERVACIÓN PENDIENTE
	=============================================*/

	static public function ctrGuardarMotivoPendiente($idAbono, $motivo, $observacion)
	{
		$idAbono = (int) $idAbono;
		if ($idAbono <= 0) {
			return array("ok" => false, "mensaje" => "Abono inválido");
		}

		$motivos = self::ctrMotivosPendiente();
		$motivo = is_string($motivo) ? trim($motivo) : "";
		if ($motivo !== "" && !isset($motivos[$motivo])) {
			return array("ok" => false, "mensaje" => "Motivo no válido");
		}

		$observacion = is_string($observacion) ? trim($observacion) : "";
		if (strlen($observacion) > 500) {
			return array("ok" => false, "mensaje" => "La observación no puede superar 500 caracteres");
		}

		date_default_timezone_set("America/Lima");
		$usuario = isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "";

		$datos = array(
			"id" => $idAbono,
			"motivo_pendiente" => $motivo === "" ? null : $motivo,
			"observacion_pendiente" => $observacion === "" ? null : $observacion,
			"motivo_usuario" => $usuario !== "" ? $usuario : null,
			"motivo_fecha" => date("Y-m-d H:i:s"),
		);

		$respuesta = ModeloAbonos::mdlEditarMotivoPendiente("abonosjf", $datos);
		if ($respuesta !== "ok") {
			return array("ok" => false, "mensaje" => "No se pudo guardar el motivo");
		}

		return array(
			"ok" => true,
			"mensaje" => "Motivo guardado",
			"motivo" => $datos["motivo_pendiente"],
			"motivo_etiqueta" => self::ctrEtiquetaMotivoPendiente($datos["motivo_pendiente"]),
			"observacion" => $datos["observacion_pendiente"],
		);
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
