-- Agente de retención IGV (SUNAT) en maestro de clientes.
-- 0 = No; 1 = Sí. Facturación/CSV eFact se conectará después.

ALTER TABLE `clientesjf`
ADD COLUMN `agente_retencion` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1=agente de retención IGV SUNAT'
  AFTER `agencia`;
