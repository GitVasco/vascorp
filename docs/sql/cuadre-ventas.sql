-- =============================================================================
-- Cuadre de ventas del día
-- =============================================================================
-- Armado de pagos (efectivo / Yape / depósito / tarjeta / link / Culqi)
-- sobre boletas y facturas del usuario que las registró.
-- tipo_medio guarda el cod_pago (80, 15, 05, 17, 16, 14).
-- num_ope es opcional; si coincide con Abonos, se reserva id_abono.
--
-- Ejecutar en BD vasco. Idempotente en CREATE (IF NOT EXISTS).
-- El ALTER de abonosjf: si id_cuadre ya existe, ignorar el error.
-- =============================================================================

CREATE TABLE IF NOT EXISTS cuadre_ventasjf (
    id                      BIGINT(20) NOT NULL AUTO_INCREMENT,
    fecha_ventas            DATE NOT NULL
        COMMENT 'Día de las ventas cuadradas',
    usuario_ventas          VARCHAR(20) NOT NULL
        COMMENT 'cuenta_ctejf.usuario (quien registró las ventas)',
    cliente                 VARCHAR(20) NOT NULL
        COMMENT 'Un solo cliente por lote',
    estado                  VARCHAR(20) NOT NULL DEFAULT 'BORRADOR'
        COMMENT 'BORRADOR, REGISTRADO, VALIDADO, PROCESADO, ANULADO, RECHAZADO',
    total_docs              DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_pagos             DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    usuario_registro        INT(11) NULL
        COMMENT 'usuariosjf.id quien armó',
    fecha_registro          DATETIME NULL,
    usuario_validacion      INT(11) NULL
        COMMENT 'usuariosjf.id Cuentas corrientes',
    fecha_validacion        DATETIME NULL,
    usuario_proceso         INT(11) NULL
        COMMENT 'usuariosjf.id quien mandó a cte (fase posterior)',
    fecha_proceso           DATETIME NULL,
    observacion             VARCHAR(500) NULL,
    creado_en               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cv_estado (estado),
    KEY idx_cv_usuario_fecha (usuario_ventas, fecha_ventas),
    KEY idx_cv_cliente (cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Cabecera de cuadre de ventas del día (no toca cte hasta PROCESADO)';

CREATE TABLE IF NOT EXISTS cuadre_ventas_docjf (
    id                      BIGINT(20) NOT NULL AUTO_INCREMENT,
    id_cuadre               BIGINT(20) NOT NULL
        COMMENT 'cuadre_ventasjf.id',
    id_cuenta               INT(11) NOT NULL
        COMMENT 'cuenta_ctejf.id del cargo (solo lectura hasta procesar)',
    tipo_doc                VARCHAR(10) NOT NULL,
    num_cta                 VARCHAR(30) NOT NULL,
    cliente                 VARCHAR(20) NOT NULL,
    monto_doc               DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    monto_aplicar           DECIMAL(14,2) NOT NULL DEFAULT 0.00
        COMMENT 'Puede ser parcial (<= saldo del cargo)',
    PRIMARY KEY (id),
    KEY idx_cvd_cuadre (id_cuadre),
    KEY idx_cvd_cuenta (id_cuenta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Documentos (boletas/facturas) de un cuadre';

CREATE TABLE IF NOT EXISTS cuadre_ventas_medjf (
    id                      BIGINT(20) NOT NULL AUTO_INCREMENT,
    id_cuadre               BIGINT(20) NOT NULL
        COMMENT 'cuadre_ventasjf.id',
    tipo_medio              VARCHAR(20) NOT NULL
        COMMENT 'cod_pago: 80 efectivo, 15 yape, 05 deposito, 17 tarjeta, 16 link, 14 culqi',
    id_abono                INT(11) NULL
        COMMENT 'abonosjf.id si la OP estaba en Abonos',
    num_ope                 VARCHAR(50) NULL
        COMMENT 'OP del medio (opcional; efectivo no lleva)',
    monto                   DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_cvm_cuadre (id_cuadre),
    KEY idx_cvm_abono (id_abono),
    KEY idx_cvm_ope (num_ope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Medios de pago de un cuadre';

-- Reserva de OP: el abono sigue existiendo; se marca el lote que lo tiene.
-- No borrar ni recortar abonosjf desde este módulo hasta PROCESADO.
ALTER TABLE abonosjf
    ADD COLUMN id_cuadre BIGINT(20) NULL
        COMMENT 'cuadre_ventasjf.id si la OP está reservada en un lote activo';

CREATE INDEX idx_abonosjf_id_cuadre ON abonosjf (id_cuadre);

-- Paso 7: lote VALIDADO → INSERT cuenta_ctejf tip_mov='-' con cod_pago
-- del medio, actualizar saldo/estado del cargo, recortar o borrar abonosjf
-- según el monto usado, estado PROCESADO. No reejecutar un lote ya procesado.
