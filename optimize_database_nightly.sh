#!/bin/bash

# Script di ottimizzazione notturna del database CRM
# Eseguito automaticamente ogni notte alle 02:00 via cron
# Ottimizza tutte le tabelle e pulisce log vecchi

LOG_FILE="/var/www/CRM/logs/database_optimization_nightly.log"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funzione di logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Crea directory log se non esiste
mkdir -p /var/www/CRM/logs

log_message "=== INIZIO OTTIMIZZAZIONE NOTTURNA DATABASE ==="

# Verifica connessione database
if ! mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "SELECT 1;" > /dev/null 2>&1; then
    log_message "❌ ERRORE: Impossibile connettersi al database"
    exit 1
fi

log_message "✅ Connessione database stabilita"

# === ANALISI SPAZIO TABELLE PRIMA DELL'OTTIMIZZAZIONE ===
log_message "--- ANALISI SPAZIO TABELLE (PRIMA) ---"

SPAZIO_PRIMA=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
    FROM information_schema.tables 
    WHERE table_schema = '$DB_NAME'
")

log_message "📊 Spazio totale database prima dell'ottimizzazione: ${SPAZIO_PRIMA} MB"

# === OTTIMIZZAZIONE TABELLE PRINCIPALI ===
log_message "--- OTTIMIZZAZIONE TABELLE PRINCIPALI ---"

TABELLE=(
    "utenti"
    "clienti" 
    "task"
    "task_clienti"
    "enea"
    "conto_termico"
    "conversations"
    "conversation_participants"
    "messages"
    "email_cronologia"
    "email_templates"
)

for tabella in "${TABELLE[@]}"; do
    log_message "🔧 Ottimizzazione tabella: $tabella"
    
    # Conta righe prima
    RIGHE_PRIMA=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "SELECT COUNT(*) FROM $tabella" 2>/dev/null)
    
    if [ $? -eq 0 ]; then
        # Ottimizza la tabella
        mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "OPTIMIZE TABLE $tabella;" > /dev/null 2>&1
        
        if [ $? -eq 0 ]; then
            # Conta righe dopo
            RIGHE_DOPO=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "SELECT COUNT(*) FROM $tabella" 2>/dev/null)
            log_message "   ✅ $tabella: $RIGHE_DOPO righe, ottimizzata"
        else
            log_message "   ❌ Errore nell'ottimizzazione di $tabella"
        fi
    else
        log_message "   ⚠️  Tabella $tabella non trovata o inaccessibile"
    fi
done

# === AGGIORNAMENTO STATISTICHE TABELLE ===
log_message "--- AGGIORNAMENTO STATISTICHE ---"

mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "ANALYZE TABLE $(IFS=,; echo "${TABELLE[*]}");" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    log_message "✅ Statistiche tabelle aggiornate"
else
    log_message "⚠️ Errore nell'aggiornamento statistiche"
fi

# === PULIZIA INDICI FRAMMENTATI ===
log_message "--- RICOSTRUZIONE INDICI FRAMMENTATI ---"

# Identifica tabelle con frammentazione degli indici > 10%
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "
    SELECT 
        table_name,
        ROUND(((data_length + index_length) / 1024 / 1024), 2) as 'Size_MB',
        ROUND((data_free / (data_length + index_length + data_free)) * 100, 2) as 'Fragmentation_%'
    FROM information_schema.tables 
    WHERE table_schema = '$DB_NAME' 
    AND data_free > 0
    AND ((data_free / (data_length + index_length + data_free)) * 100) > 10
    ORDER BY Fragmentation_% DESC;
" > /tmp/fragmented_tables.txt 2>/dev/null

if [ -s /tmp/fragmented_tables.txt ]; then
    log_message "🔍 Tabelle frammentate trovate:"
    while read line; do
        if [[ $line == *"_"* ]] && [[ $line != *"table_name"* ]]; then
            TABLE_NAME=$(echo $line | awk '{print $1}')
            FRAGMENTATION=$(echo $line | awk '{print $3}')
            log_message "   - $TABLE_NAME (frammentazione: ${FRAGMENTATION}%)"
        fi
    done < /tmp/fragmented_tables.txt
else
    log_message "✅ Nessuna tabella significativamente frammentata"
fi

rm -f /tmp/fragmented_tables.txt

# === PULIZIA LOG VECCHI ===
log_message "--- PULIZIA LOG VECCHI ---"

# Pulisce log più vecchi di 30 giorni
LOG_ELIMINATI=0
find /var/www/CRM/logs -name "*.log" -mtime +30 -type f | while read log_file; do
    if [ -f "$log_file" ]; then
        rm -f "$log_file"
        ((LOG_ELIMINATI++))
    fi
done

log_message "🧹 Log più vecchi di 30 giorni eliminati"

# === PULIZIA SESSIONI SCADUTE ===
log_message "--- PULIZIA SESSIONI SCADUTE ---"

# Pulisce file di sessione PHP più vecchi di 7 giorni
find /tmp -name "sess_*" -mtime +7 -type f -delete 2>/dev/null
find /var/lib/php/sessions -name "sess_*" -mtime +7 -type f -delete 2>/dev/null

log_message "🧹 Sessioni PHP scadute eliminate"

# === PULIZIA UTENTI OFFLINE ===
log_message "--- PULIZIA UTENTI OFFLINE ---"

# Imposta offline gli utenti inattivi da più di 10 minuti
OFFLINE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
    SELECT COUNT(*) FROM utenti 
    WHERE is_online = 1 AND updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
