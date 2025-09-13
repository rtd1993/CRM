#!/bin/bash

# =================================================================
# 🚀 CRM ASContabilmente - Script di Installazione Automatica
# =================================================================
# Versione: 2.0
# Data: $(date +%Y-%m-%d)
# Autore: Sistema CRM
# Repository: https://github.com/rtd1993/CRM
# =================================================================

set -e  # Termina in caso di errore

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variabili di configurazione
REPO_URL="https://github.com/rtd1993/CRM.git"
INSTALL_DIR="/var/www/CRM"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"
MYSQL_ROOT_PASS=""

# Banner
echo -e "${BLUE}"
echo "=================================================================="
echo "🚀 CRM ASContabilmente - Installazione Automatica"
echo "=================================================================="
echo -e "${NC}"

# Funzione per logging
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Verifica se lo script è eseguito come root
if [[ $EUID -ne 0 ]]; then
   error "Questo script deve essere eseguito come root (sudo)"
fi

# Richiedi conferma
echo -e "${YELLOW}Questo script installerà il CRM ASContabilmente con:${NC}"
echo "- Repository: $REPO_URL"
echo "- Directory: $INSTALL_DIR"
echo "- Database: $DB_NAME"
echo "- User DB: $DB_USER"
echo ""
read -p "Continuare con l'installazione? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Installazione annullata."
    exit 1
fi

# Chiedi password MySQL root se non impostata
if [ -z "$MYSQL_ROOT_PASS" ]; then
    echo -e "${YELLOW}Inserisci la password di MySQL root:${NC}"
    read -s MYSQL_ROOT_PASS
    echo
fi

# =================================================================
# FASE 1: AGGIORNAMENTO SISTEMA
# =================================================================
log "Aggiornamento del sistema..."
apt update && apt upgrade -y

# =================================================================
# FASE 2: INSTALLAZIONE DIPENDENZE
# =================================================================
log "Installazione dipendenze di sistema..."

# Pacchetti essenziali
apt install -y \
    curl \
    wget \
    git \
    unzip \
    ca-certificates \
    gnupg \
    lsb-release \
    software-properties-common

# Apache2
log "Installazione Apache2..."
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# PHP 8.1+
log "Installazione PHP..."
apt install -y \
    php \
    php-cli \
    php-common \
    php-mysql \
    php-curl \
    php-json \
    php-mbstring \
    php-xml \
    php-zip \
    php-gd \
    php-intl \
    php-bcmath \
    php-soap \
    php-imap \
    libapache2-mod-php

# MySQL Server
log "Installazione MySQL Server..."
apt install -y mysql-server

# Node.js (per sistema chat)
log "Installazione Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Composer
log "Installazione Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# =================================================================
# FASE 3: CONFIGURAZIONE MYSQL
# =================================================================
log "Configurazione database MySQL..."

# Crea database e utente
mysql -u root -p$MYSQL_ROOT_PASS <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

log "Database $DB_NAME creato con utente $DB_USER"

# =================================================================
# FASE 4: DOWNLOAD E CONFIGURAZIONE CRM
# =================================================================
log "Download del codice CRM..."

# Backup directory esistente se presente
if [ -d "$INSTALL_DIR" ]; then
    warning "Directory $INSTALL_DIR esistente, creo backup..."
    mv "$INSTALL_DIR" "${INSTALL_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Clone repository
git clone $REPO_URL $INSTALL_DIR
cd $INSTALL_DIR

# Permessi corretti
chown -R www-data:www-data $INSTALL_DIR
chmod -R 755 $INSTALL_DIR
chmod -R 777 $INSTALL_DIR/logs

# =================================================================
# FASE 5: INSTALLAZIONE DIPENDENZE CRM
# =================================================================
log "Installazione dipendenze PHP..."
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

log "Installazione dipendenze Node.js..."
if [ -f "package.json" ]; then
    npm install --production
fi

# =================================================================
# FASE 6: CONFIGURAZIONE DATABASE CRM
# =================================================================
log "Configurazione database CRM..."

# Importa schema principale se presente
if [ -f "database_schema.sql" ]; then
    mysql -u root -p$MYSQL_ROOT_PASS $DB_NAME < database_schema.sql
    log "Schema principale importato"
fi

# Importa chat system
if [ -f "install_chat_database.sql" ]; then
    mysql -u root -p$MYSQL_ROOT_PASS $DB_NAME < install_chat_database.sql
    log "Sistema chat installato"
fi

# Crea utenti di default
mysql -u root -p$MYSQL_ROOT_PASS $DB_NAME <<EOF
-- Tabella utenti se non esiste
CREATE TABLE IF NOT EXISTS utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    cognome VARCHAR(255) NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ruolo ENUM('guest', 'employee', 'admin', 'developer') DEFAULT 'employee',
    telegram_chat_id VARCHAR(255) NULL,
    colore VARCHAR(7) DEFAULT '#007BFF',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utente amministratore di default
