#!/bin/bash

# =================================================================
# 🧹 CRM ASContabilmente - Script di Manutenzione
# =================================================================

CRM_DIR="/var/www/CRM"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"

echo "🧹 Avvio manutenzione CRM - $(date)"

# 1. Pulizia log files
echo "📝 Pulizia log files..."
find $CRM_DIR/logs -name "*.log" -mtime +30 -delete 2>/dev/null || true
find /var/log/apache2 -name "crm_*.log.*" -mtime +7 -delete 2>/dev/null || true

# 2. Pulizia sessioni PHP vecchie
echo "🗂️  Pulizia sessioni PHP..."
find /var/lib/php/sessions -name "sess_*" -mtime +1 -delete 2>/dev/null || true

# 3. Ottimizzazione database
echo "🗃️  Ottimizzazione database..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME <<EOF
OPTIMIZE TABLE chat_messages;
OPTIMIZE TABLE chat_conversations;
OPTIMIZE TABLE utenti;
OPTIMIZE TABLE clienti;
EOF

# 4. Pulizia cache Composer
echo "📦 Pulizia cache Composer..."
cd $CRM_DIR
composer clear-cache 2>/dev/null || true

# 5. Pulizia npm cache
echo "📦 Pulizia cache npm..."
npm cache clean --force 2>/dev/null || true

# 6. Verifica spazio disco
echo "💾 Verifica spazio disco..."
df -h | grep -E "(Filesystem|/dev/)"

# 7. Verifica servizi
echo "🔧 Verifica servizi..."
systemctl is-active apache2 >/dev/null && echo "✅ Apache2: OK" || echo "❌ Apache2: FAILED"
systemctl is-active mysql >/dev/null && echo "✅ MySQL: OK" || echo "❌ MySQL: FAILED"
systemctl is-active crm-chat >/dev/null && echo "✅ CRM Chat: OK" || echo "❌ CRM Chat: FAILED"

# 8. Update sistema (solo security)
echo "🔒 Aggiornamenti sicurezza..."
apt update >/dev/null 2>&1
apt list --upgradable 2>/dev/null | grep -i security | wc -l | xargs echo "Aggiornamenti sicurezza disponibili:"

echo "✅ Manutenzione completata - $(date)"
