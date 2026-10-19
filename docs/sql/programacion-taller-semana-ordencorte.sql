-- =============================================================================
-- Programación semanal: ligar cada línea a su orden de corte (OC)
-- =============================================================================
-- Antes la programación/prioridad solo guardaba modelo+color y el saldo se leía
-- del total del modelo/color. Cuando la OC programada ya se cortó y se envió a
-- taller pero entraba otra OC del mismo modelo/color, la línea vieja seguía como
-- pendiente con el saldo de la OC nueva y esta quedaba "absorbida" o como
-- remanente (parecía duplicado).
--
-- Ahora cada línea apunta a una OC (ordencortejf.codigo; 0 = saldo sin OC) y su
-- saldo vivo es el de esa OC. Una OC nueva es siempre un candidato aparte.
--
-- Orden de despliegue:
--   1) Ejecutar este SQL.
--   2) php scripts/migrar_programacion_ordencorte.php            (vista previa)
--      php scripts/migrar_programacion_ordencorte.php --aplicar  (asigna OC)
--   3) Subir el código nuevo.
-- =============================================================================

ALTER TABLE programacion_taller_semanajf
    ADD COLUMN ordencorte INT NULL COMMENT 'ordencortejf.codigo, 0 = saldo sin OC, NULL = legado' AFTER cod_color,
    DROP INDEX uk_pts_semana_modelo_color_taller,
    ADD UNIQUE KEY uk_pts_semana_modelo_color_oc_taller (anio, semana, modelo, cod_color, ordencorte, cod_sector),
    ADD KEY idx_pts_oc (modelo, cod_color, ordencorte);

ALTER TABLE programacion_taller_prioridadjf
    ADD COLUMN ordencorte INT NULL COMMENT 'ordencortejf.codigo, 0 = saldo sin OC, NULL = legado' AFTER cod_color,
    DROP INDEX uk_ptp_modelo_color_taller,
    ADD UNIQUE KEY uk_ptp_modelo_color_oc_taller (modelo, cod_color, ordencorte, cod_sector);
