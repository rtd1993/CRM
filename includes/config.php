<?php
// File: includes/config.php

// Configurazione globale
const DB_HOST = 'localhost';
const DB_NAME = 'crm';
const DB_USER = 'crmuser';
const DB_PASS = 'Admin123!';

const SITE_NAME = 'CRM ASContabilmente';
const BASE_URL = 'http://crm.local';

// Telegram bot configuration
// Sostituire con il token reale fornito da BotFather
const TELEGRAM_BOT_TOKEN = 'your_telegram_bot_token_here';
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
