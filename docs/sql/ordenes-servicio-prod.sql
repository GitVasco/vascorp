-- =============================================================================
-- Órdenes de servicio de producción (sustento ante fiscalización SUNAT)
-- =============================================================================
-- Ejecutar UNA vez en BD vasco (MariaDB). Repetible.
-- La orden agrupa los servicios (serviciosjf) de un taller en un rango de fechas
-- y guarda una FOTO congelada (datos del taller, guías, detalle y precios), de modo
-- que el documento impreso no cambia aunque luego se edite un servicio o un precio.
-- =============================================================================

CREATE TABLE IF NOT EXISTS ordenes_servicio_prodjf (
    id               INT NOT NULL AUTO_INCREMENT,
    numero           VARCHAR(12) NOT NULL COMMENT 'OSP-000001',
    taller           VARCHAR(10) NOT NULL COMMENT 'sectorjf.cod_sector',
    taller_nombre    VARCHAR(100) NULL,
    taller_razon     VARCHAR(100) NULL COMMENT 'gre_sector_datosjf.razon_social al emitir',
    taller_ruc       VARCHAR(15) NULL,
    taller_direccion VARCHAR(200) NULL,
    fecha_desde      DATE NOT NULL,
    fecha_hasta      DATE NOT NULL,
    fecha_emision    DATETIME NOT NULL,
    usuario          INT NULL,
    usuario_nombre   VARCHAR(100) NULL,
    observaciones    VARCHAR(500) NULL,
    total_servicios  INT NOT NULL DEFAULT 0,
    total_unidades   INT NOT NULL DEFAULT 0,
    total_importe    DECIMAL(14,2) NOT NULL DEFAULT 0,
    estado           VARCHAR(10) NOT NULL DEFAULT 'EMITIDA' COMMENT 'EMITIDA | ANULADA',
    anulado_por      VARCHAR(100) NULL,
    anulado_en       DATETIME NULL,
    motivo_anulacion VARCHAR(200) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_osp_numero (numero),
    KEY idx_osp_taller (taller, fecha_desde, fecha_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ordenes_servicio_prod_servjf (
    id             INT NOT NULL AUTO_INCREMENT,
    id_orden       INT NOT NULL,
    servicio       VARCHAR(20) NOT NULL COMMENT 'serviciosjf.codigo',
    guia           VARCHAR(30) NULL COMMENT 'serviciosjf.guia al emitir',
    fecha_servicio DATETIME NULL,
    unidades       INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_ospserv_orden (id_orden),
    KEY idx_ospserv_servicio (servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS ordenes_servicio_prod_detjf (
    id            INT NOT NULL AUTO_INCREMENT,
    id_orden      INT NOT NULL,
    servicio      VARCHAR(20) NOT NULL,
    modelo        VARCHAR(20) NULL,
    nombre        VARCHAR(100) NULL,
    cod_color     VARCHAR(10) NULL,
    color         VARCHAR(50) NULL,
    t1 INT NOT NULL DEFAULT 0, t2 INT NOT NULL DEFAULT 0, t3 INT NOT NULL DEFAULT 0, t4 INT NOT NULL DEFAULT 0,
    t5 INT NOT NULL DEFAULT 0, t6 INT NOT NULL DEFAULT 0, t7 INT NOT NULL DEFAULT 0, t8 INT NOT NULL DEFAULT 0,
    cantidad      INT NOT NULL DEFAULT 0,
    precio_doc    DECIMAL(12,4) NOT NULL DEFAULT 0 COMMENT 'precio por docena (precio_serviciojf)',
    importe       DECIMAL(14,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_ospdet_orden (id_orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Cuentas del taller congeladas al emitir (sectorjf.cuenta_detraccion / cuenta_bcp)
ALTER TABLE ordenes_servicio_prodjf
    ADD COLUMN IF NOT EXISTS taller_cta_detraccion VARCHAR(30) NULL AFTER taller_direccion,
    ADD COLUMN IF NOT EXISTS taller_cta_bcp VARCHAR(30) NULL AFTER taller_cta_detraccion;

-- Marca en el servicio: a qué orden pertenece (NULL = aún sin orden)
ALTER TABLE serviciosjf
    ADD COLUMN IF NOT EXISTS orden_servicio INT NULL
    COMMENT 'ordenes_servicio_prodjf.id' ;

ALTER TABLE serviciosjf
    ADD INDEX IF NOT EXISTS idx_servicios_orden (orden_servicio);

SHOW COLUMNS FROM serviciosjf LIKE 'orden_servicio';
