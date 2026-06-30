-- Descuentos compuestos ESSO (cod_pago 10 y 97, vendedor 00)
-- Requiere: tablas/descuento_correccionjf.sql
--
-- Vistas:
--   v_descuento_compuesto_base    — filtro ESSO + limpieza de notas
--   v_descuento_compuesto_pct     — extracción de pct1 / pct2
--   v_descuento_compuesto_parseo  — sugerencia automática (NO es verdad oficial)
--   v_descuento_compuesto_esso    — consolidado (solo MANUAL es oficial)
--
-- Estándar de notas: DSCTO_<p1>_<p2>  (ej. DSCTO_7_2, DSCTO_13_3)
--
-- Flujo:
--   1. La vista propone nota_estandar_propuesta y montos (origen_nota = AUTO o REVISAR).
--   2. El usuario confirma o corrige en descuento_correccionjf (origen_nota = MANUAL).
--   3. El UPDATE masivo SOLO aplica registros con origen_nota = 'MANUAL'.

USE `vasco`;

DROP VIEW IF EXISTS `v_descuento_compuesto_esso`;
DROP VIEW IF EXISTS `v_descuento_compuesto_parseo`;
DROP VIEW IF EXISTS `v_descuento_compuesto_pct`;
DROP VIEW IF EXISTS `v_descuento_compuesto_base`;

-- ---------------------------------------------------------------------------
-- 1a) Base: filtro ESSO + limpieza de notas
-- ---------------------------------------------------------------------------

CREATE VIEW `v_descuento_compuesto_base` AS
SELECT
    cc.id,
    cc.tipo_doc,
    cc.num_cta,
    cc.cod_pago,
    cc.doc_origen,
    cc.fecha,
    cc.monto,
    cc.cliente,
    c.nombre AS nombre_cliente,
    cc.vendedor,
    cc.notas AS notas_original,
    TRIM(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(UPPER(TRIM(cc.notas)), 'DSCTO', ''),
                        'DSCT', ''
                    ),
                    '%', ''
                ),
                ' ', ''
            ),
            '_', ''
        )
    ) AS notas_limpia,
    CASE
        WHEN cc.notas REGEXP '^DSCTO_[0-9]+([.][0-9]+)?_[0-9]+([.][0-9]+)?$' THEN 'ESTANDAR'
        ELSE 'LEGACY'
    END AS formato_notas
FROM cuenta_ctejf cc
LEFT JOIN clientesjf c
    ON cc.cliente = c.codigo
WHERE cc.tip_mov = '-'
    AND cc.cod_pago IN ('10', '97')
    AND cc.vendedor = '00'
    AND cc.notas LIKE '%+%';

-- ---------------------------------------------------------------------------
-- 1b) Porcentajes extraídos
-- ---------------------------------------------------------------------------

CREATE VIEW `v_descuento_compuesto_pct` AS
SELECT
    b.id,
    b.tipo_doc,
    b.num_cta,
    b.cod_pago,
    b.doc_origen,
    b.fecha,
    b.monto,
    b.cliente,
    b.nombre_cliente,
    b.vendedor,
    b.notas_original,
    b.notas_limpia,
    b.formato_notas,
    CASE
        WHEN b.formato_notas = 'ESTANDAR' THEN
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(b.notas_original, '_', 2), '_', -1) AS DECIMAL(10, 4))
        WHEN SUBSTRING_INDEX(b.notas_limpia, '+', 1) REGEXP '^[0-9]+([.][0-9]+)?$' THEN
            CAST(SUBSTRING_INDEX(b.notas_limpia, '+', 1) AS DECIMAL(10, 4))
        ELSE NULL
    END AS pct1,
    CASE
        WHEN b.formato_notas = 'ESTANDAR' THEN
            CAST(SUBSTRING_INDEX(b.notas_original, '_', -1) AS DECIMAL(10, 4))
        WHEN SUBSTRING_INDEX(b.notas_limpia, '+', -1) REGEXP '^[0-9]+([.][0-9]+)?$' THEN
            CAST(SUBSTRING_INDEX(b.notas_limpia, '+', -1) AS DECIMAL(10, 4))
        ELSE NULL
    END AS pct2
