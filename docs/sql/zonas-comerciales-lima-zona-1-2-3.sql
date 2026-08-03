-- =============================================================================
-- Zonas comerciales Lima — Zona 1, Zona 2, Zona 3
-- =============================================================================
-- Territoriales por distrito (ubigeos se asignan después desde el módulo o SQL aparte).
-- Ejecutar manualmente en la BD. Idempotente.
-- =============================================================================

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_ZONA_1', 'Lima — Zona 1', 'lima',
       'Equipo PC. Distritos según tabla Supervisión + Callao completo.',
       '#00a65a', 1, 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_ZONA_1');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_ZONA_2', 'Lima — Zona 2', 'lima',
       'Equipo GS. Distritos según tabla Supervisión.',
       '#605ca8', 2, 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_ZONA_2');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_ZONA_3', 'Lima — Zona 3', 'lima',
       'Equipo JCD. Distritos según tabla Supervisión.',
       '#f39c12', 3, 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_ZONA_3');
