-- Índices para el resumen comparativo de la Ficha Gerencial de Modelos.
--
-- La tabla compara todos los modelos en un rango de fechas, por lo que
-- necesita iniciar el recorrido por fecha. El índice existente
-- (articulo, fecha, tipo) sigue siendo útil para la ficha de un solo modelo.
--
-- Ejecutar en una ventana de baja actividad y comprobar previamente con
-- SHOW INDEX que cada índice aún no exista.

CREATE INDEX idx_preciojf_modelo_id
    ON preciojf (modelo, id);

CREATE INDEX idx_cmm_modelo_estado_periodo
    ON costos_modelo_mensualjf (modelo, estado, anio, mes, id);

CREATE INDEX idx_mov_2021_fecha_tipo_articulo
    ON movimientosjf_2021 (fecha, tipo, articulo);

CREATE INDEX idx_mov_2022_fecha_tipo_articulo
    ON movimientosjf_2022 (fecha, tipo, articulo);

CREATE INDEX idx_mov_2023_fecha_tipo_articulo
    ON movimientosjf_2023 (fecha, tipo, articulo);

CREATE INDEX idx_mov_2024_fecha_tipo_articulo
    ON movimientosjf_2024 (fecha, tipo, articulo);

CREATE INDEX idx_mov_2025_fecha_tipo_articulo
    ON movimientosjf_2025 (fecha, tipo, articulo);

CREATE INDEX idx_mov_2026_fecha_tipo_articulo
    ON movimientosjf_2026 (fecha, tipo, articulo);
