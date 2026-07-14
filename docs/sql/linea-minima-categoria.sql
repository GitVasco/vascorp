-- =============================================================================
-- Piso de línea de crédito por categoría comercial
-- =============================================================================
-- Agrega el requisito `linea_minima` en categorias_clientes_requisitosjf.
-- valor_numerico NULL = aún no definido (no aplica piso).
-- Idempotente: se puede ejecutar más de una vez.
-- =============================================================================

INSERT INTO categorias_clientes_requisitosjf
    (id_categoria, tipo_requisito, valor_numerico, unidad, descripcion, estado, usureg, fecreg)
SELECT
    c.id,
    'linea_minima',
    NULL,
    'PEN',
    'Línea de crédito mínima coherente con la categoría',
    1,
    'sistema',
    NOW()
FROM categorias_clientesjf c
WHERE NOT EXISTS (
    SELECT 1
    FROM categorias_clientes_requisitosjf r
    WHERE r.id_categoria = c.id
      AND r.tipo_requisito = 'linea_minima'
);

-- Opcional: ejemplos orientativos (comentar/ajustar según reglas comerciales).
-- UPDATE categorias_clientes_requisitosjf r
-- INNER JOIN categorias_clientesjf c ON c.id = r.id_categoria
-- SET r.valor_numerico = CASE c.codigo
--         WHEN 'MAYO' THEN 10000
--         WHEN 'DIST' THEN 15000
--         WHEN 'MINO' THEN 3000
--         ELSE r.valor_numerico
--     END,
--     r.usumod = 'sistema',
--     r.fecmod = NOW()
-- WHERE r.tipo_requisito = 'linea_minima';
