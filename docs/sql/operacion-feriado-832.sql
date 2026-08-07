-- =============================================================================
-- Operación FERIADO (832) para compensación global por feriado en en-taller
-- Monto ticket = ROUND(sueldo_total / 30, 2)
-- Fórmula taller: total_precio = monto (2 decimales)
-- Con precio_doc = 1 ⇒ cantidad = ROUND(monto * 12, 2)
-- Artículo base: C001251 (modelo C001), igual que compensación 828
-- =============================================================================

INSERT INTO operacionesjf (codigo, nombre)
SELECT '832', 'FERIADO'
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
