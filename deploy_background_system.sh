#!/bin/bash

# =================================================================
# 🚀 DEPLOYMENT SCRIPT - Background Automation System
# =================================================================
# Script per il deployment completo del sistema di automazione
# Autore: Sistema CRM ASContabilmente
# Versione: 1.0
# Data: $(date)

echo "🚀 === DEPLOYMENT BACKGROUND AUTOMATION SYSTEM ==="
echo "📅 Iniziando deployment: $(date)"
echo ""

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurazione
CRM_PATH="/var/www/CRM"
BACKUP_PATH="$CRM_PATH/backups"
LOGS_PATH="$CRM_PATH/logs"
ARCHIVE_PATH="$CRM_PATH/local_drive/ASContabilmente/archivio/chat"

# Funzione per logging
log_action() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')] ✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}[$(date '+%H:%M:%S')] ⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}[$(date '+%H:%M:%S')] ❌ $1${NC}"
}

# Verifica prerequisiti
check_prerequisites() {
    log_action "Verificando prerequisiti..."
    
    # Verifica se siamo root
    if [ "$EUID" -ne 0 ]; then
        log_error "Questo script deve essere eseguito come root"
        log_action "Eseguire: sudo ./deploy_background_system.sh"
        exit 1
    fi
    
    # Verifica se siamo nella directory corretta
    if [ ! -f "$CRM_PATH/index.php" ]; then
        log_error "Non siamo nella directory CRM corretta"
        log_error "Percorso atteso: $CRM_PATH"
        exit 1
    fi
    
    # Verifica MySQL
    if ! systemctl is-active mysql >/dev/null 2>&1; then
        log_error "MySQL non è attivo"
        exit 1
    fi
    
    # Verifica connessione database
    if ! mysql -u crmuser -pAdmin123! crm -e "SELECT 1" >/dev/null 2>&1; then
        log_error "Impossibile connettersi al database CRM"
        exit 1
    fi
    
    
    log_success "Prerequisiti verificati"
}

# Creazione directory necessarie
create_directories() {
    log_action "Creando directory necessarie..."
    
    directories=(
        "$LOGS_PATH"
        "$BACKUP_PATH" 
        "$CRM_PATH/local_drive"
        "$CRM_PATH/local_drive/ASContabilmente"
        "$CRM_PATH/local_drive/ASContabilmente/archivio"
        "$ARCHIVE_PATH"
    )
    
    for dir in "${directories[@]}"; do
        if [ ! -d "$dir" ]; then
            mkdir -p "$dir"
            log_success "Creata directory: $dir"
        else
            log_action "Directory già esistente: $dir"
        fi
    done
    
    # Imposta permessi
    chown -R www-data:www-data "$CRM_PATH/local_drive"
    chown -R www-data:www-data "$LOGS_PATH"
    chown root:root "$BACKUP_PATH"
    chmod 755 "$LOGS_PATH"
    chmod 700 "$BACKUP_PATH"
    chmod -R 755 "$CRM_PATH/local_drive"
    
    log_success "Permessi directory impostati"
}

# Verifica e copia script
deploy_scripts() {
    log_action "Verificando e deployando script..."
    
    scripts=(
        "archivio_chat_mensile.sh"
        "optimize_database_nightly.sh" 
        "setup_cron_jobs.sh"
        "check_cron_status.sh"
        "quick_system_check.sh"
    )
    
    for script in "${scripts[@]}"; do
        if [ -f "$CRM_PATH/$script" ]; then
            chmod +x "$CRM_PATH/$script"
            chown root:root "$CRM_PATH/$script"
            log_success "Script configurato: $script"
        else
            log_warning "Script mancante: $script"
        fi
    done
}

# Test connessione database
test_database() {
    log_action "Testando connessione database..."
    
    # Test connessione base
    if mysql -u root -pAdmin123! crm -e "SELECT 'OK' as test" >/dev/null 2>&1; then
        log_success "Connessione database OK"
    else
        log_error "Errore connessione database"
        return 1
    fi
    
    # Test tabelle chat
    tables=("chat_conversations" "chat_messages")
    for table in "${tables[@]}"; do
        if mysql -u root -pAdmin123! crm -e "SELECT COUNT(*) FROM $table" >/dev/null 2>&1; then
            record_count=$(mysql -u root -pAdmin123! crm -e "SELECT COUNT(*) FROM $table" 2>/dev/null | tail -1)
            log_success "Tabella $table: $record_count record"
        else
            log_error "Errore accesso tabella: $table"
        fi
    done
}

# Configurazione cron job
setup_cron_jobs() {
    log_action "Configurando cron jobs..."
    
    # Backup crontab esistente
    crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null
    
    # Esegui script di setup cron
    if [ -f "$CRM_PATH/setup_cron_jobs.sh" ]; then
        cd "$CRM_PATH"
        ./setup_cron_jobs.sh
        log_success "Cron jobs configurati tramite setup_cron_jobs.sh"
    else
        log_warning "setup_cron_jobs.sh non trovato, configurazione manuale cron..."
        
        # Configurazione manuale base
        (crontab -l 2>/dev/null; echo "# CRM Background Jobs") | crontab -
        (crontab -l 2>/dev/null; echo "0 2 * * * /var/www/CRM/optimize_database_nightly.sh >/dev/null 2>&1") | crontab -
        (crontab -l 2>/dev/null; echo "0 3 1 * * /var/www/CRM/archivio_chat_mensile.sh >/dev/null 2>&1") | crontab -
        
        log_success "Cron jobs di base configurati"
    fi
}

