-- =============================================================================
-- Índices sugeridos para Centro de Decisiones
-- Archivo: docs/sql/indices-dashboard-decisiones.sql
-- =============================================================================
-- NO ejecutar a ciegas en producción.
-- 1) Revisar SHOW INDEX de cada tabla.
-- 2) Correr EXPLAIN de las consultas del dashboard.
-- 3) Aplicar solo los que falten, en horario de baja carga.
--
-- Diagnóstico previo (2026-07-13, BD vasco @ 192.168.1.64):
--   - temporaljf: SOLO PRIMARY → índices de pedidos son prioritarios.
--   - ventajf: tiene índices por cliente/fecha/tipo; falta por vendedor+fecha.
--   - cuenta_ctejf: tiene índices por cliente; falta por vendedor+estado+vencimiento.
--   - maestrajf: ya tiene idx_maestrajf_tvend_decisiones (tipo_dato, estado_decisiones, codigo).
--
-- Datos verificados antes de quitar TRIM/UPPER en aplicación:
--   - temporaljf / ventajf / cuenta_ctejf: 0 filas con vendedor <> TRIM(vendedor)
--   - cuenta_ctejf.estado ya en mayúsculas (PENDIENTE/CANCELADO)
--   - ventajf.tipo de facturación ya en S02/S03/S70 sin espacios
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) cuenta_ctejf — cartera y deudas por vendedor (cuello principal ~1.3–1.6s)
-- -----------------------------------------------------------------------------
-- SHOW INDEX FROM cuenta_ctejf;
-- Estado previo: NO existe índice con vendedor como columna líder útil para
--   estado + fecha_ven. Hay índices con vendedor al final (tip_mov, fecha, vendedor).

CREATE INDEX idx_cctejf_dd_vendedor_estado_vencimiento_cliente
    ON cuenta_ctejf (vendedor, estado, fecha_ven, cliente);

-- Subconsultas de deuda vencida por cliente (alertas / generados / atraso)
CREATE INDEX idx_cctejf_dd_cliente_estado_vencimiento
    ON cuenta_ctejf (cliente, estado, fecha_ven);

-- -----------------------------------------------------------------------------
-- 2) temporaljf — pipeline / generados / estancados
-- -----------------------------------------------------------------------------
-- SHOW INDEX FROM temporaljf;
-- Estado previo: únicamente PRIMARY (id).

CREATE INDEX idx_temporal_dd_vendedor_estado_fecha
    ON temporaljf (vendedor, estado, fecha);

CREATE INDEX idx_temporal_dd_estado_fecha
    ON temporaljf (estado, fecha);

CREATE INDEX idx_temporal_codigo
    ON temporaljf (codigo);

-- -----------------------------------------------------------------------------
-- 3) ventajf — facturado del mes y avance de ventas
-- -----------------------------------------------------------------------------
-- SHOW INDEX FROM ventajf;
-- Ya existen: idx_ventajf_cliente_fecha, idx_ventajf_ic_cliente_fecha_tipo, idx_ventajf_fast.
-- Falta soporte directo por vendedor en rango de fechas.

CREATE INDEX idx_ventajf_dd_vendedor_fecha
    ON ventajf (vendedor, fecha);

CREATE INDEX idx_ventajf_dd_fecha_tipo
    ON ventajf (fecha, tipo);

-- =============================================================================
-- Verificación sugerida después de crear índices
-- =============================================================================
-- SHOW INDEX FROM cuenta_ctejf;
-- SHOW INDEX FROM temporaljf;
-- SHOW INDEX FROM ventajf;
--
-- EXPLAIN de cartera optimizada (sin filtro de vendedor):
/*
EXPLAIN SELECT
    COUNT(DISTINCT ct.cliente) AS clientes_con_deuda,
    SUM(ct.saldo) AS deuda_total,
    SUM(CASE WHEN ct.fecha_ven < CURDATE() THEN ct.saldo ELSE 0 END) AS deuda_vencida,
    COUNT(DISTINCT CASE WHEN ct.fecha_ven < CURDATE() THEN ct.cliente END) AS clientes_vencidos
FROM cuenta_ctejf ct
INNER JOIN maestrajf mv
    ON mv.codigo = ct.vendedor
   AND mv.tipo_dato = 'TVEND'
   AND mv.estado_decisiones = 1
INNER JOIN clientesjf c
    ON c.codigo = ct.cliente
   AND c.estado = 1
WHERE ct.estado = 'PENDIENTE'
  AND ct.saldo > 0;
*/
--
-- EXPLAIN con vendedor puntual (reemplazar 'XX'):
/*
EXPLAIN SELECT
    COUNT(DISTINCT ct.cliente) AS clientes_con_deuda,
    SUM(ct.saldo) AS deuda_total,
    SUM(CASE WHEN ct.fecha_ven < CURDATE() THEN ct.saldo ELSE 0 END) AS deuda_vencida,
    COUNT(DISTINCT CASE WHEN ct.fecha_ven < CURDATE() THEN ct.cliente END) AS clientes_vencidos
FROM cuenta_ctejf ct
INNER JOIN clientesjf c
    ON c.codigo = ct.cliente
   AND c.estado = 1
WHERE ct.estado = 'PENDIENTE'
  AND ct.saldo > 0
  AND ct.vendedor = 'XX';
*/
