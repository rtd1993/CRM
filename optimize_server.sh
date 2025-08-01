#!/bin/bash

# Script di Ottimizzazione Completa del Server CRM
# Ottimizza prestazioni, libera spazio e rimuove servizi inutili

LOG_FILE="/var/www/CRM/logs/server_optimization.log"
BACKUP_DIR="/var/www/CRM/backups/system_backup_$(date +%Y%m%d_%H%M%S)"

# Funzione di logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Crea directory necessarie
mkdir -p /var/www/CRM/logs
mkdir -p /var/www/CRM/backups

log_message "=== INIZIO OTTIMIZZAZIONE SERVER CRM ==="

# 1. PULIZIA FILE DI LOG GRANDI
log_message "🧹 Pulizia file di log..."

# Backup e riduzione syslog
if [ -f "/var/log/syslog" ] && [ $(stat --format=%s "/var/log/syslog") -gt 104857600 ]; then
    log_message "📦 Backup e compressione syslog ($(du -h /var/log/syslog | cut -f1))"
    gzip -c /var/log/syslog > "$BACKUP_DIR/syslog_backup_$(date +%Y%m%d).gz"
    tail -n 1000 /var/log/syslog > /tmp/syslog_temp
    sudo mv /tmp/syslog_temp /var/log/syslog
    sudo chown syslog:adm /var/log/syslog
    log_message "✅ Syslog ottimizzato - mantenute ultime 1000 righe"
fi

# Pulizia journal systemd
log_message "🗂️ Pulizia journal systemd..."
JOURNAL_SIZE=$(du -sm /var/log/journal | cut -f1)
if [ "$JOURNAL_SIZE" -gt 100 ]; then
    log_message "📦 Journal troppo grande (${JOURNAL_SIZE}MB), pulizia in corso..."
    journalctl --vacuum-time=7d
    journalctl --vacuum-size=50M
    log_message "✅ Journal ottimizzato - mantenuti ultimi 7 giorni max 50MB"
fi

# 2. RIMOZIONE SERVIZI INUTILI
log_message "⚙️ Ottimizzazione servizi di sistema..."

# Disabilita servizi non necessari per server CRM
SERVIZI_DA_DISABILITARE=("ModemManager" "fwupd" "snap.cups.cupsd" "snap.cups.cups-browsed")

for servizio in "${SERVIZI_DA_DISABILITARE[@]}"; do
    if systemctl is-active --quiet "$servizio"; then
        log_message "🔴 Disabilitazione $servizio..."
        systemctl stop "$servizio"
        systemctl disable "$servizio"
        log_message "✅ $servizio disabilitato"
    fi
done

# 3. OTTIMIZZAZIONE APACHE
log_message "🌐 Ottimizzazione Apache..."

# Backup configurazione Apache
cp /etc/apache2/apache2.conf "$BACKUP_DIR/apache2.conf.backup"

# Ottimizzazioni Apache per server con 4GB RAM
cat > /etc/apache2/conf-available/performance.conf << 'EOF'
# Ottimizzazioni prestazioni Apache per CRM

# Modulo prefork ottimizzato per 4GB RAM
<IfModule mpm_prefork_module>
    StartServers             4
    MinSpareServers          4
    MaxSpareServers          8
    MaxRequestWorkers        50
    MaxConnectionsPerChild   1000
</IfModule>

# Compressione
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache headers
<IfModule mod_headers.c>
    <FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
        Header set Cache-Control "max-age=2592000, public"
    </FilesMatch>
</IfModule>

# Timeout ottimizzato
Timeout 30
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 2
EOF

a2enconf performance
a2enmod deflate headers expires
log_message "✅ Apache ottimizzato"

# 4. PULIZIA SERVIZI FALLITI
log_message "🔧 Rimozione servizi falliti..."

# Reset servizi falliti
systemctl reset-failed

# Rimuovi servizi obsoleti
SERVIZI_OBSOLETI=("socketjs.service" "rclone-watch.service")
for servizio in "${SERVIZI_OBSOLETI[@]}"; do
    if systemctl list-unit-files | grep -q "$servizio"; then
        log_message "🗑️ Rimozione $servizio obsoleto..."
        systemctl stop "$servizio" 2>/dev/null
        systemctl disable "$servizio" 2>/dev/null
        rm -f "/etc/systemd/system/$servizio"
        log_message "✅ $servizio rimosso"
    fi
done

systemctl daemon-reload

# 5. OTTIMIZZAZIONE CRON
log_message "⏰ Ottimizzazione processi cron..."

