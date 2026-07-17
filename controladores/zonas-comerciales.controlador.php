<?php

class ControladorZonasComerciales
{

	static private function ctrUsuarioSesion()
	{
		if (isset($_SESSION["usuario"]) && $_SESSION["usuario"] !== "") {
			return (string) $_SESSION["usuario"];
		}
		if (isset($_SESSION["id"])) {
			return (string) $_SESSION["id"];
		}
		return "sistema";
	}

	static private function ctrPuedeVer()
	{
		return function_exists("usuarioPuedeVerModulo")
			&& usuarioPuedeVerModulo("gestion_comercial", "zonas_comerciales");
	}

	static private function ctrPuedeEditar()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "zonas_comerciales", "editar");
	}

	/** Público: ¿puede editar override de zona en cliente/grupo? (IDs en JSON) */
	static public function ctrPuedeEditarZonaAsignacion()
	{
		return self::ctrPuedeEditar();
	}

	/**
	 * Lee id_zona del POST si tiene permiso; si no, mantiene valor actual (edición)
	 * o NULL (alta).
	 */
	static public function ctrIdZonaDesdePost($postKey, $idActual = null)
	{
		if (!self::ctrPuedeEditar()) {
			if ($idActual === null || $idActual === "" || (int) $idActual < 1) {
				return null;
			}
			return (int) $idActual;
		}

		if (!isset($_POST[$postKey])) {
			return null;
		}

		$valor = trim((string) $_POST[$postKey]);
		if ($valor === "" || $valor === "0") {
			return null;
		}

		return (int) $valor;
	}

	static public function ctrResolverZonaCliente($codigoCliente)
	{
		return ModeloZonasComerciales::mdlResolverZonaCliente($codigoCliente);
	}

	static public function ctrClientesZonaPorRevisar($limite = 200)
	{
		return ModeloZonasComerciales::mdlClientesZonaPorRevisar($limite);
	}

	static public function ctrEtiquetaOrigenZona($origen)
	{
		$mapa = array(
			"cliente" => "Override cliente",
			"grupo" => "Heredada del grupo",
			"ubigeo" => "Automática por ubigeo",
			"sin_zona" => "Sin zona"
		);
		$origen = trim((string) $origen);
		return isset($mapa[$origen]) ? $mapa[$origen] : $origen;
	}

	static private function ctrMacrozonasValidas()
	{
		return array("lima", "peru_norte", "peru_sur");
	}

	static public function ctrEtiquetaMacrozona($codigo)
	{
		$mapa = array(
			"lima" => "Lima y alrededores",
			"peru_norte" => "Norte del Perú",
			"peru_sur" => "Sur del Perú"
		);
		$codigo = trim((string) $codigo);
		return isset($mapa[$codigo]) ? $mapa[$codigo] : $codigo;
	}

	static public function ctrListarZonas($soloActivas = false)
	{
		return ModeloZonasComerciales::mdlListarZonas($soloActivas);
	}

	static public function ctrDetalleZona($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		return ModeloZonasComerciales::mdlMostrarZona("id", $id);
	}

	static public function ctrCrearZonaAjax($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar zonas");
		}

		$codigo = strtoupper(trim(isset($post["codigo"]) ? $post["codigo"] : ""));
		$nombre = trim(isset($post["nombre"]) ? $post["nombre"] : "");
		$macrozona = trim(isset($post["macrozona"]) ? $post["macrozona"] : "lima");
		$descripcion = trim(isset($post["descripcion"]) ? $post["descripcion"] : "");
		$color = trim(isset($post["color"]) ? $post["color"] : "#3c8dbc");
		$orden = isset($post["orden"]) ? (int) $post["orden"] : 0;
		$estado = isset($post["estado"]) ? (int) $post["estado"] : 1;

		if ($codigo === "" || $nombre === "") {
			return array("ok" => false, "mensaje" => "Código y nombre son obligatorios");
		}
		if (!in_array($macrozona, self::ctrMacrozonasValidas(), true)) {
			return array("ok" => false, "mensaje" => "Macrozona no válida");
		}
		if (ModeloZonasComerciales::mdlMostrarZona("codigo", $codigo)) {
			return array("ok" => false, "mensaje" => "Ya existe una zona con ese código");
		}

		$ok = ModeloZonasComerciales::mdlCrearZona(array(
			"codigo" => $codigo,
			"nombre" => $nombre,
			"macrozona" => $macrozona,
			"descripcion" => $descripcion,
			"color" => $color !== "" ? $color : "#3c8dbc",
			"orden" => $orden,
			"estado" => $estado === 0 ? 0 : 1,
			"usureg" => self::ctrUsuarioSesion(),
			"fecreg" => date("Y-m-d H:i:s")
		));

		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Zona creada")
			: array("ok" => false, "mensaje" => "No se pudo crear la zona");
	}

	static public function ctrEditarZonaAjax($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar zonas");
		}

		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$zona = self::ctrDetalleZona($id);
		if (!$zona) {
			return array("ok" => false, "mensaje" => "Zona no encontrada");
		}

		$nombre = trim(isset($post["nombre"]) ? $post["nombre"] : "");
		$macrozona = trim(isset($post["macrozona"]) ? $post["macrozona"] : "lima");
		$descripcion = trim(isset($post["descripcion"]) ? $post["descripcion"] : "");
		$color = trim(isset($post["color"]) ? $post["color"] : "#3c8dbc");
		$orden = isset($post["orden"]) ? (int) $post["orden"] : 0;
		$estado = isset($post["estado"]) ? (int) $post["estado"] : 1;

		if ($nombre === "") {
			return array("ok" => false, "mensaje" => "El nombre es obligatorio");
		}
		if (!in_array($macrozona, self::ctrMacrozonasValidas(), true)) {
			return array("ok" => false, "mensaje" => "Macrozona no válida");
		}

		$ok = ModeloZonasComerciales::mdlEditarZona(array(
			"id" => $id,
			"nombre" => $nombre,
			"macrozona" => $macrozona,
			"descripcion" => $descripcion,
			"color" => $color !== "" ? $color : "#3c8dbc",
			"orden" => $orden,
			"estado" => $estado === 0 ? 0 : 1,
			"usumod" => self::ctrUsuarioSesion(),
			"fecmod" => date("Y-m-d H:i:s")
		));

		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Zona actualizada")
			: array("ok" => false, "mensaje" => "No se pudo actualizar la zona");
	}

	static public function ctrCambiarEstadoAjax($id, $estado)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar zonas");
		}

		$id = (int) $id;
		$estado = ((int) $estado === 1) ? 1 : 0;
		if ($id < 1) {
			return array("ok" => false, "mensaje" => "Zona inválida");
		}

		$ok = ModeloZonasComerciales::mdlCambiarEstadoZona($id, $estado, self::ctrUsuarioSesion());

		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Estado actualizado")
			: array("ok" => false, "mensaje" => "No se pudo cambiar el estado");
	}

	static public function ctrListarUbigeosZona($idZona)
	{
		return ModeloZonasComerciales::mdlListarUbigeosZona((int) $idZona);
	}

	static public function ctrBuscarUbigeos($termino)
	{
		return ModeloZonasComerciales::mdlBuscarUbigeosDisponibles($termino);
	}

	static public function ctrAsignarUbigeoAjax($idZona, $codUbi)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar zonas");
		}

		$idZona = (int) $idZona;
		$codUbi = trim((string) $codUbi);
		if ($idZona < 1 || $codUbi === "") {
			return array("ok" => false, "mensaje" => "Datos incompletos");
		}
		if (!self::ctrDetalleZona($idZona)) {
			return array("ok" => false, "mensaje" => "Zona no encontrada");
		}

		$ok = ModeloZonasComerciales::mdlAsignarUbigeo($idZona, $codUbi, self::ctrUsuarioSesion());

		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Ubigeo asignado a la zona")
			: array("ok" => false, "mensaje" => "No se pudo asignar el ubigeo");
	}

	static public function ctrQuitarUbigeoAjax($idRegla)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar zonas");
		}

		$idRegla = (int) $idRegla;
		if ($idRegla < 1) {
			return array("ok" => false, "mensaje" => "Regla inválida");
		}

		$ok = ModeloZonasComerciales::mdlQuitarUbigeo($idRegla);

		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Ubigeo quitado de la zona")
			: array("ok" => false, "mensaje" => "No se pudo quitar el ubigeo");
	}

	static public function ctrListarVendedoresZona($idZona)
	{
		return ModeloZonasComerciales::mdlListarVendedoresZona((int) $idZona);
	}

	static public function ctrListarVendedoresDisponibles($idZona)
	{
		return ModeloZonasComerciales::mdlListarVendedoresDisponibles((int) $idZona);
	}

	static public function ctrAsignarVendedorAjax($idZona, $codVendedor)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar zonas");
		}

		$idZona = (int) $idZona;
		$codVendedor = trim((string) $codVendedor);
		if ($idZona < 1 || $codVendedor === "") {
			return array("ok" => false, "mensaje" => "Datos incompletos");
		}
		if (!self::ctrDetalleZona($idZona)) {
			return array("ok" => false, "mensaje" => "Zona no encontrada");
		}

		$ok = ModeloZonasComerciales::mdlAsignarVendedor($idZona, $codVendedor, self::ctrUsuarioSesion());

		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Vendedor asignado a la zona")
			: array("ok" => false, "mensaje" => "No se pudo asignar el vendedor");
	}

	static public function ctrQuitarVendedorAjax($idRegla)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar zonas");
		}

		$idRegla = (int) $idRegla;
		if ($idRegla < 1) {
			return array("ok" => false, "mensaje" => "Asignación inválida");
		}

		$ok = ModeloZonasComerciales::mdlQuitarVendedor($idRegla);

		return $ok === "ok"
			? array("ok" => true, "mensaje" => "Vendedor quitado de la zona")
			: array("ok" => false, "mensaje" => "No se pudo quitar el vendedor");
	}

	static public function ctrZonasPorVendedor($codVendedor)
	{
		return ModeloZonasComerciales::mdlZonasPorVendedor($codVendedor);
	}

	static public function ctrResumenMapa($vista = null, $anio = null, $mes = null, $idGrupoMarca = null, $filtroDistribuidor = "con")
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "zonas" => array());
		}

		$vista = $vista === null ? "" : trim((string) $vista);
		if (!in_array($vista, array("", "lima", "peru"), true)) {
			$vista = "";
		}

		date_default_timezone_set("America/Lima");
		$anio = $anio === null || $anio === "" ? (int) date("Y") : (int) $anio;
		$mes = $mes === null || $mes === "" ? (int) date("n") : (int) $mes;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}
		if ($mes < 1 || $mes > 12) {
			$mes = (int) date("n");
		}

		$idGrupoMarca = $idGrupoMarca === null || $idGrupoMarca === "" ? null : (int) $idGrupoMarca;
		if ($idGrupoMarca !== null && $idGrupoMarca < 1) {
			$idGrupoMarca = null;
		}
		$filtroDistribuidor = ModeloZonasComerciales::normalizarFiltroDistribuidor($filtroDistribuidor);

		$vistaHist = $vista === "" ? "lima" : $vista;

		return array(
			"ok" => true,
			"anio" => $anio,
			"mes" => $mes,
			"id_grupo_marca" => $idGrupoMarca === null ? 0 : $idGrupoMarca,
			"filtro_distribuidor" => $filtroDistribuidor,
			"geo_asignacion" => ModeloZonasComerciales::mdlMapaGeoAsignaciones(),
			"ventas_geo" => ModeloZonasComerciales::mdlVentasGeoPeriodo($anio, $mes, $idGrupoMarca, $filtroDistribuidor),
			"zonas" => ModeloZonasComerciales::mdlResumenMapaZonas(
				$vista === "" ? null : $vista,
				$anio,
				$mes,
				$idGrupoMarca,
				$filtroDistribuidor
			),
			"historico_12m" => ModeloZonasComerciales::mdlVentasTotalesVistaUltimos12Meses(
				$vistaHist,
				$anio,
				$mes,
				$idGrupoMarca,
				$filtroDistribuidor
			)
		);
	}

	static public function ctrClientesVentaZona($idZona, $anio = null, $mes = null, $idGrupoMarca = null, $filtroDistribuidor = "con")
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "clientes" => array());
		}

		$idZona = (int) $idZona;
		if ($idZona < 1) {
			return array("ok" => false, "mensaje" => "Zona inválida", "clientes" => array());
		}

		date_default_timezone_set("America/Lima");
		$anio = $anio === null || $anio === "" ? (int) date("Y") : (int) $anio;
		$mes = $mes === null || $mes === "" ? (int) date("n") : (int) $mes;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}
		if ($mes < 1 || $mes > 12) {
			$mes = (int) date("n");
		}

		$idGrupoMarca = $idGrupoMarca === null || $idGrupoMarca === "" ? null : (int) $idGrupoMarca;
		if ($idGrupoMarca !== null && $idGrupoMarca < 1) {
			$idGrupoMarca = null;
		}
		$filtroDistribuidor = ModeloZonasComerciales::normalizarFiltroDistribuidor($filtroDistribuidor);

		$zona = ModeloZonasComerciales::mdlZonaPorId($idZona);
		$clientes = ModeloZonasComerciales::mdlClientesVentaZonaPeriodo($idZona, $anio, $mes, 500, $idGrupoMarca, $filtroDistribuidor);
		$total = 0.0;
		$totalNuevos = 0;
		foreach ($clientes as $c) {
			$total += (float) $c["venta_real"];
			if (!empty($c["es_nuevo"])) {
				$totalNuevos++;
			}
		}

		return array(
			"ok" => true,
			"anio" => $anio,
			"mes" => $mes,
			"id_grupo_marca" => $idGrupoMarca === null ? 0 : $idGrupoMarca,
			"filtro_distribuidor" => $filtroDistribuidor,
			"zona" => $zona ? array(
				"id" => (int) $zona["id"],
				"codigo" => $zona["codigo"],
				"nombre" => $zona["nombre"],
				"color" => isset($zona["color"]) ? $zona["color"] : "#777"
			) : null,
			"total_venta" => round($total, 2),
			"total_clientes" => count($clientes),
			"total_nuevos" => $totalNuevos,
			"clientes" => $clientes
		);
	}

	static public function ctrClientesNuevosZona($idZona, $anio = null, $mes = null, $idGrupoMarca = null, $filtroDistribuidor = "con")
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "clientes" => array());
		}

		$idZona = (int) $idZona;
		if ($idZona < 1) {
			return array("ok" => false, "mensaje" => "Zona inválida", "clientes" => array());
		}

		date_default_timezone_set("America/Lima");
		$anio = $anio === null || $anio === "" ? (int) date("Y") : (int) $anio;
		$mes = $mes === null || $mes === "" ? (int) date("n") : (int) $mes;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}
		if ($mes < 1 || $mes > 12) {
			$mes = (int) date("n");
		}

		$idGrupoMarca = $idGrupoMarca === null || $idGrupoMarca === "" ? null : (int) $idGrupoMarca;
		if ($idGrupoMarca !== null && $idGrupoMarca < 1) {
			$idGrupoMarca = null;
		}
		$filtroDistribuidor = ModeloZonasComerciales::normalizarFiltroDistribuidor($filtroDistribuidor);

		$zona = ModeloZonasComerciales::mdlZonaPorId($idZona);
		$clientes = ModeloZonasComerciales::mdlClientesNuevosZonaPeriodo($idZona, $anio, $mes, 500, $idGrupoMarca, $filtroDistribuidor);
		$total = 0.0;
		foreach ($clientes as $c) {
			$total += (float) $c["venta_real"];
		}

		return array(
			"ok" => true,
			"anio" => $anio,
			"mes" => $mes,
			"id_grupo_marca" => $idGrupoMarca === null ? 0 : $idGrupoMarca,
			"filtro_distribuidor" => $filtroDistribuidor,
			"zona" => $zona ? array(
				"id" => (int) $zona["id"],
				"codigo" => $zona["codigo"],
				"nombre" => $zona["nombre"],
				"color" => isset($zona["color"]) ? $zona["color"] : "#777"
			) : null,
			"total_venta" => round($total, 2),
			"total_clientes" => count($clientes),
			"clientes" => $clientes
		);
	}

	static public function ctrClientesSinAtenderZona($idZona, $anio = null, $mes = null, $idGrupoMarca = null, $filtroDistribuidor = "con")
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso", "clientes" => array());
		}
		$idZona = (int) $idZona;
		if ($idZona < 1) {
			return array("ok" => false, "mensaje" => "Zona inválida", "clientes" => array());
		}
		date_default_timezone_set("America/Lima");
		$anio = $anio === null || $anio === "" ? (int) date("Y") : (int) $anio;
		$mes = $mes === null || $mes === "" ? (int) date("n") : (int) $mes;
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date("Y");
		}
		if ($mes < 1 || $mes > 12) {
			$mes = (int) date("n");
		}
		$idGrupoMarca = $idGrupoMarca === null || $idGrupoMarca === "" ? null : (int) $idGrupoMarca;
		if ($idGrupoMarca !== null && $idGrupoMarca < 1) {
			$idGrupoMarca = null;
		}
		$filtroDistribuidor = ModeloZonasComerciales::normalizarFiltroDistribuidor($filtroDistribuidor);
		$zona = ModeloZonasComerciales::mdlZonaPorId($idZona);
		$clientes = ModeloZonasComerciales::mdlClientesSinAtenderZonaPeriodo($idZona, $anio, $mes, 500, $idGrupoMarca, $filtroDistribuidor);
		return array(
			"ok" => true,
			"anio" => $anio,
			"mes" => $mes,
			"id_grupo_marca" => $idGrupoMarca === null ? 0 : $idGrupoMarca,
			"filtro_distribuidor" => $filtroDistribuidor,
			"zona" => $zona ? array(
				"id" => (int) $zona["id"],
				"codigo" => $zona["codigo"],
				"nombre" => $zona["nombre"],
				"color" => isset($zona["color"]) ? $zona["color"] : "#777"
			) : null,
			"total_clientes" => count($clientes),
			"clientes" => $clientes
		);
	}
}
