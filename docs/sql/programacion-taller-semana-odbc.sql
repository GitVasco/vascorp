-- =============================================================================
-- Programación semanal — consulta ODBC / Excel
-- Una fila por modelo + color + talla + taller.
-- Incluye: ya destinado a semana + priorizado aún sin semana + con saldo de corte pero
-- sin programar ni priorizar (origen 'NO PRIORIZADO': por modelo/color/OC; semana, nivel y taller en blanco).
-- =============================================================================
-- cantidad = lo programado/priorizado a nivel modelo-color-OC (se repite en cada talla).
-- Cada línea está ligada a una orden de corte (ordencorte; 0 = saldo sin OC; NULL = legado).
-- alm_corte_vivo / ord_corte_vivo / saldo_vivo = de esa talla Y de esa OC (no del total del
-- modelo/color). Para ordencorte 0 es el resto no cubierto por ninguna OC abierta; para NULL
-- (filas legado sin migrar) es el total de la talla.
-- Los PRIORIZADO salen siempre (mismos que la pantalla Priorizar), aunque su saldo sea 0.
-- Filtros opcionales: descomentar al final (antes del ORDER BY).
-- =============================================================================

SELECT
	q.*,
	CASE
		WHEN q.origen = 'NO PRIORIZADO' THEN 'Sin priorizar'
		WHEN q.origen = 'PRIORIZADO' THEN 'Sin semana'
		WHEN q.saldo_vivo <= 0 THEN 'Consumido'
		WHEN q.fecha_fin IS NOT NULL AND q.fecha_fin < CURDATE() THEN 'No ejecutado'
		ELSE 'Pendiente'
	END AS estado_flujo
