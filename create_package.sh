#!/bin/bash

# =================================================================
# 🚀 CRM ASContabilmente - Complete Deployment Package
# =================================================================
# Script per creare un pacchetto completo di deployment
# Include tutto il necessario per l'installazione su nuovo server
# =================================================================

set -e

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configurazione
PACKAGE_NAME="crm-ascontabilmente-$(date +%Y%m%d-%H%M%S)"
PACKAGE_DIR="/tmp/$PACKAGE_NAME"
SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ARCHIVE_NAME="$PACKAGE_NAME.tar.gz"

print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

# =================================================================
# CREAZIONE STRUTTURA PACCHETTO
# =================================================================

create_package_structure() {
    print_header "Creating Package Structure"
    
    # Rimuovi pacchetto precedente se esiste
    [[ -d "$PACKAGE_DIR" ]] && rm -rf "$PACKAGE_DIR"
    
    # Crea struttura directory
    mkdir -p "$PACKAGE_DIR"/{scripts,database,config,docs,src}
    
    print_success "Package directory created: $PACKAGE_DIR"
}

# =================================================================
# COPIA FILES ESSENZIALI
# =================================================================

copy_source_files() {
    print_header "Copying Source Files"
    
    # Lista file da escludere
    exclude_patterns=(
        "*.log"
        "*.tmp"
        ".git*"
        "node_modules"
        "vendor"
        "local_drive/*"
        "logs/*"
        "cookies.txt"
        "calendar_debug.log"
        ".env*"
        "composer.lock"
    )
    
    # Costruisci comando rsync con esclusioni
    exclude_args=""
    for pattern in "${exclude_patterns[@]}"; do
        exclude_args="$exclude_args --exclude=$pattern"
    done
    
    # Copia tutti i file sorgente escludendo i pattern specificati
    rsync -av $exclude_args "$SOURCE_DIR/" "$PACKAGE_DIR/src/" \
        --exclude="$PACKAGE_NAME*" \
        --exclude="test_installation.sh" \
        --exclude="create_package.sh"
    
    print_success "Source files copied to package"
    
    # Crea directory vuote necessarie
    mkdir -p "$PACKAGE_DIR/src"/{local_drive,logs,uploads,temp}
    touch "$PACKAGE_DIR/src/logs/.gitkeep"
    touch "$PACKAGE_DIR/src/local_drive/.gitkeep"
    
    print_success "Required directories created"
}

# =================================================================
# COPIA SCRIPTS DI INSTALLAZIONE
# =================================================================

copy_installation_scripts() {
    print_header "Copying Installation Scripts"
    
    # Copia script principali
    cp "$SOURCE_DIR/install.sh" "$PACKAGE_DIR/scripts/"
    cp "$SOURCE_DIR/test_installation.sh" "$PACKAGE_DIR/scripts/"
    
    # Copia script di manutenzione se esistono
    if [[ -d "$SOURCE_DIR/scripts" ]]; then
        cp -r "$SOURCE_DIR/scripts/"* "$PACKAGE_DIR/scripts/" 2>/dev/null || true
    fi
    
    # Rendi eseguibili gli script
    chmod +x "$PACKAGE_DIR/scripts/"*.sh
    
    print_success "Installation scripts copied and made executable"
}

# =================================================================
# COPIA DATABASE SCHEMA
# =================================================================

copy_database_files() {
    print_header "Copying Database Files"
    
    # Schema database principale
    cp "$SOURCE_DIR/database_schema.sql" "$PACKAGE_DIR/database/"
    
    # SQL files esistenti
    find "$SOURCE_DIR" -name "*.sql" -not -path "*/vendor/*" -not -path "*/.git/*" | while read -r sql_file; do
        filename=$(basename "$sql_file")
        cp "$sql_file" "$PACKAGE_DIR/database/$filename"
    done
    
    print_success "Database files copied"
}

# =================================================================
# CREA FILE DI CONFIGURAZIONE
# =================================================================

