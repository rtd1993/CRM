-- MySQL dump 10.13  Distrib 8.0.42, for Linux (x86_64)
--
-- Host: localhost    Database: crm
-- ------------------------------------------------------
-- Server version	8.0.42-0ubuntu0.24.04.1

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
-- Table structure for table `chat_messaggi`
--

DROP TABLE IF EXISTS `chat_messaggi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messaggi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utente_id` int NOT NULL,
  `messaggio` text NOT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utente_id` (`utente_id`),
  CONSTRAINT `chat_messaggi_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messaggi`
--

LOCK TABLES `chat_messaggi` WRITE;
/*!40000 ALTER TABLE `chat_messaggi` DISABLE KEYS */;
INSERT INTO `chat_messaggi` VALUES (1,1,'12','2025-06-28 14:14:53'),(2,1,'ciAO','2025-06-28 14:15:00'),(3,1,'12','2025-06-28 14:49:14'),(4,1,'0','2025-06-28 14:58:33');
/*!40000 ALTER TABLE `chat_messaggi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_pratiche`
--

DROP TABLE IF EXISTS `chat_pratiche`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_pratiche` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utente_id` int NOT NULL,
  `pratica_id` int NOT NULL,
  `messaggio` text NOT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utente_id` (`utente_id`),
  CONSTRAINT `chat_pratiche_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_pratiche`
--

LOCK TABLES `chat_pratiche` WRITE;
/*!40000 ALTER TABLE `chat_pratiche` DISABLE KEYS */;
INSERT INTO `chat_pratiche` VALUES (1,1,1,'123','2025-06-28 14:17:49'),(2,1,1,'12','2025-06-28 14:20:58'),(3,1,1,'12','2025-06-28 14:22:36'),(4,1,1,'23','2025-06-28 14:24:39'),(5,1,1,'23','2025-06-28 14:24:49');
/*!40000 ALTER TABLE `chat_pratiche` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clienti`
--

DROP TABLE IF EXISTS `clienti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clienti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Inizio rapporto` date DEFAULT NULL,
  `Fine rapporto` date DEFAULT NULL,
  `Inserito gestionale` tinyint(1) DEFAULT NULL,
  `Codice ditta` varchar(50) DEFAULT NULL,
  `Colore` varchar(20) DEFAULT NULL,
  `Cognome/Ragione sociale` varchar(255) DEFAULT NULL,
  `Nome` varchar(255) DEFAULT NULL,
  `Codice fiscale` varchar(50) DEFAULT NULL,
  `Partita IVA` varchar(50) DEFAULT NULL,
  `Qualifica` varchar(100) DEFAULT NULL,
  `Soci Amministratori` varchar(255) DEFAULT NULL,
  `Sede Legale` text,
  `Sede Operativa` text,
  `Data di nascita/costituzione` date DEFAULT NULL,
  `Luogo di nascita` varchar(100) DEFAULT NULL,
  `Cittadinanza` varchar(100) DEFAULT NULL,
  `Residenza` text,
  `Numero carta d’identità` varchar(50) DEFAULT NULL,
  `Rilasciata dal Comune di` varchar(100) DEFAULT NULL,
  `Data di rilascio` date DEFAULT NULL,
  `Valida per l’espatrio` tinyint(1) DEFAULT NULL,
  `Stato civile` varchar(50) DEFAULT NULL,
  `Data di scadenza` date DEFAULT NULL,
  `Descrizione attivita` text,
  `Codice ATECO` varchar(50) DEFAULT NULL,
  `Camera di commercio` varchar(100) DEFAULT NULL,
  `Dipendenti` int DEFAULT NULL,
  `Codice inps` varchar(50) DEFAULT NULL,
  `Titolare` varchar(100) DEFAULT NULL,
  `Codice inps_2` varchar(50) DEFAULT NULL,
  `Codice inail` varchar(50) DEFAULT NULL,
  `PAT` varchar(50) DEFAULT NULL,
  `Cod.PIN Inail` varchar(50) DEFAULT NULL,
  `Cassa Edile` varchar(100) DEFAULT NULL,
  `Numero Cassa Professionisti` varchar(50) DEFAULT NULL,
  `Contabilita` varchar(100) DEFAULT NULL,
  `Liquidazione IVA` varchar(50) DEFAULT NULL,
  `Telefono` varchar(50) DEFAULT NULL,
  `Mail` varchar(100) DEFAULT NULL,
  `PEC` varchar(100) DEFAULT NULL,
  `User Aruba` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `Scadenza PEC` date DEFAULT NULL,
  `Rinnovo Pec` date DEFAULT NULL,
  `SDI` varchar(50) DEFAULT NULL,
  `Link cartella` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clienti`
--

LOCK TABLES `clienti` WRITE;
/*!40000 ALTER TABLE `clienti` DISABLE KEYS */;
INSERT INTO `clienti` VALUES (1,NULL,NULL,NULL,NULL,NULL,'Toti','Roberto','TTORRT','03139390805',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3484212857','rtd1993@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,'');
/*!40000 ALTER TABLE `clienti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task`
--

DROP TABLE IF EXISTS `task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descrizione` text NOT NULL,
  `scadenza` date NOT NULL,
  `ricorrenza` int DEFAULT NULL,
  `completato_da` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task`
--

LOCK TABLES `task` WRITE;
/*!40000 ALTER TABLE `task` DISABLE KEYS */;
INSERT INTO `task` VALUES (2,'test','2025-06-27',NULL,NULL),(3,'test2','2025-07-01',NULL,NULL),(4,'test ricorr','2025-07-01',30,NULL);
/*!40000 ALTER TABLE `task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utenti`
--

DROP TABLE IF EXISTS `utenti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utenti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ruolo` enum('guest','employee','admin','developer') DEFAULT 'guest',
  `telegram_chat_id` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utenti`
--

LOCK TABLES `utenti` WRITE;
/*!40000 ALTER TABLE `utenti` DISABLE KEYS */;
INSERT INTO `utenti` VALUES (1,'Roberto','roberto@crm.local','$2y$10$B2hlSurvZczEaojzNsW8NunbvmqyWhbvh0GOQw8iGcT2Me6M7HF52','developer',''),(2,'Administrator','admin@crm.local','$2y$10$nQg1IHvYpVtnUSC6ULVNgup/upblTJjIFy5paOxYY/kcelL1xsJEy','admin','123456789');
/*!40000 ALTER TABLE `utenti` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-30  9:27:00
