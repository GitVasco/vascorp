<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../controladores/series-documentos.controlador.php";
require_once "../modelos/series-documentos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "series_documentos")) {
	echo json_encode(array("data" => array()));
	return;
}

$series = ControladorSeriesDocumentos::ctrListarSeries();
$data = array();

if (is_array($series)) {
	foreach ($series as $s) {
		$tipo = isset($s["tipo_documento"]) ? (string) $s["tipo_documento"] : "";
		$serie = isset($s["serie"]) ? (string) $s["serie"] : "";
		$corr = isset($s["correlativo"]) ? (int) $s["correlativo"] : 0;
		$proximo = $corr + 1;
		$pad = ($tipo === "09") ? 7 : 8;
		$marcasTexto = isset($s["marcas_texto"]) && $s["marcas_texto"] !== null
			? trim((string) $s["marcas_texto"])
			: "";
		$marcas = array();
		if ($marcasTexto !== "") {
			foreach (explode(",", $marcasTexto) as $nombre) {
				$nombre = trim($nombre);
				if ($nombre !== "") {
					$marcas[] = $nombre;
				}
			}
		}

		$data[] = array(
			"id_talonario" => (int) $s["id_talonario"],
			"tipo_documento" => $tipo,
			"tipo_etiqueta" => isset($s["tipo_etiqueta"]) ? (string) $s["tipo_etiqueta"] : $tipo,
			"serie" => $serie,
			"correlativo" => $corr,
			"proximo" => $serie . "-" . str_pad((string) $proximo, $pad, "0", STR_PAD_LEFT),
			"marcas" => $marcas,
			"total_marcas" => isset($s["total_marcas"]) ? (int) $s["total_marcas"] : count($marcas)
		);
	}
}

echo json_encode(array("data" => $data));
