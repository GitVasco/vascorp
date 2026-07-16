<?php

require_once "conexion.php";
require_once "grupos-marcas-comercial.modelo.php";

/**
 * Métricas comerciales por cobertura de marcas.
 *
 * Regla Fase 3 (venta KPI):
 * - Líneas en movimientosjf (S02/S03/S70/S05/E05 con unidades) clasificadas por marca vigente.
 * - E05 con líneas/unidades = devolución → resta/suma por marca (permitida o fuera).
 * - E05 sin líneas/unidades = descuento → resta al vendedor sin atribuir marca (nc_descuento).
 * - venta_kpi = permitida_lineas + nc_descuento.
 */
class ModeloMetricasComerciales
{

	private static function tiposVentaReal()
	{
		return array("S02", "S03", "S70", "E05", "S05");
	}

	private static function sqlTiposVentaReal($alias = "m")
	{
		$tipos = array();
		foreach (self::tiposVentaReal() as $tipo) {
			$tipos[] = "'" . $tipo . "'";
		}
		return "{$alias}.tipo IN (" . implode(", ", $tipos) . ")";
	}

	private static function sqlJoinVendedorActivo($aliasMov = "m", $aliasMaestra = "ma")
	{
		return "INNER JOIN maestrajf {$aliasMaestra}
				ON {$aliasMaestra}.codigo = TRIM({$aliasMov}.vendedor)
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
		return "movimientosjf_" . (int) $anio;
	}

	private static function existeTablaMovimientos($anio)
	{
		$tabla = self::tablaMovimientos($anio);
		try {
			$pdo = Conexion::conectar();
			$check = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
			return $check && (bool) $check->fetch();
		} catch (Exception $e) {
			return false;
		}
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

	/** Clasificación SQL de una línea (alias m, articulo a). */
	private static function sqlClasificacionCoberturaLinea($aliasMov = "m", $aliasArt = "a")
	{
		$vigencia = ModeloGruposMarcasComercial::sqlAsignacionVigenteEnFecha("vgm", "{$aliasMov}.fecha");
		return "CASE
			WHEN {$aliasArt}.id_marca IS NULL OR CAST({$aliasArt}.id_marca AS UNSIGNED) = 0
				OR TRIM(IFNULL({$aliasArt}.modelo, '')) = ''
			THEN 'sin_marca'
			WHEN EXISTS (
				SELECT 1
				FROM vendedor_grupos_marcasjf vgm
				INNER JOIN grupos_marcas_comercialjf g
					ON g.id = vgm.id_grupo_marca AND g.estado = 1
				INNER JOIN grupos_marcas_detallejf gd
					ON gd.id_grupo_marca = g.id
				WHERE TRIM(vgm.cod_vendedor) = TRIM({$aliasMov}.vendedor)
				  AND gd.id_marca = {$aliasArt}.id_marca
				  AND {$vigencia}
			) THEN 'permitida'
			ELSE 'fuera_cobertura'
		END";
	}

	/** SQL: línea con cobertura permitida (para filtros EXISTS / HAVING). */
	static public function sqlLineaMarcaPermitida($aliasMov = "m", $aliasArt = "a")
	{
		return "(" . self::sqlClasificacionCoberturaLinea($aliasMov, $aliasArt) . ") = 'permitida'";
	}

	/**
	 * Cobertura por líneas de movimientos (incluye E05 devolución con unidades).
	 * [cod => permitida, fuera_cobertura, sin_marca, total_lineas, ...]
	 */
	static public function mdlVentasCoberturaPorVendedor($anio, $mes)
	{
		if (!self::existeTablaMovimientos($anio)) {
			return array();
		}

		$rango = self::rangoMes($anio, $mes);
		$tabla = self::tablaMovimientos($anio);
		$clasif = self::sqlClasificacionCoberturaLinea("m", "a");
		$joinActivo = self::sqlJoinVendedorActivo("m", "ma");

		$sql = "SELECT TRIM(m.vendedor) AS cod_vendedor,
				{$clasif} AS cobertura,
				SUM(IFNULL(m.total, 0)) AS importe,
				COUNT(*) AS lineas
			FROM {$tabla} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			{$joinActivo}
			WHERE m.fecha >= :ini AND m.fecha < :fin
			  AND " . self::sqlTiposVentaReal("m") . "
			  AND TRIM(IFNULL(m.vendedor, '')) <> ''
			GROUP BY TRIM(m.vendedor), cobertura";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$cod = trim($fila["cod_vendedor"]);
			$tipo = trim($fila["cobertura"]);
			if (!isset($mapa[$cod])) {
				$mapa[$cod] = array(
					"permitida" => 0.0,
					"fuera_cobertura" => 0.0,
					"sin_marca" => 0.0,
					"total_lineas" => 0.0,
					"lineas_permitida" => 0,
					"lineas_fuera" => 0,
					"lineas_sin_marca" => 0
				);
			}
			$importe = (float) $fila["importe"];
			$lineas = (int) $fila["lineas"];
			if (!isset($mapa[$cod][$tipo])) {
				$mapa[$cod][$tipo] = 0.0;
			}
			$mapa[$cod][$tipo] = $importe;
			$mapa[$cod]["total_lineas"] += $importe;
			if ($tipo === "permitida") {
				$mapa[$cod]["lineas_permitida"] = $lineas;
			} elseif ($tipo === "fuera_cobertura") {
				$mapa[$cod]["lineas_fuera"] = $lineas;
			} elseif ($tipo === "sin_marca") {
				$mapa[$cod]["lineas_sin_marca"] = $lineas;
			}
		}
		return $mapa;
	}

