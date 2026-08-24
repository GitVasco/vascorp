<?php

require_once "conexion.php";

class ModeloServicios
{

	/*=============================================
	MOSTRAR VENTAS
	=============================================*/

	static public function mdlMostrarServicios($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector FROM  $tabla se LEFT JOIN sectorjf s on se.taller = s.cod_sector WHERE se.$item = :$item ");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector FROM  $tabla se LEFT JOIN sectorjf s on se.taller = s.cod_sector  ORDER BY se.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	// Método para Mostrar los detalles de servicios
	static public function mdlMostraDetallesServicios($tabla, $item, $valor)
	{

		$sql = "SELECT * FROM $tabla WHERE $item=:$item ORDER BY id ASC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	static public function mdlMostraDetallesServicioUnico($tabla, $item, $valor)
	{

		$sql = "SELECT * FROM $tabla WHERE $item=:$item ORDER BY id ASC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}



	// Método para guardar los servicios
	static public function mdlGuardarServicios($tabla, $datos)
	{

		$sql = "INSERT INTO $tabla(codigo,usuario,taller,total,fecha,estado) VALUES (:codigo,:usuario,:taller,:total,:fecha,:estado)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_INT);
		$stmt->bindParam(":taller", $datos["taller"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	// Método para guardar las ventas
	static public function mdlGuardarDetallesServicios($tabla, $datos)
	{
		$cabeceraTaller = isset($datos["cabecera_taller"]) ? $datos["cabecera_taller"] : null;

		$sql = "INSERT INTO $tabla(codigo,articulo,cantidad,saldo,cabecera_taller) VALUES (:codigo,:articulo,:cantidad,:saldo,:cabecera_taller)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
		$stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_INT);
		$stmt->bindParam(":cabecera_taller", $cabeceraTaller, PDO::PARAM_INT);

		$ok = $stmt->execute();

		$stmt = null;

		return $ok ? "ok" : "error";
	}

	/*
	* Sumar unidades al total de la cabecera del servicio del día
	*/
	static public function mdlSumarTotalServicio($codigo, $cantidad)
	{
		$sql = "UPDATE serviciosjf SET total = COALESCE(total, 0) + :cantidad WHERE codigo = :codigo";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);

		$ok = $stmt->execute();

		$stmt = null;

		return $ok ? "ok" : "error";
	}

	// Método para editar las ventas
	static public function mdlEditarServicios($tabla, $datos)
	{

		$sql = "UPDATE $tabla SET codigo=:codigo,usuario=:usuario,taller=:taller,total=:total,fecha=:fecha WHERE codigo=:codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":taller", $datos["taller"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}
	// Método para editar los detalles de ventas - NO ES NECESARIO
	static public function mdlEditarDetallesServicios($tabla, $datos)
	{

		$sql = "UPDATE $tabla SET impuesto=:impuesto,neto=:neto,total=:total,metodo_pago=:metodo_pago WHERE codigo=:codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":impuesto", $datos["impuesto"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}
		$stmt = null;
	}

	/*=============================================
	ELIMINAR SERVICIO
	=============================================*/

	static public function mdlEliminarServicio($tabla, $datos)
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

	// Método para actualizar un dato CON EL ID
	static public function mdlActualizarUnDato($tabla, $item1, $valor1, $valor2)
	{

		$sql = "UPDATE $tabla SET $item1=:$item1 WHERE articulo=:articulo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item1, $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":articulo", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	// Método para actualizar un dato con el PRODUCTO_CODIGO
	static public function mdlActualizarUnDatoArticulo($tabla, $item1, $valor1, $valor2)
	{

		$sql = "UPDATE $tabla SET $item1=:$item1 WHERE codigo=:id";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item1, $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":id", $valor2, PDO::PARAM_INT);

		$stmt->execute();

		$stmt = null;
	}



	// Método para pedir último Id de venta
	static public function mdlUltimoId($tabla, $cliente, $vendedor)
	{
		$sql = "SELECT * FROM $tabla WHERE id_cliente=:id_cliente AND id_vendedor=:id_vendedor ORDER BY fecha DESC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":id_cliente", $cliente, PDO::PARAM_STR);
		$stmt->bindParam(":id_vendedor", $vendedor, PDO::PARAM_STR);

		$stmt->execute();

		# Retornamos un fetchAll por ser más de una línea la que necesitamos devolver
		return $stmt->fetchAll();

		$stmt = null;
	}



	// Método para eliminar un detalle de venta
	static public function mdlEliminarDato($tabla, $item, $valor)
	{

		$sql = "DELETE FROM $tabla WHERE $item=:$item";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/*=============================================
	SUMAR EL TOTAL DE VENTAS
	=============================================*/

	static public function mdlSumaTotalServicios($tabla)
	{

		$stmt = Conexion::conectar()->prepare("SELECT SUM(neto) as total FROM $tabla");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlUltimoServicio($tabla)
	{

		$sql = "SELECT COUNT(codigo) + 1 AS ultimo_codigo FROM $tabla";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/*=============================================
	CREAR PRECIO SERVICIO
	=============================================*/

	static public function mdlIngresarPrecioServicio($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(taller,modelo,precio_doc) VALUES (:taller,:modelo,:precio_doc)");

		$stmt->bindParam(":taller", $datos["taller"], PDO::PARAM_STR);
		$stmt->bindParam(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindParam(":precio_doc", $datos["precio_doc"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*=============================================
	MOSTRAR PRECIO SERVICIO
	=============================================*/

	static public function mdlMostrarPrecioServicios($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT pd.*,m.nombre FROM $tabla pd LEFT JOIN modelojf  m ON pd.modelo = m.modelo WHERE pd.$item = :$item");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT pd.*,m.nombre,s.nom_sector FROM $tabla pd LEFT JOIN modelojf  m ON pd.modelo = m.modelo LEFT JOIN sectorjf s ON pd.taller = s.cod_sector ORDER BY pd.id DESC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	EDITAR PRECIO SERVICIO
	=============================================*/

	static public function mdlEditarPrecioServicio($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET taller = :taller, modelo = :modelo, precio_doc=:precio_doc WHERE id = :id");

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":taller", $datos["taller"], PDO::PARAM_STR);
		$stmt->bindParam(":modelo", $datos["modelo"], PDO::PARAM_STR);
		$stmt->bindParam(":precio_doc", $datos["precio_doc"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}


	/*=============================================
	ELIMINAR PRECIO SERVICIO
	=============================================*/

	static public function mdlEliminarPrecioServicio($tabla, $datos)
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

	//VISUALIZAR DETALLE SERVICIO
	// Muestra cantidad enviada (sd.cantidad), no el saldo pendiente.
	static public function mdlVisualizarServicioDetalle($valor)
	{

		if ($valor != null) {
			$stmt = Conexion::conectar()->prepare("SELECT 
			sd.codigo,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color,
			se.cod_sector,
			se.nom_sector,
			a.estado,
			SUM(
			  CASE
				WHEN a.cod_talla = '1' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t1,
			SUM(
			  CASE
				WHEN a.cod_talla = '2' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t2,
			SUM(
			  CASE
				WHEN a.cod_talla = '3' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t3,
			SUM(
			  CASE
				WHEN a.cod_talla = '4' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t4,
			SUM(
			  CASE
				WHEN a.cod_talla = '5' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t5,
			SUM(
			  CASE
				WHEN a.cod_talla = '6' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t6,
			SUM(
			  CASE
				WHEN a.cod_talla = '7' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t7,
			SUM(
			  CASE
				WHEN a.cod_talla = '8' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t8,
			SUM(sd.cantidad) AS total 
		  FROM
			servicios_detallejf sd 
			LEFT JOIN articulojf a 
			  ON sd.articulo = a.articulo 
			LEFT JOIN serviciosjf s
			  ON s.codigo = sd.codigo
			LEFT JOIN sectorjf se
			  ON  s.taller=se.cod_sector
		  WHERE sd.codigo = :valor
		  GROUP BY sd.codigo,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color,
			a.estado
		  HAVING SUM(sd.cantidad) > 0");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT 
			sd.codigo,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color,
			se.cod_sector,
			se.nom_sector,
			a.estado,
			SUM(
			  CASE
				WHEN a.cod_talla = '1' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t1,
			SUM(
			  CASE
				WHEN a.cod_talla = '2' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t2,
			SUM(
			  CASE
				WHEN a.cod_talla = '3' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t3,
			SUM(
			  CASE
				WHEN a.cod_talla = '4' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t4,
			SUM(
			  CASE
				WHEN a.cod_talla = '5' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t5,
			SUM(
			  CASE
				WHEN a.cod_talla = '6' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t6,
			SUM(
			  CASE
				WHEN a.cod_talla = '7' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t7,
			SUM(
			  CASE
				WHEN a.cod_talla = '8' 
				THEN sd.saldo 
				ELSE 0 
			  END
			) AS t8,
			SUM(sd.saldo) AS total 
		  FROM
			servicios_detallejf sd 
			LEFT JOIN articulojf a 
			  ON sd.articulo = a.articulo 
			LEFT JOIN serviciosjf s 
			  ON s.codigo = sd.codigo 
			LEFT JOIN sectorjf se 
			  ON s.taller = se.cod_sector 
		  WHERE saldo > 0 
			AND sd.cerrar = 0 
		  GROUP BY sd.codigo,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color,
			a.estado 
		  HAVING SUM(sd.saldo) > 0 
		  ORDER BY a.modelo ASC,
			a.cod_color
		   ");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}
		$stmt = null;
	}


	/* 
	* MOSTRAR PRODUCCION
	*/
	static public function mdlMostrarPagoServicios($valor)
	{

		if ($valor != null) {

			$stmt = Conexion::conectar()->prepare("SELECT 
                                                            q.*,
                                                            m.mes,
                                                            q.mes AS nmes,
                                                            u.nombre
                                                        FROM
                                                            pago_serviciosjf q 
                                                            LEFT JOIN usuariosjf u 
                                                            ON q.usuario = u.id 
                                                            LEFT JOIN 
                                                            (SELECT DISTINCT 
                                                                codigo,
                                                                descripcion AS mes 
                                                            FROM
                                                                meses) AS m 
                                                            ON q.mes = m.codigo 
                                                        WHERE q.id = :valor");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
                                            q.id,
                                            q.ano,
                                            CASE
                                              WHEN q.mes = '1' 
                                              THEN 'Enero' 
                                              WHEN q.mes = '2' 
                                              THEN 'Febrero' 
                                              WHEN q.mes = '3' 
                                              THEN 'Marzo' 
                                              WHEN q.mes = '4' 
                                              THEN 'Abril' 
                                              WHEN q.mes = '5' 
                                              THEN 'Mayo' 
                                              WHEN q.mes = '6' 
                                              THEN 'Junio' 
                                              WHEN q.mes = '7' 
                                              THEN 'Julio' 
                                              WHEN q.mes = '8' 
                                              THEN 'Agosto' 
                                              WHEN q.mes = '9' 
                                              THEN 'Septiembre' 
                                              WHEN q.mes = '10' 
                                              THEN 'Octubre' 
                                              WHEN q.mes = '11' 
                                              THEN 'Noviembre' 
                                              ELSE 'Diciembre' 
                                            END AS mes,
                                            q.mes AS nmes,
                                            q.inicio,
                                            q.fin,
                                            u.nombre,
                                            q.fecha_creacion,
											q.estado_pago
                                          FROM
                                            pago_serviciosjf q 
                                            LEFT JOIN usuariosjf u 
                                              ON q.usuario = u.id
											  where YEAR(q.fecha_creacion) = YEAR(NOW())
											  ");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*
	* CREAR QUINCENA
	*/
	static public function mdlCrearPagoServicio($datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO pago_serviciosjf (
                                                ano,
                                                mes,
                                                inicio,
                                                fin,
                                                usuario
                                            ) 
                                            VALUES
                                                (
                                                :ano,
                                                :mes,
                                                :inicio,
                                                :fin,
                                                :usuario
                                                )");

		$stmt->bindParam(":ano", $datos["ano"], PDO::PARAM_STR);
		$stmt->bindParam(":mes", $datos["mes"], PDO::PARAM_STR);
		$stmt->bindParam(":inicio", $datos["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $datos["fin"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	static public function mdlEditarPagoServicio($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
                                                    pago_serviciosjf 
                                                SET
                                                    ano = :ano,
                                                    mes = :mes,
                                                    inicio = :inicio,
                                                    fin = :fin,
                                                    usuario = :usuario 
                                                WHERE id = :id");

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":ano", $datos["ano"], PDO::PARAM_STR);
		$stmt->bindParam(":mes", $datos["mes"], PDO::PARAM_STR);
		$stmt->bindParam(":inicio", $datos["inicio"], PDO::PARAM_STR);
		$stmt->bindParam(":fin", $datos["fin"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	static public function mdlEditarEtiqueta($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
                                                    pago_serviciosjf 
                                                SET
                                                    taller1 = :taller1,
													taller2 = :taller2,
													taller3 = :taller3,
													taller4 = :taller4,
													taller5 = :taller5,
													taller6 = :taller6,
													taller7 = :taller7,
													taller8 = :taller8,
													taller9 = :taller9,
													taller10 = :taller10,
													taller11 = :taller11,
													taller12 = :taller12,
													taller13 = :taller13,
													taller14 = :taller14,
													taller15 = :taller15
                                                WHERE id = :id");

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":taller1", $datos["taller1"], PDO::PARAM_STR);
		$stmt->bindParam(":taller2", $datos["taller2"], PDO::PARAM_STR);
		$stmt->bindParam(":taller3", $datos["taller3"], PDO::PARAM_STR);
		$stmt->bindParam(":taller4", $datos["taller4"], PDO::PARAM_STR);
		$stmt->bindParam(":taller5", $datos["taller5"], PDO::PARAM_STR);
		$stmt->bindParam(":taller6", $datos["taller6"], PDO::PARAM_STR);
		$stmt->bindParam(":taller7", $datos["taller7"], PDO::PARAM_STR);
		$stmt->bindParam(":taller8", $datos["taller8"], PDO::PARAM_STR);
		$stmt->bindParam(":taller9", $datos["taller9"], PDO::PARAM_STR);
		$stmt->bindParam(":taller10", $datos["taller10"], PDO::PARAM_STR);
		$stmt->bindParam(":taller11", $datos["taller11"], PDO::PARAM_STR);
		$stmt->bindParam(":taller12", $datos["taller12"], PDO::PARAM_STR);
		$stmt->bindParam(":taller13", $datos["taller13"], PDO::PARAM_STR);
		$stmt->bindParam(":taller14", $datos["taller14"], PDO::PARAM_STR);
		$stmt->bindParam(":taller15", $datos["taller15"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/* 
	* BORRAR QUINCENA
	*/
	static public function mdlEliminarPagoServicio($id)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM pago_serviciosjf WHERE id = :id ");

		$stmt->bindParam(":id", $id, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* MOSTRAR PRODUCCION
	*/
	static public function mdlVerPagoServicios($inicio, $fin)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		c.taller,
		s.cod_sector,
		s.nom_sector,
		sd.codigo,
		c.fecha,
		c.guia,
		a.modelo,
		a.nombre,
		a.cod_color,
		a.color,
		SUM(
		  CASE
			WHEN a.cod_talla = '1' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t1,
		SUM(
		  CASE
			WHEN a.cod_talla = '2' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t2,
		SUM(
		  CASE
			WHEN a.cod_talla = '3' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t3,
		SUM(
		  CASE
			WHEN a.cod_talla = '4' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t4,
		SUM(
		  CASE
			WHEN a.cod_talla = '5' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t5,
		SUM(
		  CASE
			WHEN a.cod_talla = '6' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t6,
		SUM(
		  CASE
			WHEN a.cod_talla = '7' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t7,
		SUM(
		  CASE
			WHEN a.cod_talla = '8' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t8,
		SUM(sd.inicio) AS total_und,
		ROUND(SUM(sd.inicio) / 12, 2) AS total_docenas,
		ps.precio_doc,
		ROUND(
		  (
			(SUM(sd.inicio) / 12) * ps.precio_doc
		  ),
		  2
		) AS total_soles 
	  FROM
		cierresjf c 
		LEFT JOIN cierres_detallejf sd 
		  ON c.codigo = sd.codigo 
		LEFT JOIN articulojf a 
		  ON sd.articulo = a.articulo 
		LEFT JOIN precio_serviciojf ps 
		  ON c.taller = ps.taller 
		  AND a.modelo = ps.modelo 
		LEFT JOIN sectorjf s 
		  ON c.taller = s.cod_sector 
	  WHERE DATE(c.fecha) BETWEEN '" . $inicio . "' 
		AND '" . $fin . "' 
	  GROUP BY sd.codigo,
		a.modelo,
		a.nombre,
		a.cod_color,
		a.color");


		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlVerSectores($inicio, $fin)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		c.taller,
		s.nom_completo
	  FROM
		cierresjf c 
		LEFT JOIN cierres_detallejf sd 
		  ON c.codigo = sd.codigo 
		LEFT JOIN articulojf a 
		  ON sd.articulo = a.articulo 
		LEFT JOIN precio_serviciojf ps 
		  ON c.taller = ps.taller 
		  AND a.modelo = ps.modelo 
		LEFT JOIN sectorjf s 
		  ON c.taller = s.cod_sector 
	  WHERE DATE(c.fecha) BETWEEN '" . $inicio . "' 
		AND '" . $fin . "' 
	  GROUP BY c.taller ;
	  ");


		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlVerTotalPagar($inicio, $fin, $sector)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		c.taller,
		ROUND(
		  SUM(sd.inicio / 12 * ps.precio_doc),
		  2
		) AS total_soles 
	  FROM
		cierresjf c 
		LEFT JOIN cierres_detallejf sd 
		  ON c.codigo = sd.codigo 
		LEFT JOIN articulojf a 
		  ON sd.articulo = a.articulo 
		LEFT JOIN precio_serviciojf ps 
		  ON c.taller = ps.taller 
		  AND a.modelo = ps.modelo 
		LEFT JOIN sectorjf s 
		  ON c.taller = s.cod_sector 
	  WHERE DATE(c.fecha) BETWEEN '" . $inicio . "' 
		AND '" . $fin . "' 
		AND c.taller = '" . $sector . "' 
	  GROUP BY c.taller ");


		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlVerPagoServicioSector($inicio, $fin, $sector)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		c.taller,
		s.cod_sector,
		s.nom_sector,
		sd.codigo,
		c.fecha,
		CASE
		  WHEN MONTH(c.fecha) = '1' 
		  THEN CONCAT(DAY(c.fecha), '-Ene') 
		  WHEN MONTH(c.fecha) = '2' 
		  THEN CONCAT(DAY(c.fecha), '-Feb') 
		  WHEN MONTH(c.fecha) = '3' 
		  THEN CONCAT(DAY(c.fecha), '-Mar') 
		  WHEN MONTH(c.fecha) = '4' 
		  THEN CONCAT(DAY(c.fecha), '-Abr') 
		  WHEN MONTH(c.fecha) = '5' 
		  THEN CONCAT(DAY(c.fecha), '-May') 
		  WHEN MONTH(c.fecha) = '6' 
		  THEN CONCAT(DAY(c.fecha), '-Jun') 
		  WHEN MONTH(c.fecha) = '7' 
		  THEN CONCAT(DAY(c.fecha), '-Jul') 
		  WHEN MONTH(c.fecha) = '8' 
		  THEN CONCAT(DAY(c.fecha), '-Ago') 
		  WHEN MONTH(c.fecha) = '9' 
		  THEN CONCAT(DAY(c.fecha), '-Sep') 
		  WHEN MONTH(c.fecha) = '10' 
		  THEN CONCAT(DAY(c.fecha), '-Oct') 
		  WHEN MONTH(c.fecha) = '11' 
		  THEN CONCAT(DAY(c.fecha), '-Nov') 
		  ELSE CONCAT(DAY(c.fecha), '-Dic') 
		END AS fec2,
		c.guia,
		a.modelo,
		a.nombre,
		a.cod_color,
		a.color,
		SUM(
		  CASE
			WHEN a.cod_talla = '1' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t1,
		SUM(
		  CASE
			WHEN a.cod_talla = '2' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t2,
		SUM(
		  CASE
			WHEN a.cod_talla = '3' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t3,
		SUM(
		  CASE
			WHEN a.cod_talla = '4' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t4,
		SUM(
		  CASE
			WHEN a.cod_talla = '5' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t5,
		SUM(
		  CASE
			WHEN a.cod_talla = '6' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t6,
		SUM(
		  CASE
			WHEN a.cod_talla = '7' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t7,
		SUM(
		  CASE
			WHEN a.cod_talla = '8' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t8,
		SUM(sd.inicio) AS total_und,
		ROUND(SUM(sd.inicio) / 12, 2) AS total_docenas,
		ps.precio_doc,
		ROUND(
		  (
			(SUM(sd.inicio) / 12) * ps.precio_doc
		  ),
		  2
		) AS total_soles 
	  FROM
		cierresjf c 
		LEFT JOIN cierres_detallejf sd 
		  ON c.codigo = sd.codigo 
		LEFT JOIN articulojf a 
		  ON sd.articulo = a.articulo 
		LEFT JOIN precio_serviciojf ps 
		  ON c.taller = ps.taller 
		  AND a.modelo = ps.modelo 
		LEFT JOIN sectorjf s 
		  ON c.taller = s.cod_sector 
	  WHERE DATE(c.fecha) BETWEEN '" . $inicio . "' 
		AND '" . $fin . "' 
		AND c.taller = '" . $sector . "' 
	  GROUP BY sd.codigo,
		a.modelo,
		a.nombre,
		a.cod_color,
		a.color 
		ORDER BY c.guia,
  c.fecha,
  a.modelo,
  a.color ");


		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlVerSumaPagos($inicio, $fin, $sector)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		c.taller,
		SUM(
		  CASE
			WHEN a.cod_talla = '1' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t1,
		SUM(
		  CASE
			WHEN a.cod_talla = '2' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t2,
		SUM(
		  CASE
			WHEN a.cod_talla = '3' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t3,
		SUM(
		  CASE
			WHEN a.cod_talla = '4' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t4,
		SUM(
		  CASE
			WHEN a.cod_talla = '5' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t5,
		SUM(
		  CASE
			WHEN a.cod_talla = '6' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t6,
		SUM(
		  CASE
			WHEN a.cod_talla = '7' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t7,
		SUM(
		  CASE
			WHEN a.cod_talla = '8' 
			THEN sd.inicio 
			ELSE 0 
		  END
		) AS t8,
		SUM(sd.inicio) AS total_und,
		ROUND(SUM(sd.inicio) / 12, 2) AS total_docenas 
	  FROM
		cierresjf c 
		LEFT JOIN cierres_detallejf sd 
		  ON c.codigo = sd.codigo 
		LEFT JOIN articulojf a 
		  ON sd.articulo = a.articulo 
		LEFT JOIN precio_serviciojf ps 
		  ON c.taller = ps.taller 
		  AND a.modelo = ps.modelo 
		LEFT JOIN sectorjf s 
		  ON c.taller = s.cod_sector 
	  WHERE DATE(c.fecha) BETWEEN '" . $inicio . "' 
		AND '" . $fin . "' 
	  AND c.taller='" . $sector . "'
	  GROUP BY c.taller ;");


		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasServicios($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			se.*,
			s.nom_sector,
			u.nombre 
		  FROM
			$tabla se 
			LEFT JOIN sectorjf s 
			  ON se.taller = s.cod_sector 
			LEFT JOIN usuariosjf u 
			  ON se.usuario = u.id 
			  WHERE YEAR(se.fecha) = YEAR(NOW())
		  ORDER BY se.id ASC ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre FROM  $tabla se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id WHERE DATE(se.fecha) like '%$fechaFinal%'");

			$stmt->bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$fechaActual = new DateTime();
			$fechaActual->add(new DateInterval("P1D"));
			$fechaActualMasUno = $fechaActual->format("Y-m-d");

			$fechaFinal2 = new DateTime($fechaFinal);
			$fechaFinal2->add(new DateInterval("P1D"));
			$fechaFinalMasUno = $fechaFinal2->format("Y-m-d");

			if ($fechaFinalMasUno == $fechaActualMasUno) {

				$stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre FROM  $tabla se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id WHERE DATE(se.fecha) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre FROM  $tabla se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id WHERE DATE(se.fecha) BETWEEN '$fechaInicial' AND '$fechaFinal'");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasVerServicios($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			sd.codigo,
			DATE(s.fecha) AS fechas,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color,
			se.cod_sector,
			se.nom_sector,
			SUM(
			  CASE
				WHEN a.cod_talla = '1' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t1,
			SUM(
			  CASE
				WHEN a.cod_talla = '2' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t2,
			SUM(
			  CASE
				WHEN a.cod_talla = '3' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t3,
			SUM(
			  CASE
				WHEN a.cod_talla = '4' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t4,
			SUM(
			  CASE
				WHEN a.cod_talla = '5' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t5,
			SUM(
			  CASE
				WHEN a.cod_talla = '6' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t6,
			SUM(
			  CASE
				WHEN a.cod_talla = '7' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t7,
			SUM(
			  CASE
				WHEN a.cod_talla = '8' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t8,
			SUM(sd.cantidad) AS total 
		  FROM
			servicios_detallejf sd 
			LEFT JOIN articulojf a 
			  ON sd.articulo = a.articulo 
			LEFT JOIN serviciosjf s 
			  ON sd.codigo = s.codigo 
			LEFT JOIN sectorjf se 
			  ON s.taller = se.cod_sector 
			  WHERE YEAR(s.fecha) = YEAR(NOW())
		  GROUP BY sd.codigo,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color ORDER BY sd.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			sd.codigo,
			DATE(s.fecha) AS fechas,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color,
			se.cod_sector,
			se.nom_sector,
			SUM(
			  CASE
				WHEN a.cod_talla = '1' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t1,
			SUM(
			  CASE
				WHEN a.cod_talla = '2' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t2,
			SUM(
			  CASE
				WHEN a.cod_talla = '3' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t3,
			SUM(
			  CASE
				WHEN a.cod_talla = '4' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t4,
			SUM(
			  CASE
				WHEN a.cod_talla = '5' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t5,
			SUM(
			  CASE
				WHEN a.cod_talla = '6' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t6,
			SUM(
			  CASE
				WHEN a.cod_talla = '7' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t7,
			SUM(
			  CASE
				WHEN a.cod_talla = '8' 
				THEN sd.cantidad 
				ELSE 0 
			  END
			) AS t8,
			SUM(sd.cantidad) AS total 
		  FROM
			servicios_detallejf sd 
			LEFT JOIN articulojf a 
			  ON sd.articulo = a.articulo 
			LEFT JOIN serviciosjf s 
			  ON sd.codigo = s.codigo 
			LEFT JOIN sectorjf se 
			  ON s.taller = se.cod_sector 
			WHERE DATE(s.fecha) like '%$fechaFinal%'
		  GROUP BY sd.codigo,
			a.modelo,
			a.nombre,
			a.cod_color,
			a.color ");

			$stmt->bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$fechaActual = new DateTime();
			$fechaActual->add(new DateInterval("P1D"));
			$fechaActualMasUno = $fechaActual->format("Y-m-d");

			$fechaFinal2 = new DateTime($fechaFinal);
			$fechaFinal2->add(new DateInterval("P1D"));
			$fechaFinalMasUno = $fechaFinal2->format("Y-m-d");

			if ($fechaFinalMasUno == $fechaActualMasUno) {

				$stmt = Conexion::conectar()->prepare("SELECT 
				sd.codigo,
				DATE(s.fecha) AS fechas,
				a.modelo,
				a.nombre,
				a.cod_color,
				a.color,
				se.cod_sector,
				se.nom_sector,
				SUM(
				  CASE
					WHEN a.cod_talla = '1' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t1,
				SUM(
				  CASE
					WHEN a.cod_talla = '2' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t2,
				SUM(
				  CASE
					WHEN a.cod_talla = '3' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t3,
				SUM(
				  CASE
					WHEN a.cod_talla = '4' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t4,
				SUM(
				  CASE
					WHEN a.cod_talla = '5' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t5,
				SUM(
				  CASE
					WHEN a.cod_talla = '6' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t6,
				SUM(
				  CASE
					WHEN a.cod_talla = '7' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t7,
				SUM(
				  CASE
					WHEN a.cod_talla = '8' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t8,
				SUM(sd.cantidad) AS total 
			  FROM
				servicios_detallejf sd 
				LEFT JOIN articulojf a 
				  ON sd.articulo = a.articulo 
				LEFT JOIN serviciosjf s 
				  ON sd.codigo = s.codigo 
				LEFT JOIN sectorjf se 
				  ON s.taller = se.cod_sector 
				WHERE DATE(s.fecha) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'
			  GROUP BY sd.codigo,
				a.modelo,
				a.nombre,
				a.cod_color,
				a.color");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT 
				sd.codigo,
				DATE(s.fecha) AS fechas,
				a.modelo,
				a.nombre,
				a.cod_color,
				a.color,
				se.cod_sector,
				se.nom_sector,
				SUM(
				  CASE
					WHEN a.cod_talla = '1' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t1,
				SUM(
				  CASE
					WHEN a.cod_talla = '2' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t2,
				SUM(
				  CASE
					WHEN a.cod_talla = '3' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t3,
				SUM(
				  CASE
					WHEN a.cod_talla = '4' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t4,
				SUM(
				  CASE
					WHEN a.cod_talla = '5' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t5,
				SUM(
				  CASE
					WHEN a.cod_talla = '6' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t6,
				SUM(
				  CASE
					WHEN a.cod_talla = '7' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t7,
				SUM(
				  CASE
					WHEN a.cod_talla = '8' 
					THEN sd.cantidad 
					ELSE 0 
				  END
				) AS t8,
				SUM(sd.cantidad) AS total 
			  FROM
				servicios_detallejf sd 
				LEFT JOIN articulojf a 
				  ON sd.articulo = a.articulo 
				LEFT JOIN serviciosjf s 
				  ON sd.codigo = s.codigo 
				LEFT JOIN sectorjf se 
				  ON s.taller = se.cod_sector 
				WHERE DATE(s.fecha) BETWEEN '$fechaInicial' AND '$fechaFinal'

			  GROUP BY sd.codigo,
				a.modelo,
				a.nombre,
				a.cod_color,
				a.color ");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	static public function mdlPagarServicio($valor1, $valor2)
	{

		$sql = "UPDATE pago_serviciosjf SET estado_pago = :estado_pago WHERE id = :id ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":estado_pago", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":id", $valor2, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	// TRAER EL PRIMER SERVICIO CREADO POR EVENTO PARA MANDAR DE ALMACEN CORTE
	static public function mdlPrimerServicio($taller)
	{
		$sql = "SELECT 
				codigo 
			FROM
				serviciosjf 
			WHERE taller = :taller
				AND DATE(fecha) = DATE(NOW()) 
			ORDER BY id ASC 
			LIMIT 1 ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":taller", $taller, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*
	* Obtener el servicio del día para un taller o crear uno nuevo si no existe
	*/
	static public function mdlObtenerOCrearServicioDelDia($taller, $usuario)
	{
		$primerServicio = self::mdlPrimerServicio($taller);
		if ($primerServicio && isset($primerServicio["codigo"])) {
			return $primerServicio["codigo"];
		}

		$ultimo = self::mdlUltimoServicio("serviciosjf");
		$ultimoNumero = ($ultimo && isset($ultimo["ultimo_codigo"])) ? (int) $ultimo["ultimo_codigo"] : 1;
		$codigo = $taller . str_pad($ultimoNumero, 4, "0", STR_PAD_LEFT);

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();

		$datos = array(
			"codigo" => $codigo,
			"taller" => $taller,
			"usuario" => $usuario,
			"total" => 0,
			"fecha" => $fecha->format("Y-m-d H:i:s"),
			"estado" => "ACTIVO"
		);

		if (self::mdlGuardarServicios("serviciosjf", $datos) === "ok") {
			return $codigo;
		}

		return null;
	}


	/* 
	* MOSTRAR PRODUCCION
	*/
	static public function mdlMostrarEtiquetas($id, $sector)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		s.id AS id_sector,
		s.cod_sector,
		s.nom_sector,
		s.pago,
		p.unidades,
		p.docenas,
		p.precio,
		p.total 
	  FROM
		sectorjf s 
		LEFT JOIN 
		  (SELECT 
			p.id,
			'taller1' AS taller,
			p.taller1 AS unidades,
			ROUND(p.taller1 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller1 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id 
		  UNION
		  SELECT 
			p.id,
			'taller2' AS taller,
			p.taller2 AS unidades,
			ROUND(p.taller2 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller2 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id 
		  UNION
		  SELECT 
			p.id,
			'taller3' AS taller,
			p.taller3 AS unidades,
			ROUND(p.taller3 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller3 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id
		  UNION
		  SELECT 
			p.id,
			'taller4' AS taller,
			p.taller4 AS unidades,
			ROUND(p.taller4 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller4 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id
		  UNION
		  SELECT 
			p.id,
			'taller5' AS taller,
			p.taller5 AS unidades,
			ROUND(p.taller5 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller5 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id
		  UNION
		  SELECT 
			p.id,
			'taller6' AS taller,
			p.taller6 AS unidades,
			ROUND(p.taller6 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller6 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id 
		  UNION
		  SELECT 
			p.id,
			'taller7' AS taller,
			p.taller7 AS unidades,
			ROUND(p.taller7 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller7 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id
		  UNION
		  SELECT 
			p.id,
			'taller8' AS taller,
			p.taller8 AS unidades,
			ROUND(p.taller8 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller8 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id 
		  UNION
		  SELECT 
			p.id,
			'taller9' AS taller,
			p.taller9 AS unidades,
			ROUND(p.taller9 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller9 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id
		  UNION
		  SELECT 
			p.id,
			'taller10' AS taller,
			p.taller10 AS unidades,
			ROUND(p.taller10 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller10 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id
		  UNION
		  SELECT 
			p.id,
			'taller11' AS taller,
			p.taller11 AS unidades,
			ROUND(p.taller11 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller11 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id 
		  UNION
		  SELECT 
			p.id,
			'taller12' AS taller,
			p.taller12 AS unidades,
			ROUND(p.taller12 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller12 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id
		  UNION
		  SELECT 
			p.id,
			'taller13' AS taller,
			p.taller13 AS unidades,
			ROUND(p.taller13 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller13 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id 
		  UNION
		  SELECT 
			p.id,
			'taller14' AS taller,
			p.taller14 AS unidades,
			ROUND(p.taller14 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller14 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id 
		  UNION
		  SELECT 
			p.id,
			'taller15' AS taller,
			p.taller15 AS unidades,
			ROUND(p.taller15 / 12, 2) AS docenas,
			'0.30' AS precio,
			ROUND(p.taller15 / 12 * 0.3, 2) AS total 
		  FROM
			pago_serviciosjf p 
		  WHERE id = :id) AS p 
		  ON s.pago = p.taller 
		  WHERE s.cod_sector = :sector");

		$stmt->bindParam(":id", $id, PDO::PARAM_STR);
		$stmt->bindParam(":sector", $sector, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/***************************************
	 * Actualizar la guia en la tabla servicios
	 ***************************************/
	static public function mdlActualizarGuia($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE serviciosjf SET guia = :guia WHERE codigo = :codigo");

		$stmt->bindParam(":guia", $datos["guia"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	static public function mdlAniosHistorialServicios()
	{
		$stmt = Conexion::conectar()->prepare(
			"SELECT DISTINCT LEFT(fecha, 4) AS anio
			FROM serviciosjf
			WHERE fecha IS NOT NULL AND fecha <> '' AND LEFT(fecha, 4) BETWEEN '2000' AND '2100'
			ORDER BY anio DESC"
		);
		$stmt->execute();
		$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;

		$anios = array();
		foreach ($filas as $fila) {
			$anios[] = (int) $fila["anio"];
		}

		return $anios;
	}

	static public function mdlHistorialAnualServicios($anio)
	{
		$anio = (int) $anio;
		$ini = $anio . "-01-01";
		$fin = ($anio + 1) . "-01-01";

		$pdo = Conexion::conectar();

		$stmt = $pdo->prepare(
			"SELECT
				se.codigo,
				se.guia,
				se.taller,
				se.usuario,
				se.fecha,
				s.nom_sector,
				u.nombre AS usuario_nombre
			FROM serviciosjf se
			LEFT JOIN sectorjf s ON se.taller = s.cod_sector
			LEFT JOIN usuariosjf u ON se.usuario = u.id
			WHERE se.fecha >= :ini AND se.fecha < :fin
			ORDER BY se.fecha, se.codigo"
		);
		$stmt->bindValue(":ini", $ini, PDO::PARAM_STR);
		$stmt->bindValue(":fin", $fin, PDO::PARAM_STR);
		$stmt->execute();
		$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;

		if (count($servicios) === 0) {
			return array("cabeceras" => array(), "detalles" => array());
		}

		$codigos = array();
		$porCodigo = array();
		foreach ($servicios as $s) {
			$cod = (string) $s["codigo"];
			$codigos[] = $cod;
			$porCodigo[$cod] = $s;
		}

		$ph = implode(",", array_fill(0, count($codigos), "?"));
		$stmt = $pdo->prepare(
			"SELECT id, codigo, articulo, cantidad, saldo
			FROM servicios_detallejf
			WHERE codigo IN ($ph) AND cantidad > 0"
		);
		$stmt->execute($codigos);
		$lineas = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt = null;

		$artIds = array();
		$detIds = array();
		foreach ($lineas as $ln) {
			$artIds[(string) $ln["articulo"]] = true;
			$detIds[(string) $ln["id"]] = true;
		}

		$articulos = array();
		$artKeys = array_keys($artIds);
		if (count($artKeys) > 0) {
			$phArt = implode(",", array_fill(0, count($artKeys), "?"));
			$stmt = $pdo->prepare(
				"SELECT articulo, modelo, nombre, color, talla
				FROM articulojf
				WHERE articulo IN ($phArt)"
			);
			$stmt->execute($artKeys);
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
				$articulos[(string) $a["articulo"]] = $a;
			}
			$stmt = null;
		}

		$cierresCab = $pdo->query("SELECT codigo, LEFT(fecha, 10) AS fecha FROM cierresjf")->fetchAll(PDO::FETCH_ASSOC);
		$mapaCierreFecha = array();
		foreach ($cierresCab as $c) {
			$mapaCierreFecha[(string) $c["codigo"]] = $c["fecha"];
		}

		$cierresDet = $pdo->query("SELECT codigo, cod_servicio FROM cierres_detallejf")->fetchAll(PDO::FETCH_ASSOC);
		$fechasPorDetalle = array();
		foreach ($cierresDet as $cd) {
			$idDet = trim((string) $cd["cod_servicio"]);
			if ($idDet === "" || !isset($detIds[$idDet])) {
				continue;
			}
			$f = isset($mapaCierreFecha[(string) $cd["codigo"]]) ? $mapaCierreFecha[(string) $cd["codigo"]] : null;
			if ($f === null || $f === "" || $f === "0000-00-00") {
				continue;
			}
			$f = substr($f, 0, 10);
			if (!isset($fechasPorDetalle[$idDet])) {
				$fechasPorDetalle[$idDet] = array("primero" => $f, "ultimo" => $f);
			} else {
				if ($f < $fechasPorDetalle[$idDet]["primero"]) {
					$fechasPorDetalle[$idDet]["primero"] = $f;
				}
				if ($f > $fechasPorDetalle[$idDet]["ultimo"]) {
					$fechasPorDetalle[$idDet]["ultimo"] = $f;
				}
			}
		}

		$hoy = date("Y-m-d");
		$detalles = array();
		$aggCab = array();

		foreach ($lineas as $ln) {
			$cod = (string) $ln["codigo"];
			if (!isset($porCodigo[$cod])) {
				continue;
			}
			$cab = $porCodigo[$cod];
			$art = isset($articulos[(string) $ln["articulo"]]) ? $articulos[(string) $ln["articulo"]] : array();
			$idDet = (string) $ln["id"];
			$enviado = (int) $ln["cantidad"];
			$saldo = (int) $ln["saldo"];
			$cerrado = $enviado - $saldo;
			$fechaEnvio = substr((string) $cab["fecha"], 0, 10);
			$primer = isset($fechasPorDetalle[$idDet]) ? $fechasPorDetalle[$idDet]["primero"] : null;
			$ultimo = isset($fechasPorDetalle[$idDet]) ? $fechasPorDetalle[$idDet]["ultimo"] : null;

			if ($saldo <= 0) {
				$estado = "Cerrado";
				$fechaFin = $ultimo;
				$diasRef = $fechaFin;
			} elseif ($cerrado > 0) {
				$estado = "Parcial";
				$fechaFin = null;
				$diasRef = $hoy;
			} else {
				$estado = "Pendiente";
				$fechaFin = null;
				$diasRef = $hoy;
			}

			$dias = null;
			if ($fechaEnvio && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEnvio) && $diasRef && preg_match('/^\d{4}-\d{2}-\d{2}$/', $diasRef)) {
				$dias = (int) round((strtotime($diasRef) - strtotime($fechaEnvio)) / 86400);
			}

			$tallerNom = $cab["taller"] . " - " . (isset($cab["nom_sector"]) ? $cab["nom_sector"] : "");

			$detalles[] = array(
				"codigo" => $cod,
				"guia" => $cab["guia"],
				"taller" => $tallerNom,
				"usuario" => $cab["usuario_nombre"],
				"fecha_envio" => $fechaEnvio,
				"anio_envio" => (int) substr($fechaEnvio, 0, 4),
				"mes_envio" => (int) substr($fechaEnvio, 5, 2),
				"articulo" => $ln["articulo"],
				"modelo" => isset($art["modelo"]) ? $art["modelo"] : "",
				"nombre" => isset($art["nombre"]) ? $art["nombre"] : "",
				"color" => isset($art["color"]) ? $art["color"] : "",
				"talla" => isset($art["talla"]) ? $art["talla"] : "",
				"enviado" => $enviado,
				"cerrado" => $cerrado,
				"saldo" => $saldo,
				"fecha_primer_cierre" => $primer,
				"fecha_ultimo_cierre" => $ultimo,
				"fecha_fin" => $fechaFin,
				"dias" => $dias,
				"estado" => $estado
			);

			if (!isset($aggCab[$cod])) {
				$aggCab[$cod] = array(
					"codigo" => $cod,
					"guia" => $cab["guia"],
					"taller" => $tallerNom,
					"usuario" => $cab["usuario_nombre"],
					"fecha_envio" => $fechaEnvio,
					"anio_envio" => (int) substr($fechaEnvio, 0, 4),
					"mes_envio" => (int) substr($fechaEnvio, 5, 2),
					"enviado" => 0,
					"cerrado" => 0,
					"saldo" => 0,
					"fecha_primer_cierre" => null,
					"fecha_ultimo_cierre" => null
				);
			}
			$aggCab[$cod]["enviado"] += $enviado;
			$aggCab[$cod]["cerrado"] += $cerrado;
			$aggCab[$cod]["saldo"] += $saldo;
			if ($primer) {
				if ($aggCab[$cod]["fecha_primer_cierre"] === null || $primer < $aggCab[$cod]["fecha_primer_cierre"]) {
					$aggCab[$cod]["fecha_primer_cierre"] = $primer;
				}
			}
			if ($ultimo) {
				if ($aggCab[$cod]["fecha_ultimo_cierre"] === null || $ultimo > $aggCab[$cod]["fecha_ultimo_cierre"]) {
					$aggCab[$cod]["fecha_ultimo_cierre"] = $ultimo;
				}
			}
		}

		$cabeceras = array();
		foreach ($servicios as $s) {
			$cod = (string) $s["codigo"];
			if (!isset($aggCab[$cod])) {
				continue;
			}
			$row = $aggCab[$cod];
			if ($row["saldo"] <= 0) {
				$row["estado"] = "Cerrado";
				$row["fecha_fin"] = $row["fecha_ultimo_cierre"];
				$diasRef = $row["fecha_fin"];
			} elseif ($row["cerrado"] > 0) {
				$row["estado"] = "Parcial";
				$row["fecha_fin"] = null;
				$diasRef = $hoy;
			} else {
				$row["estado"] = "Pendiente";
				$row["fecha_fin"] = null;
				$diasRef = $hoy;
			}
			$row["dias"] = null;
			if ($row["fecha_envio"] && preg_match('/^\d{4}-\d{2}-\d{2}$/', $row["fecha_envio"]) && $diasRef && preg_match('/^\d{4}-\d{2}-\d{2}$/', $diasRef)) {
				$row["dias"] = (int) round((strtotime($diasRef) - strtotime($row["fecha_envio"])) / 86400);
			}
			$cabeceras[] = $row;
		}

		usort($detalles, function ($a, $b) {
			$cmp = strcmp((string) $a["fecha_envio"], (string) $b["fecha_envio"]);
			if ($cmp !== 0) {
				return $cmp;
			}
			$cmp = strcmp((string) $a["codigo"], (string) $b["codigo"]);
			if ($cmp !== 0) {
				return $cmp;
			}
			$cmp = strcmp((string) $a["modelo"], (string) $b["modelo"]);
			if ($cmp !== 0) {
				return $cmp;
			}
			$cmp = strcmp((string) $a["color"], (string) $b["color"]);
			if ($cmp !== 0) {
				return $cmp;
			}
			return strcmp((string) $a["talla"], (string) $b["talla"]);
		});

		return array("cabeceras" => $cabeceras, "detalles" => $detalles);
	}

	static public function mdlHistorialCabeceraServicios($anio)
	{
		$data = self::mdlHistorialAnualServicios($anio);
		return $data["cabeceras"];
	}

	static public function mdlHistorialDetalleServicios($anio)
	{
		$data = self::mdlHistorialAnualServicios($anio);
		return $data["detalles"];
	}
}
