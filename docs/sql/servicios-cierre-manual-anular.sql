-- Anulación de cierres manuales. Ejecutar una sola vez.
ALTER TABLE servicios_cierre_manual
  ADD COLUMN anulado TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = anulado' AFTER fecha,
  ADD COLUMN anulado_por VARCHAR(100) NULL COMMENT 'Usuario que anuló' AFTER anulado,
  ADD COLUMN anulado_en DATETIME NULL COMMENT 'Fecha/hora de anulación' AFTER anulado_por,
  ADD COLUMN motivo_anulacion VARCHAR(255) NULL COMMENT 'Motivo de anulación' AFTER anulado_en;
