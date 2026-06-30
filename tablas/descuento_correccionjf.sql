/*
MariaDB — Tabla de correcciones manuales para descuentos compuestos ESSO
Ejecutar antes de la vista consolidada en dashboard-cobranzas/descuentos-compuestos-esso.sql
*/

USE `vasco`;

DROP TABLE IF EXISTS `descuento_correccionjf`;

CREATE TABLE `descuento_correccionjf` (
  `id` int(11) NOT NULL COMMENT 'FK cuenta_ctejf.id',
  `nota_estandar` varchar(50) NOT NULL COMMENT 'Formato DSCTO_p1_p2',
  `pct1` decimal(10,4) DEFAULT NULL,
  `pct2` decimal(10,4) DEFAULT NULL,
  `observacion` varchar(200) DEFAULT NULL,
  `estado` enum('PENDIENTE','CONFIRMADO','RECHAZADO','DESCARTADO') NOT NULL DEFAULT 'CONFIRMADO',
  `usureg` varchar(50) DEFAULT NULL,
  `pcreg` varchar(50) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Correcciones manuales de notas de descuento compuesto ESSO';
