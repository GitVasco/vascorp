-- =============================================================================
-- Facturas de exportación — Fase 3B (emitir desde Comprobantes especiales → Exportación)
-- Diseño: docs/efact/EXPORTACION-diseno.md
-- Una fila por factura FX emitida desde la pantalla nueva:
--   - moneda de la cuenta corriente (USD) y tipo de cambio (se puede completar después);
--   - estado del envío a eFact (GENERADO → ENVIADO) con el CSV nuevo.
-- No modifica tablas existentes (ventajf, cuenta_ctejf, movimientos siguen igual).
-- Idempotente.
-- =============================================================================

CREATE TABLE IF NOT EXISTS exp_facturajf (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(3) NOT NULL DEFAULT 'S03',
    documento VARCHAR(20) NOT NULL,
    pedido VARCHAR(20) NOT NULL,
    cliente VARCHAR(20) NOT NULL,
    pais_iso VARCHAR(2) NULL,
    moneda VARCHAR(3) NOT NULL DEFAULT 'USD',
    tipo_cambio DECIMAL(10,4) NULL,
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    estado VARCHAR(12) NOT NULL DEFAULT 'GENERADO',
    archivo VARCHAR(80) NULL,
    usuario_registro VARCHAR(60) NULL,
    usuario_envio VARCHAR(60) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enviado_en DATETIME NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_exp_factura_doc (tipo, documento),
    KEY idx_exp_factura_pedido (pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
