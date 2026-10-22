-- =============================================================================
-- Migrar stock "en taller interno" de 10411, 10412, 10413 y 10428 -> servicio T15
-- =============================================================================
-- Objetivo: lo que hoy figura como INTERNO (articulojf.taller + entaller_cabjf
-- con taller VC/T1/T3/...) pase a ser SERVICIO del taller T15 (Seamless Trusas),
-- para que se ingrese por el flujo externo (servicio -> cierre -> ingreso),
-- igual que un envío nuevo desde corte (ver cortes.controlador.php).
--
-- Qué hace por cada cabecera abierta (entaller_cabjf.saldo > 0, taller interno):
--   1) entaller_cabjf.taller        -> 'T15'  (saldo, cantidad, fecha NO cambian)
--   2) servicios_detallejf          -> 1 fila (cantidad = saldo = saldo de la cab,
--                                      cabecera_taller = entaller_cabjf.id)
--   3) serviciosjf                  -> 1 cabecera T15 (total = suma migrada)
--   4) articulojf.taller  -= saldo ; articulojf.servicio += saldo
--
-- NO toca: entallerjf (tickets/operaciones/pagos de trabajadores), stock,
-- alm_corte, ord_corte, cierres, ingresos ni movimientos ya registrados.
-- Los ingresos históricos de esos modelos quedan con su taller original.
--
-- USO: ejecutar PASO 0 y PASO 1 (solo lectura), revisar, y recién entonces
-- PASO 2 (transacción). Probar antes en copia de BD y hacer backup de:
-- entaller_cabjf, articulojf, serviciosjf, servicios_detallejf.
-- Ejecutar en horario sin ingresos/envíos a taller en curso.
-- =============================================================================

SET @usuario := 1;   -- <-- id de usuario que figurará en el servicio generado

-- Modelos a migrar
DROP TEMPORARY TABLE IF EXISTS tmp_mig_modelos;
CREATE TEMPORARY TABLE tmp_mig_modelos (modelo VARCHAR(20) PRIMARY KEY);
INSERT INTO tmp_mig_modelos VALUES ('10411'), ('10412'), ('10413'), ('10428');

-- Cabeceras candidatas (internas con saldo)
DROP TEMPORARY TABLE IF EXISTS tmp_mig_cabs;
CREATE TEMPORARY TABLE tmp_mig_cabs AS
SELECT ec.id, ec.articulo, ec.taller AS taller_origen, ec.saldo, ec.fecha
FROM entaller_cabjf ec
INNER JOIN articulojf a ON a.articulo = ec.articulo
INNER JOIN tmp_mig_modelos m ON m.modelo = a.modelo
WHERE ec.saldo > 0
  AND (
        UPPER(ec.taller) = 'VC'
        OR EXISTS (SELECT 1 FROM sectorjf s
                   WHERE s.cod_sector = ec.taller AND (s.tipo = 0 OR s.tipo IS NULL))
      );

-- ---------------------------------------------------------------------------
-- PASO 0 (lectura): qué se migraría
-- ---------------------------------------------------------------------------
SELECT c.id AS cab, c.taller_origen, c.articulo, c.saldo, c.fecha
FROM tmp_mig_cabs c ORDER BY c.articulo, c.id;

-- Cuadre por artículo: articulojf.taller vs saldo de cabeceras internas.
-- Si "taller" < "saldo_cabs" la migración dejaría taller en negativo -> revisar.
SELECT a.articulo, a.taller AS taller_actual, a.servicio AS servicio_actual,
       SUM(c.saldo) AS saldo_cabs
FROM tmp_mig_cabs c
INNER JOIN articulojf a ON a.articulo = c.articulo
GROUP BY a.articulo, a.taller, a.servicio
ORDER BY a.articulo;

-- ---------------------------------------------------------------------------
-- PASO 1 (lectura): conflictos que conviene resolver antes
-- ---------------------------------------------------------------------------
-- a) Tickets abiertos (entallerjf) ligados a esas cabeceras. Al terminarlos,
--    el sistema descuenta entaller_cabjf.saldo; con la cab ya en T15 habría
--    doble descuento. Terminarlos/anularlos ANTES de migrar, o migrar sin ellos.
SELECT e.id_cabecera, e.codigo, e.articulo, e.cantidad, e.estado, e.trabajador
FROM entallerjf e
INNER JOIN tmp_mig_cabs c ON c.id = e.id_cabecera
WHERE e.estado IN (1, 2)
ORDER BY e.id_cabecera;

-- b) Artículos que quedarían con taller negativo
SELECT a.articulo, a.taller, SUM(c.saldo) AS saldo_cabs
FROM tmp_mig_cabs c INNER JOIN articulojf a ON a.articulo = c.articulo
GROUP BY a.articulo, a.taller
HAVING a.taller < SUM(c.saldo);

-- c) Cabeceras de años anteriores (los ingresos solo buscan cabs del año actual)
SELECT id, articulo, saldo, fecha FROM tmp_mig_cabs WHERE YEAR(fecha) <> YEAR(NOW());

-- ---------------------------------------------------------------------------
-- PASO 2 (escritura): ejecutar solo si PASO 0/1 están conformes
-- ---------------------------------------------------------------------------
START TRANSACTION;

-- Código del servicio (misma regla que mdlObtenerOCrearServicioDelDia)
SET @cod_serv := CONCAT('T15', LPAD((SELECT COUNT(codigo) + 1 FROM serviciosjf), 4, '0'));
SET @total := (SELECT COALESCE(SUM(saldo), 0) FROM tmp_mig_cabs);

INSERT INTO serviciosjf (codigo, usuario, taller, total, fecha, estado)
SELECT @cod_serv, @usuario, 'T15', @total, NOW(), 'ACTIVO'
FROM DUAL WHERE @total > 0;

INSERT INTO servicios_detallejf (codigo, articulo, cantidad, saldo, cabecera_taller)
SELECT @cod_serv, c.articulo, c.saldo, c.saldo, c.id
FROM tmp_mig_cabs c;

-- Stock espejo: de taller interno a servicio
UPDATE articulojf a
INNER JOIN (SELECT articulo, SUM(saldo) AS q FROM tmp_mig_cabs GROUP BY articulo) x
        ON x.articulo = a.articulo
SET a.taller   = GREATEST(a.taller - x.q, 0),
    a.servicio = a.servicio + x.q;

-- Cabeceras pasan a T15
UPDATE entaller_cabjf ec
INNER JOIN tmp_mig_cabs c ON c.id = ec.id
SET ec.taller = 'T15';

-- ---------------------------------------------------------------------------
-- PASO 3: verificar y confirmar (o ROLLBACK si algo no cuadra)
-- ---------------------------------------------------------------------------
SELECT @cod_serv AS servicio, @total AS total_migrado;
SELECT d.articulo, d.cantidad, d.saldo, d.cabecera_taller
FROM servicios_detallejf d WHERE d.codigo = @cod_serv ORDER BY d.articulo;
SELECT a.articulo, a.taller, a.servicio
FROM articulojf a
INNER JOIN tmp_mig_modelos m ON m.modelo = a.modelo
WHERE a.taller > 0 OR a.servicio > 0 ORDER BY a.articulo;

-- COMMIT;     -- <-- descomentar si todo cuadra
-- ROLLBACK;   -- <-- o deshacer
