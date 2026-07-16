<?php

/**
 * Configuración comercial de metas / retos.
 *
 * Base de comisión general: 'cobranza' | 'ventas' (exclusivas).
 * Se puede cambiar desde la pantalla Metas/retos (permiso editar).
 * Persistencia: metas-retos.runtime.json junto a este archivo.
 * Incentivos de producto siempre pagan por venta del objetivo.
 */

/** Factor IGV incluido en montos de cuenta_ctejf. Neto = monto / IGV_FACTOR. No usar 0.82. */
if (!defined("MR_IGV_FACTOR")) {
	define("MR_IGV_FACTOR", 1.18);
}

/** Default si no hay runtime: cobranza (política vigente Gerencia). */
if (!defined("MR_BASE_COMISION_DEFAULT")) {
	define("MR_BASE_COMISION_DEFAULT", "cobranza");
}

/**
 * @return string ruta del JSON de runtime
 */
function mrRuntimePath()
{
	return dirname(__FILE__) . "/metas-retos.runtime.json";
}

/**
 * @return array
 */
function mrLeerRuntime()
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$path = mrRuntimePath();
	$cache = array();
	if (is_file($path) && is_readable($path)) {
		$raw = @file_get_contents($path);
		$decoded = json_decode($raw !== false ? $raw : "", true);
		if (is_array($decoded)) {
			$cache = $decoded;
		}
	}
	return $cache;
}

/**
 * @param array $datos
 * @return bool
 */
function mrGuardarRuntime($datos)
{
	if (!is_array($datos)) {
		return false;
	}
	$actual = mrLeerRuntime();
	$nuevo = array_merge($actual, $datos);
	$path = mrRuntimePath();
	$json = json_encode($nuevo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if ($json === false) {
		return false;
	}
	$ok = @file_put_contents($path, $json . "\n", LOCK_EX) !== false;
	if ($ok) {
		// invalidar cache estática de mrLeerRuntime en el mismo request
		// (redefinir vía relectura forzada no es trivial con static; leer directo tras guardar)
	}
	return $ok;
}

/**
 * Base de comisión general: cobranza | ventas.
 *
 * @return string
 */
function mrBaseComision()
{
	$runtime = mrLeerRuntime();
	if (isset($runtime["base_comision"])) {
		$b = trim((string) $runtime["base_comision"]);
		if ($b === "ventas" || $b === "cobranza") {
			return $b;
		}
	}
	$def = defined("MR_BASE_COMISION_DEFAULT") ? MR_BASE_COMISION_DEFAULT : "cobranza";
	return ($def === "ventas") ? "ventas" : "cobranza";
}

/**
 * @return bool
 */
function mrComisionCobranzaHabilitada()
{
	return mrBaseComision() === "cobranza";
}

/**
 * Compat: true solo si la base activa es ventas.
 *
 * @return bool
 */
function mrComisionVentasHabilitada()
{
	return mrBaseComision() === "ventas";
}

/**
 * Códigos de pago = cobranza efectiva (misma lista definitiva comercial).
 * Fuente única para inicio-gerencia, metas-vendedor, dashboard-cobranzas y metas-retos.
 *
 * @return string[]
 */
function mrCodigosCobranzaEfectiva()
{
	return array("00", "TR", "05", "06", "14", "15", "16", "17", "18", "80", "82");
}

/**
 * Fragmento SQL: alias.cod_pago IN (...códigos efectivos...).
 *
 * @param string $alias
 * @return string
 */
function mrSqlInCodigosCobranzaEfectiva($alias = "cc")
{
	$alias = preg_replace("/[^a-zA-Z0-9_]/", "", (string) $alias);
	if ($alias === "") {
		$alias = "cc";
	}
	$quoted = array();
	foreach (mrCodigosCobranzaEfectiva() as $cod) {
		$quoted[] = "'" . str_replace("'", "", $cod) . "'";
	}
	return "{$alias}.cod_pago IN (" . implode(", ", $quoted) . ")";
}

/**
 * @return float
 */
function mrIgvFactor()
{
	return (float) MR_IGV_FACTOR;
}

/**
 * Texto corto de códigos para ayuda en UI.
 *
 * @return string
 */
function mrTextoCodigosCobranzaEfectiva()
{
	return implode(", ", mrCodigosCobranzaEfectiva());
}

/**
 * Normaliza y persiste la base de comisión.
 *
 * @param string $base
 * @return array{ok:bool,base_comision?:string,mensaje?:string}
 */
function mrSetBaseComision($base)
{
	$base = trim((string) $base);
	if ($base !== "cobranza" && $base !== "ventas") {
		return array("ok" => false, "mensaje" => "Base inválida (use cobranza o ventas)");
	}
	$path = mrRuntimePath();
	$dir = dirname($path);
	if (!is_dir($dir) || !is_writable($dir)) {
		if (is_file($path) && !is_writable($path)) {
			return array("ok" => false, "mensaje" => "No se pudo guardar la configuración (sin permiso de escritura)");
		}
		if (!is_file($path) && !is_writable($dir)) {
			return array("ok" => false, "mensaje" => "No se pudo guardar la configuración (sin permiso de escritura)");
		}
	}
	$ok = @file_put_contents(
		$path,
		json_encode(array("base_comision" => $base), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n",
		LOCK_EX
	);
	if ($ok === false) {
		return array("ok" => false, "mensaje" => "No se pudo escribir metas-retos.runtime.json");
	}
	return array("ok" => true, "base_comision" => $base);
}
