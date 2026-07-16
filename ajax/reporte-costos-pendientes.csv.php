<?php

if (!isset($_SESSION)) {
	session_start();
}

require_once "../controladores/permisos-modulos.config.php";
require_once "../modelos/costos-modelo-mensual.modelo.php";

if (!function_exists("usuarioPuedeVerModulo") || !usuarioPuedeVerModulo("gestion_comercial", "costos_modelo")) {
	http_response_code(403);
	exit("Sin permiso");
}

$anio = isset($_GET["anio"]) ? (int) $_GET["anio"] : 0;
$mes = isset($_GET["mes"]) ? (int) $_GET["mes"] : 0;
if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) {
	http_response_code(400);
	exit("Período inválido");
}

try {
	$pendientes = ModeloCostosModeloMensual::mdlListarCostosPeriodo($anio, $mes, 0, "sin_aprobado");
} catch (Exception $e) {
	http_response_code(500);
	exit("No se pudo generar el reporte");
}

header("Content-Type: text/csv; charset=utf-8");
header('Content-Disposition: attachment; filename="modelos-sin-costo-' . $anio . '-' . str_pad($mes, 2, "0", STR_PAD_LEFT) . '.csv"');
header("Cache-Control: no-store, no-cache, must-revalidate");

$salida = fopen("php://output", "wb");
fwrite($salida, "\xEF\xBB\xBF");
fputcsv($salida, array("modelo", "nombre", "marca", "anio", "mes"));
foreach ($pendientes as $fila) {
	$valores = array($fila["modelo"], $fila["nombre"], $fila["marca"], $anio, $mes);
	foreach ($valores as $indice => $valor) {
		$valor = (string) $valor;
		if (preg_match('/^[=\-+@]/', $valor)) {
			$valor = "'" . $valor;
		}
		$valores[$indice] = $valor;
	}
	fputcsv($salida, $valores);
}
fclose($salida);
