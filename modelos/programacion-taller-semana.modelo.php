<?php

require_once "conexion.php";

date_default_timezone_set('America/Lima');

class ModeloProgramacionTallerSemana
{

	static public function mdlRutaJsonNiveles()
	{
		return dirname(__FILE__) . "/../vistas/js/data/niveles-urgencia-programacion.json";
	}

	static public function mdlCargarNiveles()
	{
		$path = self::mdlRutaJsonNiveles();
		if (!is_file($path)) {
			return array("niveles" => array(), "nivel_default" => "prioridad");
		}
		$raw = file_get_contents($path);
		$data = json_decode($raw, true);
		if (!is_array($data) || empty($data["niveles"])) {
			return array("niveles" => array(), "nivel_default" => "prioridad");
		}
		return $data;
	}

	/**
	 * Lunes–domingo de una semana ISO (anio + semana).
	 * @return array{anio:int,semana:int,fecha_inicio:string,fecha_fin:string}|null
	 */
	static public function mdlRangoSemana($anio, $semana)
	{
		$anio = (int) $anio;
		$semana = (int) $semana;
		if ($anio < 2000 || $anio > 2100 || $semana < 1 || $semana > 53) {
			return null;
		}
		$dt = new DateTime();
		$dt->setISODate($anio, $semana, 1); // lunes
		$inicio = $dt->format("Y-m-d");
		$dt->modify("+6 days");
		$fin = $dt->format("Y-m-d");
		return array(
			"anio" => $anio,
			"semana" => $semana,
			"fecha_inicio" => $inicio,
			"fecha_fin" => $fin
		);
	}

	/** Semana ISO actual (Lima). */
	static public function mdlSemanaActual()
	{
		$dt = new DateTime("now");
		return array(
			"anio" => (int) $dt->format("o"),
			"semana" => (int) $dt->format("W")
		);
	}

	static public function mdlListarProgramados($filtros = array())
	{
		$sql = "SELECT p.*,
				s.nom_sector,
				s.color AS color_taller,
				IFNULL(sal.alm_corte, 0) AS alm_corte_vivo,
				IFNULL(sal.ord_corte, 0) AS ord_corte_vivo,
				IFNULL(sal.saldo_disponible, 0) AS saldo_vivo
			FROM programacion_taller_semanajf p
			LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
			LEFT JOIN (
				SELECT
					TRIM(a.modelo) AS modelo,
					TRIM(IFNULL(a.cod_color, '')) AS cod_color,
					SUM(IFNULL(a.alm_corte, 0)) AS alm_corte,
					SUM(IFNULL(a.ord_corte, 0)) AS ord_corte,
					SUM(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) AS saldo_disponible
				FROM articulojf a
				WHERE (
						LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
						OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
					)
				  AND TRIM(IFNULL(a.modelo, '')) <> ''
				GROUP BY TRIM(a.modelo), TRIM(IFNULL(a.cod_color, ''))
			) sal ON sal.modelo = TRIM(p.modelo)
				AND sal.cod_color = TRIM(IFNULL(p.cod_color, ''))
			WHERE p.estado = 1";
		$params = array();

		if (!empty($filtros["anio"])) {
			$sql .= " AND p.anio = :anio";
			$params[":anio"] = (int) $filtros["anio"];
		}
		if (!empty($filtros["semana"])) {
			$sql .= " AND p.semana = :semana";
			$params[":semana"] = (int) $filtros["semana"];
		}
		if (!empty($filtros["cod_sector"])) {
			$sql .= " AND p.cod_sector = :cod_sector";
			$params[":cod_sector"] = trim((string) $filtros["cod_sector"]);
		}
		if (!empty($filtros["nivel"])) {
			$sql .= " AND p.nivel = :nivel";
			$params[":nivel"] = trim((string) $filtros["nivel"]);
		}
		if (!empty($filtros["modelo"])) {
			$sql .= " AND TRIM(p.modelo) = :modelo";
			$params[":modelo"] = trim((string) $filtros["modelo"]);
		}

		$sql .= " ORDER BY p.cod_sector ASC, p.modelo ASC, p.cod_color ASC";

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
			"SELECT p.*, s.nom_sector, s.color AS color_taller
			 FROM programacion_taller_semanajf p
			 LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
			 WHERE p.id = :id LIMIT 1"
		);
		$stmt->bindValue(":id", $id, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();
		return $row ? $row : null;
	}

