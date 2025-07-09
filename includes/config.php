<?php
// File: includes/config.php

// Configurazione globale
const DB_HOST = 'localhost';
const DB_NAME = 'crm';
const DB_USER = 'crmuser';
const DB_PASS = 'Admin123';

const SITE_NAME = 'CRM ASContabilmente';
const BASE_URL = 'http://crm.local';

// Telegram bot (da configurare)
const TELEGRAM_TOKEN = 'your_telegram_bot_token';
const TELEGRAM_CHAT_ID = 'your_chat_id';

// Google API (da configurare)
const GOOGLE_CLIENT_ID = 'your_client_id';
const GOOGLE_CLIENT_SECRET = 'your_client_secret';
const GOOGLE_REDIRECT_URI = BASE_URL . '/oauth2callback.php';
