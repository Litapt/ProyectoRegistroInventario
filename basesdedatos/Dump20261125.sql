CREATE DATABASE  IF NOT EXISTS `refaccionaria2` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `refaccionaria2`;
-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: refaccionaria2
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ajustesinventario`
--

DROP TABLE IF EXISTS `ajustesinventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ajustesinventario` (
  `idAjuste` int unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipoAjuste` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fk_idProducto` int unsigned NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `existenciaSistema` int unsigned NOT NULL,
  `existenciaContada` int unsigned DEFAULT NULL,
  `diferencia` int DEFAULT NULL,
  `costoUnitario` decimal(10,2) DEFAULT NULL,
  `fk_idTrabajador` int unsigned NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `fk_idMotivo` int unsigned DEFAULT NULL,
  `referencia` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fk_idTrabajadorAutoriza` int unsigned DEFAULT NULL,
  `fechaAutorizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`idAjuste`),
  UNIQUE KEY `folio` (`folio`),
  KEY `fk_idProducto` (`fk_idProducto`),
  KEY `fk_idTrabajador` (`fk_idTrabajador`),
  KEY `fk_idMotivo` (`fk_idMotivo`),
  KEY `fk_idTrabajadorAutoriza` (`fk_idTrabajadorAutoriza`),
  CONSTRAINT `ajustesinventario_ibfk_1` FOREIGN KEY (`fk_idProducto`) REFERENCES `productos` (`idProducto`),
  CONSTRAINT `ajustesinventario_ibfk_2` FOREIGN KEY (`fk_idTrabajador`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `ajustesinventario_ibfk_3` FOREIGN KEY (`fk_idMotivo`) REFERENCES `motivos` (`idMotivo`),
  CONSTRAINT `ajustesinventario_ibfk_4` FOREIGN KEY (`fk_idTrabajadorAutoriza`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `ajustesinventario_chk_1` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ajustesinventario`
--

