/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.5.5-10.1.16-MariaDB : Database - new_vasco
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`new_vasco` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `new_vasco`;

/*Table structure for table `ventajf` */

DROP TABLE IF EXISTS `ventajf`;

CREATE TABLE `ventajf` (
  `tipo` varchar(3) DEFAULT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `neto` decimal(20,2) DEFAULT NULL,
  `igv` decimal(20,2) DEFAULT NULL,
  `dscto` decimal(20,2) DEFAULT NULL,
  `total` decimal(20,2) DEFAULT NULL,
  `cliente` varchar(20) DEFAULT NULL,
  `vendedor` varchar(20) DEFAULT NULL,
  `agencia` varchar(10) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `tipo_documento` varchar(20) DEFAULT NULL,
  `lista_precios` varchar(10) DEFAULT NULL,
  `condicion_venta` int(11) DEFAULT '0',
  `doc_destino` varchar(20) DEFAULT NULL,
  `doc_origen` varchar(20) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` varchar(20) DEFAULT 'GENERADO'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
