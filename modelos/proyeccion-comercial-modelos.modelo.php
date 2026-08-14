<?php

require_once "conexion.php";
require_once "ficha-gerencial-modelos.modelo.php";

/**
 * Proyección comercial por modelo–mes (Fase 0: lectura + fundaciones).
 * No toca articulojf.proyeccion.
 */
class ModeloProyeccionComercialModelos
{
	const FORMULA_VERSION = "v2-estacional-3a";
	const PESO_3 = 0.50;
	const PESO_6 = 0.30;
	const PESO_12 = 0.20;
	const MAX_MESES_PLAN = 12;
	const MAX_MESES_HISTORIAL = 36;
	const ANIO_MIN = 2021;
	/** Años hacia atrás del mismo mes para historial estacional (default 3). */
	const ANIOS_ESTACIONALES = 3;
	/** Desviación relevante: mayor a este % de |sugerencia+ajustes| o a UMBRAL_ABS unidades. */
	const UMBRAL_DESVIACION_PCT = 10.0;
	const UMBRAL_DESVIACION_ABS = 5;

	/**
	 * Motivos predefinidos cuando el oficial se desvía de sug.+factores sin factor.
	 * Se guardan en observacion de la línea.
	 */
	static public function mdlMotivosDesviacion()
	{
		return array(
			"Campaña / promoción comercial",
			"Estacionalidad distinta al histórico",
			"Lanzamiento o retiro del modelo",
			"Cambio de precio o lista",
			"Demanda de clientes clave",
			"Ajuste por meta comercial",
			"Abastecimiento / disponibilidad esperada",
			"Corrección por dato histórico atípico"
		);
	}

	/**
	 * Rango de plan: permite meses futuros (hasta 18 meses adelante).
	 * No exige que existan tablas de movimientos de años futuros.
	 */
	static public function mdlConstruirPeriodoPlan($desdeYm, $hastaYm, $maxMeses = self::MAX_MESES_PLAN)
	{
		if (!preg_match('/^(\d{4})-(\d{2})$/', (string) $desdeYm, $desdeMatch)
			|| !preg_match('/^(\d{4})-(\d{2})$/', (string) $hastaYm, $hastaMatch)
		) {
			return null;
		}
		$desdeAnio = (int) $desdeMatch[1];
		$desdeMes = (int) $desdeMatch[2];
		$hastaAnio = (int) $hastaMatch[1];
		$hastaMes = (int) $hastaMatch[2];
		if ($desdeMes < 1 || $desdeMes > 12 || $hastaMes < 1 || $hastaMes > 12) {
			return null;
		}
		if ($desdeAnio < self::ANIO_MIN || $hastaAnio < self::ANIO_MIN) {
			return null;
		}
		$desdeClave = $desdeAnio * 100 + $desdeMes;
		$hastaClave = $hastaAnio * 100 + $hastaMes;
		if ($desdeClave > $hastaClave) {
			return null;
		}
		$meses = (($hastaAnio - $desdeAnio) * 12) + ($hastaMes - $desdeMes) + 1;
		if ($meses < 1 || $meses > (int) $maxMeses) {
			return null;
		}

		$hoyAnio = (int) date("Y");
		$hoyMes = (int) date("n");
		$limite = self::mdlSumarMeses($hoyAnio, $hoyMes, 18);
		$limiteClave = $limite["anio"] * 100 + $limite["mes"];
		if ($hastaClave > $limiteClave) {
			return null;
		}

		$inicio = sprintf("%04d-%02d-01", $desdeAnio, $desdeMes);
		$fin = $hastaMes === 12
			? sprintf("%04d-01-01", $hastaAnio + 1)
			: sprintf("%04d-%02d-01", $hastaAnio, $hastaMes + 1);

		return array(
			"desde" => sprintf("%04d-%02d", $desdeAnio, $desdeMes),
			"hasta" => sprintf("%04d-%02d", $hastaAnio, $hastaMes),
			"inicio" => $inicio,
			"fin" => $fin,
			"meses" => $meses,
			"desde_anio" => $desdeAnio,
			"desde_mes" => $desdeMes,
			"hasta_anio" => $hastaAnio,
			"hasta_mes" => $hastaMes,
			"meses_lista" => self::mdlListarMeses($desdeAnio, $desdeMes, $meses)
		);
	}

	/**
	 * Historial hacia atrás (solo meses con tablas de movimientos existentes).
	 * Tolera años sin tabla: los omite del UNION y marca aviso.
	 */
	static public function mdlConstruirPeriodoHistorial($hastaYm, $mesesAtras = 12)
	{
		if (!preg_match('/^(\d{4})-(\d{2})$/', (string) $hastaYm, $match)) {
			return null;
		}
		$hastaAnio = (int) $match[1];
		$hastaMes = (int) $match[2];
		$mesesAtras = max(1, min(self::MAX_MESES_HISTORIAL, (int) $mesesAtras));
		if ($hastaMes < 1 || $hastaMes > 12 || $hastaAnio < self::ANIO_MIN) {
			return null;
		}

		$inicioCursor = self::mdlSumarMeses($hastaAnio, $hastaMes, -($mesesAtras - 1));
		$desdeAnio = $inicioCursor["anio"];
		$desdeMes = $inicioCursor["mes"];
		if ($desdeAnio < self::ANIO_MIN) {
			$desdeAnio = self::ANIO_MIN;
			$desdeMes = 1;
			$mesesAtras = (($hastaAnio - $desdeAnio) * 12) + ($hastaMes - $desdeMes) + 1;
		}

		$aniosPedidos = array();
		for ($a = $desdeAnio; $a <= $hastaAnio; $a++) {
			$aniosPedidos[] = $a;
		}
		$aniosOk = array();
		$aniosAusentes = array();
		foreach ($aniosPedidos as $anio) {
			if (ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($anio) === null) {
				$aniosAusentes[] = $anio;
			} else {
				$aniosOk[] = $anio;
			}
		}

		$inicio = sprintf("%04d-%02d-01", $desdeAnio, $desdeMes);
		$fin = $hastaMes === 12
			? sprintf("%04d-01-01", $hastaAnio + 1)
			: sprintf("%04d-%02d-01", $hastaAnio, $hastaMes + 1);

		return array(
			"desde" => sprintf("%04d-%02d", $desdeAnio, $desdeMes),
			"hasta" => sprintf("%04d-%02d", $hastaAnio, $hastaMes),
			"inicio" => $inicio,
			"fin" => $fin,
			"meses" => $mesesAtras,
			"anios" => $aniosOk,
			"anios_ausentes" => $aniosAusentes,
			"desde_anio" => $desdeAnio,
			"desde_mes" => $desdeMes,
			"hasta_anio" => $hastaAnio,
			"hasta_mes" => $hastaMes,
			"meses_lista" => self::mdlListarMeses($desdeAnio, $desdeMes, $mesesAtras)
		);
	}

	static public function mdlSumarMeses($anio, $mes, $delta)
	{
		$idx = ((int) $anio) * 12 + ((int) $mes - 1) + (int) $delta;
		return array(
			"anio" => (int) floor($idx / 12),
			"mes" => ($idx % 12) + 1
		);
	}

	static public function mdlListarMeses($anioDesde, $mesDesde, $cantidad)
	{
		$lista = array();
		$anio = (int) $anioDesde;
		$mes = (int) $mesDesde;
		for ($i = 0; $i < (int) $cantidad; $i++) {
			$lista[] = array(
				"anio" => $anio,
				"mes" => $mes,
				"periodo" => sprintf("%04d-%02d", $anio, $mes)
			);
			$mes++;
			if ($mes > 12) {
				$mes = 1;
				$anio++;
			}
		}
		return $lista;
	}

	static public function mdlCatalogo($idMarca = 0, $q = "")
	{
		$q = trim((string) $q);
		// Si el código es exacto, no expandir con LIKE (más rápido y preciso al generar un modelo).
		if ($q !== "" && preg_match('/^[A-Za-z0-9._-]+$/', $q) && strlen($q) <= 50) {
			$exacto = ModeloFichaGerencialModelos::mdlCabeceraModelo($q);
			if ($exacto) {
				if ((int) $idMarca > 0 && (int) $exacto["id_marca"] !== (int) $idMarca) {
					return array(
						"modelos" => array(),
						"marcas" => ModeloFichaGerencialModelos::mdlMarcasConModelosActivos(),
						"categorias" => ModeloFichaGerencialModelos::mdlCatalogoCategoriasSubcategorias()
					);
				}
				return array(
					"modelos" => array(array(
						"modelo" => $exacto["modelo"],
						"nombre" => $exacto["nombre"],
						"id_marca" => (int) $exacto["id_marca"],
						"marca" => $exacto["marca"]
					)),
					"marcas" => ModeloFichaGerencialModelos::mdlMarcasConModelosActivos(),
					"categorias" => ModeloFichaGerencialModelos::mdlCatalogoCategoriasSubcategorias()
				);
			}
		}
		return array(
			"modelos" => ModeloFichaGerencialModelos::mdlCatalogoModelosActivos($idMarca, $q),
			"marcas" => ModeloFichaGerencialModelos::mdlMarcasConModelosActivos(),
			"categorias" => ModeloFichaGerencialModelos::mdlCatalogoCategoriasSubcategorias()
		);
	}

