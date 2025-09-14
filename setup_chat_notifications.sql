-- Tabella per tracciare quando ogni utente ha letto per l'ultima volta ogni conversazione
CREATE TABLE IF NOT EXISTS user_conversation_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    conversation_id INT NOT NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_conversation (user_id, conversation_id),
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_user_conversation (user_id, conversation_id),
    INDEX idx_last_seen (last_seen)
);

-- Assicuriamoci che la chat globale esista
INSERT IGNORE INTO conversations (id, name, type, created_by, created_at) 
VALUES (1, 'Chat Globale', 'global', 1, NOW());

-- Aggiungiamo tutti gli utenti attivi alla chat globale se non ci sono già
INSERT IGNORE INTO conversation_participants (conversation_id, user_id, joined_at, is_active)
SELECT 1, id, NOW(), 1 
FROM utenti 
WHERE attivo = 1;
