#!/bin/bash

# Script per l'archiviazione automatica dei messaggi della chat globale
# Esegue il backup e la pulizia dei messaggi più vecchi di 60 giorni
# Programmato per essere eseguito ogni 1° del mese tramite cron

SCRIPT_DIR="/var/www/CRM"
ARCHIVIO_DIR="/var/www/CRM/local_drive/ASContabilmente/Archivio_chat"
LOG_FILE="/var/www/CRM/logs/chat_archivio.log"
DB_NAME="crm"
DB_USER="root"
DB_PASS="Admin123!"

# Funzione di logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Crea directory log se non esiste
mkdir -p /var/www/CRM/logs


log_message "=== INIZIO ARCHIVIAZIONE CHAT GLOBALE ==="

# Calcola la data di 60 giorni fa
DATA_LIMITE=$(date -d '60 days ago' '+%Y-%m-%d %H:%M:%S')
MESE=$(date -d '60 days ago' '+%m')
ANNO=$(date -d '60 days ago' '+%Y')
NOME_FILE="chat_${MESE}_${ANNO}.txt"
PERCORSO_ARCHIVIO="${ARCHIVIO_DIR}/${NOME_FILE}"

log_message "Data limite per archiviazione: $DATA_LIMITE"
log_message "File di destinazione: $NOME_FILE"

# Verifica se ci sono messaggi da archiviare
CONTA_MESSAGGI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
    SELECT COUNT(*) 
    FROM chat_messaggi 
    WHERE timestamp < '$DATA_LIMITE'
")

if [ "$CONTA_MESSAGGI" -eq 0 ]; then
    log_message "Nessun messaggio da archiviare (trovati: $CONTA_MESSAGGI messaggi)"
    log_message "=== FINE ARCHIVIAZIONE CHAT GLOBALE ==="
    exit 0
fi

log_message "Trovati $CONTA_MESSAGGI messaggi da archiviare"

# Crea l'intestazione del file di archivio se non esiste
if [ ! -f "$PERCORSO_ARCHIVIO" ]; then
    echo "# ARCHIVIO CHAT GLOBALE - MESE $MESE/$ANNO" > "$PERCORSO_ARCHIVIO"
    echo "# Messaggi archiviati automaticamente il $(date '+%Y-%m-%d %H:%M:%S')" >> "$PERCORSO_ARCHIVIO"
    echo "# Messaggi più vecchi del: $DATA_LIMITE" >> "$PERCORSO_ARCHIVIO"
    echo "=" >> "$PERCORSO_ARCHIVIO"
    echo "" >> "$PERCORSO_ARCHIVIO"
fi

# Esporta i messaggi nel file di archivio
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "
    SELECT 
        DATE_FORMAT(c.timestamp, '%Y-%m-%d %H:%i:%s') as 'Data/Ora',
        u.nome as 'Utente',
        c.messaggio as 'Messaggio'
    FROM chat_messaggi c
    JOIN utenti u ON c.utente_id = u.id
    WHERE c.timestamp < '$DATA_LIMITE'
    ORDER BY c.timestamp ASC
" --table >> "$PERCORSO_ARCHIVIO"

# Verifica che l'esportazione sia avvenuta con successo
if [ $? -eq 0 ]; then
    log_message "✅ Messaggi esportati con successo in: $PERCORSO_ARCHIVIO"
    
    # Aggiungi separatore nel file
    echo "" >> "$PERCORSO_ARCHIVIO"
    echo "=== FINE ARCHIVIO BATCH $(date '+%Y-%m-%d %H:%M:%S') ===" >> "$PERCORSO_ARCHIVIO"
    echo "" >> "$PERCORSO_ARCHIVIO"
    
    # Elimina i messaggi archiviati dal database
    MESSAGGI_ELIMINATI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
        DELETE FROM chat_messaggi WHERE timestamp < '$DATA_LIMITE';
        SELECT ROW_COUNT();
    ")
    
    if [ $? -eq 0 ]; then
        log_message "✅ $MESSAGGI_ELIMINATI messaggi eliminati dal database"
        log_message "💾 Spazio liberato nel database"
        
        # Verifica dimensione file archivio
        DIMENSIONE_FILE=$(du -h "$PERCORSO_ARCHIVIO" | cut -f1)
        log_message "📁 Dimensione file archivio: $DIMENSIONE_FILE"
        
        # Ottimizza la tabella per recuperare spazio
        mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "OPTIMIZE TABLE chat_messaggi;" > /dev/null 2>&1
        log_message "🔧 Tabella chat_messaggi ottimizzata"
        
    else
        log_message "❌ Errore durante l'eliminazione dei messaggi dal database"
        exit 1
    fi
    
else
    log_message "❌ Errore durante l'esportazione dei messaggi"
    exit 1
fi

log_message "=== FINE ARCHIVIAZIONE CHAT GLOBALE ==="
log_message "📊 Riepilogo: $CONTA_MESSAGGI messaggi archiviati e rimossi"

exit 0
