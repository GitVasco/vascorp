-- =============================================================================
-- Metas / retos — meta de modelos como cantidad o % del universo
-- =============================================================================
-- meta_modelos sigue siendo el umbral entero usado en el avance.
-- Si modo = porcentaje, se guarda el % y al configurar se calcula meta_modelos.
-- Ejecutar en copia de BD antes de producción.
-- =============================================================================

SET @col_modo := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'metas_retos_vendedorjf'
      AND COLUMN_NAME = 'meta_modelos_modo'
);
SET @sql_modo := IF(
    @col_modo = 0,
    'ALTER TABLE metas_retos_vendedorjf
        ADD COLUMN meta_modelos_modo VARCHAR(20) NOT NULL DEFAULT ''cantidad''
            COMMENT ''cantidad|porcentaje'' AFTER meta_modelos,
        ADD COLUMN meta_modelos_pct DECIMAL(8,2) NULL
            COMMENT ''% sobre universo activo del vendedor'' AFTER meta_modelos_modo',
    'SELECT ''metas_retos_vendedorjf.meta_modelos_modo ya existe'' AS info'
);
PREPARE stmt_modo FROM @sql_modo;
EXECUTE stmt_modo;
DEALLOCATE PREPARE stmt_modo;
