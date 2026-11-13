-- =============================================================================
-- Compensación por día (op. 832): concepto libre + nombre genérico
-- Ejecutar una vez en la BD de producción / pruebas.
-- =============================================================================

ALTER TABLE entallerjf
  ADD COLUMN concepto VARCHAR(120) NULL
  COMMENT 'Texto libre que reemplaza el nombre de operación (ej. Feriado 28 jul)'
  AFTER cod_operacion;

UPDATE operacionesjf
SET nombre = 'COMPENSACION'
WHERE codigo = '832';

INSERT INTO operacionesjf (codigo, nombre)
SELECT '832', 'COMPENSACION'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM operacionesjf WHERE codigo = '832'
);

INSERT INTO operaciones_detallejf (modelo, cod_operacion, precio_doc, tiempo_stand)
SELECT 'C001', '832', 1.0000, 0
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM operaciones_detallejf
    WHERE modelo = 'C001' AND cod_operacion = '832'
);
