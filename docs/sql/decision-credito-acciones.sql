-- =============================================================================
-- Bitácora de acciones de crédito (aprobaciones + objeciones + extras)
-- =============================================================================
-- Objetivo: historial único para la vista de Créditos/Cobranzas.
-- Guarda quién hizo qué y cuándo, sin depender de temporaljf.usuario_estado
-- (ese campo se pisa cuando el pedido pasa a APT/CONFIRMADO/FACTURADO).
--
-- Tipos de acción previstos (tipo_accion):
--   APROBADO            → pedido pasó GENERADO → APROBADO
--   OBJECION            → se registró motivo de no aprobación
--   OBJECION_CERRADA    → se cerró/revirtió una objeción vigente (opcional)
--   ANULADO             → pedido anulado desde Centro de Decisiones
--   CONTROL_REGISTRADO  → aprobación condicionada; queda control pendiente
--   DESPACHO_AUTORIZADO → control cumplido; pedido liberado para APT
--   CATEGORIA_ASIGNADA  → categoría comercial asignada al aprobar (opcional;
--                         también puede ir embebida en la fila APROBADO)
--
-- Relación con tablas existentes:
--   - Objeciones siguen viviendo en decision_credito_pedidojf.
--     Al registrarlas, se insertará además una fila OBJECION aquí
--     (id_decision apunta a esa tabla).
--   - Aprobaciones NO tienen hoy historial fiable; esta tabla lo cubre.
--
-- Ejecutar manualmente en la BD. Idempotente (IF NOT EXISTS).
-- =============================================================================

CREATE TABLE IF NOT EXISTS decision_credito_accionjf (
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Pedido / cliente al momento de la acción
    codigo_pedido INT(11) NOT NULL COMMENT 'temporaljf.codigo',
    codigo_cliente VARCHAR(20) NOT NULL,

    -- Clasificación
    tipo_accion VARCHAR(40) NOT NULL COMMENT 'APROBADO, OBJECION, OBJECION_CERRADA, ANULADO, CATEGORIA_ASIGNADA',
    origen VARCHAR(40) NOT NULL DEFAULT 'centro_decisiones'
        COMMENT 'centro_decisiones, facturacion, sistema',

    -- Snapshot del pedido (útil si luego cambia de estado o monto)
    pedido_total DECIMAL(15,2) NULL COMMENT 'Monto del pedido al momento de la acción',
    pedido_lista VARCHAR(20) NULL COMMENT 'precio1=USD, resto=PEN',
    pedido_estado_resultado VARCHAR(20) NULL
        COMMENT 'Estado del pedido tras la acción (ej. APROBADO, GENERADO, ANULADO)',

    -- Si la acción es objeción / cierre de objeción
    id_decision INT(11) NULL COMMENT 'FK lógica a decision_credito_pedidojf.id',
    motivo_codigo VARCHAR(50) NULL COMMENT 'Catálogo creditos-motivos.config.json',
    comentario TEXT NULL,

    -- Si al aprobar se asignó categoría comercial
    id_categoria INT(11) NULL COMMENT 'categorias_clientesjf.id',
    categoria_codigo VARCHAR(20) NULL COMMENT 'Sigla ej. DIST, MAYO',
    categoria_entidad VARCHAR(20) NULL COMMENT 'cliente | grupo',
    categoria_codigo_entidad VARCHAR(20) NULL COMMENT 'código del cliente o grupo al que se asignó',

    -- Quién / cuándo
    usuario_id INT(11) NOT NULL COMMENT 'usuariosjf.id',
    detalle TEXT NULL COMMENT 'Texto libre / JSON corto de apoyo',
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_dca_fecha (fecha),
    KEY idx_dca_pedido (codigo_pedido),
    KEY idx_dca_cliente (codigo_cliente),
    KEY idx_dca_usuario_fecha (usuario_id, fecha),
    KEY idx_dca_tipo_fecha (tipo_accion, fecha),
    KEY idx_dca_decision (id_decision)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
  COMMENT='Bitácora unificada de acciones de crédito por pedido';
