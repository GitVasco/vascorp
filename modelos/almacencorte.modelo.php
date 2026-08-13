<?php
require_once "conexion.php";

class ModeloAlmacenCorte
{

	/*
	* Método para sacar el ultimo codigo del almacen de corte
	*/
	static public function mdlUltimoCodigoAC()
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1054_almcorte_ultcod()");

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	static public function mdlSiguienteCodigoAC()
	{

		$stmt = Conexion::conectar()->prepare("SELECT IFNULL(MAX(codigo), 1000) + 1 AS siguiente FROM almacencortejf");

		$stmt->execute();

		$fila = $stmt->fetch();

		$stmt = null;

		return $fila ? (int) $fila["siguiente"] : 1001;
	}

	static public function mdlExisteGuiaAC($guia)
	{

		$stmt = Conexion::conectar()->prepare("SELECT codigo FROM almacencortejf WHERE guia = :guia LIMIT 1");

		$stmt->bindParam(":guia", $guia, PDO::PARAM_STR);

		$stmt->execute();

		$fila = $stmt->fetch();

		$stmt = null;

		return !empty($fila);
	}

	static public function mdlExisteCodigoAC($codigo)
	{

		$stmt = Conexion::conectar()->prepare("SELECT codigo FROM almacencortejf WHERE codigo = :codigo LIMIT 1");

		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_INT);

		$stmt->execute();

		$fila = $stmt->fetch();

		$stmt = null;

