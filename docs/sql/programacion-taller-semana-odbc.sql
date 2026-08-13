-- =============================================================================
-- Programación semanal — consulta ODBC / Excel
-- Una fila por modelo + color + talla + taller.
-- Incluye: ya destinado a semana + priorizado aún sin semana.
-- =============================================================================
-- cantidad = lo programado/priorizado a nivel modelo-color (se repite en cada talla).
-- alm_corte_vivo / ord_corte_vivo / saldo_vivo = de esa talla.
-- Filtros opcionales: descomentar al final (antes del ORDER BY).
-- =============================================================================

SELECT
	x.origen,
	x.id,
	x.anio,
	x.semana,
	x.fecha_inicio,
	x.fecha_fin,
	CASE
		WHEN x.origen = 'PRIORIZADO' THEN 'Sin semana'
		WHEN (IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) <= 0 THEN 'Consumido'
		WHEN x.fecha_fin IS NOT NULL AND x.fecha_fin < CURDATE() THEN 'No ejecutado'
		ELSE 'Pendiente'
	END AS estado_flujo,
	x.nivel AS nivel_id,
	CASE x.nivel
		WHEN 'critico' THEN 'Crítico'
		WHEN 'prioridad' THEN 'Prioridad'
		WHEN 'urgente' THEN 'Urgente'
		WHEN 'avanzar' THEN 'Avanzar'
		WHEN 'campana' THEN 'Campaña'
		WHEN 'muestra_diseno' THEN 'Muestra Diseño'
		WHEN 'muestra_produccion' THEN 'Muestra Producción'
		WHEN 'primera_produccion' THEN '1era Producción'
		ELSE x.nivel
	END AS nivel_nombre,
	CASE x.nivel
		WHEN 'critico' THEN 1
		WHEN 'prioridad' THEN 2
		WHEN 'urgente' THEN 3
		WHEN 'avanzar' THEN 4
		WHEN 'campana' THEN 5
		WHEN 'muestra_diseno' THEN 6
		WHEN 'muestra_produccion' THEN 7
		WHEN 'primera_produccion' THEN 8
		ELSE 99
	END AS nivel_orden,
	x.cod_sector,
	IFNULL(s.nom_sector, x.cod_sector) AS taller,
	TRIM(x.modelo) AS modelo,
	IFNULL(mj.nombre, IFNULL(x.nombre, a.nombre)) AS nombre_modelo,
	mj.tipo AS tipo,
	IFNULL(NULLIF(TRIM(a.marca), ''), mar.marca) AS marca,
	TRIM(IFNULL(x.cod_color, '')) AS cod_color,
	IFNULL(x.color, a.color) AS color,
	a.articulo,
	a.cod_talla,
	a.talla,
	x.cantidad,
	x.saldo_alm_corte AS alm_corte_al_programar,
	x.saldo_ord_corte AS ord_corte_al_programar,
	IFNULL(a.alm_corte, 0) AS alm_corte_vivo,
	IFNULL(a.ord_corte, 0) AS ord_corte_vivo,
	(IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)) AS saldo_vivo,
	IFNULL(a.stock, 0) AS stock,
	IFNULL(a.pedidos, 0) AS pedidos,
	IFNULL(a.taller, 0) AS en_taller,
	IFNULL(a.servicio, 0) AS en_servicio,
	IFNULL(a.ult_mes, 0) AS ult_mes,
	x.urg_plan AS cobertura_al_programar,
	ROUND(
		(
			(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0))
			+ IFNULL(a.taller, 0) + IFNULL(a.servicio, 0)
			+ IFNULL(a.alm_corte, 0) + IFNULL(a.ord_corte, 0)
		) / NULLIF(a.ult_mes, 0),
		2
	) AS cobertura_viva,
	x.observacion,
	x.usureg,
	x.fecreg,
	x.usumod,
	x.fecmod
FROM (
	SELECT
		'PROGRAMADO' AS origen,
		p.id,
		p.anio,
		p.semana,
		p.fecha_inicio,
		p.fecha_fin,
		p.nivel,
		p.cod_sector,
		p.modelo,
		p.nombre,
		p.cod_color,
		p.color,
		p.cantidad,
		p.saldo_alm_corte,
		p.saldo_ord_corte,
		p.urg_plan,
		p.observacion,
		p.usureg,
		p.fecreg,
		p.usumod,
		p.fecmod
	FROM programacion_taller_semanajf p
	WHERE p.estado = 1

	UNION ALL

	SELECT
		'PRIORIZADO' AS origen,
		p.id,
		CAST(NULL AS SIGNED) AS anio,
		CAST(NULL AS SIGNED) AS semana,
		CAST(NULL AS DATE) AS fecha_inicio,
		CAST(NULL AS DATE) AS fecha_fin,
		p.nivel,
		p.cod_sector,
		p.modelo,
		p.nombre,
		p.cod_color,
		p.color,
		p.cantidad,
		p.saldo_alm_corte,
		p.saldo_ord_corte,
		p.urg_plan,
		p.observacion,
		p.usureg,
		p.fecreg,
		p.usumod,
		p.fecmod
	FROM programacion_taller_prioridadjf p
	WHERE p.estado = 1
) x
LEFT JOIN sectorjf s
	ON s.cod_sector = x.cod_sector
LEFT JOIN modelojf mj
	ON TRIM(mj.modelo) = TRIM(x.modelo)
LEFT JOIN marcasjf mar
	ON mar.id = mj.id_marca
INNER JOIN articulojf a
	ON TRIM(a.modelo) = TRIM(x.modelo)
	AND TRIM(IFNULL(a.cod_color, '')) = TRIM(IFNULL(x.cod_color, ''))
	AND (
		LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
		OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
	)
WHERE 1 = 1
	-- AND x.origen = 'PROGRAMADO'
	-- AND x.anio = 2026
	-- AND x.semana = 33
	-- AND x.cod_sector = 'T1'
	-- AND x.nivel = 'critico'
	-- AND TRIM(x.modelo) = '1234'
	-- AND (IFNULL(a.alm_corte, 0) > 0 OR IFNULL(a.ord_corte, 0) > 0)
ORDER BY
	x.origen DESC,
	x.anio,
	x.semana,
	nivel_orden,
	x.cod_sector,
	x.modelo,
	x.cod_color,
	a.cod_talla,
	a.talla;
