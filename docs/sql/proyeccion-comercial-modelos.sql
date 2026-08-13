-- Proyección comercial de ventas por modelo (modelo–mes).
-- Separada de articulojf.proyeccion (flujo operativo de producción).
-- Ejecutar antes de usar el módulo proyeccion-comercial-modelos.

CREATE TABLE IF NOT EXISTS proyeccion_comercial_periodojf (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    anio_desde        SMALLINT NOT NULL,
    mes_desde         TINYINT NOT NULL,
    anio_hasta        SMALLINT NOT NULL,
    mes_hasta         TINYINT NOT NULL,
    nombre            VARCHAR(120) NULL COMMENT 'Ej. Plan Jul–Dic 2026',
    estado            VARCHAR(20) NOT NULL DEFAULT 'BORRADOR' COMMENT 'BORRADOR | PARCIAL | PUBLICADO | CERRADO',
    creado_por        INT NULL,
    creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_por   INT NULL,
    actualizado_en    DATETIME NULL,
    publicado_por     INT NULL,
    publicado_en      DATETIME NULL,
    cerrado_por       INT NULL,
    cerrado_en        DATETIME NULL,
    KEY idx_pcp_estado (estado),
    KEY idx_pcp_rango (anio_desde, mes_desde, anio_hasta, mes_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS proyeccion_comercial_modelojf (
    id                         INT AUTO_INCREMENT PRIMARY KEY,
    id_periodo                 INT NOT NULL,
    anio                       SMALLINT NOT NULL,
    mes                        TINYINT NOT NULL,
    modelo                     VARCHAR(50) NOT NULL,
    unidades_sugeridas         INT NOT NULL DEFAULT 0 COMMENT 'Snapshot al guardar/publicar',
    unidades_ajustes           INT NOT NULL DEFAULT 0 COMMENT 'Suma de factores',
    unidades_oficiales         INT NOT NULL DEFAULT 0,
    precio_lista9_snapshot     DECIMAL(14,4) NULL COMMENT 'Congelado al publicar; un precio por modelo',
    importe_lista9_proyectado  DECIMAL(18,4) NULL,
    formula_version            VARCHAR(40) NULL COMMENT 'Ej. v1-promedio-ponderado',
    observacion                VARCHAR(500) NULL,
    estado_linea               VARCHAR(20) NOT NULL DEFAULT 'BORRADOR' COMMENT 'BORRADOR | PUBLICADO | CERRADO',
    creado_por                 INT NULL,
    creado_en                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_por            INT NULL,
    actualizado_en             DATETIME NULL,
    publicado_por              INT NULL,
    publicado_en               DATETIME NULL,
    UNIQUE KEY uk_pcm_modelo_mes (anio, mes, modelo),
    KEY idx_pcm_periodo (id_periodo),
    KEY idx_pcm_estado (estado_linea),
    KEY idx_pcm_periodo_mes (id_periodo, anio, mes),
    CONSTRAINT fk_pcm_periodo
        FOREIGN KEY (id_periodo) REFERENCES proyeccion_comercial_periodojf (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS proyeccion_comercial_factorjf (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyeccion_modelo  INT NOT NULL,
    tipo                  VARCHAR(40) NOT NULL,
    titulo                VARCHAR(120) NOT NULL,
    descripcion           VARCHAR(1000) NULL,
    fecha_desde           DATE NULL,
    fecha_hasta           DATE NULL,
    ajuste_unidades       INT NOT NULL DEFAULT 0,
    impacto_pct           DECIMAL(10,4) NULL COMMENT 'Derivado o explícito; no reemplaza ajuste_unidades',
    precio_anterior       DECIMAL(14,4) NULL,
    precio_nuevo          DECIMAL(14,4) NULL,
    canal_publicidad      VARCHAR(80) NULL,
    inversion_publicidad  DECIMAL(14,2) NULL,
    referencia_evidencia  VARCHAR(500) NULL,
    activo                TINYINT(1) NOT NULL DEFAULT 1,
    creado_por            INT NULL,
    creado_en             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_por       INT NULL,
    actualizado_en        DATETIME NULL,
    KEY idx_pcf_proyeccion (id_proyeccion_modelo),
    KEY idx_pcf_tipo (tipo),
    CONSTRAINT fk_pcf_proyeccion
        FOREIGN KEY (id_proyeccion_modelo) REFERENCES proyeccion_comercial_modelojf (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS proyeccion_comercial_auditoriajf (
    id                    BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_proyeccion_modelo  INT NOT NULL,
    accion                VARCHAR(30) NOT NULL COMMENT 'CREAR | ACTUALIZAR | PUBLICAR | CERRAR | REABRIR',
    campo                 VARCHAR(80) NULL,
    valor_anterior        VARCHAR(500) NULL,
    valor_nuevo           VARCHAR(500) NULL,
    motivo                VARCHAR(500) NULL,
    usuario               INT NULL,
    fecha                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pca_proyeccion_fecha (id_proyeccion_modelo, fecha),
    KEY idx_pca_accion (accion),
    CONSTRAINT fk_pca_proyeccion
        FOREIGN KEY (id_proyeccion_modelo) REFERENCES proyeccion_comercial_modelojf (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
