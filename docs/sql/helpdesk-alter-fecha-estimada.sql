-- =============================================================================
-- Helpdesk: fecha estimada de entrega (desarrollos / requerimientos)
-- =============================================================================
-- Compromiso suave de planificación; NO es SLA.
-- Ejecutar DESPUÉS de helpdesk.sql (y alters previos si aplica).
-- Si la columna ya existe, comentar este ALTER.
-- =============================================================================

ALTER TABLE helpdesk_ticketjf
    ADD COLUMN fecha_estimada DATE NULL
        COMMENT 'Fecha estimada de entrega (DESARROLLO/REQUERIMIENTO)'
        AFTER cerrado_en;
