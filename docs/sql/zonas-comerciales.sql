-- =============================================================================
-- Zonas comerciales (Fase 0)
-- =============================================================================
-- Catálogo de zonas + reglas ubigeo → zona (asignación automática por dirección).
-- Zona Económica (Gamarra) NO se autoasigna por ubigeo: solo override manual (Fase 1).
-- Ejecutar manualmente en la BD. Idempotente donde es posible.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) Catálogo de zonas
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS zonas_comercialesjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30)  NOT NULL,
    nombre          VARCHAR(120) NOT NULL,
    -- lima | peru_norte | peru_sur  (para los dos mapas)
    macrozona       VARCHAR(30)  NOT NULL DEFAULT 'lima',
    descripcion     TEXT         NULL,
    color           VARCHAR(20)  NULL COMMENT 'Color hex para UI/mapa',
    orden           INT          NOT NULL DEFAULT 0,
    estado          TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activa, 0=inactiva',
    usureg          VARCHAR(50)  NULL,
    fecreg          DATETIME     NULL,
    usumod          VARCHAR(50)  NULL,
    fecmod          DATETIME     NULL,
    UNIQUE KEY uk_zona_com_codigo (codigo),
    KEY idx_zona_com_macro (macrozona),
    KEY idx_zona_com_estado (estado),
    KEY idx_zona_com_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Catálogo de zonas comerciales';

-- -----------------------------------------------------------------------------
-- 2) Reglas ubigeo → zona (un ubigeo solo en una zona)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS zonas_comerciales_ubigeojf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_zona         INT          NOT NULL COMMENT 'zonas_comercialesjf.id',
    cod_ubi         VARCHAR(12)  NOT NULL COMMENT 'ubigeojf.cod_ubi',
    usureg          VARCHAR(50)  NULL,
    fecreg          DATETIME     NULL,
    UNIQUE KEY uk_zona_ubi_cod (cod_ubi),
    KEY idx_zona_ubi_zona (id_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Asignación automática distrito/ubigeo a zona comercial';

-- -----------------------------------------------------------------------------
-- 3) Semilla de zonas
-- -----------------------------------------------------------------------------
INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_CENTRO', 'Lima Centro Comercial', 'lima',
       'Centro comercial (La Victoria genérica, Cercado, Breña, etc.)',
       '#dd4b39', 10, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_CENTRO');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_NORTE', 'Lima Norte', 'lima', 'Distritos del norte de Lima Metropolitana',
       '#00a65a', 20, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_NORTE');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_ESTE', 'Lima Este', 'lima', 'Distritos del este de Lima Metropolitana',
       '#f39c12', 30, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_ESTE');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_SUR', 'Lima Sur', 'lima', 'Distritos del sur de Lima Metropolitana',
       '#00c0ef', 40, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_SUR');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_MODERNA', 'Lima Moderna', 'lima',
       'San Isidro, Surco, Miraflores, San Borja y afines (por ubicación)',
       '#605ca8', 50, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_MODERNA');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'LIM_ECONOMICA', 'Zona Económica (Gamarra)', 'lima',
       'Solo Gamarra. No se autoasigna por ubigeo; override manual en cliente/grupo.',
       '#d81b60', 60, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'LIM_ECONOMICA');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'CALLAO', 'Callao', 'lima', 'Toda la Provincia Constitucional del Callao',
       '#39cccc', 70, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'CALLAO');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'NORTE_CHICO', 'Norte Chico', 'lima',
       'Provincias Barranca, Huaral y Huaura (incluye Huacho)',
       '#3d9970', 80, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'NORTE_CHICO');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'PERU_NORTE', 'Norte del Perú', 'peru_norte',
       'Bloque norte del país (sin Lima/Callao/Norte Chico)',
       '#0073b7', 90, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'PERU_NORTE');

INSERT INTO zonas_comercialesjf (codigo, nombre, macrozona, descripcion, color, orden, estado, usureg, fecreg)
SELECT 'PERU_SUR', 'Sur del Perú', 'peru_sur',
       'Bloque sur/centro-sur del país (sin Lima/Callao/Norte Chico)',
       '#ff851b', 100, 1, 'sistema', NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zonas_comercialesjf WHERE codigo = 'PERU_SUR');

