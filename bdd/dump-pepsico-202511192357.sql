-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: pepsico
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
-- Table structure for table `asignaciones_mecanico`
--

DROP TABLE IF EXISTS `asignaciones_mecanico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asignaciones_mecanico` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `VehiculoID` int(10) unsigned NOT NULL,
  `MecanicoID` int(11) NOT NULL,
  `FechaAsignacion` datetime DEFAULT current_timestamp(),
  `Estado` enum('Asignado','En progreso','Completado') DEFAULT 'Asignado',
  `Observaciones` longtext DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VehiculoID` (`VehiculoID`),
  KEY `MecanicoID` (`MecanicoID`),
  CONSTRAINT `asignaciones_mecanico_ibfk_1` FOREIGN KEY (`VehiculoID`) REFERENCES `ingreso_vehiculos` (`ID`),
  CONSTRAINT `asignaciones_mecanico_ibfk_2` FOREIGN KEY (`MecanicoID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaciones_mecanico`
--

LOCK TABLES `asignaciones_mecanico` WRITE;
/*!40000 ALTER TABLE `asignaciones_mecanico` DISABLE KEYS */;
INSERT INTO `asignaciones_mecanico` VALUES (1,1,3,'2025-11-16 15:30:42','Asignado',''),(2,59,10,'2025-11-18 18:46:36','Asignado','El camión ingresa al taller para revisión y reparación general. Se reportan ruidos anómalos provenientes del tren delantero y leve pérdida de potencia en rutas largas. El operador menciona vibraciones al frenar y dificultad ocasional al encender. Se realizará diagnóstico completo en el área de trabajo general para determinar las causas y proceder con las reparaciones necesarias.');
/*!40000 ALTER TABLE `asignaciones_mecanico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avances_mecanico`
--

DROP TABLE IF EXISTS `avances_mecanico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avances_mecanico` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `AsignacionID` int(11) NOT NULL,
  `FechaAvance` datetime DEFAULT current_timestamp(),
  `Descripcion` text NOT NULL,
  `Estado` enum('En progreso','Completado') DEFAULT 'En progreso',
  PRIMARY KEY (`ID`),
  KEY `AsignacionID` (`AsignacionID`),
  CONSTRAINT `avances_mecanico_ibfk_1` FOREIGN KEY (`AsignacionID`) REFERENCES `asignaciones_mecanico` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avances_mecanico`
--

