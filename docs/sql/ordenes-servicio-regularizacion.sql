-- =============================================================================
-- Órdenes de servicio de REGULARIZACIÓN (servicios ya enviados antes de usar las OSP)
-- =============================================================================
-- Ejecutar UNA vez en BD vasco (MariaDB). Repetible. Requiere ordenes-servicio-prod.sql.
-- Una orden de regularización agrupa los servicios históricos de un taller para que
-- las liquidaciones muestren su OSP de origen. NO lleva importes (no valoriza), no
-- exige precios cargados y no cuenta guías pendientes. Ver docs/ordenes-servicio-regularizacion.md
-- =============================================================================

ALTER TABLE ordenes_servicio_prodjf
    ADD COLUMN IF NOT EXISTS regularizacion TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = orden de regularización (servicios ya enviados; sin importes ni guías)';

SHOW COLUMNS FROM ordenes_servicio_prodjf LIKE 'regularizacion';