-- -----------------------------------------------------------------------------
-- 4) Semilla de reglas desde tabla `ubigeo` (la que usa ficha de clientes)
--     Columnas: Codigo, Departamento, Provincia, Distrito
--     NO usar ubigeojf (solo departamentos).
--     Si ya corriste el seed viejo, ejecuta también:
--       docs/sql/zonas-comerciales-ubigeo-fix.sql
-- -----------------------------------------------------------------------------

-- Callao completo
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'CALLAO'
WHERE UPPER(TRIM(u.Departamento)) = 'CALLAO'
  AND TRIM(IFNULL(u.Distrito, '')) <> ''
  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Norte Chico: Barranca, Huaral, Huaura
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'NORTE_CHICO'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Provincia),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'))
      IN ('BARRANCA', 'HUARAL', 'HUAURA')
  AND TRIM(IFNULL(u.Distrito, '')) <> ''
  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Lima Norte
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_NORTE'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'INDEPENDENCIA', 'LOS OLIVOS', 'COMAS', 'CARABAYLLO', 'PUENTE PIEDRA',
      'SAN MARTIN DE PORRES', 'ANCON', 'SANTA ROSA'
  )
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Lima Este
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_ESTE'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'ATE', 'SANTA ANITA', 'EL AGUSTINO', 'SAN JUAN DE LURIGANCHO',
      'LURIGANCHO', 'CHOSICA', 'CHACLACAYO', 'CIENEGUILLA'
  )
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Lima Sur
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_SUR'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'CHORRILLOS', 'SAN JUAN DE MIRAFLORES', 'VILLA EL SALVADOR',
      'VILLA MARIA DEL TRIUNFO', 'LURIN', 'PACHACAMAC',
      'PUNTA HERMOSA', 'PUNTA NEGRA', 'SAN BARTOLO', 'SANTA MARIA DEL MAR', 'PUCUSANA'
  )
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Lima Centro Comercial
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_CENTRO'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND (
      UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
          'LA VICTORIA', 'BRENA', 'RIMAC'
      )
      OR UPPER(TRIM(u.Distrito)) LIKE 'LIMA%'
  )
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Lima Moderna
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_MODERNA'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'SAN ISIDRO', 'SANTIAGO DE SURCO', 'LA MOLINA', 'JESUS MARIA', 'LINCE',
      'MAGDALENA DEL MAR', 'SURQUILLO', 'MIRAFLORES', 'BARRANCO', 'SAN BORJA',
      'SAN MIGUEL', 'PUEBLO LIBRE', 'SAN LUIS'
  )
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Norte del Perú
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'PERU_NORTE'
WHERE UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Departamento),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U')) IN (
      'TUMBES', 'PIURA', 'LAMBAYEQUE', 'LA LIBERTAD', 'CAJAMARCA', 'AMAZONAS',
      'SAN MARTIN', 'LORETO', 'UCAYALI', 'ANCASH'
  )
  AND TRIM(IFNULL(u.Distrito, '')) <> ''
  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Sur del Perú
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'PERU_SUR'
WHERE UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Departamento),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U')) IN (
      'ICA', 'AREQUIPA', 'MOQUEGUA', 'TACNA', 'PUNO', 'CUSCO', 'APURIMAC',
      'MADRE DE DIOS', 'AYACUCHO', 'HUANCAVELICA', 'JUNIN', 'HUANUCO', 'PASCO'
  )
  AND TRIM(IFNULL(u.Distrito, '')) <> ''
  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);

-- Otras provincias de Lima (Cañete, Canta, etc.) → Sur
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'PERU_SUR'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Provincia),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'CANETE', 'CANTA', 'OYON', 'YAUYOS', 'CAJATAMBO', 'HUAROCHIRI'
  )
  AND TRIM(IFNULL(u.Distrito, '')) <> ''
  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6
  AND NOT EXISTS (SELECT 1 FROM zonas_comerciales_ubigeojf r WHERE r.cod_ubi = u.Codigo);