# Verifica che rclone non giri troppo spesso
CURRENT_CRON=$(crontab -l | grep rclone | head -1)
if echo "$CURRENT_CRON" | grep -q "\*/5"; then
    log_message "📊 Ottimizzazione frequenza rclone sync da 5 minuti a 15 minuti..."
    (crontab -l | sed 's/\*\/5 \* \* \* \* .*rclone.*/\*\/15 * * * * \/var\/www\/CRM\/sync_rclone.sh/') | crontab -
    log_message "✅ Rclone sync ottimizzato - ogni 15 minuti invece di 5"
fi

# 6. PULIZIA CACHE E FILE TEMPORANEI
log_message "🧽 Pulizia cache e file temporanei..."

# Pulizia package cache
apt-get autoremove -y
apt-get autoclean

# Pulizia thumbnail cache
rm -rf /home/*/.cache/thumbnails/* 2>/dev/null
rm -rf /root/.cache/* 2>/dev/null

# Pulizia temp files
find /tmp -type f -atime +7 -delete 2>/dev/null
find /var/tmp -type f -atime +7 -delete 2>/dev/null

log_message "✅ Cache e file temporanei puliti"

# 7. OTTIMIZZAZIONE MYSQL (se non già fatto)
log_message "🗄️ Verifica ottimizzazioni MySQL..."

if ! grep -q "innodb_buffer_pool_size = 1G" /etc/mysql/mysql.conf.d/mysqld.cnf; then
    log_message "⚠️ MySQL non ottimizzato, esegui optimize_mysql.sh"
else
    log_message "✅ MySQL già ottimizzato"
fi

# 8. CONFIGURAZIONE SWAP (se necessario)
log_message "💾 Verifica configurazione swap..."

if [ $(free | grep Swap | awk '{print $2}') -eq 0 ]; then
    log_message "⚠️ Nessuno swap configurato - creazione swap da 1GB..."
    
    # Crea file swap da 1GB
    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    
    # Rendi permanente
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    
    # Ottimizza swappiness
    echo 'vm.swappiness=10' >> /etc/sysctl.conf
    sysctl vm.swappiness=10
    
    log_message "✅ Swap 1GB creato e ottimizzato"
else
    log_message "ℹ️ Swap già configurato"
fi

# 9. PULIZIA SNAP PACKAGES (se non utilizzati)
log_message "📦 Verifica snap packages..."

SNAP_COUNT=$(snap list 2>/dev/null | wc -l)
if [ "$SNAP_COUNT" -gt 1 ] && [ "$SNAP_COUNT" -lt 10 ]; then
    log_message "📊 Trovati $SNAP_COUNT snap packages"
    # Mantieni solo cups se presente, rimuovi altri se non essenziali
    log_message "ℹ️ Snap packages mantenuti (cups necessario per stampa)"
else
    log_message "ℹ️ Configurazione snap appropriata"
fi

# 10. OTTIMIZZAZIONE RETE
log_message "🌐 Ottimizzazione parametri di rete..."

# Ottimizzazioni TCP per server web
cat >> /etc/sysctl.conf << 'EOF'

# Ottimizzazioni rete per server CRM
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 87380 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216
net.ipv4.tcp_congestion_control = bbr
net.core.netdev_max_backlog = 5000
EOF

sysctl -p

log_message "✅ Parametri di rete ottimizzati"

# RESTART SERVIZI OTTIMIZZATI
log_message "🔄 Restart servizi ottimizzati..."

systemctl restart apache2
systemctl restart mysql

log_message "✅ Servizi riavviati"

# STATISTICHE FINALI
log_message "📊 Statistiche post-ottimizzazione:"

DISK_FREE=$(df -h / | awk 'NR==2 {print $4}')
MEM_FREE=$(free -h | awk 'NR==2 {print $7}')
SERVICES_ACTIVE=$(systemctl list-units --type=service --state=running | wc -l)

log_message "💾 Spazio disco libero: $DISK_FREE"
log_message "🧠 Memoria disponibile: $MEM_FREE"
log_message "⚙️ Servizi attivi: $SERVICES_ACTIVE"

# Calcola spazio risparmiato
SPACE_SAVED=$(du -sm "$BACKUP_DIR" 2>/dev/null | cut -f1 || echo "0")
log_message "🗜️ Spazio complessivo ottimizzato: ~${SPACE_SAVED}MB + journal + cache"

log_message "=== OTTIMIZZAZIONE SERVER COMPLETATA ==="
log_message "📁 Backup salvati in: $BACKUP_DIR"
log_message "📝 Log completo: $LOG_FILE"

echo ""
echo "🎉 Ottimizzazione completata!"
echo "📊 Controlla le statistiche nel log: $LOG_FILE"
echo "🔄 Riavvio consigliato per applicare tutte le ottimizzazioni"

exit 0
