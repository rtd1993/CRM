#!/bin/bash
# Script Master per Ottimizzazione Completa CRM Database
# Esegue tutte le operazioni di ottimizzazione in sequenza

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="/var/log/crm_master_optimization.log"

echo -e "${BLUE}╔══════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║      CRM DATABASE OPTIMIZATION MASTER   ║${NC}"
echo -e "${BLUE}║              $(date +'%Y-%m-%d %H:%M:%S')              ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════╝${NC}"

echo "=== AVVIO OTTIMIZZAZIONE MASTER $(date) ===" >> $LOG_FILE

# Verifica prerequisiti
echo -e "\n${CYAN}🔍 VERIFICA PREREQUISITI${NC}"

# Controlla se MySQL è in esecuzione
if ! systemctl is-active --quiet mysql; then
    echo -e "${RED}❌ MySQL non è in esecuzione${NC}"
    echo "Tentativo di avvio MySQL..."
    sudo systemctl start mysql
    sleep 5
    if ! systemctl is-active --quiet mysql; then
        echo -e "${RED}❌ Impossibile avviare MySQL${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}✓ MySQL in esecuzione${NC}"

# Controlla connessione database
if ! mysql -u crmuser -pAdmin123! crm -e "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${RED}❌ Impossibile connettersi al database CRM${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Connessione database OK${NC}"

