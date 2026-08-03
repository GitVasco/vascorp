-- =============================================================================
-- Zona comercial Distribuidores (solo manual, sin ubigeos)
-- =============================================================================
-- Igual que LIM_ECONOMICA: no se autoasigna por distrito; override en cliente/grupo.
-- Ejecutar manualmente en la BD. Idempotente.
-- =============================================================================

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_DISTRIBUIDORES', 'Distribuidores', 'lima',
       'Clientes distribuidores. No se autoasigna por ubigeo; override manual en cliente/grupo.',
       '#001f3f', 65, 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_DISTRIBUIDORES');
