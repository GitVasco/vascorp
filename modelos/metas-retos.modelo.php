<?php

require_once "conexion.php";
require_once dirname(__FILE__) . "/../controladores/metas-retos.config.php";

class ModeloMetasRetos
{

	private static function tiposVentaReal()
	{
		return array("S02", "S03", "S70", "E05", "S05");
	}

	private static function sqlTiposVentaReal($alias = "v")
	{
		$tipos = array();
		foreach (self::tiposVentaReal() as $tipo) {
			$tipos[] = "'" . $tipo . "'";
		}
		return "{$alias}.tipo IN (" . implode(", ", $tipos) . ")";
	}

	/** JOIN a vendedor activo (TVEND + estado_decisiones = 1). */
	private static function sqlJoinVendedorActivo($aliasVenta = "v", $aliasMaestra = "ma")
	{
		return "INNER JOIN maestrajf {$aliasMaestra}
				ON {$aliasMaestra}.codigo = TRIM({$aliasVenta}.vendedor)
			   AND UPPER({$aliasMaestra}.tipo_dato) = 'TVEND'
			   AND {$aliasMaestra}.estado_decisiones = 1";
	}

	private static function rangoMes($anio, $mes)
	{
		$anio = (int) $anio;
		$mes = (int) $mes;
		$inicio = sprintf("%04d-%02d-01", $anio, $mes);
		if ($mes === 12) {
			$fin = sprintf("%04d-01-01", $anio + 1);
		} else {
			$fin = sprintf("%04d-%02d-01", $anio, $mes + 1);
		}
		return array("inicio" => $inicio, "fin" => $fin);
	}

	private static function tablaMovimientos($anio)
	{
		$anio = (int) $anio;
		return "movimientosjf_" . $anio;
	}