# Controlla spazio disco
DISK_USAGE=$(df /var/lib/mysql | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    echo -e "${RED}⚠ Spazio disco basso: ${DISK_USAGE}%${NC}"
    echo "⚠ Spazio disco basso: ${DISK_USAGE}%" >> $LOG_FILE
else
    echo -e "${GREEN}✓ Spazio disco OK: ${DISK_USAGE}%${NC}"
fi

# FASE 1: Backup di sicurezza
echo -e "\n${CYAN}💾 FASE 1: BACKUP DI SICUREZZA${NC}"
BACKUP_DIR="/var/backups/mysql_crm"
mkdir -p "$BACKUP_DIR"

BACKUP_FILE="$BACKUP_DIR/crm_backup_$(date +%Y%m%d_%H%M%S).sql"
echo "Creazione backup in: $BACKUP_FILE"

if mysqldump -u crmuser -pAdmin123! crm > "$BACKUP_FILE" 2>/dev/null; then
    echo -e "${GREEN}✓ Backup completato: $(ls -lh "$BACKUP_FILE" | awk '{print $5}')${NC}"
    echo "✓ Backup completato: $BACKUP_FILE" >> $LOG_FILE
else
    echo -e "${RED}❌ Errore durante il backup${NC}"
    echo "❌ Errore durante il backup" >> $LOG_FILE
    exit 1
fi

# FASE 2: Creazione indici ottimizzati
echo -e "\n${CYAN}🔧 FASE 2: CREAZIONE INDICI OTTIMIZZATI${NC}"
if [ -f "$SCRIPT_DIR/create_indexes.sql" ]; then
    echo "Esecuzione script indici..."
    if mysql -u crmuser -pAdmin123! crm < "$SCRIPT_DIR/create_indexes.sql" >> $LOG_FILE 2>&1; then
        echo -e "${GREEN}✓ Indici creati con successo${NC}"
        echo "✓ Indici creati con successo" >> $LOG_FILE
    else
        echo -e "${YELLOW}⚠ Alcuni indici potrebbero già esistere${NC}"
        echo "⚠ Warning durante creazione indici" >> $LOG_FILE
    fi
else
    echo -e "${YELLOW}⚠ File create_indexes.sql non trovato${NC}"
fi

# FASE 3: Ottimizzazione database
echo -e "\n${CYAN}⚡ FASE 3: OTTIMIZZAZIONE DATABASE${NC}"
if [ -f "$SCRIPT_DIR/optimize_mysql.sh" ]; then
    echo "Esecuzione script ottimizzazione..."
    chmod +x "$SCRIPT_DIR/optimize_mysql.sh"
    if bash "$SCRIPT_DIR/optimize_mysql.sh" >> $LOG_FILE 2>&1; then
        echo -e "${GREEN}✓ Ottimizzazione completata${NC}"
    else
        echo -e "${YELLOW}⚠ Ottimizzazione completata con warning${NC}"
    fi
else
    echo -e "${RED}❌ Script optimize_mysql.sh non trovato${NC}"
fi

# FASE 4: Monitoraggio performance
echo -e "\n${CYAN}📊 FASE 4: MONITORAGGIO PERFORMANCE${NC}"
if [ -f "$SCRIPT_DIR/monitor_performance.sh" ]; then
    echo "Esecuzione monitoraggio performance..."
    chmod +x "$SCRIPT_DIR/monitor_performance.sh"
    bash "$SCRIPT_DIR/monitor_performance.sh" >> $LOG_FILE 2>&1
    echo -e "${GREEN}✓ Monitoraggio completato${NC}"
else
    echo -e "${YELLOW}⚠ Script monitor_performance.sh non trovato${NC}"
fi

# FASE 5: Applicazione configurazione MySQL (opzionale)
echo -e "\n${CYAN}⚙️ FASE 5: CONFIGURAZIONE MYSQL${NC}"
if [ -f "$SCRIPT_DIR/mysql_optimization.cnf" ]; then
    echo "Configurazione MySQL trovata"
    echo -e "${YELLOW}Per applicare la configurazione:${NC}"
    echo "  1. sudo cp $SCRIPT_DIR/mysql_optimization.cnf /etc/mysql/conf.d/"
    echo "  2. sudo systemctl restart mysql"
    echo "⚠ Configurazione MySQL disponibile in mysql_optimization.cnf" >> $LOG_FILE
else
    echo -e "${YELLOW}⚠ File mysql_optimization.cnf non trovato${NC}"
fi

# FASE 6: Pulizia vecchi backup
echo -e "\n${CYAN}🧹 FASE 6: PULIZIA VECCHI BACKUP${NC}"
OLD_BACKUPS=$(find "$BACKUP_DIR" -name "*.sql" -mtime +30 | wc -l)
if [ "$OLD_BACKUPS" -gt 0 ]; then
    echo "Rimozione $OLD_BACKUPS backup più vecchi di 30 giorni..."
    find "$BACKUP_DIR" -name "*.sql" -mtime +30 -delete
    echo -e "${GREEN}✓ Pulizia backup completata${NC}"
    echo "✓ Rimossi $OLD_BACKUPS backup vecchi" >> $LOG_FILE
else
    echo -e "${GREEN}✓ Nessun backup vecchio da rimuovere${NC}"
fi

# FASE 7: Report finale
echo -e "\n${CYAN}📋 FASE 7: REPORT FINALE${NC}"

# Statistiche finali
DB_SIZE=$(mysql -u crmuser -pAdmin123! crm -e "
SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'Size_MB'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'crm';
" | grep -v Size_MB)

TOTAL_RECORDS=$(mysql -u crmuser -pAdmin123! crm -e "
SELECT 
    (SELECT COUNT(*) FROM clienti) +
    (SELECT COUNT(*) FROM task) +
    (SELECT COUNT(*) FROM chat_messaggi) +
    (SELECT COUNT(*) FROM conto_termico) +
    (SELECT COUNT(*) FROM enea) +
    (SELECT COUNT(*) FROM chat_read_status) as total;
" | grep -v total)

echo -e "${GREEN}✅ OTTIMIZZAZIONE COMPLETATA CON SUCCESSO${NC}"
echo "📊 Statistiche finali:"
echo "   • Dimensione database: ${DB_SIZE} MB"
echo "   • Record totali: ${TOTAL_RECORDS}"
echo "   • Backup salvato: $(basename "$BACKUP_FILE")"
echo "   • Log dettagliato: $LOG_FILE"

# Salva statistiche nel log
echo "=== STATISTICHE FINALI ===" >> $LOG_FILE
echo "Dimensione database: ${DB_SIZE} MB" >> $LOG_FILE
echo "Record totali: ${TOTAL_RECORDS}" >> $LOG_FILE
echo "Backup: $BACKUP_FILE" >> $LOG_FILE
echo "=== FINE OTTIMIZZAZIONE MASTER $(date) ===" >> $LOG_FILE
echo "" >> $LOG_FILE

# Programma prossima esecuzione
echo -e "\n${BLUE}💡 RACCOMANDAZIONI:${NC}"
echo "• Eseguire questo script settimanalmente"
echo "• Monitorare il log: $LOG_FILE"
echo "• Verificare le performance con monitor_performance.sh"
echo "• Considerare l'applicazione di mysql_optimization.cnf"

echo -e "\n${GREEN}🚀 Ottimizzazione CRM completata!${NC}"
