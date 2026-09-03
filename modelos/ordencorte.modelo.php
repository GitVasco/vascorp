<?php

require_once "conexion.php";

class ModeloOrdenCorte
{

	/* 
	* Método para mostrar los datos de las ordenes de corte
	*/
	static public function mdlMostarOrdenCorte($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item ORDER BY id DESC");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
															oc.codigo,
															oc.usuario,
															u.nombre,
															oc.total,
															oc.saldo,
															oc.configuracion,
															oc.estado,
															DATE(oc.fecha) AS fecha 
														FROM
															ordencortejf oc 
															LEFT JOIN usuariosjf u 
																ON oc.usuario = u.id");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* Método para sacar el ultimo id de la tarjeta
	*/
	static public function mdlUltimoCodigoOC($tabla)
	{

		$sql = "SELECT 
                        MAX(oc.codigo) AS ultimo_codigo 
                    FROM
                        $tabla oc";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/* 
	* Método para pedir último Id de orden de corte
	*/
	static public function mdlUltimoId()
	{

		$sql = "SELECT MAX(codigo) AS ult_codigo
					FROM
						ordencortejf";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		# Retornamos un fetchAll por ser más de una línea la que necesitamos devolver
		return $stmt->fetchAll();

		$stmt = null;
	}

	static public function mdlSiguienteCodigoOC()
	{

		$stmt = Conexion::conectar()->prepare("SELECT IFNULL(MAX(codigo), 1000) + 1 AS siguiente FROM ordencortejf");

		$stmt->execute();

		$fila = $stmt->fetch();

		$stmt = null;

		return $fila ? (int) $fila["siguiente"] : 1001;
	}

	static public function mdlExisteCodigoOC($codigo)
	{

		$stmt = Conexion::conectar()->prepare("SELECT id FROM ordencortejf WHERE codigo = :codigo LIMIT 1");

		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_INT);

		$stmt->execute();

		$fila = $stmt->fetch();

		$stmt = null;

		return !empty($fila);
	}

	/* 
	* Guardar cabecera de ORDENES DE CORTE
	*/
	static public function mdlGuardarOrdenCorte($tabla, $datos)
	{

		$sql = "INSERT INTO $tabla (
											codigo,
											usuario,
											total,
											saldo,
											configuracion,
											estado
										) 
										VALUES
											(
											:codigo,
											:usuario,
											:total,
											:saldo,
											:configuracion,
											:estado
											)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_INT);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_INT);
		$stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_STR);
		$stmt->bindParam(":configuracion", $datos["configuracion"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* Guardar detalle de las ordenes de corte
	*/
	static public function mdlGuardarDetallesOrdenCorte($tabla, $datos)
	{

		$sql = "INSERT INTO $tabla (ordencorte, articulo, cantidad, saldo) 
		VALUES
		  (:ordencorte, :articulo, :cantidad, :saldo)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":ordencorte", $datos["ordencorte"], PDO::PARAM_INT);
		$stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_INT);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
		$stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_INT);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* Método para Mostrar los detalles de orden de corte
	*/
	static public function mdlMostraDetallesOrdenCorte($tabla, $item, $valor)
	{

		$sql = "SELECT do.*,a.articulo,a.nombre,a.marca,a.talla,a.color FROM $tabla do LEFT JOIN articulojf a ON do.articulo=a.articulo WHERE $item=:$item  ORDER BY do.id DESC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}


	/* 
	* Método para editar las ventas
	*/
	static public function mdlEditarOrdenCorte($tabla, $datos)
	{

		$sql = "UPDATE $tabla SET usuario=:usuario, total=:total, saldo=:saldo, lastUpdate=:lastUpdate WHERE codigo=:codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_STR);
		$stmt->bindParam(":lastUpdate", $datos["lastUpdate"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* Método para eliminar los detalles de la orden de corte
	*/
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

	/* 
	* Método para eliminar la orden de corte
	*/
	static public function mdlEliminarOrdenCorte($tabla, $item, $valor)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE $item = $valor");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* Método para vizualizar cabecera orden de corte
	*/
	static public function mdlVisualizaOrdenCorte($tabla, $item, $valor)
	{

		$sql = "SELECT 
					oc.id,
					oc.codigo,
					oc.usuario,
					u.nombre,
					oc.configuracion,
					oc.total,
					oc.saldo,
					oc.estado,
					DATE(oc.fecha) AS fecha 
				FROM
					$tabla oc 
					LEFT JOIN usuariosjf u 
					ON oc.usuario = u.id 
				WHERE oc.$item = $valor";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/* 
	* Método para vizualizar detalle de la orden de corte
	*/
	static public function mdlVisualizarOrdenCorteDetalle($tabla, $item, $valor)
	{
		if ($valor != null) {

			$sql = "SELECT 
			doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color,
			SUM(
			  CASE
				WHEN a.cod_talla = '1' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t1,
			SUM(
			  CASE
				WHEN a.cod_talla = '2' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t2,
			SUM(
			  CASE
				WHEN a.cod_talla = '3' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t3,
			SUM(
			  CASE
				WHEN a.cod_talla = '4' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t4,
			SUM(
			  CASE
				WHEN a.cod_talla = '5' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t5,
			SUM(
			  CASE
				WHEN a.cod_talla = '6' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t6,
			SUM(
			  CASE
				WHEN a.cod_talla = '7' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t7,
			SUM(
			  CASE
				WHEN a.cod_talla = '8' 
				THEN doc.saldo 
				ELSE 0 
			  END
			) AS t8,
			SUM(doc.saldo) AS subtotal 
		  FROM
			$tabla doc 
			LEFT JOIN articulojf a 
			  ON doc.articulo = a.articulo 
		  WHERE doc.$item = $valor
			AND doc.estado=0 
		  GROUP BY doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color ";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {
			$sql = "SELECT 
			doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color,
			SUM(
			CASE
				WHEN a.cod_talla = '1' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t1,
			SUM(
			CASE
				WHEN a.cod_talla = '2' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t2,
			SUM(
			CASE
				WHEN a.cod_talla = '3' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t3,
			SUM(
			CASE
				WHEN a.cod_talla = '4' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t4,
			SUM(
			CASE
				WHEN a.cod_talla = '5' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t5,
			SUM(
			CASE
				WHEN a.cod_talla = '6' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t6,
			SUM(
			CASE
				WHEN a.cod_talla = '7' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t7,
			SUM(
			CASE
				WHEN a.cod_talla = '8' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t8,
			SUM(doc.saldo) AS subtotal 
		FROM
			$tabla doc 
			LEFT JOIN articulojf a 
			ON doc.articulo = a.articulo 
		GROUP BY doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		}


		$stmt = null;
	}


	/* 
	* Método para vizualizar detalle de la orden de corte
	*/
	static public function mdlVisualizarOrdenCorteDetalleCantidad($tabla, $item, $valor)
	{
		if ($valor != null) {

			$sql = "SELECT 
				doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color,
				DATE(oc.fecha) as fechas,
				SUM(
				CASE
					WHEN a.cod_talla = '1' 
					THEN doc.cantidad 
					ELSE '' 
				END
				) AS t1,
				SUM(
				CASE
					WHEN a.cod_talla = '2' 
					THEN doc.cantidad 
					ELSE '' 
				END
				) AS t2,
				SUM(
				CASE
					WHEN a.cod_talla = '3' 
					THEN doc.cantidad 
					ELSE ''
				END
				) AS t3,
				SUM(
				CASE
					WHEN a.cod_talla = '4' 
					THEN doc.cantidad 
					ELSE '' 
				END
				) AS t4,
				SUM(
				CASE
					WHEN a.cod_talla = '5' 
					THEN doc.cantidad 
					ELSE '' 
				END
				) AS t5,
				SUM(
				CASE
					WHEN a.cod_talla = '6' 
					THEN doc.cantidad 
					ELSE '' 
				END
				) AS t6,
				SUM(
				CASE
					WHEN a.cod_talla = '7' 
					THEN doc.cantidad 
					ELSE ''
				END
				) AS t7,
				SUM(
				CASE
					WHEN a.cod_talla = '8' 
					THEN doc.cantidad 
					ELSE ''
				END
				) AS t8,
				SUM(doc.cantidad) AS subtotal 
			FROM
				$tabla doc 
				LEFT JOIN articulojf a 
				ON doc.articulo = a.articulo 
				LEFT JOIN ordencortejf oc 
				   ON doc.ordencorte = oc.codigo 
				WHERE doc.$item = $valor
			GROUP BY doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color ORDER BY doc.id ASC";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {
			$sql = "SELECT 
			doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color,
			DATE(oc.fecha) as fechas,
			SUM(
			CASE
				WHEN a.cod_talla = '1' 
				THEN doc.cantidad 
				ELSE '' 
			END
			) AS t1,
			SUM(
			CASE
				WHEN a.cod_talla = '2' 
				THEN doc.cantidad 
				ELSE '' 
			END
			) AS t2,
			SUM(
			CASE
				WHEN a.cod_talla = '3' 
				THEN doc.cantidad 
				ELSE ''
			END
			) AS t3,
			SUM(
			CASE
				WHEN a.cod_talla = '4' 
				THEN doc.cantidad 
				ELSE '' 
			END
			) AS t4,
			SUM(
			CASE
				WHEN a.cod_talla = '5' 
				THEN doc.cantidad 
				ELSE '' 
			END
			) AS t5,
			SUM(
			CASE
				WHEN a.cod_talla = '6' 
				THEN doc.cantidad 
				ELSE '' 
			END
			) AS t6,
			SUM(
			CASE
				WHEN a.cod_talla = '7' 
				THEN doc.cantidad 
				ELSE '' 
			END
			) AS t7,
			SUM(
			CASE
				WHEN a.cod_talla = '8' 
				THEN doc.cantidad 
				ELSE '' 
			END
			) AS t8,
			SUM(doc.cantidad) AS subtotal 
		FROM
			$tabla doc 
			LEFT JOIN articulojf a 
			ON doc.articulo = a.articulo 
			LEFT JOIN ordencortejf oc 
   			ON doc.ordencorte = oc.codigo 
		GROUP BY doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color ORDER BY doc.id ASC";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		}


		$stmt = null;
	}

	/*=============================================	
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasOrdenCortes($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null" || $fechaInicial == null) {

			$stmt = Conexion::conectar()->prepare("SELECT oc.codigo,
			oc.usuario,
			u.nombre,
			oc.total,
			oc.saldo,
			oc.configuracion,
			oc.estado,
			DATE(oc.fecha) AS fecha 
		FROM
			ordencortejf oc 
			LEFT JOIN usuariosjf u 
				ON oc.usuario = u.id 
				WHERE YEAR(oc.fecha)=YEAR(NOW()) 
				ORDER BY oc.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT oc.codigo,
			oc.usuario,
			u.nombre,
			oc.total,
			oc.saldo,
			oc.configuracion,
			oc.estado,
			DATE(oc.fecha) AS fecha 
		FROM
			ordencortejf oc 
			LEFT JOIN usuariosjf u 
				ON oc.usuario = u.id  WHERE oc.fecha like '%$fechaFinal%'");

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

				$stmt = Conexion::conectar()->prepare("SELECT oc.codigo,
				oc.usuario,
				u.nombre,
				oc.total,
				oc.saldo,
				oc.configuracion,
				oc.estado,
				DATE(oc.fecha) AS fecha 
			FROM
				ordencortejf oc 
				LEFT JOIN usuariosjf u 
					ON oc.usuario = u.id WHERE oc.fecha BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT oc.codigo,
				oc.usuario,
				u.nombre,
				oc.total,
				oc.saldo,
				oc.configuracion,
				oc.estado,
				DATE(oc.fecha) AS fecha 
			FROM
				ordencortejf oc 
				LEFT JOIN usuariosjf u 
					ON oc.usuario = u.id WHERE oc.fecha BETWEEN '$fechaInicial' AND '$fechaFinal'");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/* 
   * MOSTRAR ORDEN DE CORTE PENDIENTES Y ABIERTOS
   */
	static public function mdlOCPend()
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
										codigo,
										CONCAT('OC - ', codigo, ' / ', DATE(fecha)) AS ordencorte,
										DATE(fecha) AS fecha 
									FROM
										ordencortejf oc 
									WHERE oc.estado NOT IN ('Cerrado')
									ORDER BY fecha DESC");

		$stmt->execute();

		return $stmt->fetchall();
	}

	/* 
	* Método para editar las ventas
	*/
	static public function mdlEditarDetalleOrdenCorte($tabla, $datos)
	{

		$sql = "UPDATE $tabla SET cantidad=:cantidad, saldo=:saldo WHERE id=:id";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);
		$stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_STR);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}
	/* 
	* Método para Mostrar los detalles de orden de corte
	*/
	static public function mdlMostraDetalleOrdenCorte($tabla, $item, $valor)
	{

		$sql = "SELECT * FROM $tabla WHERE $item=:$item  ORDER BY id ASC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_INT);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/* 
	* Método para editar la cantidad total luego de editar
	*/
	static public function mdlEditarCantidadOC($datos)
	{

		$sql = "UPDATE 
						ordencortejf 
					SET
						usuario = :usuario,
						total = total + (:cambio),
						saldo = saldo + (:cambio),
						lastUpdate = :fecha 
					WHERE codigo = :codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":cambio", $datos["cambio"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["lastUpdate"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* Método para editar la cantidad total luego de agregar
	*/
	static public function mdlAgregarCantidadOC($datos)
	{

		$sql = "UPDATE 
				ordencortejf o 
				LEFT JOIN 
				(SELECT 
					ordencorte,
					SUM(cantidad) AS cant,
					SUM(saldo) AS saldo
				FROM
					detalles_ordencortejf doc 
				WHERE ordencorte = :codigo 
				GROUP BY ordencorte) AS doc 
				ON o.codigo = doc.ordencorte SET usuario = :usuario,
				total = doc.cant,
				saldo = doc.saldo,
				lastUpdate = :fecha 
			WHERE codigo = :codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["lastUpdate"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	static public function mdlRangoFechasOrdenCortesGeneral($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color,
			DATE(oc.fecha) as fechas,
			SUM(
			CASE
				WHEN a.cod_talla = '1' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t1,
			SUM(
			CASE
				WHEN a.cod_talla = '2' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t2,
			SUM(
			CASE
				WHEN a.cod_talla = '3' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t3,
			SUM(
			CASE
				WHEN a.cod_talla = '4' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t4,
			SUM(
			CASE
				WHEN a.cod_talla = '5' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t5,
			SUM(
			CASE
				WHEN a.cod_talla = '6' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t6,
			SUM(
			CASE
				WHEN a.cod_talla = '7' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t7,
			SUM(
			CASE
				WHEN a.cod_talla = '8' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t8,
			SUM(doc.saldo) AS subtotal 
		FROM
			$tabla doc 
			LEFT JOIN articulojf a 
			ON doc.articulo = a.articulo 
			LEFT JOIN ordencortejf oc 
   			ON doc.ordencorte = oc.codigo 
			   WHERE YEAR(oc.fecha) = YEAR(NOW())
			   AND doc.estado=0 
		GROUP BY doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color ORDER BY doc.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color,
			DATE(oc.fecha) as fechas,
			SUM(
			CASE
				WHEN a.cod_talla = '1' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t1,
			SUM(
			CASE
				WHEN a.cod_talla = '2' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t2,
			SUM(
			CASE
				WHEN a.cod_talla = '3' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t3,
			SUM(
			CASE
				WHEN a.cod_talla = '4' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t4,
			SUM(
			CASE
				WHEN a.cod_talla = '5' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t5,
			SUM(
			CASE
				WHEN a.cod_talla = '6' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t6,
			SUM(
			CASE
				WHEN a.cod_talla = '7' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t7,
			SUM(
			CASE
				WHEN a.cod_talla = '8' 
				THEN doc.saldo 
				ELSE 0 
			END
			) AS t8,
			SUM(doc.saldo) AS subtotal 
		FROM
			$tabla doc 
			LEFT JOIN articulojf a 
			ON doc.articulo = a.articulo 
			LEFT JOIN ordencortejf oc 
   			ON doc.ordencorte = oc.codigo 
			WHERE DATE(oc.fecha) = '$fechaFinal' AND doc.estado=0 
		GROUP BY doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color");

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
				doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color,
				DATE(oc.fecha) as fechas,
				SUM(
				CASE
					WHEN a.cod_talla = '1' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t1,
				SUM(
				CASE
					WHEN a.cod_talla = '2' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t2,
				SUM(
				CASE
					WHEN a.cod_talla = '3' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t3,
				SUM(
				CASE
					WHEN a.cod_talla = '4' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t4,
				SUM(
				CASE
					WHEN a.cod_talla = '5' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t5,
				SUM(
				CASE
					WHEN a.cod_talla = '6' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t6,
				SUM(
				CASE
					WHEN a.cod_talla = '7' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t7,
				SUM(
				CASE
					WHEN a.cod_talla = '8' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t8,
				SUM(doc.saldo) AS subtotal 
			FROM
				$tabla doc 
				LEFT JOIN articulojf a 
				ON doc.articulo = a.articulo 
				LEFT JOIN ordencortejf oc 
   				ON doc.ordencorte = oc.codigo 
				WHERE DATE(oc.fecha) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'  and doc.estado=0
			GROUP BY doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT 
				doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color,
				DATE(oc.fecha) as fechas,
				SUM(
				CASE
					WHEN a.cod_talla = '1' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t1,
				SUM(
				CASE
					WHEN a.cod_talla = '2' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t2,
				SUM(
				CASE
					WHEN a.cod_talla = '3' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t3,
				SUM(
				CASE
					WHEN a.cod_talla = '4' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t4,
				SUM(
				CASE
					WHEN a.cod_talla = '5' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t5,
				SUM(
				CASE
					WHEN a.cod_talla = '6' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t6,
				SUM(
				CASE
					WHEN a.cod_talla = '7' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t7,
				SUM(
				CASE
					WHEN a.cod_talla = '8' 
					THEN doc.saldo 
					ELSE 0 
				END
				) AS t8,
				SUM(doc.saldo) AS subtotal 
			FROM
				$tabla doc 
				LEFT JOIN articulojf a 
				ON doc.articulo = a.articulo 
				LEFT JOIN ordencortejf oc 
   				ON doc.ordencorte = oc.codigo 
			WHERE DATE(oc.fecha) BETWEEN '$fechaInicial' AND '$fechaFinal'  and doc.estado=0
			GROUP BY doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color ");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}


	static public function mdlRangoFechasOrdenCortesCantidad($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color,
			DATE(oc.fecha) as fechas,
			SUM(
			CASE
				WHEN a.cod_talla = '1' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t1,
			SUM(
			CASE
				WHEN a.cod_talla = '2' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t2,
			SUM(
			CASE
				WHEN a.cod_talla = '3' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t3,
			SUM(
			CASE
				WHEN a.cod_talla = '4' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t4,
			SUM(
			CASE
				WHEN a.cod_talla = '5' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t5,
			SUM(
			CASE
				WHEN a.cod_talla = '6' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t6,
			SUM(
			CASE
				WHEN a.cod_talla = '7' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t7,
			SUM(
			CASE
				WHEN a.cod_talla = '8' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t8,
			SUM(doc.cantidad) AS subtotal 
		FROM
			$tabla doc 
			LEFT JOIN articulojf a 
			ON doc.articulo = a.articulo 
			LEFT JOIN ordencortejf oc 
   			ON doc.ordencorte = oc.codigo 
			WHERE YEAR(oc.fecha)= YEAR(NOW())
		GROUP BY doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color ORDER BY doc.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color,
			DATE(oc.fecha) as fechas,
			SUM(
			CASE
				WHEN a.cod_talla = '1' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t1,
			SUM(
			CASE
				WHEN a.cod_talla = '2' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t2,
			SUM(
			CASE
				WHEN a.cod_talla = '3' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t3,
			SUM(
			CASE
				WHEN a.cod_talla = '4' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t4,
			SUM(
			CASE
				WHEN a.cod_talla = '5' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t5,
			SUM(
			CASE
				WHEN a.cod_talla = '6' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t6,
			SUM(
			CASE
				WHEN a.cod_talla = '7' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t7,
			SUM(
			CASE
				WHEN a.cod_talla = '8' 
				THEN doc.cantidad 
				ELSE 0 
			END
			) AS t8,
			SUM(doc.cantidad) AS subtotal 
		FROM
			$tabla doc 
			LEFT JOIN articulojf a 
			ON doc.articulo = a.articulo 
			LEFT JOIN ordencortejf oc 
   			ON doc.ordencorte = oc.codigo 
			WHERE DATE(oc.fecha) = '$fechaFinal'
		GROUP BY doc.ordencorte,
			a.modelo,
			a.nombre,
			a.color");

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
				doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color,
				DATE(oc.fecha) as fechas,
				SUM(
				CASE
					WHEN a.cod_talla = '1' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t1,
				SUM(
				CASE
					WHEN a.cod_talla = '2' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t2,
				SUM(
				CASE
					WHEN a.cod_talla = '3' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t3,
				SUM(
				CASE
					WHEN a.cod_talla = '4' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t4,
				SUM(
				CASE
					WHEN a.cod_talla = '5' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t5,
				SUM(
				CASE
					WHEN a.cod_talla = '6' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t6,
				SUM(
				CASE
					WHEN a.cod_talla = '7' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t7,
				SUM(
				CASE
					WHEN a.cod_talla = '8' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t8,
				SUM(doc.cantidad) AS subtotal 
			FROM
				$tabla doc 
				LEFT JOIN articulojf a 
				ON doc.articulo = a.articulo 
				LEFT JOIN ordencortejf oc 
   				ON doc.ordencorte = oc.codigo 
				WHERE DATE(oc.fecha) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'
			GROUP BY doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT 
				doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color,
				DATE(oc.fecha) as fechas,
				SUM(
				CASE
					WHEN a.cod_talla = '1' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t1,
				SUM(
				CASE
					WHEN a.cod_talla = '2' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t2,
				SUM(
				CASE
					WHEN a.cod_talla = '3' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t3,
				SUM(
				CASE
					WHEN a.cod_talla = '4' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t4,
				SUM(
				CASE
					WHEN a.cod_talla = '5' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t5,
				SUM(
				CASE
					WHEN a.cod_talla = '6' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t6,
				SUM(
				CASE
					WHEN a.cod_talla = '7' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t7,
				SUM(
				CASE
					WHEN a.cod_talla = '8' 
					THEN doc.cantidad 
					ELSE 0 
				END
				) AS t8,
				SUM(doc.cantidad) AS subtotal 
			FROM
				$tabla doc 
				LEFT JOIN articulojf a 
				ON doc.articulo = a.articulo 
				LEFT JOIN ordencortejf oc 
   				ON doc.ordencorte = oc.codigo 
			WHERE DATE(oc.fecha) BETWEEN '$fechaInicial' AND '$fechaFinal'
			GROUP BY doc.ordencorte,
				a.modelo,
				a.nombre,
				a.color ");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/* 
	* Método para vizualizar cabecera orden de corte
	*/
	static public function mdlCargarTarjetas($articulo, $cantidad)
	{

		$sql = "SELECT 
					dt.mat_pri,
					dt.consumo * $cantidad as consumo
				FROM
					detalles_tarjetajf dt 
				WHERE dt.articulo = '$articulo' ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/* 
	* Guardar articulos para articulos con tarjeta
	*/
	static public function mdlCargarArticulo($datos)
	{

		$sql = "INSERT INTO articulosorden (articulo, cantidad) 
		VALUES
		  (:articulo, :cantidad)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_INT);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* GEliminar datos 
	*/
	static public function mdlEliminarArticulo()
	{

		$sql = "DELETE FROM articulosorden ";

		$stmt = Conexion::conectar()->prepare($sql);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}
}
