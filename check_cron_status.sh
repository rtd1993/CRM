#!/bin/bash

# Script per verificare lo stato dei cron job e dei task automatici del CRM
# Controlla l'esecuzione dei backup, archiviazioni e ottimizzazioni

LOG_DIR="/var/www/CRM/logs"
BACKUP_DIR="/var/www/CRM/backups"
ARCHIVIO_DIR="/var/www/CRM/local_drive/ASContabilmente/archivio/chat"

# Colori
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== VERIFICA STATO CRON JOB CRM ===${NC}"
echo "Data verifica: $(date)"
echo ""

# Verifica servizio cron
echo -e "${YELLOW}🔍 STATO SERVIZIO CRON${NC}"
if systemctl is-active --quiet cron || systemctl is-active --quiet crond; then
    echo -e "  ✅ Servizio cron: ${GREEN}ATTIVO${NC}"
else
    echo -e "  ❌ Servizio cron: ${RED}NON ATTIVO${NC}"
fi

# Verifica cron job installati
echo -e "\n${YELLOW}📋 CRON JOB INSTALLATI${NC}"
CRON_COUNT=$(crontab -l 2>/dev/null | grep -v "^#" | grep -v "^$" | wc -l)
if [ "$CRON_COUNT" -gt 0 ]; then
    echo -e "  ✅ Cron job configurati: ${GREEN}$CRON_COUNT${NC}"
    echo "  Job attivi:"
    crontab -l 2>/dev/null | grep -v "^#" | grep -v "^$" | while read line; do
        echo "    - $line"
    done
else
    echo -e "  ❌ Nessun cron job configurato: ${RED}ERRORE${NC}"
fi

# Verifica ottimizzazione database notturna
echo -e "\n${YELLOW}🔧 OTTIMIZZAZIONE DATABASE NOTTURNA${NC}"
if [ -f "$LOG_DIR/database_optimization_nightly.log" ]; then
    LAST_OPT=$(tail -1 "$LOG_DIR/database_optimization_nightly.log" 2>/dev/null | grep "FINE OTTIMIZZAZIONE")
    if [ -n "$LAST_OPT" ]; then
        LAST_DATE=$(echo "$LAST_OPT" | sed 's/.*\[\(.*\)\].*/\1/')
        echo -e "  ✅ Ultima ottimizzazione: ${GREEN}$LAST_DATE${NC}"
        
        # Verifica se è stata eseguita nelle ultime 25 ore
        if [ $(date -d "$LAST_DATE" +%s) -gt $(date -d "25 hours ago" +%s) 2>/dev/null ]; then
            echo -e "  ✅ Stato: ${GREEN}RECENTE${NC}"
        else
            echo -e "  ⚠️  Stato: ${YELLOW}DATATA${NC}"
        fi
    else
        echo -e "  ⚠️  Log presente ma incomplete: ${YELLOW}VERIFICARE${NC}"
    fi
else
    echo -e "  ❌ Log ottimizzazione: ${RED}NON TROVATO${NC}"
fi

# Verifica archiviazione chat mensile
echo -e "\n${YELLOW}📁 ARCHIVIAZIONE CHAT MENSILE${NC}"
if [ -f "$LOG_DIR/chat_archivio_mensile.log" ]; then
    LAST_ARCH=$(tail -1 "$LOG_DIR/chat_archivio_mensile.log" 2>/dev/null | grep "FINE ARCHIVIAZIONE")
    if [ -n "$LAST_ARCH" ]; then
        LAST_DATE=$(echo "$LAST_ARCH" | sed 's/.*\[\(.*\)\].*/\1/')
        echo -e "  ✅ Ultima archiviazione: ${GREEN}$LAST_DATE${NC}"
    else
        echo -e "  ⚠️  Log presente ma incompleto: ${YELLOW}VERIFICARE${NC}"
    fi
    
    # Verifica file di archivio più recente
    if [ -d "$ARCHIVIO_DIR" ]; then
        ULTIMO_ARCHIVIO=$(find "$ARCHIVIO_DIR" -name "chat_globali_*.txt" -type f -printf '%T@ %p\n' 2>/dev/null | sort -n | tail -1 | cut -d' ' -f2-)
        if [ -n "$ULTIMO_ARCHIVIO" ]; then
            ARCH_SIZE=$(du -h "$ULTIMO_ARCHIVIO" 2>/dev/null | cut -f1)
            echo -e "  📄 Ultimo file archivio: $(basename "$ULTIMO_ARCHIVIO") (${ARCH_SIZE})"
        fi
    fi
