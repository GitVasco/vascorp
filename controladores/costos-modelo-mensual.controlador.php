<?php

class ControladorCostosModeloMensual
{
	static private function ctrPuedeVer()
	{
		return function_exists("usuarioPuedeVerModulo")
			&& usuarioPuedeVerModulo("gestion_comercial", "costos_modelo");
	}

	static private function ctrPuedeEditar()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "costos_modelo", "editar");
	}

	static private function ctrPuedeAprobar()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "costos_modelo", "aprobar");
	}

	static private function ctrPeriodoValido($anio, $mes)
	{
		return (int) $anio >= 2000
			&& (int) $anio <= 2100
			&& (int) $mes >= 1
			&& (int) $mes <= 12;
	}

	static private function ctrNormalizarCosto($valor)
	{
		return self::ctrNormalizarImporte($valor, 10);
	}

	static private function ctrNormalizarImporte($valor, $maximoEnteros)
	{
		$valor = trim(str_replace(" ", "", (string) $valor));
		if (strpos($valor, ",") !== false && strpos($valor, ".") === false) {
			$valor = str_replace(",", ".", $valor);
		}
		$patron = '/^(\d{1,' . (int) $maximoEnteros . '})(?:\.(\d{1,18}))?$/';
		if (!preg_match($patron, $valor, $partes)) {
			return null;
		}
		$entero = ltrim($partes[1], "0");
		$entero = $entero === "" ? "0" : $entero;
		$decimales = isset($partes[2]) ? $partes[2] : "";
		$decimales = str_pad($decimales, 5, "0", STR_PAD_RIGHT);
		$fijos = substr($decimales, 0, 4);

		if ((int) $decimales[4] >= 5) {
			$digitos = $entero . $fijos;
			$acarreo = 1;
			for ($indice = strlen($digitos) - 1; $indice >= 0 && $acarreo === 1; $indice--) {
				$digito = (int) $digitos[$indice] + 1;
				$digitos[$indice] = (string) ($digito % 10);
				$acarreo = $digito > 9 ? 1 : 0;
			}
			if ($acarreo === 1) {
				$digitos = "1" . $digitos;
			}
			$entero = substr($digitos, 0, -4);
			$fijos = substr($digitos, -4);
		}

		$entero = ltrim($entero, "0");
		$entero = $entero === "" ? "0" : $entero;
		if (strlen($entero) > (int) $maximoEnteros) {
			return null;
		}
		return $entero . "." . $fijos;
	}

	static public function ctrListar($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso para consultar costos");
		}

		$anio = isset($post["anio"]) ? (int) $post["anio"] : (int) date("Y");
		$mes = isset($post["mes"]) ? (int) $post["mes"] : (int) date("n");
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$estado = trim(isset($post["estado"]) ? $post["estado"] : "");

		if (!self::ctrPeriodoValido($anio, $mes)) {
			return array("ok" => false, "mensaje" => "Período inválido");
		}
		if (!in_array($estado, array("", "sin_costo", "borrador", "aprobado", "anulado"), true)) {
			return array("ok" => false, "mensaje" => "Estado inválido");
		}

		try {
			$lista = ModeloCostosModeloMensual::mdlListarCostosPeriodo($anio, $mes, $idMarca, $estado);
			return array("ok" => true, "data" => $lista ? $lista : array());
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron consultar los costos. Verifica que el SQL del módulo esté instalado.");
		}
	}

	static public function ctrListarMarcas()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		try {
			$lista = ModeloCostosModeloMensual::mdlListarMarcas();
			return array("ok" => true, "data" => $lista ? $lista : array());
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar el catálogo de marcas");
		}
	}

	static public function ctrGuardarBorrador($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar costos");
		}

		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		$costo = self::ctrNormalizarCosto(isset($post["costo_unitario"]) ? $post["costo_unitario"] : "");
		$fuente = trim(isset($post["fuente"]) ? $post["fuente"] : "");
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");
		$usuario = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;

		if ($modelo === "" || strlen($modelo) > 50) {
			return array("ok" => false, "mensaje" => "Modelo inválido");
		}
		if (!self::ctrPeriodoValido($anio, $mes)) {
			return array("ok" => false, "mensaje" => "Período inválido");
		}
		if ($costo === null) {
			return array("ok" => false, "mensaje" => "El costo debe ser un número no negativo válido");
		}
		if (strlen($fuente) > 100 || strlen($observacion) > 500) {
			return array("ok" => false, "mensaje" => "Fuente u observación exceden la longitud permitida");
		}
		if ($usuario < 1) {
			return array("ok" => false, "mensaje" => "Sesión inválida");
		}

		$resultado = ModeloCostosModeloMensual::mdlGuardarBorrador(array(
			"modelo" => $modelo,
			"anio" => $anio,
			"mes" => $mes,
			"costo_unitario" => $costo,
			"fuente" => $fuente !== "" ? $fuente : null,
			"observacion" => $observacion !== "" ? $observacion : null,
			"usuario" => $usuario
		));

		if ($resultado === "ok") {
			return array("ok" => true, "mensaje" => "Costo guardado como borrador");
		}
		if ($resultado === "modelo_invalido") {
			return array("ok" => false, "mensaje" => "El modelo no existe o no está activo");
		}
		if ($resultado === "bloqueado") {
			return array("ok" => false, "mensaje" => "El costo ya no es borrador; debe reabrirse antes de modificarlo");
		}
		return array("ok" => false, "mensaje" => "No se pudo guardar el costo");
	}

	static public function ctrCambiarEstado($post)
	{
		if (!self::ctrPuedeAprobar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para aprobar o cambiar estados");
		}

		$accion = trim(isset($post["cambio_estado"]) ? $post["cambio_estado"] : "");
		$motivo = trim(isset($post["motivo"]) ? $post["motivo"] : "");
		$idsEntrada = isset($post["ids"]) ? $post["ids"] : array();
		if (is_string($idsEntrada)) {
			$idsEntrada = json_decode($idsEntrada, true);
		}
		$ids = is_array($idsEntrada) ? array_values(array_unique(array_map("intval", $idsEntrada))) : array();
		$usuario = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;

		if (!in_array($accion, array("aprobar", "anular", "reabrir"), true)) {
			return array("ok" => false, "mensaje" => "Acción inválida");
		}
		if (empty($ids) || count($ids) > 500) {
			return array("ok" => false, "mensaje" => "Selecciona entre 1 y 500 costos");
		}
		if (($accion === "anular" || $accion === "reabrir") && $motivo === "") {
			return array("ok" => false, "mensaje" => "El motivo es obligatorio");
		}
		if (strlen($motivo) > 500 || $usuario < 1) {
			return array("ok" => false, "mensaje" => "Motivo o sesión inválidos");
		}

		$resultado = ModeloCostosModeloMensual::mdlCambiarEstado(
			$ids,
			$accion,
			$motivo !== "" ? $motivo : null,
			$usuario
		);
		if ($resultado === "ok") {
			$mensajes = array(
				"aprobar" => "Costos aprobados correctamente",
				"anular" => "Costos anulados correctamente",
				"reabrir" => "Costos reabiertos como borrador"
			);
			return array("ok" => true, "mensaje" => $mensajes[$accion]);
		}
		if ($resultado === "estado_invalido") {
			return array("ok" => false, "mensaje" => "Uno de los costos ya cambió de estado. Actualiza la grilla.");
		}
		return array("ok" => false, "mensaje" => "No se pudo completar el cambio de estado");
	}

	static public function ctrAprobarPeriodo($post)
	{
		if (!self::ctrPuedeAprobar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para aprobar costos");
		}
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		$usuario = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
		if (!self::ctrPeriodoValido($anio, $mes) || $usuario < 1) {
			return array("ok" => false, "mensaje" => "Período o sesión inválidos");
		}

		try {
			$ids = ModeloCostosModeloMensual::mdlIdsBorradoresPeriodo($anio, $mes);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron consultar los borradores");
		}
		if (empty($ids)) {
			return array("ok" => false, "mensaje" => "No hay costos en borrador para aprobar");
		}

		$resultado = ModeloCostosModeloMensual::mdlCambiarEstado($ids, "aprobar", null, $usuario);
		if ($resultado === "ok") {
			return array("ok" => true, "mensaje" => count($ids) . " costos aprobados en el período");
		}
		return array("ok" => false, "mensaje" => "Un costo cambió de estado. Actualiza la grilla e inténtalo nuevamente.");
	}

	static public function ctrCostoAprobado($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso para consultar costos");
		}
		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		if ($modelo === "" || strlen($modelo) > 50 || !self::ctrPeriodoValido($anio, $mes)) {
			return array("ok" => false, "mensaje" => "Modelo o período inválidos");
		}

		try {
			$costo = ModeloCostosModeloMensual::mdlCostoAprobado($modelo, $anio, $mes);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo consultar el costo aprobado");
		}
		if (!$costo) {
			return array(
				"ok" => true,
				"disponible" => false,
				"estado" => "costo_pendiente_aprobacion",
				"costo_unitario" => null
			);
		}
		return array("ok" => true, "disponible" => true, "estado" => "aprobado", "data" => $costo);
	}

	static public function ctrCalcularRentabilidad($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso para calcular rentabilidad");
		}
		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		$ventaNeta = self::ctrNormalizarImporte(isset($post["venta_neta"]) ? $post["venta_neta"] : "", 16);
		$unidades = self::ctrNormalizarImporte(isset($post["unidades_vendidas"]) ? $post["unidades_vendidas"] : "", 10);
		if ($modelo === "" || strlen($modelo) > 50 || !self::ctrPeriodoValido($anio, $mes)) {
			return array("ok" => false, "mensaje" => "Modelo o período inválidos");
		}
		if ($ventaNeta === null || $unidades === null) {
			return array("ok" => false, "mensaje" => "Venta neta o unidades inválidas");
		}

		try {
			$resultado = ModeloCostosModeloMensual::mdlCalcularRentabilidad(
				$modelo,
				$anio,
				$mes,
				$ventaNeta,
				$unidades
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo calcular la rentabilidad");
		}
		if (!$resultado) {
			return array(
				"ok" => true,
				"disponible" => false,
				"estado" => "costo_pendiente_aprobacion",
				"costo_venta" => null,
				"utilidad" => null,
				"margen_pct" => null
			);
		}
		$resultado["alerta"] = strpos((string) $resultado["utilidad"], "-") === 0
			? "utilidad_negativa"
			: null;
		return array("ok" => true, "disponible" => true, "estado" => "calculado", "data" => $resultado);
	}

	static private function ctrDelimitadorCsv($linea)
	{
		$opciones = array("," => substr_count($linea, ","), ";" => substr_count($linea, ";"), "\t" => substr_count($linea, "\t"));
		arsort($opciones);
		$delimitador = key($opciones);
		return current($opciones) > 0 ? $delimitador : ",";
	}

	static public function ctrImportarCsv($post, $files)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para importar costos");
		}

		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		$confirmar = isset($post["confirmar"]) && (string) $post["confirmar"] === "1";
		$usuario = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
		if (!self::ctrPeriodoValido($anio, $mes)) {
			return array("ok" => false, "mensaje" => "Período inválido");
		}
		if (
			!isset($files["archivo"]) ||
			!is_array($files["archivo"]) ||
			(int) $files["archivo"]["error"] !== UPLOAD_ERR_OK
		) {
			return array("ok" => false, "mensaje" => "Selecciona un archivo CSV válido");
		}

		$archivo = $files["archivo"];
		$extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));
		if ($extension !== "csv" || (int) $archivo["size"] < 1 || (int) $archivo["size"] > 5 * 1024 * 1024) {
			return array("ok" => false, "mensaje" => "El archivo debe ser CSV y pesar como máximo 5 MB");
		}
		if (!is_uploaded_file($archivo["tmp_name"])) {
			return array("ok" => false, "mensaje" => "La carga del archivo no es válida");
		}

		$manejador = fopen($archivo["tmp_name"], "rb");
		if ($manejador === false) {
			return array("ok" => false, "mensaje" => "No se pudo leer el archivo");
		}
		$primeraLinea = fgets($manejador);
		if ($primeraLinea === false) {
			fclose($manejador);
			return array("ok" => false, "mensaje" => "El archivo está vacío");
		}
		$delimitador = self::ctrDelimitadorCsv($primeraLinea);
		rewind($manejador);
		$encabezados = fgetcsv($manejador, 0, $delimitador);
		if (!is_array($encabezados)) {
			fclose($manejador);
			return array("ok" => false, "mensaje" => "No se pudo leer el encabezado");
		}
		foreach ($encabezados as $indice => $encabezado) {
			$encabezado = preg_replace('/^\xEF\xBB\xBF/', '', (string) $encabezado);
			$encabezados[$indice] = strtolower(trim($encabezado));
		}
		$posiciones = array_flip($encabezados);
		if (!isset($posiciones["modelo"]) || !isset($posiciones["costo_unitario"])) {
			fclose($manejador);
			return array("ok" => false, "mensaje" => "El CSV debe incluir las columnas modelo y costo_unitario");
		}

		$filas = array();
		$vistos = array();
		$numeroFila = 1;
		while (($valores = fgetcsv($manejador, 0, $delimitador)) !== false) {
			$numeroFila++;
			if ($numeroFila > 5001) {
				fclose($manejador);
				return array("ok" => false, "mensaje" => "El archivo supera el máximo de 5,000 filas");
			}
			if (count($valores) === 1 && trim((string) $valores[0]) === "") {
				continue;
			}

			$modelo = trim(isset($valores[$posiciones["modelo"]]) ? $valores[$posiciones["modelo"]] : "");
			$costoOriginal = trim(isset($valores[$posiciones["costo_unitario"]]) ? $valores[$posiciones["costo_unitario"]] : "");
			$fuente = isset($posiciones["fuente"], $valores[$posiciones["fuente"]])
				? trim($valores[$posiciones["fuente"]]) : "";
			$observacion = isset($posiciones["observacion"], $valores[$posiciones["observacion"]])
				? trim($valores[$posiciones["observacion"]]) : "";
			$costo = self::ctrNormalizarCosto($costoOriginal);
			$errores = array();

			if ($modelo === "" || strlen($modelo) > 50) {
				$errores[] = "Modelo inválido";
			}
			if ($costo === null) {
				$errores[] = "Costo inválido";
			}
			if (strlen($fuente) > 100 || strlen($observacion) > 500) {
				$errores[] = "Fuente u observación demasiado extensa";
			}
			if ($modelo !== "" && isset($vistos[$modelo])) {
				$errores[] = "Modelo duplicado en el archivo";
			}
			$vistos[$modelo] = true;

			$filas[] = array(
				"fila" => $numeroFila,
				"modelo" => $modelo,
				"costo_unitario" => $costo,
				"costo_original" => $costoOriginal,
				"fuente" => $fuente !== "" ? $fuente : null,
				"observacion" => $observacion !== "" ? $observacion : null,
				"anio" => $anio,
				"mes" => $mes,
				"errores" => $errores
			);
		}
		fclose($manejador);

		if (empty($filas)) {
			return array("ok" => false, "mensaje" => "El archivo no contiene datos");
		}

		try {
			$activos = array_flip(ModeloCostosModeloMensual::mdlModelosActivosPorCodigos(array_keys($vistos)));
			$costosActuales = ModeloCostosModeloMensual::mdlListarCostosPeriodo($anio, $mes);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo validar el archivo contra el catálogo");
		}
		$estadoPorModelo = array();
		foreach ($costosActuales as $actual) {
			if (!empty($actual["id"])) {
				$estadoPorModelo[$actual["modelo"]] = $actual["estado"];
			}
		}

		$totalErrores = 0;
		foreach ($filas as $indice => $fila) {
			if ($fila["modelo"] !== "" && !isset($activos[$fila["modelo"]])) {
				$filas[$indice]["errores"][] = "Modelo inexistente o inactivo";
			}
			if (
				isset($estadoPorModelo[$fila["modelo"]]) &&
				$estadoPorModelo[$fila["modelo"]] !== "borrador"
			) {
				$filas[$indice]["errores"][] = "El costo está " . $estadoPorModelo[$fila["modelo"]] . "; debe reabrirse";
			}
			if (!empty($filas[$indice]["errores"])) {
				$totalErrores++;
			}
		}

		if (!$confirmar) {
			return array(
				"ok" => true,
				"previsualizacion" => true,
				"data" => $filas,
				"total" => count($filas),
				"validas" => count($filas) - $totalErrores,
				"rechazadas" => $totalErrores
			);
		}
		if ($totalErrores > 0) {
			return array("ok" => false, "mensaje" => "Corrige las filas rechazadas antes de importar");
		}
		if ($usuario < 1) {
			return array("ok" => false, "mensaje" => "Sesión inválida");
		}

		foreach ($filas as $indice => $fila) {
			unset($filas[$indice]["fila"], $filas[$indice]["costo_original"], $filas[$indice]["errores"]);
		}
		$resultado = ModeloCostosModeloMensual::mdlGuardarBorradoresMasivo($filas, $usuario);
		if ($resultado === "ok") {
			return array("ok" => true, "mensaje" => count($filas) . " costos importados como borrador");
		}
		if ($resultado === "bloqueado") {
			return array("ok" => false, "mensaje" => "Un costo cambió de estado. Vuelve a previsualizar el archivo.");
		}
		return array("ok" => false, "mensaje" => "No se pudo completar la importación");
	}

	static public function ctrHistorial($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso para consultar el historial");
		}

		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		if ($modelo === "" || !self::ctrPeriodoValido($anio, $mes)) {
			return array("ok" => false, "mensaje" => "Datos incompletos");
		}

		try {
			$lista = ModeloCostosModeloMensual::mdlHistorial($modelo, $anio, $mes);
			return array("ok" => true, "data" => $lista ? $lista : array());
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo consultar el historial");
		}
	}
}
