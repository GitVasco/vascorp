-- Catálogo de factores externos (reutilizables) + vínculo a líneas.
-- Ejecutar en BD después de proyeccion-comercial-modelos.sql

CREATE TABLE IF NOT EXISTS proyeccion_comercial_factor_catalogojf (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    tipo                  VARCHAR(40) NOT NULL,
    titulo                VARCHAR(120) NOT NULL,
    descripcion           VARCHAR(1000) NULL,
    fecha_desde           DATE NULL,
    fecha_hasta           DATE NULL,
    ajuste_unidades_default INT NOT NULL DEFAULT 0 COMMENT 'Sugerido al vincular; se puede cambiar por línea',
    impacto_pct_default   DECIMAL(10,4) NULL,
    canal_publicidad      VARCHAR(80) NULL,
    inversion_publicidad  DECIMAL(14,2) NULL,
    referencia_evidencia  VARCHAR(500) NULL,
    activo                TINYINT(1) NOT NULL DEFAULT 1,
    creado_por            INT NULL,
    creado_en             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_por       INT NULL,
    actualizado_en        DATETIME NULL,
    KEY idx_pcfc_activo_tipo (activo, tipo),
    KEY idx_pcfc_fechas (fecha_desde, fecha_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Vínculo opcional línea ← catálogo (ignorar error si la columna ya existe)
-- ALTER TABLE proyeccion_comercial_factorjf
--     ADD COLUMN id_catalogo INT NULL AFTER id_proyeccion_modelo,
--     ADD KEY idx_pcf_catalogo (id_catalogo);

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'proyeccion_comercial_factorjf'
      AND COLUMN_NAME = 'id_catalogo'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE proyeccion_comercial_factorjf ADD COLUMN id_catalogo INT NULL AFTER id_proyeccion_modelo, ADD KEY idx_pcf_catalogo (id_catalogo)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ejemplos (solo si aún no existen por título)
INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'CAMPANA' AS tipo,
           'Campaña Fiestas Patrias' AS titulo,
           'Impulso comercial julio (feriado / promociones).' AS descripcion,
           20 AS ajuste_unidades_default,
           10.0000 AS impacto_pct_default,
           1 AS activo,
           NOW() AS creado_en
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Campaña Fiestas Patrias' AND c.activo = 1
);

INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'CAMPANA',
           'Black Friday / Cyber',
           'Picos de demanda por campañas de descuento online.',
           35,
           15.0000,
           1,
           NOW()
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Black Friday / Cyber' AND c.activo = 1
);

INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'PRECIO',
           'Ajuste de precio lista',
           'Cambio de precio que puede subir o bajar la demanda.',
           -10,
           -5.0000,
           1,
           NOW()
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Ajuste de precio lista' AND c.activo = 1
);

INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'LANZAMIENTO',
           'Lanzamiento / relanzamiento',
           'Modelo nuevo o relanzado: empujar unidades iniciales.',
           50,
           NULL,
           1,
           NOW()
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Lanzamiento / relanzamiento' AND c.activo = 1
);

INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'EVENTO',
           'Temporada escolar',
           'Estacionalidad marzo–abril (demanda escolar).',
           15,
           8.0000,
           1,
           NOW()
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Temporada escolar' AND c.activo = 1
);

INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'PUBLICIDAD',
           'Pauta redes / TV',
           'Inversión publicitaria puntual sobre el modelo.',
           12,
           5.0000,
           1,
           NOW()
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Pauta redes / TV' AND c.activo = 1
);

INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'DISPONIBILIDAD',
           'Quiebre de stock esperado',
           'Restringe proyección si no habrá inventario suficiente.',
           -25,
           NULL,
           1,
           NOW()
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Quiebre de stock esperado' AND c.activo = 1
);

INSERT INTO proyeccion_comercial_factor_catalogojf
    (tipo, titulo, descripcion, ajuste_unidades_default, impacto_pct_default, activo, creado_en)
SELECT * FROM (
    SELECT 'MERCADO',
           'Competencia agresiva',
           'Presión de mercado / guerra de precios en la categoría.',
           -15,
           -8.0000,
           1,
           NOW()
) x
WHERE NOT EXISTS (
    SELECT 1 FROM proyeccion_comercial_factor_catalogojf c
    WHERE c.titulo = 'Competencia agresiva' AND c.activo = 1
);
