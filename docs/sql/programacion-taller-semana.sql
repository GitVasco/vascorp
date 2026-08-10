-- =============================================================================
-- Programación semanal por taller (lunes–domingo)
-- =============================================================================
-- Guarda qué modelo/color se programa por semana y taller, a partir de saldos
-- de almacén de corte + órdenes de corte (suma de todas las tallas), con nivel.
-- Ejecutar en copia de BD antes de producción.
-- =============================================================================

CREATE TABLE IF NOT EXISTS programacion_taller_semanajf (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    anio                SMALLINT     NOT NULL COMMENT 'Año ISO de la semana',
    semana              TINYINT      NOT NULL COMMENT 'Nº semana 1-53 (lunes-domingo)',
    fecha_inicio        DATE         NOT NULL COMMENT 'Lunes',
    fecha_fin           DATE         NOT NULL COMMENT 'Domingo',
    articulo            VARCHAR(50)  NULL COMMENT 'legado; identidad = modelo+color',
    modelo              VARCHAR(50)  NOT NULL,
    cod_color           VARCHAR(10)  NOT NULL DEFAULT '',
    color               VARCHAR(100) NULL,
    cod_talla           VARCHAR(10)  NULL COMMENT 'no se usa (programación por color)',
    talla               VARCHAR(50)  NULL COMMENT 'no se usa (programación por color)',
    nombre              VARCHAR(255) NULL,
    cod_sector          VARCHAR(10)  NOT NULL COMMENT 'sectorjf.cod_sector',
    cantidad            INT          NOT NULL DEFAULT 0,
    saldo_alm_corte     INT          NULL COMMENT 'snapshot al programar (suma tallas)',
    saldo_ord_corte     INT          NULL COMMENT 'snapshot al programar (suma tallas)',
    nivel               VARCHAR(30)  NOT NULL COMMENT 'critico|urgente|avanzar|prioridad|campana',
    urg_plan            DECIMAL(10,2) NULL,
    observacion         VARCHAR(255) NULL,
    estado              TINYINT(1)   NOT NULL DEFAULT 1,
    usureg              VARCHAR(50)  NULL,
    fecreg              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usumod              VARCHAR(50)  NULL,
    fecmod              DATETIME     NULL,
    UNIQUE KEY uk_pts_semana_modelo_color_taller (anio, semana, modelo, cod_color, cod_sector),
    KEY idx_pts_taller_semana (cod_sector, anio, semana),
    KEY idx_pts_nivel (nivel),
    KEY idx_pts_fechas (fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Programación semanal por modelo/color y taller';

-- -----------------------------------------------------------------------------
-- Si la tabla ya existía con unicidad por artículo, migrar:
-- -----------------------------------------------------------------------------
-- ALTER TABLE programacion_taller_semanajf
--   MODIFY articulo VARCHAR(50) NULL COMMENT 'legado; identidad = modelo+color',
--   MODIFY modelo VARCHAR(50) NOT NULL,
--   MODIFY cod_color VARCHAR(10) NOT NULL DEFAULT '';
--
-- ALTER TABLE programacion_taller_semanajf
--   DROP INDEX uk_pts_semana_art_taller;
--
-- ALTER TABLE programacion_taller_semanajf
--   ADD UNIQUE KEY uk_pts_semana_modelo_color_taller (anio, semana, modelo, cod_color, cod_sector);

-- =============================================================================
-- Bandeja de prioridad (sin semana): priorizar primero, destinar a semana después
-- =============================================================================
CREATE TABLE IF NOT EXISTS programacion_taller_prioridadjf (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    modelo              VARCHAR(50)  NOT NULL,
    cod_color           VARCHAR(10)  NOT NULL DEFAULT '',
    color               VARCHAR(100) NULL,
    nombre              VARCHAR(255) NULL,
    cod_sector          VARCHAR(10)  NOT NULL COMMENT 'sectorjf.cod_sector',
    cantidad            INT          NOT NULL DEFAULT 0,
    saldo_alm_corte     INT          NULL,
    saldo_ord_corte     INT          NULL,
    nivel               VARCHAR(30)  NOT NULL COMMENT 'critico|urgente|avanzar|prioridad|campana',
    urg_plan            DECIMAL(10,2) NULL,
    observacion         VARCHAR(255) NULL,
    estado              TINYINT(1)   NOT NULL DEFAULT 1,
    usureg              VARCHAR(50)  NULL,
    fecreg              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usumod              VARCHAR(50)  NULL,
    fecmod              DATETIME     NULL,
    UNIQUE KEY uk_ptp_modelo_color_taller (modelo, cod_color, cod_sector),
    KEY idx_ptp_nivel (nivel),
    KEY idx_ptp_taller (cod_sector),
    KEY idx_ptp_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Prioridad de programación sin semana asignada';
