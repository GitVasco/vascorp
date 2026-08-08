-- =============================================================================
-- Helpdesk: eximir SLA de un ticket (con motivo)
-- =============================================================================
-- Solo agentes con gestionar. Motivo obligatorio al eximir.
-- Ejecutar DESPUÉS de helpdesk.sql (y alters previos).
-- Si las columnas ya existen, comentar este ALTER.
-- =============================================================================

ALTER TABLE helpdesk_ticketjf
    ADD COLUMN sla_exento TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = ticket exento de SLA de cierre'
        AFTER fecha_estimada,
    ADD COLUMN sla_exento_motivo VARCHAR(255) NULL
        COMMENT 'Motivo de la exención'
        AFTER sla_exento,
    ADD COLUMN sla_exento_en DATETIME NULL
        AFTER sla_exento_motivo,
    ADD COLUMN sla_exento_por INT(11) NULL
        COMMENT 'usuariosjf.id quien eximió'
        AFTER sla_exento_en;
