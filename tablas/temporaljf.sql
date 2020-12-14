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

/*Table structure for table `temporaljf` */

DROP TABLE IF EXISTS `temporaljf`;

CREATE TABLE `temporaljf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` int(11) DEFAULT NULL,
  `cliente` int(11) DEFAULT NULL,
  `vendedor` varchar(5) DEFAULT NULL,
  `lista` varchar(10) DEFAULT NULL,
  `op_gravada` decimal(11,2) DEFAULT '0.00',
  `descuento_total` decimal(11,4) DEFAULT '0.0000',
  `sub_total` decimal(11,2) DEFAULT '0.00',
  `igv` decimal(11,2) DEFAULT '0.00',
  `total` decimal(11,2) DEFAULT '0.00',
  `condicion_venta` int(11) DEFAULT '0',
  `estado` varchar(20) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario` int(11) DEFAULT NULL,
  `agencia` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=latin1;

/*Data for the table `temporaljf` */

insert  into `temporaljf`(`id`,`codigo`,`cliente`,`vendedor`,`lista`,`op_gravada`,`descuento_total`,`sub_total`,`igv`,`total`,`condicion_venta`,`estado`,`fecha`,`usuario`,`agencia`) values (15,201900002,31789,'18A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(16,201900004,31788,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(17,201900005,31785,'20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(18,201900006,31788,'19',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(19,201900007,31785,'18A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(20,201900022,28694,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(21,201900027,31775,'18A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(22,201900030,31790,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(23,201900031,31790,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(24,201900032,31790,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(25,1,31790,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(26,201900034,31790,'07',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(27,202000001,5,'5',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(28,202000002,31790,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(29,202000003,31790,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(30,202000004,5,'5',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(31,202000005,38392,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(32,202000006,37011,'07',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(33,202000007,37011,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(34,202000008,37011,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(35,202000009,37011,'07',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(36,202000010,38393,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(37,202000011,38394,'02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(38,202000012,38393,'18A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(39,202000015,38392,'18A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(40,202000016,35155,'18A','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(41,202000017,38395,'18A','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(42,202000018,38393,'19','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(43,202000019,35155,'18A','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(44,202000020,35155,'07','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(45,202000021,32683,'00','precio4',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-23 23:32:49',NULL,NULL),(46,202000022,32683,'00','precio4',3177.87,0.0000,3177.87,572.02,3749.89,5,'GENERADO','2020-11-23 23:32:49',6,NULL),(47,202000023,38396,'18A','precio9',429.58,34.3700,395.21,71.14,466.35,2,'GENERADO','2020-11-24 00:06:50',6,NULL),(48,NULL,38394,'00','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 00:47:48',NULL,NULL),(49,2147483647,38394,'18A','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 01:19:13',NULL,NULL),(50,2147483647,38389,'02','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 01:19:28',NULL,NULL),(51,2147483647,38394,'00','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 01:23:27',NULL,NULL),(52,2147483647,38394,'00','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 01:26:42',NULL,NULL),(53,2147483647,38394,'07','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 01:28:08',NULL,NULL),(54,620200044,38396,'00','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 01:29:30',NULL,NULL),(55,620200045,38394,'00','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-24 01:30:30',NULL,NULL),(56,620200046,38395,'02','precio9',18.10,0.0000,18.10,3.26,21.36,1,'GENERADO','2020-11-24 01:33:11',6,NULL),(57,12020047,35155,'02','precio9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-11-30 12:25:04',NULL,NULL),(58,62020048,37011,'20','precio9',220.73,0.0000,220.73,39.73,260.46,4,'GENERADO','2020-11-30 12:37:15',6,NULL),(59,62020049,32149,'00','precio10',287.42,0.0000,287.42,51.74,339.16,5,'GENERADO','2020-11-30 15:00:03',6,NULL),(60,62020050,32149,'00','precio10',274.35,0.0000,274.35,49.38,323.73,6,'GENERADO','2020-11-30 15:11:43',6,NULL),(61,62020051,32683,'00','precio9',1982.19,0.0000,1982.19,356.79,2338.98,2,'GENERADO','2020-11-30 15:14:59',1,NULL),(62,12020052,32683,'00','precio4',3177.87,222.4500,2955.42,531.98,3487.39,10,'GENERADO','2020-12-08 23:29:39',1,NULL),(63,12020053,35155,'02','precio9',4.53,0.2300,4.30,0.77,5.08,2,'GENERADO','2020-12-09 00:21:04',1,NULL),(64,12020054,38132,'18A','precio9',22.63,0.0000,22.63,4.07,26.70,20,'GENERADO','2020-12-09 00:56:39',1,NULL),(65,62020055,32683,'00','precio4',3177.87,222.4500,2955.42,531.98,3487.39,10,'GENERADO','2020-12-09 01:26:56',6,NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
