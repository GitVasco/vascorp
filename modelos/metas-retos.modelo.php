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
					meta_modelos_modo = :meta_modelos_modo,
					meta_modelos_pct = :meta_modelos_pct,
					comision_modelos_fijo = :comision_modelos_fijo,
					cumplimiento_modelos = :cumplimiento_modelos,
					modelo_especial = :modelo_especial,
					meta_docenas_especial = :meta_docenas_especial,
					comision_modelo_esp_pct = :comision_modelo_esp_pct,
					cumplimiento_modelo_esp = :cumplimiento_modelo_esp,
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
					meta_modelos, meta_modelos_modo, meta_modelos_pct, comision_modelos_fijo, cumplimiento_modelos,
					modelo_especial, meta_docenas_especial, comision_modelo_esp_pct, cumplimiento_modelo_esp,
					usuario, fecreg
				) VALUES (
					:cod_vendedor, :anio, :mes,
					:meta_monto, :comision_monto_pct, :comision_monto_fijo, :cumplimiento_monto,
					:meta_clientes, :comision_clientes_fijo, :cumplimiento_clientes,
					:meta_modelos, :meta_modelos_modo, :meta_modelos_pct, :comision_modelos_fijo, :cumplimiento_modelos,
					:modelo_especial, :meta_docenas_especial, :comision_modelo_esp_pct, :cumplimiento_modelo_esp,
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
		$modoMod = isset($datos["meta_modelos_modo"]) && $datos["meta_modelos_modo"] === "porcentaje"
			? "porcentaje" : "cantidad";
		$stmt->bindValue(":meta_modelos_modo", $modoMod, PDO::PARAM_STR);
		self::bindNullableDecimal($stmt, ":meta_modelos_pct", isset($datos["meta_modelos_pct"]) ? $datos["meta_modelos_pct"] : null);
		self::bindNullableDecimal($stmt, ":comision_modelos_fijo", $datos["comision_modelos_fijo"]);
		$stmt->bindValue(":cumplimiento_modelos", $datos["cumplimiento_modelos"], PDO::PARAM_STR);

		$modeloEsp = isset($datos["modelo_especial"]) ? trim((string) $datos["modelo_especial"]) : "";
		if ($modeloEsp === "") {
			$stmt->bindValue(":modelo_especial", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":modelo_especial", $modeloEsp, PDO::PARAM_STR);
		}
		self::bindNullableDecimal($stmt, ":meta_docenas_especial", $datos["meta_docenas_especial"]);
		self::bindNullableDecimal($stmt, ":comision_modelo_esp_pct", $datos["comision_modelo_esp_pct"]);
		$stmt->bindValue(":cumplimiento_modelo_esp", $datos["cumplimiento_modelo_esp"], PDO::PARAM_STR);

		$stmt->bindValue(":usuario", (int) $datos["usuario"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	/** Solo modelos activos del catálogo (modelojf). Sin tope artificial. */
	static public function mdlListarModelosCatalogo($q = "")
	{
		$q = trim((string) $q);

		$sql = "SELECT m.modelo,
				IFNULL(m.nombre, m.modelo) AS nombre,
				IFNULL(m.articulos, 0) AS articulos
			FROM modelojf m
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
		require_once "metricas-comerciales.modelo.php";
		return ModeloMetricasComerciales::mdlVentaPermitidaPorVendedor($anio, $mes, false);
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
		$clientes = self::mdlClientesNuevosPorVendedor($anio, $mes);
		$modelos = self::mdlModelosActivosPorVendedor($anio, $mes);
		$porModelo = self::mdlVentaModeloPorVendedor($anio, $mes);

		require_once "grupos-marcas-comercial.modelo.php";
		$fechaRef = sprintf("%04d-%02d-01", (int) $anio, (int) $mes);

		$lista = array();
		foreach ($vendedores as $vend) {
			$cod = trim($vend["codigo"]);
			$reto = isset($retos[$cod]) ? $retos[$cod] : null;
			$docenasEsp = 0.0;
			$ventaEsp = 0.0;
			$modeloEsp = ($reto && !empty($reto["modelo_especial"])) ? trim($reto["modelo_especial"]) : "";
			if ($modeloEsp !== "" && isset($porModelo[$cod][$modeloEsp])) {
				$docenasEsp = $porModelo[$cod][$modeloEsp]["docenas"];
				$ventaEsp = $porModelo[$cod][$modeloEsp]["venta"];
			}
			$lista[] = array(
				"cod_vendedor" => $cod,
				"nombre_vendedor" => $vend["descripcion"],
				"reto" => $reto,
				"venta_real" => isset($ventas[$cod]) ? $ventas[$cod] : 0,
				"clientes_nuevos" => isset($clientes[$cod]) ? $clientes[$cod] : 0,
				"modelos_activos" => isset($modelos[$cod]) ? $modelos[$cod] : 0,
				"universo_modelos" => ModeloGruposMarcasComercial::mdlUniversoModelosActivosPorVendedor($cod, $fechaRef),
				"modelo_especial" => $modeloEsp,
				"docenas_especial" => $docenasEsp,
				"venta_modelo_especial" => $ventaEsp
			);
		}
		return $lista;
	}
}
