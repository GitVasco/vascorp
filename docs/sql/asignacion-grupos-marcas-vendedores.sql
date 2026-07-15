-- =============================================================================
-- Asignación de grupos de marcas a vendedores — Fase 1
-- =============================================================================
-- Catálogo de grupos comerciales de marcas y asignación vendedor–grupo con vigencia.
-- Ejecutar en copia de BD antes de producción.
-- Carga inicial: docs/sql/asignacion-grupos-marcas-carga-inicial.sql
-- =============================================================================

CREATE TABLE IF NOT EXISTS grupos_marcas_comercialjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30) NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    descripcion     VARCHAR(255) NULL,
    estado          TINYINT(1) NOT NULL DEFAULT 1,
    usureg          VARCHAR(50) NULL,
    fecreg          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usumod          VARCHAR(50) NULL,
    fecmod          DATETIME NULL,
    UNIQUE KEY uk_gmc_codigo (codigo),
    KEY idx_gmc_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Grupos comerciales de marcas';

CREATE TABLE IF NOT EXISTS grupos_marcas_detallejf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_grupo_marca  INT NOT NULL COMMENT 'grupos_marcas_comercialjf.id',
    id_marca        INT NOT NULL COMMENT 'marcasjf.id',
    usureg          VARCHAR(50) NULL,
    fecreg          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_gmd_grupo_marca (id_grupo_marca, id_marca),
    KEY idx_gmd_marca (id_marca),
    KEY idx_gmd_grupo (id_grupo_marca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Marcas contenidas en cada grupo comercial';

CREATE TABLE IF NOT EXISTS vendedor_grupos_marcasjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cod_vendedor    VARCHAR(20) NOT NULL COMMENT 'maestrajf.codigo con tipo_dato=TVEND',
    id_grupo_marca  INT NOT NULL COMMENT 'grupos_marcas_comercialjf.id',
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NULL,
    estado          TINYINT(1) NOT NULL DEFAULT 1,
    observacion     VARCHAR(255) NULL,
    usureg          VARCHAR(50) NULL,
    fecreg          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usumod          VARCHAR(50) NULL,
    fecmod          DATETIME NULL,
    CONSTRAINT chk_vgm_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
    UNIQUE KEY uk_vgm_inicio (cod_vendedor, id_grupo_marca, fecha_inicio),
    KEY idx_vgm_vendedor_vigencia (cod_vendedor, estado, fecha_inicio, fecha_fin),
    KEY idx_vgm_grupo_vigencia (id_grupo_marca, estado, fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Grupos de marcas autorizados por vendedor con vigencia';
