# 💬 **SISTEMA CHAT FOOTER WHATSAPP-LIKE**

## 📋 **TABELLA DI MARCIA & LINEE GUIDA SVILUPPO**

---

## 🎯 **ANALISI REQUISITI COMPLETA**

### **1. Chat Globale (Gruppo)**
- ✅ Tutti gli utenti possono scambiare messaggi
- ✅ Notifiche in tempo reale  
- ✅ Cronologia persistente completa
- ✅ Stato lettura messaggi per utente
- ✅ Badge contatori messaggi non letti

### **2. Chat Pratiche (Gruppo Cliente)**
- ✅ Selezione cliente tramite dropdown
- ✅ Messaggi salvati come appunti per pratica specifica
- ✅ Cronologia completa per ogni cliente
- ✅ Notifiche specifiche per pratica
- ✅ Filtraggio per cliente/pratica

### **3. Chat Private (Utente-Utente)**
- ✅ Creazione chat tra due utenti specifici
- ✅ **CRONOLOGIA PERSISTENTE** - Messaggi salvati permanentemente
- ✅ Sistema trova-o-crea per evitare duplicati
- ✅ Lista chat private attive nel footer
- ✅ Possibilità eliminazione chat se necessario

### **4. Sistema Notifiche & Status**
- ✅ Status online/offline utenti in tempo reale
- ✅ Messaggi istantanei con polling/real-time
- ✅ **Notifiche Telegram per utenti offline**
- ✅ Notifiche browser desktop
- ✅ Badge numerici per messaggi non letti

---

## 🏗️ **FASE 1: STRUTTURA DATABASE** *(Priorità: ALTA)*

### **1.1 Schema Database Completo**

```sql
-- Conversazioni/Chat (Globale, Pratiche, Private)
CREATE TABLE chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('globale', 'pratica', 'privata') NOT NULL,
    name VARCHAR(255) NULL, -- Nome chat (per globali/gruppi)
    client_id INT NULL, -- Per chat pratiche (FK clienti)
    user1_id INT NULL, -- Per chat private (primo utente)
    user2_id INT NULL, -- Per chat private (secondo utente)
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP NULL, -- Per ordinamento chat attive
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES utenti(id),
    FOREIGN KEY (client_id) REFERENCES clienti(id),
    FOREIGN KEY (user1_id) REFERENCES utenti(id),
    FOREIGN KEY (user2_id) REFERENCES utenti(id),
    INDEX idx_type_client (type, client_id),
    INDEX idx_private_chat (type, user1_id, user2_id),
    INDEX idx_last_message (last_message_at DESC)
);

-- Messaggi di tutte le chat
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'system') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id),
    INDEX idx_conversation_time (conversation_id, created_at),
    INDEX idx_user_time (user_id, created_at)
);

-- Partecipanti delle chat (per chat di gruppo)
CREATE TABLE chat_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utenti(id),
    UNIQUE KEY unique_participant (conversation_id, user_id),
    INDEX idx_user_active (user_id, is_active)
);

-- Status lettura messaggi per utente
CREATE TABLE chat_read_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    conversation_id INT NOT NULL,
    last_read_message_id INT NULL,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unread_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES utenti(id),
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (last_read_message_id) REFERENCES chat_messages(id),
    UNIQUE KEY unique_user_conversation (user_id, conversation_id),
    INDEX idx_user_unread (user_id, unread_count)
);

-- Sessioni utenti per status online/offline
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_online BOOLEAN DEFAULT TRUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES utenti(id),
    UNIQUE KEY unique_user_session (user_id),
    INDEX idx_online_activity (is_online, last_activity)
);

-- Configurazione Telegram per notifiche offline
CREATE TABLE user_telegram_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    telegram_chat_id VARCHAR(255) NULL,
    telegram_username VARCHAR(255) NULL,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utenti(id),
    UNIQUE KEY unique_user_telegram (user_id)
);
```

### **1.2 Dati Iniziali**

```sql
-- Chat globale di default
INSERT INTO chat_conversations (type, name, created_by, created_at) 
VALUES ('globale', 'Chat Generale', 1, NOW());

-- Aggiungi tutti gli utenti alla chat globale
INSERT INTO chat_participants (conversation_id, user_id, joined_at)
SELECT 1, id, NOW() FROM utenti WHERE id > 0;
```

---

## 🎨 **FASE 2: INTERFACCIA FOOTER** *(Priorità: ALTA)*

### **2.1 Struttura Widget Footer**