	/**
	 * E05 descuento: cabecera sin líneas con cantidad ≠ 0 en movimientos.
	 * [cod_vendedor => neto] (valores negativos).
	 */
	static public function mdlNcDescuentoPorVendedor($anio, $mes)
	{
		$rango = self::rangoMes($anio, $mes);
		$joinActivo = self::sqlJoinVendedorActivo("v", "ma");
		$tablaExiste = self::existeTablaMovimientos($anio);
		$tabla = self::tablaMovimientos($anio);

		if ($tablaExiste) {
			$sinUnidades = "NOT EXISTS (
				SELECT 1 FROM {$tabla} m
				WHERE m.tipo = v.tipo
				  AND m.documento = v.documento
				  AND m.fecha = v.fecha
				  AND IFNULL(m.cantidad, 0) <> 0
			)";
		} else {
			// Sin tabla de movimientos: toda E05 se trata como descuento sin marca.
			$sinUnidades = "1=1";
		}

		$sql = "SELECT TRIM(v.vendedor) AS cod_vendedor, SUM(IFNULL(v.neto, 0)) AS nc_descuento
			FROM ventajf v
			{$joinActivo}
			WHERE v.fecha >= :ini AND v.fecha < :fin
			  AND v.tipo = 'E05'
			  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			  AND TRIM(IFNULL(v.vendedor, '')) <> ''
			  AND {$sinUnidades}
			GROUP BY TRIM(v.vendedor)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[trim($fila["cod_vendedor"])] = (float) $fila["nc_descuento"];
		}
		return $mapa;
	}

	/**
	 * KPI oficial Fase 3 por vendedor: líneas permitidas + NC descuento.
	 * Retorna mapa simple [cod => float] o detalle si $detalle=true.
	 */
	static public function mdlVentaPermitidaPorVendedor($anio, $mes, $detalle = false)
	{
		$cobertura = self::mdlVentasCoberturaPorVendedor($anio, $mes);
		$ncDesc = self::mdlNcDescuentoPorVendedor($anio, $mes);
		$codigos = array_unique(array_merge(array_keys($cobertura), array_keys($ncDesc)));
		$mapa = array();

		foreach ($codigos as $cod) {
			$permitida = isset($cobertura[$cod]["permitida"]) ? (float) $cobertura[$cod]["permitida"] : 0.0;
			$fuera = isset($cobertura[$cod]["fuera_cobertura"]) ? (float) $cobertura[$cod]["fuera_cobertura"] : 0.0;
			$sin = isset($cobertura[$cod]["sin_marca"]) ? (float) $cobertura[$cod]["sin_marca"] : 0.0;
			$nc = isset($ncDesc[$cod]) ? (float) $ncDesc[$cod] : 0.0;
			$kpi = $permitida + $nc;

			if ($detalle) {
				$mapa[$cod] = array(
					"venta_permitida" => round($kpi, 2),
					"permitida_lineas" => round($permitida, 2),
					"nc_descuento" => round($nc, 2),
					"fuera_cobertura" => round($fuera, 2),
					"sin_marca" => round($sin, 2)
				);
			} else {
				$mapa[$cod] = round($kpi, 2);
			}
		}
		return $mapa;
	}

	static public function mdlVentasCoberturaPorMarca($anio, $mes, $codVendedor = "")
	{
		if (!self::existeTablaMovimientos($anio)) {
			return array();
		}

		$rango = self::rangoMes($anio, $mes);
		$tabla = self::tablaMovimientos($anio);
		$clasif = self::sqlClasificacionCoberturaLinea("m", "a");
		$joinActivo = self::sqlJoinVendedorActivo("m", "ma");
		$filtroVend = "";
		if (trim((string) $codVendedor) !== "") {
			$filtroVend = " AND TRIM(m.vendedor) = :cod_vendedor";
		}

		$sql = "SELECT TRIM(m.vendedor) AS cod_vendedor,
				IFNULL(a.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '(sin marca)') AS nombre_marca,
				{$clasif} AS cobertura,
				SUM(IFNULL(m.total, 0)) AS importe
			FROM {$tabla} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			LEFT JOIN marcasjf mk ON mk.id = a.id_marca
			{$joinActivo}
			WHERE m.fecha >= :ini AND m.fecha < :fin
			  AND " . self::sqlTiposVentaReal("m") . "
			  AND TRIM(IFNULL(m.vendedor, '')) <> ''
			  {$filtroVend}
			GROUP BY TRIM(m.vendedor), IFNULL(a.id_marca, 0), IFNULL(mk.marca, '(sin marca)'), cobertura
			ORDER BY TRIM(m.vendedor), nombre_marca";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		if ($filtroVend !== "") {
			$codVendedor = trim((string) $codVendedor);
			$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
		}
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$cod = trim($fila["cod_vendedor"]);
			$idMarca = (int) $fila["id_marca"];
			$key = $idMarca . "|" . $fila["nombre_marca"];
			if (!isset($mapa[$cod])) {
				$mapa[$cod] = array();
			}
			if (!isset($mapa[$cod][$key])) {
				$mapa[$cod][$key] = array(
					"id_marca" => $idMarca,
					"marca" => $fila["nombre_marca"],
					"permitida" => 0.0,
					"fuera_cobertura" => 0.0,
					"sin_marca" => 0.0
				);
			}
			$tipo = trim($fila["cobertura"]);
			$mapa[$cod][$key][$tipo] += (float) $fila["importe"];
		}

		foreach ($mapa as $cod => $marcas) {
			$mapa[$cod] = array_values($marcas);
		}
		return $mapa;
	}

	static public function mdlVentaCabeceraPorVendedor($anio, $mes)
	{
		$rango = self::rangoMes($anio, $mes);
		$joinActivo = self::sqlJoinVendedorActivo("v", "ma");
		$sql = "SELECT TRIM(v.vendedor) AS cod_vendedor, SUM(IFNULL(v.neto, 0)) AS venta_cabecera
			FROM ventajf v
			{$joinActivo}
			WHERE v.fecha >= :ini AND v.fecha < :fin
			  AND " . self::sqlTiposVentaReal("v") . "
			  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			  AND TRIM(IFNULL(v.vendedor, '')) <> ''
			GROUP BY TRIM(v.vendedor)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[trim($fila["cod_vendedor"])] = (float) $fila["venta_cabecera"];
		}
		return $mapa;
	}

	/** Clientes nuevos sin filtro de marca (cabecera, para conciliación). */
	static public function mdlClientesNuevosCabeceraPorVendedor($anio, $mes)
	{
		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");
		$tipos0 = self::sqlTiposVentaReal("v0");
		$joinPrimera = self::sqlJoinVendedorActivo("v", "ma");
		$joinV0 = self::sqlJoinVendedorActivo("v0", "m0");

		$sql = "SELECT TRIM(v0.vendedor) AS cod_vendedor, COUNT(DISTINCT p.cliente) AS clientes_nuevos
			FROM (
				SELECT v.cliente, MIN(v.fecha) AS primera
				FROM ventajf v
				{$joinPrimera}
				WHERE v.fecha IS NOT NULL
				  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
				  AND TRIM(IFNULL(v.vendedor, '')) <> ''
				  AND {$tipos}
				GROUP BY v.cliente
			) p
			INNER JOIN ventajf v0
				ON v0.cliente = p.cliente
			   AND v0.fecha = p.primera
			   AND UPPER(IFNULL(v0.estado, '')) <> 'ANULADO'
			   AND {$tipos0}
			   AND TRIM(IFNULL(v0.vendedor, '')) <> ''
			{$joinV0}
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

	/**
	 * Clientes nuevos cuya 1ª compra del periodo es documento 100% permitido.
	 * E05 descuento (sin líneas) no califica como primera compra de cliente nuevo comercial.
	 */
	static public function mdlClientesNuevosPermitidosPorVendedor($anio, $mes)
	{
		if (!self::existeTablaMovimientos($anio)) {
			return array();
		}

		$rango = self::rangoMes($anio, $mes);
		$tabla = self::tablaMovimientos($anio);
		$clasif = self::sqlClasificacionCoberturaLinea("m", "a");
		$tipos = self::sqlTiposVentaReal("v");
		$tipos0 = self::sqlTiposVentaReal("v0");
		$joinPrimera = self::sqlJoinVendedorActivo("v", "ma");
		$joinV0 = self::sqlJoinVendedorActivo("v0", "m0");

		$sql = "SELECT TRIM(prim.vendedor) AS cod_vendedor,
				COUNT(DISTINCT prim.cliente) AS clientes_nuevos_permitidos
			FROM (
				SELECT p.cliente, TRIM(v0.vendedor) AS vendedor, v0.tipo, v0.documento, v0.fecha
				FROM (
					SELECT v.cliente, MIN(v.fecha) AS primera
					FROM ventajf v
					{$joinPrimera}
					WHERE v.fecha IS NOT NULL
					  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
					  AND TRIM(IFNULL(v.vendedor, '')) <> ''
					  AND {$tipos}
					GROUP BY v.cliente
				) p
				INNER JOIN ventajf v0
					ON v0.cliente = p.cliente
				   AND v0.fecha = p.primera
				   AND UPPER(IFNULL(v0.estado, '')) <> 'ANULADO'
				   AND {$tipos0}
				   AND TRIM(IFNULL(v0.vendedor, '')) <> ''
				{$joinV0}
				INNER JOIN clientesjf c ON c.codigo = p.cliente
				WHERE p.primera >= :ini AND p.primera < :fin
				  AND (c.grupo IS NULL OR TRIM(c.grupo) = '')
			) prim
			INNER JOIN (
				SELECT m.tipo, m.documento, m.fecha, TRIM(m.vendedor) AS vendedor,
					SUM(CASE WHEN ({$clasif}) = 'permitida' THEN IFNULL(m.total, 0) ELSE 0 END) AS imp_permitida,
					SUM(CASE WHEN ({$clasif}) <> 'permitida' THEN IFNULL(m.total, 0) ELSE 0 END) AS imp_no_permitida
				FROM {$tabla} m
				INNER JOIN articulojf a ON a.articulo = m.articulo
				WHERE m.fecha >= :ini2 AND m.fecha < :fin2
				  AND " . self::sqlTiposVentaReal("m") . "
				GROUP BY m.tipo, m.documento, m.fecha, TRIM(m.vendedor)
			) doc ON doc.tipo = prim.tipo
			   AND doc.documento = prim.documento
			   AND doc.fecha = prim.fecha
			   AND doc.vendedor = prim.vendedor
			WHERE doc.imp_permitida > 0 AND doc.imp_no_permitida = 0
			GROUP BY TRIM(prim.vendedor)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->bindParam(":ini2", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin2", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[trim($fila["cod_vendedor"])] = (int) $fila["clientes_nuevos_permitidos"];
		}
		return $mapa;
	}

	/** Modelos distintos con al menos una línea permitida en el mes. */
	static public function mdlModelosPermitidosPorVendedor($anio, $mes)
	{
		if (!self::existeTablaMovimientos($anio)) {
			return array();
		}

		$rango = self::rangoMes($anio, $mes);
		$tabla = self::tablaMovimientos($anio);
		$joinActivo = self::sqlJoinVendedorActivo("m", "ma");
		$permitida = self::sqlLineaMarcaPermitida("m", "a");

		$sql = "SELECT TRIM(m.vendedor) AS cod_vendedor, COUNT(DISTINCT a.modelo) AS modelos_activos
			FROM {$tabla} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			{$joinActivo}
			WHERE m.fecha >= :ini AND m.fecha < :fin
			  AND " . self::sqlTiposVentaReal("m") . "
			  AND TRIM(IFNULL(a.modelo, '')) <> ''
			  AND TRIM(IFNULL(m.vendedor, '')) <> ''
			  AND {$permitida}
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

	/**
	 * Docenas/venta por modelo solo líneas permitidas.
	 * [cod => [modelo => [docenas, venta]]]
	 */
	static public function mdlVentaModeloPermitidaPorVendedor($anio, $mes)
	{
		if (!self::existeTablaMovimientos($anio)) {
			return array();
		}

		$rango = self::rangoMes($anio, $mes);
		$tabla = self::tablaMovimientos($anio);
		$joinActivo = self::sqlJoinVendedorActivo("m", "ma");
		$permitida = self::sqlLineaMarcaPermitida("m", "a");

		$sql = "SELECT TRIM(m.vendedor) AS cod_vendedor,
				TRIM(a.modelo) AS modelo,
				SUM(m.cantidad) / 12 AS docenas,
				SUM(IFNULL(m.total, 0)) AS venta_modelo
			FROM {$tabla} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			{$joinActivo}
			WHERE m.fecha >= :ini AND m.fecha < :fin
			  AND " . self::sqlTiposVentaReal("m") . "
			  AND TRIM(IFNULL(a.modelo, '')) <> ''
			  AND TRIM(IFNULL(m.vendedor, '')) <> ''
			  AND {$permitida}
			GROUP BY TRIM(m.vendedor), TRIM(a.modelo)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$cod = trim($fila["cod_vendedor"]);
			$mod = trim($fila["modelo"]);
			if (!isset($mapa[$cod])) {
				$mapa[$cod] = array();
			}
			$mapa[$cod][$mod] = array(
				"docenas" => round((float) $fila["docenas"], 2),
				"venta" => round((float) $fila["venta_modelo"], 2)
			);
		}
		return $mapa;
	}

	/**
	 * Agregados permitidos por vendedor+modelo+color+artículo (una consulta).
	 * [cod => [ {modelo, cod_color, articulo, unidades, docenas, venta}, ... ]]
	 */
	static public function mdlAgregadoProductoPermitidoPorVendedor($anio, $mes)
	{
		if (!self::existeTablaMovimientos($anio)) {
			return array();
		}

		$rango = self::rangoMes($anio, $mes);
		$tabla = self::tablaMovimientos($anio);
		$joinActivo = self::sqlJoinVendedorActivo("m", "ma");
		$permitida = self::sqlLineaMarcaPermitida("m", "a");

		$sql = "SELECT TRIM(m.vendedor) AS cod_vendedor,
				TRIM(IFNULL(a.modelo, '')) AS modelo,
				TRIM(IFNULL(a.cod_color, '')) AS cod_color,
				TRIM(IFNULL(a.articulo, '')) AS articulo,
				SUM(IFNULL(m.cantidad, 0)) AS unidades,
				SUM(IFNULL(m.cantidad, 0)) / 12 AS docenas,
				SUM(IFNULL(m.total, 0)) AS venta
			FROM {$tabla} m
			INNER JOIN articulojf a ON a.articulo = m.articulo
			{$joinActivo}
			WHERE m.fecha >= :ini AND m.fecha < :fin
			  AND " . self::sqlTiposVentaReal("m") . "
			  AND TRIM(IFNULL(m.vendedor, '')) <> ''
			  AND {$permitida}
			GROUP BY TRIM(m.vendedor),
				TRIM(IFNULL(a.modelo, '')),
				TRIM(IFNULL(a.cod_color, '')),
				TRIM(IFNULL(a.articulo, ''))";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$cod = trim($fila["cod_vendedor"]);
			if (!isset($mapa[$cod])) {
				$mapa[$cod] = array();
			}
			$mapa[$cod][] = array(
				"modelo" => trim($fila["modelo"]),
				"cod_color" => trim($fila["cod_color"]),
				"articulo" => trim($fila["articulo"]),
				"unidades" => (float) $fila["unidades"],
				"docenas" => (float) $fila["docenas"],
				"venta" => (float) $fila["venta"]
			);
		}
		return $mapa;
	}

	/**
	 * Cruza incentivos con agregados del periodo (sin 1 consulta por incentivo).
	 * Cada incentivo recibe avance_meta, venta_objetivo, unidades, docenas.
	 *
	 * @param array $incentivosPorVendedor [cod => [incentivo, ...]]
	 * @return array misma estructura con métricas enriquecidas
	 */
	static public function mdlAvanceIncentivosProductoPorVendedorPeriodo($anio, $mes, $incentivosPorVendedor)
	{
		if (!is_array($incentivosPorVendedor) || empty($incentivosPorVendedor)) {
			return array();
		}

		$agregados = self::mdlAgregadoProductoPermitidoPorVendedor($anio, $mes);
		$salida = array();

		foreach ($incentivosPorVendedor as $cod => $lista) {
			$cod = trim((string) $cod);
			$buckets = isset($agregados[$cod]) ? $agregados[$cod] : array();
			$salida[$cod] = array();

			foreach ((array) $lista as $inc) {
				$tipo = isset($inc["tipo_objetivo"]) ? trim((string) $inc["tipo_objetivo"]) : "";
				$modelo = isset($inc["modelo"]) ? trim((string) $inc["modelo"]) : "";
				$color = isset($inc["cod_color"]) ? trim((string) $inc["cod_color"]) : "";
				$articulo = isset($inc["articulo"]) ? trim((string) $inc["articulo"]) : "";
				$unidad = (isset($inc["unidad_meta"]) && $inc["unidad_meta"] === "unidades")
					? "unidades" : "docenas";

				$unidades = 0.0;
				$docenas = 0.0;
				$venta = 0.0;

				foreach ($buckets as $b) {
					$match = false;
					if ($tipo === "modelo" && $modelo !== "" && $b["modelo"] === $modelo) {
						$match = true;
					} elseif ($tipo === "modelo_color"
						&& $modelo !== "" && $color !== ""
						&& $b["modelo"] === $modelo && $b["cod_color"] === $color) {
						$match = true;
					} elseif ($tipo === "articulo" && $articulo !== "" && $b["articulo"] === $articulo) {
						$match = true;
					}
					if ($match) {
						$unidades += $b["unidades"];
						$docenas += $b["docenas"];
						$venta += $b["venta"];
					}
				}

				$avanceMeta = ($unidad === "unidades") ? $unidades : $docenas;
				$row = $inc;
				$row["unidades"] = round($unidades, 2);
				$row["docenas"] = round($docenas, 2);
				$row["avance_meta"] = round($avanceMeta, 2);
				$row["venta_objetivo"] = round($venta, 2);
				$salida[$cod][] = $row;
			}
		}

		return $salida;
	}

	static public function mdlConciliacionCoberturaPeriodo($anio, $mes)
	{
		$vendedores = self::mdlVendedoresActivos();
		$cabecera = self::mdlVentaCabeceraPorVendedor($anio, $mes);
		$kpiDetalle = self::mdlVentaPermitidaPorVendedor($anio, $mes, true);
		$porMarca = self::mdlVentasCoberturaPorMarca($anio, $mes);
		$clientesActuales = self::mdlClientesNuevosCabeceraPorVendedor($anio, $mes);
		$clientesPermitidos = self::mdlClientesNuevosPermitidosPorVendedor($anio, $mes);

		$lista = array();
		foreach ($vendedores as $vend) {
			$cod = trim($vend["codigo"]);
			$det = isset($kpiDetalle[$cod]) ? $kpiDetalle[$cod] : array(
				"venta_permitida" => 0.0,
				"permitida_lineas" => 0.0,
				"nc_descuento" => 0.0,
				"fuera_cobertura" => 0.0,
				"sin_marca" => 0.0
			);
			$ventaCab = isset($cabecera[$cod]) ? $cabecera[$cod] : 0.0;
			$ventaKpi = (float) $det["venta_permitida"];

			$lista[] = array(
				"cod_vendedor" => $cod,
				"nombre_vendedor" => $vend["descripcion"],
				"venta_cabecera" => round($ventaCab, 2),
				"venta_permitida" => $ventaKpi,
				"permitida_lineas" => (float) $det["permitida_lineas"],
				"nc_descuento" => (float) $det["nc_descuento"],
				"venta_fuera_cobertura" => (float) $det["fuera_cobertura"],
				"venta_sin_marca" => (float) $det["sin_marca"],
				"diferencia_oficial" => round($ventaCab - $ventaKpi, 2),
				"clientes_nuevos_actual" => isset($clientesActuales[$cod]) ? (int) $clientesActuales[$cod] : 0,
				"clientes_nuevos_permitidos" => isset($clientesPermitidos[$cod]) ? (int) $clientesPermitidos[$cod] : 0,
				"detalle_marcas" => isset($porMarca[$cod]) ? $porMarca[$cod] : array()
			);
		}
		return $lista;
	}
}
