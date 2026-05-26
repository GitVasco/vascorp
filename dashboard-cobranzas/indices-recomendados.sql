-- Índices recomendados para Dashboard de Cobranzas
-- Tabla: cuenta_ctejf (movimientos tip_mov = '-' son cobranzas)
--
-- Ejecutar en horario de bajo tráfico. Verificar antes si ya existen índices similares:
--   SHOW INDEX FROM cuenta_ctejf;

-- Principal: filtra por tipo de movimiento + rango de fechas (todas las consultas del dashboard)
CREATE INDEX idx_cctejf_tipmov_fecha
    ON cuenta_ctejf (tip_mov, fecha);

-- Opcional: si filtras mucho por vendedor
CREATE INDEX idx_cctejf_tipmov_fecha_vendedor
    ON cuenta_ctejf (tip_mov, fecha, vendedor);

-- Maestro vendedores (join del combo y mejor vendedor)
CREATE INDEX idx_maestrajf_tvend
    ON maestrajf (tipo_dato, codigo);
