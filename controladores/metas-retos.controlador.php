<?php

class ControladorMetasRetos
{

	static public function ctrPeriodoActual()
	{
		date_default_timezone_set("America/Lima");
		return array(
			"anio" => (int) date("Y"),
			"mes" => (int) date("n")
		);
	}

	static public function ctrNormalizarPeriodo($anio, $mes)
	{
		$anio = (int) $anio;
		$mes = (int) $mes;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}
		if ($mes < 1 || $mes > 12) {
			$mes = (int) date("n");
		}
		return array("anio" => $anio, "mes" => $mes);
	}

	static public function ctrPuedeEditar()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "metas_vendedor", "editar");
	}

	static public function ctrListarAvance($anio, $mes)
	{
		$p = self::ctrNormalizarPeriodo($anio, $mes);
		return ModeloMetasRetos::mdlListarAvancePeriodo($p["anio"], $p["mes"]);
	}

	static public function ctrDetalleAjax($codVendedor, $anio, $mes)
	{
		$p = self::ctrNormalizarPeriodo($anio, $mes);
		$codVendedor = trim((string) $codVendedor);
		$reto = ModeloMetasRetos::mdlObtenerReto($codVendedor, $p["anio"], $p["mes"]);
		return array(
			"ok" => true,
			"cod_vendedor" => $codVendedor,
			"anio" => $p["anio"],
			"mes" => $p["mes"],
			"reto" => $reto
		);
	}

	static private function ctrNumONull($v)
	{
		if ($v === null || $v === "" || !isset($v)) {
			return null;
		}
		if (!is_numeric($v)) {
			return null;
		}
		return $v;
	}

	static private function ctrCumplimiento($v)
	{
		$v = trim((string) $v);
		return ($v === "prorrata") ? "prorrata" : "todo_nada";
	}

	static public function ctrGuardarAjax($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar metas/retos");
		}

		$cod = trim(isset($post["cod_vendedor"]) ? $post["cod_vendedor"] : "");
		$p = self::ctrNormalizarPeriodo(
			isset($post["anio"]) ? $post["anio"] : 0,
			isset($post["mes"]) ? $post["mes"] : 0
		);

		if ($cod === "") {
			return array("ok" => false, "mensaje" => "Vendedor requerido");
		}

		$activos = ModeloMetasRetos::mdlVendedoresActivos();
		$okVend = false;
		foreach ($activos as $v) {
			if (trim($v["codigo"]) === $cod) {
				$okVend = true;
				break;
			}
		}
		if (!$okVend) {
			return array("ok" => false, "mensaje" => "Solo se permiten vendedores activos");
		}

		$datos = array(
			"cod_vendedor" => $cod,
			"anio" => $p["anio"],
			"mes" => $p["mes"],
			"meta_monto" => self::ctrNumONull(isset($post["meta_monto"]) ? $post["meta_monto"] : null),
			"comision_monto_pct" => self::ctrNumONull(isset($post["comision_monto_pct"]) ? $post["comision_monto_pct"] : null),
			"comision_monto_fijo" => self::ctrNumONull(isset($post["comision_monto_fijo"]) ? $post["comision_monto_fijo"] : null),
			"cumplimiento_monto" => self::ctrCumplimiento(isset($post["cumplimiento_monto"]) ? $post["cumplimiento_monto"] : "todo_nada"),
			"meta_clientes" => self::ctrNumONull(isset($post["meta_clientes"]) ? $post["meta_clientes"] : null),
			"comision_clientes_fijo" => self::ctrNumONull(isset($post["comision_clientes_fijo"]) ? $post["comision_clientes_fijo"] : null),
			"cumplimiento_clientes" => self::ctrCumplimiento(isset($post["cumplimiento_clientes"]) ? $post["cumplimiento_clientes"] : "todo_nada"),
			"meta_modelos" => self::ctrNumONull(isset($post["meta_modelos"]) ? $post["meta_modelos"] : null),
			"comision_modelos_fijo" => self::ctrNumONull(isset($post["comision_modelos_fijo"]) ? $post["comision_modelos_fijo"] : null),
			"cumplimiento_modelos" => self::ctrCumplimiento(isset($post["cumplimiento_modelos"]) ? $post["cumplimiento_modelos"] : "todo_nada"),
			"modelo_especial" => isset($post["modelo_especial"]) ? trim((string) $post["modelo_especial"]) : "",
			"meta_docenas_especial" => self::ctrNumONull(isset($post["meta_docenas_especial"]) ? $post["meta_docenas_especial"] : null),
			"comision_modelo_esp_pct" => self::ctrNumONull(isset($post["comision_modelo_esp_pct"]) ? $post["comision_modelo_esp_pct"] : null),
			"cumplimiento_modelo_esp" => self::ctrCumplimiento(isset($post["cumplimiento_modelo_esp"]) ? $post["cumplimiento_modelo_esp"] : "todo_nada"),
			"usuario" => isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0
		);

		$ok = ModeloMetasRetos::mdlGuardarReto($datos);
		if ($ok !== "ok") {
			return array("ok" => false, "mensaje" => "No se pudo guardar");
		}

		ModeloMetasRetos::mdlSyncMetaVentaLegacy(
			$cod,
			$p["anio"],
			$p["mes"],
			$datos["meta_monto"],
			$datos["usuario"]
		);

		return array("ok" => true, "mensaje" => "Metas / retos guardados");
	}

	static public function ctrPctAvance($real, $meta)
	{
		$meta = (float) $meta;
		if ($meta <= 0) {
			return null;
		}
		return round(((float) $real / $meta) * 100, 1);
	}

	/**
	 * Factor 0..1 según cumplimiento.
	 * Sin meta (>0): si hay avance real, factor 1; si no, 0.
	 */
	static public function ctrFactorCumplimiento($real, $meta, $modo)
	{
		$real = (float) $real;
		$meta = ($meta === null || $meta === "") ? 0.0 : (float) $meta;
		$modo = ($modo === "prorrata") ? "prorrata" : "todo_nada";

		if ($meta <= 0) {
			return $real > 0 ? 1.0 : 0.0;
		}
		if ($modo === "prorrata") {
			return max(0.0, min(1.0, $real / $meta));
		}
		return ($real + 1e-9 >= $meta) ? 1.0 : 0.0;
	}

	/**
	 * Estimado a pagar según avance y reglas configuradas (no liquidación).
	 * Retorna ['total'=>float, 'detalle'=>[clave=>monto]]
	 */
	static public function ctrCalcularComisionEstimada($fila)
	{
		$reto = (isset($fila["reto"]) && is_array($fila["reto"])) ? $fila["reto"] : array();
		$detalle = array(
			"monto" => 0.0,
			"clientes" => 0.0,
			"modelos" => 0.0,
			"modelo_especial" => 0.0
		);

		// 1) Monto ventas:
		// - prorrata: comisión % sobre el monto vendido (venta real)
		// - todo_nada: comisión fija solo si se alcanza la meta
		$metaMonto = isset($reto["meta_monto"]) ? $reto["meta_monto"] : null;
		$metaMontoNum = ($metaMonto === null || $metaMonto === "") ? 0.0 : (float) $metaMonto;
		$modoMonto = (isset($reto["cumplimiento_monto"]) && $reto["cumplimiento_monto"] === "prorrata")
			? "prorrata"
			: "todo_nada";
		$pctMonto = isset($reto["comision_monto_pct"]) ? (float) $reto["comision_monto_pct"] : 0.0;
		$fijoMonto = isset($reto["comision_monto_fijo"]) ? (float) $reto["comision_monto_fijo"] : 0.0;
		$ventaReal = isset($fila["venta_real"]) ? (float) $fila["venta_real"] : 0.0;

		if ($modoMonto === "prorrata") {
			if ($pctMonto > 0 && $ventaReal > 0) {
				$detalle["monto"] = round($ventaReal * ($pctMonto / 100.0), 2);
			}
		} elseif ($metaMontoNum > 0 && $ventaReal + 1e-9 >= $metaMontoNum && $fijoMonto > 0) {
			$detalle["monto"] = round($fijoMonto, 2);
		}

		// 2) Clientes nuevos: fijo
		$metaCli = isset($reto["meta_clientes"]) ? $reto["meta_clientes"] : null;
		$fCli = self::ctrFactorCumplimiento(
			isset($fila["clientes_nuevos"]) ? $fila["clientes_nuevos"] : 0,
			$metaCli,
			isset($reto["cumplimiento_clientes"]) ? $reto["cumplimiento_clientes"] : "todo_nada"
		);
		$fijoCli = isset($reto["comision_clientes_fijo"]) ? (float) $reto["comision_clientes_fijo"] : 0.0;
		if ($fCli > 0 && $fijoCli > 0) {
			$detalle["clientes"] = round($fijoCli * $fCli, 2);
		}

		// 3) Modelos activos: fijo
		$metaMod = isset($reto["meta_modelos"]) ? $reto["meta_modelos"] : null;
		$fMod = self::ctrFactorCumplimiento(
			isset($fila["modelos_activos"]) ? $fila["modelos_activos"] : 0,
			$metaMod,
			isset($reto["cumplimiento_modelos"]) ? $reto["cumplimiento_modelos"] : "todo_nada"
		);
		$fijoMod = isset($reto["comision_modelos_fijo"]) ? (float) $reto["comision_modelos_fijo"] : 0.0;
		if ($fMod > 0 && $fijoMod > 0) {
			$detalle["modelos"] = round($fijoMod * $fMod, 2);
		}

		// 4) Modelo especial: % sobre venta del modelo
		$modeloEsp = isset($fila["modelo_especial"]) ? trim((string) $fila["modelo_especial"]) : "";
		if ($modeloEsp === "" && !empty($reto["modelo_especial"])) {
			$modeloEsp = trim((string) $reto["modelo_especial"]);
		}
		if ($modeloEsp !== "") {
			$metaDoc = isset($reto["meta_docenas_especial"]) ? $reto["meta_docenas_especial"] : null;
			$fEsp = self::ctrFactorCumplimiento(
				isset($fila["docenas_especial"]) ? $fila["docenas_especial"] : 0,
				$metaDoc,
				isset($reto["cumplimiento_modelo_esp"]) ? $reto["cumplimiento_modelo_esp"] : "todo_nada"
			);
			$pctEsp = isset($reto["comision_modelo_esp_pct"]) ? (float) $reto["comision_modelo_esp_pct"] : 0.0;
			$ventaEsp = isset($fila["venta_modelo_especial"]) ? (float) $fila["venta_modelo_especial"] : 0.0;
			if ($fEsp > 0 && $pctEsp > 0 && $ventaEsp > 0) {
				$detalle["modelo_especial"] = round($ventaEsp * ($pctEsp / 100.0) * $fEsp, 2);
			}
		}

		$total = round(
			$detalle["monto"] + $detalle["clientes"] + $detalle["modelos"] + $detalle["modelo_especial"],
			2
		);

		return array(
			"total" => $total,
			"detalle" => $detalle
		);
	}

	static public function ctrListarModelosAjax($q = "")
	{
		return array(
			"ok" => true,
			"data" => ModeloMetasRetos::mdlListarModelosCatalogo($q)
		);
	}
}
