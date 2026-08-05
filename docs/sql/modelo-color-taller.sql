-- =============================================================================
-- Taller por defecto por modelo (y opcionalmente color)
-- =============================================================================
-- Configuración editable: modelo [+ color] → sectorjf (taller).
-- - Sin color (cod_color = '') = regla general del modelo.
-- - Con color = excepción / regla específica.
-- Resolución futura (aún no cableada al flujo operativo):
--   1) buscar modelo + color exacto
--   2) si no hay, modelo + '' (general)
-- Ejecutar en copia de BD antes de producción.
-- =============================================================================

CREATE TABLE IF NOT EXISTS modelo_color_tallerjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    modelo          VARCHAR(50)  NOT NULL COMMENT 'modelojf.modelo',
    cod_color       VARCHAR(10)  NOT NULL DEFAULT '' COMMENT 'colorjf.cod_color; vacío = todo el modelo',
    nom_color       VARCHAR(100) NULL COMMENT 'denormalizado para listados',
    cod_sector      VARCHAR(10)  NOT NULL COMMENT 'sectorjf.cod_sector',
    estado          TINYINT(1)   NOT NULL DEFAULT 1,
    observacion     VARCHAR(255) NULL,
    usureg          VARCHAR(50)  NULL,
    fecreg          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usumod          VARCHAR(50)  NULL,
    fecmod          DATETIME     NULL,
    UNIQUE KEY uk_mct_modelo_color (modelo, cod_color),
    KEY idx_mct_sector (cod_sector),
    KEY idx_mct_estado (estado),
    KEY idx_mct_modelo (modelo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Taller (sector) por defecto por modelo y opcionalmente color';
