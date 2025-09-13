# 🚀 CRM ASContabilmente - Pacchetto di Installazione

Questo pacchetto contiene tutto il necessario per installare e configurare il CRM ASContabilmente su un nuovo server Ubuntu/Debian.

## 📋 Requisiti Sistema

### Minimo
- **OS**: Ubuntu 20.04+ / Debian 11+
- **RAM**: 2GB
- **Storage**: 10GB liberi
- **CPU**: 2 core

### Raccomandato
- **OS**: Ubuntu 22.04 LTS
- **RAM**: 4GB+
- **Storage**: 20GB+ SSD
- **CPU**: 4 core+

## 🏗️ Componenti Installati

### Software di Base
- **Apache 2.4+** - Web server
- **PHP 8.1+** - Runtime applicazione
- **MySQL 8.0+** - Database server
- **Node.js 20+** - Sistema chat real-time
- **Composer** - Gestione dipendenze PHP

### Estensioni PHP
- php-mysql, php-curl, php-json, php-mbstring
- php-xml, php-zip, php-gd, php-intl
- php-bcmath, php-soap, php-imap

### Servizi CRM
- **Sistema Chat** - WhatsApp-like con Socket.IO
- **Sistema Email** - SMTP configurabile
- **Sistema Telegram** - Notifiche bot
- **Backup automatici** - Scripts di manutenzione

## 🔐 Credenziali di Default

### Database MySQL
```
Host: localhost
Database: crm
Username: crmuser
Password: Admin123!
```

### Utenti CRM di Default
```
Administrator:
  Email: admin@crm.local
  Password: password
  Ruolo: admin

Developer:
  Email: dev@crm.local  
  Password: password
  Ruolo: developer
```

### Telegram Bot
```
Token: 7235317891:AAGpr8mOFVVksFV9LbF5Fe8RPWsLqdcOAd4
Chat ID: Da configurare per ogni utente
```

## 🚀 Installazione Rapida

### 1. Download e Permessi
```bash
wget https://raw.githubusercontent.com/rtd1993/CRM/master/install.sh
chmod +x install.sh
```

### 2. Esecuzione
```bash
sudo ./install.sh
```

### 3. Installazione in Produzione
```bash
sudo ./install.sh production
```
*Rimuove automaticamente file di test e debug*

## 📖 Installazione Manuale Dettagliata

### 1. Preparazione Sistema
```bash
# Aggiorna sistema
sudo apt update && sudo apt upgrade -y

# Installa Git
sudo apt install -y git curl wget
```

### 2. Clona Repository
```bash
cd /var/www
sudo git clone https://github.com/rtd1993/CRM.git
sudo chown -R www-data:www-data CRM
```

### 3. Installa Dipendenze
```bash
# Apache e PHP
sudo apt install -y apache2 php php-mysql php-curl php-json php-mbstring php-xml

# MySQL
sudo apt install -y mysql-server

# Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 4. Configura Database
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crmuser'@'localhost' IDENTIFIED BY 'Admin123!';
GRANT ALL PRIVILEGES ON crm.* TO 'crmuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Importa Schema Database
```bash
cd /var/www/CRM
sudo mysql -u root -p crm < install_chat_database.sql
```

### 6. Configura Apache
```bash
sudo nano /etc/apache2/sites-available/crm.conf
```
```apache
<VirtualHost *:80>
    ServerName tuodominio.com
    DocumentRoot /var/www/CRM
    
    <Directory /var/www/CRM>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/crm_error.log
    CustomLog ${APACHE_LOG_DIR}/crm_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite crm.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 7. Installa Dipendenze CRM
```bash
cd /var/www/CRM

# PHP Dependencies
composer install --no-dev

# Node.js Dependencies  
npm install --production
```

### 8. Configura Servizi
```bash
# Chat Service
sudo nano /etc/systemd/system/crm-chat.service
```
```ini
[Unit]
Description=CRM Chat System
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/CRM
ExecStart=/usr/bin/node socket.js
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable crm-chat.service
sudo systemctl start crm-chat.service
```

## ⚙️ Configurazione Post-Installazione

### 1. Configurazione Email SMTP
Modifica `includes/email_config.php`:
```php
const SMTP_HOST = 'smtp.tuoserver.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'noreply@tuodominio.com';
const SMTP_PASSWORD = 'tua_password_smtp';
```

### 2. Configurazione Google Calendar API
1. Crea progetto su Google Cloud Console
2. Abilita Calendar API
3. Crea Service Account
4. Scarica JSON credentials come `google-calendar.json`
5. Posiziona in root directory CRM

### 3. Configurazione SSL/HTTPS
```bash
# Installa Certbot
sudo apt install -y certbot python3-certbot-apache

# Ottieni certificato
sudo certbot --apache -d tuodominio.com

# Rinnovo automatico
sudo crontab -e
# Aggiungi: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 4. Configurazione Backup
```bash
# Script backup database
sudo nano /usr/local/bin/backup-crm.sh
```
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u crmuser -pAdmin123! crm > /var/backups/crm_$DATE.sql
find /var/backups -name "crm_*.sql" -mtime +7 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-crm.sh
sudo crontab -e
# Aggiungi: 0 2 * * * /usr/local/bin/backup-crm.sh
```

