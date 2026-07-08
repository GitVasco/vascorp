-- Decisiones de crédito: motivos de no aprobación, solicitudes y bitácora
-- Ejecutar en la BD vasco

CREATE TABLE IF NOT EXISTS decision_credito_pedidojf (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo_pedido INT(11) NOT NULL COMMENT 'temporaljf.codigo',
    codigo_cliente VARCHAR(20) NOT NULL,
    motivo_codigo VARCHAR(50) NOT NULL COMMENT 'Catálogo creditos-motivos.config.json',
    comentario TEXT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'VIGENTE' COMMENT 'VIGENTE, CERRADA',
    resolucion_codigo VARCHAR(50) NULL,
    resolucion_comentario TEXT NULL,
    usuario_registro INT(11) NOT NULL,
    usuario_resolucion INT(11) NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_dcp_pedido (codigo_pedido),
    KEY idx_dcp_cliente (codigo_cliente),
    KEY idx_dcp_estado (estado),
    KEY idx_dcp_pedido_vigente (codigo_pedido, estado)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Registro de no aprobación de créditos por pedido';

CREATE TABLE IF NOT EXISTS decision_credito_solicitudjf (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_decision INT(11) NOT NULL,
    codigo_pedido INT(11) NOT NULL,
    codigo_cliente VARCHAR(20) NOT NULL,
    tipo_solicitud VARCHAR(40) NOT NULL COMMENT 'REEVALUACION_CLIENTE, AUMENTO_LINEA, etc.',
    justificacion TEXT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE' COMMENT 'PENDIENTE, EN_REVISION, APROBADA, RECHAZADA',
    resolucion_codigo VARCHAR(50) NULL,
    comentario_resolucion TEXT NULL,
    usuario_solicita INT(11) NOT NULL,
    usuario_resuelve INT(11) NULL,
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_dcs_decision (id_decision),
    KEY idx_dcs_pedido (codigo_pedido),
    KEY idx_dcs_estado (estado),
    CONSTRAINT fk_dcs_decision FOREIGN KEY (id_decision)
        REFERENCES decision_credito_pedidojf (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Solicitudes de ventas/jefatura sobre decisiones de crédito';

CREATE TABLE IF NOT EXISTS decision_credito_eventojf (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo_pedido INT(11) NOT NULL,
    codigo_cliente VARCHAR(20) NOT NULL,
    tipo_evento VARCHAR(40) NOT NULL,
    detalle TEXT NULL,
    id_referencia INT(11) NULL COMMENT 'id de decisión o solicitud',
    usuario_id INT(11) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_dce_pedido (codigo_pedido),
    KEY idx_dce_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
COMMENT='Bitácora de eventos de decisiones de crédito';
