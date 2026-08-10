-- Motivo y observación para abonos aún no aplicados (tabla abonosjf).
-- Ejecutar en la BD vasco.

ALTER TABLE abonosjf
    ADD COLUMN motivo_pendiente VARCHAR(50) NULL
        COMMENT 'Catálogo: no_identificado, referencia_incompleta, monto_no_coincide, duplicado, pendiente_confirmacion, otro'
        AFTER num_ope,
    ADD COLUMN observacion_pendiente VARCHAR(500) NULL
        COMMENT 'Observación libre opcional'
        AFTER motivo_pendiente,
    ADD COLUMN motivo_usuario VARCHAR(100) NULL
        COMMENT 'Usuario que registró/cambió el motivo'
        AFTER observacion_pendiente,
    ADD COLUMN motivo_fecha DATETIME NULL
        COMMENT 'Fecha/hora del último cambio de motivo'
        AFTER motivo_usuario;

CREATE INDEX idx_abonosjf_motivo_pendiente ON abonosjf (motivo_pendiente);
