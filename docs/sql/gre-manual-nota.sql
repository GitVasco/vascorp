-- =============================================================================
-- GRE manual: ítems ligados a notas de salida de MP
-- =============================================================================
-- Ejecutar UNA vez en BD vasco (MariaDB). Repetible.
-- gre_manual_detjf.nota = 'NS-001-000123' (Tip-Ser-Nro de ventas_cab).
-- Una nota solo puede estar en una guía que no esté ANULADA.
-- =============================================================================

ALTER TABLE gre_manual_detjf
    ADD COLUMN IF NOT EXISTS nota VARCHAR(20) NULL
    COMMENT 'Nota de salida de MP de origen (Tip-Ser-Nro)' AFTER origen;

ALTER TABLE gre_manual_detjf
    ADD INDEX IF NOT EXISTS idx_grd_nota (nota);

SHOW COLUMNS FROM gre_manual_detjf LIKE 'nota';
