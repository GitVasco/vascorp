-- Metas mensuales por vendedor (venta y cobranza)
-- Ejecutar en la BD vasco

CREATE TABLE IF NOT EXISTS metas_vendedorjf (
    id INT(11) NOT NULL AUTO_INCREMENT,
    cod_vendedor VARCHAR(10) NOT NULL COMMENT 'Código maestrajf TVEND',
    anio SMALLINT NOT NULL,
    mes TINYINT NOT NULL COMMENT '1-12',
    meta_venta DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    meta_cobranza DECIMAL(14,2) NULL DEFAULT NULL COMMENT 'NULL = sin meta de cobranza aún',
    usuario INT(11) NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_meta_vendedor_periodo (cod_vendedor, anio, mes),
    KEY idx_meta_periodo (anio, mes),
    KEY idx_meta_vendedor (cod_vendedor)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Cuotas/metas mensuales por vendedor';
