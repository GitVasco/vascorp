<?php

date_default_timezone_set("America/Lima");

require_once "conexion.php";

class ModeloCategoriasModelos
{
	static public function mdlCatalogoActivo()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT c.id AS id_categoria, c.codigo AS codigo_categoria, c.nombre AS nombre_categoria, c.orden AS orden_categoria,
				s.id AS id_subcategoria, s.codigo AS codigo_subcategoria, s.nombre AS nombre_subcategoria, s.orden AS orden_subcategoria,
				(SELECT COUNT(*) FROM modelo_subcategoriajf ms
				 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
				   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
				 WHERE ms.id_subcategoria = s.id) AS modelos_activos,
				(SELECT COUNT(*) FROM modelo_subcategoriajf ms
				 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
				   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
				 WHERE ms.id_categoria = c.id) AS modelos_categoria,
				(SELECT COUNT(*) FROM modelo_subcategoriajf ms
				 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
				   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
				 WHERE ms.id_categoria = c.id AND ms.id_subcategoria IS NULL) AS modelos_solo_categoria
			 FROM categoria_modelojf c
			 LEFT JOIN subcategoria_modelojf s ON s.id_categoria = c.id AND s.estado = 1
			 WHERE c.estado = 1
			 ORDER BY c.orden ASC, c.nombre ASC, s.orden ASC, s.nombre ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlListarMarcasActivas()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT mk.id, mk.marca
			 FROM modelojf m
			 INNER JOIN marcasjf mk ON mk.id = m.id_marca
			 WHERE UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			   AND TRIM(IFNULL(m.modelo, '')) <> ''
			 ORDER BY mk.marca ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlConteos($idMarca = 0, $q = "")
	{
		$sqlBase = "FROM modelojf m
			LEFT JOIN modelo_subcategoriajf ms ON ms.modelo = TRIM(m.modelo)
			WHERE UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			  AND TRIM(IFNULL(m.modelo, '')) <> ''";
		$params = array();
		if ((int) $idMarca > 0) {
			$sqlBase .= " AND m.id_marca = :id_marca";
			$params[":id_marca"] = (int) $idMarca;
		}
		$q = trim((string) $q);
		if ($q !== "") {
			$sqlBase .= " AND (TRIM(m.modelo) LIKE :q
				OR IFNULL(m.nombre, '') LIKE :q
				OR IFNULL(m.tipo, '') LIKE :q
				OR IFNULL(m.linea, '') LIKE :q)";
			$params[":q"] = "%" . $q . "%";
		}

