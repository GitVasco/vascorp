-- =============================================================================
-- Operación COMPENSACION (832) para compensación por día en en-taller
-- El texto visible sale del campo entallerjf.concepto (no crear una op. por tipo).
-- Preferir docs/sql/compensacion-dia-concepto.sql si la BD ya tiene 832.
-- Monto ticket según tipo (sueldo / promedio); cantidad = ROUND(monto * 12, 2)
-- Artículo base: C001251 (modelo C001), igual que compensación 828
-- =============================================================================

INSERT INTO operacionesjf (codigo, nombre)
SELECT '832', 'COMPENSACION'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM operacionesjf WHERE codigo = '832'
);

UPDATE operacionesjf
SET nombre = 'COMPENSACION'
WHERE codigo = '832' AND nombre <> 'COMPENSACION';

INSERT INTO operaciones_detallejf (modelo, cod_operacion, precio_doc, tiempo_stand)
SELECT 'C001', '832', 1.0000, 0
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM operaciones_detallejf
    WHERE modelo = 'C001' AND cod_operacion = '832'
);
