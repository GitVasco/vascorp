-- Permite destinar un modelo a la categoría sin exigir aún la subcategoría.
-- Idempotente en lo posible: si la columna ya existe, omitir el ADD.
-- Ejecutar en staging/producción una vez.

ALTER TABLE modelo_subcategoriajf
    ADD COLUMN id_categoria INT UNSIGNED NULL AFTER modelo,
    MODIFY id_subcategoria INT UNSIGNED NULL,
    ADD KEY idx_modelo_subcategoriajf_categoria_modelo (id_categoria, modelo);

UPDATE modelo_subcategoriajf ms
INNER JOIN subcategoria_modelojf s ON s.id = ms.id_subcategoria
SET ms.id_categoria = s.id_categoria
WHERE ms.id_categoria IS NULL;

ALTER TABLE modelo_subcategoria_historialjf
    ADD COLUMN id_categoria_anterior INT UNSIGNED NULL AFTER modelo,
    ADD COLUMN id_categoria_nueva INT UNSIGNED NULL AFTER id_categoria_anterior;

UPDATE modelo_subcategoria_historialjf h
LEFT JOIN subcategoria_modelojf sa ON sa.id = h.id_subcategoria_anterior
LEFT JOIN subcategoria_modelojf sn ON sn.id = h.id_subcategoria_nueva
SET
    h.id_categoria_anterior = COALESCE(h.id_categoria_anterior, sa.id_categoria),
    h.id_categoria_nueva = COALESCE(h.id_categoria_nueva, sn.id_categoria);
