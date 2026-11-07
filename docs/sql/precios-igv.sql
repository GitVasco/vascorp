-- =============================================================================
-- Precios de servicio con o sin IGV, por taller
-- =============================================================================
-- Ejecutar UNA vez en BD vasco (MariaDB). Repetible.
-- sectorjf.precios_igv = 1  -> los precios por docena del taller YA incluyen IGV:
--                             Neto = total / 1.18, IGV = total - neto, Total = suma de los precios.
-- sectorjf.precios_igv = 0  -> los precios son netos (sin IGV):
--                             Neto = suma de los precios, IGV = 18% encima, Total = neto + IGV.
-- La orden de servicio y la liquidación guardan una copia de la marca al emitirse
-- (el documento impreso no cambia si luego se cambia el taller).
-- =============================================================================

ALTER TABLE sectorjf
    ADD COLUMN IF NOT EXISTS precios_igv TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = los precios por docena del taller ya incluyen IGV';

ALTER TABLE ordenes_servicio_prodjf
    ADD COLUMN IF NOT EXISTS precios_igv TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Copia de sectorjf.precios_igv al emitir';

ALTER TABLE liquidaciones_servicio_prodjf
    ADD COLUMN IF NOT EXISTS precios_igv TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Copia de sectorjf.precios_igv al liquidar';

-- Talleres a los que ya se les subió el 18% (ejecutar SOLO después de actualizar sus precios):
-- UPDATE sectorjf SET precios_igv = 1 WHERE cod_sector IN ('T6', 'T8');

SELECT cod_sector, nom_sector, precios_igv FROM sectorjf WHERE cod_sector IN ('T6', 'T8');
