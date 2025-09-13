#!/bin/bash

# Script per l'archiviazione mensile automatica delle chat
# - Chat globali più vecchie di 1 anno: archiviate in local_drive/ASContabilmente/archivio/chat/ANNO/MESE/
# - Chat private più vecchie di 1 anno: eliminate definitivamente
# Programmato per essere eseguito il 1° di ogni mese tramite cron

SCRIPT_DIR="/var/www/CRM"
ARCHIVIO_BASE="/var/www/CRM/local_drive/ASContabilmente/archivio/chat"
LOG_FILE="/var/www/CRM/logs/chat_archivio_mensile.log"
DB_NAME="crm"
DB_USER="root"
DB_PASS="Admin123!"

# Funzione di logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Crea directory log se non esiste
mkdir -p /var/www/CRM/logs
mkdir -p "$ARCHIVIO_BASE"

log_message "=== INIZIO ARCHIVIAZIONE MENSILE CHAT ==="

# Calcola la data di 1 anno fa
DATA_LIMITE=$(date -d '1 year ago' '+%Y-%m-%d %H:%M:%S')
ANNO_ARCHIVIO=$(date -d '1 year ago' '+%Y')
MESE_ARCHIVIO=$(date -d '1 year ago' '+%m')
NOME_MESE=$(date -d '1 year ago' '+%B' | tr '[:upper:]' '[:lower:]')

# Crea directory per l'archivio
ARCHIVIO_DIR="${ARCHIVIO_BASE}/${ANNO_ARCHIVIO}/${MESE_ARCHIVIO}"
mkdir -p "$ARCHIVIO_DIR"

log_message "Data limite per archiviazione: $DATA_LIMITE"
log_message "Directory archivio: $ARCHIVIO_DIR"

# === ARCHIVIAZIONE CHAT GLOBALI ===
log_message "--- INIZIO ARCHIVIAZIONE CHAT GLOBALI ---"

# Verifica se ci sono messaggi globali da archiviare
CONTA_GLOBALI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
    SELECT COUNT(*) 
    FROM chat_conversations cc
    JOIN chat_messages cm ON cc.id = cm.conversation_id
    WHERE cc.tipo = 'globale' 
    AND cm.created_at < '$DATA_LIMITE'
")

if [ "$CONTA_GLOBALI" -gt 0 ]; then
    log_message "Trovati $CONTA_GLOBALI messaggi globali da archiviare"
    
    # Nome file archivio per chat globali
    NOME_FILE_GLOBALI="chat_globali_${MESE_ARCHIVIO}_${ANNO_ARCHIVIO}.txt"
    PERCORSO_ARCHIVIO_GLOBALI="${ARCHIVIO_DIR}/${NOME_FILE_GLOBALI}"
    
    # Crea l'intestazione del file
    cat > "$PERCORSO_ARCHIVIO_GLOBALI" << EOF
# ARCHIVIO CHAT GLOBALI - $NOME_MESE $ANNO_ARCHIVIO
# Messaggi archiviati automaticamente il $(date '+%Y-%m-%d %H:%M:%S')
# Messaggi più vecchi del: $DATA_LIMITE
# Tipo: Chat Globali (conversazioni pubbliche)
================================================================================================

EOF
    
    # Esporta i messaggi globali nel file di archivio
    mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "
        SELECT 
            DATE_FORMAT(cm.created_at, '%Y-%m-%d %H:%i:%s') as 'Data/Ora',
            u.nome as 'Utente',
            CASE 
                WHEN LENGTH(cm.message) > 100 THEN CONCAT(LEFT(cm.message, 97), '...')
                ELSE cm.message 
            END as 'Messaggio'
        FROM chat_conversations cc
        JOIN chat_messages cm ON cc.id = cm.conversation_id
        JOIN utenti u ON cm.user_id = u.id
        WHERE cc.tipo = 'globale' 
        AND cm.created_at < '$DATA_LIMITE'
        ORDER BY cm.created_at ASC
    " --table >> "$PERCORSO_ARCHIVIO_GLOBALI"
    
    if [ $? -eq 0 ]; then
        log_message "✅ Chat globali esportate con successo in: $PERCORSO_ARCHIVIO_GLOBALI"
        
        # Elimina i messaggi globali archiviati
        GLOBALI_ELIMINATI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
            DELETE cm FROM chat_messages cm
            JOIN chat_conversations cc ON cm.conversation_id = cc.id
            WHERE cc.tipo = 'globale' AND cm.created_at < '$DATA_LIMITE';
            SELECT ROW_COUNT();
        ")
        
        if [ $? -eq 0 ]; then
            log_message "✅ $GLOBALI_ELIMINATI messaggi globali eliminati dal database"
            
            # Verifica dimensione file archivio
            DIMENSIONE_FILE=$(du -h "$PERCORSO_ARCHIVIO_GLOBALI" | cut -f1)
            log_message "📁 Dimensione file archivio globali: $DIMENSIONE_FILE"
        else
            log_message "❌ Errore durante l'eliminazione dei messaggi globali dal database"
        fi
    else
        log_message "❌ Errore durante l'esportazione dei messaggi globali"
    fi
