<?php

require_once "conexion.php";

class ModeloRecetasModelo
{

	/*=============================================
	Listado de recetas (cabecera)
	=============================================*/
	static public function mdlListar($modelo = "", $estado = "")
	{
		$sql = "SELECT
				r.id,
				r.modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), r.modelo) AS nombre_modelo,
				r.version,
				r.estado,
				r.vigente_desde,
				r.vigente_hasta,
				r.id_usuario_crea,
				r.id_usuario_actualiza,
				r.created_at,
				r.updated_at,
				(
					SELECT COUNT(*)
					FROM articulojf a
					WHERE a.modelo = r.modelo
					  AND a.estado = 'Activo'
				) AS articulos_activos,
				(
					SELECT COUNT(*)
					FROM recetas_modelo_detalles d
					WHERE d.id_receta_modelo = r.id
					  AND d.activo = 1
				) AS lineas_activas,
				(
					SELECT d.nombre_rol
					FROM recetas_modelo_detalles d
					WHERE d.id_receta_modelo = r.id
					  AND d.activo = 1
					  AND d.es_tela_principal = 1
					ORDER BY d.orden ASC, d.id ASC
					LIMIT 1
				) AS tela_principal_rol
			FROM recetas_modelo r
			LEFT JOIN modelojf m ON m.modelo = r.modelo
			WHERE 1 = 1";

		if ($modelo !== "") {
			$sql .= " AND r.modelo = :modelo";
		}
		if ($estado !== "") {
			$sql .= " AND r.estado = :estado";
		}
		$sql .= " ORDER BY r.modelo ASC, r.version DESC";

		$stmt = Conexion::conectar()->prepare($sql);
		if ($modelo !== "") {
			$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		}
		if ($estado !== "") {
			$stmt->bindValue(":estado", $estado, PDO::PARAM_STR);
		}
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Estadísticas del listado (cabecera)
	=============================================*/
	static public function mdlEstadisticas()
	{
		$pdo = Conexion::conectar();
		$out = array(
			"total" => 0,
			"borrador" => 0,
			"publicada" => 0,
			"archivada" => 0,
			"modelos_con_receta" => 0,
			"modelos_sin_receta" => 0,
			"sin_tela_principal" => 0,
		);

		$stmt = $pdo->query(
			"SELECT estado, COUNT(*) AS n
			 FROM recetas_modelo
			 GROUP BY estado"
		);
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$n = (int) $row["n"];
			$out["total"] += $n;
			$est = strtolower(trim((string) $row["estado"]));
			if (isset($out[$est])) {
				$out[$est] = $n;
			}
		}

		$stmt = $pdo->query(
			"SELECT COUNT(DISTINCT modelo) FROM recetas_modelo"
		);
		$out["modelos_con_receta"] = (int) $stmt->fetchColumn();

		$stmt = $pdo->query(
			"SELECT COUNT(DISTINCT a.modelo)
			 FROM articulojf a
			 INNER JOIN detalles_tarjetajf dt ON dt.articulo = a.articulo
			 WHERE a.estado = 'Activo'
			   AND TRIM(IFNULL(a.modelo, '')) <> ''
			   AND NOT EXISTS (
					SELECT 1 FROM recetas_modelo r WHERE r.modelo = a.modelo
			   )"
		);
		$out["modelos_sin_receta"] = (int) $stmt->fetchColumn();

		$stmt = $pdo->query(
			"SELECT COUNT(*) FROM recetas_modelo r
			 WHERE r.estado IN ('BORRADOR', 'PUBLICADA')
			   AND NOT EXISTS (
			     SELECT 1 FROM recetas_modelo_detalles d
			     WHERE d.id_receta_modelo = r.id
			       AND d.activo = 1
			       AND d.es_tela_principal = 1
			   )"
		);
		$out["sin_tela_principal"] = (int) $stmt->fetchColumn();

		return $out;
	}

	/*=============================================
	Cabecera por id
	=============================================*/
	static public function mdlObtenerCabecera($id)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				r.*,
				IFNULL(NULLIF(TRIM(m.nombre), ''), r.modelo) AS nombre_modelo
			 FROM recetas_modelo r
			 LEFT JOIN modelojf m ON m.modelo = r.modelo
			 WHERE r.id = :id
			 LIMIT 1"
		);
		$stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Líneas de una receta
	=============================================*/
	static public function mdlListarDetalles($idReceta, $soloActivos = false)
	{
		$sql = "SELECT *
			FROM recetas_modelo_detalles
			WHERE id_receta_modelo = :id";
		if ($soloActivos) {
			$sql .= " AND activo = 1";
		}
		$sql .= " ORDER BY orden ASC, id ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Variantes de una línea
	=============================================*/
	static public function mdlListarVariantes($idDetalle)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT *
			 FROM recetas_modelo_variantes
			 WHERE id_receta_modelo_detalle = :id
			 ORDER BY cod_color ASC, cod_talla ASC, id ASC"
		);
		$stmt->bindValue(":id", (int) $idDetalle, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Variantes de todas las líneas de una receta
	=============================================*/
	static public function mdlListarVariantesPorReceta($idReceta)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT v.*
			 FROM recetas_modelo_variantes v
			 INNER JOIN recetas_modelo_detalles d
			   ON d.id = v.id_receta_modelo_detalle
			 WHERE d.id_receta_modelo = :id
			 ORDER BY d.orden ASC, v.cod_color ASC, v.cod_talla ASC, v.id ASC"
		);
		$stmt->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Máxima versión de un modelo
	=============================================*/
	static public function mdlMaxVersionModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT IFNULL(MAX(version), 0) AS max_version
			 FROM recetas_modelo
			 WHERE modelo = :modelo"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return (int) $row["max_version"];
	}

	/*=============================================
	Modelo existe (maestra o con artículos)
	=============================================*/
	static public function mdlExisteModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT modelo FROM modelojf WHERE modelo = :modelo LIMIT 1"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		if ($stmt->fetch(PDO::FETCH_ASSOC)) {
			return true;
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT modelo FROM articulojf WHERE modelo = :modelo LIMIT 1"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();

		return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Artículos activos del modelo + ejes color/talla
	=============================================*/
	static public function mdlArticulosActivosModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				a.articulo,
				a.modelo,
				a.cod_color,
				a.color,
				a.cod_talla,
				a.talla,
				a.estado
			 FROM articulojf a
			 WHERE a.modelo = :modelo
			   AND a.estado = 'Activo'
			 ORDER BY a.cod_color ASC, CAST(a.cod_talla AS UNSIGNED) ASC, a.articulo ASC"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlColoresModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT a.cod_color, a.color
			 FROM articulojf a
			 WHERE a.modelo = :modelo
			   AND a.estado = 'Activo'
			   AND IFNULL(a.cod_color, '') <> ''
			 ORDER BY a.cod_color ASC"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlTallasModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT a.cod_talla, a.talla
			 FROM articulojf a
			 WHERE a.modelo = :modelo
			   AND a.estado = 'Activo'
			   AND IFNULL(a.cod_talla, '') <> ''
			 ORDER BY CAST(a.cod_talla AS UNSIGNED) ASC, a.cod_talla ASC"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Buscar MP activa (para selector)
	=============================================*/
	static public function mdlBuscarMp($q, $codigoSublinea = "", $limit = 30)
	{
		$limit = max(1, min(400, (int) $limit));
		$sql = "SELECT
				TRIM(p.CodPro) AS mp_codigo,
				p.DesPro AS descripcion,
				TRIM(p.ColPro) AS colpro,
				IFNULL(tc.Des_Larga, '') AS color,
				TRIM(p.UndPro) AS undpro,
				IFNULL(tu.Des_Corta, '') AS unidad,
				LEFT(p.CodFab, 6) AS codigo_sublinea,
				p.FamPro AS fampro
			FROM producto p
			LEFT JOIN Tabla_M_Detalle tc
			  ON tc.Cod_Tabla = 'TCOL'
			 AND TRIM(tc.Cod_Argumento) = TRIM(p.ColPro)
			LEFT JOIN Tabla_M_Detalle tu
			  ON tu.Cod_Tabla = 'TUND'
			 AND TRIM(tu.Cod_Argumento) = TRIM(p.UndPro)
			WHERE p.EstPro = '1'";

		if ($codigoSublinea !== "") {
			$sql .= " AND LEFT(p.CodFab, 6) = :sublinea";
		}
		if ($q !== "") {
			$sql .= " AND (
				p.CodPro LIKE :q
				OR p.DesPro LIKE :q2
				OR p.CodFab LIKE :q3
				OR IFNULL(tc.Des_Larga, '') LIKE :q4
				OR IFNULL(tu.Des_Corta, '') LIKE :q5
				OR p.ColPro LIKE :q6
			)";
		}
		$sql .= " ORDER BY p.CodPro ASC LIMIT " . $limit;

		$stmt = Conexion::conectar()->prepare($sql);
		if ($codigoSublinea !== "") {
			$stmt->bindValue(":sublinea", $codigoSublinea, PDO::PARAM_STR);
		}
		if ($q !== "") {
			$like = "%" . $q . "%";
			$stmt->bindValue(":q", $like, PDO::PARAM_STR);
			$stmt->bindValue(":q2", $like, PDO::PARAM_STR);
			$stmt->bindValue(":q3", $like, PDO::PARAM_STR);
			$stmt->bindValue(":q4", $like, PDO::PARAM_STR);
			$stmt->bindValue(":q5", $like, PDO::PARAM_STR);
			$stmt->bindValue(":q6", $like, PDO::PARAM_STR);
		}
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Catálogo de sublíneas (TSUB) para selector
	=============================================*/
	static public function mdlListarSublineas($q = "", $limit = 200)
	{
		$limit = max(1, min(500, (int) $limit));
		$sql = "SELECT
				CONCAT(TRIM(t.Des_Corta), TRIM(t.Valor_3)) AS codigo_sublinea,
				TRIM(t.Des_Corta) AS linea,
				TRIM(t.Valor_3) AS subcodigo,
				TRIM(t.Des_Larga) AS nombre
			FROM Tabla_M_Detalle t
			WHERE t.Cod_Tabla = 'TSUB'
			  AND t.Estado = '1'
			  AND TRIM(IFNULL(t.Des_Corta, '')) <> ''
			  AND TRIM(IFNULL(t.Valor_3, '')) <> ''";
		if ($q !== "") {
			$sql .= " AND (
				t.Des_Larga LIKE :q
				OR t.Des_Corta LIKE :q2
				OR CONCAT(t.Des_Corta, t.Valor_3) LIKE :q3
			)";
		}
		$sql .= " ORDER BY t.Des_Corta ASC, t.Valor_3 ASC LIMIT " . $limit;

		$stmt = Conexion::conectar()->prepare($sql);
		if ($q !== "") {
			$like = "%" . $q . "%";
			$stmt->bindValue(":q", $like, PDO::PARAM_STR);
			$stmt->bindValue(":q2", $like, PDO::PARAM_STR);
			$stmt->bindValue(":q3", $like, PDO::PARAM_STR);
		}
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Nombres de sublíneas por código (lote)
	=============================================*/
	static public function mdlInfoSublineas(array $codigos)
	{
		$unicos = array();
		foreach ($codigos as $c) {
			$c = strtoupper(trim((string) $c));
			if ($c !== "") {
				$unicos[$c] = true;
			}
		}
		$lista = array_keys($unicos);
		if (empty($lista)) {
			return array();
		}

		$mapa = array();
		$pdo = Conexion::conectar();
		foreach (array_chunk($lista, 200) as $chunk) {
			$placeholders = array();
			foreach ($chunk as $i => $c) {
				$placeholders[] = ":c" . $i;
			}
			$sql = "SELECT
					UPPER(CONCAT(TRIM(t.Des_Corta), TRIM(t.Valor_3))) AS codigo_sublinea,
					TRIM(t.Des_Larga) AS nombre
				FROM Tabla_M_Detalle t
				WHERE t.Cod_Tabla = 'TSUB'
				  AND t.Estado = '1'
				  AND UPPER(CONCAT(TRIM(t.Des_Corta), TRIM(t.Valor_3))) IN ("
				. implode(", ", $placeholders) . ")";
			$stmt = $pdo->prepare($sql);
			foreach ($chunk as $i => $c) {
				$stmt->bindValue(":c" . $i, $c, PDO::PARAM_STR);
			}
			$stmt->execute();
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$mapa[$row["codigo_sublinea"]] = trim((string) $row["nombre"]);
			}
		}
		return $mapa;
	}

	static public function mdlObtenerMp($mpCodigo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				TRIM(p.CodPro) AS mp_codigo,
				p.DesPro AS descripcion,
				TRIM(p.ColPro) AS colpro,
				IFNULL(tc.Des_Larga, '') AS color,
				TRIM(p.UndPro) AS undpro,
				IFNULL(tu.Des_Corta, '') AS unidad,
				LEFT(p.CodFab, 6) AS codigo_sublinea,
				p.FamPro AS fampro,
				p.EstPro AS estado
			 FROM producto p
			 LEFT JOIN Tabla_M_Detalle tc
			   ON tc.Cod_Tabla = 'TCOL'
			  AND tc.Cod_Argumento = p.ColPro
			 LEFT JOIN Tabla_M_Detalle tu
			   ON tu.Cod_Tabla = 'TUND'
			  AND tu.Cod_Argumento = p.UndPro
			 WHERE p.CodPro = :cod
			 LIMIT 1"
		);
		$stmt->bindValue(":cod", $mpCodigo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Insertar cabecera borrador
	=============================================*/
	static private function bindNullable($stmt, $param, $value, $type = PDO::PARAM_STR)
	{
		if ($value === null || $value === "") {
			$stmt->bindValue($param, null, PDO::PARAM_NULL);
			return;
		}
		$stmt->bindValue($param, $value, $type);
	}

	static public function mdlCrearCabecera($datos)
	{
		$pdo = Conexion::conectar();
		$stmt = $pdo->prepare(
			"INSERT INTO recetas_modelo
				(modelo, version, estado, vigente_desde, vigente_hasta, id_usuario_crea, id_usuario_actualiza, created_at)
			 VALUES
				(:modelo, :version, 'BORRADOR', :vigente_desde, :vigente_hasta, :id_usuario_crea, :id_usuario_actualiza, NOW())"
		);
		$stmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindValue(":version", (int) $datos["version"], PDO::PARAM_INT);
		self::bindNullable($stmt, ":vigente_desde", $datos["vigente_desde"], PDO::PARAM_STR);
		self::bindNullable($stmt, ":vigente_hasta", $datos["vigente_hasta"], PDO::PARAM_STR);
		self::bindNullable($stmt, ":id_usuario_crea", $datos["id_usuario"], PDO::PARAM_INT);
		self::bindNullable($stmt, ":id_usuario_actualiza", $datos["id_usuario"], PDO::PARAM_INT);

		if (!$stmt->execute()) {
			return false;
		}

		return (int) $pdo->lastInsertId();
	}

	static public function mdlTocarCabecera($id, $idUsuario)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE recetas_modelo
			 SET id_usuario_actualiza = :usu,
			     updated_at = NOW()
			 WHERE id = :id"
		);
		$stmt->bindValue(":usu", (int) $idUsuario, PDO::PARAM_INT);
		$stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);

		return $stmt->execute();
	}

	/*=============================================
	Reemplazar líneas + variantes de un borrador (transacción)
	=============================================*/
	static public function mdlReemplazarLineasBorrador($idReceta, $lineas, $idUsuario)
	{
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();

		try {
			$ids = array();
			$stmtIds = $pdo->prepare(
				"SELECT id FROM recetas_modelo_detalles WHERE id_receta_modelo = :id"
			);
			$stmtIds->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
			$stmtIds->execute();
			foreach ($stmtIds->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$ids[] = (int) $row["id"];
			}

			if (!empty($ids)) {
				$placeholders = implode(",", array_fill(0, count($ids), "?"));
				$delVar = $pdo->prepare(
					"DELETE FROM recetas_modelo_variantes
					 WHERE id_receta_modelo_detalle IN ($placeholders)"
				);
				$delVar->execute($ids);

				$delDet = $pdo->prepare(
					"DELETE FROM recetas_modelo_detalles WHERE id_receta_modelo = ?"
				);
				$delDet->execute(array((int) $idReceta));
			}

			$stmtDet = $pdo->prepare(
				"INSERT INTO recetas_modelo_detalles
					(id_receta_modelo, orden, nombre_rol, es_tela_principal, codigo_sublinea,
					 regla_variante, unidad, consumo_base, mp_base_codigo, activo)
				 VALUES
					(:id_receta, :orden, :nombre_rol, :es_tela, :sublinea,
					 :regla, :unidad, :consumo_base, :mp_base, :activo)"
			);

			$stmtVar = $pdo->prepare(
				"INSERT INTO recetas_modelo_variantes
					(id_receta_modelo_detalle, cod_color, cod_talla, mp_codigo, consumo, observacion)
				 VALUES
					(:id_det, :cod_color, :cod_talla, :mp_codigo, :consumo, :observacion)"
			);

			foreach ($lineas as $linea) {
				$stmtDet->bindValue(":id_receta", (int) $idReceta, PDO::PARAM_INT);
				$stmtDet->bindValue(":orden", (int) $linea["orden"], PDO::PARAM_INT);
				$stmtDet->bindValue(":nombre_rol", $linea["nombre_rol"], PDO::PARAM_STR);
				$stmtDet->bindValue(":es_tela", (int) $linea["es_tela_principal"], PDO::PARAM_INT);
				self::bindNullable($stmtDet, ":sublinea", $linea["codigo_sublinea"], PDO::PARAM_STR);
				$stmtDet->bindValue(":regla", $linea["regla_variante"], PDO::PARAM_STR);
				self::bindNullable($stmtDet, ":unidad", $linea["unidad"], PDO::PARAM_STR);
				self::bindNullable($stmtDet, ":consumo_base", $linea["consumo_base"], PDO::PARAM_STR);
				self::bindNullable($stmtDet, ":mp_base", $linea["mp_base_codigo"], PDO::PARAM_STR);
				$stmtDet->bindValue(":activo", (int) $linea["activo"], PDO::PARAM_INT);
				$stmtDet->execute();

				$idDetalle = (int) $pdo->lastInsertId();
				$variantes = isset($linea["variantes"]) && is_array($linea["variantes"])
					? $linea["variantes"]
					: array();

				foreach ($variantes as $variante) {
					$stmtVar->bindValue(":id_det", $idDetalle, PDO::PARAM_INT);
					$stmtVar->bindValue(":cod_color", $variante["cod_color"], PDO::PARAM_STR);
					$stmtVar->bindValue(":cod_talla", $variante["cod_talla"], PDO::PARAM_STR);
					$stmtVar->bindValue(":mp_codigo", $variante["mp_codigo"], PDO::PARAM_STR);
					self::bindNullable($stmtVar, ":consumo", $variante["consumo"], PDO::PARAM_STR);
					self::bindNullable($stmtVar, ":observacion", $variante["observacion"], PDO::PARAM_STR);
					$stmtVar->execute();
				}
			}

			$upd = $pdo->prepare(
				"UPDATE recetas_modelo
				 SET id_usuario_actualiza = :usu, updated_at = NOW()
				 WHERE id = :id"
			);
			$upd->bindValue(":usu", (int) $idUsuario, PDO::PARAM_INT);
			$upd->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
			$upd->execute();

			$pdo->commit();
			return true;
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return false;
		}
	}

	/*=============================================
	Modelos con tarjetas y sin receta aún (candidatos a importar)
	=============================================*/
	static public function mdlListarModelosParaImportarTarjetas()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				a.modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), a.modelo) AS nombre_modelo,
				COUNT(DISTINCT a.articulo) AS articulos_con_tarjeta
			 FROM articulojf a
			 INNER JOIN detalles_tarjetajf dt
			   ON dt.articulo = a.articulo
			 LEFT JOIN modelojf m
			   ON m.modelo = a.modelo
			 WHERE a.estado = 'Activo'
			   AND TRIM(IFNULL(a.modelo, '')) <> ''
			   AND NOT EXISTS (
					SELECT 1
					FROM recetas_modelo r
					WHERE r.modelo = a.modelo
			   )
			 GROUP BY a.modelo, m.nombre
			 ORDER BY a.modelo ASC"
		);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Detalles de tarjetas antiguas por modelo (artículos activos)
	=============================================*/
	static public function mdlDetallesTarjetasPorModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				a.articulo,
				a.modelo,
				IFNULL(a.cod_color, '') AS cod_color,
				IFNULL(a.color, '') AS color,
				IFNULL(a.cod_talla, '') AS cod_talla,
				IFNULL(a.talla, '') AS talla,
				TRIM(dt.mat_pri) AS mp_codigo,
				ROUND(dt.consumo, 6) AS consumo,
				LOWER(TRIM(IFNULL(dt.tej_princ, ''))) AS tej_princ,
				LEFT(TRIM(IFNULL(p.CodFab, '')), 6) AS codigo_sublinea,
				IFNULL(p.DesPro, '') AS mp_descripcion,
				IFNULL(tu.Des_Corta, '') AS unidad,
				IFNULL(ts.Des_Larga, '') AS nombre_sublinea
			 FROM articulojf a
			 INNER JOIN detalles_tarjetajf dt
			   ON dt.articulo = a.articulo
			 LEFT JOIN producto p
			   ON TRIM(p.CodPro) = TRIM(dt.mat_pri)
			 LEFT JOIN Tabla_M_Detalle tu
			   ON tu.Cod_Tabla = 'TUND'
			  AND tu.Cod_Argumento = p.UndPro
			 LEFT JOIN Tabla_M_Detalle ts
			   ON ts.Cod_Tabla = 'TSUB'
			  AND CONCAT(TRIM(ts.Des_Corta), TRIM(ts.Valor_3)) = LEFT(TRIM(IFNULL(p.CodFab, '')), 6)
			 WHERE a.modelo = :modelo
			   AND a.estado = 'Activo'
			 ORDER BY a.cod_color ASC, a.cod_talla ASC, a.articulo ASC, dt.id ASC"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Artículo por código
	=============================================*/
	static public function mdlArticuloPorCodigo($articulo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT articulo, modelo, cod_color, color, cod_talla, talla, estado
			 FROM articulojf
			 WHERE articulo = :articulo
			 LIMIT 1"
		);
		$stmt->bindValue(":articulo", $articulo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Lookup en lote de artículos (import Excel)
	=============================================*/
	static public function mdlArticulosPorCodigos(array $codigos)
	{
		$unicos = array();
		foreach ($codigos as $c) {
			$c = trim((string) $c);
			if ($c !== "") {
				$unicos[$c] = true;
			}
		}
		$lista = array_keys($unicos);
		if (empty($lista)) {
			return array();
		}

		$mapa = array();
		$pdo = Conexion::conectar();
		foreach (array_chunk($lista, 400) as $chunk) {
			$placeholders = array();
			foreach ($chunk as $i => $c) {
				$placeholders[] = ":a" . $i;
			}
			$sql = "SELECT
					a.articulo,
					a.modelo,
					IFNULL(a.cod_color, '') AS cod_color,
					IFNULL(a.color, '') AS color,
					IFNULL(a.cod_talla, '') AS cod_talla,
					IFNULL(a.talla, '') AS talla,
					a.estado
				FROM articulojf a
				WHERE a.articulo IN (" . implode(", ", $placeholders) . ")";
			$stmt = $pdo->prepare($sql);
			foreach ($chunk as $i => $c) {
				$stmt->bindValue(":a" . $i, $c, PDO::PARAM_STR);
			}
			$stmt->execute();
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$mapa[$row["articulo"]] = $row;
			}
		}
		return $mapa;
	}

	/*=============================================
	Receta PUBLICADA vigente del modelo (opcional por fechas)
	=============================================*/
	static public function mdlRecetaPublicadaModelo($modelo, $fecha = null)
	{
		if ($fecha === null || $fecha === "") {
			$fecha = date("Y-m-d");
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT r.*,
				IFNULL(NULLIF(TRIM(m.nombre), ''), r.modelo) AS nombre_modelo
			 FROM recetas_modelo r
			 LEFT JOIN modelojf m ON m.modelo = r.modelo
			 WHERE r.modelo = :modelo
			   AND r.estado = 'PUBLICADA'
			   AND (r.vigente_desde IS NULL OR r.vigente_desde <= :fecha1)
			   AND (r.vigente_hasta IS NULL OR r.vigente_hasta >= :fecha2)
			 ORDER BY r.version DESC
			 LIMIT 1"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->bindValue(":fecha1", $fecha, PDO::PARAM_STR);
		$stmt->bindValue(":fecha2", $fecha, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/*=============================================
	Info de MPs por lote de códigos
	=============================================*/
	static public function mdlInfoMps(array $codigos)
	{
		$unicos = array();
		foreach ($codigos as $c) {
			$c = trim((string) $c);
			if ($c !== "") {
				$unicos[$c] = true;
			}
		}
		$lista = array_keys($unicos);
		if (empty($lista)) {
			return array();
		}

		$mapa = array();
		$pdo = Conexion::conectar();
		foreach (array_chunk($lista, 400) as $chunk) {
			$placeholders = array();
			foreach ($chunk as $i => $c) {
				$placeholders[] = ":p" . $i;
			}
			$sql = "SELECT
					TRIM(p.CodPro) AS mp_codigo,
					p.DesPro AS descripcion,
					IFNULL(tc.Des_Larga, '') AS color,
					IFNULL(tu.Des_Corta, '') AS unidad,
					LEFT(p.CodFab, 6) AS codigo_sublinea
				FROM producto p
				LEFT JOIN Tabla_M_Detalle tc
				  ON tc.Cod_Tabla = 'TCOL' AND tc.Cod_Argumento = p.ColPro
				LEFT JOIN Tabla_M_Detalle tu
				  ON tu.Cod_Tabla = 'TUND' AND tu.Cod_Argumento = p.UndPro
				WHERE p.CodPro IN (" . implode(", ", $placeholders) . ")";
			$stmt = $pdo->prepare($sql);
			foreach ($chunk as $i => $c) {
				$stmt->bindValue(":p" . $i, $c, PDO::PARAM_STR);
			}
			$stmt->execute();
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$mapa[$row["mp_codigo"]] = $row;
			}
		}
		return $mapa;
	}

	/*=============================================
	Publicar borrador: archiva PUBLICADA previa del modelo
	=============================================*/
	static public function mdlPublicarReceta($idReceta, $idUsuario, $vigenteDesde = null)
	{
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();

		try {
			$stmt = $pdo->prepare(
				"SELECT id, modelo, estado FROM recetas_modelo WHERE id = :id LIMIT 1 FOR UPDATE"
			);
			$stmt->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
			$stmt->execute();
			$cab = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$cab) {
				throw new Exception("Receta no encontrada");
			}
			if ($cab["estado"] !== "BORRADOR") {
				throw new Exception("Solo se publica un BORRADOR");
			}

			$arch = $pdo->prepare(
				"UPDATE recetas_modelo
				 SET estado = 'ARCHIVADA',
				     vigente_hasta = IFNULL(vigente_hasta, CURDATE()),
				     id_usuario_actualiza = :usu,
				     updated_at = NOW()
				 WHERE modelo = :modelo
				   AND estado = 'PUBLICADA'
				   AND id <> :id"
			);
			$arch->bindValue(":usu", (int) $idUsuario, PDO::PARAM_INT);
			$arch->bindValue(":modelo", $cab["modelo"], PDO::PARAM_STR);
			$arch->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
			$arch->execute();

			$pub = $pdo->prepare(
				"UPDATE recetas_modelo
				 SET estado = 'PUBLICADA',
				     vigente_desde = IFNULL(:desde, IFNULL(vigente_desde, CURDATE())),
				     id_usuario_actualiza = :usu,
				     updated_at = NOW()
				 WHERE id = :id"
			);
			if ($vigenteDesde === null || $vigenteDesde === "") {
				$pub->bindValue(":desde", null, PDO::PARAM_NULL);
			} else {
				$pub->bindValue(":desde", $vigenteDesde, PDO::PARAM_STR);
			}
			$pub->bindValue(":usu", (int) $idUsuario, PDO::PARAM_INT);
			$pub->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
			$pub->execute();

			$pdo->commit();
			return true;
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return false;
		}
	}

	/*=============================================
	Duplicar receta a nuevo BORRADOR (misma estructura)
	=============================================*/
	static public function mdlDuplicarABorrador($idOrigen, $idUsuario)
	{
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();

		try {
			$stmt = $pdo->prepare(
				"SELECT * FROM recetas_modelo WHERE id = :id LIMIT 1"
			);
			$stmt->bindValue(":id", (int) $idOrigen, PDO::PARAM_INT);
			$stmt->execute();
			$origen = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$origen) {
				throw new Exception("Origen no encontrado");
			}

			$max = $pdo->prepare(
				"SELECT IFNULL(MAX(version), 0) AS mx FROM recetas_modelo WHERE modelo = :modelo"
			);
			$max->bindValue(":modelo", $origen["modelo"], PDO::PARAM_STR);
			$max->execute();
			$version = (int) $max->fetch(PDO::FETCH_ASSOC)["mx"] + 1;

			$ins = $pdo->prepare(
				"INSERT INTO recetas_modelo
					(modelo, version, estado, vigente_desde, vigente_hasta,
					 id_usuario_crea, id_usuario_actualiza, created_at)
				 VALUES
					(:modelo, :version, 'BORRADOR', NULL, NULL, :usu, :usu2, NOW())"
			);
			$ins->bindValue(":modelo", $origen["modelo"], PDO::PARAM_STR);
			$ins->bindValue(":version", $version, PDO::PARAM_INT);
			$ins->bindValue(":usu", (int) $idUsuario, PDO::PARAM_INT);
			$ins->bindValue(":usu2", (int) $idUsuario, PDO::PARAM_INT);
			$ins->execute();
			$idNuevo = (int) $pdo->lastInsertId();

			$dets = $pdo->prepare(
				"SELECT * FROM recetas_modelo_detalles WHERE id_receta_modelo = :id ORDER BY orden, id"
			);
			$dets->bindValue(":id", (int) $idOrigen, PDO::PARAM_INT);
			$dets->execute();
			$detalles = $dets->fetchAll(PDO::FETCH_ASSOC);

			$insDet = $pdo->prepare(
				"INSERT INTO recetas_modelo_detalles
					(id_receta_modelo, orden, nombre_rol, es_tela_principal, codigo_sublinea,
					 regla_variante, unidad, consumo_base, mp_base_codigo, activo)
				 VALUES
					(:id_receta, :orden, :nombre_rol, :es_tela, :sublinea,
					 :regla, :unidad, :consumo_base, :mp_base, :activo)"
			);
			$insVar = $pdo->prepare(
				"INSERT INTO recetas_modelo_variantes
					(id_receta_modelo_detalle, cod_color, cod_talla, mp_codigo, consumo, observacion)
				 VALUES
					(:id_det, :cod_color, :cod_talla, :mp_codigo, :consumo, :observacion)"
			);

			foreach ($detalles as $d) {
				$insDet->bindValue(":id_receta", $idNuevo, PDO::PARAM_INT);
				$insDet->bindValue(":orden", (int) $d["orden"], PDO::PARAM_INT);
				$insDet->bindValue(":nombre_rol", $d["nombre_rol"], PDO::PARAM_STR);
				$insDet->bindValue(":es_tela", (int) $d["es_tela_principal"], PDO::PARAM_INT);
				self::bindNullable($insDet, ":sublinea", $d["codigo_sublinea"], PDO::PARAM_STR);
				$insDet->bindValue(":regla", $d["regla_variante"], PDO::PARAM_STR);
				self::bindNullable($insDet, ":unidad", $d["unidad"], PDO::PARAM_STR);
				self::bindNullable($insDet, ":consumo_base", $d["consumo_base"], PDO::PARAM_STR);
				self::bindNullable($insDet, ":mp_base", $d["mp_base_codigo"], PDO::PARAM_STR);
				$insDet->bindValue(":activo", (int) $d["activo"], PDO::PARAM_INT);
				$insDet->execute();
				$idDetNuevo = (int) $pdo->lastInsertId();

				$vars = $pdo->prepare(
					"SELECT * FROM recetas_modelo_variantes WHERE id_receta_modelo_detalle = :id"
				);
				$vars->bindValue(":id", (int) $d["id"], PDO::PARAM_INT);
				$vars->execute();
				foreach ($vars->fetchAll(PDO::FETCH_ASSOC) as $v) {
					$insVar->bindValue(":id_det", $idDetNuevo, PDO::PARAM_INT);
					$insVar->bindValue(":cod_color", $v["cod_color"], PDO::PARAM_STR);
					$insVar->bindValue(":cod_talla", $v["cod_talla"], PDO::PARAM_STR);
					$insVar->bindValue(":mp_codigo", $v["mp_codigo"], PDO::PARAM_STR);
					self::bindNullable($insVar, ":consumo", $v["consumo"], PDO::PARAM_STR);
					self::bindNullable($insVar, ":observacion", $v["observacion"], PDO::PARAM_STR);
					$insVar->execute();
				}
			}

			$pdo->commit();
			return array("id" => $idNuevo, "version" => $version, "modelo" => $origen["modelo"]);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return false;
		}
	}

	/*=============================================
	Eliminar una receta (cualquier estado) + líneas + variantes
	=============================================*/
	static public function mdlEliminarReceta($idReceta)
	{
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();

		try {
			$stmt = $pdo->prepare(
				"SELECT id, modelo, version, estado FROM recetas_modelo WHERE id = :id LIMIT 1 FOR UPDATE"
			);
			$stmt->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
			$stmt->execute();
			$cab = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$cab) {
				throw new Exception("Receta no encontrada");
			}

			$ids = array();
			$stmtIds = $pdo->prepare(
				"SELECT id FROM recetas_modelo_detalles WHERE id_receta_modelo = :id"
			);
			$stmtIds->bindValue(":id", (int) $idReceta, PDO::PARAM_INT);
			$stmtIds->execute();
			foreach ($stmtIds->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$ids[] = (int) $row["id"];
			}

			if (!empty($ids)) {
				$placeholders = implode(",", array_fill(0, count($ids), "?"));
				$delVar = $pdo->prepare(
					"DELETE FROM recetas_modelo_variantes
					 WHERE id_receta_modelo_detalle IN ($placeholders)"
				);
				$delVar->execute($ids);

				$delDet = $pdo->prepare(
					"DELETE FROM recetas_modelo_detalles WHERE id_receta_modelo = ?"
				);
				$delDet->execute(array((int) $idReceta));
			}

			$delCab = $pdo->prepare("DELETE FROM recetas_modelo WHERE id = ?");
			$delCab->execute(array((int) $idReceta));

			$pdo->commit();
			return array("ok" => true, "cabecera" => $cab);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => $e->getMessage());
		}
	}

	/*=============================================
	Eliminar BORRADOR completo (cabecera + líneas + variantes)
	=============================================*/
	static public function mdlEliminarBorrador($idReceta)
	{
		$cab = self::mdlObtenerCabecera($idReceta);
		if (!$cab) {
			return false;
		}
		if ($cab["estado"] !== "BORRADOR") {
			return false;
		}
		$res = self::mdlEliminarReceta($idReceta);
		return !empty($res["ok"]);
	}

	/*=============================================
	Eliminar TODAS las recetas del módulo
	=============================================*/
	static public function mdlEliminarTodasRecetas()
	{
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();

		try {
			$nCab = (int) $pdo->query("SELECT COUNT(*) FROM recetas_modelo")->fetchColumn();
			$nDet = (int) $pdo->query("SELECT COUNT(*) FROM recetas_modelo_detalles")->fetchColumn();
			$nVar = (int) $pdo->query("SELECT COUNT(*) FROM recetas_modelo_variantes")->fetchColumn();

			$pdo->exec("DELETE FROM recetas_modelo_variantes");
			$pdo->exec("DELETE FROM recetas_modelo_detalles");
			$pdo->exec("DELETE FROM recetas_modelo");

			$pdo->commit();
			return array(
				"ok" => true,
				"recetas" => $nCab,
				"detalles" => $nDet,
				"variantes" => $nVar,
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => $e->getMessage());
		}
	}
}
