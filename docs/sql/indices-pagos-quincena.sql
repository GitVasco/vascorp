-- =============================================================================
-- Índices sugeridos para vista /pagos y reporte Excel trusas por producción
-- Archivo: docs/sql/indices-pagos-quincena.sql
-- =============================================================================
-- IMPORTANTE — LEER ANTES DE EJECUTAR
-- -----------------------------------------------------------------------------
-- CREATE INDEX en tablas grandes (entallerjf ~170k+ filas) puede:
--   • Bloquear escrituras varios minutos (o más) mientras se construye el índice
--   • Dejar la BD “pensando” / lenta para todo el sistema
--   • No es obligatorio si la consulta ya responde rápido (~0.1s) tras la optimización PHP
--
-- Recomendación:
--   1) Ejecutar UN índice a la vez, en horario de baja carga (noche / fin de semana)
--   2) Antes de cada uno: SHOW INDEX FROM <tabla>;
--   3) Si el índice ya existe, omitir esa línea
--   4) Monitorear: SHOW PROCESSLIST;  (si algo queda colgado: revisar antes de matar)
--
-- Consultas objetivo:
--   entallerjf.fecha_terminado >= :inicio AND < :fin + 1 día
--   trabajadorjf.cod_tip_tra = 1, sector opcional
--   exclusión BRASIER/SEAMLESS vía LEFT JOIN articulojf/modelojf
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 0) Diagnóstico previo (ejecutar manualmente, no crea índices)
-- -----------------------------------------------------------------------------
-- SHOW INDEX FROM entallerjf;
-- SHOW INDEX FROM trabajadorjf;
-- SHOW INDEX FROM articulojf;
-- SHOW INDEX FROM modelojf;
-- SELECT COUNT(*) FROM entallerjf;

-- -----------------------------------------------------------------------------
-- 1) entallerjf — el más pesado; aplicar solo si falta y en ventana de mantenimiento
-- -----------------------------------------------------------------------------
-- Prioridad: idx_entallerjf_fecha_trabajador (rango por quincena)

-- CREATE INDEX idx_entallerjf_fecha_trabajador
--     ON entallerjf (fecha_terminado, trabajador);

-- CREATE INDEX idx_entallerjf_articulo
--     ON entallerjf (articulo);

-- -----------------------------------------------------------------------------
-- 2) trabajadorjf — filtro destajeros y sector (tabla pequeña, suele ser rápido)
-- -----------------------------------------------------------------------------

-- CREATE INDEX idx_trabajadorjf_cod_tra_tip_sector
--     ON trabajadorjf (cod_tra, cod_tip_tra, sector);

-- -----------------------------------------------------------------------------
-- 3) articulojf / modelojf — exclusión BRASIER / SEAMLESS (tablas medianas)
-- -----------------------------------------------------------------------------

-- CREATE INDEX idx_articulojf_articulo_modelo
--     ON articulojf (articulo, modelo);

-- CREATE INDEX idx_modelojf_modelo_tipo
--     ON modelojf (modelo, tipo);
