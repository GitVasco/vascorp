-- Cierre manual de servicios (Cierres > Cierre manual): agrupa las líneas de cada cierre
-- bajo un código (CM000001) y guarda el taller. motivo pasa a guardar el código del tipo de falla
-- (controladores/cierre-manual-fallas.json).
-- Ejecutar una sola vez sobre la tabla servicios_cierre_manual existente.
ALTER TABLE servicios_cierre_manual
  ADD COLUMN cierre_codigo VARCHAR(20) NULL COMMENT 'Código del cierre manual (CM000001)' AFTER id,
  ADD COLUMN taller VARCHAR(20) NULL COMMENT 'Taller (sectorjf.cod_sector)' AFTER cierre_codigo,
  ADD KEY idx_cierre (cierre_codigo);
