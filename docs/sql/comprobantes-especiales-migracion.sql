-- =============================================================================
-- Migración: módulo "Obsequios" -> "Comprobantes especiales"
-- Ejecutar UNA sola vez, SOLO si ya había ejecutado la versión anterior (tablas obsequio*).
-- Instalación nueva: usar docs/sql/comprobantes-especiales.sql (no esta migración).
-- Conserva los datos: renombra tablas/columna y deja el tipo como GRATUITA.
-- =============================================================================
ALTER TABLE obsequiojf ADD COLUMN IF NOT EXISTS tipo_operacion VARCHAR(20) NOT NULL DEFAULT 'OBSEQUIO' AFTER tipo_doc;

RENAME TABLE obsequiojf TO comp_especialjf,
             obsequio_detjf TO comp_especial_detjf,
             obsequio_seriejf TO comp_especial_seriejf;

ALTER TABLE comp_especial_detjf CHANGE COLUMN id_obsequio id_comprobante BIGINT(20) NOT NULL;

ALTER TABLE comp_especialjf MODIFY COLUMN tipo_operacion VARCHAR(20) NOT NULL DEFAULT 'GRATUITA'
    COMMENT 'Tipo de comprobante especial (hoy solo GRATUITA)';
UPDATE comp_especialjf SET tipo_operacion = 'GRATUITA' WHERE tipo_operacion = 'OBSEQUIO';
