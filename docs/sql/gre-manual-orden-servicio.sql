-- =============================================================================
-- GRE manual desde Órdenes de servicio: una guía GLOBAL que ampara varios servicios
-- =============================================================================
-- Ejecutar UNA vez en BD vasco (MariaDB). Repetible.
-- Requiere gre-manual-servicio.sql y ordenes-servicio-prod.sql.
-- Guía independiente: gre_manualjf.servicio = un servicio (como hasta ahora).
-- Guía global:        gre_manualjf.orden_servicio = OSP y los servicios cubiertos van en
--                     gre_manual_serviciojf (uno por fila). Un servicio no puede estar en
--                     dos guías vigentes (independiente o global).
-- =============================================================================

ALTER TABLE gre_manualjf
    ADD COLUMN IF NOT EXISTS orden_servicio INT NULL
    COMMENT 'ordenes_servicio_prodjf.id si es una guía global de la orden' AFTER servicio;

CREATE TABLE IF NOT EXISTS gre_manual_serviciojf (
    id       INT NOT NULL AUTO_INCREMENT,
    id_gre   BIGINT(20) NOT NULL COMMENT 'gre_manualjf.id',
    servicio VARCHAR(20) NOT NULL COMMENT 'serviciosjf.codigo cubierto por la guía global',
    PRIMARY KEY (id),
    KEY idx_grs_gre (id_gre),
    KEY idx_grs_servicio (servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Servicios cubiertos por una guía de remisión global (por orden de servicio)';

SHOW COLUMNS FROM gre_manualjf LIKE 'orden_servicio';