# Test esecuzione script
test_scripts() {
    log_action "Testando esecuzione script..."
    
    # Test quick system check
    if [ -f "$CRM_PATH/quick_system_check.sh" ]; then
        log_action "Eseguendo quick system check..."
        cd "$CRM_PATH"
        ./quick_system_check.sh
        check_result=$?
        
        if [ $check_result -eq 0 ]; then
            log_success "Sistema in stato ottimale"
        elif [ $check_result -eq 1 ]; then
            log_warning "Sistema in stato buono con alcuni warning"
        else
            log_warning "Sistema necessita attenzione"
        fi
    fi
}

# Creazione backup iniziale
create_initial_backup() {
    log_action "Creando backup iniziale..."
    
    backup_file="$BACKUP_PATH/initial_deployment_$(date +%Y%m%d_%H%M%S).sql.gz"
    
    if mysqldump -u root -pAdmin123! --single-transaction --routines --triggers crm | gzip > "$backup_file" 2>/dev/null; then
        backup_size=$(du -h "$backup_file" | cut -f1)
        log_success "Backup iniziale creato: $(basename "$backup_file") (${backup_size})"
    else
        log_error "Errore durante creazione backup iniziale"
    fi
}

# Verifica servizi
check_services() {
    log_action "Verificando servizi di sistema..."
    
    services=("mysql" "cron" "nginx")
    
    for service in "${services[@]}"; do
        if systemctl is-active "$service" >/dev/null 2>&1; then
            log_success "Servizio attivo: $service"
        else
            log_warning "Servizio inattivo: $service"
            
            # Prova a riavviare cron se non è attivo
            if [ "$service" = "cron" ]; then
                log_action "Tentando riavvio servizio cron..."
                systemctl start cron
                if systemctl is-active cron >/dev/null 2>&1; then
                    log_success "Servizio cron riavviato con successo"
                fi
            fi
        fi
    done
}

# Report finale
generate_final_report() {
    log_action "Generando report finale..."
    
    report_file="$LOGS_PATH/deployment_report_$(date +%Y%m%d_%H%M%S).txt"
    
    cat > "$report_file" << EOF
🚀 DEPLOYMENT REPORT - Background Automation System
================================================================
Data Deployment: $(date)
Utente: $(whoami)
Percorso CRM: $CRM_PATH

📁 DIRECTORY CREATE:
- Logs: $LOGS_PATH
- Backup: $BACKUP_PATH  
- Archivio Chat: $ARCHIVE_PATH

🔧 SCRIPT DEPLOYATI:
$(ls -la $CRM_PATH/*.sh 2>/dev/null | grep -v "total")

⏰ CRON JOBS CONFIGURATI:
$(crontab -l 2>/dev/null | grep -E "(optimize_database_nightly|archivio_chat_mensile)")

🗄️ DATABASE STATUS:
- Chat Messages: $(mysql -u root -pAdmin123! crm -e "SELECT COUNT(*) FROM chat_messages" 2>/dev/null | tail -1) record
- Chat Conversations: $(mysql -u root -pAdmin123! crm -e "SELECT COUNT(*) FROM chat_conversations" 2>/dev/null | tail -1) record

💾 BACKUP:
$(ls -la $BACKUP_PATH/*.gz 2>/dev/null | tail -1)

🔌 SERVIZI:
- MySQL: $(systemctl is-active mysql 2>/dev/null)
- Cron: $(systemctl is-active cron 2>/dev/null)
- Nginx: $(systemctl is-active nginx 2>/dev/null)

💿 SPAZIO DISCO:
$(df -h $CRM_PATH | tail -1)

================================================================
Deployment completato con successo!
Per verifiche future utilizzare: ./quick_system_check.sh
================================================================
EOF

    log_success "Report salvato in: $report_file"
    
    # Mostra summary del report
    echo ""
    echo "📋 DEPLOYMENT SUMMARY:"
    echo "======================================"
    cat "$report_file" | grep -E "(Data Deployment|Chat Messages|Chat Conversations|MySQL|Cron)" | head -6
    echo "======================================"
}

# Main execution
main() {
    echo ""
    log_action "=== INIZIO DEPLOYMENT BACKGROUND AUTOMATION SYSTEM ==="
    echo ""
    
    check_prerequisites
    echo ""
    
    create_directories
    echo ""
    
    deploy_scripts
    echo ""
    
    test_database
    echo ""
    
    setup_cron_jobs  
    echo ""
    
    check_services
    echo ""
    
    create_initial_backup
    echo ""
    
    test_scripts
    echo ""
    
    generate_final_report
    echo ""
    
    log_success "=== DEPLOYMENT COMPLETATO CON SUCCESSO ==="
    echo ""
    echo "🎉 Il sistema di automazione background è stato installato e configurato!"
    echo ""
    echo "📋 PROSSIMI PASSI:"
    echo "   1. Verificare il sistema: ./quick_system_check.sh"
    echo "   2. Monitorare i log: tail -f $LOGS_PATH/*.log" 
    echo "   3. Verificare cron jobs: crontab -l"
    echo ""
    echo "📧 Per supporto: support@ascontabilmente.it"
    echo ""
}

# Esecuzione con gestione errori
set -e
trap 'log_error "Deployment fallito alla riga $LINENO"; exit 1' ERR

main "$@"
