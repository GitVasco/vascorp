-- ============================================
-- CAMBIOS MÍNIMOS EN BASE DE DATOS
-- Solo agregar columnas necesarias
-- ============================================

-- 1. Agregar campo de referencia en entaller_cabjf
ALTER TABLE entaller_cabjf 
ADD COLUMN almacencorte_detalle_id INT NULL COMMENT 'Referencia al detalle de almacén corte origen';

-- 2. Agregar índice para mejorar búsquedas
CREATE INDEX idx_almacencorte_detalle ON entaller_cabjf(almacencorte_detalle_id);
CREATE INDEX idx_entaller_articulo_estado ON entaller_cabjf(articulo, estado);

-- 3. Agregar campo saldo_taller en almacencorte_detallejf
-- (Este campo es diferente al campo 'saldo' existente que se usa en otro proceso)
ALTER TABLE almacencorte_detallejf 
ADD COLUMN saldo_taller INT DEFAULT 0 COMMENT 'Saldo disponible para enviar a taller' AFTER cantidad;

-- 4. Poner todo saldo_taller en cero primero
UPDATE almacencorte_detallejf 
SET saldo_taller = 0;

-- 5. Inicializar saldo_taller solo para registros creados este año
UPDATE almacencorte_detallejf acd
INNER JOIN almacencortejf ac ON acd.almacencorte = ac.codigo
SET acd.saldo_taller = acd.cantidad 
WHERE YEAR(DATE(ac.fecha)) = YEAR(NOW());

-- 6. Ajustar saldo_taller restando lo que ya fue enviado a taller este año (solo pendientes)
UPDATE almacencorte_detallejf acd
INNER JOIN entaller_cabjf etc ON acd.id = etc.almacencorte_detalle_id
SET acd.saldo_taller = GREATEST(acd.saldo_taller - etc.cantidad, 0)
WHERE YEAR(DATE(etc.fecha)) = YEAR(NOW())
  AND etc.estado = 0;

-- 7. Cerrar todos los registros anteriores en entaller_cabjf (marcar como procesados)
UPDATE entaller_cabjf 
SET estado = 1 
WHERE YEAR(DATE(fecha)) < YEAR(NOW())
  AND estado = 0;

-- 8. Agregar índice para mejorar rendimiento
CREATE INDEX idx_articulo_saldo_taller ON almacencorte_detallejf(articulo, saldo_taller);

-- ============================================
-- FIN DEL SCRIPT SQL
-- ============================================

