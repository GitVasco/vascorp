<?php

require_once "conexion.php";

class ModeloClientes
{


	// Método para mostrar un Cliente de la BD
	static public function mdlMostrarCliente($tabla, $item, $valor)
	{

		if ($item != null) {

			$sql = "SELECT * FROM $tabla WHERE $item=:$item";

			$stmt = Conexion::conectar()->prepare($sql);


			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
			$stmt->execute();

			# Retornamos un fetch por ser una sola línea la que necesitamos devolver

			return $stmt->fetch();
		} else {

			$sql = "SELECT * FROM $tabla ORDER BY nombre";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			# Retornamos un fetchAll por ser más de una línea la que necesitamos devolver
			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	CREAR CLIENTE
	=============================================*/

	static public function mdlIngresarCliente($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla (
											codigo,
											nombre,
											tipo_documento,
											documento,
											tipo_persona,
											ape_paterno,
											ape_materno,
											nombres,
											direccion,
											ubigeo,
											telefono,
											telefono2,
											email,
											contacto,
											vendedor,
											grupo,
											lista_precios,
											usureg,
											pcreg,
											fecreg,
											direccion_despacho,
											ubigeo_despacho,
											agencia
										) 
										VALUES
											(
											UPPER(:codigoCliente),
											UPPER(:nombre),
											:tipo_documento,
											:documento,
											:tipo_persona,
											UPPER(:ape_paterno),
											UPPER(:ape_materno),
											UPPER(:nombres),
											UPPER(:direccion),
											:ubigeo,
											:telefono,
											:telefono2,
											:email,
											:contacto,
											:vendedor,
											:grupo,
											:lista_precios,
											:usureg,
											:pcreg,
											:fecreg,
											UPPER(:direccion_despacho),
											:ubigeo_despacho,
											:agencia
											)");

		$stmt->bindParam(":codigoCliente", $datos["codigoCliente"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo_persona", $datos["tipo_persona"], PDO::PARAM_STR);
		$stmt->bindParam(":ape_paterno", $datos["ape_paterno"], PDO::PARAM_STR);
		$stmt->bindParam(":ape_materno", $datos["ape_materno"], PDO::PARAM_STR);
		$stmt->bindParam(":nombres", $datos["nombres"], PDO::PARAM_STR);
		$stmt->bindParam(":direccion", $datos["direccion"], PDO::PARAM_STR);
		$stmt->bindParam(":ubigeo", $datos["ubigeo"], PDO::PARAM_STR);
		$stmt->bindParam(":telefono", $datos["telefono"], PDO::PARAM_STR);
		$stmt->bindParam(":telefono2", $datos["telefono2"], PDO::PARAM_STR);
		$stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
		$stmt->bindParam(":contacto", $datos["contacto"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":grupo", $datos["grupo"], PDO::PARAM_STR);
		$stmt->bindParam(":lista_precios", $datos["lista_precios"], PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);
		$stmt->bindParam(":direccion_despacho", $datos["direccion_despacho"], PDO::PARAM_STR);
		$stmt->bindParam(":ubigeo_despacho", $datos["ubigeo_despacho"], PDO::PARAM_STR);
		$stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;
	}

	/*=============================================
	MOSTRAR CLIENTES
	=============================================*/

	static public function mdlMostrarClientes($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item=:$item");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT
			c.*,
			IFNULL(
				CASE
					WHEN c.grupo IS NOT NULL AND c.grupo <> '' THEN (
						SELECT cat.nombre
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
						SELECT cat.nombre
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
				END,
				'Sin categoría / pendiente'
			) AS categoria_comercial,
			CASE
				WHEN c.grupo IS NOT NULL AND c.grupo <> '' THEN (
					SELECT cat.codigo
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
					SELECT cat.codigo
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
			END AS categoria_codigo,
			CASE
				WHEN c.grupo IS NOT NULL AND c.grupo <> '' THEN (
					SELECT cat.color
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
					SELECT cat.color
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
			END AS categoria_color
		  FROM
			clientesjf c
		  WHERE c.fecha IS NOT NULL AND c.estado=1
		  ORDER BY codigo");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR CLIENTES
	=============================================*/

	static public function mdlMostrarClientesP($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT 
												c.id,
												CONCAT(
												c.codigo,
												REPEAT(' ', 12- LENGTH(c.codigo)),
												' - ',
												c.nombre
												) AS nombreB,
												c.codigo,
												c.nombre
											FROM
												clientesjf c
											WHERE c.fecha IS NOT NULL
												AND $item = :$item
											ORDER BY c.nombre ASC");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT
												c.id,
												CONCAT(
												c.codigo,
												REPEAT(' ', 12- LENGTH(c.codigo)),
												' - ',
												c.nombre
												) AS nombreB,
												c.codigo,
												c.nombre
											FROM
												clientesjf c
											WHERE c.fecha IS NOT NULL
											ORDER BY c.nombre ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	SACAR LISTA
	=============================================*/

	static public function mdlVerLista($valor)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
										c.id,
										c.lista_precios,
										c.vendedor
									FROM
										clientesjf c 
									WHERE c.codigo = '" . $valor . "'");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	EDITAR CLIENTE
	=============================================*/

	static public function mdlEditarCliente($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
											$tabla 
										SET
											nombre = UPPER(:nombre),
											tipo_documento = :tipo_documento,
											documento = :documento,
											tipo_persona = :tipo_persona,
											ape_paterno = UPPER(:ape_paterno),
											ape_materno = UPPER(:ape_materno),
											nombres = UPPER(:nombres),
											direccion = UPPER(:direccion),
											ubigeo = :ubigeo,
											telefono = :telefono,
											telefono2 = :telefono2,
											email = :email,
											contacto = :contacto,
											vendedor = :vendedor,
											grupo = :grupo,
											lista_precios = :lista_precios ,
											direccion_despacho = :direccion_despacho,
											ubigeo_despacho = :ubigeo_despacho,
											agencia = :agencia
										WHERE codigo = :codigoCliente ");

		$stmt->bindParam(":codigoCliente", $datos["codigoCliente"], PDO::PARAM_INT);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo_persona", $datos["tipo_persona"], PDO::PARAM_STR);
		$stmt->bindParam(":ape_paterno", $datos["ape_paterno"], PDO::PARAM_STR);
		$stmt->bindParam(":ape_materno", $datos["ape_materno"], PDO::PARAM_STR);
		$stmt->bindParam(":nombres", $datos["nombres"], PDO::PARAM_STR);
		$stmt->bindParam(":direccion", $datos["direccion"], PDO::PARAM_STR);
		$stmt->bindParam(":ubigeo", $datos["ubigeo"], PDO::PARAM_STR);
		$stmt->bindParam(":telefono", $datos["telefono"], PDO::PARAM_STR);
		$stmt->bindParam(":telefono2", $datos["telefono2"], PDO::PARAM_STR);
		$stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
		$stmt->bindParam(":contacto", $datos["contacto"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_INT);
		$stmt->bindParam(":grupo", $datos["grupo"], PDO::PARAM_STR);
		$stmt->bindParam(":lista_precios", $datos["lista_precios"], PDO::PARAM_STR);
		$stmt->bindParam(":direccion_despacho", $datos["direccion_despacho"], PDO::PARAM_STR);
		$stmt->bindParam(":ubigeo_despacho", $datos["ubigeo_despacho"], PDO::PARAM_STR);
		$stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;
	}


	/*=============================================
	ELIMINAR CLIENTE
	=============================================*/

	static public function mdlEliminarCliente($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE id = :id");

		$stmt->bindParam(":id", $datos, PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	ACTUALIZAR CLIENTE
	=============================================*/

	static public function mdlActualizarCliente($tabla, $item1, $valor1, $valor)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET $item1 = :$item1 WHERE id = :id");

		$stmt->bindParam(":" . $item1, $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":id", $valor, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR UBIGEOS
	*/
	static public function mdlMostrarUbigeos($tabla)
	{

		$sql = "SELECT 
						ub.codigo,
						CONCAT(
						ub.departamento,
						' /',
						ub.provincia,
						' /',
						ub.distrito
						) AS ubigeo 
					FROM
						$tabla ub 
					WHERE ub.codigo NOT IN ('000000')";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR CLIENTES CUENTAS
	=============================================*/

	static public function mdlMostrarClientesCuentas($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT codigo,nombre,documento FROM $tabla WHERE $item = :$item ORDER BY id ASC");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
			codigo,nombre,documento
		  FROM
			clientesjf 
		  ORDER BY id DESC ");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	EDITAR TIPO DE PAGO
	=============================================*/

	static public function mdlEditarAval($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET aval_nombre = :aval_nombre, aval_dir = :aval_dir,aval_postal = :aval_postal, aval_telf = :aval_telf,aval_ruc = :aval_ruc, aval_libreta = :aval_libreta WHERE codigo = :codigo");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":aval_nombre", $datos["aval_nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":aval_dir", $datos["aval_dir"], PDO::PARAM_STR);
		$stmt->bindParam(":aval_postal", $datos["aval_postal"], PDO::PARAM_STR);
		$stmt->bindParam(":aval_telf", $datos["aval_telf"], PDO::PARAM_STR);
		$stmt->bindParam(":aval_ruc", $datos["aval_ruc"], PDO::PARAM_STR);
		$stmt->bindParam(":aval_libreta", $datos["aval_libreta"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}
}
