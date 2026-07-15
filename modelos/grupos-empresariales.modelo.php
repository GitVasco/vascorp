<?php

require_once "conexion.php";

class ModeloGruposEmpresariales
{

	static public function mdlSiguienteCodigoGrupo()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT IFNULL(MAX(id), 0) + 1 AS siguiente FROM grupos_empresarialesjf"
		);
		$stmt->execute();
		$fila = $stmt->fetch();
		$numero = (int) $fila["siguiente"];

		return "GE" . str_pad($numero, 4, "0", STR_PAD_LEFT);
	}

	static public function mdlIngresarGrupo($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO grupos_empresarialesjf (codigo, nombre, descripcion, id_zona, estado, usureg, fecreg)
			 VALUES (:codigo, :nombre, :descripcion, :id_zona, :estado, :usureg, :fecreg)"
		);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		if ($datos["id_zona"] === null) {
			$stmt->bindValue(":id_zona", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":id_zona", (int) $datos["id_zona"], PDO::PARAM_INT);
		}
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);

		if ($stmt->execute()) {
			return "ok";
		}

		return "error";
	}

	static public function mdlMostrarGrupos($item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare(
				"SELECT * FROM grupos_empresarialesjf WHERE $item = :$item"
			);
			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
			$stmt->execute();

			return $stmt->fetch();
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT g.*,
				(SELECT COUNT(*) FROM clientesjf c WHERE c.grupo = g.codigo AND c.estado = 1) AS total_clientes,
				IFNULL((
					SELECT cat.nombre
					FROM categorias_clientes_asignacionesjf a
					INNER JOIN categorias_clientesjf cat ON cat.id = a.id_categoria
					WHERE a.tipo_entidad = 'grupo'
					  AND a.codigo_entidad = g.codigo
					  AND a.estado = 1
					  AND a.vigencia_desde <= NOW()
					  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
					ORDER BY a.id DESC
					LIMIT 1
				), 'Sin categoría / pendiente') AS categoria_comercial,
				(
					SELECT cat.codigo
					FROM categorias_clientes_asignacionesjf a
					INNER JOIN categorias_clientesjf cat ON cat.id = a.id_categoria
					WHERE a.tipo_entidad = 'grupo'
					  AND a.codigo_entidad = g.codigo
					  AND a.estado = 1
					  AND a.vigencia_desde <= NOW()
					  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
					ORDER BY a.id DESC
					LIMIT 1
				) AS categoria_codigo,
				(
					SELECT cat.color
					FROM categorias_clientes_asignacionesjf a
					INNER JOIN categorias_clientesjf cat ON cat.id = a.id_categoria
					WHERE a.tipo_entidad = 'grupo'
					  AND a.codigo_entidad = g.codigo
					  AND a.estado = 1
					  AND a.vigencia_desde <= NOW()
					  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
					ORDER BY a.id DESC
					LIMIT 1
				) AS categoria_color,
				(
					SELECT cat.id
					FROM categorias_clientes_asignacionesjf a
					INNER JOIN categorias_clientesjf cat ON cat.id = a.id_categoria
					WHERE a.tipo_entidad = 'grupo'
					  AND a.codigo_entidad = g.codigo
					  AND a.estado = 1
					  AND a.vigencia_desde <= NOW()
					  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
					ORDER BY a.id DESC
					LIMIT 1
				) AS categoria_id
			 FROM grupos_empresarialesjf g
			 ORDER BY g.nombre ASC"
		);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlMostrarGruposActivos()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT codigo, nombre FROM grupos_empresarialesjf WHERE estado = 1 ORDER BY nombre ASC"
		);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlEditarGrupo($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE grupos_empresarialesjf
			 SET nombre = :nombre, descripcion = :descripcion, id_zona = :id_zona, estado = :estado
			 WHERE id = :id"
		);

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		if ($datos["id_zona"] === null) {
			$stmt->bindValue(":id_zona", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":id_zona", (int) $datos["id_zona"], PDO::PARAM_INT);
		}
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);

		if ($stmt->execute()) {
			return "ok";
		}

		return "error";
	}

	static public function mdlActualizarCodigoEnClientes($codigoAnterior, $codigoNuevo)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE clientesjf SET grupo = :codigoNuevo WHERE grupo = :codigoAnterior"
		);
		$stmt->bindParam(":codigoAnterior", $codigoAnterior, PDO::PARAM_STR);
		$stmt->bindParam(":codigoNuevo", $codigoNuevo, PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEliminarGrupo($id)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM grupos_empresarialesjf WHERE id = :id");
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);

		if ($stmt->execute()) {
			return "ok";
		}

		return "error";
	}

	static public function mdlContarClientesPorGrupo($codigo, $soloActivos = false)
	{

		$sql = "SELECT COUNT(*) AS total FROM clientesjf WHERE grupo = :codigo";
		if ($soloActivos) {
			$sql .= " AND estado = 1";
		}

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
		$stmt->execute();

		$fila = $stmt->fetch();

		return $fila ? (int) $fila["total"] : 0;
	}

	static public function mdlMostrarClientesPorGrupo($codigo)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT codigo, nombre, documento, telefono
			 FROM clientesjf
			 WHERE grupo = :codigo AND estado = 1
			 ORDER BY nombre ASC"
		);
		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	static public function mdlMostrarClientePorCodigo($codigoCliente)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT codigo, nombre, documento, telefono, grupo, estado
			 FROM clientesjf
			 WHERE codigo = :codigoCliente
			 LIMIT 1"
		);
		$stmt->bindParam(":codigoCliente", $codigoCliente, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch();
	}

	static public function mdlAsignarClienteAGrupo($codigoCliente, $codigoGrupo)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE clientesjf
			 SET grupo = :codigoGrupo
			 WHERE codigo = :codigoCliente
			   AND estado = 1
			   AND (grupo IS NULL OR grupo = '')"
		);
		$stmt->bindParam(":codigoCliente", $codigoCliente, PDO::PARAM_STR);
		$stmt->bindParam(":codigoGrupo", $codigoGrupo, PDO::PARAM_STR);

		if (!$stmt->execute()) {
			return "error";
		}

		return $stmt->rowCount() > 0 ? "ok" : "no_disponible";
	}

	static public function mdlQuitarClienteDeGrupo($codigoCliente, $codigoGrupo = null)
	{

		if ($codigoGrupo !== null && $codigoGrupo !== "") {
			$stmt = Conexion::conectar()->prepare(
				"UPDATE clientesjf
				 SET grupo = ''
				 WHERE codigo = :codigoCliente
				   AND grupo = :codigoGrupo"
			);
			$stmt->bindParam(":codigoCliente", $codigoCliente, PDO::PARAM_STR);
			$stmt->bindParam(":codigoGrupo", $codigoGrupo, PDO::PARAM_STR);
		} else {
			$stmt = Conexion::conectar()->prepare(
				"UPDATE clientesjf SET grupo = '' WHERE codigo = :codigoCliente"
			);
			$stmt->bindParam(":codigoCliente", $codigoCliente, PDO::PARAM_STR);
		}

		if (!$stmt->execute()) {
			return "error";
		}

		return $stmt->rowCount() > 0 ? "ok" : "no_encontrado";
	}

	static public function mdlMostrarClientesSinGrupo()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT codigo, nombre, documento
			 FROM clientesjf
			 WHERE (grupo IS NULL OR grupo = '') AND estado = 1
			 ORDER BY nombre ASC"
		);
		$stmt->execute();

		return $stmt->fetchAll();
	}
}
