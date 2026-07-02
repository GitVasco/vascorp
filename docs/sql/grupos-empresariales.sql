-- Grupos empresariales: agrupa clientes que compran con distintos nombres/RUC bajo una misma empresa.
-- Los clientes se vinculan mediante clientesjf.grupo = grupos_empresarialesjf.codigo (columna ya existente).

CREATE TABLE IF NOT EXISTS grupos_empresarialesjf (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(20)  NOT NULL,
    nombre      VARCHAR(150) NOT NULL,
    descripcion TEXT         NULL,
    estado      TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    usureg      VARCHAR(50)  NULL,
    fecreg      DATETIME     NULL,
    UNIQUE KEY uk_grupos_emp_codigo (codigo),
    KEY idx_grupos_emp_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Migrar grupo hardcodeado existente (opcional, ejecutar solo si aplica)
-- INSERT INTO grupos_empresarialesjf (codigo, nombre, estado, fecreg)
-- SELECT 'JOEL', 'Joel', 1, NOW()
-- FROM DUAL
-- WHERE NOT EXISTS (SELECT 1 FROM grupos_empresarialesjf WHERE codigo = 'JOEL');
