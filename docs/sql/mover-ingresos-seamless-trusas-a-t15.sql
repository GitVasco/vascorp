-- =============================================================================
-- Reasignar a T15 (Seamless Trusas) los INGRESOS de 10411, 10412, 10413, 10428
-- registrados por el flujo de taller interno. NO migra stock ni saldos.
-- =============================================================================
-- Estrategia: lo que ya está en taller interno sigue su flujo normal (se ingresa
-- eligiendo el taller interno, p. ej. T3, y descuenta articulojf.taller /
-- entaller_cabjf.saldo). Este script se corre cuando se quiera (idempotente) y
-- cambia SOLO la etiqueta de taller de esos ingresos a T15, para que reportes y
-- producción los vean en el taller nuevo. Lo nuevo entra directo por T15.
--
-- Solo mueve DOCUMENTOS E20 cuyo detalle sea 100% de esos modelos. Los documentos
-- mixtos (con otros modelos) se listan y NO se tocan.
--
-- IMPORTANTE: anular/editar un ingreso decide interno vs servicio por el taller de
-- la cabecera. Un ingreso ya movido a T15 NO debe anularse desde la pantalla:
-- primero revertirlo con la sección REVERTIR de abajo.
--
-- Backup previo: movimientos_cabecerajf y movimientosjf_2026.
-- =============================================================================

SET @desde := '2026-01-01';   -- <-- solo ingresos desde esta fecha

CREATE TABLE IF NOT EXISTS ingreso_taller_migradojf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    documento       VARCHAR(30) NOT NULL,
    taller_original VARCHAR(10) NOT NULL,
    taller_nuevo    VARCHAR(10) NOT NULL,
    fecha_mig       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doc (documento)
);

DROP TEMPORARY TABLE IF EXISTS tmp_mig_modelos;
CREATE TEMPORARY TABLE tmp_mig_modelos (modelo VARCHAR(20) PRIMARY KEY);
INSERT INTO tmp_mig_modelos VALUES ('10411'), ('10412'), ('10413'), ('10428');

-- Documentos E20 internos con al menos un artículo de esos modelos
DROP TEMPORARY TABLE IF EXISTS tmp_mig_docs;
CREATE TEMPORARY TABLE tmp_mig_docs AS
SELECT mc.documento,
       mc.taller AS taller_original,
       SUM(m.modelo IS NOT NULL) AS lineas_objetivo,
       COUNT(*)                  AS lineas_total
FROM movimientos_cabecerajf mc
INNER JOIN movimientosjf_2026 d ON d.documento = mc.documento AND d.tipo = 'E20'
INNER JOIN articulojf a ON a.articulo = d.articulo
LEFT JOIN tmp_mig_modelos m ON m.modelo = a.modelo
WHERE mc.tipo = 'E20'
  AND mc.fecha >= @desde
  AND mc.taller <> 'T15'
  AND (
        UPPER(mc.taller) = 'VC'
        OR EXISTS (SELECT 1 FROM sectorjf s
                   WHERE s.cod_sector = mc.taller AND (s.tipo = 0 OR s.tipo IS NULL))
      )
GROUP BY mc.documento, mc.taller
HAVING lineas_objetivo > 0;

-- ---------------------------------------------------------------------------
-- PASO 0 (lectura): qué se movería y qué queda fuera por ser mixto
-- ---------------------------------------------------------------------------
SELECT documento, taller_original, lineas_total, 'SE MUEVE' AS accion
FROM tmp_mig_docs WHERE lineas_objetivo = lineas_total
UNION ALL
SELECT documento, taller_original, lineas_total, 'MIXTO - NO SE TOCA'
FROM tmp_mig_docs WHERE lineas_objetivo < lineas_total
ORDER BY accion, documento;

-- ---------------------------------------------------------------------------
-- PASO 1 (escritura): mover cabecera + detalle, guardando el taller original
-- ---------------------------------------------------------------------------
START TRANSACTION;

INSERT IGNORE INTO ingreso_taller_migradojf (documento, taller_original, taller_nuevo)
SELECT documento, taller_original, 'T15'
FROM tmp_mig_docs WHERE lineas_objetivo = lineas_total;

UPDATE movimientos_cabecerajf mc
INNER JOIN ingreso_taller_migradojf g ON g.documento = mc.documento AND g.taller_nuevo = 'T15'
INNER JOIN tmp_mig_docs t ON t.documento = g.documento AND t.lineas_objetivo = t.lineas_total
SET mc.taller = 'T15'
WHERE mc.tipo = 'E20';

UPDATE movimientosjf_2026 d
INNER JOIN tmp_mig_docs t ON t.documento = d.documento AND t.lineas_objetivo = t.lineas_total
SET d.taller = 'T15'
WHERE d.tipo = 'E20';

-- Verificación
SELECT g.documento, g.taller_original, mc.taller AS cab_ahora,
       (SELECT GROUP_CONCAT(DISTINCT d.taller) FROM movimientosjf_2026 d
         WHERE d.documento = g.documento) AS detalle_ahora
FROM ingreso_taller_migradojf g
INNER JOIN movimientos_cabecerajf mc ON mc.documento = g.documento
ORDER BY g.id DESC;

-- COMMIT;     -- <-- si todo cuadra
-- ROLLBACK;   -- <-- o deshacer

-- ---------------------------------------------------------------------------
-- REVERTIR (por documento, p. ej. antes de anular un ingreso desde la pantalla)
-- ---------------------------------------------------------------------------
-- SET @doc := 'XXXXXXX';
-- UPDATE movimientos_cabecerajf mc JOIN ingreso_taller_migradojf g ON g.documento = mc.documento
--    SET mc.taller = g.taller_original WHERE g.documento = @doc;
-- UPDATE movimientosjf_2026 d JOIN ingreso_taller_migradojf g ON g.documento = d.documento
--    SET d.taller = g.taller_original WHERE g.documento = @doc;
-- DELETE FROM ingreso_taller_migradojf WHERE documento = @doc;