	static public function mdlIdExistente($anio, $semana, $modelo, $codColor, $codSector, $soloActivos = false)
	{
		$sql = "SELECT id FROM programacion_taller_semanajf
			 WHERE anio = :anio AND semana = :semana
			   AND TRIM(modelo) = :modelo
			   AND TRIM(IFNULL(cod_color, '')) = :cod_color
			   AND cod_sector = :cod_sector";
		if ($soloActivos) {
			$sql .= " AND estado = 1";
		}
		$sql .= " LIMIT 1";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":semana", (int) $semana, PDO::PARAM_INT);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":cod_color", trim((string) $codColor), PDO::PARAM_STR);
		$stmt->bindValue(":cod_sector", trim((string) $codSector), PDO::PARAM_STR);
		$stmt->execute();
		$id = $stmt->fetchColumn();
		return $id !== false ? (int) $id : 0;
	}

	/** Reactiva una fila eliminada lógicamente. */
	static public function mdlReactivar($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_semanajf SET
				cantidad = :cantidad,
				nivel = :nivel,
				observacion = :observacion,
				saldo_alm_corte = :saldo_alm_corte,
				saldo_ord_corte = :saldo_ord_corte,
				urg_plan = :urg_plan,
				modelo = :modelo,
				cod_color = :cod_color,
				color = :color,
				cod_talla = NULL,
				talla = NULL,
				articulo = :articulo,
				nombre = :nombre,
				fecha_inicio = :fecha_inicio,
				fecha_fin = :fecha_fin,
				estado = 1,
				usumod = :usumod,
				fecmod = NOW()
			 WHERE id = :id"
		);
		$stmt->bindValue(":id", (int) $datos["id"], PDO::PARAM_INT);
		$stmt->bindValue(":cantidad", (int) $datos["cantidad"], PDO::PARAM_INT);
		$stmt->bindValue(":nivel", $datos["nivel"], PDO::PARAM_STR);
		$obs = isset($datos["observacion"]) ? $datos["observacion"] : null;
		if ($obs === null || $obs === "") {
			$stmt->bindValue(":observacion", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":observacion", $obs, PDO::PARAM_STR);
		}
		$stmt->bindValue(":saldo_alm_corte", (int) $datos["saldo_alm_corte"], PDO::PARAM_INT);
		$stmt->bindValue(":saldo_ord_corte", (int) $datos["saldo_ord_corte"], PDO::PARAM_INT);
		if ($datos["urg_plan"] === null || $datos["urg_plan"] === "") {
			$stmt->bindValue(":urg_plan", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":urg_plan", $datos["urg_plan"]);
		}
		$claveColor = trim((string) $datos["modelo"]) . "|" . trim((string) $datos["cod_color"]);
		$stmt->bindValue(":articulo", $claveColor, PDO::PARAM_STR);
		$stmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindValue(":cod_color", $datos["cod_color"], PDO::PARAM_STR);
		$stmt->bindValue(":color", $datos["color"], PDO::PARAM_STR);
		$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindValue(":fecha_inicio", $datos["fecha_inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fecha_fin", $datos["fecha_fin"], PDO::PARAM_STR);
		$stmt->bindValue(":usumod", $datos["usumod"], PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlCrear($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO programacion_taller_semanajf
				(anio, semana, fecha_inicio, fecha_fin, articulo, modelo, cod_color, color,
				 cod_talla, talla, nombre, cod_sector, cantidad, saldo_alm_corte, saldo_ord_corte,
				 nivel, urg_plan, observacion, estado, usureg)
			 VALUES
				(:anio, :semana, :fecha_inicio, :fecha_fin, :articulo, :modelo, :cod_color, :color,
				 NULL, NULL, :nombre, :cod_sector, :cantidad, :saldo_alm_corte, :saldo_ord_corte,
				 :nivel, :urg_plan, :observacion, 1, :usureg)"
		);
		self::mdlBindProgramacion($stmt, $datos, false);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEditar($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_semanajf SET
				cantidad = :cantidad,
				cod_sector = :cod_sector,
				nivel = :nivel,
				observacion = :observacion,
				saldo_alm_corte = :saldo_alm_corte,
				saldo_ord_corte = :saldo_ord_corte,
				urg_plan = :urg_plan,
				usumod = :usumod,
				fecmod = NOW()
			 WHERE id = :id AND estado = 1"
		);
		$stmt->bindValue(":id", (int) $datos["id"], PDO::PARAM_INT);
		$stmt->bindValue(":cantidad", (int) $datos["cantidad"], PDO::PARAM_INT);
		$stmt->bindValue(":cod_sector", $datos["cod_sector"], PDO::PARAM_STR);
		$stmt->bindValue(":nivel", $datos["nivel"], PDO::PARAM_STR);
		$obs = isset($datos["observacion"]) ? $datos["observacion"] : null;
		if ($obs === null || $obs === "") {
			$stmt->bindValue(":observacion", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":observacion", $obs, PDO::PARAM_STR);
		}
		$stmt->bindValue(":saldo_alm_corte", (int) $datos["saldo_alm_corte"], PDO::PARAM_INT);
		$stmt->bindValue(":saldo_ord_corte", (int) $datos["saldo_ord_corte"], PDO::PARAM_INT);
		if ($datos["urg_plan"] === null || $datos["urg_plan"] === "") {
			$stmt->bindValue(":urg_plan", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":urg_plan", $datos["urg_plan"]);
		}
		$stmt->bindValue(":usumod", $datos["usumod"], PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEliminar($id, $usumod = "sistema")
	{
		$id = (int) $id;
		if ($id < 1) {
			return "error";
		}
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_semanajf
			 SET estado = 0, usumod = :usumod, fecmod = NOW()
			 WHERE id = :id"
		);
		$stmt->bindValue(":id", $id, PDO::PARAM_INT);
		$stmt->bindValue(":usumod", (string) $usumod, PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static private function mdlBindProgramacion($stmt, $datos, $esEdicion)
	{
		if (!$esEdicion) {
			$claveColor = trim((string) $datos["modelo"]) . "|" . trim((string) $datos["cod_color"]);
			$stmt->bindValue(":anio", (int) $datos["anio"], PDO::PARAM_INT);
			$stmt->bindValue(":semana", (int) $datos["semana"], PDO::PARAM_INT);
			$stmt->bindValue(":fecha_inicio", $datos["fecha_inicio"], PDO::PARAM_STR);
			$stmt->bindValue(":fecha_fin", $datos["fecha_fin"], PDO::PARAM_STR);
			$stmt->bindValue(":articulo", $claveColor, PDO::PARAM_STR);
			$stmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
			$stmt->bindValue(":cod_color", $datos["cod_color"], PDO::PARAM_STR);
			$stmt->bindValue(":color", $datos["color"], PDO::PARAM_STR);
			$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
			$stmt->bindValue(":usureg", $datos["usureg"], PDO::PARAM_STR);
		}
		$stmt->bindValue(":cod_sector", $datos["cod_sector"], PDO::PARAM_STR);
		$stmt->bindValue(":cantidad", (int) $datos["cantidad"], PDO::PARAM_INT);
		$stmt->bindValue(":saldo_alm_corte", (int) $datos["saldo_alm_corte"], PDO::PARAM_INT);
		$stmt->bindValue(":saldo_ord_corte", (int) $datos["saldo_ord_corte"], PDO::PARAM_INT);
		$stmt->bindValue(":nivel", $datos["nivel"], PDO::PARAM_STR);
		if ($datos["urg_plan"] === null || $datos["urg_plan"] === "") {
			$stmt->bindValue(":urg_plan", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":urg_plan", $datos["urg_plan"]);
		}
		$obs = isset($datos["observacion"]) ? $datos["observacion"] : null;
		if ($obs === null || $obs === "") {
			$stmt->bindValue(":observacion", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":observacion", $obs, PDO::PARAM_STR);
		}
	}

	/**
	 * Candidatos: modelo/color con saldo en alm. corte u OC (suma de tallas).
	 */
	static public function mdlListarCandidatos($filtros = array())
	{
		$sql = "SELECT
				TRIM(a.modelo) AS modelo,
				TRIM(IFNULL(a.cod_color, '')) AS cod_color,
				MAX(a.color) AS color,
				MAX(IFNULL(mj.nombre, a.nombre)) AS nombre,
				MAX(a.estado) AS estado_articulo,
				SUM(IFNULL(a.alm_corte, 0)) AS alm_corte,
				SUM(IFNULL(a.ord_corte, 0)) AS ord_corte,
				SUM(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) AS saldo_disponible,
				ROUND(
					SUM(
						(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0))
						+ IFNULL(a.taller, 0) + IFNULL(a.servicio, 0)
						+ IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)
					) / NULLIF(SUM(IFNULL(a.ult_mes, 0)), 0),
					2
				) AS urg_plan,
				MAX(COALESCE(esp.cod_sector, gen.cod_sector, a.defecto_taller)) AS cod_sector_resuelto,
				MAX(COALESCE(esp_s.nom_sector, gen_s.nom_sector, def_s.nom_sector)) AS nom_sector,
				MAX(COALESCE(esp_s.color, gen_s.color, def_s.color)) AS color_taller
			FROM articulojf a
			LEFT JOIN modelojf mj ON TRIM(mj.modelo) = TRIM(a.modelo)
			LEFT JOIN modelo_color_tallerjf esp
				ON esp.estado = 1
				AND TRIM(esp.modelo) = TRIM(a.modelo)
				AND TRIM(esp.cod_color) = TRIM(IFNULL(a.cod_color, ''))
				AND TRIM(IFNULL(esp.cod_color, '')) <> ''
			LEFT JOIN modelo_color_tallerjf gen
				ON gen.estado = 1
				AND TRIM(gen.modelo) = TRIM(a.modelo)
				AND TRIM(IFNULL(gen.cod_color, '')) = ''
			LEFT JOIN sectorjf esp_s ON esp_s.cod_sector = esp.cod_sector
			LEFT JOIN sectorjf gen_s ON gen_s.cod_sector = gen.cod_sector
			LEFT JOIN sectorjf def_s ON def_s.cod_sector = a.defecto_taller
			WHERE (
					LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
					OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
				)
			  AND TRIM(IFNULL(a.modelo, '')) <> ''";

		$params = array();
		if (!empty($filtros["cod_sector"])) {
			$sql .= " AND COALESCE(esp.cod_sector, gen.cod_sector, a.defecto_taller) = :cod_sector";
			$params[":cod_sector"] = trim((string) $filtros["cod_sector"]);
		}
		if (!empty($filtros["modelo"])) {
			$sql .= " AND TRIM(a.modelo) = :modelo";
			$params[":modelo"] = trim((string) $filtros["modelo"]);
		}

		$sql .= " GROUP BY TRIM(a.modelo), TRIM(IFNULL(a.cod_color, ''))
			HAVING SUM(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) > 0
			ORDER BY modelo ASC, cod_color ASC, urg_plan ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v, PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/** Saldos y taller sugerido para un modelo/color (suma de tallas). */
	static public function mdlColorParaProgramar($modelo, $codColor)
	{
		$modelo = trim((string) $modelo);
		$codColor = trim((string) $codColor);
		if ($modelo === "") {
			return null;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				TRIM(a.modelo) AS modelo,
				TRIM(IFNULL(a.cod_color, '')) AS cod_color,
				MAX(a.color) AS color,
				MAX(IFNULL(mj.nombre, a.nombre)) AS nombre,
				MAX(a.estado) AS estado_articulo,
				SUM(IFNULL(a.alm_corte, 0)) AS alm_corte,
				SUM(IFNULL(a.ord_corte, 0)) AS ord_corte,
				SUM(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) AS saldo_disponible,
				ROUND(
					SUM(
						(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0))
						+ IFNULL(a.taller, 0) + IFNULL(a.servicio, 0)
						+ IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)
					) / NULLIF(SUM(IFNULL(a.ult_mes, 0)), 0),
					2
				) AS urg_plan,
				MAX(COALESCE(esp.cod_sector, gen.cod_sector, a.defecto_taller)) AS cod_sector_resuelto
			FROM articulojf a
			LEFT JOIN modelojf mj ON TRIM(mj.modelo) = TRIM(a.modelo)
			LEFT JOIN modelo_color_tallerjf esp
				ON esp.estado = 1
				AND TRIM(esp.modelo) = TRIM(a.modelo)
				AND TRIM(esp.cod_color) = TRIM(IFNULL(a.cod_color, ''))
				AND TRIM(IFNULL(esp.cod_color, '')) <> ''
			LEFT JOIN modelo_color_tallerjf gen
				ON gen.estado = 1
				AND TRIM(gen.modelo) = TRIM(a.modelo)
				AND TRIM(IFNULL(gen.cod_color, '')) = ''
			WHERE TRIM(a.modelo) = :modelo
			  AND TRIM(IFNULL(a.cod_color, '')) = :cod_color
			  AND (
					LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
					OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
				)
			GROUP BY TRIM(a.modelo), TRIM(IFNULL(a.cod_color, ''))
			LIMIT 1"
		);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->bindValue(":cod_color", $codColor, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch();
		return $row ? $row : null;
	}

	static public function mdlListarModelosCandidatos()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT TRIM(a.modelo) AS modelo,
				MAX(CONCAT(TRIM(a.modelo), ' - ', IFNULL(m.nombre, a.nombre))) AS etiqueta
			 FROM articulojf a
			 LEFT JOIN modelojf m ON TRIM(m.modelo) = TRIM(a.modelo)
			 WHERE (
					LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
					OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
				)
			   AND (IFNULL(a.alm_corte, 0) > 0 OR IFNULL(a.ord_corte, 0) > 0)
			   AND TRIM(IFNULL(a.modelo, '')) <> ''
			 GROUP BY TRIM(a.modelo)
			 ORDER BY TRIM(a.modelo) ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlResumenPorTaller($anio, $semana)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.cod_sector,
				IFNULL(s.nom_sector, p.cod_sector) AS nom_sector,
				IFNULL(s.color, '#A8D5E5') AS color_taller,
				COUNT(*) AS total_lineas,
				SUM(p.cantidad) AS total_cantidad
			 FROM programacion_taller_semanajf p
			 LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
			 WHERE p.estado = 1 AND p.anio = :anio AND p.semana = :semana
			 GROUP BY p.cod_sector, s.nom_sector, s.color
			 ORDER BY total_cantidad DESC, p.cod_sector ASC"
		);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":semana", (int) $semana, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/**
	 * Estadísticas de programación de una semana (totales, por nivel, por taller, pendiente/consumido).
	 */
	static public function mdlEstadisticasSemana($anio, $semana)
	{
		$anio = (int) $anio;
		$semana = (int) $semana;
		$joinSaldo = "
			LEFT JOIN (
				SELECT
					TRIM(a.modelo) AS modelo,
					TRIM(IFNULL(a.cod_color, '')) AS cod_color,
					SUM(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) AS saldo_vivo
				FROM articulojf a
				WHERE (
						LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
						OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
					)
				  AND TRIM(IFNULL(a.modelo, '')) <> ''
				GROUP BY TRIM(a.modelo), TRIM(IFNULL(a.cod_color, ''))
			) sal ON sal.modelo = TRIM(p.modelo)
				AND sal.cod_color = TRIM(IFNULL(p.cod_color, ''))
		";

		$stmtTot = Conexion::conectar()->prepare(
			"SELECT
				COUNT(*) AS total_lineas,
				IFNULL(SUM(p.cantidad), 0) AS total_cantidad,
				COUNT(DISTINCT TRIM(p.modelo)) AS total_modelos,
				COUNT(DISTINCT CONCAT(TRIM(p.modelo), '|', TRIM(IFNULL(p.cod_color, '')))) AS total_colores,
				COUNT(DISTINCT p.cod_sector) AS total_talleres,
				SUM(CASE WHEN IFNULL(sal.saldo_vivo, 0) > 0 THEN 1 ELSE 0 END) AS pendientes_lineas,
				SUM(CASE WHEN IFNULL(sal.saldo_vivo, 0) <= 0 THEN 1 ELSE 0 END) AS consumidos_lineas,
				IFNULL(SUM(CASE WHEN IFNULL(sal.saldo_vivo, 0) > 0 THEN p.cantidad ELSE 0 END), 0) AS pendientes_cantidad,
				IFNULL(SUM(CASE WHEN IFNULL(sal.saldo_vivo, 0) <= 0 THEN p.cantidad ELSE 0 END), 0) AS consumidos_cantidad
			 FROM programacion_taller_semanajf p
			 {$joinSaldo}
			 WHERE p.estado = 1 AND p.anio = :anio AND p.semana = :semana"
		);
		$stmtTot->bindValue(":anio", $anio, PDO::PARAM_INT);
		$stmtTot->bindValue(":semana", $semana, PDO::PARAM_INT);
		$stmtTot->execute();
		$totales = $stmtTot->fetch();
		if (!$totales) {
			$totales = array(
				"total_lineas" => 0,
				"total_cantidad" => 0,
				"total_modelos" => 0,
				"total_colores" => 0,
				"total_talleres" => 0,
				"pendientes_lineas" => 0,
				"consumidos_lineas" => 0,
				"pendientes_cantidad" => 0,
				"consumidos_cantidad" => 0
			);
		}

		$stmtNivel = Conexion::conectar()->prepare(
			"SELECT p.nivel,
				COUNT(*) AS total_lineas,
				IFNULL(SUM(p.cantidad), 0) AS total_cantidad
			 FROM programacion_taller_semanajf p
			 WHERE p.estado = 1 AND p.anio = :anio AND p.semana = :semana
			 GROUP BY p.nivel
			 ORDER BY total_cantidad DESC, p.nivel ASC"
		);
		$stmtNivel->bindValue(":anio", $anio, PDO::PARAM_INT);
		$stmtNivel->bindValue(":semana", $semana, PDO::PARAM_INT);
		$stmtNivel->execute();
		$porNivel = $stmtNivel->fetchAll();

		$porProgramar = self::mdlPendienteProgramarSemana($anio, $semana);

		return array(
			"totales" => $totales,
			"por_nivel" => $porNivel ? $porNivel : array(),
			"por_taller" => self::mdlResumenPorTaller($anio, $semana),
			"por_programar" => $porProgramar
		);
	}

	/**
	 * Modelo/color con saldo en corte/OC que aún no tienen fila activa en la semana.
	 * @return array{totales:array,por_taller:array}
	 */
	static public function mdlPendienteProgramarSemana($anio, $semana)
	{
		$anio = (int) $anio;
		$semana = (int) $semana;

		$subCandidatos = "
			SELECT
				TRIM(a.modelo) AS modelo,
				TRIM(IFNULL(a.cod_color, '')) AS cod_color,
				SUM(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) AS saldo_disponible,
				MAX(COALESCE(esp.cod_sector, gen.cod_sector, a.defecto_taller)) AS cod_sector
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
			WHERE (
					LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
					OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
				)
			  AND TRIM(IFNULL(a.modelo, '')) <> ''
			GROUP BY TRIM(a.modelo), TRIM(IFNULL(a.cod_color, ''))
			HAVING SUM(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) > 0
		";

		$notProgramado = "
			NOT EXISTS (
				SELECT 1
				FROM programacion_taller_semanajf p
				WHERE p.estado = 1
				  AND p.anio = :anio
				  AND p.semana = :semana
				  AND TRIM(p.modelo) = c.modelo
				  AND TRIM(IFNULL(p.cod_color, '')) = c.cod_color
			)
		";

		$stmtTot = Conexion::conectar()->prepare(
			"SELECT
				COUNT(*) AS total_colores,
				COUNT(DISTINCT c.modelo) AS total_modelos,
				IFNULL(SUM(c.saldo_disponible), 0) AS total_cantidad
			FROM ({$subCandidatos}) c
			WHERE {$notProgramado}"
		);
		$stmtTot->bindValue(":anio", $anio, PDO::PARAM_INT);
		$stmtTot->bindValue(":semana", $semana, PDO::PARAM_INT);
		$stmtTot->execute();
		$tot = $stmtTot->fetch();
		if (!$tot) {
			$tot = array(
				"total_colores" => 0,
				"total_modelos" => 0,
				"total_cantidad" => 0
			);
		}

		$stmtTaller = Conexion::conectar()->prepare(
			"SELECT
				IFNULL(NULLIF(TRIM(c.cod_sector), ''), '—') AS cod_sector,
				CASE
					WHEN IFNULL(NULLIF(TRIM(c.cod_sector), ''), '') = '' THEN 'Sin taller'
					ELSE IFNULL(s.nom_sector, c.cod_sector)
				END AS nom_sector,
				IFNULL(s.color, '#f0ad4e') AS color_taller,
				COUNT(*) AS total_colores,
				IFNULL(SUM(c.saldo_disponible), 0) AS total_cantidad
			FROM ({$subCandidatos}) c
			LEFT JOIN sectorjf s ON s.cod_sector = c.cod_sector
			WHERE {$notProgramado}
			GROUP BY IFNULL(NULLIF(TRIM(c.cod_sector), ''), '—'),
				CASE
					WHEN IFNULL(NULLIF(TRIM(c.cod_sector), ''), '') = '' THEN 'Sin taller'
					ELSE IFNULL(s.nom_sector, c.cod_sector)
				END,
				s.color
			ORDER BY total_cantidad DESC, cod_sector ASC"
		);
		$stmtTaller->bindValue(":anio", $anio, PDO::PARAM_INT);
		$stmtTaller->bindValue(":semana", $semana, PDO::PARAM_INT);
		$stmtTaller->execute();
		$porTaller = $stmtTaller->fetchAll();

		return array(
			"totales" => $tot,
			"por_taller" => $porTaller ? $porTaller : array()
		);
	}

	/**
	 * Detalle por talla de lo programado en una semana (para Excel tipo plan).
	 */
	static public function mdlExportDetalleSemana($anio, $semana, $filtros = array())
	{
		$sql = "SELECT
				p.id AS id_programacion,
				p.anio,
				p.semana,
				p.nivel,
				p.cantidad AS cant_programada,
				p.cod_sector,
				IFNULL(s.nom_sector, p.cod_sector) AS nom_sector,
				IFNULL(s.color, '#A8D5E5') AS color_taller,
				TRIM(p.modelo) AS modelo,
				TRIM(IFNULL(p.cod_color, '')) AS cod_color,
				IFNULL(p.color, a.color) AS color,
				IFNULL(mj.nombre, IFNULL(p.nombre, a.nombre)) AS nombre_modelo,
				a.articulo,
				a.cod_talla,
				a.talla,
				a.estado AS estado_articulo,
				(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0)) AS stock_disponible,
				IFNULL(a.alm_corte, 0) AS alm_corte,
				IFNULL(a.ord_corte, 0) AS ord_corte,
				(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) AS suma_orden,
				ROUND((IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0)) / NULLIF(a.ult_mes, 0), 2) AS ind,
				ROUND(
					(
						(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0))
						+ IFNULL(a.taller, 0) + IFNULL(a.servicio, 0)
						+ IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)
					) / NULLIF(a.ult_mes, 0),
					2
				) AS ind2
			FROM programacion_taller_semanajf p
			LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
			LEFT JOIN modelojf mj ON TRIM(mj.modelo) = TRIM(p.modelo)
			INNER JOIN articulojf a
				ON TRIM(a.modelo) = TRIM(p.modelo)
				AND TRIM(IFNULL(a.cod_color, '')) = TRIM(IFNULL(p.cod_color, ''))
			WHERE p.estado = 1
			  AND p.anio = :anio
			  AND p.semana = :semana
			  AND (
					LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
					OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
				)
			  AND (IFNULL(a.alm_corte, 0) > 0 OR IFNULL(a.ord_corte, 0) > 0)";

		$params = array(
			":anio" => (int) $anio,
			":semana" => (int) $semana
		);

		if (!empty($filtros["cod_sector"])) {
			$sql .= " AND p.cod_sector = :cod_sector";
			$params[":cod_sector"] = trim((string) $filtros["cod_sector"]);
		}
		if (!empty($filtros["modelo"])) {
			$sql .= " AND TRIM(p.modelo) = :modelo";
			$params[":modelo"] = trim((string) $filtros["modelo"]);
		}
		if (!empty($filtros["nivel"])) {
			$sql .= " AND p.nivel = :nivel";
			$params[":nivel"] = trim((string) $filtros["nivel"]);
		}

		$sql .= " ORDER BY p.nivel ASC, p.modelo ASC, p.cod_color ASC, a.cod_talla ASC, a.talla ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}
}