else
    echo -e "  ❌ Log archiviazione: ${RED}NON TROVATO${NC}"
fi

# Verifica backup database
echo -e "\n${YELLOW}💾 BACKUP DATABASE${NC}"
if [ -d "$BACKUP_DIR" ]; then
    BACKUP_COUNT=$(find "$BACKUP_DIR" -name "backup_*.sql.gz" -type f 2>/dev/null | wc -l)
    if [ "$BACKUP_COUNT" -gt 0 ]; then
        echo -e "  ✅ Backup disponibili: ${GREEN}$BACKUP_COUNT${NC}"
        
        # Backup più recente
        ULTIMO_BACKUP=$(find "$BACKUP_DIR" -name "backup_*.sql.gz" -type f -printf '%T@ %p\n' 2>/dev/null | sort -n | tail -1 | cut -d' ' -f2-)
        if [ -n "$ULTIMO_BACKUP" ]; then
            BACKUP_DATE=$(stat -c %y "$ULTIMO_BACKUP" 2>/dev/null | cut -d' ' -f1,2 | cut -d'.' -f1)
            BACKUP_SIZE=$(du -h "$ULTIMO_BACKUP" 2>/dev/null | cut -f1)
            echo -e "  📄 Ultimo backup: $BACKUP_DATE (${BACKUP_SIZE})"
            
            # Verifica se è recente (ultime 25 ore)
            if [ $(stat -c %Y "$ULTIMO_BACKUP" 2>/dev/null) -gt $(date -d "25 hours ago" +%s) ]; then
                echo -e "  ✅ Stato backup: ${GREEN}RECENTE${NC}"
            else
                echo -e "  ⚠️  Stato backup: ${YELLOW}DATATO${NC}"
            fi
        fi
    else
        echo -e "  ❌ Backup disponibili: ${RED}NESSUNO${NC}"
    fi
else
    echo -e "  ❌ Directory backup: ${RED}NON TROVATA${NC}"
fi

# Verifica spazio disco
echo -e "\n${YELLOW}💿 SPAZIO DISCO${NC}"
DISK_USAGE=$(df -h /var/www/CRM 2>/dev/null | awk 'NR==2{print $5}' | sed 's/%//')
if [ -n "$DISK_USAGE" ]; then
    if [ "$DISK_USAGE" -lt 80 ]; then
        echo -e "  ✅ Utilizzo disco: ${GREEN}${DISK_USAGE}%${NC}"
    elif [ "$DISK_USAGE" -lt 90 ]; then
        echo -e "  ⚠️  Utilizzo disco: ${YELLOW}${DISK_USAGE}%${NC}"
    else
        echo -e "  ❌ Utilizzo disco: ${RED}${DISK_USAGE}% - CRITICO${NC}"
    fi
    
    # Dettagli directory principali
    echo "  📁 Spazio per directory:"
    du -sh /var/www/CRM/local_drive 2>/dev/null | sed 's/^/    /'
    du -sh /var/www/CRM/logs 2>/dev/null | sed 's/^/    /'
    du -sh /var/www/CRM/backups 2>/dev/null | sed 's/^/    /'
fi

