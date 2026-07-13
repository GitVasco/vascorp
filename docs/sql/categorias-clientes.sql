-- =============================================================================
-- Categorías comerciales de clientes
-- =============================================================================
-- Objetivo: catálogo de categorías, requisitos, beneficios y asignaciones
-- (historial) sin modificar clientesjf ni grupos_empresarialesjf.
--
-- Reglas de negocio acordadas (MVP):
--   - Categorías semilla: Distribuidor, Mayorista, Minorista, Catálogo, Usuario final.
--   - Requisito principal: monto anual de compras (valores aún por definir).
--   - Beneficios: descuento en venta y descuento por pronto pago (valores por definir).
--   - Asignación siempre manual. Clientes/grupos existentes NO se clasifican aquí.
--   - Sin asignación vigente = "sin categoría / por revisar".
--   - Si el cliente pertenece a un grupo, la categoría efectiva es la del grupo
--     (se resuelve en aplicación; aquí solo se persiste la asignación).
--   - Listas de precios: fuera de alcance por ahora.
--
-- Ejecutar manualmente en la BD. Idempotente donde es posible (IF NOT EXISTS /
-- INSERT ... WHERE NOT EXISTS).
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) Catálogo de categorías comerciales
-- -----------------------------------------------------------------------------
-- Maestro de categorías. No guarda clientes aquí; solo la definición comercial.
CREATE TABLE IF NOT EXISTS categorias_clientesjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    -- Código corto estable para referencias en código/reportes (ej. DIST, MAYO).
    codigo          VARCHAR(20)  NOT NULL,
    -- Nombre visible en pantallas y selectores.
    nombre          VARCHAR(100) NOT NULL,
    -- Texto libre de apoyo comercial (opcional).
    descripcion     TEXT         NULL,
    -- Orden de presentación en listados y selectores.
    orden           INT          NOT NULL DEFAULT 0,
    -- Color hex para badges/UI (ej. #dd4b39).
    color           VARCHAR(20)  NULL COMMENT 'Color hex para UI',
    -- 1 = activa y disponible para asignar; 0 = inactiva (no se ofrece en altas).
    estado          TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activa, 0=inactiva',
    -- Auditoría de alta/modificación de la categoría.
    usureg          VARCHAR(50)  NULL COMMENT 'Usuario que creó el registro',
    fecreg          DATETIME     NULL COMMENT 'Fecha/hora de creación',
    usumod          VARCHAR(50)  NULL COMMENT 'Usuario de última modificación',
    fecmod          DATETIME     NULL COMMENT 'Fecha/hora de última modificación',
    UNIQUE KEY uk_cat_cli_codigo (codigo),
    KEY idx_cat_cli_estado (estado),
    KEY idx_cat_cli_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Catálogo de categorías comerciales de clientes/grupos';

-- -----------------------------------------------------------------------------
-- 2) Requisitos configurables por categoría
-- -----------------------------------------------------------------------------
-- Reglas objetivas asociadas a una categoría. MVP: monto_compras_anual.
-- valor_numerico puede quedar NULL hasta que ventas defina los umbrales.
CREATE TABLE IF NOT EXISTS categorias_clientes_requisitosjf (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    -- FK lógica a categorias_clientesjf.id
    id_categoria        INT          NOT NULL COMMENT 'categorias_clientesjf.id',
    -- Tipo de requisito. MVP: monto_compras_anual. Permite agregar más tipos después.
    tipo_requisito      VARCHAR(50)  NOT NULL COMMENT 'Ej: monto_compras_anual',
    -- Umbral numérico del requisito (NULL = aún no definido comercialmente).
    valor_numerico      DECIMAL(15,2) NULL COMMENT 'Ej: monto mínimo anual de compras',
    -- Unidad o moneda de apoyo (ej. PEN, USD). Informativo.
    unidad              VARCHAR(20)  NULL COMMENT 'Ej: PEN',
    -- Descripción legible del requisito para UI.
    descripcion         VARCHAR(255) NULL,
    -- 1 = se evalúa; 0 = ignorado temporalmente.
    estado              TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    usureg              VARCHAR(50)  NULL,
    fecreg              DATETIME     NULL,
    usumod              VARCHAR(50)  NULL,
    fecmod              DATETIME     NULL,
    KEY idx_cat_req_categoria (id_categoria),
    KEY idx_cat_req_tipo (tipo_requisito),
    KEY idx_cat_req_estado (estado),
    UNIQUE KEY uk_cat_req_cat_tipo (id_categoria, tipo_requisito)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Requisitos comerciales configurables por categoría';

-- -----------------------------------------------------------------------------
-- 3) Beneficios configurables por categoría
-- -----------------------------------------------------------------------------
-- Beneficios que otorga la categoría. MVP: descuento de venta y pronto pago.
-- Los valores pueden quedar en NULL hasta definición comercial.
-- No se integra aún con pedidos/facturación (solo almacenamiento).
CREATE TABLE IF NOT EXISTS categorias_clientes_beneficiosjf (
    id                        INT AUTO_INCREMENT PRIMARY KEY,
    -- FK lógica a categorias_clientesjf.id
    id_categoria              INT           NOT NULL COMMENT 'categorias_clientesjf.id',
    -- Descuento máximo autorizado en la venta (%). NULL = no definido aún.
    descuento_venta_pct       DECIMAL(5,2)  NULL COMMENT 'Porcentaje descuento en venta',
    -- Descuento por pronto pago (%). NULL = no definido aún.
    descuento_pronto_pago_pct DECIMAL(5,2)  NULL COMMENT 'Porcentaje descuento pronto pago',
    -- Notas comerciales del beneficio.
    descripcion               VARCHAR(255)  NULL,
    -- 1 = beneficio vigente para la categoría; 0 = deshabilitado.
    estado                    TINYINT(1)    NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    usureg                    VARCHAR(50)   NULL,
    fecreg                    DATETIME      NULL,
    usumod                    VARCHAR(50)   NULL,
    fecmod                    DATETIME      NULL,
    KEY idx_cat_ben_categoria (id_categoria),
    KEY idx_cat_ben_estado (estado),
    UNIQUE KEY uk_cat_ben_categoria (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Beneficios comerciales configurables por categoría';

-- -----------------------------------------------------------------------------
-- 4) Asignaciones e historial de categoría
-- -----------------------------------------------------------------------------
-- Registra qué categoría tiene un cliente o un grupo, con auditoría e historial.
-- Una entidad puede tener varias filas a lo largo del tiempo; solo una debe estar
-- vigente (estado=1 y dentro de vigencia). El cierre del registro anterior lo
-- hace la aplicación al reasignar.
--
-- Importante:
--   - tipo_entidad = 'cliente' => codigo_entidad = clientesjf.codigo
--   - tipo_entidad = 'grupo'   => codigo_entidad = grupos_empresarialesjf.codigo
--   - No se insertan asignaciones semilla: existentes quedan sin categoría
--     (= "sin categoría / por revisar" en la aplicación).
CREATE TABLE IF NOT EXISTS categorias_clientes_asignacionesjf (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    -- 'cliente' o 'grupo'. Define cómo interpretar codigo_entidad.
    tipo_entidad        VARCHAR(20)  NOT NULL COMMENT 'cliente | grupo',
    -- Código del cliente o del grupo empresarial (no es FK física a propósito).
    codigo_entidad      VARCHAR(50)  NOT NULL COMMENT 'clientesjf.codigo o grupos_empresarialesjf.codigo',
    -- Categoría asignada en este registro.
    id_categoria        INT          NOT NULL COMMENT 'categorias_clientesjf.id',
    -- 1 = asignación vigente; 0 = histórica / cerrada / anulada.
    estado              TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=vigente, 0=historica',
    -- Resultado de evaluación de requisitos (manual en MVP).
    -- pendiente: aún no evaluado; cumple / no_cumple; por_revisar: dejó de cumplir.
    cumplimiento        VARCHAR(20)  NOT NULL DEFAULT 'pendiente'
                        COMMENT 'pendiente|cumple|no_cumple|por_revisar',
    -- Origen de la asignación.
    -- manual: asignación normal; excepcion: se asignó sin cumplir requisitos.
    origen              VARCHAR(20)  NOT NULL DEFAULT 'manual'
                        COMMENT 'manual|excepcion',
    -- Motivo obligatorio en excepciones; recomendable también en cambios normales.
    motivo              VARCHAR(255) NULL COMMENT 'Justificación comercial del cambio/excepción',
    -- Inicio de vigencia de esta asignación.
    vigencia_desde      DATETIME     NOT NULL COMMENT 'Inicio de vigencia',
    -- Fin de vigencia. NULL = sin vencimiento (sigue vigente mientras estado=1).
    vigencia_hasta      DATETIME     NULL COMMENT 'Fin de vigencia; NULL=sin vencimiento',
    -- 1 = esta fila es una excepción comercial aprobada manualmente.
    es_excepcion        TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=excepción manual',
    -- Auditoría
    usureg              VARCHAR(50)  NULL COMMENT 'Usuario que registró la asignación',
    fecreg              DATETIME     NULL COMMENT 'Fecha/hora de registro',
    usumod              VARCHAR(50)  NULL COMMENT 'Usuario de última modificación',
    fecmod              DATETIME     NULL COMMENT 'Fecha/hora de última modificación',
    KEY idx_cat_asig_entidad (tipo_entidad, codigo_entidad),
    KEY idx_cat_asig_categoria (id_categoria),
    KEY idx_cat_asig_estado (estado),
    KEY idx_cat_asig_cumplimiento (cumplimiento),
    KEY idx_cat_asig_vigencia (vigencia_desde, vigencia_hasta),
    KEY idx_cat_asig_entidad_estado (tipo_entidad, codigo_entidad, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Asignaciones vigentes e historial de categoría por cliente o grupo';

-- =============================================================================
-- Datos semilla: 5 categorías iniciales
-- =============================================================================
-- No clasifica clientes ni grupos existentes.

INSERT INTO categorias_clientesjf (codigo, nombre, descripcion, orden, color, estado, usureg, fecreg)
SELECT 'DIST', 'Distribuidor', 'Categoría comercial Distribuidor', 1, '#dd4b39', 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias_clientesjf WHERE codigo = 'DIST');

INSERT INTO categorias_clientesjf (codigo, nombre, descripcion, orden, color, estado, usureg, fecreg)
SELECT 'MAYO', 'Mayorista', 'Categoría comercial Mayorista', 2, '#00a65a', 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias_clientesjf WHERE codigo = 'MAYO');

INSERT INTO categorias_clientesjf (codigo, nombre, descripcion, orden, color, estado, usureg, fecreg)
SELECT 'MINO', 'Minorista', 'Categoría comercial Minorista', 3, '#f39c12', 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias_clientesjf WHERE codigo = 'MINO');

