-- =============================================================================
-- Facturas de exportación — Fase 1 (configuración)
-- Diseño: docs/efact/EXPORTACION-diseno.md
-- 1) clientesjf: columna nueva y opcional pais_iso (NULL = Perú). No cambia columnas existentes.
-- 2) Tabla nueva exp_partida_lineajf (partida arancelaria por línea, opcional).
-- Ejecutar en BD vasco. Idempotente: se puede ejecutar más de una vez.
-- =============================================================================

-- 1) País del cliente (solo si la columna aún no existe)
SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientesjf' AND COLUMN_NAME = 'pais_iso'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE clientesjf ADD COLUMN pais_iso VARCHAR(2) NULL DEFAULT NULL COMMENT ''ISO 3166-1 alfa-2; NULL = Perú. Exportación: docs/efact/EXPORTACION-diseno.md''',
    'SELECT ''pais_iso ya existe''');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Precarga: clientes con ubigeo "ESPAÑA" (como lo detecta hoy el CSV de exportación)
UPDATE clientesjf c
INNER JOIN ubigeo u ON u.codigo = c.ubigeo
SET c.pais_iso = 'ES'
WHERE u.departamento = 'ESPAÑA'
  AND (c.pais_iso IS NULL OR c.pais_iso = '');

-- 2) Partida arancelaria por línea (opcional)
CREATE TABLE IF NOT EXISTS exp_partida_lineajf (
    id                  INT(11) NOT NULL AUTO_INCREMENT,
    linea               VARCHAR(30) NOT NULL COMMENT 'articulojf.linea',
    partida             VARCHAR(12) NOT NULL COMMENT 'Partida arancelaria (10 dígitos)',
    descripcion         VARCHAR(150) NULL,
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    usuario_registro    VARCHAR(50) NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_expp_linea (linea)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Partida arancelaria por línea de prenda (opcional)';
