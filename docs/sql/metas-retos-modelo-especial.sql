-- =============================================================================
-- Metas / retos: modelo especial por vendedor-mes (beneficio por docenas)
-- =============================================================================
-- Un modelo por vendedor y mes.
-- Avance: SUM(cantidad)/12 en movimientosjf_AAAA + articulojf.modelo
-- Comisión: % sobre venta (total de líneas) de ese modelo en el período.
-- Si las columnas ya existen, ignorar el error "Duplicate column".
-- =============================================================================

ALTER TABLE metas_retos_vendedorjf
    ADD COLUMN modelo_especial VARCHAR(50) NULL COMMENT 'articulojf.modelo (uno por vend-mes)' AFTER cumplimiento_modelos,
    ADD COLUMN meta_docenas_especial DECIMAL(10,2) NULL COMMENT 'Umbral docenas (cantidad/12)' AFTER modelo_especial,
    ADD COLUMN comision_modelo_esp_pct DECIMAL(8,2) NULL COMMENT '% sobre venta del modelo' AFTER meta_docenas_especial,
    ADD COLUMN cumplimiento_modelo_esp VARCHAR(20) NOT NULL DEFAULT 'todo_nada' AFTER comision_modelo_esp_pct
;