else
    log_message "Nessun messaggio globale da archiviare"
fi

# === ELIMINAZIONE CHAT PRIVATE ===
log_message "--- INIZIO ELIMINAZIONE CHAT PRIVATE ---"

# Conta i messaggi privati da eliminare
CONTA_PRIVATI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
    SELECT COUNT(*) 
    FROM chat_conversations cc
    JOIN chat_messages cm ON cc.id = cm.conversation_id
    WHERE cc.tipo = 'privata' 
    AND cm.created_at < '$DATA_LIMITE'
")

if [ "$CONTA_PRIVATI" -gt 0 ]; then
    log_message "Trovati $CONTA_PRIVATI messaggi privati da eliminare definitivamente"
    
    # Log delle conversazioni che verranno eliminate (solo statistiche, non contenuto)
    LOG_CONVERSAZIONI="${ARCHIVIO_DIR}/conversazioni_private_eliminate_${MESE_ARCHIVIO}_${ANNO_ARCHIVIO}.log"
    cat > "$LOG_CONVERSAZIONI" << EOF
# LOG ELIMINAZIONE CONVERSAZIONI PRIVATE - $NOME_MESE $ANNO_ARCHIVIO
# Eliminate automaticamente il $(date '+%Y-%m-%d %H:%M:%S')
# Conversazioni più vecchie del: $DATA_LIMITE
# NOTA: Solo statistiche, non contenuto dei messaggi per privacy
================================================================================================

EOF
    
    # Log delle conversazioni eliminate (solo metadati per audit)
    mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "
        SELECT 
            cc.id as 'ID_Conversazione',
            DATE_FORMAT(MIN(cm.created_at), '%Y-%m-%d %H:%i:%s') as 'Primo_Messaggio',
            DATE_FORMAT(MAX(cm.created_at), '%Y-%m-%d %H:%i:%s') as 'Ultimo_Messaggio',
            COUNT(cm.id) as 'Numero_Messaggi',
            GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome) as 'Partecipanti'
        FROM chat_conversations cc
        JOIN chat_messages cm ON cc.id = cm.conversation_id
        JOIN utenti u ON cm.user_id = u.id
        WHERE cc.tipo = 'privata' 
        AND cm.created_at < '$DATA_LIMITE'
        GROUP BY cc.id
        ORDER BY MIN(cm.created_at)
    " --table >> "$LOG_CONVERSAZIONI"
    
    # Elimina definitivamente i messaggi privati
    PRIVATI_ELIMINATI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
        DELETE cm FROM chat_messages cm
        JOIN chat_conversations cc ON cm.conversation_id = cc.id
        WHERE cc.tipo = 'privata' AND cm.created_at < '$DATA_LIMITE';
        SELECT ROW_COUNT();
    ")
    
    if [ $? -eq 0 ]; then
        log_message "✅ $PRIVATI_ELIMINATI messaggi privati eliminati definitivamente"
        
        # Elimina anche le conversazioni vuote
        CONVERSAZIONI_VUOTE=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "
            DELETE cc FROM chat_conversations cc
            LEFT JOIN chat_messages cm ON cc.id = cm.conversation_id
            WHERE cc.tipo = 'privata' AND cm.id IS NULL;
            SELECT ROW_COUNT();
        ")
        
        log_message "✅ $CONVERSAZIONI_VUOTE conversazioni private vuote eliminate"
        
        # Verifica dimensione log
        DIMENSIONE_LOG=$(du -h "$LOG_CONVERSAZIONI" | cut -f1)
        log_message "📁 Dimensione log eliminazioni: $DIMENSIONE_LOG"
    else
        log_message "❌ Errore durante l'eliminazione dei messaggi privati"
    fi
else
    log_message "Nessun messaggio privato da eliminare"
fi

# === OTTIMIZZAZIONE TABELLE ===
log_message "--- OTTIMIZZAZIONE TABELLE CHAT ---"

mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "OPTIMIZE TABLE chat_conversations, chat_messages;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    log_message "🔧 Tabelle chat ottimizzate"
else
    log_message "⚠️ Errore durante l'ottimizzazione tabelle"
fi

# === STATISTICHE FINALI ===
TOTALE_MESSAGGI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "SELECT COUNT(*) FROM chat_messages")
TOTALE_CONVERSAZIONI=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -sN -e "SELECT COUNT(*) FROM chat_conversations")

log_message "📊 STATISTICHE POST-ARCHIVIAZIONE:"
log_message "   - Messaggi globali archiviati: $CONTA_GLOBALI"
log_message "   - Messaggi privati eliminati: $CONTA_PRIVATI"
log_message "   - Messaggi rimanenti nel DB: $TOTALE_MESSAGGI"
log_message "   - Conversazioni attive: $TOTALE_CONVERSAZIONI"

log_message "=== FINE ARCHIVIAZIONE MENSILE CHAT ==="

exit 0
