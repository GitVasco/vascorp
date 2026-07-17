<?php

require_once "conexion.php";
require_once "costos-modelo-mensual.modelo.php";

class ModeloFichaGerencialModelos
{
	private static function tiposVenta()
	{
		return array("S02", "S03", "S70", "E05", "S05");
	}

	private static function sqlTiposVenta($alias = "m")
	{
		$tipos = array();
		foreach (self::tiposVenta() as $tipo) {
			$tipos[] = "'" . $tipo . "'";
		}
		return $alias . ".tipo IN (" . implode(", ", $tipos) . ")";
	}

	private static function sqlCabeceraValida($alias = "m")
	{
		return "EXISTS (
			SELECT 1
			FROM ventajf v
			WHERE v.tipo = {$alias}.tipo
			  AND v.documento = {$alias}.documento
			  AND v.fecha = {$alias}.fecha
			  AND UPPER(TRIM(IFNULL(v.estado, ''))) <> 'ANULADO'
		)";
	}

	static public function mdlRangoMes($anio, $mes)
	{
		$anio = (int) $anio;
		$mes = (int) $mes;
		$inicio = sprintf("%04d-%02d-01", $anio, $mes);
		$fin = $mes === 12
			? sprintf("%04d-01-01", $anio + 1)
			: sprintf("%04d-%02d-01", $anio, $mes + 1);
		return array("inicio" => $inicio, "fin" => $fin);
	}

	/**
	 * Normaliza un período YYYY-MM..YYYY-MM a fechas inclusivas/exclusivas.
	 * Devuelve null si el rango es inválido.
	 */
	static public function mdlConstruirPeriodo($desdeYm, $hastaYm, $maxMeses = 12)
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
		$anioMin = 2021;
		$anioMax = (int) date("Y");
		$mesActual = (int) date("n");
		if ($desdeMes < 1 || $desdeMes > 12 || $hastaMes < 1 || $hastaMes > 12) {
			return null;
		}
		if ($desdeAnio < $anioMin || $hastaAnio < $anioMin || $desdeAnio > $anioMax || $hastaAnio > $anioMax) {
			return null;
		}
		$desdeClave = $desdeAnio * 100 + $desdeMes;
		$hastaClave = $hastaAnio * 100 + $hastaMes;
		$actualClave = $anioMax * 100 + $mesActual;
		if ($desdeClave > $hastaClave || $hastaClave > $actualClave) {
			return null;
		}
		$meses = (($hastaAnio - $desdeAnio) * 12) + ($hastaMes - $desdeMes) + 1;
		if ($meses < 1 || $meses > (int) $maxMeses) {
			return null;
		}

		$anios = array();
		for ($anio = $desdeAnio; $anio <= $hastaAnio; $anio++) {
			$tabla = self::mdlResolverTablaMovimientos($anio);
			if ($tabla === null) {
				return null;
			}
			$anios[] = $anio;
		}

		$inicio = sprintf("%04d-%02d-01", $desdeAnio, $desdeMes);
		$fin = $hastaMes === 12
			? sprintf("%04d-01-01", $hastaAnio + 1)
			: sprintf("%04d-%02d-01", $hastaAnio, $hastaMes + 1);
		$parcial = ($hastaAnio === $anioMax && $hastaMes === $mesActual);

		return array(
			"desde" => sprintf("%04d-%02d", $desdeAnio, $desdeMes),
			"hasta" => sprintf("%04d-%02d", $hastaAnio, $hastaMes),
			"inicio" => $inicio,
			"fin" => $fin,
			"fin_exclusivo" => $fin,
			"meses" => $meses,
			"anios" => $anios,
			"parcial" => $parcial,
			"anio" => $hastaAnio,
			"mes" => $hastaMes,
			"desde_anio" => $desdeAnio,
			"desde_mes" => $desdeMes,
			"hasta_anio" => $hastaAnio,
			"hasta_mes" => $hastaMes
		);
	}

	static public function mdlPeriodoDesdeAnioMes($anio, $mes)
	{
		return self::mdlConstruirPeriodo(
			sprintf("%04d-%02d", (int) $anio, (int) $mes),
			sprintf("%04d-%02d", (int) $anio, (int) $mes)
		);
	}

	static public function mdlPeriodoHomologoAnterior($periodo)
	{
		if (!is_array($periodo) || empty($periodo["desde"]) || empty($periodo["hasta"])) {
			return null;
		}
		$mover = function ($ym, $deltaAnios) {
			if (!preg_match('/^(\d{4})-(\d{2})$/', $ym, $match)) {
				return null;
			}
			return sprintf("%04d-%02d", ((int) $match[1]) + $deltaAnios, (int) $match[2]);
		};
		$desde = $mover($periodo["desde"], -1);
		$hasta = $mover($periodo["hasta"], -1);
		if ($desde === null || $hasta === null) {
			return null;
		}
		return self::mdlConstruirPeriodo($desde, $hasta, max(12, (int) $periodo["meses"]));
	}

	static public function mdlDiasEfectivosPeriodo($periodo)
	{
		$inicio = new DateTime($periodo["inicio"]);
		$finExclusivo = new DateTime($periodo["fin"]);
		$hoy = new DateTime(date("Y-m-d"));
		if ($finExclusivo > $hoy) {
			$finExclusivo = clone $hoy;
			$finExclusivo->modify("+1 day");
		}
		$dias = (int) $inicio->diff($finExclusivo)->days;
		return max(1, $dias);
	}

	/**
	 * Subconsulta UNION ALL segura sobre movimientosjf_YYYY validados.
	 */
	static public function mdlSqlFuenteMovimientos($anios, $columnas = null)
	{
		$anios = array_values(array_unique(array_map("intval", (array) $anios)));
		sort($anios);
		if (empty($anios)) {
			return null;
		}
		if ($columnas === null) {
			$columnas = "articulo, cantidad, total, fecha, tipo, documento, cliente, vendedor";
		}
		$partes = array();
		foreach ($anios as $anio) {
			$tabla = self::mdlResolverTablaMovimientos($anio);
			if ($tabla === null) {
				return null;
			}
			$partes[] = "SELECT {$columnas} FROM {$tabla}";
		}
		return "(" . implode(" UNION ALL ", $partes) . ")";
	}

	static public function mdlResolverTablaMovimientos($anio)
	{
		static $cache = array();
		$anio = (int) $anio;
		$actual = (int) date("Y");
		if ($anio < 2021 || $anio > $actual) {
			return null;
		}
		if (array_key_exists($anio, $cache)) {
			return $cache[$anio];
		}

		$tabla = sprintf("movimientosjf_%04d", $anio);
		$stmt = Conexion::conectar()->prepare(
			"SELECT TABLE_NAME
			 FROM information_schema.TABLES
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla
			 LIMIT 1"
		);
		$stmt->bindValue(":tabla", $tabla, PDO::PARAM_STR);
		$stmt->execute();
		$cache[$anio] = $stmt->fetchColumn() ? $tabla : null;
		return $cache[$anio];
	}

	static public function mdlCatalogoModelosActivos($idMarca = 0, $q = "")
	{
		$sql = "SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca
			FROM modelojf m
			LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			WHERE UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			  AND TRIM(IFNULL(m.modelo, '')) <> ''";
		if ((int) $idMarca > 0) {
			$sql .= " AND m.id_marca = :id_marca";
		}
		$q = trim((string) $q);
		if ($q !== "") {
			$sql .= " AND (m.modelo LIKE :q_modelo OR m.nombre LIKE :q_nombre)";
		}
		$sql .= " ORDER BY m.modelo ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		if ((int) $idMarca > 0) {
			$stmt->bindValue(":id_marca", (int) $idMarca, PDO::PARAM_INT);
		}
		if ($q !== "") {
			$stmt->bindValue(":q_modelo", "%" . $q . "%", PDO::PARAM_STR);
			$stmt->bindValue(":q_nombre", "%" . $q . "%", PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMarcasConModelosActivos()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT mk.id, mk.marca
			 FROM modelojf m
			 INNER JOIN marcasjf mk ON mk.id = m.id_marca
			 WHERE UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			 ORDER BY mk.marca ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlGruposConModelosActivos()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT g.id, g.codigo, g.nombre
			 FROM grupos_marcas_comercialjf g
			 INNER JOIN grupos_marcas_detallejf d ON d.id_grupo_marca = g.id
			 INNER JOIN modelojf m ON m.id_marca = d.id_marca
			 WHERE g.estado = 1
			   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			 ORDER BY g.nombre ASC, g.id ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlCabeceraModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(m.estado, '') AS estado,
				IFNULL(m.tipo, '') AS tipo,
				IFNULL(m.linea, '') AS linea,
				IFNULL(NULLIF(TRIM(m.imagen), ''), 'vistas/img/modelos/default/anonymous.png') AS imagen,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca
			 FROM modelojf m
			 LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			 WHERE TRIM(m.modelo) = :modelo
			   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			 LIMIT 1"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlPrecio9Valorizado($modelo, $unidades)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.precio9,
				CAST(CAST(:unidades AS DECIMAL(20,4)) * p.precio9 AS DECIMAL(20,4)) AS ventas_acumuladas
			 FROM preciojf p
			 WHERE TRIM(p.modelo) = :modelo
			 ORDER BY p.id DESC
			 LIMIT 1"
		);
		$stmt->bindValue(":unidades", (string) $unidades, PDO::PARAM_STR);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlRankingGeneral($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(isset($periodo["anios"]) ? $periodo["anios"] : array())
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return null;
		}
		$stmtGrupo = Conexion::conectar()->prepare(
			"SELECT g.id, g.codigo, g.nombre
			 FROM modelojf mo
			 INNER JOIN grupos_marcas_detallejf d ON d.id_marca = mo.id_marca
			 INNER JOIN grupos_marcas_comercialjf g
				ON g.id = d.id_grupo_marca AND g.estado = 1
			 WHERE TRIM(mo.modelo) = :modelo
			 ORDER BY g.id ASC
			 LIMIT 1"
		);
		$stmtGrupo->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmtGrupo->execute();
		$grupo = $stmtGrupo->fetch(PDO::FETCH_ASSOC);
		if (!$grupo) {
			return array(
				"posicion" => null,
				"total_modelos_con_venta" => 0,
				"grupo" => null
			);
		}

		$sql = "SELECT TRIM(a.modelo) AS modelo,
				SUM(IFNULL(m.cantidad, 0)) AS unidades_vendidas
			FROM {$fuente} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			INNER JOIN modelojf mo ON TRIM(mo.modelo) = TRIM(a.modelo)
				AND UPPER(TRIM(IFNULL(mo.estado, ''))) = 'ACTIVO'
			INNER JOIN grupos_marcas_detallejf d
				ON d.id_marca = mo.id_marca AND d.id_grupo_marca = :id_grupo
			WHERE m.fecha >= :inicio AND m.fecha < :fin
			  AND " . self::sqlTiposVenta("m") . "
			  AND " . self::sqlCabeceraValida("m") . "
			GROUP BY TRIM(a.modelo)
			ORDER BY unidades_vendidas DESC, modelo ASC";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":id_grupo", (int) $grupo["id"], PDO::PARAM_INT);
		$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$posicion = null;
		$total = 0;
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $indice => $fila) {
			$total++;
			if ($fila["modelo"] === trim((string) $modelo)) {
				$posicion = $indice + 1;
			}
		}
		return array(
			"posicion" => $posicion,
			"total_modelos_con_venta" => $total,
			"grupo" => array(
				"id" => (int) $grupo["id"],
				"codigo" => $grupo["codigo"],
				"nombre" => $grupo["nombre"]
			)
		);
	}

	static public function mdlDatosRankingGrupos($idGrupos, $anio, $mes)
	{
		$idGrupos = array_values(array_unique(array_filter(array_map("intval", $idGrupos))));
		$tabla = self::mdlResolverTablaMovimientos($anio);
		if ($tabla === null || empty($idGrupos)) {
			return array();
		}
		$marcadores = array();
		foreach ($idGrupos as $indice => $idGrupo) {
			$marcadores[] = ":grupo_" . $indice;
		}
		$rango = self::mdlRangoMes($anio, $mes);
		$sql = "SELECT ventas.grupo_id,
				ventas.modelo,
				ventas.unidades_vendidas,
				CASE
					WHEN p.precio9 IS NOT NULL AND c.costo_unitario IS NOT NULL THEN
						CAST(
							(ventas.unidades_vendidas * p.precio9)
							- (ventas.unidades_vendidas * c.costo_unitario)
							AS DECIMAL(20,4)
						)
					ELSE NULL
				END AS utilidad
			FROM (
				SELECT gm.id_grupo AS grupo_id,
					TRIM(a.modelo) AS modelo,
					SUM(IFNULL(m.cantidad, 0)) AS unidades_vendidas
				FROM articulojf a
				INNER JOIN modelojf mo
					ON TRIM(mo.modelo) = TRIM(a.modelo)
					AND UPPER(TRIM(IFNULL(mo.estado, ''))) = 'ACTIVO'
				INNER JOIN (
					SELECT d.id_marca, d.id_grupo_marca AS id_grupo
					FROM grupos_marcas_detallejf d
					INNER JOIN grupos_marcas_comercialjf g
						ON g.id = d.id_grupo_marca AND g.estado = 1
					WHERE d.id_grupo_marca IN (" . implode(", ", $marcadores) . ")
					GROUP BY d.id_marca, d.id_grupo_marca
				) gm ON gm.id_marca = mo.id_marca
				INNER JOIN {$tabla} m ON m.articulo = a.articulo
				WHERE m.fecha >= :inicio AND m.fecha < :fin
				  AND " . self::sqlTiposVenta("m") . "
				  AND " . self::sqlCabeceraValida("m") . "
				GROUP BY gm.id_grupo, TRIM(a.modelo)
			) ventas
			LEFT JOIN (
				SELECT TRIM(modelo) AS modelo, MAX(id) AS id
				FROM preciojf
				GROUP BY TRIM(modelo)
			) ultimo_precio ON ultimo_precio.modelo = ventas.modelo
			LEFT JOIN preciojf p ON p.id = ultimo_precio.id
			LEFT JOIN costos_modelo_mensualjf c
				ON c.modelo = ventas.modelo
				AND c.estado = 'aprobado'
				AND (c.anio * 100 + c.mes) <= :periodo_costo
			LEFT JOIN costos_modelo_mensualjf costo_nuevo
				ON costo_nuevo.modelo = c.modelo
				AND costo_nuevo.estado = 'aprobado'
				AND (costo_nuevo.anio * 100 + costo_nuevo.mes) <= :periodo_costo_nuevo
				AND (
					(costo_nuevo.anio * 100 + costo_nuevo.mes) > (c.anio * 100 + c.mes)
					OR (
						(costo_nuevo.anio * 100 + costo_nuevo.mes) = (c.anio * 100 + c.mes)
						AND costo_nuevo.id > c.id
					)
				)
			WHERE costo_nuevo.id IS NULL
			ORDER BY ventas.grupo_id, ventas.unidades_vendidas DESC, ventas.modelo";
		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($idGrupos as $indice => $idGrupo) {
			$stmt->bindValue(":grupo_" . $indice, $idGrupo, PDO::PARAM_INT);
		}
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$periodoCosto = (int) $anio * 100 + (int) $mes;
		$stmt->bindValue(":periodo_costo", $periodoCosto, PDO::PARAM_INT);
		$stmt->bindValue(":periodo_costo_nuevo", $periodoCosto, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlLideresComerciales($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(isset($periodo["anios"]) ? $periodo["anios"] : array())
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return array(
				"zona" => null,
				"zonas" => array(),
				"vendedor" => null,
				"vendedores" => array(),
				"cliente" => null,
				"clientes" => array()
			);
		}
		$where = "TRIM(a.modelo) = :modelo
			AND m.fecha >= :inicio AND m.fecha < :fin
			AND " . self::sqlTiposVenta("m") . "
			AND " . self::sqlCabeceraValida("m");
		$ejecutar = function ($sql, $todos = false) use ($modelo, $periodo) {
			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
			$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
			$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
			$stmt->execute();
			if ($todos) {
				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			}
			$fila = $stmt->fetch(PDO::FETCH_ASSOC);
			return $fila ? $fila : null;
		};

		$zonas = $ejecutar(
			"SELECT COALESCE(zc.id, zg.id, zu.id, 0) AS codigo,
				COALESCE(zc.nombre, zg.nombre, zu.nombre, 'Sin zona') AS nombre,
				SUM(ventas.venta_neta) AS venta_neta,
				SUM(ventas.unidades_vendidas) AS unidades_vendidas
			 FROM (
				SELECT TRIM(m.cliente) AS cliente,
					SUM(IFNULL(m.total, 0)) AS venta_neta,
					SUM(IFNULL(m.cantidad, 0)) AS unidades_vendidas
				FROM {$fuente} m
				INNER JOIN articulojf a ON a.articulo = m.articulo
				WHERE {$where}
				GROUP BY TRIM(m.cliente)
			 ) ventas
			 LEFT JOIN clientesjf c ON c.codigo = ventas.cliente
			 LEFT JOIN zonas_comercialesjf zc ON zc.id = c.id_zona AND zc.estado = 1
			 LEFT JOIN grupos_empresarialesjf ge
				ON TRIM(ge.codigo) = TRIM(c.grupo) AND ge.estado = 1
			 LEFT JOIN zonas_comercialesjf zg ON zg.id = ge.id_zona AND zg.estado = 1
			 LEFT JOIN zonas_comerciales_ubigeojf zcu ON TRIM(zcu.cod_ubi) = TRIM(c.ubigeo)
			 LEFT JOIN zonas_comercialesjf zu ON zu.id = zcu.id_zona AND zu.estado = 1
			 GROUP BY COALESCE(zc.id, zg.id, zu.id, 0),
				COALESCE(zc.nombre, zg.nombre, zu.nombre, 'Sin zona')
			 ORDER BY venta_neta DESC, unidades_vendidas DESC, codigo ASC",
			true
		);
		$zona = null;
		foreach ($zonas as $zonaCandidata) {
			if ((float) $zonaCandidata["venta_neta"] > 0) {
				$zona = $zonaCandidata;
				break;
			}
		}

		$vendedores = $ejecutar(
			"SELECT ventas.codigo,
				COALESCE(NULLIF(TRIM(v.descripcion), ''), NULLIF(ventas.codigo, ''), 'Sin vendedor') AS nombre,
				ventas.venta_neta,
				ventas.unidades_vendidas
			 FROM (
				SELECT TRIM(m.vendedor) AS codigo,
					SUM(IFNULL(m.total, 0)) AS venta_neta,
					SUM(IFNULL(m.cantidad, 0)) AS unidades_vendidas
				FROM {$fuente} m
				INNER JOIN articulojf a ON a.articulo = m.articulo
				WHERE {$where}
				GROUP BY TRIM(m.vendedor)
			 ) ventas
			 LEFT JOIN (
				SELECT TRIM(codigo) AS codigo, MAX(descripcion) AS descripcion
				FROM maestrajf
				WHERE UPPER(TRIM(tipo_dato)) = 'TVEND'
				GROUP BY TRIM(codigo)
			 ) v ON v.codigo = ventas.codigo
			 ORDER BY ventas.venta_neta DESC, ventas.unidades_vendidas DESC, ventas.codigo ASC",
			true
		);
		$vendedor = null;
		foreach ($vendedores as $vendedorCandidato) {
			if ((float) $vendedorCandidato["venta_neta"] > 0) {
				$vendedor = $vendedorCandidato;
				break;
			}
		}

		$clientes = $ejecutar(
			"SELECT ventas.codigo,
				COALESCE(NULLIF(TRIM(c.nombre), ''), NULLIF(ventas.codigo, ''), 'Sin cliente') AS nombre,
				ventas.venta_neta,
				ventas.unidades_vendidas,
				ventas.pedidos,
				ventas.ultima_compra
			 FROM (
				SELECT TRIM(m.cliente) AS codigo,
					SUM(IFNULL(m.total, 0)) AS venta_neta,
					SUM(IFNULL(m.cantidad, 0)) AS unidades_vendidas,
					COUNT(DISTINCT CONCAT(m.tipo, '|', m.documento, '|', m.fecha)) AS pedidos,
					MAX(m.fecha) AS ultima_compra
				FROM {$fuente} m
				INNER JOIN articulojf a ON a.articulo = m.articulo
				WHERE {$where}
				GROUP BY TRIM(m.cliente)
			 ) ventas
			 LEFT JOIN (
				SELECT TRIM(codigo) AS codigo, MAX(nombre) AS nombre
				FROM clientesjf
				GROUP BY TRIM(codigo)
			 ) c ON c.codigo = ventas.codigo
			 ORDER BY ventas.venta_neta DESC, ventas.unidades_vendidas DESC, ventas.codigo ASC",
			true
		);
		$cliente = null;
		foreach ($clientes as $clienteCandidato) {
			if ((float) $clienteCandidato["venta_neta"] > 0) {
				$cliente = $clienteCandidato;
				break;
			}
		}

		return array(
			"zona" => $zona,
			"zonas" => $zonas,
			"vendedor" => $vendedor,
			"vendedores" => $vendedores,
			"cliente" => $cliente,
			"clientes" => $clientes
		);
	}

	static public function mdlInventarioResumen($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*) AS variantes,
				COALESCE(SUM(IFNULL(stock, 0)), 0) AS stock_fisico,
				COALESCE(SUM(IFNULL(pedidos, 0)), 0) AS pedidos,
				COALESCE(SUM(IFNULL(stock, 0) - IFNULL(pedidos, 0)), 0) AS stock_disponible,
				COALESCE(SUM(IFNULL(taller, 0)), 0) AS taller,
				COALESCE(SUM(IFNULL(servicio, 0)), 0) AS servicio,
				COALESCE(SUM(IFNULL(alm_corte, 0)), 0) AS alm_corte,
				COALESCE(SUM(IFNULL(ord_corte, 0)), 0) AS ord_corte
			 FROM articulojf
			 WHERE TRIM(modelo) = :modelo
			   AND UPPER(TRIM(IFNULL(estado, ''))) = 'ACTIVO'"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlResumenComparativo($anio, $mes, $idGrupo = 0, $modelos = array())
	{
		$tabla = self::mdlResolverTablaMovimientos($anio);
		if ($tabla === null) {
			return array();
		}

		$rango = self::mdlRangoMes($anio, $mes);
		$inicioAnio = sprintf("%04d-01-01", (int) $anio);
		$anioAnterior = (int) $anio - 1;
		$tablaAnterior = self::mdlResolverTablaMovimientos($anioAnterior);
		$inicioAnterior = sprintf("%04d-01-01", $anioAnterior);
		$finAnterior = $mes === 12
			? sprintf("%04d-01-01", $anioAnterior + 1)
			: sprintf("%04d-%02d-01", $anioAnterior, $mes + 1);
		$modelos = array_values(array_unique(array_filter(array_map("trim", $modelos))));
		$marcadoresModelos = array(
			"anterior" => array(),
			"ventas" => array(),
			"inventario" => array(),
			"precio" => array(),
			"principal" => array()
		);
		foreach ($modelos as $indice => $modelo) {
			foreach ($marcadoresModelos as $seccion => $marcadores) {
				$marcadoresModelos[$seccion][] = ":modelo_{$seccion}_{$indice}";
			}
		}

		if ($tablaAnterior !== null) {
			$sqlAnterior = "SELECT TRIM(a.modelo) AS modelo,
					COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades_acumuladas_anterior
				FROM {$tablaAnterior} m
				INNER JOIN articulojf a ON a.articulo = m.articulo
				WHERE m.fecha >= :inicio_anterior AND m.fecha < :fin_anterior
				  AND " . self::sqlTiposVenta("m") . "
				  AND " . self::sqlCabeceraValida("m")
				  . (!empty($modelos) ? " AND TRIM(a.modelo) IN (" . implode(", ", $marcadoresModelos["anterior"]) . ")" : "") . "
				GROUP BY TRIM(a.modelo)";
		} else {
			$sqlAnterior = "SELECT NULL AS modelo, 0 AS unidades_acumuladas_anterior WHERE 1 = 0";
		}

		if ((int) $idGrupo > 0) {
			$sqlGrupo = "SELECT d.id_marca, d.id_grupo_marca AS id_grupo
				FROM grupos_marcas_detallejf d
				INNER JOIN grupos_marcas_comercialjf ga
					ON ga.id = d.id_grupo_marca AND ga.estado = 1
				WHERE d.id_grupo_marca = :id_grupo_mapeo
				GROUP BY d.id_marca, d.id_grupo_marca";
		} else {
			$sqlGrupo = "SELECT d.id_marca, MIN(d.id_grupo_marca) AS id_grupo
				FROM grupos_marcas_detallejf d
				INNER JOIN grupos_marcas_comercialjf ga
					ON ga.id = d.id_grupo_marca AND ga.estado = 1
				GROUP BY d.id_marca";
		}

		$sql = "SELECT TRIM(mo.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(mo.nombre), ''), TRIM(mo.modelo)) AS nombre,
				IFNULL(mo.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca,
				g.id AS grupo_id,
				IFNULL(g.nombre, '') AS grupo_nombre,
				IFNULL(ventas.movimientos_periodo, 0) AS movimientos_periodo,
				COALESCE(ventas.unidades_vendidas, 0) AS unidades_vendidas,
				COALESCE(ventas.unidades_acumuladas, 0) AS unidades_acumuladas,
				COALESCE(anterior.unidades_acumuladas_anterior, 0) AS unidades_acumuladas_anterior,
				COALESCE(inv.stock_disponible, 0) AS stock_disponible,
				p.precio9,
				CASE
					WHEN p.precio9 IS NOT NULL THEN
						CAST(COALESCE(ventas.unidades_vendidas, 0) * p.precio9 AS DECIMAL(20,4))
					ELSE NULL
				END AS ventas_acumuladas,
				c.costo_unitario,
				c.anio AS costo_anio,
				c.mes AS costo_mes,
				CASE
					WHEN p.precio9 IS NOT NULL AND c.costo_unitario IS NOT NULL THEN
						CAST(
							(COALESCE(ventas.unidades_vendidas, 0) * p.precio9)
							- (COALESCE(ventas.unidades_vendidas, 0) * c.costo_unitario)
							AS DECIMAL(20,4)
						)
					ELSE NULL
				END AS utilidad,
				CASE
					WHEN p.precio9 IS NOT NULL
						AND c.costo_unitario IS NOT NULL
						AND (COALESCE(ventas.unidades_vendidas, 0) * p.precio9) <> 0 THEN
						CAST(
							(
								(
									(COALESCE(ventas.unidades_vendidas, 0) * p.precio9)
									- (COALESCE(ventas.unidades_vendidas, 0) * c.costo_unitario)
								) / (COALESCE(ventas.unidades_vendidas, 0) * p.precio9)
							) * 100
							AS DECIMAL(10,4)
						)
					ELSE NULL
				END AS margen_pct
			FROM modelojf mo
			LEFT JOIN marcasjf mk ON mk.id = mo.id_marca
			LEFT JOIN ({$sqlGrupo}) gm ON gm.id_marca = mo.id_marca
			LEFT JOIN grupos_marcas_comercialjf g ON g.id = gm.id_grupo
			LEFT JOIN (
				SELECT TRIM(a.modelo) AS modelo,
					SUM(CASE WHEN m.fecha >= :inicio_mes_actual THEN IFNULL(m.cantidad, 0) ELSE 0 END) AS unidades_vendidas,
					SUM(IFNULL(m.cantidad, 0)) AS unidades_acumuladas,
					SUM(CASE WHEN m.fecha >= :inicio_mes_movimientos THEN 1 ELSE 0 END) AS movimientos_periodo
				FROM {$tabla} m
				INNER JOIN articulojf a ON a.articulo = m.articulo
				WHERE m.fecha >= :inicio_anio_actual AND m.fecha < :fin_actual
				  AND " . self::sqlTiposVenta("m") . "
				  AND " . self::sqlCabeceraValida("m")
				  . (!empty($modelos) ? " AND TRIM(a.modelo) IN (" . implode(", ", $marcadoresModelos["ventas"]) . ")" : "") . "
				GROUP BY TRIM(a.modelo)
			) ventas ON ventas.modelo = TRIM(mo.modelo)
			LEFT JOIN ({$sqlAnterior}) anterior ON anterior.modelo = TRIM(mo.modelo)
			LEFT JOIN (
				SELECT TRIM(modelo) AS modelo,
					COALESCE(SUM(IFNULL(stock, 0) - IFNULL(pedidos, 0)), 0) AS stock_disponible
				FROM articulojf
				WHERE UPPER(TRIM(IFNULL(estado, ''))) = 'ACTIVO'
				" . (!empty($modelos) ? " AND TRIM(modelo) IN (" . implode(", ", $marcadoresModelos["inventario"]) . ")" : "") . "
				GROUP BY TRIM(modelo)
			) inv ON inv.modelo = TRIM(mo.modelo)
			LEFT JOIN (
				SELECT TRIM(modelo) AS modelo, MAX(id) AS id
				FROM preciojf
				" . (!empty($modelos) ? " WHERE TRIM(modelo) IN (" . implode(", ", $marcadoresModelos["precio"]) . ")" : "") . "
				GROUP BY TRIM(modelo)
			) ultimo_precio ON ultimo_precio.modelo = TRIM(mo.modelo)
			LEFT JOIN preciojf p ON p.id = ultimo_precio.id
			LEFT JOIN costos_modelo_mensualjf c
				ON c.modelo = TRIM(mo.modelo)
				AND c.estado = 'aprobado'
				AND (c.anio * 100 + c.mes) <= :periodo_costo
			LEFT JOIN costos_modelo_mensualjf costo_nuevo
				ON costo_nuevo.modelo = c.modelo
				AND costo_nuevo.estado = 'aprobado'
				AND (costo_nuevo.anio * 100 + costo_nuevo.mes) <= :periodo_costo_nuevo
				AND (
					(costo_nuevo.anio * 100 + costo_nuevo.mes) > (c.anio * 100 + c.mes)
					OR (
						(costo_nuevo.anio * 100 + costo_nuevo.mes) = (c.anio * 100 + c.mes)
						AND costo_nuevo.id > c.id
					)
				)
			WHERE UPPER(TRIM(IFNULL(mo.estado, ''))) = 'ACTIVO'
			  AND TRIM(IFNULL(mo.modelo, '')) <> ''
			  AND costo_nuevo.id IS NULL";
		if (!empty($modelos)) {
			$sql .= " AND TRIM(mo.modelo) IN (" . implode(", ", $marcadoresModelos["principal"]) . ")";
		}
		if ((int) $idGrupo > 0) {
			$sql .= " AND gm.id_grupo IS NOT NULL";
		}
		$sql .= " ORDER BY IFNULL(g.nombre, 'ZZZ'), grupo_id, unidades_vendidas DESC, modelo ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":inicio_mes_actual", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":inicio_mes_movimientos", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":inicio_anio_actual", $inicioAnio, PDO::PARAM_STR);
		$stmt->bindValue(":fin_actual", $rango["fin"], PDO::PARAM_STR);
		if ($tablaAnterior !== null) {
			$stmt->bindValue(":inicio_anterior", $inicioAnterior, PDO::PARAM_STR);
			$stmt->bindValue(":fin_anterior", $finAnterior, PDO::PARAM_STR);
		}
		$periodoCosto = (int) $anio * 100 + (int) $mes;
		$stmt->bindValue(":periodo_costo", $periodoCosto, PDO::PARAM_INT);
		$stmt->bindValue(":periodo_costo_nuevo", $periodoCosto, PDO::PARAM_INT);
		if ((int) $idGrupo > 0) {
			$stmt->bindValue(":id_grupo_mapeo", (int) $idGrupo, PDO::PARAM_INT);
		}
		foreach ($modelos as $indice => $modelo) {
			foreach ($marcadoresModelos as $seccion => $marcadores) {
				if ($seccion === "anterior" && $tablaAnterior === null) {
					continue;
				}
				$stmt->bindValue(":modelo_{$seccion}_{$indice}", $modelo, PDO::PARAM_STR);
			}
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlVentasResumen($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(isset($periodo["anios"]) ? $periodo["anios"] : array())
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return null;
		}
		$sql = "SELECT COALESCE(SUM(IFNULL(m.total, 0)), 0) AS venta_neta,
				COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades_vendidas,
				MAX(CASE WHEN m.tipo <> 'E05' THEN m.fecha ELSE NULL END) AS ultima_venta
			FROM {$fuente} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			WHERE TRIM(a.modelo) = :modelo
			  AND m.fecha >= :inicio AND m.fecha < :fin
			  AND " . self::sqlTiposVenta("m") . "
			  AND " . self::sqlCabeceraValida("m");
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlVentasMensuales($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(isset($periodo["anios"]) ? $periodo["anios"] : array())
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return array();
		}
		$sql = "SELECT YEAR(m.fecha) AS anio,
				MONTH(m.fecha) AS mes,
				COALESCE(SUM(IFNULL(m.total, 0)), 0) AS venta_neta,
				COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades_vendidas
			FROM {$fuente} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			WHERE TRIM(a.modelo) = :modelo
			  AND m.fecha >= :inicio AND m.fecha < :fin
			  AND " . self::sqlTiposVenta("m") . "
			  AND " . self::sqlCabeceraValida("m") . "
			GROUP BY YEAR(m.fecha), MONTH(m.fecha)
			ORDER BY anio, mes";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlVariantes($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(isset($periodo["anios"]) ? $periodo["anios"] : array())
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return array();
		}
		$sql = "SELECT TRIM(a.cod_color) AS cod_color,
				MAX(IFNULL(NULLIF(TRIM(a.color), ''), TRIM(a.cod_color))) AS color,
				TRIM(a.cod_talla) AS cod_talla,
				MAX(IFNULL(NULLIF(TRIM(a.talla), ''), TRIM(a.cod_talla))) AS talla,
				COUNT(*) AS articulos,
				SUM(CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN 1 ELSE 0 END) AS articulos_activos,
				SUM(CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) <> 'ACTIVO' THEN 1 ELSE 0 END) AS articulos_inactivos,
				COALESCE(SUM(CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.stock, 0) ELSE 0 END), 0) AS stock_fisico,
				COALESCE(SUM(CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.pedidos, 0) ELSE 0 END), 0) AS pedidos,
				COALESCE(SUM(CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0) ELSE 0 END), 0) AS stock_disponible,
				COALESCE(SUM(CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.taller, 0) + IFNULL(a.servicio, 0) + IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0) ELSE 0 END), 0) AS en_proceso,
				COALESCE(SUM(IFNULL(mv.unidades, 0)), 0) AS unidades_vendidas,
				COALESCE(SUM(IFNULL(mv.venta, 0)), 0) AS venta_neta,
				CAST(COALESCE(SUM(IFNULL(mv.unidades, 0)), 0) / :meses AS DECIMAL(20,4)) AS promedio_mensual_unidades
			FROM articulojf a
			LEFT JOIN (
				SELECT m.articulo,
					SUM(IFNULL(m.cantidad, 0)) AS unidades,
					SUM(IFNULL(m.total, 0)) AS venta
				FROM {$fuente} m
				INNER JOIN articulojf av ON av.articulo = m.articulo
				WHERE TRIM(av.modelo) = :modelo_ventas
				  AND m.fecha >= :inicio AND m.fecha < :fin
				  AND " . self::sqlTiposVenta("m") . "
				  AND " . self::sqlCabeceraValida("m") . "
				GROUP BY m.articulo
			) mv ON mv.articulo = a.articulo
			WHERE TRIM(a.modelo) = :modelo_variantes
			  AND (UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' OR mv.articulo IS NOT NULL)
			GROUP BY TRIM(a.cod_color), TRIM(a.cod_talla)
			ORDER BY TRIM(a.cod_color), CAST(a.cod_talla AS UNSIGNED), TRIM(a.cod_talla)";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":modelo_ventas", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":modelo_variantes", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":meses", max(1, (int) $periodo["meses"]), PDO::PARAM_INT);
		$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlEvolucion($modelo, $anio)
	{
		$tabla = self::mdlResolverTablaMovimientos($anio);
		if ($tabla === null) {
			return array();
		}
		$inicio = sprintf("%04d-01-01", (int) $anio);
		$fin = sprintf("%04d-01-01", (int) $anio + 1);
		$sql = "SELECT MONTH(m.fecha) AS mes,
				COALESCE(SUM(IFNULL(m.total, 0)), 0) AS venta_neta,
				COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades_vendidas
			FROM {$tabla} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			WHERE TRIM(a.modelo) = :modelo
			  AND m.fecha >= :inicio AND m.fecha < :fin
			  AND " . self::sqlTiposVenta("m") . "
			  AND " . self::sqlCabeceraValida("m") . "
			GROUP BY MONTH(m.fecha)
			ORDER BY mes";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $inicio, PDO::PARAM_STR);
		$stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlEvolucionPeriodo($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(isset($periodo["anios"]) ? $periodo["anios"] : array())
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return array();
		}
		$sql = "SELECT YEAR(m.fecha) AS anio,
				MONTH(m.fecha) AS mes,
				COALESCE(SUM(IFNULL(m.total, 0)), 0) AS venta_neta,
				COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades_vendidas
			FROM {$fuente} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			WHERE TRIM(a.modelo) = :modelo
			  AND m.fecha >= :inicio AND m.fecha < :fin
			  AND " . self::sqlTiposVenta("m") . "
			  AND " . self::sqlCabeceraValida("m") . "
			GROUP BY YEAR(m.fecha), MONTH(m.fecha)
			ORDER BY anio, mes";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$clave = sprintf("%04d-%02d", (int) $fila["anio"], (int) $fila["mes"]);
			$mapa[$clave] = $fila;
		}
		$serie = array();
		$cursorAnio = (int) $periodo["desde_anio"];
		$cursorMes = (int) $periodo["desde_mes"];
		for ($i = 0; $i < (int) $periodo["meses"]; $i++) {
			$clave = sprintf("%04d-%02d", $cursorAnio, $cursorMes);
			$serie[] = array(
				"anio" => $cursorAnio,
				"mes" => $cursorMes,
				"periodo" => $clave,
				"etiqueta" => $clave,
				"venta_neta" => isset($mapa[$clave]) ? (float) $mapa[$clave]["venta_neta"] : 0,
				"unidades_vendidas" => isset($mapa[$clave]) ? (float) $mapa[$clave]["unidades_vendidas"] : 0
			);
			$cursorMes++;
			if ($cursorMes > 12) {
				$cursorMes = 1;
				$cursorAnio++;
			}
		}
		return $serie;
	}

	static public function mdlEvolucionModelos($modelos, $anio)
	{
		$modelos = array_values(array_unique(array_filter(array_map("trim", $modelos))));
		$tabla = self::mdlResolverTablaMovimientos($anio);
		if ($tabla === null || empty($modelos)) {
			return array();
		}
		$marcadores = array();
		foreach ($modelos as $indice => $modelo) {
			$marcadores[] = ":modelo_" . $indice;
		}
		$inicio = sprintf("%04d-01-01", (int) $anio);
		$fin = sprintf("%04d-01-01", (int) $anio + 1);
		$sql = "SELECT TRIM(a.modelo) AS modelo,
				MONTH(m.fecha) AS mes,
				COALESCE(SUM(IFNULL(m.total, 0)), 0) AS venta_neta,
				COALESCE(SUM(IFNULL(m.cantidad, 0)), 0) AS unidades_vendidas
			FROM articulojf a
			INNER JOIN {$tabla} m ON m.articulo = a.articulo
			WHERE TRIM(a.modelo) IN (" . implode(", ", $marcadores) . ")
			  AND m.fecha >= :inicio AND m.fecha < :fin
			  AND " . self::sqlTiposVenta("m") . "
			  AND " . self::sqlCabeceraValida("m") . "
			GROUP BY TRIM(a.modelo), MONTH(m.fecha)
			ORDER BY TRIM(a.modelo), mes";
		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($modelos as $indice => $modelo) {
			$stmt->bindValue(":modelo_" . $indice, $modelo, PDO::PARAM_STR);
		}
		$stmt->bindValue(":inicio", $inicio, PDO::PARAM_STR);
		$stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlDetalleArticulos($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(
				isset($periodo["anios"]) ? $periodo["anios"] : array(),
				"articulo, cantidad, total, fecha, tipo, documento, cliente, vendedor"
			)
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return array();
		}
		$sql = "SELECT a.articulo,
				IFNULL(a.estado, '') AS estado,
				TRIM(IFNULL(a.cod_color, '')) AS cod_color,
				IFNULL(NULLIF(TRIM(a.color), ''), TRIM(a.cod_color)) AS color,
				TRIM(IFNULL(a.cod_talla, '')) AS cod_talla,
				IFNULL(NULLIF(TRIM(a.talla), ''), TRIM(a.cod_talla)) AS talla,
				CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.stock, 0) ELSE 0 END AS stock_fisico,
				CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.pedidos, 0) ELSE 0 END AS pedidos,
				CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0) ELSE 0 END AS stock_disponible,
				CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.taller, 0) ELSE 0 END AS taller,
				CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.servicio, 0) ELSE 0 END AS servicio,
				CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.alm_corte, 0) ELSE 0 END AS alm_corte,
				CASE WHEN UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' THEN IFNULL(a.ord_corte, 0) ELSE 0 END AS ord_corte,
				COALESCE(mv.unidades_vendidas, 0) AS unidades_vendidas,
				COALESCE(mv.venta_neta, 0) AS venta_neta,
				COALESCE(mv.produccion, 0) AS produccion,
				mv.ultima_venta
			FROM articulojf a
			LEFT JOIN (
				SELECT m.articulo,
					SUM(CASE WHEN " . self::sqlTiposVenta("m") . " AND " . self::sqlCabeceraValida("m") . " THEN IFNULL(m.cantidad, 0) ELSE 0 END) AS unidades_vendidas,
					SUM(CASE WHEN " . self::sqlTiposVenta("m") . " AND " . self::sqlCabeceraValida("m") . " THEN IFNULL(m.total, 0) ELSE 0 END) AS venta_neta,
					SUM(CASE WHEN m.tipo = 'E20' THEN IFNULL(m.cantidad, 0) ELSE 0 END) AS produccion,
					MAX(CASE WHEN m.tipo IN ('S02', 'S03', 'S70', 'S05') AND " . self::sqlCabeceraValida("m") . " THEN m.fecha ELSE NULL END) AS ultima_venta
				FROM {$fuente} m
				INNER JOIN articulojf ad ON ad.articulo = m.articulo
				WHERE TRIM(ad.modelo) = :modelo_mov
				  AND m.fecha >= :inicio AND m.fecha < :fin
				  AND (m.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05', 'E20'))
				GROUP BY m.articulo
			) mv ON mv.articulo = a.articulo
			WHERE TRIM(a.modelo) = :modelo_detalle
			  AND (UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO' OR mv.articulo IS NOT NULL)
			ORDER BY TRIM(a.cod_color), CAST(a.cod_talla AS UNSIGNED), a.articulo";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":modelo_mov", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":modelo_detalle", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$ultimas = self::mdlUltimasVentasModelo($modelo);
		foreach ($filas as $indice => $fila) {
			if (isset($ultimas[$fila["articulo"]])) {
				$filas[$indice]["ultima_venta"] = $ultimas[$fila["articulo"]];
			}
		}
		return $filas;
	}

	static public function mdlUltimasVentasModelo($modelo)
	{
		$partes = array();
		$anios = array();
		for ($anio = 2021; $anio <= (int) date("Y"); $anio++) {
			$tabla = self::mdlResolverTablaMovimientos($anio);
			if ($tabla === null) {
				continue;
			}
			$anios[] = $anio;
			$partes[] = "SELECT m.articulo, MAX(m.fecha) AS fecha
				FROM {$tabla} m
				INNER JOIN articulojf au ON au.articulo = m.articulo
				WHERE TRIM(au.modelo) = :modelo_{$anio}
				  AND m.tipo IN ('S02', 'S03', 'S70', 'S05')
				GROUP BY m.articulo";
		}
		if (empty($partes)) {
			return array();
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT articulo, MAX(fecha) AS ultima_venta
			 FROM (" . implode(" UNION ALL ", $partes) . ") historico
			 GROUP BY articulo"
		);
		foreach ($anios as $anio) {
			$stmt->bindValue(":modelo_" . $anio, trim((string) $modelo), PDO::PARAM_STR);
		}
		$stmt->execute();
		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[$fila["articulo"]] = $fila["ultima_venta"];
		}
		return $mapa;
	}

	static public function mdlConciliacion($modelo, $periodo)
	{
		$fuente = is_array($periodo)
			? self::mdlSqlFuenteMovimientos(isset($periodo["anios"]) ? $periodo["anios"] : array())
			: null;
		if ($fuente === null || !is_array($periodo)) {
			return null;
		}
		$sql = "SELECT
				COALESCE(SUM(IFNULL(m.total, 0)), 0) AS motor_lineas,
				COALESCE(SUM(CASE WHEN " . self::sqlCabeceraValida("m") . " THEN IFNULL(m.total, 0) ELSE 0 END), 0) AS ficha_lineas,
				COALESCE(SUM(CASE WHEN NOT " . self::sqlCabeceraValida("m") . " THEN IFNULL(m.total, 0) ELSE 0 END), 0) AS excluido_sin_cabecera_valida,
				SUM(CASE WHEN NOT " . self::sqlCabeceraValida("m") . " THEN 1 ELSE 0 END) AS lineas_excluidas,
				COUNT(*) AS lineas_totales
			FROM {$fuente} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			WHERE TRIM(a.modelo) = :modelo
			  AND m.fecha >= :inicio AND m.fecha < :fin
			  AND " . self::sqlTiposVenta("m");
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$totales = $stmt->fetch(PDO::FETCH_ASSOC);

		$nc = Conexion::conectar()->prepare(
			"SELECT COUNT(*) AS documentos, COALESCE(SUM(IFNULL(v.neto, 0)), 0) AS neto
			 FROM ventajf v
			 WHERE v.fecha >= :inicio AND v.fecha < :fin
			   AND v.tipo = 'E05'
			   AND UPPER(TRIM(IFNULL(v.estado, ''))) <> 'ANULADO'
			   AND NOT EXISTS (
					SELECT 1 FROM {$fuente} md
					WHERE md.tipo = v.tipo
					  AND md.documento = v.documento
					  AND md.fecha = v.fecha
					  AND IFNULL(md.cantidad, 0) <> 0
			   )"
		);
		$nc->bindValue(":inicio", $periodo["inicio"], PDO::PARAM_STR);
		$nc->bindValue(":fin", $periodo["fin"], PDO::PARAM_STR);
		$nc->execute();

		return array(
			"modelo" => trim((string) $modelo),
			"desde" => $periodo["desde"],
			"hasta" => $periodo["hasta"],
			"anio" => (int) $periodo["hasta_anio"],
			"mes" => (int) $periodo["hasta_mes"],
			"totales" => $totales,
			"nc_no_atribuibles_periodo" => $nc->fetch(PDO::FETCH_ASSOC)
		);
	}

	static public function mdlAuditoriaCatalogo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				SUM(CASE WHEN TRIM(IFNULL(cod_color, '')) = '' THEN 1 ELSE 0 END) AS sin_color,
				SUM(CASE WHEN TRIM(IFNULL(cod_talla, '')) = '' THEN 1 ELSE 0 END) AS sin_talla,
				SUM(CASE WHEN IFNULL(stock, 0) < 0 THEN 1 ELSE 0 END) AS stock_negativo,
				SUM(CASE WHEN IFNULL(stock, 0) - IFNULL(pedidos, 0) < 0 THEN 1 ELSE 0 END) AS disponible_negativo,
				COUNT(*) AS articulos_activos
			 FROM articulojf
			 WHERE TRIM(modelo) = :modelo
			   AND UPPER(TRIM(IFNULL(estado, ''))) = 'ACTIVO'"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}
