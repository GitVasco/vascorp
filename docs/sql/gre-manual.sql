-- =============================================================================
-- GRE Remitente manual (traslados, compras, otros) — independiente de ventajf
-- =============================================================================
-- Ejecutar en BD vasco. Idempotente (IF NOT EXISTS / INSERT IGNORE).
-- La serie T001 es una semilla: confirmar con contabilidad/EFACT antes de usar
-- y que NO exista en talonariosjf.serie_guias.
-- =============================================================================

CREATE TABLE IF NOT EXISTS gre_manual_seriejf (
    id              INT(11) NOT NULL AUTO_INCREMENT,
    serie           VARCHAR(4) NOT NULL COMMENT 'Debe iniciar con T (ej. T001)',
    correlativo     INT(11) NOT NULL DEFAULT 0 COMMENT 'Último número usado',
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    tipo            VARCHAR(12) NOT NULL DEFAULT 'ELECTRONICA' COMMENT 'ELECTRONICA (se envía a EFACT) o INTERNA',
    PRIMARY KEY (id),
    UNIQUE KEY uk_gre_serie (serie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Series de GRE manual (aparte de talonariosjf)';

INSERT IGNORE INTO gre_manual_seriejf (serie, correlativo, activo, tipo) VALUES ('T001', 0, 1, 'ELECTRONICA');
INSERT IGNORE INTO gre_manual_seriejf (serie, correlativo, activo, tipo) VALUES ('I001', 0, 1, 'INTERNA');

CREATE TABLE IF NOT EXISTS gre_manualjf (
    id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
    serie               VARCHAR(4) NOT NULL,
    numero              INT(11) NOT NULL,
    documento           VARCHAR(13) NOT NULL COMMENT 'T001-00000001',
    tipo                VARCHAR(12) NOT NULL DEFAULT 'ELECTRONICA' COMMENT 'ELECTRONICA o INTERNA (según la serie)',
    doc_interno         VARCHAR(13) NULL COMMENT 'Número interno original si la guía se convirtió a electrónica',
    fecha_emision       DATE NOT NULL,
    fecha_traslado      DATE NOT NULL,
    motivo_cod          VARCHAR(2) NOT NULL COMMENT 'Catálogo 20: 01,02,04,13,14',
    motivo_desc         VARCHAR(100) NOT NULL,
    modalidad           VARCHAR(2) NOT NULL COMMENT '01 público, 02 privado',
    peso_kg             DECIMAL(12,3) NOT NULL DEFAULT 0,
    bultos              INT(11) NULL,
    observaciones       VARCHAR(250) NULL,
    -- Destinatario
    dest_origen         VARCHAR(10) NOT NULL DEFAULT 'MANUAL' COMMENT 'CLIENTE, PROVEEDOR, EMPRESA, MANUAL',
    dest_codigo         VARCHAR(20) NULL,
    dest_nombre         VARCHAR(100) NOT NULL,
    dest_tipo_doc       VARCHAR(1) NOT NULL COMMENT 'Catálogo 06',
    dest_doc            VARCHAR(15) NOT NULL,
    dest_email          VARCHAR(100) NULL,
    -- Punto de partida
    par_ubigeo          VARCHAR(6) NOT NULL,
    par_direccion       VARCHAR(100) NOT NULL,
    par_dpto            VARCHAR(30) NOT NULL,
    par_prov            VARCHAR(30) NOT NULL,
    par_dist            VARCHAR(30) NOT NULL,
    -- Punto de llegada
    lle_ubigeo          VARCHAR(6) NOT NULL,
    lle_direccion       VARCHAR(100) NOT NULL,
    lle_dpto            VARCHAR(30) NOT NULL,
    lle_prov            VARCHAR(30) NOT NULL,
    lle_dist            VARCHAR(30) NOT NULL,
    -- Transporte
    transp_ruc          VARCHAR(11) NULL,
    transp_nombre       VARCHAR(100) NULL,
    transp_mtc          VARCHAR(20) NULL,
    chofer_tipo_doc     VARCHAR(1) NULL,
    chofer_doc          VARCHAR(11) NULL,
    chofer_nombres      VARCHAR(50) NULL,
    chofer_apellidos    VARCHAR(50) NULL,
    chofer_licencia     VARCHAR(20) NULL,
    placa               VARCHAR(8) NULL,
    docs_rel            TEXT NULL COMMENT 'JSON [{tipo:"06",numero:"..."}]',
    -- Estado
    estado              VARCHAR(10) NOT NULL DEFAULT 'GENERADO' COMMENT 'GENERADO, ENVIADO, ANULADO',
    archivo_csv         VARCHAR(80) NULL,
    fecha_envio         DATETIME NULL,
    usuario_registro    VARCHAR(50) NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_gre_documento (documento),
    KEY idx_gre_fecha (fecha_emision),
    KEY idx_gre_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='GRE remitente manual. No mueve stock.';

CREATE TABLE IF NOT EXISTS gre_manual_detjf (
    id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
    id_gre              BIGINT(20) NOT NULL,
    item                INT(11) NOT NULL,
    origen              VARCHAR(10) NOT NULL DEFAULT 'MANUAL' COMMENT 'MODELO, ARTICULO, MP, MANUAL',
    codigo              VARCHAR(16) NULL,
    descripcion         VARCHAR(250) NOT NULL,
    unidad_cod          VARCHAR(3) NOT NULL DEFAULT 'C62',
    unidad_desc         VARCHAR(15) NOT NULL DEFAULT 'PIEZAS',
    cantidad            DECIMAL(14,3) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_grd_gre (id_gre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Ítems de GRE manual';
