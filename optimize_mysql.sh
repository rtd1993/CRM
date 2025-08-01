#!/bin/bash
# Script di ottimizzazione e manutenzione database MySQL per CRM
# Esegui questo script regolarmente per mantenere le performance ottimali

LOG_FILE="/var/log/mysql_optimization.log"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"

echo "=== AVVIO OTTIMIZZAZIONE DATABASE $(date) ===" >> $LOG_FILE

# Funzione per eseguire query MySQL
run_mysql() {
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$1" 2>/dev/null
}

# 1. Controlla processi lenti
echo "Controllo processi MySQL lenti..." >> $LOG_FILE
SLOW_QUERIES=$(run_mysql "SHOW GLOBAL STATUS LIKE 'Slow_queries';" | grep -v Variable_name | awk '{print $2}')
echo "Query lente rilevate: $SLOW_QUERIES" >> $LOG_FILE

# 2. Analizza tabelle frammentate
echo "Analisi frammentazione tabelle..." >> $LOG_FILE
run_mysql "
SELECT 
    TABLE_NAME,
    ROUND(DATA_LENGTH/1024/1024,2) AS 'Data Size (MB)',
    ROUND(INDEX_LENGTH/1024/1024,2) AS 'Index Size (MB)',
    ROUND(DATA_FREE/1024/1024,2) AS 'Free Space (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND DATA_FREE > 0
ORDER BY DATA_FREE DESC;
" >> $LOG_FILE

# 3. Ottimizza tabelle con frammentazione > 10MB
echo "Ottimizzazione tabelle frammentate..." >> $LOG_FILE
FRAGMENTED_TABLES=$(run_mysql "
SELECT TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND DATA_FREE > 10485760
" | grep -v TABLE_NAME)

for table in $FRAGMENTED_TABLES; do
    echo "Ottimizzazione tabella: $table" >> $LOG_FILE
    run_mysql "OPTIMIZE TABLE $table;"
done

# 4. Aggiorna statistiche tabelle
echo "Aggiornamento statistiche tabelle..." >> $LOG_FILE
run_mysql "
ANALYZE TABLE clienti;
ANALYZE TABLE utenti;
ANALYZE TABLE task;
ANALYZE TABLE task_clienti;
ANALYZE TABLE chat_messaggi;
ANALYZE TABLE email_cronologia;
"

# 5. Pulizia query cache se presente
echo "Flush query cache..." >> $LOG_FILE
run_mysql "FLUSH QUERY CACHE;"

# 6. Report performance finale
echo "Report performance finale:" >> $LOG_FILE
run_mysql "
SHOW GLOBAL STATUS LIKE 'Questions';
SHOW GLOBAL STATUS LIKE 'Uptime';
SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool_read_requests';
SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool_reads';
" >> $LOG_FILE

echo "=== FINE OTTIMIZZAZIONE $(date) ===" >> $LOG_FILE
echo "" >> $LOG_FILE

# Riavvia il servizio MySQL se necessario (opzionale)
# systemctl restart mysql

echo "Ottimizzazione completata. Log salvato in: $LOG_FILE"
