<?php

/**
 * Configuración comercial de metas / retos.
 *
 * Política vigente (Gerencia General): comisión general por cobranza efectiva;
 * comisión general por ventas desactivada. Incentivos de producto siguen por venta.
 */

/** Factor IGV incluido en montos de cuenta_ctejf. Neto = monto / IGV_FACTOR. No usar 0.82. */
if (!defined("MR_IGV_FACTOR")) {
	define("MR_IGV_FACTOR", 1.18);
}

/**
 * Si es false, ctrCalcularComisionEstimada deja aporte de ventas en 0
 * sin borrar campos ni el avance informativo.
 */
if (!defined("MR_COMISION_VENTAS_HABILITADA")) {
	define("MR_COMISION_VENTAS_HABILITADA", false);
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
 * @return bool
 */
function mrComisionVentasHabilitada()
{
	return (bool) MR_COMISION_VENTAS_HABILITADA;
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
