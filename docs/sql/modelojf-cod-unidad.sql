-- Unidad de medida SUNAT por modelo (facturación electrónica).
-- Default C62 = piezas/unidad. Ajustar por modelo cuando haga falta.

ALTER TABLE `modelojf`
ADD COLUMN `cod_unidad` VARCHAR(10) NOT NULL DEFAULT 'C62'
  COMMENT 'Código SUNAT unidad de medida (catálogo unidades_medidajf)'
  AFTER `tipo`;

-- Etiqueta de negocio para C62
UPDATE `unidades_medidajf`
SET `descripcion` = 'PIEZAS'
WHERE `codigo` = 'C62';

-- Por si quedó vacío/null en algún registro
UPDATE `modelojf`
SET `cod_unidad` = 'C62'
WHERE `cod_unidad` IS NULL OR TRIM(`cod_unidad`) = '';