# Verifica database
echo -e "\n${YELLOW}🗃️  DATABASE${NC}"
DB_SIZE=$(mysql -u crmuser -pAdmin123! -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = 'crm';" 2>/dev/null | tail -1)
if [ -n "$DB_SIZE" ] && [ "$DB_SIZE" != "NULL" ]; then
    echo -e "  ✅ Dimensione database: ${GREEN}${DB_SIZE} MB${NC}"
    
    # Conta messaggi chat
    CHAT_MESSAGES=$(mysql -u crmuser -pAdmin123! crm -sN -e "SELECT COUNT(*) FROM chat_messages;" 2>/dev/null)
    CHAT_CONVERSATIONS=$(mysql -u crmuser -pAdmin123! crm -sN -e "SELECT COUNT(*) FROM chat_conversations;" 2>/dev/null)
    
    if [ -n "$CHAT_MESSAGES" ]; then
        echo -e "  📨 Messaggi chat: $CHAT_MESSAGES"
        echo -e "  💬 Conversazioni: $CHAT_CONVERSATIONS"
    fi
else
    echo -e "  ❌ Connessione database: ${RED}ERRORE${NC}"
fi

# Verifica servizi correlati
echo -e "\n${YELLOW}🔌 SERVIZI CORRELATI${NC}"

# Node.js per chat
if pgrep -f "node.*socket" > /dev/null; then
    echo -e "  ✅ Servizio chat Node.js: ${GREEN}ATTIVO${NC}"
else
    echo -e "  ❌ Servizio chat Node.js: ${RED}NON ATTIVO${NC}"
fi

# MySQL
if systemctl is-active --quiet mysql || systemctl is-active --quiet mariadb; then
    echo -e "  ✅ Servizio MySQL: ${GREEN}ATTIVO${NC}"
else
    echo -e "  ❌ Servizio MySQL: ${RED}NON ATTIVO${NC}"
fi

# Apache/Nginx
if systemctl is-active --quiet apache2 || systemctl is-active --quiet nginx; then
    echo -e "  ✅ Web server: ${GREEN}ATTIVO${NC}"
else
    echo -e "  ❌ Web server: ${RED}NON ATTIVO${NC}"
fi

# Riepilogo generale
echo -e "\n${BLUE}📊 RIEPILOGO GENERALE${NC}"

# Calcola score salute sistema
HEALTH_SCORE=0
MAX_SCORE=8

# Controlla criteri
[ "$CRON_COUNT" -gt 0 ] && ((HEALTH_SCORE++))
[ -f "$LOG_DIR/database_optimization_nightly.log" ] && ((HEALTH_SCORE++))
[ "$BACKUP_COUNT" -gt 0 ] 2>/dev/null && ((HEALTH_SCORE++))
[ -n "$DB_SIZE" ] && [ "$DB_SIZE" != "NULL" ] && ((HEALTH_SCORE++))
[ "$DISK_USAGE" -lt 90 ] 2>/dev/null && ((HEALTH_SCORE++))
pgrep -f "node.*socket" > /dev/null && ((HEALTH_SCORE++))
systemctl is-active --quiet mysql || systemctl is-active --quiet mariadb && ((HEALTH_SCORE++))
systemctl is-active --quiet apache2 || systemctl is-active --quiet nginx && ((HEALTH_SCORE++))

HEALTH_PERCENT=$((HEALTH_SCORE * 100 / MAX_SCORE))

if [ "$HEALTH_PERCENT" -ge 90 ]; then
    echo -e "  🎯 Stato sistema: ${GREEN}OTTIMO (${HEALTH_PERCENT}%)${NC}"
elif [ "$HEALTH_PERCENT" -ge 70 ]; then
    echo -e "  🎯 Stato sistema: ${YELLOW}BUONO (${HEALTH_PERCENT}%)${NC}"
elif [ "$HEALTH_PERCENT" -ge 50 ]; then
    echo -e "  🎯 Stato sistema: ${YELLOW}SUFFICIENTE (${HEALTH_PERCENT}%)${NC}"
else
    echo -e "  🎯 Stato sistema: ${RED}CRITICO (${HEALTH_PERCENT}%)${NC}"
fi

echo -e "\n${BLUE}=== FINE VERIFICA ===${NC}"

# Salva report in file
REPORT_FILE="$LOG_DIR/health_report_$(date +%Y%m%d_%H%M%S).txt"
{
    echo "REPORT SALUTE SISTEMA CRM - $(date)"
    echo "==========================================="
    echo "Health Score: $HEALTH_PERCENT% ($HEALTH_SCORE/$MAX_SCORE)"
    echo "Cron Jobs: $CRON_COUNT"
    echo "Database Size: ${DB_SIZE} MB"
    echo "Backup Files: $BACKUP_COUNT"
    echo "Disk Usage: ${DISK_USAGE}%"
    echo "Last Optimization: $LAST_DATE"
    echo "==========================================="
} > "$REPORT_FILE"

echo -e "${GREEN}📋 Report salvato in: $REPORT_FILE${NC}"

exit 0
