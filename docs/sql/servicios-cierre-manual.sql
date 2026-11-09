-- Cierre manual de servicios: registro de cada cierre (por cantidad, con motivo)
-- Ejecutar una sola vez en la BD antes de usar "Cierre manual" en Servicios.
CREATE TABLE IF NOT EXISTS servicios_cierre_manual (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  detalle_id  INT          NOT NULL COMMENT 'servicios_detallejf.id',
  codigo      VARCHAR(50)  NOT NULL COMMENT 'Código del servicio',
  articulo    VARCHAR(50)  NOT NULL,
  cantidad    INT          NOT NULL COMMENT 'Cantidad cerrada a mano',
  saldo_antes INT          NOT NULL COMMENT 'Saldo de la línea antes del cierre',
  motivo      VARCHAR(40)  NOT NULL,
  obs         VARCHAR(255) NULL,
  usuario     VARCHAR(100) NOT NULL,
  fecha       DATETIME     NOT NULL,
  KEY idx_fecha (fecha),
  KEY idx_detalle (detalle_id),
  KEY idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Si ya ejecutaste la versión anterior de este script (columnas cerrar_* en servicios_detallejf),
-- esas columnas quedan sin uso. Opcional, para limpiarlas:
-- ALTER TABLE servicios_detallejf DROP COLUMN cerrar_motivo, DROP COLUMN cerrar_obs,
--   DROP COLUMN cerrar_usuario, DROP COLUMN cerrar_fecha, DROP COLUMN cerrar_saldo;
