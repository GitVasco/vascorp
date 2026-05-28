-- =============================================================================
-- DASHBOARD DE COBRANZAS — QUERY PARA EXCEL (sin SET ni variables @)
-- =============================================================================
-- Edita las fechas en el WHERE si necesitas otro rango.
-- Por defecto: todo 2025 y 2026 (como el dashboard web). Filtra año/mes en Excel.
-- Filtro vendedor: slicer o filtro en codigo_vendedor.
-- =============================================================================

SELECT
    cc.tipo_doc,
    cc.num_cta,
    cc.cod_pago,

    CASE
        WHEN cc.cod_pago IN ('00', 'TR', '05', '06', '14', '16', '17', '18', '15', '80', '82') THEN 'EFECTIVO'
        WHEN cc.cod_pago IN ('13', '96') THEN 'DEVOLUCION'
        WHEN cc.cod_pago IN ('97', '10') THEN 'DESCUENTOS'
        ELSE 'OTROS'
    END AS ingreso,

    CASE
        WHEN cc.cod_pago IN ('00', 'TR', '05', '06', '14', '16', '17', '18', '15', '80', '82') THEN 1
        ELSE 0
    END AS es_efectivo,

    cc.doc_origen,
    cc.fecha,

    YEAR(cc.fecha) AS anno,
    MONTH(cc.fecha) AS mes,

    ELT(
        MONTH(cc.fecha),
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ) AS nom_mes,

    DAY(cc.fecha) AS dia,

    CASE
        WHEN DAY(cc.fecha) <= 7 THEN 1
        WHEN DAY(cc.fecha) <= 14 THEN 2
        WHEN DAY(cc.fecha) <= 21 THEN 3
        WHEN DAY(cc.fecha) <= 28 THEN 4
        ELSE 5
    END AS semana_mes,

    CASE
        WHEN DAY(cc.fecha) <= 7 THEN '01 - 07'
        WHEN DAY(cc.fecha) <= 14 THEN '08 - 14'
        WHEN DAY(cc.fecha) <= 21 THEN '15 - 21'
        WHEN DAY(cc.fecha) <= 28 THEN '22 - 28'
        ELSE CONCAT('29 - ', DAY(LAST_DAY(cc.fecha)))
    END AS rango_semana_mes,

    DAYOFWEEK(cc.fecha) AS num_dia_semana,

    ELT(
        DAYOFWEEK(cc.fecha),
        'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'
    ) AS nom_dia,

    ELT(
        DAYOFWEEK(cc.fecha),
        'Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'
    ) AS nom_dia_corto,

    cc.monto,

    CASE
        WHEN cc.cod_pago IN ('00', 'TR', '05', '06', '14', '16', '17', '18', '15', '80', '82')
        THEN cc.monto
        ELSE 0
    END AS monto_efectivo,

    CASE
        WHEN cc.cod_pago IN ('13', '96') THEN cc.monto
        ELSE 0
    END AS monto_devolucion,

    CASE
        WHEN cc.cod_pago IN ('97', '10') THEN cc.monto
        ELSE 0
    END AS monto_descuentos,

    cc.cliente AS codigo_cliente,
    COALESCE(c.nombre, cc.cliente) AS nombre_cliente,

    cc.vendedor AS codigo_vendedor,
    COALESCE(m.descripcion, cc.vendedor) AS nombre_vendedor,

    cc.notas

FROM cuenta_ctejf cc

LEFT JOIN clientesjf c
    ON cc.cliente = c.codigo

LEFT JOIN maestrajf m
    ON cc.vendedor = m.codigo
    AND m.tipo_dato = 'TVEND'

WHERE
    cc.tip_mov = '-'
    AND cc.fecha >= '2025-01-01'
    AND cc.fecha < '2027-01-01'
    /* Opcional: un solo vendedor */
    /* AND cc.vendedor = '01' */

ORDER BY
    cc.fecha ASC,
    cc.num_cta ASC;

-- -----------------------------------------------------------------------------
-- SOLO UN MES (si lo necesitas aparte), sustituye el WHERE por:
--   AND cc.fecha >= '2026-05-01'
--   AND cc.fecha <  '2026-06-01'
-- -----------------------------------------------------------------------------