LOCK TABLES `ajustesinventario` WRITE;
/*!40000 ALTER TABLE `ajustesinventario` DISABLE KEYS */;
INSERT INTO `ajustesinventario` VALUES (1,'AJ-2026-0001','SALIDA',1,2,10,NULL,NULL,NULL,1,'2026-11-24 00:09:01','APLICADO',3,NULL,'Vino roto',1,'2026-11-24 00:09:01'),(2,'AJ-2026-0002','SALIDA',1,6,8,NULL,NULL,NULL,1,'2026-11-24 00:09:37','APLICADO',3,NULL,'Se rompiron',1,'2026-11-24 00:09:37'),(3,'AJ-2026-0003','ENTRADA',1,5,1,NULL,NULL,200.00,1,'2026-11-24 01:02:43','APLICADO',1,NULL,NULL,1,'2026-11-24 01:02:43'),(4,'AJ-2026-0004','ENTRADA',1,5,3,NULL,NULL,200.00,3,'2026-11-24 14:24:55','APLICADO',1,NULL,NULL,3,'2026-11-24 14:24:55'),(5,'AJ-2026-0005','ENTRADA',1,20,2,NULL,NULL,200.00,1,'2026-11-24 23:05:49','APLICADO',1,NULL,NULL,1,'2026-11-24 23:05:49'),(6,'AJ-2026-0006','ENTRADA',2,2,5,NULL,NULL,241.00,2,'2026-11-24 23:52:48','APLICADO',1,NULL,NULL,2,'2026-11-24 23:52:48');
/*!40000 ALTER TABLE `ajustesinventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bitacora` (
  `idBitacora` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idTrabajador` int unsigned NOT NULL,
  `fk_idTrabajadorAutoriza` int unsigned DEFAULT NULL,
  `accion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tabla` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idRegistro` int unsigned DEFAULT NULL,
  `detalle` json DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idBitacora`),
  KEY `fk_idTrabajador` (`fk_idTrabajador`),
  KEY `fk_idTrabajadorAutoriza` (`fk_idTrabajadorAutoriza`),
  KEY `bitacora_registro` (`tabla`,`idRegistro`),
  CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`fk_idTrabajador`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `bitacora_ibfk_2` FOREIGN KEY (`fk_idTrabajadorAutoriza`) REFERENCES `trabajadores` (`idTrabajador`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bitacora`
--

LOCK TABLES `bitacora` WRITE;
/*!40000 ALTER TABLE `bitacora` DISABLE KEYS */;
INSERT INTO `bitacora` VALUES (1,1,NULL,'CREAR','productos',1,'{\"codigo\": \"FR-047\", \"nombre\": \"Filtro de aceite Castrol\"}','2026-11-24 00:04:23'),(2,1,1,'DESACTIVAR','productos',1,'{\"codigo\": \"FR-047\", \"nombre\": \"Filtro de aceite Castrol\"}','2026-11-24 00:10:17'),(3,1,1,'REACTIVAR','productos',1,'{\"codigo\": \"FR-047\", \"nombre\": \"Filtro de aceite Castrol\"}','2026-11-24 00:11:01'),(4,1,NULL,'CREAR','clientes',1,'{\"nombre\": \"Danna Daybe Cota Sandoval\"}','2026-11-24 00:18:36'),(5,1,1,'DESACTIVAR','clientes',1,'{\"nombre\": \"Danna Daybe Cota Sandoval\"}','2026-11-24 00:19:22'),(6,1,NULL,'CREAR','trabajadores',2,'{\"rol\": \"ALMACENISTA\", \"nombre\": \"Alan Aldahir Collins Navarro\"}','2026-11-24 00:32:40'),(7,1,NULL,'CREAR','trabajadores',3,'{\"rol\": \"GERENTE\", \"nombre\": \"Danna Daybe Cota Sandoval\"}','2026-11-24 00:34:14'),(8,1,NULL,'CREAR','trabajadores',4,'{\"rol\": \"CAJERO\", \"nombre\": \"Maricruz Talamantes Meza\"}','2026-11-24 00:36:58'),(9,1,NULL,'CREAR','trabajadores',5,'{\"rol\": \"VENDEDOR\", \"nombre\": \"Ernestina Murillo Lara\"}','2026-11-24 00:38:16'),(10,1,1,'DESACTIVAR','trabajadores',2,'{\"nombre\": \"Alan Aldahir Collins Navarro\"}','2026-11-24 00:40:43'),(11,1,NULL,'EDITAR','trabajadores',2,NULL,'2026-11-24 00:41:04'),(12,1,NULL,'CREAR','ventas',1,'{\"folio\": \"V-2026-0001\", \"total\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 00:58:36'),(13,1,1,'REACTIVAR','clientes',1,'{\"nombre\": \"Danna Daybe Cota Sandoval\"}','2026-11-24 01:05:35'),(14,1,NULL,'CREAR','ventas',2,'{\"folio\": \"V-2026-0002\", \"total\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 01:05:54'),(15,1,NULL,'CREAR','ventas',3,'{\"folio\": \"V-2026-0003\", \"total\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 01:11:04'),(16,1,NULL,'CREAR','clientes',2,'{\"nombre\": \"Maria Guadalupe Larrinaga\"}','2026-11-24 11:25:01'),(17,1,NULL,'CREAR','ventas',4,'{\"folio\": \"V-2026-0004\", \"total\": 365.4, \"productos\": 1, \"descuentoPorcentaje\": 10}','2026-11-24 11:25:52'),(18,1,1,'CANCELAR','ventas',1,'{\"folio\": \"V-2026-0001\", \"total\": 406, \"motivo\": 6, \"comentario\": \"el cliente devolvio el producto\"}','2026-11-24 11:40:36'),(19,1,NULL,'SOLICITAR','solicitudes',1,'{\"folio\": \"SOL-2026-0001\", \"venta\": \"V-2026-0004\", \"motivo\": \"porque si\"}','2026-11-24 12:08:47'),(20,1,1,'EDITAR','ventas',4,'{\"antes\": {\"total\": 365.4, \"cliente\": 2, \"descuento\": 10, \"productos\": [{\"id\": 1, \"cantidad\": 1}]}, \"folio\": \"V-2026-0004\", \"despues\": {\"cliente\": 2, \"descuento\": 15, \"productos\": [{\"id\": 1, \"cantidad\": 1}]}, \"solicitud\": \"SOL-2026-0001\", \"totalAntes\": 365.4, \"totalDespues\": 345.1}','2026-11-24 12:09:02'),(21,1,NULL,'SOLICITAR','solicitudes',2,'{\"folio\": \"SOL-2026-0002\", \"venta\": \"V-2026-0003\", \"motivo\": \".\"}','2026-11-24 12:09:56'),(22,1,1,'EDITAR','ventas',3,'{\"antes\": {\"total\": 406, \"cliente\": null, \"descuento\": 0, \"productos\": [{\"id\": 1, \"cantidad\": 1}]}, \"folio\": \"V-2026-0003\", \"despues\": {\"cliente\": null, \"descuento\": 0, \"productos\": [{\"id\": 1, \"cantidad\": 2}]}, \"solicitud\": \"SOL-2026-0002\", \"totalAntes\": 406, \"totalDespues\": 812}','2026-11-24 12:09:56'),(23,1,NULL,'EDITAR','configuraciones',NULL,'{\"seccion\": \"Datos de la tienda\"}','2026-11-24 12:42:49'),(24,1,NULL,'EDITAR','configuraciones',NULL,'{\"seccion\": \"Notificaciones\"}','2026-11-24 12:43:46'),(25,1,NULL,'CREAR','solicitudes',3,'{\"tipo\": \"INCIDENCIA\", \"folio\": \"INC-0001\", \"producto\": 1}','2026-11-24 13:44:30'),(26,1,NULL,'EDITAR','trabajadores',3,NULL,'2026-11-24 13:56:05'),(27,3,NULL,'RESOLVER','solicitudes',3,'{\"folio\": \"INC-0001\", \"piezasDadasDeBaja\": 1}','2026-11-24 14:25:28'),(28,3,NULL,'CREAR','pedidos',1,'{\"tipo\": \"COTIZACION\", \"folio\": \"COT-2026-0001\", \"total\": 406}','2026-11-24 14:26:16'),(29,3,NULL,'CREAR','pedidos',2,'{\"tipo\": \"COTIZACION\", \"folio\": \"COT-2026-0002\", \"total\": 406}','2026-11-24 14:31:48'),(30,3,NULL,'CREAR','ventas',5,'{\"folio\": \"V-2026-0005\", \"total\": 406, \"condicion\": \"CREDITO\", \"pagoAhora\": 50, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 14:45:07'),(31,3,1,'DEVOLUCION','ventas',4,'{\"folio\": \"SOL-2026-0003\", \"venta\": \"V-2026-0004\", \"cantidad\": 1, \"producto\": 1, \"reembolso\": 345.1, \"regresaInventario\": true}','2026-11-24 14:45:55'),(32,3,1,'CANCELAR','ventas',2,'{\"folio\": \"V-2026-0002\", \"total\": 406, \"motivo\": 6, \"comentario\": \"ya no lo queire\"}','2026-11-24 15:04:37'),(33,1,NULL,'EDITAR','configuraciones',NULL,'{\"seccion\": \"Datos de la tienda\"}','2026-11-24 15:32:17'),(34,1,NULL,'CREAR','ventas',6,'{\"folio\": \"V-2026-0006\", \"total\": 406, \"condicion\": \"CONTADO\", \"pagoAhora\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 15:34:01'),(35,4,NULL,'ABRIR_TURNO','turnosCaja',1,'{\"fondo\": 1000, \"autorizado\": 1000}','2026-11-24 16:34:29'),(36,4,NULL,'CREAR','pedidos',3,'{\"tipo\": \"PEDIDO\", \"folio\": \"P-2026-0001\", \"total\": 406}','2026-11-24 16:35:11'),(37,4,1,'CREAR','ventas',7,'{\"folio\": \"V-2026-0007\", \"total\": 365.4, \"pedido\": null, \"condicion\": \"CREDITO\", \"pagoAhora\": 100, \"productos\": 1, \"descuentoPorcentaje\": 10}','2026-11-24 16:46:37'),(38,4,NULL,'CREAR','ventas',8,'{\"folio\": \"V-2026-0008\", \"total\": 406, \"pedido\": null, \"condicion\": \"CONTADO\", \"pagoAhora\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 16:46:57'),(39,4,NULL,'CREAR','ventas',9,'{\"folio\": \"V-2026-0009\", \"total\": 406, \"pedido\": \"P-2026-0001\", \"condicion\": \"CONTADO\", \"pagoAhora\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 16:47:24'),(40,4,1,'IMPRIMIR_VENTAS_DIA','ventas',NULL,'{\"dia\": \"2026-11-24\"}','2026-11-24 17:09:49'),(41,4,1,'CORTE_CAJA','turnosCaja',1,'{\"contado\": 1916, \"esperado\": 1912, \"diferencia\": 4}','2026-11-24 17:23:20'),(42,4,NULL,'ABRIR_TURNO','turnosCaja',2,'{\"fondo\": 1000, \"autorizado\": 1000}','2026-11-24 17:39:50'),(43,5,NULL,'CREAR','pedidos',4,'{\"tipo\": \"PEDIDO\", \"folio\": \"P-2026-0002\", \"total\": 406}','2026-11-24 17:51:53'),(44,4,NULL,'CREAR','ventas',10,'{\"folio\": \"V-2026-0010\", \"total\": 406, \"pedido\": \"P-2026-0002\", \"condicion\": \"CONTADO\", \"pagoAhora\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 17:52:12'),(45,5,NULL,'CREAR','pedidos',5,'{\"tipo\": \"PEDIDO\", \"folio\": \"P-2026-0003\", \"total\": 365.4}','2026-11-24 17:53:00'),(46,5,1,'AUTORIZAR_DESCUENTO','pedidos',5,'{\"folio\": \"P-2026-0003\", \"descuento\": 10, \"descuentoTotal\": 35}','2026-11-24 17:53:00'),(47,1,NULL,'CREAR','ventas',11,'{\"folio\": \"V-2026-0011\", \"total\": 365.4, \"pedido\": \"P-2026-0003\", \"condicion\": \"CREDITO\", \"pagoAhora\": 300, \"productos\": 1, \"descuentoPorcentaje\": 10}','2026-11-24 18:05:50'),(48,1,1,'REACTIVAR','trabajadores',2,'{\"nombre\": \"Alan Aldahir Collins Navarro\"}','2026-11-24 20:08:58'),(49,5,NULL,'CREAR','pedidos',6,'{\"tipo\": \"PEDIDO\", \"folio\": \"P-2026-0004\", \"total\": 406}','2026-11-24 22:49:49'),(50,5,NULL,'CREAR','pedidos',7,'{\"tipo\": \"PEDIDO\", \"folio\": \"P-2026-0005\", \"total\": 406}','2026-11-24 23:06:25'),(51,2,NULL,'SURTIR','pedidos',7,'{\"folio\": \"P-2026-0005\", \"surtio\": 2}','2026-11-24 23:07:28'),(52,4,NULL,'CREAR','ventas',12,'{\"folio\": \"V-2026-0012\", \"total\": 406, \"pedido\": \"P-2026-0004\", \"condicion\": \"CONTADO\", \"pagoAhora\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 23:08:29'),(53,4,NULL,'CREAR','ventas',13,'{\"folio\": \"V-2026-0013\", \"total\": 406, \"pedido\": \"P-2026-0005\", \"condicion\": \"CONTADO\", \"pagoAhora\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 23:08:36'),(54,5,NULL,'CREAR','pedidos',8,'{\"tipo\": \"PEDIDO\", \"folio\": \"P-2026-0006\", \"total\": 406}','2026-11-24 23:39:19'),(55,2,NULL,'SURTIR','pedidos',8,'{\"folio\": \"P-2026-0006\", \"surtio\": 2}','2026-11-24 23:39:41'),(56,4,NULL,'CREAR','ventas',14,'{\"folio\": \"V-2026-0014\", \"total\": 406, \"pedido\": \"P-2026-0006\", \"condicion\": \"CONTADO\", \"pagoAhora\": 406, \"productos\": 1, \"descuentoPorcentaje\": 0}','2026-11-24 23:40:45'),(57,2,NULL,'CREAR','productos',2,'{\"codigo\": \"FR-123\", \"nombre\": \"Faro niebla\"}','2026-11-24 23:44:05'),(58,2,NULL,'CREAR','solicitudes',5,'{\"tipo\": \"INCIDENCIA\", \"folio\": \"INC-0002\", \"producto\": 2}','2026-11-24 23:53:40'),(59,1,NULL,'RESOLVER','solicitudes',5,'{\"folio\": \"INC-0002\", \"piezasDadasDeBaja\": 1}','2026-11-24 23:54:38'),(60,2,NULL,'CREAR','solicitudes',6,'{\"tipo\": \"INCIDENCIA\", \"folio\": \"INC-0003\", \"producto\": 1}','2026-11-25 00:01:50'),(61,2,NULL,'CREAR','solicitudes',7,'{\"tipo\": \"INCIDENCIA\", \"folio\": \"INC-0004\", \"producto\": 1}','2026-11-25 00:07:43'),(62,2,NULL,'RESOLVER','solicitudes',7,'{\"folio\": \"INC-0004\", \"piezasDadasDeBaja\": 1}','2026-11-25 00:15:09');
/*!40000 ALTER TABLE `bitacora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `idCategoria` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idCategoria`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Frenos',NULL,1),(2,'Motor',NULL,1),(3,'Encendido',NULL,1),(4,'Suspensión',NULL,1),(5,'Eléctrico',NULL,1),(6,'Filtros',NULL,1),(7,'Lubricantes',NULL,1),(8,'Transmisión',NULL,1),(9,'Enfriamiento',NULL,1),(10,'Dirección',NULL,1),(11,'Accesorios',NULL,1);
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `idCliente` int unsigned NOT NULL AUTO_INCREMENT,
  `nombres` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellidoPaterno` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellidoMaterno` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombreComercial` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razonSocial` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `RFC` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipoCliente` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fechaRegistro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notas` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idCliente`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Danna Daybe Cota Sandoval',NULL,NULL,NULL,NULL,NULL,'6131283459','dannadaybecs@gmail.com','Fertimex','NORMAL',1,'2026-11-24 00:18:36','Le gusta picarse el culo'),(2,'Maria Guadalupe Larrinaga',NULL,NULL,NULL,NULL,NULL,'6131045862','maria@gmail.com','Guaycura','NORMAL',1,'2026-11-24 11:25:01',NULL);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuraciones`
--

DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuraciones` (
  `idConfiguracion` int unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaActualizacion` datetime DEFAULT NULL,
  `fk_idTrabajadorActualizacion` int unsigned DEFAULT NULL,
  PRIMARY KEY (`idConfiguracion`),
  UNIQUE KEY `clave` (`clave`),
  KEY `fk_idTrabajadorActualizacion` (`fk_idTrabajadorActualizacion`),
  CONSTRAINT `configuraciones_ibfk_1` FOREIGN KEY (`fk_idTrabajadorActualizacion`) REFERENCES `trabajadores` (`idTrabajador`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuraciones`
--

LOCK TABLES `configuraciones` WRITE;
/*!40000 ALTER TABLE `configuraciones` DISABLE KEYS */;
INSERT INTO `configuraciones` VALUES (1,'tienda.nombreComercial','San felipe','Nombre de la tienda','2026-11-24 15:32:16',1),(2,'tienda.razonSocial',NULL,'Razón social',NULL,NULL),(3,'tienda.rfc',NULL,'RFC de la tienda','2026-11-24 15:32:16',1),(4,'tienda.regimenFiscal',NULL,'Régimen fiscal',NULL,NULL),(5,'tienda.telefono','6131164319','Teléfono','2026-11-24 15:32:16',1),(6,'tienda.correo',NULL,'Correo',NULL,NULL),(7,'tienda.direccion','Niños heroes','Dirección','2026-11-24 15:32:16',1),(8,'tienda.logo','imagenes/tienda/logo-ab5e89e9-0698-4112-aca5-4da57d01be0e.png','Ruta del logo (PNG/JPG hasta 2 MB)','2026-11-24 15:32:17',1),(9,'tienda.textoComprobante','¡GRACIAS POR ELEGIRNOS, VUELVA PRONTO!','Texto al pie del comprobante','2026-11-24 15:32:16',1),(10,'iva','16','IVA general en %',NULL,NULL),(11,'stockMinimoDefecto','10','Para productos sin stock mínimo propio',NULL,NULL),(12,'monedaBase','MXN','Moneda de precios y totales',NULL,NULL),(13,'alertasStockBajo','1','1 = activadas','2026-11-24 12:43:46',1),(14,'notificacionesCorreo','1','1 = activadas (requiere servidor de correo)','2026-11-24 12:43:46',1),(15,'correoNotificaciones','jeengogo05@gmail.com','A dónde llegan los avisos','2026-11-24 12:43:46',1),(16,'minutosCierreSesion','15','Cierre de sesión automático',NULL,NULL),(17,'fondoInicialCaja','1000','Fondo con el que abre la caja (MXN)',NULL,NULL);
/*!40000 ALTER TABLE `configuraciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detallepedidos`
--

DROP TABLE IF EXISTS `detallepedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detallepedidos` (
  `idDetallePedido` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idPedido` int unsigned NOT NULL,
  `fk_idProducto` int unsigned NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tasaIVA` decimal(5,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `totalLinea` decimal(12,2) NOT NULL,
  PRIMARY KEY (`idDetallePedido`),
  KEY `fk_idPedido` (`fk_idPedido`),
  KEY `fk_idProducto` (`fk_idProducto`),
  CONSTRAINT `detallepedidos_ibfk_1` FOREIGN KEY (`fk_idPedido`) REFERENCES `pedidos` (`idPedido`),
  CONSTRAINT `detallepedidos_ibfk_2` FOREIGN KEY (`fk_idProducto`) REFERENCES `productos` (`idProducto`),
  CONSTRAINT `detallepedidos_chk_1` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detallepedidos`
--

LOCK TABLES `detallepedidos` WRITE;
/*!40000 ALTER TABLE `detallepedidos` DISABLE KEYS */;
INSERT INTO `detallepedidos` VALUES (1,1,1,1,350.00,0.00,16.00,350.00,406.00),(2,2,1,1,350.00,0.00,16.00,350.00,406.00),(3,3,1,1,350.00,0.00,16.00,350.00,406.00),(4,4,1,1,350.00,0.00,16.00,350.00,406.00),(5,5,1,1,350.00,35.00,16.00,350.00,365.40),(6,6,1,1,350.00,0.00,16.00,350.00,406.00),(7,7,1,1,350.00,0.00,16.00,350.00,406.00),(8,8,1,1,350.00,0.00,16.00,350.00,406.00);
/*!40000 ALTER TABLE `detallepedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalleventas`
--

DROP TABLE IF EXISTS `detalleventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalleventas` (
  `idDetalleVenta` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idVenta` int unsigned NOT NULL,
  `fk_idProducto` int unsigned NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `precioUnitarioVenta` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tasaIVA` decimal(5,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `totalLinea` decimal(12,2) NOT NULL,
  PRIMARY KEY (`idDetalleVenta`),
  KEY `fk_idVenta` (`fk_idVenta`),
  KEY `fk_idProducto` (`fk_idProducto`),
  CONSTRAINT `detalleventas_ibfk_1` FOREIGN KEY (`fk_idVenta`) REFERENCES `ventas` (`idVenta`),
  CONSTRAINT `detalleventas_ibfk_2` FOREIGN KEY (`fk_idProducto`) REFERENCES `productos` (`idProducto`),
  CONSTRAINT `detalleventas_chk_1` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalleventas`
--

LOCK TABLES `detalleventas` WRITE;
/*!40000 ALTER TABLE `detalleventas` DISABLE KEYS */;
INSERT INTO `detalleventas` VALUES (1,1,1,1,350.00,0.00,16.00,350.00,406.00),(2,2,1,1,350.00,0.00,16.00,350.00,406.00),(5,4,1,1,350.00,52.50,16.00,350.00,345.10),(6,3,1,2,350.00,0.00,16.00,700.00,812.00),(7,5,1,1,350.00,0.00,16.00,350.00,406.00),(8,6,1,1,350.00,0.00,16.00,350.00,406.00),(9,7,1,1,350.00,35.00,16.00,350.00,365.40),(10,8,1,1,350.00,0.00,16.00,350.00,406.00),(11,9,1,1,350.00,0.00,16.00,350.00,406.00),(12,10,1,1,350.00,0.00,16.00,350.00,406.00),(13,11,1,1,350.00,35.00,16.00,350.00,365.40),(14,12,1,1,350.00,0.00,16.00,350.00,406.00),(15,13,1,1,350.00,0.00,16.00,350.00,406.00),(16,14,1,1,350.00,0.00,16.00,350.00,406.00);
/*!40000 ALTER TABLE `detalleventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `folios`
--

DROP TABLE IF EXISTS `folios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folios` (
  `idFolio` int unsigned NOT NULL AUTO_INCREMENT,
  `tipoDocumento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefijo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `formato` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `anio` smallint unsigned DEFAULT NULL,
  `ultimoNumero` int unsigned NOT NULL DEFAULT '0',
  `longitudNumero` tinyint unsigned NOT NULL DEFAULT '4',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idFolio`),
  UNIQUE KEY `tipoDocumento` (`tipoDocumento`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `folios`
--

LOCK TABLES `folios` WRITE;
/*!40000 ALTER TABLE `folios` DISABLE KEYS */;
INSERT INTO `folios` VALUES (1,'VENTA','V','V-{AAAA}-{0000}',2026,14,4,1),(2,'PEDIDO','P','P-{AAAA}-{0000}',2026,6,4,1),(3,'COTIZACION','COT','COT-{AAAA}-{0000}',2026,2,4,1),(4,'AJUSTE','AJ','AJ-{AAAA}-{0000}',2026,6,4,1),(5,'SOLICITUD','SOL','SOL-{AAAA}-{0000}',2026,3,4,1),(6,'INCIDENCIA','INC','INC-{0000}',2026,4,4,1);
/*!40000 ALTER TABLE `folios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marcas`
--

DROP TABLE IF EXISTS `marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marcas` (
  `idMarca` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idMarca`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marcas`
--

LOCK TABLES `marcas` WRITE;
/*!40000 ALTER TABLE `marcas` DISABLE KEYS */;
INSERT INTO `marcas` VALUES (1,'Bosch',NULL,1),(2,'Mann',NULL,1),(3,'NGK',NULL,1),(4,'Denso',NULL,1),(5,'Monroe',NULL,1),(6,'LTH',NULL,1),(7,'Gates',NULL,1),(8,'SKF',NULL,1),(9,'Champion',NULL,1),(10,'Fram',NULL,1),(11,'Castrol',NULL,1),(12,'Mobil',NULL,1),(13,'ACDelco',NULL,1),(14,'Motorcraft',NULL,1),(15,'Genérica',NULL,1);
/*!40000 ALTER TABLE `marcas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marcasvehiculo`
--

DROP TABLE IF EXISTS `marcasvehiculo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marcasvehiculo` (
  `idMarcaVehiculo` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idMarcaVehiculo`),
  UNIQUE KEY `marcasvehiculo_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marcasvehiculo`
--

LOCK TABLES `marcasvehiculo` WRITE;
/*!40000 ALTER TABLE `marcasvehiculo` DISABLE KEYS */;
INSERT INTO `marcasvehiculo` VALUES (1,'Nissan',1),(2,'Chevrolet',1),(3,'Volkswagen',1),(4,'Toyota',1),(5,'Ford',1),(6,'Honda',1),(7,'Kia',1),(8,'Hyundai',1),(9,'Mazda',1),(10,'Dodge',1),(11,'RAM',1),(12,'Renault',1),(13,'Mitsubishi',1),(14,'Suzuki',1),(15,'Jeep',1),(16,'Seat',1),(17,'Peugeot',1);
/*!40000 ALTER TABLE `marcasvehiculo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metodospago`
--

DROP TABLE IF EXISTS `metodospago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `metodospago` (
  `idMetodoPago` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idMetodoPago`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metodospago`
--

LOCK TABLES `metodospago` WRITE;
/*!40000 ALTER TABLE `metodospago` DISABLE KEYS */;
INSERT INTO `metodospago` VALUES (1,'Efectivo',1),(2,'Tarjeta',1),(3,'Transferencia',1);
/*!40000 ALTER TABLE `metodospago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_crear_tabla_sessions',1),(2,'0001_01_01_000001_create_cache_table',2),(3,'0001_01_01_000002_create_jobs_table',2),(4,'2026_11_20_000001_crear_tablas_refaccionaria',2),(5,'2026_11_23_000001_insertar_categorias_y_marcas',3),(6,'2026_11_24_000001_crear_marcas_y_modelos_de_vehiculo',3),(7,'2026_11_24_000002_insertar_mas_motivos_de_devolucion',4),(8,'2026_11_24_000003_agregar_foto_a_trabajadores',5),(9,'2026_11_24_000004_dar_permisos_al_gerente',6),(10,'2026_11_24_000005_crear_turnos_de_caja',7),(11,'2026_11_24_000006_dar_permisos_al_vendedor',8),(12,'2026_11_24_000007_dar_permisos_al_almacenista',9),(13,'2026_11_25_000001_pedidos_pasan_por_almacen',10);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modelosvehiculo`
--

DROP TABLE IF EXISTS `modelosvehiculo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelosvehiculo` (
  `idModeloVehiculo` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idMarcaVehiculo` int unsigned NOT NULL,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idModeloVehiculo`),
  UNIQUE KEY `modelosVehiculo_unico` (`fk_idMarcaVehiculo`,`nombre`),
  CONSTRAINT `modelosvehiculo_fk_idmarcavehiculo_foreign` FOREIGN KEY (`fk_idMarcaVehiculo`) REFERENCES `marcasvehiculo` (`idMarcaVehiculo`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modelosvehiculo`
--

LOCK TABLES `modelosvehiculo` WRITE;
/*!40000 ALTER TABLE `modelosvehiculo` DISABLE KEYS */;
INSERT INTO `modelosvehiculo` VALUES (1,1,'Versa',1),(2,1,'Sentra',1),(3,1,'March',1),(4,1,'Tsuru',1),(5,1,'Tiida',1),(6,1,'Kicks',1),(7,1,'Altima',1),(8,1,'NP300',1),(9,1,'Frontier',1),(10,1,'X-Trail',1),(11,2,'Aveo',1),(12,2,'Spark',1),(13,2,'Beat',1),(14,2,'Onix',1),(15,2,'Cavalier',1),(16,2,'Sonic',1),(17,2,'Chevy',1),(18,2,'Silverado',1),(19,2,'S10',1),(20,2,'Tornado',1),(21,3,'Jetta',1),(22,3,'Vento',1),(23,3,'Gol',1),(24,3,'Polo',1),(25,3,'Virtus',1),(26,3,'Sedán',1),(27,3,'Golf',1),(28,3,'Tiguan',1),(29,3,'Saveiro',1),(30,3,'Amarok',1),(31,4,'Corolla',1),(32,4,'Yaris',1),(33,4,'Camry',1),(34,4,'Avanza',1),(35,4,'RAV4',1),(36,4,'Hilux',1),(37,4,'Tacoma',1),(38,4,'Hiace',1),(39,5,'Fiesta',1),(40,5,'Focus',1),(41,5,'Figo',1),(42,5,'Ikon',1),(43,5,'Escape',1),(44,5,'Ranger',1),(45,5,'F-150',1),(46,5,'Lobo',1),(47,6,'Civic',1),(48,6,'City',1),(49,6,'Fit',1),(50,6,'Accord',1),(51,6,'CR-V',1),(52,6,'HR-V',1),(53,7,'Rio',1),(54,7,'Forte',1),(55,7,'Soul',1),(56,7,'Seltos',1),(57,7,'Sportage',1),(58,8,'Grand i10',1),(59,8,'Accent',1),(60,8,'Elantra',1),(61,8,'Creta',1),(62,8,'Tucson',1),(63,9,'Mazda 2',1),(64,9,'Mazda 3',1),(65,9,'CX-3',1),(66,9,'CX-30',1),(67,9,'CX-5',1),(68,10,'Attitude',1),(69,10,'Neon',1),(70,10,'Journey',1),(71,10,'Durango',1),(72,11,'RAM 700',1),(73,11,'RAM 1500',1),(74,11,'RAM 2500',1),(75,12,'Kwid',1),(76,12,'Logan',1),(77,12,'Sandero',1),(78,12,'Stepway',1),(79,12,'Duster',1),(80,12,'Oroch',1),(81,13,'Mirage',1),(82,13,'Outlander',1),(83,13,'L200',1),(84,14,'Swift',1),(85,14,'Ertiga',1),(86,14,'Vitara',1),(87,15,'Wrangler',1),(88,15,'Cherokee',1),(89,15,'Compass',1),(90,16,'Ibiza',1),(91,16,'León',1),(92,17,'208',1),(93,17,'301',1),(94,17,'2008',1);
/*!40000 ALTER TABLE `modelosvehiculo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motivos`
--

DROP TABLE IF EXISTS `motivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motivos` (
  `idMotivo` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoriaMotivo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idMotivo`),
  UNIQUE KEY `motivos_unico` (`categoriaMotivo`,`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motivos`
--

LOCK TABLES `motivos` WRITE;
/*!40000 ALTER TABLE `motivos` DISABLE KEYS */;
INSERT INTO `motivos` VALUES (1,'Llegada de mercancía','AJUSTE',NULL,1),(2,'Conteo físico','AJUSTE',NULL,1),(3,'Merma','AJUSTE',NULL,1),(4,'Uso interno','AJUSTE',NULL,1),(5,'Error de captura','CANCELACION',NULL,1),(6,'El cliente ya no la quiere','CANCELACION',NULL,1),(7,'Producto descontinuado','DESACTIVACION',NULL,1),(8,'Registro duplicado','DESACTIVACION',NULL,1),(9,'Producto dañado','INCIDENCIA',NULL,1),(10,'Producto defectuoso','INCIDENCIA',NULL,1),(11,'Envase con fuga','INCIDENCIA',NULL,1),(12,'Producto incorrecto','INCIDENCIA',NULL,1),(13,'Accesorio faltante','INCIDENCIA',NULL,1),(14,'Etiqueta ilegible','INCIDENCIA',NULL,1),(15,'Diferencia de inventario','INCIDENCIA',NULL,1),(16,'No es compatible con el vehículo','DEVOLUCION',NULL,1),(17,'Llegó dañado','DEVOLUCION',NULL,1),(18,'Pieza equivocada (no es la que pidió el cliente)','DEVOLUCION',NULL,1),(19,'Error del vendedor al surtir','DEVOLUCION',NULL,1),(20,'El cliente compró de más','DEVOLUCION',NULL,1),(21,'El cliente ya no la necesita','DEVOLUCION',NULL,1),(22,'Producto defectuoso','DEVOLUCION',NULL,1),(23,'Falla al instalar','DEVOLUCION',NULL,1),(24,'Llegó incompleto (le falta una parte)','DEVOLUCION',NULL,1),(25,'Garantía','DEVOLUCION',NULL,1),(26,'Otro (explicar en el comentario)','DEVOLUCION',NULL,1);
/*!40000 ALTER TABLE `motivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientosinventario`
--

DROP TABLE IF EXISTS `movimientosinventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientosinventario` (
  `idMovimientoInventario` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idProducto` int unsigned NOT NULL,
  `tipoMovimiento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `existenciaAnterior` int unsigned NOT NULL,
  `existenciaNueva` int unsigned NOT NULL,
  `costoUnitario` decimal(10,2) DEFAULT NULL,
  `fk_idTrabajador` int unsigned NOT NULL,
  `fechaMovimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `referenciaTipo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referenciaId` int unsigned DEFAULT NULL,
  `fk_idMotivo` int unsigned DEFAULT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idMovimientoInventario`),
  KEY `fk_idProducto` (`fk_idProducto`),
  KEY `fk_idTrabajador` (`fk_idTrabajador`),
  KEY `fk_idMotivo` (`fk_idMotivo`),
  KEY `movimientos_referencia` (`referenciaTipo`,`referenciaId`),
  CONSTRAINT `movimientosinventario_ibfk_1` FOREIGN KEY (`fk_idProducto`) REFERENCES `productos` (`idProducto`),
  CONSTRAINT `movimientosinventario_ibfk_2` FOREIGN KEY (`fk_idTrabajador`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `movimientosinventario_ibfk_3` FOREIGN KEY (`fk_idMotivo`) REFERENCES `motivos` (`idMotivo`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientosinventario`
--

LOCK TABLES `movimientosinventario` WRITE;
/*!40000 ALTER TABLE `movimientosinventario` DISABLE KEYS */;
INSERT INTO `movimientosinventario` VALUES (1,1,'ENTRADA',10,0,10,200.00,1,'2026-11-24 00:04:23',NULL,NULL,NULL,'Stock inicial al registrar el producto'),(2,1,'SALIDA',2,10,8,NULL,1,'2026-11-24 00:09:01','AJUSTE',1,3,'Vino roto'),(3,1,'SALIDA',6,8,2,NULL,1,'2026-11-24 00:09:37','AJUSTE',2,3,'Se rompiron'),(4,1,'VENTA',1,2,1,200.00,1,'2026-11-24 00:58:36','VENTA',1,NULL,'Venta V-2026-0001'),(5,1,'ENTRADA',5,1,6,200.00,1,'2026-11-24 01:02:43','AJUSTE',3,1,NULL),(6,1,'VENTA',1,6,5,200.00,1,'2026-11-24 01:05:54','VENTA',2,NULL,'Venta V-2026-0002'),(7,1,'VENTA',1,5,4,200.00,1,'2026-11-24 01:11:04','VENTA',3,NULL,'Venta V-2026-0003'),(8,1,'VENTA',1,4,3,200.00,1,'2026-11-24 11:25:52','VENTA',4,NULL,'Venta V-2026-0004'),(9,1,'CANCELACION_VENTA',1,3,4,200.00,1,'2026-11-24 11:40:36','VENTA',1,6,'Cancelación de la venta V-2026-0001'),(10,1,'VENTA',1,4,3,200.00,1,'2026-11-24 12:09:56','VENTA',3,NULL,'Edición de la venta V-2026-0003 (solicitud SOL-2026-0002)'),(11,1,'ENTRADA',5,3,8,200.00,3,'2026-11-24 14:24:55','AJUSTE',4,1,NULL),(12,1,'INCIDENCIA',1,8,7,200.00,3,'2026-11-24 14:25:28','SOLICITUD',3,11,'Baja por la incidencia INC-0001'),(13,1,'VENTA',1,7,6,200.00,3,'2026-11-24 14:45:07','VENTA',5,NULL,'Venta V-2026-0005'),(14,1,'DEVOLUCION',1,6,7,200.00,3,'2026-11-24 14:45:55','SOLICITUD',4,16,'Devolución SOL-2026-0003 de la venta V-2026-0004'),(15,1,'CANCELACION_VENTA',1,7,8,200.00,3,'2026-11-24 15:04:37','VENTA',2,6,'Cancelación de la venta V-2026-0002'),(16,1,'VENTA',1,8,7,200.00,1,'2026-11-24 15:34:01','VENTA',6,NULL,'Venta V-2026-0006'),(17,1,'VENTA',1,7,6,200.00,4,'2026-11-24 16:46:37','VENTA',7,NULL,'Venta V-2026-0007'),(18,1,'VENTA',1,6,5,200.00,4,'2026-11-24 16:46:57','VENTA',8,NULL,'Venta V-2026-0008'),(19,1,'VENTA',1,5,4,200.00,4,'2026-11-24 16:47:24','VENTA',9,NULL,'Venta V-2026-0009'),(20,1,'VENTA',1,4,3,200.00,4,'2026-11-24 17:52:12','VENTA',10,NULL,'Venta V-2026-0010'),(21,1,'VENTA',1,3,2,200.00,1,'2026-11-24 18:05:50','VENTA',11,NULL,'Venta V-2026-0011'),(22,1,'ENTRADA',20,2,22,200.00,1,'2026-11-24 23:05:49','AJUSTE',5,1,NULL),(23,1,'VENTA',1,22,21,200.00,4,'2026-11-24 23:08:29','VENTA',12,NULL,'Venta V-2026-0012'),(24,1,'VENTA',1,21,20,200.00,4,'2026-11-24 23:08:36','VENTA',13,NULL,'Venta V-2026-0013'),(25,1,'VENTA',1,20,19,200.00,4,'2026-11-24 23:40:45','VENTA',14,NULL,'Venta V-2026-0014'),(26,2,'ENTRADA',5,0,5,241.00,2,'2026-11-24 23:44:05',NULL,NULL,NULL,'Stock inicial al registrar el producto'),(27,2,'ENTRADA',2,5,7,241.00,2,'2026-11-24 23:52:48','AJUSTE',6,1,NULL),(28,2,'INCIDENCIA',1,7,6,241.00,1,'2026-11-24 23:54:38','SOLICITUD',5,12,'Baja por la incidencia INC-0002'),(29,1,'INCIDENCIA',1,19,18,200.00,1,'2026-11-25 00:15:09','SOLICITUD',7,10,'Baja por la incidencia INC-0004');
/*!40000 ALTER TABLE `movimientosinventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagosventa`
--

DROP TABLE IF EXISTS `pagosventa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagosventa` (
  `idPagoVenta` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idVenta` int unsigned DEFAULT NULL,
  `fk_idPedido` int unsigned DEFAULT NULL,
  `fk_idMetodoPago` int unsigned NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `tipoCambio` decimal(10,4) NOT NULL DEFAULT '1.0000',
  `fechaPago` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_idTrabajador` int unsigned NOT NULL,
  `referencia` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idPagoVenta`),
  KEY `fk_idVenta` (`fk_idVenta`),
  KEY `fk_idPedido` (`fk_idPedido`),
  KEY `fk_idMetodoPago` (`fk_idMetodoPago`),
  KEY `fk_idTrabajador` (`fk_idTrabajador`),
  CONSTRAINT `pagosventa_ibfk_1` FOREIGN KEY (`fk_idVenta`) REFERENCES `ventas` (`idVenta`),
  CONSTRAINT `pagosventa_ibfk_2` FOREIGN KEY (`fk_idPedido`) REFERENCES `pedidos` (`idPedido`),
  CONSTRAINT `pagosventa_ibfk_3` FOREIGN KEY (`fk_idMetodoPago`) REFERENCES `metodospago` (`idMetodoPago`),
  CONSTRAINT `pagosventa_ibfk_4` FOREIGN KEY (`fk_idTrabajador`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `pagosventa_chk_1` CHECK (((`monto` > 0) and (`tipoCambio` > 0)))
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagosventa`
--

LOCK TABLES `pagosventa` WRITE;
/*!40000 ALTER TABLE `pagosventa` DISABLE KEYS */;
INSERT INTO `pagosventa` VALUES (1,1,NULL,2,406.00,'MXN',1.0000,'2026-11-24 00:58:36',1,NULL,NULL),(2,2,NULL,3,406.00,'MXN',1.0000,'2026-11-24 01:05:54',1,NULL,NULL),(3,3,NULL,1,812.00,'MXN',1.0000,'2026-11-24 01:11:04',1,NULL,NULL),(4,4,NULL,1,345.10,'MXN',1.0000,'2026-11-24 11:25:52',1,NULL,NULL),(5,5,NULL,1,50.00,'MXN',1.0000,'2026-11-24 14:45:07',3,NULL,'Pago inicial (anticipo)'),(6,6,NULL,1,406.00,'MXN',1.0000,'2026-11-24 15:34:01',1,NULL,NULL),(7,7,NULL,1,100.00,'MXN',1.0000,'2026-11-24 16:46:37',4,'TURNO-1','Pago inicial (anticipo) · Recibido $100.00 · Cambio $0.00'),(8,8,NULL,1,406.00,'MXN',1.0000,'2026-11-24 16:46:57',4,'TURNO-1','Recibido $450.00 · Cambio $44.00'),(9,9,3,2,406.00,'MXN',1.0000,'2026-11-24 16:47:24',4,'TURNO-1',NULL),(10,10,4,3,406.00,'MXN',1.0000,'2026-11-24 17:52:12',4,'TURNO-2',NULL),(11,11,5,1,300.00,'MXN',1.0000,'2026-11-24 18:05:50',1,NULL,'Pago inicial (anticipo)'),(12,12,6,2,406.00,'MXN',1.0000,'2026-11-24 23:08:29',4,'TURNO-2',NULL),(13,13,7,1,406.00,'MXN',1.0000,'2026-11-24 23:08:36',4,'TURNO-2','Recibido $500.00 · Cambio $94.00'),(14,14,8,2,406.00,'MXN',1.0000,'2026-11-24 23:40:45',4,'TURNO-2',NULL);
/*!40000 ALTER TABLE `pagosventa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `idPedido` int unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipoPedido` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PEDIDO',
  `fk_idCliente` int unsigned DEFAULT NULL,
  `fk_idTrabajador` int unsigned NOT NULL,
  `fechaPedido` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fechaEntrega` date DEFAULT NULL,
  `vigencia` date DEFAULT NULL,
  `estadoPedido` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `subtotal` decimal(12,2) NOT NULL,
  `descuentoTotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `impuestosTotal` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fk_idVenta` int unsigned DEFAULT NULL,
  PRIMARY KEY (`idPedido`),
  UNIQUE KEY `folio` (`folio`),
  KEY `fk_idCliente` (`fk_idCliente`),
  KEY `fk_idTrabajador` (`fk_idTrabajador`),
  KEY `fk_idVenta` (`fk_idVenta`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`fk_idCliente`) REFERENCES `clientes` (`idCliente`),
  CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`fk_idTrabajador`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`fk_idVenta`) REFERENCES `ventas` (`idVenta`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,'COT-2026-0001','COTIZACION',NULL,3,'2026-11-24 14:26:16',NULL,'2026-12-09','PENDIENTE',350.00,0.00,56.00,406.00,NULL,NULL),(2,'COT-2026-0002','COTIZACION',NULL,3,'2026-11-24 14:31:48',NULL,'2026-12-09','PENDIENTE',350.00,0.00,56.00,406.00,NULL,NULL),(3,'P-2026-0001','PEDIDO',2,4,'2026-11-24 16:35:11',NULL,NULL,'CONVERTIDA',350.00,0.00,56.00,406.00,NULL,9),(4,'P-2026-0002','PEDIDO',NULL,5,'2026-11-24 17:51:53',NULL,NULL,'CONVERTIDA',350.00,0.00,56.00,406.00,NULL,10),(5,'P-2026-0003','PEDIDO',2,5,'2026-11-24 17:53:00',NULL,NULL,'CONVERTIDA',350.00,35.00,50.40,365.40,NULL,11),(6,'P-2026-0004','PEDIDO',NULL,5,'2026-11-24 22:49:49',NULL,NULL,'CONVERTIDA',350.00,0.00,56.00,406.00,NULL,12),(7,'P-2026-0005','PEDIDO',NULL,5,'2026-11-24 23:06:25','2026-11-24',NULL,'CONVERTIDA',350.00,0.00,56.00,406.00,NULL,13),(8,'P-2026-0006','PEDIDO',NULL,5,'2026-11-24 23:39:19','2026-11-24',NULL,'CONVERTIDA',350.00,0.00,56.00,406.00,NULL,14);
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permisos` (
  `idPermiso` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idPermiso`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permisos`
--

LOCK TABLES `permisos` WRITE;
/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
INSERT INTO `permisos` VALUES (1,'ventas.registrar','Registrar ventas'),(2,'ventas.consultar','Consultar ventas'),(3,'ventas.cancelar','Cancelar ventas'),(4,'ventas.solicitarEdicion','Pedir que se edite una venta'),(5,'pagos.registrar','Registrar pagos y abonos'),(6,'pedidos.gestionar','Registrar y editar pedidos y cotizaciones'),(7,'clientes.gestionar','Registrar y editar clientes'),(8,'clientes.desactivar','Desactivar clientes'),(9,'productos.consultar','Consultar productos'),(10,'productos.gestionar','Registrar y editar productos'),(11,'productos.desactivar','Desactivar productos'),(12,'inventario.ajustar','Registrar ajustes de stock'),(13,'incidencias.reportar','Reportar incidencias'),(14,'solicitudes.autorizar','Aceptar o rechazar solicitudes'),(15,'usuarios.gestionar','Usuarios y roles'),(16,'configuracion.editar','Configuración de la tienda'),(17,'reportes.ver','Inicio y reportes');
/*!40000 ALTER TABLE `permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `idProducto` int unsigned NOT NULL AUTO_INCREMENT,
  `codigoInterno` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigoBarras` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `fk_idCategoria` int unsigned NOT NULL,
  `fk_idMarca` int unsigned NOT NULL,
  `fk_idUbicacion` int unsigned NOT NULL,
  `numeroParteFabricante` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requiereNumeroSerie` tinyint(1) NOT NULL DEFAULT '0',
  `precioCompraActual` decimal(10,2) NOT NULL,
  `precioVentaActual` decimal(10,2) NOT NULL,
  `tasaIVA` decimal(5,2) DEFAULT NULL,
  `existencia` int unsigned NOT NULL DEFAULT '0',
  `stockMinimo` int unsigned DEFAULT NULL,
  `stockMaximo` int unsigned DEFAULT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fechaActualizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`idProducto`),
  UNIQUE KEY `codigoInterno` (`codigoInterno`),
  UNIQUE KEY `codigoBarras` (`codigoBarras`),
  KEY `fk_idCategoria` (`fk_idCategoria`),
  KEY `fk_idMarca` (`fk_idMarca`),
  KEY `fk_idUbicacion` (`fk_idUbicacion`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`fk_idCategoria`) REFERENCES `categorias` (`idCategoria`),
  CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`fk_idMarca`) REFERENCES `marcas` (`idMarca`),
  CONSTRAINT `productos_ibfk_3` FOREIGN KEY (`fk_idUbicacion`) REFERENCES `ubicaciones` (`idUbicacion`),
  CONSTRAINT `productos_chk_1` CHECK (((`precioCompraActual` >= 0) and (`precioVentaActual` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'FR-047',NULL,'Filtro de aceite Castrol',NULL,6,11,2,NULL,0,200.00,350.00,NULL,18,3,NULL,NULL,1,'2026-11-24 00:04:23','2026-11-25 00:15:09'),(2,'FR-123',NULL,'Faro niebla','Faros niebla para Civic',3,2,3,NULL,0,241.00,365.00,NULL,6,2,NULL,NULL,1,'2026-11-24 23:44:05','2026-11-24 23:54:38');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productoscompatibilidades`
--

DROP TABLE IF EXISTS `productoscompatibilidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productoscompatibilidades` (
  `idCompatibilidad` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idProducto` int unsigned NOT NULL,
  `fk_idModeloVehiculo` int unsigned NOT NULL,
  `anioDesde` smallint unsigned NOT NULL,
  `anioHasta` smallint unsigned DEFAULT NULL,
  `motor` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idCompatibilidad`),
  KEY `fk_idProducto` (`fk_idProducto`),
  KEY `productoscompatibilidades_fk_idmodelovehiculo_foreign` (`fk_idModeloVehiculo`),
  CONSTRAINT `productoscompatibilidades_fk_idmodelovehiculo_foreign` FOREIGN KEY (`fk_idModeloVehiculo`) REFERENCES `modelosvehiculo` (`idModeloVehiculo`),
  CONSTRAINT `productoscompatibilidades_ibfk_1` FOREIGN KEY (`fk_idProducto`) REFERENCES `productos` (`idProducto`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productoscompatibilidades`
--

LOCK TABLES `productoscompatibilidades` WRITE;
/*!40000 ALTER TABLE `productoscompatibilidades` DISABLE KEYS */;
INSERT INTO `productoscompatibilidades` VALUES (1,1,45,1995,2005,NULL,NULL);
/*!40000 ALTER TABLE `productoscompatibilidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `idRol` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idRol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'ADMINISTRADOR','Acceso total',1),(2,'GERENTE','Autoriza solicitudes, todo menos Configuración',1),(3,'CAJERO','Cobra ventas',1),(4,'VENDEDOR','Pedidos y cotizaciones',1),(5,'ALMACENISTA','Inventario e incidencias',1);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rolpermisos`
--

DROP TABLE IF EXISTS `rolpermisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rolpermisos` (
  `idRolPermiso` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idRol` int unsigned NOT NULL,
  `fk_idPermiso` int unsigned NOT NULL,
  PRIMARY KEY (`idRolPermiso`),
  UNIQUE KEY `rolPermisos_unico` (`fk_idRol`,`fk_idPermiso`),
  KEY `fk_idPermiso` (`fk_idPermiso`),
  CONSTRAINT `rolpermisos_ibfk_1` FOREIGN KEY (`fk_idRol`) REFERENCES `roles` (`idRol`),
  CONSTRAINT `rolpermisos_ibfk_2` FOREIGN KEY (`fk_idPermiso`) REFERENCES `permisos` (`idPermiso`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rolpermisos`
--

LOCK TABLES `rolpermisos` WRITE;
/*!40000 ALTER TABLE `rolpermisos` DISABLE KEYS */;
INSERT INTO `rolpermisos` VALUES (16,1,1),(15,1,2),(14,1,3),(17,1,4),(6,1,5),(7,1,6),(2,1,7),(1,1,8),(8,1,9),(10,1,10),(9,1,11),(5,1,12),(4,1,13),(12,1,14),(13,1,15),(3,1,16),(11,1,17),(31,2,1),(30,2,2),(29,2,3),(32,2,4),(22,2,5),(23,2,6),(19,2,7),(18,2,8),(24,2,9),(26,2,10),(25,2,11),(21,2,12),(20,2,13),(28,2,14),(27,2,17),(38,3,1),(37,3,2),(39,3,4),(34,3,5),(35,3,6),(33,3,7),(36,3,9),(41,4,6),(40,4,7),(42,4,9),(45,5,9),(46,5,10),(44,5,12),(43,5,13);
/*!40000 ALTER TABLE `rolpermisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('3YbBGD42V6W5FZy4ibUQWFC3tBoj0artb3gRVsRO',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 OPR/140.0.0.0 (Edition std-2)','eyJfdG9rZW4iOiJBRG93cmZFWjNQWWNhcHMxNWVRUlU1V1JIUjFRWVN2OWVrRVNRdFd0IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL2FsbWFjZW5pc3RhXC9pbmNpZGVuY2lhcyIsInJvdXRlIjoiYWxtYWNlbmlzdGEuYWxlcnRhcy5pbmNpZGVuY2lhcyJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MiwicGFzc3dvcmRfaGFzaF93ZWIiOiI0ZjZlODViNzAzOTY1N2E0ZjM3NjEzOWFiM2ZiY2FjNTc5OTg0YTM0MGIxZjc2MTJlY2UyNzY4YmIxYzRkYzBlIiwidWx0aW1hQWN0aXZpZGFkIjoxNzk1NTkwOTA5fQ==',1795590909);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes`
--

DROP TABLE IF EXISTS `solicitudes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes` (
  `idSolicitud` int unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipoSolicitud` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fk_idCliente` int unsigned DEFAULT NULL,
  `fk_idVenta` int unsigned DEFAULT NULL,
  `fk_idProducto` int unsigned DEFAULT NULL,
  `cantidad` int unsigned DEFAULT NULL,
  `fk_idTrabajadorSolicitante` int unsigned DEFAULT NULL,
  `fk_idTrabajadorResponsable` int unsigned DEFAULT NULL,
  `fechaSolicitud` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `prioridad` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fk_idMotivo` int unsigned DEFAULT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentario` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datosPropuestos` json DEFAULT NULL,
  `fechaResolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`idSolicitud`),
  UNIQUE KEY `folio` (`folio`),
  KEY `fk_idCliente` (`fk_idCliente`),
  KEY `fk_idVenta` (`fk_idVenta`),
  KEY `fk_idProducto` (`fk_idProducto`),
  KEY `fk_idTrabajadorSolicitante` (`fk_idTrabajadorSolicitante`),
  KEY `fk_idTrabajadorResponsable` (`fk_idTrabajadorResponsable`),
  KEY `fk_idMotivo` (`fk_idMotivo`),
  CONSTRAINT `solicitudes_ibfk_1` FOREIGN KEY (`fk_idCliente`) REFERENCES `clientes` (`idCliente`),
  CONSTRAINT `solicitudes_ibfk_2` FOREIGN KEY (`fk_idVenta`) REFERENCES `ventas` (`idVenta`),
  CONSTRAINT `solicitudes_ibfk_3` FOREIGN KEY (`fk_idProducto`) REFERENCES `productos` (`idProducto`),
  CONSTRAINT `solicitudes_ibfk_4` FOREIGN KEY (`fk_idTrabajadorSolicitante`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `solicitudes_ibfk_5` FOREIGN KEY (`fk_idTrabajadorResponsable`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `solicitudes_ibfk_6` FOREIGN KEY (`fk_idMotivo`) REFERENCES `motivos` (`idMotivo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes`
--

LOCK TABLES `solicitudes` WRITE;
/*!40000 ALTER TABLE `solicitudes` DISABLE KEYS */;
INSERT INTO `solicitudes` VALUES (1,'SOL-2026-0001','EDITAR_VENTA',2,4,NULL,NULL,1,1,'2026-11-24 12:08:47','APROBADA',NULL,NULL,'porque si',NULL,'{\"antes\": {\"total\": 365.4, \"cliente\": 2, \"descuento\": 10, \"productos\": [{\"id\": 1, \"cantidad\": 1}]}, \"total\": 345.1, \"cliente\": 2, \"descuento\": 15, \"productos\": [{\"id\": 1, \"cantidad\": 1}]}','2026-11-24 12:09:02'),(2,'SOL-2026-0002','EDITAR_VENTA',NULL,3,NULL,NULL,1,1,'2026-11-24 12:09:56','APROBADA',NULL,NULL,'.',NULL,'{\"antes\": {\"total\": 406, \"cliente\": null, \"descuento\": 0, \"productos\": [{\"id\": 1, \"cantidad\": 1}]}, \"total\": 812, \"cliente\": null, \"descuento\": 0, \"productos\": [{\"id\": 1, \"cantidad\": 2}]}','2026-11-24 12:09:56'),(3,'INC-0001','INCIDENCIA',NULL,NULL,1,1,1,3,'2026-11-24 13:44:30','RESUELTA','MEDIA',11,'viene roto','merma de producto','{\"piezasDadasDeBaja\": 1}','2026-11-24 14:25:28'),(4,'SOL-2026-0003','DEVOLUCION',2,4,1,1,3,1,'2026-11-24 14:45:55','APROBADA',NULL,16,NULL,NULL,'{\"reembolso\": 345.1, \"precioUnitario\": 350, \"regresaInventario\": true}','2026-11-24 14:45:55'),(5,'INC-0002','INCIDENCIA',NULL,NULL,2,1,2,1,'2026-11-24 23:53:40','RESUELTA','BAJA',12,'Llego un faro de otro coche','Se desara de el producto','{\"piezasDadasDeBaja\": 1}','2026-11-24 23:54:38'),(6,'INC-0003','INCIDENCIA',NULL,NULL,1,1,2,NULL,'2026-11-25 00:01:50','PENDIENTE','BAJA',9,'viene dañado de un costado',NULL,NULL,NULL),(7,'INC-0004','INCIDENCIA',NULL,NULL,1,1,2,1,'2026-11-25 00:07:43','RESUELTA','MEDIA',10,'asdasdas','se deasdkaskd','{\"piezasDadasDeBaja\": 1}','2026-11-25 00:15:09');
/*!40000 ALTER TABLE `solicitudes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trabajadores`
--

DROP TABLE IF EXISTS `trabajadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajadores` (
  `idTrabajador` int unsigned NOT NULL AUTO_INCREMENT,
  `nombres` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidoPaterno` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidoMaterno` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `RFC` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correoElectronico` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hashContrasena` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fk_idRol` int unsigned NOT NULL,
  `fechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fechaUltimoAcceso` datetime DEFAULT NULL,
  `rememberToken` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idTrabajador`),
  UNIQUE KEY `RFC` (`RFC`),
  UNIQUE KEY `correoElectronico` (`correoElectronico`),
  KEY `fk_idRol` (`fk_idRol`),
  CONSTRAINT `trabajadores_ibfk_1` FOREIGN KEY (`fk_idRol`) REFERENCES `roles` (`idRol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trabajadores`
--

LOCK TABLES `trabajadores` WRITE;
/*!40000 ALTER TABLE `trabajadores` DISABLE KEYS */;
INSERT INTO `trabajadores` VALUES (1,'Jesus Enrique','Garcia','Gomez','GAGJ140905ND1','6131164319','imagenes/perfiles/foto-6b64aebb-82ed-40a1-a9db-be780b997b4a.jpg','jeengogo05@gmail.com','$2y$12$WtxYrSmq4S28aSJpdwZrnuafZew07dSEBR07jZtVa2Er9zb/QHLIW',1,'2026-11-20 20:26:53',1,'2026-11-24 23:53:56',NULL),(2,'Alan Aldahir','Collins','Navarro','CONA050415ND9','6131047896',NULL,'alan@gmail.com','$2y$12$7KKKSDIzghXdidgt0r07w.TK2seRhM3fIaQxxjj7vAfrJSFTvWtji',5,'2026-11-24 00:32:40',1,'2026-11-25 00:00:58',NULL),(3,'Danna Daybe','Cota','Sandoval','COSD050405MB2','6131083459','imagenes/perfiles/foto-0648b58a-4900-4674-9d38-d5873935bbd2.png','danna@gmail.com','$2y$12$z/QBP8DwwH11t71fpF5UwOA/v.tCZvViObmuv9esmTzn/i7n42yXi',2,'2026-11-24 00:34:14',1,'2026-11-24 14:24:04',NULL),(4,'Maricruz','Talamantes','Meza','TAMM051117LK1','6131023457',NULL,'miri@gmail.com','$2y$12$Vb.TG.MWNeVrQDmH0bPwwOS5zHHPTXHa44IJySxX2iMUQMEvpGqbO',3,'2026-11-24 00:36:58',1,'2026-11-24 23:39:49',NULL),(5,'Ernestina','Murillo','Lara','MULE051027LK2','6131187456',NULL,'mune@gmail.com','$2y$12$STcFYcry5PYwNGTsUZ.hm.xFi1DFDT.m1MoGPP4/LZcDv/1iynEz.',4,'2026-11-24 00:38:16',1,'2026-11-24 23:39:09',NULL);
/*!40000 ALTER TABLE `trabajadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turnoscaja`
--

DROP TABLE IF EXISTS `turnoscaja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnoscaja` (
  `idTurno` int unsigned NOT NULL AUTO_INCREMENT,
  `fk_idTrabajador` int unsigned NOT NULL,
  `numeroCaja` tinyint unsigned NOT NULL DEFAULT '1',
  `estado` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ABIERTO',
  `fechaApertura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fondoInicial` decimal(12,2) NOT NULL,
  `conteoApertura` json DEFAULT NULL,
  `fk_idTrabajadorAutorizaApertura` int unsigned DEFAULT NULL,
  `observacionesApertura` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaCierre` datetime DEFAULT NULL,
  `conteoCierre` json DEFAULT NULL,
  `efectivoContado` decimal(12,2) DEFAULT NULL,
  `tarjetaContado` decimal(12,2) DEFAULT NULL,
  `transferenciaContado` decimal(12,2) DEFAULT NULL,
  `efectivoEsperado` decimal(12,2) DEFAULT NULL,
  `tarjetaEsperado` decimal(12,2) DEFAULT NULL,
  `transferenciaEsperado` decimal(12,2) DEFAULT NULL,
  `diferencia` decimal(12,2) DEFAULT NULL,
  `fk_idTrabajadorAutorizaCierre` int unsigned DEFAULT NULL,
  `observacionesCierre` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idTurno`),
  KEY `turnos_trabajador_estado` (`fk_idTrabajador`,`estado`),
  KEY `turnoscaja_fk_idtrabajadorautorizaapertura_foreign` (`fk_idTrabajadorAutorizaApertura`),
  KEY `turnoscaja_fk_idtrabajadorautorizacierre_foreign` (`fk_idTrabajadorAutorizaCierre`),
  CONSTRAINT `turnoscaja_fk_idtrabajador_foreign` FOREIGN KEY (`fk_idTrabajador`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `turnoscaja_fk_idtrabajadorautorizaapertura_foreign` FOREIGN KEY (`fk_idTrabajadorAutorizaApertura`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `turnoscaja_fk_idtrabajadorautorizacierre_foreign` FOREIGN KEY (`fk_idTrabajadorAutorizaCierre`) REFERENCES `trabajadores` (`idTrabajador`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turnoscaja`
--

LOCK TABLES `turnoscaja` WRITE;
/*!40000 ALTER TABLE `turnoscaja` DISABLE KEYS */;
INSERT INTO `turnoscaja` VALUES (1,4,1,'CERRADO','2026-11-24 16:34:29',1000.00,'{\"monedas\": 100, \"billetes\": {\"20\": 5, \"50\": 4, \"100\": 4, \"200\": 1, \"500\": 0}}',NULL,NULL,'2026-11-24 17:23:20','{\"otros\": [{\"id\": 2, \"nombre\": \"Tarjeta\", \"contado\": 406, \"esperado\": 406}, {\"id\": 3, \"nombre\": \"Transferencia\", \"contado\": 0, \"esperado\": 0}], \"monedas\": 70, \"billetes\": {\"20\": 2, \"50\": 4, \"100\": 0, \"200\": 1, \"500\": 2}, \"reembolsos\": 0, \"cobradoEfectivo\": 506}',1510.00,406.00,0.00,1506.00,406.00,0.00,4.00,1,'sobraron 4 pesos'),(2,4,1,'ABIERTO','2026-11-24 17:39:50',1000.00,'{\"monedas\": 20, \"billetes\": {\"20\": 4, \"50\": 2, \"100\": 4, \"200\": 2, \"500\": 0}}',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `turnoscaja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ubicaciones`
--

DROP TABLE IF EXISTS `ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ubicaciones` (
  `idUbicacion` int unsigned NOT NULL AUTO_INCREMENT,
  `pasillo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rack` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anaquel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nivel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `posicion` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigoUbicacion` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idUbicacion`),
  UNIQUE KEY `codigoUbicacion` (`codigoUbicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubicaciones`
--

LOCK TABLES `ubicaciones` WRITE;
/*!40000 ALTER TABLE `ubicaciones` DISABLE KEYS */;
INSERT INTO `ubicaciones` VALUES (1,NULL,NULL,NULL,NULL,NULL,'SIN-ASIGNAR','Sin asignar',1),(2,NULL,NULL,NULL,NULL,NULL,'A-1',NULL,1),(3,'A','R-02','A-01',NULL,NULL,'A · A-01 · R-02',NULL,1);
/*!40000 ALTER TABLE `ubicaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `idVenta` int unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fk_idCliente` int unsigned DEFAULT NULL,
  `fk_idTrabajador` int unsigned NOT NULL,
  `fechaVenta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estadoVenta` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETADA',
  `condicionPago` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CONTADO',
  `subtotal` decimal(12,2) NOT NULL,
  `descuentoTotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `impuestosTotal` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaCancelacion` datetime DEFAULT NULL,
  `fk_idTrabajadorCancelacion` int unsigned DEFAULT NULL,
  `fk_idMotivoCancelacion` int unsigned DEFAULT NULL,
  `motivoCancelacion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idVenta`),
  UNIQUE KEY `folio` (`folio`),
  KEY `fk_idCliente` (`fk_idCliente`),
  KEY `fk_idTrabajador` (`fk_idTrabajador`),
  KEY `fk_idTrabajadorCancelacion` (`fk_idTrabajadorCancelacion`),
  KEY `fk_idMotivoCancelacion` (`fk_idMotivoCancelacion`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`fk_idCliente`) REFERENCES `clientes` (`idCliente`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`fk_idTrabajador`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`fk_idTrabajadorCancelacion`) REFERENCES `trabajadores` (`idTrabajador`),
  CONSTRAINT `ventas_ibfk_4` FOREIGN KEY (`fk_idMotivoCancelacion`) REFERENCES `motivos` (`idMotivo`),
  CONSTRAINT `ventas_chk_1` CHECK (((`subtotal` >= 0) and (`descuentoTotal` >= 0) and (`impuestosTotal` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,'V-2026-0001',NULL,1,'2026-11-24 00:58:36','CANCELADA','CONTADO',350.00,0.00,56.00,406.00,NULL,'2026-11-24 11:40:36',1,6,'el cliente devolvio el producto'),(2,'V-2026-0002',1,1,'2026-11-24 01:05:54','CANCELADA','CONTADO',350.00,0.00,56.00,406.00,NULL,'2026-11-24 15:04:37',3,6,'ya no lo queire'),(3,'V-2026-0003',NULL,1,'2026-11-24 01:11:04','COMPLETADA','CONTADO',700.00,0.00,112.00,812.00,NULL,NULL,NULL,NULL,NULL),(4,'V-2026-0004',2,1,'2026-11-24 11:25:52','COMPLETADA','CONTADO',350.00,52.50,47.60,345.10,NULL,NULL,NULL,NULL,NULL),(5,'V-2026-0005',1,3,'2026-11-24 14:45:07','COMPLETADA','CREDITO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL),(6,'V-2026-0006',NULL,1,'2026-11-24 15:34:01','COMPLETADA','CONTADO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL),(7,'V-2026-0007',1,4,'2026-11-24 16:46:36','COMPLETADA','CREDITO',350.00,35.00,50.40,365.40,NULL,NULL,NULL,NULL,NULL),(8,'V-2026-0008',NULL,4,'2026-11-24 16:46:57','COMPLETADA','CONTADO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL),(9,'V-2026-0009',2,4,'2026-11-24 16:47:24','COMPLETADA','CONTADO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL),(10,'V-2026-0010',NULL,4,'2026-11-24 17:52:12','COMPLETADA','CONTADO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL),(11,'V-2026-0011',2,1,'2026-11-24 18:05:50','COMPLETADA','CREDITO',350.00,35.00,50.40,365.40,NULL,NULL,NULL,NULL,NULL),(12,'V-2026-0012',NULL,4,'2026-11-24 23:08:29','COMPLETADA','CONTADO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL),(13,'V-2026-0013',NULL,4,'2026-11-24 23:08:36','COMPLETADA','CONTADO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL),(14,'V-2026-0014',NULL,4,'2026-11-24 23:40:45','COMPLETADA','CONTADO',350.00,0.00,56.00,406.00,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'refaccionaria2'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-25 10:29:34
