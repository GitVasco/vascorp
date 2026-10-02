-- =============================================================================
-- GRE manual desde Servicios: datos de talleres y vínculo guía <-> servicio
-- =============================================================================
-- Ejecutar UNA vez en BD vasco (MariaDB). Repetible.
-- =============================================================================

CREATE TABLE IF NOT EXISTS gre_sector_datosjf (
    cod_sector      VARCHAR(10) NOT NULL COMMENT 'sectorjf.cod_sector',
    razon_social    VARCHAR(100) NOT NULL,
    tipo_doc        VARCHAR(1) NOT NULL DEFAULT '6' COMMENT 'Catálogo 06 (6 RUC, 1 DNI...)',
    doc             VARCHAR(15) NOT NULL,
    email           VARCHAR(100) NULL,
    direccion       VARCHAR(100) NOT NULL,
    ubigeo          VARCHAR(6) NOT NULL,
    dpto            VARCHAR(30) NOT NULL,
    prov            VARCHAR(30) NOT NULL,
    dist            VARCHAR(30) NOT NULL,
    usuario         VARCHAR(50) NULL,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cod_sector)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Datos fiscales/dirección de talleres externos para guías de remisión';

ALTER TABLE gre_manualjf
    ADD COLUMN IF NOT EXISTS servicio VARCHAR(20) NULL
    COMMENT 'serviciosjf.codigo si la guía se emitió desde Servicios' AFTER doc_interno;

ALTER TABLE gre_manualjf
    ADD INDEX IF NOT EXISTS idx_gre_servicio (servicio);

SHOW COLUMNS FROM gre_manualjf LIKE 'servicio';
