<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/modelo-color-taller.controlador.php";

if (!ControladorModeloColorTaller::ctrPuedeProduccion()) {
	http_response_code(403);
	exit("Sin permiso de producción");
}

header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="plantilla-modelo-color-taller.csv"');
header("Cache-Control: no-store, no-cache, must-revalidate");

$salida = fopen("php://output", "wb");
fwrite($salida, "\xEF\xBB\xBF");
fputcsv($salida, array("modelo", "cod_color", "cod_sector", "observacion", "estado"));
// Regla general: color vacío
fputcsv($salida, array("10026", "", "T1", "Regla general del modelo (dejar color vacío)", "1"));
// ="01" hace que Excel conserve el cero a la izquierda al abrir el CSV
fputcsv($salida, array("10026", '="01"', "T3", "Excepción por color (código con cero: 01)", "1"));
fputcsv($salida, array("10026", '="02"', "T1", "Otro ejemplo de color 02", "1"));
fputcsv($salida, array("10026", "34", "T3", "Color 34 (sin cero a la izquierda)", "1"));
fclose($salida);
