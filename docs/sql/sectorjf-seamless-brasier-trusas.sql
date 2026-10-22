-- =============================================================================
-- Servicios Seamless: renombrar T11 y crear el nuevo servicio Seamless Trusas
-- =============================================================================
-- Ambos son SERVICIO externo (sectorjf.tipo = 1): van por servicios/cierres,
-- sin tickets ni stock interno (articulojf). Todo el flujo (corte, servicios,
-- ingresos, ingresos multi, cierres, pagos, GRE, programación semanal,
-- modelo-color-taller) lee de sectorjf, no requiere cambios de código.
-- Ejecutar primero en copia de BD. Idempotente.
-- =============================================================================

-- 1) T11 pasa a llamarse "Seamless Brasier" (el código no cambia: no rompe históricos)
UPDATE sectorjf
SET nom_sector = 'SEAMLESS BRASIER'
WHERE cod_sector = 'T11';

-- 2) Nuevo servicio "Seamless Trusas" (código T15; verificar que esté libre)
--    SELECT cod_sector, nom_sector FROM sectorjf ORDER BY cod_sector;
INSERT INTO sectorjf (cod_sector, nom_sector, tipo, estado, color)
SELECT 'T15', 'SEAMLESS TRUSAS', 1, 1, '#9BF6FF'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sectorjf WHERE cod_sector = 'T15');

-- 3) Verificación
SELECT cod_sector, nom_sector, tipo, estado, color
FROM sectorjf
WHERE cod_sector IN ('T11', 'T15');

-- =============================================================================
-- Pendiente de carga por el usuario (datos, no código):
--   * Precios de servicio de T15 (Producción > Precio servicio)
--   * Datos fiscales GRE de T15 (gre_sector_datosjf: razón social, RUC, dirección)
--   * Trabajadores/maquinas asociados a T15 si aplica (trabajador.sector)
--   * Asignación modelo/color -> T15 (Producción > Modelo color taller)
-- =============================================================================
