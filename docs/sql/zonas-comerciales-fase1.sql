-- =============================================================================
-- Zonas comerciales — Fase 1
-- =============================================================================
-- Campos de override en cliente y grupo (NULL = seguir cascada).
-- Cascada: id_zona del cliente > id_zona del grupo > regla ubigeo.
-- =============================================================================

-- Cliente
SET @col_cli := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clientesjf'
      AND COLUMN_NAME = 'id_zona'
);
SET @sql_cli := IF(
    @col_cli = 0,
    'ALTER TABLE clientesjf ADD COLUMN id_zona INT NULL DEFAULT NULL COMMENT ''zonas_comercialesjf.id override'' AFTER grupo, ADD KEY idx_clientes_id_zona (id_zona)',
    'SELECT ''clientesjf.id_zona ya existe'' AS info'
);
PREPARE stmt_cli FROM @sql_cli;
EXECUTE stmt_cli;
DEALLOCATE PREPARE stmt_cli;

-- Grupo empresarial
SET @col_grp := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'grupos_empresarialesjf'
      AND COLUMN_NAME = 'id_zona'
);
SET @sql_grp := IF(
    @col_grp = 0,
    'ALTER TABLE grupos_empresarialesjf ADD COLUMN id_zona INT NULL DEFAULT NULL COMMENT ''zonas_comercialesjf.id'' AFTER descripcion, ADD KEY idx_grupos_id_zona (id_zona)',
    'SELECT ''grupos_empresarialesjf.id_zona ya existe'' AS info'
);
PREPARE stmt_grp FROM @sql_grp;
EXECUTE stmt_grp;
DEALLOCATE PREPARE stmt_grp;
