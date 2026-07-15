<?php

class ControladorGruposMarcasComercial
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

	static private function ctrPuedeVerGrupos()
	{
		return function_exists("usuarioPuedeVerModulo")
			&& usuarioPuedeVerModulo("gestion_comercial", "grupos_marcas");
	}

	static private function ctrPuedeEditarGrupos()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "grupos_marcas", "editar");
	}

	static private function ctrPuedeVerAsignacion()
	{
		return function_exists("usuarioPuedeVerModulo")
			&& usuarioPuedeVerModulo("gestion_comercial", "asignacion_grupos_marcas");
	}

	static private function ctrPuedeEditarAsignacion()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "asignacion_grupos_marcas", "editar");
	}

	static public function ctrListarGrupos($soloActivas = false)
	{
		return ModeloGruposMarcasComercial::mdlListarGrupos($soloActivas);
	}

	static public function ctrDetalleGrupo($id)
	{
		$id = (int) $id;
		if ($id < 1) {
			return null;
		}
		return ModeloGruposMarcasComercial::mdlMostrarGrupo($id);
	}

	static public function ctrCrearGrupoAjax($post)
	{
		if (!self::ctrPuedeEditarGrupos()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar grupos de marcas");
		}

		$codigo = strtoupper(trim(isset($post["codigo"]) ? $post["codigo"] : ""));
		$nombre = trim(isset($post["nombre"]) ? $post["nombre"] : "");
		$descripcion = trim(isset($post["descripcion"]) ? $post["descripcion"] : "");
		$estado = isset($post["estado"]) ? (int) $post["estado"] : 1;

		if ($codigo === "" || $nombre === "") {
			return array("ok" => false, "mensaje" => "Código y nombre son obligatorios");
		}
		if (!preg_match('/^[A-Z0-9_\-]{1,30}$/', $codigo)) {
			return array("ok" => false, "mensaje" => "Código inválido (máx. 30, alfanumérico)");
		}
		if (ModeloGruposMarcasComercial::mdlGrupoPorCodigo($codigo)) {
			return array("ok" => false, "mensaje" => "Ya existe un grupo con ese código");
		}

		$datos = array(
			"codigo" => $codigo,
			"nombre" => $nombre,
			"descripcion" => $descripcion !== "" ? $descripcion : null,
			"estado" => $estado === 0 ? 0 : 1,
			"usureg" => self::ctrUsuarioSesion()
		);

		if (ModeloGruposMarcasComercial::mdlCrearGrupo($datos) === "ok") {
			return array("ok" => true, "mensaje" => "Grupo creado");
		}
		return array("ok" => false, "mensaje" => "No se pudo crear el grupo");
	}

	static public function ctrEditarGrupoAjax($post)
	{
		if (!self::ctrPuedeEditarGrupos()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar grupos de marcas");
		}

		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		$nombre = trim(isset($post["nombre"]) ? $post["nombre"] : "");
		$descripcion = trim(isset($post["descripcion"]) ? $post["descripcion"] : "");
		$estado = isset($post["estado"]) ? (int) $post["estado"] : 1;

		if ($id < 1 || $nombre === "") {
			return array("ok" => false, "mensaje" => "Datos incompletos");
		}
		if (!ModeloGruposMarcasComercial::mdlMostrarGrupo($id)) {
			return array("ok" => false, "mensaje" => "Grupo no encontrado");
		}

		$datos = array(
			"id" => $id,
			"nombre" => $nombre,
			"descripcion" => $descripcion !== "" ? $descripcion : null,
			"estado" => $estado === 0 ? 0 : 1,
			"usumod" => self::ctrUsuarioSesion()
		);

		if (ModeloGruposMarcasComercial::mdlEditarGrupo($datos) === "ok") {
			return array("ok" => true, "mensaje" => "Grupo actualizado");
		}
		return array("ok" => false, "mensaje" => "No se pudo actualizar");
	}

	static public function ctrCambiarEstadoGrupoAjax($id, $estado)
	{
		if (!self::ctrPuedeEditarGrupos()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$id = (int) $id;
		$estado = (int) $estado === 1 ? 1 : 0;
		if ($id < 1) {
			return array("ok" => false, "mensaje" => "Id inválido");
		}
		if (ModeloGruposMarcasComercial::mdlCambiarEstadoGrupo($id, $estado, self::ctrUsuarioSesion()) === "ok") {
			return array("ok" => true, "mensaje" => "Estado actualizado");
		}
		return array("ok" => false, "mensaje" => "No se pudo cambiar el estado");
	}

	static public function ctrListarMarcasGrupo($idGrupo)
	{
		return ModeloGruposMarcasComercial::mdlListarMarcasGrupo((int) $idGrupo);
	}

	static public function ctrListarMarcasCatalogo()
	{
		return ModeloGruposMarcasComercial::mdlListarMarcasCatalogo();
	}

	static public function ctrAgregarMarcaGrupoAjax($idGrupo, $idMarca)
	{
		if (!self::ctrPuedeEditarGrupos()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idGrupo = (int) $idGrupo;
		$idMarca = (int) $idMarca;
		if ($idGrupo < 1 || $idMarca < 1) {
			return array("ok" => false, "mensaje" => "Datos inválidos");
		}
		if (!ModeloGruposMarcasComercial::mdlMostrarGrupo($idGrupo)) {
			return array("ok" => false, "mensaje" => "Grupo no encontrado");
		}
		if (!ModeloGruposMarcasComercial::mdlMarcaExiste($idMarca)) {
			return array("ok" => false, "mensaje" => "Marca no encontrada");
		}
		if (ModeloGruposMarcasComercial::mdlAgregarMarcaGrupo($idGrupo, $idMarca, self::ctrUsuarioSesion()) === "ok") {
			return array("ok" => true, "mensaje" => "Marca agregada al grupo");
		}
		return array("ok" => false, "mensaje" => "No se pudo agregar (¿ya estaba en el grupo?)");
	}

	static public function ctrQuitarMarcaGrupoAjax($idDetalle)
	{
		if (!self::ctrPuedeEditarGrupos()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idDetalle = (int) $idDetalle;
		if ($idDetalle < 1) {
			return array("ok" => false, "mensaje" => "Id inválido");
		}
		if (ModeloGruposMarcasComercial::mdlQuitarMarcaGrupo($idDetalle) === "ok") {
			return array("ok" => true, "mensaje" => "Marca quitada del grupo");
		}
		return array("ok" => false, "mensaje" => "No se pudo quitar");
	}

	static public function ctrListarVendedoresTvend()
	{
		return ModeloGruposMarcasComercial::mdlListarVendedoresTvend();
	}

	static public function ctrListarAsignaciones($filtros = array())
	{
		if (!self::ctrPuedeVerAsignacion()) {
			return array();
		}
		return ModeloGruposMarcasComercial::mdlListarAsignaciones($filtros);
	}

	static public function ctrCrearAsignacionesAjax($post)
	{
		if (!self::ctrPuedeEditarAsignacion()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar asignaciones");
		}

		$codVendedor = trim(isset($post["cod_vendedor"]) ? $post["cod_vendedor"] : "");
		$fechaInicio = trim(isset($post["fecha_inicio"]) ? $post["fecha_inicio"] : "");
		$observacion = trim(isset($post["observacion"]) ? $post["observacion"] : "");
		$idsGrupos = array();

		if (isset($post["ids_grupos"]) && is_array($post["ids_grupos"])) {
			$idsGrupos = $post["ids_grupos"];
		} elseif (isset($post["ids_grupos"]) && is_string($post["ids_grupos"])) {
			$idsGrupos = array_filter(array_map("trim", explode(",", $post["ids_grupos"])));
		}

		if ($codVendedor === "" || $fechaInicio === "" || !count($idsGrupos)) {
			return array("ok" => false, "mensaje" => "Vendedor, fecha de inicio y al menos un grupo son obligatorios");
		}
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
			return array("ok" => false, "mensaje" => "Fecha de inicio inválida");
		}

		return ModeloGruposMarcasComercial::mdlCrearAsignacionesLote(
			$codVendedor,
			$idsGrupos,
			$fechaInicio,
			$observacion,
			self::ctrUsuarioSesion()
		);
	}

	static public function ctrCerrarAsignacionAjax($id, $fechaFin)
	{
		if (!self::ctrPuedeEditarAsignacion()) {
			return array("ok" => false, "mensaje" => "Sin permiso para cerrar asignaciones");
		}
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) $fechaFin))) {
			return array("ok" => false, "mensaje" => "Fecha de fin inválida");
		}
		return ModeloGruposMarcasComercial::mdlCerrarAsignacion((int) $id, $fechaFin, self::ctrUsuarioSesion());
	}

	static public function ctrMarcasVigentesPorVendedor($codVendedor, $fechaRef = null)
	{
		return ModeloGruposMarcasComercial::mdlMarcasVigentesPorVendedor($codVendedor, $fechaRef);
	}

	static public function ctrUniversoModelosActivos($codVendedor, $fechaRef = null)
	{
		return ModeloGruposMarcasComercial::mdlUniversoModelosActivosPorVendedor($codVendedor, $fechaRef);
	}

	static public function ctrVerificarCoberturaModelo($codVendedor, $modelo, $fechaRef = null)
	{
		return ModeloGruposMarcasComercial::mdlVerificarCoberturaModelo($codVendedor, $modelo, $fechaRef);
	}

	static public function ctrVerificarCoberturaArticulo($codVendedor, $articulo, $fechaRef = null)
	{
		return ModeloGruposMarcasComercial::mdlVerificarCoberturaArticulo($codVendedor, $articulo, $fechaRef);
	}
}
