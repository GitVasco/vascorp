-- =============================================================================
-- sectorjf: estado (activo/inactivo) + color pastel por taller
-- =============================================================================
-- Por ahora solo se administra en el maestro de sectores.
-- NO filtrar listas operativas por estado hasta nueva indicación.
-- Ejecutar en copia de BD antes de producción.
-- =============================================================================

ALTER TABLE sectorjf
    ADD COLUMN estado TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1=activo, 0=inactivo; aún no aplica en otros módulos'
        AFTER tipo,
    ADD COLUMN color VARCHAR(7) NULL
        COMMENT 'Color pastel UI (#RRGGBB)'
        AFTER estado;

-- Colores pastel iniciales por código (se pueden cambiar en el maestro)
UPDATE sectorjf SET color = CASE UPPER(TRIM(cod_sector))
    WHEN 'T0'  THEN '#A8D5E5'
    WHEN 'T1'  THEN '#B8E0D2'
    WHEN 'T2'  THEN '#D4C1EC'
    WHEN 'T3'  THEN '#F7C5CC'
    WHEN 'T4'  THEN '#FFE5B4'
    WHEN 'T5'  THEN '#C5E1A5'
    WHEN 'T6'  THEN '#FFD6A5'
    WHEN 'T8'  THEN '#B5EAD7'
    WHEN 'T9'  THEN '#E2F0CB'
    WHEN 'TA'  THEN '#FFDAC1'
    WHEN 'TB'  THEN '#C7CEEA'
    WHEN 'TC'  THEN '#F1C0E8'
    WHEN 'TD'  THEN '#A0C4FF'
    WHEN 'T11' THEN '#FDFFB6'
    WHEN 'T13' THEN '#CAFFBF'
    ELSE color
END
WHERE color IS NULL OR TRIM(color) = '';

-- Resto sin color: asignar de la paleta según id
UPDATE sectorjf s
JOIN (
    SELECT id,
           ELT(
               ((id - 1) % 16) + 1,
               '#A8D5E5','#B8E0D2','#D4C1EC','#F7C5CC',
               '#FFE5B4','#C5E1A5','#FFD6A5','#B5EAD7',
               '#E2F0CB','#FFDAC1','#C7CEEA','#F1C0E8',
               '#A0C4FF','#FDFFB6','#CAFFBF','#9BF6FF'
           ) AS color_pastel
    FROM sectorjf
) x ON x.id = s.id
SET s.color = x.color_pastel
WHERE s.color IS NULL OR TRIM(s.color) = '';
