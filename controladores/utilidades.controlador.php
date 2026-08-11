<?php

class ControladorUtilidades
{

	static private function ctrPuedeVer()
	{
		return function_exists("usuarioPuedeVerModulo")
			&& usuarioPuedeVerModulo("utilidades", "utilidades");
	}

	static private function ctrPuedeEjecutar()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("utilidades", "utilidades", "ejecutar");
	}

	static public function ctrDescuadresStock01()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$filas = ModeloUtilidades::mdlDescuadresStock01($anio);
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar movimientos", "data" => array());
		}

		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"articulo" => (string) $f["articulo"],
				"nombre" => (string) $f["nombre"],
				"modelo" => (string) $f["modelo"],
				"color" => (string) $f["color"],
				"talla" => (string) $f["talla"],
				"ingresos" => (float) $f["ingresos"],
				"salidas" => (float) $f["salidas"],
				"stock_calculado" => (float) $f["stock_calculado"],
				"stock_actual" => (float) $f["stock_actual"],
				"diferencia" => (float) $f["diferencia"]
			);
		}

		return array(
			"ok" => true,
			"anio" => $anio,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "Todo cuadra: no hay descuadres en almacén 01"
				: ("Se encontraron " . count($data) . " artículo(s) que no cuadran")
		);
	}

	static public function ctrActualizarStock01($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay artículos para actualizar");
		}

		$limpios = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$articulo = isset($item["articulo"]) ? trim((string) $item["articulo"]) : "";
			if ($articulo === "") {
				continue;
			}
			$limpios[] = array(
				"articulo" => $articulo,
				"stock_calculado" => isset($item["stock_calculado"])
					? (float) $item["stock_calculado"]
					: 0
			);
		}

		if (count($limpios) < 1) {
			return array("ok" => false, "mensaje" => "No hay artículos válidos");
		}

		return ModeloUtilidades::mdlActualizarStock01($limpios);
	}

	static public function ctrDescuadresServicio()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$filas = ModeloUtilidades::mdlDescuadresServicio();
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar servicio/cierre", "data" => array());
		}

		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"articulo" => (string) $f["articulo"],
				"nombre" => (string) $f["nombre"],
				"modelo" => (string) $f["modelo"],
				"color" => (string) $f["color"],
				"talla" => (string) $f["talla"],
				"servicio_total" => (float) $f["servicio_total"],
				"servicio" => (float) $f["servicio"],
				"cierre" => (float) $f["cierre"],
				"servicio_calculado" => (float) $f["servicio_calculado"],
				"diferencia" => (float) $f["diferencia"]
			);
		}

		return array(
			"ok" => true,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "Todo cuadra: no hay descuadres de servicio"
				: ("Se encontraron " . count($data) . " artículo(s) que no cuadran")
		);
	}

	static public function ctrActualizarServicio($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay artículos para actualizar");
		}

		$limpios = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$articulo = isset($item["articulo"]) ? trim((string) $item["articulo"]) : "";
			if ($articulo === "") {
				continue;
			}
			$limpios[] = array(
				"articulo" => $articulo,
				"servicio_calculado" => isset($item["servicio_calculado"])
					? (float) $item["servicio_calculado"]
					: 0
			);
		}

		if (count($limpios) < 1) {
			return array("ok" => false, "mensaje" => "No hay artículos válidos");
		}

		return ModeloUtilidades::mdlActualizarServicio($limpios);
	}

	/** Cliente fijo venta oficina. */
	const CLIENTE_VTA_OFICINA = "VTAOFIC21";

	private static function ctrCargarModeloCuentas()
	{
		if (!class_exists("ModeloCuentas")) {
			require_once __DIR__ . "/../modelos/cuentas.modelo.php";
		}
	}

	/**
	 * Cuántos movimientos tiene VTAOFIC21 (previa a borrar).
	 */
	static public function ctrContarCuentaVtaOficina()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "total" => 0);
		}

		self::ctrCargarModeloCuentas();
		$cliente = self::CLIENTE_VTA_OFICINA;
		$conteo = ModeloCuentas::mdlContarCuentasPorCliente("cuenta_ctejf", $cliente);

		if (empty($conteo["ok"])) {
			return array(
				"ok" => false,
				"mensaje" => isset($conteo["msg"]) ? $conteo["msg"] : "No se pudo consultar.",
				"total" => 0,
				"cliente" => $cliente,
			);
		}

		$total = (int) $conteo["total"];

		return array(
			"ok" => true,
			"cliente" => $cliente,
			"total" => $total,
			"mensaje" => $total === 1
				? "Hay 1 movimiento de {$cliente}."
				: ("Hay {$total} movimientos de {$cliente}."),
		);
	}

	/**
	 * Borra toda la cuenta corriente de VTAOFIC21.
	 */
	static public function ctrEliminarCuentaVtaOficina()
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para ejecutar");
		}

		self::ctrCargarModeloCuentas();

		$cliente = self::CLIENTE_VTA_OFICINA;
		$resultado = ModeloCuentas::mdlEliminarCuentasPorCliente("cuenta_ctejf", $cliente);

		if (empty($resultado["ok"])) {
			return array(
				"ok" => false,
				"mensaje" => isset($resultado["msg"]) ? $resultado["msg"] : "No se pudo eliminar.",
				"eliminados" => 0,
			);
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$eliminados = isset($resultado["eliminados"]) ? (int) $resultado["eliminados"] : 0;
		$descripcion = "Utilidades: {$usuario} eliminó cte. de {$cliente} ({$eliminados} registro(s)).";

		if (isset($_SESSION["datos"]) && (int) $_SESSION["datos"] === 1) {
			if (!class_exists("ModeloUsuarios")) {
				require_once __DIR__ . "/../modelos/usuarios.modelo.php";
			}
			ModeloUsuarios::mdlIngresarAuditoria("auditoriajf", array(
				"usuario" => $usuario,
				"concepto" => $descripcion,
				"fecha" => $fecha->format("Y-m-d H:i:s"),
			));
		}

		return array(
			"ok" => true,
			"mensaje" => $eliminados > 0
				? "Se eliminaron {$eliminados} movimiento(s) de {$cliente}."
				: "No había movimientos de {$cliente} por eliminar.",
			"eliminados" => $eliminados,
			"cliente" => $cliente,
		);
	}
}
