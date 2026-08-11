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

	/**
	 * Tracking de producción por código de modelo (solo lectura).
	 */
	static public function ctrTrackingModelo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}

		$modelo = isset($post["modelo"]) ? trim((string) $post["modelo"]) : "";
		if ($modelo === "" || strlen($modelo) > 20 || !preg_match('/^[A-Za-z0-9_-]+$/', $modelo)) {
			return array(
				"ok" => false,
				"mensaje" => "Indica un código de modelo válido (máx. 20 caracteres alfanuméricos)"
			);
		}

		$raw = ModeloUtilidades::mdlTrackingModelo($modelo);
		if ($raw === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar el modelo");
		}

		if (empty($raw["existe"])) {
			return array(
				"ok" => false,
				"mensaje" => "No hay artículos con el modelo «{$modelo}»"
			);
		}

		$articulosOut = array();
		$problemas = array();
		$filasOcModelo = (int) (isset($raw["documentos"]["oc"]["filas"]) ? $raw["documentos"]["oc"]["filas"] : 0);
		$filasCorteModelo = (int) (isset($raw["documentos"]["corte"]["filas"]) ? $raw["documentos"]["corte"]["filas"] : 0);
		$almCorteResumen = (float) (isset($raw["resumen"]["alm_corte"]) ? $raw["resumen"]["alm_corte"] : 0);
		$hayAlmCorteModelo = $almCorteResumen > 0 || $filasCorteModelo > 0;

		if ($hayAlmCorteModelo && $filasOcModelo < 1) {
			$problemas[] = array(
				"codigo" => "MODELO_SIN_OC_CON_CORTE",
				"articulo" => "",
				"color" => "",
				"talla" => "",
				"actual" => $almCorteResumen,
				"calculado" => 0,
				"diferencia" => $almCorteResumen,
				"mensaje" => "El modelo tiene almacén de corte / alm_corte pero no tiene detalle de orden de corte"
			);
		}

		foreach ($raw["articulos"] as $a) {
			$articulo = (string) $a["articulo"];
			$color = (string) $a["color"];
			$talla = (string) $a["talla"];
			$nombre = (string) $a["nombre"];

			$ord = (float) $a["ord_corte"];
			$ordCalc = (float) $a["ord_corte_calc"];
			$alm = (float) $a["alm_corte"];
			$almCalc = (float) $a["alm_corte_calc"];
			$taller = (float) $a["taller"];
			$tallerCalc = (float) $a["taller_calc"];
			$serv = (float) $a["servicio"];
			$servCalc = (float) $a["servicio_calc"];
			$filasCorte = (int) $a["filas_corte"];

			$flags = array();

			if (round($ord, 4) !== round($ordCalc, 4)) {
				$flags[] = "ORD_CORTE_DESCUADRE";
				$problemas[] = array(
					"codigo" => "ORD_CORTE_DESCUADRE",
					"articulo" => $articulo,
					"color" => $color,
					"talla" => $talla,
					"actual" => $ord,
					"calculado" => $ordCalc,
					"diferencia" => round($ord - $ordCalc, 4),
					"mensaje" => "ord_corte del artículo no cuadra con el saldo de órdenes de corte"
				);
			}

			if (round($alm, 4) !== round($almCalc, 4)) {
				$flags[] = "ALM_CORTE_DESCUADRE";
				$problemas[] = array(
					"codigo" => "ALM_CORTE_DESCUADRE",
					"articulo" => $articulo,
					"color" => $color,
					"talla" => $talla,
					"actual" => $alm,
					"calculado" => $almCalc,
					"diferencia" => round($alm - $almCalc, 4),
					"mensaje" => "alm_corte no cuadra con la suma de saldo_taller en almacén de corte"
				);
			}

			if (round($taller, 4) !== round($tallerCalc, 4)) {
				$flags[] = "TALLER_DESCUADRE";
				$problemas[] = array(
					"codigo" => "TALLER_DESCUADRE",
					"articulo" => $articulo,
					"color" => $color,
					"talla" => $talla,
					"actual" => $taller,
					"calculado" => $tallerCalc,
					"diferencia" => round($taller - $tallerCalc, 4),
					"mensaje" => "taller no cuadra con el saldo de envíos a taller"
				);
			}

			if (round($serv, 4) !== round($servCalc, 4)) {
				$flags[] = "SERVICIO_DESCUADRE";
				$problemas[] = array(
					"codigo" => "SERVICIO_DESCUADRE",
					"articulo" => $articulo,
					"color" => $color,
					"talla" => $talla,
					"actual" => $serv,
					"calculado" => $servCalc,
					"diferencia" => round($serv - $servCalc, 4),
					"mensaje" => "servicio no cuadra con servicios abiertos + cierres"
				);
			}

			if ($alm > 0 && $filasCorte < 1) {
				$flags[] = "MODELO_SIN_DOC_CORTE";
				$problemas[] = array(
					"codigo" => "MODELO_SIN_DOC_CORTE",
					"articulo" => $articulo,
					"color" => $color,
					"talla" => $talla,
					"actual" => $alm,
					"calculado" => 0,
					"diferencia" => $alm,
					"mensaje" => "alm_corte > 0 sin filas en almacén de corte (posible ajuste directo)"
				);
			}

			$inicioCorte = (float) (isset($a["inicio_corte"]) ? $a["inicio_corte"] : $a["cantidad_corte"]);
			$enProceso = (float) (isset($a["en_proceso"]) ? $a["en_proceso"] : ($almCalc + $tallerCalc + $servCalc));
			$ingresosArt = (float) (isset($a["ingresos_e20"]) ? $a["ingresos_e20"] : 0);
			$ingDisp = !empty($a["ingresos_disponible"]);
			$brecha = null;
			if ($ingDisp && isset($a["brecha"]) && $a["brecha"] !== null) {
				$brecha = (float) $a["brecha"];
			} elseif ($ingDisp) {
				$brecha = round($inicioCorte - ($enProceso + $ingresosArt), 4);
			}

			if ($ingDisp && $brecha !== null && round($brecha, 4) !== 0.0) {
				$flags[] = "BRECHA_CORTE_INGRESOS";
				$problemas[] = array(
					"codigo" => "BRECHA_CORTE_INGRESOS",
					"articulo" => $articulo,
					"color" => $color,
					"talla" => $talla,
					"actual" => $inicioCorte,
					"calculado" => round($enProceso + $ingresosArt, 4),
					"diferencia" => $brecha,
					"mensaje" => "Inicio corte no cuadra con en proceso + ingresos E20 (solo alerta; no se corrige con el botón)"
				);
			}

			$articulosOut[] = array(
				"articulo" => $articulo,
				"nombre" => $nombre,
				"color" => $color,
				"talla" => $talla,
				"ord_corte" => $ord,
				"ord_corte_calc" => $ordCalc,
				"alm_corte" => $alm,
				"alm_corte_calc" => $almCalc,
				"taller" => $taller,
				"taller_calc" => $tallerCalc,
				"servicio" => $serv,
				"servicio_calc" => $servCalc,
				"servicio_abierto" => (float) $a["servicio_abierto"],
				"cierre" => (float) $a["cierre"],
				"stock" => (float) $a["stock"],
				"inicio_corte" => $inicioCorte,
				"en_proceso" => $enProceso,
				"ingresos_e20" => $ingresosArt,
				"ingresos_disponible" => $ingDisp ? 1 : 0,
				"brecha" => $brecha,
				"entaller_ext_saldo" => (float) (isset($a["entaller_ext_saldo"]) ? $a["entaller_ext_saldo"] : 0),
				"entaller_ext_calc" => (float) (isset($a["entaller_ext_calc"]) ? $a["entaller_ext_calc"] : 0),
				"servicio_origen" => (float) (isset($a["servicio_origen"]) ? $a["servicio_origen"] : 0),
				"cierre_inicio" => (float) (isset($a["cierre_inicio"]) ? $a["cierre_inicio"] : 0),
				"e20_cierre" => (float) (isset($a["e20_cierre"]) ? $a["e20_cierre"] : 0),
				"brecha_serv_cierre" => isset($a["brecha_serv_cierre"]) ? (float) $a["brecha_serv_cierre"] : 0,
				"brecha_cierre_ing" => isset($a["brecha_cierre_ing"]) && $a["brecha_cierre_ing"] !== null
					? (float) $a["brecha_cierre_ing"]
					: null,
				"brecha_cadena" => isset($a["brecha_cadena"]) && $a["brecha_cadena"] !== null
					? (float) $a["brecha_cadena"]
					: null,
				"cierre_inicio_calc" => isset($a["cierre_inicio_calc"]) && $a["cierre_inicio_calc"] !== null
					? (float) $a["cierre_inicio_calc"]
					: null,
				"servicio_abierto_calc" => isset($a["servicio_abierto_calc"]) && $a["servicio_abierto_calc"] !== null
					? (float) $a["servicio_abierto_calc"]
					: null,
				"filas_oc" => (int) $a["filas_oc"],
				"filas_corte" => $filasCorte,
				"filas_envio" => (int) $a["filas_envio"],
				"filas_servicio" => (int) $a["filas_servicio"],
				"filas_cierre" => (int) $a["filas_cierre"],
				"tiene_problemas" => count($flags) > 0,
				"flags" => $flags
			);
		}

		foreach ($raw["huerfanos"]["corte_sin_oc"] as $h) {
			$problemas[] = array(
				"codigo" => "CORTE_SIN_OC",
				"articulo" => (string) $h["articulo"],
				"color" => (string) $h["color"],
				"talla" => (string) $h["talla"],
				"actual" => (float) $h["cantidad"],
				"calculado" => 0,
				"diferencia" => (float) $h["cantidad"],
				"mensaje" => "Detalle de corte #" . (int) $h["id"]
					. " (AC " . (string) $h["almacencorte"] . ") sin orden de corte válida"
			);
		}

		foreach ($raw["huerfanos"]["envio_sin_corte"] as $h) {
			$problemas[] = array(
				"codigo" => "ENVIO_SIN_CORTE",
				"articulo" => (string) $h["articulo"],
				"color" => (string) $h["color"],
				"talla" => (string) $h["talla"],
				"actual" => (float) $h["cantidad"],
				"calculado" => 0,
				"diferencia" => (float) $h["cantidad"],
				"mensaje" => "Envío a taller #" . (int) $h["id"] . " sin vínculo a detalle de almacén de corte"
			);
		}

		foreach ($raw["huerfanos"]["servicio_sin_envio"] as $h) {
			$problemas[] = array(
				"codigo" => "SERVICIO_SIN_ENVIO",
				"articulo" => (string) $h["articulo"],
				"color" => (string) $h["color"],
				"talla" => (string) $h["talla"],
				"actual" => (float) $h["cantidad"],
				"calculado" => 0,
				"diferencia" => (float) $h["cantidad"],
				"mensaje" => "Detalle de servicio #" . (int) $h["id"]
					. " (código " . (string) $h["codigo"] . ") sin envío a taller válido"
			);
		}

		$totalProblemas = count($problemas);

		return array(
			"ok" => true,
			"modelo" => $modelo,
			"anio" => (int) $raw["anio"],
			"resumen" => $raw["resumen"],
			"documentos" => $raw["documentos"],
			"articulos" => $articulosOut,
			"problemas" => $problemas,
			"total_problemas" => $totalProblemas,
			"mensaje" => $totalProblemas === 0
				? "Sin inconsistencias detectadas para el modelo {$modelo}"
				: ("Se detectaron {$totalProblemas} inconsistencia(s) en el modelo {$modelo}")
		);
	}

	/**
	 * Corrige columnas espejo de articulojf para un modelo (requiere ejecutar).
	 */
	static public function ctrCorregirSaldosModelo($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para corregir");
		}

		$modelo = isset($post["modelo"]) ? trim((string) $post["modelo"]) : "";
		if ($modelo === "" || strlen($modelo) > 20 || !preg_match('/^[A-Za-z0-9_-]+$/', $modelo)) {
			return array(
				"ok" => false,
				"mensaje" => "Indica un código de modelo válido (máx. 20 caracteres alfanuméricos)"
			);
		}

		$resultado = ModeloUtilidades::mdlCorregirSaldosModelo($modelo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$entallerExt = isset($resultado["entaller_ext"]) ? (int) $resultado["entaller_ext"] : 0;
		$cierres = isset($resultado["cierres"]) ? (int) $resultado["cierres"] : 0;
		$servicios = isset($resultado["servicios"]) ? (int) $resultado["servicios"] : 0;
		$descripcion = "Utilidades: {$usuario} corrigió modelo {$modelo} (art={$actualizados}, serv={$servicios}, cierre={$cierres}, ent.ext={$entallerExt}).";

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

		return $resultado;
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
