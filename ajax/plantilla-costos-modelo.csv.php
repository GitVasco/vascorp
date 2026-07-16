<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";

if (!function_exists("usuarioPuedeModulo") || !usuarioPuedeModulo("gestion_comercial", "costos_modelo", "editar")) {
	http_response_code(403);
	exit("Sin permiso");
}

header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="plantilla-costos-modelo.csv"');
header("Cache-Control: no-store, no-cache, must-revalidate");

$salida = fopen("php://output", "wb");
fwrite($salida, "\xEF\xBB\xBF");
fputcsv($salida, array("modelo", "costo_unitario", "fuente", "observacion"));
fputcsv($salida, array("10026", "22.5000", "Costeo mensual", "Reemplazar esta fila con datos reales"));
fclose($salida);
