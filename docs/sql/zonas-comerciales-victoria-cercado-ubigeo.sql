-- =============================================================================
-- Ubigeos → La Victoria y Lima Cercado (zonas propias, no Z1/Z2/Z3)
-- =============================================================================
-- Autoasignación por distrito a LIM_VICTORIA / LIM_CERCADO.
-- Gamarra (LIM_ECONOMICA) y Distribuidores siguen solo manual.
-- Idempotente: ON DUPLICATE KEY UPDATE.
-- =============================================================================

INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_VICTORIA'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) = 'LA VICTORIA'
ON DUPLICATE KEY UPDATE id_zona = VALUES(id_zona), usureg = VALUES(usureg), fecreg = NOW();

INSERT INTO zonas_comerciales_ubigeojf (id_zona, cod_ubi, usureg, fecreg)
SELECT z.id, u.Codigo, 'sistema', NOW()
FROM ubigeo u
INNER JOIN zonas_comercialesjf z ON z.codigo = 'LIM_CERCADO'
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) = 'LIMA (CERCADO)'
ON DUPLICATE KEY UPDATE id_zona = VALUES(id_zona), usureg = VALUES(usureg), fecreg = NOW();
