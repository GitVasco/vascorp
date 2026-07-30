<?php

require_once "conexion.php";

class ModeloModelos
{

	/* 
	* MOSTRAR MODELOS
	*/
	static public function mdlMostrarModelos($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT t.id_modelo,t.modelo,t.nombre,t.estado,t.tipo,COALESCE(NULLIF(TRIM(t.cod_unidad), ''), 'C62') AS cod_unidad,t.linea,t.operaciones,t.imagen,t.id_marca,t.descuentos,t.precios,t.efectos_desc,t.efectos_igv,m.marca FROM $tabla t LEFT JOIN marcasjf m on t.id_marca=m.id WHERE $item = :$item");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT t.id_modelo,t.modelo,t.nombre,t.estado,t.tipo,COALESCE(NULLIF(TRIM(t.cod_unidad), ''), 'C62') AS cod_unidad,t.linea,t.operaciones,t.imagen,t.articulos,m.marca FROM $tabla t LEFT JOIN marcasjf m on t.id_marca=m.id ORDER BY id_modelo ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR MODELOS
	*/
	static public function mdlMostrarModelosActivos()
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
			modelo,
			CONCAT(modelo, ' - ', nombre) AS nombre 
		  FROM
			modelojf 
		  WHERE estado = 'activo' ORDER BY modelo");

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*
	* REGISTRO DE MODELO
	*/
	static public function mdlIngresarModelo($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(modelo,nombre,estado,tipo,cod_unidad,imagen,id_marca) VALUES (:modelo,:nombre,:estado,:tipo,:cod_unidad,:imagen,:id_marca)");

		$stmt->bindParam(":id_marca", $datos["id_marca"], PDO::PARAM_STR);
		$stmt->bindParam(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_unidad", $datos["cod_unidad"], PDO::PARAM_STR);
		$stmt->bindParam(":imagen", $datos["imagen"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* EDITAR MODELO
	*/
	static public function mdlEditarModelo($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET modelo = :modelo, nombre = :nombre, tipo = :tipo, cod_unidad = :cod_unidad, imagen=:imagen, id_marca = :id_marca  WHERE modelo = :modelo");
		$stmt->bindParam(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_unidad", $datos["cod_unidad"], PDO::PARAM_STR);
		$stmt->bindParam(":imagen", $datos["imagen"], PDO::PARAM_STR);
		$stmt->bindParam(":id_marca", $datos["id_marca"], PDO::PARAM_INT);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}
	/* 
	* Método para activar y desactivar un MODELO con articulos
	*/
	static public function mdlActualizarModelo($tabla, $tabla2, $valor1, $valor2)
	{

		$sql = "UPDATE $tabla m INNER JOIN $tabla2 a ON m.modelo=a.modelo SET m.estado = :estado, a.estado = :estado WHERE m.modelo=:valor";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":estado", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":valor", $valor2, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}


	/* 
	* BORRAR MODELO
	*/
	static public function mdlEliminarModelo($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE id_modelo = :id");

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
	MOSTRAR MODELOS CON ARTICULOS
	=============================================*/
	static public function mdlMostrarModeloArticulo($tabla, $item, $valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT t.id_modelo,t.modelo,t.nombre,t.estado,t.tipo,t.linea,t.operaciones,t.imagen,a.color,a.talla FROM $tabla t LEFT JOIN articulojf a on t.modelo=a.modelo WHERE t.modelo = $valor");
		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR TALLAS
	*/
	static public function mdlMostrarTallas($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla  ORDER BY id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}
	/* 
	* MOSTRAR TALLA CON GRUPO
	*/
	static public function mdlMostrarTallaGrupo($tabla, $item, $valor, $valor2)
	{

		$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item AND nombre_grupo= :valor");

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
		$stmt->bindParam(":valor", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/* 
	* ACTUALIZAR MODELO
	*/
	static public function mdlModeloPrecios($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET descuentos = :descuentos, precios = :precios, efectos_desc = :efectos_desc, efectos_igv=:efectos_igv, articulos=:articulos WHERE modelo = :modelo");
		$stmt->bindParam(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindParam(":descuentos", $datos["descuentos"], PDO::PARAM_INT);
		$stmt->bindParam(":precios", $datos["precios"], PDO::PARAM_INT);
		$stmt->bindParam(":efectos_desc", $datos["efectos_desc"], PDO::PARAM_INT);
		$stmt->bindParam(":efectos_igv", $datos["efectos_igv"], PDO::PARAM_INT);
		$stmt->bindParam(":articulos", $datos["articulos"], PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
	* REGISTRO DE PRECIOS
	*/
	static public function mdlIngresarPrecio($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(modelo,precio1,precio2,precio3,precio4,precio5,precio6,precio7,precio8,precio9,precio10) VALUES (:modelo,:precio1,:precio2,:precio3,:precio4,:precio5,:precio6,:precio7,:precio8,:precio9,:precio10)");

		$stmt->bindParam(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindParam(":precio1", $datos["precio1"], PDO::PARAM_INT);
		$stmt->bindParam(":precio2", $datos["precio2"], PDO::PARAM_INT);
		$stmt->bindParam(":precio3", $datos["precio3"], PDO::PARAM_INT);
		$stmt->bindParam(":precio4", $datos["precio4"], PDO::PARAM_INT);
		$stmt->bindParam(":precio5", $datos["precio5"], PDO::PARAM_INT);
		$stmt->bindParam(":precio6", $datos["precio6"], PDO::PARAM_INT);
		$stmt->bindParam(":precio7", $datos["precio7"], PDO::PARAM_INT);
		$stmt->bindParam(":precio8", $datos["precio8"], PDO::PARAM_INT);
		$stmt->bindParam(":precio9", $datos["precio9"], PDO::PARAM_INT);
		$stmt->bindParam(":precio10", $datos["precio10"], PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
	* REGISTRO DE MODELO
	*/
	static public function mdlEditarPrecio($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET precio1=:precio1,precio2=:precio2,precio3=:precio3,precio4=:precio4,precio5=:precio5,precio6=:precio6,precio7=:precio7,precio8=:precio8,precio9=:precio9,precio10=:precio10,precio11=:precio11 WHERE modelo = :modelo");

		$stmt->bindParam(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindParam(":precio1", $datos["precio1"], PDO::PARAM_INT);
		$stmt->bindParam(":precio2", $datos["precio2"], PDO::PARAM_INT);
		$stmt->bindParam(":precio3", $datos["precio3"], PDO::PARAM_INT);
		$stmt->bindParam(":precio4", $datos["precio4"], PDO::PARAM_INT);
		$stmt->bindParam(":precio5", $datos["precio5"], PDO::PARAM_INT);
		$stmt->bindParam(":precio6", $datos["precio6"], PDO::PARAM_INT);
		$stmt->bindParam(":precio7", $datos["precio7"], PDO::PARAM_INT);
		$stmt->bindParam(":precio8", $datos["precio8"], PDO::PARAM_INT);
		$stmt->bindParam(":precio9", $datos["precio9"], PDO::PARAM_INT);
		$stmt->bindParam(":precio10", $datos["precio10"], PDO::PARAM_INT);
		$stmt->bindParam(":precio11", $datos["precio11"], PDO::PARAM_INT);

		if ($stmt->execute()) {
			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* MOSTRAR PRECIOS
	*/
	static public function mdlMostrarPrecios($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla  WHERE modelo = :modelo");

			$stmt->bindParam(":modelo", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR MODELOS
	*/
	static public function mdlMostrarColorModelo($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT
		  a.modelo,
		  a.nombre,
		  a.cod_color,
		  a.color 
		FROM
		  articulojf a 
		WHERE a.modelo = :modelo");

		$stmt->bindParam(":modelo", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	/*
	* COLORES Y TALLAS YA CREADOS EN UN MODELO
	*/
	static public function mdlMostrarVariantesModelo($modelo)
	{

		$pdo = Conexion::conectar();

		$stmtColores = $pdo->prepare("SELECT DISTINCT
			a.cod_color,
			a.color
		FROM articulojf a
		WHERE a.modelo = :modelo
		ORDER BY a.cod_color ASC");
		$stmtColores->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmtColores->execute();
		$colores = $stmtColores->fetchAll();

		$stmtTallas = $pdo->prepare("SELECT DISTINCT
			a.cod_talla,
			a.talla
		FROM articulojf a
		WHERE a.modelo = :modelo
		ORDER BY a.cod_talla ASC");
		$stmtTallas->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmtTallas->execute();
		$tallas = $stmtTallas->fetchAll();

		$stmtPares = $pdo->prepare("SELECT
			a.cod_color,
			a.color,
			a.cod_talla,
			a.talla,
			a.articulo
		FROM articulojf a
		WHERE a.modelo = :modelo
		ORDER BY a.cod_color ASC, a.cod_talla ASC");
		$stmtPares->bindParam(":modelo", $modelo, PDO::PARAM_STR);
		$stmtPares->execute();
		$pares = $stmtPares->fetchAll();

		return array(
			"colores" => $colores,
			"tallas" => $tallas,
			"pares" => $pares
		);
	}

	//* Actualizar Detalle
	static public function mdlActualizarDetallePedido($pedido, $lista)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
					detalle_temporal dt 
					LEFT JOIN articulojf a 
					ON dt.articulo = a.articulo 
					LEFT JOIN preciojf p 
					ON a.modelo = p.modelo 
					SET dt.precio = p.precio{$lista},
					dt.total = dt.cantidad * p.precio{$lista}
				WHERE dt.codigo=  '$pedido'");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	//* Actualizar Detalle
	static public function mdlActualizarCabeceraPedido($pedido, $lista)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
							temporaljf 
						SET
							lista = 'precio{$lista}' 
						WHERE codigo = '$pedido'");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}
}
