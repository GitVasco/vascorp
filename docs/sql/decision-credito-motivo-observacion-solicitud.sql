-- Motivo de catálogo (opcional) al resolver una solicitud de crédito.
-- Ejecutar en la BD vasco.

ALTER TABLE decision_credito_solicitudjf
    ADD COLUMN motivo_observacion_codigo VARCHAR(50) NULL
        COMMENT 'Catálogo motivos_aprobacion (creditos-motivos.config.json)'
        AFTER comentario_resolucion;
