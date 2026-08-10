-- =============================================================================
-- Catálogo de reprocesos de materia prima
-- =============================================================================
-- Procesos (SUBLIMAR, TENIR, etc.) siguen en controladores/mp-reprocesos.config.json
-- Este script crea la tabla de relaciones MP origen → proceso → MP destino.
-- Ejecutar manualmente en la BD. Idempotente donde es posible.
-- =============================================================================

CREATE TABLE IF NOT EXISTS mp_reprocesojf (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    cod_pro_origen   VARCHAR(20)  NOT NULL COMMENT 'producto.CodPro origen',
    proceso          VARCHAR(30)  NOT NULL COMMENT 'código en mp-reprocesos.config.json',
    cod_pro_destino  VARCHAR(20)  NOT NULL COMMENT 'producto.CodPro destino',
    observacion      VARCHAR(200) NULL,
    estado           TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    usureg           VARCHAR(50)  NULL,
    fecreg           DATETIME     NULL,
    usumod           VARCHAR(50)  NULL,
    fecmod           DATETIME     NULL,
    UNIQUE KEY uk_mp_reproceso (cod_pro_origen, proceso, cod_pro_destino),
    KEY idx_mp_rep_origen (cod_pro_origen),
    KEY idx_mp_rep_destino (cod_pro_destino),
    KEY idx_mp_rep_proceso (proceso),
    KEY idx_mp_rep_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Catálogo MP origen → proceso → MP destino';

-- Semilla: registros migrados desde mp-reprocesos.catalogo.json (ago 2026)
INSERT INTO mp_reprocesojf
    (cod_pro_origen, proceso, cod_pro_destino, observacion, estado, usureg, fecreg, usumod, fecmod)
SELECT * FROM (
    SELECT '02678' AS cod_pro_origen, 'SUBLIMAR' AS proceso, '04951' AS cod_pro_destino,
           '' AS observacion, 1 AS estado,
           'Joel Medrano' AS usureg, '2026-08-10 19:02:42' AS fecreg,
           'Joel Medrano' AS usumod, '2026-08-10 19:02:42' AS fecmod
    UNION ALL
    SELECT '09245', 'SUBLIMAR', '09365', '', 1,
           'Joel Medrano', '2026-08-10 14:06:26',
           'Joel Medrano', '2026-08-10 14:06:26'
    UNION ALL
    SELECT '02678', 'SUBLIMAR', '04555', '', 1,
           'Joel Medrano', '2026-08-10 14:16:47',
           'Joel Medrano', '2026-08-10 14:16:47'
) AS seed
WHERE NOT EXISTS (
    SELECT 1
    FROM mp_reprocesojf t
    WHERE t.cod_pro_origen = seed.cod_pro_origen
      AND t.proceso = seed.proceso
      AND t.cod_pro_destino = seed.cod_pro_destino
);
