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

	/**
	 * True si el domingo de esa semana ya pasó (no se puede destinar/programar).
	 * La semana en curso sigue permitida.
	 */
	static public function mdlSemanaYaPasada($anio, $semana)
	{
		$rango = self::mdlRangoSemana($anio, $semana);
		if (!$rango) {
			return true;
		}
		$hoy = (new DateTime("now"))->format("Y-m-d");
		return strcmp($rango["fecha_fin"], $hoy) < 0;
	}

	/**
	 * Saldo vivo por orden de corte: modelo|color => [ordencorte => {alm_corte, ord_corte, saldo, fecha_oc, tallas}].
	 * Sale de detalles_ordencortejf (OC no cerradas) y almacencorte_detallejf, topado por talla al total de
	 * articulojf (alm_corte / ord_corte): si los detalles suman más (saldos viejos sin cerrar) se asigna de la OC
	 * más nueva a la más antigua; si suman menos, el resto queda en la OC 0 (saldo sin OC).
	 */
	static public function mdlSaldosPorOc($modelo = null, $codColor = null)
	{
		$filtroArt = "";
		$params = array();
		if ($modelo !== null) {
			$filtroArt = " AND TRIM(a.modelo) = :modelo AND TRIM(IFNULL(a.cod_color, '')) = :cod_color";
			$params[":modelo"] = trim((string) $modelo);
			$params[":cod_color"] = trim((string) $codColor);
		}
		$whereArt = "(
					LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
					OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
				)
			  AND TRIM(IFNULL(a.modelo, '')) <> ''" . $filtroArt;

		$pdo = Conexion::conectar();
		$ejecutar = function ($sql) use ($pdo, $params) {
			$stmt = $pdo->prepare($sql);
			foreach ($params as $k => $v) {
				$stmt->bindValue($k, $v, PDO::PARAM_STR);
			}
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		};

		$arts = $ejecutar(
			"SELECT a.articulo, TRIM(a.modelo) AS modelo, TRIM(IFNULL(a.cod_color, '')) AS cod_color,
				IFNULL(a.alm_corte, 0) AS alm, IFNULL(a.ord_corte, 0) AS ord
			 FROM articulojf a
			 WHERE {$whereArt}
			   AND (IFNULL(a.alm_corte, 0) > 0 OR IFNULL(a.ord_corte, 0) > 0)"
		);
		if (!count($arts)) {
			return array();
		}
		// Detalles sin join a articulojf (tablas sin índice útil); se filtran por artículo en PHP
		$filtroDet = "";
		if ($modelo !== null) {
			$lista = array();
			foreach ($arts as $a) {
				$lista[] = $pdo->quote($a["articulo"]);
			}
			$filtroDet = " AND %s.articulo IN (" . implode(",", $lista) . ")";
		}
		$stmt = $pdo->prepare(
			"SELECT d.articulo, d.ordencorte, SUM(d.saldo) AS saldo
			 FROM detalles_ordencortejf d
			 INNER JOIN ordencortejf o ON o.codigo = d.ordencorte
			 WHERE d.saldo > 0 AND d.estado = 0 AND o.estado <> 'Cerrado'" . sprintf($filtroDet, "d") . "
			 GROUP BY d.articulo, d.ordencorte"
		);
		$stmt->execute();
		$detOrd = $stmt->fetchAll(PDO::FETCH_ASSOC);
		// Mismo criterio de saldo que usa el envío a taller (mdlMandarTallerCabV2)
		$stmt = $pdo->prepare(
			"SELECT acd.articulo, IFNULL(acd.ordencorte, 0) AS ordencorte,
				SUM(COALESCE(acd.saldo_taller, acd.cantidad)) AS saldo
			 FROM almacencorte_detallejf acd
			 INNER JOIN almacencortejf ac ON ac.codigo = acd.almacencorte
			 WHERE COALESCE(acd.saldo_taller, acd.cantidad) > 0
			   AND YEAR(DATE(ac.fecha)) = YEAR(NOW())" . sprintf($filtroDet, "acd") . "
			 GROUP BY acd.articulo, IFNULL(acd.ordencorte, 0)"
		);
		$stmt->execute();
		$detAlm = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$porArt = array();
		foreach ($detOrd as $r) {
			$porArt[$r["articulo"]]["ord"][(int) $r["ordencorte"]] = (int) $r["saldo"];
		}
		foreach ($detAlm as $r) {
			$porArt[$r["articulo"]]["alm"][(int) $r["ordencorte"]] = (int) $r["saldo"];
		}

		$out = array();
		$codigos = array();
		foreach ($arts as $a) {
			$clave = $a["modelo"] . "|" . $a["cod_color"];
			foreach (array("ord" => "ord_corte", "alm" => "alm_corte") as $tipo => $campo) {
				$total = (int) $a[$tipo];
				if ($total <= 0) {
					continue;
				}
				$lista = isset($porArt[$a["articulo"]][$tipo]) ? $porArt[$a["articulo"]][$tipo] : array();
				unset($lista[0]);
				krsort($lista); // códigos de OC crecen en el tiempo: la más nueva primero
				$lista[0] = $total;
				foreach ($lista as $oc => $saldo) {
					$usar = min((int) $saldo, $total);
					if ($usar <= 0) {
						continue;
					}
					if (!isset($out[$clave][$oc])) {
						$out[$clave][$oc] = array(
							"ordencorte" => $oc,
							"alm_corte" => 0,
							"ord_corte" => 0,
							"saldo" => 0,
							"fecha_oc" => null,
							"tallas" => array()
						);
						$codigos[$oc] = true;
					}
					$out[$clave][$oc][$campo] += $usar;
					$out[$clave][$oc]["saldo"] += $usar;
					if (!isset($out[$clave][$oc]["tallas"][$a["articulo"]])) {
						$out[$clave][$oc]["tallas"][$a["articulo"]] = array("alm_corte" => 0, "ord_corte" => 0);
					}
					$out[$clave][$oc]["tallas"][$a["articulo"]][$campo] += $usar;
					$total -= $usar;
					if ($total <= 0) {
						break;
					}
				}
			}
		}

		$fechas = self::mdlFechasOc(array_keys($codigos));
		foreach ($out as $clave => $ocs) {
			foreach ($ocs as $oc => $info) {
				$out[$clave][$oc]["fecha_oc"] = isset($fechas[$oc]) ? $fechas[$oc] : null;
			}
			ksort($out[$clave]);
		}
		return $out;
	}

	/** Fecha de cada orden de corte (codigo => Y-m-d). */
	static public function mdlFechasOc($codigos)
	{
		$ids = array();
		foreach ((array) $codigos as $c) {
			if ((int) $c > 0) {
				$ids[(int) $c] = (int) $c;
			}
		}
		if (!count($ids)) {
			return array();
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT codigo, DATE(fecha) AS fecha FROM ordencortejf WHERE codigo IN (" . implode(",", $ids) . ")"
		);
		$stmt->execute();
		$out = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
			$out[(int) $r["codigo"]] = $r["fecha"];
		}
		return $out;
	}

	/**
	 * Claves modelo|color|ordencorte con fila activa en prioridad o programación (cualquier semana).
	 * @return array clave => array{tipo:string,id:int,anio:?int,semana:?int}
	 */
	static public function mdlClavesOcOcupadas()
	{
		$out = array();
		try {
			$stmt = Conexion::conectar()->prepare(
				"SELECT 'programado' AS tipo, id, anio, semana,
					CONCAT(TRIM(modelo), '|', TRIM(IFNULL(cod_color, '')), '|', ordencorte) AS clave
				 FROM programacion_taller_semanajf
				 WHERE estado = 1 AND ordencorte IS NOT NULL
				 UNION ALL
				 SELECT 'prioridad', id, NULL, NULL,
					CONCAT(TRIM(modelo), '|', TRIM(IFNULL(cod_color, '')), '|', ordencorte)
				 FROM programacion_taller_prioridadjf
				 WHERE estado = 1 AND ordencorte IS NOT NULL"
			);
			$stmt->execute();
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
				if (!isset($out[$r["clave"]])) {
					$out[$r["clave"]] = array(
						"tipo" => $r["tipo"],
						"id" => (int) $r["id"],
						"anio" => $r["anio"] !== null ? (int) $r["anio"] : null,
						"semana" => $r["semana"] !== null ? (int) $r["semana"] : null
					);
				}
			}
		} catch (Exception $e) {
			return array();
		}
		return $out;
	}

	static public function mdlListarProgramados($filtros = array())
	{
		$sql = "SELECT p.*,
				s.nom_sector,
				s.color AS color_taller
			FROM programacion_taller_semanajf p
			LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
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

	/**
	 * Programados en semanas ya cerradas (fecha_fin < hoy). El filtro de saldo vivo de su OC lo aplica el controlador.
	 */
	static public function mdlListarNoEjecutados($filtros = array())
	{
		$hoy = (new DateTime("now"))->format("Y-m-d");
		$sql = "SELECT p.*,
				s.nom_sector,
				s.color AS color_taller
			FROM programacion_taller_semanajf p
			LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
			WHERE p.estado = 1
			  AND p.fecha_fin < :hoy";
		$params = array(":hoy" => $hoy);

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

		$sql .= " ORDER BY p.anio ASC, p.semana ASC, p.cod_sector ASC, p.modelo ASC, p.cod_color ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/** Cambia año/semana de una programación activa. */
	static public function mdlMoverSemana($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_semanajf SET
				anio = :anio,
				semana = :semana,
				fecha_inicio = :fecha_inicio,
				fecha_fin = :fecha_fin,
				cantidad = :cantidad,
				saldo_alm_corte = :saldo_alm_corte,
				saldo_ord_corte = :saldo_ord_corte,
				urg_plan = :urg_plan,
				usumod = :usumod,
				fecmod = NOW()
			 WHERE id = :id AND estado = 1"
		);
		$stmt->bindValue(":id", (int) $datos["id"], PDO::PARAM_INT);
		$stmt->bindValue(":anio", (int) $datos["anio"], PDO::PARAM_INT);
		$stmt->bindValue(":semana", (int) $datos["semana"], PDO::PARAM_INT);
		$stmt->bindValue(":fecha_inicio", $datos["fecha_inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fecha_fin", $datos["fecha_fin"], PDO::PARAM_STR);
		$stmt->bindValue(":cantidad", (int) $datos["cantidad"], PDO::PARAM_INT);
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

	/** $ordencorte null = fila legado sin OC. */
	static public function mdlIdExistente($anio, $semana, $modelo, $codColor, $codSector, $soloActivos = false, $ordencorte = null)
	{
		$sql = "SELECT id FROM programacion_taller_semanajf
			 WHERE anio = :anio AND semana = :semana
			   AND TRIM(modelo) = :modelo
			   AND TRIM(IFNULL(cod_color, '')) = :cod_color
			   AND cod_sector = :cod_sector
			   AND IFNULL(ordencorte, -1) = :ordencorte";
		if ($soloActivos) {
			$sql .= " AND estado = 1";
		}
		$sql .= " ORDER BY estado DESC LIMIT 1";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":semana", (int) $semana, PDO::PARAM_INT);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":cod_color", trim((string) $codColor), PDO::PARAM_STR);
		$stmt->bindValue(":cod_sector", trim((string) $codSector), PDO::PARAM_STR);
		$stmt->bindValue(":ordencorte", $ordencorte === null ? -1 : (int) $ordencorte, PDO::PARAM_INT);
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
				(anio, semana, fecha_inicio, fecha_fin, articulo, modelo, cod_color, ordencorte, color,
				 cod_talla, talla, nombre, cod_sector, cantidad, saldo_alm_corte, saldo_ord_corte,
				 nivel, urg_plan, observacion, estado, usureg)
			 VALUES
				(:anio, :semana, :fecha_inicio, :fecha_fin, :articulo, :modelo, :cod_color, :ordencorte, :color,
				 NULL, NULL, :nombre, :cod_sector, :cantidad, :saldo_alm_corte, :saldo_ord_corte,
				 :nivel, :urg_plan, :observacion, 1, :usureg)"
		);
		self::mdlBindProgramacion($stmt, $datos, false);
		$stmt->bindValue(":ordencorte", (int) $datos["ordencorte"], PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEditar($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_semanajf SET
				anio = :anio,
				semana = :semana,
				fecha_inicio = :fecha_inicio,
				fecha_fin = :fecha_fin,
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
		$stmt->bindValue(":anio", (int) $datos["anio"], PDO::PARAM_INT);
		$stmt->bindValue(":semana", (int) $datos["semana"], PDO::PARAM_INT);
		$stmt->bindValue(":fecha_inicio", $datos["fecha_inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fecha_fin", $datos["fecha_fin"], PDO::PARAM_STR);
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
	 * Estadísticas de programación de una semana (totales, por nivel, por taller).
	 * Pendiente/consumido y "por programar" dependen del saldo por OC y los arma el controlador.
	 */
	static public function mdlEstadisticasSemana($anio, $semana)
	{
		$anio = (int) $anio;
		$semana = (int) $semana;

		$stmtTot = Conexion::conectar()->prepare(
			"SELECT
				COUNT(*) AS total_lineas,
				IFNULL(SUM(p.cantidad), 0) AS total_cantidad,
				COUNT(DISTINCT TRIM(p.modelo)) AS total_modelos,
				COUNT(DISTINCT CONCAT(TRIM(p.modelo), '|', TRIM(IFNULL(p.cod_color, '')))) AS total_colores,
				COUNT(DISTINCT p.cod_sector) AS total_talleres
			 FROM programacion_taller_semanajf p
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
				"total_talleres" => 0
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

		return array(
			"totales" => $totales,
			"por_nivel" => $porNivel ? $porNivel : array(),
			"por_taller" => self::mdlResumenPorTaller($anio, $semana)
		);
	}

	/** Claves modelo|color|ordencorte con fila activa en una semana. */
	static public function mdlClavesOcSemana($anio, $semana)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT CONCAT(TRIM(modelo), '|', TRIM(IFNULL(cod_color, '')), '|', IFNULL(ordencorte, -1)) AS clave
			 FROM programacion_taller_semanajf
			 WHERE estado = 1 AND anio = :anio AND semana = :semana"
		);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":semana", (int) $semana, PDO::PARAM_INT);
		$stmt->execute();
		$out = array();
		foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $c) {
			$out[$c] = true;
		}
		return $out;
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
				p.ordencorte,
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

	/**
	 * Artículos (talla) programados en un rango de semanas con saldo en almacén de corte.
	 * Exclusivo de la pestaña Enviar a taller (no altera en-cortes).
	 */
	static public function mdlListarArticulosEnviarSemana($anio, $semana, $filtros = array())
	{
		$anioHasta = isset($filtros["anio_hasta"]) ? (int) $filtros["anio_hasta"] : (int) $anio;
		$semanaHasta = isset($filtros["semana_hasta"]) ? (int) $filtros["semana_hasta"] : (int) $semana;
		$desde = self::mdlRangoSemana($anio, $semana);
		$hasta = self::mdlRangoSemana($anioHasta, $semanaHasta);
		if (!$desde || !$hasta) {
			return array();
		}
		$fechaDesde = $desde["fecha_inicio"];
		$fechaHasta = $hasta["fecha_inicio"];
		if (strcmp($fechaDesde, $fechaHasta) > 0) {
			$tmp = $fechaDesde;
			$fechaDesde = $fechaHasta;
			$fechaHasta = $tmp;
		}

		$sql = "SELECT
				p.id AS id_programacion,
				p.anio,
				p.semana,
				p.fecha_inicio,
				p.fecha_fin,
				p.nivel,
				p.cantidad AS cant_programada,
				p.ordencorte,
				p.cod_sector,
				IFNULL(s.nom_sector, p.cod_sector) AS nom_sector,
				IFNULL(s.color, '#A8D5E5') AS color_taller,
				IFNULL(s.tipo, 0) AS tipo_sector,
				TRIM(p.modelo) AS modelo,
				TRIM(IFNULL(p.cod_color, '')) AS cod_color,
				IFNULL(p.color, a.color) AS color,
				IFNULL(mj.nombre, IFNULL(p.nombre, a.nombre)) AS nombre_modelo,
				a.articulo,
				a.cod_talla,
				a.talla,
				a.estado AS estado_articulo,
				IFNULL(a.alm_corte, 0) AS alm_corte,
				IFNULL(a.ord_corte, 0) AS ord_corte
			FROM programacion_taller_semanajf p
			LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
			LEFT JOIN modelojf mj ON TRIM(mj.modelo) = TRIM(p.modelo)
			INNER JOIN articulojf a
				ON TRIM(a.modelo) = TRIM(p.modelo)
				AND TRIM(IFNULL(a.cod_color, '')) = TRIM(IFNULL(p.cod_color, ''))
			WHERE p.estado = 1
			  AND p.fecha_inicio >= :fecha_desde
			  AND p.fecha_inicio <= :fecha_hasta
			  AND IFNULL(a.alm_corte, 0) > 0
			  AND (
					LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
					OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
				)";

		$params = array(
			":fecha_desde" => $fechaDesde,
			":fecha_hasta" => $fechaHasta
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

		$sql .= " ORDER BY p.fecha_inicio ASC, p.cod_sector ASC, p.modelo ASC, p.cod_color ASC, a.cod_talla ASC, a.talla ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v, PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlListarPriorizados($filtros = array())
	{
		$sql = "SELECT p.*,
				s.nom_sector,
				s.color AS color_taller
			FROM programacion_taller_prioridadjf p
			LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
			WHERE p.estado = 1";
		$params = array();
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
		$sql .= " ORDER BY p.nivel ASC, p.modelo ASC, p.cod_color ASC";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $k => $v) {
				$stmt->bindValue($k, $v, PDO::PARAM_STR);
			}
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (Exception $e) {
			return array();
		}
	}

	static public function mdlMostrarPrioridad($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		try {
			$stmt = Conexion::conectar()->prepare(
				"SELECT p.*, s.nom_sector, s.color AS color_taller
				 FROM programacion_taller_prioridadjf p
				 LEFT JOIN sectorjf s ON s.cod_sector = p.cod_sector
				 WHERE p.id = :id LIMIT 1"
			);
			$stmt->bindValue(":id", $id, PDO::PARAM_INT);
			$stmt->execute();
			$row = $stmt->fetch();
			return $row ? $row : null;
		} catch (Exception $e) {
			return null;
		}
	}

	static public function mdlIdPrioridadExistente($modelo, $codColor, $codSector, $soloActivos = false, $ordencorte = null)
	{
		$sql = "SELECT id FROM programacion_taller_prioridadjf
			 WHERE TRIM(modelo) = :modelo
			   AND TRIM(IFNULL(cod_color, '')) = :cod_color
			   AND cod_sector = :cod_sector
			   AND IFNULL(ordencorte, -1) = :ordencorte";
		if ($soloActivos) {
			$sql .= " AND estado = 1";
		}
		$sql .= " ORDER BY estado DESC LIMIT 1";
		try {
			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
			$stmt->bindValue(":cod_color", trim((string) $codColor), PDO::PARAM_STR);
			$stmt->bindValue(":cod_sector", trim((string) $codSector), PDO::PARAM_STR);
			$stmt->bindValue(":ordencorte", $ordencorte === null ? -1 : (int) $ordencorte, PDO::PARAM_INT);
			$stmt->execute();
			$id = $stmt->fetchColumn();
			return $id !== false ? (int) $id : 0;
		} catch (Exception $e) {
			return 0;
		}
	}

	static public function mdlCrearPrioridad($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO programacion_taller_prioridadjf
				(modelo, cod_color, ordencorte, color, nombre, cod_sector, cantidad, saldo_alm_corte, saldo_ord_corte,
				 nivel, urg_plan, observacion, estado, usureg)
			 VALUES
				(:modelo, :cod_color, :ordencorte, :color, :nombre, :cod_sector, :cantidad, :saldo_alm_corte, :saldo_ord_corte,
				 :nivel, :urg_plan, :observacion, 1, :usureg)"
		);
		$stmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindValue(":cod_color", $datos["cod_color"], PDO::PARAM_STR);
		$stmt->bindValue(":ordencorte", (int) $datos["ordencorte"], PDO::PARAM_INT);
		$stmt->bindValue(":color", $datos["color"], PDO::PARAM_STR);
		$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
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
		$stmt->bindValue(":usureg", $datos["usureg"], PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEditarPrioridad($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_prioridadjf SET
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

	static public function mdlReactivarPrioridad($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_prioridadjf SET
				cantidad = :cantidad,
				nivel = :nivel,
				observacion = :observacion,
				saldo_alm_corte = :saldo_alm_corte,
				saldo_ord_corte = :saldo_ord_corte,
				urg_plan = :urg_plan,
				color = :color,
				nombre = :nombre,
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
		$stmt->bindValue(":color", $datos["color"], PDO::PARAM_STR);
		$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindValue(":usumod", $datos["usumod"], PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEliminarPrioridad($id, $usumod = "sistema")
	{
		$id = (int) $id;
		if ($id < 1) {
			return "error";
		}
		$stmt = Conexion::conectar()->prepare(
			"UPDATE programacion_taller_prioridadjf
			 SET estado = 0, usumod = :usumod, fecmod = NOW()
			 WHERE id = :id"
		);
		$stmt->bindValue(":id", $id, PDO::PARAM_INT);
		$stmt->bindValue(":usumod", (string) $usumod, PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}
}
