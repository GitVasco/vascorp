<?php

require_once "conexion.php";

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

	static public function mdlGuardarReto($datos)
	{
		$existente = self::mdlObtenerReto($datos["cod_vendedor"], $datos["anio"], $datos["mes"]);

		if ($existente) {
			$stmt = Conexion::conectar()->prepare(
				"UPDATE metas_retos_vendedorjf SET
					meta_monto = :meta_monto,
					comision_monto_pct = :comision_monto_pct,
					comision_monto_fijo = :comision_monto_fijo,
					cumplimiento_monto = :cumplimiento_monto,
					meta_clientes = :meta_clientes,
					comision_clientes_fijo = :comision_clientes_fijo,
					cumplimiento_clientes = :cumplimiento_clientes,
					meta_modelos = :meta_modelos,
					comision_modelos_fijo = :comision_modelos_fijo,
					cumplimiento_modelos = :cumplimiento_modelos,
					usuario = :usuario,
					fecmod = NOW()
				 WHERE id = :id"
			);
			$stmt->bindValue(":id", (int) $existente["id"], PDO::PARAM_INT);
		} else {
			$stmt = Conexion::conectar()->prepare(
				"INSERT INTO metas_retos_vendedorjf (
					cod_vendedor, anio, mes,
					meta_monto, comision_monto_pct, comision_monto_fijo, cumplimiento_monto,
					meta_clientes, comision_clientes_fijo, cumplimiento_clientes,
					meta_modelos, comision_modelos_fijo, cumplimiento_modelos,
					usuario, fecreg
				) VALUES (
					:cod_vendedor, :anio, :mes,
					:meta_monto, :comision_monto_pct, :comision_monto_fijo, :cumplimiento_monto,
					:meta_clientes, :comision_clientes_fijo, :cumplimiento_clientes,
					:meta_modelos, :comision_modelos_fijo, :cumplimiento_modelos,
					:usuario, NOW()
				)"
			);
			$stmt->bindValue(":cod_vendedor", $datos["cod_vendedor"], PDO::PARAM_STR);
			$stmt->bindValue(":anio", (int) $datos["anio"], PDO::PARAM_INT);
			$stmt->bindValue(":mes", (int) $datos["mes"], PDO::PARAM_INT);
		}

		self::bindNullableDecimal($stmt, ":meta_monto", $datos["meta_monto"]);
		self::bindNullableDecimal($stmt, ":comision_monto_pct", $datos["comision_monto_pct"]);
		self::bindNullableDecimal($stmt, ":comision_monto_fijo", $datos["comision_monto_fijo"]);
		$stmt->bindValue(":cumplimiento_monto", $datos["cumplimiento_monto"], PDO::PARAM_STR);

		self::bindNullableInt($stmt, ":meta_clientes", $datos["meta_clientes"]);
		self::bindNullableDecimal($stmt, ":comision_clientes_fijo", $datos["comision_clientes_fijo"]);
		$stmt->bindValue(":cumplimiento_clientes", $datos["cumplimiento_clientes"], PDO::PARAM_STR);

		self::bindNullableInt($stmt, ":meta_modelos", $datos["meta_modelos"]);
		self::bindNullableDecimal($stmt, ":comision_modelos_fijo", $datos["comision_modelos_fijo"]);
		$stmt->bindValue(":cumplimiento_modelos", $datos["cumplimiento_modelos"], PDO::PARAM_STR);

		$stmt->bindValue(":usuario", (int) $datos["usuario"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
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

	static public function mdlVentaRealPorVendedor($anio, $mes)
	{
		$rango = self::rangoMes($anio, $mes);
		$sql = "SELECT TRIM(v.vendedor) AS cod_vendedor, SUM(v.neto) AS venta_real
			FROM ventajf v
			WHERE v.fecha >= :ini AND v.fecha < :fin
			  AND " . self::sqlTiposVentaReal("v") . "
			  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			GROUP BY TRIM(v.vendedor)";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[trim($fila["cod_vendedor"])] = (float) $fila["venta_real"];
		}
		return $mapa;
	}

	/**
	 * Clientes con primera venta en el período, del vendedor, sin grupo empresarial.
	 */
	static public function mdlClientesNuevosPorVendedor($anio, $mes)
	{
		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");
		$tipos0 = self::sqlTiposVentaReal("v0");

		$sql = "SELECT TRIM(v0.vendedor) AS cod_vendedor, COUNT(DISTINCT p.cliente) AS clientes_nuevos
			FROM (
				SELECT v.cliente, MIN(v.fecha) AS primera
				FROM ventajf v
				WHERE v.fecha IS NOT NULL
				  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
				  AND {$tipos}
				GROUP BY v.cliente
			) p
			INNER JOIN ventajf v0
				ON v0.cliente = p.cliente
			   AND v0.fecha = p.primera
			   AND UPPER(IFNULL(v0.estado, '')) <> 'ANULADO'
			   AND {$tipos0}
			INNER JOIN clientesjf c ON c.codigo = p.cliente
			WHERE p.primera >= :ini AND p.primera < :fin
			  AND (c.grupo IS NULL OR TRIM(c.grupo) = '')
			GROUP BY TRIM(v0.vendedor)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[trim($fila["cod_vendedor"])] = (int) $fila["clientes_nuevos"];
		}
		return $mapa;
	}

	/** Modelos distintos vendidos en el mes (movimientosjf_AAAA + articulojf.modelo). */
	static public function mdlModelosActivosPorVendedor($anio, $mes)
	{
		$rango = self::rangoMes($anio, $mes);
		$tabla = self::tablaMovimientos($anio);

		try {
			$pdo = Conexion::conectar();
			$check = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
			if (!$check || !$check->fetch()) {
				return array();
			}
		} catch (Exception $e) {
			return array();
		}

		$sql = "SELECT TRIM(m.vendedor) AS cod_vendedor, COUNT(DISTINCT a.modelo) AS modelos_activos
			FROM {$tabla} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			WHERE m.fecha >= :ini AND m.fecha < :fin
			  AND " . self::sqlTiposVentaReal("m") . "
			  AND TRIM(IFNULL(a.modelo, '')) <> ''
			  AND TRIM(IFNULL(m.vendedor, '')) <> ''
			GROUP BY TRIM(m.vendedor)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();
		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[trim($fila["cod_vendedor"])] = (int) $fila["modelos_activos"];
		}
		return $mapa;
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
		$clientes = self::mdlClientesNuevosPorVendedor($anio, $mes);
		$modelos = self::mdlModelosActivosPorVendedor($anio, $mes);

		$lista = array();
		foreach ($vendedores as $vend) {
			$cod = trim($vend["codigo"]);
			$reto = isset($retos[$cod]) ? $retos[$cod] : null;
			$lista[] = array(
				"cod_vendedor" => $cod,
				"nombre_vendedor" => $vend["descripcion"],
				"reto" => $reto,
				"venta_real" => isset($ventas[$cod]) ? $ventas[$cod] : 0,
				"clientes_nuevos" => isset($clientes[$cod]) ? $clientes[$cod] : 0,
				"modelos_activos" => isset($modelos[$cod]) ? $modelos[$cod] : 0
			);
		}
		return $lista;
	}
}
