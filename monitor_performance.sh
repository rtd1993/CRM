#!/bin/bash
# Script di monitoraggio performance database CRM
# Monitora performance delle tabelle principali e nuove funzionalità

LOG_FILE="/var/log/mysql_performance_monitor.log"
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${BLUE}=== MONITORAGGIO PERFORMANCE CRM $(date) ===${NC}"
echo "=== MONITORAGGIO PERFORMANCE CRM $(date) ===" >> $LOG_FILE

# Funzione per eseguire query MySQL
run_mysql() {
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$1" 2>/dev/null
}

# 1. Statistiche generali database
echo -e "${CYAN}📊 STATISTICHE GENERALI DATABASE${NC}"
echo "📊 STATISTICHE GENERALI DATABASE" >> $LOG_FILE

DB_STATS=$(run_mysql "
SELECT 
    ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'DB Size (MB)',
    COUNT(*) AS 'Total Tables',
    ROUND(SUM(DATA_LENGTH) / 1024 / 1024, 2) AS 'Data Size (MB)',
    ROUND(SUM(INDEX_LENGTH) / 1024 / 1024, 2) AS 'Index Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME';
")
echo "$DB_STATS"
echo "$DB_STATS" >> $LOG_FILE

# 2. Conteggi record per tabella
echo -e "\n${CYAN}📈 CONTEGGI RECORD PER TABELLA${NC}"
echo "📈 CONTEGGI RECORD PER TABELLA" >> $LOG_FILE

TABLES=("clienti" "utenti" "task" "task_clienti" "chat_messaggi" "email_cronologia" "conto_termico" "enea" "chat_read_status")

for table in "${TABLES[@]}"; do
    TABLE_EXISTS=$(run_mysql "SHOW TABLES LIKE '$table';" | grep -v Tables_in | wc -l)
    if [ "$TABLE_EXISTS" -eq 1 ]; then
        COUNT=$(run_mysql "SELECT COUNT(*) FROM $table;" | grep -v count | head -1)
        SIZE=$(run_mysql "
            SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'Size (MB)'
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = '$DB_NAME' AND TABLE_NAME = '$table';
        " | grep -v "Size" | head -1)
        
        echo -e "${GREEN}$table:${NC} $COUNT record - ${SIZE} MB"
        echo "$table: $COUNT record - ${SIZE} MB" >> $LOG_FILE
    else
        echo -e "${RED}$table: TABELLA NON TROVATA${NC}"
        echo "$table: TABELLA NON TROVATA" >> $LOG_FILE
    fi
done

# 3. Performance query frequenti
echo -e "\n${CYAN}⚡ PERFORMANCE QUERY FREQUENTI${NC}"
echo "⚡ PERFORMANCE QUERY FREQUENTI" >> $LOG_FILE

# Test query conto_termico
echo -e "${YELLOW}Test query Conto Termico...${NC}"
CONTO_QUERY_TIME=$(run_mysql "
SELECT BENCHMARK(1000, (
    SELECT COUNT(*) 
    FROM conto_termico ct 
    JOIN clienti c ON ct.cliente_id = c.id 
    WHERE ct.anno = YEAR(CURDATE())
)) AS 'Query Time';
" | grep -v "Query Time" | head -1)
echo "Query conto_termico con JOIN: ${CONTO_QUERY_TIME}" >> $LOG_FILE

# Test query ENEA
echo -e "${YELLOW}Test query ENEA...${NC}"
ENEA_QUERY_TIME=$(run_mysql "
SELECT BENCHMARK(1000, (
    SELECT COUNT(*) 
    FROM enea e 
    JOIN clienti c ON e.cliente_id = c.id 
    WHERE e.prima_tel >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
)) AS 'Query Time';
" | grep -v "Query Time" | head -1)
echo "Query ENEA con JOIN e filtro data: ${ENEA_QUERY_TIME}" >> $LOG_FILE

# 4. Analisi indici utilizzati
echo -e "\n${CYAN}🔍 ANALISI UTILIZZO INDICI${NC}"
echo "🔍 ANALISI UTILIZZO INDICI" >> $LOG_FILE

INDEX_USAGE=$(run_mysql "
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    CARDINALITY,
    COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND TABLE_NAME IN ('conto_termico', 'enea', 'chat_read_status')
AND CARDINALITY > 0
ORDER BY TABLE_NAME, CARDINALITY DESC;
")
echo "$INDEX_USAGE" >> $LOG_FILE

# 5. Query lente recenti
echo -e "\n${CYAN}🐌 QUERY LENTE RECENTI${NC}"
echo "🐌 QUERY LENTE RECENTI" >> $LOG_FILE

SLOW_QUERIES=$(run_mysql "SHOW GLOBAL STATUS LIKE 'Slow_queries';" | grep -v Variable_name | awk '{print $2}')
echo -e "Query lente totali: ${RED}$SLOW_QUERIES${NC}"
echo "Query lente totali: $SLOW_QUERIES" >> $LOG_FILE

# 6. Monitoraggio spazio disco e frammentazione
echo -e "\n${CYAN}💾 FRAMMENTAZIONE TABELLE${NC}"
echo "💾 FRAMMENTAZIONE TABELLE" >> $LOG_FILE

FRAGMENTATION=$(run_mysql "
SELECT 
    TABLE_NAME,
    ROUND(DATA_LENGTH/1024/1024,2) AS 'Data (MB)',
    ROUND(INDEX_LENGTH/1024/1024,2) AS 'Index (MB)',
    ROUND(DATA_FREE/1024/1024,2) AS 'Free (MB)',
    ROUND((DATA_FREE/(DATA_LENGTH+INDEX_LENGTH))*100,2) AS 'Frag %'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND TABLE_NAME IN ('conto_termico', 'enea', 'chat_read_status', 'clienti', 'chat_messaggi')
AND (DATA_LENGTH + INDEX_LENGTH) > 0
ORDER BY DATA_FREE DESC;
")
echo "$FRAGMENTATION"
echo "$FRAGMENTATION" >> $LOG_FILE

# 7. Statistiche connessioni e cache
echo -e "\n${CYAN}🔗 STATISTICHE CONNESSIONI E CACHE${NC}"
echo "🔗 STATISTICHE CONNESSIONI E CACHE" >> $LOG_FILE

CONNECTION_STATS=$(run_mysql "
SHOW GLOBAL STATUS WHERE Variable_name IN (
    'Connections', 
    'Max_used_connections', 
    'Threads_connected', 
    'Threads_running',
    'Query_cache_hits',
    'Query_cache_inserts',
    'Query_cache_not_cached'
);
")
echo "$CONNECTION_STATS"
echo "$CONNECTION_STATS" >> $LOG_FILE

# 8. Buffer pool statistics
echo -e "\n${CYAN}📊 INNODB BUFFER POOL${NC}"
echo "📊 INNODB BUFFER POOL" >> $LOG_FILE

BUFFER_STATS=$(run_mysql "
SELECT 
    'Buffer Pool Size' as Metric,
    ROUND(@@innodb_buffer_pool_size/1024/1024/1024,2) as 'Value (GB)'
UNION ALL
SELECT 
    'Buffer Pool Hit Rate' as Metric,
    ROUND(
        (1 - (
            (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS WHERE VARIABLE_NAME = 'Innodb_buffer_pool_reads') /
            (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS WHERE VARIABLE_NAME = 'Innodb_buffer_pool_read_requests')
        )) * 100, 2
    ) as 'Value (GB)';
")
echo "$BUFFER_STATS"
echo "$BUFFER_STATS" >> $LOG_FILE

# 9. Controllo crescita dati
echo -e "\n${CYAN}📈 CRESCITA DATI (ultimi record)${NC}"
echo "📈 CRESCITA DATI (ultimi record)" >> $LOG_FILE

# Record recenti per ogni tabella principale
for table in "conto_termico" "enea" "chat_messaggi"; do
    TABLE_EXISTS=$(run_mysql "SHOW TABLES LIKE '$table';" | grep -v Tables_in | wc -l)
    if [ "$TABLE_EXISTS" -eq 1 ]; then
        RECENT_COUNT=$(run_mysql "
            SELECT COUNT(*) 
            FROM $table 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY);
        " | grep -v count | head -1)
        echo -e "${GREEN}$table:${NC} $RECENT_COUNT record negli ultimi 7 giorni"
        echo "$table: $RECENT_COUNT record negli ultimi 7 giorni" >> $LOG_FILE
    fi
done

# 10. Raccomandazioni automatiche
echo -e "\n${CYAN}💡 RACCOMANDAZIONI AUTOMATICHE${NC}"
echo "💡 RACCOMANDAZIONI AUTOMATICHE" >> $LOG_FILE

# Controlla se ci sono tabelle che necessitano ottimizzazione
NEEDS_OPTIMIZATION=$(run_mysql "
SELECT COUNT(*) 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND (DATA_FREE > 10485760 OR (DATA_FREE/(DATA_LENGTH+INDEX_LENGTH))*100 > 15);
" | grep -v count | head -1)

if [ "$NEEDS_OPTIMIZATION" -gt 0 ]; then
    echo -e "${RED}⚠ $NEEDS_OPTIMIZATION tabelle necessitano di ottimizzazione${NC}"
    echo "⚠ $NEEDS_OPTIMIZATION tabelle necessitano di ottimizzazione" >> $LOG_FILE
    echo -e "${YELLOW}Eseguire: ./optimize_mysql.sh${NC}"
else
    echo -e "${GREEN}✓ Tutte le tabelle sono ottimizzate${NC}"
    echo "✓ Tutte le tabelle sono ottimizzate" >> $LOG_FILE
fi

# Controlla hit rate della query cache
CACHE_HIT_RATE=$(run_mysql "
SELECT ROUND(
    (Qcache_hits / (Qcache_hits + Qcache_inserts)) * 100, 2
) as hit_rate
FROM (
    SELECT 
        (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS WHERE VARIABLE_NAME = 'Qcache_hits') as Qcache_hits,
        (SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS WHERE VARIABLE_NAME = 'Qcache_inserts') as Qcache_inserts
) as cache_stats;
" | grep -v hit_rate | head -1)

if (( $(echo "$CACHE_HIT_RATE < 80" | bc -l) )); then
    echo -e "${YELLOW}⚠ Hit rate query cache basso: ${CACHE_HIT_RATE}%${NC}"
    echo "⚠ Hit rate query cache basso: ${CACHE_HIT_RATE}%" >> $LOG_FILE
    echo -e "${YELLOW}Considerare di aumentare query_cache_size${NC}"
else
    echo -e "${GREEN}✓ Hit rate query cache ottimo: ${CACHE_HIT_RATE}%${NC}"
    echo "✓ Hit rate query cache ottimo: ${CACHE_HIT_RATE}%" >> $LOG_FILE
fi

echo -e "\n${BLUE}=== FINE MONITORAGGIO $(date) ===${NC}"
echo "=== FINE MONITORAGGIO $(date) ===" >> $LOG_FILE
echo "" >> $LOG_FILE

echo -e "${GREEN}Report salvato in: $LOG_FILE${NC}"
