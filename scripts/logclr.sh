#!/bin/bash
# Script di pulizia log per liberare spazio
# Uso: logclr [opzione]
# Opzioni: 
#   -f, --force     Pulizia forzata senza conferma
#   -h, --help      Mostra questo aiuto

set -e

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funzione per mostrare l'uso
show_usage() {
    echo -e "${BLUE}=== SCRIPT PULIZIA LOG ===${NC}"
    echo "Uso: logclr [opzione]"
    echo ""
    echo "Opzioni:"
    echo "  -f, --force     Pulizia forzata senza conferma"
    echo "  -h, --help      Mostra questo aiuto"
    echo ""
    echo "Cosa viene pulito:"
    echo "  • Log di sistema (/var/log/syslog*)"
    echo "  • Log di Apache (/var/log/apache2/*)"
    echo "  • Journal systemd vecchi"
    echo "  • Log di apt e dpkg"
    echo "  • Cache di pacchetti"
    echo "  • File temporanei"
}

# Funzione per mostrare lo spazio
show_space() {
    echo -e "${BLUE}=== SPAZIO DISCO ===${NC}"
    df -h / | grep -E "Size|mmcblk|sda|nvme"
    echo ""
}

# Funzione per pulire i log
clean_logs() {
    local force=$1
    
    echo -e "${YELLOW}=== ANALISI SPAZIO CORRENTE ===${NC}"
    show_space
    
    echo -e "${YELLOW}=== LOG PIÙ GRANDI ===${NC}"
    du -sh /var/log/* 2>/dev/null | sort -hr | head -10
    echo ""
    
    if [[ "$force" != "true" ]]; then
        echo -e "${RED}ATTENZIONE: Questa operazione cancellerà i log!${NC}"
        read -p "Vuoi continuare? (s/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Ss]$ ]]; then
            echo "Operazione annullata."
            exit 0
        fi
    fi
    
    echo -e "${GREEN}=== INIZIO PULIZIA ===${NC}"
    
    # 1. Pulizia log di sistema
    echo "🗑️  Pulizia log di sistema..."
    journalctl --vacuum-time=1d >/dev/null 2>&1 || true
    truncate -s 0 /var/log/syslog* 2>/dev/null || true
    
    # 2. Pulizia log Apache
    echo "🗑️  Pulizia log Apache..."
    truncate -s 0 /var/log/apache2/*.log 2>/dev/null || true
    rm -f /var/log/apache2/*.log.* 2>/dev/null || true
    
    # 3. Pulizia log auth
    echo "🗑️  Pulizia log autenticazione..."
    truncate -s 0 /var/log/auth.log* 2>/dev/null || true
    
    # 4. Pulizia log kern
    echo "🗑️  Pulizia log kernel..."
    truncate -s 0 /var/log/kern.log* 2>/dev/null || true
    
    # 5. Pulizia log dpkg/apt
    echo "🗑️  Pulizia log pacchetti..."
    truncate -s 0 /var/log/dpkg.log* 2>/dev/null || true
    truncate -s 0 /var/log/apt/*.log 2>/dev/null || true
    rm -f /var/log/apt/*.log.* 2>/dev/null || true
    
    # 6. Pulizia cache apt
    echo "🗑️  Pulizia cache pacchetti..."
    apt-get clean >/dev/null 2>&1 || true
    apt-get autoclean >/dev/null 2>&1 || true
    
    # 7. Pulizia file temporanei
    echo "🗑️  Pulizia file temporanei..."
    rm -rf /tmp/* 2>/dev/null || true
    rm -rf /var/tmp/* 2>/dev/null || true
    
    # 8. Pulizia log rotati compressi
    echo "🗑️  Pulizia log rotati..."
    find /var/log -type f -name "*.gz" -delete 2>/dev/null || true
    find /var/log -type f -name "*.[0-9]" -delete 2>/dev/null || true
    
    # 9. Pulizia cache thumbnail (se esistono)
    echo "🗑️  Pulizia cache thumbnail..."
    find /home -name ".thumbnails" -type d -exec rm -rf {} + 2>/dev/null || true
    find /root -name ".thumbnails" -type d -exec rm -rf {} + 2>/dev/null || true
    
    # 10. Pulizia crash reports
    echo "🗑️  Pulizia crash reports..."
    rm -rf /var/crash/* 2>/dev/null || true
    
    echo -e "${GREEN}=== PULIZIA COMPLETATA ===${NC}"
    
    echo -e "${YELLOW}=== SPAZIO LIBERATO ===${NC}"
    show_space
    
    # Riavvia Apache per essere sicuri
    echo "🔄 Riavvio Apache..."
    systemctl restart apache2 || true
    
    echo -e "${GREEN}✅ Pulizia completata con successo!${NC}"
}

# Parsing parametri
FORCE=false

case "${1:-}" in
    -h|--help)
        show_usage
        exit 0
        ;;
    -f|--force)
        FORCE=true
        ;;
    "")
        # Nessun parametro, modalità interattiva
        ;;
    *)
        echo -e "${RED}Parametro non riconosciuto: $1${NC}"
        show_usage
        exit 1
        ;;
esac

# Esegui pulizia
clean_logs $FORCE
