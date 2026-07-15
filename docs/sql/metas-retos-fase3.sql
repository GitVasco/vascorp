-- =============================================================================
-- Metas / retos mensuales por vendedor (Fase 3)
-- =============================================================================
-- Solo vendedores activos (estado_decisiones = 1) en la app.
-- Tipos MVP: monto ventas, clientes nuevos, modelos activos.
-- Comision: valores guardados para reporte futuro (Fase 5); aqui se configura.
-- cumplimiento_*: todo_nada | prorrata
-- =============================================================================

CREATE TABLE IF NOT EXISTS metas_retos_vendedorjf (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    cod_vendedor            VARCHAR(10)  NOT NULL COMMENT 'maestrajf TVEND',
    anio                    SMALLINT     NOT NULL,
    mes                     TINYINT      NOT NULL COMMENT '1-12',

    meta_monto              DECIMAL(14,2) NULL COMMENT 'Umbral soles venta',
    comision_monto_pct      DECIMAL(8,2)  NULL COMMENT '% sobre ventas al cumplir',
    comision_monto_fijo     DECIMAL(14,2) NULL COMMENT 'Monto fijo opcional al cumplir',
    cumplimiento_monto      VARCHAR(20)   NOT NULL DEFAULT 'todo_nada',

    meta_clientes           INT           NULL COMMENT 'Umbral clientes nuevos',
    comision_clientes_fijo  DECIMAL(14,2) NULL,
    cumplimiento_clientes   VARCHAR(20)   NOT NULL DEFAULT 'todo_nada',

    meta_modelos            INT           NULL COMMENT 'Umbral modelos distintos',
    comision_modelos_fijo   DECIMAL(14,2) NULL,
    cumplimiento_modelos    VARCHAR(20)   NOT NULL DEFAULT 'todo_nada',

    usuario                 INT           NULL,
    fecreg                  DATETIME      NULL,
    fecmod                  DATETIME      NULL,

    UNIQUE KEY uk_metas_retos_vend_per (cod_vendedor, anio, mes),
    KEY idx_metas_retos_periodo (anio, mes),
    KEY idx_metas_retos_vendedor (cod_vendedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Metas retos mensuales por vendedor activo'
;
