# 🔔 Configurazione Notifiche Telegram

Questo sistema permette di inviare notifiche Telegram agli utenti quando non sono connessi al CRM.

## 📋 Prerequisiti

1. **Account Telegram** attivo
2. **Accesso amministratore** al CRM
3. **Server Node.js** attivo per Socket.IO

## 🤖 Configurazione Bot Telegram

### 1. Creazione del Bot

1. Apri Telegram e cerca **@BotFather**
2. Invia il comando `/newbot`
3. Scegli un nome per il bot (es: "CRM ASContabilmente Bot")
4. Scegli un username (es: "ascontabilmente_crm_bot")
5. Copia il **token** fornito da BotFather

### 2. Configurazione nel CRM

1. Modifica il file `includes/config.php`
2. Sostituisci `your_telegram_bot_token_here` con il token del bot:

```php
const TELEGRAM_BOT_TOKEN = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz';
```

### 3. Configurazione Chat ID Utenti

#### Metodo 1: Tramite interfaccia web
1. Vai su `/telegram_get_id.php`
2. Configura il token del bot nel file
3. Avvia il bot su Telegram
4. Invia un messaggio al bot
5. Copia il Chat ID dalla pagina web

#### Metodo 2: Tramite profilo utente
1. Ogni utente va su `/profilo.php`
2. Inserisce il proprio Chat ID di Telegram
3. Salva le modifiche

## 🔧 Funzionalità

### Notifiche Automatiche
- **Chat Globale**: Quando un utente scrive nella chat globale, tutti gli utenti offline ricevono una notifica
- **Chat Pratiche**: Quando viene aggiunto un appunto a una pratica, gli utenti offline vengono notificati
- **Test Notifiche**: Gli admin possono testare le notifiche dalla pagina `/telegram_config.php`

### Gestione Utenti
- **Pannello Admin**: `/telegram_config.php` per gestire le configurazioni Telegram
- **Statistiche**: Mostra quanti utenti hanno configurato Telegram
- **Test Messaggi**: Possibilità di inviare messaggi di test

## 🛠️ Risoluzione Problemi

### Bot non risponde
- Verifica che il token sia corretto
- Assicurati che il bot sia stato avviato su Telegram
- Controlla i log del server per errori

### Notifiche non arrivano
- Verifica che il Chat ID sia corretto
- Controlla che l'utente abbia avviato il bot
- Verifica la connessione internet del server

### Errori di configurazione
- Controlla che le costanti in `config.php` siano impostate correttamente
- Verifica che il server Node.js sia in esecuzione
- Controlla i permessi del database

## 📱 Comandi Bot Utili

### Per gli utenti:
- `/start` - Avvia il bot
- Qualsiasi messaggio per generare il Chat ID

### Per gli admin:
- Test notifiche dal pannello admin
- Monitoraggio statistiche utilizzo

## 🔒 Sicurezza

- I Chat ID sono memorizzati in modo sicuro nel database
- Le notifiche contengono solo le informazioni necessarie
- Il token del bot è protetto nelle configurazioni del server

## 📊 Monitoraggio

Il sistema fornisce:
- **Statistiche** utenti con Telegram configurato
- **Log** delle notifiche inviate
- **Test** della connettività del bot

## 🆘 Supporto

Per problemi o domande:
1. Controlla i log del server
2. Verifica la configurazione in `/telegram_config.php`
3. Testa la connessione con `/telegram_get_id.php`

---

*Configurazione completata con successo! 🎉*
