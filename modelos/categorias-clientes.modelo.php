<?php

require_once "conexion.php";

class ModeloCategoriasClientes
{

	/*=============================================
	Datos mínimos del cliente para resolver categoría efectiva
	=============================================*/
	static public function mdlDatosClienteCategoria($codigoCliente)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT c.codigo, c.nombre, c.grupo, c.estado,
				g.nombre AS nombre_grupo
			 FROM clientesjf c
			 LEFT JOIN grupos_empresarialesjf g
			   ON g.codigo = c.grupo
			  AND c.grupo IS NOT NULL
			  AND c.grupo <> ''
			 WHERE c.codigo = :codigo
			 LIMIT 1"
		);
		$stmt->bindParam(":codigo", $codigoCliente, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch();
	}

	/*=============================================
	Categoría efectiva por lote de clientes (grupo gana)
	Devuelve mapa: codigo_cliente => [codigo, nombre, color]
	=============================================*/
	static public function mdlCategoriasEfectivasPorClientes(array $codigosClientes)
	{

		$unicos = array();
		foreach ($codigosClientes as $codigo) {
			$codigo = trim((string) $codigo);
			if ($codigo !== "") {
				$unicos[$codigo] = true;
			}
		}

		$codigos = array_keys($unicos);
		if (empty($codigos)) {
			return array();
		}

		$placeholders = array();
		foreach ($codigos as $i => $codigo) {
			$placeholders[] = ":c" . $i;
		}

		$sql = "SELECT
					c.codigo AS codigo_cliente,
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
					END AS categoria_nombre,
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
				FROM clientesjf c
				WHERE c.codigo IN (" . implode(", ", $placeholders) . ")";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($codigos as $i => $codigo) {
			$stmt->bindValue(":c" . $i, $codigo, PDO::PARAM_STR);
		}
		$stmt->execute();

		$mapa = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$codigoCat = isset($row["categoria_codigo"]) ? trim((string) $row["categoria_codigo"]) : "";
			if ($codigoCat === "") {
				continue;
			}

			$mapa[$row["codigo_cliente"]] = array(
				"codigo" => $codigoCat,
				"nombre" => isset($row["categoria_nombre"]) ? trim((string) $row["categoria_nombre"]) : $codigoCat,
				"color" => isset($row["categoria_color"]) ? trim((string) $row["categoria_color"]) : "",
			);
		}

		return $mapa;
	}

	/*=============================================
	Asignación vigente de una entidad (cliente | grupo)
	=============================================*/
	static public function mdlAsignacionVigente($tipoEntidad, $codigoEntidad)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT a.*,
				c.codigo AS categoria_codigo,
				c.nombre AS categoria_nombre,
				c.descripcion AS categoria_descripcion,
				c.estado AS categoria_estado,
				c.orden AS categoria_orden,
				c.color AS categoria_color
			 FROM categorias_clientes_asignacionesjf a
			 INNER JOIN categorias_clientesjf c ON c.id = a.id_categoria
			 WHERE a.tipo_entidad = :tipo_entidad
			   AND a.codigo_entidad = :codigo_entidad
			   AND a.estado = 1
			   AND a.vigencia_desde <= NOW()
			   AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
			 ORDER BY a.id DESC
			 LIMIT 1"
		);
		$stmt->bindParam(":tipo_entidad", $tipoEntidad, PDO::PARAM_STR);
		$stmt->bindParam(":codigo_entidad", $codigoEntidad, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch();
	}

	/*=============================================
	Requisitos activos de una categoría
	=============================================*/
	static public function mdlRequisitosPorCategoria($idCategoria)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT id, id_categoria, tipo_requisito, valor_numerico, unidad,
				descripcion, estado
			 FROM categorias_clientes_requisitosjf
			 WHERE id_categoria = :id_categoria
			   AND estado = 1
			 ORDER BY id ASC"
		);
		$stmt->bindParam(":id_categoria", $idCategoria, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/*=============================================
	Beneficios activos de una categoría
	=============================================*/
	static public function mdlBeneficiosPorCategoria($idCategoria)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT id, id_categoria, descuento_venta_pct, descuento_pronto_pago_pct,
				descripcion, estado
			 FROM categorias_clientes_beneficiosjf
			 WHERE id_categoria = :id_categoria
			   AND estado = 1
			 LIMIT 1"
		);
		$stmt->bindParam(":id_categoria", $idCategoria, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetch();
	}

	/*=============================================
	Categoría por id o código
	=============================================*/
	static public function mdlMostrarCategoria($item, $valor)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM categorias_clientesjf WHERE $item = :valor LIMIT 1"
		);
		$stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch();
	}

	/*=============================================
	Listado de categorías con conteos de asignaciones vigentes
	=============================================*/
	static public function mdlListarCategorias()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT c.*,
				(
					SELECT COUNT(*)
					FROM clientesjf cli
					WHERE cli.estado = 1
					  AND (
						(
							(cli.grupo IS NULL OR cli.grupo = '')
							AND EXISTS (
								SELECT 1
								FROM categorias_clientes_asignacionesjf a
								WHERE a.tipo_entidad = 'cliente'
								  AND a.codigo_entidad = cli.codigo
								  AND a.id_categoria = c.id
								  AND a.estado = 1
								  AND a.vigencia_desde <= NOW()
								  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
							)
						)
						OR (
							cli.grupo IS NOT NULL AND cli.grupo <> ''
							AND EXISTS (
								SELECT 1
								FROM categorias_clientes_asignacionesjf a
								WHERE a.tipo_entidad = 'grupo'
								  AND a.codigo_entidad = cli.grupo
								  AND a.id_categoria = c.id
								  AND a.estado = 1
								  AND a.vigencia_desde <= NOW()
								  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
							)
						)
					  )
				) AS total_clientes,
				(SELECT COUNT(*)
				 FROM categorias_clientes_asignacionesjf a
				 WHERE a.id_categoria = c.id
				   AND a.tipo_entidad = 'grupo'
				   AND a.estado = 1
				   AND a.vigencia_desde <= NOW()
				   AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
				) AS total_grupos,
				(
					SELECT COUNT(*)
					FROM clientesjf cli
					WHERE cli.estado = 1
					  AND (
						(
							(cli.grupo IS NULL OR cli.grupo = '')
							AND EXISTS (
								SELECT 1
								FROM categorias_clientes_asignacionesjf a
								WHERE a.tipo_entidad = 'cliente'
								  AND a.codigo_entidad = cli.codigo
								  AND a.id_categoria = c.id
								  AND a.cumplimiento = 'por_revisar'
								  AND a.estado = 1
								  AND a.vigencia_desde <= NOW()
								  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
							)
						)
						OR (
							cli.grupo IS NOT NULL AND cli.grupo <> ''
							AND EXISTS (
								SELECT 1
								FROM categorias_clientes_asignacionesjf a
								WHERE a.tipo_entidad = 'grupo'
								  AND a.codigo_entidad = cli.grupo
								  AND a.id_categoria = c.id
								  AND a.cumplimiento = 'por_revisar'
								  AND a.estado = 1
								  AND a.vigencia_desde <= NOW()
								  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
							)
						)
					  )
				) AS total_por_revisar,
				(SELECT r.valor_numerico
				 FROM categorias_clientes_requisitosjf r
				 WHERE r.id_categoria = c.id
				   AND r.tipo_requisito = 'monto_compras_anual'
				   AND r.estado = 1
				 LIMIT 1) AS monto_ventas_anual,
				(SELECT r.valor_numerico
				 FROM categorias_clientes_requisitosjf r
				 WHERE r.id_categoria = c.id
				   AND r.tipo_requisito = 'linea_minima'
				   AND r.estado = 1
				 LIMIT 1) AS linea_minima,
				(SELECT b.descuento_venta_pct
				 FROM categorias_clientes_beneficiosjf b
				 WHERE b.id_categoria = c.id
				   AND b.estado = 1
				 LIMIT 1) AS descuento_venta_pct,
				(SELECT b.descuento_pronto_pago_pct
				 FROM categorias_clientes_beneficiosjf b
				 WHERE b.id_categoria = c.id
				   AND b.estado = 1
				 LIMIT 1) AS descuento_pronto_pago_pct
			 FROM categorias_clientesjf c
			 ORDER BY c.orden ASC, c.nombre ASC"
		);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/*=============================================
	Detalle de categoría + requisitos + beneficios
	=============================================*/
	static public function mdlDetalleCategoria($idCategoria)
	{

		$categoria = self::mdlMostrarCategoria("id", $idCategoria);
		if (!$categoria) {
			return null;
		}

		$stmtReq = Conexion::conectar()->prepare(
			"SELECT *
			 FROM categorias_clientes_requisitosjf
			 WHERE id_categoria = :id_categoria
			   AND tipo_requisito = 'monto_compras_anual'
			 LIMIT 1"
		);
		$stmtReq->bindParam(":id_categoria", $idCategoria, PDO::PARAM_INT);
		$stmtReq->execute();
		$requisito = $stmtReq->fetch();

		$stmtLinea = Conexion::conectar()->prepare(
			"SELECT *
			 FROM categorias_clientes_requisitosjf
			 WHERE id_categoria = :id_categoria
			   AND tipo_requisito = 'linea_minima'
			 LIMIT 1"
		);
		$stmtLinea->bindParam(":id_categoria", $idCategoria, PDO::PARAM_INT);
		$stmtLinea->execute();
		$requisitoLinea = $stmtLinea->fetch();

		$stmtBen = Conexion::conectar()->prepare(
			"SELECT *
			 FROM categorias_clientes_beneficiosjf
			 WHERE id_categoria = :id_categoria
			 LIMIT 1"
		);
		$stmtBen->bindParam(":id_categoria", $idCategoria, PDO::PARAM_INT);
		$stmtBen->execute();
		$beneficio = $stmtBen->fetch();

		return array(
			"categoria" => $categoria,
			"requisito" => $requisito ? $requisito : null,
			"requisito_linea" => $requisitoLinea ? $requisitoLinea : null,
			"beneficio" => $beneficio ? $beneficio : null
		);
	}

	/*=============================================
	Crear categoría
	=============================================*/
	static public function mdlCrearCategoria($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO categorias_clientesjf
				(codigo, nombre, descripcion, orden, color, estado, usureg, fecreg)
			 VALUES
				(:codigo, :nombre, :descripcion, :orden, :color, :estado, :usureg, :fecreg)"
		);

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":orden", $datos["orden"], PDO::PARAM_INT);
		$stmt->bindParam(":color", $datos["color"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);

		if (!$stmt->execute()) {
			return false;
		}

		$creada = self::mdlMostrarCategoria("codigo", $datos["codigo"]);
		return $creada ? (int) $creada["id"] : false;
	}

	/*=============================================
	Editar categoría
	=============================================*/
	static public function mdlEditarCategoria($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE categorias_clientesjf
			 SET nombre = :nombre,
				 descripcion = :descripcion,
				 orden = :orden,
				 color = :color,
				 estado = :estado,
				 usumod = :usumod,
				 fecmod = :fecmod
			 WHERE id = :id"
		);

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":orden", $datos["orden"], PDO::PARAM_INT);
		$stmt->bindParam(":color", $datos["color"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
		$stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);

		return $stmt->execute() ? true : false;
	}

	/*=============================================
	Cambiar solo estado (activar / desactivar)
	=============================================*/
	static public function mdlCambiarEstadoCategoria($id, $estado, $usumod, $fecmod)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE categorias_clientesjf
			 SET estado = :estado,
				 usumod = :usumod,
				 fecmod = :fecmod
			 WHERE id = :id"
		);

		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		$stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
		$stmt->bindParam(":usumod", $usumod, PDO::PARAM_STR);
		$stmt->bindParam(":fecmod", $fecmod, PDO::PARAM_STR);

		return $stmt->execute() ? true : false;
	}

	/*=============================================
	Asegurar fila de requisito monto_compras_anual
	=============================================*/
	static public function mdlUpsertRequisitoMonto($datos)
	{

		return self::mdlUpsertRequisitoTipo(array_merge($datos, array(
			"tipo_requisito" => "monto_compras_anual",
			"descripcion" => isset($datos["descripcion"])
				? $datos["descripcion"]
				: "Monto mínimo anual de compras"
		)));
	}

	/*=============================================
	Asegurar fila de requisito linea_minima
	=============================================*/
	static public function mdlUpsertRequisitoLineaMinima($datos)
	{

		return self::mdlUpsertRequisitoTipo(array_merge($datos, array(
			"tipo_requisito" => "linea_minima",
			"descripcion" => isset($datos["descripcion"])
				? $datos["descripcion"]
				: "Línea de crédito mínima coherente con la categoría"
		)));
	}

	/*=============================================
	Upsert genérico de requisito por tipo
	=============================================*/
	static public function mdlUpsertRequisitoTipo($datos)
	{

		$tipo = isset($datos["tipo_requisito"]) ? $datos["tipo_requisito"] : "";
		if ($tipo === "") {
			return false;
		}

		$stmtBuscar = Conexion::conectar()->prepare(
			"SELECT id
			 FROM categorias_clientes_requisitosjf
			 WHERE id_categoria = :id_categoria
			   AND tipo_requisito = :tipo_requisito
			 LIMIT 1"
		);
		$stmtBuscar->bindParam(":id_categoria", $datos["id_categoria"], PDO::PARAM_INT);
		$stmtBuscar->bindParam(":tipo_requisito", $tipo, PDO::PARAM_STR);
		$stmtBuscar->execute();
		$existe = $stmtBuscar->fetch();

		if ($existe) {
			$stmt = Conexion::conectar()->prepare(
				"UPDATE categorias_clientes_requisitosjf
				 SET valor_numerico = :valor_numerico,
					 unidad = :unidad,
					 descripcion = :descripcion,
					 estado = :estado,
					 usumod = :usuario,
					 fecmod = :fecha
				 WHERE id = :id"
			);
			$stmt->bindParam(":id", $existe["id"], PDO::PARAM_INT);
		} else {
			$stmt = Conexion::conectar()->prepare(
				"INSERT INTO categorias_clientes_requisitosjf
					(id_categoria, tipo_requisito, valor_numerico, unidad, descripcion, estado, usureg, fecreg)
				 VALUES
					(:id_categoria, :tipo_requisito, :valor_numerico, :unidad, :descripcion, :estado, :usuario, :fecha)"
			);
			$stmt->bindParam(":id_categoria", $datos["id_categoria"], PDO::PARAM_INT);
			$stmt->bindParam(":tipo_requisito", $tipo, PDO::PARAM_STR);
		}

		if ($datos["valor_numerico"] === null) {
			$stmt->bindValue(":valor_numerico", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":valor_numerico", $datos["valor_numerico"]);
		}
		$stmt->bindParam(":unidad", $datos["unidad"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);

		return $stmt->execute() ? true : false;
	}

	/*=============================================
	Piso de línea desde categoría vigente de una entidad
	=============================================*/
	static public function mdlPisoLineaEntidad($tipoEntidad, $codigoEntidad)
	{

		$vacio = array(
			"activo" => false,
			"tiene_categoria" => false,
			"monto" => 0.0,
			"monto_compras_anual" => null,
			"id_categoria" => null,
			"categoria_codigo" => null,
			"categoria_nombre" => null,
			"categoria_color" => null,
		);

		$tipoEntidad = trim((string) $tipoEntidad);
		$codigoEntidad = trim((string) $codigoEntidad);
		if ($tipoEntidad === "" || $codigoEntidad === "") {
			return $vacio;
		}

		$asignacion = self::mdlAsignacionVigente($tipoEntidad, $codigoEntidad);
		if (!$asignacion) {
			return $vacio;
		}

		$idCategoria = (int) $asignacion["id_categoria"];
		$stmt = Conexion::conectar()->prepare(
			"SELECT tipo_requisito, valor_numerico
			 FROM categorias_clientes_requisitosjf
			 WHERE id_categoria = :id_categoria
			   AND tipo_requisito IN ('linea_minima', 'monto_compras_anual')
			   AND estado = 1"
		);
		$stmt->bindParam(":id_categoria", $idCategoria, PDO::PARAM_INT);
		$stmt->execute();
		$reqs = $stmt->fetchAll();

		$monto = 0.0;
		$montoAnual = null;
		foreach ($reqs as $req) {
			if ($req["tipo_requisito"] === "linea_minima"
				&& $req["valor_numerico"] !== null
				&& $req["valor_numerico"] !== ""
			) {
				$monto = (float) $req["valor_numerico"];
			}
			if ($req["tipo_requisito"] === "monto_compras_anual"
				&& $req["valor_numerico"] !== null
				&& $req["valor_numerico"] !== ""
			) {
				$montoAnual = (float) $req["valor_numerico"];
			}
		}

		return array(
			"activo" => $monto > 0,
			"tiene_categoria" => true,
			"monto" => round($monto, 2),
			"monto_compras_anual" => $montoAnual !== null ? round($montoAnual, 2) : null,
			"id_categoria" => $idCategoria,
			"categoria_codigo" => $asignacion["categoria_codigo"],
			"categoria_nombre" => $asignacion["categoria_nombre"],
			"categoria_color" => isset($asignacion["categoria_color"]) ? $asignacion["categoria_color"] : null,
		);
	}

	/*=============================================
	Piso de línea efectiva para un cliente (grupo gana)
	=============================================*/
	static public function mdlPisoLineaCliente($codigoCliente)
	{

		$vacio = array(
			"activo" => false,
			"tiene_categoria" => false,
			"monto" => 0.0,
			"monto_compras_anual" => null,
			"id_categoria" => null,
			"categoria_codigo" => null,
			"categoria_nombre" => null,
			"categoria_color" => null,
			"origen" => null,
		);

		$cliente = self::mdlDatosClienteCategoria($codigoCliente);
		if (!$cliente) {
			return $vacio;
		}

		$codigoGrupo = isset($cliente["grupo"]) ? trim((string) $cliente["grupo"]) : "";
		if ($codigoGrupo !== "") {
			$piso = self::mdlPisoLineaEntidad("grupo", $codigoGrupo);
			$piso["origen"] = "grupo";
			return $piso;
		}

		$piso = self::mdlPisoLineaEntidad("cliente", trim((string) $codigoCliente));
		$piso["origen"] = "cliente";
		return $piso;
	}

	/*=============================================
	Asegurar fila de beneficios
	=============================================*/
	static public function mdlUpsertBeneficios($datos)
	{

		$stmtBuscar = Conexion::conectar()->prepare(
			"SELECT id
			 FROM categorias_clientes_beneficiosjf
			 WHERE id_categoria = :id_categoria
			 LIMIT 1"
		);
		$stmtBuscar->bindParam(":id_categoria", $datos["id_categoria"], PDO::PARAM_INT);
		$stmtBuscar->execute();
		$existe = $stmtBuscar->fetch();

		if ($existe) {
			$stmt = Conexion::conectar()->prepare(
				"UPDATE categorias_clientes_beneficiosjf
				 SET descuento_venta_pct = :descuento_venta_pct,
					 descuento_pronto_pago_pct = :descuento_pronto_pago_pct,
					 descripcion = :descripcion,
					 estado = :estado,
					 usumod = :usuario,
					 fecmod = :fecha
				 WHERE id = :id"
			);
			$stmt->bindParam(":id", $existe["id"], PDO::PARAM_INT);
		} else {
			$stmt = Conexion::conectar()->prepare(
				"INSERT INTO categorias_clientes_beneficiosjf
					(id_categoria, descuento_venta_pct, descuento_pronto_pago_pct, descripcion, estado, usureg, fecreg)
				 VALUES
					(:id_categoria, :descuento_venta_pct, :descuento_pronto_pago_pct, :descripcion, :estado, :usuario, :fecha)"
			);
			$stmt->bindParam(":id_categoria", $datos["id_categoria"], PDO::PARAM_INT);
		}

		if ($datos["descuento_venta_pct"] === null) {
			$stmt->bindValue(":descuento_venta_pct", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":descuento_venta_pct", $datos["descuento_venta_pct"]);
		}

		if ($datos["descuento_pronto_pago_pct"] === null) {
			$stmt->bindValue(":descuento_pronto_pago_pct", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":descuento_pronto_pago_pct", $datos["descuento_pronto_pago_pct"]);
		}

		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_INT);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);

		return $stmt->execute() ? true : false;
	}

	/*=============================================
	Clientes activos sin categoría efectiva
	=============================================*/
	static public function mdlContarClientesSinCategoria()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*) AS total
			 FROM clientesjf c
			 WHERE c.estado = 1
			   AND c.fecha IS NOT NULL
			   AND NOT (
					(
						(c.grupo IS NULL OR c.grupo = '')
						AND EXISTS (
							SELECT 1
							FROM categorias_clientes_asignacionesjf a
							WHERE a.tipo_entidad = 'cliente'
							  AND a.codigo_entidad = c.codigo
							  AND a.estado = 1
							  AND a.vigencia_desde <= NOW()
							  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
						)
					)
					OR (
						c.grupo IS NOT NULL AND c.grupo <> ''
						AND EXISTS (
							SELECT 1
							FROM categorias_clientes_asignacionesjf a
							WHERE a.tipo_entidad = 'grupo'
							  AND a.codigo_entidad = c.grupo
							  AND a.estado = 1
							  AND a.vigencia_desde <= NOW()
							  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
						)
					)
			   )"
		);
		$stmt->execute();
		$fila = $stmt->fetch();

		return $fila && isset($fila["total"]) ? (int) $fila["total"] : 0;
	}

	/*=============================================
	Categorías activas para selectores
	=============================================*/
	static public function mdlListarCategoriasActivas()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT id, codigo, nombre, color, orden
			 FROM categorias_clientesjf
			 WHERE estado = 1
			 ORDER BY orden ASC, nombre ASC"
		);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/*=============================================
	Cerrar asignaciones vigentes de una entidad
	=============================================*/
	static public function mdlCerrarAsignacionesVigentes($tipoEntidad, $codigoEntidad, $usuario, $fecha)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE categorias_clientes_asignacionesjf
			 SET estado = 0,
				 vigencia_hasta = :fecha,
				 usumod = :usuario,
				 fecmod = :fecha
			 WHERE tipo_entidad = :tipo_entidad
			   AND codigo_entidad = :codigo_entidad
			   AND estado = 1"
		);

		$stmt->bindParam(":tipo_entidad", $tipoEntidad, PDO::PARAM_STR);
		$stmt->bindParam(":codigo_entidad", $codigoEntidad, PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);

		return $stmt->execute() ? true : false;
	}

	/*=============================================
	Crear asignación (historial)
	=============================================*/
	static public function mdlCrearAsignacion($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO categorias_clientes_asignacionesjf
				(tipo_entidad, codigo_entidad, id_categoria, estado, cumplimiento,
				 origen, motivo, vigencia_desde, vigencia_hasta, es_excepcion, usureg, fecreg)
			 VALUES
				(:tipo_entidad, :codigo_entidad, :id_categoria, 1, :cumplimiento,
				 :origen, :motivo, :vigencia_desde, :vigencia_hasta, :es_excepcion, :usureg, :fecreg)"
		);

		$stmt->bindParam(":tipo_entidad", $datos["tipo_entidad"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo_entidad", $datos["codigo_entidad"], PDO::PARAM_STR);
		$stmt->bindParam(":id_categoria", $datos["id_categoria"], PDO::PARAM_INT);
		$stmt->bindParam(":cumplimiento", $datos["cumplimiento"], PDO::PARAM_STR);
		$stmt->bindParam(":origen", $datos["origen"], PDO::PARAM_STR);
		$stmt->bindParam(":vigencia_desde", $datos["vigencia_desde"], PDO::PARAM_STR);
		$stmt->bindParam(":es_excepcion", $datos["es_excepcion"], PDO::PARAM_INT);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecreg", $datos["fecreg"], PDO::PARAM_STR);

		if ($datos["motivo"] === null || $datos["motivo"] === "") {
			$stmt->bindValue(":motivo", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":motivo", $datos["motivo"], PDO::PARAM_STR);
		}

		if ($datos["vigencia_hasta"] === null) {
			$stmt->bindValue(":vigencia_hasta", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":vigencia_hasta", $datos["vigencia_hasta"], PDO::PARAM_STR);
		}

		return $stmt->execute() ? true : false;
	}

	/*=============================================
	Actualizar cumplimiento / excepción de asignación vigente
	=============================================*/
	static public function mdlActualizarRevisionAsignacion($datos)
	{

		$stmt = Conexion::conectar()->prepare(
			"UPDATE categorias_clientes_asignacionesjf
			 SET cumplimiento = :cumplimiento,
				 origen = :origen,
				 motivo = :motivo,
				 es_excepcion = :es_excepcion,
				 vigencia_hasta = :vigencia_hasta,
				 usumod = :usumod,
				 fecmod = :fecmod
			 WHERE id = :id
			   AND estado = 1"
		);

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":cumplimiento", $datos["cumplimiento"], PDO::PARAM_STR);
		$stmt->bindParam(":origen", $datos["origen"], PDO::PARAM_STR);
		$stmt->bindParam(":es_excepcion", $datos["es_excepcion"], PDO::PARAM_INT);
		$stmt->bindParam(":usumod", $datos["usumod"], PDO::PARAM_STR);
		$stmt->bindParam(":fecmod", $datos["fecmod"], PDO::PARAM_STR);

		if ($datos["motivo"] === null || $datos["motivo"] === "") {
			$stmt->bindValue(":motivo", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":motivo", $datos["motivo"], PDO::PARAM_STR);
		}

		if ($datos["vigencia_hasta"] === null || $datos["vigencia_hasta"] === "") {
			$stmt->bindValue(":vigencia_hasta", null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(":vigencia_hasta", $datos["vigencia_hasta"], PDO::PARAM_STR);
		}

		return $stmt->execute() ? true : false;
	}

	static public function mdlAsignacionPorId($id)
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT a.*, c.codigo AS categoria_codigo, c.nombre AS categoria_nombre, c.color AS categoria_color
			 FROM categorias_clientes_asignacionesjf a
			 INNER JOIN categorias_clientesjf c ON c.id = a.id_categoria
			 WHERE a.id = :id
			 LIMIT 1"
		);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetch();
	}

	/*=============================================
	Conteos resumen bandeja
	=============================================*/
	static public function mdlContarGruposSinCategoria()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT COUNT(*) AS total
			 FROM grupos_empresarialesjf g
			 WHERE g.estado = 1
			   AND NOT EXISTS (
				SELECT 1
				FROM categorias_clientes_asignacionesjf a
				WHERE a.tipo_entidad = 'grupo'
				  AND a.codigo_entidad = g.codigo
				  AND a.estado = 1
				  AND a.vigencia_desde <= NOW()
				  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
			   )"
		);
		$stmt->execute();
		$fila = $stmt->fetch();
		return $fila ? (int) $fila["total"] : 0;
	}

	/*=============================================
	Resumen clientes/grupos de DIST, MAYO, MINO
	=============================================*/
	static public function mdlResumenClientesGruposPorCategoriasBandeja()
	{

		$stmt = Conexion::conectar()->prepare(
			"SELECT
				c.codigo,
				c.nombre,
				c.orden,
				c.color,
				(
					SELECT COUNT(*)
					FROM clientesjf cli
					WHERE cli.estado = 1
					  AND (
						(
							(cli.grupo IS NULL OR cli.grupo = '')
							AND EXISTS (
								SELECT 1
								FROM categorias_clientes_asignacionesjf a
								WHERE a.tipo_entidad = 'cliente'
								  AND a.codigo_entidad = cli.codigo
								  AND a.id_categoria = c.id
								  AND a.estado = 1
								  AND a.vigencia_desde <= NOW()
								  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
							)
						)
						OR (
							cli.grupo IS NOT NULL AND cli.grupo <> ''
							AND EXISTS (
								SELECT 1
								FROM categorias_clientes_asignacionesjf a
								WHERE a.tipo_entidad = 'grupo'
								  AND a.codigo_entidad = cli.grupo
								  AND a.id_categoria = c.id
								  AND a.estado = 1
								  AND a.vigencia_desde <= NOW()
								  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
							)
						)
					  )
				) AS total_clientes,
				(
					SELECT COUNT(*)
					FROM categorias_clientes_asignacionesjf a
					WHERE a.id_categoria = c.id
					  AND a.tipo_entidad = 'grupo'
					  AND a.estado = 1
					  AND a.vigencia_desde <= NOW()
					  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
				) AS total_grupos
			 FROM categorias_clientesjf c
			 WHERE c.codigo IN ('DIST', 'MAYO', 'MINO')
			 ORDER BY FIELD(c.codigo, 'DIST', 'MAYO', 'MINO')"
		);
		$stmt->execute();

		$filas = $stmt->fetchAll();
		return is_array($filas) ? $filas : array();
	}

	/*=============================================
	Bandeja: solo DIST / MAYO / MINO
	=============================================*/
	static public function mdlListarBandejaRevision()
	{

		$sql = "
			SELECT
				a.tipo_entidad,
				a.codigo_entidad,
				CASE
					WHEN a.tipo_entidad = 'cliente' THEN IFNULL(cli.nombre, a.codigo_entidad)
					ELSE IFNULL(g.nombre, a.codigo_entidad)
				END AS nombre_entidad,
				a.id AS id_asignacion,
				a.id_categoria,
				cat.codigo AS categoria_codigo,
				cat.nombre AS categoria_nombre,
				cat.color AS categoria_color,
				a.cumplimiento,
				a.origen,
				a.es_excepcion,
				a.motivo,
				a.vigencia_hasta,
				CASE
					WHEN a.es_excepcion = 1 THEN 'Excepción'
					WHEN a.cumplimiento = 'por_revisar' THEN 'Por revisar'
					WHEN a.cumplimiento = 'pendiente' THEN 'Pendiente evaluación'
					WHEN a.cumplimiento = 'cumple' THEN 'Cumple'
					WHEN a.cumplimiento = 'no_cumple' THEN 'No cumple'
					ELSE a.cumplimiento
				END AS motivo_bandeja,
				a.codigo_entidad AS orden_codigo
			FROM categorias_clientes_asignacionesjf a
			INNER JOIN categorias_clientesjf cat ON cat.id = a.id_categoria
			LEFT JOIN clientesjf cli
				ON a.tipo_entidad = 'cliente' AND cli.codigo = a.codigo_entidad
			LEFT JOIN grupos_empresarialesjf g
				ON a.tipo_entidad = 'grupo' AND g.codigo = a.codigo_entidad
			WHERE a.estado = 1
			  AND a.vigencia_desde <= NOW()
			  AND (a.vigencia_hasta IS NULL OR a.vigencia_hasta >= NOW())
			  AND cat.codigo IN ('DIST', 'MAYO', 'MINO')
			  AND (
				a.tipo_entidad = 'grupo'
				OR (
					a.tipo_entidad = 'cliente'
					AND (cli.grupo IS NULL OR cli.grupo = '')
				)
			  )
			ORDER BY
				FIELD(cat.codigo, 'DIST', 'MAYO', 'MINO'),
				a.tipo_entidad,
				nombre_entidad
		";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();

		return $stmt->fetchAll();
	}

	/*=============================================
	Monto facturado últimos 12 meses (S02/S03/S70, no anulados)
	=============================================*/
	static public function mdlMontoFacturado12mClientes($codigos)
	{

		if (!is_array($codigos) || count($codigos) === 0) {
			return array();
		}

		$placeholders = array();
		$params = array();
		foreach ($codigos as $i => $codigo) {
			$key = ":c" . $i;
			$placeholders[] = $key;
			$params[$key] = $codigo;
		}

		$sql = "SELECT v.cliente AS codigo,
				IFNULL(SUM(v.total), 0) AS monto_12m
			 FROM ventajf v
			 WHERE v.cliente IN (" . implode(",", $placeholders) . ")
			   AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
			   AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			   AND UPPER(v.tipo) IN ('S02', 'S03', 'S70')
			 GROUP BY v.cliente";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $key => $valor) {
			$stmt->bindValue($key, $valor, PDO::PARAM_STR);
		}
		$stmt->execute();

		$mapa = array();
		$filas = $stmt->fetchAll();
		if (is_array($filas)) {
			foreach ($filas as $fila) {
				$mapa[$fila["codigo"]] = (float) $fila["monto_12m"];
			}
		}

		return $mapa;
	}

	static public function mdlMontoFacturado12mGrupos($codigosGrupo)
	{

		if (!is_array($codigosGrupo) || count($codigosGrupo) === 0) {
			return array();
		}

		$placeholders = array();
		$params = array();
		foreach ($codigosGrupo as $i => $codigo) {
			$key = ":g" . $i;
			$placeholders[] = $key;
			$params[$key] = $codigo;
		}

		$sql = "SELECT c.grupo AS codigo,
				IFNULL(SUM(v.total), 0) AS monto_12m
			 FROM ventajf v
			 INNER JOIN clientesjf c
			   ON c.codigo = v.cliente
			  AND c.estado = 1
			  AND c.grupo IN (" . implode(",", $placeholders) . ")
			 WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
			   AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			   AND UPPER(v.tipo) IN ('S02', 'S03', 'S70')
			 GROUP BY c.grupo";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $key => $valor) {
			$stmt->bindValue($key, $valor, PDO::PARAM_STR);
		}
		$stmt->execute();

		$mapa = array();
		$filas = $stmt->fetchAll();
		if (is_array($filas)) {
			foreach ($filas as $fila) {
				$mapa[$fila["codigo"]] = (float) $fila["monto_12m"];
			}
		}

		return $mapa;
	}

	/*=============================================
	Monto facturado del mes actual (S02/S03/S70, no anulados)
	=============================================*/
	static public function mdlMontoFacturadoMesGrupos($codigosGrupo)
	{

		if (!is_array($codigosGrupo) || count($codigosGrupo) === 0) {
			return array();
		}

		$placeholders = array();
		$params = array();
		foreach ($codigosGrupo as $i => $codigo) {
			$key = ":g" . $i;
			$placeholders[] = $key;
			$params[$key] = $codigo;
		}

		$sql = "SELECT c.grupo AS codigo,
				IFNULL(SUM(v.total), 0) AS monto_mes
			 FROM ventajf v
			 INNER JOIN clientesjf c
			   ON c.codigo = v.cliente
			  AND c.estado = 1
			  AND c.grupo IN (" . implode(",", $placeholders) . ")
			 WHERE v.fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
			   AND v.fecha < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
			   AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
			   AND UPPER(v.tipo) IN ('S02', 'S03', 'S70')
			 GROUP BY c.grupo";

		$stmt = Conexion::conectar()->prepare($sql);
		foreach ($params as $key => $valor) {
			$stmt->bindValue($key, $valor, PDO::PARAM_STR);
		}
		$stmt->execute();

		$mapa = array();
		$filas = $stmt->fetchAll();
		if (is_array($filas)) {
			foreach ($filas as $fila) {
				$mapa[$fila["codigo"]] = (float) $fila["monto_mes"];
			}
		}

		return $mapa;
	}

	static public function mdlRequisitosMontoPorCategorias($idsCategoria)
	{

		if (!is_array($idsCategoria) || count($idsCategoria) === 0) {
			return array();
		}

		$ids = array();
		foreach ($idsCategoria as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}

		if (count($ids) === 0) {
			return array();
		}

		$sql = "SELECT id_categoria, valor_numerico
			 FROM categorias_clientes_requisitosjf
			 WHERE tipo_requisito = 'monto_compras_anual'
			   AND estado = 1
			   AND id_categoria IN (" . implode(",", $ids) . ")";

		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute();

		$mapa = array();
		$filas = $stmt->fetchAll();
		if (is_array($filas)) {
			foreach ($filas as $fila) {
				$mapa[(int) $fila["id_categoria"]] = $fila["valor_numerico"];
			}
		}

		return $mapa;
	}
}
