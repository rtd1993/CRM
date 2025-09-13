#!/bin/bash

# Script per configurare i cron job automatici del CRM
# Configura:
# - Archiviazione mensile chat (1° di ogni mese alle 03:00)
# - Ottimizzazione database notturna (ogni giorno alle 02:00)
# - Sincronizzazione rclone (ogni ora)

CRON_FILE="/tmp/crm_cron_setup"
CRM_DIR="/var/www/CRM"
LOG_DIR="/var/www/CRM/logs"

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== CONFIGURAZIONE CRON JOB CRM ===${NC}"
echo "Configurazione automatica dei task schedulati..."

# Verifica permessi root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}ERRORE: Questo script deve essere eseguito come root${NC}"
    echo "Usa: sudo $0"
    exit 1
fi

# Verifica esistenza directory
if [ ! -d "$CRM_DIR" ]; then
    echo -e "${RED}ERRORE: Directory CRM non trovata: $CRM_DIR${NC}"
    exit 1
fi

# Crea directory log se non esiste
mkdir -p "$LOG_DIR"

# Rende eseguibili gli script
echo -e "${YELLOW}Impostazione permessi script...${NC}"
chmod +x "$CRM_DIR/archivio_chat_mensile.sh"
chmod +x "$CRM_DIR/optimize_database_nightly.sh"
chmod +x "$CRM_DIR/sync_rclone.sh"

# Backup del crontab attuale
echo -e "${YELLOW}Backup crontab attuale...${NC}"
crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S) 2>/dev/null

# Crea il nuovo file cron
cat > "$CRON_FILE" << 'EOF'
# ============================================
# CRON JOB SISTEMA CRM
# Configurato automaticamente
# ============================================

# Variabili ambiente
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
MAILTO=admin@ascontabilmente.it

# ============================================
# OTTIMIZZAZIONE DATABASE NOTTURNA
# Ogni giorno alle 02:00
# ============================================
0 2 * * * /var/www/CRM/optimize_database_nightly.sh >> /var/www/CRM/logs/cron_database.log 2>&1

# ============================================
# ARCHIVIAZIONE MENSILE CHAT
# Ogni 1° del mese alle 03:00
# ============================================
0 3 1 * * /var/www/CRM/archivio_chat_mensile.sh >> /var/www/CRM/logs/cron_archivio.log 2>&1

# ============================================
# SINCRONIZZAZIONE CLOUD (RCLONE)
# Ogni ora al minuto 30
# ============================================
30 * * * * /var/www/CRM/sync_rclone.sh >> /var/www/CRM/logs/cron_rclone.log 2>&1

# ============================================
# PULIZIA LOG SISTEMA
# Ogni domenica alle 04:00
# ============================================
0 4 * * 0 find /var/www/CRM/logs -name "*.log" -mtime +30 -delete && echo "[$(date)] Log più vecchi di 30 giorni eliminati" >> /var/www/CRM/logs/cron_cleanup.log

# ============================================
# BACKUP AUTOMATICO DATABASE
# Ogni giorno alle 01:00
# ============================================
0 1 * * * mysqldump -u crmuser -pAdmin123! --single-transaction --routines --triggers crm | gzip > /var/www/CRM/backups/backup_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz 2>> /var/www/CRM/logs/cron_backup.log

# ============================================
# ROTAZIONE BACKUP (mantieni ultimi 7)
# Ogni giorno alle 05:00
# ============================================
0 5 * * * find /var/www/CRM/backups -name "backup_*.sql.gz" -mtime +7 -delete && echo "[$(date)] Backup più vecchi di 7 giorni eliminati" >> /var/www/CRM/logs/cron_backup.log

# ============================================
# MONITORAGGIO SPAZIO DISCO
# Ogni 6 ore
# ============================================
0 */6 * * * df -h /var/www/CRM | awk 'NR==2{if(substr($5,1,length($5)-1) > 85) print "[ALERT] Spazio disco CRM al " $5}' >> /var/www/CRM/logs/cron_monitoring.log

