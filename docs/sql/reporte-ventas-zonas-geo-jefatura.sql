-- =============================================================================
-- Reporte jefatura: TODAS las ventas reales por zona / geo
-- Sin filtrar vendedor activo (incluye showroom, oficina, digitales, etc.)
-- Compatible MariaDB 10.1 (sin WITH / CTE).
--
-- Periodo half-open: incluye @fecha_ini, excluye @fecha_fin
-- =============================================================================

SET @fecha_ini = '2026-01-01';
SET @fecha_fin = '2026-07-01';  -- exclusivo: incluye hasta 30-jun-2026

-- -----------------------------------------------------------------------------
-- Totales de apoyo
-- -----------------------------------------------------------------------------
SELECT ROUND(SUM(v.neto), 2) INTO @total_lima_macro
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
LEFT JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
LEFT JOIN zonas_comerciales_ubigeojf r ON r.cod_ubi = c.ubigeo
INNER JOIN zonas_comercialesjf z
    ON z.id = CASE
        WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
        WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
        ELSE r.id_zona
    END
   AND z.estado = 1
   AND z.macrozona = 'lima'
WHERE v.fecha >= @fecha_ini AND v.fecha < @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO';

SELECT ROUND(SUM(v.neto), 2) INTO @total_peru_macro
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
LEFT JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
LEFT JOIN zonas_comerciales_ubigeojf r ON r.cod_ubi = c.ubigeo
INNER JOIN zonas_comercialesjf z
    ON z.id = CASE
        WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
        WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
        ELSE r.id_zona
    END
   AND z.estado = 1
   AND z.macrozona IN ('peru_norte', 'peru_sur')
WHERE v.fecha >= @fecha_ini AND v.fecha < @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO';

SELECT ROUND(SUM(v.neto), 2) INTO @total_lima_dist
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
INNER JOIN ubigeo u ON u.Codigo = c.ubigeo
WHERE v.fecha >= @fecha_ini AND v.fecha < @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
  AND (
        (UPPER(TRIM(IFNULL(u.Departamento, ''))) = 'LIMA'
         AND UPPER(TRIM(IFNULL(u.Provincia, ''))) = 'LIMA')
     OR UPPER(TRIM(IFNULL(u.Departamento, ''))) = 'CALLAO'
  );

SELECT ROUND(SUM(v.neto), 2) INTO @total_peru_dep
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
INNER JOIN ubigeo u ON u.Codigo = c.ubigeo
WHERE v.fecha >= @fecha_ini AND v.fecha < @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
  AND UPPER(TRIM(IFNULL(u.Departamento, ''))) NOT IN ('', 'LIMA', 'CALLAO');

SELECT
    @fecha_ini AS fecha_ini,
    @fecha_fin AS fecha_fin,
    @total_lima_macro AS total_lima_macro,
    @total_peru_macro AS total_peru_macro,
    ROUND(IFNULL(@total_lima_macro,0) + IFNULL(@total_peru_macro,0), 2) AS total_con_zona,
    @total_lima_dist AS total_lima_distritos,
    @total_peru_dep AS total_peru_departamentos;


-- -----------------------------------------------------------------------------
-- 1) Ventas por zona ACTIVA (todas las ventas reales)
-- -----------------------------------------------------------------------------
SELECT
    z.codigo,
    z.nombre,
    z.macrozona,
    ROUND(SUM(v.neto), 2) AS venta_real,
    CASE
        WHEN z.macrozona = 'lima' AND IFNULL(@total_lima_macro, 0) > 0
            THEN ROUND(SUM(v.neto) * 100 / @total_lima_macro, 2)
        ELSE NULL
    END AS pct_sobre_lima,
    CASE
        WHEN z.macrozona IN ('peru_norte', 'peru_sur') AND IFNULL(@total_peru_macro, 0) > 0
            THEN ROUND(SUM(v.neto) * 100 / @total_peru_macro, 2)
        ELSE NULL
    END AS pct_sobre_peru
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
LEFT JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
LEFT JOIN zonas_comerciales_ubigeojf r ON r.cod_ubi = c.ubigeo
INNER JOIN zonas_comercialesjf z
    ON z.id = CASE
        WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
        WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
        ELSE r.id_zona
    END
   AND z.estado = 1
WHERE v.fecha >= @fecha_ini
  AND v.fecha <  @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
GROUP BY z.id, z.codigo, z.nombre, z.macrozona, z.orden
ORDER BY FIELD(z.macrozona, 'lima', 'peru_norte', 'peru_sur'), z.orden, z.nombre;


-- Sin zona (para cuadrar al total venta real)
SELECT
    'SIN_ZONA' AS codigo,
    ROUND(SUM(v.neto), 2) AS venta_real,
    COUNT(*) AS docs,
    COUNT(DISTINCT v.cliente) AS clientes
