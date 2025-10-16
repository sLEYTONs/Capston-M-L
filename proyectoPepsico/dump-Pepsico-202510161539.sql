-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: Pepsico
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ingreso_vehiculos`
--

DROP TABLE IF EXISTS `ingreso_vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingreso_vehiculos` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Placa` varchar(10) NOT NULL,
  `TipoVehiculo` varchar(50) NOT NULL,
  `Marca` varchar(50) NOT NULL,
  `Modelo` varchar(50) NOT NULL,
  `Color` varchar(30) DEFAULT 'Sin especificar',
  `Anio` smallint(5) unsigned DEFAULT NULL CHECK (`Anio` between 1980 and 2025),
  `ConductorNombre` varchar(100) NOT NULL,
  `ConductorCedula` varchar(15) NOT NULL,
  `ConductorTelefono` varchar(20) DEFAULT 'No registrado',
  `Licencia` varchar(20) DEFAULT 'No registrada',
  `EmpresaCodigo` varchar(50) NOT NULL,
  `EmpresaNombre` varchar(100) NOT NULL,
  `FechaIngreso` datetime NOT NULL DEFAULT current_timestamp(),
  `Proposito` varchar(50) NOT NULL,
  `Area` varchar(50) DEFAULT 'General',
  `PersonaContacto` varchar(100) DEFAULT 'No asignado',
  `Observaciones` text DEFAULT NULL,
  `Estado` enum('active','inactive','retired') DEFAULT 'active',
  `FechaRegistro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Placa` (`Placa`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingreso_vehiculos`
--

LOCK TABLES `ingreso_vehiculos` WRITE;
/*!40000 ALTER TABLE `ingreso_vehiculos` DISABLE KEYS */;
INSERT INTO `ingreso_vehiculos` VALUES (1,'BB1234','camion','Volvo','FH16','Sin especificar',NULL,'Juan Perez Gonzalez','12345678-9','No registrado','No registrada','pepsico','PepsiCo Chile','2024-01-15 08:30:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(2,'CC5678','furgon','Mercedes-Benz','Sprinter','Sin especificar',NULL,'Carlos Lopez Silva','23456789-0','No registrado','No registrada','pepsico','PepsiCo Chile','2024-01-15 09:15:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(3,'DD9012','camion','Scania','R450','Sin especificar',NULL,'Ana Fernandez Rojas','34567890-1','No registrado','No registrada','pepsico','PepsiCo Chile','2024-01-15 10:00:00','recogida','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(4,'FF3456','van','Toyota','Hiace','Sin especificar',NULL,'Roberto Diaz Munoz','45678901-2','No registrado','No registrada','proveedor','Distribuidora Andina','2024-01-16 08:45:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(5,'GG7890','pickup','Ford','Ranger','Sin especificar',NULL,'Miguel Angel Torres','56789012-3','No registrado','No registrada','proveedor','Logistica Sur','2024-01-16 09:30:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(6,'HH2345','camion','Volvo','FMX','Sin especificar',NULL,'Patricia Navarro Jimenez','67890123-4','No registrado','No registrada','proveedor','Transportes del Pacifico','2024-01-16 11:20:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(7,'JJ6789','furgon','Renault','Master','Sin especificar',NULL,'Fernando Vargas Rios','78901234-5','No registrado','No registrada','cliente','Supermercado Lider','2024-01-17 07:45:00','recogida','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(8,'KK1234','camion','Mercedes-Benz','Actros','Sin especificar',NULL,'Ricardo Soto Mendez','89012345-6','No registrado','No registrada','cliente','Jumbo','2024-01-17 08:30:00','recogida','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(9,'LL5678','van','Nissan','Urvan','Sin especificar',NULL,'Gabriela Mora Pizarro','90123456-7','No registrado','No registrada','cliente','Santa Isabel','2024-01-17 10:15:00','recogida','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(10,'MM9012','pickup','Chevrolet','S10','Sin especificar',NULL,'Sergio Contreras Diaz','11234567-8','No registrado','No registrada','proveedor','Servicios Tecnicos RMC','2024-01-18 08:00:00','mantenimiento','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(11,'NN3456','camion','Kenworth','T680','Sin especificar',NULL,'Monica Reyes Paredes','22345678-9','No registrado','No registrada','proveedor','Agroindustria Central','2024-01-18 09:45:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(12,'PP7890','furgon','Fiat','Ducato','Sin especificar',NULL,'Alejandro Castro Salas','33456789-0','No registrado','No registrada','cliente','Unimarc','2024-01-18 11:30:00','recogida','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(13,'RR2345','van','Peugeot','Partner','Sin especificar',NULL,'Claudia Herrera Munoz','44567890-1','No registrado','No registrada','proveedor','Tecnologia Integral','2024-01-19 07:30:00','visita','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(14,'SS6789','camion','International','LT625','Sin especificar',NULL,'Hector Gonzalez Rojas','55678901-2','No registrado','No registrada','proveedor','Transportes Andes','2024-01-19 08:45:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(15,'TT1234','pickup','Toyota','Hilux','Sin especificar',NULL,'Veronica Silva Castro','66789012-3','No registrado','No registrada','proveedor','Constructora Norte','2024-01-19 10:20:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(16,'UU5678','furgon','Mercedes-Benz','Vito','Sin especificar',NULL,'Raul Martinez Fernandez','77890123-4','No registrado','No registrada','cliente','Acuenta','2024-01-22 08:15:00','recogida','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(17,'VV9012','camion','Volvo','VNL','Sin especificar',NULL,'Daniela Ortiz Lopez','88901234-5','No registrado','No registrada','proveedor','Distribuidora Costa','2024-01-22 09:30:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(18,'WW3456','van','Citroen','Jumper','Sin especificar',NULL,'Mauricio Diaz Soto','99012345-6','No registrado','No registrada','proveedor','Servicios Express','2024-01-22 11:00:00','visita','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(19,'XX7890','pickup','Nissan','Navara','Sin especificar',NULL,'Carolina Mendez Rios','10123456-7','No registrado','No registrada','proveedor','Mantenimiento Total','2024-01-23 07:45:00','mantenimiento','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(20,'YY2345','camion','Peterbilt','579','Sin especificar',NULL,'Francisco Rojas Herrera','21234567-8','No registrado','No registrada','proveedor','Carga Pesada Sur','2024-01-23 08:30:00','entrega','General','No asignado',NULL,'active','2025-10-16 15:12:40'),(21,'test','camion','test','test','test',2020,'test test','123','123','123','pepsico','test','2025-10-16 20:14:16','recogida','almacen','test','test','active','2025-10-16 15:14:16');
/*!40000 ALTER TABLE `ingreso_vehiculos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `UsuarioID` int(11) NOT NULL AUTO_INCREMENT,
  `NombreUsuario` varchar(50) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `ClaveHash` varchar(255) NOT NULL,
  `Rol` varchar(50) NOT NULL,
  `Estado` tinyint(1) NOT NULL DEFAULT 1,
  `FechaCreacion` datetime NOT NULL DEFAULT current_timestamp(),
  `UltimoAcceso` datetime DEFAULT NULL,
  PRIMARY KEY (`UsuarioID`),
  UNIQUE KEY `ux_NombreUsuario` (`NombreUsuario`),
  UNIQUE KEY `ux_Correo` (`Correo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','admin@pepsico.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','ADMIN',1,'2025-10-16 14:59:05','2025-10-16 14:59:17'),(2,'usuario','usuario@pepsico.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','USER',1,'2025-10-16 14:59:05',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'Pepsico'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-16 15:39:37
