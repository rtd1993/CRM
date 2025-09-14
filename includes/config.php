<?php
// File: includes/config.php

// Configurazione globale
const DB_HOST = 'localhost';
const DB_NAME = 'crm';
const DB_USER = 'crmuser';
const DB_PASS = 'Admin123!';

const SITE_NAME = 'CRM ASContabilmente';
const BASE_URL = 'https://ascontabilmente.homes';

// Telegram bot configuration
// Sostituire con il token reale fornito da BotFather
const TELEGRAM_BOT_TOKEN = '7235317891:AAGpr8mOFVVksFV9LbF5Fe8RPWsLqdcOAd4';
const TELEGRAM_CHAT_ID = 'your_default_chat_id_here';

// Per ottenere il token:
// 1. Contatta @BotFather su Telegram
// 2. Invia /newbot e segui le istruzioni
// 3. Copia il token qui sopra
//
// Per ottenere il Chat ID:
// 1. Visita telegram_get_id.php nel CRM
// 2. Segui le istruzioni per configurare il bot
// 3. Invia un messaggio al bot
// 4. Copia il Chat ID dal sistema

// Google API (da configurare)
const GOOGLE_CLIENT_ID = 'your_client_id';
const GOOGLE_CLIENT_SECRET = 'your_client_secret';
const GOOGLE_REDIRECT_URI = BASE_URL . '/oauth2callback.php';

// Connessione al database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}