LOCK TABLES `avances_mecanico` WRITE;
/*!40000 ALTER TABLE `avances_mecanico` DISABLE KEYS */;
/*!40000 ALTER TABLE `avances_mecanico` ENABLE KEYS */;
UNLOCK TABLES;

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
  `Chasis` varchar(100) DEFAULT NULL,
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
  `Estado` enum('Ingresado','En espera','Asignado','En progreso','Completado') DEFAULT 'Ingresado',
  `FechaRegistro` datetime DEFAULT current_timestamp(),
  `EstadoIngreso` enum('Bueno','Regular','Malo','Accidentado') NOT NULL DEFAULT 'Bueno',
  `Kilometraje` int(11) DEFAULT NULL,
  `Combustible` enum('Lleno','3/4','1/2','1/4','Reserva') NOT NULL DEFAULT '1/2',
  `Documentos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Documentos`)),
  `Fotos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Fotos`)),
  `UsuarioRegistro` int(11) DEFAULT NULL,
  `Notificado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Placa` (`Placa`),
  KEY `UsuarioRegistro` (`UsuarioRegistro`),
  CONSTRAINT `ingreso_vehiculos_ibfk_1` FOREIGN KEY (`UsuarioRegistro`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingreso_vehiculos`
--

LOCK TABLES `ingreso_vehiculos` WRITE;
/*!40000 ALTER TABLE `ingreso_vehiculos` DISABLE KEYS */;
INSERT INTO `ingreso_vehiculos` VALUES (1,'JNLR12','camion','Volvo','FH16',NULL,'Blanco',2022,'Felipe Miles','201231231','123123123','LIC001','pepsico','PepsiCo','2025-10-16 02:44:20','entrega','almacen','test','test','Asignado','2025-10-15 21:44:20','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(2,'XDGG69','Camión','Volvo','FH16',NULL,'Blanco',2020,'Juan Pérez','201231232','555-0101','LIC001','PEP001','PepsiCo','2025-10-15 22:07:35','Entrega','Almacén','María García','Entrega de bebidas gaseosas','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(3,'XYZ789','Furgón','Mercedes-Benz','Sprinter',NULL,'Azul',2019,'Carlos López','201231233','555-0102','LIC002','PROV001','Distribuidora Central','2025-10-15 22:07:35','Recogida','Producción','Ana Martínez','Recogida de empaques','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(4,'JHG245','Camioneta','Toyota','Hilux',NULL,'Rojo',2021,'Pedro Ramírez','201231234','555-0103','LIC003','PEP001','PepsiCo','2025-10-15 22:07:35','Entrega','Logística','Luis Soto','Entrega de insumos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(5,'LKM923','Camión','Scania','R450',NULL,'Blanco',2018,'Ricardo Torres','14785236','555-0104','LIC004','PROV002','TransLog Chile','2025-10-15 22:07:35','Transporte','Distribución','Jorge Díaz','Transporte de pallets','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(6,'POI812','Auto','Hyundai','Elantra',NULL,'Gris',2022,'Francisco Vega','19562347','555-0105','LIC005','ADM001','Administradora Sur','2025-10-15 22:07:35','Supervisión','Oficinas','Claudia Pino','Visita técnica','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(7,'ASD321','Camión','Volvo','FMX',NULL,'Rojo',2017,'Luis Muñoz','18234756','555-0106','LIC006','PROV003','Transportes Norte','2025-10-15 22:07:35','Entrega','Depósito','Felipe Rojas','Entrega de contenedores','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(8,'BVC654','Furgón','Peugeot','Boxer',NULL,'Blanco',2020,'Mario Díaz','19872345','555-0107','LIC007','PEP002','PepsiCo Andina','2025-10-15 22:07:35','Reparto','Bodega','Patricia Leal','Reparto diario','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(9,'NHY987','Camioneta','Ford','Ranger',NULL,'Negro',2021,'Tomás Fuentes','16543219','555-0108','LIC008','PROV004','Suministros Industriales','2025-10-15 22:07:35','Entrega','Producción','Sofía Contreras','Entrega de repuestos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(10,'WER456','Camión','Iveco','Stralis',NULL,'Azul',2019,'Jorge Morales','15892347','555-0109','LIC009','PROV005','Logística Total','2025-10-15 22:07:35','Transporte','Planta','Camila Reyes','Distribución regional','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(11,'UIO741','Auto','Chevrolet','Sail',NULL,'Gris',2020,'Diego Herrera','19283746','555-0110','LIC010','ADM002','Administración Central','2025-10-15 22:07:35','Supervisión','Gerencia','Daniela Cruz','Reunión interna','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(12,'KLO951','Camioneta','Mitsubishi','L200',NULL,'Blanca',2019,'Felipe Araya','17896543','555-0111','LIC011','PROV006','Servicios del Sur','2025-10-15 22:07:35','Entrega','Patio','Rodrigo Espinoza','Entrega de materiales','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(13,'PQR147','Camión','Hino','500',NULL,'Verde',2018,'Cristóbal Núñez','18456327','555-0112','LIC012','PROV007','Carga Pesada Ltda','2025-10-15 22:07:35','Transporte','Depósito','Valentina Castro','Transporte de insumos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(14,'ZXC963','Furgón','Renault','Master',NULL,'Blanco',2021,'Matías Orellana','15324687','555-0113','LIC013','PROV008','Distribuciones Express','2025-10-15 22:07:35','Reparto','Logística','María López','Reparto de zona norte','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(15,'RTY852','Auto','Kia','Cerato',NULL,'Azul',2020,'Sebastián Vidal','17483920','555-0114','LIC014','ADM003','Oficinas Centrales','2025-10-15 22:07:35','Supervisión','Administración','Gonzalo Palma','Visita de control','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(16,'GHJ753','Camión','Mercedes-Benz','Actros',NULL,'Blanco',2021,'Rodrigo Vega','19783465','555-0115','LIC015','PROV009','Transandes','2025-10-15 22:07:35','Entrega','Depósito','Juan Rojas','Distribución nacional','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(17,'BNM159','Camioneta','Nissan','Navara',NULL,'Rojo',2022,'Pablo Aravena','18563729','555-0116','LIC016','PROV010','Servicios Patagónicos','2025-10-15 22:07:35','Entrega','Zona Sur','Ignacio Reyes','Entrega de repuestos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(18,'TYU369','Camión','DAF','XF',NULL,'Amarillo',2019,'Carlos Paredes','17382946','555-0117','LIC017','PROV011','Transportes del Norte','2025-10-15 22:07:35','Transporte','Centro Logístico','Lucía Navarro','Viaje interregional','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(19,'QWE741','Furgón','Fiat','Ducato',NULL,'Gris',2018,'Javier Campos','16273489','555-0118','LIC018','PROV012','Distribuidores del Valle','2025-10-15 22:07:35','Reparto','Zona Centro','Pedro Ortiz','Reparto de productos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(20,'MNB258','Camión','Volvo','FH12',NULL,'Rojo',2021,'Luis Vargas','19827346','555-0119','LIC019','PROV013','Carga Express','2025-10-15 22:07:35','Entrega','Depósito','Andrés Silva','Entrega urgente','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(21,'YUI357','Camioneta','Toyota','Tacoma',NULL,'Negra',2020,'Fernando Soto','16372849','555-0120','LIC020','PROV014','Servicios Técnicos','2025-10-15 22:07:35','Mantención','Planta','Rosa González','Revisión de equipos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(22,'FGH852','Auto','Mazda','3',NULL,'Blanco',2021,'Hernán Espinoza','17892364','555-0121','LIC021','ADM004','Administración Norte','2025-10-15 22:07:35','Supervisión','Gerencia','Nicolás Díaz','Supervisión de operaciones','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(23,'RTU741','Camión','Isuzu','NQR',NULL,'Blanco',2022,'Daniel Navarro','16482937','555-0122','LIC022','PROV015','Camiones del Pacífico','2025-10-15 22:07:35','Entrega','Zona Norte','Eduardo Flores','Entrega de materiales','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(24,'CXZ456','Furgón','Citroën','Jumper',NULL,'Rojo',2019,'Mario Cáceres','15928374','555-0123','LIC023','PROV016','Distribuidora Rápida','2025-10-15 22:07:35','Reparto','Zona Sur','Andrea Valdés','Reparto de insumos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(25,'VBN741','Camioneta','Chevrolet','Colorado',NULL,'Azul',2020,'Claudio Morales','18273649','555-0124','LIC024','PROV017','Logística Austral','2025-10-15 22:07:35','Transporte','Depósito','Felipe Herrera','Traslado de equipos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(26,'UIY951','Camión','MAN','TGX',NULL,'Verde',2018,'Oscar Reyes','19283745','555-0125','LIC025','PROV018','Carga Global','2025-10-15 22:07:35','Entrega','Producción','José Pérez','Entrega de materiales','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(27,'PLM123','Auto','Honda','Civic',NULL,'Blanco',2021,'Camilo Torres','17654283','555-0126','LIC026','ADM005','Oficinas Zona Sur','2025-10-15 22:07:35','Supervisión','Administración','Esteban Fuentes','Visita administrativa','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(28,'QAZ654','Camión','Volvo','FH13',NULL,'Rojo',2020,'Alberto Morales','18927346','555-0127','LIC027','PROV019','Transportes Central','2025-10-15 22:07:35','Entrega','Depósito','Patricio Vidal','Entrega de pallets','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(29,'WSX951','Camioneta','Ford','F-150',NULL,'Negro',2021,'Rodrigo Cabrera','19823746','555-0128','LIC028','PROV020','Servicios Logísticos','2025-10-15 22:07:35','Transporte','Planta','Cristina Tapia','Traslado de herramientas','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(30,'EDC357','Camión','Mercedes-Benz','Arocs',NULL,'Blanco',2022,'Hugo Leiva','16748392','555-0129','LIC029','PROV021','Transporte Total','2025-10-15 22:07:35','Entrega','Depósito','Álvaro Navarro','Entrega de alimentos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(31,'RFV852','Furgón','Peugeot','Expert',NULL,'Gris',2019,'José Ramírez','18492736','555-0130','LIC030','PROV022','Distribuciones Norte','2025-10-15 22:07:35','Reparto','Bodega','Mónica Flores','Distribución diaria','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(32,'TGB951','Auto','Toyota','Yaris',NULL,'Azul',2021,'Gonzalo Pérez','17836492','555-0131','LIC031','ADM006','Administración Sur','2025-10-15 22:07:35','Supervisión','Oficinas','Claudio Díaz','Visita administrativa','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(33,'YHN357','Camión','Scania','P410',NULL,'Blanco',2020,'Ignacio Martínez','18573924','555-0132','LIC032','PROV023','Carga Pesada','2025-10-15 22:07:35','Transporte','Depósito','Fernando Aguilera','Transporte de maquinaria','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(34,'UJM654','Camioneta','Nissan','Frontier',NULL,'Rojo',2018,'Martín Castro','19283746','555-0133','LIC033','PROV024','Servicios Técnicos','2025-10-15 22:07:35','Mantención','Taller','Carolina Moya','Inspección de vehículos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(35,'IKM951','Auto','Mazda','CX-5',NULL,'Blanco',2021,'Raúl Sandoval','18473926','555-0134','LIC034','ADM007','Gerencia Central','2025-10-15 22:07:35','Supervisión','Administración','Esteban Pino','Reunión corporativa','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(36,'OLP123','Camión','Iveco','Eurocargo',NULL,'Gris',2020,'Cristian Valdés','17645293','555-0135','LIC035','PROV025','Transportes Regionales','2025-10-15 22:07:35','Entrega','Zona Centro','Felipe Torres','Entrega interregional','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(37,'POL852','Camioneta','Toyota','Hilux',NULL,'Negra',2022,'Felipe Muñoz','19384726','555-0136','LIC036','PROV026','Servicios Logísticos Sur','2025-10-15 22:07:35','Entrega','Zona Sur','Rodrigo Ruiz','Entrega local','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(38,'LKJ147','Furgón','Mercedes-Benz','Vito',NULL,'Blanco',2019,'Alexis Bravo','16749283','555-0137','LIC037','PROV027','Distribuidora Express','2025-10-15 22:07:35','Reparto','Zona Norte','Lorena Soto','Entrega de pedidos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(39,'MKO951','Camión','MAN','TGS',NULL,'Rojo',2018,'José Salinas','18572643','555-0138','LIC038','PROV028','Carga Chile','2025-10-15 22:07:35','Transporte','Producción','Natalia Vega','Transporte interno','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(40,'NJI357','Camioneta','Ford','Raptor',NULL,'Gris',2021,'Roberto Álvarez','19765438','555-0139','LIC039','PROV029','Servicios Industriales','2025-10-15 22:07:35','Mantención','Taller','Pablo Méndez','Revisión de maquinaria','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(41,'BHU654','Auto','Honda','Fit',NULL,'Rojo',2020,'Fernando Bravo','18467291','555-0140','LIC040','ADM008','Oficinas Norte','2025-10-15 22:07:35','Supervisión','Gerencia','Valeria Campos','Visita gerencial','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(42,'VFR951','Camión','Volvo','FH16',NULL,'Blanco',2021,'Patricio Ochoa','17384925','555-0141','LIC041','PROV030','Transportes Global','2025-10-15 22:07:35','Entrega','Depósito','Eduardo Castillo','Entrega nacional','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(43,'CDE357','Camioneta','Mitsubishi','L200',NULL,'Negra',2022,'Mauricio Rivas','19482736','555-0142','LIC042','PROV031','Servicios del Valle','2025-10-15 22:07:35','Entrega','Zona Centro','Camila Navarro','Reparto regional','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(44,'XSW654','Furgón','Renault','Kangoo',NULL,'Blanco',2019,'Cristian Díaz','19873645','555-0143','LIC043','PROV032','Distribuciones del Sur','2025-10-15 22:07:35','Reparto','Zona Sur','Paula Figueroa','Entrega de productos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(45,'ZAQ951','Camión','Scania','R500',NULL,'Azul',2020,'Raúl Vergara','17293846','555-0144','LIC044','PROV033','Logística Internacional','2025-10-15 22:07:35','Transporte','Puerto','Hernán Fuentes','Exportación de carga','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(46,'XDC357','Camioneta','Toyota','Hilux',NULL,'Rojo',2021,'Felipe Carrasco','19374628','555-0145','LIC045','PEP003','PepsiCo Chile','2025-10-15 22:07:35','Entrega','Producción','Claudio Silva','Entrega de bebidas','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(47,'EDR654','Auto','Kia','Rio',NULL,'Blanco',2022,'Cristian Molina','19582647','555-0146','LIC046','ADM009','Administración General','2025-10-15 22:07:35','Supervisión','Oficinas','Héctor Vargas','Visita administrativa','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(48,'RFN951','Camión','Hino','700',NULL,'Verde',2020,'Sergio Tapia','16472839','555-0147','LIC047','PROV034','Transporte Nacional','2025-10-15 22:07:35','Transporte','Depósito','Mario Gutiérrez','Distribución nacional','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(49,'TGB753','Camioneta','Chevrolet','S10',NULL,'Azul',2021,'Carlos Jiménez','18294736','555-0148','LIC048','PROV035','Servicios Técnicos Norte','2025-10-15 22:07:35','Entrega','Zona Norte','Tomás Herrera','Entrega de repuestos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(50,'YHN258','Auto','Hyundai','Accent',NULL,'Rojo',2020,'Ricardo Navarro','19283645','555-0149','LIC049','ADM010','Gerencia Comercial','2025-10-15 22:07:35','Supervisión','Oficinas','Gabriela Soto','Revisión administrativa','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(51,'UJM951','Camión','Mercedes-Benz','Axor',NULL,'Blanco',2021,'Claudio Ortega','18473928','555-0150','LIC050','PROV036','Carga Logística','2025-10-15 22:07:35','Entrega','Depósito','Verónica Torres','Entrega de insumos','Ingresado','2025-10-15 22:07:35','Bueno',NULL,'1/2',NULL,NULL,NULL,0),(54,'ABCD35','Camión','test','test','12312323123123123','Blanco',2022,'Chofer','1231231233','1231312313','test','1231231313','test','2025-11-13 17:14:59','Reparación','General','test','test','Ingresado','2025-11-13 17:14:59','Bueno',22,'Lleno',NULL,NULL,1,0),(55,'ABCD34','Camión','test','test','12312323123123123','Blanco',2022,'Chofer','1231231233','1231231231','test','1231233','test','2025-11-13 17:17:27','Mantenimiento','Mecánica','tset','test','Ingresado','2025-11-13 17:17:27','Bueno',22,'3/4',NULL,NULL,1,0),(56,'ABCD36','Camión','test','test','12312323123123123','Blanco',2022,'Admin','1231231233','123123123','test','131231313','test','2025-11-15 10:48:23','Mantenimiento','General','PERSONA 1','TEST','Ingresado','2025-11-15 10:48:23','Bueno',23,'1/4',NULL,NULL,1,0),(57,'ABCD37','Camión','test','test','123123123','Blanco',2025,'Admin','1231231233','2343434','23434','23434','test','2025-11-15 11:02:06','Mantenimiento','Mecánica','test','test','Ingresado','2025-11-15 11:02:06','Regular',22,'1/2',NULL,NULL,1,0),(58,'ABCD38','Camión','test','test','12312323123123123','Blanco',2025,'Admin','1231231233','324234234','test','234234','test','2025-11-15 11:45:17','Mantenimiento','General','test','test','Ingresado','2025-11-15 11:45:17','Bueno',33,'3/4',NULL,NULL,1,0),(59,'ABCD40','Camión','volvo','4xga','WMA13XZZ9KC742581','Blanco',2025,'Chofer','149873264','123123123123','LNC-4827-93','EMP-903174-X','test','2025-11-18 18:34:00','Reparación','General','Mecanico','El camión ingresa al taller para revisión y reparación general. Se reportan ruidos anómalos provenientes del tren delantero y leve pérdida de potencia en rutas largas. El operador menciona vibraciones al frenar y dificultad ocasional al encender. Se realizará diagnóstico completo en el área de trabajo general para determinar las causas y proceder con las reparaciones necesarias.','Asignado','2025-11-18 18:34:00','Bueno',22,'1/2',NULL,NULL,1,0),(78,'ABCD69','Camión','test','test','WNX1341N1234','Blanco',2002,'Chofer','123131231232','1231231231','LNC-4827-93','2123123123','test','2025-11-18 22:44:43','Mantenimiento','Mecánica','test','test','Ingresado','2025-11-18 22:44:43','Bueno',22,'3/4',NULL,NULL,8,0),(80,'ABCD55','Camión','test','test','WMASCWRWER13122','Blanco',2022,'Chofer','111111111121','1234567890','TEST123123123','1111111111111111','test','2025-11-19 20:49:05','Mantenimiento','Mecánica','test','test','Ingresado','2025-11-19 20:49:05','Regular',22,'1/2',NULL,'[{\"success\":true,\"ruta\":\"..\\/..\\/uploads\\/fotos\\/691e5771af7f5_1763596145_test.jpeg\",\"nombre_guardado\":\"691e5771af7f5_1763596145_test.jpeg\",\"nombre_original\":\"test.jpeg\",\"tipo\":\"foto\",\"extension\":\"jpeg\"}]',8,0),(81,'ABCD60','Camión','test','test','12345612345','Blanco',2022,'Chofer','1234567654','12345612345','TEST123','TEST123','TEST','2025-11-19 21:42:15','Reparación','General','Mecanico','TEST','Ingresado','2025-11-19 22:21:46','Bueno',22,'3/4',NULL,'[{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 01:42:15\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"frontal\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 01:42:15\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"lateral-izq\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 01:42:15\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"lateral-der\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 01:42:15\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"trasera\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 01:42:15\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"interior\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 01:42:15\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"da\\u00f1os\"}]',8,0),(82,'ABCD61','Camión','test','test','TEST12345671234','Blanco',2022,'Chofer','123333333333','123456789','123232333333333','1111111111','test','2025-11-19 22:57:33','Mantenimiento','General','test','test','Ingresado','2025-11-19 22:59:04','Bueno',112,'1/2',NULL,'[{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 02:57:33\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"frontal\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 02:57:33\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"lateral-izq\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 02:57:33\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"lateral-der\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 02:57:33\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"trasera\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 02:57:33\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"interior\"},{\"foto\":\"data:,\",\"fecha\":\"2025-11-20 02:57:33\",\"usuario\":5,\"tipo\":\"foto_vehiculo\",\"angulo\":\"da\\u00f1os\"}]',8,0);
/*!40000 ALTER TABLE `ingreso_vehiculos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `modulo` varchar(100) NOT NULL,
  `enlace` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_leida` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`UsuarioID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES (1,2,'Nuevo Ingreso de Vehículo','Vehículo ABCD61 - test test ingresado por Chofer','ingreso_vehiculos','consulta.php',0,'2025-11-20 01:59:04',NULL),(2,7,'Nuevo Ingreso de Vehículo','Vehículo ABCD61 - test test ingresado por Chofer','ingreso_vehiculos','consulta.php',1,'2025-11-20 01:59:04','2025-11-20 02:01:53');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `novedades_guardia`
--

DROP TABLE IF EXISTS `novedades_guardia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `novedades_guardia` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Placa` varchar(10) NOT NULL,
  `Tipo` varchar(50) NOT NULL,
  `Descripcion` text NOT NULL,
  `Gravedad` enum('Baja','Media','Alta','Critica') NOT NULL,
  `UsuarioReporta` int(11) NOT NULL,
  `FechaReporte` datetime DEFAULT current_timestamp(),
  `Estado` enum('Pendiente','Atendida','Cerrada') DEFAULT 'Pendiente',
  PRIMARY KEY (`ID`),
  KEY `UsuarioReporta` (`UsuarioReporta`),
  CONSTRAINT `novedades_guardia_ibfk_1` FOREIGN KEY (`UsuarioReporta`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `novedades_guardia`
--

LOCK TABLES `novedades_guardia` WRITE;
/*!40000 ALTER TABLE `novedades_guardia` DISABLE KEYS */;
INSERT INTO `novedades_guardia` VALUES (1,'ABCD60','Daño vehiculo','choque','Media',5,'2025-11-19 21:41:51','Pendiente');
/*!40000 ALTER TABLE `novedades_guardia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repuestos`
--

DROP TABLE IF EXISTS `repuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repuestos` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Codigo` varchar(50) NOT NULL,
  `Nombre` varchar(255) NOT NULL,
  `Categoria` varchar(100) DEFAULT NULL,
  `Stock` int(11) DEFAULT 0,
  `Precio` decimal(10,2) DEFAULT 0.00,
  `StockMinimo` int(11) DEFAULT 5,
  `Descripcion` text DEFAULT NULL,
  `Estado` varchar(20) DEFAULT 'Activo',
  `FechaCreacion` datetime DEFAULT current_timestamp(),
  `FechaActualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Codigo` (`Codigo`),
  KEY `Estado` (`Estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repuestos`
--

LOCK TABLES `repuestos` WRITE;
/*!40000 ALTER TABLE `repuestos` DISABLE KEYS */;
/*!40000 ALTER TABLE `repuestos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repuestos_asignacion`
--

DROP TABLE IF EXISTS `repuestos_asignacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repuestos_asignacion` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `AsignacionID` int(11) NOT NULL,
  `RepuestoID` int(11) NOT NULL,
  `Cantidad` int(11) NOT NULL DEFAULT 1,
  `PrecioUnitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Total` decimal(10,2) GENERATED ALWAYS AS (`Cantidad` * `PrecioUnitario`) STORED,
  `FechaRegistro` datetime DEFAULT current_timestamp(),
  `Observaciones` text DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `AsignacionID` (`AsignacionID`),
  KEY `RepuestoID` (`RepuestoID`),
  KEY `idx_repuestos_asignacion_asignacion` (`AsignacionID`,`RepuestoID`),
  CONSTRAINT `fk_repuestos_asignacion_asignacion` FOREIGN KEY (`AsignacionID`) REFERENCES `asignaciones_mecanico` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_repuestos_asignacion_repuesto` FOREIGN KEY (`RepuestoID`) REFERENCES `repuestos` (`ID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repuestos_asignacion`
--

LOCK TABLES `repuestos_asignacion` WRITE;
/*!40000 ALTER TABLE `repuestos_asignacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `repuestos_asignacion` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Admin','admin@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrador',1,'2025-10-14 18:38:25','2025-11-18 11:50:27'),(2,'Jefetaller','usuario@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Jefe de Taller',1,'2025-10-14 18:38:25','2025-11-19 23:11:04'),(3,'Mecanico','mecanico@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-10-19 09:57:46','2025-11-19 20:52:12'),(4,'Recepcionista','recepcionista@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Recepcionista',1,'2025-10-19 09:58:23','2025-11-19 23:52:26'),(5,'Guardia','guardia@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Guardia',1,'2025-10-19 09:59:02','2025-11-19 23:53:55'),(7,'Supervisor','supervisor@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Supervisor',1,'2025-10-19 09:59:28','2025-11-19 22:59:27'),(8,'Chofer','chofer@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Chofer',1,'2025-10-19 11:02:11','2025-11-19 22:58:03'),(9,'JuanPerez','juan.perez@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(10,'CarlosMunoz','carlos.munoz@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06','2025-11-19 22:27:26'),(11,'RicardoSoto','ricardo.soto@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(12,'MiguelLopez','miguel.lopez@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(13,'DavidReyes','david.reyes@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(14,'FranciscoToro','francisco.toro@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(15,'LuisAravena','luis.aravena@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(16,'SebastianVergara','sebastian.vergara@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(17,'JorgeFuentes','jorge.fuentes@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL),(18,'MarcoBustos','marco.bustos@test.cl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mecánico',1,'2025-11-18 18:42:06',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'pepsico'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-19 23:57:47