FROM v_descuento_compuesto_base b;

-- ---------------------------------------------------------------------------
-- 1c) Parseo automático (sugerencia, no oficial)
-- ---------------------------------------------------------------------------

CREATE VIEW `v_descuento_compuesto_parseo` AS
SELECT
    p.id,
    p.tipo_doc,
    p.num_cta,
    p.cod_pago,
    p.doc_origen,
    p.fecha,
    p.monto,
    p.cliente,
    p.nombre_cliente,
    p.vendedor,
    p.notas_original,
    p.notas_limpia,
    p.formato_notas,
    p.pct1,
    p.pct2,
    CASE
        WHEN p.pct1 IS NOT NULL
            AND p.pct2 IS NOT NULL
            AND p.pct1 >= 0
            AND p.pct2 >= 0
            AND p.pct1 <= 100
            AND p.pct2 <= 100
        THEN 'OK'
        ELSE 'REVISAR'
    END AS estado_parseo,
    CASE
        WHEN p.pct1 IS NOT NULL
            AND p.pct2 IS NOT NULL
            AND p.pct1 >= 0
            AND p.pct2 >= 0
            AND p.pct1 <= 100
            AND p.pct2 <= 100
        THEN CONCAT(
            'DSCTO_',
            IF(
                p.pct1 = FLOOR(p.pct1),
                CAST(p.pct1 AS UNSIGNED),
                TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(p.pct1 AS CHAR)))
            ),
            '_',
            IF(
                p.pct2 = FLOOR(p.pct2),
                CAST(p.pct2 AS UNSIGNED),
                TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(p.pct2 AS CHAR)))
            )
        )
        ELSE NULL
    END AS nota_estandar_propuesta,
    CASE
        WHEN p.pct1 IS NOT NULL
            AND p.pct2 IS NOT NULL
            AND p.pct1 >= 0
            AND p.pct2 >= 0
            AND p.pct1 <= 100
            AND p.pct2 <= 100
            AND p.monto > 0
            AND ((p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100))) > 0
        THEN ROUND(
            p.monto / ((p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100))),
            2
        )
        ELSE NULL
    END AS monto_base,
    CASE
        WHEN p.pct1 IS NOT NULL
            AND p.pct2 IS NOT NULL
            AND p.pct1 >= 0
            AND p.pct2 >= 0
            AND p.pct1 <= 100
            AND p.pct2 <= 100
            AND p.monto > 0
            AND ((p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100))) > 0
        THEN ROUND(
            (p.monto / ((p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100))))
            * (p.pct1 / 100),
            2
        )
        ELSE NULL
    END AS monto_pct1,
    CASE
        WHEN p.pct1 IS NOT NULL
            AND p.pct2 IS NOT NULL
            AND p.pct1 >= 0
            AND p.pct2 >= 0
            AND p.pct1 <= 100
            AND p.pct2 <= 100
            AND p.monto > 0
            AND ((p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100))) > 0
        THEN ROUND(
            (p.monto / ((p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100))))
            * (p.pct2 / 100)
            * (1 - (p.pct1 / 100)),
            2
        )
        ELSE NULL
    END AS monto_pct2,
    CASE
        WHEN p.pct1 IS NOT NULL
            AND p.pct2 IS NOT NULL
            AND p.pct1 >= 0
            AND p.pct2 >= 0
            AND p.pct1 <= 100
            AND p.pct2 <= 100
            AND p.monto > 0
            AND ((p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100))) > 0
        THEN ROUND(
            (p.pct1 / 100) + (p.pct2 / 100) * (1 - (p.pct1 / 100)),
            6
        )
        ELSE NULL
    END AS factor_descuento
FROM v_descuento_compuesto_pct p;

-- ---------------------------------------------------------------------------
-- 2) Consolidado (corrección manual gana sobre parseo automático)
-- ---------------------------------------------------------------------------

