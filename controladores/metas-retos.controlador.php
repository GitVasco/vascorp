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
}
