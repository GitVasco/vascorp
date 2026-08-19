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

	/**
	 * Cargos sin fecha_ven (tip_mov = '+') para completar con la fecha del documento.
	 */
	static public function ctrCteSinFechaVen()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$filas = ModeloUtilidades::mdlCteSinFechaVen();
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar cuenta corriente", "data" => array());
		}

		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"id" => (int) $f["id"],
				"tipo_doc" => (string) $f["tipo_doc"],
				"num_cta" => (string) $f["num_cta"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"fecha" => (string) $f["fecha"],
				"fecha_ven_propuesta" => (string) $f["fecha"],
				"monto" => (float) $f["monto"],
				"saldo" => (float) $f["saldo"],
				"estado" => (string) $f["estado"]
			);
		}

		return array(
			"ok" => true,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "No hay cargos sin fecha de vencimiento"
				: ("Se encontraron " . count($data) . " cargo(s) sin fecha de vencimiento")
		);
	}

	/**
	 * Completa fecha_ven = fecha en los ids seleccionados.
	 */
	static public function ctrCompletarFechaVenCte($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$raw = isset($post["ids"]) ? $post["ids"] : "";
		if (is_string($raw)) {
			$ids = json_decode($raw, true);
		} else {
			$ids = $raw;
		}

		if (!is_array($ids) || count($ids) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarFechaVenCte($ids);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$descripcion = "Utilidades: {$usuario} completó fecha_ven en {$actualizados} cargo(s) de cte.";

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

	/** Ventana por defecto (días) para abonos sin fecha_ori. */
	const CTE_FECHA_ORI_DIAS = 60;

	/**
	 * Abonos sin fecha_ori / fecha_ori_ven (últimos N días) con cargo de referencia.
	 */
	static public function ctrCteSinFechaOri()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$dias = self::CTE_FECHA_ORI_DIAS;
		$filas = ModeloUtilidades::mdlCteSinFechaOri($dias);
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar cuenta corriente", "data" => array());
		}

		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"id" => (int) $f["id"],
				"tipo_doc" => (string) $f["tipo_doc"],
				"num_cta" => (string) $f["num_cta"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"fecha" => (string) $f["fecha"],
				"fecha_ori" => (string) $f["fecha_ori"],
				"fecha_ori_ven" => (string) $f["fecha_ori_ven"],
				"fecha_ori_prop" => (string) $f["fecha_ori_prop"],
				"fecha_ori_ven_prop" => (string) $f["fecha_ori_ven_prop"],
				"monto" => (float) $f["monto"],
				"estado" => (string) $f["estado"]
			);
		}

		return array(
			"ok" => true,
			"dias" => $dias,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "No hay abonos sin fecha de origen (últimos {$dias} días)"
				: ("Se encontraron " . count($data) . " abono(s) sin fecha de origen")
		);
	}

	/**
	 * Completa fecha_ori / fecha_ori_ven desde el cargo (+) del mismo documento.
	 */
	static public function ctrCompletarFechaOriCte($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$raw = isset($post["ids"]) ? $post["ids"] : "";
		if (is_string($raw)) {
			$ids = json_decode($raw, true);
		} else {
			$ids = $raw;
		}

		if (!is_array($ids) || count($ids) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarFechaOriCte($ids);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$descripcion = "Utilidades: {$usuario} completó fecha_ori en {$actualizados} abono(s) de cte.";

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

	/**
	 * Cuentas del año actual sin tip_cambio (con cambio en totalesjf).
	 */
	static public function ctrCteSinTipCambio()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$filas = ModeloUtilidades::mdlCteSinTipCambio($anio);
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar cuenta corriente", "data" => array());
		}

		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"id" => (int) $f["id"],
				"tipo_doc" => (string) $f["tipo_doc"],
				"num_cta" => (string) $f["num_cta"],
				"tip_mov" => (string) $f["tip_mov"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"fecha" => (string) $f["fecha"],
				"tip_cambio" => (float) $f["tip_cambio"],
				"tip_cambio_prop" => (float) $f["tip_cambio_prop"],
				"monto" => (float) $f["monto"],
				"estado" => (string) $f["estado"]
			);
		}

		return array(
			"ok" => true,
			"anio" => $anio,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "No hay cuentas sin tipo de cambio en {$anio}"
				: ("Se encontraron " . count($data) . " cuenta(s) sin tipo de cambio")
		);
	}

	/**
	 * Completa tip_cambio desde totalesjf.cambio_venta.
	 */
	static public function ctrCompletarTipCambioCte($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$raw = isset($post["ids"]) ? $post["ids"] : "";
		if (is_string($raw)) {
			$ids = json_decode($raw, true);
		} else {
			$ids = $raw;
		}

		if (!is_array($ids) || count($ids) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$resultado = ModeloUtilidades::mdlCompletarTipCambioCte($ids, $anio);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$descripcion = "Utilidades: {$usuario} actualizó tip_cambio en {$actualizados} cte. ({$anio}).";

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

	/**
	 * Ventas del año sin tipo_cambio (fecha &lt; hoy) con cambio en totalesjf.
	 */
	static public function ctrVentasSinTipCambio()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$filas = ModeloUtilidades::mdlVentasSinTipCambio($anio);
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar ventas", "data" => array());
		}

		$data = array();
		foreach ($filas as $f) {
			$data[] = array(
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"fecha" => (string) $f["fecha"],
				"tipo_cambio" => (float) $f["tipo_cambio"],
				"tipo_cambio_prop" => (float) $f["tipo_cambio_prop"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"anio" => $anio,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "No hay ventas sin tipo de cambio en {$anio}"
				: ("Se encontraron " . count($data) . " venta(s) sin tipo de cambio")
		);
	}

	/**
	 * Completa ventajf.tipo_cambio desde totalesjf.cambio_venta.
	 */
	static public function ctrCompletarTipCambioVentas($post)
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
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$resultado = ModeloUtilidades::mdlCompletarTipCambioVentas($items, $anio);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$descripcion = "Utilidades: {$usuario} actualizó tipo_cambio en {$actualizados} venta(s) ({$anio}).";

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

	/**
	 * Facturas/boletas del periodo sin cuenta contable.
	 */
	static public function ctrVentasSinCuenta($post = array())
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$res = ModeloUtilidades::mdlVentasSinCuenta($periodo);
		if ($res === false) {
			return array("ok" => false, "mensaje" => "Periodo inválido", "data" => array());
		}

		$rango = $res["rango"];
		$data = array();
		foreach ($res["filas"] as $f) {
			$data[] = array(
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"ubigeo" => (string) $f["ubigeo"],
				"zona" => (string) $f["zona"],
				"fecha" => (string) $f["fecha"],
				"cuenta" => (string) $f["cuenta"],
				"cuenta_prop" => (string) $f["cuenta_prop"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"periodo" => $rango["periodo"],
			"inicio" => $rango["inicio"],
			"fin" => $rango["fin"],
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? ("No hay facturas/boletas sin cuenta en " . $rango["periodo"])
				: ("Se encontraron " . count($data) . " venta(s) sin cuenta")
		);
	}

	/**
	 * Completa ventajf.cuenta (S02/S03) según ubigeo.
	 */
	static public function ctrCompletarCuentaVentas($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarCuentaVentas($items, $periodo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$per = isset($resultado["periodo"]) ? $resultado["periodo"] : (string) $periodo;
		$descripcion = "Utilidades: {$usuario} completó cuenta contable en {$actualizados} venta(s) ({$per}).";

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

	/**
	 * Ventas con abono POS showroom del periodo → cuenta 702213.
	 */
	static public function ctrVentasCuentaPos($post = array())
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$res = ModeloUtilidades::mdlVentasCuentaPos($periodo);
		if ($res === false) {
			return array("ok" => false, "mensaje" => "Periodo inválido", "data" => array());
		}

		$rango = $res["rango"];
		$cuenta = $res["cuenta"];
		$data = array();
		foreach ($res["filas"] as $f) {
			$data[] = array(
				"tipo_doc" => (string) $f["tipo_doc"],
				"num_cta" => (string) $f["num_cta"],
				"cod_pago" => (string) $f["cod_pago"],
				"vendedor" => (string) $f["vendedor"],
				"fecha_pago" => (string) $f["fecha_pago"],
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"fecha" => (string) $f["fecha"],
				"cuenta" => (string) $f["cuenta"],
				"cuenta_prop" => (string) $f["cuenta_prop"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"periodo" => $rango["periodo"],
			"inicio" => $rango["inicio"],
			"fin" => $rango["fin"],
			"cuenta" => $cuenta,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? ("No hay ventas POS showroom pendientes en " . $rango["periodo"])
				: ("Se encontraron " . count($data) . " venta(s) POS showroom")
		);
	}

	/**
	 * Completa cuenta 702213 en ventas POS showroom seleccionadas.
	 */
	static public function ctrCompletarCuentaPosVentas($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarCuentaPosVentas($items, $periodo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$per = isset($resultado["periodo"]) ? $resultado["periodo"] : (string) $periodo;
		$cuenta = isset($resultado["cuenta"]) ? $resultado["cuenta"] : ModeloUtilidades::CUENTA_POS_SHOWROOM;
		$descripcion = "Utilidades: {$usuario} asignó cuenta {$cuenta} (POS showroom) en {$actualizados} venta(s) ({$per}).";

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

	/**
	 * Ventas con abono Culqi showroom del periodo → 702215/702216.
	 */
	static public function ctrVentasCuentaCulqi($post = array())
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$res = ModeloUtilidades::mdlVentasCuentaCulqi($periodo);
		if ($res === false) {
			return array("ok" => false, "mensaje" => "Periodo inválido", "data" => array());
		}

		$rango = $res["rango"];
		$data = array();
		foreach ($res["filas"] as $f) {
			$data[] = array(
				"tipo_doc" => (string) $f["tipo_doc"],
				"num_cta" => (string) $f["num_cta"],
				"cod_pago" => (string) $f["cod_pago"],
				"vendedor" => (string) $f["vendedor"],
				"fecha_pago" => (string) $f["fecha_pago"],
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"ubigeo" => (string) $f["ubigeo"],
				"zona" => (string) $f["zona"],
				"fecha" => (string) $f["fecha"],
				"cuenta" => (string) $f["cuenta"],
				"cuenta_prop" => (string) $f["cuenta_prop"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"periodo" => $rango["periodo"],
			"inicio" => $rango["inicio"],
			"fin" => $rango["fin"],
			"cuenta_lima" => $res["cuenta_lima"],
			"cuenta_prov" => $res["cuenta_prov"],
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? ("No hay ventas Culqi pendientes en " . $rango["periodo"])
				: ("Se encontraron " . count($data) . " venta(s) Culqi")
		);
	}

	/**
	 * Completa cuenta Culqi (702215/702216) en ventas seleccionadas.
	 */
	static public function ctrCompletarCuentaCulqiVentas($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarCuentaCulqiVentas($items, $periodo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$per = isset($resultado["periodo"]) ? $resultado["periodo"] : (string) $periodo;
		$descripcion = "Utilidades: {$usuario} asignó cuenta Culqi en {$actualizados} venta(s) ({$per}).";

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

	/**
	 * NC E05 devolución (C1/C7) del periodo → 709411/709412.
	 */
	static public function ctrVentasCuentaNcDev($post = array())
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$res = ModeloUtilidades::mdlVentasCuentaNcDev($periodo);
		if ($res === false) {
			return array("ok" => false, "mensaje" => "Periodo inválido", "data" => array());
		}

		$rango = $res["rango"];
		$data = array();
		foreach ($res["filas"] as $f) {
			$data[] = array(
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"ubigeo" => (string) $f["ubigeo"],
				"zona" => (string) $f["zona"],
				"vendedor" => (string) $f["vendedor"],
				"fecha" => (string) $f["fecha"],
				"cuenta" => (string) $f["cuenta"],
				"cuenta_prop" => (string) $f["cuenta_prop"],
				"motivo" => (string) $f["motivo"],
				"observacion" => (string) $f["observacion"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"periodo" => $rango["periodo"],
			"inicio" => $rango["inicio"],
			"fin" => $rango["fin"],
			"cuenta_lima" => $res["cuenta_lima"],
			"cuenta_prov" => $res["cuenta_prov"],
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? ("No hay NC devolución pendientes en " . $rango["periodo"])
				: ("Se encontraron " . count($data) . " NC devolución")
		);
	}

	/**
	 * Completa cuenta NC devolución (709411/709412).
	 */
	static public function ctrCompletarCuentaNcDevVentas($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarCuentaNcDevVentas($items, $periodo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$per = isset($resultado["periodo"]) ? $resultado["periodo"] : (string) $periodo;
		$descripcion = "Utilidades: {$usuario} asignó cuenta NC devolución en {$actualizados} doc(s) ({$per}).";

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

	/**
	 * NC E05 descuento (motivo ≠ C1/C7) del periodo → 741101/741102.
	 */
	static public function ctrVentasCuentaNcDscto($post = array())
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$res = ModeloUtilidades::mdlVentasCuentaNcDscto($periodo);
		if ($res === false) {
			return array("ok" => false, "mensaje" => "Periodo inválido", "data" => array());
		}

		$rango = $res["rango"];
		$data = array();
		foreach ($res["filas"] as $f) {
			$data[] = array(
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"ubigeo" => (string) $f["ubigeo"],
				"zona" => (string) $f["zona"],
				"vendedor" => (string) $f["vendedor"],
				"fecha" => (string) $f["fecha"],
				"cuenta" => (string) $f["cuenta"],
				"cuenta_prop" => (string) $f["cuenta_prop"],
				"motivo" => (string) $f["motivo"],
				"observacion" => (string) $f["observacion"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"periodo" => $rango["periodo"],
			"inicio" => $rango["inicio"],
			"fin" => $rango["fin"],
			"cuenta_lima" => $res["cuenta_lima"],
			"cuenta_prov" => $res["cuenta_prov"],
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? ("No hay NC descuento pendientes en " . $rango["periodo"])
				: ("Se encontraron " . count($data) . " NC descuento")
		);
	}

	/**
	 * Completa cuenta NC descuento (741101/741102).
	 */
	static public function ctrCompletarCuentaNcDsctoVentas($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarCuentaNcDsctoVentas($items, $periodo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$per = isset($resultado["periodo"]) ? $resultado["periodo"] : (string) $periodo;
		$descripcion = "Utilidades: {$usuario} asignó cuenta NC descuento en {$actualizados} doc(s) ({$per}).";

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

	/**
	 * ND S05 flete showroom (vendedor %08%) del periodo → 75995/75996.
	 */
	static public function ctrVentasCuentaNdFlete($post = array())
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$res = ModeloUtilidades::mdlVentasCuentaNdFlete($periodo);
		if ($res === false) {
			return array("ok" => false, "mensaje" => "Periodo inválido", "data" => array());
		}

		$rango = $res["rango"];
		$data = array();
		foreach ($res["filas"] as $f) {
			$data[] = array(
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"ubigeo" => (string) $f["ubigeo"],
				"zona" => (string) $f["zona"],
				"vendedor" => (string) $f["vendedor"],
				"fecha" => (string) $f["fecha"],
				"cuenta" => (string) $f["cuenta"],
				"cuenta_prop" => (string) $f["cuenta_prop"],
				"motivo" => (string) $f["motivo"],
				"observacion" => (string) $f["observacion"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"periodo" => $rango["periodo"],
			"inicio" => $rango["inicio"],
			"fin" => $rango["fin"],
			"cuenta_lima" => $res["cuenta_lima"],
			"cuenta_prov" => $res["cuenta_prov"],
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? ("No hay ND flete pendientes en " . $rango["periodo"])
				: ("Se encontraron " . count($data) . " ND flete")
		);
	}

	/**
	 * Completa cuenta ND flete (75995/75996).
	 */
	static public function ctrCompletarCuentaNdFleteVentas($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarCuentaNdFleteVentas($items, $periodo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$per = isset($resultado["periodo"]) ? $resultado["periodo"] : (string) $periodo;
		$descripcion = "Utilidades: {$usuario} asignó cuenta ND flete en {$actualizados} doc(s) ({$per}).";

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

	/**
	 * ND S05 protesto (vendedor sin 08) del periodo → 75991/75992.
	 */
	static public function ctrVentasCuentaNdProtesto($post = array())
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$res = ModeloUtilidades::mdlVentasCuentaNdProtesto($periodo);
		if ($res === false) {
			return array("ok" => false, "mensaje" => "Periodo inválido", "data" => array());
		}

		$rango = $res["rango"];
		$data = array();
		foreach ($res["filas"] as $f) {
			$data[] = array(
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"cliente" => (string) $f["cliente"],
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"ubigeo" => (string) $f["ubigeo"],
				"zona" => (string) $f["zona"],
				"vendedor" => (string) $f["vendedor"],
				"fecha" => (string) $f["fecha"],
				"cuenta" => (string) $f["cuenta"],
				"cuenta_prop" => (string) $f["cuenta_prop"],
				"motivo" => (string) $f["motivo"],
				"observacion" => (string) $f["observacion"],
				"total" => (float) $f["total"]
			);
		}

		return array(
			"ok" => true,
			"periodo" => $rango["periodo"],
			"inicio" => $rango["inicio"],
			"fin" => $rango["fin"],
			"cuenta_lima" => $res["cuenta_lima"],
			"cuenta_prov" => $res["cuenta_prov"],
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? ("No hay ND protesto pendientes en " . $rango["periodo"])
				: ("Se encontraron " . count($data) . " ND protesto")
		);
	}

	/**
	 * Completa cuenta ND protesto (75991/75992).
	 */
	static public function ctrCompletarCuentaNdProtestoVentas($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$periodo = isset($post["periodo"]) ? $post["periodo"] : null;
		$raw = isset($post["items"]) ? $post["items"] : "";
		if (is_string($raw)) {
			$items = json_decode($raw, true);
		} else {
			$items = $raw;
		}

		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "mensaje" => "No hay registros para actualizar");
		}

		$resultado = ModeloUtilidades::mdlCompletarCuentaNdProtestoVentas($items, $periodo);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$per = isset($resultado["periodo"]) ? $resultado["periodo"] : (string) $periodo;
		$descripcion = "Utilidades: {$usuario} asignó cuenta ND protesto en {$actualizados} doc(s) ({$per}).";

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

	/**
	 * Días del año en totalesjf sin tipo de cambio.
	 */
	static public function ctrTotalesSinTipCambio()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$filas = ModeloUtilidades::mdlTotalesSinTipCambio($anio);
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar totales", "data" => array());
		}

		$dias = array(
			1 => "Lun", 2 => "Mar", 3 => "Mié", 4 => "Jue",
			5 => "Vie", 6 => "Sáb", 7 => "Dom"
		);
		$data = array();
		foreach ($filas as $f) {
			$fecha = (string) $f["fecha"];
			$n = (int) date("N", strtotime($fecha));
			$data[] = array(
				"fecha" => $fecha,
				"dia_semana" => isset($dias[$n]) ? $dias[$n] : "",
				"mes" => (int) $f["mes"],
				"dia" => (int) $f["dia"],
				"cambio_compra" => (float) $f["cambio_compra"],
				"cambio_venta" => (float) $f["cambio_venta"]
			);
		}

		return array(
			"ok" => true,
			"anio" => $anio,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "No hay días sin tipo de cambio en totales ({$anio})"
				: ("Se encontraron " . count($data) . " día(s) sin tipo de cambio en totales")
		);
	}

	/**
	 * Completa cambio_compra/venta en totalesjf vía API (misma fuente que datos-dia).
	 * Si la API no responde, reusa el último TC previo registrado.
	 */
	static public function ctrCompletarTipCambioTotales($post)
	{
		if (!self::ctrPuedeEjecutar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para actualizar");
		}

		$raw = isset($post["fechas"]) ? $post["fechas"] : "";
		if (is_string($raw)) {
			$fechas = json_decode($raw, true);
		} else {
			$fechas = $raw;
		}

		if (!is_array($fechas) || count($fechas) < 1) {
			return array("ok" => false, "mensaje" => "No hay fechas para actualizar");
		}

		$limpias = array();
		foreach ($fechas as $f) {
			$f = trim((string) $f);
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
				$limpias[$f] = $f;
			}
		}
		$limpias = array_values($limpias);
		if (count($limpias) < 1) {
			return array("ok" => false, "mensaje" => "Fechas inválidas");
		}
		if (count($limpias) > 120) {
			return array("ok" => false, "mensaje" => "Máximo 120 días por vez");
		}

		if (!class_exists("JsonPeApi")) {
			require_once __DIR__ . "/../helpers/jsonpe.api.php";
		}

		date_default_timezone_set("America/Lima");
		@set_time_limit(300);

		$actualizados = 0;
		$omitidos = 0;
		$detalle = array();

		foreach ($limpias as $fecha) {
			$compra = null;
			$venta = null;
			$fuente = "api";

			$api = JsonPeApi::consultarTipoCambio($fecha);
			$ventaApi = isset($api["venta"]) ? $api["venta"] : null;
			$compraApi = isset($api["compra"]) ? $api["compra"] : null;

			if (
				$ventaApi !== null
				&& $ventaApi !== "Fuera de plazo permitido"
				&& is_numeric($ventaApi)
				&& (float) $ventaApi > 0
			) {
				$compra = is_numeric($compraApi) ? (float) $compraApi : 0;
				$venta = (float) $ventaApi;
			} else {
				$prev = ModeloUtilidades::mdlUltimoTipCambioTotalesAntes($fecha);
				if ($prev && (float) $prev["cambio_venta"] > 0) {
					$compra = (float) $prev["cambio_compra"];
					$venta = (float) $prev["cambio_venta"];
					$fuente = "previo:" . $prev["fecha"];
				}
			}

			if ($venta === null || $venta <= 0) {
				$omitidos++;
				$detalle[] = array(
					"fecha" => $fecha,
					"ok" => false,
					"mensaje" => "Sin TC en API ni día previo"
				);
				continue;
			}

			$upd = ModeloUtilidades::mdlActualizarTipCambioTotales(
				$fecha,
				(string) $compra,
				(string) $venta
			);
			if (empty($upd["ok"])) {
				$omitidos++;
				$detalle[] = array(
					"fecha" => $fecha,
					"ok" => false,
					"mensaje" => isset($upd["mensaje"]) ? $upd["mensaje"] : "Error al guardar"
				);
				continue;
			}

			$actualizados++;
			$detalle[] = array(
				"fecha" => $fecha,
				"ok" => true,
				"compra" => $compra,
				"venta" => $venta,
				"fuente" => $fuente
			);
		}

		$fechaAudit = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$anio = (int) date("Y");
		$descripcion = "Utilidades: {$usuario} completó TC totales ({$actualizados} ok, {$omitidos} omit.) {$anio}.";

		if (isset($_SESSION["datos"]) && (int) $_SESSION["datos"] === 1) {
			if (!class_exists("ModeloUsuarios")) {
				require_once __DIR__ . "/../modelos/usuarios.modelo.php";
			}
			ModeloUsuarios::mdlIngresarAuditoria("auditoriajf", array(
				"usuario" => $usuario,
				"concepto" => $descripcion,
				"fecha" => $fechaAudit->format("Y-m-d H:i:s"),
			));
		}

		return array(
			"ok" => true,
			"actualizados" => $actualizados,
			"omitidos" => $omitidos,
			"total" => count($limpias),
			"detalle" => $detalle,
			"mensaje" => "Totales: {$actualizados} actualizado(s), {$omitidos} omitido(s)"
		);
	}

	/**
	 * Clientes cuyo vendedor del maestro no coincide con el de la última venta
	 * de los últimos 2 años (en grupo: última venta de cualquier local).
	 * No toca 06* ni 08*. Sin venta en esa ventana, no se lista.
	 */
	static public function ctrClientesVendedorUltimaVenta()
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "data" => array());
		}

		$filas = ModeloUtilidades::mdlClientesVendedorUltimaVenta();
		if ($filas === false) {
			return array("ok" => false, "mensaje" => "No se pudo consultar última venta", "data" => array());
		}

		$vistos = array();
		$data = array();
		foreach ($filas as $f) {
			$cliente = trim((string) $f["cliente"]);
			if ($cliente === "" || isset($vistos[$cliente])) {
				continue;
			}
			$vistos[$cliente] = true;
			$alcance = (isset($f["alcance"]) && (string) $f["alcance"] === "grupo")
				? "grupo"
				: "cliente";
			$data[] = array(
				"cliente" => $cliente,
				"cliente_nombre" => (string) $f["cliente_nombre"],
				"grupo" => (string) $f["grupo"],
				"grupo_nombre" => (string) $f["grupo_nombre"],
				"vendedor_actual" => (string) $f["vendedor_actual"],
				"vendedor_actual_nombre" => (string) $f["vendedor_actual_nombre"],
				"vendedor_propuesto" => (string) $f["vendedor_propuesto"],
				"vendedor_propuesto_nombre" => (string) $f["vendedor_propuesto_nombre"],
				"fecha_ultima" => (string) $f["fecha_ultima"],
				"tipo" => (string) $f["tipo"],
				"documento" => (string) $f["documento"],
				"alcance" => $alcance
			);
		}

		return array(
			"ok" => true,
			"total" => count($data),
			"data" => $data,
			"mensaje" => count($data) === 0
				? "El maestro ya coincide con la última venta"
				: ("Se encontraron " . count($data) . " cliente(s) para actualizar")
		);
	}

	/**
	 * Actualiza clientesjf.vendedor con el de la última venta (seleccionados).
	 */
	static public function ctrActualizarVendedorUltimaVenta($post)
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
			return array("ok" => false, "mensaje" => "No hay clientes para actualizar");
		}

		$limpios = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$cliente = isset($item["cliente"]) ? trim((string) $item["cliente"]) : "";
			$vendedor = isset($item["vendedor_propuesto"])
				? trim((string) $item["vendedor_propuesto"])
				: "";
			if ($cliente === "" || $vendedor === "") {
				continue;
			}
			$limpios[] = array(
				"cliente" => $cliente,
				"vendedor_propuesto" => $vendedor
			);
		}

		if (count($limpios) < 1) {
			return array("ok" => false, "mensaje" => "No hay clientes válidos");
		}

		$resultado = ModeloUtilidades::mdlActualizarVendedorUltimaVenta($limpios);
		if (empty($resultado["ok"])) {
			return $resultado;
		}

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		$usuario = isset($_SESSION["nombre"]) ? (string) $_SESSION["nombre"] : "Usuario";
		$actualizados = isset($resultado["actualizados"]) ? (int) $resultado["actualizados"] : 0;
		$descripcion = "Utilidades: {$usuario} actualizó vendedor de última venta en {$actualizados} cliente(s).";

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
}
