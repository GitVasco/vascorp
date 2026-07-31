-- =============================================================================
-- Controles post-aprobación (condiciones operativas antes de despachar)
-- =============================================================================
-- Pedidos APROBADOS con condición pendiente (pago previo, aviso a APT, etc.)
-- Ejecutar manualmente en la BD. Idempotente (IF NOT EXISTS).
-- =============================================================================

CREATE TABLE IF NOT EXISTS decision_credito_controljf (
    id INT(11) NOT NULL AUTO_INCREMENT,

    codigo_pedido INT(11) NOT NULL COMMENT 'temporaljf.codigo',
    codigo_cliente VARCHAR(20) NOT NULL,

    id_accion_aprobacion INT(11) NULL COMMENT 'decision_credito_accionjf.id de la fila APROBADO',

    condicion_codigo VARCHAR(50) NOT NULL COMMENT 'Catálogo controles_post_aprobacion',
    area_autoriza_codigo VARCHAR(50) NULL COMMENT 'Catálogo areas_autorizacion',
    comentario TEXT NULL COMMENT 'Detalle de la condición',

    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE' COMMENT 'PENDIENTE | LIBERADO',
    bloquea_apt TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=bloquea paso a APT hasta liberar',

    usuario_registra INT(11) NOT NULL,
    usuario_liberacion INT(11) NULL,
    comentario_liberacion TEXT NULL,

    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_liberacion TIMESTAMP NULL,

    PRIMARY KEY (id),
    KEY idx_dcc_pedido (codigo_pedido),
    KEY idx_dcc_estado (estado),
    KEY idx_dcc_pedido_estado (codigo_pedido, estado),
    KEY idx_dcc_fecha (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
  COMMENT='Controles operativos pendientes tras aprobar un pedido';
