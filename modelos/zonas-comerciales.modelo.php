<?php

require_once "conexion.php";

class ModeloZonasComerciales
{

	static public function mdlListarZonas($soloActivas = false)
	{

		$sql = "SELECT z.*,
				(SELECT COUNT(*) FROM zonas_comerciales_ubigeojf r WHERE r.id_zona = z.id) AS total_ubigeos,
				(SELECT COUNT(*) FROM zonas_comerciales_vendedoresjf v WHERE v.id_zona = z.id) AS total_vendedores
			FROM zonas_comercialesjf z";

		if ($soloActivas) {
			$sql .= " WHERE z.estado = 1";
		}

		$sql .= " ORDER BY z.orden ASC, z.nombre ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlMostrarZona($item, $valor)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM zonas_comercialesjf WHERE $item = :valor LIMIT 1"
		);
		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch();
	}

	static public function mdlCrearZona($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO zonas_comercialesjf
				(codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
			 VALUES
				(:codigo, :nombre, :macrozona, :descripcion, :color, :orden, :estado, :usureg, :fecreg)"
		);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":macrozona", $datos["macrozona"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":color", $datos["color"], PDO::PARAM_STR);
		$stmt->bindParam(":orden", $datos["orden"], PDO::PARAM_INT);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEditarZona($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE zonas_comercialesjf
			 SET nombre = :nombre,
				 macrozona = :macrozona,
				 descripcion = :descripcion,
				 color = :color,
				 orden = :orden,
				 estado = :estado,
				 usumod = :usumod,
				 fecmod = :fecmod
			 WHERE id = :id"
		);

		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":macrozona", $datos["macrozona"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":color", $datos["color"], PDO::PARAM_STR);
		$stmt->bindParam(":orden", $datos["orden"], PDO::PARAM_INT);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
		$stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlCambiarEstadoZona($id, $estado, $usuario)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE zonas_comercialesjf
			 SET estado = :estado, usumod = :usumod, fecmod = NOW()
			 WHERE id = :id"
		);
		$stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
		$stmt->bindParam(":usumod", $usuario, PDO::PARAM_STR);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlListarUbigeosZona($idZona)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT r.id, r.cod_ubi,
				u.Departamento AS departamento,
				u.Provincia AS provincia,
				u.Distrito AS distrito
			 FROM zonas_comerciales_ubigeojf r
			 LEFT JOIN ubigeo u ON u.Codigo = r.cod_ubi
			 WHERE r.id_zona = :id_zona
			 ORDER BY u.Departamento, u.Provincia, u.Distrito"
		);
		$stmt->bindParam(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlBuscarUbigeosDisponibles($termino, $limite = 40)
	{

		$termino = trim((string) $termino);
		$limite = (int) $limite;
		if ($limite < 1) {
			$limite = 40;
		}
		if ($limite > 100) {
			$limite = 100;
		}

		$sql = "SELECT u.Codigo AS cod_ubi,
				u.Departamento AS departamento,
				u.Provincia AS provincia,
				u.Distrito AS distrito,
				r.id_zona, z.nombre AS zona_nombre
			FROM ubigeo u
			LEFT JOIN zonas_comerciales_ubigeojf r ON r.cod_ubi = u.Codigo
			LEFT JOIN zonas_comercialesjf z ON z.id = r.id_zona
			WHERE TRIM(IFNULL(u.Distrito, '')) <> ''
			  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6
			  AND (
				u.Codigo LIKE :q
				OR u.Distrito LIKE :q
				OR u.Provincia LIKE :q
				OR u.Departamento LIKE :q
			)
			ORDER BY u.Departamento, u.Provincia, u.Distrito
			LIMIT $limite";

		$stmt = Conexion::conectar()->prepare($sql);
		$q = "%" . $termino . "%";
		$stmt->bindParam(":q", $q, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlAsignarUbigeo($idZona, $codUbi, $usuario)
	{

		$pdo = Conexion::conectar();
		$stmt = $pdo->prepare(
			"INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
			 VALUES (:id_zona, :cod_ubi, :usureg, NOW())
			 ON DUPLICATE KEY UPDATE id_zona = VALUES(id_zona), usureg = VALUES(usureg), fecreg = NOW()"
		);
		$stmt->bindParam(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->bindParam(":cod_ubi", $codUbi, PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $usuario, PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlQuitarUbigeo($idRegla)
	{

		$stmt = Conexion::conectar()->prepare(
			"DELETE FROM zonas_comerciales_ubigeojf WHERE id = :id"
		);
		$stmt->bindParam(":id", $idRegla, PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlZonaPorId($idZona)
	{

		$idZona = (int) $idZona;
		if ($idZona < 1) {
			return null;
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM zonas_comercialesjf WHERE id = :id AND estado = 1 LIMIT 1"
		);
		$stmt->bindParam(":id", $idZona, PDO::PARAM_INT);
		$stmt->execute();
		$fila = $stmt->fetch();

		return $fila ? $fila : null;
	}

	static public function mdlZonaPorUbigeo($codUbi)
	{

		$codUbi = trim((string) $codUbi);
		if ($codUbi === "") {
			return null;
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT z.*
			 FROM zonas_comerciales_ubigeojf r
			 INNER JOIN zonas_comercialesjf z ON z.id = r.id_zona AND z.estado = 1
			 WHERE r.cod_ubi = :cod_ubi
			 LIMIT 1"
		);
		$stmt->bindParam(":cod_ubi", $codUbi, PDO::PARAM_STR);
		$stmt->execute();
		$fila = $stmt->fetch();

		return $fila ? $fila : null;
	}

	/**
	 * Cascada Fase 1: override cliente > zona grupo > ubigeo dirección principal.
	 */
	static public function mdlResolverZonaCliente($codigoCliente)
	{

		$codigoCliente = trim((string) $codigoCliente);
		$base = array(
			"ok" => false,
			"codigo_cliente" => $codigoCliente,
			"zona" => null,
			"origen" => null,
			"id_zona_cliente" => null,
			"id_zona_grupo" => null,
			"ubigeo" => null,
			"mensaje" => ""
		);

		if ($codigoCliente === "") {
			$base["mensaje"] = "Código de cliente vacío";
			return $base;
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT c.codigo, c.nombre, c.ubigeo, c.id_zona AS id_zona_cliente,
				c.grupo, g.id_zona AS id_zona_grupo, g.nombre AS nombre_grupo
			 FROM clientesjf c
			 LEFT JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
			 WHERE c.codigo = :codigo
			 LIMIT 1"
		);
		$stmt->bindParam(":codigo", $codigoCliente, PDO::PARAM_STR);
		$stmt->execute();
		$cli = $stmt->fetch();

		if (!$cli) {
			$base["mensaje"] = "Cliente no encontrado";
			return $base;
		}

		$base["ok"] = true;
		$base["nombre_cliente"] = $cli["nombre"];
		$base["ubigeo"] = $cli["ubigeo"];
		$base["codigo_grupo"] = isset($cli["grupo"]) ? $cli["grupo"] : null;
		$base["nombre_grupo"] = isset($cli["nombre_grupo"]) ? $cli["nombre_grupo"] : null;
		$base["id_zona_cliente"] = !empty($cli["id_zona_cliente"]) ? (int) $cli["id_zona_cliente"] : null;
		$base["id_zona_grupo"] = !empty($cli["id_zona_grupo"]) ? (int) $cli["id_zona_grupo"] : null;

		if ($base["id_zona_cliente"]) {
			$zona = self::mdlZonaPorId($base["id_zona_cliente"]);
			if ($zona) {
				$base["zona"] = $zona;
				$base["origen"] = "cliente";
				return $base;
			}
		}

		if ($base["id_zona_grupo"]) {
			$zona = self::mdlZonaPorId($base["id_zona_grupo"]);
			if ($zona) {
				$base["zona"] = $zona;
				$base["origen"] = "grupo";
				return $base;
			}
		}

		$zona = self::mdlZonaPorUbigeo($cli["ubigeo"]);
		if ($zona) {
			$base["zona"] = $zona;
			$base["origen"] = "ubigeo";
			return $base;
		}

		$base["origen"] = "sin_zona";
		$base["mensaje"] = "Sin zona resoluble";
		return $base;
	}

	/** Clientes sin zona efectiva o candidatos Gamarra (La Victoria sin override). */
	static public function mdlClientesZonaPorRevisar($limite = 200)
	{

		$limite = (int) $limite;
		if ($limite < 1) {
			$limite = 200;
		}

		$sql = "SELECT c.codigo, c.nombre, c.ubigeo, c.grupo, c.id_zona AS id_zona_cliente,
				g.nombre AS nombre_grupo, g.id_zona AS id_zona_grupo,
				u.Distrito AS distrito, u.Provincia AS provincia, u.Departamento AS departamento,
				zc.nombre AS zona_cliente_nombre,
				zg.nombre AS zona_grupo_nombre,
				zu.nombre AS zona_ubigeo_nombre,
				zu.codigo AS zona_ubigeo_codigo
			FROM clientesjf c
			LEFT JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN ubigeo u ON u.Codigo = c.ubigeo
			LEFT JOIN zonas_comercialesjf zc ON zc.id = c.id_zona
			LEFT JOIN zonas_comercialesjf zg ON zg.id = g.id_zona
			LEFT JOIN zonas_comerciales_ubigeojf ru ON ru.cod_ubi = c.ubigeo
			LEFT JOIN zonas_comercialesjf zu ON zu.id = ru.id_zona
			WHERE c.estado = 1
			  AND (
				(
					(c.id_zona IS NULL OR c.id_zona = 0)
					AND (g.id_zona IS NULL OR g.id_zona = 0)
					AND ru.id_zona IS NULL
				)
				OR (
					UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(u.Distrito,'')),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) = 'LA VICTORIA'
					AND (c.id_zona IS NULL OR c.id_zona = 0)
				)
			  )
			ORDER BY
				CASE WHEN UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(u.Distrito,'')),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) = 'LA VICTORIA'
					THEN 0 ELSE 1 END,
				c.nombre
			LIMIT $limite";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlResumenMacrozonas()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT z.macrozona,
				COUNT(*) AS total_zonas,
				SUM((SELECT COUNT(*) FROM zonas_comerciales_ubigeojf r WHERE r.id_zona = z.id)) AS total_ubigeos
			 FROM zonas_comercialesjf z
			 WHERE z.estado = 1
			 GROUP BY z.macrozona
			 ORDER BY z.macrozona"
		);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlListarVendedoresZona($idZona)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT v.id, v.cod_vendedor, v.fecreg,
				IFNULL(m.descripcion, v.cod_vendedor) AS nombre_vendedor
			 FROM zonas_comerciales_vendedoresjf v
			 LEFT JOIN maestrajf m
			   ON m.codigo = v.cod_vendedor AND UPPER(m.tipo_dato) = 'TVEND'
			 WHERE v.id_zona = :id_zona
			 ORDER BY v.cod_vendedor ASC"
		);
		$stmt->bindParam(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/** Vendedores activos asignados a la zona (mapa / cobertura operativa). */
	static public function mdlListarVendedoresZonaActivos($idZona)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT v.id, v.cod_vendedor, v.fecreg,
				IFNULL(m.descripcion, v.cod_vendedor) AS nombre_vendedor
			 FROM zonas_comerciales_vendedoresjf v
			 INNER JOIN maestrajf m
			   ON m.codigo = v.cod_vendedor
			  AND UPPER(m.tipo_dato) = 'TVEND'
			  AND m.estado_decisiones = 1
			 WHERE v.id_zona = :id_zona
			 ORDER BY v.cod_vendedor ASC"
		);
		$stmt->bindParam(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlListarVendedoresDisponibles($idZona)
	{

		$idZona = (int) $idZona;

		$stmt = Conexion::conectar()->prepare(
			"SELECT m.codigo, m.descripcion
			 FROM maestrajf m
			 WHERE UPPER(m.tipo_dato) = 'TVEND'
			   AND m.estado_decisiones = 1
			   AND m.codigo NOT IN (
				 SELECT v.cod_vendedor
				 FROM zonas_comerciales_vendedoresjf v
				 WHERE v.id_zona = :id_zona
			   )
			 ORDER BY m.codigo ASC"
		);
		$stmt->bindParam(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlAsignarVendedor($idZona, $codVendedor, $usuario)
	{

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO zonas_comerciales_vendedoresjf (id_zona, cod_vendedor, usureg, fecreg)
			 VALUES (:id_zona, :cod_vendedor, :usureg, NOW())
			 ON DUPLICATE KEY UPDATE usureg = VALUES(usureg)"
		);
		$stmt->bindParam(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $usuario, PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlQuitarVendedor($idRegla)
	{

		$stmt = Conexion::conectar()->prepare(
			"DELETE FROM zonas_comerciales_vendedoresjf WHERE id = :id"
		);
		$stmt->bindParam(":id", $idRegla, PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlZonasPorVendedor($codVendedor)
	{

		$codVendedor = trim((string) $codVendedor);
		if ($codVendedor === "") {
			return array();
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT z.*, v.id AS id_asignacion
			 FROM zonas_comerciales_vendedoresjf v
			 INNER JOIN zonas_comercialesjf z ON z.id = v.id_zona AND z.estado = 1
			 WHERE v.cod_vendedor = :cod
			 ORDER BY z.orden ASC, z.nombre ASC"
		);
		$stmt->bindParam(":cod", $codVendedor, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/** Clientes con zona efectiva = esta zona (cliente > grupo > ubigeo). */
	static public function mdlContarClientesZonaEfectiva($idZona)
	{

		$idZona = (int) $idZona;
		if ($idZona < 1) {
			return 0;
		}

		$sql = "SELECT COUNT(*) AS total
			FROM clientesjf c
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			WHERE c.estado = 1
			  AND (
				c.id_zona = :z1
				OR (
					(c.id_zona IS NULL OR c.id_zona = 0)
					AND g.id_zona = :z2
				)
				OR (
					(c.id_zona IS NULL OR c.id_zona = 0)
					AND (g.id_zona IS NULL OR g.id_zona = 0)
					AND r.id_zona = :z3
				)
			  )";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":z1", $idZona, PDO::PARAM_INT);
		$stmt->bindValue(":z2", $idZona, PDO::PARAM_INT);
		$stmt->bindValue(":z3", $idZona, PDO::PARAM_INT);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);

		return $fila ? (int) $fila["total"] : 0;
	}

	private static function sqlTiposVentaReal($alias = "v")
	{
		$tipos = array("S02", "S03", "S70", "E05", "S05");
		$quoted = array();
		foreach ($tipos as $t) {
			$quoted[] = "'" . $t . "'";
		}
		return $alias . ".tipo IN (" . implode(", ", $quoted) . ")";
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

	/**
	 * Venta real (neto) del mes por zona efectiva del cliente,
	 * solo documentos de vendedores activos.
	 * Retorna [id_zona => ['venta_real'=>float, 'por_vendedor'=>[cod=>['nombre'=>, 'venta'=>]]]]
	 */
	static public function mdlVentasZonaEfectivaPeriodo($anio, $mes)
	{

		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");

		$sql = "SELECT
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END AS id_zona,
				TRIM(v.vendedor) AS cod_vendedor,
				MAX(IFNULL(m.descripcion, TRIM(v.vendedor))) AS nombre_vendedor,
				SUM(v.neto) AS venta_real
			FROM ventajf v
			INNER JOIN clientesjf c ON c.codigo = v.cliente
			INNER JOIN maestrajf m
				ON m.codigo = TRIM(v.vendedor)
			   AND UPPER(m.tipo_dato) = 'TVEND'
			   AND m.estado_decisiones = 1
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			WHERE v.fecha >= :ini AND v.fecha < :fin
			  AND {$tipos}
			  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			  AND TRIM(IFNULL(v.vendedor, '')) <> ''
			GROUP BY
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END,
				TRIM(v.vendedor)
			HAVING id_zona IS NOT NULL AND id_zona > 0";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$id = (int) $fila["id_zona"];
			$cod = trim($fila["cod_vendedor"]);
			$venta = (float) $fila["venta_real"];
			if (!isset($mapa[$id])) {
				$mapa[$id] = array(
					"venta_real" => 0.0,
					"por_vendedor" => array()
				);
			}
			$mapa[$id]["venta_real"] += $venta;
			$mapa[$id]["por_vendedor"][$cod] = array(
				"codigo" => $cod,
				"nombre" => $fila["nombre_vendedor"],
				"venta" => $venta
			);
		}

		foreach ($mapa as $id => $datos) {
			$lista = array_values($datos["por_vendedor"]);
			usort($lista, function ($a, $b) {
				if ($a["venta"] == $b["venta"]) {
					return strcmp($a["codigo"], $b["codigo"]);
				}
				return ($a["venta"] < $b["venta"]) ? 1 : -1;
			});
			$mapa[$id]["por_vendedor"] = $lista;
		}

		return $mapa;
	}

	/**
	 * Cantidad de clientes distintos con venta en el período por zona efectiva.
	 * Retorna [id_zona => int]
	 */
	static public function mdlContarClientesConVentaPorZona($anio, $mes)
	{

		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");

		$sql = "SELECT
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END AS id_zona,
				COUNT(DISTINCT c.codigo) AS clientes_con_venta
			FROM ventajf v
			INNER JOIN clientesjf c ON c.codigo = v.cliente
			INNER JOIN maestrajf m
				ON m.codigo = TRIM(v.vendedor)
			   AND UPPER(m.tipo_dato) = 'TVEND'
			   AND m.estado_decisiones = 1
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			WHERE v.fecha >= :ini AND v.fecha < :fin
			  AND {$tipos}
			  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			  AND TRIM(IFNULL(v.vendedor, '')) <> ''
			GROUP BY
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END
			HAVING id_zona IS NOT NULL AND id_zona > 0";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[(int) $fila["id_zona"]] = (int) $fila["clientes_con_venta"];
		}

		return $mapa;
	}

	/**
	 * Clientes nuevos del período por zona efectiva:
	 * primera venta válida de un vendedor activo cae en el mes, sin grupo.
	 * Retorna [id_zona => int]
	 */
	static public function mdlContarClientesNuevosPorZona($anio, $mes)
	{

		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");
		$joinActivo = self::sqlJoinVendedorActivo("v", "ma");

		$sql = "SELECT
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END AS id_zona,
				COUNT(DISTINCT p.cliente) AS clientes_nuevos
			FROM (
				SELECT v.cliente, MIN(v.fecha) AS primera
				FROM ventajf v
				{$joinActivo}
				WHERE v.fecha IS NOT NULL
				  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
				  AND TRIM(IFNULL(v.vendedor, '')) <> ''
				  AND {$tipos}
				GROUP BY v.cliente
			) p
			INNER JOIN clientesjf c ON c.codigo = p.cliente
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			WHERE p.primera >= :ini AND p.primera < :fin
			  AND (c.grupo IS NULL OR TRIM(c.grupo) = '')
			GROUP BY
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END
			HAVING id_zona IS NOT NULL AND id_zona > 0";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[(int) $fila["id_zona"]] = (int) $fila["clientes_nuevos"];
		}

		return $mapa;
	}

	/**
	 * Cartera de vendedores activos en la zona, sin venta de vendedor activo en el mes.
	 * Retorna [id_zona => int]
	 */
	static public function mdlContarClientesSinAtenderPorZona($anio, $mes)
	{

		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");
		$joinActivoVenta = self::sqlJoinVendedorActivo("v", "ma");

		$sql = "SELECT
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END AS id_zona,
				COUNT(*) AS clientes_sin_atender
			FROM clientesjf c
			INNER JOIN maestrajf mc
				ON mc.codigo = TRIM(c.vendedor)
			   AND UPPER(mc.tipo_dato) = 'TVEND'
			   AND mc.estado_decisiones = 1
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			WHERE c.estado = 1
			  AND TRIM(IFNULL(c.vendedor, '')) <> ''
			  AND NOT EXISTS (
				SELECT 1
				FROM ventajf v
				{$joinActivoVenta}
				WHERE v.cliente = c.codigo
				  AND v.fecha >= :ini AND v.fecha < :fin
				  AND {$tipos}
				  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
				  AND TRIM(IFNULL(v.vendedor, '')) <> ''
			  )
			GROUP BY
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END
			HAVING id_zona IS NOT NULL AND id_zona > 0";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$mapa[(int) $fila["id_zona"]] = (int) $fila["clientes_sin_atender"];
		}

		return $mapa;
	}

	/**
	 * Clientes con venta en el período cuya zona efectiva = $idZona
	 * (mismos criterios de venta real / vendedores activos).
	 */
	static public function mdlClientesVentaZonaPeriodo($idZona, $anio, $mes, $limite = 500)
	{

		$idZona = (int) $idZona;
		$anio = (int) $anio;
		$mes = (int) $mes;
		$limite = (int) $limite;
		if ($idZona < 1) {
			return array();
		}
		if ($limite < 1 || $limite > 2000) {
			$limite = 500;
		}

		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");

		$sqlCatBase = "CASE
					WHEN c.grupo IS NOT NULL AND TRIM(c.grupo) <> '' THEN (
						SELECT %s
						FROM categorias_clientes_asignacionesjf a
						INNER JOIN categorias_clientesjf cat ON cat.id = a.id_categoria
						WHERE a.tipo_entidad = 'grupo'
						  AND a.codigo_entidad = c.grupo
						  AND a.estado = 1
						  AND a.vigencia_desde <= NOW()
						  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
						ORDER BY a.id DESC
						LIMIT 1
					)
					ELSE (
						SELECT %s
						FROM categorias_clientes_asignacionesjf a
						INNER JOIN categorias_clientesjf cat ON cat.id = a.id_categoria
						WHERE a.tipo_entidad = 'cliente'
						  AND a.codigo_entidad = c.codigo
						  AND a.estado = 1
						  AND a.vigencia_desde <= NOW()
						  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
						ORDER BY a.id DESC
						LIMIT 1
					)
				END";

		$sqlCatNombre = "IFNULL(" . sprintf($sqlCatBase, "cat.nombre", "cat.nombre") . ", 'Sin categoría')";
		$sqlCatColor = sprintf($sqlCatBase, "cat.color", "cat.color");

		$sql = "SELECT
				c.codigo AS codigo_cliente,
				IFNULL(c.nombre, c.codigo) AS nombre_cliente,
				{$sqlCatNombre} AS categoria_cliente,
				{$sqlCatColor} AS categoria_color,
				SUM(v.neto) AS venta_real,
				COUNT(DISTINCT TRIM(v.vendedor)) AS vendedores,
				GROUP_CONCAT(DISTINCT TRIM(v.vendedor) ORDER BY TRIM(v.vendedor) SEPARATOR ', ') AS codigos_vendedor
			FROM ventajf v
			INNER JOIN clientesjf c ON c.codigo = v.cliente
			INNER JOIN maestrajf m
				ON m.codigo = TRIM(v.vendedor)
			   AND UPPER(m.tipo_dato) = 'TVEND'
			   AND m.estado_decisiones = 1
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			WHERE v.fecha >= :ini AND v.fecha < :fin
			  AND {$tipos}
			  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			  AND TRIM(IFNULL(v.vendedor, '')) <> ''
			  AND (
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END
			  ) = :id_zona
			GROUP BY c.codigo, c.nombre, c.grupo
			ORDER BY venta_real DESC
			LIMIT {$limite}";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->bindValue(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->execute();

		$nuevosSet = array();
		foreach (self::mdlClientesNuevosZonaPeriodo($idZona, $anio, $mes, 5000) as $n) {
			$nuevosSet[$n["codigo"]] = true;
		}

		$lista = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$cod = $fila["codigo_cliente"];
			$lista[] = array(
				"codigo" => $cod,
				"nombre" => $fila["nombre_cliente"],
				"categoria" => isset($fila["categoria_cliente"]) ? $fila["categoria_cliente"] : "Sin categoría",
				"categoria_color" => !empty($fila["categoria_color"]) ? $fila["categoria_color"] : "#777777",
				"venta_real" => round((float) $fila["venta_real"], 2),
				"vendedores" => (int) $fila["vendedores"],
				"codigos_vendedor" => isset($fila["codigos_vendedor"]) ? $fila["codigos_vendedor"] : "",
				"es_nuevo" => isset($nuevosSet[$cod])
			);
		}

		usort($lista, function ($a, $b) {
			if ($a["es_nuevo"] !== $b["es_nuevo"]) {
				return $a["es_nuevo"] ? -1 : 1;
			}
			if ($a["venta_real"] == $b["venta_real"]) {
				return strcmp($a["codigo"], $b["codigo"]);
			}
			return ($a["venta_real"] < $b["venta_real"]) ? 1 : -1;
		});

		return $lista;
	}

	/**
	 * Detalle de clientes nuevos de la zona en el período
	 * (1ª venta de vendedor activo en el mes, sin grupo; vendedor = esa 1ª venta).
	 */
	static public function mdlClientesNuevosZonaPeriodo($idZona, $anio, $mes, $limite = 500)
	{

		$idZona = (int) $idZona;
		$anio = (int) $anio;
		$mes = (int) $mes;
		$limite = (int) $limite;
		if ($idZona < 1) {
			return array();
		}
		if ($limite < 1 || $limite > 5000) {
			$limite = 500;
		}

		$rango = self::rangoMes($anio, $mes);
		$tipos = self::sqlTiposVentaReal("v");
		$tipos0 = self::sqlTiposVentaReal("v0");
		$joinPrimera = self::sqlJoinVendedorActivo("v", "ma");
		$joinV0 = self::sqlJoinVendedorActivo("v0", "m0");
		$joinV2 = self::sqlJoinVendedorActivo("v2", "m2");

		$sql = "SELECT
				c.codigo AS codigo_cliente,
				IFNULL(c.nombre, c.codigo) AS nombre_cliente,
				MIN(TRIM(v0.vendedor)) AS cod_vendedor,
				MAX(IFNULL(m0.descripcion, TRIM(v0.vendedor))) AS nombre_vendedor,
				p.primera AS fecha_primera,
				IFNULL(ven.venta_real, 0) AS venta_real
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
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			LEFT JOIN (
				SELECT v2.cliente, SUM(v2.neto) AS venta_real
				FROM ventajf v2
				{$joinV2}
				WHERE v2.fecha >= :ini2 AND v2.fecha < :fin2
				  AND " . self::sqlTiposVentaReal("v2") . "
				  AND UPPER(IFNULL(v2.estado, '')) <> 'ANULADO'
				  AND TRIM(IFNULL(v2.vendedor, '')) <> ''
				GROUP BY v2.cliente
			) ven ON ven.cliente = c.codigo
			WHERE p.primera >= :ini AND p.primera < :fin
			  AND (c.grupo IS NULL OR TRIM(c.grupo) = '')
			  AND (
				CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END
			  ) = :id_zona
			GROUP BY c.codigo, c.nombre, p.primera, ven.venta_real
			ORDER BY venta_real DESC, c.codigo ASC
			LIMIT {$limite}";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $rango["fin"], PDO::PARAM_STR);
		$stmt->bindParam(":ini2", $rango["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin2", $rango["fin"], PDO::PARAM_STR);
		$stmt->bindValue(":id_zona", $idZona, PDO::PARAM_INT);
		$stmt->execute();

		$lista = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$lista[] = array(
				"codigo" => $fila["codigo_cliente"],
				"nombre" => $fila["nombre_cliente"],
				"cod_vendedor" => isset($fila["cod_vendedor"]) ? $fila["cod_vendedor"] : "",
				"nombre_vendedor" => isset($fila["nombre_vendedor"]) ? $fila["nombre_vendedor"] : "",
				"fecha_primera" => isset($fila["fecha_primera"]) ? $fila["fecha_primera"] : "",
				"venta_real" => round((float) $fila["venta_real"], 2),
				"es_nuevo" => true
			);
		}

		return $lista;
	}

	/**
	 * Ventas por zona y mes (vendedores activos), últimos 12 meses
	 * cerrando en $anio/$mes, filtrados por vista de mapa (lima | peru).
	 * Retorna labels + series[] con 12 valores por zona.
	 */
	static public function mdlVentasTotalesVistaUltimos12Meses($vista, $anioFin, $mesFin)
	{

		$vista = trim((string) $vista);
		$anioFin = (int) $anioFin;
		$mesFin = (int) $mesFin;
		if ($anioFin < 2000 || $anioFin > 2100) {
			$anioFin = (int) date("Y");
		}
		if ($mesFin < 1 || $mesFin > 12) {
			$mesFin = (int) date("n");
		}

		$macros = array();
		if ($vista === "peru") {
			$macros = array("peru_norte", "peru_sur");
		} else {
			$macros = array("lima");
			$vista = "lima";
		}

		$placeholders = array();
		foreach ($macros as $i => $m) {
			$placeholders[] = ":m" . $i;
		}

		$tsFin = mktime(0, 0, 0, $mesFin, 1, $anioFin);
		$inicio = date("Y-m-01", mktime(0, 0, 0, $mesFin - 11, 1, $anioFin));
		$finExcl = date("Y-m-01", mktime(0, 0, 0, $mesFin + 1, 1, $anioFin));

		$nombresMes = array(
			1 => "Ene", 2 => "Feb", 3 => "Mar", 4 => "Abr",
			5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Ago",
			9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dic"
		);

		$labels = array();
		$keysMes = array();
		for ($i = 0; $i < 12; $i++) {
			$ts = mktime(0, 0, 0, $mesFin - 11 + $i, 1, $anioFin);
			$a = (int) date("Y", $ts);
			$m = (int) date("n", $ts);
			$keysMes[] = sprintf("%04d-%02d", $a, $m);
			$labels[] = $nombresMes[$m] . " " . substr((string) $a, -2);
		}

		$zonas = self::mdlListarZonas(true);
		$seriesBase = array();
		foreach ($zonas as $z) {
			$macro = isset($z["macrozona"]) ? $z["macrozona"] : "";
			if (!in_array($macro, $macros, true)) {
				continue;
			}
			$id = (int) $z["id"];
			$seriesBase[$id] = array(
				"id" => $id,
				"codigo" => $z["codigo"],
				"nombre" => $z["nombre"],
				"color" => isset($z["color"]) && $z["color"] !== "" ? $z["color"] : "#777777",
				"orden" => (int) $z["orden"],
				"por_mes" => array()
			);
		}

		if (empty($seriesBase)) {
			return array(
				"vista" => $vista,
				"desde" => $inicio,
				"hasta" => date("Y-m-t", $tsFin),
				"labels" => $labels,
				"series" => array()
			);
		}

		$tipos = self::sqlTiposVentaReal("v");
		$joinActivo = self::sqlJoinVendedorActivo("v", "ma");

		$sql = "SELECT
				z.id AS id_zona,
				YEAR(v.fecha) AS anio,
				MONTH(v.fecha) AS mes,
				SUM(v.neto) AS venta_real
			FROM ventajf v
			{$joinActivo}
			INNER JOIN clientesjf c ON c.codigo = v.cliente
			LEFT JOIN grupos_empresarialesjf g
				ON g.codigo = c.grupo AND g.estado = 1
			LEFT JOIN zonas_comerciales_ubigeojf r
				ON r.cod_ubi = c.ubigeo
			INNER JOIN zonas_comercialesjf z
				ON z.id = CASE
					WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
					WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
					ELSE r.id_zona
				END
			   AND z.estado = 1
			   AND z.macrozona IN (" . implode(", ", $placeholders) . ")
			WHERE v.fecha >= :ini AND v.fecha < :fin
			  AND {$tipos}
			  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			  AND TRIM(IFNULL(v.vendedor, '')) <> ''
			GROUP BY z.id, YEAR(v.fecha), MONTH(v.fecha)
			ORDER BY z.id ASC, anio ASC, mes ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":ini", $inicio, PDO::PARAM_STR);
		$stmt->bindParam(":fin", $finExcl, PDO::PARAM_STR);
		foreach ($macros as $i => $m) {
			$stmt->bindValue(":m" . $i, $m, PDO::PARAM_STR);
		}
		$stmt->execute();

		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
			$id = (int) $fila["id_zona"];
			if (!isset($seriesBase[$id])) {
				continue;
			}
			$key = sprintf("%04d-%02d", (int) $fila["anio"], (int) $fila["mes"]);
			$seriesBase[$id]["por_mes"][$key] = round((float) $fila["venta_real"], 2);
		}

		$series = array_values($seriesBase);
		usort($series, function ($a, $b) {
			if ($a["orden"] === $b["orden"]) {
				return strcmp($a["nombre"], $b["nombre"]);
			}
			return ($a["orden"] < $b["orden"]) ? -1 : 1;
		});

		$salida = array();
		foreach ($series as $s) {
			$valores = array();
			foreach ($keysMes as $km) {
				$valores[] = isset($s["por_mes"][$km]) ? (float) $s["por_mes"][$km] : 0.0;
			}
			$salida[] = array(
				"id" => $s["id"],
				"codigo" => $s["codigo"],
				"nombre" => $s["nombre"],
				"color" => $s["color"],
				"valores" => $valores
			);
		}

		return array(
			"vista" => $vista,
			"desde" => $inicio,
			"hasta" => date("Y-m-t", $tsFin),
			"labels" => $labels,
			"series" => $salida
		);
	}

	static public function mdlResumenMapaZonas($vista = null, $anio = null, $mes = null)
	{

		date_default_timezone_set("America/Lima");
		$anio = $anio === null ? (int) date("Y") : (int) $anio;
		$mes = $mes === null ? (int) date("n") : (int) $mes;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}
		if ($mes < 1 || $mes > 12) {
			$mes = (int) date("n");
		}

		$zonas = self::mdlListarZonas(true);
		$ventas = self::mdlVentasZonaEfectivaPeriodo($anio, $mes);
		$clientesVenta = self::mdlContarClientesConVentaPorZona($anio, $mes);
		$clientesNuevos = self::mdlContarClientesNuevosPorZona($anio, $mes);
		$clientesSinAtender = self::mdlContarClientesSinAtenderPorZona($anio, $mes);
		$lista = array();
		$vista = $vista === null ? "" : trim((string) $vista);

		foreach ($zonas as $z) {
			$macro = isset($z["macrozona"]) ? $z["macrozona"] : "";

			if ($vista === "lima" && $macro !== "lima") {
				continue;
			}
			if ($vista === "peru" && !in_array($macro, array("peru_norte", "peru_sur"), true)) {
				continue;
			}

			$id = (int) $z["id"];
			$vendedores = self::mdlListarVendedoresZonaActivos($id);
			$vendActivos = array();
			foreach ($vendedores as $v) {
				$vendActivos[] = array(
					"codigo" => $v["cod_vendedor"],
					"nombre" => $v["nombre_vendedor"]
				);
			}

			$ventaZona = isset($ventas[$id]) ? $ventas[$id]["venta_real"] : 0.0;
			$vendVentas = isset($ventas[$id]) ? $ventas[$id]["por_vendedor"] : array();
			$cliVenta = isset($clientesVenta[$id]) ? (int) $clientesVenta[$id] : 0;
			$cliNuevos = isset($clientesNuevos[$id]) ? (int) $clientesNuevos[$id] : 0;
			$cliSinAtender = isset($clientesSinAtender[$id]) ? (int) $clientesSinAtender[$id] : 0;

			$lista[] = array(
				"id" => $id,
				"codigo" => $z["codigo"],
				"nombre" => $z["nombre"],
				"macrozona" => $macro,
				"color" => isset($z["color"]) && $z["color"] !== "" ? $z["color"] : "#777777",
				"descripcion" => isset($z["descripcion"]) ? $z["descripcion"] : "",
				"orden" => (int) $z["orden"],
				"anio" => $anio,
				"mes" => $mes,
				"total_ubigeos" => isset($z["total_ubigeos"]) ? (int) $z["total_ubigeos"] : 0,
				"total_vendedores" => count($vendActivos),
				"vendedores" => $vendActivos,
				"clientes_con_venta" => $cliVenta,
				"clientes_nuevos" => $cliNuevos,
				"clientes_sin_atender" => $cliSinAtender,
				"venta_real" => round($ventaZona, 2),
				"venta_por_vendedor" => $vendVentas
			);
		}

		return $lista;
	}
}
