-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: hotel_booking_system
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.22.04.2

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
-- Table structure for table `booking_extensions`
--

DROP TABLE IF EXISTS `booking_extensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_extensions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `original_checkout` date NOT NULL,
  `new_checkout` date NOT NULL,
  `additional_nights` int NOT NULL,
  `extension_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('paid','pending','overdue') DEFAULT 'pending',
  `payment_due_date` date DEFAULT NULL,
  `payment_notes` text,
  `discount_type` varchar(20) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `booking_extensions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_extensions`
--

LOCK TABLES `booking_extensions` WRITE;
/*!40000 ALTER TABLE `booking_extensions` DISABLE KEYS */;
INSERT INTO `booking_extensions` VALUES (1,78,'2025-10-17','2025-10-18',1,40.00,'cash','pending','2025-10-21','hable con el cliente me dijo que estaba en cusco y queria extender pero no la vi llegar a noche ','fixed_pen',0.00,'2025-10-18 13:36:23',1);
/*!40000 ALTER TABLE `booking_extensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_guests`
--

DROP TABLE IF EXISTS `booking_guests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_guests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `guest_name` varchar(255) NOT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_phone` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `booking_guests_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_guests`
--

LOCK TABLES `booking_guests` WRITE;
/*!40000 ALTER TABLE `booking_guests` DISABLE KEYS */;
INSERT INTO `booking_guests` VALUES (10,71,'Juan Manuel Chazo','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ','','',1,'2025-10-17 15:45:49'),(11,72,'Juan Manuel Chazo','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ','','',1,'2025-10-17 15:47:30'),(12,73,'Juan Manuel Chazo','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ','','',1,'2025-10-17 15:47:40'),(15,65,'Gilberto Figueroa','gfigue.483406@guest.booking.com','+51 970 408 064 ','','',1,'2025-10-17 16:02:40'),(23,77,'Matt Williams','test@test.com','+31 6 44838683','','',1,'2025-10-17 17:49:01'),(28,67,'Erik Nooi','enooij.423294@guest.booking.com','+31 6 57992596','','',1,'2025-10-17 19:27:03'),(31,79,'nigel purchase','enooij.423294@guest.booking.com','+44 7462 183477','','',1,'2025-10-17 19:34:55'),(34,70,'Gustav Brandt-Pedersen','gbrand.875041@guest.booking.com','+45 53 37 40 53','','',1,'2025-10-17 20:48:17'),(36,66,'Carolina gomes','none@none.com','+972 52 299 4367 ','','',1,'2025-10-17 20:50:22'),(37,69,'it Kad','none@none.com','+972512617275','','',1,'2025-10-17 20:56:45'),(38,68,'samara, hila','none@none.com','+972506622155','','',1,'2025-10-17 20:57:59'),(39,74,'Katinka Stienen','kstien.963830@guest.booking.com','+31 6 44838683','','',1,'2025-10-17 21:06:59'),(42,75,'Llamoja Cabanillas Roony Mirko','roonyllamojallj@gmail.com','+51 986 826 664 ','','',1,'2025-10-18 05:05:40'),(45,80,'limor Sobol','sobolimor@gmail.com','+972502429889','','',1,'2025-10-18 14:57:23'),(47,78,'SAMIya haoi','samia.buhairi@gmail.com','+51900193160','','',1,'2025-10-18 20:16:24');
/*!40000 ALTER TABLE `booking_guests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_notes`
--

DROP TABLE IF EXISTS `booking_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `note_text` text NOT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `booking_notes_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_notes`
--

LOCK TABLES `booking_notes` WRITE;
/*!40000 ALTER TABLE `booking_notes` DISABLE KEYS */;
INSERT INTO `booking_notes` VALUES (1,65,'Monto pagado actualizado: 46.15 USD',1,'2025-10-17 15:59:15'),(2,65,'Monto pagado actualizado: 46.15 USD',1,'2025-10-17 15:59:42'),(3,65,'Monto pagado actualizado: 41.38 USD',1,'2025-10-17 16:03:05'),(4,74,'Monto pagado actualizado: 195 USD - Notas: PAGO EFECTIVO',1,'2025-10-17 16:51:14'),(5,74,'Monto pagado actualizado: 195 USD - Notas: PAGO EFECTIVO',1,'2025-10-17 16:51:55'),(6,74,'Monto pagado actualizado: 195 USD - Notas: PAGO EFECTIVO',1,'2025-10-17 16:52:38'),(7,74,'Monto pagado actualizado: 57.77 USD',1,'2025-10-17 17:03:01'),(8,78,'Monto pagado actualizado: 280 USD - Notas: Pago 200 Faltan 80 soles',1,'2025-10-17 19:12:41'),(9,66,'Monto pagado actualizado: 320 USD',1,'2025-10-17 19:27:27'),(10,66,'Monto pagado actualizado: 320 USD',1,'2025-10-17 19:27:54'),(11,67,'Monto pagado actualizado: 320 USD',1,'2025-10-17 19:30:36'),(12,66,'Monto pagado actualizado: 0 USD',1,'2025-10-17 20:50:43'),(13,78,'Monto pagado actualizado: 53.333333333333 USD - Notas: Pago ingresado en soles: S/ 200.00 PEN',1,'2025-10-18 00:19:03'),(14,78,'Monto pagado actualizado: 53.333333333333 USD - Notas: Pago ingresado en soles: S/ 200.00 PEN',1,'2025-10-18 00:19:12'),(15,75,'Monto pagado actualizado: 50 USD',1,'2025-10-18 05:03:53'),(16,75,'Monto pagado actualizado: 50 USD',1,'2025-10-18 05:03:57'),(17,75,'Marcado como PAGADO COMPLETO - Monto: $50.15',1,'2025-10-18 05:04:26'),(18,78,'Monto pagado actualizado: 149.33333333333 USD - Notas: Pago ingresado en soles: S/ 560.00 PEN',1,'2025-10-18 20:05:46'),(19,78,'Monto pagado actualizado: 149.33333333333 USD - Notas: Pago ingresado en soles: S/ 560.00 PEN',1,'2025-10-18 20:05:50'),(20,78,'Marcado como PAGO PARCIAL - Monto: $200.00',1,'2025-10-18 20:16:57');
/*!40000 ALTER TABLE `booking_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_multi_room` tinyint(1) DEFAULT '0',
  `primary_booking_id` int DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `service_charge` decimal(10,2) DEFAULT '0.00',
  `selected_currency` enum('USD','PEN') DEFAULT 'USD',
  `booking_reference` varchar(100) DEFAULT NULL,
  `booking_source` varchar(50) DEFAULT 'direct',
  `sync_status` enum('pending','synced','failed') DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_phone` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `client_photo` varchar(255) DEFAULT NULL,
  `special_requests` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (7,3,9,'2025-09-29','2025-10-05',112.00,'confirmed','2025-09-29 01:43:12',0,NULL,0.00,'paid','cash',112.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,8,4,'2025-09-30','2025-10-03',77.27,'confirmed','2025-09-29 09:05:54',0,NULL,0.00,'paid','cash',77.27,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,9,2,'2025-09-29','2025-10-02',63.57,'confirmed','2025-09-29 12:42:13',0,NULL,0.00,'paid','cash',63.57,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,10,7,'2025-09-30','2025-10-02',68.57,'confirmed','2025-09-29 15:04:00',0,NULL,0.00,'paid','cash',68.57,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,11,5,'2025-09-29','2025-10-02',12.00,'confirmed','2025-09-30 10:43:45',0,NULL,0.00,'paid','cash',12.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,12,6,'2025-09-30','2025-10-05',115.33,'confirmed','2025-09-30 12:57:00',0,NULL,0.00,'paid','cash',115.33,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,12,6,'2025-09-30','2025-10-02',52.00,'confirmed','2025-09-30 12:58:25',0,NULL,0.00,'paid','cash',52.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,13,3,'2025-09-30','2025-10-05',157.33,'confirmed','2025-09-30 13:55:30',0,NULL,0.00,'paid','cash',157.33,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,13,3,'2025-09-30','2025-10-04',117.33,'confirmed','2025-09-30 14:04:53',0,NULL,0.00,'paid','cash',117.33,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,14,8,'2025-09-30','2025-10-02',40.00,'confirmed','2025-09-30 14:30:47',0,NULL,0.00,'paid','cash',40.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,14,8,'2025-09-30','2025-10-02',40.00,'confirmed','2025-09-30 14:31:31',0,NULL,0.00,'paid','cash',40.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,15,10,'2025-09-30','2025-10-02',48.00,'confirmed','2025-09-30 14:40:24',0,NULL,0.00,'paid','cash',48.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(19,16,1,'2025-10-01','2025-10-02',35.00,'confirmed','2025-10-01 09:56:18',0,NULL,0.00,'paid','cash',35.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(20,17,11,'2025-10-02','2025-10-06',140.00,'confirmed','2025-10-02 14:56:22',0,NULL,0.00,'paid','cash',140.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,17,11,'2025-10-02','2025-10-03',35.00,'confirmed','2025-10-02 14:56:36',0,NULL,0.00,'paid','cash',35.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,18,1,'2025-10-02','2025-10-05',96.00,'confirmed','2025-10-02 15:22:03',0,NULL,0.00,'paid','cash',96.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,19,5,'2025-10-03','2025-10-05',42.67,'confirmed','2025-10-03 14:21:49',0,NULL,0.00,'paid','cash',42.67,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,19,5,'2025-10-03','2025-10-05',42.67,'confirmed','2025-10-03 15:20:54',0,NULL,0.00,'paid','cash',42.67,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(27,21,2,'2025-10-03','2025-10-05',129.99,'confirmed','2025-10-04 10:53:09',0,NULL,0.00,'paid','cash',129.99,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(28,20,4,'2025-10-03','2025-10-05',120.00,'confirmed','2025-10-04 11:34:13',0,NULL,0.00,'paid','cash',120.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(29,22,7,'2025-10-03','2025-10-05',69.00,'confirmed','2025-10-04 11:54:58',0,NULL,0.00,'paid','cash',69.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(30,22,8,'2025-10-03','2025-10-05',70.00,'confirmed','2025-10-04 11:58:10',0,NULL,0.00,'paid','cash',70.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(31,23,10,'2025-10-04','2025-10-06',80.00,'confirmed','2025-10-04 15:00:42',0,NULL,0.00,'paid','cash',80.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(32,24,6,'2025-10-05','2025-10-06',25.00,'confirmed','2025-10-05 15:04:18',0,NULL,0.00,'paid','cash',25.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(33,25,7,'2025-10-05','2025-10-06',25.00,'confirmed','2025-10-05 15:07:53',0,NULL,0.00,'paid','cash',25.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(34,26,11,'2025-10-07','2025-10-09',90.00,'confirmed','2025-10-07 16:55:41',0,NULL,0.00,'paid','cash',90.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(35,27,6,'2025-10-08','2025-10-12',100.00,'confirmed','2025-10-08 14:30:59',0,NULL,0.00,'paid','cash',100.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(36,28,2,'2025-10-07','2025-10-09',129.99,'confirmed','2025-10-08 14:46:53',0,NULL,0.00,'paid','cash',129.99,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(37,28,3,'2025-10-07','2025-10-10',180.00,'confirmed','2025-10-08 14:47:37',0,NULL,0.00,'paid','cash',180.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(38,28,4,'2025-10-07','2025-10-09',120.00,'confirmed','2025-10-08 14:48:07',0,NULL,0.00,'paid','cash',120.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(39,28,8,'2025-10-07','2025-10-09',70.00,'confirmed','2025-10-08 14:48:59',0,NULL,0.00,'paid','cash',70.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(40,28,10,'2025-10-07','2025-10-09',80.00,'confirmed','2025-10-08 14:49:29',0,NULL,0.00,'paid','cash',80.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(41,29,1,'2025-10-07','2025-10-09',179.98,'confirmed','2025-10-08 14:50:26',0,NULL,0.00,'paid','cash',179.98,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(42,3,5,'2025-10-06','2025-10-10',240.00,'confirmed','2025-10-08 14:55:07',0,NULL,0.00,'paid','cash',240.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(43,30,7,'2025-10-06','2025-10-09',75.00,'confirmed','2025-10-08 15:09:15',0,NULL,0.00,'paid','cash',75.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(44,31,9,'2025-10-07','2025-10-10',105.00,'confirmed','2025-10-08 16:29:21',0,NULL,0.00,'paid','cash',105.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(45,32,4,'2025-10-09','2025-10-10',60.00,'confirmed','2025-10-09 08:55:43',0,NULL,0.00,'paid','cash',60.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(46,32,7,'2025-10-09','2025-10-10',25.00,'confirmed','2025-10-09 08:56:30',0,NULL,0.00,'paid','cash',25.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(47,32,10,'2025-10-09','2025-10-10',40.00,'confirmed','2025-10-09 08:57:48',0,NULL,0.00,'paid','cash',40.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(48,33,8,'2025-10-09','2025-10-10',35.00,'confirmed','2025-10-09 13:56:00',0,NULL,0.00,'paid','cash',35.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(49,34,11,'2025-10-09','2025-10-12',135.00,'confirmed','2025-10-09 15:31:01',0,NULL,0.00,'paid','cash',135.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(50,3,3,'2025-10-10','2025-10-12',120.00,'confirmed','2025-10-10 13:22:16',0,NULL,0.00,'paid','cash',120.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(51,35,3,'2025-10-19','2025-11-01',780.00,'confirmed','2025-10-10 13:27:07',0,NULL,0.00,'refunded','cash',0.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(52,36,1,'2025-10-10','2025-10-13',260.00,'confirmed','2025-10-10 14:30:55',0,NULL,0.00,'paid','cash',260.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(53,37,5,'2025-10-10','2025-10-12',120.00,'confirmed','2025-10-10 14:58:22',0,NULL,0.00,'paid','cash',120.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(55,38,2,'2025-10-11','2025-10-12',129.99,'confirmed','2025-10-11 12:02:54',0,NULL,0.00,'paid','cash',129.99,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(57,38,9,'2025-10-11','2025-10-12',35.00,'confirmed','2025-10-11 12:02:54',0,NULL,0.00,'paid','cash',35.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(59,39,7,'2025-10-11','2025-10-12',25.00,'confirmed','2025-10-11 21:55:26',0,NULL,0.00,'paid','cash',25.00,0.00,0.00,'USD',NULL,'direct',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(65,41,1,'2025-10-15','2025-10-16',41.38,'confirmed','2025-10-15 15:35:13',0,NULL,0.00,'paid','',41.38,0.00,0.00,'USD','HTL-20251015-9687','direct',NULL,NULL,'Gilberto Figueroa','gfigue.483406@guest.booking.com','+51 970 408 064 ','','',NULL,''),(66,3,4,'2025-10-12','2025-10-17',100.00,'confirmed','2025-10-15 15:37:14',0,NULL,40.00,'pending','',0.00,0.00,0.00,'USD','HTL-20251015-8144','direct',NULL,NULL,'Carolina gomes','none@none.com','+972 52 299 4367 ','','',NULL,''),(67,42,7,'2025-10-13','2025-10-17',320.00,'confirmed','2025-10-15 15:39:18',0,NULL,0.00,'paid','',320.00,0.00,0.00,'USD','HTL-20251015-6148','direct',NULL,NULL,'Erik Nooi','enooij.423294@guest.booking.com','+31 6 57992596','','',NULL,''),(68,3,9,'2025-10-14','2025-10-17',28.00,'confirmed','2025-10-15 15:40:14',0,NULL,0.00,'pending','',0.00,0.00,0.00,'USD','HTL-20251015-3088','direct',NULL,NULL,'samara, hila','none@none.com','+972506622155','','',NULL,''),(69,3,8,'2025-10-13','2025-10-17',28.00,'confirmed','2025-10-15 15:42:24',0,NULL,56.00,'pending','',0.00,0.00,0.00,'USD','HTL-20251015-6460','direct',NULL,NULL,'it Kad','none@none.com','+972512617275','','',NULL,''),(70,43,10,'2025-10-15','2025-10-17',30.00,'confirmed','2025-10-17 13:41:52',0,NULL,30.00,'pending','',0.00,0.00,0.00,'USD','HTL-20251017-1193','direct',NULL,NULL,'Gustav Brandt-Pedersen','gbrand.875041@guest.booking.com','+45 53 37 40 53','','',NULL,''),(71,44,2,'2025-10-17','2025-10-19',48.76,'confirmed','2025-10-17 15:45:49',0,NULL,15.24,'paid','cash',48.76,0.00,0.00,'USD','HTL-20251017-3136','direct',NULL,NULL,'','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ','','',NULL,'Descuento aplicado: $15.24 (descunto de booking.com) Subtotal: $64.00 / S/ 240.00, Total final: $48.76 / S/ 182.85. '),(72,44,2,'2025-10-17','2025-10-19',48.76,'confirmed','2025-10-17 15:47:30',0,NULL,15.24,'paid','cash',48.76,0.00,0.00,'USD','HTL-20251017-3992','direct',NULL,NULL,'','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ','','',NULL,'Descuento aplicado: $15.24 (descunto de booking.com) Subtotal: $64.00 / S/ 240.00, Total final: $48.76 / S/ 182.85. '),(73,44,2,'2025-10-17','2025-10-19',48.76,'confirmed','2025-10-17 15:47:40',0,NULL,15.24,'paid','cash',48.76,0.00,0.00,'USD','HTL-20251017-5320','direct',NULL,NULL,'','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ','','',NULL,'Descuento aplicado: $15.24 (descunto de booking.com) Subtotal: $64.00 / S/ 240.00, Total final: $48.76 / S/ 182.85. '),(74,45,11,'2025-10-17','2025-10-19',56.00,'confirmed','2025-10-17 16:09:31',0,NULL,0.00,'paid','',57.77,0.00,0.00,'USD','HTL-20251017-3947','direct',NULL,NULL,'Katinka Stienen','kstien.963830@guest.booking.com','+31 6 44838683','','',NULL,''),(75,46,1,'2025-10-17','2025-10-19',50.00,'confirmed','2025-10-17 17:01:02',0,NULL,0.00,'paid','',50.15,0.00,0.00,'USD','HTL-20251017-1355','direct',NULL,NULL,'Llamoja Cabanillas Roony Mirko','lmirko.451867@guest.booking.com','+51 986 826 664 ','','',NULL,''),(77,40,9,'2025-10-17','2025-10-18',57.59,'confirmed','2025-10-17 17:47:56',0,NULL,0.00,'pending','',0.00,0.00,0.00,'USD','HTL-20251017-0502','direct',NULL,NULL,'Matt Williams','test@test.com','+31 6 44838683','','',NULL,''),(78,48,3,'2025-10-15','2025-10-19',109.33,'confirmed','2025-10-17 19:06:10',0,NULL,40.00,'partial','',200.00,0.00,0.00,'USD','HTL-20251017-6820','direct',NULL,NULL,'SAMIya haoi','samia.buhairi@gmail.com','+51900193160','','',NULL,'\r\n[EXTENSION] Extended stay from 2025-10-17 to 2025-10-18 (1 nights) - Total: $40.00 USD'),(79,42,6,'2025-10-13','2025-10-19',130.00,'confirmed','2025-10-17 19:34:10',0,NULL,0.00,'pending','',0.00,0.00,0.00,'USD','HTL-20251017-8480','direct',NULL,NULL,'nigel purchase','enooij.423294@guest.booking.com','+44 7462 183477','','',NULL,''),(80,49,8,'2025-10-18','2025-10-22',112.00,'confirmed','2025-10-17 20:35:09',0,NULL,53.33,'pending','',0.00,0.00,0.00,'USD','HTL-20251017-0334','direct',NULL,NULL,'limor Sobol','sobolimor@gmail.com','+972502429889','','',NULL,'80 per night room eight');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bot_prices`
--

DROP TABLE IF EXISTS `bot_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bot_prices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `item` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_price` (`category`,`item`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bot_prices`
--

LOCK TABLES `bot_prices` WRITE;
/*!40000 ALTER TABLE `bot_prices` DISABLE KEYS */;
INSERT INTO `bot_prices` VALUES (1,'rooms','individual',45.00,1,'2025-10-17 12:49:15'),(2,'rooms','doble',65.00,1,'2025-10-17 12:49:15'),(3,'rooms','suite',90.00,1,'2025-10-17 12:49:15'),(4,'ceremonies','ayahuasca_1day',180.00,1,'2025-10-17 12:49:15'),(5,'ceremonies','sanpedro_1day',120.00,1,'2025-10-17 12:49:15'),(6,'ceremonies','retiro_7days',980.00,1,'2025-10-17 12:49:15'),(7,'tours','machupicchu_1day',180.00,1,'2025-10-17 12:49:15'),(8,'tours','valle_sagrado',65.00,1,'2025-10-17 12:49:15'),(9,'tours','waqrapukara',85.00,1,'2025-10-17 12:49:15');
/*!40000 ALTER TABLE `bot_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bot_responses`
--

DROP TABLE IF EXISTS `bot_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bot_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `command` varchar(50) NOT NULL,
  `response` text NOT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `command` (`command`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bot_responses`
--

LOCK TABLES `bot_responses` WRITE;
/*!40000 ALTER TABLE `bot_responses` DISABLE KEYS */;
INSERT INTO `bot_responses` VALUES (1,'ayuda','🌟 *CASA DE PAZ - HOTEL ESPIRITUAL* 🌟\\n\\n*Comandos disponibles:*\\n\\n🏨 *!habitaciones* - Ver habitaciones disponibles\\n💰 *!precios* - Lista completa de precios\\n🍄 *!ceremonias* - Información sobre ceremonias\\n🏔️ *!tours* - Tours disponibles\\n📞 *!contacto* - Información de contacto\\n🪙 *!hotelcoins* - Sistema de recompensas\\n📍 *!ubicacion* - Cómo llegar\\n❓ *!info* - Información general\\n\\n¿En qué te puedo ayudar? 🙏',1,'2025-10-17 12:49:15'),(2,'precios','Precios dinámicos cargados desde base de datos',1,'2025-10-17 12:49:15'),(3,'ceremonias','Información de ceremonias cargada desde base de datos',1,'2025-10-17 12:49:15'),(4,'tours','Tours cargados desde base de datos',1,'2025-10-17 12:49:15');
/*!40000 ALTER TABLE `bot_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_config`
--

DROP TABLE IF EXISTS `email_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) NOT NULL DEFAULT 'smtp.gmail.com',
  `smtp_port` int NOT NULL DEFAULT '587',
  `smtp_username` varchar(255) NOT NULL,
  `smtp_password` varchar(255) NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) NOT NULL DEFAULT 'AiNi Hotel',
  `reply_to` varchar(255) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `use_ssl` tinyint(1) NOT NULL DEFAULT '0',
  `use_tls` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `email_config_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_config`
--

LOCK TABLES `email_config` WRITE;
/*!40000 ALTER TABLE `email_config` DISABLE KEYS */;
INSERT INTO `email_config` VALUES (1,'smtp.ionos.com',587,'gerente@samaywasipisac.com','JUor@0027','gerente@samaywasipisac.com','Samay Wasi Casa De Paz','gerente@samaywasipisac.com',1,0,1,'2025-10-18 01:39:35','2025-10-18 02:11:34',1);
/*!40000 ALTER TABLE `email_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT '0.00',
  `hourly_rate_currency` varchar(3) DEFAULT 'USD',
  `overtime_rate` decimal(10,2) DEFAULT '0.00',
  `overtime_rate_currency` varchar(3) DEFAULT 'USD',
  `weekly_hours` int DEFAULT '40',
  `salary_type` enum('hourly','monthly') DEFAULT 'hourly',
  `monthly_salary` decimal(10,2) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `address` text,
  `tax_id` varchar(50) DEFAULT NULL,
  `notes` text,
  `status` enum('active','inactive','terminated') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `user_id` (`user_id`),
  KEY `idx_employees_employee_id` (`employee_id`),
  KEY `idx_employees_department` (`department`),
  KEY `idx_employees_status` (`status`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,NULL,'EMP001','John','Doe','john.doe@hotel.com','555-0101','Front Desk Agent','Front Office','2024-01-15',15.00,'USD',22.50,'USD',40,'hourly',NULL,NULL,NULL,NULL,NULL,NULL,'active','2025-10-13 16:38:29','2025-10-13 16:38:29'),(2,NULL,'EMP002','Jane','Smith','jane.smith@hotel.com','555-0102','Housekeeper','Housekeeping','2024-02-01',14.00,'USD',21.00,'USD',40,'hourly',NULL,NULL,NULL,NULL,NULL,NULL,'active','2025-10-13 16:38:29','2025-10-13 16:38:29'),(3,NULL,'EMP003','Mike','Johnson','mike.johnson@hotel.com','555-0103','Maintenance','Maintenance','2024-01-10',18.00,'USD',27.00,'USD',40,'hourly',NULL,NULL,NULL,NULL,NULL,NULL,'active','2025-10-13 16:38:29','2025-10-13 16:38:29'),(4,NULL,'EMP004','Sarah','Wilson','sarah.wilson@hotel.com','555-0104','Assistant Manager','Management','2023-12-01',25.00,'USD',37.50,'USD',40,'hourly',NULL,NULL,NULL,NULL,NULL,NULL,'active','2025-10-13 16:38:29','2025-10-13 16:38:29');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'PEN',
  `expense_category` varchar(100) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_method` varchar(50) DEFAULT 'cash',
  `vendor_name` varchar(100) DEFAULT NULL,
  `vendor_contact` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT '0',
  `recurring_frequency` varchar(20) DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `paid_by` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'paid',
  `approved_by` int DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `tax_deductible` decimal(5,2) DEFAULT '0.00',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `paid_by` (`paid_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'Office Supplies',30.00,'PEN','Operations','2025-10-13','2025-10-13 01:48:50','cash',NULL,NULL,NULL,0,NULL,NULL,NULL,'paid',NULL,NULL,0.00,NULL),(2,'Maintenance Repair',80.00,'PEN','Maintenance','2025-10-13','2025-10-13 01:48:50','cash',NULL,NULL,NULL,0,NULL,NULL,NULL,'paid',NULL,NULL,0.00,NULL),(3,'Utility Bills',120.00,'PEN','Utilities','2025-10-13','2025-10-13 01:48:50','cash',NULL,NULL,NULL,0,NULL,NULL,NULL,'paid',NULL,NULL,0.00,NULL);
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hotel_amenities`
--

DROP TABLE IF EXISTS `hotel_amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_amenities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `amenity_name` varchar(100) NOT NULL,
  `amenity_description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `amenity_icon` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hotel_amenities`
--

LOCK TABLES `hotel_amenities` WRITE;
/*!40000 ALTER TABLE `hotel_amenities` DISABLE KEYS */;
INSERT INTO `hotel_amenities` VALUES (1,'Free WiFi','Complimentary high-speed internet access',1,'📶','2025-10-13 01:34:40'),(2,'Swimming Pool','Indoor/outdoor swimming pool',0,'🏊','2025-10-13 01:34:40'),(3,'Fitness Center','Fully equipped gym and fitness facilities',0,'💪','2025-10-13 01:34:40'),(4,'Spa & Wellness','Relaxation and wellness treatments',0,'🧘','2025-10-13 01:34:40'),(5,'Restaurant','On-site dining restaurant',0,'🍴','2025-10-13 01:34:40'),(6,'Bar/Lounge','Cocktail bar and lounge area',0,'🍸','2025-10-13 01:34:40'),(7,'Parking','Free or paid parking facilities',1,'🚗','2025-10-13 01:34:40'),(8,'Pet Friendly','Pets welcome with special accommodations',0,'🐕','2025-10-13 01:34:40'),(9,'Airport Shuttle','Transportation to/from airport',0,'🚐','2025-10-13 01:34:40'),(10,'Meeting Rooms','Conference and meeting facilities',1,'👥','2025-10-13 01:34:40'),(11,'Laundry Service','Professional laundry and dry cleaning',0,'👔','2025-10-13 01:34:40'),(12,'Safe Deposit Box','Secure storage for valuables',0,'🔒','2025-10-13 01:34:40');
/*!40000 ALTER TABLE `hotel_amenities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hotel_info`
--

DROP TABLE IF EXISTS `hotel_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_name` varchar(200) NOT NULL,
  `hotel_description` text,
  `address_line1` varchar(200) DEFAULT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `check_in_time` time DEFAULT '15:00:00',
  `check_out_time` time DEFAULT '11:00:00',
  `total_rooms` int DEFAULT '0',
  `hotel_rating` decimal(2,1) DEFAULT '0.0',
  `hotel_logo` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hotel_info`
--

LOCK TABLES `hotel_info` WRITE;
/*!40000 ALTER TABLE `hotel_info` DISABLE KEYS */;
INSERT INTO `hotel_info` VALUES (1,'Samay Wasi Casa De Paz','hola','calle arequipa 286',NULL,'Pisac, Cusco, Peru','cusco','08106','Peru','+51938118436','gerente@samaywasihotel.com','https://www.samaywasipisac.com','14:00:00','11:00:00',11,3.0,NULL,'2025-10-13 19:51:47','2025-10-13 19:51:47');
/*!40000 ALTER TABLE `hotel_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hotel_services`
--

DROP TABLE IF EXISTS `hotel_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `service_description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `service_icon` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hotel_services`
--

LOCK TABLES `hotel_services` WRITE;
/*!40000 ALTER TABLE `hotel_services` DISABLE KEYS */;
INSERT INTO `hotel_services` VALUES (1,'24/7 Front Desk','Round-the-clock reception and guest services',1,'🏨','2025-10-13 01:34:40'),(2,'Room Service','In-room dining available',0,'🍽️','2025-10-13 01:34:40'),(3,'Housekeeping','Daily room cleaning and maintenance',1,'🧹','2025-10-13 01:34:40'),(4,'Concierge Service','Local recommendations and booking assistance',0,'🛎️','2025-10-13 01:34:40'),(5,'Wake-up Calls','Personalized wake-up call service',0,'⏰','2025-10-13 01:34:40'),(6,'Luggage Storage','Secure luggage storage for guests',1,'🧳','2025-10-13 01:34:40'),(7,'Express Check-in/out','Quick and efficient check-in and check-out',1,'⚡','2025-10-13 01:34:40'),(8,'Business Center','Computer and printing facilities',0,'💼','2025-10-13 01:34:40');
/*!40000 ALTER TABLE `hotel_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `income`
--

DROP TABLE IF EXISTS `income`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `income` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'PEN',
  `payment_method` varchar(50) DEFAULT 'cash',
  `payment_status` varchar(20) DEFAULT 'paid',
  `transaction_date` date NOT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `booking_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `income_type` varchar(50) DEFAULT 'room_booking',
  `created_by` int DEFAULT NULL,
  `notes` text,
  `receipt_number` varchar(50) DEFAULT NULL,
  `income_status` varchar(20) DEFAULT 'received',
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `income_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `income_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `income`
--

LOCK TABLES `income` WRITE;
/*!40000 ALTER TABLE `income` DISABLE KEYS */;
INSERT INTO `income` VALUES (1,'Room Booking Payment',150.00,'PEN','cash','paid','2025-10-13','John Doe','john@example.com',NULL,NULL,'2025-10-13 01:48:50','room_booking',NULL,NULL,NULL,'received'),(2,'Restaurant Service',45.00,'PEN','cash','paid','2025-10-13','Jane Smith','jane@example.com',NULL,NULL,'2025-10-13 01:48:50','room_booking',NULL,NULL,NULL,'received'),(3,'Laundry Service',25.00,'PEN','cash','paid','2025-10-13','Bob Johnson','bob@example.com',NULL,NULL,'2025-10-13 01:48:50','room_booking',NULL,NULL,NULL,'received'),(4,'Room 102 - Standard Double',32.00,'USD','','pending','2025-10-13','','test@test.com','555555555',NULL,'2025-10-14 06:49:10','room_booking',NULL,'Auto-generated from booking HTL-20251014-2995',NULL,'received'),(5,'Room 101 - Executive Suite',35.00,'USD','','pending','2025-10-15','','test@test.com','+51938118436',NULL,'2025-10-15 15:23:01','room_booking',NULL,'Auto-generated from booking HTL-20251015-9563',NULL,'received'),(6,'Room 101 - Executive Suite',35.00,'USD','','pending','2025-10-15','','gfigue.483406@guest.booking.com','+51 970 408 064 ',65,'2025-10-15 15:35:13','room_booking',NULL,'Auto-generated from booking HTL-20251015-9687',NULL,'received'),(7,'Room 104 - Twin Beds',28.00,'USD','','pending','2025-10-15','','none@none.com','+972 52 299 4367 ',66,'2025-10-15 15:37:14','room_booking',NULL,'Auto-generated from booking HTL-20251015-8144',NULL,'received'),(8,'Room 107 - Twin Beds',28.00,'USD','','pending','2025-10-15','','enooij.423294@guest.booking.com','+31 6 57992596',67,'2025-10-15 15:39:18','room_booking',NULL,'Auto-generated from booking HTL-20251015-6148',NULL,'received'),(9,'Room 109 - Standard Double',28.00,'USD','','pending','2025-10-15','','none@none.com','+972506622155',68,'2025-10-15 15:40:14','room_booking',NULL,'Auto-generated from booking HTL-20251015-3088',NULL,'received'),(10,'Room 108 - Deluxe Twin',28.00,'USD','','pending','2025-10-15','','none@none.com','+972512617275',69,'2025-10-15 15:42:24','room_booking',NULL,'Auto-generated from booking HTL-20251015-6460',NULL,'received'),(11,'Room 110 - Standard Double',30.00,'USD','','pending','2025-10-17','','gbrand.875041@guest.booking.com','+45 53 37 40 53',70,'2025-10-17 13:41:52','room_booking',NULL,'Auto-generated from booking HTL-20251017-1193',NULL,'received'),(12,'Room 102 - Standard Double',48.76,'USD','cash','paid','2025-10-17','','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ',71,'2025-10-17 15:45:49','room_booking',NULL,'Auto-generated from booking HTL-20251017-3136',NULL,'received'),(13,'Room 102 - Standard Double',48.76,'USD','cash','paid','2025-10-17','','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ',72,'2025-10-17 15:47:30','room_booking',NULL,'Auto-generated from booking HTL-20251017-3992',NULL,'received'),(14,'Room 102 - Standard Double',48.76,'USD','cash','paid','2025-10-17','','jchazo.536622@guest.booking.com','+54 9 11 3369 6383 ',73,'2025-10-17 15:47:40','room_booking',NULL,'Auto-generated from booking HTL-20251017-5320',NULL,'received'),(15,'Room 107 - Twin Beds',56.00,'USD','','pending','2025-10-17','','kstien.963830@guest.booking.com','+31 6 44838683',74,'2025-10-17 16:09:31','room_booking',NULL,'Auto-generated from booking HTL-20251017-3947',NULL,'received'),(16,'Room 101 - Executive Suite',70.00,'USD','','pending','2025-10-17','','lmirko.451867@guest.booking.com','+51 986 826 664 ',75,'2025-10-17 17:01:02','room_booking',NULL,'Auto-generated from booking HTL-20251017-1355',NULL,'received'),(17,'Room 111 - Deluxe Queen',35.00,'USD','','paid','2025-10-17','','kstienen@gmail.com','+31 6 44838683',NULL,'2025-10-17 17:27:41','room_booking',NULL,'Auto-generated from booking HTL-20251017-5393',NULL,'received'),(18,'Room 109 - Standard Double',28.00,'USD','','pending','2025-10-17','','test@test.com','+31 6 44838683',77,'2025-10-17 17:47:56','room_booking',NULL,'Auto-generated from booking HTL-20251017-0502',NULL,'received'),(19,'Room 103 - Deluxe Queen',40.00,'USD','','pending','2025-10-15','','samia.buhairi@gmail.com','+51900193160',78,'2025-10-17 19:06:10','room_booking',NULL,'Auto-generated from booking HTL-20251017-6820',NULL,'received'),(20,'Room 106 - Twin Beds',130.00,'USD','','pending','2025-10-13','','enooij.423294@guest.booking.com','+44 7462 183477',79,'2025-10-17 19:34:10','room_booking',NULL,'Auto-generated from booking HTL-20251017-8480',NULL,'received'),(21,'Room 108 - Deluxe Twin',28.00,'USD','','pending','2025-10-18','','sobolimor@gmail.com','+972502429889',80,'2025-10-17 20:35:09','room_booking',NULL,'Auto-generated from booking HTL-20251017-0334',NULL,'received');
/*!40000 ALTER TABLE `income` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `regular_hours` decimal(5,2) DEFAULT '0.00',
  `overtime_hours` decimal(5,2) DEFAULT '0.00',
  `regular_pay` decimal(10,2) DEFAULT '0.00',
  `overtime_pay` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `deductions` decimal(10,2) DEFAULT '0.00',
  `net_pay` decimal(10,2) DEFAULT '0.00',
  `currency` varchar(3) DEFAULT 'USD',
  `pay_date` date DEFAULT NULL,
  `status` enum('draft','processed','paid') DEFAULT 'draft',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_employee` (`employee_id`),
  KEY `idx_payroll_period` (`pay_period_start`,`pay_period_end`),
  CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_photos`
--

DROP TABLE IF EXISTS `room_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_id` int NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `photo_name` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_photos_room_id` (`room_id`),
  KEY `idx_room_photos_primary` (`room_id`,`is_primary`),
  CONSTRAINT `room_photos_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_photos`
--

LOCK TABLES `room_photos` WRITE;
/*!40000 ALTER TABLE `room_photos` DISABLE KEYS */;
INSERT INTO `room_photos` VALUES (1,1,'uploads/rooms/68d84a10b9fd6_executive room.jpg','68d84a10b9fd6_executive room.jpg',1,'2025-09-27 15:33:20','2025-10-13 22:42:24'),(2,2,'uploads/rooms/68d9b6cdbb5cc_574522699.jpg','68d9b6cdbb5cc_574522699.jpg',1,'2025-09-28 17:29:33','2025-10-13 22:42:24'),(3,2,'uploads/rooms/68df187d66212_2025-02-24.jpg','68df187d66212_2025-02-24.jpg',0,'2025-10-02 19:27:41','2025-10-13 22:42:24'),(4,2,'uploads/rooms/68df188522ea4_2025-02-24.jpg','68df188522ea4_2025-02-24.jpg',0,'2025-10-02 19:27:49','2025-10-13 22:42:24'),(5,3,'uploads/rooms/68d9b8889203a_0224812000ejlx6yi846A_Z_1280_720_R5 1.jpg','68d9b8889203a_0224812000ejlx6yi846A_Z_1280_720_R5 1.jpg',1,'2025-09-28 17:36:56','2025-10-13 22:42:24'),(6,4,'uploads/rooms/68d9b98681eb8_2025-02-24.jpg','68d9b98681eb8_2025-02-24.jpg',1,'2025-09-28 17:41:10','2025-10-13 22:42:24'),(7,5,'uploads/rooms/68dabfc79cf24_executive room (1).jpg','68dabfc79cf24_executive room (1).jpg',0,'2025-09-29 12:20:07','2025-10-13 22:42:24'),(8,6,'uploads/rooms/68dabffd710d1_executive room (1).jpg','68dabffd710d1_executive room (1).jpg',0,'2025-09-29 12:21:01','2025-10-13 22:42:24'),(9,7,'uploads/rooms/68dac06c484b5_Standar Double Bed .jpg','68dac06c484b5_Standar Double Bed .jpg',0,'2025-09-29 12:22:52','2025-10-13 22:42:24'),(10,8,'uploads/rooms/68de02796c0fc_Standar Double Bed .jpg','68de02796c0fc_Standar Double Bed .jpg',0,'2025-10-01 23:41:29','2025-10-13 22:42:24'),(11,8,'uploads/rooms/68de032f00a05_Standar Double Bed .jpg','68de032f00a05_Standar Double Bed .jpg',0,'2025-10-01 23:44:31','2025-10-13 22:42:24');
/*!40000 ALTER TABLE `room_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `capacity` int NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `room_status` enum('clean','dirty','maintenance','out_of_order') DEFAULT 'clean',
  `amenities` text,
  `extra_bed_available` tinyint(1) DEFAULT '0',
  `extra_bed_price` decimal(10,2) DEFAULT '0.00',
  `is_available` tinyint(1) DEFAULT '1',
  `max_occupancy` int DEFAULT '1',
  `price` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'101','Executive Suite',2,35.00,'Comfortable single room with city view','2025-10-13 01:34:31','clean','Free fast Wifi, Desk, optional extra bed, view the Mountain View to the garden, private bathroom and shower fast',1,15.00,1,2,35.00),(2,'102','Standard Double',1,32.00,'Spacious double room with queen bed','2025-10-13 01:34:31','dirty','Free WiFi, Air Conditioning, TV, Private Bathroom',0,0.00,1,1,32.00),(3,'103','Deluxe Queen',4,40.00,'Luxury suite with separate living area','2025-10-13 01:34:31','clean',NULL,0,0.00,1,2,40.00),(4,'104','Twin Beds',6,28.00,'Perfect for families with connecting rooms','2025-10-13 01:34:31','clean',NULL,0,0.00,1,2,28.00),(5,'105','Deluxe Twin',8,28.00,'Ultimate luxury with panoramic views','2025-10-13 01:34:31','clean',NULL,0,0.00,1,2,28.00),(6,'106','Twin Beds',2,26.00,'Comfortable twin bed room','2025-10-13 19:24:52','clean',NULL,0,0.00,1,2,26.00),(7,'107','Twin Beds',2,28.00,'Comfortable twin bed room','2025-10-13 19:24:52','clean',NULL,0,0.00,1,2,28.00),(8,'108','Deluxe Twin',2,28.00,'Deluxe room with queen bed','2025-10-13 19:24:52','clean',NULL,0,0.00,1,2,28.00),(9,'109','Standard Double',2,28.00,'Comfortable twin bed room','2025-10-13 19:24:52','clean',NULL,0,0.00,1,2,28.00),(10,'110','Standard Double',2,30.00,'Deluxe twin bed room','2025-10-13 19:24:52','clean',NULL,0,0.00,1,2,30.00),(11,'111','Deluxe Queen',2,35.00,'Comfortable twin bed room','2025-10-13 19:24:52','clean',NULL,0,0.00,1,2,35.00);
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_clock`
--

DROP TABLE IF EXISTS `time_clock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_clock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `break_start` datetime DEFAULT NULL,
  `break_end` datetime DEFAULT NULL,
  `total_break_minutes` int DEFAULT '0',
  `total_hours` decimal(5,2) DEFAULT '0.00',
  `overtime_hours` decimal(5,2) DEFAULT '0.00',
  `notes` text,
  `status` enum('clocked_in','on_break','clocked_out') DEFAULT 'clocked_in',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_manual_entry` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_time_clock_employee` (`employee_id`),
  KEY `idx_time_clock_date` (`clock_in`),
  CONSTRAINT `time_clock_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_clock`
--

LOCK TABLES `time_clock` WRITE;
/*!40000 ALTER TABLE `time_clock` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_clock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_role` enum('guest','manager','admin') DEFAULT 'guest',
  `role` varchar(50) DEFAULT 'guest',
  `phone` varchar(20) DEFAULT NULL,
  `terms_accepted` tinyint(1) DEFAULT '0',
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `terms_version` varchar(10) DEFAULT '1.0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Hotel','Manager','manager@hotel.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2025-10-13 01:34:40','2025-10-13 15:50:33','manager','manager',NULL,0,NULL,'1.0'),(3,'samara,','Tamir','none@none.com','$2y$10$tO2ZdIxtGelzILX8xFvqsujj5MA9TluAvIL4h1VuIGYFI3j9EWViS','2025-09-26 16:30:45','2025-10-17 20:57:59','guest','guest','+972506622155',0,NULL,'1.0'),(8,'Clil','katz , Atar sivan','clil.katz@gmail.com','$2y$10$2geZhyCm8xNQ8fl45igXPO7CowVgYSXQ5Yq3ezEbBdDYiJMU9f9E.','2025-09-29 09:05:54','2025-09-29 09:05:54','guest','guest',NULL,0,NULL,'1.0'),(9,'lisha','Levi','lihi26013@gmail.com','$2y$10$LuEh3QL6L/bCTY3T8/iiFuLs2j1N9VpwOoxslrRXzkm1MHhUFJvHi','2025-09-29 12:42:13','2025-09-29 12:42:13','guest','guest',NULL,0,NULL,'1.0'),(10,'ella','shoval','ellagreenfeld33@gmail.com','$2y$10$/JxrPz6faK3ZxUATelMMweneMxgvXo7UJlwyVEOWcjEuAFpGFd4uq','2025-09-29 15:04:00','2025-09-30 14:35:08','guest','guest',NULL,0,NULL,'1.0'),(11,'Roberto','Eraso','robertoeraso79@gmail.com','$2y$10$KdI6Ugu2xhfpdrJLoNxbJOYFtUpysQZdcG5FyHZUxYBUnz7rdlTiq','2025-09-29 16:48:07','2025-09-29 16:48:07','guest','guest',NULL,0,NULL,'1.0'),(12,'Eden','Hili','hilieden86@gmail.com','$2y$10$dmUlDbxZHEp3VyLRFLKzHu0SR23MkGVGFGT8lG2sHyAPpsTfL7vuS','2025-09-29 18:14:44','2025-09-29 18:14:44','guest','guest',NULL,0,NULL,'1.0'),(13,'Inbar','Shelhava','InbarSHA2002@gmail.com','$2y$10$7FSQ7KPyJYPDW8aMwdq5yuHF1y1cJpDKPHOfvL4e1CsIsOW/NobLS','2025-09-30 08:21:46','2025-09-30 08:21:46','guest','guest',NULL,0,NULL,'1.0'),(14,'Tamara','Recarte','rocketier2005@gmail.com','$2y$10$jdDj9v4UIaagWKWwf8ON6.jcZ5U.L3Xpg/XUaXqzJN9hRRGio5ZjK','2025-09-30 09:55:02','2025-09-30 09:55:02','guest','guest',NULL,0,NULL,'1.0'),(15,'Shir','Cohin','shirrrr33@gmail.com','$2y$10$QB8JwTZekNDTBPZHXsPfe.fhfmZDBqn3NO4jknKGkewrEfbA/.3gG','2025-09-30 10:15:40','2025-09-30 10:15:40','guest','guest',NULL,0,NULL,'1.0'),(16,'Martin','Groppa','mgropp.284431@guest.booking.com','$2y$10$NORqmUKBr0hoSmNsyBdQUOW4J.1JVbXu4HI8qEAjKwMqNFMbRk8eq','2025-09-30 11:20:03','2025-09-30 11:20:03','guest','guest',NULL,0,NULL,'1.0'),(17,'Gabay','Moran','gmoran.788257@guest.booking.com','$2y$10$7GLMrddIhCcqDSO6XBYh1.vDLJf/nC9WO5W9m4EQzsOrtaOW/xKt2','2025-09-30 11:38:27','2025-09-30 11:38:27','guest','guest',NULL,0,NULL,'1.0'),(18,'Roy','Armony','rarmon.407383@guest.booking.com','$2y$10$tGjib5YqXVEXryyP8VCdS.N49Mr.VR.vL.CcSVPmJQArcZ.yfcDoq','2025-09-30 13:29:16','2025-09-30 13:29:16','guest','guest',NULL,0,NULL,'1.0'),(19,'Matan','El','matanelch@gmail.com','$2y$10$uL7wM/H.KRLR9fqtfFAiE.OK5fGD4f4smwE8KrC90opQUzIyKdW7O','2025-09-30 13:29:40','2025-09-30 13:29:40','guest','guest',NULL,0,NULL,'1.0'),(20,'Tiltan','Marsh','no@no.com','$2y$10$ULN6gBViwKlvI5gRuK5nmuak8tJAlqb7zbSm4VEf1bJtba4xJJou6','2025-09-30 15:00:41','2025-09-30 15:00:41','guest','guest',NULL,0,NULL,'1.0'),(21,'Dominik','Morgott','dmorgo.674433@guest.booking.com','$2y$10$RCcylnvK8lOcnFW6t.F3QurkISmdOqIOv8jghMVDwG3q..pQOszKK','2025-10-01 08:41:36','2025-10-01 08:41:36','guest','guest',NULL,0,NULL,'1.0'),(22,'Shadaj','Rotem','srvtm.543374@guest.booking.com','$2y$10$iMvKX6asYU0W2/zB9iAa.u9yoKMUm.ya.jF6vWZW2RS/JzPxTtvIS','2025-10-01 10:10:31','2025-10-01 10:10:31','guest','guest',NULL,0,NULL,'1.0'),(23,'Larisa','Ustalov','lustal.807553@guest.booking.com','$2y$10$tPOMYcoQS/nxgJGMjDBvmelqvLbKDZoVrlPav0Vut8bLfLKhyfJDO','2025-10-01 12:07:44','2025-10-01 12:07:44','guest','guest',NULL,0,NULL,'1.0'),(24,'airbnb','','none@no.com','$2y$10$K6n70PnHT7RS6qg8oolmm.N8VJcVhyAkuvjVdpXLjXXlg39igsTDa','2025-10-01 14:51:40','2025-10-01 14:51:40','guest','guest',NULL,0,NULL,'1.0'),(25,'MAASA','YUSAKU','myusak.812445@guest.booking.com','$2y$10$r6.aN1t4Fpl8bC6A6O7nvOwMvWM5O2CzhzVla0dskSOXRu0LzSDA2','2025-10-01 15:56:11','2025-10-01 15:56:11','guest','guest',NULL,0,NULL,'1.0'),(26,'Alex','Wingarten','alexw1900@gmail.com','$2y$10$43CamNYmhSuBukKGqhrWHe4EjCLLK5tBJnTRLkdmqZ7FSJJBZFa5m','2025-10-02 09:05:40','2025-10-02 09:05:40','guest','guest',NULL,0,NULL,'1.0'),(27,'Ina','Kathrin','ina.nenning@gmail.com','$2y$10$k1Nr6FKhG30SLoyrsXZhQO6PZ9JIBJhB5Wd1i7pHGRXaJO.wCYUS2','2025-10-02 09:27:48','2025-10-02 09:27:48','guest','guest',NULL,0,NULL,'1.0'),(28,'Cassie','Luzenski','cluzen.768584@guest.booking.com','$2y$10$F4uKvTmQ1Asdr4DhQgq4KutVbnWgHjw2yfS6/2ddpMh4PTxGMJk4S','2025-10-02 09:40:43','2025-10-02 09:40:43','guest','guest',NULL,0,NULL,'1.0'),(29,'Alejandro','Costanzo','acosta.198532@guest.booking.com','$2y$10$l5/D0HX7ODBXZ9VWwB8apeK0mu8vbP8x6h2EISzByYBJl0GfM7zMS','2025-10-02 10:27:15','2025-10-02 10:27:15','guest','guest',NULL,0,NULL,'1.0'),(30,'Greenfeld','Ella','glh.907935@guest.booking.com','$2y$10$vHhj1EkGFgknXvMHO2Tio.XRsgXBDnY4e5asgDT.0qdbPcxJNZM7K','2025-10-02 11:16:56','2025-10-02 11:16:56','guest','guest',NULL,0,NULL,'1.0'),(31,'Nadine','Salha','nsalha.516763@guest.booking.com','$2y$10$vipmoWL2BMljfMDjD6hs8.E7fczUrXGqLfeDo7uLqJU4IHrwD2Cka','2025-10-02 12:05:08','2025-10-02 12:05:08','guest','guest',NULL,0,NULL,'1.0'),(32,'Adriana','Pereiro Felipez','afelip.767956@guest.booking.com','$2y$10$2Kin6GCqGY7PUQM2MDch7ekgMcUgat3ew2OaqQ9U6G8ji6a3vDXUi','2025-10-02 12:44:32','2025-10-02 12:44:32','guest','guest',NULL,0,NULL,'1.0'),(33,'Chad','Mummert','markertersnerviana@gmail.com','$2y$10$wNng.t6tuJ27SLJy8TGh..CfzrVGa6ig1wobc4.zOX7GSJnisIP8W','2025-10-03 08:26:50','2025-10-03 08:26:50','guest','guest',NULL,0,NULL,'1.0'),(34,'Yasmin','Dital','ditalyasmin@gmail.com','$2y$10$QXjAeNZPdCuyGZRhAmQjO.k6nc1WrNf.SUgY9TcZpLzzO8rxJtj8e','2025-10-03 11:31:17','2025-10-03 11:31:17','guest','guest',NULL,0,NULL,'1.0'),(35,'JP','GIll','none@gmail.com','$2y$10$PUSmR4B7ij.OZ.Ux6Uy7h.qfSTFFFVoJTXJg5P3sGZ2j.6wrXvzEK','2025-10-03 13:47:36','2025-10-03 13:47:36','guest','guest',NULL,0,NULL,'1.0'),(36,'Michel','Doerk','michel-doerk@gmx.de','$2y$10$aivpDyB/lmgkt8GhUKgOcOV1IvxVsXgeOoMK.UcqlTvsK8mQzCzQ6','2025-10-03 14:37:37','2025-10-03 14:37:37','guest','guest',NULL,0,NULL,'1.0'),(37,'Ruzena','Duchkova','ruzena.duchkova@gmail.com','$2y$10$aivpDyB/lmgkt8GhUKgOcOV1IvxVsXgeOoMK.UcqlTvsK8mQzCzQ6','2025-10-03 14:37:37','2025-10-03 14:37:37','guest','guest',NULL,0,NULL,'1.0'),(38,'Julia','','overig2019@protonmail.com','$2y$10$aivpDyB/lmgkt8GhUKgOcOV1IvxVsXgeOoMK.UcqlTvsK8mQzCzQ6','2025-10-03 14:37:37','2025-10-03 14:37:37','guest','guest',NULL,0,NULL,'1.0'),(39,'name','','n2@n2.com','$2y$10$aivpDyB/lmgkt8GhUKgOcOV1IvxVsXgeOoMK.UcqlTvsK8mQzCzQ6','2025-10-03 14:37:37','2025-10-03 14:37:37','guest','guest',NULL,0,NULL,'1.0'),(40,'Matt','','test@test.com','$2y$10$8Zd1TOP5VQMEXbRjp/i2ruylyU91ZZCZVPOw9W5EbSSeKOvTo5/Mu','2025-10-14 06:49:10','2025-10-17 17:49:01','guest','guest','+31 6 44838683',1,'2025-10-14 06:49:10','1.0'),(41,'Gilberto','','gfigue.483406@guest.booking.com','$2y$10$F21MoxV8WXYs2mnmRjIOduxG/HfopcC2gBmaLxSJ9xt9/bOwbUg0y','2025-10-15 15:35:13','2025-10-17 15:49:31','guest','guest','+51 970 408 064 ',1,'2025-10-15 15:35:13','1.0'),(42,'nigel','','enooij.423294@guest.booking.com','$2y$10$aBzC8jU0cch1Z/U4lY6N4.okOhfYPqXocA096TWJjDCsN69fr9m4i','2025-10-15 15:39:18','2025-10-17 19:34:39','guest','guest','+44 7462 183477',1,'2025-10-15 15:39:18','1.0'),(43,'Gustav','','gbrand.875041@guest.booking.com','$2y$10$6FbL1Y86ZKs0mJYfjnuISu/A2IHFCYLrkmUmMmVskt07sMRkCDAHK','2025-10-17 13:41:52','2025-10-17 15:36:29','guest','guest','+45 53 37 40 53',1,'2025-10-17 13:41:52','1.0'),(44,'','','jchazo.536622@guest.booking.com','$2y$10$UWJoncixOkeOYh7u3bx8I.RcsxCZEq39hjecpGOoy7MAKVuhx4bky','2025-10-17 15:45:49','2025-10-17 15:45:49','guest','guest','+54 9 11 3369 6383 ',1,'2025-10-17 15:45:49','1.0'),(45,'Katinka','','kstien.963830@guest.booking.com','$2y$10$F/HEfOS79maxPp/85IZXtek.UkQ1z96VKYVXf33idB4mwD0av7heS','2025-10-17 16:09:31','2025-10-17 16:55:17','guest','guest','+31 6 44838683',1,'2025-10-17 16:09:31','1.0'),(46,'Llamoja','','lmirko.451867@guest.booking.com','$2y$10$Hj938CLNzIWDz36kExEfF.UvaoDAzhz/C0pg28tP56uIqBEBIhMXq','2025-10-17 17:01:02','2025-10-17 17:02:13','guest','guest','+51 986 826 664 ',1,'2025-10-17 17:01:02','1.0'),(47,'Katinka','','kstienen@gmail.com','$2y$10$xAT6fUZ7X/4Gwk0BTHee6ugInZHLNwKxuRx4honhgcbiPMeOKV8ZO','2025-10-17 17:27:41','2025-10-17 17:28:45','guest','guest','+31 6 44838683',1,'2025-10-17 17:27:41','1.0'),(48,'SAMIya','','samia.buhairi@gmail.com','$2y$10$UGz2.rGMtrVQ8ruyqHoALOdejoOmZebZISX7YPuv/FG6TKmTAStRi','2025-10-17 19:06:10','2025-10-17 19:15:07','guest','guest','+51900193160',1,'2025-10-17 19:06:10','1.0'),(49,'limor','','sobolimor@gmail.com','$2y$10$WaM/H7VrHleYcOscbcs89uUqow6sqfWey8iEtyPWfPVuHxJ2XuCgy','2025-10-17 20:35:09','2025-10-18 14:54:52','guest','guest','+972502429889',1,'2025-10-17 20:35:09','1.0');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_conversations`
--

DROP TABLE IF EXISTS `whatsapp_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `bot_response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_conversations`
--

LOCK TABLES `whatsapp_conversations` WRITE;
/*!40000 ALTER TABLE `whatsapp_conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_conversations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-19 17:39:36