## 🔧 Struttura Database

### Tabelle Principali
- `utenti` - Gestione utenti e autenticazione
- `clienti` - Anagrafica clienti
- `chat_conversations` - Conversazioni chat
- `chat_messages` - Messaggi chat
- `chat_participants` - Partecipanti chat
- `calendar_events_meta` - Metadati eventi calendario

### Ruoli Utente
- **guest** - Solo lettura
- **employee** - Operazioni base
- **admin** - Gestione completa
- **developer** - Accesso sviluppo

## 🛠️ Script di Manutenzione

### Pulizia Logs
```bash
sudo find /var/www/CRM/logs -name "*.log" -mtime +30 -delete
```

### Ottimizzazione Database
```bash
sudo mysql -u root -p crm -e "OPTIMIZE TABLE chat_messages, chat_conversations;"
```

### Aggiornamento Sistema
```bash
cd /var/www/CRM
sudo git pull origin master
composer install --no-dev
npm install --production
sudo systemctl restart apache2 crm-chat
```

## 📊 Monitoraggio

### Status Servizi
```bash
sudo systemctl status apache2 mysql crm-chat
```

### Log Files
- Apache: `/var/log/apache2/crm_*.log`
- PHP: `/var/log/php_errors.log` 
- CRM: `/var/www/CRM/logs/`
- Chat: `journalctl -u crm-chat.service`

### Performance
```bash
# Spazio disco
df -h

# Memoria
free -h

# Processi attivi
top -p $(pgrep -d',' -f "apache2|mysql|node")
```

## 🔒 Sicurezza

### Firewall
```bash
sudo ufw allow 22      # SSH
sudo ufw allow 80      # HTTP  
sudo ufw allow 443     # HTTPS
sudo ufw enable
```

### Hardening Apache
```apache
# In /etc/apache2/conf-available/security.conf
ServerTokens Prod
ServerSignature Off
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
```

### Backup Credenziali
```bash
# Backup configurazione
sudo cp /var/www/CRM/includes/config.php /root/crm-config-backup.php
sudo chmod 600 /root/crm-config-backup.php
```

## 🚨 Troubleshooting

### Problemi Comuni

**1. Errore "No space left on device"**
```bash
# Pulizia sistema
sudo apt autoclean
sudo apt autoremove
sudo find /var/log -name "*.log" -mtime +7 -delete
```

**2. Chat non funziona**
```bash
# Verifica servizio Node.js
sudo systemctl status crm-chat
sudo journalctl -u crm-chat -f
```

**3. Database connection error**
```bash
# Verifica MySQL
sudo systemctl status mysql
sudo mysql -u crmuser -pAdmin123! crm -e "SELECT 1;"
```

**4. Sessioni non persistenti**
```bash
# Verifica permessi cartella sessioni
sudo ls -la /var/lib/php/sessions
sudo chown www-data:www-data /var/lib/php/sessions
```

### Log Debug
```bash
# Abilita debug PHP temporaneamente
sudo nano /var/www/CRM/includes/config.php
# Aggiungi: ini_set('display_errors', 1);
```

## 📱 Configurazione Mobile

### Progressive Web App
Il CRM include supporto PWA. Per abilitare:
1. Configura HTTPS
2. Verifica `manifest.json`
3. Testa su Chrome DevTools > Application > Manifest

### Touch Interface
Per dispositivi touch, il sistema include:
- Interfaccia ottimizzata mobile
- Gesture support per chat
- Keyboard virtuale friendly

## 🔄 Aggiornamenti

### Processo di Update
```bash
cd /var/www/CRM

# Backup
sudo cp -r . ../CRM-backup-$(date +%Y%m%d)

# Update
sudo git pull
composer install --no-dev
npm install --production

# Database migrations se necessarie
sudo mysql -u root -p crm < updates/migration_*.sql

# Restart services
sudo systemctl restart apache2 crm-chat
```

### Rollback
```bash
sudo systemctl stop apache2 crm-chat
sudo rm -rf /var/www/CRM
sudo mv /var/www/CRM-backup-YYYYMMDD /var/www/CRM
sudo systemctl start apache2 crm-chat
```

## 📞 Supporto

### Documentazione
- Chat System: `README_CHAT_SYSTEM.md`
- API Documentation: `/api/docs/`
- Database Schema: `install_chat_database.sql`

### Debug Tools
- Database Test: `/test_database_chat.php`
- PHP Info: `/info.php`
- System Status: `/system_monitor.php`

---

## 📝 Note Finali

### Crediti
- Sviluppato per ASContabilmente
- Sistema Chat basato su Socket.IO
- UI/UX ispirato a WhatsApp Web

### Licenza
Uso interno ASContabilmente - Tutti i diritti riservati

### Versioning
- v1.0 - Sistema base CRM
- v2.0 - Sistema chat integrato
- v2.1 - Ottimizzazioni performance

---

**Data creazione**: $(date +%Y-%m-%d)  
**Ultima modifica**: $(date +%Y-%m-%d %H:%M:%S)  
**Versione**: 2.1.0
