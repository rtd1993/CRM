-- SISTEMA CHAT FOOTER WHATSAPP-LIKE
-- Database Schema Complete
-- Data: 2025-09-02

-- ==================================================================
-- FASE 1: CREAZIONE TABELLE PRINCIPALI
-- ==================================================================

-- Conversazioni/Chat (Globale, Pratiche, Private)
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('globale', 'pratica', 'privata') NOT NULL,
    name VARCHAR(255) NULL COMMENT 'Nome chat (per globali/gruppi)',
    client_id INT NULL COMMENT 'Per chat pratiche (FK clienti)',
    user1_id INT NULL COMMENT 'Per chat private (primo utente)',
    user2_id INT NULL COMMENT 'Per chat private (secondo utente)',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP NULL COMMENT 'Per ordinamento chat attive',
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clienti(id) ON DELETE CASCADE,
    FOREIGN KEY (user1_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_type_client (type, client_id),
    INDEX idx_private_chat (type, user1_id, user2_id),
    INDEX idx_last_message (last_message_at DESC),
    INDEX idx_active (is_active, last_message_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messaggi di tutte le chat
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'system') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_conversation_time (conversation_id, created_at),
    INDEX idx_user_time (user_id, created_at),
    INDEX idx_not_deleted (is_deleted, created_at),
    FULLTEXT KEY idx_message_search (message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partecipanti delle chat (per chat di gruppo)
CREATE TABLE IF NOT EXISTS chat_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (conversation_id, user_id),
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_conversation_active (conversation_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status lettura messaggi per utente
CREATE TABLE IF NOT EXISTS chat_read_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    conversation_id INT NOT NULL,
    last_read_message_id INT NULL,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unread_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (last_read_message_id) REFERENCES chat_messages(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_conversation (user_id, conversation_id),
    INDEX idx_user_unread (user_id, unread_count),
    INDEX idx_conversation_unread (conversation_id, unread_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessioni utenti per status online/offline
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_online BOOLEAN DEFAULT TRUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_session (user_id),
    INDEX idx_online_activity (is_online, last_activity),
    INDEX idx_session_cleanup (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurazione Telegram per notifiche offline
CREATE TABLE IF NOT EXISTS user_telegram_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    telegram_chat_id VARCHAR(255) NULL,
    telegram_username VARCHAR(255) NULL,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_telegram (user_id),
    INDEX idx_notifications_enabled (notifications_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- FASE 2: DATI INIZIALI
-- ==================================================================

-- Chat globale di default
INSERT IGNORE INTO chat_conversations (id, type, name, created_by, created_at, is_active) 
VALUES (1, 'globale', 'Chat Generale', 1, NOW(), TRUE);

-- Aggiungi tutti gli utenti esistenti alla chat globale
INSERT IGNORE INTO chat_participants (conversation_id, user_id, joined_at, is_active)
SELECT 1, id, NOW(), TRUE FROM utenti WHERE id > 0;

-- Inizializza status lettura per tutti gli utenti nella chat globale
INSERT IGNORE INTO chat_read_status (user_id, conversation_id, unread_count)
SELECT id, 1, 0 FROM utenti WHERE id > 0;

-- ==================================================================
-- FASE 3: STORED PROCEDURES UTILI
-- ==================================================================

DELIMITER //

-- Procedure per ottenere o creare chat privata
CREATE PROCEDURE IF NOT EXISTS GetOrCreatePrivateChat(
    IN p_user1_id INT,
    IN p_user2_id INT,
    OUT p_conversation_id INT
)
BEGIN
    DECLARE v_existing_id INT DEFAULT NULL;
    
    -- Cerca chat esistente (indipendentemente dall'ordine degli utenti)
    SELECT id INTO v_existing_id
    FROM chat_conversations 
    WHERE type = 'privata' 
    AND is_active = TRUE
    AND ((user1_id = p_user1_id AND user2_id = p_user2_id) 
         OR (user1_id = p_user2_id AND user2_id = p_user1_id))
    LIMIT 1;
    
    IF v_existing_id IS NOT NULL THEN
        -- Chat esistente trovata
        SET p_conversation_id = v_existing_id;
    ELSE
        -- Crea nuova chat privata
        INSERT INTO chat_conversations (type, user1_id, user2_id, created_by, created_at, is_active)
        VALUES ('privata', LEAST(p_user1_id, p_user2_id), GREATEST(p_user1_id, p_user2_id), p_user1_id, NOW(), TRUE);
        
        SET p_conversation_id = LAST_INSERT_ID();
        
        -- Aggiungi partecipanti
        INSERT INTO chat_participants (conversation_id, user_id, joined_at, is_active)
        VALUES 
        (p_conversation_id, p_user1_id, NOW(), TRUE),
        (p_conversation_id, p_user2_id, NOW(), TRUE);
        
        -- Inizializza status lettura
        INSERT INTO chat_read_status (user_id, conversation_id, unread_count)
        VALUES 
        (p_user1_id, p_conversation_id, 0),
        (p_user2_id, p_conversation_id, 0);
    END IF;
END //

-- Procedure per cleanup sessioni scadute
CREATE PROCEDURE IF NOT EXISTS CleanupExpiredSessions()
BEGIN
    -- Segna come offline le sessioni inattive da più di 5 minuti
    UPDATE user_sessions 
    SET is_online = FALSE 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
    AND is_online = TRUE;
    
    -- Rimuovi sessioni molto vecchie (più di 24 ore)
    DELETE FROM user_sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END //

DELIMITER ;

-- ==================================================================
-- FASE 4: EVENTI AUTOMATICI
-- ==================================================================

-- Evento per cleanup automatico sessioni (ogni 5 minuti)
CREATE EVENT IF NOT EXISTS cleanup_sessions
ON SCHEDULE EVERY 5 MINUTE
STARTS CURRENT_TIMESTAMP
DO
  CALL CleanupExpiredSessions();

-- Abilita scheduler eventi se non già attivo
SET GLOBAL event_scheduler = ON;

-- ==================================================================
-- FASE 5: VISTE UTILI
-- ==================================================================

-- Vista per chat attive di un utente
CREATE OR REPLACE VIEW user_active_chats AS
SELECT 
    c.id,
    c.type,
    c.name,
    c.client_id,
    c.user1_id,
    c.user2_id,
    c.last_message_at,
    COALESCE(rs.unread_count, 0) as unread_count,
    CASE 
        WHEN c.type = 'globale' THEN c.name
        WHEN c.type = 'pratica' THEN CONCAT('Pratica: ', cl.Cognome_Ragione_sociale)
        WHEN c.type = 'privata' THEN 
            CASE 
                WHEN c.user1_id = rs.user_id THEN u2.nome
                ELSE u1.nome
            END
    END as display_name
FROM chat_conversations c
LEFT JOIN chat_read_status rs ON c.id = rs.conversation_id
LEFT JOIN clienti cl ON c.client_id = cl.id
LEFT JOIN utenti u1 ON c.user1_id = u1.id
LEFT JOIN utenti u2 ON c.user2_id = u2.id
WHERE c.is_active = TRUE;

-- Vista per utenti online
CREATE OR REPLACE VIEW users_online_status AS
SELECT 
    u.id,
    u.nome,
    u.email,
    u.ruolo,
    COALESCE(s.is_online, FALSE) as is_online,
    s.last_activity
FROM utenti u
LEFT JOIN user_sessions s ON u.id = s.user_id
ORDER BY s.is_online DESC, s.last_activity DESC;

-- ==================================================================
-- COMPLETAMENTO
-- ==================================================================

-- Messaggio di completamento
SELECT 'DATABASE CHAT SYSTEM CREATED SUCCESSFULLY!' as status;
SELECT COUNT(*) as total_tables FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name LIKE 'chat_%' OR table_name LIKE 'user_%';
