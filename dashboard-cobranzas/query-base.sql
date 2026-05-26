-- Query base — Dashboard de Cobranzas
-- Fuente: cuenta_ctejf (cobranzas: tip_mov = '-')
-- Documentación: QUERY-BASE.md

SELECT
    cc.tipo_doc,
    cc.num_cta,
    cc.cod_pago,

    CASE
        WHEN cc.cod_pago IN ('00', 'TR', '05', '06', '14', '16', '17', '18', '15', '80', '82')
        THEN 'EFECTIVO'

        WHEN cc.cod_pago IN ('13', '96')
        THEN 'DEVOLUCION'

        WHEN cc.cod_pago IN ('97', '10')
        THEN 'DESCUENTOS'

        ELSE 'OTROS'
    END AS ingreso,

    cc.doc_origen,
    cc.fecha,

    YEAR(cc.fecha) AS anno,
    MONTH(cc.fecha) AS mes,

    ELT(
        MONTH(cc.fecha),
        'Ene','Feb','Mar','Abr','May','Jun',
        'Jul','Ago','Sep','Oct','Nov','Dic'
    ) AS nom_mes,

    WEEK(cc.fecha, 1) AS semana_anual,
    CEIL(DAY(cc.fecha) / 7) AS semana_mes,

    CASE
        WHEN CEIL(DAY(cc.fecha) / 7) = 1 THEN '01 - 07'
        WHEN CEIL(DAY(cc.fecha) / 7) = 2 THEN '08 - 14'
        WHEN CEIL(DAY(cc.fecha) / 7) = 3 THEN '15 - 21'
        WHEN CEIL(DAY(cc.fecha) / 7) = 4 THEN '22 - 28'
        ELSE CONCAT('29 - ', DAY(LAST_DAY(cc.fecha)))
    END AS rango_semana_mes,

    DAY(cc.fecha) AS dia,
    DAYOFWEEK(cc.fecha) AS num_dia_semana,

    ELT(
        DAYOFWEEK(cc.fecha),
        'Dom','Lun','Mar','Mie','Jue','Vie','Sab'
    ) AS nom_dia,

    cc.monto,
    cc.cliente,
    c.nombre,
    cc.vendedor,
    cc.notas

FROM cuenta_ctejf cc

LEFT JOIN clientesjf c
    ON cc.cliente = c.codigo

WHERE
    YEAR(cc.fecha) IN (2025, 2026)
    AND cc.tip_mov = '-';
