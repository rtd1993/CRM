#!/bin/bash

# =================================================================
# 🔧 CRM ASContabilmente - Sistema di Backup Completo
# =================================================================

set -e

# Configurazione
BACKUP_DIR="/var/backups/crm"
CRM_DIR="/var/www/CRM"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"
DATE=$(date +%Y%m%d_%H%M%S)

# Crea directory backup se non esiste
mkdir -p $BACKUP_DIR

echo "🔄 Avvio backup CRM - $DATE"

# 1. Backup Database
echo "📊 Backup database..."
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/database_$DATE.sql

# 2. Backup Files
echo "📂 Backup files applicazione..."
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C /var/www CRM \
    --exclude='CRM/node_modules' \
    --exclude='CRM/vendor' \
    --exclude='CRM/logs/*.log' \
    --exclude='CRM/.git'

# 3. Backup Configurazioni
echo "⚙️ Backup configurazioni sistema..."
tar -czf $BACKUP_DIR/config_$DATE.tar.gz \
    /etc/apache2/sites-available/crm.conf \
    /etc/systemd/system/crm-chat.service \
    /etc/php/*/apache2/conf.d/99-crm.ini

# 4. Pulizia backup vecchi (>7 giorni)
echo "🧹 Pulizia backup vecchi..."
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "✅ Backup completato!"
echo "📁 Files salvati in: $BACKUP_DIR"
ls -lh $BACKUP_DIR/*$DATE*
