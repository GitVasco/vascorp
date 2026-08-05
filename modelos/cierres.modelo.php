<?php

require_once "conexion.php";

class ModeloCierres
{

	/*=============================================
	MOSTRAR VENTAS
	=============================================*/

	static public function mdlMostrarCierres($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre  FROM  $tabla se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id  WHERE $item = :$item ");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT se.*, s.nom_sector,u.nombre FROM  $tabla se LEFT JOIN sectorjf s on se.taller = s.cod_sector LEFT JOIN usuariosjf u ON se.usuario = u.id  ORDER BY se.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	// Método para Mostrar los detalles de servicios
	static public function mdlMostraDetallesCierres($tabla, $item, $valor)
	{

		$sql = "SELECT * FROM $tabla WHERE $item=:$item ORDER BY id ASC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}


	// Método para guardar los servicios
	static public function mdlGuardarCierres($tabla, $datos)
	{

		$sql = "INSERT INTO $tabla(codigo,guia,usuario,taller,total,fecha,estado) VALUES (:codigo,:guia,:usuario,:taller,:total,:fecha,:estado)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":guia", $datos["guia"], PDO::PARAM_STR);
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

	// Método para actualizar los servicios totales
	static public function mdlActualizarServicioTotal()
	{

		$sql = "UPDATE 
					articulojf a 
					LEFT JOIN 
					(SELECT 
						a.articulo,
						a.servicio AS servicio_total,
						IFNULL(s.servicio, 0) AS servicio,
						IFNULL(c.cierre, 0) AS cierre,
						(
						IFNULL(s.servicio, 0) + IFNULL(c.cierre, 0)
						) AS servicio_real 
					FROM
						articulojf a 
						LEFT JOIN 
						(SELECT 
							s.articulo,
							SUM(s.saldo) AS servicio 
						FROM
							servicios_detallejf s 
						WHERE s.cerrar = 0 
							AND s.saldo > 0 
						GROUP BY s.articulo) s 
						ON a.articulo = s.articulo 
						LEFT JOIN 
						(SELECT 
							c.articulo,
							SUM(c.cantidad) AS cierre 
						FROM
							cierres_detallejf c 
						WHERE c.cantidad > 0 
						GROUP BY c.articulo) c 
						ON a.articulo = c.articulo 
					HAVING servicio_total <> servicio_real) s 
					ON a.articulo = s.articulo SET a.servicio = IFNULL(s.servicio_real, 0) 
				WHERE a.servicio <> s.servicio_real";

		$stmt = Conexion::conectar()->prepare($sql);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	// Método para guardar las ventas
	static public function mdlGuardarDetallesCierres($tabla, $datos)
	{

		$sql = "INSERT INTO $tabla(codigo,articulo,cantidad,inicio,cod_servicio) VALUES (:codigo,:articulo,:cantidad,:inicio,:cod_servicio)";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
		$stmt->bindParam(":inicio", $datos["inicio"], PDO::PARAM_INT);
		$stmt->bindParam(":cod_servicio", $datos["cod_servicio"], PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	// Método para editar las ventas
	static public function mdlEditarCierres($tabla, $datos)
	{

		$sql = "UPDATE $tabla SET guia=:guia, usuario=:usuario, taller=:taller, total=:total, fecha=:fecha WHERE codigo=:codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":guia", $datos["guia"], PDO::PARAM_STR);
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
	static public function mdlEditarDetallesCierres($tabla, $datos)
	{

		$sql = "UPDATE $tabla SET impuesto=:impuesto,neto=:neto,total=:total,metodo_pago=:metodo_pago,cod_servicio=:cod_servicio WHERE codigo=:codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":impuesto", $datos["impuesto"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_servicio", $datos["cod_servicio"], PDO::PARAM_STR);


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

	static public function mdlEliminarCierre($tabla, $datos)
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
	// MODIFICADO: Si es servicios_detallejf y actualiza saldo, también descuenta de entaller_cabjf
	static public function mdlActualizarUnDato($tabla, $item1, $valor1, $valor2)
	{
		try {
			$pdo = Conexion::conectar();
			$pdo->beginTransaction();

			// Si es servicios_detallejf y se actualiza saldo, también actualizar entaller_cabjf
			if ($tabla == "servicios_detallejf" && $item1 == "saldo") {
				// Obtener información del servicio detalle antes de actualizar
				$stmt = $pdo->prepare("SELECT saldo, cabecera_taller, cantidad FROM servicios_detallejf WHERE id = :id");
				$stmt->bindParam(":id", $valor2, PDO::PARAM_INT);
				$stmt->execute();
				$servicioDetalle = $stmt->fetch(PDO::FETCH_ASSOC);
				$stmt->closeCursor();

				if ($servicioDetalle && $servicioDetalle['cabecera_taller']) {
					$saldoAnterior = $servicioDetalle['saldo'];
					$saldoNuevo = $valor1;
					$cabeceraTaller = $servicioDetalle['cabecera_taller'];

					// Calcular la diferencia (cuánto se descontó)
					$cantidadDescontada = $saldoAnterior - $saldoNuevo;

					if ($cantidadDescontada > 0) {
						// Descontar de entaller_cabjf
						$stmt = $pdo->prepare("UPDATE entaller_cabjf 
							SET saldo = GREATEST(saldo - :cantidad, 0)
							WHERE id = :id_cabecera AND estado = '0'");
						$stmt->bindParam(":cantidad", $cantidadDescontada, PDO::PARAM_INT);
						$stmt->bindParam(":id_cabecera", $cabeceraTaller, PDO::PARAM_INT);
						$stmt->execute();
						$stmt->closeCursor();

						// Verificar si el saldo llegó a cero para cerrar el registro
						$stmt = $pdo->prepare("SELECT saldo FROM entaller_cabjf WHERE id = :id_cabecera");
						$stmt->bindParam(":id_cabecera", $cabeceraTaller, PDO::PARAM_INT);
						$stmt->execute();
						$cabecera = $stmt->fetch(PDO::FETCH_ASSOC);
						$stmt->closeCursor();

						// Si el saldo es 0 o menor, cerrar el registro (estado = 1)
						if ($cabecera && $cabecera['saldo'] <= 0) {
							$stmt = $pdo->prepare("UPDATE entaller_cabjf 
								SET estado = 1 
								WHERE id = :id_cabecera");
							$stmt->bindParam(":id_cabecera", $cabeceraTaller, PDO::PARAM_INT);
							$stmt->execute();
							$stmt->closeCursor();
						}
					}
				}
			}

			// Actualizar el registro original
			$sql = "UPDATE $tabla SET $item1=:$item1 WHERE id=:id";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(":" . $item1, $valor1, PDO::PARAM_STR);
			$stmt->bindParam(":id", $valor2, PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();

			$pdo->commit();
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			throw $e;
		}

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

	static public function mdlSumaTotalCierres($tabla)
	{

		$stmt = Conexion::conectar()->prepare("SELECT SUM(neto) as total FROM $tabla");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlUltimoCierre($tabla)
	{

		$sql = "SELECT COUNT(codigo) + 1 AS ultimo_codigo FROM $tabla";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/* 
	* MOSTRAR ARTICULOS PARA LA TABLA DE SERVICIOS O VENTAS
	*/
	static public function mdlMostrarArticulosCiere($sectorCierre)
	{
		if ($sectorCierre != "null") {

			$stmt = Conexion::conectar()->prepare("CALL sp_1072_consulta_cierre_articulos_sector(:valor)");
			$stmt->bindParam(":valor", $sectorCierre, PDO::PARAM_STR);
			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("CALL sp_1070_consulta_cierre_articulos()");

			$stmt->execute();

			return $stmt->fetchAll();
		}


		$stmt->close();
		$stmt = null;
	}

	//VISUALIZAR DETALLE CIERRE
	static public function mdlVisualizarCierreDetalle($valor)
	{

		if ($valor != null) {
			$stmt = Conexion::conectar()->prepare("SELECT 
		sd.codigo,
		s.guia,
		DATE(s.fecha) AS fechas,
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
		cierres_detallejf sd 
		LEFT JOIN articulojf a 
		  ON sd.articulo = a.articulo 
		LEFT JOIN cierresjf s 
		  ON sd.codigo = s.codigo 
		LEFT JOIN sectorjf se 
		  ON s.taller = se.cod_sector 
	  WHERE sd.codigo = :valor 
	  GROUP BY sd.codigo,
		a.modelo,
		a.nombre,
		a.cod_color,
		a.color,
		a.estado");

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
		cierres_detallejf sd 
		LEFT JOIN articulojf a 
		  ON sd.articulo = a.articulo 
		LEFT JOIN cierresjf s 
		  ON sd.codigo = s.codigo 
		LEFT JOIN sectorjf se 
		  ON s.taller = se.cod_sector 
	  GROUP BY sd.codigo,
		a.modelo,
		a.nombre,
		a.cod_color,
		a.color,
		a.estado
		HAVING SUM(sd.cantidad)>0");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}


		$stmt = null;
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasCierres($tabla, $fechaInicial, $fechaFinal)
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

	static public function mdlRangoFechasVerCierres($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			sd.codigo,
			s.guia,
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
			SUM(sd.inicio) AS total 
		  FROM
			cierres_detallejf sd 
			LEFT JOIN articulojf a 
			  ON sd.articulo = a.articulo 
			LEFT JOIN cierresjf s 
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
			s.guia,
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
			SUM(sd.inicio) AS total 
		  FROM
			cierres_detallejf sd 
			LEFT JOIN articulojf a 
			  ON sd.articulo = a.articulo 
			LEFT JOIN cierresjf s 
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
				s.guia,
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
				SUM(sd.inicio) AS total 
			  FROM
				cierres_detallejf sd 
				LEFT JOIN articulojf a 
				  ON sd.articulo = a.articulo 
				LEFT JOIN cierresjf s 
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
				s.guia,
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
				SUM(sd.inicio) AS total 
			  FROM
				cierres_detallejf sd 
				LEFT JOIN articulojf a 
				  ON sd.articulo = a.articulo 
				LEFT JOIN cierresjf s 
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

	/* 
	* Método para activar y desactivar un usuario
	*/
	static public function mdlPagarCierre($valor1, $valor2)
	{

		$sql = "UPDATE cierresjf SET estado_pago = :estado_pago WHERE guia = :guia ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":estado_pago", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":guia", $valor2, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* Método para activar y desactivar un usuario
	*/
	static public function mdlPagarCierreServicio($estado, $inicio, $fin)
	{

		$sql = "UPDATE 
		cierresjf 
	  SET
		estado_pago = :estado_pago 
	  WHERE (DATE(fecha) BETWEEN :inicio 
		  AND :fin) ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":estado_pago", $estado, PDO::PARAM_STR);
		$stmt->bindParam(":inicio", $inicio, PDO::PARAM_STR);
		$stmt->bindParam(":fin", $fin, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	//* CERRAR SERVICIO
	static public function mdlCerrarServicio($id)
	{
		$pdo = Conexion::conectar();
		$pdo->beginTransaction();

		try {
			// Primero obtener información del servicio antes de cerrarlo
			$stmt = $pdo->prepare("SELECT saldo, cabecera_taller FROM servicios_detallejf WHERE id = :id");
			$stmt->bindParam(":id", $id, PDO::PARAM_INT);
			$stmt->execute();
			$servicio = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt->closeCursor();

			if (!$servicio) {
				throw new Exception("No se encontró el servicio con ID: $id");
			}

			// Cerrar el servicio
			$sql = "UPDATE 
						servicios_detallejf 
					SET
						cerrar = '1' 
					WHERE id = :id";

			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(":id", $id, PDO::PARAM_INT);

			if (!$stmt->execute()) {
				throw new Exception("Error al cerrar el servicio");
			}
			$stmt->closeCursor();

			// Si el servicio está vinculado a entaller_cabjf, actualizar el saldo
			if ($servicio['cabecera_taller'] && $servicio['saldo'] > 0) {
				$cabeceraTaller = $servicio['cabecera_taller'];
				$saldoServicio = $servicio['saldo'];

				// Reducir el saldo en entaller_cabjf
				$stmt = $pdo->prepare("UPDATE entaller_cabjf 
					SET saldo = GREATEST(saldo - :saldo, 0)
					WHERE id = :id_cabecera AND estado = '0'");
				$stmt->bindParam(":saldo", $saldoServicio, PDO::PARAM_INT);
				$stmt->bindParam(":id_cabecera", $cabeceraTaller, PDO::PARAM_INT);
				$stmt->execute();
				$stmt->closeCursor();

				// Verificar si el saldo llegó a cero para cerrar el registro
				$stmt = $pdo->prepare("SELECT saldo FROM entaller_cabjf WHERE id = :id_cabecera");
				$stmt->bindParam(":id_cabecera", $cabeceraTaller, PDO::PARAM_INT);
				$stmt->execute();
				$cabecera = $stmt->fetch(PDO::FETCH_ASSOC);
				$stmt->closeCursor();

				if ($cabecera && $cabecera['saldo'] <= 0) {
					// Cerrar el registro en entaller_cabjf
					$stmt = $pdo->prepare("UPDATE entaller_cabjf 
						SET estado = 1 
						WHERE id = :id_cabecera");
					$stmt->bindParam(":id_cabecera", $cabeceraTaller, PDO::PARAM_INT);
					$stmt->execute();
					$stmt->closeCursor();
				}
			}

			$pdo->commit();
			return "ok";
		} catch (Exception $e) {
			$pdo->rollBack();
			return ["error" => $e->getMessage()];
		}

		$stmt = null;
	}

	static public function mdlVerDetalleCierres($valor)
	{
		$stmt = Conexion::conectar()->prepare("SELECT 
							* 
						FROM
							cierres_detallejf 
						WHERE id = '$valor'");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}
}
