-- =============================================================================
-- Helpdesk: campos mockup + adjuntos
-- =============================================================================
-- Ejecutar DESPUÉS de docs/sql/helpdesk.sql (ya aplicado).
-- Idempotente parcial: si una columna ya existe, comentar esa línea ALTER.
-- =============================================================================

ALTER TABLE helpdesk_ticketjf
    ADD COLUMN area VARCHAR(50) NULL
        COMMENT 'Área solicitante (catálogo fijo app)' AFTER modulo,
    ADD COLUMN pasos_reproducir TEXT NULL
        COMMENT 'Pasos para reproducir (opcional)' AFTER descripcion,
    ADD COLUMN correo_contacto VARCHAR(120) NULL AFTER pasos_reproducir,
    ADD COLUMN telefono_contacto VARCHAR(40) NULL AFTER correo_contacto,
    ADD COLUMN canal_preferido VARCHAR(20) NULL
        COMMENT 'CORREO, TELEFONO, WHATSAPP' AFTER telefono_contacto;

ALTER TABLE helpdesk_ticketjf
    MODIFY COLUMN tipo VARCHAR(20) NOT NULL
        COMMENT 'INCIDENCIA, REQUERIMIENTO, CONSULTA, OTRO, DESARROLLO, CORRECCION';

CREATE TABLE IF NOT EXISTS helpdesk_adjuntojf (
    id                  BIGINT(20) NOT NULL AUTO_INCREMENT,
    ticket_id           BIGINT(20) NOT NULL
        COMMENT 'helpdesk_ticketjf.id',
    nombre_original     VARCHAR(255) NOT NULL,
    nombre_guardado     VARCHAR(255) NOT NULL
        COMMENT 'Nombre en disco bajo vistas/img/helpdesk/',
    mime                VARCHAR(100) NULL,
    tamanio             INT(11) NOT NULL DEFAULT 0
        COMMENT 'Bytes',
    usuario_id          INT(11) NOT NULL
        COMMENT 'usuariosjf.id quien subió',
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_hda_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Adjuntos de tickets helpdesk';
