-- =================================================================
-- 🗃️ CRM ASContabilmente - Schema Database Principale
-- =================================================================
-- Versione: 2.1.0
-- Data: 2025-09-08
-- =================================================================

-- Impostazioni database
SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- =================================================================
-- TABELLA UTENTI
-- =================================================================
CREATE TABLE IF NOT EXISTS `utenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cognome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruolo` enum('guest','employee','admin','developer') COLLATE utf8mb4_unicode_ci DEFAULT 'employee',
  `telegram_chat_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colore` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007BFF',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_ruolo` (`ruolo`),
  KEY `idx_telegram` (`telegram_chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utenti di default
INSERT IGNORE INTO `utenti` (`id`, `nome`, `email`, `password`, `ruolo`) VALUES
(1, 'Administrator', 'admin@crm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Developer', 'dev@crm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'developer'),
(3, 'Roberto', 'roberto@crm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- =================================================================
-- TABELLA CLIENTI
-- =================================================================
CREATE TABLE IF NOT EXISTS `clienti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Inizio_rapporto` date DEFAULT NULL,
  `Fine_rapporto` date DEFAULT NULL,
  `Inserito_gestionale` tinyint(1) DEFAULT 0,
  `Codice_ditta` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Colore` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007BFF',
  `Cognome_Ragione_sociale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Codice_fiscale` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Partita_IVA` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Qualifica` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Soci_Amministratori` text COLLATE utf8mb4_unicode_ci,
  `Sede_Legale` text COLLATE utf8mb4_unicode_ci,
  `Sede_Operativa` text COLLATE utf8mb4_unicode_ci,
  `Data_di_nascita_costituzione` date DEFAULT NULL,
  `Luogo_di_nascita_costituzione` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Cellulare` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PEC` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Codice_SDI` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `IBAN` varchar(27) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_codice_fiscale` (`Codice_fiscale`),
  KEY `idx_partita_iva` (`Partita_IVA`),
  KEY `idx_email` (`Email`),
  KEY `idx_ragione_sociale` (`Cognome_Ragione_sociale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABELLA TASKS
-- =================================================================
CREATE TABLE IF NOT EXISTS `task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titolo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descrizione` text COLLATE utf8mb4_unicode_ci,
  `data_scadenza` date DEFAULT NULL,
  `priorita` enum('bassa','media','alta','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `stato` enum('da_fare','in_corso','completato','annullato') COLLATE utf8mb4_unicode_ci DEFAULT 'da_fare',
  `assegnato_a` int(11) DEFAULT NULL,
  `creato_da` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_task_assegnato` (`assegnato_a`),
  KEY `fk_task_creato` (`creato_da`),
  KEY `idx_stato` (`stato`),
  KEY `idx_scadenza` (`data_scadenza`),
  CONSTRAINT `fk_task_assegnato` FOREIGN KEY (`assegnato_a`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_task_creato` FOREIGN KEY (`creato_da`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABELLA TASK CLIENTI
-- =================================================================
CREATE TABLE IF NOT EXISTS `task_clienti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titolo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descrizione` text COLLATE utf8mb4_unicode_ci,
  `cliente_id` int(11) NOT NULL,
  `data_scadenza` date DEFAULT NULL,
  `priorita` enum('bassa','media','alta','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `stato` enum('da_fare','in_corso','completato','annullato') COLLATE utf8mb4_unicode_ci DEFAULT 'da_fare',
  `assegnato_a` int(11) DEFAULT NULL,
  `creato_da` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_task_clienti_cliente` (`cliente_id`),
  KEY `fk_task_clienti_assegnato` (`assegnato_a`),
  KEY `fk_task_clienti_creato` (`creato_da`),
  KEY `idx_stato_clienti` (`stato`),
  KEY `idx_scadenza_clienti` (`data_scadenza`),
  CONSTRAINT `fk_task_clienti_assegnato` FOREIGN KEY (`assegnato_a`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_task_clienti_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clienti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_clienti_creato` FOREIGN KEY (`creato_da`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABELLA DOCUMENTI
-- =================================================================
CREATE TABLE IF NOT EXISTS `documenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percorso` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dimensione` bigint(20) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descrizione` text COLLATE utf8mb4_unicode_ci,
  `caricato_da` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_documenti_cliente` (`cliente_id`),
  KEY `fk_documenti_utente` (`caricato_da`),
  KEY `idx_categoria` (`categoria`),
  CONSTRAINT `fk_documenti_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clienti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_documenti_utente` FOREIGN KEY (`caricato_da`) REFERENCES `utenti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABELLA EMAIL LOG
-- =================================================================
CREATE TABLE IF NOT EXISTS `email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destinatario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oggetto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `messaggio` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('normale','sistema','notifica') COLLATE utf8mb4_unicode_ci DEFAULT 'normale',
  `stato` enum('inviata','fallita','in_coda') COLLATE utf8mb4_unicode_ci DEFAULT 'in_coda',
  `inviata_da` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_email_utente` (`inviata_da`),
  KEY `fk_email_cliente` (`cliente_id`),
  KEY `idx_stato_email` (`stato`),
  KEY `idx_tipo_email` (`tipo`),
  CONSTRAINT `fk_email_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clienti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_email_utente` FOREIGN KEY (`inviata_da`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- TABELLA CALENDAR EVENTS META
-- =================================================================
CREATE TABLE IF NOT EXISTS `calendar_events_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `google_event_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007BFF',
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `google_event_id` (`google_event_id`),
  KEY `fk_calendar_assigned` (`assigned_to_user_id`),
  KEY `fk_calendar_created` (`created_by_user_id`),
  KEY `fk_calendar_client` (`client_id`),
  CONSTRAINT `fk_calendar_assigned` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_calendar_client` FOREIGN KEY (`client_id`) REFERENCES `clienti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_calendar_created` FOREIGN KEY (`created_by_user_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- VIEWS PER REPORTING
-- =================================================================

-- Vista task scaduti
CREATE VIEW `task_scaduti` AS
SELECT 
    t.id,
    t.titolo,
    t.descrizione,
    t.data_scadenza,
    t.priorita,
    t.stato,
    u1.nome as assegnato_a_nome,
    u2.nome as creato_da_nome
FROM `task` t
LEFT JOIN `utenti` u1 ON t.assegnato_a = u1.id
LEFT JOIN `utenti` u2 ON t.creato_da = u2.id
WHERE t.data_scadenza < CURDATE() AND t.stato != 'completato';

-- Vista statistiche utenti
CREATE VIEW `statistiche_utenti` AS
SELECT 
    u.id,
    u.nome,
    u.email,
    u.ruolo,
    COUNT(DISTINCT t1.id) as task_assegnati,
    COUNT(DISTINCT t2.id) as task_creati,
    COUNT(DISTINCT tc1.id) as task_clienti_assegnati,
    COUNT(DISTINCT tc2.id) as task_clienti_creati
FROM `utenti` u
LEFT JOIN `task` t1 ON u.id = t1.assegnato_a
LEFT JOIN `task` t2 ON u.id = t2.creato_da
LEFT JOIN `task_clienti` tc1 ON u.id = tc1.assegnato_a
LEFT JOIN `task_clienti` tc2 ON u.id = tc2.creato_da
GROUP BY u.id;

-- =================================================================
-- INDEXES PER PERFORMANCE
-- =================================================================

-- Indexes aggiuntivi per ottimizzazione
ALTER TABLE `utenti` 
    ADD INDEX `idx_created_at` (`created_at`),
    ADD INDEX `idx_updated_at` (`updated_at`);

ALTER TABLE `clienti` 
    ADD INDEX `idx_created_at` (`created_at`),
    ADD INDEX `idx_updated_at` (`updated_at`),
    ADD FULLTEXT KEY `idx_search_cliente` (`Cognome_Ragione_sociale`, `Nome`);

ALTER TABLE `task` 
    ADD INDEX `idx_created_at` (`created_at`),
    ADD INDEX `idx_updated_at` (`updated_at`),
    ADD INDEX `idx_priorita_stato` (`priorita`, `stato`);

ALTER TABLE `task_clienti` 
    ADD INDEX `idx_created_at` (`created_at`),
    ADD INDEX `idx_updated_at` (`updated_at`),
    ADD INDEX `idx_priorita_stato_clienti` (`priorita`, `stato`);

-- =================================================================
-- TRIGGERS PER AUDIT
-- =================================================================

-- Trigger per aggiornamento automatico timestamp
DELIMITER $$

CREATE TRIGGER `update_utenti_timestamp` 
    BEFORE UPDATE ON `utenti`
    FOR EACH ROW 
    SET NEW.updated_at = CURRENT_TIMESTAMP;

CREATE TRIGGER `update_clienti_timestamp` 
    BEFORE UPDATE ON `clienti`
    FOR EACH ROW 
    SET NEW.updated_at = CURRENT_TIMESTAMP;

CREATE TRIGGER `update_task_timestamp` 
    BEFORE UPDATE ON `task`
    FOR EACH ROW 
    SET NEW.updated_at = CURRENT_TIMESTAMP;

CREATE TRIGGER `update_task_clienti_timestamp` 
    BEFORE UPDATE ON `task_clienti`
    FOR EACH ROW 
    SET NEW.updated_at = CURRENT_TIMESTAMP;

DELIMITER ;

-- =================================================================
-- STORED PROCEDURES
-- =================================================================

DELIMITER $$

-- Procedura per statistiche dashboard
CREATE PROCEDURE `GetDashboardStats`()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM utenti WHERE ruolo != 'guest') as total_utenti,
        (SELECT COUNT(*) FROM clienti) as total_clienti,
        (SELECT COUNT(*) FROM task WHERE stato != 'completato') as task_aperti,
        (SELECT COUNT(*) FROM task_clienti WHERE stato != 'completato') as task_clienti_aperti,
        (SELECT COUNT(*) FROM task WHERE data_scadenza < CURDATE() AND stato != 'completato') as task_scaduti,
        (SELECT COUNT(*) FROM task_clienti WHERE data_scadenza < CURDATE() AND stato != 'completato') as task_clienti_scaduti;
END$$

-- Procedura per cleanup periodico
CREATE PROCEDURE `CleanupOldData`()
BEGIN
    -- Pulisci log email vecchi (>6 mesi)
    DELETE FROM email_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Pulisci task completati vecchi (>1 anno)
    DELETE FROM task WHERE stato = 'completato' AND updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    DELETE FROM task_clienti WHERE stato = 'completato' AND updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
END$$

DELIMITER ;

-- =================================================================
-- IMPOSTAZIONI FINALI
-- =================================================================

-- Ripristina impostazioni
SET foreign_key_checks = 1;

-- Messaggio di completamento
SELECT 'Database CRM ASContabilmente installato con successo!' as Status;
