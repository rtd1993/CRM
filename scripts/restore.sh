#!/bin/bash

# =================================================================
# 🔄 CRM ASContabilmente - Restore da Backup
# =================================================================

set -e

BACKUP_DIR="/var/backups/crm"
CRM_DIR="/var/www/CRM"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"

# Verifica parametri
if [ -z "$1" ]; then
    echo "Uso: $0 <data_backup>"
    echo "Esempio: $0 20241208_143000"
    echo ""
    echo "Backup disponibili:"
    ls -1 $BACKUP_DIR/database_*.sql | sed 's/.*database_//' | sed 's/.sql$//' | sort -r | head -10
    exit 1
fi

DATE=$1

# Verifica files di backup
DATABASE_FILE="$BACKUP_DIR/database_$DATE.sql"
FILES_BACKUP="$BACKUP_DIR/files_$DATE.tar.gz"
CONFIG_BACKUP="$BACKUP_DIR/config_$DATE.tar.gz"

if [ ! -f "$DATABASE_FILE" ]; then
    echo "❌ File database backup non trovato: $DATABASE_FILE"
    exit 1
fi

if [ ! -f "$FILES_BACKUP" ]; then
    echo "❌ File applicazione backup non trovato: $FILES_BACKUP"
    exit 1
fi

echo "🔄 Restore CRM dal backup $DATE"
echo "⚠️  ATTENZIONE: Questa operazione sovrascriverà il sistema attuale!"
read -p "Continuare? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Operazione annullata."
    exit 1
fi

# 1. Stop servizi
echo "⏹️  Stop servizi..."
systemctl stop apache2
systemctl stop crm-chat 2>/dev/null || true

# 2. Backup sistema attuale
echo "💾 Backup sistema attuale..."
if [ -d "$CRM_DIR" ]; then
    mv "$CRM_DIR" "${CRM_DIR}.pre-restore.$(date +%Y%m%d_%H%M%S)"
fi

# 3. Restore database
echo "📊 Restore database..."
mysql -u root -p -e "DROP DATABASE IF EXISTS $DB_NAME;"
mysql -u root -p -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p $DB_NAME < $DATABASE_FILE

# 4. Restore files
echo "📂 Restore files applicazione..."
cd /var/www
tar -xzf $FILES_BACKUP

# 5. Restore configurazioni
echo "⚙️ Restore configurazioni..."
if [ -f "$CONFIG_BACKUP" ]; then
    cd /
    tar -xzf $CONFIG_BACKUP
fi

# 6. Ripristina permessi
echo "🔒 Ripristino permessi..."
chown -R www-data:www-data $CRM_DIR
chmod -R 755 $CRM_DIR
chmod -R 777 $CRM_DIR/logs

# 7. Reinstalla dipendenze
echo "📦 Reinstallazione dipendenze..."
cd $CRM_DIR
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi
if [ -f "package.json" ]; then
    npm install --production
fi

# 8. Restart servizi
echo "▶️  Restart servizi..."
systemctl daemon-reload
systemctl start apache2
systemctl start crm-chat 2>/dev/null || true

echo ""
echo "✅ Restore completato!"
echo "🌐 Sistema disponibile su: http://$(hostname -I | awk '{print $1}')"
echo "📅 Backup ripristinato: $DATE"
