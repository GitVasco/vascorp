-- Talla confirmada en cada línea del cierre manual.
-- Puede diferir de la talla del artículo. Ejecutar una sola vez.
ALTER TABLE servicios_cierre_manual
  ADD COLUMN talla VARCHAR(20) NULL COMMENT 'Talla confirmada al cerrar' AFTER articulo;
