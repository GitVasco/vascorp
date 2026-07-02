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
			"INSERT INTO grupos_empresarialesjf (codigo, nombre, descripcion, estado, usureg, fecreg)
			 VALUES (:codigo, :nombre, :descripcion, :estado, :usureg, :fecreg)"
		);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
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
				(SELECT COUNT(*) FROM clientesjf c WHERE c.grupo = g.codigo AND c.estado = 1) AS total_clientes
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
			 SET nombre = :nombre, descripcion = :descripcion, estado = :estado
			 WHERE id = :id"
		);

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
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

	static public function mdlContarClientesPorGrupo($codigo)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*) AS total FROM clientesjf WHERE grupo = :codigo"
		);
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

	static public function mdlAsignarClienteAGrupo($codigoCliente, $codigoGrupo)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE clientesjf SET grupo = :codigoGrupo WHERE codigo = :codigoCliente"
		);
		$stmt->bindParam(":codigoCliente", $codigoCliente, PDO::PARAM_STR);
		$stmt->bindParam(":codigoGrupo", $codigoGrupo, PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlQuitarClienteDeGrupo($codigoCliente)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE clientesjf SET grupo = '' WHERE codigo = :codigoCliente"
		);
		$stmt->bindParam(":codigoCliente", $codigoCliente, PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
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