```html
<!-- Footer Chat Widget -->
<div id="chat-footer-widget" class="chat-widget-container">
    <!-- Toggle Button -->
    <div id="chat-toggle-btn" class="chat-toggle-button">
        <i class="fas fa-comments"></i>
        <span id="total-unread-badge" class="chat-badge">0</span>
    </div>
    
    <!-- Chat Panel -->
    <div id="chat-panel" class="chat-panel hidden">
        <!-- Header Panel -->
        <div class="chat-panel-header">
            <h5>💬 Chat</h5>
            <button id="chat-minimize-btn" class="btn-minimize">
                <i class="fas fa-minus"></i>
            </button>
        </div>
        
        <!-- Chat List -->
        <div class="chat-list-container">
            <!-- Chat Globale -->
            <div class="chat-item" data-type="globale" data-id="1">
                <div class="chat-avatar">🌐</div>
                <div class="chat-info">
                    <div class="chat-name">Chat Generale</div>
                    <div class="chat-last-message">Ultimo messaggio...</div>
                </div>
                <div class="chat-meta">
                    <span class="chat-time">14:30</span>
                    <span class="chat-unread-badge">3</span>
                </div>
            </div>
            
            <!-- Chat Pratiche -->
            <div class="chat-item" data-type="pratiche">
                <div class="chat-avatar">📝</div>
                <div class="chat-info">
                    <div class="chat-name">Chat Pratiche</div>
                    <div class="chat-selector">
                        <select id="client-selector" class="form-select form-select-sm">
                            <option value="">Seleziona cliente...</option>
                        </select>
                    </div>
                </div>
                <div class="chat-meta">
                    <span class="chat-unread-badge">1</span>
                </div>
            </div>
            
            <!-- Chat Private -->
            <div class="chat-private-section">
                <div class="chat-section-header">
                    <span>👥 Chat Private</span>
                    <button id="new-private-chat-btn" class="btn-new-chat">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div id="private-chats-list">
                    <!-- Lista chat private dinamica -->
                </div>
            </div>
        </div>
        
        <!-- Chat Window -->
        <div id="chat-window" class="chat-window hidden">
            <!-- Chat Header -->
            <div class="chat-window-header">
                <button id="back-to-list-btn" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-window-info">
                    <div class="chat-window-title">Nome Chat</div>
                    <div class="chat-window-status">Online</div>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div id="chat-messages-area" class="chat-messages-area">
                <!-- Messaggi dinamici -->
            </div>
            
            <!-- Input Area -->
            <div class="chat-input-area">
                <input type="text" id="chat-message-input" class="chat-input" placeholder="Scrivi un messaggio...">
                <button id="send-message-btn" class="btn-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>
```

### **2.2 CSS Styling**

```css
/* Footer Chat Widget - Base */
.chat-widget-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: 'Segoe UI', sans-serif;
}

/* Toggle Button */
.chat-toggle-button {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    position: relative;
}

.chat-toggle-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0,0,0,0.2);
}

/* Badge Notifiche */
.chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
}

/* Chat Panel */
.chat-panel {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Responsive */
@media (max-width: 768px) {
    .chat-panel {
        width: 320px;
        height: 450px;
        bottom: 70px;
        right: -10px;
    }
    
    .chat-widget-container {
        bottom: 15px;
        right: 15px;
    }
}
```

---

## 🔧 **FASE 3: API BACKEND** *(Priorità: ALTA)*

### **3.1 Struttura API**

```
api/chat/
├── conversations/
│   ├── get_list.php           # Lista chat utente
│   ├── get_global.php         # Chat globale
│   ├── get_pratiche.php       # Chat pratiche per cliente
│   └── create_private.php     # Crea/trova chat privata
├── messages/
│   ├── send.php              # Invia messaggio
│   ├── get_history.php       # Cronologia messaggi
│   └── mark_read.php         # Segna come letti
├── users/
│   ├── get_online.php        # Lista utenti online
│   ├── update_activity.php   # Aggiorna attività utente
│   └── get_status.php        # Status specifico utente
├── notifications/
│   ├── get_unread.php        # Conta non letti
│   ├── send_telegram.php     # Notifica Telegram
│   └── mark_notification.php # Gestione notifiche
└── system/
    ├── heartbeat.php         # Keep-alive sessione
    └── cleanup.php           # Pulizia sessioni scadute
```

### **3.2 API Core - Esempi**

