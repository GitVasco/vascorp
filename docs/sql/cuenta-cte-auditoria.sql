-- Auditoría de abonos / renovaciones (cuenta corriente).
-- No reemplaza cuenta_cte_bkpjf. Solo historial de cambios.
-- Ejecutar en la BD vasco.

CREATE TABLE IF NOT EXISTS cuenta_cte_auditoriajf (
  id INT(11) NOT NULL AUTO_INCREMENT,
  accion VARCHAR(30) NOT NULL,
  campo VARCHAR(50) DEFAULT NULL,
  valor_anterior VARCHAR(255) DEFAULT NULL,
  valor_nuevo VARCHAR(255) DEFAULT NULL,
  id_movimiento INT(11) DEFAULT NULL,
  id_cuenta INT(11) DEFAULT NULL,
  tipo_doc VARCHAR(10) DEFAULT NULL,
  num_cta VARCHAR(20) DEFAULT NULL,
  monto DECIMAL(11,2) DEFAULT NULL,
  saldo_antes DECIMAL(11,2) DEFAULT NULL,
  saldo_despues DECIMAL(11,2) DEFAULT NULL,
  estado_antes VARCHAR(20) DEFAULT NULL,
  estado_despues VARCHAR(20) DEFAULT NULL,
  usuario VARCHAR(100) DEFAULT NULL,
  pc VARCHAR(100) DEFAULT NULL,
  fecha DATETIME DEFAULT NULL,
  detalle VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_cta_auditoria (tipo_doc, num_cta),
  KEY idx_fecha_auditoria (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
