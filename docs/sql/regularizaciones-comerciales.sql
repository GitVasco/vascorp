-- =============================================================================
-- Regularizaciones comerciales excepcionales (fraude junio)
-- =============================================================================
-- Módulo aislado: refleja en VascoPro pagos comprobados no ingresados a Vascorp,
-- sin modificar cuenta_ctejf ni contabilidad.
--
-- Ejecutar en copia de BD antes de producción.
-- Idempotente (CREATE IF NOT EXISTS). Sin ALTER ni FKs a tablas legacy.
-- =============================================================================

CREATE TABLE IF NOT EXISTS regularizacion_comercialjf (
    id                          BIGINT(20) NOT NULL AUTO_INCREMENT,
    cuenta_cte_id               INT(11) NOT NULL
        COMMENT 'Referencia lógica a cuenta_ctejf.id (cargo oficial)',
    tipo_doc                    VARCHAR(10) NOT NULL
        COMMENT 'Snapshot cuenta_ctejf.tipo_doc',
    num_cta                     VARCHAR(20) NOT NULL
        COMMENT 'Snapshot cuenta_ctejf.num_cta',
    cliente_codigo              VARCHAR(15) NOT NULL
        COMMENT 'Snapshot cuenta_ctejf.cliente',
    monto_original              DECIMAL(14,2) NOT NULL
        COMMENT 'Pago comercial comprobado al alta; inmutable',
    monto_aplicable             DECIMAL(14,2) NOT NULL
        COMMENT 'Porción aún restada del saldo comercial',
    fecha_pago_cliente          DATE NOT NULL
        COMMENT 'Fecha en que el cliente pagó (negocio)',
    fecha_registro              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    saldo_oficial_al_registrar  DECIMAL(14,2) NOT NULL
        COMMENT 'cuenta_ctejf.saldo al momento del alta',
    corte_movimiento_oficial_id BIGINT(20) NULL
        COMMENT 'Último abono oficial (cuenta_ctejf.id tip_mov=-) visto al crear',
    estado                      VARCHAR(30) NOT NULL DEFAULT 'ACTIVA'
        COMMENT 'ACTIVA, RESUELTA_AUTOMATICA, ANULADA, REQUIERE_REVISION',
    motivo                      VARCHAR(255) NOT NULL,
    sustento_referencia         VARCHAR(100) NOT NULL
        COMMENT 'OP / nro. recibo / código de pago',
    observacion                 VARCHAR(500) NULL,
    usuario_registro_id         INT(11) NOT NULL
        COMMENT 'usuariosjf.id',
    usuario_anulacion_id        INT(11) NULL,
    fecha_anulacion             DATETIME NULL,
    motivo_anulacion            VARCHAR(255) NULL,
    version                     INT(11) NOT NULL DEFAULT 1
        COMMENT 'Control optimista',
    PRIMARY KEY (id),
    KEY idx_rc_cuenta_estado (cuenta_cte_id, estado),
    KEY idx_rc_doc_cliente_estado (tipo_doc, num_cta, cliente_codigo, estado),
    KEY idx_rc_estado_fecha (estado, fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Regularizaciones comerciales excepcionales (solo efecto VascoPro)';

CREATE TABLE IF NOT EXISTS regularizacion_comercial_eventojf (
    id                          BIGINT(20) NOT NULL AUTO_INCREMENT,
    regularizacion_id           BIGINT(20) NOT NULL
        COMMENT 'regularizacion_comercialjf.id',
    tipo_evento                 VARCHAR(40) NOT NULL
        COMMENT 'ALTA, ANULACION, RESOLUCION_PARCIAL, RESOLUCION_TOTAL, REQUIERE_REVISION',
    estado_anterior             VARCHAR(30) NULL,
    estado_nuevo                VARCHAR(30) NULL,
    monto_delta                 DECIMAL(14,2) NULL
        COMMENT 'Cambio aplicado a monto_aplicable (negativo al reducir)',
    monto_aplicable_resultante  DECIMAL(14,2) NULL,
    movimiento_oficial_id       BIGINT(20) NULL
        COMMENT 'cuenta_ctejf.id del abono oficial consumido, si aplica',
    detalle_json                TEXT NULL
        COMMENT 'Contexto adicional serializado',
    usuario_id                  INT(11) NULL
        COMMENT 'usuariosjf.id; NULL si origen AUTO_SYNC',
    fecha                       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    origen                      VARCHAR(20) NOT NULL DEFAULT 'USUARIO'
        COMMENT 'USUARIO, AUTO_SYNC',
    PRIMARY KEY (id),
    KEY idx_rce_regularizacion (regularizacion_id),
    KEY idx_rce_fecha (fecha),
    KEY idx_rce_movimiento (movimiento_oficial_id),
    KEY idx_rce_tipo_fecha (tipo_evento, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Historial append-only de regularizaciones comerciales';
