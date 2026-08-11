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

	/**
	 * Tracking de producción por modelo (solo lectura).
	 * Cruza saldos de articulojf con documentos OC → corte → taller/servicio → cierre → ingresos E20.
	 *
	 * @return array|false
	 */
	static public function mdlTrackingModelo($modelo)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return false;
		}

		$db = Conexion::conectar();
		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$tablaMov = self::mdlTablaMovimientosAnio($anio);

		$sqlArticulos = "
			SELECT
				a.articulo,
				COALESCE(a.nombre, '') AS nombre,
				COALESCE(a.modelo, '') AS modelo,
				COALESCE(a.cod_color, '') AS cod_color,
				COALESCE(a.color, '') AS color,
				COALESCE(a.cod_talla, '') AS cod_talla,
				COALESCE(a.talla, '') AS talla,
				ROUND(COALESCE(a.ord_corte, 0), 4) AS ord_corte,
				ROUND(COALESCE(a.alm_corte, 0), 4) AS alm_corte,
				ROUND(COALESCE(a.taller, 0), 4) AS taller,
				ROUND(COALESCE(a.servicio, 0), 4) AS servicio,
				ROUND(COALESCE(a.stock, 0), 4) AS stock,
				ROUND(COALESCE(oc.saldo_oc, 0), 4) AS ord_corte_calc,
				ROUND(COALESCE(oc.filas_oc, 0), 0) AS filas_oc,
				ROUND(COALESCE(oc.cantidad_oc, 0), 4) AS cantidad_oc,
				ROUND(COALESCE(ac.saldo_taller, 0), 4) AS alm_corte_calc,
				ROUND(COALESCE(ac.filas_corte, 0), 0) AS filas_corte,
				ROUND(COALESCE(ac.cantidad_corte, 0), 4) AS cantidad_corte,
				ROUND(COALESCE(et.saldo_taller_doc, 0), 4) AS taller_calc,
				ROUND(COALESCE(et.filas_envio, 0), 0) AS filas_envio,
				ROUND(COALESCE(et.cantidad_envio, 0), 4) AS cantidad_envio,
				ROUND(COALESCE(sv.servicio_abierto, 0), 4) AS servicio_abierto,
				ROUND(COALESCE(sv.filas_servicio, 0), 0) AS filas_servicio,
				ROUND(COALESCE(svo.servicio_origen, 0), 4) AS servicio_origen,
				ROUND(COALESCE(cj.cierre, 0), 4) AS cierre,
				ROUND(COALESCE(cj.filas_cierre, 0), 0) AS filas_cierre,
				ROUND(COALESCE(cj.cierre_inicio, 0), 4) AS cierre_inicio,
				ROUND(COALESCE(sv.servicio_abierto, 0) + COALESCE(cj.cierre, 0), 4) AS servicio_calc,
				ROUND(COALESCE(ex.entaller_ext_saldo, 0), 4) AS entaller_ext_saldo,
				ROUND(COALESCE(ex.entaller_ext_calc, 0), 4) AS entaller_ext_calc,
				ROUND(COALESCE(ex.filas_entaller_ext, 0), 0) AS filas_entaller_ext
			FROM articulojf a
			LEFT JOIN (
				SELECT
					articulo,
					COUNT(*) AS filas_oc,
					SUM(cantidad) AS cantidad_oc,
					SUM(saldo) AS saldo_oc
				FROM detalles_ordencortejf
				GROUP BY articulo
			) oc ON oc.articulo = a.articulo
			LEFT JOIN (
				SELECT
					articulo,
					COUNT(*) AS filas_corte,
					SUM(cantidad) AS cantidad_corte,
					SUM(CASE WHEN saldo_taller > 0 THEN saldo_taller ELSE 0 END) AS saldo_taller
				FROM almacencorte_detallejf
				GROUP BY articulo
			) ac ON ac.articulo = a.articulo
			LEFT JOIN (
				SELECT
					e.articulo,
					COUNT(*) AS filas_envio,
					SUM(e.cantidad) AS cantidad_envio,
					SUM(
						CASE
							WHEN UPPER(COALESCE(e.taller, '')) = 'VC' THEN COALESCE(e.saldo, 0)
							WHEN s.tipo = 0 OR s.tipo IS NULL THEN COALESCE(e.saldo, 0)
							ELSE 0
						END
					) AS saldo_taller_doc
				FROM entaller_cabjf e
				LEFT JOIN sectorjf s ON s.cod_sector = e.taller
				GROUP BY e.articulo
			) et ON et.articulo = a.articulo
			LEFT JOIN (
				SELECT
					articulo,
					COUNT(*) AS filas_servicio,
					SUM(saldo) AS servicio_abierto
				FROM servicios_detallejf
				WHERE cerrar = 0
				GROUP BY articulo
			) sv ON sv.articulo = a.articulo
			LEFT JOIN (
				SELECT
					articulo,
					SUM(cantidad) AS servicio_origen
				FROM servicios_detallejf
				GROUP BY articulo
			) svo ON svo.articulo = a.articulo
			LEFT JOIN (
				SELECT
					articulo,
					COUNT(*) AS filas_cierre,
					SUM(cantidad) AS cierre,
					SUM(COALESCE(inicio, cantidad)) AS cierre_inicio
				FROM cierres_detallejf
				GROUP BY articulo
			) cj ON cj.articulo = a.articulo
			LEFT JOIN (
				SELECT
					e.articulo,
					ROUND(SUM(COALESCE(e.saldo, 0)), 4) AS entaller_ext_saldo,
					ROUND(SUM(COALESCE(svx.saldo_esp, 0)), 4) AS entaller_ext_calc,
					COUNT(*) AS filas_entaller_ext
				FROM entaller_cabjf e
				INNER JOIN sectorjf s
					ON s.cod_sector = e.taller
					AND s.tipo IS NOT NULL
					AND s.tipo <> 0
				LEFT JOIN (
					SELECT
						cabecera_taller,
						SUM(saldo) AS saldo_esp
					FROM servicios_detallejf
					WHERE cerrar = 0
						AND cabecera_taller IS NOT NULL
						AND cabecera_taller <> 0
					GROUP BY cabecera_taller
				) svx ON svx.cabecera_taller = e.id
				GROUP BY e.articulo
			) ex ON ex.articulo = a.articulo
			WHERE a.modelo = :modelo
			ORDER BY a.articulo ASC
		";

		$stmt = $db->prepare($sqlArticulos);
		$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		$articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		if (!$articulos || count($articulos) < 1) {
			return array(
				"existe" => false,
				"modelo" => $modelo,
				"anio" => $anio,
				"articulos" => array(),
				"resumen" => array(),
				"documentos" => array(),
				"huerfanos" => array(),
				"ingresos" => array("filas" => 0, "cantidad" => 0, "disponible" => false)
			);
		}

		$ingresos = array("filas" => 0, "cantidad" => 0, "disponible" => false);
		$ingresosPorArticulo = array();
		$e20CierrePorArticulo = array();
		$existeMov = false;
		try {
			$chkMov = $db->query("SHOW TABLES LIKE " . $db->quote($tablaMov));
			if ($chkMov) {
				$existeMov = (bool) $chkMov->fetch(PDO::FETCH_NUM);
				$chkMov->closeCursor();
			}
		} catch (Exception $eChkMov) {
			$existeMov = false;
		}
		if (!$existeMov) {
			try {
				$probeMov = $db->query("SELECT 1 FROM `{$tablaMov}` LIMIT 1");
				if ($probeMov !== false) {
					$existeMov = true;
					$probeMov->closeCursor();
				}
			} catch (Exception $eProbeMov) {
				$existeMov = false;
			}
		}
		if ($existeMov) {
			$sqlIng = "
				SELECT
					m.articulo,
					COUNT(*) AS filas,
					ROUND(COALESCE(SUM(m.cantidad), 0), 4) AS cantidad,
					ROUND(COALESCE(SUM(CASE WHEN m.idcierre IS NOT NULL AND m.idcierre > 0 THEN m.cantidad ELSE 0 END), 0), 4) AS cantidad_cierre,
					SUM(CASE WHEN m.idcierre IS NOT NULL AND m.idcierre > 0 THEN 1 ELSE 0 END) AS filas_cierre
				FROM `{$tablaMov}` m
				INNER JOIN articulojf a ON a.articulo = m.articulo
				WHERE a.modelo = :modelo
					AND m.tipo = 'E20'
				GROUP BY m.articulo
			";
			$stmtIng = $db->prepare($sqlIng);
			$stmtIng->bindValue(":modelo", $modelo, PDO::PARAM_STR);
			if ($stmtIng->execute()) {
				$rowsIng = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
				$stmtIng->closeCursor();
				$totFilasIng = 0;
				$totCantIng = 0.0;
				$totE20Cierre = 0.0;
				foreach ($rowsIng as $rowIng) {
					$artKey = (string) $rowIng["articulo"];
					$filasArt = (int) (isset($rowIng["filas"]) ? $rowIng["filas"] : 0);
					$cantArt = (float) (isset($rowIng["cantidad"]) ? $rowIng["cantidad"] : 0);
					$cantCierreArt = (float) (isset($rowIng["cantidad_cierre"]) ? $rowIng["cantidad_cierre"] : 0);
					$ingresosPorArticulo[$artKey] = array(
						"filas" => $filasArt,
						"cantidad" => $cantArt
					);
					$e20CierrePorArticulo[$artKey] = $cantCierreArt;
					$totFilasIng += $filasArt;
					$totCantIng += $cantArt;
					$totE20Cierre += $cantCierreArt;
				}
				$ingresos = array(
					"filas" => $totFilasIng,
					"cantidad" => $totCantIng,
					"cantidad_cierre" => $totE20Cierre,
					"disponible" => true
				);
			}
		}

		// Enriquecer artículos con inicio_corte / ingresos / brecha / cadena servicio
		for ($i = 0, $n = count($articulos); $i < $n; $i++) {
			$artKey = (string) $articulos[$i]["articulo"];
			$inicioCorte = (float) $articulos[$i]["cantidad_corte"];
			$enProceso = (float) $articulos[$i]["alm_corte_calc"]
				+ (float) $articulos[$i]["taller_calc"]
				+ (float) $articulos[$i]["servicio_calc"];
			$ingArt = 0.0;
			$ingDisponible = !empty($ingresos["disponible"]);
			if ($ingDisponible && isset($ingresosPorArticulo[$artKey])) {
				$ingArt = (float) $ingresosPorArticulo[$artKey]["cantidad"];
			}
			$brecha = null;
			if ($ingDisponible) {
				$brecha = round($inicioCorte - ($enProceso + $ingArt), 4);
			}

			$servOrigen = (float) $articulos[$i]["servicio_origen"];
			$servAbierto = (float) $articulos[$i]["servicio_abierto"];
			$cierreInicio = (float) $articulos[$i]["cierre_inicio"];
			$cierrePend = (float) $articulos[$i]["cierre"];
			$e20Cierre = 0.0;
			if ($ingDisponible && isset($e20CierrePorArticulo[$artKey])) {
				$e20Cierre = (float) $e20CierrePorArticulo[$artKey];
			}

			// Servicio → cierre: origen = abierto + inicio de cierres
			$brechaServCierre = round($servOrigen - ($servAbierto + $cierreInicio), 4);
			// Cierre → ingreso: inicio cierre = pendiente + E20 con idcierre
			$brechaCierreIng = null;
			if ($ingDisponible) {
				$brechaCierreIng = round($cierreInicio - ($cierrePend + $e20Cierre), 4);
			}
			// Cadena completa: origen = abierto + cierre pend + E20 cierre
			$brechaCadena = null;
			$cierreInicioCalc = null;
			$servAbiertoCalc = null;
			if ($ingDisponible) {
				$brechaCadena = round($servOrigen - ($servAbierto + $cierrePend + $e20Cierre), 4);
				$cierreInicioCalc = round($cierrePend + $e20Cierre, 4);
				// Pendiente legítimo = origen − lo ya cerrado (no inflar origen)
				$servAbiertoCalc = round(max(0, $servOrigen - $cierreInicioCalc), 4);
			} else {
				$servAbiertoCalc = round(max(0, $servOrigen - $cierreInicio), 4);
			}

			$articulos[$i]["inicio_corte"] = $inicioCorte;
			$articulos[$i]["en_proceso"] = round($enProceso, 4);
			$articulos[$i]["ingresos_e20"] = $ingArt;
			$articulos[$i]["ingresos_disponible"] = $ingDisponible ? 1 : 0;
			$articulos[$i]["brecha"] = $brecha;
			$articulos[$i]["e20_cierre"] = $e20Cierre;
			$articulos[$i]["brecha_serv_cierre"] = $brechaServCierre;
			$articulos[$i]["brecha_cierre_ing"] = $brechaCierreIng;
			$articulos[$i]["brecha_cadena"] = $brechaCadena;
			$articulos[$i]["cierre_inicio_calc"] = $cierreInicioCalc;
			$articulos[$i]["servicio_abierto_calc"] = $servAbiertoCalc;
			$articulos[$i]["servicio_origen_calc"] = null;
		}

		$sqlHuerfanosCorte = "
			SELECT
				acd.id,
				acd.articulo,
				COALESCE(a.color, '') AS color,
				COALESCE(a.talla, '') AS talla,
				acd.almacencorte,
				acd.ordencorte,
				acd.detordencorte,
				ROUND(COALESCE(acd.cantidad, 0), 4) AS cantidad,
				ROUND(COALESCE(acd.saldo_taller, 0), 4) AS saldo_taller
			FROM almacencorte_detallejf acd
			INNER JOIN articulojf a ON a.articulo = acd.articulo
			LEFT JOIN detalles_ordencortejf doc ON doc.id = acd.detordencorte
			WHERE a.modelo = :modelo
				AND (
					acd.ordencorte IS NULL
					OR acd.ordencorte = 0
					OR acd.detordencorte IS NULL
					OR acd.detordencorte = 0
					OR doc.id IS NULL
				)
			ORDER BY acd.articulo ASC, acd.id ASC
		";
		$stmtH = $db->prepare($sqlHuerfanosCorte);
		$stmtH->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmtH->execute();
		$corteSinOc = $stmtH->fetchAll(PDO::FETCH_ASSOC);
		$stmtH->closeCursor();

		$sqlEnvioSinCorte = "
			SELECT
				e.id,
				e.articulo,
				COALESCE(a.color, '') AS color,
				COALESCE(a.talla, '') AS talla,
				e.almacencorte_detalle_id,
				ROUND(COALESCE(e.cantidad, 0), 4) AS cantidad,
				ROUND(COALESCE(e.saldo, 0), 4) AS saldo
			FROM entaller_cabjf e
			INNER JOIN articulojf a ON a.articulo = e.articulo
			LEFT JOIN almacencorte_detallejf acd ON acd.id = e.almacencorte_detalle_id
			WHERE a.modelo = :modelo
				AND (
					e.almacencorte_detalle_id IS NULL
					OR e.almacencorte_detalle_id = 0
					OR acd.id IS NULL
				)
			ORDER BY e.articulo ASC, e.id ASC
		";
		$stmtE = $db->prepare($sqlEnvioSinCorte);
		$stmtE->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmtE->execute();
		$envioSinCorte = $stmtE->fetchAll(PDO::FETCH_ASSOC);
		$stmtE->closeCursor();

		$sqlServicioSinEnvio = "
			SELECT
				s.id,
				s.articulo,
				COALESCE(a.color, '') AS color,
				COALESCE(a.talla, '') AS talla,
				s.cabecera_taller,
				s.codigo,
				ROUND(COALESCE(s.cantidad, 0), 4) AS cantidad,
				ROUND(COALESCE(s.saldo, 0), 4) AS saldo,
				s.cerrar
			FROM servicios_detallejf s
			INNER JOIN articulojf a ON a.articulo = s.articulo
			LEFT JOIN entaller_cabjf e ON e.id = s.cabecera_taller
			WHERE a.modelo = :modelo
				AND (
					s.cabecera_taller IS NULL
					OR s.cabecera_taller = 0
					OR e.id IS NULL
				)
			ORDER BY s.articulo ASC, s.id ASC
		";
		$stmtS = $db->prepare($sqlServicioSinEnvio);
		$stmtS->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$stmtS->execute();
		$servicioSinEnvio = $stmtS->fetchAll(PDO::FETCH_ASSOC);
		$stmtS->closeCursor();

		$totOrdCorte = 0.0;
		$totAlmCorte = 0.0;
		$totTaller = 0.0;
		$totServicio = 0.0;
		$totStock = 0.0;
		$totOrdCalc = 0.0;
		$totAlmCalc = 0.0;
		$totTallerCalc = 0.0;
		$totServCalc = 0.0;
		$filasOc = 0;
		$cantOc = 0.0;
		$filasCorte = 0;
		$cantCorte = 0.0;
		$saldoTallerDoc = 0.0;
		$filasEnvio = 0;
		$cantEnvio = 0.0;
		$filasServ = 0;
		$servAbierto = 0.0;
		$filasCierre = 0;
		$cantCierre = 0.0;
		$totInicioCorte = 0.0;
		$totEnProceso = 0.0;
		$totBrecha = null;
		$totEntExtSaldo = 0.0;
		$totEntExtCalc = 0.0;
		$totServOrigen = 0.0;
		$totCierreInicio = 0.0;
		$totE20Cierre = 0.0;
		$totBrechaServCierre = 0.0;
		$totBrechaCierreIng = null;
		$totBrechaCadena = null;
		$ingresosDisponible = !empty($ingresos["disponible"]);

		foreach ($articulos as $a) {
			$totOrdCorte += (float) $a["ord_corte"];
			$totAlmCorte += (float) $a["alm_corte"];
			$totTaller += (float) $a["taller"];
			$totServicio += (float) $a["servicio"];
			$totStock += (float) $a["stock"];
			$totOrdCalc += (float) $a["ord_corte_calc"];
			$totAlmCalc += (float) $a["alm_corte_calc"];
			$totTallerCalc += (float) $a["taller_calc"];
			$totServCalc += (float) $a["servicio_calc"];
			$filasOc += (int) $a["filas_oc"];
			$cantOc += (float) $a["cantidad_oc"];
			$filasCorte += (int) $a["filas_corte"];
			$cantCorte += (float) $a["cantidad_corte"];
			$saldoTallerDoc += (float) $a["alm_corte_calc"];
			$filasEnvio += (int) $a["filas_envio"];
			$cantEnvio += (float) $a["cantidad_envio"];
			$filasServ += (int) $a["filas_servicio"];
			$servAbierto += (float) $a["servicio_abierto"];
			$filasCierre += (int) $a["filas_cierre"];
			$cantCierre += (float) $a["cierre"];
			$totInicioCorte += (float) $a["inicio_corte"];
			$totEnProceso += (float) $a["en_proceso"];
			$totEntExtSaldo += (float) $a["entaller_ext_saldo"];
			$totEntExtCalc += (float) $a["entaller_ext_calc"];
			$totServOrigen += (float) $a["servicio_origen"];
			$totCierreInicio += (float) $a["cierre_inicio"];
			$totE20Cierre += (float) $a["e20_cierre"];
			$totBrechaServCierre += (float) $a["brecha_serv_cierre"];
		}

		if ($ingresosDisponible) {
			$totBrecha = round(
				$totInicioCorte - ($totEnProceso + (float) $ingresos["cantidad"]),
				4
			);
			$totBrechaCierreIng = round(
				$totCierreInicio - ($cantCierre + $totE20Cierre),
				4
			);
			$totBrechaCadena = round(
				$totServOrigen - ($servAbierto + $cantCierre + $totE20Cierre),
				4
			);
		}

		return array(
			"existe" => true,
			"modelo" => $modelo,
			"anio" => $anio,
			"articulos" => $articulos,
			"resumen" => array(
				"articulos" => count($articulos),
				"ord_corte" => $totOrdCorte,
				"alm_corte" => $totAlmCorte,
				"taller" => $totTaller,
				"servicio" => $totServicio,
				"stock" => $totStock,
				"ord_corte_calc" => $totOrdCalc,
				"alm_corte_calc" => $totAlmCalc,
				"taller_calc" => $totTallerCalc,
				"servicio_calc" => $totServCalc,
				"inicio_corte" => $totInicioCorte,
				"en_proceso" => round($totEnProceso, 4),
				"ingresos_e20" => (float) $ingresos["cantidad"],
				"ingresos_disponible" => $ingresosDisponible ? 1 : 0,
				"brecha" => $totBrecha,
				"entaller_ext_saldo" => $totEntExtSaldo,
				"entaller_ext_calc" => $totEntExtCalc,
				"servicio_origen" => $totServOrigen,
				"cierre_inicio" => $totCierreInicio,
				"e20_cierre" => $totE20Cierre,
				"brecha_serv_cierre" => round($totBrechaServCierre, 4),
				"brecha_cierre_ing" => $totBrechaCierreIng,
				"brecha_cadena" => $totBrechaCadena
			),
			"documentos" => array(
				"oc" => array("filas" => $filasOc, "cantidad" => $cantOc, "saldo" => $totOrdCalc),
				"corte" => array(
					"filas" => $filasCorte,
					"cantidad" => $cantCorte,
					"saldo_taller" => $saldoTallerDoc
				),
				"envio" => array("filas" => $filasEnvio, "cantidad" => $cantEnvio, "saldo" => $totTallerCalc),
				"servicio_abierto" => array("filas" => $filasServ, "saldo" => $servAbierto),
				"cierre" => array("filas" => $filasCierre, "cantidad" => $cantCierre, "inicio" => $totCierreInicio),
				"ingresos_e20" => $ingresos,
				"entaller_ext" => array(
					"saldo" => $totEntExtSaldo,
					"calculado" => $totEntExtCalc
				)
			),
			"huerfanos" => array(
				"corte_sin_oc" => $corteSinOc,
				"envio_sin_corte" => $envioSinCorte,
				"servicio_sin_envio" => $servicioSinEnvio
			),
			"ingresos" => $ingresos
		);
	}

	/**
	 * Sincroniza espejos de articulojf, saldos entaller externo y cadena
	 * servicio → cierre → ingreso (ajusta inicio/cantidad documental; no toca E20 ni stock).
	 */
	static public function mdlCorregirSaldosModelo($modelo)
	{
		$modelo = trim((string) $modelo);
		if ($modelo === "") {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Modelo vacío");
		}

		$db = Conexion::conectar();
		date_default_timezone_set("America/Lima");
		$anio = (int) date("Y");
		$tablaMov = self::mdlTablaMovimientosAnio($anio);
		$db->beginTransaction();

		try {
			$cierresFix = 0;
			$serviciosFix = 0;
			$entallerExt = 0;
			$actualizados = 0;

			// 1) Cierre → ingreso: inicio = cantidad pendiente + E20 ligados
			$tieneMov = false;
			try {
				$chk = $db->query("SHOW TABLES LIKE " . $db->quote($tablaMov));
				if ($chk) {
					$tieneMov = (bool) $chk->fetch(PDO::FETCH_NUM);
					$chk->closeCursor();
				}
			} catch (Exception $eChk) {
				$tieneMov = false;
			}
			if (!$tieneMov) {
				try {
					$probe = $db->query("SELECT 1 FROM `{$tablaMov}` LIMIT 1");
					if ($probe !== false) {
						$tieneMov = true;
						$probe->closeCursor();
					}
				} catch (Exception $eProbe) {
					$tieneMov = false;
				}
			}

			if ($tieneMov) {
				// Misma regla que el preview: inicio(artículo) = pend + E20 con idcierre>0
				$e20PorArt = array();
				$stmtE20 = $db->prepare("
					SELECT
						m.articulo,
						ROUND(COALESCE(SUM(m.cantidad), 0), 4) AS cant_e20
					FROM `{$tablaMov}` m
					INNER JOIN articulojf a ON a.articulo = m.articulo
					WHERE a.modelo = :modelo
						AND m.tipo = 'E20'
						AND m.idcierre IS NOT NULL
						AND m.idcierre > 0
					GROUP BY m.articulo
				");
				$stmtE20->bindValue(":modelo", $modelo, PDO::PARAM_STR);
				$stmtE20->execute();
				foreach ($stmtE20->fetchAll(PDO::FETCH_ASSOC) as $rowE) {
					$e20PorArt[(string) $rowE["articulo"]] = (float) $rowE["cant_e20"];
				}
				$stmtE20->closeCursor();

				$sqlCierres = "
					SELECT
						c.id,
						c.articulo,
						COALESCE(c.inicio, c.cantidad) AS inicio_actual,
						COALESCE(c.cantidad, 0) AS cantidad
					FROM cierres_detallejf c
					INNER JOIN articulojf a ON a.articulo = c.articulo
					WHERE a.modelo = :modelo
					ORDER BY c.articulo ASC, c.id ASC
				";
				$stmtC = $db->prepare($sqlCierres);
				$stmtC->bindValue(":modelo", $modelo, PDO::PARAM_STR);
				$stmtC->execute();
				$filasC = $stmtC->fetchAll(PDO::FETCH_ASSOC);
				$stmtC->closeCursor();

				$cierresPorArt = array();
				foreach ($filasC as $c) {
					$artKey = (string) $c["articulo"];
					if (!isset($cierresPorArt[$artKey])) {
						$cierresPorArt[$artKey] = array();
					}
					$cierresPorArt[$artKey][] = $c;
				}

				$updC = $db->prepare("UPDATE cierres_detallejf SET inicio = :inicio WHERE id = :id");
				foreach ($cierresPorArt as $artKey => $lines) {
					$pend = 0.0;
					foreach ($lines as $c) {
						$pend += (float) $c["cantidad"];
					}
					$e20Art = isset($e20PorArt[$artKey]) ? (float) $e20PorArt[$artKey] : 0.0;
					$target = round($pend + $e20Art, 4);
					$n = count($lines);
					$restante = $target;

					for ($i = 0; $i < $n; $i++) {
						$id = (int) $lines[$i]["id"];
						$actual = (float) $lines[$i]["inicio_actual"];
						$cantLine = (float) $lines[$i]["cantidad"];
						if ($i === $n - 1) {
							$esperado = round($restante, 4);
						} else {
							$esperado = round(min($cantLine, $restante), 4);
							$restante = round($restante - $esperado, 4);
						}
						if (round($actual, 4) === round($esperado, 4)) {
							continue;
						}
						$updC->bindValue(":inicio", $esperado);
						$updC->bindValue(":id", $id, PDO::PARAM_INT);
						$updC->execute();
						$cierresFix++;
					}
				}
			}

			// 2) Servicio → cierre (por artículo):
			//    abierto esperado = max(0, SUM(cantidad) − SUM(inicio cierres del artículo)).
			//    Redistribuye saldos en las líneas (quita pendiente fantasma; no infla origen).
			$sqlArts = "
				SELECT a.articulo
				FROM articulojf a
				WHERE a.modelo = :modelo
			";
			$stmtArts = $db->prepare($sqlArts);
			$stmtArts->bindValue(":modelo", $modelo, PDO::PARAM_STR);
			$stmtArts->execute();
			$artsModelo = $stmtArts->fetchAll(PDO::FETCH_ASSOC);
			$stmtArts->closeCursor();

			$stmtSumOrig = $db->prepare("
				SELECT COALESCE(SUM(cantidad), 0) AS origen
				FROM servicios_detallejf
				WHERE articulo = :articulo
			");
			$stmtSumCierre = $db->prepare("
				SELECT COALESCE(SUM(COALESCE(inicio, cantidad)), 0) AS cierre_inicio
				FROM cierres_detallejf
				WHERE articulo = :articulo
			");
			$stmtLines = $db->prepare("
				SELECT id, cantidad, saldo, cerrar
				FROM servicios_detallejf
				WHERE articulo = :articulo
				ORDER BY
					CASE WHEN COALESCE(saldo, 0) > 0 THEN 0 ELSE 1 END ASC,
					CASE WHEN COALESCE(cerrar, 0) = 0 THEN 0 ELSE 1 END ASC,
					id ASC
			");
			$updS = $db->prepare("
				UPDATE servicios_detallejf
				SET saldo = :saldo, cerrar = :cerrar
				WHERE id = :id
			");

			foreach ($artsModelo as $artRow) {
				$articulo = isset($artRow["articulo"]) ? trim((string) $artRow["articulo"]) : "";
				if ($articulo === "") {
					continue;
				}

				$stmtSumOrig->bindValue(":articulo", $articulo, PDO::PARAM_STR);
				$stmtSumOrig->execute();
				$sumOrig = $stmtSumOrig->fetch(PDO::FETCH_ASSOC);
				$stmtSumOrig->closeCursor();
				$origen = (float) (isset($sumOrig["origen"]) ? $sumOrig["origen"] : 0);

				$stmtSumCierre->bindValue(":articulo", $articulo, PDO::PARAM_STR);
				$stmtSumCierre->execute();
				$sumCierre = $stmtSumCierre->fetch(PDO::FETCH_ASSOC);
				$stmtSumCierre->closeCursor();
				$cierreInicio = (float) (isset($sumCierre["cierre_inicio"]) ? $sumCierre["cierre_inicio"] : 0);

				$targetAbierto = round(max(0, $origen - $cierreInicio), 4);

				$stmtLines->bindValue(":articulo", $articulo, PDO::PARAM_STR);
				$stmtLines->execute();
				$lines = $stmtLines->fetchAll(PDO::FETCH_ASSOC);
				$stmtLines->closeCursor();

				$restante = $targetAbierto;
				foreach ($lines as $line) {
					$id = (int) $line["id"];
					$cantLine = (float) $line["cantidad"];
					$saldoActual = (float) $line["saldo"];
					$cerrarActual = (int) $line["cerrar"];

					if ($restante <= 0) {
						$saldoNuevo = 0.0;
					} else {
						$saldoNuevo = round(min($cantLine, $restante), 4);
						$restante = round($restante - $saldoNuevo, 4);
					}
					$cerrarNuevo = ($saldoNuevo <= 0) ? 1 : 0;

					if (
						round($saldoActual, 4) === round($saldoNuevo, 4)
						&& $cerrarActual === $cerrarNuevo
					) {
						continue;
					}

					$updS->bindValue(":saldo", $saldoNuevo);
					$updS->bindValue(":cerrar", $cerrarNuevo, PDO::PARAM_INT);
					$updS->bindValue(":id", $id, PDO::PARAM_INT);
					$updS->execute();
					$serviciosFix++;
				}
			}

			// 3) Espejos articulojf (tras corregir documentos)
			$sql = "
				SELECT
					a.articulo,
					ROUND(COALESCE(oc.saldo_oc, 0), 4) AS ord_corte_calc,
					ROUND(COALESCE(ac.saldo_taller, 0), 4) AS alm_corte_calc,
					ROUND(COALESCE(et.saldo_taller_doc, 0), 4) AS taller_calc,
					ROUND(COALESCE(sv.servicio_abierto, 0) + COALESCE(cj.cierre, 0), 4) AS servicio_calc
				FROM articulojf a
				LEFT JOIN (
					SELECT articulo, SUM(saldo) AS saldo_oc
					FROM detalles_ordencortejf
					GROUP BY articulo
				) oc ON oc.articulo = a.articulo
				LEFT JOIN (
					SELECT
						articulo,
						SUM(CASE WHEN saldo_taller > 0 THEN saldo_taller ELSE 0 END) AS saldo_taller
					FROM almacencorte_detallejf
					GROUP BY articulo
				) ac ON ac.articulo = a.articulo
				LEFT JOIN (
					SELECT
						e.articulo,
						SUM(
							CASE
								WHEN UPPER(COALESCE(e.taller, '')) = 'VC' THEN COALESCE(e.saldo, 0)
								WHEN s.tipo = 0 OR s.tipo IS NULL THEN COALESCE(e.saldo, 0)
								ELSE 0
							END
						) AS saldo_taller_doc
					FROM entaller_cabjf e
					LEFT JOIN sectorjf s ON s.cod_sector = e.taller
					GROUP BY e.articulo
				) et ON et.articulo = a.articulo
				LEFT JOIN (
					SELECT articulo, SUM(saldo) AS servicio_abierto
					FROM servicios_detallejf
					WHERE cerrar = 0
					GROUP BY articulo
				) sv ON sv.articulo = a.articulo
				LEFT JOIN (
					SELECT articulo, SUM(cantidad) AS cierre
					FROM cierres_detallejf
					GROUP BY articulo
				) cj ON cj.articulo = a.articulo
				WHERE a.modelo = :modelo
			";

			$stmt = $db->prepare($sql);
			$stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
			$stmt->execute();
			$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();

			if (!$filas || count($filas) < 1) {
				$db->rollBack();
				return array(
					"ok" => false,
					"actualizados" => 0,
					"entaller_ext" => 0,
					"cierres" => 0,
					"servicios" => 0,
					"mensaje" => "No hay artículos con ese modelo"
				);
			}

			$upd = $db->prepare("
				UPDATE articulojf
				SET
					ord_corte = :ord_corte,
					alm_corte = :alm_corte,
					taller = :taller,
					servicio = :servicio
				WHERE articulo = :articulo
			");

			foreach ($filas as $f) {
				$articulo = isset($f["articulo"]) ? trim((string) $f["articulo"]) : "";
				if ($articulo === "") {
					continue;
				}
				$upd->bindValue(":ord_corte", (float) $f["ord_corte_calc"]);
				$upd->bindValue(":alm_corte", (float) $f["alm_corte_calc"]);
				$upd->bindValue(":taller", (float) $f["taller_calc"]);
				$upd->bindValue(":servicio", (float) $f["servicio_calc"]);
				$upd->bindValue(":articulo", $articulo, PDO::PARAM_STR);
				$upd->execute();
				if ($upd->rowCount() > 0) {
					$actualizados++;
				}
			}

			// 4) Envíos a servicio externo
			$sqlExt = "
				SELECT
					e.id,
					COALESCE(e.saldo, 0) AS saldo_actual,
					COALESCE(sv.saldo_esp, 0) AS saldo_esp
				FROM entaller_cabjf e
				INNER JOIN articulojf a ON a.articulo = e.articulo
				INNER JOIN sectorjf s
					ON s.cod_sector = e.taller
					AND s.tipo IS NOT NULL
					AND s.tipo <> 0
				LEFT JOIN (
					SELECT
						cabecera_taller,
						SUM(saldo) AS saldo_esp
					FROM servicios_detallejf
					WHERE cerrar = 0
						AND cabecera_taller IS NOT NULL
						AND cabecera_taller <> 0
					GROUP BY cabecera_taller
				) sv ON sv.cabecera_taller = e.id
				WHERE a.modelo = :modelo
			";
			$stmtExt = $db->prepare($sqlExt);
			$stmtExt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
			$stmtExt->execute();
			$filasExt = $stmtExt->fetchAll(PDO::FETCH_ASSOC);
			$stmtExt->closeCursor();

			$updExt = $db->prepare("
				UPDATE entaller_cabjf
				SET
					saldo = :saldo,
					estado = CASE WHEN :saldo_estado <= 0 THEN 1 ELSE 0 END
				WHERE id = :id
			");

			foreach ($filasExt as $ex) {
				$id = (int) $ex["id"];
				$actual = (float) $ex["saldo_actual"];
				$esperado = (float) $ex["saldo_esp"];
				if (round($actual, 4) === round($esperado, 4)) {
					continue;
				}
				$updExt->bindValue(":saldo", $esperado);
				$updExt->bindValue(":saldo_estado", $esperado);
				$updExt->bindValue(":id", $id, PDO::PARAM_INT);
				$updExt->execute();
				if ($updExt->rowCount() > 0) {
					$entallerExt++;
				}
			}

			$db->commit();
			$msgMov = $tieneMov ? "" : " (sin movimientos {$tablaMov}: no se ajustó inicio de cierres)";
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"entaller_ext" => $entallerExt,
				"cierres" => $cierresFix,
				"servicios" => $serviciosFix,
				"movimientos" => $tieneMov ? 1 : 0,
				"mensaje" => "Modelo {$modelo}: {$actualizados} art., {$serviciosFix} serv., {$cierresFix} cierre(s), {$entallerExt} envío(s) ext.{$msgMov}"
			);
		} catch (Exception $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			return array(
				"ok" => false,
				"actualizados" => 0,
				"entaller_ext" => 0,
				"cierres" => 0,
				"servicios" => 0,
				"mensaje" => "Error al corregir saldos del modelo"
			);
		}
	}
}
