-- =============================================================================
-- Series de documentos fiscales ↔ marcas (N:N)
-- =============================================================================
-- Mantenimiento sobre talonariosjf. El amarre a marcas aún no filtra facturación.
-- Ejecutar en copia de BD antes de producción.
-- =============================================================================

CREATE TABLE IF NOT EXISTS serie_documento_marcajf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_talonario    INT NOT NULL COMMENT 'talonariosjf.id',
    tipo_documento  VARCHAR(2) NOT NULL COMMENT '01 factura, 03 boleta, 07 NC, 08 ND, 09 proforma, 90 guía',
    id_marca        INT NOT NULL COMMENT 'marcasjf.id',
    usureg          VARCHAR(50) NULL,
    fecreg          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sdm_talonario_tipo_marca (id_talonario, tipo_documento, id_marca),
    KEY idx_sdm_marca (id_marca),
    KEY idx_sdm_talonario_tipo (id_talonario, tipo_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Marcas asociadas a cada serie de documento en talonariosjf';
