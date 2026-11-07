-- =============================================================================
-- Liquidaciones de servicios de producción (pago a talleres)
-- =============================================================================
-- Ejecutar UNA vez en BD vasco (MariaDB). Repetible.
-- La liquidación agrupa los CIERRES (cierresjf: lo que el taller ya terminó) de un taller
-- en un rango de fechas y guarda una FOTO congelada (datos del taller, detalle y precios).
-- Cada línea indica a qué OSP (ordenes_servicio_prodjf) pertenece el servicio de origen;
-- si aún no tiene OSP queda con id_osp NULL ("Sin OSP") y se completa sola al emitir la OSP.
-- Al pagar, los cierres pasan a cierresjf.estado_pago = 'PAGADO' (igual que el módulo Pagos).
-- Requiere docs/sql/ordenes-servicio-prod.sql.
-- =============================================================================

CREATE TABLE IF NOT EXISTS liquidaciones_servicio_prodjf (
    id                    INT NOT NULL AUTO_INCREMENT,
    numero                VARCHAR(12) NOT NULL COMMENT 'LSP-000001',
    taller                VARCHAR(10) NOT NULL COMMENT 'sectorjf.cod_sector',
    taller_nombre         VARCHAR(100) NULL,
    taller_razon          VARCHAR(100) NULL,
    taller_ruc            VARCHAR(15) NULL,
    taller_direccion      VARCHAR(200) NULL,
    taller_cta_detraccion VARCHAR(30) NULL,
    taller_cta_bcp        VARCHAR(30) NULL,
    fecha_desde           DATE NOT NULL,
    fecha_hasta           DATE NOT NULL,
    fecha_emision         DATETIME NOT NULL,
    usuario               INT NULL,
    usuario_nombre        VARCHAR(100) NULL,
    observaciones         VARCHAR(500) NULL,
    total_cierres         INT NOT NULL DEFAULT 0,
    total_unidades        INT NOT NULL DEFAULT 0,
    total_importe         DECIMAL(14,2) NOT NULL DEFAULT 0,
    estado                VARCHAR(10) NOT NULL DEFAULT 'LIQUIDADA' COMMENT 'LIQUIDADA | PAGADA | ANULADA',
    fecha_pago            DATE NULL,
    pagado_por            VARCHAR(100) NULL,
    referencia_pago       VARCHAR(100) NULL COMMENT 'N° operación / transferencia',
    anulado_por           VARCHAR(100) NULL,
    anulado_en            DATETIME NULL,
    motivo_anulacion      VARCHAR(200) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lsp_numero (numero),
    KEY idx_lsp_taller (taller, fecha_desde, fecha_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS liquidaciones_servicio_prod_cierrejf (
    id             INT NOT NULL AUTO_INCREMENT,
    id_liquidacion INT NOT NULL,
    cierre         VARCHAR(11) NOT NULL COMMENT 'cierresjf.codigo',
    guia           VARCHAR(10) NULL COMMENT 'cierresjf.guia',
    fecha_cierre   VARCHAR(20) NULL,
    unidades       INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_lspc_liq (id_liquidacion),
    KEY idx_lspc_cierre (cierre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS liquidaciones_servicio_prod_detjf (
    id             INT NOT NULL AUTO_INCREMENT,
    id_liquidacion INT NOT NULL,
    cierre         VARCHAR(11) NOT NULL,
    servicio       VARCHAR(20) NULL COMMENT 'serviciosjf.codigo de origen (NULL = no se pudo rastrear)',
    id_osp         INT NULL COMMENT 'ordenes_servicio_prodjf.id; NULL = SIN OSP',
    osp_numero     VARCHAR(12) NULL,
    modelo         VARCHAR(20) NULL,
    nombre         VARCHAR(100) NULL,
    cod_color      VARCHAR(10) NULL,
    color          VARCHAR(50) NULL,
    t1 INT NOT NULL DEFAULT 0, t2 INT NOT NULL DEFAULT 0, t3 INT NOT NULL DEFAULT 0, t4 INT NOT NULL DEFAULT 0,
    t5 INT NOT NULL DEFAULT 0, t6 INT NOT NULL DEFAULT 0, t7 INT NOT NULL DEFAULT 0, t8 INT NOT NULL DEFAULT 0,
    cantidad       INT NOT NULL DEFAULT 0,
    precio_doc     DECIMAL(12,4) NOT NULL DEFAULT 0 COMMENT 'precio por docena (precio_serviciojf)',
    importe        DECIMAL(14,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_lspd_liq (id_liquidacion),
    KEY idx_lspd_servicio (servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Marca en el cierre: a qué liquidación pertenece (NULL = aún sin liquidar)
ALTER TABLE cierresjf
    ADD COLUMN IF NOT EXISTS liquidacion INT NULL COMMENT 'liquidaciones_servicio_prodjf.id';

ALTER TABLE cierresjf
    ADD INDEX IF NOT EXISTS idx_cierres_liquidacion (liquidacion);

SHOW COLUMNS FROM cierresjf LIKE 'liquidacion';
