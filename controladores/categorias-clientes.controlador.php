<?php

class ControladorCategoriasClientes
{

	/*=============================================
	Colores por código (fallback si color en BD está vacío)
	=============================================*/
	static public function ctrColorDefaultPorCodigo($codigo)
	{

		$mapa = array(
			"DIST" => "#dd4b39",
			"MAYO" => "#00a65a",
			"MINO" => "#f39c12",
			"CATA" => "#00c0ef",
			"UFIN" => "#605ca8"
		);

		$codigo = strtoupper(trim((string) $codigo));
		return isset($mapa[$codigo]) ? $mapa[$codigo] : "#777777";
	}

	static public function ctrResolverColorCategoria($color, $codigo = "")
	{

		$color = trim((string) $color);
		if ($color !== "" && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $color)) {
			return $color;
		}

		return self::ctrColorDefaultPorCodigo($codigo);
	}

	static public function ctrHtmlBadgeCategoria($nombre, $codigo = "", $color = null)
	{

		$nombre = trim((string) $nombre);
		if (
			$nombre === "" ||
			$nombre === "Sin categoría / pendiente" ||
			$nombre === "Sin categoría"
		) {
			return "<span class='label label-default'>Sin categoría</span>";
		}

		$hex = self::ctrResolverColorCategoria($color, $codigo);
		return "<span class='label' style='background-color:" . $hex . ";'>" .
			htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") .
			"</span>";
	}

	/*=============================================
	Categoría efectiva de un cliente
	-----------------------------------------------
	Regla única:
	- Sin grupo  -> asignación vigente del cliente
	- Con grupo  -> SOLO asignación vigente del grupo (ignora individual)
	- Sin asignación vigente -> "Sin categoría / pendiente"
	=============================================*/
	static public function ctrObtenerCategoriaEfectivaCliente($codigoCliente)
	{

		$respuestaBase = array(
			"ok" => false,
			"tiene_categoria" => false,
			"etiqueta" => "Sin categoría / pendiente",
			"origen_asignacion" => null,
			"codigo_cliente" => $codigoCliente,
			"nombre_cliente" => null,
			"codigo_grupo" => null,
			"nombre_grupo" => null,
			"categoria" => null,
			"asignacion" => null,
			"requisitos" => array(),
			"beneficios" => null,
			"mensaje" => ""
		);

		$codigoCliente = trim((string) $codigoCliente);
		if ($codigoCliente === "") {
			$respuestaBase["mensaje"] = "Código de cliente vacío";
			return $respuestaBase;
		}

		$cliente = ModeloCategoriasClientes::mdlDatosClienteCategoria($codigoCliente);
		if (!$cliente) {
			$respuestaBase["mensaje"] = "Cliente no encontrado";
			return $respuestaBase;
		}

		$respuestaBase["ok"] = true;
		$respuestaBase["nombre_cliente"] = $cliente["nombre"];

		$codigoGrupo = isset($cliente["grupo"]) ? trim((string) $cliente["grupo"]) : "";
		$tieneGrupo = ($codigoGrupo !== "");

		if ($tieneGrupo) {
			$tipoEntidad = "grupo";
			$codigoEntidad = $codigoGrupo;
			$origenAsignacion = "grupo";
			$respuestaBase["codigo_grupo"] = $codigoGrupo;
			$respuestaBase["nombre_grupo"] = isset($cliente["nombre_grupo"]) ? $cliente["nombre_grupo"] : null;
			$respuestaBase["mensaje"] = "La categoría efectiva proviene del grupo empresarial";
		} else {
			$tipoEntidad = "cliente";
			$codigoEntidad = $codigoCliente;
			$origenAsignacion = "cliente";
			$respuestaBase["mensaje"] = "La categoría efectiva proviene de la asignación individual del cliente";
		}

		$asignacion = ModeloCategoriasClientes::mdlAsignacionVigente($tipoEntidad, $codigoEntidad);

		if (!$asignacion) {
			$respuestaBase["origen_asignacion"] = $origenAsignacion;
			$respuestaBase["etiqueta"] = "Sin categoría / pendiente";
			$respuestaBase["tiene_categoria"] = false;

			if ($tieneGrupo) {
				$respuestaBase["mensaje"] = "El cliente pertenece a un grupo sin categoría vigente";
			} else {
				$respuestaBase["mensaje"] = "El cliente no tiene categoría vigente asignada";
			}

			return $respuestaBase;
		}

		$idCategoria = (int) $asignacion["id_categoria"];
		$requisitos = ModeloCategoriasClientes::mdlRequisitosPorCategoria($idCategoria);
		$beneficios = ModeloCategoriasClientes::mdlBeneficiosPorCategoria($idCategoria);

		$respuestaBase["tiene_categoria"] = true;
		$respuestaBase["origen_asignacion"] = $origenAsignacion;
		$respuestaBase["etiqueta"] = $asignacion["categoria_nombre"];
		$respuestaBase["categoria"] = array(
			"id" => $idCategoria,
			"codigo" => $asignacion["categoria_codigo"],
			"nombre" => $asignacion["categoria_nombre"],
			"descripcion" => $asignacion["categoria_descripcion"],
			"estado" => (int) $asignacion["categoria_estado"],
			"orden" => (int) $asignacion["categoria_orden"],
			"color" => self::ctrResolverColorCategoria(
				isset($asignacion["categoria_color"]) ? $asignacion["categoria_color"] : "",
				$asignacion["categoria_codigo"]
			)
		);
		$respuestaBase["asignacion"] = array(
			"id" => (int) $asignacion["id"],
			"tipo_entidad" => $asignacion["tipo_entidad"],
			"codigo_entidad" => $asignacion["codigo_entidad"],
			"origen" => $asignacion["origen"],
			"cumplimiento" => $asignacion["cumplimiento"],
			"motivo" => $asignacion["motivo"],
			"vigencia_desde" => $asignacion["vigencia_desde"],
			"vigencia_hasta" => $asignacion["vigencia_hasta"],
			"es_excepcion" => (int) $asignacion["es_excepcion"],
			"estado" => (int) $asignacion["estado"]
		);
		$respuestaBase["requisitos"] = $requisitos ? $requisitos : array();
		$respuestaBase["beneficios"] = $beneficios ? $beneficios : null;

		return $respuestaBase;
	}

	/*=============================================
	Listado para DataTable / UI
	=============================================*/
	static public function ctrListarCategorias()
	{

		return ModeloCategoriasClientes::mdlListarCategorias();
	}

	static public function ctrDetalleCategoria($idCategoria)
	{

		return ModeloCategoriasClientes::mdlDetalleCategoria((int) $idCategoria);
	}

	/*=============================================
	Helpers internos
	=============================================*/
	static private function ctrUsuarioActual()
	{

		return isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "sistema";
	}

	static private function ctrFechaActual()
	{

		date_default_timezone_set("America/Lima");
		$fecha = new DateTime();
		return $fecha->format("Y-m-d H:i:s");
	}

	static private function ctrNormalizarDecimal($valor)
	{

		if ($valor === null) {
			return null;
		}

		$valor = trim((string) $valor);
		if ($valor === "") {
			return null;
		}

		$valor = str_replace(",", ".", $valor);
		if (!is_numeric($valor)) {
			return false;
		}

		return round((float) $valor, 2);
	}

	static private function ctrNormalizarCodigo($codigo)
	{

		$codigo = strtoupper(trim((string) $codigo));
		$codigo = preg_replace("/[^A-Z0-9_\-]/", "", $codigo);
		return $codigo;
	}

	/*=============================================
	Crear categoría + requisito + beneficios (AJAX)
	=============================================*/
	static public function ctrCrearCategoriaAjax($payload)
	{

		$codigo = self::ctrNormalizarCodigo(isset($payload["codigo"]) ? $payload["codigo"] : "");
		$nombre = trim(isset($payload["nombre"]) ? $payload["nombre"] : "");
		$descripcion = trim(isset($payload["descripcion"]) ? $payload["descripcion"] : "");
		$orden = isset($payload["orden"]) ? (int) $payload["orden"] : 0;
		$estado = isset($payload["estado"]) ? (int) $payload["estado"] : 1;
		$color = self::ctrResolverColorCategoria(
			isset($payload["color"]) ? $payload["color"] : "",
			$codigo
		);

		if ($codigo === "" || strlen($codigo) > 20) {
			return array("ok" => false, "mensaje" => "Código inválido (máx. 20, A-Z 0-9)");
		}

		if ($nombre === "") {
			return array("ok" => false, "mensaje" => "El nombre es obligatorio");
		}

		$existe = ModeloCategoriasClientes::mdlMostrarCategoria("codigo", $codigo);
		if ($existe) {
			return array("ok" => false, "mensaje" => "Ya existe una categoría con ese código");
		}

		$monto = self::ctrNormalizarDecimal(isset($payload["monto_compras_anual"]) ? $payload["monto_compras_anual"] : "");
		$dtoVenta = self::ctrNormalizarDecimal(isset($payload["descuento_venta_pct"]) ? $payload["descuento_venta_pct"] : "");
		$dtoPronto = self::ctrNormalizarDecimal(isset($payload["descuento_pronto_pago_pct"]) ? $payload["descuento_pronto_pago_pct"] : "");

		if ($monto === false || $dtoVenta === false || $dtoPronto === false) {
			return array("ok" => false, "mensaje" => "Hay valores numéricos inválidos");
		}

		$usuario = self::ctrUsuarioActual();
		$fecha = self::ctrFechaActual();

		$id = ModeloCategoriasClientes::mdlCrearCategoria(array(
			"codigo" => $codigo,
			"nombre" => $nombre,
			"descripcion" => $descripcion,
			"orden" => $orden,
			"color" => $color,
			"estado" => ($estado === 1 ? 1 : 0),
			"usureg" => $usuario,
			"fecreg" => $fecha
		));

		if (!$id) {
			return array("ok" => false, "mensaje" => "No se pudo crear la categoría");
		}

		ModeloCategoriasClientes::mdlUpsertRequisitoMonto(array(
			"id_categoria" => $id,
			"valor_numerico" => $monto,
			"unidad" => "PEN",
			"descripcion" => "Monto mínimo anual de compras",
			"estado" => 1,
			"usuario" => $usuario,
			"fecha" => $fecha
		));

		ModeloCategoriasClientes::mdlUpsertBeneficios(array(
			"id_categoria" => $id,
			"descuento_venta_pct" => $dtoVenta,
			"descuento_pronto_pago_pct" => $dtoPronto,
			"descripcion" => "Beneficios de la categoría",
			"estado" => 1,
			"usuario" => $usuario,
			"fecha" => $fecha
		));

		return array("ok" => true, "mensaje" => "Categoría creada correctamente", "id" => $id);
	}

	/*=============================================
	Editar categoría + requisito + beneficios (AJAX)
	=============================================*/
	static public function ctrEditarCategoriaAjax($payload)
	{

		$id = isset($payload["id"]) ? (int) $payload["id"] : 0;
		$nombre = trim(isset($payload["nombre"]) ? $payload["nombre"] : "");
		$descripcion = trim(isset($payload["descripcion"]) ? $payload["descripcion"] : "");
		$orden = isset($payload["orden"]) ? (int) $payload["orden"] : 0;
		$estado = isset($payload["estado"]) ? (int) $payload["estado"] : 1;
		$colorInput = isset($payload["color"]) ? trim($payload["color"]) : "";

		if ($id <= 0) {
			return array("ok" => false, "mensaje" => "Categoría no válida");
		}

		if ($nombre === "") {
			return array("ok" => false, "mensaje" => "El nombre es obligatorio");
		}

		$actual = ModeloCategoriasClientes::mdlMostrarCategoria("id", $id);
		if (!$actual) {
			return array("ok" => false, "mensaje" => "La categoría no existe");
		}

		$color = self::ctrResolverColorCategoria(
			$colorInput,
			isset($actual["codigo"]) ? $actual["codigo"] : ""
		);

		$monto = self::ctrNormalizarDecimal(isset($payload["monto_compras_anual"]) ? $payload["monto_compras_anual"] : "");
		$dtoVenta = self::ctrNormalizarDecimal(isset($payload["descuento_venta_pct"]) ? $payload["descuento_venta_pct"] : "");
		$dtoPronto = self::ctrNormalizarDecimal(isset($payload["descuento_pronto_pago_pct"]) ? $payload["descuento_pronto_pago_pct"] : "");

		if ($monto === false || $dtoVenta === false || $dtoPronto === false) {
			return array("ok" => false, "mensaje" => "Hay valores numéricos inválidos");
		}

		$usuario = self::ctrUsuarioActual();
		$fecha = self::ctrFechaActual();

		$ok = ModeloCategoriasClientes::mdlEditarCategoria(array(
			"id" => $id,
			"nombre" => $nombre,
			"descripcion" => $descripcion,
			"orden" => $orden,
			"color" => $color,
			"estado" => ($estado === 1 ? 1 : 0),
			"usumod" => $usuario,
			"fecmod" => $fecha
		));

		if (!$ok) {
			return array("ok" => false, "mensaje" => "No se pudo actualizar la categoría");
		}

		ModeloCategoriasClientes::mdlUpsertRequisitoMonto(array(
			"id_categoria" => $id,
			"valor_numerico" => $monto,
			"unidad" => "PEN",
			"descripcion" => "Monto mínimo anual de compras",
			"estado" => 1,
			"usuario" => $usuario,
			"fecha" => $fecha
		));

		ModeloCategoriasClientes::mdlUpsertBeneficios(array(
			"id_categoria" => $id,
			"descuento_venta_pct" => $dtoVenta,
			"descuento_pronto_pago_pct" => $dtoPronto,
			"descripcion" => "Beneficios de la categoría",
			"estado" => 1,
			"usuario" => $usuario,
			"fecha" => $fecha
		));

		return array("ok" => true, "mensaje" => "Categoría actualizada correctamente");
	}

	/*=============================================
	Activar / desactivar (AJAX)
	=============================================*/
	static public function ctrCambiarEstadoCategoriaAjax($id, $estado)
	{

		$id = (int) $id;
		$estado = ((int) $estado === 1) ? 1 : 0;

		if ($id <= 0) {
			return array("ok" => false, "mensaje" => "Categoría no válida");
		}

		$actual = ModeloCategoriasClientes::mdlMostrarCategoria("id", $id);
		if (!$actual) {
			return array("ok" => false, "mensaje" => "La categoría no existe");
		}

		$ok = ModeloCategoriasClientes::mdlCambiarEstadoCategoria(
			$id,
			$estado,
			self::ctrUsuarioActual(),
			self::ctrFechaActual()
		);

		if (!$ok) {
			return array("ok" => false, "mensaje" => "No se pudo cambiar el estado");
		}

		return array(
			"ok" => true,
			"mensaje" => $estado === 1 ? "Categoría activada" : "Categoría desactivada",
			"estado" => $estado
		);
	}

	/*=============================================
	Categorías activas (selectores)
	=============================================*/
	static public function ctrListarCategoriasActivas()
	{

		return ModeloCategoriasClientes::mdlListarCategoriasActivas();
	}

	/*=============================================
	Clientes activos sin categoría efectiva
	=============================================*/
	static public function ctrContarClientesSinCategoria()
	{

		return ModeloCategoriasClientes::mdlContarClientesSinCategoria();
	}

	/*=============================================
	Cerrar asignación vigente de una entidad
	=============================================*/
	static public function ctrCerrarAsignacionEntidad($tipoEntidad, $codigoEntidad)
	{

		return ModeloCategoriasClientes::mdlCerrarAsignacionesVigentes(
			$tipoEntidad,
			$codigoEntidad,
			self::ctrUsuarioActual(),
			self::ctrFechaActual()
		);
	}

	/*=============================================
	Asignar categoría a cliente o grupo
	$idCategoria vacío/0 = quitar categoría vigente
	=============================================*/
	static public function ctrAsignarCategoriaEntidad($payload)
	{

		$tipoEntidad = isset($payload["tipo_entidad"]) ? trim($payload["tipo_entidad"]) : "";
		$codigoEntidad = isset($payload["codigo_entidad"]) ? trim($payload["codigo_entidad"]) : "";
		$idCategoria = isset($payload["id_categoria"]) ? (int) $payload["id_categoria"] : 0;
		$motivo = isset($payload["motivo"]) ? trim($payload["motivo"]) : "";
		$esExcepcion = isset($payload["es_excepcion"]) ? (int) $payload["es_excepcion"] : 0;
		$origen = ($esExcepcion === 1) ? "excepcion" : "manual";
		$cumplimiento = isset($payload["cumplimiento"]) ? trim($payload["cumplimiento"]) : "pendiente";
		$vigenciaHasta = isset($payload["vigencia_hasta"]) ? trim($payload["vigencia_hasta"]) : "";

		$cumplimientosOk = array("pendiente", "cumple", "no_cumple", "por_revisar");
		if (!in_array($cumplimiento, $cumplimientosOk)) {
			$cumplimiento = "pendiente";
		}

		if ($tipoEntidad !== "cliente" && $tipoEntidad !== "grupo") {
			return array("ok" => false, "mensaje" => "Tipo de entidad inválido");
		}

		if ($codigoEntidad === "") {
			return array("ok" => false, "mensaje" => "Código de entidad vacío");
		}

		if ($tipoEntidad === "cliente") {
			$cliente = ModeloCategoriasClientes::mdlDatosClienteCategoria($codigoEntidad);
			if (!$cliente) {
				return array("ok" => false, "mensaje" => "Cliente no encontrado");
			}

			$codigoGrupo = isset($cliente["grupo"]) ? trim((string) $cliente["grupo"]) : "";
			if ($codigoGrupo !== "") {
				return array(
					"ok" => false,
					"mensaje" => "Este cliente pertenece a un grupo. La categoría se administra en el grupo empresarial."
				);
			}
		}

		$usuario = self::ctrUsuarioActual();
		$fecha = self::ctrFechaActual();

		ModeloCategoriasClientes::mdlCerrarAsignacionesVigentes(
			$tipoEntidad,
			$codigoEntidad,
			$usuario,
			$fecha
		);

		if ($idCategoria <= 0) {
			return array("ok" => true, "mensaje" => "Categoría retirada. Queda sin categoría / pendiente");
		}

		$categoria = ModeloCategoriasClientes::mdlMostrarCategoria("id", $idCategoria);
		if (!$categoria || (int) $categoria["estado"] !== 1) {
			return array("ok" => false, "mensaje" => "La categoría no existe o está inactiva");
		}

		if ($esExcepcion === 1 && $motivo === "") {
			return array("ok" => false, "mensaje" => "Debe indicar un motivo para la excepción");
		}

		if ($esExcepcion === 1 && $vigenciaHasta === "") {
			return array("ok" => false, "mensaje" => "Debe indicar la fecha de vencimiento de la excepción");
		}

		$ok = ModeloCategoriasClientes::mdlCrearAsignacion(array(
			"tipo_entidad" => $tipoEntidad,
			"codigo_entidad" => $codigoEntidad,
			"id_categoria" => $idCategoria,
			"cumplimiento" => $cumplimiento,
			"origen" => $origen,
			"motivo" => ($motivo !== "" ? $motivo : null),
			"vigencia_desde" => $fecha,
			"vigencia_hasta" => ($vigenciaHasta !== "" ? $vigenciaHasta . (strlen($vigenciaHasta) === 10 ? " 23:59:59" : "") : null),
			"es_excepcion" => ($esExcepcion === 1 ? 1 : 0),
			"usureg" => $usuario,
			"fecreg" => $fecha
		));

		if (!$ok) {
			return array("ok" => false, "mensaje" => "No se pudo guardar la asignación");
		}

		return array(
			"ok" => true,
			"mensaje" => "Categoría asignada correctamente",
			"categoria" => array(
				"id" => (int) $categoria["id"],
				"codigo" => $categoria["codigo"],
				"nombre" => $categoria["nombre"],
				"color" => self::ctrResolverColorCategoria(
					isset($categoria["color"]) ? $categoria["color"] : "",
					$categoria["codigo"]
				)
			)
		);
	}

	/*=============================================
	Categoría vigente de un grupo (para UI)
	=============================================*/
	static public function ctrCategoriaVigenteGrupo($codigoGrupo)
	{

		$codigoGrupo = trim((string) $codigoGrupo);
		if ($codigoGrupo === "") {
			return array(
				"ok" => true,
				"tiene_categoria" => false,
				"etiqueta" => "Sin categoría / pendiente",
				"categoria" => null,
				"asignacion" => null,
				"requisitos" => array(),
				"beneficios" => null
			);
		}

		$asignacion = ModeloCategoriasClientes::mdlAsignacionVigente("grupo", $codigoGrupo);
		if (!$asignacion) {
			return array(
				"ok" => true,
				"tiene_categoria" => false,
				"etiqueta" => "Sin categoría / pendiente",
				"codigo_grupo" => $codigoGrupo,
				"categoria" => null,
				"asignacion" => null,
				"requisitos" => array(),
				"beneficios" => null
			);
		}

		$idCategoria = (int) $asignacion["id_categoria"];

		return array(
			"ok" => true,
			"tiene_categoria" => true,
			"etiqueta" => $asignacion["categoria_nombre"],
			"codigo_grupo" => $codigoGrupo,
			"categoria" => array(
				"id" => $idCategoria,
				"codigo" => $asignacion["categoria_codigo"],
				"nombre" => $asignacion["categoria_nombre"],
				"descripcion" => $asignacion["categoria_descripcion"],
				"estado" => (int) $asignacion["categoria_estado"],
				"color" => self::ctrResolverColorCategoria(
					isset($asignacion["categoria_color"]) ? $asignacion["categoria_color"] : "",
					$asignacion["categoria_codigo"]
				)
			),
			"asignacion" => array(
				"origen" => $asignacion["origen"],
				"cumplimiento" => $asignacion["cumplimiento"],
				"es_excepcion" => (int) $asignacion["es_excepcion"],
				"motivo" => $asignacion["motivo"]
			),
			"requisitos" => ModeloCategoriasClientes::mdlRequisitosPorCategoria($idCategoria),
			"beneficios" => ModeloCategoriasClientes::mdlBeneficiosPorCategoria($idCategoria)
		);
	}

	/*=============================================
	Bandeja de revisión
	=============================================*/
	static public function ctrListarBandejaRevision()
	{

		$filas = ModeloCategoriasClientes::mdlListarBandejaRevision();
		if (!is_array($filas) || count($filas) === 0) {
			return array();
		}

		$codigosCliente = array();
		$codigosGrupo = array();
		$idsCategoria = array();

		foreach ($filas as $fila) {
			if ($fila["tipo_entidad"] === "grupo") {
				$codigosGrupo[] = $fila["codigo_entidad"];
			} else {
				$codigosCliente[] = $fila["codigo_entidad"];
			}
			if (!empty($fila["id_categoria"])) {
				$idsCategoria[] = (int) $fila["id_categoria"];
			}
		}

		$montosCliente = ModeloCategoriasClientes::mdlMontoFacturado12mClientes(array_values(array_unique($codigosCliente)));
		$montosGrupo = ModeloCategoriasClientes::mdlMontoFacturado12mGrupos(array_values(array_unique($codigosGrupo)));
		$requisitos = ModeloCategoriasClientes::mdlRequisitosMontoPorCategorias(array_values(array_unique($idsCategoria)));

		foreach ($filas as $i => $fila) {
			$monto = 0;
			if ($fila["tipo_entidad"] === "grupo") {
				$monto = isset($montosGrupo[$fila["codigo_entidad"]])
					? $montosGrupo[$fila["codigo_entidad"]]
					: 0;
			} else {
				$monto = isset($montosCliente[$fila["codigo_entidad"]])
					? $montosCliente[$fila["codigo_entidad"]]
					: 0;
			}

			$idCat = !empty($fila["id_categoria"]) ? (int) $fila["id_categoria"] : 0;
			$requisito = ($idCat > 0 && array_key_exists($idCat, $requisitos))
				? $requisitos[$idCat]
				: null;

			$indicativo = "sin_umbral";
			if ($requisito !== null && $requisito !== "" && is_numeric($requisito)) {
				$indicativo = ($monto >= (float) $requisito) ? "alcanza" : "no_alcanza";
			}

			$filas[$i]["monto_12m"] = $monto;
			$filas[$i]["requisito_monto"] = $requisito;
			$filas[$i]["indicativo_requisito"] = $indicativo;
		}

		return $filas;
	}

	static public function ctrResumenBandejaRevision()
	{

		return array(
			"clientes_sin_categoria" => ModeloCategoriasClientes::mdlContarClientesSinCategoria(),
			"grupos_sin_categoria" => ModeloCategoriasClientes::mdlContarGruposSinCategoria(),
			"por_categoria" => ModeloCategoriasClientes::mdlResumenClientesGruposPorCategoriasBandeja()
		);
	}

	static public function ctrActualizarRevisionAjax($payload)
	{

		$idAsignacion = isset($payload["id_asignacion"]) ? (int) $payload["id_asignacion"] : 0;
		$cumplimiento = isset($payload["cumplimiento"]) ? trim($payload["cumplimiento"]) : "";
		$motivo = isset($payload["motivo"]) ? trim($payload["motivo"]) : "";
		$esExcepcion = isset($payload["es_excepcion"]) ? (int) $payload["es_excepcion"] : 0;
		$vigenciaHasta = isset($payload["vigencia_hasta"]) ? trim($payload["vigencia_hasta"]) : "";

		$cumplimientosOk = array("pendiente", "cumple", "no_cumple", "por_revisar");
		if (!in_array($cumplimiento, $cumplimientosOk)) {
			return array("ok" => false, "mensaje" => "Estado de cumplimiento inválido");
		}

		if ($idAsignacion <= 0) {
			return array("ok" => false, "mensaje" => "Asignación no válida");
		}

		$asignacion = ModeloCategoriasClientes::mdlAsignacionPorId($idAsignacion);
		if (!$asignacion || (int) $asignacion["estado"] !== 1) {
			return array("ok" => false, "mensaje" => "La asignación no existe o no está vigente");
		}

		if ($esExcepcion === 1 && $motivo === "") {
			return array("ok" => false, "mensaje" => "Debe indicar un motivo para la excepción");
		}

		if ($esExcepcion === 1 && $vigenciaHasta === "") {
			return array("ok" => false, "mensaje" => "Debe indicar la fecha de vencimiento de la excepción");
		}

		$origen = ($esExcepcion === 1) ? "excepcion" : (isset($asignacion["origen"]) ? $asignacion["origen"] : "manual");
		if ($esExcepcion !== 1 && $origen === "excepcion") {
			$origen = "manual";
		}

		$ok = ModeloCategoriasClientes::mdlActualizarRevisionAsignacion(array(
			"id" => $idAsignacion,
			"cumplimiento" => $cumplimiento,
			"origen" => $origen,
			"motivo" => ($motivo !== "" ? $motivo : null),
			"es_excepcion" => ($esExcepcion === 1 ? 1 : 0),
			"vigencia_hasta" => ($esExcepcion === 1 && $vigenciaHasta !== "")
				? ($vigenciaHasta . (strlen($vigenciaHasta) === 10 ? " 23:59:59" : ""))
				: null,
			"usumod" => self::ctrUsuarioActual(),
			"fecmod" => self::ctrFechaActual()
		));

		if (!$ok) {
			return array("ok" => false, "mensaje" => "No se pudo actualizar la revisión");
		}

		return array("ok" => true, "mensaje" => "Revisión actualizada correctamente");
	}

	static public function ctrResolverBandejaAjax($payload)
	{

		$tipoEntidad = isset($payload["tipo_entidad"]) ? trim($payload["tipo_entidad"]) : "";
		$codigoEntidad = isset($payload["codigo_entidad"]) ? trim($payload["codigo_entidad"]) : "";
		$idCategoria = isset($payload["id_categoria"]) ? (int) $payload["id_categoria"] : 0;
		$cumplimiento = isset($payload["cumplimiento"]) ? trim($payload["cumplimiento"]) : "pendiente";
		$motivo = isset($payload["motivo"]) ? trim($payload["motivo"]) : "";
		$esExcepcion = isset($payload["es_excepcion"]) ? (int) $payload["es_excepcion"] : 0;
		$vigenciaHasta = isset($payload["vigencia_hasta"]) ? trim($payload["vigencia_hasta"]) : "";
		$idAsignacion = isset($payload["id_asignacion"]) ? (int) $payload["id_asignacion"] : 0;

		// Quitar categoría
		if ($idCategoria <= 0) {
			return self::ctrAsignarCategoriaEntidad(array(
				"tipo_entidad" => $tipoEntidad,
				"codigo_entidad" => $codigoEntidad,
				"id_categoria" => 0,
				"motivo" => $motivo,
				"es_excepcion" => 0,
				"cumplimiento" => "pendiente",
				"vigencia_hasta" => ""
			));
		}

		// Misma asignación vigente: solo actualizar evaluación / excepción
		if ($idAsignacion > 0) {
			$asignacion = ModeloCategoriasClientes::mdlAsignacionPorId($idAsignacion);
			if ($asignacion && (int) $asignacion["id_categoria"] === $idCategoria) {
				return self::ctrActualizarRevisionAjax(array(
					"id_asignacion" => $idAsignacion,
					"cumplimiento" => $cumplimiento,
					"motivo" => $motivo,
					"es_excepcion" => $esExcepcion,
					"vigencia_hasta" => $vigenciaHasta
				));
			}
		}

		return self::ctrAsignarCategoriaEntidad(array(
			"tipo_entidad" => $tipoEntidad,
			"codigo_entidad" => $codigoEntidad,
			"id_categoria" => $idCategoria,
			"motivo" => $motivo,
			"es_excepcion" => $esExcepcion,
			"cumplimiento" => $cumplimiento,
			"vigencia_hasta" => $vigenciaHasta
		));
	}
}
