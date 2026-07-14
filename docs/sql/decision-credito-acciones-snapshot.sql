-- =============================================================================
-- Ampliar decision_credito_accionjf con snapshot crédito al aprobar
-- =============================================================================
-- Ejecutar una sola vez. Si alguna columna ya existe, salta ese ALTER.
-- =============================================================================

ALTER TABLE decision_credito_accionjf
    ADD COLUMN categoria_nombre VARCHAR(100) NULL COMMENT 'Nombre categoría efectiva' AFTER categoria_codigo_entidad,
    ADD COLUMN cupo_modo VARCHAR(20) NULL COMMENT 'cliente | grupo' AFTER categoria_nombre,
    ADD COLUMN codigo_grupo VARCHAR(20) NULL AFTER cupo_modo,
    ADD COLUMN nombre_grupo VARCHAR(150) NULL AFTER codigo_grupo,
    ADD COLUMN linea_aprobada DECIMAL(15,2) NULL AFTER nombre_grupo,
    ADD COLUMN linea_recomendada DECIMAL(15,2) NULL AFTER linea_aprobada,
    ADD COLUMN linea_referencia DECIMAL(15,2) NULL COMMENT 'La que se usó para validar cupo' AFTER linea_recomendada,
    ADD COLUMN deuda_actual DECIMAL(15,2) NULL AFTER linea_referencia,
    ADD COLUMN cupo_disponible DECIMAL(15,2) NULL AFTER deuda_actual,
    ADD COLUMN utilizacion_pct DECIMAL(8,2) NULL AFTER cupo_disponible,
    ADD COLUMN score_riesgo DECIMAL(8,2) NULL AFTER utilizacion_pct,
    ADD COLUMN score_comercial DECIMAL(8,2) NULL AFTER score_riesgo,
    ADD COLUMN score_fidelidad DECIMAL(8,2) NULL AFTER score_comercial,
    ADD COLUMN etiqueta_linea VARCHAR(80) NULL COMMENT 'Aprobada / Recomendada IC / etc.' AFTER score_fidelidad;
