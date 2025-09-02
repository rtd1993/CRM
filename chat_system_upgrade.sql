-- Aggiornamento del sistema chat per supportare WhatsApp-like interno
-- Data: 2025-09-02

-- 1. Crea tabella per le chat/conversazioni
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_type ENUM('globale', 'pratica', 'privata') NOT NULL,
    name VARCHAR(255) NULL, -- Nome della chat (per chat private)
    pratica_id INT NULL, -- Per chat delle pratiche
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (pratica_id) REFERENCES clienti(id) ON DELETE CASCADE,
    INDEX idx_chat_type_pratica (chat_type, pratica_id),
    INDEX idx_created_at (created_at)
);

-- 2. Crea tabella per i partecipanti alle chat
CREATE TABLE IF NOT EXISTS chat_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (chat_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (chat_id, user_id),
    INDEX idx_user_active (user_id, is_active)
);

-- 3. Aggiorna tabella messaggi per il nuovo sistema
CREATE TABLE IF NOT EXISTS chat_messages_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'file', 'system') DEFAULT 'text',
    file_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (chat_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_chat_created (chat_id, created_at),
    INDEX idx_user_created (user_id, created_at)
);

-- 4. Tabella per tracciare l'ultimo messaggio letto per utente
CREATE TABLE IF NOT EXISTS chat_read_status_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chat_id INT NOT NULL,
    last_read_message_id INT NULL,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unread_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (chat_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (last_read_message_id) REFERENCES chat_messages_new(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_chat (user_id, chat_id),
    INDEX idx_user_unread (user_id, unread_count),
    INDEX idx_last_read (last_read_at)
);

-- 5. Tabella per le sessioni chat attive (per riaprir le chat)
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chat_id INT NOT NULL,
    is_minimized TINYINT(1) DEFAULT 0,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (chat_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_chat_session (user_id, chat_id),
    INDEX idx_user_activity (user_id, last_activity)
);

-- 6. Inserisci chat globale predefinita
INSERT IGNORE INTO chat_conversations (id, chat_type, name, created_by) 
VALUES (1, 'globale', 'Chat Globale', 1);

-- 7. Aggiungi tutti gli utenti alla chat globale
INSERT IGNORE INTO chat_participants (chat_id, user_id)
SELECT 1, id FROM utenti WHERE id > 0;

-- 8. Migra i messaggi esistenti dalla chat globale
INSERT INTO chat_messages_new (chat_id, user_id, message, created_at)
SELECT 1, utente_id, messaggio, timestamp 
FROM chat_messaggi 
WHERE chat_id = 'globale' OR chat_id IS NULL
ORDER BY timestamp;

-- 9. Migra le chat delle pratiche
INSERT INTO chat_conversations (chat_type, pratica_id, name, created_by)
SELECT DISTINCT 'pratica', pratica_id, CONCAT('Pratica ', c.Cognome_Ragione_sociale), MIN(cp.utente_id)
FROM chat_pratiche cp
JOIN clienti c ON cp.pratica_id = c.id
GROUP BY pratica_id;

-- 10. Migra i messaggi delle pratiche
INSERT INTO chat_messages_new (chat_id, user_id, message, created_at)
SELECT cc.id, cp.utente_id, cp.messaggio, cp.timestamp
FROM chat_pratiche cp
JOIN chat_conversations cc ON cc.pratica_id = cp.pratica_id AND cc.chat_type = 'pratica';

-- 11. Aggiungi partecipanti alle chat delle pratiche (tutti gli utenti attivi)
INSERT IGNORE INTO chat_participants (chat_id, user_id)
SELECT cc.id, u.id
FROM chat_conversations cc
CROSS JOIN utenti u
WHERE cc.chat_type = 'pratica' AND u.id > 0;
