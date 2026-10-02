-- =============================================================================
-- GRE manual: guías internas vs electrónicas (migración sobre gre-manual.sql)
-- =============================================================================
-- Se puede ejecutar varias veces (ADD ... IF NOT EXISTS + MODIFY). Requiere MariaDB.
-- Las guías y series existentes quedan como ELECTRONICA.
-- =============================================================================

ALTER TABLE gre_manual_seriejf
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(12) NOT NULL DEFAULT 'ELECTRONICA'
    COMMENT 'ELECTRONICA (se envía a EFACT) o INTERNA';
ALTER TABLE gre_manual_seriejf
    MODIFY COLUMN tipo VARCHAR(12) NOT NULL DEFAULT 'ELECTRONICA'
    COMMENT 'ELECTRONICA (se envía a EFACT) o INTERNA';

ALTER TABLE gre_manualjf
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(12) NOT NULL DEFAULT 'ELECTRONICA'
    COMMENT 'ELECTRONICA o INTERNA (según la serie)' AFTER documento;
ALTER TABLE gre_manualjf
    MODIFY COLUMN tipo VARCHAR(12) NOT NULL DEFAULT 'ELECTRONICA'
    COMMENT 'ELECTRONICA o INTERNA (según la serie)';
ALTER TABLE gre_manualjf
    ADD COLUMN IF NOT EXISTS doc_interno VARCHAR(13) NULL
    COMMENT 'Número interno original si la guía se convirtió a electrónica' AFTER tipo;

INSERT IGNORE INTO gre_manual_seriejf (serie, correlativo, activo, tipo) VALUES ('I001', 0, 1, 'INTERNA');

-- Comprobación: debe mostrar T001 ELECTRONICA e I001 INTERNA
SELECT serie, correlativo, tipo FROM gre_manual_seriejf;
