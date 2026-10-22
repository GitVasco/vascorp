<?php
require_once "conexion.php";

class ModeloSalidas
{

	/*
    * MOSTRAR TEMPORAL CABECERA
    */
	static public function mdlMostrarTemporal($tabla, $valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE codigo = '$valor' ORDER BY id ASC");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*
    * MOSTRAR TEMPORAL CABECERA
    */
	static public function mdlMostrarTemporalTotal($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT
												dt.codigo,
												SUM(total) AS totalArt
											FROM
												detalle_ing_sal dt
											WHERE dt.codigo = $valor");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	/*
    * MOSTRAR DETALLE DE TEMPORAL
    */
	static public function mdlMostraDetallesTemporal($tabla, $valor)
	{

		$sql = "SELECT * FROM $tabla WHERE codigo='$valor' ORDER BY id ASC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*
	* GUARDAR DETALLE DE TEMPORAL
	*/
	static public function mdlGuardarTemporal($tabla, $datos)
	{


		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla (codigo, cliente, vendedor, lista,usuario) VALUES (:codigo, :cliente, :vendedor, :lista,:usuario)");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":lista", $datos["lista"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
	* GUARDAR DETALLE DE TEMPORAL
	*/
	static public function mdlGuardarTemporalDetalle($tabla, $datos)
	{


		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla (codigo, articulo, cantidad, precio, total) VALUES (:codigo, :articulo, :cantidad, :precio, (:cantidad * :precio) )");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":articulo", $datos["articulo"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);
		$stmt->bindParam(":precio", $datos["precio"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
    * ELIMINAR ARTICULO REPETIDO
    */
	static public function mdlEliminarDetalleTemporal($tabla, $eliminar)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE codigo = :codigo AND articulo = :articulo");

		$stmt->bindParam(":codigo", $eliminar["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":articulo", $eliminar["articulo"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/*
    * ELIMINAR DETALLES DEL PEDIDO PARA PONER LOS REALES
    */
	static public function mdlEliminarDetalleTemporalTotal($datos)
	{

		$stmt = Conexion::conectar()->prepare("DELETE
												FROM
												detalle_ing_sal
												WHERE codigo = :codigo");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/*
    * ACTUALIZAR TALONARIO +1
    */
	static public function mdlActualizarTalonario()
	{

		$sql = "UPDATE talonariosjf SET pedido = pedido+1 WHERE id = 2";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		$stmt = null;
	}

	/*
    *ACTUALIZAR TOTALES DEL PEDIDO
    */
	static public function mdlActualizarTotalesPedido($datos)
	{

		$sql = "UPDATE
					ing_sal
				SET
					cliente = :cliente,
					op_gravada = :op_gravada,
					descuento_total = :descuento_total,
					sub_total = :sub_total,
					igv = :impuesto,
					total = :total,
					condicion_venta = :condicion_venta,
					agencia = :agencia,
					estado = 'GENERADO',
					usuario = :usuario
				WHERE codigo = :codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":op_gravada", $datos["op_gravada"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento_total", $datos["descuento_total"], PDO::PARAM_STR);
		$stmt->bindParam(":sub_total", $datos["sub_total"], PDO::PARAM_STR);
		$stmt->bindParam(":impuesto", $datos["impuesto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":condicion_venta", $datos["condicion_venta"], PDO::PARAM_STR);
		$stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/*
    * MOSTRAR DETALLE DE TEMPORAL
    */
	static public function mdlMostrarSalidasCabecera($valor)
	{

		if ($valor == null) {

			$sql = "SELECT
				t.id,
				t.codigo,
				c.codigo AS cod_cli,
				c.nombre,
				c.tipo_documento,
				c.documento,
				t.lista,
				t.vendedor,
				t.op_gravada,
				t.descuento_total,
				t.sub_total,
				t.igv,
				t.total,
				ROUND(
				t.descuento_total / t.op_gravada * 100,
				2
				) AS dscto,
				t.condicion_venta,
				cv.descripcion,
				t.estado,
				t.usuario,
				t.agencia,
				u.nombre AS nom_usu,
				DATE(t.fecha) AS fecha,
				cv.dias,
				DATE_ADD(DATE(t.fecha), INTERVAL cv.dias DAY) AS fecha_ven
			FROM
				ing_sal t
				LEFT JOIN clientesjf c
				ON t.cliente = c.id
				LEFT JOIN condiciones_ventajf cv
				ON t.condicion_venta = cv.id
				LEFT JOIN usuariosjf u
				ON t.usuario = u.id
			WHERE t.estado = 'generado'
			ORDER BY fecha DESC";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$sql = "SELECT
					t.id,
					t.codigo,
					c.codigo AS cod_cli,
					t.cliente AS cli_tra,
  					t.vendedor AS ven_tra,
					c.nombre,
					c.tipo_documento,
					c.documento,
					t.lista,
					t.vendedor,
					t.op_gravada,
					t.descuento_total,
					t.sub_total,
					t.igv,
					t.total,
					ROUND(
					t.descuento_total / t.op_gravada * 100,
					2
					) AS dscto,
					t.condicion_venta,
					cv.descripcion,
					t.estado,
					t.usuario,
					t.agencia,
					u.nombre AS nom_usu,
					DATE(t.fecha) AS fecha,
					cv.dias,
					DATE_ADD(DATE(t.fecha), INTERVAL cv.dias DAY) AS fecha_ven
				FROM
					ing_sal t
					LEFT JOIN clientesjf c
					ON t.cliente = c.id
					LEFT JOIN condiciones_ventajf cv
					ON t.condicion_venta = cv.id
					LEFT JOIN usuariosjf u
					ON t.usuario = u.id
				WHERE t.codigo = '$valor'";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetch();
		}

		$stmt = null;
	}
	/*
    * MOSTRAR LOS DATOS PARA LA IMPRESION
    */
	static public function mdlSalidaImpresion($codigo, $modelo)
	{

		$sql = "SELECT 
						m.id_modelo,
						m.modelo,
						a.cod_color,
						a.color,
						SUM(
						CASE
							WHEN a.cod_talla = '1'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t1,
						SUM(
						CASE
							WHEN a.cod_talla = '2'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t2,
						SUM(
						CASE
							WHEN a.cod_talla = '3'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t3,
						SUM(
						CASE
							WHEN a.cod_talla = '4'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t4,
						SUM(
						CASE
							WHEN a.cod_talla = '5'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t5,
						SUM(
						CASE
							WHEN a.cod_talla = '6'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t6,
						SUM(
						CASE
							WHEN a.cod_talla = '7'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t7,
						SUM(
						CASE
							WHEN a.cod_talla = '8'
							THEN dt.cantidad
							ELSE 0
						END
						) AS t8,
						SUM(dt.cantidad) AS total
					FROM
						detalle_ing_sal dt
						LEFT JOIN articulojf a
						ON dt.articulo = a.articulo
						LEFT JOIN modelojf m
						ON a.modelo = m.modelo
					WHERE dt.codigo = '" . $codigo . "'
						AND m.modelo = '" . $modelo . "'
					GROUP BY m.modelo,
						a.cod_color,
						a.color";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*
    * MOSTRAR LOS DATOS PARA LA IMPRESION
    */
	static public function mdlSalidaImpresionMod($valor)
	{

		$sql = "SELECT
			m.id_modelo,
			m.modelo
		FROM
			detalle_ing_sal dt
			LEFT JOIN articulojf a
			ON dt.articulo = a.articulo
			LEFT JOIN modelojf m
			ON a.modelo = m.modelo
		WHERE dt.codigo = '$valor'
		GROUP BY m.id_modelo
		ORDER BY m.modelo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*
    * MOSTRAR LOS DATOS PARA LA IMPRESION CABECERA
    */
	static public function mdlSalidaImpresionCab($valor)
	{

		$sql = "SELECT
					t.codigo AS pedido,
					DATE(t.fecha) AS fecha,
					c.codigo,
					t.cliente,
					c.nombre,
					c.direccion,
					c.ubigeo,
					u.nom_ubi,
					t.vendedor,
					td.tipo_doc,
					c.documento
				FROM
					ing_sal t
					LEFT JOIN clientesjf c
					ON t.cliente = c.codigo
					LEFT JOIN ubigeojf u
					ON c.ubigeo = u.cod_ubi
					LEFT JOIN tipo_documentojf td
    				ON td.cod_doc = c.tipo_documento
				WHERE t.codigo = '$valor'";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/*
    * MOSTRAR PEDIDO CON FORMATO DE IMRPESION - TOTALES GENERALES
    */
	static public function mdlSalidaImpresionTotales($valor)
	{

		$sql = "SELECT
					'TOTAL',
					'PEDIDO',
					SUM(
					CASE
						WHEN a.cod_talla = '1'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t1,
					SUM(
					CASE
						WHEN a.cod_talla = '2'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t2,
					SUM(
					CASE
						WHEN a.cod_talla = '3'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t3,
					SUM(
					CASE
						WHEN a.cod_talla = '4'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t4,
					SUM(
					CASE
						WHEN a.cod_talla = '5'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t5,
					SUM(
					CASE
						WHEN a.cod_talla = '6'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t6,
					SUM(
					CASE
						WHEN a.cod_talla = '7'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t7,
					SUM(
					CASE
						WHEN a.cod_talla = '8'
						THEN dt.cantidad
						ELSE 0
					END
					) AS t8,
					SUM(dt.cantidad) AS total 
				FROM
					detalle_ing_sal dt
					LEFT JOIN articulojf a
					ON dt.articulo = a.articulo
				WHERE dt.codigo = '$valor'";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}


	/*
    * MOSTRAR DETALLE DE TEMPORAL
    */
	static public function mdlMostrarSalidasGeneral($valor)
	{

		if ($valor == null) {

			$sql = "SELECT
						t.id,
						t.codigo,
						c.codigo AS cod_cli,
						t.cliente AS cli_tra,
  						t.vendedor AS ven_tra,
						c.nombre,
						c.tipo_documento,
						c.documento,
						t.lista,
						t.vendedor,
						t.op_gravada,
						t.descuento_total,
						t.sub_total,
						t.igv,
						t.total,
						ROUND(
						t.descuento_total / t.op_gravada * 100,
						2
						) AS dscto,
						t.condicion_venta,
						cv.descripcion,
						t.estado,
						t.usuario,
						t.agencia,
						u.nombre AS nom_usu,
						DATE(t.fecha) AS fecha,
						cv.dias,
						DATE_ADD(DATE(t.fecha), INTERVAL cv.dias DAY) AS fecha_ven
					FROM
						ing_sal t
						LEFT JOIN clientesjf c
						ON t.cliente = c.codigo
						LEFT JOIN condiciones_ventajf cv
						ON t.condicion_venta = cv.id
						LEFT JOIN usuariosjf u
						ON t.usuario = u.id
					WHERE YEAR(t.fecha) = YEAR(NOW())
					ORDER BY fecha DESC";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$sql = "SELECT
					t.id,
					t.codigo,
					c.codigo AS cod_cli,
					c.nombre,
					c.tipo_documento,
					c.documento,
					t.lista,
					t.vendedor,
					t.op_gravada,
					t.descuento_total,
					t.sub_total,
					t.igv,
					t.total,
					ROUND(
					t.descuento_total / t.op_gravada * 100,
					2
					) AS dscto,
					t.condicion_venta,
					cv.descripcion,
					t.estado,
					t.usuario,
					t.agencia,
					u.nombre AS nom_usu,
					DATE(t.fecha) AS fecha,
					cv.dias,
					DATE_ADD(DATE(t.fecha), INTERVAL cv.dias DAY) AS fecha_ven
				FROM
					ing_sal t
					LEFT JOIN clientesjf c
					ON t.cliente = c.codigo
					LEFT JOIN condiciones_ventajf cv
					ON t.condicion_venta = cv.id
					LEFT JOIN usuariosjf u
					ON t.usuario = u.id
				WHERE t.codigo = $valor";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetch();
		}

		$stmt = null;
	}

	/*
    * MOSTRAR DETALLE DE TEMPORAL
    */
	static public function mdlMostraPedidosTablas($valor)
	{

		if ($valor != null) {

			$sql = "SELECT
						t.id,
						t.codigo,
						c.codigo AS cod_cli,
						c.nombre,
						c.tipo_documento,
						c.documento,
						t.lista,
						t.vendedor,
						t.op_gravada,
						t.descuento_total,
						t.sub_total,
						t.igv,
						t.total,
						ROUND(
						t.descuento_total / t.op_gravada * 100,
						2
						) AS dscto,
						t.condicion_venta,
						cv.descripcion,
						t.estado,
						t.usuario,
						t.agencia,
						u.nombre AS nom_usu,
						DATE(t.fecha) AS fecha,
						cv.dias,
						DATE_ADD(DATE(t.fecha), INTERVAL cv.dias DAY) AS fecha_ven
					FROM
						ing_sal t
						LEFT JOIN clientesjf c
						ON t.cliente = c.codigo
						LEFT JOIN condiciones_ventajf cv
						ON t.condicion_venta = cv.id
						LEFT JOIN usuariosjf u
						ON t.usuario = u.id
					WHERE t.estado = '$valor'
					ORDER BY fecha DESC";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$sql = "SELECT
					t.id,
					t.codigo,
					c.codigo AS cod_cli,
					c.nombre,
					c.tipo_documento,
					c.documento,
					t.lista,
					t.vendedor,
					t.op_gravada,
					t.descuento_total,
					t.sub_total,
					t.igv,
					t.total,
					ROUND(
					t.descuento_total / t.op_gravada * 100,
					2
					) AS dscto,
					t.condicion_venta,
					cv.descripcion,
					t.estado,
					t.usuario,
					t.agencia,
					u.nombre AS nom_usu,
					DATE(t.fecha) AS fecha,
					cv.dias,
					DATE_ADD(DATE(t.fecha), INTERVAL cv.dias DAY) AS fecha_ven
				FROM
					ing_sal t
					LEFT JOIN clientesjf c
					ON t.cliente = c.codigo
					LEFT JOIN condiciones_ventajf cv
					ON t.condicion_venta = cv.id
					LEFT JOIN usuariosjf u
					ON t.usuario = u.id
				WHERE t.codigo = $valor";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetch();
		}

		$stmt = null;
	}

	/*
	* GUARDAR TEMPORAL BKP
	*/
	static public function mdlGuardarTemporalBkp($tabla, $datos)
	{


		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla (codigo, cliente, vendedor, lista,op_gravada,descuento_total,sub_total,igv,total,condicion_venta,estado,fecha,usuario,agencia,usuario_estado) VALUES (:codigo, :cliente, :vendedor, :lista,:op_gravada,:descuento_total,:sub_total,:igv,:total,:condicion_venta,:estado,:fecha,:usuario,:agencia,:usuario_estado)");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":lista", $datos["lista"], PDO::PARAM_STR);
		$stmt->bindParam(":op_gravada", $datos["op_gravada"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento_total", $datos["descuento_total"], PDO::PARAM_STR);
		$stmt->bindParam(":sub_total", $datos["sub_total"], PDO::PARAM_STR);
		$stmt->bindParam(":igv", $datos["igv"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":condicion_venta", $datos["condicion_venta"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario_estado", $datos["usuario_estado"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
    * MOSTRAR TEMPORAL CABECERA
    */
	static public function mdlMostrarArgumentoSalida($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT argumento FROM maestrajf WHERE codigo = '" . $valor . "' ");

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}


	/*
    * BLOQUEAR LA CABECERA DE LA SALIDA (SELECT ... FOR UPDATE)
    * Debe llamarse dentro de una transacción: serializa los intentos de
    * "Pasar Movimiento a:" sobre el mismo código y permite verificar el
    * estado ya confirmado por el intento que ganó.
    */
	static public function mdlBloquearSalida($codigo)
	{

		$stmt = Conexion::conectar()->prepare("SELECT codigo, estado FROM ing_sal WHERE codigo = :codigo FOR UPDATE");

		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);

		if (!$stmt->execute()) {

			return false;
		}

		return $stmt->fetch();
	}

	/*
    * BLOQUEAR EL CORRELATIVO DEL TIPO DE DOCUMENTO (SELECT ... FOR UPDATE)
    * Debe llamarse dentro de una transacción: serializa la generación de
    * documentos del mismo tipo para que no se duplique el correlativo.
    */
	static public function mdlBloquearArgumento($codigo)
	{

		$stmt = Conexion::conectar()->prepare("SELECT codigo, argumento FROM maestrajf WHERE codigo = :codigo AND tipo_dato = 'TTOP' FOR UPDATE");

		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);

		if (!$stmt->execute()) {

			return false;
		}

		return $stmt->fetch();
	}

	/*
    * ¿YA EXISTE UN DOCUMENTO GENERADO DESDE ESTA SALIDA?
    */
	static public function mdlExisteDocumentoSalidaPorOrigen($codigo)
	{

		$stmt = Conexion::conectar()->prepare("SELECT COUNT(*) FROM doc_ing_sal WHERE doc_origen = :codigo");

		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);

		if (!$stmt->execute()) {

			return false;
		}

		return (int) $stmt->fetchColumn();
	}

	/*
    * ¿YA EXISTE EL DOCUMENTO DESTINO (TIPO + NÚMERO) EN doc_ing_sal?
    */
	static public function mdlExisteDocumentoSalidaDestino($tipo, $documento)
	{

		$stmt = Conexion::conectar()->prepare("SELECT COUNT(*) FROM doc_ing_sal WHERE tipo = :tipo AND documento = :documento");

		$stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
		$stmt->bindParam(":documento", $documento, PDO::PARAM_STR);

		if (!$stmt->execute()) {

			return false;
		}

		return (int) $stmt->fetchColumn();
	}

	/*
    * ACTUALIZAR ARGUMENTO + 1 GUIA
    */
	static public function mdlActualizarArgumento($valor)
	{

		$sql = "UPDATE
                    maestrajf
                SET
                    argumento = argumento + 1
                WHERE codigo = :valor";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/*
    * ACTUALIZAR PEDIDO A FACTURADO
    */
	static public function mdlActualizarSalidaF($codigo)
	{

		$sql = "UPDATE
                    ing_sal
                SET
                    estado = 'FACTURADO'
                WHERE codigo = :codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/*
	* REGISTAR DOCUMENTO 
	*/
	static public function mdlRegistrarDocumentoSalida($datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO doc_ing_sal (
                                                        tipo,
                                                        documento,
                                                        neto,
                                                        igv,
                                                        dscto,
                                                        total,
                                                        cliente,
                                                        vendedor,
                                                        agencia,
                                                        fecha,
                                                        tipo_documento,
                                                        lista_precios,
                                                        condicion_venta,
                                                        doc_destino,
                                                        doc_origen,
                                                        usuario
                                                    )
                                                    VALUES
                                                        (
                                                        :tipo,
                                                        :documento,
                                                        :neto,
                                                        :igv,
                                                        :dscto,
                                                        :total,
                                                        :cliente,
                                                        :vendedor,
                                                        :agencia,
                                                        DATE(NOW()),
                                                        :tipo_documento,
                                                        :lista_precios,
                                                        :condicion_venta,
                                                        :doc_destino,
                                                        :doc_origen,
                                                        :usuario
                                                        )");

		$stmt->bindParam(":tipo", $datos["tipo"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":igv", $datos["igv"], PDO::PARAM_STR);
		$stmt->bindParam(":dscto", $datos["dscto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);
		$stmt->bindParam(":lista_precios", $datos["lista_precios"], PDO::PARAM_STR);
		$stmt->bindParam(":condicion_venta", $datos["condicion_venta"], PDO::PARAM_STR);
		$stmt->bindParam(":doc_destino", $datos["doc_destino"], PDO::PARAM_STR);
		$stmt->bindParam(":doc_origen", $datos["doc_origen"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo_documento", $datos["tipo_documento"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/*
    * MOSTRAR DETALLE DE TEMPORAL
    */
	static public function mdlListarDocumentos($valor)
	{

		if ($valor == null) {

			$sql = "SELECT 
			d.*,u.nombre  
		  FROM
			doc_ing_sal d
		 LEFT JOIN usuariosjf u ON u.id = d.usuario
		 where year(d.fecha) = YEAR(NOW()) ";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$sql = "SELECT 
		* 
		FROM
		doc_ing_sal  
		WHERE documento = '" . $valor . "' ";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetch();
		}

		$stmt = null;
	}
}
