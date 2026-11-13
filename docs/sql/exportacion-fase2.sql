-- =============================================================================
-- Facturas de exportación — Fase 2 (CSV nuevo en vista previa)
-- Diseño: docs/efact/EXPORTACION-diseno.md
-- La tabla exp_partida_lineajf guarda el CÓDIGO DE PRODUCTO SUNAT (catálogo 25, 8 dígitos)
-- por línea; va en la columna R de cada ítem del CSV.
-- Precarga los códigos que ya existían en config.php ($codigo_arancel), que hoy no llegan al CSV.
-- Idempotente: no pisa códigos ya guardados.
-- =============================================================================

INSERT IGNORE INTO exp_partida_lineajf (linea, partida, descripcion, activo, usuario_registro) VALUES
    ('BOXER V', '53102303', 'CALZONCILLOS', 1, 'precarga config.php'),
    ('BRASIER', '53102304', 'BRASSIERES', 1, 'precarga config.php'),
    ('FAJA',    '53102307', 'FAJAS', 1, 'precarga config.php'),
    ('TOP',     '53102304', 'BRASSIERES', 1, 'precarga config.php'),
    ('TRUSA',   '53102303', 'CALZONCILLOS', 1, 'precarga config.php');
