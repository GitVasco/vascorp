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

	/**
	 * Cargos (tip_mov = '+') sin fecha de vencimiento válida,
	 * con fecha de documento usable para completar.
	 */
	static public function mdlCteSinFechaVen()
	{
		$sql = "
			SELECT
				c.id,
				COALESCE(c.tipo_doc, '') AS tipo_doc,
				COALESCE(c.num_cta, '') AS num_cta,
				COALESCE(c.cliente, '') AS cliente,
				COALESCE(cli.nombre, '') AS cliente_nombre,
				COALESCE(c.fecha, '') AS fecha,
				ROUND(COALESCE(c.monto, 0), 2) AS monto,
				ROUND(COALESCE(c.saldo, 0), 2) AS saldo,
				COALESCE(c.estado, '') AS estado
			FROM cuenta_ctejf c
			LEFT JOIN clientesjf cli ON cli.codigo = c.cliente
			WHERE c.tip_mov = '+'
				AND (
					c.fecha_ven IS NULL
					OR TRIM(c.fecha_ven) = ''
					OR c.fecha_ven = '0000-00-00'
				)
				AND c.fecha IS NOT NULL
				AND TRIM(c.fecha) <> ''
				AND c.fecha <> '0000-00-00'
			ORDER BY c.fecha ASC, c.id ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Completa fecha_ven = fecha en los ids indicados (solo cargos sin vencimiento).
	 * $ids: array de enteros
	 */
	static public function mdlCompletarFechaVenCte($ids)
	{
		if (!is_array($ids) || count($ids) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$limpios = array();
		foreach ($ids as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$limpios[$id] = $id;
			}
		}
		$limpios = array_values($limpios);
		if (count($limpios) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$placeholders = implode(",", array_fill(0, count($limpios), "?"));
		$sql = "
			UPDATE cuenta_ctejf
			SET fecha_ven = fecha
			WHERE tip_mov = '+'
				AND id IN ({$placeholders})
				AND (
					fecha_ven IS NULL
					OR TRIM(fecha_ven) = ''
					OR fecha_ven = '0000-00-00'
				)
				AND fecha IS NOT NULL
				AND TRIM(fecha) <> ''
				AND fecha <> '0000-00-00'
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($limpios as $i => $id) {
				$stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"mensaje" => "Se completaron {$actualizados} registro(s)"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar fechas de vencimiento"
			);
		}
	}

	/**
	 * Abonos (tip_mov = '-') sin fecha_ori / fecha_ori_ven, últimos N días,
	 * con cargo (+) del mismo tipo_doc + num_cta.
	 */
	static public function mdlCteSinFechaOri($dias = 60)
	{
		$dias = (int) $dias;
		if ($dias < 1) {
			$dias = 60;
		}
		if ($dias > 3650) {
			$dias = 3650;
		}

		$sql = "
			SELECT
				cc.id,
				COALESCE(cc.tipo_doc, '') AS tipo_doc,
				COALESCE(cc.num_cta, '') AS num_cta,
				COALESCE(cc.cliente, '') AS cliente,
				COALESCE(cli.nombre, '') AS cliente_nombre,
				COALESCE(cc.fecha, '') AS fecha,
				COALESCE(cc.fecha_ori, '') AS fecha_ori,
				COALESCE(cc.fecha_ori_ven, '') AS fecha_ori_ven,
				COALESCE(c1.fecha, '') AS fecha_ori_prop,
				COALESCE(c1.fecha_ven, '') AS fecha_ori_ven_prop,
				ROUND(COALESCE(cc.monto, 0), 2) AS monto,
				COALESCE(cc.estado, '') AS estado
			FROM cuenta_ctejf cc
			LEFT JOIN clientesjf cli ON cli.codigo = cc.cliente
			INNER JOIN (
				SELECT
					c.tipo_doc,
					c.num_cta,
					c.fecha,
					c.fecha_ven
				FROM cuenta_ctejf c
				INNER JOIN (
					SELECT tipo_doc, num_cta, MIN(id) AS id
					FROM cuenta_ctejf
					WHERE tip_mov = '+'
					GROUP BY tipo_doc, num_cta
				) x ON x.id = c.id
			) c1
				ON cc.tipo_doc = c1.tipo_doc
				AND cc.num_cta = c1.num_cta
			WHERE cc.tip_mov = '-'
				AND cc.fecha >= (CURDATE() - INTERVAL {$dias} DAY)
				AND cc.fecha <= CURDATE()
				AND (
					cc.fecha_ori IS NULL
					OR TRIM(cc.fecha_ori) = ''
					OR cc.fecha_ori = '0000-00-00'
					OR cc.fecha_ori_ven IS NULL
					OR TRIM(cc.fecha_ori_ven) = ''
					OR cc.fecha_ori_ven = '0000-00-00'
				)
				AND c1.fecha IS NOT NULL
				AND TRIM(c1.fecha) <> ''
				AND c1.fecha <> '0000-00-00'
			ORDER BY cc.fecha ASC, cc.id ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Completa fecha_ori / fecha_ori_ven desde el cargo (+) del mismo documento.
	 * $ids: array de enteros (abonos)
	 */
	static public function mdlCompletarFechaOriCte($ids)
	{
		if (!is_array($ids) || count($ids) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$limpios = array();
		foreach ($ids as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$limpios[$id] = $id;
			}
		}
		$limpios = array_values($limpios);
		if (count($limpios) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$placeholders = implode(",", array_fill(0, count($limpios), "?"));
		$sql = "
			UPDATE cuenta_ctejf cc
			INNER JOIN (
				SELECT
					c.tipo_doc,
					c.num_cta,
					c.fecha,
					c.fecha_ven
				FROM cuenta_ctejf c
				INNER JOIN (
					SELECT tipo_doc, num_cta, MIN(id) AS id
					FROM cuenta_ctejf
					WHERE tip_mov = '+'
					GROUP BY tipo_doc, num_cta
				) x ON x.id = c.id
			) c1
				ON cc.tipo_doc = c1.tipo_doc
				AND cc.num_cta = c1.num_cta
			SET
				cc.fecha_ori = c1.fecha,
				cc.fecha_ori_ven = c1.fecha_ven
			WHERE cc.tip_mov = '-'
				AND cc.id IN ({$placeholders})
				AND (
					cc.fecha_ori IS NULL
					OR TRIM(cc.fecha_ori) = ''
					OR cc.fecha_ori = '0000-00-00'
					OR cc.fecha_ori_ven IS NULL
					OR TRIM(cc.fecha_ori_ven) = ''
					OR cc.fecha_ori_ven = '0000-00-00'
				)
				AND c1.fecha IS NOT NULL
				AND TRIM(c1.fecha) <> ''
				AND c1.fecha <> '0000-00-00'
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($limpios as $i => $id) {
				$stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"mensaje" => "Se completaron {$actualizados} registro(s)"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar fechas de origen"
			);
		}
	}

	/**
	 * Cuentas del año sin tip_cambio, con cambio_venta en totalesjf para esa fecha.
	 */
	static public function mdlCteSinTipCambio($anio = null)
	{
		date_default_timezone_set("America/Lima");
		$anio = $anio === null ? (int) date("Y") : (int) $anio;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}

		$sql = "
			SELECT
				cc.id,
				COALESCE(cc.tipo_doc, '') AS tipo_doc,
				COALESCE(cc.num_cta, '') AS num_cta,
				COALESCE(cc.tip_mov, '') AS tip_mov,
				COALESCE(cc.cliente, '') AS cliente,
				COALESCE(cli.nombre, '') AS cliente_nombre,
				COALESCE(cc.fecha, '') AS fecha,
				ROUND(COALESCE(cc.tip_cambio, 0), 4) AS tip_cambio,
				ROUND(COALESCE(t.cambio_venta, 0), 4) AS tip_cambio_prop,
				ROUND(COALESCE(cc.monto, 0), 2) AS monto,
				COALESCE(cc.estado, '') AS estado
			FROM cuenta_ctejf cc
			LEFT JOIN clientesjf cli ON cli.codigo = cc.cliente
			INNER JOIN (
				SELECT
					DATE(fecha) AS fecha_dia,
					MAX(cambio_venta) AS cambio_venta
				FROM totalesjf
				WHERE cambio_venta IS NOT NULL
					AND cambio_venta <> 0
				GROUP BY DATE(fecha)
			) t ON DATE(cc.fecha) = t.fecha_dia
			WHERE YEAR(cc.fecha) = :anio
				AND (
					cc.tip_cambio IS NULL
					OR cc.tip_cambio = 0
				)
			ORDER BY cc.fecha ASC, cc.id ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":anio", $anio, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Completa tip_cambio = cambio_venta de totalesjf (misma fecha) en los ids.
	 */
	static public function mdlCompletarTipCambioCte($ids, $anio = null)
	{
		if (!is_array($ids) || count($ids) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		date_default_timezone_set("America/Lima");
		$anio = $anio === null ? (int) date("Y") : (int) $anio;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}

		$limpios = array();
		foreach ($ids as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$limpios[$id] = $id;
			}
		}
		$limpios = array_values($limpios);
		if (count($limpios) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$placeholders = implode(",", array_fill(0, count($limpios), "?"));
		$sql = "
			UPDATE cuenta_ctejf cc
			INNER JOIN (
				SELECT
					DATE(fecha) AS fecha_dia,
					MAX(cambio_venta) AS cambio_venta
				FROM totalesjf
				WHERE cambio_venta IS NOT NULL
					AND cambio_venta <> 0
				GROUP BY DATE(fecha)
			) t ON DATE(cc.fecha) = t.fecha_dia
			SET cc.tip_cambio = t.cambio_venta
			WHERE YEAR(cc.fecha) = ?
				AND cc.id IN ({$placeholders})
				AND (
					cc.tip_cambio IS NULL
					OR cc.tip_cambio = 0
				)
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->bindValue(1, $anio, PDO::PARAM_INT);
			foreach ($limpios as $i => $id) {
				$stmt->bindValue($i + 2, $id, PDO::PARAM_INT);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"mensaje" => "Se actualizaron {$actualizados} registro(s)"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al actualizar tipo de cambio"
			);
		}
	}

	/**
	 * Ventas del año con tipo_cambio en 0/NULL (fecha &lt; hoy),
	 * con cambio_venta en totalesjf para esa fecha.
	 */
	static public function mdlVentasSinTipCambio($anio = null)
	{
		date_default_timezone_set("America/Lima");
		$anio = $anio === null ? (int) date("Y") : (int) $anio;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}

		$sql = "
			SELECT
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(cli.nombre, '') AS cliente_nombre,
				COALESCE(v.fecha, '') AS fecha,
				ROUND(COALESCE(v.tipo_cambio, 0), 4) AS tipo_cambio,
				ROUND(COALESCE(t.cambio_venta, 0), 4) AS tipo_cambio_prop,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM ventajf v
			LEFT JOIN clientesjf cli ON cli.codigo = v.cliente
			INNER JOIN (
				SELECT
					DATE(fecha) AS fecha_dia,
					MAX(cambio_venta) AS cambio_venta
				FROM totalesjf
				WHERE cambio_venta IS NOT NULL
					AND cambio_venta <> 0
				GROUP BY DATE(fecha)
			) t ON DATE(v.fecha) = t.fecha_dia
			WHERE YEAR(v.fecha) = :anio
				AND DATE(v.fecha) < CURDATE()
				AND (
					v.tipo_cambio IS NULL
					OR v.tipo_cambio = 0
				)
			ORDER BY v.fecha ASC, v.tipo ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":anio", $anio, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Completa ventajf.tipo_cambio = totalesjf.cambio_venta (misma fecha).
	 * $items: [ ['tipo' => ..., 'documento' => ...], ... ]
	 */
	static public function mdlCompletarTipCambioVentas($items, $anio = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		date_default_timezone_set("America/Lima");
		$anio = $anio === null ? (int) date("Y") : (int) $anio;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}

		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo === "" || $documento === "") {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$orParts = array();
		$params = array($anio);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		$sql = "
			UPDATE ventajf v
			INNER JOIN (
				SELECT
					DATE(fecha) AS fecha_dia,
					MAX(cambio_venta) AS cambio_venta
				FROM totalesjf
				WHERE cambio_venta IS NOT NULL
					AND cambio_venta <> 0
				GROUP BY DATE(fecha)
			) t ON DATE(v.fecha) = t.fecha_dia
			SET v.tipo_cambio = t.cambio_venta
			WHERE YEAR(v.fecha) = ?
				AND DATE(v.fecha) < CURDATE()
				AND (
					v.tipo_cambio IS NULL
					OR v.tipo_cambio = 0
				)
				AND ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$tipoBind = ($i === 0) ? PDO::PARAM_INT : PDO::PARAM_STR;
				$stmt->bindValue($i + 1, $val, $tipoBind);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"mensaje" => "Se actualizaron {$actualizados} venta(s)"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al actualizar tipo de cambio de ventas"
			);
		}
	}

	/**
	 * Normaliza periodo YYYY-MM → [anio, mes, inicio, fin] o false.
	 */
	static public function mdlPeriodoMes($periodo = null)
	{
		date_default_timezone_set("America/Lima");
		if ($periodo === null || trim((string) $periodo) === "") {
			$periodo = date("Y-m");
		}
		$periodo = trim((string) $periodo);
		if (!preg_match('/^(\d{4})-(\d{2})$/', $periodo, $m)) {
			return false;
		}
		$anio = (int) $m[1];
		$mes = (int) $m[2];
		if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) {
			return false;
		}
		$inicio = sprintf("%04d-%02d-01", $anio, $mes);
		$dt = DateTime::createFromFormat("Y-m-d", $inicio);
		if (!$dt) {
			return false;
		}
		$fin = $dt->format("Y-m-t");
		return array(
			"periodo" => sprintf("%04d-%02d", $anio, $mes),
			"anio" => $anio,
			"mes" => $mes,
			"inicio" => $inicio,
			"fin" => $fin
		);
	}

	/**
	 * Facturas/boletas (S02/S03) del periodo sin cuenta contable.
	 * Propone 702211 (Lima) o 702212 (provincia) según ubigeo del cliente.
	 */
	static public function mdlVentasSinCuenta($periodo = null)
	{
		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return false;
		}

		$sql = "
			SELECT
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(c.nombre, '') AS cliente_nombre,
				COALESCE(c.ubigeo, '') AS ubigeo,
				COALESCE(v.fecha, '') AS fecha,
				COALESCE(v.cuenta, '') AS cuenta,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN '702211'
					ELSE '702212'
				END AS cuenta_prop,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN 'Lima'
					ELSE 'Provincia'
				END AS zona,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM ventajf v
			LEFT JOIN clientesjf c ON v.cliente = c.codigo
			WHERE v.fecha BETWEEN :inicio AND :fin
				AND v.tipo IN ('S02', 'S03')
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
				)
			ORDER BY v.fecha ASC, v.tipo ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array(
			"rango" => $rango,
			"filas" => $filas
		);
	}

	/**
	 * Completa ventajf.cuenta según ubigeo del cliente (S02/S03, periodo).
	 * $items: [ ['tipo'=>..., 'documento'=>...], ... ]
	 */
	static public function mdlCompletarCuentaVentas($items, $periodo = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Periodo inválido");
		}

		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo === "" || $documento === "") {
				continue;
			}
			if ($tipo !== "S02" && $tipo !== "S03") {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$orParts = array();
		$params = array($rango["inicio"], $rango["fin"]);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		$sql = "
			UPDATE ventajf v
			LEFT JOIN clientesjf c ON v.cliente = c.codigo
			SET v.cuenta = CASE
				WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
					OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
				THEN '702211'
				ELSE '702212'
			END
			WHERE v.fecha BETWEEN ? AND ?
				AND v.tipo IN ('S02', 'S03')
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
				)
				AND ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"periodo" => $rango["periodo"],
				"mensaje" => "Se actualizaron {$actualizados} venta(s)"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar cuentas contables"
			);
		}
	}

	/** Cuenta contable POS showroom. */
	const CUENTA_POS_SHOWROOM = "702213";

	/**
	 * Ventas ligadas a abonos POS showroom (vendedor %08%, pagos 06/17)
	 * del periodo, para asignar cuenta 702213.
	 * Mapeo cte→venta: 01→S03, 03→S02, 07→E05, 08→S05.
	 */
	static public function mdlVentasCuentaPos($periodo = null)
	{
		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return false;
		}

		$cuenta = self::CUENTA_POS_SHOWROOM;
		$sql = "
			SELECT
				COALESCE(cc.tipo_doc, '') AS tipo_doc,
				COALESCE(cc.num_cta, '') AS num_cta,
				COALESCE(cc.cod_pago, '') AS cod_pago,
				COALESCE(cc.vendedor, '') AS vendedor,
				COALESCE(cc.fecha, '') AS fecha_pago,
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(cli.nombre, '') AS cliente_nombre,
				COALESCE(v.fecha, '') AS fecha,
				COALESCE(v.cuenta, '') AS cuenta,
				:cuenta_prop AS cuenta_prop,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM cuenta_ctejf cc
			INNER JOIN ventajf v
				ON v.documento = cc.num_cta
				AND (
					(cc.tipo_doc = '01' AND v.tipo = 'S03')
					OR (cc.tipo_doc = '03' AND v.tipo = 'S02')
					OR (cc.tipo_doc = '07' AND v.tipo = 'E05')
					OR (cc.tipo_doc = '08' AND v.tipo = 'S05')
				)
			LEFT JOIN clientesjf cli ON cli.codigo = v.cliente
			WHERE cc.tip_mov = '-'
				AND cc.fecha BETWEEN :inicio AND :fin
				AND cc.vendedor LIKE '%08%'
				AND cc.cod_pago IN ('06', '17')
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
					OR v.cuenta <> :cuenta_diff
				)
			GROUP BY
				v.tipo,
				v.documento,
				v.cliente,
				cli.nombre,
				v.fecha,
				v.cuenta,
				v.total,
				cc.tipo_doc,
				cc.num_cta,
				cc.cod_pago,
				cc.vendedor,
				cc.fecha
			ORDER BY cc.fecha ASC, v.tipo ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":cuenta_prop", $cuenta, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_diff", $cuenta, PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array(
			"rango" => $rango,
			"filas" => $filas,
			"cuenta" => $cuenta
		);
	}

	/**
	 * Asigna cuenta 702213 a ventas (tipo+documento) del periodo POS.
	 * $items: [ ['tipo'=>..., 'documento'=>...], ... ]
	 */
	static public function mdlCompletarCuentaPosVentas($items, $periodo = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Periodo inválido");
		}

		$tiposOk = array("S02" => 1, "S03" => 1, "E05" => 1, "S05" => 1);
		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo === "" || $documento === "" || !isset($tiposOk[$tipo])) {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$cuenta = self::CUENTA_POS_SHOWROOM;
		$orParts = array();
		$params = array($rango["inicio"], $rango["fin"], $cuenta);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		// Solo actualiza si hay abono POS showroom del periodo que mapea a esa venta
		$sql = "
			UPDATE ventajf v
			INNER JOIN (
				SELECT DISTINCT
					v2.tipo,
					v2.documento
				FROM cuenta_ctejf cc
				INNER JOIN ventajf v2
					ON v2.documento = cc.num_cta
					AND (
						(cc.tipo_doc = '01' AND v2.tipo = 'S03')
						OR (cc.tipo_doc = '03' AND v2.tipo = 'S02')
						OR (cc.tipo_doc = '07' AND v2.tipo = 'E05')
						OR (cc.tipo_doc = '08' AND v2.tipo = 'S05')
					)
				WHERE cc.tip_mov = '-'
					AND cc.fecha BETWEEN ? AND ?
					AND cc.vendedor LIKE '%08%'
					AND cc.cod_pago IN ('06', '17')
			) pos ON pos.tipo = v.tipo AND pos.documento = v.documento
			SET v.cuenta = ?
			WHERE ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"periodo" => $rango["periodo"],
				"cuenta" => $cuenta,
				"mensaje" => "Se actualizaron {$actualizados} venta(s) con cuenta {$cuenta}"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar cuenta POS showroom"
			);
		}
	}

	const CUENTA_CULQI_LIMA = "702215";
	const CUENTA_CULQI_PROV = "702216";

	/**
	 * Ventas ligadas a abonos Culqi showroom (vendedor %08%, pago 14, docs 01/03)
	 * del periodo. Cuenta según ubigeo: Lima 702215 / provincia 702216.
	 */
	static public function mdlVentasCuentaCulqi($periodo = null)
	{
		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return false;
		}

		$lima = self::CUENTA_CULQI_LIMA;
		$prov = self::CUENTA_CULQI_PROV;

		$sql = "
			SELECT
				COALESCE(cc.tipo_doc, '') AS tipo_doc,
				COALESCE(cc.num_cta, '') AS num_cta,
				COALESCE(cc.cod_pago, '') AS cod_pago,
				COALESCE(cc.vendedor, '') AS vendedor,
				COALESCE(cc.fecha, '') AS fecha_pago,
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(cli.nombre, '') AS cliente_nombre,
				COALESCE(cli.ubigeo, '') AS ubigeo,
				COALESCE(v.fecha, '') AS fecha,
				COALESCE(v.cuenta, '') AS cuenta,
				CASE
					WHEN LEFT(COALESCE(cli.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(cli.ubigeo, ''), 1) = 'L'
					THEN :cuenta_lima
					ELSE :cuenta_prov
				END AS cuenta_prop,
				CASE
					WHEN LEFT(COALESCE(cli.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(cli.ubigeo, ''), 1) = 'L'
					THEN 'Lima'
					ELSE 'Provincia'
				END AS zona,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM cuenta_ctejf cc
			INNER JOIN ventajf v
				ON v.documento = cc.num_cta
				AND (
					(cc.tipo_doc = '01' AND v.tipo = 'S03')
					OR (cc.tipo_doc = '03' AND v.tipo = 'S02')
				)
			LEFT JOIN clientesjf cli ON cli.codigo = v.cliente
			WHERE cc.tip_mov = '-'
				AND cc.fecha BETWEEN :inicio AND :fin
				AND cc.vendedor LIKE '%08%'
				AND cc.cod_pago = '14'
				AND cc.tipo_doc IN ('01', '03')
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
					OR v.cuenta <> CASE
						WHEN LEFT(COALESCE(cli.ubigeo, ''), 2) = '15'
							OR LEFT(COALESCE(cli.ubigeo, ''), 1) = 'L'
						THEN :cuenta_lima2
						ELSE :cuenta_prov2
					END
				)
			GROUP BY
				v.tipo,
				v.documento,
				v.cliente,
				cli.nombre,
				cli.ubigeo,
				v.fecha,
				v.cuenta,
				v.total,
				cc.tipo_doc,
				cc.num_cta,
				cc.cod_pago,
				cc.vendedor,
				cc.fecha
			ORDER BY cc.fecha ASC, v.tipo ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":cuenta_lima", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_lima2", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov2", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array(
			"rango" => $rango,
			"filas" => $filas,
			"cuenta_lima" => $lima,
			"cuenta_prov" => $prov
		);
	}

	/**
	 * Asigna 702215/702216 a ventas Culqi del periodo (por ubigeo).
	 * $items: [ ['tipo'=>..., 'documento'=>...], ... ]
	 */
	static public function mdlCompletarCuentaCulqiVentas($items, $periodo = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Periodo inválido");
		}

		$tiposOk = array("S02" => 1, "S03" => 1);
		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo === "" || $documento === "" || !isset($tiposOk[$tipo])) {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$lima = self::CUENTA_CULQI_LIMA;
		$prov = self::CUENTA_CULQI_PROV;
		$orParts = array();
		$params = array($rango["inicio"], $rango["fin"], $lima, $prov);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		$sql = "
			UPDATE ventajf v
			INNER JOIN (
				SELECT DISTINCT
					v2.tipo,
					v2.documento
				FROM cuenta_ctejf cc
				INNER JOIN ventajf v2
					ON v2.documento = cc.num_cta
					AND (
						(cc.tipo_doc = '01' AND v2.tipo = 'S03')
						OR (cc.tipo_doc = '03' AND v2.tipo = 'S02')
					)
				WHERE cc.tip_mov = '-'
					AND cc.fecha BETWEEN ? AND ?
					AND cc.vendedor LIKE '%08%'
					AND cc.cod_pago = '14'
					AND cc.tipo_doc IN ('01', '03')
			) culqi ON culqi.tipo = v.tipo AND culqi.documento = v.documento
			LEFT JOIN clientesjf c ON c.codigo = v.cliente
			SET v.cuenta = CASE
				WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
					OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
				THEN ?
				ELSE ?
			END
			WHERE ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"periodo" => $rango["periodo"],
				"mensaje" => "Se actualizaron {$actualizados} venta(s) Culqi ({$lima}/{$prov})"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar cuenta Culqi"
			);
		}
	}

	const CUENTA_NC_DEV_LIMA = "709411";
	const CUENTA_NC_DEV_PROV = "709412";

	/**
	 * Notas de crédito (E05) por devolución (motivos C1, C7) del periodo,
	 * sin cuenta o con cuenta distinta a la propuesta por ubigeo.
	 */
	static public function mdlVentasCuentaNcDev($periodo = null)
	{
		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return false;
		}

		$lima = self::CUENTA_NC_DEV_LIMA;
		$prov = self::CUENTA_NC_DEV_PROV;

		$sql = "
			SELECT
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(c.nombre, '') AS cliente_nombre,
				COALESCE(c.ubigeo, '') AS ubigeo,
				COALESCE(v.vendedor, '') AS vendedor,
				COALESCE(v.fecha, '') AS fecha,
				COALESCE(v.cuenta, '') AS cuenta,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN :cuenta_lima
					ELSE :cuenta_prov
				END AS cuenta_prop,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN 'Lima'
					ELSE 'Provincia'
				END AS zona,
				COALESCE(n.motivo, '') AS motivo,
				COALESCE(n.observacion, '') AS observacion,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM ventajf v
			LEFT JOIN clientesjf c ON v.cliente = c.codigo
			INNER JOIN notascd_jf n
				ON v.tipo = n.tipo
				AND v.documento = n.documento
			WHERE v.fecha BETWEEN :inicio AND :fin
				AND v.tipo = 'E05'
				AND n.motivo IN ('C1', 'C7')
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
					OR v.cuenta <> CASE
						WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
							OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
						THEN :cuenta_lima2
						ELSE :cuenta_prov2
					END
				)
			ORDER BY v.fecha ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":cuenta_lima", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_lima2", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov2", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array(
			"rango" => $rango,
			"filas" => $filas,
			"cuenta_lima" => $lima,
			"cuenta_prov" => $prov
		);
	}

	/**
	 * Asigna 709411/709412 a NC devolución (E05, C1/C7) seleccionadas.
	 * $items: [ ['tipo'=>..., 'documento'=>...], ... ]
	 */
	static public function mdlCompletarCuentaNcDevVentas($items, $periodo = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Periodo inválido");
		}

		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo !== "E05" || $documento === "") {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$lima = self::CUENTA_NC_DEV_LIMA;
		$prov = self::CUENTA_NC_DEV_PROV;
		$orParts = array();
		$params = array($lima, $prov, $rango["inicio"], $rango["fin"]);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		$sql = "
			UPDATE ventajf v
			INNER JOIN notascd_jf n
				ON v.tipo = n.tipo
				AND v.documento = n.documento
				AND n.motivo IN ('C1', 'C7')
			LEFT JOIN clientesjf c ON c.codigo = v.cliente
			SET v.cuenta = CASE
				WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
					OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
				THEN ?
				ELSE ?
			END
			WHERE v.tipo = 'E05'
				AND v.fecha BETWEEN ? AND ?
				AND ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"periodo" => $rango["periodo"],
				"mensaje" => "Se actualizaron {$actualizados} NC devolución ({$lima}/{$prov})"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar cuenta NC devolución"
			);
		}
	}

	const CUENTA_NC_DSCTO_LIMA = "741101";
	const CUENTA_NC_DSCTO_PROV = "741102";

	/**
	 * Notas de crédito (E05) por descuento (motivos distintos de C1/C7)
	 * del periodo, sin cuenta o con cuenta distinta a la propuesta.
	 */
	static public function mdlVentasCuentaNcDscto($periodo = null)
	{
		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return false;
		}

		$lima = self::CUENTA_NC_DSCTO_LIMA;
		$prov = self::CUENTA_NC_DSCTO_PROV;

		$sql = "
			SELECT
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(c.nombre, '') AS cliente_nombre,
				COALESCE(c.ubigeo, '') AS ubigeo,
				COALESCE(v.vendedor, '') AS vendedor,
				COALESCE(v.fecha, '') AS fecha,
				COALESCE(v.cuenta, '') AS cuenta,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN :cuenta_lima
					ELSE :cuenta_prov
				END AS cuenta_prop,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN 'Lima'
					ELSE 'Provincia'
				END AS zona,
				COALESCE(n.motivo, '') AS motivo,
				COALESCE(n.observacion, '') AS observacion,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM ventajf v
			LEFT JOIN clientesjf c ON v.cliente = c.codigo
			INNER JOIN notascd_jf n
				ON v.tipo = n.tipo
				AND v.documento = n.documento
			WHERE v.fecha BETWEEN :inicio AND :fin
				AND v.tipo = 'E05'
				AND n.motivo NOT IN ('C1', 'C7')
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
					OR v.cuenta <> CASE
						WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
							OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
						THEN :cuenta_lima2
						ELSE :cuenta_prov2
					END
				)
			ORDER BY v.fecha ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":cuenta_lima", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_lima2", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov2", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array(
			"rango" => $rango,
			"filas" => $filas,
			"cuenta_lima" => $lima,
			"cuenta_prov" => $prov
		);
	}

	/**
	 * Asigna 741101/741102 a NC descuento (E05, motivo ≠ C1/C7).
	 * $items: [ ['tipo'=>..., 'documento'=>...], ... ]
	 */
	static public function mdlCompletarCuentaNcDsctoVentas($items, $periodo = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Periodo inválido");
		}

		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo !== "E05" || $documento === "") {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$lima = self::CUENTA_NC_DSCTO_LIMA;
		$prov = self::CUENTA_NC_DSCTO_PROV;
		$orParts = array();
		$params = array($lima, $prov, $rango["inicio"], $rango["fin"]);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		$sql = "
			UPDATE ventajf v
			INNER JOIN notascd_jf n
				ON v.tipo = n.tipo
				AND v.documento = n.documento
				AND n.motivo NOT IN ('C1', 'C7')
			LEFT JOIN clientesjf c ON c.codigo = v.cliente
			SET v.cuenta = CASE
				WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
					OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
				THEN ?
				ELSE ?
			END
			WHERE v.tipo = 'E05'
				AND v.fecha BETWEEN ? AND ?
				AND ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"periodo" => $rango["periodo"],
				"mensaje" => "Se actualizaron {$actualizados} NC descuento ({$lima}/{$prov})"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar cuenta NC descuento"
			);
		}
	}

	const CUENTA_ND_FLETE_LIMA = "75995";
	const CUENTA_ND_FLETE_PROV = "75996";

	/**
	 * Notas de débito (S05) por flete showroom (vendedor %08%) del periodo.
	 */
	static public function mdlVentasCuentaNdFlete($periodo = null)
	{
		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return false;
		}

		$lima = self::CUENTA_ND_FLETE_LIMA;
		$prov = self::CUENTA_ND_FLETE_PROV;

		$sql = "
			SELECT
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(c.nombre, '') AS cliente_nombre,
				COALESCE(c.ubigeo, '') AS ubigeo,
				COALESCE(v.vendedor, '') AS vendedor,
				COALESCE(v.fecha, '') AS fecha,
				COALESCE(v.cuenta, '') AS cuenta,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN :cuenta_lima
					ELSE :cuenta_prov
				END AS cuenta_prop,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN 'Lima'
					ELSE 'Provincia'
				END AS zona,
				COALESCE(n.motivo, '') AS motivo,
				COALESCE(n.observacion, '') AS observacion,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM ventajf v
			LEFT JOIN clientesjf c ON v.cliente = c.codigo
			LEFT JOIN notascd_jf n
				ON v.tipo = n.tipo
				AND v.documento = n.documento
			WHERE v.fecha BETWEEN :inicio AND :fin
				AND v.tipo = 'S05'
				AND v.vendedor LIKE '%08%'
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
					OR v.cuenta <> CASE
						WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
							OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
						THEN :cuenta_lima2
						ELSE :cuenta_prov2
					END
				)
			ORDER BY v.fecha ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":cuenta_lima", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_lima2", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov2", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array(
			"rango" => $rango,
			"filas" => $filas,
			"cuenta_lima" => $lima,
			"cuenta_prov" => $prov
		);
	}

	/**
	 * Asigna 75995/75996 a ND flete (S05, vendedor %08%).
	 * $items: [ ['tipo'=>..., 'documento'=>...], ... ]
	 */
	static public function mdlCompletarCuentaNdFleteVentas($items, $periodo = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Periodo inválido");
		}

		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo !== "S05" || $documento === "") {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$lima = self::CUENTA_ND_FLETE_LIMA;
		$prov = self::CUENTA_ND_FLETE_PROV;
		$orParts = array();
		$params = array($lima, $prov, $rango["inicio"], $rango["fin"]);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		$sql = "
			UPDATE ventajf v
			LEFT JOIN clientesjf c ON c.codigo = v.cliente
			SET v.cuenta = CASE
				WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
					OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
				THEN ?
				ELSE ?
			END
			WHERE v.tipo = 'S05'
				AND v.vendedor LIKE '%08%'
				AND v.fecha BETWEEN ? AND ?
				AND ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"periodo" => $rango["periodo"],
				"mensaje" => "Se actualizaron {$actualizados} ND flete ({$lima}/{$prov})"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar cuenta ND flete"
			);
		}
	}

	const CUENTA_ND_PROTESTO_LIMA = "75991";
	const CUENTA_ND_PROTESTO_PROV = "75992";

	/**
	 * Notas de débito (S05) por protesto (vendedor sin 08) del periodo.
	 */
	static public function mdlVentasCuentaNdProtesto($periodo = null)
	{
		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return false;
		}

		$lima = self::CUENTA_ND_PROTESTO_LIMA;
		$prov = self::CUENTA_ND_PROTESTO_PROV;

		$sql = "
			SELECT
				COALESCE(v.tipo, '') AS tipo,
				COALESCE(v.documento, '') AS documento,
				COALESCE(v.cliente, '') AS cliente,
				COALESCE(c.nombre, '') AS cliente_nombre,
				COALESCE(c.ubigeo, '') AS ubigeo,
				COALESCE(v.vendedor, '') AS vendedor,
				COALESCE(v.fecha, '') AS fecha,
				COALESCE(v.cuenta, '') AS cuenta,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN :cuenta_lima
					ELSE :cuenta_prov
				END AS cuenta_prop,
				CASE
					WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
						OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
					THEN 'Lima'
					ELSE 'Provincia'
				END AS zona,
				COALESCE(n.motivo, '') AS motivo,
				COALESCE(n.observacion, '') AS observacion,
				ROUND(COALESCE(v.total, 0), 2) AS total
			FROM ventajf v
			LEFT JOIN clientesjf c ON v.cliente = c.codigo
			LEFT JOIN notascd_jf n
				ON v.tipo = n.tipo
				AND v.documento = n.documento
			WHERE v.fecha BETWEEN :inicio AND :fin
				AND v.tipo = 'S05'
				AND v.vendedor NOT LIKE '%08%'
				AND (
					v.cuenta IS NULL
					OR TRIM(v.cuenta) = ''
					OR v.cuenta <> CASE
						WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
							OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
						THEN :cuenta_lima2
						ELSE :cuenta_prov2
					END
				)
			ORDER BY v.fecha ASC, v.documento ASC
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":cuenta_lima", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_lima2", $lima, PDO::PARAM_STR);
		$stmt->bindValue(":cuenta_prov2", $prov, PDO::PARAM_STR);
		$stmt->bindValue(":inicio", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindValue(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array(
			"rango" => $rango,
			"filas" => $filas,
			"cuenta_lima" => $lima,
			"cuenta_prov" => $prov
		);
	}

	/**
	 * Asigna 75991/75992 a ND protesto (S05, vendedor sin 08).
	 * $items: [ ['tipo'=>..., 'documento'=>...], ... ]
	 */
	static public function mdlCompletarCuentaNdProtestoVentas($items, $periodo = null)
	{
		if (!is_array($items) || count($items) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros");
		}

		$rango = self::mdlPeriodoMes($periodo);
		if ($rango === false) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Periodo inválido");
		}

		$pares = array();
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$tipo = isset($item["tipo"]) ? trim((string) $item["tipo"]) : "";
			$documento = isset($item["documento"]) ? trim((string) $item["documento"]) : "";
			if ($tipo !== "S05" || $documento === "") {
				continue;
			}
			$key = $tipo . "|" . $documento;
			$pares[$key] = array("tipo" => $tipo, "documento" => $documento);
		}
		$pares = array_values($pares);
		if (count($pares) < 1) {
			return array("ok" => false, "actualizados" => 0, "mensaje" => "Sin registros válidos");
		}

		$lima = self::CUENTA_ND_PROTESTO_LIMA;
		$prov = self::CUENTA_ND_PROTESTO_PROV;
		$orParts = array();
		$params = array($lima, $prov, $rango["inicio"], $rango["fin"]);
		foreach ($pares as $p) {
			$orParts[] = "(v.tipo = ? AND v.documento = ?)";
			$params[] = $p["tipo"];
			$params[] = $p["documento"];
		}
		$orSql = implode(" OR ", $orParts);

		$sql = "
			UPDATE ventajf v
			LEFT JOIN clientesjf c ON c.codigo = v.cliente
			SET v.cuenta = CASE
				WHEN LEFT(COALESCE(c.ubigeo, ''), 2) = '15'
					OR LEFT(COALESCE(c.ubigeo, ''), 1) = 'L'
				THEN ?
				ELSE ?
			END
			WHERE v.tipo = 'S05'
				AND v.vendedor NOT LIKE '%08%'
				AND v.fecha BETWEEN ? AND ?
				AND ({$orSql})
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			foreach ($params as $i => $val) {
				$stmt->bindValue($i + 1, $val, PDO::PARAM_STR);
			}
			$stmt->execute();
			$actualizados = (int) $stmt->rowCount();
			return array(
				"ok" => true,
				"actualizados" => $actualizados,
				"periodo" => $rango["periodo"],
				"mensaje" => "Se actualizaron {$actualizados} ND protesto ({$lima}/{$prov})"
			);
		} catch (Exception $e) {
			return array(
				"ok" => false,
				"actualizados" => 0,
				"mensaje" => "Error al completar cuenta ND protesto"
			);
		}
	}

	/**
	 * Días del año en totalesjf sin cambio_venta (hasta hoy).
	 */
	static public function mdlTotalesSinTipCambio($anio)
	{
		$anio = (int) $anio;
		$sql = "
			SELECT
				DATE(t.fecha) AS fecha,
				t.ano,
				t.mes,
				t.dia,
				ROUND(COALESCE(t.cambio_compra, 0), 4) AS cambio_compra,
				ROUND(COALESCE(t.cambio_venta, 0), 4) AS cambio_venta
			FROM totalesjf t
			WHERE YEAR(t.fecha) = :anio
				AND DATE(t.fecha) <= CURDATE()
				AND (
					t.cambio_venta IS NULL
					OR t.cambio_venta = 0
				)
			ORDER BY t.fecha ASC
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Último TC registrado en totalesjf antes de una fecha.
	 */
	static public function mdlUltimoTipCambioTotalesAntes($fecha)
	{
		$sql = "
			SELECT
				DATE(fecha) AS fecha,
				ROUND(cambio_compra, 4) AS cambio_compra,
				ROUND(cambio_venta, 4) AS cambio_venta
			FROM totalesjf
			WHERE DATE(fecha) < :fecha
				AND cambio_venta IS NOT NULL
				AND cambio_venta <> 0
			ORDER BY fecha DESC
			LIMIT 1
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			return $row ? $row : null;
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * Actualiza cambio_compra / cambio_venta de un día en totalesjf.
	 */
	static public function mdlActualizarTipCambioTotales($fecha, $compra, $venta)
	{
		$sql = "
			UPDATE totalesjf
			SET
				cambio_compra = :compra,
				cambio_venta = :venta
			WHERE DATE(fecha) = :fecha
		";

		try {
			$stmt = Conexion::conectar()->prepare($sql);
			$stmt->bindParam(":compra", $compra, PDO::PARAM_STR);
			$stmt->bindParam(":venta", $venta, PDO::PARAM_STR);
			$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
			if (!$stmt->execute()) {
				return array("ok" => false, "mensaje" => "No se pudo actualizar totales");
			}
			return array("ok" => true, "actualizados" => (int) $stmt->rowCount());
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "Error al actualizar totales");
		}
	}
}