INSERT IGNORE INTO utenti (nome, email, password, ruolo) VALUES 
('Administrator', 'admin@crm.local', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Developer', 'dev@crm.local', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'developer');

-- Tabella clienti se non esiste  
CREATE TABLE IF NOT EXISTS clienti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    Cognome_Ragione_sociale VARCHAR(255) NOT NULL,
    Nome VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    telefono VARCHAR(50) NULL,
    Codice_fiscale VARCHAR(16) NULL,
    Partita_IVA VARCHAR(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF

log "Utenti di default creati"

# =================================================================
# FASE 7: CONFIGURAZIONE APACHE
# =================================================================
log "Configurazione Apache Virtual Host..."

# Crea virtual host
cat > /etc/apache2/sites-available/crm.conf <<EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot $INSTALL_DIR
    
    <Directory $INSTALL_DIR>
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
        DirectoryIndex index.php index.html
    </Directory>
    
    # Log files
    ErrorLog \${APACHE_LOG_DIR}/crm_error.log
    CustomLog \${APACHE_LOG_DIR}/crm_access.log combined
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
EOF

# Abilita moduli Apache
a2enmod rewrite
a2enmod headers
a2enmod ssl

# Abilita sito
a2ensite crm.conf
a2dissite 000-default

# Restart Apache
systemctl restart apache2

# =================================================================
# FASE 8: CONFIGURAZIONE PHP
# =================================================================
log "Configurazione PHP..."

# Crea configurazione PHP personalizzata
cat > /etc/php/*/apache2/conf.d/99-crm.ini <<EOF
; CRM Configuration
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
session.gc_maxlifetime = 1440
session.gc_probability = 1
session.gc_divisor = 100
date.timezone = Europe/Rome
EOF

# Restart Apache per applicare configurazioni PHP
systemctl restart apache2

# =================================================================
# FASE 9: CONFIGURAZIONE SERVIZI
# =================================================================
log "Configurazione servizi di sistema..."

# Servizio per Node.js chat system
if [ -f "$INSTALL_DIR/socket.js" ]; then
    cat > /etc/systemd/system/crm-chat.service <<EOF
[Unit]
Description=CRM Chat System (Node.js Socket.IO)
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=$INSTALL_DIR
ExecStart=/usr/bin/node socket.js
Restart=always
RestartSec=10
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    systemctl enable crm-chat.service
    systemctl start crm-chat.service
fi

# Crontab per maintenance
if [ -f "$INSTALL_DIR/scripts/maintenance.sh" ]; then
    (crontab -l 2>/dev/null; echo "0 2 * * * $INSTALL_DIR/scripts/maintenance.sh") | crontab -
fi

# =================================================================
# FASE 10: CONFIGURAZIONE FIREWALL
# =================================================================
log "Configurazione firewall..."
ufw allow 22/tcp      # SSH
ufw allow 80/tcp      # HTTP
ufw allow 443/tcp     # HTTPS
ufw allow 3001/tcp    # Node.js Chat
ufw --force enable

# =================================================================
# FASE 11: PULIZIA E OTTIMIZZAZIONE
# =================================================================
log "Pulizia sistema..."

# Rimuovi file di test se in produzione
if [ "$1" = "production" ]; then
    rm -f $INSTALL_DIR/test_*.php
    rm -f $INSTALL_DIR/debug_*.php
    rm -f $INSTALL_DIR/*_debug.php
    rm -f $INSTALL_DIR/login_debug.php
    rm -f $INSTALL_DIR/dashboard-test.php
    rm -f $INSTALL_DIR/dashboard-ultra-basic.php
fi

# Ottimizza autoloader PHP
if [ -f "$INSTALL_DIR/composer.json" ]; then
    cd $INSTALL_DIR
    composer dump-autoload --optimize --no-dev
fi

# Pulizia logs
find $INSTALL_DIR/logs -name "*.log" -type f -delete 2>/dev/null || true

# =================================================================
# COMPLETAMENTO
# =================================================================
echo -e "${GREEN}"
echo "=================================================================="
echo "🎉 INSTALLAZIONE COMPLETATA CON SUCCESSO!"
echo "=================================================================="
echo -e "${NC}"

echo -e "${BLUE}📋 INFORMAZIONI SISTEMA:${NC}"
echo "• URL CRM: http://$(hostname -I | awk '{print $1}')"
echo "• Directory: $INSTALL_DIR"
echo "• Database: $DB_NAME"
echo "• User DB: $DB_USER"
echo ""

echo -e "${BLUE}👥 UTENTI DI DEFAULT:${NC}"
echo "• Admin: admin@crm.local / password"
echo "• Developer: dev@crm.local / password"
echo ""

echo -e "${BLUE}🔧 SERVIZI:${NC}"
echo "• Apache2: $(systemctl is-active apache2)"
echo "• MySQL: $(systemctl is-active mysql)"
echo "• CRM Chat: $(systemctl is-active crm-chat 2>/dev/null || echo 'not installed')"
echo ""

echo -e "${BLUE}📂 FILE CONFIGURAZIONE:${NC}"
echo "• Config: $INSTALL_DIR/includes/config.php"
echo "• Apache: /etc/apache2/sites-available/crm.conf"
echo "• PHP: /etc/php/*/apache2/conf.d/99-crm.ini"
echo ""

echo -e "${YELLOW}📝 PROSSIMI PASSI:${NC}"
echo "1. Visitare http://$(hostname -I | awk '{print $1}') per accedere al CRM"
echo "2. Effettuare login con le credenziali di default"
echo "3. Cambiare le password di default"
echo "4. Configurare SSL/HTTPS se necessario"
echo "5. Configurare backup automatici"
echo ""

echo -e "${GREEN}✅ Installazione completata!${NC}"
