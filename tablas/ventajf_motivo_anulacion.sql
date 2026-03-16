-- Agregar columna para registrar motivo de anulación (solo usado en proformas)
-- Ejecutar en la base de datos que usa la tabla ventajf

USE `new_vasco`;

ALTER TABLE `ventajf`
ADD COLUMN `motivo_anulacion` VARCHAR(500) DEFAULT NULL COMMENT 'Motivo de anulación (proformas)';
