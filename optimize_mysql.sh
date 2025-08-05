#!/bin/bash
# Script di ottimizzazione e manutenzione database MySQL per CRM
# Esegui questo script regolarmente per mantenere le performance ottimali
# Versione aggiornata con supporto per tabelle: conto_termico, enea, chat_read_status

LOG_FILE="/var/log/mysql_optimization.log"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== AVVIO OTTIMIZZAZIONE DATABASE $(date) ===${NC}"
echo "=== AVVIO OTTIMIZZAZIONE DATABASE $(date) ===" >> $LOG_FILE

# Funzione per eseguire query MySQL con gestione errori
run_mysql() {
    local query="$1"
    local result=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$query" 2>/dev/null)
    if [ $? -eq 0 ]; then
        echo "$result"
    else
        echo "ERRORE: Impossibile eseguire query: $query" >> $LOG_FILE
        return 1
    fi
}

# Funzione per formattare dimensioni
format_size() {
    local size=$1
    if [ "$size" -gt 1048576 ]; then
        echo "$(echo "scale=2; $size/1048576" | bc) MB"
    elif [ "$size" -gt 1024 ]; then
        echo "$(echo "scale=2; $size/1024" | bc) KB"
    else
        echo "$size bytes"
    fi
}

# 1. Controlla connessione database
echo -e "${YELLOW}Verifica connessione database...${NC}"
if ! run_mysql "SELECT 1;" > /dev/null; then
    echo -e "${RED}ERRORE: Impossibile connettersi al database${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Connessione database OK${NC}"

# 2. Controlla processi lenti
echo -e "${YELLOW}Controllo processi MySQL lenti...${NC}"
SLOW_QUERIES=$(run_mysql "SHOW GLOBAL STATUS LIKE 'Slow_queries';" | grep -v Variable_name | awk '{print $2}')
echo "Query lente rilevate: $SLOW_QUERIES" >> $LOG_FILE
echo -e "${BLUE}Query lente rilevate: $SLOW_QUERIES${NC}"

# 3. Analizza dimensioni e frammentazione di TUTTE le tabelle
echo -e "${YELLOW}Analisi completa delle tabelle...${NC}"
echo "Analisi frammentazione tabelle..." >> $LOG_FILE
run_mysql "
SELECT 
    TABLE_NAME,
    ROUND(DATA_LENGTH/1024/1024,2) AS 'Data Size (MB)',
    ROUND(INDEX_LENGTH/1024/1024,2) AS 'Index Size (MB)',
    ROUND(DATA_FREE/1024/1024,2) AS 'Free Space (MB)',
    ROUND((DATA_FREE/(DATA_LENGTH+INDEX_LENGTH))*100,2) AS 'Fragmentation %'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND TABLE_TYPE = 'BASE TABLE'
ORDER BY DATA_FREE DESC;
" >> $LOG_FILE

# 4. Lista dettagliata tabelle del sistema
echo -e "${YELLOW}Inventario tabelle sistema...${NC}"
SYSTEM_TABLES="clienti utenti task task_clienti chat_messaggi email_cronologia conto_termico enea chat_read_status"
echo "Tabelle nel sistema CRM:" >> $LOG_FILE
for table in $SYSTEM_TABLES; do
    TABLE_EXISTS=$(run_mysql "SHOW TABLES LIKE '$table';" | grep -v Tables_in | wc -l)
    if [ "$TABLE_EXISTS" -eq 1 ]; then
        ROWS=$(run_mysql "SELECT COUNT(*) FROM $table;" | grep -v count)
        echo -e "${GREEN}✓ $table${NC} - $ROWS record"
        echo "✓ $table - $ROWS record" >> $LOG_FILE
    else
        echo -e "${RED}✗ $table${NC} - TABELLA MANCANTE"
        echo "✗ $table - TABELLA MANCANTE" >> $LOG_FILE
    fi
done

