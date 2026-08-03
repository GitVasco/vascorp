-- =============================================================================
-- Zona comercial La Victoria (solo manual, sin ubigeos)
-- =============================================================================
-- Clientes del distrito La Victoria (fuera de Gamarra / LIM_ECONOMICA si aplica).
-- No se autoasigna por ubigeo; override manual en cliente/grupo.
-- Ejecutar manualmente en la BD. Idempotente.
-- =============================================================================

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_VICTORIA', 'La Victoria', 'lima',
       'Distrito La Victoria → zona propia (ubigeo). Gamarra sigue manual en LIM_ECONOMICA.',
       '#932ab6', 62, 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_VICTORIA');
