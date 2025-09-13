#!/bin/bash

# =================================================================
# 🔍 QUICK SYSTEM CHECK - Background Scripts Verification
# =================================================================
# Script per verificare rapidamente lo stato degli script di background
# Autore: Sistema CRM ASContabilmente
# Versione: 1.0
# Data: $(date)

echo "🔍 === QUICK SYSTEM CHECK - Background Scripts Verification ==="
echo "📅 Data controllo: $(date)"
echo ""

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funzione per status check
check_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ PASS${NC}"
    else
        echo -e "${RED}❌ FAIL${NC}"
    fi
}

# =================================================================
# 1. VERIFICA SCRIPT ESISTENTI
# =================================================================
echo "📁 1. Verifica presenza script..."
scripts=("archivio_chat_mensile.sh" "optimize_database_nightly.sh" "setup_cron_jobs.sh" "check_cron_status.sh")

for script in "${scripts[@]}"; do
    echo -n "   $script: "
    if [ -f "/var/www/CRM/$script" ]; then
        if [ -x "/var/www/CRM/$script" ]; then
            echo -e "${GREEN}✅ Presente ed eseguibile${NC}"
        else
            echo -e "${YELLOW}⚠️  Presente ma non eseguibile${NC}"
        fi
    else
        echo -e "${RED}❌ Mancante${NC}"
    fi
done

# =================================================================
# 2. VERIFICA CRON JOBS
# =================================================================
echo ""
echo "⏰ 2. Verifica cron jobs installati..."

# Controlla se cron service è attivo
echo -n "   Servizio cron: "
systemctl is-active cron >/dev/null 2>&1
check_status $?

# Lista cron jobs
echo "   Cron jobs configurati:"
crontab -l 2>/dev/null | grep -E "(archivio_chat_mensile|optimize_database_nightly)" | while read -r line; do
    echo "   📅 $line"
done

if [ -z "$(crontab -l 2>/dev/null | grep -E '(archivio_chat_mensile|optimize_database_nightly)')" ]; then
    echo -e "   ${RED}❌ Nessun cron job configurato per gli script principali${NC}"
fi

# =================================================================
# 3. VERIFICA DATABASE
# =================================================================
echo ""
echo "🗄️  3. Verifica connessione database..."

echo -n "   Servizio MySQL: "
systemctl is-active mysql >/dev/null 2>&1
check_status $?

echo -n "   Connessione database CRM: "
mysql -u root -pAdmin123! crm -e "SELECT 1" >/dev/null 2>&1
check_status $?

echo -n "   Tabella chat_messages: "
mysql -u root -pAdmin123! crm -e "SELECT COUNT(*) FROM chat_messages" >/dev/null 2>&1
check_status $?

echo -n "   Tabella chat_conversations: "
mysql -u root -pAdmin123! crm -e "SELECT COUNT(*) FROM chat_conversations" >/dev/null 2>&1
check_status $?

# =================================================================
# 4. VERIFICA DIRECTORY E PERMESSI
# =================================================================
echo ""
echo "📂 4. Verifica directory e permessi..."

directories=("logs" "backups" "local_drive" "local_drive/ASContabilmente" "local_drive/ASContabilmente/archivio" "local_drive/ASContabilmente/archivio/chat")

for dir in "${directories[@]}"; do
    echo -n "   /var/www/CRM/$dir: "
    if [ -d "/var/www/CRM/$dir" ]; then
        echo -e "${GREEN}✅ Presente${NC}"
    else
        echo -e "${YELLOW}⚠️  Mancante (verrà creata automaticamente)${NC}"
    fi
done

# =================================================================
# 5. VERIFICA SPAZIO DISCO
# =================================================================
echo ""
echo "💿 5. Verifica spazio disco..."

# Spazio totale
disk_usage=$(df /var/www/CRM | awk 'NR==2 {print $5}' | sed 's/%//')
echo -n "   Utilizzo disco /var/www/CRM: ${disk_usage}% "

if [ "$disk_usage" -gt 85 ]; then
    echo -e "${RED}❌ CRITICO (>85%)${NC}"
elif [ "$disk_usage" -gt 70 ]; then
    echo -e "${YELLOW}⚠️  WARNING (>70%)${NC}"
else
    echo -e "${GREEN}✅ OK (<70%)${NC}"
fi

