-- Orden de compra del cliente (eFact Legacy FILA 7 col B)
-- Opcional; sin espacios ni guiones al enviar a eFact.

ALTER TABLE `ventajf`
ADD COLUMN `orden_compra` VARCHAR(20) DEFAULT NULL
  COMMENT 'OC del cliente (eFact FILA7 col B)'
  AFTER `doc_origen`;
