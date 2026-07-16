<?php

require_once dirname(__FILE__) . "/metas-retos.config.php";

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
		$fechaRef = sprintf("%04d-%02d-01", $p["anio"], $p["mes"]);
		require_once dirname(__FILE__) . "/../modelos/grupos-marcas-comercial.modelo.php";
		$universo = ModeloGruposMarcasComercial::mdlUniversoModelosActivosPorVendedor($codVendedor, $fechaRef);

		$incentivos = array();
		if ($reto && isset($reto["id"])) {
			$incentivos = ModeloMetasRetos::mdlListarIncentivosPorReto((int) $reto["id"]);
		}

		return array(
			"ok" => true,
			"cod_vendedor" => $codVendedor,
			"anio" => $p["anio"],
			"mes" => $p["mes"],
			"reto" => $reto,
			"universo_modelos" => $universo,
			"incentivos" => $incentivos,
			"comision_ventas_habilitada" => mrComisionVentasHabilitada(),
			"codigos_cobranza" => mrCodigosCobranzaEfectiva(),
			"igv_factor" => mrIgvFactor()
		);
	}

	static public function ctrUniversoModelosAjax($codVendedor, $anio, $mes)
	{
		$p = self::ctrNormalizarPeriodo($anio, $mes);
		$codVendedor = trim((string) $codVendedor);
		$fechaRef = sprintf("%04d-%02d-01", $p["anio"], $p["mes"]);
		require_once dirname(__FILE__) . "/../modelos/grupos-marcas-comercial.modelo.php";
		$universo = ModeloGruposMarcasComercial::mdlUniversoModelosActivosPorVendedor($codVendedor, $fechaRef);
		return array(
			"ok" => true,
			"cod_vendedor" => $codVendedor,
			"universo_modelos" => $universo,
			"fecha_ref" => $fechaRef
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

	static private function ctrClaveIncentivo($inc)
	{
		$tipo = isset($inc["tipo_objetivo"]) ? $inc["tipo_objetivo"] : "";
		$modelo = isset($inc["modelo"]) ? trim((string) $inc["modelo"]) : "";
		$color = isset($inc["cod_color"]) ? trim((string) $inc["cod_color"]) : "";
		$art = isset($inc["articulo"]) ? trim((string) $inc["articulo"]) : "";
		$unidad = isset($inc["unidad_meta"]) ? $inc["unidad_meta"] : "docenas";
		return strtolower($tipo . "|" . $modelo . "|" . $color . "|" . $art . "|" . $unidad);
	}

	static private function ctrEtiquetaIncentivo($inc)
	{
		$tipo = isset($inc["tipo_objetivo"]) ? $inc["tipo_objetivo"] : "";
		if ($tipo === "modelo") {
			return "Modelo " . (isset($inc["modelo"]) ? $inc["modelo"] : "");
		}
		if ($tipo === "modelo_color") {
			$nom = !empty($inc["nombre_color"]) ? $inc["nombre_color"] : (isset($inc["cod_color"]) ? $inc["cod_color"] : "");
			return "Modelo " . (isset($inc["modelo"]) ? $inc["modelo"] : "") . " · " . $nom;
		}
		if ($tipo === "articulo") {
			return "Artículo " . (isset($inc["articulo"]) ? $inc["articulo"] : "");
		}
		return "Incentivo";
	}

	/**
	 * Detecta pares que pueden sumar la misma línea (aviso, no bloqueo).
	 */
	static public function ctrDetectarSuperposiciones($incentivos)
	{
		$avisos = array();
		$n = count($incentivos);
		for ($i = 0; $i < $n; $i++) {
			for ($j = $i + 1; $j < $n; $j++) {
				$a = $incentivos[$i];
				$b = $incentivos[$j];
				$ta = $a["tipo_objetivo"];
				$tb = $b["tipo_objetivo"];

				$solapa = false;
				if ($ta === "modelo" && $tb === "modelo_color"
					&& $a["modelo"] === $b["modelo"]) {
					$solapa = true;
				} elseif ($tb === "modelo" && $ta === "modelo_color"
					&& $a["modelo"] === $b["modelo"]) {
					$solapa = true;
				} elseif ($ta === "modelo" && $tb === "articulo"
					&& $a["modelo"] === (isset($b["_modelo_art"]) ? $b["_modelo_art"] : "")) {
					$solapa = true;
				} elseif ($tb === "modelo" && $ta === "articulo"
					&& $b["modelo"] === (isset($a["_modelo_art"]) ? $a["_modelo_art"] : "")) {
					$solapa = true;
				} elseif ($ta === "modelo_color" && $tb === "articulo"
					&& $a["modelo"] === (isset($b["_modelo_art"]) ? $b["_modelo_art"] : "")
					&& $a["cod_color"] === (isset($b["_color_art"]) ? $b["_color_art"] : "")) {
					$solapa = true;
				} elseif ($tb === "modelo_color" && $ta === "articulo"
					&& $b["modelo"] === (isset($a["_modelo_art"]) ? $a["_modelo_art"] : "")
					&& $b["cod_color"] === (isset($a["_color_art"]) ? $a["_color_art"] : "")) {
					$solapa = true;
				}

				if ($solapa) {
					$avisos[] = self::ctrEtiquetaIncentivo($a) . " se solapa con " . self::ctrEtiquetaIncentivo($b);
				}
			}
		}
		return $avisos;
	}

	static private function ctrNormalizarIncentivosPost($post, $codVendedor, $anio, $mes)
	{
		$raw = array();
		if (isset($post["incentivos_json"]) && is_string($post["incentivos_json"])) {
			$decoded = json_decode($post["incentivos_json"], true);
			if (is_array($decoded)) {
				$raw = $decoded;
			}
		} elseif (isset($post["incentivos"]) && is_array($post["incentivos"])) {
			$raw = $post["incentivos"];
		}

		$fechaRef = sprintf("%04d-%02d-01", (int) $anio, (int) $mes);
		require_once dirname(__FILE__) . "/../modelos/grupos-marcas-comercial.modelo.php";

		$normalizados = array();
		$claves = array();

		foreach ($raw as $idx => $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo_objetivo"]) ? trim((string) $item["tipo_objetivo"]) : "";
			if (!in_array($tipo, array("modelo", "modelo_color", "articulo"), true)) {
				return array("ok" => false, "mensaje" => "Tipo de objetivo inválido en incentivo #" . ($idx + 1));
			}

			$unidad = (isset($item["unidad_meta"]) && trim((string) $item["unidad_meta"]) === "unidades")
				? "unidades" : "docenas";
			$meta = self::ctrNumONull(isset($item["meta_cantidad"]) ? $item["meta_cantidad"] : null);
			$pct = self::ctrNumONull(isset($item["comision_pct"]) ? $item["comision_pct"] : null);
			$cumpl = self::ctrCumplimiento(isset($item["cumplimiento"]) ? $item["cumplimiento"] : "todo_nada");

			if ($meta === null || (float) $meta <= 0) {
				return array("ok" => false, "mensaje" => "La meta del incentivo #" . ($idx + 1) . " debe ser mayor a 0");
			}
			if ($pct === null) {
				$pct = 0;
			}
			$pctNum = (float) $pct;
			if ($pctNum < 0 || $pctNum > 100) {
				return array("ok" => false, "mensaje" => "Comisión del incentivo #" . ($idx + 1) . " debe estar entre 0 y 100");
			}

			$modelo = "";
			$codColor = "";
			$articulo = "";
			$nombreColor = "";
			$metaArt = null;

			if ($tipo === "modelo") {
				$modelo = isset($item["modelo"]) ? trim((string) $item["modelo"]) : "";
				if ($modelo === "" || !ModeloMetasRetos::mdlExisteModelo($modelo)) {
					return array("ok" => false, "mensaje" => "Modelo inválido en incentivo #" . ($idx + 1));
				}
				$cob = ModeloGruposMarcasComercial::mdlVerificarCoberturaModelo($codVendedor, $modelo, $fechaRef);
				if (empty($cob["ok"])) {
					return array(
						"ok" => false,
						"mensaje" => "El modelo {$modelo} no está en cobertura del vendedor (incentivo #" . ($idx + 1) . ")"
					);
				}
			} elseif ($tipo === "modelo_color") {
				$modelo = isset($item["modelo"]) ? trim((string) $item["modelo"]) : "";
				$codColor = isset($item["cod_color"]) ? trim((string) $item["cod_color"]) : "";
				if ($modelo === "" || $codColor === ""
					|| !ModeloMetasRetos::mdlExisteModeloColor($modelo, $codColor)) {
					return array("ok" => false, "mensaje" => "Modelo/color inválido en incentivo #" . ($idx + 1));
				}
				$cob = ModeloGruposMarcasComercial::mdlVerificarCoberturaModelo($codVendedor, $modelo, $fechaRef);
				if (empty($cob["ok"])) {
					return array(
						"ok" => false,
						"mensaje" => "El modelo {$modelo} no está en cobertura del vendedor (incentivo #" . ($idx + 1) . ")"
					);
				}
				$nombreColor = isset($item["nombre_color"]) ? trim((string) $item["nombre_color"]) : $codColor;
			} else {
				$articulo = isset($item["articulo"]) ? trim((string) $item["articulo"]) : "";
				$metaArt = ModeloMetasRetos::mdlExisteArticulo($articulo);
				if (!$metaArt) {
					return array("ok" => false, "mensaje" => "Artículo inválido en incentivo #" . ($idx + 1));
				}
				$cob = ModeloGruposMarcasComercial::mdlVerificarCoberturaArticulo($codVendedor, $articulo, $fechaRef);
				if (empty($cob["ok"])) {
					return array(
						"ok" => false,
						"mensaje" => "El artículo {$articulo} no está en cobertura del vendedor (incentivo #" . ($idx + 1) . ")"
					);
				}
				$modelo = isset($metaArt["modelo"]) ? trim((string) $metaArt["modelo"]) : "";
				$codColor = isset($metaArt["cod_color"]) ? trim((string) $metaArt["cod_color"]) : "";
			}

			$row = array(
				"tipo_objetivo" => $tipo,
				"modelo" => ($tipo === "articulo") ? null : $modelo,
				"cod_color" => ($tipo === "modelo_color") ? $codColor : null,
				"articulo" => ($tipo === "articulo") ? $articulo : null,
				"unidad_meta" => $unidad,
				"meta_cantidad" => round((float) $meta, 2),
				"comision_pct" => round($pctNum, 2),
				"cumplimiento" => $cumpl,
				"observacion" => isset($item["observacion"]) ? trim((string) $item["observacion"]) : null,
				"nombre_color" => $nombreColor,
				"_modelo_art" => ($tipo === "articulo") ? $modelo : "",
				"_color_art" => ($tipo === "articulo") ? $codColor : ""
			);

			$clave = self::ctrClaveIncentivo($row);
			if (isset($claves[$clave])) {
				return array(
					"ok" => false,
					"mensaje" => "Hay incentivos duplicados (mismo objetivo y unidad). Revisá la fila #" . ($idx + 1)
				);
			}
			$claves[$clave] = true;
			$normalizados[] = $row;
		}

		$superpuestos = self::ctrDetectarSuperposiciones($normalizados);
		$forzar = !empty($post["forzar_superpuestos"]) && (
			$post["forzar_superpuestos"] === "1"
			|| $post["forzar_superpuestos"] === 1
			|| $post["forzar_superpuestos"] === true
			|| $post["forzar_superpuestos"] === "true"
		);

		if (!empty($superpuestos) && !$forzar) {
			return array(
				"ok" => false,
				"requiere_confirmacion" => true,
				"mensaje" => "Hay objetivos superpuestos que pueden pagar dos veces la misma venta. Confirmá para guardar.",
				"superpuestos" => $superpuestos
			);
		}

		// Limpiar claves internas antes de persistir
		foreach ($normalizados as &$n) {
			unset($n["_modelo_art"], $n["_color_art"], $n["nombre_color"]);
		}
		unset($n);

		return array("ok" => true, "incentivos" => $normalizados, "superpuestos" => $superpuestos);
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

		$modoModelos = isset($post["meta_modelos_modo"]) && trim((string) $post["meta_modelos_modo"]) === "porcentaje"
			? "porcentaje"
			: "cantidad";
		$pctModelos = self::ctrNumONull(isset($post["meta_modelos_pct"]) ? $post["meta_modelos_pct"] : null);
		$metaModelos = self::ctrNumONull(isset($post["meta_modelos"]) ? $post["meta_modelos"] : null);

		$fechaRef = sprintf("%04d-%02d-01", $p["anio"], $p["mes"]);
		require_once dirname(__FILE__) . "/../modelos/grupos-marcas-comercial.modelo.php";
		$universo = ModeloGruposMarcasComercial::mdlUniversoModelosActivosPorVendedor($cod, $fechaRef);

		if ($modoModelos === "porcentaje") {
			if ($pctModelos === null) {
				return array("ok" => false, "mensaje" => "Indicá el porcentaje de modelos");
			}
			$pctNum = (float) $pctModelos;
			if ($pctNum < 0 || $pctNum > 100) {
				return array("ok" => false, "mensaje" => "El porcentaje debe estar entre 0 y 100");
			}
			if ($universo < 1) {
				return array("ok" => false, "mensaje" => "El vendedor no tiene modelos activos en grupos vigentes");
			}
			$metaModelos = ($pctNum > 0)
				? (int) ceil($universo * ($pctNum / 100.0))
				: 0;
			if ($pctNum > 0 && $metaModelos < 1) {
				$metaModelos = 1;
			}
		} else {
			$pctModelos = null;
		}

		$normInc = self::ctrNormalizarIncentivosPost($post, $cod, $p["anio"], $p["mes"]);
		if (empty($normInc["ok"])) {
			return $normInc;
		}
		$incentivos = $normInc["incentivos"];

		$cumplCob = self::ctrCumplimiento(isset($post["cumplimiento_cobranza"]) ? $post["cumplimiento_cobranza"] : "todo_nada");
		$metaCob = self::ctrNumONull(isset($post["meta_cobranza"]) ? $post["meta_cobranza"] : null);
		$pctCob = self::ctrNumONull(isset($post["comision_cobranza_pct"]) ? $post["comision_cobranza_pct"] : null);
		$fijoCob = self::ctrNumONull(isset($post["comision_cobranza_fijo"]) ? $post["comision_cobranza_fijo"] : null);

		if ($metaCob !== null && (float) $metaCob < 0) {
			return array("ok" => false, "mensaje" => "La meta de cobranza no puede ser negativa");
		}
		if ($cumplCob === "prorrata") {
			$fijoCob = null;
			if ($pctCob !== null) {
				$pctNum = (float) $pctCob;
				if ($pctNum < 0 || $pctNum > 100) {
					return array("ok" => false, "mensaje" => "Comisión % de cobranza debe estar entre 0 y 100");
				}
			}
		} else {
			$pctCob = null;
			if ($fijoCob !== null && (float) $fijoCob < 0) {
				return array("ok" => false, "mensaje" => "Comisión fija de cobranza no puede ser negativa");
			}
		}

		$existente = ModeloMetasRetos::mdlObtenerReto($cod, $p["anio"], $p["mes"]);
		$metaMonto = self::ctrNumONull(isset($post["meta_monto"]) ? $post["meta_monto"] : null);
		$pctMonto = self::ctrNumONull(isset($post["comision_monto_pct"]) ? $post["comision_monto_pct"] : null);
		$fijoMonto = self::ctrNumONull(isset($post["comision_monto_fijo"]) ? $post["comision_monto_fijo"] : null);
		$cumplMonto = self::ctrCumplimiento(isset($post["cumplimiento_monto"]) ? $post["cumplimiento_monto"] : "todo_nada");

		// Con comisión de ventas desactivada, conservar valores existentes (campos no editables).
		if (!mrComisionVentasHabilitada() && $existente) {
			$metaMonto = isset($existente["meta_monto"]) ? $existente["meta_monto"] : $metaMonto;
			$pctMonto = isset($existente["comision_monto_pct"]) ? $existente["comision_monto_pct"] : $pctMonto;
			$fijoMonto = isset($existente["comision_monto_fijo"]) ? $existente["comision_monto_fijo"] : $fijoMonto;
			$cumplMonto = isset($existente["cumplimiento_monto"])
				? self::ctrCumplimiento($existente["cumplimiento_monto"])
				: $cumplMonto;
		} elseif ($cumplMonto === "prorrata") {
			$fijoMonto = null;
		} else {
			$pctMonto = null;
		}

		$datos = array(
			"cod_vendedor" => $cod,
			"anio" => $p["anio"],
			"mes" => $p["mes"],
			"meta_cobranza" => $metaCob,
			"comision_cobranza_pct" => $pctCob,
			"comision_cobranza_fijo" => $fijoCob,
			"cumplimiento_cobranza" => $cumplCob,
			"meta_monto" => $metaMonto,
			"comision_monto_pct" => $pctMonto,
			"comision_monto_fijo" => $fijoMonto,
			"cumplimiento_monto" => $cumplMonto,
			"meta_clientes" => self::ctrNumONull(isset($post["meta_clientes"]) ? $post["meta_clientes"] : null),
			"comision_clientes_fijo" => self::ctrNumONull(isset($post["comision_clientes_fijo"]) ? $post["comision_clientes_fijo"] : null),
			"cumplimiento_clientes" => self::ctrCumplimiento(isset($post["cumplimiento_clientes"]) ? $post["cumplimiento_clientes"] : "todo_nada"),
			"meta_modelos" => $metaModelos,
			"meta_modelos_modo" => $modoModelos,
			"meta_modelos_pct" => $pctModelos,
			"comision_modelos_fijo" => self::ctrNumONull(isset($post["comision_modelos_fijo"]) ? $post["comision_modelos_fijo"] : null),
			"cumplimiento_modelos" => self::ctrCumplimiento(isset($post["cumplimiento_modelos"]) ? $post["cumplimiento_modelos"] : "todo_nada"),
			"usuario" => isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0
		);

		$ok = ModeloMetasRetos::mdlGuardarReto($datos, $incentivos);
		if ($ok === "error_cobranza") {
			return array(
				"ok" => false,
				"mensaje" => "Faltan columnas de cobranza. Ejecutá docs/sql/metas-retos-comision-cobranza.sql"
			);
		}
		if ($ok !== "ok") {
			return array(
				"ok" => false,
				"mensaje" => "No se pudo guardar. Verificá que exista la tabla metas_retos_incentivos_productojf (docs/sql/metas-retos-incentivos-producto.sql)."
			);
		}

		ModeloMetasRetos::mdlSyncMetaVentaLegacy(
			$cod,
			$p["anio"],
			$p["mes"],
			$datos["meta_monto"],
			$datos["usuario"]
		);
		ModeloMetasRetos::mdlSyncMetaCobranzaLegacy(
			$cod,
			$p["anio"],
			$p["mes"],
			$datos["meta_cobranza"],
			$datos["usuario"]
		);

		return array(
			"ok" => true,
			"mensaje" => "Metas / retos guardados",
			"meta_modelos" => $metaModelos,
			"universo_modelos" => $universo,
			"incentivos_count" => count($incentivos)
		);
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
	 *
	 * Política vigente: comisión general por cobranza; ventas = 0 si
	 * MR_COMISION_VENTAS_HABILITADA es false (incentivos de producto sí pagan).
	 */
	static public function ctrCalcularComisionEstimada($fila)
	{
		$reto = (isset($fila["reto"]) && is_array($fila["reto"])) ? $fila["reto"] : array();
		$detalle = array(
			"cobranza" => 0.0,
			"monto" => 0.0,
			"clientes" => 0.0,
			"modelos" => 0.0,
			"incentivos_producto" => 0.0,
			"incentivos" => array(),
			"comision_ventas_habilitada" => mrComisionVentasHabilitada()
		);

		// 0) Cobranza efectiva (neta sin IGV):
		// - prorrata: % sobre toda la cobranza neta lograda (sin tope por meta)
		// - todo_nada: fijo solo si cobranza_neta >= meta
		$metaCob = isset($reto["meta_cobranza"]) ? $reto["meta_cobranza"] : null;
		$metaCobNum = ($metaCob === null || $metaCob === "") ? 0.0 : (float) $metaCob;
		$modoCob = (isset($reto["cumplimiento_cobranza"]) && $reto["cumplimiento_cobranza"] === "prorrata")
			? "prorrata"
			: "todo_nada";
		$pctCob = isset($reto["comision_cobranza_pct"]) ? (float) $reto["comision_cobranza_pct"] : 0.0;
		$fijoCob = isset($reto["comision_cobranza_fijo"]) ? (float) $reto["comision_cobranza_fijo"] : 0.0;
		$cobranzaReal = isset($fila["cobranza_neta_real"]) ? (float) $fila["cobranza_neta_real"] : 0.0;

		if ($modoCob === "prorrata") {
			if ($pctCob > 0 && $cobranzaReal > 0) {
				$detalle["cobranza"] = round($cobranzaReal * ($pctCob / 100.0), 2);
			}
		} elseif ($metaCobNum > 0 && $cobranzaReal + 1e-9 >= $metaCobNum && $fijoCob > 0) {
			$detalle["cobranza"] = round($fijoCob, 2);
		}

		// 1) Monto ventas (referencia; aporte 0 si comisión de ventas desactivada)
		$metaMonto = isset($reto["meta_monto"]) ? $reto["meta_monto"] : null;
		$metaMontoNum = ($metaMonto === null || $metaMonto === "") ? 0.0 : (float) $metaMonto;
		$modoMonto = (isset($reto["cumplimiento_monto"]) && $reto["cumplimiento_monto"] === "prorrata")
			? "prorrata"
			: "todo_nada";
		$pctMonto = isset($reto["comision_monto_pct"]) ? (float) $reto["comision_monto_pct"] : 0.0;
		$fijoMonto = isset($reto["comision_monto_fijo"]) ? (float) $reto["comision_monto_fijo"] : 0.0;
		$ventaReal = isset($fila["venta_real"]) ? (float) $fila["venta_real"] : 0.0;

		$aporteMontoCalc = 0.0;
		if ($modoMonto === "prorrata") {
			if ($pctMonto > 0 && $ventaReal > 0) {
				$aporteMontoCalc = round($ventaReal * ($pctMonto / 100.0), 2);
			}
		} elseif ($metaMontoNum > 0 && $ventaReal + 1e-9 >= $metaMontoNum && $fijoMonto > 0) {
			$aporteMontoCalc = round($fijoMonto, 2);
		}
		$detalle["monto"] = mrComisionVentasHabilitada() ? $aporteMontoCalc : 0.0;

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

		// 4) Incentivos de producto: % sobre venta del objetivo, acumulables
		$incs = (isset($fila["incentivos"]) && is_array($fila["incentivos"])) ? $fila["incentivos"] : array();
		foreach ($incs as $inc) {
			$metaCant = isset($inc["meta_cantidad"]) ? $inc["meta_cantidad"] : null;
			$metaCantNum = ($metaCant === null || $metaCant === "") ? 0.0 : (float) $metaCant;
			$avance = isset($inc["avance_meta"]) ? (float) $inc["avance_meta"] : 0.0;
			$modo = isset($inc["cumplimiento"]) ? $inc["cumplimiento"] : "todo_nada";
			$pct = isset($inc["comision_pct"]) ? (float) $inc["comision_pct"] : 0.0;
			$ventaObj = isset($inc["venta_objetivo"]) ? (float) $inc["venta_objetivo"] : 0.0;
			$aporte = 0.0;
			// Sin meta > 0 no se paga (evita factor=1 de ctrFactorCumplimiento con meta vacía).
			if ($metaCantNum > 0) {
				$factor = self::ctrFactorCumplimiento($avance, $metaCantNum, $modo);
				if ($factor > 0 && $pct > 0 && $ventaObj > 0) {
					$aporte = round($ventaObj * ($pct / 100.0) * $factor, 2);
				}
			}
			$detalle["incentivos"][] = array(
				"id" => isset($inc["id"]) ? (int) $inc["id"] : null,
				"etiqueta" => self::ctrEtiquetaIncentivo($inc),
				"avance_meta" => $avance,
				"meta_cantidad" => $metaCant,
				"aporte" => $aporte
			);
			$detalle["incentivos_producto"] = round($detalle["incentivos_producto"] + $aporte, 2);
		}

		$total = round(
			$detalle["cobranza"] + $detalle["monto"] + $detalle["clientes"]
			+ $detalle["modelos"] + $detalle["incentivos_producto"],
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

	static public function ctrListarColoresModeloAjax($modelo)
	{
		return array(
			"ok" => true,
			"data" => ModeloMetasRetos::mdlListarColoresPorModelo($modelo)
		);
	}

	static public function ctrBuscarArticulosAjax($q = "")
	{
		return array(
			"ok" => true,
			"data" => ModeloMetasRetos::mdlBuscarArticulos($q)
		);
	}
}
