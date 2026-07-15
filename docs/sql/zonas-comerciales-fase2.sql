-- =============================================================================
-- Zonas comerciales — Fase 2
-- =============================================================================
-- Relación muchos-a-muchos: zona y vendedor (código maestrajf TVEND).
-- Una zona puede tener varios vendedores. Un vendedor puede cubrir varias zonas.
-- =============================================================================

CREATE TABLE IF NOT EXISTS zonas_comerciales_vendedoresjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_zona         INT          NOT NULL COMMENT 'zonas_comercialesjf.id',
    cod_vendedor    VARCHAR(20)  NOT NULL COMMENT 'Codigo vendedor maestrajf TVEND',
    usureg          VARCHAR(50)  NULL,
    fecreg          DATETIME     NULL,
    UNIQUE KEY uk_zona_vend (id_zona, cod_vendedor),
    KEY idx_zona_vend_zona (id_zona),
    KEY idx_zona_vend_vendedor (cod_vendedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Vendedores asignados a zonas comerciales'
;
