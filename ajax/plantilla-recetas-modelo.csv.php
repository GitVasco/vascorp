<?php

if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION["id"])) {
	http_response_code(403);
	exit("Sin sesión");
}

if (isset($_SESSION["tarjetas"]) && (int) $_SESSION["tarjetas"] !== 1) {
	http_response_code(403);
	exit("Sin permiso");
}

header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="plantilla-recetas-modelo.csv"');
header("Cache-Control: no-store, no-cache, must-revalidate");

$salida = fopen("php://output", "wb");
fwrite($salida, "\xEF\xBB\xBF");

// Obligatorio: articulo + CodPro + consumo
// ="01517" hace que Excel conserve el cero a la izquierda al abrir el CSV
fputcsv($salida, array("articulo", "mp_codigo", "consumo"));

fputcsv($salida, array("1004001S", '="01517"', "1.25"));
fputcsv($salida, array("1004001M", '="01517"', "1.30"));
fputcsv($salida, array("1004001S", '="06789"', "1"));
fputcsv($salida, array("1004001M", "67890", "1"));

fclose($salida);
