<?php
// Widget Chat Completo come da README_CHAT_SYSTEM.md
// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    return;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Utente';

// Carica lista clienti per chat pratiche
try {
    $stmt = $pdo->prepare("SELECT id, Cognome_Ragione_sociale AS nome FROM clienti ORDER BY Cognome_Ragione_sociale ASC");
    $stmt->execute();
    $clienti_chat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clienti_chat = [];
}
?>

<script>
// Configurazione Chat System completo
window.completeChatConfig = {
    userId: <?= $user_id ?>,
    userName: '<?= addslashes($user_name) ?>',
    apiBase: '/api/chat/',
    pollingInterval: 3000,
    maxMessageLength: 1000,
    debug: true
};

console.log('🔧 Chat Config caricato:', window.completeChatConfig);
</script>

<!-- Includi il JavaScript del sistema chat completo -->
<script src="/assets/js/chat-complete.js?v=<?= time() ?>"></script>

<!-- Footer Chat Widget -->
<div id="chat-footer-widget" class="chat-widget-container">
    <!-- Toggle Button -->
    <div id="chat-toggle-btn" class="chat-toggle-button">
        <i class="fas fa-comments"></i>
        <span id="total-unread-badge" class="chat-badge hidden">0</span>
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
        
        <!-- Chat List Container -->
        <div id="chat-list-container" class="chat-list-container">
            <!-- Chat Globale -->
            <div class="chat-item" data-type="globale" data-id="1">
                <div class="chat-avatar">🌐</div>
                <div class="chat-info">
                    <div class="chat-name">Chat Generale</div>
                    <div class="chat-last-message">Clicca per aprire la chat globale</div>
                </div>
                <div class="chat-meta">
                    <span id="global-chat-badge" class="chat-unread-badge hidden">0</span>
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
                            <?php foreach ($clienti_chat as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="chat-meta">
                    <span id="practice-chat-badge" class="chat-unread-badge hidden">0</span>
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
                    <div class="no-private-chats" style="padding: 15px; text-align: center; color: #6c757d; font-size: 13px;">
                        Nessuna chat privata.<br>Clicca + per iniziare.
                    </div>
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
                    <div id="chat-window-title" class="chat-window-title">Nome Chat</div>
                    <div id="chat-window-status" class="chat-window-status">Online</div>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div id="chat-messages-area" class="chat-messages-area">
                <div class="loading-messages">
                    <i class="fas fa-spinner fa-spin"></i> Caricamento messaggi...
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="chat-input-area">
                <input type="text" id="chat-message-input" class="chat-input" placeholder="Scrivi un messaggio..." disabled>
                <button id="send-message-btn" class="btn-send" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Selezione Utente per Chat Private -->
<div class="modal fade" id="userSelectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuova Chat Privata</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group" id="users-list">
                    <!-- Lista utenti dinamica -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
    border: none;
}

.chat-toggle-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0,0,0,0.2);
}

.chat-toggle-button.active {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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

.chat-badge.hidden {
    display: none;
}

/* Chat Panel */
.chat-panel {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background: lime !important; /* DEBUG: sfondo verde lime */
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    z-index: 10000;
    border: 3px solid red; /* DEBUG: border rosso per vedere il pannello */
    opacity: 1 !important; /* DEBUG: assicura opacità */
}

.chat-panel.hidden {
    display: none !important;
}

/* Panel Header */
.chat-panel-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-panel-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.btn-minimize {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.btn-minimize:hover {
    background: rgba(255,255,255,0.1);
}

/* Chat List */
.chat-list-container {
    flex: 1;
    overflow-y: auto;
    background: #f8f9fa;
}

.chat-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background 0.3s ease;
}

.chat-item:hover {
    background: #e9ecef;
}

.chat-item:last-child {
    border-bottom: none;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-right: 12px;
    flex-shrink: 0;
}

.chat-info {
    flex: 1;
    min-width: 0;
}

.chat-name {
    font-weight: 600;
    font-size: 14px;
    color: #2c3e50;
    margin-bottom: 4px;
}

.chat-last-message {
    font-size: 12px;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-selector select {
    width: 100%;
    font-size: 12px;
    margin-top: 4px;
}

.chat-meta {
    text-align: right;
    font-size: 11px;
    color: #6c757d;
}

.chat-unread-badge {
    background: #e74c3c;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    margin-top: 4px;
    display: inline-block;
}

.chat-unread-badge.hidden {
    display: none;
}

/* Private Chat Section */
.chat-private-section {
    border-top: 1px solid #e9ecef;
}

.chat-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #f1f3f4;
    font-weight: 600;
    font-size: 13px;
    color: #495057;
}

.btn-new-chat {
    background: #28a745;
    border: none;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
}

.btn-new-chat:hover {
    background: #218838;
}

/* Chat Window */
.chat-window {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: white;
    display: flex;
    flex-direction: column;
}

.chat-window.hidden {
    display: none !important;
}

.chat-window-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 12px 15px;
    display: flex;
    align-items: center;
}

.btn-back {
    background: none;
    border: none;
    color: #6c757d;
    font-size: 16px;
    cursor: pointer;
    margin-right: 12px;
    padding: 5px;
    border-radius: 4px;
}

.btn-back:hover {
    background: #e9ecef;
    color: #495057;
}

.chat-window-info {
    flex: 1;
}

.chat-window-title {
    font-weight: 600;
    font-size: 14px;
    color: #2c3e50;
}

.chat-window-status {
    font-size: 12px;
    color: #6c757d;
}

/* Messages Area */
.chat-messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
}

.loading-messages {
    text-align: center;
    color: #6c757d;
    padding: 40px 20px;
}

/* Input Area */
.chat-input-area {
    padding: 15px;
    background: white;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 10px;
}

.chat-input {
    flex: 1;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 8px 15px;
    font-size: 14px;
    outline: none;
    resize: none;
}

.chat-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
}

.chat-input:disabled {
    background: #f8f9fa;
    color: #6c757d;
}

.btn-send {
    background: #667eea;
    border: none;
    color: white;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}

.btn-send:hover:not(:disabled) {
    background: #5a6fd8;
}

.btn-send:disabled {
    background: #adb5bd;
    cursor: not-allowed;
}

/* Message Bubbles */
.message-bubble {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
}

.message-bubble.own {
    align-items: flex-end;
}

.message-sender {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 4px;
    padding-left: 12px;
}

.message-content {
    background: #e9ecef;
    padding: 8px 12px;
    border-radius: 18px;
    max-width: 70%;
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.4;
}

.message-bubble.own .message-content {
    background: #667eea;
    color: white;
}

.message-meta {
    font-size: 11px;
    color: #6c757d;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.message-bubble.own .message-meta {
    justify-content: flex-end;
}

.message-status {
    font-size: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .chat-panel {
        width: calc(100vw - 30px);
        height: calc(100vh - 120px);
        bottom: 70px;
        right: 15px;
    }
    
    .chat-widget-container {
        bottom: 15px;
        right: 15px;
    }
}

/* Scrollbar */
.chat-messages-area::-webkit-scrollbar,
.chat-list-container::-webkit-scrollbar {
    width: 6px;
}

.chat-messages-area::-webkit-scrollbar-track,
.chat-list-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chat-messages-area::-webkit-scrollbar-thumb,
.chat-list-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.chat-messages-area::-webkit-scrollbar-thumb:hover,
.chat-list-container::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Online indicator */
.online-indicator {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    background: #28a745;
    border: 2px solid white;
    border-radius: 50%;
}

.online-indicator.offline {
    background: #6c757d;
}
</style>