**api/chat/messages/send.php**
```php
<?php
require_once '../../includes/auth.php';
require_login();
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $conversation_id = $input['conversation_id'];
    $message = trim($input['message']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($message)) {
        throw new Exception('Messaggio vuoto');
    }
    
    // Inserisci messaggio
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (conversation_id, user_id, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$conversation_id, $user_id, $message]);
    
    $message_id = $pdo->lastInsertId();
    
    // Aggiorna ultimo messaggio conversazione
    $stmt = $pdo->prepare("
        UPDATE chat_conversations 
        SET last_message_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$conversation_id]);
    
    // Aggiorna contatori non letti per altri utenti
    $stmt = $pdo->prepare("
        UPDATE chat_read_status 
        SET unread_count = unread_count + 1 
        WHERE conversation_id = ? AND user_id != ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    
    // Invia notifiche Telegram agli utenti offline
    // TODO: Implementare notifiche Telegram
    
    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
```

---

## ⚡ **FASE 4: REAL-TIME & NOTIFICHE** *(Priorità: MEDIA)*

### **4.1 Sistema Polling AJAX**

```javascript
class ChatSystem {
    constructor() {
        this.pollingInterval = 3000; // 3 secondi
        this.currentChat = null;
        this.unreadCounts = {};
        this.lastMessageIds = {};
        this.isPolling = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.startPolling();
        this.updateUserActivity();
    }
    
    startPolling() {
        if (this.isPolling) return;
        this.isPolling = true;
        
        setInterval(() => {
            this.checkNewMessages();
            this.updateOnlineUsers();
            this.updateUnreadCounts();
        }, this.pollingInterval);
    }
    
    async checkNewMessages() {
        try {
            const response = await fetch('api/chat/messages/get_new.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    last_message_ids: this.lastMessageIds
                })
            });
            
            const data = await response.json();
            
            if (data.new_messages) {
                this.handleNewMessages(data.new_messages);
            }
            
        } catch (error) {
            console.error('Errore polling messaggi:', error);
        }
    }
    
    handleNewMessages(messages) {
        messages.forEach(msg => {
            // Aggiorna UI con nuovo messaggio
            this.displayMessage(msg);
            
            // Mostra notifica se necessario
            if (msg.user_id !== this.currentUserId) {
                this.showNotification(msg);
            }
            
            // Aggiorna contatori
            this.updateBadges();
        });
    }
    
    showNotification(message) {
        // Notifica browser
        if (Notification.permission === 'granted') {
            new Notification(`Nuovo messaggio da ${message.user_name}`, {
                body: message.message.substring(0, 100),
                icon: '/assets/img/chat-icon.png'
            });
        }
        
        // Suono notifica
        this.playNotificationSound();
    }
}

// Inizializza sistema chat
document.addEventListener('DOMContentLoaded', () => {
    window.chatSystem = new ChatSystem();
});
```

### **4.2 Notifiche Telegram**

```php
// api/chat/notifications/send_telegram.php
<?php
require_once '../../includes/auth.php';
require_once '../../includes/telegram.php';

function sendTelegramNotification($user_id, $message, $sender_name) {
    global $pdo;
    
    // Recupera configurazione Telegram utente
    $stmt = $pdo->prepare("
        SELECT telegram_chat_id, notifications_enabled 
        FROM user_telegram_config 
        WHERE user_id = ? AND telegram_chat_id IS NOT NULL
    ");
    $stmt->execute([$user_id]);
    $config = $stmt->fetch();
    
    if (!$config || !$config['notifications_enabled']) {
        return false;
    }
    
    // Verifica se utente è offline
    $stmt = $pdo->prepare("
        SELECT is_online 
        FROM user_sessions 
        WHERE user_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([$user_id]);
    $session = $stmt->fetch();
    
    if ($session && $session['is_online']) {
        return false; // Utente online, non inviare Telegram
    }
    
    // Invia notifica Telegram
    $telegram_message = "💬 *Nuovo messaggio CRM*\n\n";
    $telegram_message .= "👤 *Da:* {$sender_name}\n";
    $telegram_message .= "📝 *Messaggio:* " . substr($message, 0, 200);
    
    if (strlen($message) > 200) {
        $telegram_message .= "...";
    }
    
    return sendTelegramMessage($config['telegram_chat_id'], $telegram_message);
}
?>
```

---

## 📱 **FASE 5: FUNZIONALITÀ AVANZATE** *(Priorità: BASSA)*

### **5.1 Miglioramenti UX**

- **🎨 Emoji Picker**: Integrazione emoji nel chat input
- **⏰ Timestamp Intelligenti**: "ora", "ieri", data completa
- **✍️ Indicatori "Sta scrivendo"**: Real-time typing indicators
- **📜 Scroll Automatico**: Auto-scroll per nuovi messaggi
- **🔍 Ricerca Messaggi**: Search nei messaggi salvati
- **📎 Allegati**: Upload file/immagini nelle chat

### **5.2 Gestione Avanzata**

