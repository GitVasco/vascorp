-- Línea de crédito por cliente (estado vigente + historial + solicitudes)
-- Ejecutar en la BD vasco

CREATE TABLE IF NOT EXISTS linea_credito_clientejf (
    codigo_cliente VARCHAR(20) NOT NULL,
    linea_operativa DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    linea_recomendada DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    linea_aprobada DECIMAL(14,2) NULL DEFAULT NULL COMMENT 'Línea autorizada por créditos',
    deuda_actual DECIMAL(14,2) NOT NULL DEFAULT 0.00,
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
    PRIMARY KEY (codigo_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Estado vigente de línea de crédito por cliente';

CREATE TABLE IF NOT EXISTS linea_credito_historialjf (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo_cliente VARCHAR(20) NOT NULL,
    anio SMALLINT NOT NULL,
    mes TINYINT NOT NULL,
    tipo_evento VARCHAR(40) NOT NULL COMMENT 'CIERRE_MENSUAL, ACTUALIZACION_INDIVIDUAL, LINEA_APROBADA, LINEA_ACTUALIZADA, LINEA_RECHAZADA',
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
    detalle TEXT NULL COMMENT 'JSON resumen IC',
    id_solicitud INT(11) NULL,
    usuario_id INT(11) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lch_cliente (codigo_cliente),
    KEY idx_lch_periodo (anio, mes),
    KEY idx_lch_cliente_periodo (codigo_cliente, anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Historial de línea de crédito y cierres mensuales';

CREATE TABLE IF NOT EXISTS linea_credito_solicitudjf (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo_cliente VARCHAR(20) NOT NULL,
    linea_actual DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    linea_solicitada DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    justificacion TEXT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE' COMMENT 'PENDIENTE, APROBADA, RECHAZADA',
    linea_resuelta DECIMAL(14,2) NULL COMMENT 'Monto aprobado si aplica',
    comentario_resolucion TEXT NULL,
    usuario_solicita INT(11) NOT NULL,
    usuario_resuelve INT(11) NULL,
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_lcs_cliente (codigo_cliente),
    KEY idx_lcs_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Solicitudes de incremento de línea de crédito';
