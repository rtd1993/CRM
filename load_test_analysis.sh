#!/bin/bash

# Script di Analisi Capacità Server CRM
# Valuta se il server può gestire 100 clienti con 6 utenti simultanei

LOG_FILE="/var/www/CRM/logs/load_analysis.log"

# Funzione di logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

mkdir -p /var/www/CRM/logs

log_message "=== ANALISI CAPACITÀ SERVER CRM ==="

# 1. SPECIFICHE HARDWARE ATTUALI
log_message "📊 Specifiche Hardware:"

CPU_CORES=$(nproc)
CPU_MODEL=$(lscpu | grep "Model name" | awk -F: '{print $2}' | xargs)
TOTAL_RAM=$(free -h | awk 'NR==2{print $2}')
AVAILABLE_RAM=$(free -h | awk 'NR==2{print $7}')
DISK_TOTAL=$(df -h / | awk 'NR==2{print $2}')
DISK_FREE=$(df -h / | awk 'NR==2{print $4}')

log_message "🖥️ CPU: $CPU_CORES cores - $CPU_MODEL"
log_message "🧠 RAM: $TOTAL_RAM totale, $AVAILABLE_RAM disponibile"
log_message "💾 Disco: $DISK_FREE liberi su $DISK_TOTAL"

# 2. ANALISI DATABASE ATTUALE
log_message "🗄️ Analisi Database Attuale:"

