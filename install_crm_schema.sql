-- Script di installazione struttura database CRM
-- Utente: crmuser
-- Password: Admin123!
-- Database: crm

-- Crea database se non esiste
CREATE DATABASE IF NOT EXISTS crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crm;

-- Crea utente e assegna permessi
CREATE USER IF NOT EXISTS 'crmuser'@'localhost' IDENTIFIED BY 'Admin123!';
GRANT ALL PRIVILEGES ON crm.* TO 'crmuser'@'localhost';
FLUSH PRIVILEGES;

-- Esempio struttura tabella utenti
CREATE TABLE IF NOT EXISTS utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    ruolo ENUM('guest','employee','admin','developer') NOT NULL DEFAULT 'employee',
    telegram_chat_id VARCHAR(50),
    colore VARCHAR(10) DEFAULT '#007BFF',
    password VARCHAR(255) NOT NULL,
    is_online BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Aggiungi qui la struttura delle altre tabelle...
-- Per una copia completa, eseguire il comando mysqldump --no-data -u crmuser -p crm > install_crm_schema.sql
