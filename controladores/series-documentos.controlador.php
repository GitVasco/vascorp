<?php

class ControladorSeriesDocumentos
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
			&& usuarioPuedeVerModulo("gestion_comercial", "series_documentos");
	}

	static private function ctrPuedeEditar()
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "series_documentos", "editar");
	}

	static public function ctrMapaTipos()
	{
		return ModeloSeriesDocumentos::mdlMapaTipos();
	}

	static public function ctrListarSeries()
	{
		return ModeloSeriesDocumentos::mdlListarSeries();
	}

	static public function ctrDetalleSerie($idTalonario, $tipoDocumento)
	{
		$idTalonario = (int) $idTalonario;
		$tipoDocumento = trim((string) $tipoDocumento);
		if ($idTalonario < 1 || $tipoDocumento === "") {
			return null;
		}
		$detalle = ModeloSeriesDocumentos::mdlDetalleSerie($idTalonario, $tipoDocumento);
		if (!$detalle) {
			return null;
		}
		$detalle["marcas"] = ModeloSeriesDocumentos::mdlListarMarcasSerie($idTalonario, $tipoDocumento);
		return $detalle;
	}

	static private function ctrNormalizarSerie($serie)
	{
		return strtoupper(trim((string) $serie));
	}

	static public function ctrCrearSerieAjax($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar series");
		}

		$tipo = trim(isset($post["tipo_documento"]) ? $post["tipo_documento"] : "");
		$serie = self::ctrNormalizarSerie(isset($post["serie"]) ? $post["serie"] : "");
		$correlativo = isset($post["correlativo"]) ? (int) $post["correlativo"] : 0;
		$mapa = ModeloSeriesDocumentos::mdlMapaTipos();

		if (!isset($mapa[$tipo])) {
			return array("ok" => false, "mensaje" => "Tipo de documento inválido");
		}
		if ($serie === "" || !preg_match('/^[A-Z0-9]{1,4}$/', $serie)) {
			return array("ok" => false, "mensaje" => "Serie inválida (máx. 4, alfanumérica)");
		}
		if ($correlativo < 0) {
			return array("ok" => false, "mensaje" => "El correlativo no puede ser negativo");
		}
		if (ModeloSeriesDocumentos::mdlExisteSerieTipo($tipo, $serie)) {
			return array("ok" => false, "mensaje" => "Ya existe esa serie para el tipo de documento");
		}

		$id = ModeloSeriesDocumentos::mdlCrearSerieRetornandoId($tipo, $serie, $correlativo);
		if ($id < 1) {
			return array("ok" => false, "mensaje" => "No se pudo crear la serie");
		}

		return array("ok" => true, "mensaje" => "Serie creada", "id_talonario" => $id);
	}

	static public function ctrEditarSerieAjax($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar series");
		}

		$id = isset($post["id_talonario"]) ? (int) $post["id_talonario"] : 0;
		$tipo = trim(isset($post["tipo_documento"]) ? $post["tipo_documento"] : "");
		$serie = self::ctrNormalizarSerie(isset($post["serie"]) ? $post["serie"] : "");
		$correlativo = isset($post["correlativo"]) ? (int) $post["correlativo"] : 0;
		$mapa = ModeloSeriesDocumentos::mdlMapaTipos();

		if ($id < 1 || !isset($mapa[$tipo])) {
			return array("ok" => false, "mensaje" => "Serie no válida");
		}
		if ($serie === "" || !preg_match('/^[A-Z0-9]{1,4}$/', $serie)) {
			return array("ok" => false, "mensaje" => "Serie inválida (máx. 4, alfanumérica)");
		}
		if ($correlativo < 0) {
			return array("ok" => false, "mensaje" => "El correlativo no puede ser negativo");
		}
		if (!ModeloSeriesDocumentos::mdlDetalleSerie($id, $tipo)) {
			return array("ok" => false, "mensaje" => "No se encontró la serie");
		}
		if (ModeloSeriesDocumentos::mdlExisteSerieTipo($tipo, $serie, $id)) {
			return array("ok" => false, "mensaje" => "Ya existe esa serie para el tipo de documento");
		}

		if (ModeloSeriesDocumentos::mdlEditarSerie($id, $tipo, $serie, $correlativo) !== "ok") {
			return array("ok" => false, "mensaje" => "No se pudo actualizar la numeración");
		}

		return array("ok" => true, "mensaje" => "Numeración actualizada");
	}

	static public function ctrMatrizAjax()
	{
		$series = ModeloSeriesDocumentos::mdlListarSeries();
		$marcas = ModeloSeriesDocumentos::mdlListarMarcasCatalogo();
		$vinculos = ModeloSeriesDocumentos::mdlListarVinculosMarcas();

		$mapa = array();
		if (is_array($vinculos)) {
			foreach ($vinculos as $v) {
				$key = (int) $v["id_talonario"] . "|" . $v["tipo_documento"] . "|" . (int) $v["id_marca"];
				$mapa[$key] = true;
			}
		}

		$seriesOut = array();
		if (is_array($series)) {
			foreach ($series as $s) {
				$tipo = isset($s["tipo_documento"]) ? (string) $s["tipo_documento"] : "";
				$serie = isset($s["serie"]) ? (string) $s["serie"] : "";
				$corr = isset($s["correlativo"]) ? (int) $s["correlativo"] : 0;
				$pad = ($tipo === "09") ? 7 : 8;
				$seriesOut[] = array(
					"id_talonario" => (int) $s["id_talonario"],
					"tipo_documento" => $tipo,
					"tipo_etiqueta" => isset($s["tipo_etiqueta"]) ? (string) $s["tipo_etiqueta"] : $tipo,
					"serie" => $serie,
					"correlativo" => $corr,
					"proximo" => $serie . "-" . str_pad((string) ($corr + 1), $pad, "0", STR_PAD_LEFT),
					"total_marcas" => isset($s["total_marcas"]) ? (int) $s["total_marcas"] : 0
				);
			}
		}

		$marcasOut = array();
		if (is_array($marcas)) {
			foreach ($marcas as $m) {
				$marcasOut[] = array(
					"id" => (int) $m["id"],
					"marca" => isset($m["marca"]) ? (string) $m["marca"] : ""
				);
			}
		}

		return array(
			"ok" => true,
			"series" => $seriesOut,
			"marcas" => $marcasOut,
			"vinculos" => $mapa
		);
	}

	static public function ctrToggleMarcaAjax($post)
	{
		if (!self::ctrPuedeEditar()) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}

		$id = isset($post["id_talonario"]) ? (int) $post["id_talonario"] : 0;
		$tipo = trim(isset($post["tipo_documento"]) ? $post["tipo_documento"] : "");
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$activo = !empty($post["activo"]) && (string) $post["activo"] !== "0";

		if ($id < 1 || $idMarca < 1 || !isset(ModeloSeriesDocumentos::mdlMapaTipos()[$tipo])) {
			return array("ok" => false, "mensaje" => "Datos inválidos");
		}
		if (!ModeloSeriesDocumentos::mdlDetalleSerie($id, $tipo)) {
			return array("ok" => false, "mensaje" => "Serie no encontrada");
		}

		if (ModeloSeriesDocumentos::mdlToggleMarcaSerie($id, $tipo, $idMarca, $activo, self::ctrUsuarioSesion()) !== "ok") {
			return array("ok" => false, "mensaje" => "No se pudo actualizar el amarre");
		}

		return array("ok" => true, "mensaje" => $activo ? "Marca amarrada" : "Marca desamarrada");
	}

	static public function ctrListarMarcasCatalogo()
	{
		return ModeloSeriesDocumentos::mdlListarMarcasCatalogo();
	}
}
