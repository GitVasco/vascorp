-- =============================================================================
-- DEPRECADO — no usar en instalaciones nuevas
-- =============================================================================
-- Antes se quitaba ubigeo de La Victoria y Lima Cercado (tratándolos como Gamarra).
-- Modelo correcto: esos distritos SÍ van a LIM_VICTORIA / LIM_CERCADO.
-- Usar en su lugar: zonas-comerciales-victoria-cercado-ubigeo.sql
-- Solo Distribuidores y Gamarra (LIM_ECONOMICA) quedan sin ubigeo.
-- =============================================================================

DELETE r FROM zonas_comerciales_ubigeojf r
INNER JOIN ubigeo u ON u.Codigo = r.cod_ubi
WHERE UPPER(TRIM(u.Departamento)) = 'LIMA'
  AND UPPER(TRIM(u.Provincia)) = 'LIMA'
  AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(u.Distrito),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N')) IN (
      'LA VICTORIA',
      'LIMA (CERCADO)'
  );