	/**
	 * Precio lista 9 vigente por modelo (último registro en preciojf).
	 */
	static public function mdlPreciosLista9($modelos = array())
	{
		$modelos = array_values(array_unique(array_filter(array_map("trim", (array) $modelos))));
		$sql = "SELECT TRIM(p.modelo) AS modelo, p.precio9
			FROM preciojf p
			INNER JOIN (
				SELECT TRIM(modelo) AS modelo, MAX(id) AS max_id
				FROM preciojf
				WHERE TRIM(IFNULL(modelo, '')) <> ''";
		$marcadores = array();
		if (!empty($modelos)) {
			foreach ($modelos as $i => $modelo) {
				$marcadores[] = ":modelo_" . $i;
			}
			$sql .= " AND TRIM(modelo) IN (" . implode(", ", $marcadores) . ")";
		}
		$sql .= " GROUP BY TRIM(modelo)
			) u ON TRIM(p.modelo) = u.modelo AND p.id = u.max_id";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($modelos as $i => $modelo) {
			$stmt->bindValue(":modelo_" . $i, $modelo, PDO::PARAM_STR);
		}
		$stmt->execute();
		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$precio = $fila["precio9"];
			$mapa[trim($fila["modelo"])] = ($precio === null || $precio === "")
				? null
				: (float) $precio;
		}
		return $mapa;
	}

	static public function mdlInventarioPorModelos($modelos = array())
	{
		$modelos = array_values(array_unique(array_filter(array_map("trim", (array) $modelos))));
		$sql = "SELECT TRIM(modelo) AS modelo,
				COUNT(*) AS variantes,
				COALESCE(SUM(IFNULL(stock, 0)), 0) AS stock_fisico,
				COALESCE(SUM(IFNULL(pedidos, 0)), 0) AS pedidos,
				COALESCE(SUM(IFNULL(stock, 0) - IFNULL(pedidos, 0)), 0) AS stock_disponible,
				COALESCE(SUM(IFNULL(taller, 0)), 0) AS taller,
				COALESCE(SUM(IFNULL(servicio, 0)), 0) AS servicio,
				COALESCE(SUM(IFNULL(alm_corte, 0)), 0) AS alm_corte,
				COALESCE(SUM(IFNULL(ord_corte, 0)), 0) AS ord_corte,
				COALESCE(SUM(
					IFNULL(taller, 0) + IFNULL(servicio, 0) + IFNULL(alm_corte, 0) + IFNULL(ord_corte, 0)
				), 0) AS en_proceso
			FROM articulojf
			WHERE UPPER(TRIM(IFNULL(estado, ''))) = 'ACTIVO'
			  AND TRIM(IFNULL(modelo, '')) <> ''";
		$marcadores = array();
		if (!empty($modelos)) {
			foreach ($modelos as $i => $modelo) {
				$marcadores[] = ":modelo_" . $i;
			}
			$sql .= " AND TRIM(modelo) IN (" . implode(", ", $marcadores) . ")";
		}
		$sql .= " GROUP BY TRIM(modelo)";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($modelos as $i => $modelo) {
			$stmt->bindValue(":modelo_" . $i, $modelo, PDO::PARAM_STR);
		}
		$stmt->execute();
		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[trim($fila["modelo"])] = array(
				"variantes" => (int) $fila["variantes"],
				"stock_fisico" => (float) $fila["stock_fisico"],
				"pedidos" => (float) $fila["pedidos"],
				"stock_disponible" => (float) $fila["stock_disponible"],
				"taller" => (float) $fila["taller"],
				"servicio" => (float) $fila["servicio"],
				"alm_corte" => (float) $fila["alm_corte"],
				"ord_corte" => (float) $fila["ord_corte"],
				"en_proceso" => (float) $fila["en_proceso"]
			);
		}
		return $mapa;
	}

	/**
	 * Artículos de uno o más modelos (para consultas indexadas por articulo).
	 */
	static public function mdlArticulosDeModelos($modelos)
	{
		$modelos = array_values(array_unique(array_filter(array_map("trim", (array) $modelos))));
		if (empty($modelos)) {
			return array("por_modelo" => array(), "articulos" => array());
		}
		$marcadores = array();
		foreach ($modelos as $i => $modelo) {
			$marcadores[] = ":modelo_" . $i;
		}
		$sql = "SELECT articulo, TRIM(modelo) AS modelo
			FROM articulojf
			WHERE TRIM(modelo) IN (" . implode(", ", $marcadores) . ")";
		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($modelos as $i => $modelo) {
			$stmt->bindValue(":modelo_" . $i, $modelo, PDO::PARAM_STR);
		}
		$stmt->execute();
		$porModelo = array();
		$articulos = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$modelo = trim($fila["modelo"]);
			$art = $fila["articulo"];
			if (!isset($porModelo[$modelo])) {
				$porModelo[$modelo] = array();
			}
			$porModelo[$modelo][] = $art;
			$articulos[] = $art;
		}
		return array(
			"por_modelo" => $porModelo,
			"articulos" => array_values(array_unique($articulos))
		);
	}

	/**
	 * Ventas netas mensuales por lote (misma regla que ficha gerencial).
	 * Consulta año por año filtrando por articulo (usa índice), no UNION completo.
	 */
	static public function mdlVentasMensualesLote($modelos, $periodoHistorial)
	{
		$modelos = array_values(array_unique(array_filter(array_map("trim", (array) $modelos))));
		if (empty($modelos) || !is_array($periodoHistorial) || empty($periodoHistorial["anios"])) {
			return array();
		}

		$resArt = self::mdlArticulosDeModelos($modelos);
		$articulos = $resArt["articulos"];
		$porModelo = $resArt["por_modelo"];
		if (empty($articulos)) {
			return array();
		}

		$mapaArticuloModelo = array();
		foreach ($porModelo as $modelo => $arts) {
			foreach ($arts as $art) {
				$mapaArticuloModelo[$art] = $modelo;
			}
		}

		$mapa = array();
		$pdo = Conexion::conectar();
		$inicio = $periodoHistorial["inicio"];
		$fin = $periodoHistorial["fin"];

		foreach ($periodoHistorial["anios"] as $anio) {
			$tabla = ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($anio);
			if ($tabla === null) {
				continue;
			}

			// Chunks por si un modelo tiene muchos SKU
			$chunks = array_chunk($articulos, 400);
			foreach ($chunks as $chunkIdx => $chunk) {
				$marcadores = array();
				foreach ($chunk as $i => $art) {
					$marcadores[] = ":art_{$chunkIdx}_{$i}";
				}
				$sql = "SELECT m.articulo,
						YEAR(m.fecha) AS anio,
						MONTH(m.fecha) AS mes,
						COALESCE(SUM(IFNULL(m.total, 0)), 0) AS venta_neta,
						COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades_vendidas
					FROM {$tabla} m
					WHERE m.articulo IN (" . implode(", ", $marcadores) . ")
					  AND m.fecha >= :inicio AND m.fecha < :fin
					  AND " . ModeloFichaGerencialModelos::sqlTiposVenta("m") . "
					  AND " . ModeloFichaGerencialModelos::sqlCabeceraValida("m") . "
					GROUP BY m.articulo, YEAR(m.fecha), MONTH(m.fecha)";
				$stmt = $pdo->prepare($sql);
				foreach ($chunk as $i => $art) {
					$stmt->bindValue(":art_{$chunkIdx}_{$i}", $art, PDO::PARAM_STR);
				}
				$stmt->bindValue(":inicio", $inicio, PDO::PARAM_STR);
				$stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
				$stmt->execute();
				foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
					$art = $fila["articulo"];
					if (!isset($mapaArticuloModelo[$art])) {
						continue;
					}
					$modelo = $mapaArticuloModelo[$art];
					$clave = sprintf("%04d-%02d", (int) $fila["anio"], (int) $fila["mes"]);
					if (!isset($mapa[$modelo])) {
						$mapa[$modelo] = array();
					}
					if (!isset($mapa[$modelo][$clave])) {
						$mapa[$modelo][$clave] = array(
							"anio" => (int) $fila["anio"],
							"mes" => (int) $fila["mes"],
							"periodo" => $clave,
							"venta_neta" => 0.0,
							"unidades_vendidas" => 0.0
						);
					}
					$mapa[$modelo][$clave]["venta_neta"] += (float) $fila["venta_neta"];
					$mapa[$modelo][$clave]["unidades_vendidas"] += (float) $fila["unidades_vendidas"];
				}
			}
		}
		return $mapa;
	}

	/**
	 * Completa serie mensual con ceros para meses sin venta.
	 */
	static public function mdlSerieCompleta($mapaMeses, $periodoHistorial)
	{
		$serie = array();
		foreach ($periodoHistorial["meses_lista"] as $item) {
			$clave = $item["periodo"];
			if (isset($mapaMeses[$clave])) {
				$serie[] = $mapaMeses[$clave];
			} else {
				$serie[] = array(
					"anio" => $item["anio"],
					"mes" => $item["mes"],
					"periodo" => $clave,
					"venta_neta" => 0.0,
					"unidades_vendidas" => 0.0
				);
			}
		}
		return $serie;
	}

	static public function mdlTendenciaReciente($modelo)
	{
		$vacio = array(
			"hasta" => null,
			"anio" => null,
			"anio_ant" => null,
			"rango" => "",
			"n_meses" => 0,
			"uds" => null,
			"uds_anio_ant" => null,
			"pct" => null,
			"rango_3" => "",
			"uds_3" => null,
			"uds_3_anio_ant" => null,
			"pct_3" => null
		);
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return $vacio;
		}
		$ultimo = self::mdlSumarMeses((int) date("Y"), (int) date("n"), -1);
		$anio = (int) $ultimo["anio"];
		$mesHasta = (int) $ultimo["mes"];
		if ($anio < self::ANIO_MIN || $mesHasta < 1 || $mesHasta > 12) {
			return $vacio;
		}

		$anioAnt = $anio - 1;
		$anios = array();
		foreach (array($anioAnt, $anio) as $a) {
			if ($a >= self::ANIO_MIN && ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($a) !== null) {
				$anios[] = $a;
			}
		}
		if (empty($anios)) {
			return $vacio;
		}

		$inicio = sprintf("%04d-01-01", $anioAnt);
		$fin = $mesHasta === 12
			? sprintf("%04d-01-01", $anio + 1)
			: sprintf("%04d-%02d-01", $anio, $mesHasta + 1);
		$ventas = self::mdlVentasMensualesLote(array($modelo), array(
			"inicio" => $inicio,
			"fin" => $fin,
			"anios" => $anios
		));
		$mapa = isset($ventas[$modelo]) ? $ventas[$modelo] : array();

		$sumar = function ($anioS, $mesDesde, $mesHastaS) use ($mapa) {
			$t = 0.0;
			for ($m = (int) $mesDesde; $m <= (int) $mesHastaS; $m++) {
				$clave = sprintf("%04d-%02d", (int) $anioS, $m);
				if (isset($mapa[$clave])) {
					$t += (float) $mapa[$clave]["unidades_vendidas"];
				}
			}
			return $t;
		};

		$uds = $sumar($anio, 1, $mesHasta);
		$udsAnt = $sumar($anioAnt, 1, $mesHasta);
		$pct = ($udsAnt != 0.0) ? (($uds - $udsAnt) / abs($udsAnt)) * 100.0 : null;

		$nombres = array(1 => "ene", 2 => "feb", 3 => "mar", 4 => "abr", 5 => "may", 6 => "jun",
			7 => "jul", 8 => "ago", 9 => "sep", 10 => "oct", 11 => "nov", 12 => "dic");
		$rango = $nombres[1] . "–" . $nombres[$mesHasta] . " " . $anio;

		$uds3 = null;
		$uds3Ant = null;
		$pct3 = null;
		$rango3 = "";
		if ($mesHasta >= 3) {
			$mesDesde3 = $mesHasta - 2;
			$uds3 = $sumar($anio, $mesDesde3, $mesHasta);
			$uds3Ant = $sumar($anioAnt, $mesDesde3, $mesHasta);
			$pct3 = ($uds3Ant != 0.0) ? (($uds3 - $uds3Ant) / abs($uds3Ant)) * 100.0 : null;
			$rango3 = $nombres[$mesDesde3] . "–" . $nombres[$mesHasta];
		}

		return array(
			"hasta" => sprintf("%04d-%02d", $anio, $mesHasta),
			"anio" => $anio,
			"anio_ant" => $anioAnt,
			"rango" => $rango,
			"n_meses" => $mesHasta,
			"uds" => (int) round($uds),
			"uds_anio_ant" => (int) round($udsAnt),
			"pct" => ($pct === null) ? null : round($pct, 1),
			"rango_3" => $rango3,
			"uds_3" => ($uds3 === null) ? null : (int) round($uds3),
			"uds_3_anio_ant" => ($uds3Ant === null) ? null : (int) round($uds3Ant),
			"pct_3" => ($pct3 === null) ? null : round($pct3, 1)
		);
	}

	static public function mdlPromedioMovil($serieUnidades, $n)
	{
		$n = (int) $n;
		if ($n < 1 || count($serieUnidades) < 1) {
			return null;
		}
		$slice = array_slice($serieUnidades, -$n);
		if (count($slice) < 1) {
			return null;
		}
		return array_sum($slice) / count($slice);
	}

	/**
	 * Sugerencia explicable v1: promedio ponderado 3/6/12 + ajuste estacional simple.
	 * $unidadesHastaAntes: unidades mensuales ordenadas cronológicamente, exclusivas del mes objetivo.
	 */
	static public function mdlCalcularSugerencia($unidadesHastaAntes, $mesObjetivo, $historiaMismoMes = array())
	{
		$unidadesHastaAntes = array_values(array_map("floatval", (array) $unidadesHastaAntes));
		$disponibles = count($unidadesHastaAntes);
		$prom3 = self::mdlPromedioMovil($unidadesHastaAntes, min(3, $disponibles));
		$prom6 = self::mdlPromedioMovil($unidadesHastaAntes, min(6, $disponibles));
		$prom12 = self::mdlPromedioMovil($unidadesHastaAntes, min(12, $disponibles));

		$pesos = array();
		if ($prom3 !== null && $disponibles >= 1) {
			$pesos["p3"] = self::PESO_3;
		}
		if ($prom6 !== null && $disponibles >= 3) {
			$pesos["p6"] = self::PESO_6;
		}
		if ($prom12 !== null && $disponibles >= 6) {
			$pesos["p12"] = self::PESO_12;
		}
		if (empty($pesos)) {
			return array(
				"unidades" => 0,
				"formula_version" => self::FORMULA_VERSION,
				"sin_historia" => true,
				"pesos_efectivos" => array(),
				"promedio_3" => null,
				"promedio_6" => null,
				"promedio_12" => null,
				"base_ponderada" => 0,
				"factor_estacional" => 1,
				"explicacion" => "Sin historia de ventas netas; sugerencia 0. Requiere estimación manual."
			);
		}
		$sumaPesos = array_sum($pesos);
		foreach ($pesos as $k => $v) {
			$pesos[$k] = $v / $sumaPesos;
		}
		$base = 0.0;
		if (isset($pesos["p3"])) {
			$base += $pesos["p3"] * $prom3;
		}
		if (isset($pesos["p6"])) {
			$base += $pesos["p6"] * $prom6;
		}
		if (isset($pesos["p12"])) {
			$base += $pesos["p12"] * $prom12;
		}

		$factorEstacional = 1.0;
		$historiaMismoMes = array_values(array_map("floatval", (array) $historiaMismoMes));
		if (count($historiaMismoMes) >= 2 && $prom12 !== null && $prom12 > 0) {
			$promMismoMes = array_sum($historiaMismoMes) / count($historiaMismoMes);
			$factorEstacional = $promMismoMes / $prom12;
			if ($factorEstacional < 0.5) {
				$factorEstacional = 0.5;
			}
			if ($factorEstacional > 2.0) {
				$factorEstacional = 2.0;
			}
		}

		$sugerido = (int) max(0, round($base * $factorEstacional));
		$explicacion = sprintf(
			"Promedio ponderado (pesos efectivos p3=%.0f%% p6=%.0f%% p12=%.0f%%) × factor estacional mes %d (%.2f). Meses con historia: %d.",
			isset($pesos["p3"]) ? $pesos["p3"] * 100 : 0,
			isset($pesos["p6"]) ? $pesos["p6"] * 100 : 0,
			isset($pesos["p12"]) ? $pesos["p12"] * 100 : 0,
			(int) $mesObjetivo,
			$factorEstacional,
			$disponibles
		);

		return array(
			"unidades" => $sugerido,
			"formula_version" => self::FORMULA_VERSION,
			"sin_historia" => false,
			"pesos_efectivos" => $pesos,
			"promedio_3" => $prom3 === null ? null : round($prom3, 2),
			"promedio_6" => $prom6 === null ? null : round($prom6, 2),
			"promedio_12" => $prom12 === null ? null : round($prom12, 2),
			"base_ponderada" => round($base, 2),
			"factor_estacional" => round($factorEstacional, 4),
			"explicacion" => $explicacion
		);
	}

	static public function mdlInventarioVacio()
	{
		return array(
			"variantes" => 0,
			"stock_fisico" => 0,
			"pedidos" => 0,
			"stock_disponible" => 0,
			"taller" => 0,
			"servicio" => 0,
			"alm_corte" => 0,
			"ord_corte" => 0,
			"en_proceso" => 0
		);
	}

	/**
	 * Matriz de contexto para un rango de plan + historial de apoyo.
	 * $maxModelos limita el trabajo (evita timeouts con "Todas").
	 */
	static public function mdlMatrizContexto($periodoPlan, $idMarca = 0, $q = "", $mesesHistorial = 12, $maxModelos = 40)
	{
		$catalogo = self::mdlCatalogo($idMarca, $q);
		$modelosMeta = $catalogo["modelos"];
		$totalSinLimite = count($modelosMeta);
		$truncado = false;
		if ($totalSinLimite > (int) $maxModelos) {
			$modelosMeta = array_slice($modelosMeta, 0, (int) $maxModelos);
			$truncado = true;
		}
		$codigos = array();
		foreach ($modelosMeta as $m) {
			$codigos[] = trim($m["modelo"]);
		}

		$ultimoMesHist = self::mdlSumarMeses(
			(int) date("Y"),
			(int) date("n"),
			-1
		);
		$mesesHistorial = max(36, (int) $mesesHistorial);
		$hastaHist = sprintf("%04d-%02d", $ultimoMesHist["anio"], $ultimoMesHist["mes"]);
		$periodoHist = self::mdlConstruirPeriodoHistorial($hastaHist, $mesesHistorial);

		$ventas = empty($codigos) || $periodoHist === null
			? array()
			: self::mdlVentasMensualesLote($codigos, $periodoHist);
		$inventarios = empty($codigos) ? array() : self::mdlInventarioPorModelos($codigos);
		$precios = empty($codigos) ? array() : self::mdlPreciosLista9($codigos);

		$filas = array();
		foreach ($modelosMeta as $meta) {
			$modelo = trim($meta["modelo"]);
			$mapaMeses = isset($ventas[$modelo]) ? $ventas[$modelo] : array();
			$serie = ($periodoHist === null)
				? array()
				: self::mdlSerieCompleta($mapaMeses, $periodoHist);
			$unidadesSerie = array();
			foreach ($serie as $punto) {
				$unidadesSerie[] = (float) $punto["unidades_vendidas"];
			}

			$inv = isset($inventarios[$modelo]) ? $inventarios[$modelo] : self::mdlInventarioVacio();
			$precio9 = array_key_exists($modelo, $precios) ? $precios[$modelo] : null;

			foreach ($periodoPlan["meses_lista"] as $mesPlan) {
				$anioObj = (int) $mesPlan["anio"];
				$mesObj = (int) $mesPlan["mes"];
				$periodoObj = $mesPlan["periodo"];

				$mismoMes = array();
				for ($off = 1; $off <= self::ANIOS_ESTACIONALES; $off++) {
					$anioH = $anioObj - $off;
					$claveH = sprintf("%04d-%02d", $anioH, $mesObj);
					if (!self::mdlMesHistorialCerrado($anioH, $mesObj)) {
						continue;
					}
					if (isset($mapaMeses[$claveH])) {
						$mismoMes[] = array(
							"anio" => $anioH,
							"unidades" => (float) $mapaMeses[$claveH]["unidades_vendidas"]
						);
					}
				}
				$sugerencia = self::mdlCalcularSugerenciaEstacional($mismoMes);
				$mismoMesAnteriorClave = sprintf("%04d-%02d", $anioObj - 1, $mesObj);
				$histMismoMes = isset($mapaMeses[$mismoMesAnteriorClave])
					? $mapaMeses[$mismoMesAnteriorClave]
					: array(
						"periodo" => $mismoMesAnteriorClave,
						"unidades_vendidas" => 0,
						"venta_neta" => 0
					);

				$filas[] = array(
					"periodo" => $periodoObj,
					"anio" => $anioObj,
					"mes" => $mesObj,
					"modelo" => $modelo,
					"nombre" => $meta["nombre"],
					"id_marca" => (int) $meta["id_marca"],
					"marca" => $meta["marca"],
					"hist_mismo_mes" => array(
						"periodo" => $histMismoMes["periodo"],
						"unidades" => (float) $histMismoMes["unidades_vendidas"],
						"venta_neta" => (float) $histMismoMes["venta_neta"]
					),
					"promedio_3" => $sugerencia["promedio_3"],
					"promedio_6" => $sugerencia["promedio_6"],
					"promedio_12" => $sugerencia["promedio_12"],
					"sugerencia" => $sugerencia,
					"precio_lista9" => $precio9,
					"sin_lista9" => ($precio9 === null),
					"inventario" => $inv,
					"brecha_referencial" => (int) $sugerencia["unidades"]
						- (float) $inv["stock_disponible"]
						- (float) $inv["en_proceso"]
				);
			}
		}

		return array(
			"periodo_plan" => $periodoPlan,
			"periodo_historial" => $periodoHist,
			"marcas" => $catalogo["marcas"],
			"categorias" => $catalogo["categorias"],
			"total_modelos" => count($modelosMeta),
			"total_modelos_filtro" => $totalSinLimite,
			"truncado" => $truncado,
			"max_modelos" => (int) $maxModelos,
			"total_filas" => count($filas),
			"filas" => $filas,
			"formula_version" => self::FORMULA_VERSION,
			"meta" => array(
				"tipos_venta" => ModeloFichaGerencialModelos::tiposVenta(),
				"anios_movimientos_ausentes" => $periodoHist
					? $periodoHist["anios_ausentes"]
					: array(),
				"no_usa_articulojf_proyeccion" => true
			)
		);
	}

	/**
	 * Sugerencia v2: promedio ponderado del mismo mes en años previos (hasta 3).
	 * Pesos: año-1 50%, año-2 30%, año-3 20% (renormaliza si faltan años).
	 */
	static public function mdlCalcularSugerenciaEstacional($historiaMismoMes)
	{
		// $historiaMismoMes: [ ['anio'=>2025,'unidades'=>10], ... ] más reciente primero o no
		$porAnio = array();
		foreach ((array) $historiaMismoMes as $h) {
			$anio = isset($h["anio"]) ? (int) $h["anio"] : 0;
			if ($anio > 0) {
				$porAnio[$anio] = (float) $h["unidades"];
			}
		}
		if (empty($porAnio)) {
			return array(
				"unidades" => 0,
				"formula_version" => self::FORMULA_VERSION,
				"sin_historia" => true,
				"anios_usados" => array(),
				"pesos_efectivos" => array(),
				"promedio_simple" => null,
				"explicacion" => "Sin ventas del mismo mes en años previos; sugerencia 0."
			);
		}
		krsort($porAnio);
		$anios = array_keys($porAnio);
		$pesosBase = array(0.50, 0.30, 0.20);
		$pesos = array();
		$base = 0.0;
		$sumaP = 0.0;
		$i = 0;
		foreach ($anios as $anio) {
			if ($i >= self::ANIOS_ESTACIONALES) {
				break;
			}
			$p = $pesosBase[$i];
			$pesos[(string) $anio] = $p;
			$base += $p * $porAnio[$anio];
			$sumaP += $p;
			$i++;
		}
		foreach ($pesos as $k => $p) {
			$pesos[$k] = $p / $sumaP;
		}
		$base = 0.0;
		$i = 0;
		foreach ($anios as $anio) {
			if ($i >= self::ANIOS_ESTACIONALES) {
				break;
			}
			$base += $pesos[(string) $anio] * $porAnio[$anio];
			$i++;
		}
		$vals = array_values($porAnio);
		$vals = array_slice($vals, 0, self::ANIOS_ESTACIONALES);
		$promSimple = array_sum($vals) / count($vals);
		// Redondeo comercial: múltiplo de 10 (mitad hacia arriba).
		$sugerido = (int) max(0, round($base / 10) * 10);
		$partes = array();
		foreach ($pesos as $anio => $p) {
			$partes[] = $anio . "=" . round($p * 100) . "% (" . round($porAnio[(int) $anio]) . " uds)";
		}
		return array(
			"unidades" => $sugerido,
			"formula_version" => self::FORMULA_VERSION,
			"sin_historia" => false,
			"anios_usados" => array_slice($anios, 0, self::ANIOS_ESTACIONALES),
			"pesos_efectivos" => $pesos,
			"promedio_simple" => round($promSimple, 2),
			"explicacion" => "Mismo mes en años previos: " . implode(", ", $partes)
		);
	}

	/**
	 * Un mes histórico solo cuenta para sugerencia si ya cerró (es anterior al mes calendario actual).
	 * El mes en curso y futuros se muestran como referencia parcial, pero no entran al promedio.
	 */
	static public function mdlMesHistorialCerrado($anio, $mes)
	{
		$anio = (int) $anio;
		$mes = (int) $mes;
		if ($anio < self::ANIO_MIN || $mes < 1 || $mes > 12) {
			return false;
		}
		$ahora = ((int) date("Y")) * 100 + (int) date("n");
		$clave = $anio * 100 + $mes;
		return $clave < $ahora;
	}

	/**
	 * Matriz estacional: por cada mes del plan, unidades del mismo mes hace 1..N años.
	 */
	static public function mdlHistorialEstacional($modelo, $periodoPlan, $aniosAtras = self::ANIOS_ESTACIONALES)
	{
		$aniosAtras = max(1, min(3, (int) $aniosAtras));
		$anioMin = null;
		$anioMax = null;
		foreach ($periodoPlan["meses_lista"] as $m) {
			for ($i = 1; $i <= $aniosAtras; $i++) {
				$a = (int) $m["anio"] - $i;
				if ($anioMin === null || $a < $anioMin) {
					$anioMin = $a;
				}
				if ($anioMax === null || $a > $anioMax) {
					$anioMax = $a;
				}
			}
		}
		if ($anioMin < self::ANIO_MIN) {
			$anioMin = self::ANIO_MIN;
		}
		$periodoHist = array(
			"inicio" => sprintf("%04d-01-01", $anioMin),
			"fin" => sprintf("%04d-01-01", $anioMax + 1),
			"anios" => array()
		);
		for ($a = $anioMin; $a <= $anioMax; $a++) {
			if (ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($a) !== null) {
				$periodoHist["anios"][] = $a;
			}
		}
		$ventas = empty($periodoHist["anios"])
			? array()
			: self::mdlVentasMensualesLote(array($modelo), $periodoHist);
		$mapa = isset($ventas[$modelo]) ? $ventas[$modelo] : array();

		$columnasAnio = array();
		for ($i = 1; $i <= $aniosAtras; $i++) {
			$columnasAnio[] = $i;
		}

		$filas = array();
		$sugerencias = array();
		foreach ($periodoPlan["meses_lista"] as $mesPlan) {
			$anioObj = (int) $mesPlan["anio"];
			$mesObj = (int) $mesPlan["mes"];
			$hist = array();
			$histDetalle = array();
			foreach ($columnasAnio as $offset) {
				$anioH = $anioObj - $offset;
				$clave = sprintf("%04d-%02d", $anioH, $mesObj);
				$uds = isset($mapa[$clave]) ? (float) $mapa[$clave]["unidades_vendidas"] : null;
				$venta = isset($mapa[$clave]) ? (float) $mapa[$clave]["venta_neta"] : null;
				$cerrado = self::mdlMesHistorialCerrado($anioH, $mesObj);
				$sinTabla = (ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($anioH) === null);
				$histDetalle[] = array(
					"offset" => $offset,
					"anio" => $anioH,
					"periodo" => $clave,
					"unidades" => $uds,
					"venta_neta" => $venta,
					"sin_tabla" => $sinTabla,
					"periodo_abierto" => !$cerrado,
					"excluye_sugerencia" => (!$cerrado || $sinTabla || $uds === null)
				);
				// Solo meses ya cerrados entran a promedio / sugerencia.
				if ($cerrado && !$sinTabla && $uds !== null) {
					$hist[] = array("anio" => $anioH, "unidades" => $uds);
				}
			}
			$sug = self::mdlCalcularSugerenciaEstacional($hist);
			$sugerencias[$mesPlan["periodo"]] = $sug;
			$filas[] = array(
				"periodo" => $mesPlan["periodo"],
				"anio" => $anioObj,
				"mes" => $mesObj,
				"historial" => $histDetalle,
				"sugerencia" => $sug
			);
		}
		return array(
			"anios_atras" => $aniosAtras,
			"filas" => $filas,
			"sugerencias" => $sugerencias,
			"formula_version" => self::FORMULA_VERSION
		);
	}

	static public function mdlNombreMesCorto($mes)
	{
		$n = array(
			1 => "Ene", 2 => "Feb", 3 => "Mar", 4 => "Abr",
			5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Ago",
			9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dic"
		);
		$mes = (int) $mes;
		return isset($n[$mes]) ? $n[$mes] : (string) $mes;
	}

	static public function mdlClaveVariante($codColor, $codTalla)
	{
		return trim((string) $codColor) . "|" . trim((string) $codTalla);
	}

	static public function mdlRepartirEnteros($total, $pesos)
	{
		$total = (int) round((float) $total);
		$out = array();
		$suma = 0.0;
		foreach ((array) $pesos as $k => $p) {
			$out[$k] = 0;
			$suma += (float) $p;
		}
		if ($total <= 0 || $suma <= 0 || empty($out)) {
			return $out;
		}
		$fracs = array();
		$asignado = 0;
		foreach ($pesos as $k => $p) {
			$v = $total * ((float) $p / $suma);
			$ent = (int) floor($v);
			$out[$k] = $ent;
			$asignado += $ent;
			$fracs[$k] = $v - $ent;
		}
		$resto = $total - $asignado;
		arsort($fracs, SORT_NUMERIC);
		foreach ($fracs as $k => $ignore) {
			if ($resto <= 0) {
				break;
			}
			$out[$k]++;
			$resto--;
		}
		return $out;
	}

	static public function mdlCatalogoVariantes($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT articulo,
				TRIM(IFNULL(cod_color, '')) AS cod_color,
				IFNULL(NULLIF(TRIM(color), ''), TRIM(cod_color)) AS color,
				TRIM(IFNULL(cod_talla, '')) AS cod_talla,
				IFNULL(NULLIF(TRIM(talla), ''), TRIM(cod_talla)) AS talla,
				UPPER(TRIM(IFNULL(estado, ''))) AS estado
			FROM articulojf
			WHERE TRIM(modelo) = :modelo"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		$articulos = array();
		$colores = array();
		$tallas = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$codC = (string) $fila["cod_color"];
			$codT = (string) $fila["cod_talla"];
			$estado = (string) $fila["estado"];
			$activo = ($estado === "ACTIVO" || $estado === "CAMPAÑAD" || $estado === "CAMPANAD");
			$articulos[$fila["articulo"]] = array(
				"cod_color" => $codC,
				"color" => trim((string) $fila["color"]),
				"cod_talla" => $codT,
				"talla" => trim((string) $fila["talla"]),
				"activo" => $activo
			);
			if (!$activo) {
				continue;
			}
			$claveC = $codC === "" ? "_" : $codC;
			$claveT = $codT === "" ? "_" : $codT;
			if (!isset($colores[$claveC])) {
				$colores[$claveC] = array(
					"cod" => $codC,
					"nombre" => trim((string) $fila["color"]) !== "" ? trim($fila["color"]) : "(s/color)",
					"activo" => true
				);
			}
			if (!isset($tallas[$claveT])) {
				$tallas[$claveT] = array(
					"cod" => $codT,
					"nombre" => trim((string) $fila["talla"]) !== "" ? trim($fila["talla"]) : "(s/talla)",
					"ord" => ctype_digit($codT) ? (int) $codT : 1000,
					"activo" => true
				);
			}
		}
		uasort($tallas, function ($a, $b) {
			if ($a["ord"] === $b["ord"]) {
				return strcasecmp($a["nombre"], $b["nombre"]);
			}
			return $a["ord"] < $b["ord"] ? -1 : 1;
		});
		uasort($colores, function ($a, $b) {
			return strcasecmp($a["nombre"], $b["nombre"]);
		});
		return array(
			"articulos" => $articulos,
			"colores" => array_values($colores),
			"tallas" => array_values($tallas)
		);
	}

	static public function mdlVentasColorTallaMeses($modelo, $periodos)
	{
		$periodos = array_values(array_unique(array_filter((array) $periodos)));
		$out = array();
		foreach ($periodos as $p) {
			$out[$p] = array();
		}
		if (empty($periodos)) {
			return $out;
		}
		$cat = self::mdlCatalogoVariantes($modelo);
		$arts = $cat["articulos"];
		if (empty($arts)) {
			return $out;
		}
		$anios = array();
		$mesesPorAnio = array();
		foreach ($periodos as $p) {
			$anio = (int) substr($p, 0, 4);
			$mes = (int) substr($p, 5, 2);
			if ($anio < self::ANIO_MIN || $mes < 1 || $mes > 12) {
				continue;
			}
			$anios[$anio] = true;
			if (!isset($mesesPorAnio[$anio])) {
				$mesesPorAnio[$anio] = array();
			}
			$mesesPorAnio[$anio][$mes] = true;
		}
		$listaArts = array();
		foreach ($arts as $art => $info) {
			if (!empty($info["activo"])) {
				$listaArts[] = $art;
			}
		}
		if (empty($listaArts)) {
			return $out;
		}
		$pdo = Conexion::conectar();
		foreach (array_keys($anios) as $anio) {
			$tabla = ModeloFichaGerencialModelos::mdlResolverTablaMovimientos($anio);
			if ($tabla === null) {
				continue;
			}
			$inicio = sprintf("%04d-01-01", $anio);
			$fin = sprintf("%04d-01-01", $anio + 1);
			$chunks = array_chunk($listaArts, 400);
			foreach ($chunks as $idx => $chunk) {
				$marcadores = array();
				foreach ($chunk as $i => $art) {
					$marcadores[] = ":art_{$anio}_{$idx}_{$i}";
				}
				$sql = "SELECT m.articulo,
						MONTH(m.fecha) AS mes,
						COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades
					FROM {$tabla} m
					WHERE m.articulo IN (" . implode(", ", $marcadores) . ")
					  AND m.fecha >= :inicio AND m.fecha < :fin
					  AND " . ModeloFichaGerencialModelos::sqlTiposVenta("m") . "
					  AND " . ModeloFichaGerencialModelos::sqlCabeceraValida("m") . "
					GROUP BY m.articulo, MONTH(m.fecha)";
				$stmt = $pdo->prepare($sql);
				foreach ($chunk as $i => $art) {
					$stmt->bindValue(":art_{$anio}_{$idx}_{$i}", $art, PDO::PARAM_STR);
				}
				$stmt->bindValue(":inicio", $inicio, PDO::PARAM_STR);
				$stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
				$stmt->execute();
				foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
					$mes = (int) $fila["mes"];
					if (empty($mesesPorAnio[$anio][$mes])) {
						continue;
					}
					$art = $fila["articulo"];
					if (!isset($arts[$art])) {
						continue;
					}
					$claveP = sprintf("%04d-%02d", $anio, $mes);
					$claveV = self::mdlClaveVariante($arts[$art]["cod_color"], $arts[$art]["cod_talla"]);
					if (!isset($out[$claveP][$claveV])) {
						$out[$claveP][$claveV] = 0.0;
					}
					$out[$claveP][$claveV] += (float) $fila["unidades"];
				}
			}
		}
		return $out;
	}

	static public function mdlMatrizSugerenciaArticulos($modelo, $periodoPlan, $estacional)
	{
		$vacio = array(
			"colores" => array(),
			"tallas" => array(),
			"por_mes" => array(),
			"plan" => null
		);
		$cat = self::mdlCatalogoVariantes($modelo);
		if (empty($cat["colores"]) || empty($cat["tallas"])) {
			return $vacio;
		}
		$mesesPlan = isset($periodoPlan["meses_lista"]) ? $periodoPlan["meses_lista"] : array();
		if (empty($mesesPlan)) {
			return $vacio;
		}
		$periodosMix = array();
		$periodosViejos = array();
		foreach ($mesesPlan as $m) {
			$anioObj = (int) $m["anio"];
			$mesObj = (int) $m["mes"];
			$mixTomado = false;
			for ($off = 1; $off <= self::ANIOS_ESTACIONALES; $off++) {
				$anioH = $anioObj - $off;
				if (!self::mdlMesHistorialCerrado($anioH, $mesObj)) {
					continue;
				}
				$claveH = sprintf("%04d-%02d", $anioH, $mesObj);
				if (!$mixTomado) {
					$periodosMix[] = $claveH;
					$mixTomado = true;
				}
				if ($off >= 2) {
					$periodosViejos[] = $claveH;
				}
			}
		}
		$ventas = self::mdlVentasColorTallaMeses(
			$modelo,
			array_values(array_unique(array_merge($periodosMix, $periodosViejos)))
		);
		$histViejoColor = array();
		foreach ($cat["colores"] as $col) {
			$histViejoColor[$col["cod"]] = 0.0;
			foreach ($periodosViejos as $pViejo) {
				if (!isset($ventas[$pViejo])) {
					continue;
				}
				foreach ($cat["tallas"] as $tal) {
					$k = self::mdlClaveVariante($col["cod"], $tal["cod"]);
					if (isset($ventas[$pViejo][$k])) {
						$histViejoColor[$col["cod"]] += (float) $ventas[$pViejo][$k];
					}
				}
			}
		}
		$histMixColor = array();
		$totalMix = 0.0;
		foreach ($cat["colores"] as $col) {
			$hm = 0.0;
			foreach ($periodosMix as $pMix) {
				if (!isset($ventas[$pMix])) {
					continue;
				}
				foreach ($cat["tallas"] as $tal) {
					$k = self::mdlClaveVariante($col["cod"], $tal["cod"]);
					if (isset($ventas[$pMix][$k])) {
						$hm += (float) $ventas[$pMix][$k];
					}
				}
			}
			$histMixColor[$col["cod"]] = $hm;
			$totalMix += $hm;
		}
		foreach ($cat["colores"] as &$colRef) {
			$hv = isset($histViejoColor[$colRef["cod"]]) ? $histViejoColor[$colRef["cod"]] : 0.0;
			$hm = isset($histMixColor[$colRef["cod"]]) ? $histMixColor[$colRef["cod"]] : 0.0;
			$shareM = $totalMix > 0 ? $hm / $totalMix : 0.0;
			$colRef["hist_viejo"] = $hv;
			$colRef["nuevo"] = ($hv <= 0) || ($totalMix > 0 && $shareM < 0.04);
		}
		unset($colRef);
		$sugerencias = isset($estacional["sugerencias"]) ? $estacional["sugerencias"] : array();
		$porMes = array();
		$planCeldas = array();
		$planSug = 0;
		$planHist = 0;
		foreach ($mesesPlan as $m) {
			$periodo = $m["periodo"];
			$anioObj = (int) $m["anio"];
			$mesObj = (int) $m["mes"];
			$sug = 0;
			if (isset($sugerencias[$periodo]["unidades"])) {
				$sug = (int) $sugerencias[$periodo]["unidades"];
			}
			$mixClave = null;
			for ($off = 1; $off <= self::ANIOS_ESTACIONALES; $off++) {
				$anioH = $anioObj - $off;
				if (self::mdlMesHistorialCerrado($anioH, $mesObj)) {
					$mixClave = sprintf("%04d-%02d", $anioH, $mesObj);
					break;
				}
			}
			$pesos = ($mixClave && isset($ventas[$mixClave])) ? $ventas[$mixClave] : array();
			$clavesOk = array();
			foreach ($cat["colores"] as $col) {
				foreach ($cat["tallas"] as $tal) {
					$clavesOk[self::mdlClaveVariante($col["cod"], $tal["cod"])] = true;
				}
			}
			$pesosAct = array();
			$sumaHist = 0.0;
			foreach ($pesos as $k => $u) {
				if (!isset($clavesOk[$k])) {
					continue;
				}
				$pesosAct[$k] = (float) $u;
				$sumaHist += (float) $u;
			}
			$reparto = self::mdlRepartirEnteros($sug, $pesosAct);
			$celdas = array();
			foreach ($cat["colores"] as $col) {
				foreach ($cat["tallas"] as $tal) {
					$k = self::mdlClaveVariante($col["cod"], $tal["cod"]);
					$hist = isset($pesosAct[$k]) ? (float) $pesosAct[$k] : 0.0;
					$uds = isset($reparto[$k]) ? (int) $reparto[$k] : 0;
					if ($hist <= 0 && $uds <= 0) {
						continue;
					}
					$celdas[$k] = array(
						"hist" => $hist,
						"pct" => $sumaHist > 0 ? round($hist * 100 / $sumaHist, 1) : 0,
						"sug" => $uds
					);
					if (!isset($planCeldas[$k])) {
						$planCeldas[$k] = array("hist" => 0.0, "sug" => 0, "pct" => 0);
					}
					$planCeldas[$k]["hist"] += $hist;
					$planCeldas[$k]["sug"] += $uds;
				}
			}
			$porMes[$periodo] = array(
				"sug" => $sug,
				"mix_periodo" => $mixClave,
				"mix_label" => $mixClave
					? (self::mdlNombreMesCorto((int) substr($mixClave, 5, 2)) . " " . substr($mixClave, 0, 4))
					: "",
				"hist" => $sumaHist,
				"celdas" => $celdas
			);
			$planSug += $sug;
			$planHist += $sumaHist;
		}
		foreach ($planCeldas as $k => $c) {
			$planCeldas[$k]["pct"] = $planHist > 0 ? round($c["hist"] * 100 / $planHist, 1) : 0;
		}
		$rango = array();
		foreach ($mesesPlan as $m) {
			$rango[] = self::mdlNombreMesCorto($m["mes"]);
		}
		$rangoTxt = "";
		if (count($rango) === 1) {
			$rangoTxt = strtolower($rango[0]);
		} elseif (count($rango) > 1) {
			$rangoTxt = strtolower($rango[0] . "–" . $rango[count($rango) - 1]);
		}
		return array(
			"colores" => $cat["colores"],
			"tallas" => $cat["tallas"],
			"por_mes" => $porMes,
			"plan" => array(
				"sug" => $planSug,
				"mix_periodo" => null,
				"mix_label" => $rangoTxt !== "" ? "Plan " . $rangoTxt : "Plan",
				"hist" => $planHist,
				"celdas" => $planCeldas
			)
		);
	}

	static private function mdlNormColorTxt($valor)
	{
		$t = strtoupper(trim((string) $valor));
		if (class_exists("Normalizer")) {
			$n = Normalizer::normalize($t, Normalizer::FORM_D);
			if ($n !== false) {
				$t = $n;
			}
		}
		$t = preg_replace("/\\p{Mn}/u", "", $t);
		$t = strtr($t, array("Á" => "A", "É" => "E", "Í" => "I", "Ó" => "O", "Ú" => "U", "Ü" => "U", "Ñ" => "N"));
		return trim($t);
	}

	static private function mdlClasificarRolMp($rol, $linea, $esTela)
	{
		$t = strtoupper(trim((string) $rol . " " . $linea));
		$t = strtr($t, array("Á" => "A", "É" => "E", "Í" => "I", "Ó" => "O", "Ú" => "U"));
		if ($esTela || preg_match("/TELA|LICRA|NAZCA|JERSEY/", $t)) {
			return "tela";
		}
		if (preg_match("/BLONDA|ENCAJE|PUNTILLA/", $t)) {
			return "blonda";
		}
		if (preg_match("/ELAST/", $t)) {
			return "elastico";
		}
		if (preg_match("/SESGO|BIES|BIÉS/", $t)) {
			return "sesgo";
		}
		if (preg_match("/TIRANTE|TIRA\\b|CINTA/", $t)) {
			return "tirante";
		}
		return null;
	}

	/**
	 * MP crítica de la receta (tela, blonda, elástico, sesgo, tirante):
	 * stock, consumo del plan y cuántos artículos/modelos la comparten.
	 */
	static public function mdlMpRiesgoModelo($modelo, $udsPlan, $mixColor = array())
	{
		$vacio = array(
			"fuente" => null,
			"version" => null,
			"uds_plan" => (int) $udsPlan,
			"items" => array()
		);
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return $vacio;
		}
		$udsPlan = max(0, (int) $udsPlan);
		$pdo = Conexion::conectar();
		$mps = array();
		$fuente = null;
		$version = null;

		try {
			$receta = $pdo->prepare(
				"SELECT r.id, r.version
				 FROM recetas_modelo r
				 WHERE TRIM(r.modelo) = :modelo
				   AND r.estado = 'PUBLICADA'
				   AND (r.vigente_desde IS NULL OR r.vigente_desde <= CURDATE())
				   AND (r.vigente_hasta IS NULL OR r.vigente_hasta >= CURDATE())
				 ORDER BY r.version DESC
				 LIMIT 1"
			);
			$receta->bindValue(":modelo", $modelo, PDO::PARAM_STR);
			$receta->execute();
			$cab = $receta->fetch(PDO::FETCH_ASSOC);
			if ($cab) {
				$fuente = "receta";
				$version = (int) $cab["version"];
				$det = $pdo->prepare(
					"SELECT id, nombre_rol, es_tela_principal, mp_base_codigo, consumo_base, regla_variante
					 FROM recetas_modelo_detalles
					 WHERE id_receta_modelo = :id AND activo = 1
					 ORDER BY orden ASC, id ASC"
				);
				$det->bindValue(":id", (int) $cab["id"], PDO::PARAM_INT);
				$det->execute();
				$vars = $pdo->prepare(
					"SELECT v.id_receta_modelo_detalle, TRIM(v.mp_codigo) AS mp_codigo,
						v.consumo, IFNULL(v.cod_color, '') AS cod_color
					 FROM recetas_modelo_variantes v
					 INNER JOIN recetas_modelo_detalles d
					   ON d.id = v.id_receta_modelo_detalle
					 WHERE d.id_receta_modelo = :id AND d.activo = 1
					   AND TRIM(IFNULL(v.mp_codigo, '')) <> ''"
				);
				$vars->bindValue(":id", (int) $cab["id"], PDO::PARAM_INT);
				$vars->execute();
				$varsPorDet = array();
				foreach ($vars->fetchAll(PDO::FETCH_ASSOC) as $v) {
					$idD = (int) $v["id_receta_modelo_detalle"];
					if (!isset($varsPorDet[$idD])) {
						$varsPorDet[$idD] = array();
					}
					$varsPorDet[$idD][] = $v;
				}
				foreach ($det->fetchAll(PDO::FETCH_ASSOC) as $d) {
					$tipo = self::mdlClasificarRolMp(
						isset($d["nombre_rol"]) ? $d["nombre_rol"] : "",
						"",
						!empty($d["es_tela_principal"])
					);
					if ($tipo === null) {
						continue;
					}
					$lista = isset($varsPorDet[(int) $d["id"]]) ? $varsPorDet[(int) $d["id"]] : array();
					$mpBase = trim((string) $d["mp_base_codigo"]);
					if ($mpBase !== "" && empty($lista)) {
						$lista[] = array(
							"mp_codigo" => $mpBase,
							"consumo" => $d["consumo_base"],
							"cod_color" => ""
						);
					}
					foreach ($lista as $v) {
						$cod = substr(trim((string) $v["mp_codigo"]), 0, 5);
						if ($cod === "") {
							continue;
						}
						if (!isset($mps[$cod])) {
							$mps[$cod] = array(
								"mp" => $cod,
								"tipo" => $tipo,
								"rol" => isset($d["nombre_rol"]) ? $d["nombre_rol"] : $tipo,
								"tela_principal" => !empty($d["es_tela_principal"]) ? 1 : 0,
								"consumo" => 0.0,
								"n_cons" => 0,
								"colores" => array()
							);
						} elseif (!empty($d["es_tela_principal"])) {
							$mps[$cod]["tela_principal"] = 1;
						}
						$cons = isset($v["consumo"]) && $v["consumo"] !== null && $v["consumo"] !== ""
							? (float) $v["consumo"]
							: (isset($d["consumo_base"]) ? (float) $d["consumo_base"] : 0.0);
						if ($cons > 0) {
							$mps[$cod]["consumo"] += $cons;
							$mps[$cod]["n_cons"]++;
						}
						$col = trim((string) $v["cod_color"]);
						if ($col !== "") {
							$mps[$cod]["colores"][$col] = true;
						}
					}
				}
			}
		} catch (Exception $e) {
			$mps = array();
		}

		if (empty($mps)) {
			try {
				$stmt = $pdo->prepare(
					"SELECT TRIM(dt.mat_pri) AS mp,
						LOWER(TRIM(IFNULL(dt.tej_princ, ''))) AS tej,
						AVG(dt.consumo) AS consumo,
						IFNULL(MAX(ts.Des_Larga), '') AS sublinea
					 FROM articulojf a
					 INNER JOIN detalles_tarjetajf dt ON dt.articulo = a.articulo
					 LEFT JOIN producto p ON TRIM(p.CodPro) = TRIM(dt.mat_pri)
					 LEFT JOIN Tabla_M_Detalle ts
					   ON ts.Cod_Tabla = 'TSUB'
					  AND CONCAT(TRIM(ts.Des_Corta), TRIM(ts.Valor_3)) = LEFT(TRIM(IFNULL(p.CodFab, '')), 6)
					 WHERE a.modelo = :modelo
					   AND a.estado IN ('Activo', 'ACTIVO', 'CAMPAÑAD', 'CAMPANAD')
					   AND TRIM(IFNULL(dt.mat_pri, '')) <> ''
					 GROUP BY TRIM(dt.mat_pri), LOWER(TRIM(IFNULL(dt.tej_princ, '')))"
				);
				$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
				$stmt->execute();
				foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
					$tipo = self::mdlClasificarRolMp(
						$row["sublinea"],
						$row["sublinea"],
						$row["tej"] === "si"
					);
					if ($tipo === null) {
						continue;
					}
					$cod = substr(trim((string) $row["mp"]), 0, 5);
					if ($cod === "" || isset($mps[$cod])) {
						continue;
					}
					$mps[$cod] = array(
						"mp" => $cod,
						"tipo" => $tipo,
						"rol" => $tipo,
						"tela_principal" => $row["tej"] === "si" ? 1 : 0,
						"consumo" => (float) $row["consumo"],
						"n_cons" => 1,
						"colores" => array()
					);
				}
				if (!empty($mps)) {
					$fuente = "tarjeta";
				}
			} catch (Exception $e) {
				return $vacio;
			}
		}

		if (empty($mps)) {
			$vacio["fuente"] = $fuente;
			$vacio["version"] = $version;
			return $vacio;
		}

		$mapaNomCod = array();
		try {
			$catCols = self::mdlCatalogoVariantes($modelo);
			foreach ($catCols["colores"] as $cCat) {
				$mapaNomCod[self::mdlNormColorTxt($cCat["nombre"])] = $cCat["cod"];
				$mapaNomCod[self::mdlNormColorTxt($cCat["cod"])] = $cCat["cod"];
			}
		} catch (Exception $e) {
			$mapaNomCod = array();
		}

		$codigos = array_keys($mps);
		$info = array();
		$uso = array();
		foreach (array_chunk($codigos, 80) as $chunk) {
			$ph = array();
			foreach ($chunk as $i => $c) {
				$ph[] = ":p" . $i;
			}
			$in = implode(", ", $ph);
			$sqlInfo = "SELECT
					TRIM(p.CodPro) AS mp,
					IFNULL(p.DesPro, '') AS descripcion,
					IFNULL(tc.Des_Larga, '') AS color,
					IFNULL(tu.Des_Corta, '') AS unidad
				 FROM producto p
				 LEFT JOIN Tabla_M_Detalle tc
				   ON tc.Cod_Tabla = 'TCOL' AND tc.Cod_Argumento = p.ColPro
				 LEFT JOIN Tabla_M_Detalle tu
				   ON tu.Cod_Tabla = 'TUND' AND tu.Cod_Argumento = p.UndPro
				 WHERE p.CodPro IN ($in)";
			$st = $pdo->prepare($sqlInfo);
			foreach ($chunk as $i => $c) {
				$st->bindValue(":p" . $i, $c, PDO::PARAM_STR);
			}
			$st->execute();
			foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$info[$row["mp"]] = $row;
			}
			$sqlUso = "SELECT
					TRIM(dt.mat_pri) AS mp,
					COUNT(DISTINCT dt.articulo) AS articulos,
					COUNT(DISTINCT a.modelo) AS modelos,
					COUNT(DISTINCT CASE WHEN a.modelo = :mod THEN dt.articulo END) AS art_modelo
				 FROM detalles_tarjetajf dt
				 INNER JOIN articulojf a ON a.articulo = dt.articulo
				 WHERE TRIM(dt.mat_pri) IN ($in)
				   AND a.estado IN ('Activo', 'ACTIVO', 'CAMPAÑAD', 'CAMPANAD')
				 GROUP BY TRIM(dt.mat_pri)";
			$stU = $pdo->prepare($sqlUso);
			$stU->bindValue(":mod", $modelo, PDO::PARAM_STR);
			foreach ($chunk as $i => $c) {
				$stU->bindValue(":p" . $i, $c, PDO::PARAM_STR);
			}
			$stU->execute();
			foreach ($stU->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$uso[$row["mp"]] = $row;
			}
		}

		$items = array();
		foreach ($mps as $cod => $m) {
			$inf = isset($info[$cod]) ? $info[$cod] : array();
			$u = isset($uso[$cod]) ? $uso[$cod] : array();
			$cons = $m["n_cons"] > 0 ? $m["consumo"] / $m["n_cons"] : 0.0;
			$udsMp = $udsPlan;
			if (!empty($m["colores"]) && !empty($mixColor)) {
				$udsMp = 0;
				foreach ($m["colores"] as $col => $ok) {
					if (isset($mixColor[$col])) {
						$udsMp += (int) $mixColor[$col];
					}
				}
				if ($udsMp <= 0) {
					$udsMp = $udsPlan;
				}
			}
			$req = $cons > 0 ? $cons * $udsMp : 0.0;
			$colsRes = array();
			foreach ($m["colores"] as $colK => $okC) {
				$nk = self::mdlNormColorTxt($colK);
				$colsRes[isset($mapaNomCod[$nk]) ? $mapaNomCod[$nk] : $colK] = true;
			}
			$nomProd = self::mdlNormColorTxt(isset($inf["color"]) ? $inf["color"] : "");
			$descProd = self::mdlNormColorTxt(isset($inf["descripcion"]) ? $inf["descripcion"] : "");
			foreach ($mapaNomCod as $nomC => $codC) {
				if ($nomC === "" || strlen($nomC) < 4) {
					continue;
				}
				if ($nomProd === $nomC || ($descProd !== "" && strpos($descProd, $nomC) !== false)) {
					$colsRes[$codC] = true;
				}
			}
			$art = isset($u["articulos"]) ? (int) $u["articulos"] : 0;
			$mods = isset($u["modelos"]) ? (int) $u["modelos"] : 0;
			if ($art < 1) {
				$art = 1;
			}
			if ($mods < 1) {
				$mods = 1;
			}
			$estado = "ok";
			if ($art <= 1) {
				$estado = "unico";
			} elseif ($art <= 3) {
				$estado = "poco";
			}
			$items[] = array(
				"mp" => $cod,
				"tipo" => $m["tipo"],
				"rol" => $m["rol"],
				"tela_principal" => !empty($m["tela_principal"]) ? 1 : 0,
				"descripcion" => isset($inf["descripcion"]) ? $inf["descripcion"] : $cod,
				"color" => isset($inf["color"]) ? $inf["color"] : "",
				"unidad" => isset($inf["unidad"]) ? $inf["unidad"] : "",
				"consumo" => round($cons, 4),
				"requerido" => round($req, 2),
				"colores" => array_values(array_keys($colsRes)),
				"articulos" => $art,
				"art_modelo" => isset($u["art_modelo"]) ? (int) $u["art_modelo"] : 0,
				"modelos" => $mods,
				"estado" => $estado
			);
		}

		$ordenTipo = array("tela" => 1, "blonda" => 2, "elastico" => 3, "sesgo" => 4, "tirante" => 5);
		usort($items, function ($a, $b) use ($ordenTipo) {
			$ta = isset($ordenTipo[$a["tipo"]]) ? $ordenTipo[$a["tipo"]] : 9;
			$tb = isset($ordenTipo[$b["tipo"]]) ? $ordenTipo[$b["tipo"]] : 9;
			if ($ta !== $tb) {
				return $ta - $tb;
			}
			if ((int) $a["tela_principal"] !== (int) $b["tela_principal"]) {
				return (int) $b["tela_principal"] - (int) $a["tela_principal"];
			}
			return strcmp($a["descripcion"], $b["descripcion"]);
		});

		return array(
			"fuente" => $fuente,
			"version" => $version,
			"uds_plan" => $udsPlan,
			"items" => $items
		);
	}

	/**
	 * Detalle de un modelo: historial estacional + inventario + lista 9 + sugerencias.
	 */
	static public function mdlContextoModelo($modelo, $periodoPlan, $mesesHistorial = 24, $aniosEstacionales = self::ANIOS_ESTACIONALES)
	{
		$modelo = trim((string) $modelo);
		$cabecera = ModeloFichaGerencialModelos::mdlCabeceraModelo($modelo);
		if (!$cabecera) {
			return null;
		}

		$estacional = self::mdlHistorialEstacional($modelo, $periodoPlan, $aniosEstacionales);
		$invMap = self::mdlInventarioPorModelos(array($modelo));
		$inv = isset($invMap[$modelo]) ? $invMap[$modelo] : self::mdlInventarioVacio();
		$precios = self::mdlPreciosLista9(array($modelo));
		$precio9 = array_key_exists($modelo, $precios) ? $precios[$modelo] : null;

		// Serie continua corta (apoyo): últimos 12 meses hasta mes anterior
		$ultimoMesHist = self::mdlSumarMeses((int) date("Y"), (int) date("n"), -1);
		$hastaHist = sprintf("%04d-%02d", $ultimoMesHist["anio"], $ultimoMesHist["mes"]);
		$periodoHist = self::mdlConstruirPeriodoHistorial($hastaHist, 12);
		$ventas = ($periodoHist === null)
			? array()
			: self::mdlVentasMensualesLote(array($modelo), $periodoHist);
		$mapaMeses = isset($ventas[$modelo]) ? $ventas[$modelo] : array();
		$serie = ($periodoHist === null) ? array() : self::mdlSerieCompleta($mapaMeses, $periodoHist);

		$matriz = array("colores" => array(), "tallas" => array(), "por_mes" => array(), "plan" => null);
		try {
			$matriz = self::mdlMatrizSugerenciaArticulos($modelo, $periodoPlan, $estacional);
		} catch (Exception $e) {
			$matriz = array("colores" => array(), "tallas" => array(), "por_mes" => array(), "plan" => null);
		}

		$udsPlan = 0;
		if (isset($estacional["sugerencias"]) && is_array($estacional["sugerencias"])) {
			foreach ($estacional["sugerencias"] as $sugMes) {
				if (isset($sugMes["unidades"])) {
					$udsPlan += (int) $sugMes["unidades"];
				}
			}
		}
		$mixColor = array();
		if (isset($matriz["plan"]["celdas"]) && is_array($matriz["plan"]["celdas"])) {
			foreach ($matriz["plan"]["celdas"] as $clave => $cel) {
				$partes = explode("|", $clave, 2);
				$col = isset($partes[0]) ? $partes[0] : "";
				if ($col === "") {
					continue;
				}
				if (!isset($mixColor[$col])) {
					$mixColor[$col] = 0;
				}
				$mixColor[$col] += isset($cel["sug"]) ? (int) $cel["sug"] : 0;
			}
		}
		$mpRiesgo = array("fuente" => null, "version" => null, "uds_plan" => $udsPlan, "items" => array());
		try {
			$mpRiesgo = self::mdlMpRiesgoModelo($modelo, $udsPlan, $mixColor);
		} catch (Exception $e) {
			$mpRiesgo = array("fuente" => null, "version" => null, "uds_plan" => $udsPlan, "items" => array());
		}

		return array(
			"cabecera" => $cabecera,
			"periodo_plan" => $periodoPlan,
			"periodo_historial" => $periodoHist,
			"historial_estacional" => $estacional,
			"serie_mensual" => $serie,
			"tendencia_reciente" => self::mdlTendenciaReciente($modelo),
			"inventario" => $inv,
			"precio_lista9" => $precio9,
			"sin_lista9" => ($precio9 === null),
			"sugerencias" => $estacional["sugerencias"],
			"matriz_articulos" => $matriz,
			"mp_riesgo" => $mpRiesgo,
			"formula_version" => self::FORMULA_VERSION,
			"anios_estacionales" => (int) $aniosEstacionales
		);
	}

	/**
	 * Conciliación: misma cifra que ficha (mdlVentasMensuales / mdlEvolucionPeriodo).
	 */
	static public function mdlConciliarContraFicha($modelo, $anio, $mes)
	{
		$modelo = trim((string) $modelo);
		$anio = (int) $anio;
		$mes = (int) $mes;
		$periodo = ModeloFichaGerencialModelos::mdlPeriodoDesdeAnioMes($anio, $mes);
		$resultado = array(
			"modelo" => $modelo,
			"anio" => $anio,
			"mes" => $mes,
			"ok" => false,
			"ficha" => null,
			"proyeccion" => null,
			"diferencia_unidades" => null,
			"diferencia_venta_neta" => null,
			"mensaje" => ""
		);

		if ($periodo === null) {
			$resultado["mensaje"] = "Período inválido o sin tabla de movimientos para conciliar (ficha no admite meses futuros).";
			return $resultado;
		}

		$fichaFilas = ModeloFichaGerencialModelos::mdlVentasMensuales($modelo, $periodo);
		$ficha = empty($fichaFilas)
			? array("unidades_vendidas" => 0.0, "venta_neta" => 0.0)
			: array(
				"unidades_vendidas" => (float) $fichaFilas[0]["unidades_vendidas"],
				"venta_neta" => (float) $fichaFilas[0]["venta_neta"]
			);

		$periodoHist = array(
			"inicio" => $periodo["inicio"],
			"fin" => $periodo["fin"],
			"anios" => $periodo["anios"],
			"meses_lista" => array(array(
				"anio" => $anio,
				"mes" => $mes,
				"periodo" => sprintf("%04d-%02d", $anio, $mes)
			))
		);
		$lote = self::mdlVentasMensualesLote(array($modelo), $periodoHist);
		$clave = sprintf("%04d-%02d", $anio, $mes);
		$propia = (isset($lote[$modelo][$clave]))
			? $lote[$modelo][$clave]
			: array("unidades_vendidas" => 0.0, "venta_neta" => 0.0);

		$diffU = (float) $propia["unidades_vendidas"] - (float) $ficha["unidades_vendidas"];
		$diffV = (float) $propia["venta_neta"] - (float) $ficha["venta_neta"];
		$ok = (abs($diffU) < 0.0001 && abs($diffV) < 0.01);

		$resultado["ok"] = $ok;
		$resultado["ficha"] = $ficha;
		$resultado["proyeccion"] = array(
			"unidades_vendidas" => (float) $propia["unidades_vendidas"],
			"venta_neta" => (float) $propia["venta_neta"]
		);
		$resultado["diferencia_unidades"] = $diffU;
		$resultado["diferencia_venta_neta"] = $diffV;
		$resultado["mensaje"] = $ok
			? "Conciliación OK: mismas unidades y venta neta que la ficha gerencial."
			: "Desvío detectado respecto de la ficha gerencial.";
		$resultado["tipos_venta"] = ModeloFichaGerencialModelos::tiposVenta();
		$resultado["inventario_ficha"] = ModeloFichaGerencialModelos::mdlInventarioResumen($modelo);
		$resultado["inventario_proyeccion"] = self::mdlInventarioPorModelos(array($modelo));
		$precioFicha = ModeloFichaGerencialModelos::mdlPrecio9Valorizado($modelo, 1);
		$precios = self::mdlPreciosLista9(array($modelo));
		$resultado["precio9_ficha"] = $precioFicha ? (float) $precioFicha["precio9"] : null;
		$resultado["precio9_proyeccion"] = array_key_exists($modelo, $precios) ? $precios[$modelo] : null;

		return $resultado;
	}

	static public function mdlTiposFactor()
	{
		return array(
			array("codigo" => "CAMPANA", "nombre" => "Campaña comercial"),
			array("codigo" => "PRECIO", "nombre" => "Precio"),
			array("codigo" => "LANZAMIENTO", "nombre" => "Lanzamiento/relanzamiento"),
			array("codigo" => "EVENTO", "nombre" => "Evento/estacionalidad"),
			array("codigo" => "PUBLICIDAD", "nombre" => "Publicidad/redes"),
			array("codigo" => "DISPONIBILIDAD", "nombre" => "Disponibilidad"),
			array("codigo" => "MERCADO", "nombre" => "Mercado"),
			array("codigo" => "OTRO", "nombre" => "Otro")
		);
	}

	static public function mdlTipoFactorValido($tipo)
	{
		foreach (self::mdlTiposFactor() as $t) {
			if ($t["codigo"] === $tipo) {
				return true;
			}
		}
		return false;
	}

	static public function mdlDesviacionRelevante($sugeridas, $ajustes, $oficiales)
	{
		$base = (int) $sugeridas + (int) $ajustes;
		$diff = abs((int) $oficiales - $base);
		if ($diff <= self::UMBRAL_DESVIACION_ABS) {
			return false;
		}
		$den = max(1, abs($base));
		$pct = ($diff / $den) * 100.0;
		return $pct > self::UMBRAL_DESVIACION_PCT;
	}

	static private function mdlContarFactoresActivosLinea($pdo, $idLinea)
	{
		$stmt = $pdo->prepare(
			"SELECT COUNT(*) FROM proyeccion_comercial_factorjf
			 WHERE id_proyeccion_modelo = :id AND activo = 1"
		);
		$stmt->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	static public function mdlRecalcularAjustesLinea($pdo, $idLinea, $usuario = 0)
	{
		$sel = $pdo->prepare(
			"SELECT unidades_sugeridas, unidades_ajustes, unidades_oficiales, estado_linea
			 FROM proyeccion_comercial_modelojf WHERE id = :id LIMIT 1"
		);
		$sel->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
		$sel->execute();
		$linea = $sel->fetch(PDO::FETCH_ASSOC);
		if (!$linea) {
			return 0;
		}

		$sum = $pdo->prepare(
			"SELECT COALESCE(SUM(ajuste_unidades), 0)
			 FROM proyeccion_comercial_factorjf
			 WHERE id_proyeccion_modelo = :id AND activo = 1"
		);
		$sum->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
		$sum->execute();
		$total = (int) $sum->fetchColumn();

		$sug = (int) $linea["unidades_sugeridas"];
		$ajAnt = (int) $linea["unidades_ajustes"];
		$ofi = (int) $linea["unidades_oficiales"];
		$baseAnt = max(0, $sug + $ajAnt);
		$baseNueva = max(0, $sug + $total);
		$ofiAutoAnt = (int) max(0, round($baseAnt / 10) * 10);
		// Si el oficial seguía la base automática, lo actualiza con sug + factores (múltiplo de 10).
		$ofiNueva = $ofi;
		if ($linea["estado_linea"] === "BORRADOR"
			&& ($ofi === $baseAnt || $ofi === $sug || $ofi === $ofiAutoAnt)
		) {
			$ofiNueva = (int) max(0, round($baseNueva / 10) * 10);
		}

		$upd = $pdo->prepare(
			"UPDATE proyeccion_comercial_modelojf
			 SET unidades_ajustes = :aj,
				 unidades_oficiales = :ofi,
				 actualizado_por = :usuario,
				 actualizado_en = NOW()
			 WHERE id = :id"
		);
		$upd->bindValue(":aj", $total, PDO::PARAM_INT);
		$upd->bindValue(":ofi", $ofiNueva, PDO::PARAM_INT);
		$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
		$upd->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
		$upd->execute();
		return $total;
	}

	static public function mdlObtenerLinea($idLinea)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM proyeccion_comercial_modelojf WHERE id = :id LIMIT 1"
		);
		$stmt->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		return $fila ? $fila : null;
	}

	static public function mdlListarFactores($idLinea)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM proyeccion_comercial_factorjf
			 WHERE id_proyeccion_modelo = :id AND activo = 1
			 ORDER BY id ASC"
		);
		$stmt->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$linea = self::mdlObtenerLinea($idLinea);
		$sug = $linea ? (int) $linea["unidades_sugeridas"] : 0;
		$out = array();
		foreach ($filas as $f) {
			$ajuste = (int) $f["ajuste_unidades"];
			$pct = $f["impacto_pct"] !== null ? (float) $f["impacto_pct"] : null;
			if ($pct === null && $sug > 0) {
				$pct = round(($ajuste / $sug) * 100, 2);
			}
			$out[] = array(
				"id" => (int) $f["id"],
				"id_proyeccion_modelo" => (int) $f["id_proyeccion_modelo"],
				"tipo" => $f["tipo"],
				"titulo" => $f["titulo"],
				"descripcion" => $f["descripcion"],
				"fecha_desde" => $f["fecha_desde"],
				"fecha_hasta" => $f["fecha_hasta"],
				"ajuste_unidades" => $ajuste,
				"impacto_pct" => $pct,
				"precio_anterior" => $f["precio_anterior"] === null ? null : (float) $f["precio_anterior"],
				"precio_nuevo" => $f["precio_nuevo"] === null ? null : (float) $f["precio_nuevo"],
				"canal_publicidad" => $f["canal_publicidad"],
				"inversion_publicidad" => $f["inversion_publicidad"] === null ? null : (float) $f["inversion_publicidad"],
				"referencia_evidencia" => $f["referencia_evidencia"],
				"creado_por" => $f["creado_por"],
				"creado_en" => $f["creado_en"]
			);
		}
		return array(
			"factores" => $out,
			"linea" => $linea ? array(
				"id" => (int) $linea["id"],
				"modelo" => $linea["modelo"],
				"anio" => (int) $linea["anio"],
				"mes" => (int) $linea["mes"],
				"periodo" => sprintf("%04d-%02d", (int) $linea["anio"], (int) $linea["mes"]),
				"unidades_sugeridas" => (int) $linea["unidades_sugeridas"],
				"unidades_ajustes" => (int) $linea["unidades_ajustes"],
				"unidades_oficiales" => (int) $linea["unidades_oficiales"],
				"estado_linea" => $linea["estado_linea"],
				"observacion" => $linea["observacion"]
			) : null,
			"tipos" => self::mdlTiposFactor(),
			"suma_ajustes" => array_sum(array_map(function ($x) {
				return (int) $x["ajuste_unidades"];
			}, $out))
		);
	}

	/**
	 * Factores activos agrupados por mes para un modelo dentro del plan.
	 */
	static public function mdlResumenFactoresPorModelo($idPeriodo, $modelo)
	{
		$idPeriodo = (int) $idPeriodo;
		$modelo = trim((string) $modelo);
		$stmt = Conexion::conectar()->prepare(
			"SELECT l.id AS id_linea, l.anio, l.mes,
				l.unidades_sugeridas, l.unidades_ajustes, l.unidades_oficiales, l.estado_linea,
				f.id AS id_factor, f.tipo, f.titulo, f.ajuste_unidades
			 FROM proyeccion_comercial_modelojf l
			 LEFT JOIN proyeccion_comercial_factorjf f
				ON f.id_proyeccion_modelo = l.id AND f.activo = 1
			 WHERE l.id_periodo = :id AND TRIM(l.modelo) = :modelo
			 ORDER BY l.anio ASC, l.mes ASC, f.id ASC"
		);
		$stmt->bindValue(":id", $idPeriodo, PDO::PARAM_INT);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$porLinea = array();
		foreach ($rows as $r) {
			$idL = (int) $r["id_linea"];
			if (!isset($porLinea[$idL])) {
				$porLinea[$idL] = array(
					"id_linea" => $idL,
					"periodo" => sprintf("%04d-%02d", (int) $r["anio"], (int) $r["mes"]),
					"unidades_sugeridas" => (int) $r["unidades_sugeridas"],
					"unidades_ajustes" => (int) $r["unidades_ajustes"],
					"unidades_oficiales" => (int) $r["unidades_oficiales"],
					"estado_linea" => $r["estado_linea"],
					"factores" => array()
				);
			}
			if (!empty($r["id_factor"])) {
				$porLinea[$idL]["factores"][] = array(
					"id" => (int) $r["id_factor"],
					"tipo" => $r["tipo"],
					"titulo" => $r["titulo"],
					"ajuste_unidades" => (int) $r["ajuste_unidades"]
				);
			}
		}
		return array_values($porLinea);
	}

	static private function mdlValidarPayloadFactor($datos, $esOtro)
	{
		$tipo = isset($datos["tipo"]) ? trim((string) $datos["tipo"]) : "";
		$titulo = isset($datos["titulo"]) ? trim((string) $datos["titulo"]) : "";
		$descripcion = isset($datos["descripcion"]) ? trim((string) $datos["descripcion"]) : "";
		if (!self::mdlTipoFactorValido($tipo)) {
			throw new Exception("Tipo de factor inválido");
		}
		if ($titulo === "" || strlen($titulo) > 120) {
			throw new Exception("Título obligatorio (máx. 120)");
		}
		if ($esOtro && $descripcion === "") {
			throw new Exception("El tipo “Otro” exige descripción");
		}
		if (strlen($descripcion) > 1000) {
			throw new Exception("Descripción demasiado larga");
		}
		if (!isset($datos["ajuste_unidades"]) || !is_numeric($datos["ajuste_unidades"])) {
			throw new Exception("Ajuste en unidades inválido");
		}
		$ajuste = (int) $datos["ajuste_unidades"];
		if ($ajuste === 0) {
			throw new Exception("El ajuste en unidades no puede ser 0");
		}
		return array(
			"tipo" => $tipo,
			"titulo" => $titulo,
			"descripcion" => $descripcion === "" ? null : $descripcion,
			"ajuste_unidades" => $ajuste,
			"impacto_pct" => isset($datos["impacto_pct"]) && $datos["impacto_pct"] !== "" && is_numeric($datos["impacto_pct"])
				? (float) $datos["impacto_pct"] : null,
			"fecha_desde" => !empty($datos["fecha_desde"]) ? $datos["fecha_desde"] : null,
			"fecha_hasta" => !empty($datos["fecha_hasta"]) ? $datos["fecha_hasta"] : null,
			"precio_anterior" => isset($datos["precio_anterior"]) && $datos["precio_anterior"] !== "" && is_numeric($datos["precio_anterior"])
				? (float) $datos["precio_anterior"] : null,
			"precio_nuevo" => isset($datos["precio_nuevo"]) && $datos["precio_nuevo"] !== "" && is_numeric($datos["precio_nuevo"])
				? (float) $datos["precio_nuevo"] : null,
			"canal_publicidad" => isset($datos["canal_publicidad"]) ? trim((string) $datos["canal_publicidad"]) : null,
			"inversion_publicidad" => isset($datos["inversion_publicidad"]) && $datos["inversion_publicidad"] !== "" && is_numeric($datos["inversion_publicidad"])
				? (float) $datos["inversion_publicidad"] : null,
			"referencia_evidencia" => isset($datos["referencia_evidencia"]) ? trim((string) $datos["referencia_evidencia"]) : null
		);
	}

	static public function mdlGuardarFactor($idLinea, $datos, $usuario, $motivo = "")
	{
		$linea = self::mdlObtenerLinea($idLinea);
		if (!$linea) {
			return array("ok" => false, "mensaje" => "Línea no encontrada");
		}
		if ($linea["estado_linea"] === "CERRADO") {
			return array("ok" => false, "mensaje" => "Línea cerrada");
		}
		if ($linea["estado_linea"] === "PUBLICADO" && trim((string) $motivo) === "") {
			return array("ok" => false, "mensaje" => "Motivo obligatorio: la línea ya está publicada");
		}

		$idFactor = isset($datos["id"]) ? (int) $datos["id"] : 0;
		try {
			$payload = self::mdlValidarPayloadFactor($datos, (isset($datos["tipo"]) && $datos["tipo"] === "OTRO"));
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => $e->getMessage());
		}

		// Si viene % y no quieren recalcular unidades, respetamos unidades.
		// Si viene % y sugerencia > 0 y marcaron aplicar_pct, recalcular.
		if (!empty($datos["aplicar_pct"]) && $payload["impacto_pct"] !== null) {
			$payload["ajuste_unidades"] = (int) round(((int) $linea["unidades_sugeridas"]) * ($payload["impacto_pct"] / 100.0));
			if ($payload["ajuste_unidades"] === 0) {
				return array("ok" => false, "mensaje" => "El % aplicado resulta en ajuste 0");
			}
		} elseif ($payload["impacto_pct"] === null && (int) $linea["unidades_sugeridas"] > 0) {
			$payload["impacto_pct"] = round(
				($payload["ajuste_unidades"] / (int) $linea["unidades_sugeridas"]) * 100,
				2
			);
		}

		$pdo = Conexion::conectar();
		try {
			$pdo->beginTransaction();
			if ($idFactor > 0) {
				$sel = $pdo->prepare(
					"SELECT * FROM proyeccion_comercial_factorjf
					 WHERE id = :id AND id_proyeccion_modelo = :id_linea AND activo = 1 LIMIT 1"
				);
				$sel->bindValue(":id", $idFactor, PDO::PARAM_INT);
				$sel->bindValue(":id_linea", (int) $idLinea, PDO::PARAM_INT);
				$sel->execute();
				$prev = $sel->fetch(PDO::FETCH_ASSOC);
				if (!$prev) {
					throw new Exception("Factor no encontrado");
				}
				$upd = $pdo->prepare(
					"UPDATE proyeccion_comercial_factorjf
					 SET tipo = :tipo, titulo = :titulo, descripcion = :descripcion,
						 fecha_desde = :fd, fecha_hasta = :fh,
						 ajuste_unidades = :aj, impacto_pct = :pct,
						 precio_anterior = :pa, precio_nuevo = :pn,
						 canal_publicidad = :canal, inversion_publicidad = :inv,
						 referencia_evidencia = :ref,
						 actualizado_por = :usuario, actualizado_en = NOW()
					 WHERE id = :id"
				);
				$upd->bindValue(":id", $idFactor, PDO::PARAM_INT);
			} else {
				$upd = $pdo->prepare(
					"INSERT INTO proyeccion_comercial_factorjf
						(id_proyeccion_modelo, tipo, titulo, descripcion, fecha_desde, fecha_hasta,
						 ajuste_unidades, impacto_pct, precio_anterior, precio_nuevo,
						 canal_publicidad, inversion_publicidad, referencia_evidencia,
						 activo, creado_por, creado_en)
					 VALUES
						(:id_linea, :tipo, :titulo, :descripcion, :fd, :fh,
						 :aj, :pct, :pa, :pn, :canal, :inv, :ref,
						 1, :usuario, NOW())"
				);
				$upd->bindValue(":id_linea", (int) $idLinea, PDO::PARAM_INT);
			}

			$upd->bindValue(":tipo", $payload["tipo"], PDO::PARAM_STR);
			$upd->bindValue(":titulo", $payload["titulo"], PDO::PARAM_STR);
			$upd->bindValue(":descripcion", $payload["descripcion"], $payload["descripcion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$upd->bindValue(":fd", $payload["fecha_desde"], $payload["fecha_desde"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$upd->bindValue(":fh", $payload["fecha_hasta"], $payload["fecha_hasta"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$upd->bindValue(":aj", $payload["ajuste_unidades"], PDO::PARAM_INT);
			$upd->bindValue(":pct", $payload["impacto_pct"] === null ? null : number_format($payload["impacto_pct"], 4, ".", ""), $payload["impacto_pct"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$upd->bindValue(":pa", $payload["precio_anterior"] === null ? null : number_format($payload["precio_anterior"], 4, ".", ""), $payload["precio_anterior"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$upd->bindValue(":pn", $payload["precio_nuevo"] === null ? null : number_format($payload["precio_nuevo"], 4, ".", ""), $payload["precio_nuevo"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$canal = $payload["canal_publicidad"] === null || $payload["canal_publicidad"] === "" ? null : $payload["canal_publicidad"];
			$upd->bindValue(":canal", $canal, $canal === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$upd->bindValue(":inv", $payload["inversion_publicidad"] === null ? null : number_format($payload["inversion_publicidad"], 2, ".", ""), $payload["inversion_publicidad"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$ref = $payload["referencia_evidencia"] === null || $payload["referencia_evidencia"] === "" ? null : $payload["referencia_evidencia"];
			$upd->bindValue(":ref", $ref, $ref === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
			$upd->execute();

			if ($idFactor <= 0) {
				$idFactor = (int) $pdo->lastInsertId();
			}

			$suma = self::mdlRecalcularAjustesLinea($pdo, $idLinea, $usuario);
			self::mdlInsertarAuditoria(
				$pdo,
				$idLinea,
				"ACTUALIZAR",
				"factor",
				null,
				$payload["tipo"] . ":" . $payload["ajuste_unidades"],
				$motivo !== "" ? $motivo : ("Factor " . ($datos["id"] ? "actualizado" : "creado") . ": " . $payload["titulo"]),
				$usuario
			);
			$pdo->commit();
			return array(
				"ok" => true,
				"id_factor" => $idFactor,
				"unidades_ajustes" => $suma,
				"detalle" => self::mdlListarFactores($idLinea)
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => $e->getMessage());
		}
	}

	static public function mdlEliminarFactor($idFactor, $usuario, $motivo = "")
	{
		$pdo = Conexion::conectar();
		$sel = $pdo->prepare(
			"SELECT f.*, l.estado_linea, l.id AS id_linea
			 FROM proyeccion_comercial_factorjf f
			 INNER JOIN proyeccion_comercial_modelojf l ON l.id = f.id_proyeccion_modelo
			 WHERE f.id = :id AND f.activo = 1 LIMIT 1"
		);
		$sel->bindValue(":id", (int) $idFactor, PDO::PARAM_INT);
		$sel->execute();
		$f = $sel->fetch(PDO::FETCH_ASSOC);
		if (!$f) {
			return array("ok" => false, "mensaje" => "Factor no encontrado");
		}
		if ($f["estado_linea"] === "CERRADO") {
			return array("ok" => false, "mensaje" => "Línea cerrada");
		}
		if ($f["estado_linea"] === "PUBLICADO" && trim((string) $motivo) === "") {
			return array("ok" => false, "mensaje" => "Motivo obligatorio: la línea ya está publicada");
		}

		try {
			$pdo->beginTransaction();
			$upd = $pdo->prepare(
				"UPDATE proyeccion_comercial_factorjf
				 SET activo = 0, actualizado_por = :usuario, actualizado_en = NOW()
				 WHERE id = :id"
			);
			$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
			$upd->bindValue(":id", (int) $idFactor, PDO::PARAM_INT);
			$upd->execute();
			$suma = self::mdlRecalcularAjustesLinea($pdo, (int) $f["id_linea"], $usuario);
			self::mdlInsertarAuditoria(
				$pdo,
				(int) $f["id_linea"],
				"ACTUALIZAR",
				"factor",
				$f["titulo"] . ":" . $f["ajuste_unidades"],
				null,
				$motivo !== "" ? $motivo : ("Factor inactivado: " . $f["titulo"]),
				$usuario
			);
			$pdo->commit();
			return array(
				"ok" => true,
				"unidades_ajustes" => $suma,
				"detalle" => self::mdlListarFactores((int) $f["id_linea"])
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudo eliminar el factor");
		}
	}

	/**
	 * Aplica oficial = sugerencia + ajustes (atajo).
	 */
	static public function mdlAplicarSugerenciaMasAjustes($idLinea, $usuario, $motivo = "")
	{
		$linea = self::mdlObtenerLinea($idLinea);
		if (!$linea) {
			return array("ok" => false, "mensaje" => "Línea no encontrada");
		}
		$ofi = (int) $linea["unidades_sugeridas"] + (int) $linea["unidades_ajustes"];
		if ($ofi < 0) {
			$ofi = 0;
		}
		return self::mdlGuardarLineas(
			(int) $linea["id_periodo"],
			array(array(
				"id" => (int) $idLinea,
				"unidades_oficiales" => $ofi,
				"observacion" => $linea["observacion"]
			)),
			$usuario,
			$motivo !== "" ? $motivo : "Aplicar sugerencia + factores"
		);
	}

	static public function mdlListarPeriodos($limite = 50)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.*,
				(SELECT COUNT(*) FROM proyeccion_comercial_modelojf l WHERE l.id_periodo = p.id) AS total_lineas,
				(SELECT COUNT(*) FROM proyeccion_comercial_modelojf l
					WHERE l.id_periodo = p.id AND l.estado_linea = 'PUBLICADO') AS lineas_publicadas,
				(SELECT COUNT(*) FROM proyeccion_comercial_modelojf l
					WHERE l.id_periodo = p.id AND l.estado_linea = 'BORRADOR') AS lineas_borrador
			 FROM proyeccion_comercial_periodojf p
			 ORDER BY p.id DESC
			 LIMIT :limite"
		);
		$stmt->bindValue(":limite", (int) $limite, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Modelos ya presentes en el plan (distinct) con resumen por estado.
	 */
	static public function mdlModelosProyectadosPeriodo($idPeriodo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(l.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(l.modelo)) AS nombre,
				IFNULL(mk.marca, '') AS marca,
				IFNULL(m.id_marca, 0) AS id_marca,
				COUNT(*) AS meses,
				SUM(CASE WHEN l.estado_linea = 'BORRADOR' THEN 1 ELSE 0 END) AS meses_borrador,
				SUM(CASE WHEN l.estado_linea = 'PUBLICADO' THEN 1 ELSE 0 END) AS meses_publicados,
				SUM(CASE WHEN l.estado_linea = 'CERRADO' THEN 1 ELSE 0 END) AS meses_cerrados,
				COALESCE(SUM(l.unidades_oficiales), 0) AS unidades_oficiales,
				COALESCE(SUM(l.unidades_sugeridas), 0) AS unidades_sugeridas,
				MAX(CASE WHEN l.precio_lista9_snapshot IS NULL THEN 1 ELSE 0 END) AS sin_lista9
			 FROM proyeccion_comercial_modelojf l
			 LEFT JOIN modelojf m ON TRIM(m.modelo) = TRIM(l.modelo)
			 LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			 WHERE l.id_periodo = :id
			 GROUP BY TRIM(l.modelo), m.nombre, mk.marca, m.id_marca
			 ORDER BY modelo ASC"
		);
		$stmt->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$out = array();
		foreach ($rows as $r) {
			$out[] = array(
				"modelo" => trim($r["modelo"]),
				"nombre" => $r["nombre"],
				"marca" => $r["marca"],
				"id_marca" => (int) $r["id_marca"],
				"meses" => (int) $r["meses"],
				"meses_borrador" => (int) $r["meses_borrador"],
				"meses_publicados" => (int) $r["meses_publicados"],
				"meses_cerrados" => (int) $r["meses_cerrados"],
				"unidades_oficiales" => (int) $r["unidades_oficiales"],
				"unidades_sugeridas" => (int) $r["unidades_sugeridas"],
				"sin_lista9" => ((int) $r["sin_lista9"] === 1)
			);
		}
		return $out;
	}

	/**
	 * Modelos activos aún no proyectados en el plan.
	 */
	static public function mdlModelosPendientesPeriodo($idPeriodo, $idMarca = 0, $q = "", $limite = 800)
	{
		$idPeriodo = (int) $idPeriodo;
		$idMarca = (int) $idMarca;
		$q = trim((string) $q);
		$limite = max(1, min(1500, (int) $limite));

		$sql = "SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca
			FROM modelojf m
			LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			WHERE UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			  AND TRIM(IFNULL(m.modelo, '')) <> ''
			  AND TRIM(m.modelo) NOT IN (
				SELECT DISTINCT TRIM(l.modelo)
				FROM proyeccion_comercial_modelojf l
				WHERE l.id_periodo = :id_periodo
			  )";
		if ($idMarca > 0) {
			$sql .= " AND m.id_marca = :id_marca";
		}
		if ($q !== "") {
			$sql .= " AND (m.modelo LIKE :q1 OR m.nombre LIKE :q2)";
		}
		$sql .= " ORDER BY mk.marca ASC, m.modelo ASC LIMIT " . (int) $limite;

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":id_periodo", $idPeriodo, PDO::PARAM_INT);
		if ($idMarca > 0) {
			$stmt->bindValue(":id_marca", $idMarca, PDO::PARAM_INT);
		}
		if ($q !== "") {
			$stmt->bindValue(":q1", "%" . $q . "%", PDO::PARAM_STR);
			$stmt->bindValue(":q2", "%" . $q . "%", PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Estadísticas del plan para el panel de avance.
	 */
	static public function mdlEstadisticasPeriodo($idPeriodo)
	{
		$idPeriodo = (int) $idPeriodo;
		$pdo = Conexion::conectar();

		$totActivos = (int) $pdo->query(
			"SELECT COUNT(*) FROM modelojf
			 WHERE UPPER(TRIM(IFNULL(estado, ''))) = 'ACTIVO'
			   AND TRIM(IFNULL(modelo, '')) <> ''"
		)->fetchColumn();

		$stmt = $pdo->prepare(
			"SELECT
				COUNT(*) AS total_lineas,
				COUNT(DISTINCT TRIM(modelo)) AS modelos_proyectados,
				SUM(CASE WHEN estado_linea = 'BORRADOR' THEN 1 ELSE 0 END) AS lineas_borrador,
				SUM(CASE WHEN estado_linea = 'PUBLICADO' THEN 1 ELSE 0 END) AS lineas_publicadas,
				SUM(CASE WHEN estado_linea = 'CERRADO' THEN 1 ELSE 0 END) AS lineas_cerradas,
				COALESCE(SUM(unidades_oficiales), 0) AS unidades_oficiales,
				COALESCE(SUM(unidades_sugeridas), 0) AS unidades_sugeridas,
				COALESCE(SUM(unidades_ajustes), 0) AS unidades_ajustes,
				SUM(CASE WHEN precio_lista9_snapshot IS NULL THEN 1 ELSE 0 END) AS lineas_sin_lista9
			 FROM proyeccion_comercial_modelojf
			 WHERE id_periodo = :id"
		);
		$stmt->bindValue(":id", $idPeriodo, PDO::PARAM_INT);
		$stmt->execute();
		$r = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$r) {
			$r = array();
		}

		$modelosProy = (int) (isset($r["modelos_proyectados"]) ? $r["modelos_proyectados"] : 0);
		$pendientes = max(0, $totActivos - $modelosProy);
		$avance = $totActivos > 0 ? round(($modelosProy / $totActivos) * 100, 1) : 0.0;

		return array(
			"modelos_activos" => $totActivos,
			"modelos_proyectados" => $modelosProy,
			"modelos_pendientes" => $pendientes,
			"avance_pct" => $avance,
			"total_lineas" => (int) (isset($r["total_lineas"]) ? $r["total_lineas"] : 0),
			"lineas_borrador" => (int) (isset($r["lineas_borrador"]) ? $r["lineas_borrador"] : 0),
			"lineas_publicadas" => (int) (isset($r["lineas_publicadas"]) ? $r["lineas_publicadas"] : 0),
			"lineas_cerradas" => (int) (isset($r["lineas_cerradas"]) ? $r["lineas_cerradas"] : 0),
			"unidades_oficiales" => (int) (isset($r["unidades_oficiales"]) ? $r["unidades_oficiales"] : 0),
			"unidades_sugeridas" => (int) (isset($r["unidades_sugeridas"]) ? $r["unidades_sugeridas"] : 0),
			"unidades_ajustes" => (int) (isset($r["unidades_ajustes"]) ? $r["unidades_ajustes"] : 0),
			"lineas_sin_lista9" => (int) (isset($r["lineas_sin_lista9"]) ? $r["lineas_sin_lista9"] : 0)
		);
	}

	/**
	 * Resumen del plan para el panel derecho (meses, factores, alertas).
	 */
	static public function mdlDashboardPeriodo($idPeriodo)
	{
		$idPeriodo = (int) $idPeriodo;
		$pdo = Conexion::conectar();
		$umbralPct = self::UMBRAL_DESVIACION_PCT;
		$umbralAbs = self::UMBRAL_DESVIACION_ABS;

		$stmt = $pdo->prepare(
			"SELECT anio, mes,
				COUNT(*) AS lineas,
				COUNT(DISTINCT TRIM(modelo)) AS modelos,
				COALESCE(SUM(unidades_oficiales), 0) AS unidades_oficiales,
				COALESCE(SUM(unidades_sugeridas), 0) AS unidades_sugeridas,
				COALESCE(SUM(unidades_ajustes), 0) AS unidades_ajustes,
				SUM(CASE WHEN estado_linea = 'BORRADOR' THEN 1 ELSE 0 END) AS borrador,
				SUM(CASE WHEN estado_linea = 'PUBLICADO' THEN 1 ELSE 0 END) AS publicado,
				SUM(CASE WHEN estado_linea = 'CERRADO' THEN 1 ELSE 0 END) AS cerrado
			 FROM proyeccion_comercial_modelojf
			 WHERE id_periodo = :id
			 GROUP BY anio, mes
			 ORDER BY anio ASC, mes ASC"
		);
		$stmt->bindValue(":id", $idPeriodo, PDO::PARAM_INT);
		$stmt->execute();
		$meses = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmt = $pdo->prepare(
			"SELECT
				SUM(CASE WHEN ABS(unidades_oficiales - (unidades_sugeridas + unidades_ajustes)) > :abs
					AND (ABS(unidades_oficiales - (unidades_sugeridas + unidades_ajustes))
						/ GREATEST(1, ABS(unidades_sugeridas + unidades_ajustes))) * 100 > :pct
					THEN 1 ELSE 0 END) AS lineas_desvio
			 FROM proyeccion_comercial_modelojf
			 WHERE id_periodo = :id"
		);
		$stmt->bindValue(":id", $idPeriodo, PDO::PARAM_INT);
		$stmt->bindValue(":abs", $umbralAbs, PDO::PARAM_INT);
		$stmt->bindValue(":pct", $umbralPct);
		$stmt->execute();
		$alertas = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$alertas) {
			$alertas = array();
		}

		$modelosCero = 0;
		$stmt = $pdo->prepare(
			"SELECT COUNT(*) FROM (
				SELECT TRIM(modelo) AS modelo
				 FROM proyeccion_comercial_modelojf
				 WHERE id_periodo = :id
				 GROUP BY TRIM(modelo)
				 HAVING SUM(unidades_oficiales) = 0
			 ) t"
		);
		$stmt->bindValue(":id", $idPeriodo, PDO::PARAM_INT);
		$stmt->execute();
		$modelosCero = (int) $stmt->fetchColumn();

		$factores = array(
			"aplicados" => 0,
			"modelos" => 0,
			"ajuste" => 0
		);
		try {
			$stmt = $pdo->prepare(
				"SELECT COUNT(*) AS aplicados,
					COUNT(DISTINCT TRIM(l.modelo)) AS modelos,
					COALESCE(SUM(f.ajuste_unidades), 0) AS ajuste
				 FROM proyeccion_comercial_factorjf f
				 INNER JOIN proyeccion_comercial_modelojf l ON l.id = f.id_proyeccion_modelo
				 WHERE l.id_periodo = :id AND f.activo = 1"
			);
			$stmt->bindValue(":id", $idPeriodo, PDO::PARAM_INT);
			$stmt->execute();
			$f = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($f) {
				$factores["aplicados"] = (int) $f["aplicados"];
				$factores["modelos"] = (int) $f["modelos"];
				$factores["ajuste"] = (int) $f["ajuste"];
			}
		} catch (Exception $e) {
			// tabla de factores aún no creada
		}

		$catalogo = 0;
		try {
			$catalogo = (int) $pdo->query(
				"SELECT COUNT(*) FROM proyeccion_comercial_factor_catalogojf WHERE activo = 1"
			)->fetchColumn();
		} catch (Exception $e) {
			$catalogo = 0;
		}

		return array(
			"meses" => $meses ? $meses : array(),
			"factores" => $factores,
			"catalogo_activos" => $catalogo,
			"lineas_desvio" => (int) (isset($alertas["lineas_desvio"]) ? $alertas["lineas_desvio"] : 0),
			"modelos_cero" => $modelosCero,
			"umbral_pct" => $umbralPct,
			"formula" => self::FORMULA_VERSION
		);
	}

	static public function mdlObtenerPeriodo($idPeriodo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM proyeccion_comercial_periodojf WHERE id = :id LIMIT 1"
		);
		$stmt->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		return $fila ? $fila : null;
	}

	static public function mdlPeriodoDesdeFila($fila)
	{
		if (!$fila) {
			return null;
		}
		return self::mdlConstruirPeriodoPlan(
			sprintf("%04d-%02d", (int) $fila["anio_desde"], (int) $fila["mes_desde"]),
			sprintf("%04d-%02d", (int) $fila["anio_hasta"], (int) $fila["mes_hasta"])
		);
	}

	static private function mdlInsertarAuditoria($pdo, $idLinea, $accion, $campo, $anterior, $nuevo, $motivo, $usuario)
	{
		$stmt = $pdo->prepare(
			"INSERT INTO proyeccion_comercial_auditoriajf
				(id_proyeccion_modelo, accion, campo, valor_anterior, valor_nuevo, motivo, usuario, fecha)
			 VALUES
				(:id_linea, :accion, :campo, :anterior, :nuevo, :motivo, :usuario, NOW())"
		);
		$stmt->bindValue(":id_linea", (int) $idLinea, PDO::PARAM_INT);
		$stmt->bindValue(":accion", $accion, PDO::PARAM_STR);
		$stmt->bindValue(":campo", $campo, $campo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->bindValue(":anterior", $anterior === null ? null : (string) $anterior, $anterior === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->bindValue(":nuevo", $nuevo === null ? null : (string) $nuevo, $nuevo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->bindValue(":motivo", $motivo === null || $motivo === "" ? null : (string) $motivo, ($motivo === null || $motivo === "") ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
		$stmt->execute();
	}

	static public function mdlRecalcularEstadoCabecera($pdo, $idPeriodo, $usuario = 0)
	{
		$stmt = $pdo->prepare(
			"SELECT
				SUM(CASE WHEN estado_linea = 'BORRADOR' THEN 1 ELSE 0 END) AS borrador,
				SUM(CASE WHEN estado_linea = 'PUBLICADO' THEN 1 ELSE 0 END) AS publicado,
				SUM(CASE WHEN estado_linea = 'CERRADO' THEN 1 ELSE 0 END) AS cerrado,
				COUNT(*) AS total
			 FROM proyeccion_comercial_modelojf
			 WHERE id_periodo = :id"
		);
		$stmt->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
		$stmt->execute();
		$c = $stmt->fetch(PDO::FETCH_ASSOC);
		$total = (int) $c["total"];
		$borrador = (int) $c["borrador"];
		$publicado = (int) $c["publicado"];
		$cerrado = (int) $c["cerrado"];

		if ($total === 0 || ($borrador === $total)) {
			$estado = "BORRADOR";
		} elseif ($cerrado === $total) {
			$estado = "CERRADO";
		} elseif ($borrador === 0 && ($publicado + $cerrado) === $total) {
			$estado = "PUBLICADO";
		} else {
			$estado = "PARCIAL";
		}

		$upd = $pdo->prepare(
			"UPDATE proyeccion_comercial_periodojf
			 SET estado = :estado,
				 actualizado_por = :usuario,
				 actualizado_en = NOW()
			 WHERE id = :id"
		);
		$upd->bindValue(":estado", $estado, PDO::PARAM_STR);
		$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
		$upd->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
		$upd->execute();
		return $estado;
	}

	static public function mdlCrearPeriodo($desdeYm, $hastaYm, $nombre, $usuario)
	{
		$periodo = self::mdlConstruirPeriodoPlan($desdeYm, $hastaYm);
		if ($periodo === null) {
			return array("ok" => false, "mensaje" => "Rango de plan inválido");
		}
		$nombre = trim((string) $nombre);
		if ($nombre === "") {
			$nombre = "Plan " . $periodo["desde"] . " a " . $periodo["hasta"];
		}
		if (strlen($nombre) > 120) {
			return array("ok" => false, "mensaje" => "Nombre demasiado largo");
		}

		$pdo = Conexion::conectar();
		$stmt = $pdo->prepare(
			"INSERT INTO proyeccion_comercial_periodojf
				(anio_desde, mes_desde, anio_hasta, mes_hasta, nombre, estado, creado_por, creado_en)
			 VALUES
				(:ad, :md, :ah, :mh, :nombre, 'BORRADOR', :usuario, NOW())"
		);
		$stmt->bindValue(":ad", (int) $periodo["desde_anio"], PDO::PARAM_INT);
		$stmt->bindValue(":md", (int) $periodo["desde_mes"], PDO::PARAM_INT);
		$stmt->bindValue(":ah", (int) $periodo["hasta_anio"], PDO::PARAM_INT);
		$stmt->bindValue(":mh", (int) $periodo["hasta_mes"], PDO::PARAM_INT);
		$stmt->bindValue(":nombre", $nombre, PDO::PARAM_STR);
		$stmt->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
		$stmt->execute();
		$id = (int) $pdo->lastInsertId();
		return array(
			"ok" => true,
			"id_periodo" => $id,
			"periodo" => self::mdlObtenerPeriodo($id)
		);
	}

	/**
	 * Genera/actualiza líneas en borrador desde sugerencias para el filtro dado.
	 */
	static public function mdlGenerarLineasPeriodo($idPeriodo, $idMarca, $q, $usuario, $maxModelos = 40)
	{
		$cab = self::mdlObtenerPeriodo($idPeriodo);
		if (!$cab) {
			return array("ok" => false, "mensaje" => "Plan no encontrado");
		}
		if ($cab["estado"] === "CERRADO") {
			return array("ok" => false, "mensaje" => "El plan está cerrado");
		}
		$periodoPlan = self::mdlPeriodoDesdeFila($cab);
		if ($periodoPlan === null) {
			return array("ok" => false, "mensaje" => "Rango del plan inválido");
		}

		$matriz = self::mdlMatrizContexto($periodoPlan, $idMarca, $q, 36, $maxModelos);
		$pdo = Conexion::conectar();
		$creadas = 0;
		$actualizadas = 0;
		$omitidas = 0;
		$conflictos = array();

		try {
			$pdo->beginTransaction();

			$sel = $pdo->prepare(
				"SELECT id, id_periodo, estado_linea, unidades_sugeridas, unidades_ajustes, unidades_oficiales
				 FROM proyeccion_comercial_modelojf
				 WHERE anio = :anio AND mes = :mes AND modelo = :modelo
				 LIMIT 1"
			);
			$ins = $pdo->prepare(
				"INSERT INTO proyeccion_comercial_modelojf
					(id_periodo, anio, mes, modelo, unidades_sugeridas, unidades_ajustes, unidades_oficiales,
					 formula_version, observacion, estado_linea, creado_por, creado_en)
				 VALUES
					(:id_periodo, :anio, :mes, :modelo, :sug, 0, :ofi,
					 :formula, NULL, 'BORRADOR', :usuario, NOW())"
			);
			$updBorrador = $pdo->prepare(
				"UPDATE proyeccion_comercial_modelojf
				 SET unidades_sugeridas = :sug,
					 unidades_oficiales = :ofi,
					 formula_version = :formula,
					 actualizado_por = :usuario,
					 actualizado_en = NOW()
				 WHERE id = :id AND estado_linea = 'BORRADOR' AND id_periodo = :id_periodo"
			);

			foreach ($matriz["filas"] as $fila) {
				$sug = (int) $fila["sugerencia"]["unidades"];
				$sel->bindValue(":anio", (int) $fila["anio"], PDO::PARAM_INT);
				$sel->bindValue(":mes", (int) $fila["mes"], PDO::PARAM_INT);
				$sel->bindValue(":modelo", $fila["modelo"], PDO::PARAM_STR);
				$sel->execute();
				$existente = $sel->fetch(PDO::FETCH_ASSOC);

				if ($existente) {
					if ((int) $existente["id_periodo"] !== (int) $idPeriodo) {
						$omitidas++;
						if (count($conflictos) < 20) {
							$conflictos[] = $fila["modelo"] . " " . $fila["periodo"] . " (otro plan #" . $existente["id_periodo"] . ", " . $existente["estado_linea"] . ")";
						}
						continue;
					}
					if ($existente["estado_linea"] !== "BORRADOR") {
						$omitidas++;
						continue;
					}
					// Recalcula sugerencia; solo mueve oficial si seguía la base automática (sug o sug+factores).
					$ofiAnterior = (int) $existente["unidades_oficiales"];
					$sugAnterior = (int) $existente["unidades_sugeridas"];
					$aj = (int) $existente["unidades_ajustes"];
					$baseAnt = max(0, $sugAnterior + $aj);
					$ofiAutoAnt = (int) max(0, round($baseAnt / 10) * 10);
					$baseNueva = max(0, $sug + $aj);
					$ofiAutoNueva = (int) max(0, round($baseNueva / 10) * 10);
					$ofiNueva = ($ofiAnterior === $sugAnterior
						|| $ofiAnterior === $baseAnt
						|| $ofiAnterior === $ofiAutoAnt)
						? $ofiAutoNueva
						: $ofiAnterior;
					$updBorrador->bindValue(":sug", $sug, PDO::PARAM_INT);
					$updBorrador->bindValue(":ofi", $ofiNueva, PDO::PARAM_INT);
					$updBorrador->bindValue(":formula", self::FORMULA_VERSION, PDO::PARAM_STR);
					$updBorrador->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
					$updBorrador->bindValue(":id", (int) $existente["id"], PDO::PARAM_INT);
					$updBorrador->bindValue(":id_periodo", (int) $idPeriodo, PDO::PARAM_INT);
					$updBorrador->execute();
					self::mdlInsertarAuditoria(
						$pdo,
						(int) $existente["id"],
						"ACTUALIZAR",
						"unidades_sugeridas",
						$sugAnterior,
						$sug,
						"Recálculo estacional de sugerencia en borrador",
						$usuario
					);
					$actualizadas++;
					continue;
				}

				$ins->bindValue(":id_periodo", (int) $idPeriodo, PDO::PARAM_INT);
				$ins->bindValue(":anio", (int) $fila["anio"], PDO::PARAM_INT);
				$ins->bindValue(":mes", (int) $fila["mes"], PDO::PARAM_INT);
				$ins->bindValue(":modelo", $fila["modelo"], PDO::PARAM_STR);
				$ins->bindValue(":sug", $sug, PDO::PARAM_INT);
				$ins->bindValue(":ofi", $sug, PDO::PARAM_INT);
				$ins->bindValue(":formula", self::FORMULA_VERSION, PDO::PARAM_STR);
				$ins->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
				$ins->execute();
				$idLinea = (int) $pdo->lastInsertId();
				self::mdlInsertarAuditoria(
					$pdo,
					$idLinea,
					"CREAR",
					"unidades_oficiales",
					null,
					$sug,
					"Alta de línea en borrador con sugerencia",
					$usuario
				);
				$creadas++;
			}

			$estado = self::mdlRecalcularEstadoCabecera($pdo, $idPeriodo, $usuario);
			$pdo->commit();
			return array(
				"ok" => true,
				"creadas" => $creadas,
				"actualizadas" => $actualizadas,
				"omitidas" => $omitidas,
				"conflictos" => $conflictos,
				"truncado" => !empty($matriz["truncado"]),
				"total_modelos_filtro" => $matriz["total_modelos_filtro"],
				"estado_periodo" => $estado
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudieron generar las líneas");
		}
	}

	static public function mdlListarLineasPeriodo($idPeriodo, $anio = 0, $mes = 0, $q = "")
	{
		$sql = "SELECT l.*,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(l.modelo)) AS nombre,
				IFNULL(mk.marca, '') AS marca,
				IFNULL(m.id_marca, 0) AS id_marca
			FROM proyeccion_comercial_modelojf l
			LEFT JOIN modelojf m ON TRIM(m.modelo) = TRIM(l.modelo)
			LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			WHERE l.id_periodo = :id";
		if ((int) $anio > 0) {
			$sql .= " AND l.anio = :anio";
		}
		if ((int) $mes > 0) {
			$sql .= " AND l.mes = :mes";
		}
		$q = trim((string) $q);
		if ($q !== "") {
			$sql .= " AND (l.modelo LIKE :q1 OR m.nombre LIKE :q2)";
		}
		$sql .= " ORDER BY l.anio, l.mes, l.modelo";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
		if ((int) $anio > 0) {
			$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		}
		if ((int) $mes > 0) {
			$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		}
		if ($q !== "") {
			$stmt->bindValue(":q1", "%" . $q . "%", PDO::PARAM_STR);
			$stmt->bindValue(":q2", "%" . $q . "%", PDO::PARAM_STR);
		}
		$stmt->execute();
		$lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (empty($lineas)) {
			return array();
		}

		$idsLinea = array();
		$modelos = array();
		foreach ($lineas as $l) {
			$idsLinea[] = (int) $l["id"];
			$modelos[] = trim($l["modelo"]);
		}

		$factoresMap = array();
		$marcadores = array();
		foreach ($idsLinea as $i => $idL) {
			$marcadores[] = ":lid_" . $i;
		}
		$fc = Conexion::conectar()->prepare(
			"SELECT id_proyeccion_modelo,
				COUNT(*) AS n_factores,
				COALESCE(SUM(ajuste_unidades), 0) AS suma_ajustes
			 FROM proyeccion_comercial_factorjf
			 WHERE activo = 1 AND id_proyeccion_modelo IN (" . implode(", ", $marcadores) . ")
			 GROUP BY id_proyeccion_modelo"
		);
		foreach ($idsLinea as $i => $idL) {
			$fc->bindValue(":lid_" . $i, $idL, PDO::PARAM_INT);
		}
		$fc->execute();
		foreach ($fc->fetchAll(PDO::FETCH_ASSOC) as $fr) {
			$factoresMap[(int) $fr["id_proyeccion_modelo"]] = array(
				"n_factores" => (int) $fr["n_factores"],
				"suma_ajustes" => (int) $fr["suma_ajustes"]
			);
		}

		$inv = self::mdlInventarioPorModelos($modelos);
		$precios = self::mdlPreciosLista9($modelos);
		$out = array();
		foreach ($lineas as $l) {
			$modelo = trim($l["modelo"]);
			$invM = isset($inv[$modelo]) ? $inv[$modelo] : self::mdlInventarioVacio();
			$precioVigente = array_key_exists($modelo, $precios) ? $precios[$modelo] : null;
			$precioSnap = $l["precio_lista9_snapshot"] === null ? null : (float) $l["precio_lista9_snapshot"];
			$ofi = (int) $l["unidades_oficiales"];
			$idL = (int) $l["id"];
			$fcInfo = isset($factoresMap[$idL]) ? $factoresMap[$idL] : array("n_factores" => 0, "suma_ajustes" => 0);
			$out[] = array(
				"id" => $idL,
				"id_periodo" => (int) $l["id_periodo"],
				"anio" => (int) $l["anio"],
				"mes" => (int) $l["mes"],
				"periodo" => sprintf("%04d-%02d", (int) $l["anio"], (int) $l["mes"]),
				"modelo" => $modelo,
				"nombre" => $l["nombre"],
				"marca" => $l["marca"],
				"unidades_sugeridas" => (int) $l["unidades_sugeridas"],
				"unidades_ajustes" => (int) $l["unidades_ajustes"],
				"unidades_oficiales" => $ofi,
				"n_factores" => $fcInfo["n_factores"],
				"precio_lista9_snapshot" => $precioSnap,
				"precio_lista9_vigente" => $precioVigente,
				"sin_lista9" => ($precioSnap === null && $precioVigente === null),
				"importe_lista9_proyectado" => $l["importe_lista9_proyectado"] === null ? null : (float) $l["importe_lista9_proyectado"],
				"formula_version" => $l["formula_version"],
				"observacion" => $l["observacion"],
				"estado_linea" => $l["estado_linea"],
				"inventario" => $invM,
				"brecha_referencial" => $ofi - (float) $invM["stock_disponible"] - (float) $invM["en_proceso"],
				"publicado_en" => $l["publicado_en"],
				"actualizado_en" => $l["actualizado_en"]
			);
		}
		return $out;
	}

	/**
	 * Guarda unidades oficiales de líneas en borrador (o publicadas con motivo).
	 * $cambios: array of {id, unidades_oficiales, observacion?}
	 */
	static public function mdlGuardarLineas($idPeriodo, $cambios, $usuario, $motivo = "")
	{
		$cab = self::mdlObtenerPeriodo($idPeriodo);
		if (!$cab) {
			return array("ok" => false, "mensaje" => "Plan no encontrado");
		}
		if ($cab["estado"] === "CERRADO") {
			return array("ok" => false, "mensaje" => "El plan está cerrado");
		}
		if (!is_array($cambios) || empty($cambios)) {
			return array("ok" => false, "mensaje" => "Sin cambios");
		}

		$pdo = Conexion::conectar();
		$guardadas = 0;
		try {
			$pdo->beginTransaction();
			$sel = $pdo->prepare(
				"SELECT * FROM proyeccion_comercial_modelojf
				 WHERE id = :id AND id_periodo = :id_periodo LIMIT 1"
			);
			$upd = $pdo->prepare(
				"UPDATE proyeccion_comercial_modelojf
				 SET unidades_oficiales = :ofi,
					 observacion = :obs,
					 actualizado_por = :usuario,
					 actualizado_en = NOW()
				 WHERE id = :id"
			);

			foreach ($cambios as $cambio) {
				$id = isset($cambio["id"]) ? (int) $cambio["id"] : 0;
				if ($id <= 0) {
					continue;
				}
				if (!isset($cambio["unidades_oficiales"]) || !is_numeric($cambio["unidades_oficiales"])) {
					throw new Exception("Unidades inválidas");
				}
				$ofi = (int) $cambio["unidades_oficiales"];
				if ($ofi < 0) {
					throw new Exception("Las unidades no pueden ser negativas");
				}
				$sel->bindValue(":id", $id, PDO::PARAM_INT);
				$sel->bindValue(":id_periodo", (int) $idPeriodo, PDO::PARAM_INT);
				$sel->execute();
				$linea = $sel->fetch(PDO::FETCH_ASSOC);
				if (!$linea) {
					throw new Exception("Línea no encontrada");
				}
				if ($linea["estado_linea"] === "CERRADO") {
					throw new Exception("No se puede editar una línea cerrada");
				}
				if ($linea["estado_linea"] === "PUBLICADO") {
					$motivo = trim((string) $motivo);
					if ($motivo === "") {
						throw new Exception("Motivo obligatorio para corregir líneas publicadas");
					}
				}
				$obs = isset($cambio["observacion"]) ? trim((string) $cambio["observacion"]) : $linea["observacion"];
				if ($obs !== null && strlen($obs) > 500) {
					throw new Exception("Observación demasiado larga");
				}

				if ((int) $linea["unidades_oficiales"] === $ofi && (string) $linea["observacion"] === (string) $obs) {
					continue;
				}

				if (self::mdlDesviacionRelevante(
					(int) $linea["unidades_sugeridas"],
					(int) $linea["unidades_ajustes"],
					$ofi
				)) {
					$tieneFactor = self::mdlContarFactoresActivosLinea($pdo, $id) > 0;
					$tieneObs = ($obs !== null && trim((string) $obs) !== "");
					if (!$tieneFactor && !$tieneObs) {
						throw new Exception(
							"Desviación relevante en " . $linea["modelo"] . " " .
							sprintf("%04d-%02d", (int) $linea["anio"], (int) $linea["mes"]) .
							": elige un motivo en la tabla o agrega un factor."
						);
					}
				}

				$upd->bindValue(":ofi", $ofi, PDO::PARAM_INT);
				$upd->bindValue(":obs", $obs === null || $obs === "" ? null : $obs, ($obs === null || $obs === "") ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
				$upd->bindValue(":id", $id, PDO::PARAM_INT);
				$upd->execute();

				if ($linea["estado_linea"] === "PUBLICADO") {
					$precio = $linea["precio_lista9_snapshot"] === null ? null : (float) $linea["precio_lista9_snapshot"];
					if ($precio !== null) {
						$imp = $pdo->prepare(
							"UPDATE proyeccion_comercial_modelojf
							 SET importe_lista9_proyectado = :imp
							 WHERE id = :id"
						);
						$imp->bindValue(":imp", number_format($ofi * $precio, 4, ".", ""), PDO::PARAM_STR);
						$imp->bindValue(":id", $id, PDO::PARAM_INT);
						$imp->execute();
					}
				}

				self::mdlInsertarAuditoria(
					$pdo,
					$id,
					"ACTUALIZAR",
					"unidades_oficiales",
					$linea["unidades_oficiales"],
					$ofi,
					$linea["estado_linea"] === "PUBLICADO" ? $motivo : "Guardar borrador",
					$usuario
				);
				$guardadas++;
			}

			self::mdlRecalcularEstadoCabecera($pdo, $idPeriodo, $usuario);
			$pdo->commit();
			return array("ok" => true, "guardadas" => $guardadas);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => $e->getMessage());
		}
	}

	/**
	 * Publica líneas en borrador del plan. $anio/$mes opcionales (0 = todas del plan).
	 * $modelo opcional: solo ese modelo.
	 */
	static public function mdlPublicarLineas($idPeriodo, $anio, $mes, $usuario, $modelo = "")
	{
		$cab = self::mdlObtenerPeriodo($idPeriodo);
		if (!$cab) {
			return array("ok" => false, "mensaje" => "Plan no encontrado");
		}
		if ($cab["estado"] === "CERRADO") {
			return array("ok" => false, "mensaje" => "El plan está cerrado");
		}

		$sql = "SELECT * FROM proyeccion_comercial_modelojf
			WHERE id_periodo = :id AND estado_linea = 'BORRADOR'";
		if ((int) $anio > 0) {
			$sql .= " AND anio = :anio";
		}
		if ((int) $mes > 0) {
			$sql .= " AND mes = :mes";
		}
		$modelo = trim((string) $modelo);
		if ($modelo !== "") {
			$sql .= " AND TRIM(modelo) = :modelo";
		}
		$pdo = Conexion::conectar();
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
		if ((int) $anio > 0) {
			$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		}
		if ((int) $mes > 0) {
			$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		}
		if ($modelo !== "") {
			$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		}
		$stmt->execute();
		$lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (empty($lineas)) {
			return array("ok" => false, "mensaje" => "No hay líneas en borrador para publicar");
		}

		$modelos = array();
		foreach ($lineas as $l) {
			$modelos[] = trim($l["modelo"]);
		}
		$precios = self::mdlPreciosLista9($modelos);
		$pendientesPrecio = array();
		foreach ($lineas as $l) {
			$m = trim($l["modelo"]);
			if (!isset($precios[$m]) || $precios[$m] === null) {
				$pendientesPrecio[] = $m . " " . sprintf("%04d-%02d", (int) $l["anio"], (int) $l["mes"]);
			}
		}

		$publicadas = 0;
		$omitidasSinPrecio = 0;
		try {
			$pdo->beginTransaction();
			$upd = $pdo->prepare(
				"UPDATE proyeccion_comercial_modelojf
				 SET estado_linea = 'PUBLICADO',
					 precio_lista9_snapshot = :precio,
					 importe_lista9_proyectado = :importe,
					 publicado_por = :usuario,
					 publicado_en = NOW(),
					 actualizado_por = :usuario2,
					 actualizado_en = NOW()
				 WHERE id = :id AND estado_linea = 'BORRADOR'"
			);

			foreach ($lineas as $l) {
				$m = trim($l["modelo"]);
				if (!isset($precios[$m]) || $precios[$m] === null) {
					$omitidasSinPrecio++;
					continue;
				}
				$precio = (float) $precios[$m];
				$ofi = (int) $l["unidades_oficiales"];
				$importe = $ofi * $precio;
				$upd->bindValue(":precio", number_format($precio, 4, ".", ""), PDO::PARAM_STR);
				$upd->bindValue(":importe", number_format($importe, 4, ".", ""), PDO::PARAM_STR);
				$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
				$upd->bindValue(":usuario2", (int) $usuario, PDO::PARAM_INT);
				$upd->bindValue(":id", (int) $l["id"], PDO::PARAM_INT);
				$upd->execute();
				if ($upd->rowCount() > 0) {
					self::mdlInsertarAuditoria(
						$pdo,
						(int) $l["id"],
						"PUBLICAR",
						"estado_linea",
						"BORRADOR",
						"PUBLICADO",
						"Publicación; lista 9 congelada",
						$usuario
					);
					$publicadas++;
				}
			}

			if ($publicadas > 0) {
				$pubCab = $pdo->prepare(
					"UPDATE proyeccion_comercial_periodojf
					 SET publicado_por = COALESCE(publicado_por, :usuario),
						 publicado_en = COALESCE(publicado_en, NOW()),
						 actualizado_por = :usuario2,
						 actualizado_en = NOW()
					 WHERE id = :id"
				);
				$pubCab->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
				$pubCab->bindValue(":usuario2", (int) $usuario, PDO::PARAM_INT);
				$pubCab->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
				$pubCab->execute();
			}

			$estado = self::mdlRecalcularEstadoCabecera($pdo, $idPeriodo, $usuario);
			$pdo->commit();

			$mensaje = "Publicadas: {$publicadas}.";
			if ($omitidasSinPrecio > 0) {
				$mensaje .= " Omitidas sin lista 9: {$omitidasSinPrecio}.";
			}
			return array(
				"ok" => true,
				"publicadas" => $publicadas,
				"omitidas_sin_lista9" => $omitidasSinPrecio,
				"pendientes_lista9" => array_slice($pendientesPrecio, 0, 30),
				"estado_periodo" => $estado,
				"mensaje" => $mensaje
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudo publicar");
		}
	}

	static public function mdlAuditoriaLinea($idLinea, $limite = 50)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM proyeccion_comercial_auditoriajf
			 WHERE id_proyeccion_modelo = :id
			 ORDER BY id DESC
			 LIMIT :limite"
		);
		$stmt->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
		$stmt->bindValue(":limite", (int) $limite, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlConsultaOficial($desdeYm, $hastaYm, $q = "", $limite = 500)
	{
		$periodo = self::mdlConstruirPeriodoPlan($desdeYm, $hastaYm);
		if ($periodo === null) {
			return array("ok" => false, "mensaje" => "Rango inválido", "filas" => array());
		}
		$sql = "SELECT l.*,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(l.modelo)) AS nombre,
				IFNULL(mk.marca, '') AS marca
			FROM proyeccion_comercial_modelojf l
			LEFT JOIN modelojf m ON TRIM(m.modelo) = TRIM(l.modelo)
			LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			WHERE l.estado_linea IN ('PUBLICADO', 'CERRADO')
			  AND (l.anio * 100 + l.mes) >= :desde_clave
			  AND (l.anio * 100 + l.mes) <= :hasta_clave";
		$q = trim((string) $q);
		if ($q !== "") {
			$sql .= " AND (l.modelo LIKE :q1 OR m.nombre LIKE :q2)";
		}
		$sql .= " ORDER BY l.anio, l.mes, l.modelo LIMIT :limite";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":desde_clave", (int) $periodo["desde_anio"] * 100 + (int) $periodo["desde_mes"], PDO::PARAM_INT);
		$stmt->bindValue(":hasta_clave", (int) $periodo["hasta_anio"] * 100 + (int) $periodo["hasta_mes"], PDO::PARAM_INT);
		if ($q !== "") {
			$stmt->bindValue(":q1", "%" . $q . "%", PDO::PARAM_STR);
			$stmt->bindValue(":q2", "%" . $q . "%", PDO::PARAM_STR);
		}
		$stmt->bindValue(":limite", (int) $limite, PDO::PARAM_INT);
		$stmt->execute();
		return array("ok" => true, "filas" => $stmt->fetchAll(PDO::FETCH_ASSOC), "periodo" => $periodo);
	}

	/**
	 * Elimina un plan solo si no tiene líneas PUBLICADO/CERRADO.
	 */
	static public function mdlEliminarPeriodo($idPeriodo, $usuario)
	{
		$cab = self::mdlObtenerPeriodo($idPeriodo);
		if (!$cab) {
			return array("ok" => false, "mensaje" => "Plan no encontrado");
		}

		$pdo = Conexion::conectar();
		try {
			$pdo->beginTransaction();
			$chk = $pdo->prepare(
				"SELECT COUNT(*) FROM proyeccion_comercial_modelojf
				 WHERE id_periodo = :id AND estado_linea IN ('PUBLICADO', 'CERRADO')"
			);
			$chk->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
			$chk->execute();
			if ((int) $chk->fetchColumn() > 0) {
				$pdo->rollBack();
				return array(
					"ok" => false,
					"mensaje" => "No se puede eliminar: el plan tiene líneas publicadas o cerradas."
				);
			}

			$idsStmt = $pdo->prepare(
				"SELECT id FROM proyeccion_comercial_modelojf WHERE id_periodo = :id"
			);
			$idsStmt->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
			$idsStmt->execute();
			$ids = $idsStmt->fetchAll(PDO::FETCH_COLUMN);

			if (!empty($ids)) {
				$marcadores = array();
				foreach ($ids as $i => $idLinea) {
					$marcadores[] = ":lid_" . $i;
				}
				$in = implode(", ", $marcadores);

				$delAud = $pdo->prepare(
					"DELETE FROM proyeccion_comercial_auditoriajf WHERE id_proyeccion_modelo IN ({$in})"
				);
				$delFac = $pdo->prepare(
					"DELETE FROM proyeccion_comercial_factorjf WHERE id_proyeccion_modelo IN ({$in})"
				);
				foreach ($ids as $i => $idLinea) {
					$delAud->bindValue(":lid_" . $i, (int) $idLinea, PDO::PARAM_INT);
					$delFac->bindValue(":lid_" . $i, (int) $idLinea, PDO::PARAM_INT);
				}
				$delAud->execute();
				$delFac->execute();

				$delLin = $pdo->prepare(
					"DELETE FROM proyeccion_comercial_modelojf WHERE id_periodo = :id"
				);
				$delLin->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
				$delLin->execute();
			}

			$delPer = $pdo->prepare(
				"DELETE FROM proyeccion_comercial_periodojf WHERE id = :id"
			);
			$delPer->bindValue(":id", (int) $idPeriodo, PDO::PARAM_INT);
			$delPer->execute();

			$pdo->commit();
			return array(
				"ok" => true,
				"mensaje" => "Plan #" . (int) $idPeriodo . " eliminado.",
				"eliminado_por" => (int) $usuario,
				"lineas_eliminadas" => count($ids)
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudo eliminar el plan");
		}
	}

	/* =========================
	 * Catálogo de factores
	 * ========================= */

	static public function mdlListarCatalogoFactores($soloActivos = true)
	{
		$sql = "SELECT * FROM proyeccion_comercial_factor_catalogojf";
		if ($soloActivos) {
			$sql .= " WHERE activo = 1";
		}
		$sql .= " ORDER BY fecha_desde IS NULL, fecha_desde DESC, titulo ASC";
		try {
			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			return array();
		}
	}

	static public function mdlGuardarCatalogoFactor($datos, $usuario)
	{
		$id = isset($datos["id"]) ? (int) $datos["id"] : 0;
		$tipo = isset($datos["tipo"]) ? trim((string) $datos["tipo"]) : "";
		$titulo = isset($datos["titulo"]) ? trim((string) $datos["titulo"]) : "";
		$descripcion = isset($datos["descripcion"]) ? trim((string) $datos["descripcion"]) : "";
		if (!self::mdlTipoFactorValido($tipo)) {
			return array("ok" => false, "mensaje" => "Tipo inválido");
		}
		if ($titulo === "" || strlen($titulo) > 120) {
			return array("ok" => false, "mensaje" => "Título obligatorio");
		}
		if ($tipo === "OTRO" && $descripcion === "") {
			return array("ok" => false, "mensaje" => "“Otro” exige descripción");
		}
		$ajuste = isset($datos["ajuste_unidades_default"]) && is_numeric($datos["ajuste_unidades_default"])
			? (int) $datos["ajuste_unidades_default"] : 0;
		$pct = isset($datos["impacto_pct_default"]) && $datos["impacto_pct_default"] !== "" && is_numeric($datos["impacto_pct_default"])
			? (float) $datos["impacto_pct_default"] : null;
		$fd = !empty($datos["fecha_desde"]) ? $datos["fecha_desde"] : null;
		$fh = !empty($datos["fecha_hasta"]) ? $datos["fecha_hasta"] : null;
		$canal = isset($datos["canal_publicidad"]) ? trim((string) $datos["canal_publicidad"]) : null;
		$inv = isset($datos["inversion_publicidad"]) && $datos["inversion_publicidad"] !== "" && is_numeric($datos["inversion_publicidad"])
			? (float) $datos["inversion_publicidad"] : null;
		$ref = isset($datos["referencia_evidencia"]) ? trim((string) $datos["referencia_evidencia"]) : null;

		$pdo = Conexion::conectar();
		try {
			if ($id > 0) {
				$stmt = $pdo->prepare(
					"UPDATE proyeccion_comercial_factor_catalogojf
					 SET tipo=:tipo, titulo=:titulo, descripcion=:descripcion,
						 fecha_desde=:fd, fecha_hasta=:fh,
						 ajuste_unidades_default=:aj, impacto_pct_default=:pct,
						 canal_publicidad=:canal, inversion_publicidad=:inv,
						 referencia_evidencia=:ref,
						 actualizado_por=:usuario, actualizado_en=NOW()
					 WHERE id=:id"
				);
				$stmt->bindValue(":id", $id, PDO::PARAM_INT);
			} else {
				$stmt = $pdo->prepare(
					"INSERT INTO proyeccion_comercial_factor_catalogojf
						(tipo, titulo, descripcion, fecha_desde, fecha_hasta,
						 ajuste_unidades_default, impacto_pct_default,
						 canal_publicidad, inversion_publicidad, referencia_evidencia,
						 activo, creado_por, creado_en)
					 VALUES
						(:tipo, :titulo, :descripcion, :fd, :fh,
						 :aj, :pct, :canal, :inv, :ref,
						 1, :usuario, NOW())"
				);
			}
			$stmt->bindValue(":tipo", $tipo, PDO::PARAM_STR);
			$stmt->bindValue(":titulo", $titulo, PDO::PARAM_STR);
			$stmt->bindValue(":descripcion", $descripcion === "" ? null : $descripcion, $descripcion === "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$stmt->bindValue(":fd", $fd, $fd === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$stmt->bindValue(":fh", $fh, $fh === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$stmt->bindValue(":aj", $ajuste, PDO::PARAM_INT);
			$stmt->bindValue(":pct", $pct === null ? null : number_format($pct, 4, ".", ""), $pct === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$stmt->bindValue(":canal", ($canal === null || $canal === "") ? null : $canal, ($canal === null || $canal === "") ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$stmt->bindValue(":inv", $inv === null ? null : number_format($inv, 2, ".", ""), $inv === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$stmt->bindValue(":ref", ($ref === null || $ref === "") ? null : $ref, ($ref === null || $ref === "") ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$stmt->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
			$stmt->execute();
			if ($id <= 0) {
				$id = (int) $pdo->lastInsertId();
			}
			return array("ok" => true, "id" => $id, "catalogo" => self::mdlListarCatalogoFactores(false));
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo guardar el factor de catálogo. ¿Ejecutaste el SQL del catálogo?");
		}
	}

	static public function mdlDesactivarCatalogoFactor($id, $usuario)
	{
		try {
			$stmt = Conexion::conectar()->prepare(
				"UPDATE proyeccion_comercial_factor_catalogojf
				 SET activo = 0, actualizado_por = :usuario, actualizado_en = NOW()
				 WHERE id = :id"
			);
			$stmt->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
			$stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
			$stmt->execute();
			return array("ok" => true, "catalogo" => self::mdlListarCatalogoFactores(false));
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo desactivar");
		}
	}

	/**
	 * Catálogo con flag aplicado a una línea (check).
	 */
	static public function mdlCatalogoParaLinea($idLinea)
	{
		$linea = self::mdlObtenerLinea($idLinea);
		if (!$linea) {
			return array("ok" => false, "mensaje" => "Línea no encontrada");
		}
		$catalogo = self::mdlListarCatalogoFactores(true);
		$vinculos = array();
		try {
			$stmt = Conexion::conectar()->prepare(
				"SELECT id, id_catalogo, ajuste_unidades
				 FROM proyeccion_comercial_factorjf
				 WHERE id_proyeccion_modelo = :id AND activo = 1 AND id_catalogo IS NOT NULL"
			);
			$stmt->bindValue(":id", (int) $idLinea, PDO::PARAM_INT);
			$stmt->execute();
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $v) {
				$vinculos[(int) $v["id_catalogo"]] = array(
					"id_factor" => (int) $v["id"],
					"ajuste_unidades" => (int) $v["ajuste_unidades"]
				);
			}
		} catch (Exception $e) {
			// columna id_catalogo puede no existir aún
		}
		$items = array();
		foreach ($catalogo as $c) {
			$idC = (int) $c["id"];
			$items[] = array(
				"id" => $idC,
				"tipo" => $c["tipo"],
				"titulo" => $c["titulo"],
				"descripcion" => $c["descripcion"],
				"fecha_desde" => $c["fecha_desde"],
				"fecha_hasta" => $c["fecha_hasta"],
				"ajuste_unidades_default" => (int) $c["ajuste_unidades_default"],
				"impacto_pct_default" => $c["impacto_pct_default"] === null ? null : (float) $c["impacto_pct_default"],
				"aplicado" => isset($vinculos[$idC]),
				"id_factor" => isset($vinculos[$idC]) ? $vinculos[$idC]["id_factor"] : null,
				"ajuste_aplicado" => isset($vinculos[$idC]) ? $vinculos[$idC]["ajuste_unidades"] : (int) $c["ajuste_unidades_default"]
			);
		}
		return array(
			"ok" => true,
			"linea" => array(
				"id" => (int) $linea["id"],
				"modelo" => $linea["modelo"],
				"periodo" => sprintf("%04d-%02d", (int) $linea["anio"], (int) $linea["mes"]),
				"unidades_sugeridas" => (int) $linea["unidades_sugeridas"],
				"unidades_ajustes" => (int) $linea["unidades_ajustes"],
				"unidades_oficiales" => (int) $linea["unidades_oficiales"],
				"estado_linea" => $linea["estado_linea"]
			),
			"items" => $items,
			"tipos" => self::mdlTiposFactor()
		);
	}

	/**
	 * Activa/desactiva un factor de catálogo en una línea (check).
	 */
	static public function mdlToggleCatalogoEnLinea($idLinea, $idCatalogo, $aplicar, $usuario, $motivo = "", $ajusteOverride = null)
	{
		$linea = self::mdlObtenerLinea($idLinea);
		if (!$linea) {
			return array("ok" => false, "mensaje" => "Línea no encontrada");
		}
		if ($linea["estado_linea"] === "CERRADO") {
			return array("ok" => false, "mensaje" => "Línea cerrada");
		}
		if ($linea["estado_linea"] === "PUBLICADO" && trim((string) $motivo) === "") {
			return array("ok" => false, "mensaje" => "Motivo obligatorio (línea publicada)");
		}

		$pdo = Conexion::conectar();
		try {
			$cat = $pdo->prepare(
				"SELECT * FROM proyeccion_comercial_factor_catalogojf WHERE id = :id AND activo = 1 LIMIT 1"
			);
			$cat->bindValue(":id", (int) $idCatalogo, PDO::PARAM_INT);
			$cat->execute();
			$c = $cat->fetch(PDO::FETCH_ASSOC);
			if (!$c) {
				return array("ok" => false, "mensaje" => "Factor de catálogo no encontrado");
			}

			$pdo->beginTransaction();
			$sel = $pdo->prepare(
				"SELECT id FROM proyeccion_comercial_factorjf
				 WHERE id_proyeccion_modelo = :linea AND id_catalogo = :cat AND activo = 1
				 LIMIT 1"
			);
			$sel->bindValue(":linea", (int) $idLinea, PDO::PARAM_INT);
			$sel->bindValue(":cat", (int) $idCatalogo, PDO::PARAM_INT);
			$sel->execute();
			$existente = $sel->fetch(PDO::FETCH_ASSOC);

			if ($aplicar) {
				$ajuste = $ajusteOverride !== null && is_numeric($ajusteOverride)
					? (int) $ajusteOverride
					: (int) $c["ajuste_unidades_default"];
				if ($ajuste === 0 && $c["impacto_pct_default"] !== null && (int) $linea["unidades_sugeridas"] > 0) {
					$ajuste = (int) round((int) $linea["unidades_sugeridas"] * ((float) $c["impacto_pct_default"] / 100.0));
				}
				if ($existente) {
					$upd = $pdo->prepare(
						"UPDATE proyeccion_comercial_factorjf
						 SET ajuste_unidades = :aj, actualizado_por = :usuario, actualizado_en = NOW()
						 WHERE id = :id"
					);
					$upd->bindValue(":aj", $ajuste, PDO::PARAM_INT);
					$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
					$upd->bindValue(":id", (int) $existente["id"], PDO::PARAM_INT);
					$upd->execute();
				} else {
					$ins = $pdo->prepare(
						"INSERT INTO proyeccion_comercial_factorjf
							(id_proyeccion_modelo, id_catalogo, tipo, titulo, descripcion,
							 fecha_desde, fecha_hasta, ajuste_unidades, impacto_pct,
							 canal_publicidad, inversion_publicidad, referencia_evidencia,
							 activo, creado_por, creado_en)
						 VALUES
							(:linea, :cat, :tipo, :titulo, :descripcion,
							 :fd, :fh, :aj, :pct, :canal, :inv, :ref,
							 1, :usuario, NOW())"
					);
					$ins->bindValue(":linea", (int) $idLinea, PDO::PARAM_INT);
					$ins->bindValue(":cat", (int) $idCatalogo, PDO::PARAM_INT);
					$ins->bindValue(":tipo", $c["tipo"], PDO::PARAM_STR);
					$ins->bindValue(":titulo", $c["titulo"], PDO::PARAM_STR);
					$ins->bindValue(":descripcion", $c["descripcion"], $c["descripcion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$ins->bindValue(":fd", $c["fecha_desde"], $c["fecha_desde"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$ins->bindValue(":fh", $c["fecha_hasta"], $c["fecha_hasta"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$ins->bindValue(":aj", $ajuste, PDO::PARAM_INT);
					$ins->bindValue(":pct", $c["impacto_pct_default"], $c["impacto_pct_default"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$ins->bindValue(":canal", $c["canal_publicidad"], $c["canal_publicidad"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$ins->bindValue(":inv", $c["inversion_publicidad"], $c["inversion_publicidad"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$ins->bindValue(":ref", $c["referencia_evidencia"], $c["referencia_evidencia"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$ins->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
					$ins->execute();
				}
				self::mdlInsertarAuditoria($pdo, $idLinea, "ACTUALIZAR", "factor_catalogo", null, $c["titulo"] . ":" . $ajuste, $motivo !== "" ? $motivo : "Factor catálogo activado", $usuario);
			} else {
				if ($existente) {
					$upd = $pdo->prepare(
						"UPDATE proyeccion_comercial_factorjf
						 SET activo = 0, actualizado_por = :usuario, actualizado_en = NOW()
						 WHERE id = :id"
					);
					$upd->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
					$upd->bindValue(":id", (int) $existente["id"], PDO::PARAM_INT);
					$upd->execute();
					self::mdlInsertarAuditoria($pdo, $idLinea, "ACTUALIZAR", "factor_catalogo", $c["titulo"], null, $motivo !== "" ? $motivo : "Factor catálogo desactivado", $usuario);
				}
			}

			$suma = self::mdlRecalcularAjustesLinea($pdo, $idLinea, $usuario);
			$pdo->commit();
			$detalle = self::mdlCatalogoParaLinea($idLinea);
			$detalle["unidades_ajustes"] = $suma;
			return $detalle;
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudo aplicar el factor. ¿Ejecutaste el SQL del catálogo?");
		}
	}
}