- **🗑️ Eliminazione Messaggi**: Soft delete con recupero
- **✏️ Modifica Messaggi**: Edit messaggi inviati (entro X minuti)
- **📌 Pin Messaggi**: Messaggi importanti fissati in alto
- **🔕 Silenzia Chat**: Disattiva notifiche per chat specifiche
- **📊 Statistiche**: Analytics utilizzo chat per admin

---

## 🚀 **PIANO DI IMPLEMENTAZIONE TEMPORALE**

### **SETTIMANA 1** *(Fondamenta)*
- [ ] ✅ Setup completo database con tutte le tabelle
- [ ] ✅ Struttura footer base con CSS responsive
- [ ] ✅ API core: send_message, get_messages, mark_read
- [ ] ✅ Chat globale funzionante con cronologia

### **SETTIMANA 2** *(Chat Specifiche)*
- [ ] ✅ Chat pratiche con selezione cliente/pratica
- [ ] ✅ Chat private persistenti con trova-o-crea
- [ ] ✅ Sistema lettura messaggi e contatori
- [ ] ✅ Lista chat attive nel footer

### **SETTIMANA 3** *(Status & Notifiche)*
- [ ] ✅ Sistema status online/offline utenti
- [ ] ✅ Polling automatico per aggiornamenti real-time
- [ ] ✅ Notifiche browser e badge contatori
- [ ] ✅ Integrazione base Telegram per offline

### **SETTIMANA 4** *(Ottimizzazioni & Test)*
- [ ] ✅ Performance optimization e caching
- [ ] ✅ Testing completo funzionalità
- [ ] ✅ Debugging e fix issues
- [ ] ✅ Documentazione finale e deploy

---

## 🔧 **CONFIGURAZIONE & SETUP**

### **Prerequisiti Sistema**
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx con mod_rewrite
- Telegram Bot Token (per notifiche offline)

### **File Configurazione**
```php
// includes/chat_config.php
<?php
define('CHAT_POLLING_INTERVAL', 3000); // ms
define('CHAT_MESSAGE_MAX_LENGTH', 1000);
define('CHAT_HISTORY_LIMIT', 100);
define('CHAT_ONLINE_TIMEOUT', 300); // secondi
define('TELEGRAM_NOTIFICATIONS_ENABLED', true);
?>
```

### **Permissions Database**
```sql
-- Grant permissions per chat tables
GRANT SELECT, INSERT, UPDATE, DELETE ON crm.chat_* TO 'crmuser'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON crm.user_sessions TO 'crmuser'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON crm.user_telegram_config TO 'crmuser'@'localhost';
```

---

## 📝 **NOTE SVILUPPO**

### **Sicurezza**
- ✅ Validazione input su tutti gli endpoint
- ✅ Sanitizzazione messaggi per XSS
- ✅ Rate limiting per prevenire spam
- ✅ Autenticazione obbligatoria per tutte le API

### **Performance**
- ✅ Indici database ottimizzati
- ✅ Limit query per cronologia messaggi
- ✅ Cleanup automatico sessioni scadute
- ✅ Caching per lista utenti online

### **Backup & Recovery**
- ✅ Backup automatico tabelle chat
- ✅ Log degli errori dettagliato
- ✅ Monitoraggio prestazioni database

---

## 🎯 **DELIVERABLES FINALI**

1. **💬 Sistema Chat Footer Completo**
   - Chat globale, pratiche e private funzionanti
   - Interfaccia responsive tipo WhatsApp
   - Notifiche real-time e badge contatori

2. **🗄️ Database Strutturato**
   - Schema completo e ottimizzato
   - Dati iniziali e configurazioni

3. **🔧 API RESTful Complete**
   - Tutti gli endpoint necessari
   - Documentazione API completa

4. **📱 Notifiche Multi-canale**
   - Browser notifications
   - Telegram per utenti offline
   - Badge e contatori visivi

5. **📚 Documentazione**
   - Manuale utente
   - Documentazione tecnica
   - Guida configurazione

---

## ✅ **CHECKLIST FINALE**

- [ ] Database schema implementato e testato
- [ ] Chat globale funzionante con cronologia
- [ ] Chat pratiche con selezione cliente
- [ ] Chat private persistenti
- [ ] Sistema status online/offline
- [ ] Notifiche browser e Telegram
- [ ] Interface responsive e user-friendly
- [ ] Testing completo su tutti i browser
- [ ] Performance optimization
- [ ] Documentazione completa

---

**📞 CONTATTO SVILUPPO**: Per qualsiasi domanda o modifica ai requisiti, aggiornare questo README.md

**🔄 VERSIONE**: 1.0 - Data: 2025-09-02

**👥 TEAM**: Sviluppo Sistema Chat CRM WhatsApp-like
