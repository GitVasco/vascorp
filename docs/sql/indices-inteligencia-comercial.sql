-- Índices recomendados para Inteligencia Comercial (cliente y grupo empresarial).
-- Ejecutar en horario de baja carga. Verificar antes con: SHOW INDEX FROM nombre_tabla;
--
-- Impacto principal:
--   - clientesjf.grupo        → filtro de miembros del grupo
--   - ventajf.cliente+fecha   → motores comercial, fidelidad, cierre mensual
--   - cuenta_ctejf.cliente    → riesgo crediticio y línea operativa consolidada

-- 1) Grupos empresariales: lookup de RUC por grupo (consulta más repetida en modo grupo)
CREATE INDEX idx_clientesjf_grupo_estado_codigo
    ON clientesjf (grupo, estado, codigo);

-- 2) Ventas por cliente en rango de fechas (motores 2, 3, 4 y cierre mensual)
CREATE INDEX idx_ventajf_cliente_fecha
    ON ventajf (cliente, fecha);

-- 3) Filtros adicionales de ventajf usados en IC (tipo, estado, vendedor)
CREATE INDEX idx_ventajf_ic_cliente_fecha_tipo
    ON ventajf (cliente, fecha, tipo, estado);

-- 4) Cuenta corriente por cliente (deuda, documentos, línea operativa)
CREATE INDEX idx_cctejf_cliente_tipmov_estado
    ON cuenta_ctejf (cliente, tip_mov, estado);

-- 5) Reconstrucción cronológica de línea operativa consolidada
CREATE INDEX idx_cctejf_cliente_fecha_id
    ON cuenta_ctejf (cliente, fecha, id);

-- 6) Movimientos de venta por cliente (penetración de productos — tabla anual configurable)
-- Ajustar el nombre si ic_motor2_tabla_movimientos apunta a otro año:
CREATE INDEX idx_movimientosjf_2026_cliente
    ON movimientosjf_2026 (cliente);

-- Índices ya documentados en tablas/indices_estado_cuenta.sql (cuenta corriente legacy).
-- Si no existen en tu servidor, descomenta:
-- CREATE INDEX idx_cctejf_cliente ON cuenta_ctejf (cliente);
-- CREATE INDEX idx_cctejf_tipmov_fecha ON cuenta_ctejf (tip_mov, fecha);
