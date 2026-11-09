-- =============================================================================
-- Comprobantes especiales (boleta/factura fuera del flujo de ventas). Tipo actual: GRATUITA
-- Independiente de ventajf / movimientosjf / articulojf. No mueve stock.
-- Diseño: docs/efact/COMPROBANTES-ESPECIALES-diseno.md
-- =============================================================================
-- Ejecutar en BD vasco. Idempotente (IF NOT EXISTS / INSERT IGNORE).
-- Las series BG01 (boleta) y FG01 (factura) son PROVISIONALES para pruebas:
-- confirmar con Contabilidad/eFact antes de emitir en producción.
-- =============================================================================

CREATE TABLE IF NOT EXISTS comp_especial_seriejf (
    id              INT(11) NOT NULL AUTO_INCREMENT,
    tipo_doc        VARCHAR(2) NOT NULL COMMENT '01 factura, 03 boleta',
    serie           VARCHAR(4) NOT NULL COMMENT 'F... para 01, B... para 03',
    correlativo     INT(11) NOT NULL DEFAULT 0 COMMENT 'Último número usado',
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uk_obs_serie (serie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Series de comprobantes especiales (aparte de talonariosjf)';

INSERT IGNORE INTO comp_especial_seriejf (tipo_doc, serie, correlativo, activo) VALUES ('03', 'BG01', 0, 1);
INSERT IGNORE INTO comp_especial_seriejf (tipo_doc, serie, correlativo, activo) VALUES ('01', 'FG01', 0, 1);

CREATE TABLE IF NOT EXISTS comp_especialjf (
    id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
    tipo_doc            VARCHAR(2) NOT NULL COMMENT '01 factura, 03 boleta',
    tipo_operacion      VARCHAR(20) NOT NULL DEFAULT 'GRATUITA' COMMENT 'Tipo de comprobante especial (hoy solo GRATUITA)',
    serie               VARCHAR(4) NOT NULL,
    numero              INT(11) NOT NULL,
    documento           VARCHAR(13) NOT NULL COMMENT 'BG01-00000001',
    fecha_emision       DATE NOT NULL,
    moneda              VARCHAR(3) NOT NULL DEFAULT 'PEN',
    lote                VARCHAR(20) NULL COMMENT 'Agrupa los comprobantes creados juntos',
    categoria           VARCHAR(12) NOT NULL COMMENT 'TRABAJADOR, CLIENTE, PROVEEDOR, OTRO',
    afectacion          VARCHAR(2) NOT NULL COMMENT 'Catálogo 07: 11-16 gravado retiro (IGV informativo), 31-37 inafecto',
    motivo              VARCHAR(150) NULL COMMENT 'Texto del motivo (ej. ENTREGA A TRABAJADORES)',
    observaciones       VARCHAR(200) NULL,
    receptor_tipo_doc   VARCHAR(1) NOT NULL COMMENT '1 DNI, 6 RUC',
    receptor_doc        VARCHAR(15) NOT NULL,
    receptor_nombre     VARCHAR(100) NOT NULL,
    receptor_direccion  VARCHAR(100) NULL,
    receptor_ubigeo     VARCHAR(6) NULL,
    receptor_dpto       VARCHAR(30) NULL,
    receptor_prov       VARCHAR(30) NULL,
    receptor_dist       VARCHAR(30) NULL,
    receptor_email      VARCHAR(100) NULL,
    total_gratuito      DECIMAL(14,2) NOT NULL DEFAULT 0,
    igv_informativo     DECIMAL(14,2) NOT NULL DEFAULT 0,
    estado              VARCHAR(10) NOT NULL DEFAULT 'GENERADO' COMMENT 'GENERADO, ENVIADO, BAJA, ANULADO',
    archivo_csv         VARCHAR(80) NULL,
    fecha_envio         DATETIME NULL,
    fecha_baja          DATETIME NULL,
    aprobado_por        VARCHAR(60) NULL,
    usuario_registro    VARCHAR(50) NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_obs_documento (documento),
    KEY idx_obs_fecha (fecha_emision),
    KEY idx_obs_estado (estado),
    KEY idx_obs_lote (lote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Comprobantes especiales. No mueve stock.';

CREATE TABLE IF NOT EXISTS comp_especial_detjf (
    id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
    id_comprobante         BIGINT(20) NOT NULL,
    item                INT(11) NOT NULL,
    codigo              VARCHAR(30) NULL,
    descripcion         VARCHAR(250) NOT NULL,
    unidad_cod          VARCHAR(3) NOT NULL DEFAULT 'C62',
    cantidad            DECIMAL(14,3) NOT NULL,
    valor_ref_unit      DECIMAL(14,2) NOT NULL COMMENT 'Valor referencial unitario (sin IGV)',
    valor_venta         DECIMAL(14,2) NOT NULL COMMENT 'valor_ref_unit x cantidad',
    igv_informativo     DECIMAL(14,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_obsd_obs (id_comprobante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Ítems de comprobante especial';
