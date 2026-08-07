<?php

/**
 * Configuración del Dashboard Gerencial.
 *
 * Fuentes de datos (Bloque 0 — contrato):
 * - Ventas (KPI / mensual / vs año / Origen): ventajf.neto tipos S02/S03/S70/E05/S05
 * - Cobranza (KPI / mensual / vs año / rangos): cuenta_ctejf tip_mov='-', códigos EFECTIVO
 *   (mrSqlInCodigosCobranzaEfectiva; misma lista que dashboard-cobranzas)
 * - Todas las cobranzas / proyección de este dashboard se muestran SIN IGV (÷1.18)
 *   para comparar con ventas netas.
 * - Cumplimiento vencimientos: cargos con fecha_ven en el período;
 *   a tiempo / atrasado / pendiente según ult_pago (o MAX abono) vs fecha_ven.
 * - Cobertura proyección: saldo por vencer vs cobranza del mes (distinto de puntualidad).
 * - Filtro vendedores: misma lista fija que dashboard-cobranzas/vendedores-filtro.php
 * - Origen cobranza: fecha_ori del abono o fecha del cargo (+); % recup. = mismo mes pago/total
 * - Proyección: reglas dashboard-cxc (fecha_ven / vencido); real = cobranza efectiva del mes
 * - No usar totalesjf (snapshot desfasado).
 */

function dashboardGerencialAnnosPermitidos()
{
    return array(2025, 2026);
}