		$pdo = Conexion::conectar();
		$stmtActivos = $pdo->prepare("SELECT COUNT(*) " . $sqlBase);
		foreach ($params as $k => $v) {
			$stmtActivos->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmtActivos->execute();
		$activos = (int) $stmtActivos->fetchColumn();

		$stmtPend = $pdo->prepare("SELECT COUNT(*) " . $sqlBase . " AND ms.modelo IS NULL");
		foreach ($params as $k => $v) {
			$stmtPend->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmtPend->execute();
		$pendientes = (int) $stmtPend->fetchColumn();

		$stmtSoloCat = $pdo->prepare(
			"SELECT COUNT(*) " . $sqlBase . " AND ms.modelo IS NOT NULL AND ms.id_subcategoria IS NULL"
		);
		foreach ($params as $k => $v) {
			$stmtSoloCat->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmtSoloCat->execute();
		$enCategoria = (int) $stmtSoloCat->fetchColumn();

		$stmtClas = $pdo->prepare(
			"SELECT COUNT(*) " . $sqlBase . " AND ms.id_subcategoria IS NOT NULL"
		);
		foreach ($params as $k => $v) {
			$stmtClas->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmtClas->execute();
		$clasificados = (int) $stmtClas->fetchColumn();

		return array(
			"activos" => $activos,
			"pendientes" => $pendientes,
			"en_categoria" => $enCategoria,
			"clasificados" => $clasificados
		);
	}

	static public function mdlListar($filtros)
	{
		$pagina = max(1, (int) $filtros["pagina"]);
		$limite = (int) $filtros["limite"];
		if ($limite < 1) {
			$limite = 50;
		}
		if ($limite > 2000) {
			$limite = 2000;
		}
		$offset = ($pagina - 1) * $limite;

		$sql = "SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca,
				IFNULL(TRIM(m.tipo), '') AS tipo,
				IFNULL(TRIM(m.linea), '') AS linea,
				IFNULL(NULLIF(TRIM(m.imagen), ''), 'vistas/img/modelos/default/anonymous.png') AS imagen,
				ms.id_subcategoria,
				s.nombre AS nombre_subcategoria,
				s.codigo AS codigo_subcategoria,
				c.id AS id_categoria,
				c.nombre AS nombre_categoria,
				c.codigo AS codigo_categoria,
				ms.fecha_asignacion,
				ms.usuario_asignacion,
				ms.actualizado_en,
				ms.usuario_actualizacion,
				IFNULL(ua.nombre, '') AS nombre_usuario_asignacion,
				IFNULL(uu.nombre, '') AS nombre_usuario_actualizacion
			FROM modelojf m
			LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			LEFT JOIN modelo_subcategoriajf ms ON ms.modelo = TRIM(m.modelo)
			LEFT JOIN subcategoria_modelojf s ON s.id = ms.id_subcategoria
			LEFT JOIN categoria_modelojf c ON c.id = COALESCE(ms.id_categoria, s.id_categoria)
			LEFT JOIN usuariosjf ua ON ua.id = ms.usuario_asignacion
			LEFT JOIN usuariosjf uu ON uu.id = ms.usuario_actualizacion
			WHERE UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			  AND TRIM(IFNULL(m.modelo, '')) <> ''";

		$params = array();
		if ((int) $filtros["id_marca"] > 0) {
			$sql .= " AND m.id_marca = :id_marca";
			$params[":id_marca"] = (int) $filtros["id_marca"];
		}
		$estadoLista = $filtros["estado_lista"];
		$idCategoriaPool = isset($filtros["id_categoria_pool"]) ? (int) $filtros["id_categoria_pool"] : 0;
		if ($estadoLista !== "pendientes_o_categoria" && (int) $filtros["id_categoria"] > 0) {
			$sql .= " AND c.id = :id_categoria";
			$params[":id_categoria"] = (int) $filtros["id_categoria"];
		}
		if ((int) $filtros["id_subcategoria"] > 0) {
			$sql .= " AND ms.id_subcategoria = :id_subcategoria";
			$params[":id_subcategoria"] = (int) $filtros["id_subcategoria"];
		}
		$q = trim((string) $filtros["q"]);
		if ($q !== "") {
			$sql .= " AND (TRIM(m.modelo) LIKE :q
				OR IFNULL(m.nombre, '') LIKE :q
				OR IFNULL(m.tipo, '') LIKE :q
				OR IFNULL(m.linea, '') LIKE :q)";
			$params[":q"] = "%" . $q . "%";
		}
		if ($estadoLista === "pendientes") {
			$sql .= " AND ms.modelo IS NULL";
		} elseif ($estadoLista === "en_categoria") {
			$sql .= " AND ms.modelo IS NOT NULL AND ms.id_subcategoria IS NULL";
		} elseif ($estadoLista === "clasificados") {
			$sql .= " AND ms.id_subcategoria IS NOT NULL";
		} elseif ($estadoLista === "pendientes_o_categoria") {
			if ($idCategoriaPool > 0) {
				$sql .= " AND (ms.modelo IS NULL OR (ms.id_categoria = :id_cat_pool AND ms.id_subcategoria IS NULL))";
				$params[":id_cat_pool"] = $idCategoriaPool;
			} else {
				$sql .= " AND ms.modelo IS NULL";
			}
		}

		$sqlCount = "SELECT COUNT(*) FROM (" . $sql . ") t";
		$pdo = Conexion::conectar();
		$stmtCount = $pdo->prepare($sqlCount);
		foreach ($params as $k => $v) {
			$stmtCount->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmtCount->execute();
		$total = (int) $stmtCount->fetchColumn();

		$sql .= " ORDER BY TRIM(m.modelo) ASC LIMIT :limite OFFSET :offset";
		$stmt = $pdo->prepare($sql);
		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
		$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return array(
			"filas" => $filas,
			"total" => $total,
			"pagina" => $pagina,
			"limite" => $limite
		);
	}

	static public function mdlModeloActivo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre
			 FROM modelojf m
			 WHERE TRIM(m.modelo) = :modelo
			   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			 LIMIT 1"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlSubcategoriaActiva($idSubcategoria)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT s.id AS id_subcategoria, s.codigo AS codigo_subcategoria, s.nombre AS nombre_subcategoria,
				c.id AS id_categoria, c.codigo AS codigo_categoria, c.nombre AS nombre_categoria
			 FROM subcategoria_modelojf s
			 INNER JOIN categoria_modelojf c ON c.id = s.id_categoria AND c.estado = 1
			 WHERE s.id = :id AND s.estado = 1
			 LIMIT 1"
		);
		$stmt->bindValue(":id", (int) $idSubcategoria, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlCategoriaActiva($idCategoria)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT id AS id_categoria, codigo AS codigo_categoria, nombre AS nombre_categoria
			 FROM categoria_modelojf
			 WHERE id = :id AND estado = 1
			 LIMIT 1"
		);
		$stmt->bindValue(":id", (int) $idCategoria, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlAsignacionVigente($modelo, $pdo = null, $forUpdate = false)
	{
		$pdo = $pdo instanceof PDO ? $pdo : Conexion::conectar();
		$sql = "SELECT ms.modelo, ms.id_subcategoria, ms.id_categoria AS id_categoria_asig,
				ms.fecha_asignacion, ms.usuario_asignacion,
				ms.actualizado_en, ms.usuario_actualizacion,
				s.nombre AS nombre_subcategoria, s.codigo AS codigo_subcategoria,
				c.id AS id_categoria, c.nombre AS nombre_categoria, c.codigo AS codigo_categoria
			 FROM modelo_subcategoriajf ms
			 LEFT JOIN categoria_modelojf c ON c.id = ms.id_categoria
			 LEFT JOIN subcategoria_modelojf s ON s.id = ms.id_subcategoria
			 WHERE ms.modelo = :modelo
			 LIMIT 1";
		if ($forUpdate) {
			$sql .= " FOR UPDATE";
		}
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlAsignar($modelo, $idSubcategoria, $usuarioId, $observacion = null, $origen = "pantalla")
	{
		$pdo = Conexion::conectar();
		$modelo = trim((string) $modelo);
		$idSubcategoria = (int) $idSubcategoria;
		$usuarioId = (int) $usuarioId > 0 ? (int) $usuarioId : null;
		$observacion = $observacion !== null ? trim((string) $observacion) : null;
		if ($observacion === "") {
			$observacion = null;
		}
		if ($observacion !== null && mb_strlen($observacion) > 250) {
			$observacion = mb_substr($observacion, 0, 250);
		}

		try {
			$pdo->beginTransaction();

			$modeloActivo = self::mdlModeloActivo($modelo);
			if (!$modeloActivo) {
				$pdo->rollBack();
				return array("ok" => false, "mensaje" => "Modelo inactivo o inexistente");
			}

			$sub = self::mdlSubcategoriaActiva($idSubcategoria);
			if (!$sub) {
				$pdo->rollBack();
				return array("ok" => false, "mensaje" => "Subcategoría inválida o inactiva");
			}

			$actual = self::mdlAsignacionVigente($modelo, $pdo, true);
			$idSubActual = $actual && $actual["id_subcategoria"] !== null ? (int) $actual["id_subcategoria"] : null;
			if ($actual && $idSubActual === $idSubcategoria) {
				$pdo->commit();
				return array(
					"ok" => true,
					"idempotente" => true,
					"data" => self::mdlFormatearAsignacion($actual, $usuarioId)
				);
			}

			$idCategoria = (int) $sub["id_categoria"];
			$ahora = date("Y-m-d H:i:s");
			if ($actual) {
				$upd = $pdo->prepare(
					"UPDATE modelo_subcategoriajf
					 SET id_categoria = :id_categoria,
						 id_subcategoria = :id_subcategoria,
						 actualizado_en = :ahora,
						 usuario_actualizacion = :usuario
					 WHERE modelo = :modelo"
				);
				$upd->bindValue(":id_categoria", $idCategoria, PDO::PARAM_INT);
				$upd->bindValue(":id_subcategoria", $idSubcategoria, PDO::PARAM_INT);
				$upd->bindValue(":ahora", $ahora, PDO::PARAM_STR);
				$upd->bindValue(":usuario", $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
				$upd->bindValue(":modelo", $modelo, PDO::PARAM_STR);
				$upd->execute();
				$accion = "CAMBIO";
				$idAnterior = $idSubActual;
				$idCatAnterior = $actual["id_categoria"] !== null ? (int) $actual["id_categoria"] : null;
			} else {
				$ins = $pdo->prepare(
					"INSERT INTO modelo_subcategoriajf
						(modelo, id_categoria, id_subcategoria, fecha_asignacion, usuario_asignacion)
					 VALUES (:modelo, :id_categoria, :id_subcategoria, :ahora, :usuario)"
				);
				$ins->bindValue(":modelo", $modelo, PDO::PARAM_STR);
				$ins->bindValue(":id_categoria", $idCategoria, PDO::PARAM_INT);
				$ins->bindValue(":id_subcategoria", $idSubcategoria, PDO::PARAM_INT);
				$ins->bindValue(":ahora", $ahora, PDO::PARAM_STR);
				$ins->bindValue(":usuario", $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
				$ins->execute();
				$accion = "ALTA";
				$idAnterior = null;
				$idCatAnterior = null;
			}

			self::mdlInsertarHistorial(
				$pdo,
				$modelo,
				$idCatAnterior,
				$idCategoria,
				$idAnterior,
				$idSubcategoria,
				$accion,
				$ahora,
				$usuarioId,
				$origen,
				$observacion
			);

			$pdo->commit();
			$vigente = self::mdlAsignacionVigente($modelo);
			return array(
				"ok" => true,
				"idempotente" => false,
				"accion" => $accion,
				"data" => self::mdlFormatearAsignacion($vigente, $usuarioId)
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudo guardar la asignación");
		}
	}

	static public function mdlAsignarCategoria($modelo, $idCategoria, $usuarioId, $observacion = null, $origen = "pantalla", $forzarSoloCategoria = false)
	{
		$pdo = Conexion::conectar();
		$modelo = trim((string) $modelo);
		$idCategoria = (int) $idCategoria;
		$usuarioId = (int) $usuarioId > 0 ? (int) $usuarioId : null;
		$observacion = $observacion !== null ? trim((string) $observacion) : null;
		if ($observacion === "") {
			$observacion = null;
		}
		if ($observacion !== null && mb_strlen($observacion) > 250) {
			$observacion = mb_substr($observacion, 0, 250);
		}

		try {
			$pdo->beginTransaction();

			$modeloActivo = self::mdlModeloActivo($modelo);
			if (!$modeloActivo) {
				$pdo->rollBack();
				return array("ok" => false, "mensaje" => "Modelo inactivo o inexistente");
			}

			$cat = self::mdlCategoriaActiva($idCategoria);
			if (!$cat) {
				$pdo->rollBack();
				return array("ok" => false, "mensaje" => "Categoría inválida o inactiva");
			}

			$actual = self::mdlAsignacionVigente($modelo, $pdo, true);
			$idCatActual = $actual && $actual["id_categoria"] !== null ? (int) $actual["id_categoria"] : null;
			$idSubActual = $actual && $actual["id_subcategoria"] !== null ? (int) $actual["id_subcategoria"] : null;
			$yaSoloCategoria = $actual && $idCatActual === $idCategoria && $idSubActual === null;
			$mismaCategoriaConSub = $actual && $idCatActual === $idCategoria && $idSubActual !== null;
			if ($yaSoloCategoria || ($mismaCategoriaConSub && !$forzarSoloCategoria)) {
				$pdo->commit();
				return array(
					"ok" => true,
					"idempotente" => true,
					"data" => self::mdlFormatearAsignacion($actual, $usuarioId)
				);
			}

			$ahora = date("Y-m-d H:i:s");
			if ($actual) {
				$upd = $pdo->prepare(
					"UPDATE modelo_subcategoriajf
					 SET id_categoria = :id_categoria,
						 id_subcategoria = NULL,
						 actualizado_en = :ahora,
						 usuario_actualizacion = :usuario
					 WHERE modelo = :modelo"
				);
				$upd->bindValue(":id_categoria", $idCategoria, PDO::PARAM_INT);
				$upd->bindValue(":ahora", $ahora, PDO::PARAM_STR);
				$upd->bindValue(":usuario", $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
				$upd->bindValue(":modelo", $modelo, PDO::PARAM_STR);
				$upd->execute();
				$accion = "CAMBIO";
			} else {
				$ins = $pdo->prepare(
					"INSERT INTO modelo_subcategoriajf
						(modelo, id_categoria, id_subcategoria, fecha_asignacion, usuario_asignacion)
					 VALUES (:modelo, :id_categoria, NULL, :ahora, :usuario)"
				);
				$ins->bindValue(":modelo", $modelo, PDO::PARAM_STR);
				$ins->bindValue(":id_categoria", $idCategoria, PDO::PARAM_INT);
				$ins->bindValue(":ahora", $ahora, PDO::PARAM_STR);
				$ins->bindValue(":usuario", $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
				$ins->execute();
				$accion = "ALTA";
				$idSubActual = null;
			}

			self::mdlInsertarHistorial(
				$pdo,
				$modelo,
				$idCatActual,
				$idCategoria,
				$idSubActual,
				null,
				$accion,
				$ahora,
				$usuarioId,
				$origen,
				$observacion
			);

			$pdo->commit();
			$vigente = self::mdlAsignacionVigente($modelo);
			return array(
				"ok" => true,
				"idempotente" => false,
				"accion" => $accion,
				"data" => self::mdlFormatearAsignacion($vigente, $usuarioId)
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudo guardar la categoría");
		}
	}

	static public function mdlQuitar($modelo, $usuarioId, $origen = "pantalla")
	{
		$pdo = Conexion::conectar();
		$modelo = trim((string) $modelo);
		$usuarioId = (int) $usuarioId > 0 ? (int) $usuarioId : null;

		try {
			$pdo->beginTransaction();

			$modeloActivo = self::mdlModeloActivo($modelo);
			if (!$modeloActivo) {
				$pdo->rollBack();
				return array("ok" => false, "mensaje" => "Modelo inactivo o inexistente");
			}

			$actual = self::mdlAsignacionVigente($modelo, $pdo, true);
			if (!$actual) {
				$pdo->commit();
				return array("ok" => true, "idempotente" => true);
			}

			$ahora = date("Y-m-d H:i:s");
			$del = $pdo->prepare("DELETE FROM modelo_subcategoriajf WHERE modelo = :modelo LIMIT 1");
			$del->bindValue(":modelo", $modelo, PDO::PARAM_STR);
			$del->execute();

			$idSubAnt = $actual["id_subcategoria"] !== null ? (int) $actual["id_subcategoria"] : null;
			$idCatAnt = $actual["id_categoria"] !== null ? (int) $actual["id_categoria"] : null;
			self::mdlInsertarHistorial(
				$pdo,
				$modelo,
				$idCatAnt,
				null,
				$idSubAnt,
				null,
				"BAJA",
				$ahora,
				$usuarioId,
				$origen,
				null
			);

			$pdo->commit();
			return array("ok" => true, "idempotente" => false, "accion" => "BAJA");
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "No se pudo quitar la asignación");
		}
	}

	static private function mdlInsertarHistorial(
		$pdo,
		$modelo,
		$idCatAnterior,
		$idCatNueva,
		$idSubAnterior,
		$idSubNueva,
		$accion,
		$fecha,
		$usuarioId,
		$origen,
		$observacion
	) {
		$hist = $pdo->prepare(
			"INSERT INTO modelo_subcategoria_historialjf
				(modelo, id_categoria_anterior, id_categoria_nueva,
				 id_subcategoria_anterior, id_subcategoria_nueva,
				 accion, fecha, usuario_id, origen, observacion)
			 VALUES (:modelo, :id_cat_ant, :id_cat_nueva, :id_ant, :id_nueva,
				 :accion, :fecha, :usuario, :origen, :obs)"
		);
		$hist->bindValue(":modelo", $modelo, PDO::PARAM_STR);
		$hist->bindValue(":id_cat_ant", $idCatAnterior, $idCatAnterior === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$hist->bindValue(":id_cat_nueva", $idCatNueva, $idCatNueva === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$hist->bindValue(":id_ant", $idSubAnterior, $idSubAnterior === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$hist->bindValue(":id_nueva", $idSubNueva, $idSubNueva === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$hist->bindValue(":accion", $accion, PDO::PARAM_STR);
		$hist->bindValue(":fecha", $fecha, PDO::PARAM_STR);
		$hist->bindValue(":usuario", $usuarioId, $usuarioId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$hist->bindValue(":origen", substr((string) $origen, 0, 30), PDO::PARAM_STR);
		$hist->bindValue(":obs", $observacion, $observacion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$hist->execute();
	}

	static private function mdlFormatearAsignacion($fila, $usuarioFallback = null)
	{
		if (!$fila) {
			return null;
		}
		$usuarioId = isset($fila["usuario_actualizacion"]) && $fila["usuario_actualizacion"] !== null
			? (int) $fila["usuario_actualizacion"]
			: (isset($fila["usuario_asignacion"]) ? (int) $fila["usuario_asignacion"] : null);
		$fecha = !empty($fila["actualizado_en"]) ? $fila["actualizado_en"] : $fila["fecha_asignacion"];
		$nombreUsuario = "";
		if ($usuarioId > 0) {
			$stmt = Conexion::conectar()->prepare("SELECT nombre FROM usuariosjf WHERE id = :id LIMIT 1");
			$stmt->bindValue(":id", $usuarioId, PDO::PARAM_INT);
			$stmt->execute();
			$nombreUsuario = (string) $stmt->fetchColumn();
		}
		return array(
			"modelo" => $fila["modelo"],
			"id_subcategoria" => $fila["id_subcategoria"] !== null ? (int) $fila["id_subcategoria"] : null,
			"nombre_subcategoria" => $fila["nombre_subcategoria"],
			"codigo_subcategoria" => $fila["codigo_subcategoria"],
			"id_categoria" => $fila["id_categoria"] !== null ? (int) $fila["id_categoria"] : null,
			"nombre_categoria" => $fila["nombre_categoria"],
			"codigo_categoria" => $fila["codigo_categoria"],
			"fecha_asignacion" => $fila["fecha_asignacion"],
			"actualizado_en" => $fecha,
			"usuario_id" => $usuarioId,
			"usuario_nombre" => $nombreUsuario
		);
	}

	static public function mdlHistorial($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT h.id, h.modelo, h.accion, h.fecha, h.usuario_id, h.origen, h.observacion,
				h.id_categoria_anterior, h.id_categoria_nueva,
				h.id_subcategoria_anterior, h.id_subcategoria_nueva,
				sa.nombre AS nombre_sub_anterior, ca.nombre AS nombre_cat_anterior,
				sn.nombre AS nombre_sub_nueva, cn.nombre AS nombre_cat_nueva,
				IFNULL(u.nombre, '') AS usuario_nombre
			 FROM modelo_subcategoria_historialjf h
			 LEFT JOIN subcategoria_modelojf sa ON sa.id = h.id_subcategoria_anterior
			 LEFT JOIN subcategoria_modelojf sn ON sn.id = h.id_subcategoria_nueva
			 LEFT JOIN categoria_modelojf ca ON ca.id = COALESCE(h.id_categoria_anterior, sa.id_categoria)
			 LEFT JOIN categoria_modelojf cn ON cn.id = COALESCE(h.id_categoria_nueva, sn.id_categoria)
			 LEFT JOIN usuariosjf u ON u.id = h.usuario_id
			 WHERE h.modelo = :modelo
			 ORDER BY h.fecha DESC, h.id DESC
			 LIMIT 100"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlHistorialReciente($limite = 40, $idSubcategoria = 0)
	{
		$limite = (int) $limite;
		if ($limite < 1) {
			$limite = 40;
		}
		if ($limite > 100) {
			$limite = 100;
		}
		$sql = "SELECT h.id, h.modelo, h.accion, h.fecha, h.origen,
				h.id_categoria_anterior, h.id_categoria_nueva,
				h.id_subcategoria_anterior, h.id_subcategoria_nueva,
				sa.nombre AS nombre_sub_anterior, ca.nombre AS nombre_cat_anterior,
				sn.nombre AS nombre_sub_nueva, cn.nombre AS nombre_cat_nueva,
				IFNULL(u.nombre, '') AS usuario_nombre,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(h.modelo)) AS nombre_modelo
			 FROM modelo_subcategoria_historialjf h
			 LEFT JOIN subcategoria_modelojf sa ON sa.id = h.id_subcategoria_anterior
			 LEFT JOIN subcategoria_modelojf sn ON sn.id = h.id_subcategoria_nueva
			 LEFT JOIN categoria_modelojf ca ON ca.id = COALESCE(h.id_categoria_anterior, sa.id_categoria)
			 LEFT JOIN categoria_modelojf cn ON cn.id = COALESCE(h.id_categoria_nueva, sn.id_categoria)
			 LEFT JOIN usuariosjf u ON u.id = h.usuario_id
			 LEFT JOIN modelojf m ON TRIM(m.modelo) = h.modelo
			 WHERE 1 = 1";
		if ((int) $idSubcategoria > 0) {
			$sql .= " AND (h.id_subcategoria_nueva = :id_sub OR h.id_subcategoria_anterior = :id_sub)";
		}
		$sql .= " ORDER BY h.fecha DESC, h.id DESC LIMIT :limite";
		$stmt = Conexion::conectar()->prepare($sql);
		if ((int) $idSubcategoria > 0) {
			$stmt->bindValue(":id_sub", (int) $idSubcategoria, PDO::PARAM_INT);
		}
		$stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlListarCategoriasAdmin()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT c.*,
				(SELECT COUNT(*) FROM subcategoria_modelojf s WHERE s.id_categoria = c.id) AS total_subcategorias,
				(SELECT COUNT(*) FROM modelo_subcategoriajf ms
				 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
				   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
				 WHERE ms.id_categoria = c.id) AS modelos_activos
			 FROM categoria_modelojf c
			 ORDER BY c.orden ASC, c.nombre ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlListarSubcategoriasAdmin($idCategoria = 0)
	{
		$sql = "SELECT s.*,
				c.codigo AS codigo_categoria,
				c.nombre AS nombre_categoria,
				(SELECT COUNT(*) FROM modelo_subcategoriajf ms
				 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
				   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
				 WHERE ms.id_subcategoria = s.id) AS modelos_activos,
				(SELECT COUNT(*) FROM modelo_subcategoria_historialjf h
				 WHERE h.id_subcategoria_nueva = s.id OR h.id_subcategoria_anterior = s.id) AS total_historial
			 FROM subcategoria_modelojf s
			 INNER JOIN categoria_modelojf c ON c.id = s.id_categoria
			 WHERE 1 = 1";
		if ((int) $idCategoria > 0) {
			$sql .= " AND s.id_categoria = :id_categoria";
		}
		$sql .= " ORDER BY c.orden ASC, s.orden ASC, s.nombre ASC";
		$stmt = Conexion::conectar()->prepare($sql);
		if ((int) $idCategoria > 0) {
			$stmt->bindValue(":id_categoria", (int) $idCategoria, PDO::PARAM_INT);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlListarModelosPorCategoria($idCategoria, $soloSinSubcategoria = false)
	{
		$idCategoria = (int) $idCategoria;
		if ($idCategoria < 1) {
			return array();
		}
		$sql = "SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(mk.marca, '') AS marca,
				IFNULL(TRIM(m.tipo), '') AS tipo,
				IFNULL(TRIM(m.linea), '') AS linea,
				IFNULL(NULLIF(TRIM(m.imagen), ''), 'vistas/img/modelos/default/anonymous.png') AS imagen,
				UPPER(TRIM(IFNULL(m.estado, ''))) AS estado,
				ms.id_subcategoria,
				s.nombre AS nombre_subcategoria,
				ms.fecha_asignacion,
				ms.actualizado_en,
				IFNULL(u.nombre, '') AS usuario_nombre
			 FROM modelo_subcategoriajf ms
			 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
			 LEFT JOIN subcategoria_modelojf s ON s.id = ms.id_subcategoria
			 LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			 LEFT JOIN usuariosjf u ON u.id = COALESCE(ms.usuario_actualizacion, ms.usuario_asignacion)
			 WHERE ms.id_categoria = :id";
		if ($soloSinSubcategoria) {
			$sql .= " AND ms.id_subcategoria IS NULL";
		}
		$sql .= " ORDER BY
				CASE WHEN UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO' THEN 0 ELSE 1 END,
				CASE WHEN ms.id_subcategoria IS NULL THEN 0 ELSE 1 END,
				TRIM(m.modelo) ASC";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":id", $idCategoria, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlListarModelosPorSubcategoria($idSubcategoria)
	{
		$idSubcategoria = (int) $idSubcategoria;
		if ($idSubcategoria < 1) {
			return array();
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(mk.marca, '') AS marca,
				IFNULL(TRIM(m.tipo), '') AS tipo,
				IFNULL(TRIM(m.linea), '') AS linea,
				IFNULL(NULLIF(TRIM(m.imagen), ''), 'vistas/img/modelos/default/anonymous.png') AS imagen,
				UPPER(TRIM(IFNULL(m.estado, ''))) AS estado,
				ms.fecha_asignacion,
				ms.actualizado_en,
				IFNULL(u.nombre, '') AS usuario_nombre
			 FROM modelo_subcategoriajf ms
			 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
			 LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			 LEFT JOIN usuariosjf u ON u.id = COALESCE(ms.usuario_actualizacion, ms.usuario_asignacion)
			 WHERE ms.id_subcategoria = :id
			 ORDER BY
				CASE WHEN UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO' THEN 0 ELSE 1 END,
				TRIM(m.modelo) ASC"
		);
		$stmt->bindValue(":id", $idSubcategoria, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlCategoriaPorId($id)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM categoria_modelojf WHERE id = :id LIMIT 1"
		);
		$stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlCategoriaPorCodigo($codigo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM categoria_modelojf WHERE codigo = :codigo LIMIT 1"
		);
		$stmt->bindValue(":codigo", strtoupper(trim((string) $codigo)), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlSubcategoriaPorId($id)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM subcategoria_modelojf WHERE id = :id LIMIT 1"
		);
		$stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlSubcategoriaPorCodigo($codigo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM subcategoria_modelojf WHERE codigo = :codigo LIMIT 1"
		);
		$stmt->bindValue(":codigo", strtoupper(trim((string) $codigo)), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlContarModelosActivosSubcategoria($idSubcategoria)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*)
			 FROM modelo_subcategoriajf ms
			 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
			   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			 WHERE ms.id_subcategoria = :id"
		);
		$stmt->bindValue(":id", (int) $idSubcategoria, PDO::PARAM_INT);
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	static public function mdlContarModelosActivosCategoria($idCategoria)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*)
			 FROM modelo_subcategoriajf ms
			 INNER JOIN modelojf m ON TRIM(m.modelo) = ms.modelo
			   AND UPPER(TRIM(IFNULL(m.estado, ''))) = 'ACTIVO'
			 WHERE ms.id_categoria = :id"
		);
		$stmt->bindValue(":id", (int) $idCategoria, PDO::PARAM_INT);
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	static public function mdlGuardarCategoria($datos)
	{
		$pdo = Conexion::conectar();
		$id = isset($datos["id"]) ? (int) $datos["id"] : 0;
		$ahora = date("Y-m-d H:i:s");
		$usuario = isset($datos["usuario_id"]) ? (int) $datos["usuario_id"] : null;
		if ($usuario < 1) {
			$usuario = null;
		}

		try {
			if ($id > 0) {
				$stmt = $pdo->prepare(
					"UPDATE categoria_modelojf
					 SET nombre = :nombre,
						 orden = :orden,
						 estado = :estado,
						 actualizado_en = :ahora,
						 actualizado_por = :usuario
					 WHERE id = :id"
				);
				$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
				$stmt->bindValue(":orden", (int) $datos["orden"], PDO::PARAM_INT);
				$stmt->bindValue(":estado", (int) $datos["estado"], PDO::PARAM_INT);
				$stmt->bindValue(":ahora", $ahora, PDO::PARAM_STR);
				$stmt->bindValue(":usuario", $usuario, $usuario === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
				$stmt->bindValue(":id", $id, PDO::PARAM_INT);
				$stmt->execute();
				return "ok";
			}

			$stmt = $pdo->prepare(
				"INSERT INTO categoria_modelojf (codigo, nombre, estado, orden, creado_en, creado_por)
				 VALUES (:codigo, :nombre, :estado, :orden, :ahora, :usuario)"
			);
			$stmt->bindValue(":codigo", $datos["codigo"], PDO::PARAM_STR);
			$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
			$stmt->bindValue(":estado", (int) $datos["estado"], PDO::PARAM_INT);
			$stmt->bindValue(":orden", (int) $datos["orden"], PDO::PARAM_INT);
			$stmt->bindValue(":ahora", $ahora, PDO::PARAM_STR);
			$stmt->bindValue(":usuario", $usuario, $usuario === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
			$stmt->execute();
			return "ok";
		} catch (Exception $e) {
			return "error";
		}
	}

	static public function mdlGuardarSubcategoria($datos)
	{
		$pdo = Conexion::conectar();
		$id = isset($datos["id"]) ? (int) $datos["id"] : 0;
		$ahora = date("Y-m-d H:i:s");
		$usuario = isset($datos["usuario_id"]) ? (int) $datos["usuario_id"] : null;
		if ($usuario < 1) {
			$usuario = null;
		}

		try {
			if ($id > 0) {
				$stmt = $pdo->prepare(
					"UPDATE subcategoria_modelojf
					 SET id_categoria = :id_categoria,
						 nombre = :nombre,
						 orden = :orden,
						 estado = :estado,
						 actualizado_en = :ahora,
						 actualizado_por = :usuario
					 WHERE id = :id"
				);
				$stmt->bindValue(":id_categoria", (int) $datos["id_categoria"], PDO::PARAM_INT);
				$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
				$stmt->bindValue(":orden", (int) $datos["orden"], PDO::PARAM_INT);
				$stmt->bindValue(":estado", (int) $datos["estado"], PDO::PARAM_INT);
				$stmt->bindValue(":ahora", $ahora, PDO::PARAM_STR);
				$stmt->bindValue(":usuario", $usuario, $usuario === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
				$stmt->bindValue(":id", $id, PDO::PARAM_INT);
				$stmt->execute();
				return "ok";
			}

			$stmt = $pdo->prepare(
				"INSERT INTO subcategoria_modelojf
					(id_categoria, codigo, nombre, estado, orden, creado_en, creado_por)
				 VALUES (:id_categoria, :codigo, :nombre, :estado, :orden, :ahora, :usuario)"
			);
			$stmt->bindValue(":id_categoria", (int) $datos["id_categoria"], PDO::PARAM_INT);
			$stmt->bindValue(":codigo", $datos["codigo"], PDO::PARAM_STR);
			$stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
			$stmt->bindValue(":estado", (int) $datos["estado"], PDO::PARAM_INT);
			$stmt->bindValue(":orden", (int) $datos["orden"], PDO::PARAM_INT);
			$stmt->bindValue(":ahora", $ahora, PDO::PARAM_STR);
			$stmt->bindValue(":usuario", $usuario, $usuario === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
			$stmt->execute();
			return "ok";
		} catch (Exception $e) {
			return "error";
		}
	}

	static public function mdlContarAsignacionesSubcategoria($idSubcategoria)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*) FROM modelo_subcategoriajf WHERE id_subcategoria = :id"
		);
		$stmt->bindValue(":id", (int) $idSubcategoria, PDO::PARAM_INT);
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	static public function mdlContarHistorialSubcategoria($idSubcategoria)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*)
			 FROM modelo_subcategoria_historialjf
			 WHERE id_subcategoria_nueva = :id OR id_subcategoria_anterior = :id"
		);
		$stmt->bindValue(":id", (int) $idSubcategoria, PDO::PARAM_INT);
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	static public function mdlEliminarSubcategoria($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return "error";
		}
		try {
			$stmt = Conexion::conectar()->prepare(
				"DELETE FROM subcategoria_modelojf WHERE id = :id LIMIT 1"
			);
			$stmt->bindValue(":id", $id, PDO::PARAM_INT);
			$stmt->execute();
			return $stmt->rowCount() > 0 ? "ok" : "error";
		} catch (Exception $e) {
			return "error";
		}
	}
}