create_config_files() {
    print_header "Creating Configuration Files"
    
    # File di configurazione ambiente
    cat > "$PACKAGE_DIR/config/environment.conf" << 'EOF'
# =================================================================
# CRM ASContabilmente - Environment Configuration
# =================================================================

# Database Configuration
DB_HOST=localhost
DB_NAME=crm_ascontabilmente
DB_USER=crmuser
DB_PASS=Admin123!

# Web Configuration
DOMAIN=localhost
WEB_ROOT=/var/www/html/crm
SSL_ENABLED=false

# Node.js Configuration
NODE_PORT=3001
SOCKET_PORT=3001

# Email Configuration
SMTP_HOST=localhost
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM=noreply@localhost

# Telegram Configuration
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

# Google Calendar Configuration
GOOGLE_CALENDAR_ID=
GOOGLE_API_KEY=

# Security
SECRET_KEY=change_this_secret_key_in_production
SESSION_TIMEOUT=3600

# Backup Configuration
BACKUP_DIR=/var/backups/crm
BACKUP_RETENTION_DAYS=30
EOF

    # File di configurazione Apache
    cat > "$PACKAGE_DIR/config/apache-crm.conf" << 'EOF'
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/crm
    
    <Directory /var/www/html/crm>
        AllowOverride All
        Require all granted
        
        # Disable directory browsing
        Options -Indexes
        
        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
        Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'"
    </Directory>
    
    # Deny access to sensitive files
    <FilesMatch "\.(sql|log|ini|env)$">
        Require all denied
    </FilesMatch>
    
    # Deny access to sensitive directories
    <DirectoryMatch "/(includes|scripts|config|logs)">
        Require all denied
    </DirectoryMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/crm_error.log
    CustomLog ${APACHE_LOG_DIR}/crm_access.log combined
</VirtualHost>
EOF

    # File di configurazione PHP
    cat > "$PACKAGE_DIR/config/php-crm.ini" << 'EOF'
; CRM ASContabilmente - PHP Configuration

; File uploads
upload_max_filesize = 50M
post_max_size = 50M
max_file_uploads = 20

; Memory and execution
memory_limit = 256M
max_execution_time = 300
max_input_time = 300

; Session configuration
session.gc_probability = 1
session.gc_divisor = 100
session.gc_maxlifetime = 3600
session.cookie_httponly = 1
session.cookie_secure = 0
session.use_strict_mode = 1

; Error handling
display_errors = Off
log_errors = On
error_log = /var/log/php/crm_errors.log

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Date
date.timezone = Europe/Rome
EOF

    # Systemd service per Node.js
    cat > "$PACKAGE_DIR/config/crm-chat.service" << 'EOF'
[Unit]
Description=CRM Chat Service
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/crm
ExecStart=/usr/bin/node socket.js
Restart=always
RestartSec=10
Environment=NODE_ENV=production
Environment=PORT=3001

[Install]
WantedBy=multi-user.target
EOF

    # Crontab per manutenzione
    cat > "$PACKAGE_DIR/config/crm-crontab" << 'EOF'
# CRM ASContabilmente - Scheduled Tasks

# Backup giornaliero alle 2:00
0 2 * * * /var/www/html/crm/scripts/backup.sh > /dev/null 2>&1

# Pulizia log settimanale la domenica alle 3:00
0 3 * * 0 /var/www/html/crm/scripts/maintenance.sh cleanup > /dev/null 2>&1

# Controllo stato servizi ogni 5 minuti
*/5 * * * * /var/www/html/crm/scripts/maintenance.sh check > /dev/null 2>&1

# Sincronizzazione Google Calendar ogni ora
0 * * * * /usr/bin/php /var/www/html/crm/scripts/sync_calendar.php > /dev/null 2>&1
EOF

    print_success "Configuration files created"
}

# =================================================================
# CREA DOCUMENTAZIONE
# =================================================================

