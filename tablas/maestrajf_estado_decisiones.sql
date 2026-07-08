-- Centro de Decisiones: flag por vendedor en maestrajf (solo tipo TVEND)
-- Ejecutar en la BD vasco

ALTER TABLE maestrajf
    ADD COLUMN estado_decisiones TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1=incluido en Centro de Decisiones, 0=excluido'
    AFTER tipo_dato;

-- Migración inicial: mismos vendedores que el filtro anterior (excluye prefijos 08, 06, 23, 99)
UPDATE maestrajf
SET estado_decisiones = 1
WHERE UPPER(tipo_dato) = 'TVEND'
  AND COALESCE(LEFT(TRIM(codigo), 2), '') NOT IN ('08', '06', '23', '99');

-- Índice opcional para el dashboard
CREATE INDEX idx_maestrajf_tvend_decisiones
    ON maestrajf (tipo_dato, estado_decisiones, codigo);
