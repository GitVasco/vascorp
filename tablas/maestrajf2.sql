/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.5.5-10.1.38-MariaDB : Database - new_vasco
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

/*Table structure for table `maestrajf` */

DROP TABLE IF EXISTS `maestrajf`;

CREATE TABLE `maestrajf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) DEFAULT NULL,
  `descripcion` varchar(50) DEFAULT NULL,
  `tipo_dato` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;

/*Data for the table `maestrajf` */

insert  into `maestrajf`(`id`,`codigo`,`descripcion`,`tipo_dato`) values (1,'01','FACTURA','tdoc'),(2,'03','BOLETA','tdoc'),(3,'07','NOTA DE CREDITO','tdoc'),(4,'08','NOTA DE DEBITO','tdoc'),(5,'70','PROFORMA','tdoc'),(6,'00','OFICINA','TVEND'),(7,'02','MANUEL VASQUEZ','TVEND'),(8,'03','ORLANDO LACHIRA','TVEND'),(9,'04','JUAN CARLOS BRAVO','TVEND'),(10,'05A','ARTURO GALVEZ',NULL),(11,'05','ARTURO GALVEZ - LIMA','TVEND'),(12,'06','VENTAS INTERNAS','TVEND'),(13,'07','ANTONIO DIAZ','TVEND'),(14,'08','SHOW ROOM','TVEND'),(15,'14','JORGE CARMEN','TVEND'),(16,'14A','JHORDI ROMERO','TVEND'),(17,'19','AMELIA PORTAL','TVEND'),(18,'20','JUAN CARLOS DIAZ','TVEND');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
