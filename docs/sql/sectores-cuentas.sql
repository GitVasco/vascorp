-- Cuenta de detracción (Banco de la Nación) y cuenta BCP del taller/servicio externo
ALTER TABLE sectorjf
    ADD COLUMN IF NOT EXISTS cuenta_detraccion VARCHAR(30) NULL COMMENT 'Cuenta de detracciones (Banco de la Nación)',
    ADD COLUMN IF NOT EXISTS cuenta_bcp VARCHAR(30) NULL COMMENT 'Cuenta BCP para pagos';

SHOW COLUMNS FROM sectorjf LIKE 'cuenta_%';
