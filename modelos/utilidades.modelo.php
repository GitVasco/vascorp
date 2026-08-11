<?php

require_once "conexion.php";

class ModeloUtilidades
{

	static public function mdlTablaMovimientosAnio($anio = null)
	{
		date_default_timezone_set("America/Lima");
		$anio = $anio === null ? (int) date("Y") : (int) $anio;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}
		return "movimientosjf_" . $anio;
	}

	/**
	 * Artículos del almacén 01 cuyo stock no cuadra con movimientos del año
	 * (ingresos E* − salidas S*; E05 × -1; excluye S01, marca ELASTICOS y modelos D0*).
	 */
	static public function mdlDescuadresStock01($anio = null)
	{
		$tablaMov = self::mdlTablaMovimientosAnio($anio);

		$sql = "
			SELECT
				a.articulo,
				COALESCE(a.nombre, '') AS nombre,
				COALESCE(a.modelo, '') AS modelo,
				COALESCE(a.color, '') AS color,
				COALESCE(a.talla, '') AS talla,
				ROUND(COALESCE(a.stock, 0), 4) AS stock_actual,
				ROUND(COALESCE(m.ingresos, 0), 4) AS ingresos,
				ROUND(COALESCE(m.salidas, 0), 4) AS salidas,
				ROUND(COALESCE(m.ingresos, 0) - COALESCE(m.salidas, 0), 4) AS stock_calculado,
				ROUND(
					(COALESCE(m.ingresos, 0) - COALESCE(m.salidas, 0)) - COALESCE(a.stock, 0),
					4
				) AS diferencia
			FROM (
				SELECT
					articulo,
					SUM(
						CASE
							WHEN tipo = 'E05' THEN cantidad * -1
							WHEN LEFT(tipo, 1) = 'E' THEN cantidad
							ELSE 0
						END
					) AS ingresos,
					SUM(
						CASE
							WHEN LEFT(tipo, 1) = 'S' AND tipo <> 'S01' THEN cantidad
							ELSE 0
						END
					) AS salidas
				FROM {$tablaMov}
				WHERE almacen = '01'
					AND articulo IS NOT NULL
					AND articulo <> ''
					AND (
						LEFT(tipo, 1) = 'E'
						OR (LEFT(tipo, 1) = 'S' AND tipo <> 'S01')
					)
				GROUP BY articulo
			) m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			WHERE ROUND(COALESCE(m.ingresos, 0) - COALESCE(m.salidas, 0), 4)
				<> ROUND(COALESCE(a.stock, 0), 4)
				AND UPPER(TRIM(COALESCE(a.marca, ''))) <> 'ELASTICOS'
				AND LEFT(UPPER(TRIM(COALESCE(a.modelo, ''))), 2) <> 'D0'
			ORDER BY a.articulo ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Actualiza stock de los artículos indicados al valor calculado.
	 * $items: [ ['articulo' => ..., 'stock_calculado' => ...], ... ]
	 */
	static public function mdlActualizarStock01($items)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin ítems");
		}

		$db = Conexion::conectar();
		$db->beginTransaction();

		try {
			$stmt = $db->prepare("
				UPDATE articulojf
				SET stock = :stock
				WHERE articulo = :articulo
			");

			$actualizados = 0;
			foreach ($items as $item) {
				$articulo = isset($item["articulo"]) ? trim((string) $item["articulo"]) : "";
				if ($articulo === "") {
					continue;
				}
				$stock = isset($item["stock_calculado"]) ? (float) $item["stock_calculado"] : 0;

				$stmt->bindValue(":articulo", $articulo, PDO::PARAM_STR);
				$stmt->bindValue(":stock", $stock);
				$stmt->execute();
				$actualizados += $stmt->rowCount() > 0 ? 1 : 0;
			}

			$db->commit();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"mensaje" => "Se actualizaron {$actualizados} artículo(s)"
			);
		} catch (Exception $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al actualizar stock"
			);
		}
	}

	/**
	 * Artículos cuyo articulojf.servicio no cuadra con
	 * SUM(servicios_detallejf.saldo WHERE cerrar=0) + SUM(cierres_detallejf.cantidad).
	 */
	static public function mdlDescuadresServicio()
	{
		$sql = "
			SELECT
				a.articulo,
				COALESCE(a.nombre, '') AS nombre,
				COALESCE(a.modelo, '') AS modelo,
				COALESCE(a.color, '') AS color,
				COALESCE(a.talla, '') AS talla,
				ROUND(COALESCE(a.servicio, 0), 4) AS servicio_total,
				ROUND(COALESCE(s.servicio, 0), 4) AS servicio,
				ROUND(COALESCE(c.cierre, 0), 4) AS cierre,
				ROUND(COALESCE(s.servicio, 0) + COALESCE(c.cierre, 0), 4) AS servicio_calculado,
				ROUND(
					COALESCE(a.servicio, 0) - (COALESCE(s.servicio, 0) + COALESCE(c.cierre, 0)),
					4
				) AS diferencia
			FROM articulojf a
			LEFT JOIN (
				SELECT
					articulo,
					SUM(saldo) AS servicio
				FROM servicios_detallejf
				WHERE cerrar = 0
				GROUP BY articulo
			) s ON a.articulo = s.articulo
			LEFT JOIN (
				SELECT
					articulo,
					SUM(cantidad) AS cierre
				FROM cierres_detallejf
				GROUP BY articulo
			) c ON a.articulo = c.articulo
			WHERE ROUND(COALESCE(a.servicio, 0), 4)
				<> ROUND(COALESCE(s.servicio, 0) + COALESCE(c.cierre, 0), 4)
			ORDER BY a.articulo ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Actualiza articulojf.servicio al valor calculado.
	 * $items: [ ['articulo' => ..., 'servicio_calculado' => ...], ... ]
	 */
	static public function mdlActualizarServicio($items)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin ítems");
		}

		$db = Conexion::conectar();
		$db->beginTransaction();

		try {
			$stmt = $db->prepare("
				UPDATE articulojf
				SET servicio = :servicio
				WHERE articulo = :articulo
			");

			$actualizados = 0;
			foreach ($items as $item) {
				$articulo = isset($item["articulo"]) ? trim((string) $item["articulo"]) : "";
				if ($articulo === "") {
					continue;
				}
				$servicio = isset($item["servicio_calculado"])
					? (float) $item["servicio_calculado"]
					: 0;

				$stmt->bindValue(":articulo", $articulo, PDO::PARAM_STR);
				$stmt->bindValue(":servicio", $servicio);
				$stmt->execute();
				$actualizados += $stmt->rowCount() > 0 ? 1 : 0;
			}

			$db->commit();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"mensaje" => "Se actualizaron {$actualizados} artículo(s)"
			);
		} catch (Exception $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al actualizar servicio"
			);
		}
	}
}
