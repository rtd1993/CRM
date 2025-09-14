-- Creazione completa struttura database chat

-- Tabella conversazioni
CREATE TABLE IF NOT EXISTS conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('global', 'private', 'pratica', 'cliente') NOT NULL DEFAULT 'global',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (created_by) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Tabella partecipanti conversazioni
CREATE TABLE IF NOT EXISTS conversation_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT 1,
    role ENUM('admin', 'member') DEFAULT 'member',
    UNIQUE KEY unique_participant (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_user (user_id)
);

-- Tabella messaggi (rinominiamo da messages_new)
CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'file', 'image', 'system') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT 0,
    reply_to INT NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to) REFERENCES messages(id) ON DELETE SET NULL,
    INDEX idx_conversation_created (conversation_id, created_at),
    INDEX idx_user_created (user_id, created_at),
    FULLTEXT KEY ft_message (message)
);

-- Tabella per tracciare letture messaggi
CREATE TABLE IF NOT EXISTS user_conversation_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    conversation_id INT NOT NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_message_id INT NULL,
    UNIQUE KEY unique_user_conversation (user_id, conversation_id),
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (last_message_id) REFERENCES messages(id) ON DELETE SET NULL,
    INDEX idx_user_conversation (user_id, conversation_id),
    INDEX idx_last_seen (last_seen)
);

-- Tabella per allegati
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    INDEX idx_message (message_id)
);

-- Migrazione dati esistenti da messages_new se esiste
INSERT IGNORE INTO messages (id, conversation_id, user_id, message, created_at)
SELECT id, conversation_id, user_id, message, created_at 
FROM messages_new 
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'messages_new');

-- Chat globale
INSERT IGNORE INTO conversations (id, name, type, created_by, created_at) 
VALUES (1, 'Chat Globale', 'global', 1, NOW());

-- Aggiungiamo tutti gli utenti attivi alla chat globale
INSERT IGNORE INTO conversation_participants (conversation_id, user_id, joined_at, is_active, role)
SELECT 1, id, NOW(), 1, 'member'
FROM utenti 
WHERE attivo = 1;

-- Creiamo conversazioni per pratiche esistenti (se esistono tabelle pratiche)
INSERT IGNORE INTO conversations (name, type, created_by, created_at)
SELECT CONCAT('Pratica Cliente ID: ', id), 'cliente', 1, NOW()
FROM clienti 
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'clienti')
LIMIT 50; -- Limitiamo per evitare troppi record
