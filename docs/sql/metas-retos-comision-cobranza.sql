-- =============================================================================
-- Metas / retos — comisión por cobranza efectiva
-- =============================================================================
-- 1) Columnas de meta/comisión de cobranza en metas_retos_vendedorjf
-- 2) Semilla idempotente de meta_cobranza desde metas_vendedorjf (sin sobrescribir)
-- 3) Consultas de verificación
--
-- Política: comisión general por cobranza neta (monto/1.18); ventas desactivadas
-- en config (MR_COMISION_VENTAS_HABILITADA). Incentivos de producto siguen por venta.
-- Ejecutar respaldo de metas_retos_vendedorjf y metas_vendedorjf antes.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) Columnas (idempotente: solo agrega si no existen)
-- -----------------------------------------------------------------------------
SET @db := DATABASE();

SET @existe_meta_cob := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'metas_retos_vendedorjf'
      AND COLUMN_NAME = 'meta_cobranza'
);

SET @sql_meta_cob := IF(
    @existe_meta_cob = 0,
    "ALTER TABLE metas_retos_vendedorjf
        ADD COLUMN meta_cobranza DECIMAL(14,2) NULL COMMENT 'Meta mensual de cobranza efectiva',
        ADD COLUMN comision_cobranza_pct DECIMAL(8,2) NULL COMMENT '% sobre cobranza efectiva',
        ADD COLUMN comision_cobranza_fijo DECIMAL(14,2) NULL COMMENT 'Monto fijo al cumplir meta',
        ADD COLUMN cumplimiento_cobranza VARCHAR(20) NOT NULL DEFAULT 'todo_nada'
            COMMENT 'todo_nada | prorrata'",
    "SELECT 'columnas cobranza ya existen' AS info"
);

PREPARE stmt_meta_cob FROM @sql_meta_cob;
EXECUTE stmt_meta_cob;
DEALLOCATE PREPARE stmt_meta_cob;

-- -----------------------------------------------------------------------------
-- 2) Semilla: solo donde el reto aún no tiene meta_cobranza y el legacy sí
--    (nunca sobrescribe un valor ya cargado en metas-retos)
-- -----------------------------------------------------------------------------
UPDATE metas_retos_vendedorjf r
INNER JOIN metas_vendedorjf m
    ON TRIM(m.cod_vendedor) = TRIM(r.cod_vendedor)
   AND m.anio = r.anio
   AND m.mes = r.mes
SET r.meta_cobranza = m.meta_cobranza
WHERE r.meta_cobranza IS NULL
  AND m.meta_cobranza IS NOT NULL;

-- -----------------------------------------------------------------------------
-- 3) Verificación (descomentar al revisar)
-- -----------------------------------------------------------------------------
-- SELECT COUNT(*) AS retos_con_meta_cobranza
-- FROM metas_retos_vendedorjf
-- WHERE meta_cobranza IS NOT NULL;
--
-- SELECT r.cod_vendedor, r.anio, r.mes,
--        r.meta_cobranza AS reto_cob,
--        m.meta_cobranza AS legacy_cob
-- FROM metas_retos_vendedorjf r
-- LEFT JOIN metas_vendedorjf m
--   ON TRIM(m.cod_vendedor) = TRIM(r.cod_vendedor)
--  AND m.anio = r.anio AND m.mes = r.mes
-- WHERE r.meta_cobranza IS NOT NULL
--    OR m.meta_cobranza IS NOT NULL
-- ORDER BY r.anio DESC, r.mes DESC, r.cod_vendedor;
--
-- -- Conciliación bruta vs neto (ejemplo mes cerrado; ajustar fechas):
-- -- SELECT TRIM(cc.vendedor) AS vendedor,
-- --        ROUND(SUM(IFNULL(cc.monto, 0)), 2) AS bruto,
-- --        ROUND(SUM(IFNULL(cc.monto, 0) / 1.18), 2) AS neto
-- -- FROM cuenta_ctejf cc
-- -- WHERE cc.tip_mov = '-'
-- --   AND cc.fecha >= '2026-06-01' AND cc.fecha < '2026-07-01'
-- --   AND cc.cod_pago IN ('00','TR','05','06','14','15','16','17','18','80','82')
-- -- GROUP BY TRIM(cc.vendedor);
