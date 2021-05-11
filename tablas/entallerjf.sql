/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.5.5-10.1.16-MariaDB : Database - vasco
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`vasco` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `vasco`;

/*Table structure for table `entallerjf` */

DROP TABLE IF EXISTS `entallerjf`;

CREATE TABLE `entallerjf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cabecera` int(11) DEFAULT NULL,
  `articulo` varchar(20) DEFAULT NULL,
  `cod_operacion` varchar(3) DEFAULT NULL,
  `trabajador` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` int(11) DEFAULT '1',
  `total_precio` decimal(11,6) DEFAULT NULL,
  `total_tiempo` decimal(11,6) DEFAULT NULL,
  `sector` int(11) DEFAULT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `fecha_proceso` datetime DEFAULT NULL,
  `fecha_terminado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`trabajador`)
) ENGINE=InnoDB AUTO_INCREMENT=27841 DEFAULT CHARSET=latin1;

/*Data for the table `entallerjf` */


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;