	static public function mdlVendedoresActivos()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT codigo, descripcion
			 FROM maestrajf
			 WHERE UPPER(tipo_dato) = 'TVEND'
			   AND estado_decisiones = 1
			 ORDER BY codigo ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlObtenerReto($codVendedor, $anio, $mes)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM metas_retos_vendedorjf
			 WHERE cod_vendedor = :cod AND anio = :anio AND mes = :mes
			 LIMIT 1"
		);
		$stmt->bindParam(":cod", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
		$stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		return $fila ? $fila : null;
	}

	private static function existeTablaIncentivos()
	{
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		try {
			$pdo = Conexion::conectar();
			$check = $pdo->query("SHOW TABLES LIKE 'metas_retos_incentivos_productojf'");
			$cache = $check && (bool) $check->fetch();
		} catch (Exception $e) {
			$cache = false;
		}
		return $cache;
	}

	/** Columnas de comisión por cobranza (migración metas-retos-comision-cobranza.sql). */
	private static function existeColumnasCobranza()
	{
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		try {
			$pdo = Conexion::conectar();
			$check = $pdo->query(
				"SHOW COLUMNS FROM metas_retos_vendedorjf LIKE 'meta_cobranza'"
			);
			$cache = $check && (bool) $check->fetch();
		} catch (Exception $e) {
			$cache = false;
		}
		return $cache;
	}

	static public function mdlListarIncentivosPorReto($idMetaReto)
	{
		$idMetaReto = (int) $idMetaReto;
		if ($idMetaReto < 1 || !self::existeTablaIncentivos()) {
			return array();
		}
		// Subconsulta de color: colorjf puede tener códigos duplicados.
		$stmt = Conexion::conectar()->prepare(
			"SELECT i.*,
				IFNULL(c.nombre_color, i.cod_color) AS nombre_color
			 FROM metas_retos_incentivos_productojf i
			 LEFT JOIN (
				SELECT TRIM(cod_color) AS cod_color,
					MAX(IFNULL(NULLIF(TRIM(nom_color), ''), TRIM(cod_color))) AS nombre_color
				FROM colorjf
				WHERE TRIM(IFNULL(cod_color, '')) <> ''
				GROUP BY TRIM(cod_color)
			 ) c ON c.cod_color = TRIM(IFNULL(i.cod_color, ''))
			 WHERE i.id_meta_reto = :id
			 ORDER BY i.orden ASC, i.id ASC"
		);
		$stmt->bindValue(":id", $idMetaReto, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/** Incentivos del período agrupados por cod_vendedor. */
	static public function mdlListarIncentivosPeriodo($anio, $mes)
	{
		if (!self::existeTablaIncentivos()) {
			return array();
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT i.*,
				TRIM(r.cod_vendedor) AS cod_vendedor,
				IFNULL(c.nombre_color, i.cod_color) AS nombre_color
			 FROM metas_retos_incentivos_productojf i
			 INNER JOIN metas_retos_vendedorjf r ON r.id = i.id_meta_reto
			 LEFT JOIN (
				SELECT TRIM(cod_color) AS cod_color,
					MAX(IFNULL(NULLIF(TRIM(nom_color), ''), TRIM(cod_color))) AS nombre_color
				FROM colorjf
				WHERE TRIM(IFNULL(cod_color, '')) <> ''
				GROUP BY TRIM(cod_color)
			 ) c ON c.cod_color = TRIM(IFNULL(i.cod_color, ''))
			 WHERE r.anio = :anio AND r.mes = :mes
			 ORDER BY r.cod_vendedor ASC, i.orden ASC, i.id ASC"
		);
		$stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
		$stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$cod = trim($fila["cod_vendedor"]);
			if (!isset($mapa[$cod])) {
				$mapa[$cod] = array();
			}
			$mapa[$cod][] = $fila;
		}
		return $mapa;
	}

	/**
	 * Guarda cabecera + reemplaza incentivos hijos en una transacción.
	 * No escribe columnas legacy modelo_especial_* (respaldo de lectura).
	 */
	static public function mdlGuardarReto($datos, $incentivos = array())
	{
		if (!self::existeTablaIncentivos()) {
			return "error";
		}
		if (!self::existeColumnasCobranza()) {
			return "error_cobranza";
		}
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();

		try {
			$existente = self::mdlObtenerReto($datos["cod_vendedor"], $datos["anio"], $datos["mes"]);

			if ($existente) {
				$stmt = $pdo->prepare(
					"UPDATE metas_retos_vendedorjf SET
						meta_cobranza = :meta_cobranza,
						comision_cobranza_pct = :comision_cobranza_pct,
						comision_cobranza_fijo = :comision_cobranza_fijo,
						cumplimiento_cobranza = :cumplimiento_cobranza,
						meta_monto = :meta_monto,
						comision_monto_pct = :comision_monto_pct,
						comision_monto_fijo = :comision_monto_fijo,
						cumplimiento_monto = :cumplimiento_monto,
						meta_clientes = :meta_clientes,
						comision_clientes_fijo = :comision_clientes_fijo,
						cumplimiento_clientes = :cumplimiento_clientes,
						meta_modelos = :meta_modelos,
						meta_modelos_modo = :meta_modelos_modo,
						meta_modelos_pct = :meta_modelos_pct,
						comision_modelos_fijo = :comision_modelos_fijo,
						cumplimiento_modelos = :cumplimiento_modelos,
						usuario = :usuario,
						fecmod = NOW()
					 WHERE id = :id"
				);
				$stmt->bindValue(":id", (int) $existente["id"], PDO::PARAM_INT);
				$idReto = (int) $existente["id"];
			} else {
				$stmt = $pdo->prepare(
					"INSERT INTO metas_retos_vendedorjf (
						cod_vendedor, anio, mes,
						meta_cobranza, comision_cobranza_pct, comision_cobranza_fijo, cumplimiento_cobranza,
						meta_monto, comision_monto_pct, comision_monto_fijo, cumplimiento_monto,
						meta_clientes, comision_clientes_fijo, cumplimiento_clientes,
						meta_modelos, meta_modelos_modo, meta_modelos_pct, comision_modelos_fijo, cumplimiento_modelos,
						usuario, fecreg
					) VALUES (
						:cod_vendedor, :anio, :mes,
						:meta_cobranza, :comision_cobranza_pct, :comision_cobranza_fijo, :cumplimiento_cobranza,
						:meta_monto, :comision_monto_pct, :comision_monto_fijo, :cumplimiento_monto,
						:meta_clientes, :comision_clientes_fijo, :cumplimiento_clientes,
						:meta_modelos, :meta_modelos_modo, :meta_modelos_pct, :comision_modelos_fijo, :cumplimiento_modelos,
						:usuario, NOW()
					)"
				);
				$stmt->bindValue(":cod_vendedor", $datos["cod_vendedor"], PDO::PARAM_STR);
				$stmt->bindValue(":anio", (int) $datos["anio"], PDO::PARAM_INT);
				$stmt->bindValue(":mes", (int) $datos["mes"], PDO::PARAM_INT);
				$idReto = 0;
			}

			self::bindNullableDecimal($stmt, ":meta_cobranza", isset($datos["meta_cobranza"]) ? $datos["meta_cobranza"] : null);
			self::bindNullableDecimal($stmt, ":comision_cobranza_pct", isset($datos["comision_cobranza_pct"]) ? $datos["comision_cobranza_pct"] : null);
			self::bindNullableDecimal($stmt, ":comision_cobranza_fijo", isset($datos["comision_cobranza_fijo"]) ? $datos["comision_cobranza_fijo"] : null);
			$cumplCob = isset($datos["cumplimiento_cobranza"]) && $datos["cumplimiento_cobranza"] === "prorrata"
				? "prorrata" : "todo_nada";
			$stmt->bindValue(":cumplimiento_cobranza", $cumplCob, PDO::PARAM_STR);

			self::bindNullableDecimal($stmt, ":meta_monto", $datos["meta_monto"]);
			self::bindNullableDecimal($stmt, ":comision_monto_pct", $datos["comision_monto_pct"]);
			self::bindNullableDecimal($stmt, ":comision_monto_fijo", $datos["comision_monto_fijo"]);
			$stmt->bindValue(":cumplimiento_monto", $datos["cumplimiento_monto"], PDO::PARAM_STR);

			self::bindNullableInt($stmt, ":meta_clientes", $datos["meta_clientes"]);
			self::bindNullableDecimal($stmt, ":comision_clientes_fijo", $datos["comision_clientes_fijo"]);
			$stmt->bindValue(":cumplimiento_clientes", $datos["cumplimiento_clientes"], PDO::PARAM_STR);

			self::bindNullableInt($stmt, ":meta_modelos", $datos["meta_modelos"]);
			$modoMod = isset($datos["meta_modelos_modo"]) && $datos["meta_modelos_modo"] === "porcentaje"
				? "porcentaje" : "cantidad";
			$stmt->bindValue(":meta_modelos_modo", $modoMod, PDO::PARAM_STR);
			self::bindNullableDecimal($stmt, ":meta_modelos_pct", isset($datos["meta_modelos_pct"]) ? $datos["meta_modelos_pct"] : null);
			self::bindNullableDecimal($stmt, ":comision_modelos_fijo", $datos["comision_modelos_fijo"]);
			$stmt->bindValue(":cumplimiento_modelos", $datos["cumplimiento_modelos"], PDO::PARAM_STR);
			$stmt->bindValue(":usuario", (int) $datos["usuario"], PDO::PARAM_INT);

			if (!$stmt->execute()) {
				throw new Exception("Error al guardar cabecera");
			}

			if ($idReto < 1) {
				$idReto = (int) $pdo->lastInsertId();
			}
			if ($idReto < 1) {
				throw new Exception("Sin id de meta/reto");
			}

			$del = $pdo->prepare("DELETE FROM metas_retos_incentivos_productojf WHERE id_meta_reto = :id");
			$del->bindValue(":id", $idReto, PDO::PARAM_INT);
			$del->execute();

			if (!empty($incentivos) && is_array($incentivos)) {
				$ins = $pdo->prepare(
					"INSERT INTO metas_retos_incentivos_productojf (
						id_meta_reto, tipo_objetivo, modelo, cod_color, articulo,
						unidad_meta, meta_cantidad, comision_pct, cumplimiento,
						orden, observacion, usuario, fecreg
					) VALUES (
						:id_meta_reto, :tipo_objetivo, :modelo, :cod_color, :articulo,
						:unidad_meta, :meta_cantidad, :comision_pct, :cumplimiento,
						:orden, :observacion, :usuario, NOW()
					)"
				);

				$orden = 0;
				foreach ($incentivos as $inc) {
					$ins->bindValue(":id_meta_reto", $idReto, PDO::PARAM_INT);
					$ins->bindValue(":tipo_objetivo", $inc["tipo_objetivo"], PDO::PARAM_STR);

					self::bindNullableStr($ins, ":modelo", isset($inc["modelo"]) ? $inc["modelo"] : null);
					self::bindNullableStr($ins, ":cod_color", isset($inc["cod_color"]) ? $inc["cod_color"] : null);
					self::bindNullableStr($ins, ":articulo", isset($inc["articulo"]) ? $inc["articulo"] : null);

					$ins->bindValue(":unidad_meta", $inc["unidad_meta"], PDO::PARAM_STR);
					$ins->bindValue(":meta_cantidad", $inc["meta_cantidad"]);
					$ins->bindValue(":comision_pct", $inc["comision_pct"]);
					$ins->bindValue(":cumplimiento", $inc["cumplimiento"], PDO::PARAM_STR);
					$ins->bindValue(":orden", $orden, PDO::PARAM_INT);
					self::bindNullableStr($ins, ":observacion", isset($inc["observacion"]) ? $inc["observacion"] : null);
					$ins->bindValue(":usuario", (int) $datos["usuario"], PDO::PARAM_INT);

					if (!$ins->execute()) {
						throw new Exception("Error al guardar incentivo");
					}
					$orden++;
				}
			}

			$pdo->commit();
			return "ok";
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return "error";
		}
	}

	/** Solo modelos activos del catálogo (modelojf). Sin tope artificial. */
	static public function mdlListarModelosCatalogo($q = "")
	{
		$q = trim((string) $q);

		$sql = "SELECT m.modelo,
				IFNULL(m.nombre, m.modelo) AS nombre,
				IFNULL(m.articulos, 0) AS articulos,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca
			FROM modelojf m
			LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			WHERE LOWER(TRIM(IFNULL(m.estado, ''))) = 'activo'
			  AND TRIM(IFNULL(m.modelo, '')) <> ''";
		if ($q !== "") {
			$sql .= " AND (m.modelo LIKE :q OR m.nombre LIKE :q2)";
		}
		$sql .= " ORDER BY m.modelo ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		if ($q !== "") {
			$like = "%" . $q . "%";
			$stmt->bindParam(":q", $like, PDO::PARAM_STR);
			$stmt->bindParam(":q2", $like, PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/** Colores existentes para un modelo (todas las tallas implícitas). */
	static public function mdlListarColoresPorModelo($modelo)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return array();
		}

		// Subconsulta primero para evitar duplicar por colorjf con códigos repetidos
		$stmt = Conexion::conectar()->prepare(
			"SELECT x.cod_color,
				MAX(IFNULL(NULLIF(TRIM(x.color_art), ''), IFNULL(c.nom_color, x.cod_color))) AS nombre_color,
				SUM(x.articulos) AS articulos
			 FROM (
				SELECT TRIM(a.cod_color) AS cod_color,
					MAX(TRIM(IFNULL(a.color, ''))) AS color_art,
					COUNT(*) AS articulos
				FROM articulojf a
				WHERE TRIM(a.modelo) = :modelo
				  AND TRIM(IFNULL(a.cod_color, '')) <> ''
				GROUP BY TRIM(a.cod_color)
			 ) x
			 LEFT JOIN colorjf c ON TRIM(c.cod_color) = x.cod_color
			 GROUP BY x.cod_color
			 ORDER BY x.cod_color ASC"
		);
		$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/** Búsqueda de SKU para incentivos tipo artículo. */
	static public function mdlBuscarArticulos($q = "", $limite = 40)
	{
		$q = trim((string) $q);
		$limite = max(1, min(100, (int) $limite));
		if ($q === "") {
			return array();
		}

		$like = "%" . $q . "%";
		$stmt = Conexion::conectar()->prepare(
			"SELECT a.articulo,
				TRIM(IFNULL(a.modelo, '')) AS modelo,
				TRIM(IFNULL(a.cod_color, '')) AS cod_color,
				IFNULL(NULLIF(TRIM(a.color), ''), IFNULL(c.nom_color, a.cod_color)) AS nombre_color,
				IFNULL(a.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca
			 FROM articulojf a
			 LEFT JOIN colorjf c ON TRIM(c.cod_color) = TRIM(a.cod_color)
			 LEFT JOIN marcasjf mk ON mk.id = a.id_marca
			 WHERE a.articulo LIKE :q
			    OR a.modelo LIKE :q2
			 ORDER BY a.articulo ASC
			 LIMIT {$limite}"
		);
		$stmt->bindParam(":q", $like, PDO::PARAM_STR);
		$stmt->bindParam(":q2", $like, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlExisteModelo($modelo)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM modelojf WHERE TRIM(modelo) = :m LIMIT 1"
		);
		$stmt->bindParam(":m", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		if ($stmt->fetch()) {
			return true;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM articulojf WHERE TRIM(modelo) = :m LIMIT 1"
		);
		$stmt->bindParam(":m", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		return (bool) $stmt->fetch();
	}

	static public function mdlExisteModeloColor($modelo, $codColor)
	{
		$modelo = trim((string) $modelo);
		$codColor = trim((string) $codColor);
		if ($modelo === "" || $codColor === "") {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM articulojf
			 WHERE TRIM(modelo) = :m AND TRIM(cod_color) = :c
			 LIMIT 1"
		);
		$stmt->bindParam(":m", $modelo, PDO::PARAM_STR);
		$stmt->bindParam(":c", $codColor, PDO::PARAM_STR);
		$stmt->execute();
		return (bool) $stmt->fetch();
	}

	static public function mdlExisteArticulo($articulo)
	{
		$articulo = trim((string) $articulo);
		if ($articulo === "") {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT articulo, modelo, cod_color, color, id_marca
			 FROM articulojf WHERE articulo = :a LIMIT 1"
		);
		$stmt->bindParam(":a", $articulo, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ? $row : null;
	}

	private static function bindNullableDecimal($stmt, $param, $valor)
	{
		if ($valor === null || $valor === "") {
			$stmt->bindValue($param, null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue($param, $valor, PDO::PARAM_STR);
		}
	}

	private static function bindNullableInt($stmt, $param, $valor)
	{
		if ($valor === null || $valor === "") {
			$stmt->bindValue($param, null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue($param, (int) $valor, PDO::PARAM_INT);
		}
	}

	private static function bindNullableStr($stmt, $param, $valor)
	{
		if ($valor === null || $valor === "") {
			$stmt->bindValue($param, null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue($param, trim((string) $valor), PDO::PARAM_STR);
		}
	}

	/** Sincroniza umbral de monto a metas_vendedorjf.meta_venta (dashboard). */
	static public function mdlSyncMetaVentaLegacy($codVendedor, $anio, $mes, $metaMonto, $usuario)
	{
		$metaMonto = ($metaMonto === null || $metaMonto === "") ? 0 : (float) $metaMonto;

		$stmt = Conexion::conectar()->prepare(
			"SELECT id FROM metas_vendedorjf
			 WHERE cod_vendedor = :cod AND anio = :anio AND mes = :mes LIMIT 1"
		);
		$stmt->bindParam(":cod", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
		$stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
		$stmt->execute();
		$ex = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($ex) {
			$u = Conexion::conectar()->prepare(
				"UPDATE metas_vendedorjf SET meta_venta = :meta WHERE id = :id"
			);
			$u->bindValue(":meta", $metaMonto);
			$u->bindValue(":id", (int) $ex["id"], PDO::PARAM_INT);
			return $u->execute() ? "ok" : "error";
		}

		$i = Conexion::conectar()->prepare(
			"INSERT INTO metas_vendedorjf (cod_vendedor, anio, mes, meta_venta, meta_cobranza, usuario)
			 VALUES (:cod, :anio, :mes, :meta, NULL, :usuario)"
		);
		$i->bindValue(":cod", $codVendedor, PDO::PARAM_STR);
		$i->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$i->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		$i->bindValue(":meta", $metaMonto);
		$i->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
		return $i->execute() ? "ok" : "error";
	}

	/** Sincroniza solo meta_cobranza hacia metas_vendedorjf (sin reglas de comisión). */
	static public function mdlSyncMetaCobranzaLegacy($codVendedor, $anio, $mes, $metaCobranza, $usuario)
	{
		$metaCob = ($metaCobranza === null || $metaCobranza === "")
			? null
			: (float) $metaCobranza;

		$stmt = Conexion::conectar()->prepare(
			"SELECT id FROM metas_vendedorjf
			 WHERE cod_vendedor = :cod AND anio = :anio AND mes = :mes LIMIT 1"
		);
		$stmt->bindParam(":cod", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
		$stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
		$stmt->execute();
		$ex = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($ex) {
			$u = Conexion::conectar()->prepare(
				"UPDATE metas_vendedorjf SET meta_cobranza = :meta WHERE id = :id"
			);
			if ($metaCob === null) {
				$u->bindValue(":meta", null, PDO::PARAM_NULL);
			} else {
				$u->bindValue(":meta", $metaCob);
			}
			$u->bindValue(":id", (int) $ex["id"], PDO::PARAM_INT);
			return $u->execute() ? "ok" : "error";
		}

		$i = Conexion::conectar()->prepare(
			"INSERT INTO metas_vendedorjf (cod_vendedor, anio, mes, meta_venta, meta_cobranza, usuario)
			 VALUES (:cod, :anio, :mes, 0, :meta, :usuario)"
		);
		$i->bindValue(":cod", $codVendedor, PDO::PARAM_STR);
		$i->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$i->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		if ($metaCob === null) {
			$i->bindValue(":meta", null, PDO::PARAM_NULL);
		} else {
			$i->bindValue(":meta", $metaCob);
		}
		$i->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
		return $i->execute() ? "ok" : "error";
	}

	static public function mdlVentaRealPorVendedor($anio, $mes)
	{
		require_once "metricas-comerciales.modelo.php";
		return ModeloMetricasComerciales::mdlVentaPermitidaPorVendedor($anio, $mes, false);
	}

	static public function mdlCobranzaNetaPorVendedor($anio, $mes)
	{
		require_once "metricas-comerciales.modelo.php";
		return ModeloMetricasComerciales::mdlCobranzaNetaGerenciaPorVendedor($anio, $mes);
	}

	/**
	 * Clientes nuevos: 1ª compra del periodo con documento 100% permitido.
	 */
	static public function mdlClientesNuevosPorVendedor($anio, $mes)
	{
		require_once "metricas-comerciales.modelo.php";
		return ModeloMetricasComerciales::mdlClientesNuevosPermitidosPorVendedor($anio, $mes);
	}

	/** Modelos distintos con líneas de marca permitida. */
	static public function mdlModelosActivosPorVendedor($anio, $mes)
	{
		require_once "metricas-comerciales.modelo.php";
		return ModeloMetricasComerciales::mdlModelosPermitidosPorVendedor($anio, $mes);
	}

	/**
	 * Docenas y venta por vendedor+modelo (solo marcas permitidas).
	 * Retorna [cod_vendedor => [modelo => ['docenas'=>, 'venta'=>]]]
	 */
	static public function mdlVentaModeloPorVendedor($anio, $mes)
	{
		require_once "metricas-comerciales.modelo.php";
		return ModeloMetricasComerciales::mdlVentaModeloPermitidaPorVendedor($anio, $mes);
	}

	static public function mdlListarAvancePeriodo($anio, $mes)
	{
		$vendedores = self::mdlVendedoresActivos();
		$retos = array();
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM metas_retos_vendedorjf WHERE anio = :anio AND mes = :mes"
		);
		$stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
		$stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
		$stmt->execute();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
			$retos[trim($r["cod_vendedor"])] = $r;
		}

		$ventas = self::mdlVentaRealPorVendedor($anio, $mes);
		$cobranzas = self::mdlCobranzaNetaPorVendedor($anio, $mes);
		$clientes = self::mdlClientesNuevosPorVendedor($anio, $mes);
		$modelos = self::mdlModelosActivosPorVendedor($anio, $mes);

		$incentivosBase = self::mdlListarIncentivosPeriodo($anio, $mes);
		require_once "metricas-comerciales.modelo.php";
		$incentivosAvance = ModeloMetricasComerciales::mdlAvanceIncentivosProductoPorVendedorPeriodo(
			$anio,
			$mes,
			$incentivosBase
		);

		require_once "grupos-marcas-comercial.modelo.php";
		$fechaRef = sprintf("%04d-%02d-01", (int) $anio, (int) $mes);

		$lista = array();
		foreach ($vendedores as $vend) {
			$cod = trim($vend["codigo"]);
			$reto = isset($retos[$cod]) ? $retos[$cod] : null;
			$incs = isset($incentivosAvance[$cod])
				? $incentivosAvance[$cod]
				: (isset($incentivosBase[$cod]) ? $incentivosBase[$cod] : array());

			$lista[] = array(
				"cod_vendedor" => $cod,
				"nombre_vendedor" => $vend["descripcion"],
				"reto" => $reto,
				"cobranza_neta_real" => isset($cobranzas[$cod]) ? $cobranzas[$cod] : 0,
				"venta_real" => isset($ventas[$cod]) ? $ventas[$cod] : 0,
				"clientes_nuevos" => isset($clientes[$cod]) ? $clientes[$cod] : 0,
				"modelos_activos" => isset($modelos[$cod]) ? $modelos[$cod] : 0,
				"universo_modelos" => ModeloGruposMarcasComercial::mdlUniversoModelosActivosPorVendedor($cod, $fechaRef),
				"incentivos" => $incs
			);
		}
		return $lista;
	}
}