FROM (
SELECT
	x.origen,
	x.id,
	x.anio,
	x.semana,
	x.fecha_inicio,
	x.fecha_fin,
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
	x.ordencorte,
	oc.fecha AS fecha_oc,
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
	CASE
		WHEN x.ordencorte IS NULL THEN IFNULL(a.alm_corte, 0)
		WHEN x.ordencorte = 0 THEN GREATEST(IFNULL(a.alm_corte, 0) - IFNULL(alt.saldo, 0), 0)
		ELSE LEAST(IFNULL(a.alm_corte, 0), IFNULL(ao.saldo, 0))
	END AS alm_corte_vivo,
	CASE
		WHEN x.ordencorte IS NULL THEN IFNULL(a.ord_corte, 0)
		WHEN x.ordencorte = 0 THEN GREATEST(IFNULL(a.ord_corte, 0) - IFNULL(odt.saldo, 0), 0)
		ELSE LEAST(IFNULL(a.ord_corte, 0), IFNULL(oo.saldo, 0))
	END AS ord_corte_vivo,
	(
		CASE
		WHEN x.ordencorte IS NULL THEN IFNULL(a.alm_corte, 0)
		WHEN x.ordencorte = 0 THEN GREATEST(IFNULL(a.alm_corte, 0) - IFNULL(alt.saldo, 0), 0)
		ELSE LEAST(IFNULL(a.alm_corte, 0), IFNULL(ao.saldo, 0))
	END
		+ CASE
		WHEN x.ordencorte IS NULL THEN IFNULL(a.ord_corte, 0)
		WHEN x.ordencorte = 0 THEN GREATEST(IFNULL(a.ord_corte, 0) - IFNULL(odt.saldo, 0), 0)
		ELSE LEAST(IFNULL(a.ord_corte, 0), IFNULL(oo.saldo, 0))
	END
	) AS saldo_vivo,
	IFNULL(a.alm_corte, 0) AS alm_corte_total_talla,
	IFNULL(a.ord_corte, 0) AS ord_corte_total_talla,
	IFNULL(a.stock, 0) AS stock,
	IFNULL(a.pedidos, 0) AS pedidos,
	(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0)) AS stock_menos_pedidos,
	IFNULL(a.taller, 0) AS en_taller,
	IFNULL(a.servicio, 0) AS en_servicio,
	IFNULL(a.ult_mes, 0) AS ult_mes,
	x.urg_plan AS cobertura_al_programar,
	(
		(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0))
		+ (IFNULL(a.taller, 0) + IFNULL(a.servicio, 0))
	) AS cobertura_viva,
	ROUND(
		(
			(IFNULL(a.stock, 0) - IFNULL(a.pedidos, 0))
			+ (IFNULL(a.taller, 0) + IFNULL(a.servicio, 0))
		) / NULLIF(a.ult_mes, 0),
		2
	) AS cobertura_meses,
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
		p.ordencorte,
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
		p.ordencorte,
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

	UNION ALL

	-- Modelo/color/OC con saldo de corte que no está programado ni priorizado (misma clave que el sistema:
	-- modelo|color|ordencorte; una OC nueva sale aunque otra OC del mismo modelo/color esté programada).
	-- Semana, nivel y taller quedan en blanco. ordencorte 0 = saldo sin OC.
	SELECT
		'NO PRIORIZADO' AS origen,
		CAST(NULL AS SIGNED) AS id,
		CAST(NULL AS SIGNED) AS anio,
		CAST(NULL AS SIGNED) AS semana,
		CAST(NULL AS DATE) AS fecha_inicio,
		CAST(NULL AS DATE) AS fecha_fin,
		CAST(NULL AS CHAR) AS nivel,
		CAST(NULL AS CHAR) AS cod_sector,
		TRIM(np.modelo) AS modelo,
		CAST(NULL AS CHAR) AS nombre,
		TRIM(IFNULL(np.cod_color, '')) AS cod_color,
		np.ordencorte AS ordencorte,
		np.color,
		CAST(NULL AS SIGNED) AS cantidad,
		CAST(NULL AS SIGNED) AS saldo_alm_corte,
		CAST(NULL AS SIGNED) AS saldo_ord_corte,
		CAST(NULL AS SIGNED) AS urg_plan,
		CAST(NULL AS CHAR) AS observacion,
		CAST(NULL AS CHAR) AS usureg,
		CAST(NULL AS DATETIME) AS fecreg,
		CAST(NULL AS CHAR) AS usumod,
		CAST(NULL AS DATETIME) AS fecmod
	FROM (
		SELECT DISTINCT a2.modelo, a2.cod_color, a2.color, 0 AS ordencorte
		FROM articulojf a2
		WHERE (IFNULL(a2.alm_corte, 0) > 0 OR IFNULL(a2.ord_corte, 0) > 0)
			AND (
				LOWER(TRIM(IFNULL(a2.estado, ''))) = 'activo'
				OR UPPER(REPLACE(TRIM(IFNULL(a2.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
			)
		UNION
		SELECT DISTINCT a2.modelo, a2.cod_color, a2.color, oc2.ordencorte
		FROM articulojf a2
		INNER JOIN (
			SELECT d.articulo, d.ordencorte
			FROM detalles_ordencortejf d
			INNER JOIN ordencortejf o ON o.codigo = d.ordencorte
			WHERE d.saldo > 0 AND d.estado = 0 AND o.estado <> 'Cerrado'
			UNION
			SELECT acd.articulo, acd.ordencorte
			FROM almacencorte_detallejf acd
			INNER JOIN almacencortejf ac ON ac.codigo = acd.almacencorte
			WHERE COALESCE(acd.saldo_taller, acd.cantidad) > 0
				AND acd.ordencorte IS NOT NULL AND acd.ordencorte > 0
				AND YEAR(DATE(ac.fecha)) = YEAR(NOW())
		) oc2 ON oc2.articulo = a2.articulo
		WHERE (IFNULL(a2.alm_corte, 0) > 0 OR IFNULL(a2.ord_corte, 0) > 0)
			AND (
				LOWER(TRIM(IFNULL(a2.estado, ''))) = 'activo'
				OR UPPER(REPLACE(TRIM(IFNULL(a2.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
			)
	) np
	WHERE NOT EXISTS (
		SELECT 1 FROM programacion_taller_semanajf p2
		WHERE p2.estado = 1
			AND TRIM(p2.modelo) = TRIM(np.modelo)
			AND TRIM(IFNULL(p2.cod_color, '')) = TRIM(IFNULL(np.cod_color, ''))
			AND p2.ordencorte = np.ordencorte
	)
	AND NOT EXISTS (
		SELECT 1 FROM programacion_taller_prioridadjf p3
		WHERE p3.estado = 1
			AND TRIM(p3.modelo) = TRIM(np.modelo)
			AND TRIM(IFNULL(p3.cod_color, '')) = TRIM(IFNULL(np.cod_color, ''))
			AND p3.ordencorte = np.ordencorte
	)
) x
LEFT JOIN sectorjf s
	ON s.cod_sector = x.cod_sector
LEFT JOIN modelojf mj
	ON TRIM(mj.modelo) = TRIM(x.modelo)
LEFT JOIN marcasjf mar
	ON mar.id = mj.id_marca
-- LEFT JOIN: un PRIORIZADO debe salir aunque no tenga tallas activas (igual que la pantalla Priorizar)
LEFT JOIN articulojf a
	ON TRIM(a.modelo) = TRIM(x.modelo)
	AND TRIM(IFNULL(a.cod_color, '')) = TRIM(IFNULL(x.cod_color, ''))
	AND (
		x.origen = 'PRIORIZADO'
		OR LOWER(TRIM(IFNULL(a.estado, ''))) = 'activo'
		OR UPPER(REPLACE(TRIM(IFNULL(a.estado, '')), 'Ñ', 'N')) LIKE '%CAMPANA%'
	)
-- Saldo de corte por talla y OC (mismo criterio que mdlSaldosPorOc)
LEFT JOIN (
	SELECT d.articulo, d.ordencorte, SUM(d.saldo) AS saldo
	FROM detalles_ordencortejf d
	INNER JOIN ordencortejf o ON o.codigo = d.ordencorte
	WHERE d.saldo > 0 AND d.estado = 0 AND o.estado <> 'Cerrado'
	GROUP BY d.articulo, d.ordencorte
) oo
	ON oo.articulo = a.articulo AND oo.ordencorte = x.ordencorte
LEFT JOIN (
	SELECT d.articulo, SUM(d.saldo) AS saldo
	FROM detalles_ordencortejf d
	INNER JOIN ordencortejf o ON o.codigo = d.ordencorte
	WHERE d.saldo > 0 AND d.estado = 0 AND o.estado <> 'Cerrado'
	GROUP BY d.articulo
) odt
	ON odt.articulo = a.articulo
LEFT JOIN (
	SELECT acd.articulo, IFNULL(acd.ordencorte, 0) AS ordencorte,
		SUM(COALESCE(acd.saldo_taller, acd.cantidad)) AS saldo
	FROM almacencorte_detallejf acd
	INNER JOIN almacencortejf ac ON ac.codigo = acd.almacencorte
	WHERE COALESCE(acd.saldo_taller, acd.cantidad) > 0
		AND YEAR(DATE(ac.fecha)) = YEAR(NOW())
	GROUP BY acd.articulo, IFNULL(acd.ordencorte, 0)
) ao
	ON ao.articulo = a.articulo AND ao.ordencorte = x.ordencorte
LEFT JOIN (
	SELECT acd.articulo, SUM(COALESCE(acd.saldo_taller, acd.cantidad)) AS saldo
	FROM almacencorte_detallejf acd
	INNER JOIN almacencortejf ac ON ac.codigo = acd.almacencorte
	WHERE COALESCE(acd.saldo_taller, acd.cantidad) > 0
		AND acd.ordencorte IS NOT NULL AND acd.ordencorte > 0
		AND YEAR(DATE(ac.fecha)) = YEAR(NOW())
	GROUP BY acd.articulo
) alt
	ON alt.articulo = a.articulo
LEFT JOIN (
	SELECT codigo, DATE(fecha) AS fecha FROM ordencortejf
) oc
	ON oc.codigo = x.ordencorte
WHERE 1 = 1
	-- AND x.origen = 'PROGRAMADO'
	-- AND x.anio = 2026
	-- AND x.semana = 33
	-- AND x.cod_sector = 'T1'
	-- AND x.nivel = 'critico'
	-- AND TRIM(x.modelo) = '1234'
	-- AND x.ordencorte = 1234
HAVING (origen = 'PRIORIZADO' OR saldo_vivo > 0)  -- los priorizados siempre salen; quitar para ver también lo consumido
) q
ORDER BY
	q.origen DESC,
	q.anio,
	q.semana,
	q.nivel_orden,
	q.cod_sector,
	q.modelo,
	q.cod_color,
	q.ordencorte,
	q.cod_talla,
	q.talla;