# ============================================
# VERIFICA SERVIZI CRM
# Ogni 15 minuti
# ============================================
*/15 * * * * pgrep -f "node.*socket" > /dev/null || (/var/www/CRM/start_node.sh && echo "[$(date)] Servizio Node.js chat riavviato" >> /var/www/CRM/logs/cron_services.log)

# ============================================
# OTTIMIZZAZIONE TABELLE CHAT (settimanale)
# Ogni lunedì alle 03:30
# ============================================
30 3 * * 1 mysql -u crmuser -pAdmin123! crm -e "OPTIMIZE TABLE chat_conversations, chat_messages;" && echo "[$(date)] Tabelle chat ottimizzate" >> /var/www/CRM/logs/cron_optimization.log

EOF

echo -e "${YELLOW}Installazione cron job...${NC}"

# Installa il nuovo crontab
crontab "$CRON_FILE"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Cron job installati con successo!${NC}"
    
    # Mostra i job installati
    echo -e "\n${BLUE}Cron job attivi:${NC}"
    crontab -l | grep -v "^#" | grep -v "^$"
    
    # Crea file di stato
    cat > "$LOG_DIR/cron_status.txt" << EOF
CRON JOB CRM CONFIGURATI - $(date)
=================================

SCHEDULE:
- Database optimization: Daily at 02:00
- Chat archiving: 1st of month at 03:00
- Cloud sync: Every hour at :30
- Log cleanup: Sunday at 04:00
- Database backup: Daily at 01:00
- Backup rotation: Daily at 05:00
- Disk monitoring: Every 6 hours
- Service monitoring: Every 15 minutes
- Weekly chat optimization: Monday at 03:30

LOGS DIRECTORY: /var/www/CRM/logs/
BACKUP DIRECTORY: /var/www/CRM/backups/

STATUS: ACTIVE
CONFIGURED: $(date)
EOF

    echo -e "\n${GREEN}📋 File di stato creato: $LOG_DIR/cron_status.txt${NC}"
    
else
    echo -e "${RED}❌ Errore nell'installazione dei cron job${NC}"
    exit 1
fi

# Verifica servizio cron
if systemctl is-active --quiet cron; then
    echo -e "${GREEN}✅ Servizio cron attivo${NC}"
elif systemctl is-active --quiet crond; then
    echo -e "${GREEN}✅ Servizio crond attivo${NC}"
else
    echo -e "${YELLOW}⚠️ Avvio servizio cron...${NC}"
    systemctl start cron 2>/dev/null || systemctl start crond 2>/dev/null
fi

# Crea directory per i backup se non esiste
mkdir -p /var/www/CRM/backups

# Test immediato di uno script
echo -e "\n${YELLOW}Test ottimizzazione database (dry run)...${NC}"
if [ -f "$CRM_DIR/optimize_database_nightly.sh" ]; then
    # Test connessione database
    mysql -u crmuser -pAdmin123! crm -e "SELECT 1;" > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Connessione database OK${NC}"
    else
        echo -e "${RED}❌ Errore connessione database${NC}"
    fi
else
    echo -e "${RED}❌ Script ottimizzazione non trovato${NC}"
fi

# Pulizia
rm -f "$CRON_FILE"

echo -e "\n${BLUE}=== CONFIGURAZIONE COMPLETATA ===${NC}"
echo -e "${GREEN}I seguenti task sono ora schedulati:${NC}"
echo -e "  🔧 Ottimizzazione DB: ogni giorno alle 02:00"
echo -e "  📁 Archiviazione chat: 1° del mese alle 03:00"
echo -e "  ☁️  Sync cloud: ogni ora"
echo -e "  🧹 Pulizia log: ogni domenica"
echo -e "  💾 Backup DB: ogni giorno alle 01:00"
echo -e "  🔍 Monitoraggio: continuo"
echo -e "\n${YELLOW}Consulta i log in: /var/www/CRM/logs/${NC}"
echo -e "${YELLOW}Per verificare lo stato: crontab -l${NC}"

exit 0
