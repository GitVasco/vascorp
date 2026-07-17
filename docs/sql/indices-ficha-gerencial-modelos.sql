-- Índices medidos para la Ficha Gerencial de Modelos.
--
-- EXPLAIN previo (2026): movimientosjf_2026 recorría ~457,524 filas
-- porque solo tenía un índice por cliente. articulojf sí resolvía por PK.
-- ventajf ya cuenta con idx_ventajf_fast (tipo, documento, fecha).
--
-- Ejecutar en una ventana de baja actividad y verificar primero que cada
-- índice no exista en SHOW INDEX. MySQL de esta instalación no garantiza
-- CREATE INDEX IF NOT EXISTS.

CREATE INDEX idx_articulo_modelo_estado_variante
    ON articulojf (modelo, estado, cod_color, cod_talla, articulo);

CREATE INDEX idx_mov_2021_articulo_fecha_tipo
    ON movimientosjf_2021 (articulo, fecha, tipo);

CREATE INDEX idx_mov_2022_articulo_fecha_tipo
    ON movimientosjf_2022 (articulo, fecha, tipo);

CREATE INDEX idx_mov_2023_articulo_fecha_tipo
    ON movimientosjf_2023 (articulo, fecha, tipo);

CREATE INDEX idx_mov_2024_articulo_fecha_tipo
    ON movimientosjf_2024 (articulo, fecha, tipo);

CREATE INDEX idx_mov_2025_articulo_fecha_tipo
    ON movimientosjf_2025 (articulo, fecha, tipo);

CREATE INDEX idx_mov_2026_articulo_fecha_tipo
    ON movimientosjf_2026 (articulo, fecha, tipo);
