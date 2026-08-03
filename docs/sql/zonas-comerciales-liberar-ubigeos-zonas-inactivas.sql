-- =============================================================================
-- Quitar ubigeos de zonas inactivas (Lima estructura anterior)
-- =============================================================================
-- Desactivar zona no borra filas en zonas_comerciales_ubigeojf.
-- Este script las suelta: en el catálogo las viejas muestran 0 ubigeos y
-- los distritos pasan a "Libre" en la bandeja de pendientes.
-- Idempotente.
-- =============================================================================

DELETE r FROM zonas_comerciales_ubigeojf r
INNER JOIN zonas_comercialesjf z ON z.id = r.id_zona
WHERE z.estado = 0
  AND z.codigo IN (
    'LIM_CENTRO',
    'LIM_NORTE',
    'LIM_ESTE',
    'LIM_SUR',
    'LIM_MODERNA',
    'CALLAO'
  );