")

if [ "$OFFLINE_COUNT" -gt 0 ]; then
    mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "
        UPDATE utenti SET is_online = FALSE 
        WHERE is_online = 1 AND updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    "
    log_message "🔄 $OFFLINE_COUNT utenti impostati offline (inattivi > 10 minuti)"
else
    log_message "✅ Nessun utente da impostare offline"
fi

# === BACKUP ROTAZIONE ===
log_message "--- GESTIONE BACKUP ---"

# Crea backup compresso delle tabelle principali (solo struttura per velocità)
BACKUP_DIR="/var/www/CRM/backups"
mkdir -p "$BACKUP_DIR"

BACKUP_FILE="$BACKUP_DIR/structure_backup_$(date +%Y%m%d_%H%M%S).sql"

mysqldump -u "$DB_USER" -p"$DB_PASS" --no-data --routines --triggers "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null

if [ $? -eq 0 ]; then
    gzip "$BACKUP_FILE"
    log_message "💾 Backup struttura database creato: ${BACKUP_FILE}.gz"
    
    # Mantieni solo gli ultimi 7 backup di struttura
    find "$BACKUP_DIR" -name "structure_backup_*.sql.gz" -mtime +7 -delete
    log_message "🧹 Backup struttura più vecchi di 7 giorni eliminati"
else
    log_message "❌ Errore nella creazione backup struttura"
fi

# === ANALISI SPAZIO FINALE ===
log_message "--- ANALISI SPAZIO TABELLE (DOPO) ---"

SPAZIO_DOPO=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
    FROM information_schema.tables 
    WHERE table_schema = '$DB_NAME'
")

SPAZIO_LIBERATO=$(echo "$SPAZIO_PRIMA - $SPAZIO_DOPO" | bc -l)

log_message "📊 Spazio totale database dopo l'ottimizzazione: ${SPAZIO_DOPO} MB"
log_message "💾 Spazio liberato: ${SPAZIO_LIBERATO} MB"

# === STATISTICHE PERFORMANCE ===
log_message "--- STATISTICHE PERFORMANCE DATABASE ---"

# Query lente
SLOW_QUERIES=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW GLOBAL STATUS LIKE 'Slow_queries';" | tail -1 | awk '{print $2}')

# Connessioni
MAX_CONNECTIONS=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW VARIABLES LIKE 'max_connections';" | tail -1 | awk '{print $2}')
CURRENT_CONNECTIONS=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW STATUS LIKE 'Threads_connected';" | tail -1 | awk '{print $2}')

# Cache hit ratio
KEY_READS=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW STATUS LIKE 'Key_reads';" | tail -1 | awk '{print $2}')
KEY_READ_REQUESTS=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW STATUS LIKE 'Key_read_requests';" | tail -1 | awk '{print $2}')

if [ "$KEY_READ_REQUESTS" -gt 0 ]; then
    KEY_CACHE_HIT_RATIO=$(echo "scale=2; (1 - $KEY_READS / $KEY_READ_REQUESTS) * 100" | bc -l)
else
    KEY_CACHE_HIT_RATIO="100.00"
fi

log_message "📈 METRICHE PERFORMANCE:"
log_message "   - Query lente: $SLOW_QUERIES"
log_message "   - Connessioni: $CURRENT_CONNECTIONS/$MAX_CONNECTIONS"
log_message "   - Cache hit ratio: ${KEY_CACHE_HIT_RATIO}%"

# === VERIFICA INTEGRITÀ ===
log_message "--- VERIFICA INTEGRITÀ TABELLE ---"

TABELLE_CORROTTE=0
for tabella in "${TABELLE[@]}"; do
    CHECK_RESULT=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "CHECK TABLE $tabella;" 2>/dev/null | grep -c "OK")
    if [ "$CHECK_RESULT" -eq 0 ]; then
        log_message "⚠️ Possibile corruzione in tabella: $tabella"
        ((TABELLE_CORROTTE++))
    fi
done

if [ "$TABELLE_CORROTTE" -eq 0 ]; then
    log_message "✅ Tutte le tabelle sono integre"
else
    log_message "⚠️ $TABELLE_CORROTTE tabelle richiedono attenzione"
fi

# === RIEPILOGO FINALE ===
DURATA=$(($(date +%s) - $(date -d "$(head -1 $LOG_FILE | sed 's/.*\[\(.*\)\].*/\1/')" +%s)))

log_message "🎯 RIEPILOGO OTTIMIZZAZIONE NOTTURNA:"
log_message "   - Tabelle ottimizzate: ${#TABELLE[@]}"
log_message "   - Spazio liberato: ${SPAZIO_LIBERATO} MB"
log_message "   - Durata: ${DURATA} secondi"
log_message "   - Stato: $([ "$TABELLE_CORROTTE" -eq 0 ] && echo "✅ SUCCESSO" || echo "⚠️ CON AVVISI")"

log_message "=== FINE OTTIMIZZAZIONE NOTTURNA DATABASE ==="

# Rotazione del log stesso se troppo grande (>10MB)
LOG_SIZE=$(du -m "$LOG_FILE" 2>/dev/null | cut -f1)
if [ "$LOG_SIZE" -gt 10 ]; then
    mv "$LOG_FILE" "${LOG_FILE}.old"
    log_message "🔄 Log ruotato per dimensione (${LOG_SIZE}MB)"
fi

exit 0
