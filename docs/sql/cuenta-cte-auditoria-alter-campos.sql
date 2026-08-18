-- Si cuenta_cte_auditoriajf ya existía, ejecutar esto una vez.
-- Si la tabla se crea ahora con cuenta-cte-auditoria.sql, no hace falta.

ALTER TABLE cuenta_cte_auditoriajf
  ADD COLUMN campo VARCHAR(50) NULL AFTER accion,
  ADD COLUMN valor_anterior VARCHAR(255) NULL AFTER campo,
  ADD COLUMN valor_nuevo VARCHAR(255) NULL AFTER valor_anterior;
