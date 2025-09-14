#!/bin/bash

# Script pulizia log mensile
# Eseguito il primo giorno di ogni mese alle 04:00
# Mantiene solo gli ultimi 30 giorni di log

LOG_FILE="/var/www/CRM/logs/log_cleanup_monthly.log"
LOG_DIR="/var/www/CRM/logs"
APACHE_LOG_DIR="/var/log/apache2"
MYSQL_LOG_DIR="/var/log/mysql"

# Funzione di logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Crea directory log se non esiste
mkdir -p "$LOG_DIR"

log_message "=== INIZIO PULIZIA LOG MENSILE ==="

# Conta dimensione log prima della pulizia
LOG_SIZE_BEFORE=$(du -sh "$LOG_DIR" 2>/dev/null | cut -f1)
log_message "📊 Dimensione log CRM prima della pulizia: $LOG_SIZE_BEFORE"

# 1. Pulizia log CRM più vecchi di 30 giorni
log_message "--- PULIZIA LOG CRM ---"
OLD_LOGS=$(find "$LOG_DIR" -name "*.log" -type f -mtime +30 2>/dev/null | wc -l)

if [ "$OLD_LOGS" -gt 0 ]; then
    find "$LOG_DIR" -name "*.log" -type f -mtime +30 -delete
    log_message "✅ Eliminati $OLD_LOGS file di log CRM più vecchi di 30 giorni"
else
    log_message "✅ Nessun file di log CRM da eliminare"
fi

# 2. Pulizia log Apache più vecchi di 30 giorni
log_message "--- PULIZIA LOG APACHE ---"
if [ -d "$APACHE_LOG_DIR" ]; then
    APACHE_LOGS=$(find "$APACHE_LOG_DIR" -name "*.log*" -type f -mtime +30 2>/dev/null | wc -l)
    
    if [ "$APACHE_LOGS" -gt 0 ]; then
        find "$APACHE_LOG_DIR" -name "*.log*" -type f -mtime +30 -delete 2>/dev/null
        log_message "✅ Eliminati $APACHE_LOGS file di log Apache più vecchi di 30 giorni"
    else
        log_message "✅ Nessun file di log Apache da eliminare"
    fi
else
    log_message "⚠️ Directory log Apache non trovata"
fi

# 3. Pulizia log MySQL più vecchi di 30 giorni
log_message "--- PULIZIA LOG MYSQL ---"
if [ -d "$MYSQL_LOG_DIR" ]; then
    MYSQL_LOGS=$(find "$MYSQL_LOG_DIR" -name "*.log*" -type f -mtime +30 2>/dev/null | wc -l)
    
    if [ "$MYSQL_LOGS" -gt 0 ]; then
        find "$MYSQL_LOG_DIR" -name "*.log*" -type f -mtime +30 -delete 2>/dev/null
        log_message "✅ Eliminati $MYSQL_LOGS file di log MySQL più vecchi di 30 giorni"
    else
        log_message "✅ Nessun file di log MySQL da eliminare"
    fi
else
    log_message "⚠️ Directory log MySQL non trovata"
fi

# 4. Pulizia log di sistema più vecchi di 30 giorni
log_message "--- PULIZIA LOG SISTEMA ---"
SYSTEM_LOGS=$(find /var/log -name "*.log*" -type f -mtime +30 2>/dev/null | wc -l)

if [ "$SYSTEM_LOGS" -gt 0 ]; then
    find /var/log -name "*.log*" -type f -mtime +30 -delete 2>/dev/null
    log_message "✅ Eliminati $SYSTEM_LOGS file di log sistema più vecchi di 30 giorni"
else
    log_message "✅ Nessun file di log sistema da eliminare"
fi

# 5. Pulizia file temporanei PHP
log_message "--- PULIZIA FILE TEMPORANEI ---"
TEMP_FILES=0

# Sessioni PHP scadute
if [ -d "/tmp" ]; then
    TEMP_SESSION=$(find /tmp -name "sess_*" -type f -mtime +7 2>/dev/null | wc -l)
    find /tmp -name "sess_*" -type f -mtime +7 -delete 2>/dev/null
    TEMP_FILES=$((TEMP_FILES + TEMP_SESSION))
fi

if [ -d "/var/lib/php/sessions" ]; then
    TEMP_SESSION2=$(find /var/lib/php/sessions -name "sess_*" -type f -mtime +7 2>/dev/null | wc -l)
    find /var/lib/php/sessions -name "sess_*" -type f -mtime +7 -delete 2>/dev/null
    TEMP_FILES=$((TEMP_FILES + TEMP_SESSION2))
fi

# File temporanei vari
TEMP_MISC=$(find /tmp -type f -mtime +7 2>/dev/null | wc -l)
find /tmp -type f -mtime +7 -delete 2>/dev/null
TEMP_FILES=$((TEMP_FILES + TEMP_MISC))

log_message "✅ Eliminati $TEMP_FILES file temporanei"

# 6. Rotazione log correnti troppo grandi (>100MB)
log_message "--- ROTAZIONE LOG GRANDI ---"
ROTATED=0

find "$LOG_DIR" -name "*.log" -type f -size +100M -exec bash -c '
    for file; do
        if [ -f "$file" ]; then
            mv "$file" "${file}.$(date +%Y%m%d_%H%M%S).old"
            touch "$file"
            chmod 644 "$file"
            echo "Ruotato: $(basename "$file")"
        fi
    done
' bash {} +

# Conta dimensione log dopo la pulizia
LOG_SIZE_AFTER=$(du -sh "$LOG_DIR" 2>/dev/null | cut -f1)
log_message "📊 Dimensione log CRM dopo la pulizia: $LOG_SIZE_AFTER"

# 7. Spazio disco liberato
DISK_USAGE=$(df -h /var | tail -1 | awk '{print $5}')
log_message "💾 Utilizzo disco attuale: $DISK_USAGE"

log_message "✅ PULIZIA LOG MENSILE COMPLETATA"
log_message "================================================="
