<?php

class ControladorFichaGerencialModelos
{
	static private function ctrPuedeVer()
	{
		return function_exists("usuarioPuedeVerModulo")
			&& usuarioPuedeVerModulo("gestion_comercial", "ficha_modelos");
	}

	static private function ctrPuedeConciliar()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "ficha_modelos", "conciliar");
	}

	static private function ctrPeriodo($post, $requiereMes = true)
	{
		$anio = isset($post["anio"]) ? (int) $post["anio"] : (int) date("Y");
		$mes = isset($post["mes"]) ? (int) $post["mes"] : (int) date("n");
		if ($anio < 2021 || $anio > (int) date("Y")) {
			return null;
		}
		if ($requiereMes && ($mes < 1 || $mes > 12)) {
			return null;
		}
		return array("anio" => $anio, "mes" => $mes);
	}

	static private function ctrModelo($post)
	{
		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		if ($modelo === "" || strlen($modelo) > 10 || !preg_match('/^[A-Za-z0-9._-]+$/', $modelo)) {
			return null;
		}
		return $modelo;
	}

	static private function ctrMeta($fuente, $formula)
	{
		return array(
			"fuente" => $fuente,
			"formula" => $formula,
			"consultado_en" => date("Y-m-d H:i:s")
		);
	}

	static private function ctrContexto($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso para consultar la ficha");
		}
		$modelo = self::ctrModelo($post);
		$periodo = self::ctrPeriodo($post);
		if ($modelo === null || $periodo === null) {
			return array("ok" => false, "mensaje" => "Modelo o período inválidos");
		}
		$cabecera = ModeloFichaGerencialModelos::mdlCabeceraModelo($modelo);
		if (!$cabecera) {
			return array("ok" => false, "mensaje" => "El modelo no existe o no está activo");
		}
		if (ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($periodo["anio"]) === null) {
			return array("ok" => false, "mensaje" => "No existe información de movimientos para el año seleccionado");
		}
		return array(
			"ok" => true,
			"modelo" => $modelo,
			"anio" => $periodo["anio"],
			"mes" => $periodo["mes"],
			"cabecera" => $cabecera
		);
	}

	static private function ctrContextoComparativo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso para consultar el resumen");
		}
		$periodo = self::ctrPeriodo($post);
		if ($periodo === null) {
			return array("ok" => false, "mensaje" => "Período inválido");
		}
		if (ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($periodo["anio"]) === null) {
			return array("ok" => false, "mensaje" => "No existe información de movimientos para el año seleccionado");
		}
		return array(
			"ok" => true,
			"anio" => $periodo["anio"],
			"mes" => $periodo["mes"]
		);
	}

	static public function ctrCatalogo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		if (strlen($q) > 100) {
			return array("ok" => false, "mensaje" => "Búsqueda inválida");
		}
		try {
			return array(
				"ok" => true,
				"modelos" => ModeloFichaGerencialModelos::mdlCatalogoModelosActivos($idMarca, $q),
				"marcas" => ModeloFichaGerencialModelos::mdlMarcasConModelosActivos(),
				"grupos" => ModeloFichaGerencialModelos::mdlGruposConModelosActivos()
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar el catálogo");
		}
	}

	static public function ctrResumen($post)
	{
		try {
			$ctx = self::ctrContexto($post);
			if (!$ctx["ok"]) {
				return $ctx;
			}
			$inventario = ModeloFichaGerencialModelos::mdlInventarioResumen($ctx["modelo"]);
			$ventas = ModeloFichaGerencialModelos::mdlVentasResumen($ctx["modelo"], $ctx["anio"], $ctx["mes"]);
			$unidadesNetas = isset($ventas["unidades_vendidas"]) ? $ventas["unidades_vendidas"] : "0";
			$precio9 = ModeloFichaGerencialModelos::mdlPrecio9Valorizado($ctx["modelo"], $unidadesNetas);
			$ranking = ModeloFichaGerencialModelos::mdlRankingGeneral($ctx["modelo"], $ctx["anio"], $ctx["mes"]);
			$lideresComerciales = ModeloFichaGerencialModelos::mdlLideresComerciales($ctx["modelo"], $ctx["anio"], $ctx["mes"]);
			$rentabilidad = null;
			if ($precio9) {
				$rentabilidad = ModeloCostosModeloMensual::mdlCalcularRentabilidadUltimoAprobado(
					$ctx["modelo"],
					$ctx["anio"],
					$ctx["mes"],
					$precio9["ventas_acumuladas"],
					$unidadesNetas
				);
				if ($rentabilidad) {
					$rentabilidad["costo_arrastrado"] = (int) $rentabilidad["costo_anio"] !== $ctx["anio"]
						|| (int) $rentabilidad["costo_mes"] !== $ctx["mes"];
				}
			}

			$precioPromedio = null;
			$unidades = isset($ventas["unidades_vendidas"]) ? (float) $ventas["unidades_vendidas"] : 0.0;
			if ($unidades != 0.0) {
				$precioPromedio = number_format((float) $ventas["venta_neta"] / $unidades, 4, ".", "");
			}
			$stockDisponible = isset($inventario["stock_disponible"]) ? (float) $inventario["stock_disponible"] : 0.0;
			$rotacion = $stockDisponible > 0 ? $unidades / $stockDisponible : null;
			$diasPeriodo = ($ctx["anio"] === (int) date("Y") && $ctx["mes"] === (int) date("n"))
				? (int) date("j")
				: cal_days_in_month(CAL_GREGORIAN, $ctx["mes"], $ctx["anio"]);
			$promedioDiario = $diasPeriodo > 0 ? $unidades / $diasPeriodo : 0.0;
			$diasInventario = ($stockDisponible > 0 && $promedioDiario > 0)
				? $stockDisponible / $promedioDiario
				: null;

			return array(
				"ok" => true,
				"cabecera" => $ctx["cabecera"],
				"periodo" => array("anio" => $ctx["anio"], "mes" => $ctx["mes"]),
				"ventas" => $ventas,
				"inventario" => $inventario,
				"precio_promedio" => $precioPromedio,
				"precio_lista9" => $precio9 ? $precio9["precio9"] : null,
				"ventas_acumuladas" => $precio9 ? $precio9["ventas_acumuladas"] : null,
				"ranking_general" => $ranking,
				"lideres_comerciales" => $lideresComerciales,
				"rotacion_promedio" => $rotacion === null ? null : number_format($rotacion, 4, ".", ""),
				"dias_inventario" => $diasInventario === null ? null : number_format($diasInventario, 2, ".", ""),
				"rentabilidad" => $rentabilidad ? $rentabilidad : array(
					"costo_anio" => null,
					"costo_mes" => null,
					"costo_unitario" => null,
					"costo_venta" => null,
					"utilidad" => null,
					"margen_pct" => null,
					"estado" => "costo_pendiente_aprobacion"
				),
				"meta" => array(
					"ventas" => self::ctrMeta("movimientosjf_" . $ctx["anio"] . " + articulojf + preciojf", "ventas acumuladas = unidades netas × precio de lista 9"),
					"inventario" => self::ctrMeta("articulojf", "stock disponible = stock físico - pedidos"),
					"rentabilidad" => self::ctrMeta("costos_modelo_mensualjf + preciojf", "usa el costo aprobado del período o el último aprobado anterior; nunca uno futuro")
				)
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo construir el resumen");
		}
	}

	static public function ctrResumenComparativo($post)
	{
		try {
			$ctx = self::ctrContextoComparativo($post);
			if (!$ctx["ok"]) {
				return $ctx;
			}
			$idGrupo = isset($post["id_grupo"]) ? (int) $post["id_grupo"] : 0;
			if ($idGrupo < 0) {
				return array("ok" => false, "mensaje" => "Grupo inválido");
			}

			$filas = ModeloFichaGerencialModelos::mdlResumenComparativo(
				$ctx["anio"],
				$ctx["mes"],
				$idGrupo
			);
			$totalesGrupo = array();
			foreach ($filas as $fila) {
				if ((int) $fila["movimientos_periodo"] <= 0 || $fila["grupo_id"] === null) {
					continue;
				}
				$claveGrupo = (string) $fila["grupo_id"];
				$totalesGrupo[$claveGrupo] = isset($totalesGrupo[$claveGrupo])
					? $totalesGrupo[$claveGrupo] + 1
					: 1;
			}

			$posicionesGrupo = array();
			$diasPeriodo = ($ctx["anio"] === (int) date("Y") && $ctx["mes"] === (int) date("n"))
				? (int) date("j")
				: cal_days_in_month(CAL_GREGORIAN, $ctx["mes"], $ctx["anio"]);
			$resultado = array();
			foreach ($filas as $fila) {
				$unidades = (float) $fila["unidades_vendidas"];
				$stockDisponible = (float) $fila["stock_disponible"];
				$rotacion = $stockDisponible > 0 ? $unidades / $stockDisponible : null;
				$promedioDiario = $diasPeriodo > 0 ? $unidades / $diasPeriodo : 0.0;
				$diasInventario = ($stockDisponible > 0 && $promedioDiario > 0)
					? $stockDisponible / $promedioDiario
					: null;
				$unidadesAnterior = (float) $fila["unidades_acumuladas_anterior"];
				$variacionInteranual = $unidadesAnterior != 0.0
					? (((float) $fila["unidades_acumuladas"] - $unidadesAnterior) * 100 / abs($unidadesAnterior))
					: null;

				$ranking = null;
				$totalRanking = 0;
				if ((int) $fila["movimientos_periodo"] > 0 && $fila["grupo_id"] !== null) {
					$claveGrupo = (string) $fila["grupo_id"];
					$posicionesGrupo[$claveGrupo] = isset($posicionesGrupo[$claveGrupo])
						? $posicionesGrupo[$claveGrupo] + 1
						: 1;
					$ranking = $posicionesGrupo[$claveGrupo];
					$totalRanking = isset($totalesGrupo[$claveGrupo]) ? $totalesGrupo[$claveGrupo] : 0;
				}

				$resultado[] = array(
					"modelo" => $fila["modelo"],
					"nombre" => $fila["nombre"],
					"marca" => $fila["marca"],
					"grupo_id" => $fila["grupo_id"] === null ? null : (int) $fila["grupo_id"],
					"grupo" => $fila["grupo_nombre"],
					"ranking" => $ranking,
					"ranking_total" => $totalRanking,
					"ranking_utilidad" => null,
					"ranking_utilidad_total" => 0,
					"ventas_acumuladas" => $fila["ventas_acumuladas"],
					"unidades_vendidas" => $fila["unidades_vendidas"],
					"utilidad" => $fila["utilidad"],
					"margen_pct" => $fila["margen_pct"],
					"stock_disponible" => $fila["stock_disponible"],
					"rotacion" => $rotacion === null ? null : number_format($rotacion, 4, ".", ""),
					"dias_inventario" => $diasInventario === null ? null : number_format($diasInventario, 2, ".", ""),
					"variacion_interanual_pct" => $variacionInteranual === null
						? null
						: number_format($variacionInteranual, 2, ".", ""),
					"costo_anio" => $fila["costo_anio"] === null ? null : (int) $fila["costo_anio"],
					"costo_mes" => $fila["costo_mes"] === null ? null : (int) $fila["costo_mes"]
				);
			}

			$indicesUtilidadPorGrupo = array();
			foreach ($resultado as $indice => $filaResultado) {
				if ($filaResultado["grupo_id"] === null || $filaResultado["utilidad"] === null) {
					continue;
				}
				$claveGrupo = (string) $filaResultado["grupo_id"];
				if (!isset($indicesUtilidadPorGrupo[$claveGrupo])) {
					$indicesUtilidadPorGrupo[$claveGrupo] = array();
				}
				$indicesUtilidadPorGrupo[$claveGrupo][] = $indice;
			}
			foreach ($indicesUtilidadPorGrupo as $indicesGrupo) {
				usort($indicesGrupo, function ($indiceA, $indiceB) use (&$resultado) {
					$diferencia = (float) $resultado[$indiceB]["utilidad"] - (float) $resultado[$indiceA]["utilidad"];
					if ($diferencia == 0.0) {
						return strcmp($resultado[$indiceA]["modelo"], $resultado[$indiceB]["modelo"]);
					}
					return $diferencia > 0 ? 1 : -1;
				});
				$totalUtilidadGrupo = count($indicesGrupo);
				foreach ($indicesGrupo as $posicion => $indiceResultado) {
					$resultado[$indiceResultado]["ranking_utilidad"] = $posicion + 1;
					$resultado[$indiceResultado]["ranking_utilidad_total"] = $totalUtilidadGrupo;
				}
			}

			return array(
				"ok" => true,
				"data" => $resultado,
				"periodo" => array("anio" => $ctx["anio"], "mes" => $ctx["mes"]),
				"anio_anterior" => $ctx["anio"] - 1,
				"meta" => self::ctrMeta(
					"movimientos, catálogo, precio9, inventario y costos aprobados",
					"comparación de modelos activos; variación interanual acumulada hasta el mes seleccionado"
				)
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo construir el resumen comparativo");
		}
	}

	static public function ctrVariantes($post)
	{
		try {
			$ctx = self::ctrContexto($post);
			if (!$ctx["ok"]) {
				return $ctx;
			}
			$filas = ModeloFichaGerencialModelos::mdlVariantes($ctx["modelo"], $ctx["anio"], $ctx["mes"]);
			$colores = array();
			$tallas = array();
			foreach ($filas as $fila) {
				$colores[$fila["cod_color"]] = $fila["color"];
				$tallas[$fila["cod_talla"]] = $fila["talla"];
			}
			return array(
				"ok" => true,
				"data" => $filas,
				"colores" => $colores,
				"tallas" => $tallas,
				"meta" => self::ctrMeta("articulojf + movimientosjf_" . $ctx["anio"], "Agregado por código de color y talla")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron cargar las variantes");
		}
	}

	static public function ctrRankings($post)
	{
		$respuesta = self::ctrVariantes($post);
		if (!$respuesta["ok"]) {
			return $respuesta;
		}
		$colores = array();
		$tallas = array();
		$combinaciones = array();
		foreach ($respuesta["data"] as $fila) {
			$codColor = $fila["cod_color"];
			$codTalla = $fila["cod_talla"];
			if (!isset($colores[$codColor])) {
				$colores[$codColor] = array("codigo" => $codColor, "nombre" => $fila["color"], "venta_neta" => 0.0, "unidades" => 0.0, "stock" => 0.0);
			}
			if (!isset($tallas[$codTalla])) {
				$tallas[$codTalla] = array("codigo" => $codTalla, "nombre" => $fila["talla"], "venta_neta" => 0.0, "unidades" => 0.0, "stock" => 0.0);
			}
			$colores[$codColor]["venta_neta"] += (float) $fila["venta_neta"];
			$colores[$codColor]["unidades"] += (float) $fila["unidades_vendidas"];
			$colores[$codColor]["stock"] += (float) $fila["stock_disponible"];
			$tallas[$codTalla]["venta_neta"] += (float) $fila["venta_neta"];
			$tallas[$codTalla]["unidades"] += (float) $fila["unidades_vendidas"];
			$tallas[$codTalla]["stock"] += (float) $fila["stock_disponible"];
			$combinaciones[] = array(
				"color" => $fila["color"],
				"talla" => $fila["talla"],
				"venta_neta" => (float) $fila["venta_neta"],
				"unidades" => (float) $fila["unidades_vendidas"],
				"stock" => (float) $fila["stock_disponible"]
			);
		}
		$ordenar = function ($a, $b) {
			if ($a["venta_neta"] == $b["venta_neta"]) {
				return 0;
			}
			return $a["venta_neta"] < $b["venta_neta"] ? 1 : -1;
		};
		$colores = array_values($colores);
		$tallas = array_values($tallas);
		usort($colores, $ordenar);
		usort($tallas, $ordenar);
		usort($combinaciones, $ordenar);
		return array(
			"ok" => true,
			"colores" => array_slice($colores, 0, 10),
			"tallas" => array_slice($tallas, 0, 10),
			"combinaciones" => array_slice($combinaciones, 0, 10),
			"meta" => $respuesta["meta"]
		);
	}

	static public function ctrEvolucion($post)
	{
		try {
			$ctx = self::ctrContexto($post);
			if (!$ctx["ok"]) {
				return $ctx;
			}
			$construirSerie = function ($anio) use ($ctx) {
				$mapa = array();
				foreach (ModeloFichaGerencialModelos::mdlEvolucion($ctx["modelo"], $anio) as $fila) {
					$mapa[(int) $fila["mes"]] = $fila;
				}
				$serie = array();
				for ($mes = 1; $mes <= 12; $mes++) {
					$serie[] = array(
						"mes" => $mes,
						"venta_neta" => isset($mapa[$mes]) ? (float) $mapa[$mes]["venta_neta"] : 0,
						"unidades_vendidas" => isset($mapa[$mes]) ? (float) $mapa[$mes]["unidades_vendidas"] : 0
					);
				}
				return $serie;
			};
			$anioAnterior = $ctx["anio"] - 1;
			return array(
				"ok" => true,
				"anio" => $ctx["anio"],
				"anio_anterior" => $anioAnterior,
				"data" => $construirSerie($ctx["anio"]),
				"data_anterior" => $construirSerie($anioAnterior),
				"meta" => self::ctrMeta(
					"movimientosjf_" . $ctx["anio"] . " + movimientosjf_" . $anioAnterior,
					"Suma mensual de ventas y unidades netas comparada con el año anterior"
				)
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar la evolución");
		}
	}

	static public function ctrDetalle($post)
	{
		try {
			$ctx = self::ctrContexto($post);
			if (!$ctx["ok"]) {
				return $ctx;
			}
			return array(
				"ok" => true,
				"data" => ModeloFichaGerencialModelos::mdlDetalleArticulos($ctx["modelo"], $ctx["anio"], $ctx["mes"]),
				"meta" => self::ctrMeta("articulojf + movimientosjf_" . $ctx["anio"], "Una fila por SKU activo o con venta en el período")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar el detalle");
		}
	}

	static public function ctrConciliacion($post)
	{
		if (!self::ctrPuedeConciliar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para conciliar");
		}
		try {
			$ctx = self::ctrContexto($post);
			if (!$ctx["ok"]) {
				return $ctx;
			}
			return array(
				"ok" => true,
				"data" => ModeloFichaGerencialModelos::mdlConciliacion($ctx["modelo"], $ctx["anio"], $ctx["mes"]),
				"auditoria" => ModeloFichaGerencialModelos::mdlAuditoriaCatalogo($ctx["modelo"]),
				"meta" => self::ctrMeta("movimientosjf + ventajf + articulojf", "Motor de líneas vs líneas con cabecera no anulada")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo ejecutar la conciliación");
		}
	}
}
