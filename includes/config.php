<?php
// File: includes/config.php

// Configurazione globale
const DB_HOST = 'localhost';
const DB_NAME = 'crm';
const DB_USER = 'crmuser';
const DB_PASS = 'Admin123!';

const SITE_NAME = 'CRM ASContabilmente';
const BASE_URL = 'https://ascontabilmente.homes';

// Socket.IO Configuration
// Usa l'URL del tunnel Cloudflare per Socket.IO
function getSocketIOUrl() {
    // Se siamo in un tunnel Cloudflare, usa il sottodominio socket
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ascontabilmente.homes') !== false) {
        return "https://socket.ascontabilmente.homes";
    }
    
    // Fallback per accesso locale
    $server_ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $server_ip = explode(':', $server_ip)[0];
    return "http://{$server_ip}:3001";
}

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