FROM ventajf v
LEFT JOIN clientesjf c ON c.codigo = v.cliente
LEFT JOIN grupos_empresarialesjf g ON g.codigo = c.grupo AND g.estado = 1
LEFT JOIN zonas_comerciales_ubigeojf r ON r.cod_ubi = c.ubigeo
LEFT JOIN zonas_comercialesjf z
    ON z.id = CASE
        WHEN c.id_zona IS NOT NULL AND c.id_zona > 0 THEN c.id_zona
        WHEN g.id_zona IS NOT NULL AND g.id_zona > 0 THEN g.id_zona
        ELSE r.id_zona
    END
   AND z.estado = 1
WHERE v.fecha >= @fecha_ini
  AND v.fecha <  @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
  AND z.id IS NULL;


-- -----------------------------------------------------------------------------
-- 2) Ventas por DISTRITO — Lima metropolitana + Callao (+ %)
-- -----------------------------------------------------------------------------
SELECT
    UPPER(TRIM(IFNULL(u.Departamento, ''))) AS departamento,
    UPPER(TRIM(IFNULL(u.Distrito, ''))) AS distrito,
    ROUND(SUM(v.neto), 2) AS venta_real,
    COUNT(DISTINCT v.cliente) AS clientes,
    CASE
        WHEN IFNULL(@total_lima_dist, 0) > 0
            THEN ROUND(SUM(v.neto) * 100 / @total_lima_dist, 2)
        ELSE NULL
    END AS pct_sobre_lima
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
INNER JOIN ubigeo u ON u.Codigo = c.ubigeo
WHERE v.fecha >= @fecha_ini
  AND v.fecha <  @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
  AND TRIM(IFNULL(c.ubigeo, '')) <> ''
  AND (
        (UPPER(TRIM(IFNULL(u.Departamento, ''))) = 'LIMA'
         AND UPPER(TRIM(IFNULL(u.Provincia, ''))) = 'LIMA')
     OR UPPER(TRIM(IFNULL(u.Departamento, ''))) = 'CALLAO'
  )
GROUP BY
    UPPER(TRIM(IFNULL(u.Departamento, ''))),
    UPPER(TRIM(IFNULL(u.Distrito, '')))
ORDER BY venta_real DESC, distrito;


-- -----------------------------------------------------------------------------
-- 3) Ventas por DEPARTAMENTO — resto del Perú (+ %)
-- -----------------------------------------------------------------------------
SELECT
    UPPER(TRIM(IFNULL(u.Departamento, ''))) AS departamento,
    ROUND(SUM(v.neto), 2) AS venta_real,
    COUNT(DISTINCT v.cliente) AS clientes,
    CASE
        WHEN IFNULL(@total_peru_dep, 0) > 0
            THEN ROUND(SUM(v.neto) * 100 / @total_peru_dep, 2)
        ELSE NULL
    END AS pct_sobre_peru
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
INNER JOIN ubigeo u ON u.Codigo = c.ubigeo
WHERE v.fecha >= @fecha_ini
  AND v.fecha <  @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
  AND TRIM(IFNULL(c.ubigeo, '')) <> ''
  AND UPPER(TRIM(IFNULL(u.Departamento, ''))) NOT IN ('', 'LIMA', 'CALLAO')
GROUP BY UPPER(TRIM(IFNULL(u.Departamento, '')))
ORDER BY venta_real DESC, departamento;


-- -----------------------------------------------------------------------------
-- 4) EXTRA: provincias Lima ≠ Lima metro (Norte Chico, etc.)
-- -----------------------------------------------------------------------------
SELECT
    UPPER(TRIM(IFNULL(u.Provincia, ''))) AS provincia,
    ROUND(SUM(v.neto), 2) AS venta_real,
    COUNT(DISTINCT v.cliente) AS clientes
FROM ventajf v
INNER JOIN clientesjf c ON c.codigo = v.cliente
INNER JOIN ubigeo u ON u.Codigo = c.ubigeo
WHERE v.fecha >= @fecha_ini
  AND v.fecha <  @fecha_fin
  AND v.tipo IN ('S02', 'S03', 'S70', 'E05', 'S05')
  AND UPPER(IFNULL(v.estado, '')) <> 'ANULADO'
  AND UPPER(TRIM(IFNULL(u.Departamento, ''))) = 'LIMA'
  AND UPPER(TRIM(IFNULL(u.Provincia, ''))) <> 'LIMA'
  AND UPPER(TRIM(IFNULL(u.Provincia, ''))) <> ''
GROUP BY UPPER(TRIM(IFNULL(u.Provincia, '')))
ORDER BY venta_real DESC;