# Dimensione database
db_size=$(mysql -u root -pAdmin123! -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema='crm';" 2>/dev/null | grep -E '[0-9]+\.[0-9]+')
if [ -n "$db_size" ]; then
    echo "   Dimensione database CRM: ${db_size} MB"
else
    echo -e "   ${YELLOW}⚠️  Impossibile determinare dimensione database${NC}"
fi

# =================================================================
# 6. VERIFICA LOG RECENTI
# =================================================================
echo ""
echo "📝 6. Verifica log recenti..."

log_files=("database_optimization_nightly.log" "chat_archivio_mensile.log")

for log_file in "${log_files[@]}"; do
    echo -n "   $log_file: "
    if [ -f "/var/www/CRM/logs/$log_file" ]; then
        last_mod=$(stat -c %Y "/var/www/CRM/logs/$log_file" 2>/dev/null)
        current_time=$(date +%s)
        age_hours=$(( (current_time - last_mod) / 3600 ))
        
        if [ "$age_hours" -lt 48 ]; then
            echo -e "${GREEN}✅ Aggiornato (${age_hours}h fa)${NC}"
        else
            echo -e "${YELLOW}⚠️  Vecchio (${age_hours}h fa)${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  Non presente${NC}"
    fi
done

# =================================================================
# 7. VERIFICA BACKUP RECENTI
# =================================================================
echo ""
echo "💾 7. Verifica backup recenti..."

if [ -d "/var/www/CRM/backups" ]; then
    backup_count=$(find /var/www/CRM/backups -name "backup_*.sql.gz" -mtime -1 | wc -l)
    echo -n "   Backup nelle ultime 24h: $backup_count "
    
    if [ "$backup_count" -gt 0 ]; then
        echo -e "${GREEN}✅ OK${NC}"
        latest_backup=$(find /var/www/CRM/backups -name "backup_*.sql.gz" -mtime -1 | head -1)
        if [ -n "$latest_backup" ]; then
            backup_size=$(du -h "$latest_backup" | cut -f1)
            echo "   Ultimo backup: $(basename "$latest_backup") (${backup_size})"
        fi
    else
        echo -e "${RED}❌ Nessun backup recente${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠️  Directory backup non presente${NC}"
fi

# =================================================================
# 8. HEALTH SCORE
# =================================================================
echo ""
echo "📊 8. Health Score Generale..."

# Calcola score (molto semplificato)
score=0

# Script presenti (+20 punti ciascuno)
for script in "${scripts[@]}"; do
    if [ -f "/var/www/CRM/$script" ] && [ -x "/var/www/CRM/$script" ]; then
        score=$((score + 20))
    fi
done

# Servizi attivi (+5 punti ciascuno)
systemctl is-active cron >/dev/null 2>&1 && score=$((score + 5))
systemctl is-active mysql >/dev/null 2>&1 && score=$((score + 5))

# Database accessibile (+10 punti)
mysql -u root -pAdmin123! crm -e "SELECT 1" >/dev/null 2>&1 && score=$((score + 10))

echo -n "   Health Score: ${score}/100 "
if [ "$score" -ge 80 ]; then
    echo -e "${GREEN}✅ ECCELLENTE${NC}"
elif [ "$score" -ge 60 ]; then
    echo -e "${YELLOW}⚠️  BUONO${NC}"
elif [ "$score" -ge 40 ]; then
    echo -e "${YELLOW}⚠️  MEDIOCRE${NC}"
else
    echo -e "${RED}❌ CRITICO${NC}"
fi

# =================================================================
# 9. RACCOMANDAZIONI
# =================================================================
echo ""
echo "💡 9. Raccomandazioni..."

recommendations=0

# Controlla se gli script non sono configurati
if [ -z "$(crontab -l 2>/dev/null | grep -E '(archivio_chat_mensile|optimize_database_nightly)')" ]; then
    echo -e "   ${YELLOW}💡 Eseguire ./setup_cron_jobs.sh per configurare i cron job${NC}"
    recommendations=$((recommendations + 1))
fi

# Controlla spazio disco
if [ "$disk_usage" -gt 70 ]; then
    echo -e "   ${YELLOW}💡 Liberare spazio disco (attualmente al ${disk_usage}%)${NC}"
    recommendations=$((recommendations + 1))
fi

# Controlla backup
if [ "$backup_count" -eq 0 ]; then
    echo -e "   ${YELLOW}💡 Verificare configurazione backup automatici${NC}"
    recommendations=$((recommendations + 1))
fi

if [ "$recommendations" -eq 0 ]; then
    echo -e "   ${GREEN}✅ Nessuna raccomandazione - sistema ottimale${NC}"
fi

# =================================================================
# SUMMARY FINALE
# =================================================================
echo ""
echo "=" | awk '{for(i=1;i<=65;i++) printf "="}END{print ""}'
echo "📋 SUMMARY:"
echo "   Health Score: ${score}/100"
echo "   Raccomandazioni: ${recommendations}"
echo "   Data controllo: $(date)"
echo "=" | awk '{for(i=1;i<=65;i++) printf "="}END{print ""}'

# Return code basato sul health score
if [ "$score" -ge 80 ]; then
    exit 0
elif [ "$score" -ge 60 ]; then
    exit 1
else
    exit 2
fi