create_documentation() {
    print_header "Creating Documentation"
    
    # Copia documentazione esistente
    [[ -f "$SOURCE_DIR/README_INSTALLATION.md" ]] && cp "$SOURCE_DIR/README_INSTALLATION.md" "$PACKAGE_DIR/docs/"
    [[ -f "$SOURCE_DIR/README_CHAT_SYSTEM.md" ]] && cp "$SOURCE_DIR/README_CHAT_SYSTEM.md" "$PACKAGE_DIR/docs/"
    
    # Crea README principale del pacchetto
    cat > "$PACKAGE_DIR/README.md" << 'EOF'
# 🏢 CRM ASContabilmente - Complete Deployment Package

Questo pacchetto contiene tutto il necessario per installare il sistema CRM ASContabilmente su un nuovo server.

## 📦 Contenuto del Pacchetto

```
├── scripts/           # Script di installazione e manutenzione
│   ├── install.sh          # Installer automatico principale
│   ├── test_installation.sh # Suite di test e validazione
│   ├── backup.sh           # Script di backup automatico
│   ├── restore.sh          # Script di ripristino
│   └── maintenance.sh      # Script di manutenzione
├── database/          # Schema e dati database
│   ├── database_schema.sql # Schema completo database
│   └── *.sql              # Altri file SQL
├── config/            # File di configurazione
│   ├── environment.conf    # Configurazione ambiente
│   ├── apache-crm.conf     # Configurazione Apache
│   ├── php-crm.ini         # Configurazione PHP
│   ├── crm-chat.service    # Servizio systemd
│   └── crm-crontab         # Task schedulati
├── docs/              # Documentazione
│   ├── README_INSTALLATION.md
│   └── README_CHAT_SYSTEM.md
└── src/               # Codice sorgente completo
    ├── *.php              # File PHP principali
    ├── api/               # API endpoints
    ├── includes/          # File di inclusione
    ├── assets/            # Asset statici
    └── scripts/           # Script di utilità
```

## 🚀 Installazione Rapida

1. **Estrai il pacchetto:**
   ```bash
   tar -xzf crm-ascontabilmente-YYYYMMDD-HHMMSS.tar.gz
   cd crm-ascontabilmente-YYYYMMDD-HHMMSS
   ```

2. **Esegui l'installer:**
   ```bash
   sudo chmod +x scripts/install.sh
   sudo ./scripts/install.sh
   ```

3. **Testa l'installazione:**
   ```bash
   sudo ./scripts/test_installation.sh
   ```

## ⚙️ Configurazione

Modifica i file in `config/` secondo le tue esigenze prima dell'installazione:

- `environment.conf` - Configurazione principale
- `apache-crm.conf` - Virtual host Apache
- `php-crm.ini` - Impostazioni PHP

## 🔐 Credenziali di Default

**Database:**
- Host: localhost
- Database: crm_ascontabilmente
- User: crmuser
- Password: Admin123!

**Utenti di Default:**
- admin@crm.local / password
- dev@crm.local / password

**⚠️ IMPORTANTE:** Cambia tutte le password di default dopo l'installazione!

## 📚 Documentazione Completa

Consulta i file nella directory `docs/` per:
- Procedure di installazione dettagliate
- Configurazione sistema chat
- Risoluzione problemi comuni
- Procedure di backup e ripristino

## 🆘 Supporto

Per problemi o domande:
1. Consulta la documentazione in `docs/`
2. Esegui il test suite: `./scripts/test_installation.sh`
3. Controlla i log di sistema

## 📄 Licenza

Sistema CRM ASContabilmente - Uso interno
EOF

    # Crea changelog
    cat > "$PACKAGE_DIR/CHANGELOG.md" << 'EOF'
# Changelog

## [2.1.0] - 2025-01-XX

### Added
- Complete deployment package
- Automated installation script
- Comprehensive test suite
- Backup and restore system
- WhatsApp-like chat system
- Real-time notifications
- Google Calendar integration
- Telegram notifications

### Fixed
- Session management issues
- Database authentication problems
- Chat widget functionality
- Login redirect problems
- Disk space monitoring

### Security
- Enhanced authentication
- SQL injection protection
- XSS protection
- CSRF protection
- Secure headers implementation

### Performance
- Database query optimization
- Caching implementation
- Asset optimization
- Session cleanup automation
EOF

    print_success "Documentation created"
}

# =================================================================
# CREA SCRIPT DI POST-INSTALLAZIONE
# =================================================================

