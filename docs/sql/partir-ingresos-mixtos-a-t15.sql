-- =============================================================================
-- Partir los ingresos E20 MIXTOS: las líneas de 10411, 10412, 10413 y 10428 pasan
-- a un documento NUEVO en T15; el resto de modelos se queda en el documento original.
-- Complementa a mover-ingresos-seamless-trusas-a-t15.sql (que solo mueve los
-- documentos 100% de esos modelos). NO migra stock ni saldos.
--
-- Documento nuevo = <documento original> + '-15'   (ej. T33679 -> T33679-15)
-- Es idempotente: un documento ya partido no se vuelve a partir.
--
-- Backup previo: movimientos_cabecerajf y movimientosjf_2026.
-- =============================================================================

SET @desde := '2026-01-01';

CREATE TABLE IF NOT EXISTS ingreso_split_t15jf (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    documento_orig   VARCHAR(20) NOT NULL,
    documento_nuevo  VARCHAR(20) NOT NULL,
    taller_original  VARCHAR(10) NOT NULL,
    fecha_mig        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orig (documento_orig),
    UNIQUE KEY uq_nuevo (documento_nuevo)
);

DROP TEMPORARY TABLE IF EXISTS tmp_split_modelos;
CREATE TEMPORARY TABLE tmp_split_modelos (modelo VARCHAR(20) PRIMARY KEY);
INSERT INTO tmp_split_modelos VALUES ('10411'), ('10412'), ('10413'), ('10428');

-- Documentos E20 internos MIXTOS (tienen modelos objetivo y de otros)
DROP TEMPORARY TABLE IF EXISTS tmp_split_docs;
CREATE TEMPORARY TABLE tmp_split_docs AS
SELECT mc.documento,
       mc.taller AS taller_original,
       SUM(m.modelo IS NOT NULL) AS lineas_objetivo,
       COUNT(*)                  AS lineas_total
FROM movimientos_cabecerajf mc
INNER JOIN movimientosjf_2026 d ON d.documento = mc.documento AND d.tipo = 'E20'
INNER JOIN articulojf a ON a.articulo = d.articulo
LEFT JOIN tmp_split_modelos m ON m.modelo = a.modelo
WHERE mc.tipo = 'E20'
  AND mc.fecha >= @desde
  AND mc.taller <> 'T15'
  AND (
        UPPER(mc.taller) = 'VC'
        OR EXISTS (SELECT 1 FROM sectorjf s
                   WHERE s.cod_sector = mc.taller AND (s.tipo = 0 OR s.tipo IS NULL))
      )
  AND NOT EXISTS (SELECT 1 FROM ingreso_split_t15jf g WHERE g.documento_orig = mc.documento)
GROUP BY mc.documento, mc.taller
HAVING lineas_objetivo > 0 AND lineas_objetivo < lineas_total;

-- ---------------------------------------------------------------------------
-- PASO 0 (lectura): qué documentos se partirían
-- ---------------------------------------------------------------------------
SELECT documento, CONCAT(documento, '-15') AS documento_nuevo, taller_original,
       lineas_objetivo AS lineas_a_T15, lineas_total - lineas_objetivo AS lineas_que_se_quedan
FROM tmp_split_docs
ORDER BY documento;

-- ---------------------------------------------------------------------------
-- PASO 1 (escritura)
-- ---------------------------------------------------------------------------
START TRANSACTION;

INSERT INTO ingreso_split_t15jf (documento_orig, documento_nuevo, taller_original)
SELECT documento, CONCAT(documento, '-15'), taller_original FROM tmp_split_docs;

-- 1.a Cabecera nueva en T15 (copia de la original; el total se ajusta abajo)
INSERT INTO movimientos_cabecerajf (tipo, usuario, guia, taller, documento, total, fecha, almacen)
SELECT mc.tipo, mc.usuario, mc.guia, 'T15', g.documento_nuevo, 0, mc.fecha, mc.almacen
FROM movimientos_cabecerajf mc
INNER JOIN ingreso_split_t15jf g ON g.documento_orig = mc.documento
INNER JOIN tmp_split_docs t ON t.documento = g.documento_orig
WHERE mc.tipo = 'E20';

-- 1.b Líneas de los 4 modelos: pasan al documento nuevo y a T15
UPDATE movimientosjf_2026 d
INNER JOIN articulojf a ON a.articulo = d.articulo
INNER JOIN tmp_split_modelos m ON m.modelo = a.modelo
INNER JOIN ingreso_split_t15jf g ON g.documento_orig = d.documento
INNER JOIN tmp_split_docs t ON t.documento = g.documento_orig
SET d.documento = g.documento_nuevo,
    d.taller    = 'T15'
WHERE d.tipo = 'E20';

-- 1.c Recalcular totales (suma de cantidades) de ambos documentos
UPDATE movimientos_cabecerajf mc
INNER JOIN ingreso_split_t15jf g ON mc.documento IN (g.documento_orig, g.documento_nuevo)
INNER JOIN tmp_split_docs t ON t.documento = g.documento_orig
SET mc.total = (SELECT COALESCE(SUM(d.cantidad), 0)
                FROM movimientosjf_2026 d
                WHERE d.documento = mc.documento AND d.tipo = 'E20')
WHERE mc.tipo = 'E20';

-- Verificación: cada par original / nuevo con sus líneas y totales
SELECT g.documento_orig, g.documento_nuevo,
       (SELECT mc.total FROM movimientos_cabecerajf mc WHERE mc.documento = g.documento_orig) AS total_orig,
       (SELECT COUNT(*) FROM movimientosjf_2026 d WHERE d.documento = g.documento_orig) AS lineas_orig,
       (SELECT mc.total FROM movimientos_cabecerajf mc WHERE mc.documento = g.documento_nuevo) AS total_nuevo,
       (SELECT COUNT(*) FROM movimientosjf_2026 d WHERE d.documento = g.documento_nuevo) AS lineas_nuevo
FROM ingreso_split_t15jf g
ORDER BY g.id DESC;

-- COMMIT;     -- <-- si todo cuadra
-- ROLLBACK;   -- <-- o deshacer
