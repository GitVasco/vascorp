-- =============================================================================
-- Migración ubigeos → Zona 1, Zona 2, Zona 3 (Lima reestructura)
-- =============================================================================
-- Solo distritos confirmados por Supervisión.
-- NO asigna: La Victoria, Lima Cercado (zonas manuales), ni los 14 distritos pendientes.
-- NO desactiva zonas viejas (LIM_NORTE, CALLAO, etc.) — hacerlo al final en UI.
-- Idempotente: ON DUPLICATE KEY UPDATE mueve el ubigeo a la zona nueva.
-- Ejecutar después de: zonas-comerciales-lima-zona-1-2-3.sql
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Zona 1 (PC) — Lima
-- -----------------------------------------------------------------------------
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_ZONA_1'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'PUENTE PIEDRA', 'COMAS', 'CARABAYLLO', 'LOS OLIVOS',
      'MAGDALENA DEL MAR', 'JESUS MARIA', 'SAN JUAN DE LURIGANCHO'
  )
ON DUPLICATE KEY UPDATE id_zona = VALUES(id_zona), usureg = VALUES(usureg), fecreg = NOW();

-- Zona 1 — Callao (provincia completa, códigos ubigeo de 6 dígitos)
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_ZONA_1'
WHERE UPPER(TRIM(u.Departamento)) = 'CALLAO'
  AND TRIM(IFNULL(u.Distrito, '')) <> ''
  AND CHAR_LENGTH(TRIM(u.Codigo)) = 6
ON DUPLICATE KEY UPDATE id_zona = VALUES(id_zona), usureg = VALUES(usureg), fecreg = NOW();

-- -----------------------------------------------------------------------------
-- Zona 2 (GS)
-- -----------------------------------------------------------------------------
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_ZONA_2'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'INDEPENDENCIA', 'SAN MARTIN DE PORRES', 'BRENA', 'LINCE',
      'PUEBLO LIBRE', 'SAN ISIDRO', 'SAN BORJA', 'SURQUILLO', 'MIRAFLORES'
  )
ON DUPLICATE KEY UPDATE id_zona = VALUES(id_zona), usureg = VALUES(usureg), fecreg = NOW();

-- -----------------------------------------------------------------------------
-- Zona 3 (JCD)
-- -----------------------------------------------------------------------------
INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_ZONA_3'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'LURIGANCHO', 'ATE', 'SANTA ANITA', 'LURIN', 'PACHACAMAC',
      'LA MOLINA', 'SANTIAGO DE SURCO', 'BARRANCO', 'CHORRILLOS',
      'SAN JUAN DE MIRAFLORES', 'VILLA EL SALVADOR', 'VILLA MARIA DEL TRIUNFO'
  )
ON DUPLICATE KEY UPDATE id_zona = VALUES(id_zona), usureg = VALUES(usureg), fecreg = NOW();

-- -----------------------------------------------------------------------------
-- Verificación (opcional)
-- -----------------------------------------------------------------------------
-- SELECT z.codigo, z.nombre, COUNT(*) AS ubigeos
-- FROM zonas_comerciales_ubigeojf r
-- INNER JOIN zonas_comercialesjf z ON z.id = r.id_zona
-- WHERE z.codigo IN ('LIM_ZONA_1', 'LIM_ZONA_2', 'LIM_ZONA_3')
-- GROUP BY z.codigo, z.nombre
-- ORDER BY z.codigo;
