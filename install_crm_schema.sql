-- MySQL dump 10.13  Distrib 8.4.6, for Linux (aarch64)
--
-- Host: localhost    Database: crm
-- ------------------------------------------------------
-- Server version	8.4.6-0ubuntu0.25.04.1

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
-- Table structure for table `calendar_events_meta`
--

DROP TABLE IF EXISTS `calendar_events_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_events_meta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `google_event_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_to_user_id` int DEFAULT NULL,
  `created_by_user_id` int DEFAULT NULL,
  `event_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007BFF',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `google_event_id` (`google_event_id`),
  KEY `assigned_to_user_id` (`assigned_to_user_id`),
  KEY `created_by_user_id` (`created_by_user_id`),
  CONSTRAINT `calendar_events_meta_ibfk_1` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calendar_events_meta_ibfk_2` FOREIGN KEY (`created_by_user_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_conversations`
--

DROP TABLE IF EXISTS `chat_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('globale','pratica','privata') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome chat (per globali/gruppi)',
  `client_id` int DEFAULT NULL COMMENT 'Per chat pratiche (FK clienti)',
  `user1_id` int DEFAULT NULL COMMENT 'Per chat private (primo utente)',
  `user2_id` int DEFAULT NULL COMMENT 'Per chat private (secondo utente)',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_message_at` timestamp NULL DEFAULT NULL COMMENT 'Per ordinamento chat attive',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `client_id` (`client_id`),
  KEY `user1_id` (`user1_id`),
  KEY `user2_id` (`user2_id`),
  KEY `idx_type_client` (`type`,`client_id`),
  KEY `idx_private_chat` (`type`,`user1_id`,`user2_id`),
  KEY `idx_last_message` (`last_message_at` DESC),
  KEY `idx_active` (`is_active`,`last_message_at` DESC),
  CONSTRAINT `chat_conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_conversations_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clienti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_conversations_ibfk_3` FOREIGN KEY (`user1_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_conversations_ibfk_4` FOREIGN KEY (`user2_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('text','system') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_conversation_time` (`conversation_id`,`created_at`),
  KEY `idx_user_time` (`user_id`,`created_at`),
  KEY `idx_not_deleted` (`is_deleted`,`created_at`),
  FULLTEXT KEY `idx_message_search` (`message`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_participants`
--

DROP TABLE IF EXISTS `chat_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`conversation_id`,`user_id`),
  KEY `idx_user_active` (`user_id`,`is_active`),
  KEY `idx_conversation_active` (`conversation_id`,`is_active`),
  CONSTRAINT `chat_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_read_status`
--

DROP TABLE IF EXISTS `chat_read_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_read_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `last_read_message_id` int DEFAULT NULL,
  `last_read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `unread_count` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_conversation` (`user_id`,`conversation_id`),
  KEY `last_read_message_id` (`last_read_message_id`),
  KEY `idx_user_unread` (`user_id`,`unread_count`),
  KEY `idx_conversation_unread` (`conversation_id`,`unread_count`),
  CONSTRAINT `chat_read_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_read_status_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_read_status_ibfk_3` FOREIGN KEY (`last_read_message_id`) REFERENCES `chat_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clienti`
--

