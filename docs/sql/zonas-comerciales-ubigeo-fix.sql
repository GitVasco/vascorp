-- =============================================================================
-- FIX: mapear zonas con tabla `ubigeo` (la que usan los clientes)
-- =============================================================================
-- Contexto: el seed inicial usó `ubigeojf`, que solo tiene departamentos
-- (sin Provincia/Distrito). Eso falló en 7 queries y dejó ~75 reglas malas.
-- Este script limpia reglas y vuelve a cargar desde `ubigeo`.
-- =============================================================================

-- Quitar reglas incorrectas (departamentos 01, 02, …)
DELETE FROM zonas_comerciales_ubigeojf;

-- Helper: solo filas de distrito (Distrito no vacío, código de 6 dígitos)
-- Columnas reales: Codigo, Departamento, Provincia, Distrito

-- Callao completo
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'CALLAO'
WHERE UPPER(TRIM(u.Departamento)) = 'CALLAO'
  AND TRIM(IFNULL(u.Distrito, '')) <> ''
  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6;

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

-- Verificación rápida (opcional, solo SELECT)
-- SELECT z.codigo, z.nombre, COUNT(*) total
-- FROM zonas_comerciales_ubigeojf r
-- INNER JOIN zonas_comercialesjf z ON z.id = r.id_zona
-- GROUP BY z.codigo, z.nombre
-- ORDER BY z.orden;