CURRENT_CLIENTS=$(mysql -u crmuser -p'Admin123!' crm -e "SELECT COUNT(*) FROM clienti;" -s -N 2>/dev/null)
CURRENT_USERS=$(mysql -u crmuser -p'Admin123!' crm -e "SELECT COUNT(*) FROM utenti;" -s -N 2>/dev/null)
CURRENT_TASKS=$(mysql -u crmuser -p'Admin123!' crm -e "SELECT COUNT(*) FROM task;" -s -N 2>/dev/null)
CURRENT_CHAT=$(mysql -u crmuser -p'Admin123!' crm -e "SELECT COUNT(*) FROM chat_messaggi;" -s -N 2>/dev/null)
DB_SIZE=$(mysql -u crmuser -p'Admin123!' crm -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb FROM information_schema.tables WHERE table_schema = 'crm';" -s -N 2>/dev/null)

log_message "👥 Clienti attuali: $CURRENT_CLIENTS"
log_message "🔑 Utenti sistema: $CURRENT_USERS"
log_message "📋 Task totali: $CURRENT_TASKS"
log_message "💬 Messaggi chat: $CURRENT_CHAT"
log_message "📊 Dimensione DB: ${DB_SIZE}MB"

# 3. PROIEZIONI PER 100 CLIENTI E 6 UTENTI
log_message "🎯 Proiezioni per 100 Clienti + 6 Utenti Simultanei:"

# Calcoli proiezioni
PROJECTED_DB_SIZE=$(echo "$DB_SIZE * 100" | bc -l | cut -d. -f1)
PROJECTED_CONCURRENT_SESSIONS=6
PROJECTED_DAILY_TASKS=$((100 * 5))  # 5 task per cliente al giorno
PROJECTED_DAILY_CHAT=$((6 * 50))    # 50 messaggi per utente al giorno

log_message "📈 Database proiettato: ~${PROJECTED_DB_SIZE}MB"
log_message "🔄 Sessioni simultanee: $PROJECTED_CONCURRENT_SESSIONS utenti"
log_message "📋 Task giornalieri stimati: $PROJECTED_DAILY_TASKS"
log_message "💬 Messaggi chat giornalieri: $PROJECTED_DAILY_CHAT"

# 4. CALCOLO RISORSE NECESSARIE
log_message "🔧 Calcolo Risorse Necessarie:"

# Stima RAM per 100 clienti (assumendo crescita lineare + overhead)
MYSQL_CURRENT_RAM=$(ps aux | grep mysql | grep -v grep | awk '{sum += $6} END {print sum/1024}' | cut -d. -f1)
APACHE_CURRENT_RAM=$(ps aux | grep apache2 | grep -v grep | awk '{sum += $6} END {print sum/1024}' | cut -d. -f1)
NODE_CURRENT_RAM=$(ps aux | grep "socket.js" | grep -v grep | awk '{print $6/1024}' | cut -d. -f1)

ESTIMATED_MYSQL_RAM=$((MYSQL_CURRENT_RAM + (PROJECTED_DB_SIZE / 10)))  # +10% RAM per ogni 100MB DB
ESTIMATED_APACHE_RAM=$((APACHE_CURRENT_RAM * 2))  # x2 per 6 utenti simultanei
ESTIMATED_NODE_RAM=$((NODE_CURRENT_RAM + 50))     # +50MB per chat più intensa

TOTAL_ESTIMATED_RAM=$((ESTIMATED_MYSQL_RAM + ESTIMATED_APACHE_RAM + ESTIMATED_NODE_RAM + 500))  # +500MB sistema

log_message "🗄️ MySQL stimato: ${ESTIMATED_MYSQL_RAM}MB (attuale: ${MYSQL_CURRENT_RAM}MB)"
log_message "🌐 Apache stimato: ${ESTIMATED_APACHE_RAM}MB (attuale: ${APACHE_CURRENT_RAM}MB)"
log_message "💬 Node.js stimato: ${ESTIMATED_NODE_RAM}MB (attuale: ${NODE_CURRENT_RAM}MB)"
log_message "🖥️ RAM totale stimata: ${TOTAL_ESTIMATED_RAM}MB"

# 5. VALUTAZIONE CAPACITÀ
log_message "⚖️ Valutazione Capacità:"

CURRENT_RAM_MB=$(free -m | awk 'NR==2{print $2}')
AVAILABLE_RAM_MB=$(free -m | awk 'NR==2{print $7}')

if [ $TOTAL_ESTIMATED_RAM -lt $AVAILABLE_RAM_MB ]; then
    log_message "✅ RAM SUFFICIENTE - Richiesta: ${TOTAL_ESTIMATED_RAM}MB, Disponibile: ${AVAILABLE_RAM_MB}MB"
    RAM_STATUS="SUFFICIENTE"
else
    log_message "⚠️ RAM INSUFFICIENTE - Richiesta: ${TOTAL_ESTIMATED_RAM}MB, Disponibile: ${AVAILABLE_RAM_MB}MB"
    RAM_STATUS="INSUFFICIENTE"
fi

if [ $PROJECTED_DB_SIZE -lt 1000 ]; then
    log_message "✅ SPAZIO DB SUFFICIENTE - Proiettato: ${PROJECTED_DB_SIZE}MB (< 1GB)"
    DISK_STATUS="SUFFICIENTE"
else
    log_message "⚠️ SPAZIO DB DA MONITORARE - Proiettato: ${PROJECTED_DB_SIZE}MB"
    DISK_STATUS="DA_MONITORARE"
fi

if [ $CPU_CORES -ge 4 ]; then
    log_message "✅ CPU SUFFICIENTE - $CPU_CORES cores per 6 utenti simultanei"
    CPU_STATUS="SUFFICIENTE"
else
    log_message "⚠️ CPU LIMITATA - $CPU_CORES cores potrebbero essere insufficienti"
    CPU_STATUS="LIMITATA"
fi

# 6. RACCOMANDAZIONI
log_message "💡 Raccomandazioni:"

if [ "$RAM_STATUS" = "SUFFICIENTE" ] && [ "$CPU_STATUS" = "SUFFICIENTE" ]; then
    log_message "🎉 SERVER ADEGUATO per 100 clienti + 6 utenti simultanei"
    log_message "📈 Crescita supportata con configurazione attuale"
else
    log_message "🔧 OTTIMIZZAZIONI NECESSARIE:"
    
    if [ "$RAM_STATUS" = "INSUFFICIENTE" ]; then
        EXTRA_RAM_NEEDED=$((TOTAL_ESTIMATED_RAM - AVAILABLE_RAM_MB))
        log_message "   💾 Aumentare RAM di almeno ${EXTRA_RAM_NEEDED}MB"
    fi
    
    if [ "$CPU_STATUS" = "LIMITATA" ]; then
        log_message "   🖥️ Considerare upgrade CPU o ottimizzazioni aggressive"
    fi
fi

# Raccomandazioni specifiche
log_message "🔧 Ottimizzazioni Consigliate:"
log_message "   📊 Monitoraggio continuo delle performance"
log_message "   🗄️ Archivio automatico dati vecchi (già implementato)"
log_message "   ⚡ Cache Redis per sessioni multiple"
log_message "   📈 Load balancing se si supera la capacità"

# 7. TEST DI CARICO SEMPLIFICATO
log_message "🧪 Test di Carico Base:"

# Test MySQL con query simulate
START_TIME=$(date +%s.%N)
for i in {1..100}; do
    mysql -u crmuser -p'Admin123!' crm -e "SELECT * FROM clienti LIMIT 1;" >/dev/null 2>&1
done
END_TIME=$(date +%s.%N)
MYSQL_TEST_TIME=$(echo "$END_TIME - $START_TIME" | bc -l)

log_message "🗄️ 100 query MySQL: ${MYSQL_TEST_TIME}s (media: $(echo "scale=4; $MYSQL_TEST_TIME/100" | bc)s per query)"

# Test Apache con richieste curl simulate
START_TIME=$(date +%s.%N)
for i in {1..10}; do
    curl -s "http://localhost/CRM/dashboard.php" >/dev/null 2>&1
done
END_TIME=$(date +%s.%N)
APACHE_TEST_TIME=$(echo "$END_TIME - $START_TIME" | bc -l)

log_message "🌐 10 richieste Apache: ${APACHE_TEST_TIME}s (media: $(echo "scale=4; $APACHE_TEST_TIME/10" | bc)s per richiesta)"

# 8. CONCLUSIONI FINALI
log_message "📋 CONCLUSIONI FINALI:"

if [ "$RAM_STATUS" = "SUFFICIENTE" ] && [ "$CPU_STATUS" = "SUFFICIENTE" ]; then
    VERDICT="🎯 IL SERVER PUÒ GESTIRE 100 CLIENTI + 6 UTENTI SIMULTANEI"
    CONFIDENCE="Alta confidenza con configurazione attuale"
else
    VERDICT="⚠️ SERVER RICHIEDE OTTIMIZZAZIONI PER GESTIRE IL CARICO TARGET"
    CONFIDENCE="Necessari upgrade hardware o ottimizzazioni software"
fi

log_message "$VERDICT"
log_message "📊 Livello di confidenza: $CONFIDENCE"
log_message "📁 Report completo salvato in: $LOG_FILE"

echo ""
echo "🎯 RISULTATO ANALISI CAPACITÀ:"
echo "$VERDICT"
echo "📊 $CONFIDENCE"
echo ""
echo "📋 Dettagli completi nel log: $LOG_FILE"

exit 0