		return !empty($fila);
	}

	static public function mdlMostarArticulosOrdCorte()
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1055_articulos_ordcorte()");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();
		$stmt = null;
	}

	/*
	* Método para actualizar el total del corte por articulo
	*/
	static public function mdlActualizarAlmCorte($valor, $valor1)
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1056_update_articulos_almcorte_p(:valor, :valor1)");

		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":valor1", $valor1, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para recuperar el total del corte por articulo
	*/
	static public function mdlRecuperarAlmCorte($valor, $valor1)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
			articulojf 
		SET
			alm_corte = alm_corte - :cantidad 
		WHERE articulo = :articulo ");

		$stmt->bindParam(":articulo", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $valor1, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para recuperar el total del corte por articulo -ORD CORTE
	*/
	static public function mdlRecuperarOrdCorte($valor, $valor1)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
		articulojf 
	  SET
		ord_corte = ord_corte + :cantidad 
		
	  WHERE articulo = :articulo");

		$stmt->bindParam(":articulo", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $valor1, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para recuperar los saldos de detalle de ordenes de corte
	*/
	static public function mdlRecuperarSaldoOrdCorte($valor, $valor1, $valor2)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
		detalles_ordencortejf 
	  SET
		saldo = saldo + :cantidad,
		estado = 0 
	  WHERE ordencorte = :ordcorte
		AND articulo = :articulo");

		$stmt->bindParam(":articulo", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":ordcorte", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para actualizar los saldos de detalle de ordenes de corte
	*/
	static public function mdlActualizarSaldoOrdCorte($valor, $valor1, $valor2)
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1057_update_ordencorte_saldo_p(:valor, :valor1, :valor2)");

		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":valor1", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":valor2", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para actualizar los saldos de detalle de ordenes de corte
	*/
	static public function mdlActualizarSaldoOrdCorteB($valor, $valor1, $valor2)
	{

		$stmt = Conexion::conectar()->prepare("  UPDATE 
		detalles_ordencortejf 
	  SET
		saldo = saldo - :valor2,
		estado = 0 
	  WHERE ordencorte = :valor1
		AND articulo = :valor");

		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":valor1", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":valor2", $valor2, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para actualizar los saldos de ordenes de corte
	*/
	static public function mdlActualizarSaldoOrdCorteGral()
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1058_update_ordencorte_saldo()");

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Guardar cabecera de Almacen DE CORTE
	*/
	static public function mdlGuardarAlmacenCorte($datos)
	{

		$sql = "CALL sp_1059_insert_almcorte_p(:codigo, :guia, :usuario, :total, :estado)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":guia", $datos["guia"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_INT);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_INT);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/* 
	* Método para editar el almacen corte
	*/
	static public function mdlEditarAlmacenCorte($datos)
	{

		$sql = "UPDATE almacencortejf SET  guia = :guia, usuario=:usuario, total=:total, estado=:estado WHERE codigo=:codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":guia", $datos["guia"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_INT);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_INT);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/*
	* Guardar detalle de almacen de corte
	*/
	static public function mdlGuardarDetallesAlmacenCorte($datos)
	{

		$sql = "CALL sp_1060_insert_almcorte_detalle_p(:almcorte, :ordcorte, :detordcorte, :art, :cant)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":almcorte", $datos["almacencorte"], PDO::PARAM_INT);
		$stmt->bindParam(":ordcorte", $datos["ordcorte"], PDO::PARAM_INT);
		$stmt->bindParam(":detordcorte", $datos["idocd"], PDO::PARAM_INT);
		$stmt->bindParam(":art", $datos["articulo"], PDO::PARAM_INT);
		$stmt->bindParam(":cant", $datos["cantidad"], PDO::PARAM_INT);


		if ($stmt->execute()) {

			$codigoAC = $datos["almacencorte"];

			$stmt2 = Conexion::conectar()->prepare("SELECT id FROM almacencorte_detallejf 
				WHERE almacencorte = :almacencorte 
				AND articulo = :articulo 
				AND ordencorte = :ordencorte
				ORDER BY id DESC LIMIT 1");

			$stmt2->bindParam(":almacencorte", $codigoAC, PDO::PARAM_INT);
			$stmt2->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
			$stmt2->bindParam(":ordencorte", $datos["ordcorte"], PDO::PARAM_INT);
			$stmt2->execute();
			$detalleCreado = $stmt2->fetch(PDO::FETCH_ASSOC);
			$stmt2->closeCursor();

			if ($detalleCreado) {
				// Inicializar saldo_taller con la cantidad completa (todo disponible para taller/servicios)
				$stmt4 = Conexion::conectar()->prepare("UPDATE almacencorte_detallejf 
					SET saldo_taller = :cantidad 
					WHERE id = :id");
				$stmt4->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
				$stmt4->bindParam(":id", $detalleCreado['id'], PDO::PARAM_INT);
				$stmt4->execute();
				$stmt4->closeCursor();
			}

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/*
	* Guardar detalle de almacen de corte
	*/
	static public function mdlGuardarDetallesAlmacenCorteMP($id)
	{

		$sql = "INSERT INTO almacencorte_detalle_mpjf (almacencorte, mat_pri, cons_total) 
		(SELECT 
		  ac.almacencorte,
		  dt.mat_pri,
		  SUM(ac.cantidad * dt.consumo) 
		FROM
		  almacencorte_detallejf ac 
		  LEFT JOIN detalles_tarjetajf dt 
			ON ac.articulo = dt.articulo 
		WHERE ac.almacencorte = :id 
		  -- AND dt.tej_princ = 'si' 
		GROUP BY dt.mat_pri)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":id", $id, PDO::PARAM_INT);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para DESCONTAR el total del corte por articulo -ORD CORTE
	*/
	static public function mdlActualizarOrdCorte($valor, $valor1)
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1061_update_articulos_ordcorte_p(:valor, :valor1)");

		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":valor1", $valor1, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para mostrar los datos de almacen de corte
	*/
	static public function mdlMostrarAlmacenCorte($valor)
	{

		if ($valor != null) {

			$stmt = Conexion::conectar()->prepare("CALL sp_1066_consulta_almacencorte_p(:valor)");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("CALL sp_1062_consulta_almacencorte()");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*
	* Método para actualizar lel estado de ordenes de corte a parcial
	*/
	static public function mdlActualizarOrdCorteEstadoParcial()
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1063_update_ordencorte_parcial()");

		$stmt->execute();

		$stmt = null;
	}

	/*
	* Método para actualizar lel estado de ordenes de corte a cerrado
	*/
	static public function mdlActualizarOrdCorteEstadoCerrado()
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1064_update_ordencorte_cerrado()");

		$stmt->execute();

		$stmt = null;
	}

	/* 
	* Método para vizualizar detalle de la orden de corte
	*/
	static public function mdlVisualizarAlmacenCorteDetalle($valor)
	{

		if ($valor != null) {
			$stmt = Conexion::conectar()->prepare("CALL sp_1067_consulta_almacencorte_detalle_p(:valor)");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {
			$stmt = Conexion::conectar()->prepare("CALL sp_1071_consulta_almacencorte_detalle()");

			$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}



		$stmt = null;
	}

	/* 
	* Método para activar y desactivar un usuario
	*/
	static public function mdlEstadoCorte($valor, $valor1)
	{

		$stmt = Conexion::conectar()->prepare("CALL sp_1068_update_alm_estado_p(:valor, :estado)");

		$stmt->bindParam(":estado", $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}


	/*
	* Método para ingresar la cantidad de cortes por operacion
	*/
	static public function mdlIngresarCantCorte($valor, $valor1)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE encortejf
													SET
														cantidad = cantidad + :valor1,
														total_precio = (precio_doc / 12) * cantidad,
														total_tiempo = (tiempo_stand / 60) * cantidad
													WHERE
														articulo = :valor");

		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
		$stmt->bindParam(":valor1", $valor1, PDO::PARAM_STR);

		$stmt->execute();

		$stmt = null;
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasAlmacenCortes($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT  
			ac.id,
			ac.codigo,
			ac.guia,
			ac.usuario,
			u.nombre,
			ac.total,
			DATE(ac.fecha) AS fecha,
			CASE
			  WHEN ac.estado = 1 
			  THEN 'Procesado' 
			  ELSE 'Sistemas' 
			END AS estado 
		  FROM
			almacencortejf ac 
			LEFT JOIN usuariosjf u 
			  ON ac.usuario = u.id 
			  WHERE YEAR(ac.fecha) = YEAR(NOW())
			   ORDER BY ac.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			ac.id,
			ac.codigo,
			ac.guia,
			ac.usuario,
			u.nombre,
			ac.total,
			DATE(ac.fecha) AS fecha,
			CASE
			  WHEN ac.estado = 1 
			  THEN 'Procesado' 
			  ELSE 'Sistemas' 
			END AS estado 
		  FROM
			almacencortejf ac 
			LEFT JOIN usuariosjf u 
			  ON ac.usuario = u.id   WHERE ac.fecha like '%$fechaFinal%'");

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
				ac.id,
				ac.codigo,
				ac.guia,
				ac.usuario,
				u.nombre,
				ac.total,
				DATE(ac.fecha) AS fecha,
				CASE
				  WHEN ac.estado = 1 
				  THEN 'Procesado' 
				  ELSE 'Sistemas' 
				END AS estado 
			  FROM
				almacencortejf ac 
				LEFT JOIN usuariosjf u 
				  ON ac.usuario = u.id  WHERE ac.fecha BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT 
				ac.id,
				ac.codigo,
				ac.guia,
				ac.usuario,
				u.nombre,
				ac.total,
				DATE(ac.fecha) AS fecha,
				CASE
				  WHEN ac.estado = 1 
				  THEN 'Procesado' 
				  ELSE 'Sistemas' 
				END AS estado 
			  FROM
				almacencortejf ac 
				LEFT JOIN usuariosjf u 
				  ON ac.usuario = u.id  WHERE ac.fecha BETWEEN '$fechaInicial' AND '$fechaFinal'");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/*
	* Método para mostrar las telas de almacen de corte
	*/
	static public function mdlMostrarTelasAlmacenCorte($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		adm.id,
		adm.almacencorte,
		adm.mat_pri,
		adm.nota_salida,
		mp.descripcion,
		adm.cons_total,
		adm.diferencia,
  		adm.cons_real,
		adm.can_entregada,
		adm.merma,
		adm.mp_sinuso,
		adm.notificacion,
		det.union_ns  
	  FROM
		almacencorte_detalle_mpjf adm
		LEFT JOIN venta_det det
		ON det.Nro = adm.nota_salida
  		AND det.CodPro = adm.mat_pri
		LEFT JOIN 
		  (SELECT DISTINCT 
			p.codpro,
			CONCAT(p.DesPro, ' - ', tb.Des_Larga) AS descripcion 
		  FROM
			producto AS p,
			Tabla_M_Detalle AS tb 
		  WHERE tb.Cod_Tabla IN ('TCOL') 
			AND tb.Cod_Argumento = p.ColPro 
			AND p.estpro = '1' 
		  ORDER BY SUBSTRING(CodFab, 1, 6) ASC) AS mp 
		  ON adm.mat_pri = mp.codpro 
	  WHERE adm.almacencorte = :codigo 
	  ORDER BY mp.descripcion");

		$stmt->bindParam(":codigo", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	// Método para ingresar la telas de corte

	static public function mdlIngresarTelaCorte($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE almacencorte_detalle_mpjf
													SET
														cons_real= :cantidad,
														diferencia= :diferencia,
														can_entregada = :entrega,
														merma = :merma,
														mp_sinuso = :mp_sinuso,
														nota_salida = :nota_salida
													WHERE
														almacencorte = :codigo AND mat_pri= :materia");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
		$stmt->bindParam(":diferencia", $datos["diferencia"], PDO::PARAM_INT);
		$stmt->bindParam(":entrega", $datos["entrega"], PDO::PARAM_INT);
		$stmt->bindParam(":merma", $datos["merma"], PDO::PARAM_INT);
		$stmt->bindParam(":mp_sinuso", $datos["mp_sinuso"], PDO::PARAM_INT);
		$stmt->bindParam(":materia", $datos["materia"], PDO::PARAM_STR);
		$stmt->bindParam(":nota_salida", $datos["nota_salida"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	// Método para ingresar la notificaciones de telas

	static public function mdlIngresarNotificacionCorte($datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE almacencorte_detalle_mpjf
													SET
														notificacion= :notificacion
													WHERE
														almacencorte = :codigo AND mat_pri= :materia");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":notificacion", $datos["notificacion"], PDO::PARAM_STR);
		$stmt->bindParam(":materia", $datos["materia"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	static public function mdlRangoFechasVerCortes($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			dac.almacencorte,
			DATE(da.fecha) as fechas,
			da.guia,
			a.modelo,
			a.nombre,
			a.color,
			SUM(
			  CASE
				WHEN a.cod_talla = '1' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t1,
			SUM(
			  CASE
				WHEN a.cod_talla = '2' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t2,
			SUM(
			  CASE
				WHEN a.cod_talla = '3' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t3,
			SUM(
			  CASE
				WHEN a.cod_talla = '4' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t4,
			SUM(
			  CASE
				WHEN a.cod_talla = '5' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t5,
			SUM(
			  CASE
				WHEN a.cod_talla = '6' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t6,
			SUM(
			  CASE
				WHEN a.cod_talla = '7' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t7,
			SUM(
			  CASE
				WHEN a.cod_talla = '8' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t8,
			SUM(dac.cantidad) AS subtotal 
		  FROM
			almacencorte_detallejf dac 
			LEFT JOIN articulojf a 
			  ON dac.articulo = a.articulo
			LEFT JOIN almacencortejf da
 			  ON (da.codigo = dac.almacencorte OR (da.guia = dac.almacencorte AND NOT EXISTS (SELECT 1 FROM almacencortejf x WHERE x.codigo = dac.almacencorte)))
			  where year(da.fecha)=YEAR(NOW())
		  GROUP BY dac.almacencorte,
			a.modelo,
			a.nombre,
			a.color  ORDER BY dac.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			dac.almacencorte,
			DATE(da.fecha) as fechas,
			da.guia,
			a.modelo,
			a.nombre,
			a.color,
			SUM(
			  CASE
				WHEN a.cod_talla = '1' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t1,
			SUM(
			  CASE
				WHEN a.cod_talla = '2' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t2,
			SUM(
			  CASE
				WHEN a.cod_talla = '3' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t3,
			SUM(
			  CASE
				WHEN a.cod_talla = '4' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t4,
			SUM(
			  CASE
				WHEN a.cod_talla = '5' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t5,
			SUM(
			  CASE
				WHEN a.cod_talla = '6' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t6,
			SUM(
			  CASE
				WHEN a.cod_talla = '7' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t7,
			SUM(
			  CASE
				WHEN a.cod_talla = '8' 
				THEN dac.cantidad 
				ELSE 0 
			  END
			) AS t8,
			SUM(dac.cantidad) AS subtotal 
		  FROM
			almacencorte_detallejf dac 
			LEFT JOIN articulojf a 
			  ON dac.articulo = a.articulo 
			LEFT JOIN almacencortejf da
 			  ON (da.codigo = dac.almacencorte OR (da.guia = dac.almacencorte AND NOT EXISTS (SELECT 1 FROM almacencortejf x WHERE x.codigo = dac.almacencorte)))
			WHERE DATE(da.fecha) like '%$fechaFinal%'
		  GROUP BY dac.almacencorte,
			a.modelo,
			a.nombre,
			a.color  ");

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
				dac.almacencorte,
				DATE(da.fecha) as fechas,
				da.guia,
				a.modelo,
				a.nombre,
				a.color,
				SUM(
				  CASE
					WHEN a.cod_talla = '1' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t1,
				SUM(
				  CASE
					WHEN a.cod_talla = '2' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t2,
				SUM(
				  CASE
					WHEN a.cod_talla = '3' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t3,
				SUM(
				  CASE
					WHEN a.cod_talla = '4' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t4,
				SUM(
				  CASE
					WHEN a.cod_talla = '5' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t5,
				SUM(
				  CASE
					WHEN a.cod_talla = '6' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t6,
				SUM(
				  CASE
					WHEN a.cod_talla = '7' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t7,
				SUM(
				  CASE
					WHEN a.cod_talla = '8' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t8,
				SUM(dac.cantidad) AS subtotal 
			  FROM
				almacencorte_detallejf dac 
				LEFT JOIN articulojf a 
				  ON dac.articulo = a.articulo
				LEFT JOIN almacencortejf da
 			  	  ON (da.codigo = dac.almacencorte OR (da.guia = dac.almacencorte AND NOT EXISTS (SELECT 1 FROM almacencortejf x WHERE x.codigo = dac.almacencorte)))
				WHERE DATE(da.fecha) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'
			  GROUP BY dac.almacencorte,
				a.modelo,
				a.nombre,
				a.color ");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT 
				dac.almacencorte,
				DATE(da.fecha) as fechas,
				da.guia,
				a.modelo,
				a.nombre,
				a.color,
				SUM(
				  CASE
					WHEN a.cod_talla = '1' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t1,
				SUM(
				  CASE
					WHEN a.cod_talla = '2' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t2,
				SUM(
				  CASE
					WHEN a.cod_talla = '3' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t3,
				SUM(
				  CASE
					WHEN a.cod_talla = '4' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t4,
				SUM(
				  CASE
					WHEN a.cod_talla = '5' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t5,
				SUM(
				  CASE
					WHEN a.cod_talla = '6' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t6,
				SUM(
				  CASE
					WHEN a.cod_talla = '7' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t7,
				SUM(
				  CASE
					WHEN a.cod_talla = '8' 
					THEN dac.cantidad 
					ELSE 0 
				  END
				) AS t8,
				SUM(dac.cantidad) AS subtotal 
			  FROM
				almacencorte_detallejf dac 
				LEFT JOIN articulojf a 
				  ON dac.articulo = a.articulo 
				LEFT JOIN almacencortejf da
 				  ON (da.codigo = dac.almacencorte OR (da.guia = dac.almacencorte AND NOT EXISTS (SELECT 1 FROM almacencortejf x WHERE x.codigo = dac.almacencorte)))
				WHERE DATE(da.fecha) BETWEEN '$fechaInicial' AND '$fechaFinal'
			  GROUP BY dac.almacencorte,
				a.modelo,
				a.nombre,
				a.color  ");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}


	static public function mdlRangoFechasConsumoTelas($fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			adm.id,
			adm.almacencorte,
			adm.nota_salida,
			a.guia,
			DATE(a.fecha) AS fechas,
			Stk_Act,
			adm.mat_pri,
			mp.despro,
			mp.color,
			mp.unidad,
			adm.cons_total,
			adm.diferencia,
			adm.cons_real,
			adm.can_entregada,
			adm.merma,
			adm.mp_sinuso,
			adm.notificacion 
		  FROM
			almacencorte_detalle_mpjf adm 
			LEFT JOIN 
			  (SELECT 
				pro.CodPro,
				pro.CodFab,
				pro.DesPro,
				pro.codalm01 AS Stk_Act,
				TbUnd.Des_Corta AS Unidad,
				TbCol.Des_Larga AS Color,
				pro.ColPro 
			  FROM
				producto pro 
				INNER JOIN Tabla_M_Detalle AS TbUnd 
				  ON pro.UndPro = TbUnd.Cod_Argumento 
				  AND (TbUnd.Cod_Tabla = 'TUND') 
				INNER JOIN Tabla_M_Detalle AS TbCol 
				  ON pro.ColPro = TbCol.Cod_Argumento 
				  AND (TbCol.Cod_Tabla = 'TCOL') 
			  WHERE pro.EstPro = '1') AS mp 
			  ON adm.mat_pri = mp.codpro 
			  LEFT JOIN almacencortejf a
			  ON adm.almacencorte=a.codigo");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			adm.id,
			adm.almacencorte,
			adm.nota_salida,
			a.guia,
			Stk_Act,
			DATE(a.fecha) AS fechas,
			adm.mat_pri,
			mp.despro,
			mp.color,
			mp.unidad,
			adm.cons_total,
			adm.diferencia,
			adm.cons_real,
			adm.can_entregada,
			adm.merma,
			adm.mp_sinuso,
			adm.notificacion 
		  FROM
			almacencorte_detalle_mpjf adm 
			LEFT JOIN 
			  (SELECT 
				pro.CodPro,
				pro.CodFab,
				pro.DesPro,
				pro.codalm01 AS Stk_Act,
				TbUnd.Des_Corta AS Unidad,
				TbCol.Des_Larga AS Color,
				pro.ColPro 
			  FROM
				producto pro 
				INNER JOIN Tabla_M_Detalle AS TbUnd 
				  ON pro.UndPro = TbUnd.Cod_Argumento 
				  AND (TbUnd.Cod_Tabla = 'TUND') 
				INNER JOIN Tabla_M_Detalle AS TbCol 
				  ON pro.ColPro = TbCol.Cod_Argumento 
				  AND (TbCol.Cod_Tabla = 'TCOL') 
			  WHERE pro.EstPro = '1') AS mp 
			  ON adm.mat_pri = mp.codpro 
			  LEFT JOIN almacencortejf a
			  ON adm.almacencorte=a.codigo
			WHERE DATE(fechas) like '%$fechaFinal%' ");

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
				adm.id,
				adm.almacencorte,
				adm.nota_salida,
				a.guia,
				Stk_Act,
				DATE(a.fecha) AS fechas,
				adm.mat_pri,
				mp.despro,
				mp.color,
				mp.unidad,
				adm.cons_total,
				adm.diferencia,
				adm.cons_real,
				adm.can_entregada,
				adm.merma,
				adm.mp_sinuso,
				adm.notificacion 
			  FROM
				almacencorte_detalle_mpjf adm 
				LEFT JOIN 
				  (SELECT 
					pro.CodPro,
					pro.CodFab,
					pro.DesPro,
					pro.codalm01 AS Stk_Act,
					TbUnd.Des_Corta AS Unidad,
					TbCol.Des_Larga AS Color,
					pro.ColPro 
				  FROM
					producto pro 
					INNER JOIN Tabla_M_Detalle AS TbUnd 
					  ON pro.UndPro = TbUnd.Cod_Argumento 
					  AND (TbUnd.Cod_Tabla = 'TUND') 
					INNER JOIN Tabla_M_Detalle AS TbCol 
					  ON pro.ColPro = TbCol.Cod_Argumento 
					  AND (TbCol.Cod_Tabla = 'TCOL') 
				  WHERE pro.EstPro = '1') AS mp 
				  ON adm.mat_pri = mp.codpro 
				  LEFT JOIN almacencortejf a
				  ON adm.almacencorte=a.codigo
				WHERE DATE(fechas) BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT 
				adm.id,
				adm.almacencorte,
				adm.nota_salida,
				a.guia,
				Stk_Act,
				DATE(a.fecha) AS fechas,
				adm.mat_pri,
				mp.despro,
				mp.color,
				mp.unidad,
				adm.cons_total,
				adm.diferencia,
				adm.cons_real,
				adm.can_entregada,
				adm.merma,
				adm.mp_sinuso,
				adm.notificacion 
			  FROM
				almacencorte_detalle_mpjf adm 
				LEFT JOIN 
				  (SELECT 
					pro.CodPro,
					pro.CodFab,
					pro.DesPro,
					pro.codalm01 AS Stk_Act,
					TbUnd.Des_Corta AS Unidad,
					TbCol.Des_Larga AS Color,
					pro.ColPro 
				  FROM
					producto pro 
					INNER JOIN Tabla_M_Detalle AS TbUnd 
					  ON pro.UndPro = TbUnd.Cod_Argumento 
					  AND (TbUnd.Cod_Tabla = 'TUND') 
					INNER JOIN Tabla_M_Detalle AS TbCol 
					  ON pro.ColPro = TbCol.Cod_Argumento 
					  AND (TbCol.Cod_Tabla = 'TCOL') 
				  WHERE pro.EstPro = '1') AS mp 
				  ON adm.mat_pri = mp.codpro 
				  LEFT JOIN almacencortejf a
				  ON adm.almacencorte=a.codigo
				WHERE DATE(fechas) BETWEEN '$fechaInicial' AND '$fechaFinal'");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}


	static public function mdlMostarDetallesAlmacenCorte($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT
													a.*,d.saldo 
													FROM
													almacencorte_detallejf a 
													LEFT JOIN detalles_ordencortejf AS d 
													ON d.id = a.detordencorte  
													WHERE a.$item = :$item ");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* Método para eliminar los detalles de almacencorte
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
	* Método ACTUALIZAR CANTIDAD EN ORDEN DE CORTE CON SALDO
	*/
	static public function mdlActualizarOrdCorteSaldo()
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
							articulojf a 
							LEFT JOIN 
							(SELECT 
								doc.articulo,
								SUM(doc.saldo) AS ord_corte 
							FROM
								detalles_ordencortejf doc 
							WHERE doc.estado = '0' 
								AND doc.saldo > 0 
							GROUP BY doc.articulo) AS doc 
							ON a.articulo = doc.articulo 
							SET a.ord_corte = IFNULL(doc.ord_corte, 0)");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	// ver el lote
	static public function mdlVerLotes($ac)
	{

		$stmt = Conexion::conectar()->prepare("SELECT
				ad.almacencorte ,
				date(ad.fecha) as fecha,
				concat(a.modelo, a.cod_color) as articulo,
				a.modelo ,
				a.nombre ,
				a.cod_color ,
				a.color ,
				sum(ad.cantidad) as cantidad,
				ad.lote
			from
				almacencorte_detallejf ad
			left join articulojf a 
				on
				ad.articulo = a.articulo
			where
				ad.almacencorte = $ac
			group by
				concat(a.modelo, a.cod_color)
	");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	static public function mdlVerConsumos($ac)
	{
		$familias = FAMILIA_TELA;

		$stmt = Conexion::conectar()->prepare("SELECT
				adm.id ,
				adm.almacencorte ,
				adm.mat_pri ,
				p.codfab ,
				p.despro ,
				p.colpro,
				tmd.des_larga,
				p.talpro,
				p.undpro,
				adm.cons_total ,
				adm.cons_real ,
				adm.diferencia ,
				adm.can_entregada ,
				adm.merma ,
				adm.mp_sinuso ,
				adm.notificacion ,
				adm.nota_salida
			from
				almacencorte_detalle_mpjf adm
			left join producto p 
				on
				adm.mat_pri = p.codpro
			left join tabla_m_detalle tmd 
			on
				p.colpro = tmd.cod_argumento
				and tmd.cod_tabla = 'TCOL'
			where
				adm.almacencorte = '$ac'
				and left(p.codfab,
				3) in ($familias)
			order by
				p.codfab
	");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	// actualizamos el lote
	static public function mdlActualizarLotes($articulo, $lote)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE
					almacencorte_detallejf
				set
					lote = '$lote'
				where
					SUBSTRING(articulo , 1, length(articulo) - 1) = '$articulo'");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}
}