DROP TABLE IF EXISTS `clienti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clienti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Cognome_Ragione_sociale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Codice_fiscale` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Codice_ditta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Mail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PEC` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Indirizzo` text COLLATE utf8mb4_unicode_ci,
  `Data_di_scadenza` date DEFAULT NULL,
  `Scadenza_PEC` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Inizio_rapporto` date DEFAULT NULL,
  `Fine_rapporto` date DEFAULT NULL,
  `Inserito_gestionale` tinyint(1) DEFAULT '0',
  `Colore` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Partita_IVA` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Qualifica` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Soci_Amministratori` text COLLATE utf8mb4_unicode_ci,
  `Sede_Legale` text COLLATE utf8mb4_unicode_ci,
  `Sede_Operativa` text COLLATE utf8mb4_unicode_ci,
  `Data_di_nascita_costituzione` date DEFAULT NULL,
  `Luogo_di_nascita` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Cittadinanza` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Residenza` text COLLATE utf8mb4_unicode_ci,
  `Numero_carta_d_identità` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Rilasciata_dal_Comune_di` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Data_di_rilascio` date DEFAULT NULL,
  `Valida_per_espatrio` tinyint(1) DEFAULT '0',
  `Stato_civile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Descrizione_attivita` text COLLATE utf8mb4_unicode_ci,
  `Codice_ATECO` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Camera_di_commercio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Dipendenti` int DEFAULT NULL,
  `Codice_inps` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Titolare` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Codice_inps_2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Codice_inail` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PAT` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Cod_PIN_Inail` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Cassa_Edile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Numero_Cassa_Professionisti` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Contabilita` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Liquidazione_IVA` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `User_Aruba` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Rinnovo_Pec` date DEFAULT NULL,
  `SDI` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Link_cartella` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `completo` tinyint(1) DEFAULT '0' COMMENT 'Indica se il cliente ha tutti i dati necessari (0=incompleto, 1=completo)',
  PRIMARY KEY (`id`),
  KEY `idx_cognome` (`Cognome_Ragione_sociale`),
  KEY `idx_codice_fiscale` (`Codice_fiscale`),
  KEY `idx_email` (`Mail`)
) ENGINE=InnoDB AUTO_INCREMENT=186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conto_termico`
--

DROP TABLE IF EXISTS `conto_termico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conto_termico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `anno` year DEFAULT NULL,
  `numero_pratica` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_presentazione` date DEFAULT NULL,
  `tipo_intervento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esito` enum('positivo','negativo','in_attesa') COLLATE utf8mb4_unicode_ci DEFAULT 'in_attesa',
  `prestazione` decimal(10,2) DEFAULT NULL,
  `incassato` decimal(10,2) DEFAULT NULL,
  `user` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modello_stufa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_termine` date DEFAULT NULL,
  `mese` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `importo_ammissibile` decimal(10,2) DEFAULT NULL,
  `contributo` decimal(10,2) DEFAULT NULL,
  `stato` enum('bozza','presentata','istruttoria','accettata','respinta','liquidata') COLLATE utf8mb4_unicode_ci DEFAULT 'bozza',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_numero_pratica` (`numero_pratica`),
  KEY `idx_stato` (`stato`),
  KEY `idx_data_presentazione` (`data_presentazione`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation_participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `role` enum('admin','member') COLLATE utf8mb4_unicode_ci DEFAULT 'member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`conversation_id`,`user_id`),
  KEY `idx_conversation` (`conversation_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=398 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('global','private','pratica','cliente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `created_by` int NOT NULL,
  `client_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2824 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_cronologia`
--

DROP TABLE IF EXISTS `email_cronologia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_cronologia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `destinatario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oggetto` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corpo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_invio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stato` enum('inviata','fallita','in_coda') COLLATE utf8mb4_unicode_ci DEFAULT 'in_coda',
  `user_id` int DEFAULT NULL,
  `cliente_id` int DEFAULT NULL,
  `messaggio_errore` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_destinatario` (`destinatario`),
  KEY `idx_data_invio` (`data_invio`),
  KEY `idx_stato` (`stato`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oggetto` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corpo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_creazione` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_modifica` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enea`
--

DROP TABLE IF EXISTS `enea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enea` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `codice_enea` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anno_fiscale` year DEFAULT NULL,
  `tipo_detrazione` enum('50','65','110','bonus_facciate') COLLATE utf8mb4_unicode_ci DEFAULT '50',
  `importo_spesa` decimal(10,2) DEFAULT NULL,
  `importo_detrazione` decimal(10,2) DEFAULT NULL,
  `stato` enum('bozza','trasmessa','accettata','respinta') COLLATE utf8mb4_unicode_ci DEFAULT 'bozza',
  `data_trasmissione` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `descrizione` text COLLATE utf8mb4_unicode_ci,
  `prima_tel` date DEFAULT NULL,
  `richiesta_doc` date DEFAULT NULL,
  `ns_prev_n_del` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ns_ord_n_del` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ns_fatt_n_del` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bonifico_ns` date DEFAULT NULL,
  `copia_fatt_fornitore` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `schede_tecniche` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `visura_catastale` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `firma_notorio` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `firma_delega_ag_entr` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `firma_delega_enea` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `consenso` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `ev_atto_notorio` enum('PENDING','OK','NO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_codice_enea` (`codice_enea`),
  KEY `idx_anno_fiscale` (`anno_fiscale`),
  KEY `idx_stato` (`stato`),
  KEY `idx_tipo_detrazione` (`tipo_detrazione`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message_attachments`
--

DROP TABLE IF EXISTS `message_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_attachments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message_id` int NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_message` (`message_id`),
  CONSTRAINT `message_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('text','file','image','system') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) DEFAULT '0',
  `reply_to` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reply_to` (`reply_to`),
  KEY `idx_conversation_created` (`conversation_id`,`created_at`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  FULLTEXT KEY `ft_message` (`message`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`reply_to`) REFERENCES `messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `procedure_crm`
--

DROP TABLE IF EXISTS `procedure_crm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `procedure_crm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `denominazione` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valida_dal` date NOT NULL,
  `procedura` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `allegato` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_creazione` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_modifica` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_denominazione` (`denominazione`),
  KEY `idx_valida_dal` (`valida_dal`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `richieste`
--

DROP TABLE IF EXISTS `richieste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `richieste` (
  `id` int NOT NULL AUTO_INCREMENT,
  `denominazione` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_richiesta` date NOT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attivita_pagamento` tinyint(1) DEFAULT '0',
  `importo` decimal(10,2) DEFAULT NULL,
  `richiesta` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `soluzione` text COLLATE utf8mb4_unicode_ci,
  `stato` enum('aperta','in_lavorazione','completata','chiusa') COLLATE utf8mb4_unicode_ci DEFAULT 'aperta',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task`
--

DROP TABLE IF EXISTS `task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descrizione` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `scadenza` date NOT NULL,
  `ricorrenza` int DEFAULT '0',
  `assegnato_a` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fatturabile` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_scadenza` (`scadenza`),
  KEY `idx_ricorrenza` (`ricorrenza`),
  KEY `idx_assegnato_a` (`assegnato_a`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task_clienti`
--

DROP TABLE IF EXISTS `task_clienti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_clienti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `titolo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descrizione` text COLLATE utf8mb4_unicode_ci,
  `stato` enum('aperto','in_corso','completato','cancellato') COLLATE utf8mb4_unicode_ci DEFAULT 'aperto',
  `priorita` enum('bassa','media','alta','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `data_scadenza` date DEFAULT NULL,
  `assegnato_a` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `scadenza` date DEFAULT NULL,
  `ricorrenza` int DEFAULT '0',
  `fatturabile` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_stato` (`stato`),
  KEY `idx_priorita` (`priorita`),
  KEY `idx_scadenza` (`data_scadenza`),
  KEY `idx_assegnato` (`assegnato_a`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `user_active_chats`
--

DROP TABLE IF EXISTS `user_active_chats`;
/*!50001 DROP VIEW IF EXISTS `user_active_chats`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `user_active_chats` AS SELECT 
 1 AS `id`,
 1 AS `type`,
 1 AS `name`,
 1 AS `client_id`,
 1 AS `user1_id`,
 1 AS `user2_id`,
 1 AS `last_message_at`,
 1 AS `unread_count`,
 1 AS `display_name`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user_conversation_status`
--

DROP TABLE IF EXISTS `user_conversation_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_conversation_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `conversation_id` int NOT NULL,
  `last_seen` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_conversation` (`user_id`,`conversation_id`),
  KEY `idx_user_conversation` (`user_id`,`conversation_id`),
  KEY `idx_last_seen` (`last_seen`),
  CONSTRAINT `user_conversation_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3353 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_online` tinyint(1) DEFAULT '1',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_session` (`user_id`),
  KEY `idx_online_activity` (`is_online`,`last_activity`),
  KEY `idx_session_cleanup` (`last_activity`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_telegram_config`
--

DROP TABLE IF EXISTS `user_telegram_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_telegram_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `telegram_chat_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notifications_enabled` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_telegram` (`user_id`),
  KEY `idx_notifications_enabled` (`notifications_enabled`),
  CONSTRAINT `user_telegram_config_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `users_online_status`
--

DROP TABLE IF EXISTS `users_online_status`;
/*!50001 DROP VIEW IF EXISTS `users_online_status`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `users_online_status` AS SELECT 
 1 AS `id`,
 1 AS `nome`,
 1 AS `email`,
 1 AS `ruolo`,
 1 AS `is_online`,
 1 AS `last_activity`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `utenti`
--

DROP TABLE IF EXISTS `utenti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utenti` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruolo` enum('guest','employee','impiegato','admin','developer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'employee',
  `telegram_chat_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `colore` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007BFF',
  `is_online` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_ruolo` (`ruolo`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `user_active_chats`
--

/*!50001 DROP VIEW IF EXISTS `user_active_chats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`crmuser`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `user_active_chats` AS select `c`.`id` AS `id`,`c`.`type` AS `type`,`c`.`name` AS `name`,`c`.`client_id` AS `client_id`,`c`.`user1_id` AS `user1_id`,`c`.`user2_id` AS `user2_id`,`c`.`last_message_at` AS `last_message_at`,coalesce(`rs`.`unread_count`,0) AS `unread_count`,(case when (`c`.`type` = 'globale') then `c`.`name` when (`c`.`type` = 'pratica') then concat('Pratica: ',`cl`.`Cognome_Ragione_sociale`) when (`c`.`type` = 'privata') then (case when (`c`.`user1_id` = `rs`.`user_id`) then `u2`.`nome` else `u1`.`nome` end) end) AS `display_name` from ((((`chat_conversations` `c` left join `chat_read_status` `rs` on((`c`.`id` = `rs`.`conversation_id`))) left join `clienti` `cl` on((`c`.`client_id` = `cl`.`id`))) left join `utenti` `u1` on((`c`.`user1_id` = `u1`.`id`))) left join `utenti` `u2` on((`c`.`user2_id` = `u2`.`id`))) where (`c`.`is_active` = true) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `users_online_status`
--

/*!50001 DROP VIEW IF EXISTS `users_online_status`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`crmuser`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `users_online_status` AS select `u`.`id` AS `id`,`u`.`nome` AS `nome`,`u`.`email` AS `email`,`u`.`ruolo` AS `ruolo`,coalesce(`s`.`is_online`,false) AS `is_online`,`s`.`last_activity` AS `last_activity` from (`utenti` `u` left join `user_sessions` `s` on((`u`.`id` = `s`.`user_id`))) order by `s`.`is_online` desc,`s`.`last_activity` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-18 20:34:10