INSERT INTO categorias_clientesjf (codigo, nombre, descripcion, orden, color, estado, usureg, fecreg)
SELECT 'CATA', 'Catálogo', 'Categoría comercial Catálogo', 4, '#00c0ef', 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias_clientesjf WHERE codigo = 'CATA');

INSERT INTO categorias_clientesjf (codigo, nombre, descripcion, orden, color, estado, usureg, fecreg)
SELECT 'UFIN', 'Usuario final', 'Categoría comercial Usuario final', 5, '#605ca8', 1, 'sistema', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categorias_clientesjf WHERE codigo = 'UFIN');

-- -----------------------------------------------------------------------------
-- Semilla de requisito (monto anual) y beneficios por categoría
-- -----------------------------------------------------------------------------
-- valor_numerico / descuentos en NULL = pendiente de definición comercial.

INSERT INTO categorias_clientes_requisitosjf
    (id_categoria, tipo_requisito, valor_numerico, unidad, descripcion, estado, usureg, fecreg)
SELECT c.id, 'monto_compras_anual', NULL, 'PEN', 'Monto mínimo anual de compras', 1, 'sistema', NOW()
FROM categorias_clientesjf c
WHERE c.codigo IN ('DIST', 'MAYO', 'MINO', 'CATA', 'UFIN')
  AND NOT EXISTS (
      SELECT 1
      FROM categorias_clientes_requisitosjf r
      WHERE r.id_categoria = c.id
        AND r.tipo_requisito = 'monto_compras_anual'
  );

INSERT INTO categorias_clientes_beneficiosjf
    (id_categoria, descuento_venta_pct, descuento_pronto_pago_pct, descripcion, estado, usureg, fecreg)
SELECT c.id, NULL, NULL, 'Beneficios pendientes de definir', 1, 'sistema', NOW()
FROM categorias_clientesjf c
WHERE c.codigo IN ('DIST', 'MAYO', 'MINO', 'CATA', 'UFIN')
  AND NOT EXISTS (
      SELECT 1
      FROM categorias_clientes_beneficiosjf b
      WHERE b.id_categoria = c.id
  );
