-- Script SQL per ottimizzare la gestione dei task clienti
-- Questo script migliora la struttura esistente e supporta entrambi gli approcci:
-- 1. Campo cliente_id diretto nella tabella task (opzionale)
-- 2. Tabella di associazione task_clienti (raccomandato)

-- Opzione 1: Aggiungi campo cliente_id alla tabella task (opzionale)
-- ALTER TABLE task ADD COLUMN cliente_id INT DEFAULT NULL AFTER id;
-- ALTER TABLE task ADD CONSTRAINT fk_task_cliente FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE CASCADE;

-- Opzione 2: Crea tabella di associazione (raccomandato - più flessibile)
CREATE TABLE IF NOT EXISTS task_clienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    cliente_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_task_client (task_id, cliente_id),
    INDEX idx_task_id (task_id),
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (task_id) REFERENCES task(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE CASCADE
);

-- Aggiungi indici per migliorare le performance se non esistono già
CREATE INDEX IF NOT EXISTS idx_task_scadenza ON task(scadenza);
CREATE INDEX IF NOT EXISTS idx_task_ricorrenza ON task(ricorrenza);
CREATE INDEX IF NOT EXISTS idx_clienti_ragione_sociale ON clienti(`Cognome/Ragione sociale`);

-- Vista per semplificare le query sui task dei clienti
CREATE OR REPLACE VIEW view_task_clienti AS
SELECT 
    t.id as task_id,
    t.descrizione,
    t.scadenza,
    t.ricorrenza,
    t.completato_da,
    tc.cliente_id,
    tc.created_at as associazione_creata,
    c.`Cognome/Ragione sociale`,
    c.`Nome`,
    c.`Codice fiscale`,
    CONCAT(COALESCE(c.`Nome`, ''), ' ', COALESCE(c.`Cognome/Ragione sociale`, '')) as nome_completo,
    CASE 
        WHEN t.scadenza < CURDATE() THEN 'scaduto'
        WHEN t.scadenza = CURDATE() THEN 'oggi'
        WHEN t.scadenza <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'settimana'
        ELSE 'futuro'
    END as stato_scadenza,
    CASE 
        WHEN t.ricorrenza IS NOT NULL AND t.ricorrenza > 0 THEN 'ricorrente'
        ELSE 'one-shot'
    END as tipo_task
FROM task t
JOIN task_clienti tc ON t.id = tc.task_id
JOIN clienti c ON tc.cliente_id = c.id;
