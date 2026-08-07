-- =============================================================================
-- Helpdesk: columna sistema (Vascorp / Sistema Vasco / VascoPro / TI empresa)
-- =============================================================================
-- Ejecutar DESPUÉS de helpdesk.sql y helpdesk-alter-mockup.sql.
-- Si la columna ya existe, comentar este ALTER.
-- =============================================================================

ALTER TABLE helpdesk_ticketjf
    ADD COLUMN sistema VARCHAR(30) NULL
        COMMENT 'VASCORP, SISTEMA_VASCO, VASCOPRO, TI_EMPRESA'
        AFTER modulo,
    ADD KEY idx_hd_sistema (sistema);
