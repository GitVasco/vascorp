-- Línea de crédito por grupo empresarial (estado vigente + historial)
-- Ejecutar en la BD vasco

CREATE TABLE IF NOT EXISTS linea_credito_grupojf (
    codigo_grupo VARCHAR(20) NOT NULL,
    linea_operativa DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    linea_recomendada DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    linea_aprobada DECIMAL(14,2) NULL DEFAULT NULL COMMENT 'Línea autorizada por créditos a nivel grupo',
    deuda_actual DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Deuda consolidada de locales en cartera',
    cupo_disponible DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    utilizacion_pct DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    score_riesgo DECIMAL(6,2) NULL,
    score_comercial DECIMAL(6,2) NULL,
    score_fidelidad DECIMAL(6,2) NULL,
    accion_linea VARCHAR(120) NULL,
    ultimo_cierre_anio SMALLINT NULL,
    ultimo_cierre_mes TINYINT NULL,
    usuario_actualiza INT(11) NULL,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (codigo_grupo)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Estado vigente de línea de crédito por grupo empresarial';

CREATE TABLE IF NOT EXISTS linea_credito_historial_grupojf (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo_grupo VARCHAR(20) NOT NULL,
    anio SMALLINT NOT NULL,
    mes TINYINT NOT NULL,
    tipo_evento VARCHAR(40) NOT NULL COMMENT 'CIERRE_MENSUAL, ACTUALIZACION_INDIVIDUAL, LINEA_APROBADA, LINEA_ACTUALIZADA',
    linea_operativa DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    linea_recomendada DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    linea_aprobada DECIMAL(14,2) NULL,
    deuda_actual DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    cupo_disponible DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    utilizacion_pct DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    score_riesgo DECIMAL(6,2) NULL,
    score_comercial DECIMAL(6,2) NULL,
    score_fidelidad DECIMAL(6,2) NULL,
    accion_linea VARCHAR(120) NULL,
    detalle TEXT NULL COMMENT 'JSON resumen IC / motivo manual',
    usuario_id INT(11) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lchg_grupo (codigo_grupo),
    KEY idx_lchg_periodo (anio, mes),
    KEY idx_lchg_grupo_periodo (codigo_grupo, anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Historial de línea de crédito y cierres mensuales por grupo';

-- Migración inicial: suma de líneas aprobadas por local → línea del grupo
INSERT INTO linea_credito_grupojf (
    codigo_grupo,
    linea_operativa,
    linea_recomendada,
    linea_aprobada,
    deuda_actual,
    cupo_disponible,
    utilizacion_pct,
    fecha_actualizacion
)
SELECT
    c.grupo,
    COALESCE(SUM(lc.linea_operativa), 0),
    COALESCE(SUM(lc.linea_recomendada), 0),
    NULLIF(SUM(COALESCE(lc.linea_aprobada, 0)), 0),
    COALESCE(SUM(lc.deuda_actual), 0),
    GREATEST(0, NULLIF(SUM(COALESCE(lc.linea_aprobada, 0)), 0) - COALESCE(SUM(lc.deuda_actual), 0)),
    CASE
        WHEN NULLIF(SUM(COALESCE(lc.linea_aprobada, 0)), 0) > 0
        THEN ROUND(COALESCE(SUM(lc.deuda_actual), 0) / NULLIF(SUM(COALESCE(lc.linea_aprobada, 0)), 0) * 100, 2)
        ELSE 0
    END,
    NOW()
FROM clientesjf c
INNER JOIN linea_credito_clientejf lc ON lc.codigo_cliente = c.codigo
WHERE c.grupo IS NOT NULL
  AND TRIM(c.grupo) <> ''
  AND c.estado = 1
GROUP BY c.grupo
HAVING SUM(COALESCE(lc.linea_aprobada, 0)) > 0
ON DUPLICATE KEY UPDATE
    linea_aprobada = VALUES(linea_aprobada),
    deuda_actual = VALUES(deuda_actual),
    cupo_disponible = VALUES(cupo_disponible),
    utilizacion_pct = VALUES(utilizacion_pct),
    fecha_actualizacion = NOW();

-- Los locales del grupo ya no llevan línea aprobada individual (vive en el grupo)
UPDATE linea_credito_clientejf lc
INNER JOIN clientesjf c ON c.codigo = lc.codigo_cliente
SET lc.linea_aprobada = NULL
WHERE c.grupo IS NOT NULL
  AND TRIM(c.grupo) <> ''
  AND lc.linea_aprobada IS NOT NULL
  AND lc.linea_aprobada > 0
  AND EXISTS (
      SELECT 1
      FROM linea_credito_grupojf lg
      WHERE lg.codigo_grupo = c.grupo
        AND lg.linea_aprobada IS NOT NULL
        AND lg.linea_aprobada > 0
  );
