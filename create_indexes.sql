-- Script SQL per creare indici ottimizzati per il CRM
-- Eseguire dopo l'installazione delle nuove tabelle: conto_termico, enea, chat_read_status
-- Versione: 2.0 - Aggiornata per le nuove funzionalità

USE crm;

-- ==================================================
-- INDICI PER TABELLA CONTO_TERMICO
-- ==================================================
-- Indice per ricerche per cliente (molto frequente)
CREATE INDEX IF NOT EXISTS idx_conto_termico_cliente_id ON conto_termico(cliente_id);

-- Indice per ricerche per anno (filtro principale)
CREATE INDEX IF NOT EXISTS idx_conto_termico_anno ON conto_termico(anno);

-- Indice per filtro esito
CREATE INDEX IF NOT EXISTS idx_conto_termico_esito ON conto_termico(esito);

-- Indice composto per ricerche anno + cliente (query ottimizzata)
CREATE INDEX IF NOT EXISTS idx_conto_termico_anno_cliente ON conto_termico(anno, cliente_id);

-- Indice per ordinamento per data termine
CREATE INDEX IF NOT EXISTS idx_conto_termico_data_termine ON conto_termico(data_termine);

-- Indice per ricerche per mese
CREATE INDEX IF NOT EXISTS idx_conto_termico_mese ON conto_termico(mese);

-- Indice per timestamp di creazione/aggiornamento
CREATE INDEX IF NOT EXISTS idx_conto_termico_created_at ON conto_termico(created_at);
CREATE INDEX IF NOT EXISTS idx_conto_termico_updated_at ON conto_termico(updated_at);

-- ==================================================
-- INDICI PER TABELLA ENEA
-- ==================================================
-- Indice per ricerche per cliente (molto frequente)
CREATE INDEX IF NOT EXISTS idx_enea_cliente_id ON enea(cliente_id);

-- Indice per data prima telefonata (filtraggio cronologico)
CREATE INDEX IF NOT EXISTS idx_enea_prima_tel ON enea(prima_tel);

-- Indice per data richiesta documenti
CREATE INDEX IF NOT EXISTS idx_enea_richiesta_doc ON enea(richiesta_doc);

-- Indice per bonifico (ricerche per importo)
CREATE INDEX IF NOT EXISTS idx_enea_bonifico_ns ON enea(bonifico_ns);

-- Indici per stati documenti (per statistiche completamento)
CREATE INDEX IF NOT EXISTS idx_enea_copia_fatt_fornitore ON enea(copia_fatt_fornitore);
CREATE INDEX IF NOT EXISTS idx_enea_schede_tecniche ON enea(schede_tecniche);
CREATE INDEX IF NOT EXISTS idx_enea_visura_catastale ON enea(visura_catastale);
CREATE INDEX IF NOT EXISTS idx_enea_firma_notorio ON enea(firma_notorio);
CREATE INDEX IF NOT EXISTS idx_enea_firma_delega_ag_entr ON enea(firma_delega_ag_entr);
CREATE INDEX IF NOT EXISTS idx_enea_firma_delega_enea ON enea(firma_delega_enea);
CREATE INDEX IF NOT EXISTS idx_enea_consenso ON enea(consenso);
CREATE INDEX IF NOT EXISTS idx_enea_ev_atto_notorio ON enea(ev_atto_notorio);

-- Indice composto per query complesse cliente + data
CREATE INDEX IF NOT EXISTS idx_enea_cliente_prima_tel ON enea(cliente_id, prima_tel);

-- Indici per timestamp
CREATE INDEX IF NOT EXISTS idx_enea_created_at ON enea(created_at);
CREATE INDEX IF NOT EXISTS idx_enea_updated_at ON enea(updated_at);

-- ==================================================
-- INDICI PER TABELLA CHAT_READ_STATUS
-- ==================================================
-- Indice composto principale per query di lettura
CREATE INDEX IF NOT EXISTS idx_chat_read_user_pratica ON chat_read_status(user_id, pratica_id);

-- Indice per ricerche per utente
CREATE INDEX IF NOT EXISTS idx_chat_read_user_id ON chat_read_status(user_id);

-- Indice per ricerche per pratica
CREATE INDEX IF NOT EXISTS idx_chat_read_pratica_id ON chat_read_status(pratica_id);

-- Indice per timestamp ultima lettura
CREATE INDEX IF NOT EXISTS idx_chat_read_last_read_at ON chat_read_status(last_read_at);

-- Indice per conteggio messaggi non letti
CREATE INDEX IF NOT EXISTS idx_chat_read_unread_count ON chat_read_status(unread_count);

-- ==================================================
-- OTTIMIZZAZIONE INDICI ESISTENTI (se necessario)
-- ==================================================
-- Verifica indici sulla tabella clienti per le FK
SHOW INDEX FROM clienti;

-- Indici per task_clienti se non esistono
CREATE INDEX IF NOT EXISTS idx_task_clienti_cliente_id ON task_clienti(cliente_id);
CREATE INDEX IF NOT EXISTS idx_task_clienti_created_at ON task_clienti(created_at);

-- Indici per chat_messaggi per performance
CREATE INDEX IF NOT EXISTS idx_chat_messaggi_pratica_id ON chat_messaggi(pratica_id);
CREATE INDEX IF NOT EXISTS idx_chat_messaggi_timestamp ON chat_messaggi(timestamp);

-- ==================================================
-- FULL TEXT SEARCH per ricerche testuali
-- ==================================================
-- Full text per descrizioni conto termico
ALTER TABLE conto_termico ADD FULLTEXT(modello_stufa) IF NOT EXISTS;

-- Full text per descrizioni ENEA
ALTER TABLE enea ADD FULLTEXT(descrizione) IF NOT EXISTS;
ALTER TABLE enea ADD FULLTEXT(ns_prev_n_del, ns_ord_n_del, ns_fatt_n_del) IF NOT EXISTS;

-- Full text per ricerca clienti
ALTER TABLE clienti ADD FULLTEXT(`Cognome_Ragione_sociale`, `Nome`) IF NOT EXISTS;

-- ==================================================
-- VERIFICA PERFORMANCE INDICI
-- ==================================================
-- Query per verificare l'utilizzo degli indici
/*
-- Eseguire dopo qualche giorno di utilizzo per verificare performance:

SELECT 
    table_name,
    index_name,
    cardinality,
    seq_in_index,
    column_name
FROM information_schema.statistics 
WHERE table_schema = 'crm' 
AND table_name IN ('conto_termico', 'enea', 'chat_read_status')
ORDER BY table_name, index_name, seq_in_index;

-- Query per verificare query lente
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log 
WHERE sql_text LIKE '%conto_termico%' 
   OR sql_text LIKE '%enea%' 
   OR sql_text LIKE '%chat_read_status%'
ORDER BY query_time DESC 
LIMIT 10;
*/

-- ==================================================
-- STATISTICHE FINALI
-- ==================================================
-- Aggiorna le statistiche delle tabelle
ANALYZE TABLE conto_termico;
ANALYZE TABLE enea;
ANALYZE TABLE chat_read_status;

SELECT CONCAT('✓ Indici creati per le tabelle: conto_termico, enea, chat_read_status') AS Status;
SELECT CONCAT('✓ ', COUNT(*), ' indici totali creati/verificati') AS IndexCount 
FROM information_schema.statistics 
WHERE table_schema = 'crm' 
AND table_name IN ('conto_termico', 'enea', 'chat_read_status');
