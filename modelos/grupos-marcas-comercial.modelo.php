<?php

require_once "conexion.php";

class ModeloGruposMarcasComercial
{

	/**
	 * Condición SQL reutilizable: asignación vigente en una fecha de referencia.
	 * $aliasVgm = alias de vendedor_grupos_marcasjf; $fechaExpr = expresión SQL de la fecha del documento.
	 */
	static public function sqlAsignacionVigenteEnFecha($aliasVgm, $fechaExpr)
	{
		$aliasVgm = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $aliasVgm);
		if ($aliasVgm === "") {
			$aliasVgm = "vgm";
		}
		return "({$aliasVgm}.estado = 1"
			. " AND {$aliasVgm}.fecha_inicio <= ({$fechaExpr})"
			. " AND ({$aliasVgm}.fecha_fin IS NULL OR {$aliasVgm}.fecha_fin >= ({$fechaExpr})))";
	}

	static public function mdlExisteVendedorTvend($codVendedor)
	{
		$codVendedor = trim((string) $codVendedor);
		if ($codVendedor === "") {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM maestrajf
			 WHERE codigo = :codigo AND UPPER(tipo_dato) = 'TVEND'
			 LIMIT 1"
		);
		$stmt->bindParam(":codigo", $codVendedor, PDO::PARAM_STR);
		$stmt->execute();
		return (bool) $stmt->fetchColumn();
	}

	static public function mdlGrupoActivo($idGrupo)
	{
		$idGrupo = (int) $idGrupo;
		if ($idGrupo < 1) {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM grupos_marcas_comercialjf
			 WHERE id = :id AND estado = 1 LIMIT 1"
		);
		$stmt->bindParam(":id", $idGrupo, PDO::PARAM_INT);
		$stmt->execute();
		return (bool) $stmt->fetchColumn();
	}

	static public function mdlListarGrupos($soloActivas = false)
	{
		$sql = "SELECT g.*,
				(SELECT COUNT(*) FROM grupos_marcas_detallejf d WHERE d.id_grupo_marca = g.id) AS total_marcas,
				(SELECT COUNT(DISTINCT mo.modelo)
				 FROM grupos_marcas_detallejf d
				 INNER JOIN modelojf mo ON mo.id_marca = d.id_marca
				 WHERE d.id_grupo_marca = g.id
				   AND TRIM(IFNULL(mo.modelo, '')) <> ''
				   AND UPPER(TRIM(IFNULL(mo.estado, ''))) = 'ACTIVO') AS total_modelos,
				(SELECT GROUP_CONCAT(m.marca ORDER BY m.marca SEPARATOR ', ')
				 FROM grupos_marcas_detallejf d
				 INNER JOIN marcasjf m ON m.id = d.id_marca
				 WHERE d.id_grupo_marca = g.id) AS marcas_texto
			FROM grupos_marcas_comercialjf g";
		if ($soloActivas) {
			$sql .= " WHERE g.estado = 1";
		}
		$sql .= " ORDER BY g.codigo ASC, g.nombre ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlMostrarGrupo($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM grupos_marcas_comercialjf WHERE id = :id LIMIT 1"
		);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch();
	}

	static public function mdlGrupoPorCodigo($codigo)
	{
		$codigo = strtoupper(trim((string) $codigo));
		if ($codigo === "") {
			return null;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM grupos_marcas_comercialjf WHERE codigo = :codigo LIMIT 1"
		);
		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch();
	}

	static public function mdlCrearGrupo($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO grupos_marcas_comercialjf
				(codigo, nombre, descripcion, estado, usureg, fecreg)
			 VALUES
				(:codigo, :nombre, :descripcion, :estado, :usureg, NOW())"
		);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEditarGrupo($datos)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE grupos_marcas_comercialjf
			 SET nombre = :nombre,
				 descripcion = :descripcion,
				 estado = :estado,
				 usumod = :usumod,
				 fecmod = NOW()
			 WHERE id = :id"
		);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlCambiarEstadoGrupo($id, $estado, $usuario)
	{
		$stmt = Conexion::conectar()->prepare(
			"UPDATE grupos_marcas_comercialjf
			 SET estado = :estado, usumod = :usumod, fecmod = NOW()
			 WHERE id = :id"
		);
		$stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
		$stmt->bindParam(":usumod", $usuario, PDO::PARAM_STR);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlListarMarcasCatalogo()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT id, marca FROM marcasjf ORDER BY marca ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlListarMarcasGrupo($idGrupo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT d.id, d.id_marca, d.fecreg,
				m.marca
			 FROM grupos_marcas_detallejf d
			 INNER JOIN marcasjf m ON m.id = d.id_marca
			 WHERE d.id_grupo_marca = :id_grupo
			 ORDER BY m.marca ASC"
		);
		$stmt->bindParam(":id_grupo", $idGrupo, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlMarcaExiste($idMarca)
	{
		$idMarca = (int) $idMarca;
		if ($idMarca < 1) {
			return false;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT 1 FROM marcasjf WHERE id = :id LIMIT 1"
		);
		$stmt->bindParam(":id", $idMarca, PDO::PARAM_INT);
		$stmt->execute();
		return (bool) $stmt->fetchColumn();
	}

	static public function mdlAgregarMarcaGrupo($idGrupo, $idMarca, $usuario)
	{
		$stmt = Conexion::conectar()->prepare(
			"INSERT IGNORE INTO grupos_marcas_detallejf (id_grupo_marca, id_marca, usureg, fecreg)
			 VALUES (:id_grupo, :id_marca, :usureg, NOW())"
		);
		$stmt->bindParam(":id_grupo", $idGrupo, PDO::PARAM_INT);
		$stmt->bindParam(":id_marca", $idMarca, PDO::PARAM_INT);
		$stmt->bindParam(":usureg", $usuario, PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlQuitarMarcaGrupo($idDetalle)
	{
		$stmt = Conexion::conectar()->prepare(
			"DELETE FROM grupos_marcas_detallejf WHERE id = :id"
		);
		$stmt->bindParam(":id", $idDetalle, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlListarVendedoresTvend()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT m.codigo, m.descripcion AS nombre
			 FROM maestrajf m
			 WHERE UPPER(m.tipo_dato) = 'TVEND'
			 ORDER BY m.codigo ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/**
	 * Detecta solapamiento de vigencias para la misma pareja vendedor–grupo.
	 */
	static public function mdlHaySolapamientoAsignacion($codVendedor, $idGrupo, $fechaInicio, $fechaFin, $excluirId = 0)
	{
		$codVendedor = trim((string) $codVendedor);
		$idGrupo = (int) $idGrupo;
		$excluirId = (int) $excluirId;
		$finNueva = $fechaFin !== null && $fechaFin !== "" ? $fechaFin : "9999-12-31";

		$sql = "SELECT COUNT(*) FROM vendedor_grupos_marcasjf v
			WHERE v.cod_vendedor = :cod_vendedor
			  AND v.id_grupo_marca = :id_grupo
			  AND v.estado = 1";
		if ($excluirId > 0) {
			$sql .= " AND v.id <> :excluir_id";
		}
		$sql .= " AND v.fecha_inicio <= :fin_nueva
			  AND IFNULL(v.fecha_fin, '9999-12-31') >= :fecha_inicio";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":id_grupo", $idGrupo, PDO::PARAM_INT);
		$stmt->bindParam(":fin_nueva", $finNueva, PDO::PARAM_STR);
		$stmt->bindParam(":fecha_inicio", $fechaInicio, PDO::PARAM_STR);
		if ($excluirId > 0) {
			$stmt->bindParam(":excluir_id", $excluirId, PDO::PARAM_INT);
		}
		$stmt->execute();
		return ((int) $stmt->fetchColumn()) > 0;
	}

	static public function mdlListarAsignaciones($filtros = array())
	{
		$codVendedor = isset($filtros["cod_vendedor"]) ? trim((string) $filtros["cod_vendedor"]) : "";
		$idGrupo = isset($filtros["id_grupo"]) ? (int) $filtros["id_grupo"] : 0;
		$idMarca = isset($filtros["id_marca"]) ? (int) $filtros["id_marca"] : 0;
		$fechaRef = isset($filtros["fecha_ref"]) ? trim((string) $filtros["fecha_ref"]) : date("Y-m-d");
		$vigente = isset($filtros["vigente"]) ? trim((string) $filtros["vigente"]) : "";

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRef)) {
			$fechaRef = date("Y-m-d");
		}

		$sql = "SELECT vgm.*,
				g.codigo AS codigo_grupo,
				g.nombre AS nombre_grupo,
				IFNULL(mv.descripcion, vgm.cod_vendedor) AS nombre_vendedor,
				(SELECT GROUP_CONCAT(m.marca ORDER BY m.marca SEPARATOR ', ')
				 FROM grupos_marcas_detallejf d
				 INNER JOIN marcasjf m ON m.id = d.id_marca
				 WHERE d.id_grupo_marca = g.id) AS marcas_texto,
				CASE
					WHEN vgm.estado = 1
					 AND vgm.fecha_inicio <= :fecha_ref
					 AND (vgm.fecha_fin IS NULL OR vgm.fecha_fin >= :fecha_ref2)
					THEN 1 ELSE 0
				END AS es_vigente
			FROM vendedor_grupos_marcasjf vgm
			INNER JOIN grupos_marcas_comercialjf g ON g.id = vgm.id_grupo_marca
			LEFT JOIN maestrajf mv
			  ON mv.codigo = vgm.cod_vendedor AND UPPER(mv.tipo_dato) = 'TVEND'
			WHERE 1=1";

		if ($codVendedor !== "") {
			$sql .= " AND vgm.cod_vendedor = :cod_vendedor";
		}
		if ($idGrupo > 0) {
			$sql .= " AND vgm.id_grupo_marca = :id_grupo";
		}
		if ($idMarca > 0) {
			$sql .= " AND EXISTS (
				SELECT 1 FROM grupos_marcas_detallejf d
				WHERE d.id_grupo_marca = g.id AND d.id_marca = :id_marca
			)";
		}
		if ($vigente === "1") {
			$sql .= " AND vgm.estado = 1
				AND vgm.fecha_inicio <= :fecha_ref_v
				AND (vgm.fecha_fin IS NULL OR vgm.fecha_fin >= :fecha_ref_v2)";
		} elseif ($vigente === "0") {
			$sql .= " AND NOT (
				vgm.estado = 1
				AND vgm.fecha_inicio <= :fecha_ref_nv
				AND (vgm.fecha_fin IS NULL OR vgm.fecha_fin >= :fecha_ref_nv2)
			)";
		}

		$sql .= " ORDER BY vgm.cod_vendedor ASC, g.codigo ASC, vgm.fecha_inicio DESC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":fecha_ref", $fechaRef, PDO::PARAM_STR);
		$stmt->bindParam(":fecha_ref2", $fechaRef, PDO::PARAM_STR);
		if ($codVendedor !== "") {
			$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
		}
		if ($idGrupo > 0) {
			$stmt->bindParam(":id_grupo", $idGrupo, PDO::PARAM_INT);
		}
		if ($idMarca > 0) {
			$stmt->bindParam(":id_marca", $idMarca, PDO::PARAM_INT);
		}
		if ($vigente === "1") {
			$stmt->bindParam(":fecha_ref_v", $fechaRef, PDO::PARAM_STR);
			$stmt->bindParam(":fecha_ref_v2", $fechaRef, PDO::PARAM_STR);
		} elseif ($vigente === "0") {
			$stmt->bindParam(":fecha_ref_nv", $fechaRef, PDO::PARAM_STR);
			$stmt->bindParam(":fecha_ref_nv2", $fechaRef, PDO::PARAM_STR);
		}

		$stmt->execute();
		return $stmt->fetchAll();
	}

	static public function mdlMostrarAsignacion($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT vgm.*, g.codigo AS codigo_grupo, g.nombre AS nombre_grupo
			 FROM vendedor_grupos_marcasjf vgm
			 INNER JOIN grupos_marcas_comercialjf g ON g.id = vgm.id_grupo_marca
			 WHERE vgm.id = :id LIMIT 1"
		);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch();
	}

	static public function mdlCrearAsignacionesLote($codVendedor, $idsGrupos, $fechaInicio, $observacion, $usuario)
	{
		$codVendedor = trim((string) $codVendedor);
		$fechaInicio = trim((string) $fechaInicio);
		$observacion = trim((string) $observacion);
		if ($codVendedor === "" || $fechaInicio === "" || !is_array($idsGrupos) || !count($idsGrupos)) {
			return array("ok" => false, "mensaje" => "Datos incompletos");
		}
		if (!self::mdlExisteVendedorTvend($codVendedor)) {
			return array("ok" => false, "mensaje" => "Vendedor no válido (TVEND)");
		}

		$pdo = Conexion::conectar();
		try {
			$pdo->beginTransaction();
			$creadas = 0;
			foreach ($idsGrupos as $idGrupo) {
				$idGrupo = (int) $idGrupo;
				if ($idGrupo < 1) {
					continue;
				}
				if (!self::mdlGrupoActivo($idGrupo)) {
					$pdo->rollBack();
					return array("ok" => false, "mensaje" => "Grupo inactivo o inexistente (id " . $idGrupo . ")");
				}
				if (self::mdlHaySolapamientoAsignacion($codVendedor, $idGrupo, $fechaInicio, null)) {
					$pdo->rollBack();
					return array(
						"ok" => false,
						"mensaje" => "Ya existe una asignación vigente o solapada para el vendedor y el grupo id " . $idGrupo
					);
				}

				$stmt = $pdo->prepare(
					"INSERT INTO vendedor_grupos_marcasjf
						(cod_vendedor, id_grupo_marca, fecha_inicio, fecha_fin, estado, observacion, usureg, fecreg)
					 VALUES
						(:cod_vendedor, :id_grupo, :fecha_inicio, NULL, 1, :observacion, :usureg, NOW())"
				);
				$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
				$stmt->bindParam(":id_grupo", $idGrupo, PDO::PARAM_INT);
				$stmt->bindParam(":fecha_inicio", $fechaInicio, PDO::PARAM_STR);
				$stmt->bindParam(":observacion", $observacion, PDO::PARAM_STR);
				$stmt->bindParam(":usureg", $usuario, PDO::PARAM_STR);
				if (!$stmt->execute()) {
					$pdo->rollBack();
					return array("ok" => false, "mensaje" => "Error al crear asignación");
				}
				$creadas++;
			}
			if ($creadas === 0) {
				$pdo->rollBack();
				return array("ok" => false, "mensaje" => "No se seleccionaron grupos válidos");
			}
			$pdo->commit();
			return array("ok" => true, "mensaje" => "Se crearon " . $creadas . " asignación(es)", "creadas" => $creadas);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return array("ok" => false, "mensaje" => "Error de transacción: " . $e->getMessage());
		}
	}

	static public function mdlCerrarAsignacion($id, $fechaFin, $usuario)
	{
		$id = (int) $id;
		$fechaFin = trim((string) $fechaFin);
		if ($id < 1 || $fechaFin === "") {
			return array("ok" => false, "mensaje" => "Datos incompletos");
		}

		$asig = self::mdlMostrarAsignacion($id);
		if (!$asig) {
			return array("ok" => false, "mensaje" => "Asignación no encontrada");
		}
		if ($asig["fecha_fin"] !== null && $asig["fecha_fin"] !== "") {
			return array("ok" => false, "mensaje" => "La asignación ya está cerrada");
		}
		if ($fechaFin < $asig["fecha_inicio"]) {
			return array("ok" => false, "mensaje" => "La fecha de fin no puede ser anterior al inicio");
		}

		$stmt = Conexion::conectar()->prepare(
			"UPDATE vendedor_grupos_marcasjf
			 SET fecha_fin = :fecha_fin, usumod = :usumod, fecmod = NOW()
			 WHERE id = :id AND fecha_fin IS NULL"
		);
		$stmt->bindParam(":fecha_fin", $fechaFin, PDO::PARAM_STR);
		$stmt->bindParam(":usumod", $usuario, PDO::PARAM_STR);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		if (!$stmt->execute() || $stmt->rowCount() < 1) {
			return array("ok" => false, "mensaje" => "No se pudo cerrar la asignación");
		}
		return array("ok" => true, "mensaje" => "Asignación cerrada hasta " . $fechaFin);
	}

	static public function mdlMarcasVigentesPorVendedor($codVendedor, $fechaRef = null)
	{
		$codVendedor = trim((string) $codVendedor);
		if ($codVendedor === "") {
			return array();
		}
		if ($fechaRef === null || $fechaRef === "") {
			$fechaRef = date("Y-m-d");
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT m.id, m.marca, g.codigo AS codigo_grupo, g.nombre AS nombre_grupo
			 FROM vendedor_grupos_marcasjf vgm
			 INNER JOIN grupos_marcas_comercialjf g
			   ON g.id = vgm.id_grupo_marca AND g.estado = 1
			 INNER JOIN grupos_marcas_detallejf d ON d.id_grupo_marca = g.id
			 INNER JOIN marcasjf m ON m.id = d.id_marca
			 WHERE vgm.cod_vendedor = :cod_vendedor
			   AND " . self::sqlAsignacionVigenteEnFecha("vgm", ":fecha_ref") . "
			 ORDER BY g.codigo, m.marca"
		);
		$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":fecha_ref", $fechaRef, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/**
	 * Universo de modelos activos (modelojf.estado=Activo) en marcas de grupos
	 * vigentes del vendedor en $fechaRef.
	 */
	static public function mdlUniversoModelosActivosPorVendedor($codVendedor, $fechaRef = null)
	{
		$codVendedor = trim((string) $codVendedor);
		if ($codVendedor === "") {
			return 0;
		}
		if ($fechaRef === null || $fechaRef === "") {
			$fechaRef = date("Y-m-d");
		}

		$vigencia = self::sqlAsignacionVigenteEnFecha("vgm", ":fecha_ref");
		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(DISTINCT mo.modelo) AS total
			 FROM vendedor_grupos_marcasjf vgm
			 INNER JOIN grupos_marcas_comercialjf g
			   ON g.id = vgm.id_grupo_marca AND g.estado = 1
			 INNER JOIN grupos_marcas_detallejf d
			   ON d.id_grupo_marca = g.id
			 INNER JOIN modelojf mo
			   ON mo.id_marca = d.id_marca
			  AND TRIM(IFNULL(mo.modelo, '')) <> ''
			  AND UPPER(TRIM(IFNULL(mo.estado, ''))) = 'ACTIVO'
			 WHERE TRIM(vgm.cod_vendedor) = :cod_vendedor
			   AND {$vigencia}"
		);
		$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":fecha_ref", $fechaRef, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ? (int) $row["total"] : 0;
	}

	/**
	 * ¿La marca está en un grupo vigente del vendedor en la fecha?
	 * Retorna array: ok (bool cobertura), motivo, id_marca, marca, modelo.
	 */
	static public function mdlVerificarCoberturaArticulo($codVendedor, $articulo, $fechaRef = null)
	{
		$codVendedor = trim((string) $codVendedor);
		$articulo = trim((string) $articulo);
		if ($fechaRef === null || $fechaRef === "") {
			$fechaRef = date("Y-m-d");
		}
		if ($codVendedor === "" || $articulo === "") {
			return array(
				"ok" => false,
				"motivo" => "Datos incompletos",
				"id_marca" => null,
				"marca" => null,
				"modelo" => null
			);
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT a.articulo, a.modelo, a.id_marca, m.marca
			 FROM articulojf a
			 LEFT JOIN marcasjf m ON m.id = a.id_marca
			 WHERE a.articulo = :articulo
			 LIMIT 1"
		);
		$stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);
		$stmt->execute();
		$art = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$art) {
			return array(
				"ok" => false,
				"motivo" => "Artículo no encontrado",
				"id_marca" => null,
				"marca" => null,
				"modelo" => null
			);
		}

		return self::mdlVerificarCoberturaMarca(
			$codVendedor,
			isset($art["id_marca"]) ? (int) $art["id_marca"] : 0,
			$fechaRef,
			isset($art["marca"]) ? $art["marca"] : null,
			isset($art["modelo"]) ? $art["modelo"] : null
		);
	}

	static public function mdlVerificarCoberturaModelo($codVendedor, $modelo, $fechaRef = null)
	{
		$codVendedor = trim((string) $codVendedor);
		$modelo = trim((string) $modelo);
		if ($fechaRef === null || $fechaRef === "") {
			$fechaRef = date("Y-m-d");
		}
		if ($codVendedor === "" || $modelo === "") {
			return array(
				"ok" => false,
				"motivo" => "Datos incompletos",
				"id_marca" => null,
				"marca" => null,
				"modelo" => $modelo
			);
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT mo.modelo, mo.id_marca, m.marca
			 FROM modelojf mo
			 LEFT JOIN marcasjf m ON m.id = mo.id_marca
			 WHERE TRIM(mo.modelo) = :modelo
			 LIMIT 1"
		);
		$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			// Fallback: primera línea de artículo del modelo
			$stmt = Conexion::conectar()->prepare(
				"SELECT a.modelo, a.id_marca, m.marca
				 FROM articulojf a
				 LEFT JOIN marcasjf m ON m.id = a.id_marca
				 WHERE TRIM(a.modelo) = :modelo
				 LIMIT 1"
			);
			$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
		}

		if (!$row) {
			return array(
				"ok" => false,
				"motivo" => "Modelo no encontrado",
				"id_marca" => null,
				"marca" => null,
				"modelo" => $modelo
			);
		}

		return self::mdlVerificarCoberturaMarca(
			$codVendedor,
			isset($row["id_marca"]) ? (int) $row["id_marca"] : 0,
			$fechaRef,
			isset($row["marca"]) ? $row["marca"] : null,
			isset($row["modelo"]) ? $row["modelo"] : $modelo
		);
	}

	static public function mdlVerificarCoberturaMarca($codVendedor, $idMarca, $fechaRef = null, $nombreMarca = null, $modelo = null)
	{
		$codVendedor = trim((string) $codVendedor);
		$idMarca = (int) $idMarca;
		if ($fechaRef === null || $fechaRef === "") {
			$fechaRef = date("Y-m-d");
		}

		if ($idMarca < 1) {
			return array(
				"ok" => false,
				"motivo" => "sin_marca",
				"mensaje" => "El artículo/modelo no tiene marca asignada.",
				"id_marca" => null,
				"marca" => $nombreMarca,
				"modelo" => $modelo
			);
		}

		$vigencia = self::sqlAsignacionVigenteEnFecha("vgm", ":fecha_ref");
		$stmt = Conexion::conectar()->prepare(
			"SELECT g.codigo, g.nombre
			 FROM vendedor_grupos_marcasjf vgm
			 INNER JOIN grupos_marcas_comercialjf g
			   ON g.id = vgm.id_grupo_marca AND g.estado = 1
			 INNER JOIN grupos_marcas_detallejf d
			   ON d.id_grupo_marca = g.id
			 WHERE TRIM(vgm.cod_vendedor) = :cod_vendedor
			   AND d.id_marca = :id_marca
			   AND {$vigencia}
			 LIMIT 1"
		);
		$stmt->bindParam(":cod_vendedor", $codVendedor, PDO::PARAM_STR);
		$stmt->bindParam(":id_marca", $idMarca, PDO::PARAM_INT);
		$stmt->bindParam(":fecha_ref", $fechaRef, PDO::PARAM_STR);
		$stmt->execute();
		$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($grupo) {
			return array(
				"ok" => true,
				"motivo" => "permitida",
				"mensaje" => null,
				"id_marca" => $idMarca,
				"marca" => $nombreMarca,
				"modelo" => $modelo,
				"grupo" => $grupo["codigo"] . " — " . $grupo["nombre"]
			);
		}

		$etiquetaMarca = $nombreMarca !== null && $nombreMarca !== ""
			? $nombreMarca
			: ("id " . $idMarca);

		return array(
			"ok" => false,
			"motivo" => "fuera_cobertura",
			"mensaje" => "La marca " . $etiquetaMarca
				. ($modelo ? " (modelo " . $modelo . ")" : "")
				. " no está en un grupo vigente del vendedor " . $codVendedor . ".",
			"id_marca" => $idMarca,
			"marca" => $nombreMarca,
			"modelo" => $modelo
		);
	}
}
