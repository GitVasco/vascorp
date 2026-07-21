-- =============================================================================
-- Recetas por modelo (explosión de materiales) — Fase 1
-- =============================================================================
-- MariaDB 10.1+ / InnoDB. Sin FOREIGN KEYs (integridad en aplicación).
-- No altera tarjetasjf ni detalles_tarjetajf.
--
-- Referencias lógicas (sin FK):
--   modelo            -> articulojf.modelo / modelojf.modelo (varchar 10)
--   mp_* / mat_pri    -> producto.CodPro (char 5)
--   codigo_sublinea   -> LEFT(producto.CodFab, 6) / FamPro (ej. BAL002)
--   cod_color/talla   -> articulojf.cod_color / cod_talla (varchar 2)
--   unidad            -> Tabla_M_Detalle TUND.Des_Corta (ej. MTS, UND)
--   id_usuario_*      -> usuariosjf.id
--
-- Variantes: usar '' (cadena vacía) en vez de NULL en cod_color/cod_talla
-- para que el UNIQUE funcione en MariaDB 10.1.
-- Una sola PUBLICADA por modelo: validar en aplicación.
-- =============================================================================

CREATE TABLE IF NOT EXISTS recetas_modelo (
    id                      INT             NOT NULL AUTO_INCREMENT,
    modelo                  VARCHAR(10)     NOT NULL COMMENT 'articulojf.modelo',
    version                 INT             NOT NULL DEFAULT 1 COMMENT 'Correlativo por modelo',
    estado                  VARCHAR(12)     NOT NULL DEFAULT 'BORRADOR'
                            COMMENT 'BORRADOR | PUBLICADA | ARCHIVADA',
    vigente_desde           DATE            NULL DEFAULT NULL,
    vigente_hasta           DATE            NULL DEFAULT NULL,
    id_usuario_crea         INT             NULL DEFAULT NULL,
    id_usuario_actualiza    INT             NULL DEFAULT NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NULL DEFAULT NULL
                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_recetas_modelo_version (modelo, version),
    KEY idx_recetas_modelo_estado (modelo, estado),
    KEY idx_recetas_modelo_vigencia (estado, vigente_desde, vigente_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Cabecera versionada de receta por modelo';

CREATE TABLE IF NOT EXISTS recetas_modelo_detalles (
    id                      INT             NOT NULL AUTO_INCREMENT,
    id_receta_modelo        INT             NOT NULL COMMENT 'recetas_modelo.id (sin FK)',
    orden                   INT             NOT NULL DEFAULT 1,
    nombre_rol              VARCHAR(80)     NOT NULL COMMENT 'Ej. Tela principal, Etiqueta',
    es_tela_principal       TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = tela que direcciona corte',
    codigo_sublinea         VARCHAR(6)      NULL DEFAULT NULL COMMENT 'FamPro / LEFT(CodFab,6)',
    regla_variante          VARCHAR(12)     NOT NULL DEFAULT 'GENERAL'
                            COMMENT 'GENERAL | COLOR | TALLA | COLOR_TALLA',
    unidad                  VARCHAR(10)     NULL DEFAULT NULL COMMENT 'TUND.Des_Corta',
    consumo_base            DECIMAL(12,8)   NULL DEFAULT NULL COMMENT 'Respaldo GENERAL',
    mp_base_codigo          VARCHAR(5)      NULL DEFAULT NULL COMMENT 'producto.CodPro respaldo',
    activo                  TINYINT(1)      NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_rmd_receta (id_receta_modelo, activo, orden),
    KEY idx_rmd_tela (id_receta_modelo, es_tela_principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Líneas / roles de insumos de una receta';

CREATE TABLE IF NOT EXISTS recetas_modelo_variantes (
    id                          INT             NOT NULL AUTO_INCREMENT,
    id_receta_modelo_detalle    INT             NOT NULL COMMENT 'recetas_modelo_detalles.id (sin FK)',
    cod_color                   VARCHAR(2)      NOT NULL DEFAULT '' COMMENT 'articulojf.cod_color; vacio si no aplica',
    cod_talla                   VARCHAR(2)      NOT NULL DEFAULT '' COMMENT 'articulojf.cod_talla; vacio si no aplica',
    mp_codigo                   VARCHAR(5)      NOT NULL COMMENT 'producto.CodPro final',
    consumo                     DECIMAL(12,8)   NULL DEFAULT NULL COMMENT 'NULL = usar consumo_base de la linea',
    observacion                 VARCHAR(255)    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_rmv_detalle_variante (id_receta_modelo_detalle, cod_color, cod_talla),
    KEY idx_rmv_mp (mp_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Excepciones / asignaciones por color, talla o color+talla';
