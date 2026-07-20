-- Clasificación de modelos por categoría / subcategoría.
-- Diagnóstico (2026-07-20): modelojf.modelo = VARCHAR(10) latin1_swedish_ci, InnoDB;
--   max TRIM activos = 5 chars; 206 activos; 0 duplicados tras TRIM.
-- No altera modelojf ni tablas legacy. Ejecutar primero en staging.

CREATE TABLE IF NOT EXISTS categoria_modelojf (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NULL,
    actualizado_en DATETIME NULL DEFAULT NULL,
    actualizado_por INT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categoria_modelojf_codigo (codigo),
    UNIQUE KEY uq_categoria_modelojf_nombre (nombre),
    KEY idx_categoria_modelojf_estado_orden (estado, orden, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subcategoria_modelojf (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_categoria INT UNSIGNED NOT NULL,
    codigo VARCHAR(70) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NULL,
    actualizado_en DATETIME NULL DEFAULT NULL,
    actualizado_por INT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subcategoria_modelojf_codigo (codigo),
    UNIQUE KEY uq_subcategoria_modelojf_categoria_nombre (id_categoria, nombre),
    KEY idx_subcategoria_modelojf_categoria_estado_orden (id_categoria, estado, orden, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modelo_subcategoriajf (
    modelo VARCHAR(10) NOT NULL,
    id_subcategoria INT UNSIGNED NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_asignacion INT NULL,
    actualizado_en DATETIME NULL DEFAULT NULL,
    usuario_actualizacion INT NULL,
    PRIMARY KEY (modelo),
    KEY idx_modelo_subcategoriajf_subcategoria_modelo (id_subcategoria, modelo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modelo_subcategoria_historialjf (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    modelo VARCHAR(10) NOT NULL,
    id_subcategoria_anterior INT UNSIGNED NULL,
    id_subcategoria_nueva INT UNSIGNED NULL,
    accion ENUM('ALTA', 'CAMBIO', 'BAJA') NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NULL,
    origen VARCHAR(30) NOT NULL DEFAULT 'pantalla',
    observacion VARCHAR(250) NULL,
    PRIMARY KEY (id),
    KEY idx_modelo_subcategoria_historial_modelo_fecha (modelo, fecha),
    KEY idx_modelo_subcategoria_historial_nueva_fecha (id_subcategoria_nueva, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Semilla idempotente (no reactiva estado desactivado por admin)
INSERT INTO categoria_modelojf (codigo, nombre, estado, orden)
VALUES
    ('TRUSA', 'Trusas', 1, 10),
    ('BRASIER', 'Brasieres', 1, 20),
    ('FAJA', 'Fajas', 1, 30)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    orden = VALUES(orden);

INSERT INTO subcategoria_modelojf (id_categoria, codigo, nombre, estado, orden)
SELECT c.id, v.codigo, v.nombre, 1, v.orden
FROM (
    SELECT 'TRUSA' AS cat, 'TRUSA_BIKINI' AS codigo, 'Bikini / clásica' AS nombre, 10 AS orden
    UNION ALL SELECT 'TRUSA', 'TRUSA_CACHETERO', 'Cachetero', 20
    UNION ALL SELECT 'TRUSA', 'TRUSA_HILO', 'Hilo dental / tanga', 30
    UNION ALL SELECT 'TRUSA', 'TRUSA_CULOTTE', 'Culotte / bóxer', 40
    UNION ALL SELECT 'TRUSA', 'TRUSA_ALTA_CINTURA', 'Alta cintura / control suave', 50
    UNION ALL SELECT 'TRUSA', 'TRUSA_MENSTRUAL', 'Menstrual / absorbente', 60
    UNION ALL SELECT 'BRASIER', 'BRASIER_BASICO', 'Básico / copa suave', 10
    UNION ALL SELECT 'BRASIER', 'BRASIER_PREFORMADA', 'Copa preformada', 20
    UNION ALL SELECT 'BRASIER', 'BRASIER_CON_ARO', 'Con aro', 30
    UNION ALL SELECT 'BRASIER', 'BRASIER_PUSHUP', 'Push-up / realce', 40
    UNION ALL SELECT 'BRASIER', 'BRASIER_DEPORTIVO', 'Deportivo', 50
    UNION ALL SELECT 'BRASIER', 'BRASIER_LACTANCIA', 'Lactancia', 60
    UNION ALL SELECT 'BRASIER', 'BRASIER_STRAPLESS', 'Strapless / multiuso', 70
    UNION ALL SELECT 'BRASIER', 'BRASIER_POSTQX', 'Postquirúrgico', 80
    UNION ALL SELECT 'FAJA', 'FAJA_PANTY', 'Panty / alta cintura', 10
    UNION ALL SELECT 'FAJA', 'FAJA_SHORT', 'Short / bermuda', 20
    UNION ALL SELECT 'FAJA', 'FAJA_BODY', 'Body entero', 30
    UNION ALL SELECT 'FAJA', 'FAJA_CHALECO', 'Chaleco / torso', 40
    UNION ALL SELECT 'FAJA', 'FAJA_POSTPARTO', 'Postparto', 50
    UNION ALL SELECT 'FAJA', 'FAJA_POSTQX', 'Postquirúrgica', 60
    UNION ALL SELECT 'FAJA', 'FAJA_DEPORTIVA', 'Deportiva / modeladora activa', 70
) v
INNER JOIN categoria_modelojf c ON c.codigo = v.cat
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    orden = VALUES(orden),
    id_categoria = VALUES(id_categoria);
