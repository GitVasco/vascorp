-- =============================================================================
-- Zona comercial Lima Cercado (solo manual, sin ubigeos)
-- =============================================================================
-- Mismo trato que La Victoria: no se autoasigna por distrito; override en cliente/grupo.
-- Ejecutar manualmente en la BD. Idempotente.
-- =============================================================================

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_CERCADO', 'Lima Cercado', 'lima',
       'Distrito Lima (Cercado) → zona propia (ubigeo). No entra a Z1/Z2/Z3.',
       '#85144b', 63, 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_CERCADO');
