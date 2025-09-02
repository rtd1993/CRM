<?php
// Footer Chat Widget Component
// Incluso automaticamente in tutte le pagine tramite header.php

// Verifica che l'utente sia loggato
if (!isset($_SESSION['user_id'])) {
    return;
}

// Carica lista clienti per chat pratiche
try {
    $stmt = $pdo->prepare("SELECT id, Cognome_Ragione_sociale AS nome FROM clienti ORDER BY Cognome_Ragione_sociale ASC");
    $stmt->execute();
    $clienti_chat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clienti_chat = [];
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>

<!-- FOOTER CHAT WIDGET -->
<div id="chatFooterWidget" class="chat-footer-widget">
    <!-- Toggle Button -->
    <button id="chatToggleBtn" class="chat-toggle-button" title="Apri Chat">
        <i class="fas fa-comments"></i>
        <span id="chatTotalBadge" class="chat-total-badge hidden">0</span>
    </button>
    
    <!-- Chat Panel -->
    <div id="chatPanel" class="chat-panel">
        <!-- Panel Header -->
        <div class="chat-panel-header">
            <h5 class="chat-panel-title">
                <i class="fas fa-comments"></i>
                Chat
            </h5>
            <button id="chatMinimizeBtn" class="chat-minimize-btn" title="Chiudi">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Chat List Container -->
        <div id="chatListContainer" class="chat-list-container">
            
            <!-- Chat Globale -->
            <div class="chat-item" id="globalChatItem" data-type="globale" data-id="1">
                <div class="chat-avatar global">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="chat-info">
                    <div class="chat-name">Chat Generale</div>
                    <div class="chat-last-message">Clicca per aprire la chat...</div>
                </div>
                <div class="chat-meta">
                    <span class="chat-time">--:--</span>
                    <span id="globalChatBadge" class="chat-unread-badge hidden">0</span>
                </div>
            </div>
            
            <!-- Chat Pratiche -->
            <div class="chat-item" id="practiceChatItem" data-type="pratica">
                <div class="chat-avatar practice">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="chat-info">
                    <div class="chat-name">Chat Pratiche</div>
                    <div class="chat-selector">
                        <select id="clientSelector" class="form-select">
                            <option value="">-- Seleziona Cliente --</option>
                            <?php foreach ($clienti_chat as $cliente): ?>
                                <option value="<?= htmlspecialchars($cliente['id']) ?>">
                                    <?= htmlspecialchars($cliente['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="chat-meta">
                    <span id="practiceChatBadge" class="chat-unread-badge hidden">0</span>
                </div>
            </div>
            
            <!-- Sezione Chat Private -->
            <div class="chat-section-header">
                <span><i class="fas fa-user-friends"></i> Chat Private</span>
                <button id="newPrivateChatBtn" class="btn-new-chat" title="Nuova Chat Privata">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <!-- Lista Chat Private (dinamica) -->
            <div id="privateChatsList">
                <!-- Le chat private verranno caricate dinamicamente qui -->
            </div>
            
            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="loading-indicator" style="display: none;">
                <div class="spinner"></div>
                Caricamento...
            </div>
            
        </div>
        
        <!-- Chat Window -->
        <div id="chatWindow" class="chat-window">
            <!-- Chat Window Header -->
            <div class="chat-window-header">
                <button id="backToListBtn" class="btn-back" title="Torna alla lista">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-window-info">
                    <div id="chatWindowTitle" class="chat-window-title">Nome Chat</div>
                    <div id="chatWindowStatus" class="chat-window-status">Seleziona una chat</div>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div id="chatMessagesArea" class="chat-messages-area">
                <!-- I messaggi verranno caricati dinamicamente qui -->
                <div class="loading-indicator">
                    <div class="spinner"></div>
                    Caricamento messaggi...
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="chat-input-area">
                <input 
                    type="text" 
                    id="chatMessageInput" 
                    class="chat-message-input" 
                    placeholder="Scrivi un messaggio..."
                    maxlength="1000"
                    disabled
                />
                <button id="sendMessageBtn" class="btn-send" title="Invia messaggio" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal per selezione utente (Chat Private) -->
<div id="userSelectionModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Nuova Chat Privata
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Seleziona un utente con cui iniziare una chat privata:</p>
                <div id="usersList" class="list-group">
                    <!-- Lista utenti caricata dinamicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dati JavaScript -->
<script>
// Configurazione globale chat
window.chatConfig = {
    currentUserId: <?= json_encode($user_id) ?>,
    currentUserName: <?= json_encode($user_name) ?>,
    pollingInterval: 3000, // 3 secondi
    maxMessageLength: 1000,
    apiBase: 'api/chat/',
    debug: <?= json_encode(isset($_GET['debug'])) ?>
};

// Log di debug
if (window.chatConfig.debug) {
    console.log('Chat Config:', window.chatConfig);
}
</script>
