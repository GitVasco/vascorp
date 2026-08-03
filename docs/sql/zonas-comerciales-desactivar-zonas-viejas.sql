-- =============================================================================
-- Desactivar zonas comerciales Lima (estructura anterior)
-- =============================================================================
-- Reemplazadas por LIM_ZONA_1/2/3, LIM_VICTORIA, LIM_CERCADO.
-- NO desactiva: LIM_ECONOMICA, LIM_DISTRIBUIDORES, NORTE_CHICO.
-- Los 14 distritos Lima aún en zonas viejas quedarán sin zona activa por ubigeo
-- hasta migrarlos — ver PENDIENTE_REESTRUCTURA_LIMA_3_ZONAS.md
-- Idempotente.
-- =============================================================================

UPDATE zonas_comercialesjf
SET estado = 0,
    usumod = 'sistema',
    fecmod = NOW()
WHERE codigo IN (
    'LIM_CENTRO',
    'LIM_NORTE',
    'LIM_ESTE',
    'LIM_SUR',
    'LIM_MODERNA',
    'CALLAO'
)
  AND estado = 1;

-- Soltar ubigeos colgados (ver bandeja "Distritos pendientes" en UI)
DELETE r FROM zonas_comerciales_ubigeojf r
INNER JOIN zonas_comercialesjf z ON z.id = r.id_zona
WHERE z.estado = 0
  AND z.codigo IN (
    'LIM_CENTRO', 'LIM_NORTE', 'LIM_ESTE', 'LIM_SUR', 'LIM_MODERNA', 'CALLAO'
  );
