-- =============================================================================
-- Salvaguarda anti-duplicado de correlativos en ventajf
-- =============================================================================
-- El correlativo ya se asigna con candado en talonariosjf al facturar.
-- Este índice evita que, ante un fallo residual, queden dos ventas con el
-- mismo tipo + documento.
--
-- Antes de ejecutar en producción, revisar duplicados existentes:
--
--   SELECT tipo, documento, COUNT(*) AS cnt
--   FROM ventajf
--   GROUP BY tipo, documento
--   HAVING COUNT(*) > 1;
--
-- Si hay filas, limpiarlas / renumerar antes de crear el índice.
-- =============================================================================

ALTER TABLE ventajf
  ADD UNIQUE KEY uk_ventajf_tipo_documento (tipo, documento);
