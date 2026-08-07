-- =============================================================================
-- Helpdesk interno (TI)
-- =============================================================================
-- Tickets de soporte / desarrollo / correcciones con historial de comentarios.
-- Ejecutar en BD antes de usar el módulo. Idempotente (CREATE IF NOT EXISTS).
-- =============================================================================

CREATE TABLE IF NOT EXISTS helpdesk_ticketjf (
    id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
    titulo              VARCHAR(200) NOT NULL,
    descripcion         TEXT NOT NULL,
    tipo                VARCHAR(20) NOT NULL
        COMMENT 'SOPORTE, CORRECCION, DESARROLLO, CONSULTA',
    prioridad           VARCHAR(10) NOT NULL DEFAULT 'MEDIA'
        COMMENT 'BAJA, MEDIA, ALTA',
    estado              VARCHAR(30) NOT NULL DEFAULT 'ABIERTO'
        COMMENT 'ABIERTO, EN_PROGRESO, ESPERANDO_USUARIO, CERRADO',
    modulo              VARCHAR(100) NULL
        COMMENT 'Sistema o pantalla relacionada (texto libre)',
    solicitante_id      INT(11) NOT NULL
        COMMENT 'usuariosjf.id quien reporta',
    asignado_id         INT(11) NULL
        COMMENT 'usuariosjf.id agente TI/soporte',
    creado_por_id       INT(11) NOT NULL
        COMMENT 'usuariosjf.id quien registró (puede diferir del solicitante)',
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cerrado_en          DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_hd_estado (estado),
    KEY idx_hd_tipo (tipo),
    KEY idx_hd_solicitante (solicitante_id),
    KEY idx_hd_asignado (asignado_id),
    KEY idx_hd_creado (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Tickets helpdesk TI';

CREATE TABLE IF NOT EXISTS helpdesk_comentariojf (
    id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
    ticket_id           BIGINT(20) NOT NULL
        COMMENT 'helpdesk_ticketjf.id',
    usuario_id          INT(11) NOT NULL
        COMMENT 'usuariosjf.id',
    tipo_evento         VARCHAR(30) NOT NULL DEFAULT 'COMENTARIO'
        COMMENT 'COMENTARIO, CAMBIO_ESTADO, ASIGNACION, ALTA',
    mensaje             TEXT NOT NULL,
    estado_anterior     VARCHAR(30) NULL,
    estado_nuevo        VARCHAR(30) NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_hdc_ticket (ticket_id),
    KEY idx_hdc_creado (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Historial / comentarios de tickets helpdesk';
