<?php

require_once "conexion.php";

class ModeloCostosModeloMensual
{
	static public function mdlListarCostosPeriodo($anio, $mes, $idMarca = 0, $estado = "")
	{
		$sql = "SELECT
				TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca,
				c.id,
				c.costo_unitario,
				c.fuente,
				c.observacion,
				c.estado,
				c.usuario_registro,
				c.fecha_registro,
				c.usuario_modificacion,
				c.fecha_modificacion
			FROM modelojf m
			LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			LEFT JOIN costos_modelo_mensualjf c
				ON c.modelo = TRIM(m.modelo)
				AND c.anio = :anio
				AND c.mes = :mes
			WHERE LOWER(TRIM(IFNULL(m.estado, ''))) = 'activo'
			  AND TRIM(IFNULL(m.modelo, '')) <> ''";

		if ((int) $idMarca > 0) {
			$sql .= " AND m.id_marca = :id_marca";
		}
		if ($estado === "sin_costo") {
			$sql .= " AND c.id IS NULL";
		} elseif ($estado === "sin_aprobado") {
			$sql .= " AND (c.id IS NULL OR c.estado <> 'aprobado')";
		} elseif (in_array($estado, array("borrador", "aprobado", "anulado"), true)) {
			$sql .= " AND c.estado = :estado";
		}
		$sql .= " ORDER BY m.modelo ASC, mk.marca ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		if ((int) $idMarca > 0) {
			$stmt->bindValue(":id_marca", (int) $idMarca, PDO::PARAM_INT);
		}
		if (in_array($estado, array("borrador", "aprobado", "anulado"), true)) {
			$stmt->bindValue(":estado", $estado, PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlListarMarcas()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT mk.id, mk.marca
			 FROM modelojf m
			 INNER JOIN marcasjf mk ON mk.id = m.id_marca
			 WHERE LOWER(TRIM(IFNULL(m.estado, ''))) = 'activo'
			   AND TRIM(IFNULL(m.modelo, '')) <> ''
			 ORDER BY mk.marca ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlModeloActivo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(m.modelo) AS modelo,
				IFNULL(NULLIF(TRIM(m.nombre), ''), TRIM(m.modelo)) AS nombre,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca
			 FROM modelojf m
			 LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			 WHERE TRIM(m.modelo) = :modelo
			   AND LOWER(TRIM(IFNULL(m.estado, ''))) = 'activo'
			 LIMIT 1"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlGuardarBorrador($datos)
	{
		$pdo = Conexion::conectar();

		try {
			$pdo->beginTransaction();

			$modeloStmt = $pdo->prepare(
				"SELECT TRIM(modelo)
				 FROM modelojf
				 WHERE TRIM(modelo) = :modelo
				   AND LOWER(TRIM(IFNULL(estado, ''))) = 'activo'
				 LIMIT 1"
			);
			$modeloStmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
			$modeloStmt->execute();
			if (!$modeloStmt->fetchColumn()) {
				$pdo->rollBack();
				return "modelo_invalido";
			}

			$actualStmt = $pdo->prepare(
				"SELECT *
				 FROM costos_modelo_mensualjf
				 WHERE modelo = :modelo AND anio = :anio AND mes = :mes
				 LIMIT 1
				 FOR UPDATE"
			);
			$actualStmt->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
			$actualStmt->bindValue(":anio", $datos["anio"], PDO::PARAM_INT);
			$actualStmt->bindValue(":mes", $datos["mes"], PDO::PARAM_INT);
			$actualStmt->execute();
			$actual = $actualStmt->fetch(PDO::FETCH_ASSOC);
			$id = 0;

			if ($actual && $actual["estado"] !== "borrador") {
				$pdo->rollBack();
				return "bloqueado";
			}

			if ($actual) {
				$id = (int) $actual["id"];
				$guardar = $pdo->prepare(
					"UPDATE costos_modelo_mensualjf
					 SET costo_unitario = :costo,
						 fuente = :fuente,
						 observacion = :observacion,
						 usuario_modificacion = :usuario,
						 fecha_modificacion = NOW()
					 WHERE id = :id"
				);
				$guardar->bindValue(":id", $id, PDO::PARAM_INT);
				$accion = "modificado";
			} else {
				$guardar = $pdo->prepare(
					"INSERT INTO costos_modelo_mensualjf
						(modelo, anio, mes, costo_unitario, fuente, observacion, estado,
						 usuario_registro, fecha_registro)
					 VALUES
						(:modelo, :anio, :mes, :costo, :fuente, :observacion, 'borrador',
						 :usuario, NOW())"
				);
				$guardar->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
				$guardar->bindValue(":anio", $datos["anio"], PDO::PARAM_INT);
				$guardar->bindValue(":mes", $datos["mes"], PDO::PARAM_INT);
				$accion = "creado";
			}

			$guardar->bindValue(":costo", $datos["costo_unitario"], PDO::PARAM_STR);
			$guardar->bindValue(":fuente", $datos["fuente"], $datos["fuente"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$guardar->bindValue(":observacion", $datos["observacion"], $datos["observacion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$guardar->bindValue(":usuario", $datos["usuario"], PDO::PARAM_INT);
			if (!$guardar->execute()) {
				throw new Exception("No se pudo guardar el costo");
			}

			if (!$actual) {
				$id = (int) $pdo->lastInsertId();
			}

			$historial = $pdo->prepare(
				"INSERT INTO costos_modelo_mensual_historialjf
					(costo_modelo_id, modelo, anio, mes, costo_unitario, fuente,
					 observacion, estado, accion, motivo, usuario, fecha)
				 VALUES
					(:id, :modelo, :anio, :mes, :costo, :fuente,
					 :observacion, 'borrador', :accion, NULL, :usuario, NOW())"
			);
			$historial->bindValue(":id", $id, PDO::PARAM_INT);
			$historial->bindValue(":modelo", $datos["modelo"], PDO::PARAM_STR);
			$historial->bindValue(":anio", $datos["anio"], PDO::PARAM_INT);
			$historial->bindValue(":mes", $datos["mes"], PDO::PARAM_INT);
			$historial->bindValue(":costo", $datos["costo_unitario"], PDO::PARAM_STR);
			$historial->bindValue(":fuente", $datos["fuente"], $datos["fuente"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$historial->bindValue(":observacion", $datos["observacion"], $datos["observacion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
			$historial->bindValue(":accion", $accion, PDO::PARAM_STR);
			$historial->bindValue(":usuario", $datos["usuario"], PDO::PARAM_INT);
			if (!$historial->execute()) {
				throw new Exception("No se pudo auditar el costo");
			}

			$pdo->commit();
			return "ok";
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return "error";
		}
	}

	static public function mdlModelosActivosPorCodigos($modelos)
	{
		$modelos = array_values(array_unique(array_filter(array_map("trim", $modelos))));
		if (empty($modelos)) {
			return array();
		}

		$marcadores = array();
		foreach ($modelos as $indice => $modelo) {
			$marcadores[] = ":modelo_" . $indice;
		}
		$stmt = Conexion::conectar()->prepare(
			"SELECT TRIM(modelo) AS modelo
			 FROM modelojf
			 WHERE LOWER(TRIM(IFNULL(estado, ''))) = 'activo'
			   AND TRIM(modelo) IN (" . implode(", ", $marcadores) . ")"
		);
		foreach ($modelos as $indice => $modelo) {
			$stmt->bindValue(":modelo_" . $indice, $modelo, PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	static public function mdlGuardarBorradoresMasivo($filas, $usuario)
	{
		$pdo = Conexion::conectar();

		try {
			$pdo->beginTransaction();
			$buscar = $pdo->prepare(
				"SELECT * FROM costos_modelo_mensualjf
				 WHERE modelo = :modelo AND anio = :anio AND mes = :mes
				 LIMIT 1 FOR UPDATE"
			);
			$insertar = $pdo->prepare(
				"INSERT INTO costos_modelo_mensualjf
					(modelo, anio, mes, costo_unitario, fuente, observacion, estado,
					 usuario_registro, fecha_registro)
				 VALUES
					(:modelo, :anio, :mes, :costo, :fuente, :observacion, 'borrador',
					 :usuario, NOW())"
			);
			$actualizar = $pdo->prepare(
				"UPDATE costos_modelo_mensualjf
				 SET costo_unitario = :costo,
					 fuente = :fuente,
					 observacion = :observacion,
					 usuario_modificacion = :usuario,
					 fecha_modificacion = NOW()
				 WHERE id = :id"
			);
			$historial = $pdo->prepare(
				"INSERT INTO costos_modelo_mensual_historialjf
					(costo_modelo_id, modelo, anio, mes, costo_unitario, fuente,
					 observacion, estado, accion, motivo, usuario, fecha)
				 VALUES
					(:id, :modelo, :anio, :mes, :costo, :fuente,
					 :observacion, 'borrador', :accion, NULL, :usuario, NOW())"
			);

			foreach ($filas as $fila) {
				$buscar->execute(array(
					":modelo" => $fila["modelo"],
					":anio" => $fila["anio"],
					":mes" => $fila["mes"]
				));
				$actual = $buscar->fetch(PDO::FETCH_ASSOC);
				if ($actual && $actual["estado"] !== "borrador") {
					$pdo->rollBack();
					return "bloqueado";
				}

				if ($actual) {
					$id = (int) $actual["id"];
					$actualizar->bindValue(":id", $id, PDO::PARAM_INT);
					$actualizar->bindValue(":costo", $fila["costo_unitario"], PDO::PARAM_STR);
					$actualizar->bindValue(":fuente", $fila["fuente"], $fila["fuente"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$actualizar->bindValue(":observacion", $fila["observacion"], $fila["observacion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$actualizar->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
					if (!$actualizar->execute()) {
						throw new Exception("No se pudo actualizar el costo");
					}
					$accion = "modificado";
				} else {
					$insertar->bindValue(":modelo", $fila["modelo"], PDO::PARAM_STR);
					$insertar->bindValue(":anio", $fila["anio"], PDO::PARAM_INT);
					$insertar->bindValue(":mes", $fila["mes"], PDO::PARAM_INT);
					$insertar->bindValue(":costo", $fila["costo_unitario"], PDO::PARAM_STR);
					$insertar->bindValue(":fuente", $fila["fuente"], $fila["fuente"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$insertar->bindValue(":observacion", $fila["observacion"], $fila["observacion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
					$insertar->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
					if (!$insertar->execute()) {
						throw new Exception("No se pudo insertar el costo");
					}
					$id = (int) $pdo->lastInsertId();
					$accion = "creado";
				}

				$historial->bindValue(":id", $id, PDO::PARAM_INT);
				$historial->bindValue(":modelo", $fila["modelo"], PDO::PARAM_STR);
				$historial->bindValue(":anio", $fila["anio"], PDO::PARAM_INT);
				$historial->bindValue(":mes", $fila["mes"], PDO::PARAM_INT);
				$historial->bindValue(":costo", $fila["costo_unitario"], PDO::PARAM_STR);
				$historial->bindValue(":fuente", $fila["fuente"], $fila["fuente"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$historial->bindValue(":observacion", $fila["observacion"], $fila["observacion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$historial->bindValue(":accion", $accion, PDO::PARAM_STR);
				$historial->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
				if (!$historial->execute()) {
					throw new Exception("No se pudo auditar el costo");
				}
			}

			$pdo->commit();
			return "ok";
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return "error";
		}
	}

	static public function mdlCambiarEstado($ids, $accion, $motivo, $usuario)
	{
		$ids = array_values(array_unique(array_map("intval", $ids)));
		sort($ids, SORT_NUMERIC);
		if (empty($ids)) {
			return "sin_registros";
		}

		$transiciones = array(
			"aprobar" => array("borrador"),
			"anular" => array("borrador", "aprobado"),
			"reabrir" => array("aprobado", "anulado")
		);
		if (!isset($transiciones[$accion])) {
			return "accion_invalida";
		}

		$pdo = Conexion::conectar();
		try {
			$pdo->beginTransaction();
			$buscar = $pdo->prepare(
				"SELECT * FROM costos_modelo_mensualjf WHERE id = :id LIMIT 1 FOR UPDATE"
			);
			$historial = $pdo->prepare(
				"INSERT INTO costos_modelo_mensual_historialjf
					(costo_modelo_id, modelo, anio, mes, costo_unitario, fuente,
					 observacion, estado, accion, motivo, usuario, fecha)
				 VALUES
					(:id, :modelo, :anio, :mes, :costo, :fuente,
					 :observacion, :estado, :accion, :motivo, :usuario, NOW())"
			);

			foreach ($ids as $id) {
				if ($id < 1) {
					$pdo->rollBack();
					return "sin_registros";
				}
				$buscar->execute(array(":id" => $id));
				$actual = $buscar->fetch(PDO::FETCH_ASSOC);
				if (!$actual || !in_array($actual["estado"], $transiciones[$accion], true)) {
					$pdo->rollBack();
					return "estado_invalido";
				}

				if ($accion === "aprobar") {
					$nuevoEstado = "aprobado";
					$actualizar = $pdo->prepare(
						"UPDATE costos_modelo_mensualjf
						 SET estado = 'aprobado',
							 usuario_aprobacion = :usuario,
							 fecha_aprobacion = NOW(),
							 usuario_modificacion = :usuario,
							 fecha_modificacion = NOW()
						 WHERE id = :id"
					);
				} elseif ($accion === "anular") {
					$nuevoEstado = "anulado";
					$actualizar = $pdo->prepare(
						"UPDATE costos_modelo_mensualjf
						 SET estado = 'anulado',
							 usuario_modificacion = :usuario,
							 fecha_modificacion = NOW()
						 WHERE id = :id"
					);
				} else {
					$nuevoEstado = "borrador";
					$actualizar = $pdo->prepare(
						"UPDATE costos_modelo_mensualjf
						 SET estado = 'borrador',
							 usuario_aprobacion = NULL,
							 fecha_aprobacion = NULL,
							 usuario_modificacion = :usuario,
							 fecha_modificacion = NOW()
						 WHERE id = :id"
					);
				}
				if (!$actualizar->execute(array(":usuario" => (int) $usuario, ":id" => $id))) {
					throw new Exception("No se pudo cambiar el estado");
				}

				$historial->bindValue(":id", $id, PDO::PARAM_INT);
				$historial->bindValue(":modelo", $actual["modelo"], PDO::PARAM_STR);
				$historial->bindValue(":anio", (int) $actual["anio"], PDO::PARAM_INT);
				$historial->bindValue(":mes", (int) $actual["mes"], PDO::PARAM_INT);
				$historial->bindValue(":costo", $actual["costo_unitario"], PDO::PARAM_STR);
				$historial->bindValue(":fuente", $actual["fuente"], $actual["fuente"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$historial->bindValue(":observacion", $actual["observacion"], $actual["observacion"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$historial->bindValue(":estado", $nuevoEstado, PDO::PARAM_STR);
				$accionHistorial = $accion === "aprobar"
					? "aprobado"
					: ($accion === "anular" ? "anulado" : "reabierto");
				$historial->bindValue(":accion", $accionHistorial, PDO::PARAM_STR);
				$historial->bindValue(":motivo", $motivo, $motivo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
				$historial->bindValue(":usuario", (int) $usuario, PDO::PARAM_INT);
				if (!$historial->execute()) {
					throw new Exception("No se pudo auditar el cambio");
				}
			}

			$pdo->commit();
			return "ok";
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			return "error";
		}
	}

	static public function mdlIdsBorradoresPeriodo($anio, $mes)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT id
			 FROM costos_modelo_mensualjf
			 WHERE anio = :anio AND mes = :mes AND estado = 'borrador'
			 ORDER BY id ASC"
		);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		$stmt->execute();
		return array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));
	}

	static public function mdlCostoAprobado($modelo, $anio, $mes)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT c.id, c.modelo, c.anio, c.mes, c.costo_unitario,
				c.fuente, c.observacion, c.usuario_aprobacion, c.fecha_aprobacion,
				IFNULL(NULLIF(TRIM(m.nombre), ''), c.modelo) AS nombre,
				IFNULL(m.id_marca, 0) AS id_marca,
				IFNULL(mk.marca, '') AS marca
			 FROM costos_modelo_mensualjf c
			 LEFT JOIN modelojf m ON TRIM(m.modelo) = c.modelo
			 LEFT JOIN marcasjf mk ON mk.id = m.id_marca
			 WHERE c.modelo = :modelo
			   AND c.anio = :anio
			   AND c.mes = :mes
			   AND c.estado = 'aprobado'
			 LIMIT 1"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlCostosAprobadosPeriodo($anio, $mes, $modelos = array())
	{
		$modelos = array_values(array_unique(array_filter(array_map("trim", $modelos))));
		$sql = "SELECT modelo, costo_unitario, fuente, fecha_aprobacion
			FROM costos_modelo_mensualjf
			WHERE anio = :anio AND mes = :mes AND estado = 'aprobado'";
		$marcadores = array();
		foreach ($modelos as $indice => $modelo) {
			$marcadores[] = ":modelo_" . $indice;
		}
		if (!empty($marcadores)) {
			$sql .= " AND modelo IN (" . implode(", ", $marcadores) . ")";
		}
		$sql .= " ORDER BY modelo ASC";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		foreach ($modelos as $indice => $modelo) {
			$stmt->bindValue(":modelo_" . $indice, $modelo, PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlCalcularRentabilidad($modelo, $anio, $mes, $ventaNeta, $unidades)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT base.modelo,
				base.anio,
				base.mes,
				base.costo_unitario,
				base.venta_neta,
				base.unidades_vendidas,
				base.costo_venta,
				CAST(base.venta_neta - base.costo_venta AS DECIMAL(20,4)) AS utilidad,
				CASE
					WHEN base.venta_neta > 0 THEN
						CAST(((base.venta_neta - base.costo_venta) / base.venta_neta) * 100 AS DECIMAL(10,4))
					ELSE NULL
				END AS margen_pct
			 FROM (
				SELECT c.modelo,
					c.anio,
					c.mes,
					c.costo_unitario,
					parametros.venta_neta,
					parametros.unidades_vendidas,
					CAST(parametros.unidades_vendidas * c.costo_unitario AS DECIMAL(20,4)) AS costo_venta
				FROM costos_modelo_mensualjf c
				CROSS JOIN (
					SELECT
						CAST(:venta_neta AS DECIMAL(20,4)) AS venta_neta,
						CAST(:unidades AS DECIMAL(14,4)) AS unidades_vendidas
				) parametros
				WHERE c.modelo = :modelo
				  AND c.anio = :anio
				  AND c.mes = :mes
				  AND c.estado = 'aprobado'
				LIMIT 1
			 ) base"
		);
		$stmt->bindValue(":venta_neta", $ventaNeta, PDO::PARAM_STR);
		$stmt->bindValue(":unidades", $unidades, PDO::PARAM_STR);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlCalcularRentabilidadUltimoAprobado($modelo, $anio, $mes, $ventaNeta, $unidades)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT base.modelo,
				base.anio_solicitado AS anio,
				base.mes_solicitado AS mes,
				base.costo_anio,
				base.costo_mes,
				base.costo_unitario,
				base.venta_neta,
				base.unidades_vendidas,
				base.costo_venta,
				CAST(base.venta_neta - base.costo_venta AS DECIMAL(20,4)) AS utilidad,
				CASE
					WHEN base.venta_neta > 0 THEN
						CAST(((base.venta_neta - base.costo_venta) / base.venta_neta) * 100 AS DECIMAL(10,4))
					ELSE NULL
				END AS margen_pct
			 FROM (
				SELECT c.modelo,
					CAST(:anio_solicitado AS UNSIGNED) AS anio_solicitado,
					CAST(:mes_solicitado AS UNSIGNED) AS mes_solicitado,
					c.anio AS costo_anio,
					c.mes AS costo_mes,
					c.costo_unitario,
					parametros.venta_neta,
					parametros.unidades_vendidas,
					CAST(parametros.unidades_vendidas * c.costo_unitario AS DECIMAL(20,4)) AS costo_venta
				FROM costos_modelo_mensualjf c
				CROSS JOIN (
					SELECT
						CAST(:venta_neta AS DECIMAL(20,4)) AS venta_neta,
						CAST(:unidades AS DECIMAL(14,4)) AS unidades_vendidas
				) parametros
				WHERE c.modelo = :modelo
				  AND c.estado = 'aprobado'
				  AND (c.anio < :anio_limite OR (c.anio = :anio_limite_igual AND c.mes <= :mes_limite))
				ORDER BY c.anio DESC, c.mes DESC, c.id DESC
				LIMIT 1
			 ) base"
		);
		$stmt->bindValue(":anio_solicitado", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes_solicitado", (int) $mes, PDO::PARAM_INT);
		$stmt->bindValue(":venta_neta", $ventaNeta, PDO::PARAM_STR);
		$stmt->bindValue(":unidades", $unidades, PDO::PARAM_STR);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":anio_limite", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":anio_limite_igual", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes_limite", (int) $mes, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlCostosAprobadosModelo($modelo)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT anio, mes, costo_unitario
			 FROM costos_modelo_mensualjf
			 WHERE modelo = :modelo AND estado = 'aprobado'
			 ORDER BY anio ASC, mes ASC, id ASC"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static private function mdlCostoAprobadoHasta($costos, $anio, $mes)
	{
		$limite = (int) $anio * 100 + (int) $mes;
		$seleccion = null;
		foreach ($costos as $costo) {
			$clave = (int) $costo["anio"] * 100 + (int) $costo["mes"];
			if ($clave <= $limite) {
				$seleccion = $costo;
			} else {
				break;
			}
		}
		return $seleccion;
	}

	/**
	 * Rentabilidad de un rango: aplica a cada mes el último costo aprobado no futuro.
	 * Si el modelo tiene un único costo aprobado, se usa también en meses anteriores a ese registro.
	 * $ventasPorMes: filas con anio, mes, unidades_vendidas, venta_neta.
	 */
	static public function mdlCalcularRentabilidadRango($modelo, $ventasPorMes, $ventaNetaTotal, $unidadesTotal)
	{
		$modelo = trim((string) $modelo);
		$ventaNetaTotal = (float) $ventaNetaTotal;
		$unidadesTotal = (float) $unidadesTotal;
		if (empty($ventasPorMes)) {
			return null;
		}

		$costos = self::mdlCostosAprobadosModelo($modelo);
		if (empty($costos)) {
			return null;
		}
		$costoUnicoFallback = count($costos) === 1 ? $costos[0] : null;

		$costoVenta = 0.0;
		$mesesConCosto = 0;
		$mesesSinCosto = 0;
		$costoAnio = null;
		$costoMes = null;
		$costoUnitario = null;
		$costoArrastrado = false;

		foreach ($ventasPorMes as $fila) {
			$anio = (int) $fila["anio"];
			$mes = (int) $fila["mes"];
			$unidadesMes = (float) $fila["unidades_vendidas"];
			if ($unidadesMes == 0.0) {
				continue;
			}
			$costo = self::mdlCostoAprobadoHasta($costos, $anio, $mes);
			if (!$costo || $costo["costo_unitario"] === null) {
				if ($costoUnicoFallback) {
					$costo = $costoUnicoFallback;
				} else {
					$mesesSinCosto++;
					continue;
				}
			}
			$mesesConCosto++;
			$costoVenta += $unidadesMes * (float) $costo["costo_unitario"];
			$costoAnio = (int) $costo["anio"];
			$costoMes = (int) $costo["mes"];
			$costoUnitario = $costo["costo_unitario"];
			if ((int) $costo["anio"] !== $anio || (int) $costo["mes"] !== $mes) {
				$costoArrastrado = true;
			}
		}

		if ($mesesConCosto === 0) {
			return null;
		}
		if ($mesesSinCosto > 0) {
			return null;
		}

		$utilidad = $ventaNetaTotal - $costoVenta;
		$margen = $ventaNetaTotal > 0
			? ($utilidad * 100 / $ventaNetaTotal)
			: null;

		return array(
			"modelo" => $modelo,
			"anio" => null,
			"mes" => null,
			"costo_anio" => $costoAnio,
			"costo_mes" => $costoMes,
			"costo_unitario" => $costoUnitario === null ? null : number_format((float) $costoUnitario, 4, ".", ""),
			"venta_neta" => number_format($ventaNetaTotal, 4, ".", ""),
			"unidades_vendidas" => number_format($unidadesTotal, 4, ".", ""),
			"costo_venta" => number_format($costoVenta, 4, ".", ""),
			"utilidad" => number_format($utilidad, 4, ".", ""),
			"margen_pct" => $margen === null ? null : number_format($margen, 4, ".", ""),
			"costo_arrastrado" => $costoArrastrado,
			"meses_con_costo" => $mesesConCosto,
			"estado" => "ok"
		);
	}

	static public function mdlHistorial($modelo, $anio, $mes)
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT id, costo_modelo_id, modelo, anio, mes, costo_unitario,
				fuente, observacion, estado, accion, motivo, usuario, fecha
			 FROM costos_modelo_mensual_historialjf
			 WHERE modelo = :modelo AND anio = :anio AND mes = :mes
			 ORDER BY fecha DESC, id DESC"
		);
		$stmt->bindValue(":modelo", trim((string) $modelo), PDO::PARAM_STR);
		$stmt->bindValue(":anio", (int) $anio, PDO::PARAM_INT);
		$stmt->bindValue(":mes", (int) $mes, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