# 5. Ottimizza tabelle con frammentazione > 5MB o > 10%
echo -e "${YELLOW}Ottimizzazione tabelle frammentate...${NC}"
echo "Ottimizzazione tabelle frammentate..." >> $LOG_FILE
FRAGMENTED_TABLES=$(run_mysql "
SELECT TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND (DATA_FREE > 5242880 OR (DATA_FREE/(DATA_LENGTH+INDEX_LENGTH))*100 > 10)
AND TABLE_TYPE = 'BASE TABLE'
" | grep -v TABLE_NAME)

if [ -n "$FRAGMENTED_TABLES" ]; then
    for table in $FRAGMENTED_TABLES; do
        echo -e "${YELLOW}Ottimizzazione tabella: $table${NC}"
        echo "Ottimizzazione tabella: $table" >> $LOG_FILE
        run_mysql "OPTIMIZE TABLE $table;" >> $LOG_FILE
    done
else
    echo -e "${GREEN}✓ Nessuna tabella necessita di ottimizzazione${NC}"
    echo "✓ Nessuna tabella necessita di ottimizzazione" >> $LOG_FILE
fi

# 6. Aggiorna statistiche TUTTE le tabelle del sistema
echo -e "${YELLOW}Aggiornamento statistiche tabelle...${NC}"
echo "Aggiornamento statistiche tabelle..." >> $LOG_FILE
run_mysql "
ANALYZE TABLE clienti;
ANALYZE TABLE utenti;
ANALYZE TABLE task;
ANALYZE TABLE task_clienti;
ANALYZE TABLE chat_messaggi;
ANALYZE TABLE email_cronologia;
ANALYZE TABLE conto_termico;
ANALYZE TABLE enea;
ANALYZE TABLE chat_read_status;
" >> $LOG_FILE

# 7. Verifica e crea indici mancanti per le nuove tabelle
echo -e "${YELLOW}Verifica indici tabelle...${NC}"
echo "Verifica indici tabelle..." >> $LOG_FILE

# Indici per conto_termico
run_mysql "
CREATE INDEX IF NOT EXISTS idx_conto_termico_cliente ON conto_termico(cliente_id);
CREATE INDEX IF NOT EXISTS idx_conto_termico_anno ON conto_termico(anno);
CREATE INDEX IF NOT EXISTS idx_conto_termico_esito ON conto_termico(esito);
" >> $LOG_FILE 2>&1

# Indici per enea
run_mysql "
CREATE INDEX IF NOT EXISTS idx_enea_cliente ON enea(cliente_id);
CREATE INDEX IF NOT EXISTS idx_enea_prima_tel ON enea(prima_tel);
CREATE INDEX IF NOT EXISTS idx_enea_richiesta_doc ON enea(richiesta_doc);
" >> $LOG_FILE 2>&1

# Indici per chat_read_status
run_mysql "
CREATE INDEX IF NOT EXISTS idx_chat_read_user_pratica ON chat_read_status(user_id, pratica_id);
CREATE INDEX IF NOT EXISTS idx_chat_read_last_read ON chat_read_status(last_read_at);
" >> $LOG_FILE 2>&1

echo -e "${GREEN}✓ Verifica indici completata${NC}"

# 8. Pulizia cache e ottimizzazioni varie
echo -e "${YELLOW}Pulizia cache e ottimizzazioni...${NC}"
echo "Flush query cache..." >> $LOG_FILE
run_mysql "FLUSH QUERY CACHE;" >> $LOG_FILE 2>&1
run_mysql "FLUSH TABLES;" >> $LOG_FILE 2>&1

# 9. Backup automatico delle tabelle critiche (opzionale)
echo -e "${YELLOW}Verifica backup recenti...${NC}"
BACKUP_DIR="/var/backups/mysql_crm"
if [ -d "$BACKUP_DIR" ]; then
    LAST_BACKUP=$(find "$BACKUP_DIR" -name "*.sql" -mtime -7 | wc -l)
    echo "Backup recenti (ultimi 7 giorni): $LAST_BACKUP" >> $LOG_FILE
    if [ "$LAST_BACKUP" -eq 0 ]; then
        echo -e "${RED}⚠ ATTENZIONE: Nessun backup recente trovato${NC}"
        echo "⚠ ATTENZIONE: Nessun backup recente trovato" >> $LOG_FILE
    else
        echo -e "${GREEN}✓ Backup recenti disponibili${NC}"
    fi
fi

# 10. Report performance finale dettagliato
echo -e "${YELLOW}Generazione report performance...${NC}"
echo "Report performance finale:" >> $LOG_FILE
run_mysql "
SELECT 
    'Uptime' as Metric, 
    ROUND(VARIABLE_VALUE/3600,2) as 'Hours' 
FROM information_schema.GLOBAL_STATUS 
WHERE VARIABLE_NAME = 'Uptime';

SELECT 
    'Total Questions' as Metric, 
    VARIABLE_VALUE as Value 
FROM information_schema.GLOBAL_STATUS 
WHERE VARIABLE_NAME = 'Questions';

SELECT 
    'Slow Queries' as Metric, 
    VARIABLE_VALUE as Value 
FROM information_schema.GLOBAL_STATUS 
WHERE VARIABLE_NAME = 'Slow_queries';

SELECT 
    'Buffer Pool Hit Rate' as Metric, 
    ROUND(
        (1 - (VARIABLE_VALUE / 
        (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS WHERE VARIABLE_NAME = 'Innodb_buffer_pool_read_requests'))
        ) * 100, 2
    ) as 'Percentage'
FROM information_schema.GLOBAL_STATUS 
WHERE VARIABLE_NAME = 'Innodb_buffer_pool_reads';
" >> $LOG_FILE

# 11. Controllo spazio disco database
echo -e "${YELLOW}Controllo spazio disco database...${NC}"
DB_SIZE=$(run_mysql "
SELECT 
    ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'DB Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME';
" | grep -v "DB Size" | head -1)

echo "Dimensione totale database: ${DB_SIZE} MB" >> $LOG_FILE
echo -e "${BLUE}Dimensione totale database: ${DB_SIZE} MB${NC}"

# 12. Riepilogo finale
echo -e "${GREEN}=== RIEPILOGO OTTIMIZZAZIONE ===${NC}"
echo "✓ Connessione database verificata"
echo "✓ Analisi frammentazione completata"
echo "✓ Statistiche tabelle aggiornate"
echo "✓ Indici verificati e creati"
echo "✓ Cache pulita"
echo "✓ Report performance generato"

echo "=== FINE OTTIMIZZAZIONE $(date) ===" >> $LOG_FILE
echo "" >> $LOG_FILE

# Messaggio finale
echo -e "${BLUE}Ottimizzazione completata. Log salvato in: $LOG_FILE${NC}"
echo -e "${YELLOW}Per risultati ottimali, eseguire questo script settimanalmente.${NC}"

# Nota: Per riavviare MySQL (decommentare se necessario)
# echo "Riavvio MySQL in corso..."
# systemctl restart mysql
