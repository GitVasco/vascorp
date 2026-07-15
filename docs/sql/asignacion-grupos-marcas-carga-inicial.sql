-- =============================================================================
-- Carga inicial — grupos X (Grupo Jackyform) / Y (RosaFlor), marcas y asignaciones
-- =============================================================================
-- Vigencia: primer día del mes actual.
-- Revisar salida de las validaciones antes de confirmar.
-- Ejecutar DESPUÉS de asignacion-grupos-marcas-vendedores.sql
-- =============================================================================

SET @fecha_inicio := DATE_FORMAT(CURDATE(), '%Y-%m-01');
SET @usuario_carga := 'carga_inicial';

-- ---------------------------------------------------------------------------
-- Validación: vendedores TVEND
-- ---------------------------------------------------------------------------
SELECT 'Vendedores ausentes en maestrajf (TVEND)' AS control,
       v.codigo AS cod_vendedor
FROM (
    SELECT '04' AS codigo UNION ALL SELECT '05' UNION ALL SELECT '19' UNION ALL
    SELECT '24j' UNION ALL SELECT '27' UNION ALL SELECT '31' UNION ALL
    SELECT '32' UNION ALL SELECT '30' UNION ALL SELECT '18a'
) v
LEFT JOIN maestrajf m
  ON m.codigo = v.codigo AND UPPER(m.tipo_dato) = 'TVEND'
WHERE m.codigo IS NULL;

-- ---------------------------------------------------------------------------
-- Validación: marcas por nombre
-- ---------------------------------------------------------------------------
SELECT 'Marcas no encontradas en marcasjf' AS control,
       n.nombre_marca
FROM (
    SELECT 'JACKYFORM' AS nombre_marca UNION ALL
    SELECT 'GUAPITAS' UNION ALL
    SELECT 'ROSALINDA' UNION ALL
    SELECT 'ROSITAS'
) n
LEFT JOIN marcasjf m ON UPPER(TRIM(m.marca)) = n.nombre_marca
WHERE m.id IS NULL;

-- ---------------------------------------------------------------------------
-- Grupos (idempotente por código)
-- ---------------------------------------------------------------------------
INSERT INTO grupos_marcas_comercialjf (codigo, nombre, descripcion, estado, usureg, fecreg)
SELECT 'X', 'Grupo Jackyform', 'Jackyform + Guapitas', 1, @usuario_carga, NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM grupos_marcas_comercialjf WHERE codigo = 'X');

INSERT INTO grupos_marcas_comercialjf (codigo, nombre, descripcion, estado, usureg, fecreg)
SELECT 'Y', 'RosaFlor', 'Rosalinda + Rositas', 1, @usuario_carga, NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM grupos_marcas_comercialjf WHERE codigo = 'Y');

UPDATE grupos_marcas_comercialjf
SET nombre = 'Grupo Jackyform',
    descripcion = 'Jackyform + Guapitas',
    usumod = @usuario_carga,
    fecmod = NOW()
WHERE codigo = 'X';

UPDATE grupos_marcas_comercialjf
SET nombre = 'RosaFlor',
    descripcion = 'Rosalinda + Rositas',
    usumod = @usuario_carga,
    fecmod = NOW()
WHERE codigo = 'Y';

SET @id_grupo_x := (SELECT id FROM grupos_marcas_comercialjf WHERE codigo = 'X' LIMIT 1);
SET @id_grupo_y := (SELECT id FROM grupos_marcas_comercialjf WHERE codigo = 'Y' LIMIT 1);

-- ---------------------------------------------------------------------------
-- Marcas por grupo
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO grupos_marcas_detallejf (id_grupo_marca, id_marca, usureg, fecreg)
SELECT @id_grupo_x, m.id, @usuario_carga, NOW()
FROM marcasjf m
WHERE UPPER(TRIM(m.marca)) IN ('JACKYFORM', 'GUAPITAS');

INSERT IGNORE INTO grupos_marcas_detallejf (id_grupo_marca, id_marca, usureg, fecreg)
SELECT @id_grupo_y, m.id, @usuario_carga, NOW()
FROM marcasjf m
WHERE UPPER(TRIM(m.marca)) IN ('ROSALINDA', 'ROSITAS');

-- ---------------------------------------------------------------------------
-- Asignaciones vendedor–grupo (idempotente por uk_vgm_inicio)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO vendedor_grupos_marcasjf
    (cod_vendedor, id_grupo_marca, fecha_inicio, fecha_fin, estado, observacion, usureg, fecreg)
SELECT v.codigo, @id_grupo_x, @fecha_inicio, NULL, 1, 'Carga inicial Fase 1', @usuario_carga, NOW()
FROM (
    SELECT '04' AS codigo UNION ALL SELECT '05' UNION ALL SELECT '19' UNION ALL
    SELECT '24j' UNION ALL SELECT '27' UNION ALL SELECT '31'
) v
INNER JOIN maestrajf m ON m.codigo = v.codigo AND UPPER(m.tipo_dato) = 'TVEND';

INSERT IGNORE INTO vendedor_grupos_marcasjf
    (cod_vendedor, id_grupo_marca, fecha_inicio, fecha_fin, estado, observacion, usureg, fecreg)
SELECT v.codigo, @id_grupo_y, @fecha_inicio, NULL, 1, 'Carga inicial Fase 1', @usuario_carga, NOW()
FROM (
    SELECT '32' AS codigo UNION ALL SELECT '30' UNION ALL SELECT '18a'
) v
INNER JOIN maestrajf m ON m.codigo = v.codigo AND UPPER(m.tipo_dato) = 'TVEND';

-- ---------------------------------------------------------------------------
-- Resumen post-carga
-- ---------------------------------------------------------------------------
SELECT g.codigo, g.nombre, COUNT(d.id) AS total_marcas
FROM grupos_marcas_comercialjf g
LEFT JOIN grupos_marcas_detallejf d ON d.id_grupo_marca = g.id
WHERE g.codigo IN ('X', 'Y')
GROUP BY g.id, g.codigo, g.nombre;

SELECT g.codigo AS grupo,
       vgm.cod_vendedor,
       mv.descripcion AS nombre_vendedor,
       vgm.fecha_inicio,
       vgm.fecha_fin,
       vgm.estado
FROM vendedor_grupos_marcasjf vgm
INNER JOIN grupos_marcas_comercialjf g ON g.id = vgm.id_grupo_marca
LEFT JOIN maestrajf mv
  ON mv.codigo = vgm.cod_vendedor AND UPPER(mv.tipo_dato) = 'TVEND'
WHERE g.codigo IN ('X', 'Y')
ORDER BY g.codigo, vgm.cod_vendedor;
