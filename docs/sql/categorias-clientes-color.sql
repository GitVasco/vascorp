-- Color distintivo por categoría comercial (ejecutar una vez).
-- Hex usado en badges/listados para diferenciar categorías.

ALTER TABLE categorias_clientesjf
  ADD COLUMN color VARCHAR(20) NULL COMMENT 'Color hex para UI (ej. #dd4b39)' AFTER orden;

UPDATE categorias_clientesjf SET color = '#dd4b39' WHERE codigo = 'DIST' AND (color IS NULL OR color = '');
UPDATE categorias_clientesjf SET color = '#00a65a' WHERE codigo = 'MAYO' AND (color IS NULL OR color = '');
UPDATE categorias_clientesjf SET color = '#f39c12' WHERE codigo = 'MINO' AND (color IS NULL OR color = '');
UPDATE categorias_clientesjf SET color = '#00c0ef' WHERE codigo = 'CATA' AND (color IS NULL OR color = '');
UPDATE categorias_clientesjf SET color = '#605ca8' WHERE codigo = 'UFIN' AND (color IS NULL OR color = '');
