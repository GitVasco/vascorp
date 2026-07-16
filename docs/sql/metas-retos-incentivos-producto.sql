-- =============================================================================
-- Metas / retos — múltiples incentivos de producto (modelo / modelo+color / artículo)
-- =============================================================================
-- 1) Crear tabla hija
-- 2) Migrar modelo_especial legacy → una fila hija por reto
-- 3) Consultas de verificación (comparar avance/comisión vs columnas antiguas)
--
-- Regla de negocio: modelo+color = todas las tallas de ese color.
-- Ejecutar respaldo de metas_retos_vendedorjf antes. Probar en copia.
-- Las columnas legacy se conservan como respaldo de lectura; no usan los cálculos nuevos.
-- =============================================================================

CREATE TABLE IF NOT EXISTS metas_retos_incentivos_productojf (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    id_meta_reto            INT NOT NULL COMMENT 'metas_retos_vendedorjf.id',
    tipo_objetivo           VARCHAR(20) NOT NULL COMMENT 'modelo | modelo_color | articulo',
    modelo                  VARCHAR(50) NULL COMMENT 'Obligatorio para modelo y modelo_color',
    cod_color               VARCHAR(30) NULL COMMENT 'Obligatorio para modelo_color',
    articulo                VARCHAR(80) NULL COMMENT 'Obligatorio para articulo',
    unidad_meta             VARCHAR(15) NOT NULL DEFAULT 'docenas' COMMENT 'unidades | docenas',
    meta_cantidad           DECIMAL(12,2) NOT NULL,
    comision_pct            DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT '% sobre venta objetivo',
    cumplimiento            VARCHAR(20) NOT NULL DEFAULT 'todo_nada' COMMENT 'todo_nada | prorrata',
    orden                   SMALLINT NOT NULL DEFAULT 0,
    observacion             VARCHAR(255) NULL,
    usuario                 INT NULL,
    fecreg                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecmod                  DATETIME NULL,
    KEY idx_mrip_reto (id_meta_reto),
    KEY idx_mrip_modelo_color (modelo, cod_color),
    KEY idx_mrip_articulo (articulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Incentivos de producto de una meta/reto mensual por vendedor';

-- -----------------------------------------------------------------------------
-- Migración: un modelo especial → un incentivo tipo modelo (docenas)
-- Solo inserta si aún no hay filas hijas para ese reto (idempotente).
-- -----------------------------------------------------------------------------
INSERT INTO metas_retos_incentivos_productojf (
    id_meta_reto,
    tipo_objetivo,
    modelo,
    cod_color,
    articulo,
    unidad_meta,
    meta_cantidad,
    comision_pct,
    cumplimiento,
    orden,
    observacion,
    usuario,
    fecreg
)
SELECT
    r.id,
    'modelo',
    TRIM(r.modelo_especial),
    NULL,
    NULL,
    'docenas',
    IFNULL(r.meta_docenas_especial, 0),
    IFNULL(r.comision_modelo_esp_pct, 0),
    IFNULL(NULLIF(TRIM(r.cumplimiento_modelo_esp), ''), 'todo_nada'),
    0,
    'Migrado desde modelo_especial',
    r.usuario,
    NOW()
FROM metas_retos_vendedorjf r
WHERE TRIM(IFNULL(r.modelo_especial, '')) <> ''
  AND IFNULL(r.meta_docenas_especial, 0) > 0
  AND NOT EXISTS (
        SELECT 1
        FROM metas_retos_incentivos_productojf i
        WHERE i.id_meta_reto = r.id
    );

-- -----------------------------------------------------------------------------
-- Verificación: conteos y comparación básica legacy vs hija
-- -----------------------------------------------------------------------------
-- SELECT
--     (SELECT COUNT(*) FROM metas_retos_vendedorjf WHERE TRIM(IFNULL(modelo_especial,'')) <> '') AS retos_con_especial,
--     (SELECT COUNT(*) FROM metas_retos_incentivos_productojf WHERE observacion = 'Migrado desde modelo_especial') AS migrados;

-- SELECT r.id, r.cod_vendedor, r.anio, r.mes,
--        r.modelo_especial, r.meta_docenas_especial, r.comision_modelo_esp_pct,
--        i.modelo, i.meta_cantidad, i.comision_pct, i.unidad_meta
-- FROM metas_retos_vendedorjf r
-- INNER JOIN metas_retos_incentivos_productojf i ON i.id_meta_reto = r.id
-- WHERE TRIM(IFNULL(r.modelo_especial, '')) <> ''
--   AND (
--         TRIM(r.modelo_especial) <> TRIM(IFNULL(i.modelo, ''))
--      OR IFNULL(r.meta_docenas_especial, 0) <> IFNULL(i.meta_cantidad, 0)
--      OR IFNULL(r.comision_modelo_esp_pct, 0) <> IFNULL(i.comision_pct, 0)
--   );