create_post_install_script() {
    print_header "Creating Post-Installation Script"
    
    cat > "$PACKAGE_DIR/scripts/post_install.sh" << 'EOF'
#!/bin/bash

# =================================================================
# CRM ASContabilmente - Post Installation Setup
# =================================================================

print_info() {
    echo -e "\033[0;34mℹ️ $1\033[0m"
}

print_success() {
    echo -e "\033[0;32m✅ $1\033[0m"
}

print_header() {
    echo -e "\n\033[0;34m=== $1 ===\033[0m"
}

print_header "Post Installation Setup"

# Configurazione finale
print_info "Configuring final settings..."

# Imposta permessi finali
chown -R www-data:www-data /var/www/html/crm
find /var/www/html/crm -type f -exec chmod 644 {} \;
find /var/www/html/crm -type d -exec chmod 755 {} \;
chmod 600 /var/www/html/crm/includes/config.php

# Avvia servizi
systemctl enable crm-chat
systemctl start crm-chat

# Aggiungi crontab
crontab -u www-data /var/www/html/crm/config/crm-crontab

# Test finale
print_info "Running final tests..."
/var/www/html/crm/scripts/test_installation.sh

print_success "Post-installation setup completed!"

echo ""
echo "🎉 CRM ASContabilmente is now ready!"
echo ""
echo "📱 Access your CRM at: http://$(hostname -I | awk '{print $1}')/crm"
echo ""
echo "🔐 Default credentials:"
echo "   Email: admin@crm.local"
echo "   Password: password"
echo ""
echo "⚠️  Remember to change default passwords!"
echo ""
EOF

    chmod +x "$PACKAGE_DIR/scripts/post_install.sh"
    
    print_success "Post-installation script created"
}

# =================================================================
# VALIDA PACCHETTO
# =================================================================

validate_package() {
    print_header "Validating Package"
    
    local errors=0
    
    # Controlla file essenziali
    essential_files=(
        "scripts/install.sh"
        "scripts/test_installation.sh"
        "database/database_schema.sql"
        "config/environment.conf"
        "src/index.php"
        "src/login.php"
        "README.md"
    )
    
    for file in "${essential_files[@]}"; do
        if [[ -f "$PACKAGE_DIR/$file" ]]; then
            print_success "✓ $file"
        else
            print_error "✗ $file missing"
            ((errors++))
        fi
    done
    
    # Controlla dimensione pacchetto
    package_size=$(du -sh "$PACKAGE_DIR" | cut -f1)
    print_info "Package size: $package_size"
    
    return $errors
}

# =================================================================
# CREA ARCHIVIO FINALE
# =================================================================

create_archive() {
    print_header "Creating Final Archive"
    
    cd /tmp
    
    # Crea archivio compresso
    tar -czf "$ARCHIVE_NAME" "$PACKAGE_NAME/"
    
    # Calcola checksum
    sha256sum "$ARCHIVE_NAME" > "$ARCHIVE_NAME.sha256"
    
    archive_size=$(du -sh "$ARCHIVE_NAME" | cut -f1)
    
    print_success "Archive created: /tmp/$ARCHIVE_NAME ($archive_size)"
    print_success "Checksum created: /tmp/$ARCHIVE_NAME.sha256"
    
    # Sposta archivio nel directory sorgente se possibile
    if [[ -w "$SOURCE_DIR" ]]; then
        mv "/tmp/$ARCHIVE_NAME" "$SOURCE_DIR/"
        mv "/tmp/$ARCHIVE_NAME.sha256" "$SOURCE_DIR/"
        print_success "Archive moved to: $SOURCE_DIR/$ARCHIVE_NAME"
    fi
}

# =================================================================
# MAIN EXECUTION
# =================================================================

main() {
    echo -e "${BLUE}"
    echo "==================================================================="
    echo "📦 CRM ASContabilmente - Deployment Package Creator"
    echo "==================================================================="
    echo -e "${NC}"
    
    print_info "Starting package creation..."
    print_info "Source directory: $SOURCE_DIR"
    print_info "Package name: $PACKAGE_NAME"
    
    # Esegui tutte le operazioni
    create_package_structure
    copy_source_files
    copy_installation_scripts
    copy_database_files
    create_config_files
    create_documentation
    create_post_install_script
    
    # Valida e crea archivio
    if validate_package; then
        create_archive
        
        echo ""
        print_success "Package creation completed successfully!"
        echo ""
        print_info "📦 Package: $ARCHIVE_NAME"
        print_info "📍 Location: $(pwd)/$ARCHIVE_NAME"
        echo ""
        print_info "📋 Next steps:"
        echo "   1. Transfer the archive to your target server"
        echo "   2. Extract: tar -xzf $ARCHIVE_NAME"
        echo "   3. Run: sudo ./scripts/install.sh"
        echo "   4. Test: sudo ./scripts/test_installation.sh"
        echo ""
    else
        print_error "Package validation failed!"
        exit 1
    fi
    
    # Cleanup temporary directory
    [[ -d "$PACKAGE_DIR" ]] && rm -rf "$PACKAGE_DIR"
}

# Esegui se chiamato direttamente
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
