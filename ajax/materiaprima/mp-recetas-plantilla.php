<?php

@ini_set("display_errors", "0");
error_reporting(0);

if (!isset($_SESSION)) {
	session_start();
}

require_once "mp-recetas.lib.php";

if (!isset($_SESSION["materiaprima"]) || (int) $_SESSION["materiaprima"] !== 1) {
	http_response_code(403);
	exit("Sin permiso");
}

$linea = isset($_GET["linea"]) ? $_GET["linea"] : "";
$sublinea = isset($_GET["sublinea"]) ? $_GET["sublinea"] : "";
$mp = isset($_GET["mp"]) ? $_GET["mp"] : "";
$costo = isset($_GET["costo"]) ? $_GET["costo"] : "";

$data = mpRecetasListarData();
if ($data === false) {
	http_response_code(500);
	exit("No se pudo armar la plantilla");
}
$filas = mpRecetasFiltrarData($data, $linea, $sublinea, $mp, $costo);

while (ob_get_level() > 0) {
	ob_end_clean();
}

header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="plantilla-costos-mp-recetas.csv"');
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: public");

$salida = fopen("php://output", "wb");
fwrite($salida, "\xEF\xBB\xBF");
fputcsv($salida, array("codigo", "fabrica", "descripcion", "color", "unidad", "costo"));

foreach ($filas as $r) {
	$codigo = (string) $r["codpro"];
	fputcsv($salida, array(
		'="' . str_replace('"', "", $codigo) . '"',
		(string) $r["codfab"],
		(string) $r["despro"],
		(string) $r["color"],
		(string) $r["unidad"],
		number_format((float) $r["costo"], 4, ".", "")
	));
}

fclose($salida);
exit;
