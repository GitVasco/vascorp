<?php

require_once "conexion.php";

date_default_timezone_set('America/Lima');

class ModeloModeloColorTaller
{

	static public function mdlListar($filtros = array())
	{
		$sql = "SELECT mct.*,
				mo.nombre AS nombre_modelo,
				s.nom_sector
			FROM modelo_color_tallerjf mct
			LEFT JOIN modelojf mo ON TRIM(mo.modelo) = TRIM(mct.modelo)
			LEFT JOIN sectorjf s ON s.cod_sector = mct.cod_sector
			WHERE 1 = 1";

		$params = array();

		if (!empty($filtros["modelo"])) {
			$sql .= " AND TRIM(mct.modelo) = :modelo";
			$params[":modelo"] = trim((string) $filtros["modelo"]);
		}
		if (isset($filtros["cod_color"]) && $filtros["cod_color"] !== "" && $filtros["cod_color"] !== null) {
			if ($filtros["cod_color"] === "__SIN_COLOR__") {
				$sql .= " AND mct.cod_color = ''";
			} else {
				$sql .= " AND TRIM(mct.cod_color) = :cod_color";
				$params[":cod_color"] = trim((string) $filtros["cod_color"]);
			}
		}
		if (!empty($filtros["cod_sector"])) {
			$sql .= " AND mct.cod_sector = :cod_sector";
			$params[":cod_sector"] = trim((string) $filtros["cod_sector"]);
		}
		if (isset($filtros["estado"]) && $filtros["estado"] !== "") {
			$sql .= " AND mct.estado = :estado";
			$params[":estado"] = (int) $filtros["estado"];
		}

		$sql .= " ORDER BY mct.modelo ASC, mct.cod_color ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlMostrar($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT mct.*,
				mo.nombre AS nombre_modelo,
				s.nom_sector
			 FROM modelo_color_tallerjf mct
			 LEFT JOIN modelojf mo ON TRIM(mo.modelo) = TRIM(mct.modelo)
			 LEFT JOIN sectorjf s ON s.cod_sector = mct.cod_sector
			 WHERE mct.id = :id
			 LIMIT 1"
		);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		return $row ? $row : null;
	}

	static public function mdlIdPorModeloColor($modelo, $codColor)
	{
		$modelo = trim((string) $modelo);
		$codColor = trim((string) $codColor);
		$stmt = Conexion::conectar()->prepare(
			"SELECT id FROM modelo_color_tallerjf
			 WHERE TRIM(modelo) = :modelo AND TRIM(cod_color) = :cod_color
			 LIMIT 1"
		);
		$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->bindParam(":cod_color", $codColor, PDO::PARAM_STR);
		$stmt->execute();
		$id = $stmt->fetchColumn();
		return $id !== false ? (int) $id : 0;
	}

	static public function mdlExisteModeloColor($modelo, $codColor, $excluirId = 0)
	{
		$id = self::mdlIdPorModeloColor($modelo, $codColor);
		if ($id < 1) {
			return false;
		}
		$excluirId = (int) $excluirId;
		return $excluirId < 1 || $id !== $excluirId;
	}

	static private function mdlBindNullable($stmt, $param, $value)
	{
		if ($value === null) {
			$stmt->bindValue($param, null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue($param, $value, PDO::PARAM_STR);
		}
	}

	static public function mdlCrear($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO modelo_color_tallerjf
				(modelo, cod_color, nom_color, cod_sector, estado, observacion, usureg)
			 VALUES
				(:modelo, :cod_color, :nom_color, :cod_sector, :estado, :observacion, :usureg)"
		);
		$stmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindValue(":cod_color", $datos["cod_color"], PDO::PARAM_STR);
		self::mdlBindNullable($stmt, ":nom_color", $datos["nom_color"]);
		$stmt->bindValue(":cod_sector", $datos["cod_sector"], PDO::PARAM_STR);
		$stmt->bindValue(":estado", (int) $datos["estado"], PDO::PARAM_INT);
		self::mdlBindNullable($stmt, ":observacion", $datos["observacion"]);
		$stmt->bindValue(":usureg", $datos["usureg"], PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEditar($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE modelo_color_tallerjf SET
				modelo = :modelo,
				cod_color = :cod_color,
				nom_color = :nom_color,
				cod_sector = :cod_sector,
				estado = :estado,
				observacion = :observacion,
				usumod = :usumod,
				fecmod = NOW()
			 WHERE id = :id"
		);
		$stmt->bindValue(":id", (int) $datos["id"], PDO::PARAM_INT);
		$stmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindValue(":cod_color", $datos["cod_color"], PDO::PARAM_STR);
		self::mdlBindNullable($stmt, ":nom_color", $datos["nom_color"]);
		$stmt->bindValue(":cod_sector", $datos["cod_sector"], PDO::PARAM_STR);
		$stmt->bindValue(":estado", (int) $datos["estado"], PDO::PARAM_INT);
		self::mdlBindNullable($stmt, ":observacion", $datos["observacion"]);
		$stmt->bindValue(":usumod", $datos["usumod"], PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEliminar($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return "error";
		}
		$stmt = Conexion::conectar()->prepare(
			"DELETE FROM modelo_color_tallerjf WHERE id = :id LIMIT 1"
		);
		$stmt->bindValue(":id", $id, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	/**
	 * Resolver taller por defecto (para uso futuro en envío a taller).
	 * Prioridad: modelo+color exacto activo → modelo sin color activo.
	 */
	static public function mdlResolverTaller($modelo, $codColor = "")
	{
		$modelo = trim((string) $modelo);
		$codColor = trim((string) $codColor);
		if ($modelo === "") {
			return null;
		}

		if ($codColor !== "") {
			$stmt = Conexion::conectar()->prepare(
				"SELECT * FROM modelo_color_tallerjf
				 WHERE TRIM(modelo) = :modelo
				   AND TRIM(cod_color) = :cod_color
				   AND estado = 1
				 LIMIT 1"
			);
			$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);
			$stmt->bindParam(":cod_color", $codColor, PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch();
			if ($row) {
				return $row;
			}
		}

		$vacio = "";
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM modelo_color_tallerjf
			 WHERE TRIM(modelo) = :modelo
			   AND cod_color = :vacio
			   AND estado = 1
			 LIMIT 1"
		);
		$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->bindParam(":vacio", $vacio, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch();
		return $row ? $row : null;
	}

	static public function mdlExisteModelo($modelo)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM modelojf WHERE TRIM(modelo) = :modelo LIMIT 1"
		);
		$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		return (bool) $stmt->fetchColumn();
	}

	static public function mdlExisteSector($codSector)
	{
		$codSector = trim((string) $codSector);
		if ($codSector === "") {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM sectorjf WHERE cod_sector = :cod LIMIT 1"
		);
		$stmt->bindParam(":cod", $codSector, PDO::PARAM_STR);
		$stmt->execute();
		return (bool) $stmt->fetchColumn();
	}

	static public function mdlNombreColor($codColor)
	{
		$codColor = trim((string) $codColor);
		if ($codColor === "") {
			return null;
		}
		// colorjf puede tener códigos duplicados: tomar un nombre representativo
		$stmt = Conexion::conectar()->prepare(
			"SELECT MAX(IFNULL(NULLIF(TRIM(nom_color), ''), TRIM(cod_color))) AS nom_color
			 FROM colorjf
			 WHERE TRIM(cod_color) = :cod"
		);
		$stmt->bindParam(":cod", $codColor, PDO::PARAM_STR);
		$stmt->execute();
		$nom = $stmt->fetchColumn();
		return ($nom !== false && $nom !== null && $nom !== "") ? (string) $nom : null;
	}

	static public function mdlListarColoresCatalogo()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(cod_color) AS cod_color,
				MAX(nom_color) AS nom_color
			 FROM colorjf
			 WHERE TRIM(IFNULL(cod_color, '')) <> ''
			 GROUP BY TRIM(cod_color)
			 ORDER BY MAX(nom_color) ASC, TRIM(cod_color) ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/**
	 * Artículos activos cubiertos por la config, agrupados por taller resuelto
	 * (color específico gana sobre regla general del modelo).
	 */
	static public function mdlResumenArticulosPorTaller()
	{
		$sql = "SELECT
				res.cod_sector,
				IFNULL(MAX(s.nom_sector), res.cod_sector) AS nom_sector,
				COUNT(*) AS total_articulos
			FROM (
				SELECT
					a.articulo,
					COALESCE(esp.cod_sector, gen.cod_sector) AS cod_sector
				FROM articulojf a
				LEFT JOIN modelo_color_tallerjf esp
					ON esp.estado = 1
					AND TRIM(esp.modelo) = TRIM(a.modelo)
					AND TRIM(esp.cod_color) = TRIM(IFNULL(a.cod_color, ''))
					AND TRIM(IFNULL(esp.cod_color, '')) <> ''
				LEFT JOIN modelo_color_tallerjf gen
					ON gen.estado = 1
					AND TRIM(gen.modelo) = TRIM(a.modelo)
					AND TRIM(IFNULL(gen.cod_color, '')) = ''
				WHERE UPPER(TRIM(IFNULL(a.estado, ''))) = 'ACTIVO'
					AND COALESCE(esp.cod_sector, gen.cod_sector) IS NOT NULL
			) res
			LEFT JOIN sectorjf s ON s.cod_sector = res.cod_sector
			GROUP BY res.cod_sector
			ORDER BY total_articulos DESC, res.cod_sector ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll();
	}
}
