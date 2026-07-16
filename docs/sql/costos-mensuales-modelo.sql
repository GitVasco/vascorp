-- Costos unitarios mensuales por modelo.
-- Ejecutar antes de habilitar el módulo Costos mensuales.

CREATE TABLE IF NOT EXISTS costos_modelo_mensualjf (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    modelo               VARCHAR(50) NOT NULL COMMENT 'modelojf.modelo',
    anio                 SMALLINT NOT NULL,
    mes                  TINYINT NOT NULL,
    costo_unitario       DECIMAL(14,4) NOT NULL DEFAULT 0 COMMENT 'Costo directo unitario sin IGV',
    fuente               VARCHAR(100) NULL,
    observacion          VARCHAR(500) NULL,
    estado               VARCHAR(20) NOT NULL DEFAULT 'borrador',
    usuario_registro     INT NULL,
    fecha_registro       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_aprobacion   INT NULL,
    fecha_aprobacion     DATETIME NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion   DATETIME NULL,
    UNIQUE KEY uk_cmm_modelo_periodo (modelo, anio, mes),
    KEY idx_cmm_periodo_estado (anio, mes, estado),
    KEY idx_cmm_modelo_estado (modelo, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS costos_modelo_mensual_historialjf (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    costo_modelo_id  INT NOT NULL,
    modelo           VARCHAR(50) NOT NULL,
    anio             SMALLINT NOT NULL,
    mes              TINYINT NOT NULL,
    costo_unitario   DECIMAL(14,4) NOT NULL,
    fuente           VARCHAR(100) NULL,
    observacion      VARCHAR(500) NULL,
    estado           VARCHAR(20) NOT NULL,
    accion           VARCHAR(30) NOT NULL COMMENT 'creado, modificado, aprobado, anulado o reabierto',
    motivo           VARCHAR(500) NULL,
    usuario          INT NULL,
    fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cmmh_registro_fecha (costo_modelo_id, fecha),
    KEY idx_cmmh_modelo_periodo (modelo, anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