CREATE VIEW `v_descuento_compuesto_esso` AS
SELECT
    p.id,
    p.tipo_doc,
    p.num_cta,
    p.cod_pago,
    p.doc_origen,
    p.fecha,
    p.monto,
    p.cliente,
    p.nombre_cliente,
    p.vendedor,
    p.notas_original,
    p.notas_limpia,
    p.formato_notas,
    p.pct1 AS pct1_parseo,
    p.pct2 AS pct2_parseo,
    p.estado_parseo,
    p.nota_estandar_propuesta,
    dc.nota_estandar AS nota_estandar_manual,
    dc.observacion AS observacion_manual,
    dc.estado AS estado_correccion,
    dc.usureg AS usureg_correccion,
    dc.fecha_creacion AS fecha_correccion,
    CASE
        WHEN dc.estado = 'CONFIRMADO' THEN dc.nota_estandar
        ELSE p.nota_estandar_propuesta
    END AS nota_estandar_final,
    CASE
        WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1
        ELSE p.pct1
    END AS pct1_final,
    CASE
        WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2
        ELSE p.pct2
    END AS pct2_final,
    CASE
        WHEN dc.estado = 'DESCARTADO' THEN 'DESCARTADO'
        WHEN dc.estado = 'CONFIRMADO' THEN 'MANUAL'
        WHEN p.estado_parseo = 'OK' THEN 'AUTO'
        ELSE 'REVISAR'
    END AS origen_nota,
    CASE
        WHEN COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) IS NOT NULL
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) IS NOT NULL
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) >= 0
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) >= 0
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) <= 100
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) <= 100
            AND p.monto > 0
        THEN ROUND(
            p.monto / (
                (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100)
                + (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) / 100)
                * (1 - (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100))
            ),
            2
        )
        ELSE NULL
    END AS monto_base_final,
    CASE
        WHEN COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) IS NOT NULL
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) IS NOT NULL
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) >= 0
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) >= 0
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) <= 100
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) <= 100
            AND p.monto > 0
        THEN ROUND(
            (
                p.monto / (
                    (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100)
                    + (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) / 100)
                    * (1 - (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100))
                )
            ) * (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100),
            2
        )
        ELSE NULL
    END AS monto_pct1_final,
    CASE
        WHEN COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) IS NOT NULL
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) IS NOT NULL
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) >= 0
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) >= 0
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) <= 100
            AND COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) <= 100
            AND p.monto > 0
        THEN ROUND(
            (
                p.monto / (
                    (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100)
                    + (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) / 100)
                    * (1 - (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100))
                )
            ) * (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct2 END, p.pct2) / 100)
            * (1 - (COALESCE(CASE WHEN dc.estado = 'CONFIRMADO' THEN dc.pct1 END, p.pct1) / 100)),
            2
        )
        ELSE NULL
    END AS monto_pct2_final
FROM v_descuento_compuesto_parseo p
LEFT JOIN descuento_correccionjf dc
    ON p.id = dc.id;

-- ---------------------------------------------------------------------------
-- Consultas útiles
-- ---------------------------------------------------------------------------

-- Revisar casos ambiguos:
-- SELECT * FROM v_descuento_compuesto_esso WHERE origen_nota = 'REVISAR' ORDER BY fecha DESC;

-- Ver sugerencias automáticas pendientes de confirmar:
-- SELECT id, notas_original, nota_estandar_propuesta, monto, monto_pct1, monto_pct2
-- FROM v_descuento_compuesto_parseo WHERE estado_parseo = 'OK';

-- Confirmar manualmente (ejemplo):
-- INSERT INTO descuento_correccionjf (id, nota_estandar, pct1, pct2, observacion, usureg)
-- VALUES (896480, 'DSCTO_7_2', 7, 2, 'Confirmado', 'joel');

-- UPDATE masivo SOLO con confirmaciones manuales:
-- UPDATE cuenta_ctejf cc
-- INNER JOIN v_descuento_compuesto_esso v ON cc.id = v.id
-- SET cc.notas = v.nota_estandar_final
-- WHERE v.origen_nota = 'MANUAL'
--   AND v.estado_correccion = 'CONFIRMADO'
--   AND v.nota_estandar_final IS NOT NULL;
