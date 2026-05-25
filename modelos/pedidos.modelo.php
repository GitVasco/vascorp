<?php
require_once "conexion.php";

class ModeloPedidos
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


	// Nueva función para buscar múltiples registros en una sola consulta
	static public function mdlMostrarTemporalMultiple($tabla, $valores)
	{
		$placeholders = implode(',', array_fill(0, count($valores), '?'));
		$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE codigo IN ($placeholders) ORDER BY id ASC");

		$stmt->execute($valores);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}


	/*
    * MOSTRAR TEMPORAL CABECERA
    */
	static public function mdlMostrarTemporalFecha($fecha)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
													CONCAT(
													'C|',
													codigo,
													'|',
													cliente,
													'|',
													vendedor,
													'|',
													lista,
													'|',
													op_gravada,
													'|',
													descuento_total,
													'|',
													sub_total,
													'|',
													igv,
													'|',
													total,
													'|',
													condicion_venta,
													'|',
													estado,
													'|',
													DATE(fecha),
													'|',
													usuario,
													'|',
													agencia,
													'|',
													usuario_estado,
													'|',
													dscto
													) AS cabecera 
												FROM
													temporaljf t 
												WHERE DATE(t.fecha) = :fecha 
													AND estado = 'GENERADO' 
												UNION
												SELECT 
													CONCAT(
													'D|',
													dt.codigo,
													'|',
													articulo,
													'|',
													cantidad,
													'|',
													precio,
													'|',
													dt.total
													) AS detalle 
												FROM
													temporaljf t 
													LEFT JOIN detalle_temporal dt 
													ON t.codigo = dt.codigo 
												WHERE DATE(t.fecha) = :fecha 
													AND estado = 'GENERADO'");


		$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/*
    * MOSTRAR TEMPORAL CABECERA
    */
	static public function mdlMostrarTemporalFechaD($fecha)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
									CONCAT(
									dt.codigo,
									'|',
									articulo,
									'|',
									cantidad,
									'|',
									precio,
									'|',
									dt.total
									) AS detalle 
								FROM
									temporaljf t 
									LEFT JOIN detalle_temporal dt 
									ON t.codigo = dt.codigo
							WHERE DATE(t.fecha) = :fecha 
							AND t.estado ='GENERADO'");


		$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

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
												detalle_temporal dt
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

		$sql = "SELECT * FROM $tabla WHERE codigo=$valor ORDER BY id ASC";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*
    * MOSTRAR DETALLE DE TEMPORAL B
    */
	static public function mdlMostraDetallesTemporalB($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
					t.codigo,
					t.articulo,
					t.cantidad,
					t.precio,
					t.total,
					CONCAT(
					modelo,
					' - ',
					nombre,
					' - ',
					color,
					' - T',
					talla
					) AS packing,
					a.pedidos 
				FROM
					detalle_temporal t 
					LEFT JOIN articulojf a 
					ON t.articulo = a.articulo 
				WHERE t.codigo = :codigo 
				ORDER BY t.articulo");

		$stmt->bindParam(":codigo", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*
	* GUARDAR DETALLE DE TEMPORAL
	*/
	static public function mdlGuardarTemporal($tabla, $datos)
	{


		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla (codigo, cliente, vendedor, lista, agencia) VALUES (:codigo, :cliente, :vendedor, :lista, :agencia)");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":lista", $datos["lista"], PDO::PARAM_STR);
		$stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);

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
	 * Incrementa cantidad (+1) en detalle temporal por SKU (articulo articulojf / código de barras).
	 */
	static public function mdlIncrementarArticuloTemporalPedidoCv($codigoPedido, $articuloSku, $precio)
	{

		$pdo = Conexion::conectar();

		$stmt = $pdo->prepare(
			"SELECT cantidad FROM detalle_temporal WHERE codigo = :codigo AND articulo = :articulo LIMIT 1"
		);

		$stmt->bindParam(":codigo", $codigoPedido, PDO::PARAM_STR);
		$stmt->bindParam(":articulo", $articuloSku, PDO::PARAM_STR);
		$stmt->execute();
		$fila = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		$stmt = null;

		if ($fila) {

			$nueva = intval($fila["cantidad"], 10) + 1;

			$upd = $pdo->prepare(
				"UPDATE detalle_temporal SET cantidad = :cant, total = (:cant * :precio), precio = :preciob WHERE codigo = :codigo2 AND articulo = :articulo2"
			);

			$upd->bindParam(":cant", $nueva, PDO::PARAM_INT);
			$upd->bindParam(":precio", $precio, PDO::PARAM_STR);
			$upd->bindParam(":preciob", $precio, PDO::PARAM_STR);
			$upd->bindParam(":codigo2", $codigoPedido, PDO::PARAM_STR);
			$upd->bindParam(":articulo2", $articuloSku, PDO::PARAM_STR);

			if ($upd->execute()) {

				return "ok";
			}

			return "error";
		}

		$datos = array(
			"codigo"   => $codigoPedido,
			"articulo" => $articuloSku,
			"cantidad" => 1,
			"precio"   => $precio
		);

		return self::mdlGuardarTemporalDetalle("detalle_temporal", $datos);
	}

	/*
	 * Edición manual — línea detalle temporal (cantidad / precio) — pantalla escaneo barcode.
	 */
	static public function mdlActualizarLineaDetalleTemporalEscaneo($codigoPedido, $articuloSku, $cantidad, $precio)
	{

		$cantidad = (int) $cantidad;
		if ($cantidad < 1) {
			return "error_validacion";
		}

		$precio = round(floatval($precio), 4);
		if ($precio < 0) {
			return "error_validacion";
		}

		$total = round($cantidad * $precio, 2);
		$precStr = sprintf("%.4f", $precio);

		$pdo = Conexion::conectar();
		$upd = $pdo->prepare(
			"UPDATE detalle_temporal SET cantidad = :cant, precio = :prec, total = :tot WHERE codigo = :cod AND articulo = :art"
		);
		$upd->bindParam(":cant", $cantidad, PDO::PARAM_INT);
		$upd->bindParam(":prec", $precStr, PDO::PARAM_STR);
		$upd->bindParam(":tot", $total, PDO::PARAM_STR);
		$upd->bindParam(":cod", $codigoPedido, PDO::PARAM_STR);
		$upd->bindParam(":art", $articuloSku, PDO::PARAM_STR);

		if (!$upd->execute()) {
			return "error";
		}

		/* MySQL puede devolver rowCount 0 si cant./precio iguales; verificamos que la línea exista */
		$chk = $pdo->prepare(
			"SELECT 1 FROM detalle_temporal WHERE codigo = :cod2 AND articulo = :art2 LIMIT 1"
		);
		$chk->bindParam(":cod2", $codigoPedido, PDO::PARAM_STR);
		$chk->bindParam(":art2", $articuloSku, PDO::PARAM_STR);
		$chk->execute();

		return $chk->fetchColumn() !== false ? "ok" : "error";
	}

	/*
	 * Elimina línea por pedido temporal + SKU — pantalla escaneo barcode (prepared stmt).
	 */
	static public function mdlBorrarLineaDetalleTemporalEscaneo($codigoPedido, $articuloSku)
	{

		$pdo = Conexion::conectar();
		$del = $pdo->prepare(
			"DELETE FROM detalle_temporal WHERE codigo = :cod AND articulo = :art LIMIT 1"
		);
		$del->bindParam(":cod", $codigoPedido, PDO::PARAM_STR);
		$del->bindParam(":art", $articuloSku, PDO::PARAM_STR);

		if (!$del->execute()) {
			return "error";
		}

		if ($del->rowCount() > 0) {
			return "ok";
		}

		return "error";
	}

	/*
	* GUARDAR DETALLE DE TEMPORAL
	*/
	static public function mdlGuardarTemporalDetalleB($detalle)
	{


		$stmt = Conexion::conectar()->prepare("INSERT INTO detalle_temporal (
													codigo,
													articulo,
													cantidad,
													precio,
													total
												) 
												VALUES 
												$detalle");

		//$stmt->bindParam(":detalle", $detalle, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			//return $detalle;
			return $stmt->errorInfo();
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

	static public function mdlEliminarDetalleTemporalB($tabla, $pedido, $modelo)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE codigo = '$pedido' AND articulo like '$modelo%'");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
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
												detalle_temporal
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

		$sql = "UPDATE talonariosjf SET pedido = pedido+1";

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
					temporaljf
				SET
					cliente = :cliente,
					op_gravada = :op_gravada,
					descuento_total = :descuento_total,
					sub_total = :sub_total,
					igv = :impuesto,
					total = :total,
					vendedor = :vendedor,
					condicion_venta = :condicion_venta,
					agencia = :agencia,
					usuario = :usuario,
					dscto = :dscto
				WHERE codigo = :codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":op_gravada", $datos["op_gravada"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento_total", $datos["descuento_total"], PDO::PARAM_STR);
		$stmt->bindParam(":sub_total", $datos["sub_total"], PDO::PARAM_STR);
		$stmt->bindParam(":impuesto", $datos["impuesto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":condicion_venta", $datos["condicion_venta"], PDO::PARAM_STR);
		$stmt->bindParam(":agencia", $datos["agencia"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":dscto", $datos["dscto"], PDO::PARAM_STR);

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
	static public function mdlMostraPedidosCabecera($valor)
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
				temporaljf t
				LEFT JOIN clientesjf c
				ON t.cliente = c.codigo
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
					temporaljf t
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
	static public function mdlPedidosPendientes($vendedor)
	{

		if ($vendedor == null) {

			$sql = "SELECT 
			t.codigo,
			t.codigo,
			t.vendedor,
			(SELECT 
			  descripcion 
			FROM
			  maestrajf m 
			WHERE m.tipo_dato = 'tvend' 
			  AND m.codigo = t.vendedor) AS nom_vendedor,
			t.op_gravada,
			t.igv,
			t.total,
			t.condicion_venta,
			(SELECT 
			  descripcion 
			FROM
			  condiciones_ventajf cv 
			WHERE cv.id = t.condicion_venta) AS nom_condicion,
			t.estado,
			DATE(t.fecha) AS fecha,
			t.cliente,
			c.nombre,
			c.ubigeo,
			(SELECT 
			  nombre 
			FROM
			  ubigeo u 
			WHERE u.codigo = c.ubigeo) AS nom_ubigeo 
		  FROM
			temporaljf t 
			LEFT JOIN clientesjf c 
			  ON t.cliente = c.codigo 
			  WHERE t.estado NOT IN ('FACTURADOS', 'ANULADO') 
				AND t.cliente <> '' 
				AND (t.vendedor NOT LIKE '%08%' and t.vendedor NOT LIKE '%06%')
		  ORDER BY t.vendedor,
					t.estado,
					c.ubigeo,
					t.fecha";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$sql = "SELECT 
						t.codigo,
						t.codigo,
						t.vendedor,
						(SELECT 
						descripcion 
						FROM
						maestrajf m 
						WHERE m.tipo_dato = 'tvend' 
						AND m.codigo = t.vendedor) AS nom_vendedor,
						t.op_gravada,
						t.igv,
						t.total,
						t.condicion_venta,
						(SELECT 
						descripcion 
						FROM
						condiciones_ventajf cv 
						WHERE cv.id = t.condicion_venta) AS nom_condicion,
						t.estado,
						DATE(t.fecha) AS fecha,
						t.cliente,
						c.nombre,
						c.ubigeo,
						(SELECT 
						nombre 
						FROM
						ubigeo u 
						WHERE u.codigo = c.ubigeo) AS nom_ubigeo 
					FROM
						temporaljf t 
						LEFT JOIN clientesjf c 
						ON t.cliente = c.codigo 
					WHERE t.estado NOT IN ('FACTURADOS', 'ANULADO') 
						AND t.vendedor = :vendedor 
						ORDER BY t.estado,
							t.fecha DESC,
							c.ubigeo";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt = null;
	}

	/*
    * MOSTRAR LOS DATOS PARA LA IMPRESION
    */
	static public function mdlPedidoImpresion($codigo, $modelo)
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
						detalle_temporal dt
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
	static public function mdlPedidoImpresionC($codigo)
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
						detalle_temporal dt
						LEFT JOIN articulojf a
						ON dt.articulo = a.articulo
						LEFT JOIN modelojf m
						ON a.modelo = m.modelo
					WHERE dt.codigo = '$codigo'
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
	static public function mdlPedidoImpresionB($codigo, $ini, $fin)
	{

		$sql = "SELECT 
						dt.codigo,
						a.modelo,
						dt.precio,
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
						detalle_temporal dt 
						LEFT JOIN articulojf a 
						ON dt.articulo = a.articulo 
					WHERE codigo = $codigo 
					GROUP BY dt.codigo,
						a.modelo,
						a.cod_color,
						a.color 
					UNION
					SELECT 
						dt.codigo,
						a.modelo,
						'',
						'99',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'' 
					FROM
						detalle_temporal dt 
						LEFT JOIN articulojf a 
						ON dt.articulo = a.articulo 
					WHERE codigo = $codigo 
					GROUP BY dt.codigo,
						a.modelo 
					ORDER BY modelo,
						cod_color
					LIMIT $ini, $fin";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt = null;
	}

	/*
    * MOSTRAR LOS DATOS PARA LA IMPRESION
    */
	static public function mdlPedidoImpresionMod($valor)
	{

		$sql = "SELECT
			m.id_modelo,
			m.modelo
		FROM
			detalle_temporal dt
			LEFT JOIN articulojf a
			ON dt.articulo = a.articulo
			LEFT JOIN modelojf m
			ON a.modelo = m.modelo
		WHERE dt.codigo = $valor
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
	static public function mdlPedidoImpresionCab($valor)
	{

		$sql = "SELECT 
					t.codigo AS pedido,
					DATE(t.fecha) AS fecha,
					c.codigo,
					c.nombre,
					c.direccion,
					c.ubigeo,
					u.nombre AS nom_ubi,
					t.op_gravada,
					t.descuento_total,
					t.sub_total,
					t.igv,
					t.total,
					t.dscto,
					t.lista,
					CONCAT(
						t.vendedor,
						'-',
						(SELECT 
						m.descripcion 
						FROM
						maestrajf m 
						WHERE m.tipo_dato = 'TVEND' 
						AND t.vendedor = m.codigo)
					) AS vendedor,
					td.tipo_doc,
					c.documento,
					t.agencia,
					(SELECT 
						nombre 
					FROM
						agenciasjf a 
					WHERE a.id = t.agencia) AS nom_agencia  
					FROM
					temporaljf t 
					LEFT JOIN clientesjf c 
						ON t.cliente = c.codigo 
					LEFT JOIN ubigeo u 
						ON c.ubigeo = u.codigo 
					LEFT JOIN tipo_documentojf td 
						ON td.cod_doc = c.tipo_documento
				WHERE t.codigo = $valor";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/*
    * MOSTRAR PEDIDO CON FORMATO DE IMRPESION - TOTALES GENERALES
    */
	static public function mdlPedidoImpresionTotales($valor)
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
					detalle_temporal dt
					LEFT JOIN articulojf a
					ON dt.articulo = a.articulo
				WHERE dt.codigo = $valor";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/*
    * MOSTRAR DETALLE DE TEMPORAL
    */
	static public function mdlMostraPedidosGeneral($valor)
	{

		if ($valor == null) {

			$sql = "SELECT
						t.id,
						t.codigo,
						RIGHT(t.codigo, 7) AS codigoB,
						c.codigo AS cod_cli,
						c.nombre,
						c.tipo_documento,
						(SELECT 
							tipo_doc 
						FROM
							tipo_documentojf td 
						WHERE c.tipo_documento = td.cod_doc) AS tipo_doc,						
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
						temporaljf t
						LEFT JOIN clientesjf c
						ON t.cliente = c.codigo
						LEFT JOIN condiciones_ventajf cv
						ON t.condicion_venta = cv.id
						LEFT JOIN usuariosjf u
						ON t.usuario = u.id
					ORDER BY fecha DESC,
					 RIGHT(t.codigo, 7) DESC";

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
					temporaljf t
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
			RIGHT(t.codigo, 7) AS codigoB,
			c.codigo AS cod_cli,
			c.nombre,
			c.tipo_documento,
			(SELECT 
			  tipo_doc 
			FROM
			  tipo_documentojf td 
			WHERE c.tipo_documento = td.cod_doc) AS tipo_doc,
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
			temporaljf t 
			LEFT JOIN clientesjf c 
			  ON t.cliente = c.codigo 
			LEFT JOIN condiciones_ventajf cv 
			  ON t.condicion_venta = cv.id 
			LEFT JOIN usuariosjf u 
			  ON t.usuario = u.id 
		  WHERE t.estado = '$valor' 
		  ORDER BY 
			t.fecha DESC,
			RIGHT(t.codigo, 7)";

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
					(SELECT 
						tipo_doc 
					FROM
						tipo_documentojf td 
					WHERE c.tipo_documento = td.cod_doc) AS tipo_doc,
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
					temporaljf t
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
	* GUARDAR TEMPORAL BKP
	*/
	static public function mdlGuardarTemporalBkpCab($codigo)
	{


		$stmt = Conexion::conectar()->prepare("INSERT INTO temporaljf_bkp 
													(SELECT 
													* 
													FROM
													temporaljf 
													WHERE codigo = $codigo)");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}


	/*
	* GUARDAR TEMPORAL BKP
	*/
	static public function mdlGuardarTemporalBkpDet($codigo)
	{


		$stmt = Conexion::conectar()->prepare("INSERT INTO detalle_temporalbkp 
												(SELECT 
												* 
												FROM
												detalle_temporal 
												WHERE codigo = $codigo)");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
	* GUARDAR TEMPORAL BKP
	*/
	static public function mdlDividirPedidoD($pedido, $porcentaje)
	{


		$stmt = Conexion::conectar()->prepare("UPDATE 
											detalle_temporal 
										SET
											cantidad = ROUND((cantidad * $porcentaje), 0),
											total = ROUND(cantidad * precio, 2)
										WHERE codigo = $pedido");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
    * MOSTRAR los datos del nuevo total
    */
	static public function mdlDetalleToalDiv($pedido)
	{

		$sql = "SELECT 
						dt.codigo,
						SUM(total) AS total 
					FROM
						detalle_temporal dt 
					WHERE dt.codigo = $pedido 
					GROUP BY dt.codigo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->execute();

		return $stmt->fetch();

		$stmt = null;
	}

	/*
	* GUARDAR TEMPORAL DIVIDICO
	*/
	static public function mdlActualizarDiv($pedido, $op_gravada, $dscto, $subTotal, $igv, $total)
	{


		$stmt = Conexion::conectar()->prepare("UPDATE 
													temporaljf 
												SET
													op_gravada = $op_gravada,
													descuento_total = $dscto,
													sub_total = $subTotal,
													igv = $igv,
													total = $total 
												WHERE codigo = $pedido");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;
	}

	/*
    *ACTUALIZAR TOTALES DEL PEDIDO
    */
	static public function mdlLeerPedidoC($datos)
	{

		$sql = "INSERT INTO temporaljf (
							codigo,
							cliente,
							vendedor,
							lista,
							op_gravada,
							descuento_total,
							sub_total,
							igv,
							total,
							condicion_venta,
							estado,
							fecha,
							usuario,
							agencia,
							usuario_estado,
							dscto
						) 
						VALUES
							(
							:codigo,
							:cliente,
							:vendedor,
							:lista,
							:op_gravada,
							:descuento_total,
							:sub_total,
							:igv,
							:total,
							:condicion_venta,
							:estado,
							:fecha,
							:usuario,
							:agencia,
							:usuario_estado,
							:dscto
							)";

		$stmt = Conexion::conectar()->prepare($sql);

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
		$stmt->bindParam(":dscto", $datos["dscto"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	static public function mdlLeerPedidoD($detalle)
	{

		$sql = "INSERT INTO detalle_temporal (
								codigo,
								articulo,
								cantidad,
								precio,
								total
							) 
							VALUES
								$detalle";

		$stmt = Conexion::conectar()->prepare($sql);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	/*
    * MOSTRAR TEMPORAL con fecha y vendeor
    */
	static public function mdlMostrarTemporalFecVen($fecha, $vend)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
						'S60' AS tipo,
						t.codigo,
						DATE_FORMAT(t.fecha,'%d/%m/%y') AS fecha,
						t.cliente,
						c.nombre,
						t.total,
						cv.descripcion 
					FROM
						temporaljf t 
						LEFT JOIN clientesjf c 
						ON t.cliente = c.codigo 
						LEFT JOIN condiciones_ventajf cv 
						ON t.condicion_venta = cv.id 
					WHERE t.fecha = :fecha 
						AND t.usuario = :vend");

		$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
		$stmt->bindParam(":vend", $vend, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlCantAprobados()
	{


		$stmt = Conexion::conectar()->prepare("UPDATE 
		articulojf a 
		LEFT JOIN 
		  (SELECT 
			articulo,
			SUM(cantidad) AS total 
		  FROM
			temporaljf t 
			LEFT JOIN detalle_temporal dt 
			  ON t.codigo = dt.codigo 
		  WHERE estado IN ('APROBADO', 'APT', 'CONFIRMADO') 
		  GROUP BY articulo) t 
		  ON a.articulo = t.articulo SET a.pedidos = IFNULL(t.total,0)");

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	static public function mdlReiniciarPedido()
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
										articulojf 
									SET
										pedidos = 0");
		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*
    *BORRAR POR MODELO
    */
	static public function mdlBorrarModelo($modelo, $pedido)
	{

		$sql = "DELETE 
		FROM
		  detalle_temporal 
		WHERE codigo = $pedido 
		  AND articulo LIKE '$modelo%'";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":pedido", $pedido, PDO::PARAM_STR);
		$stmt->bindParam(":modelo", $modelo, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	static public function mdlBorrarArticulo($articulo, $pedido)
	{

		$sql = "DELETE 
		FROM
		  detalle_temporal 
		WHERE codigo = $pedido 
		  AND articulo = '$articulo'";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":pedido", $pedido, PDO::PARAM_STR);
		$stmt->bindParam(":articulo", $articulo, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	/*
    * MOSTRAR COTIZACION
    */
	static public function mdlMostrarCotizacion($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		dt.codigo,
		a.modelo,
		a.nombre,
		dt.precio,
		SUM(dt.cantidad) AS cantidad,
		SUM(dt.total) AS neto,
		SUM(dt.total) * 0.18 AS igv,
		SUM(dt.total) * 1.18 AS total 
	  FROM
		detalle_temporal dt 
		LEFT JOIN articulojf a 
		  ON dt.articulo = a.articulo 
	  WHERE dt.codigo = $valor 
	  GROUP BY a.modelo ");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	//*COPIAR CABECERA
	static public function mdlDuplicarCabecera($codDup, $talonarioN)
	{

		$sql = "INSERT INTO temporaljf (
								codigo,
								cliente,
								vendedor,
								lista,
								op_gravada,
								descuento_total,
								sub_total,
								igv,
								total,
								condicion_venta,
								estado,
								fecha,
								usuario,
								agencia,
								usuario_estado,
								dscto
							) 
							(SELECT 
								:talonarioN,
								cliente,
								vendedor,
								lista,
								op_gravada,
								descuento_total,
								sub_total,
								igv,
								total,
								condicion_venta,
								'GENERADO',
								NOW(),
								usuario,
								agencia,
								usuario_estado,
								dscto 
							FROM
								temporaljf 
							WHERE codigo = :codDup)";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codDup", $codDup, PDO::PARAM_STR);
		$stmt->bindParam(":talonarioN", $talonarioN, PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	//*COPIAR DETALLE
	static public function mdlDuplicarDetalle($codDup, $talonarioN)
	{

		$sql = "INSERT INTO detalle_temporal (
								codigo,
								articulo,
								cantidad,
								precio,
								total
							) 
							(SELECT 
								:talonarioN,
								articulo,
								cantidad,
								precio,
								total 
							FROM
								detalle_temporal 
							WHERE codigo = :codDup) ";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":codDup", $codDup, PDO::PARAM_STR);
		$stmt->bindParam(":talonarioN", $talonarioN, PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt = null;
	}

	/*
    * MOSTRAR COTIZACION
    */
	static public function mdlVerTalonario($serie, $talonario)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
							argumento AS talonario 
						FROM
							maestrajf 
						WHERE tipo_dato = 'TTAL' 
							AND descripcion = :serie");


		$stmt->bindParam(":serie", $serie, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	//*ACTUALIZAR TALONARIO
	static public function mdlSepararTalonario($serie, $talonario)
	{

		$sql = "UPDATE 
						maestrajf 
					SET
						argumento = :talonarioA 
					WHERE tipo_dato = 'TTAL' 
						AND descripcion = :serieA";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":serieA", $serie, PDO::PARAM_STR);
		$stmt->bindParam(":talonarioA", $talonario, PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	//*REINICIAR TALONARIO
	static public function mdlReiniciarTalonario($tipo)
	{

		$sql = "UPDATE 
					maestrajf 
				SET
					argumento = '0' 
				WHERE tipo_dato = 'TTAL' 
					AND codigo = :tipo";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	/*
    * MOSTRAR DETALLE DE TEMPORAL
    */
	static public function mdlTraerCuentas($valor)
	{

		if ($valor == "01" || $valor == "03") {

			$sql = "SELECT 
					t.cod_argumento AS codint,
					t.des_larga AS cuenta,
					t.des_corta AS codigo,
					t.valor_1 AS tipo 
				FROM
					tabla_m_detalle t 
				WHERE t.cod_tabla = 'TCUE' 
					AND t.valor_1 = '1'";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($valor == "07") {

			$sql = "SELECT 
					t.cod_argumento AS codint,
					t.des_larga AS cuenta,
					t.des_corta AS codigo,
					t.valor_1 AS tipo 
				FROM
					tabla_m_detalle t 
				WHERE t.cod_tabla = 'TCUE' 
					AND t.valor_1 IN ('2','3')";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($valor == "08") {

			$sql = "SELECT 
					t.cod_argumento AS codint,
					t.des_larga AS cuenta,
					t.des_corta AS codigo,
					t.valor_1 AS tipo 
				FROM
					tabla_m_detalle t 
				WHERE t.cod_tabla = 'TCUE' 
					AND t.valor_1 = '3'";

			$stmt = Conexion::conectar()->prepare($sql);

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt = null;
	}

	static public function mdlActualizarTotalPedido()
	{

		$sql = "UPDATE 
				temporaljf 
			SET
				total = op_gravada + igv";

		$stmt = Conexion::conectar()->prepare($sql);

		if ($stmt->execute()) {
			return "ok";
		} else {
			return "error";
		}

		$stmt = null;
	}

	//*REINICIAR TALONARIO
	static public function mdlActualizarTotales($codPedido)
	{

		$sql = "UPDATE 
					temporaljf t 
					LEFT JOIN 
					(SELECT 
						dt.codigo,
						SUM(dt.total) AS op_gravada,
						ROUND(SUM(dt.total) * 0.18, 2) AS igv,
						SUM(dt.total) + ROUND(SUM(dt.total) * 0.18, 2) AS total 
					FROM
						detalle_temporal dt 
					WHERE dt.codigo = '$codPedido') AS dt 
					ON t.codigo = dt.codigo SET t.op_gravada = dt.op_gravada,
					t.igv = 
					CASE
					WHEN t.lista = 'precio1' 
					THEN 0 
					ELSE dt.igv 
					END,
					t.total = 
					CASE
					WHEN t.lista = 'precio1' 
					THEN t.op_gravada 
					ELSE dt.total 
					END
				WHERE t.codigo = '$codPedido'";

		$stmt = Conexion::conectar()->prepare($sql);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	/*
    * MOSTRAR TEMPORAL CABECERA
    */
	static public function MostrarDatos($valor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
				* 
			FROM
				tabla_m_detalle t 
			WHERE t.cod_tabla = '$valor'");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}
}
