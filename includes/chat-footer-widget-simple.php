<?php
// Footer Chat Widget Component - Versione Semplificata
// Incluso automaticamente in tutte le pagine tramite header.php

// Verifica che l'utente sia loggato
if (!isset($_SESSION['user_id'])) {
    return;
}

// Dati mock per i clienti invece di query al database
$clienti_chat = [
    ['id' => 1, 'nome' => 'Cliente Test 1'],
    ['id' => 2, 'nome' => 'Cliente Test 2'],
    ['id' => 3, 'nome' => 'Azienda XYZ Srl']
];

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>

<!-- FOOTER CHAT WIDGET -->
<div id="chatFooterWidget" class="chat-footer-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; font-family: 'Segoe UI', sans-serif;">
    <!-- Toggle Button -->
    <button id="chatToggleBtn" class="chat-toggle-button" title="Apri Chat" style="
        width: 64px; height: 64px; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        border-radius: 50%; border: none; 
        display: flex; align-items: center; justify-content: center; 
        color: white; cursor: pointer; 
        box-shadow: 0 8px 32px rgba(0,0,0,0.15); 
        transition: all 0.3s ease; position: relative;">
        <i class="fas fa-comments" style="font-size: 24px;"></i>
        <span id="chatTotalBadge" class="chat-total-badge hidden" style="
            position: absolute; top: -5px; right: -5px; 
            background: #dc3545; color: white; 
            border-radius: 50%; width: 24px; height: 24px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 12px; font-weight: bold;">0</span>
    </button>
    
    <!-- Chat Panel -->
    <div id="chatPanel" class="chat-panel" style="
        position: absolute; bottom: 80px; right: 0; 
        width: 360px; height: 500px; 
        background: white; border-radius: 16px; 
        box-shadow: 0 20px 60px rgba(0,0,0,0.2); 
        display: none; overflow: hidden;
        border: 1px solid #e0e0e0;">
        
        <!-- Panel Header -->
        <div class="chat-panel-header" style="
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 16px 20px; 
            display: flex; justify-content: space-between; align-items: center;">
            <h5 class="chat-panel-title" style="margin: 0; font-size: 16px; font-weight: 600;">
                <i class="fas fa-comments"></i> Chat
            </h5>
            <button id="chatMinimizeBtn" class="chat-minimize-btn" title="Chiudi" style="
                background: none; border: none; color: white; 
                cursor: pointer; padding: 5px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Chat List Container -->
        <div id="chatListContainer" class="chat-list-container" style="
            padding: 16px; height: calc(100% - 68px); 
            overflow-y: auto; display: block;">
            
            <!-- Chat Globale -->
            <div class="chat-item" id="globalChatItem" data-type="globale" data-id="1" style="
                display: flex; align-items: center; padding: 12px; 
                border-radius: 12px; cursor: pointer; 
                transition: background-color 0.2s ease; margin-bottom: 8px;
                border: 1px solid #f0f0f0;" 
                onmouseover="this.style.backgroundColor='#f8f9fa'" 
                onmouseout="this.style.backgroundColor='transparent'">
                <div class="chat-avatar global" style="
                    width: 48px; height: 48px; border-radius: 50%; 
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
                    display: flex; align-items: center; justify-content: center; 
                    color: white; margin-right: 12px;">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="chat-info" style="flex: 1;">
                    <div class="chat-name" style="font-weight: 600; color: #333; margin-bottom: 4px;">
                        Chat Generale
                    </div>
                    <div class="chat-last-message" style="color: #666; font-size: 14px;">
                        Clicca per aprire la chat...
                    </div>
                </div>
                <div class="chat-meta" style="text-align: right;">
                    <span class="chat-time" style="color: #999; font-size: 12px;">--:--</span>
                    <span id="globalChatBadge" class="chat-unread-badge hidden" style="
                        background: #007bff; color: white; border-radius: 50%; 
                        width: 20px; height: 20px; display: none; 
                        align-items: center; justify-content: center; 
                        font-size: 12px; margin-top: 4px;">0</span>
                </div>
            </div>
            
            <!-- Chat Pratiche -->
            <div class="chat-item" id="practiceChatItem" data-type="pratica" style="
                display: flex; align-items: center; padding: 12px; 
                border-radius: 12px; margin-bottom: 8px;
                border: 1px solid #f0f0f0;">
                <div class="chat-avatar practice" style="
                    width: 48px; height: 48px; border-radius: 50%; 
                    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); 
                    display: flex; align-items: center; justify-content: center; 
                    color: white; margin-right: 12px;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="chat-info" style="flex: 1;">
                    <div class="chat-name" style="font-weight: 600; color: #333; margin-bottom: 4px;">
                        Chat Pratiche
                    </div>
                    <div class="chat-selector">
                        <select id="clientSelector" class="form-select" style="
                            border: 1px solid #ddd; border-radius: 6px; 
                            padding: 6px 12px; font-size: 14px;">
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
                    <span id="practiceChatBadge" class="chat-unread-badge hidden" style="
                        background: #007bff; color: white; border-radius: 50%; 
                        width: 20px; height: 20px; display: none;">0</span>
                </div>
            </div>
            
            <!-- Sezione Chat Private -->
            <div class="chat-section-header" style="
                display: flex; justify-content: space-between; align-items: center; 
                margin: 16px 0 12px 0; padding-bottom: 8px; 
                border-bottom: 1px solid #eee; font-size: 14px; color: #666;">
                <span><i class="fas fa-user-friends"></i> Chat Private</span>
                <button id="newPrivateChatBtn" class="btn-new-chat" title="Nuova Chat Privata" style="
                    background: #007bff; color: white; border: none; 
                    border-radius: 50%; width: 24px; height: 24px; 
                    display: flex; align-items: center; justify-content: center; 
                    cursor: pointer; font-size: 12px;">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <!-- Lista Chat Private (dinamica) -->
            <div id="privateChatsList">
                <!-- Le chat private verranno caricate dinamicamente qui -->
                <div style="text-align: center; color: #999; padding: 20px; font-size: 14px;">
                    Nessuna chat privata attiva
                </div>
            </div>
            
        </div>
        
        <!-- Chat Window (inizialmente nascosta) -->
        <div id="chatWindow" class="chat-window" style="
            position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
            background: white; display: none; flex-direction: column;">
            
            <!-- Chat Window Header -->
            <div class="chat-window-header" style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; padding: 16px 20px; 
                display: flex; align-items: center;">
                <button id="backToListBtn" class="btn-back" title="Torna alla lista" style="
                    background: none; border: none; color: white; 
                    cursor: pointer; margin-right: 12px; padding: 5px;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-window-info">
                    <div id="chatWindowTitle" class="chat-window-title" style="font-weight: 600;">
                        Nome Chat
                    </div>
                    <div id="chatWindowStatus" class="chat-window-status" style="font-size: 12px; opacity: 0.8;">
                        Seleziona una chat
                    </div>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div id="chatMessagesArea" class="chat-messages-area" style="
                flex: 1; padding: 16px; overflow-y: auto; 
                background: #f8f9fa;">
                <div class="loading-indicator" style="text-align: center; color: #666; padding: 20px;">
                    <div class="spinner" style="
                        border: 3px solid #f3f3f3; border-top: 3px solid #007bff; 
                        border-radius: 50%; width: 30px; height: 30px; 
                        animation: spin 1s linear infinite; margin: 0 auto 10px;"></div>
                    Caricamento messaggi...
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="chat-input-area" style="
                padding: 16px; border-top: 1px solid #eee; 
                display: flex; align-items: center; gap: 12px;">
                <input 
                    type="text" 
                    id="chatMessageInput" 
                    class="chat-message-input" 
                    placeholder="Scrivi un messaggio..."
                    maxlength="1000"
                    style="flex: 1; border: 1px solid #ddd; border-radius: 20px; 
                           padding: 10px 16px; outline: none;"
                    disabled
                />
                <button id="sendMessageBtn" class="btn-send" title="Invia messaggio" style="
                    background: #007bff; color: white; border: none; 
                    border-radius: 50%; width: 40px; height: 40px; 
                    display: flex; align-items: center; justify-content: center; 
                    cursor: pointer;" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal per selezione utente (Chat Private) -->
<div id="userSelectionModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Nuova Chat Privata
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
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

<!-- Stile CSS per l'animazione spinner -->
<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.chat-panel.visible {
    display: block !important;
}

.chat-toggle-button:hover {
    transform: scale(1.05);
    box-shadow: 0 12px 40px rgba(0,0,0,0.2);
}

.chat-unread-badge:not(.hidden) {
    display: flex !important;
}
</style>

<!-- Dati JavaScript -->
<script>
// Configurazione globale chat
window.chatConfig = {
    currentUserId: <?= json_encode($user_id) ?>,
    currentUserName: <?= json_encode($user_name) ?>,
    pollingInterval: 3000, // 3 secondi
    maxMessageLength: 1000,
    apiBase: 'api/chat/',
    debug: true // Sempre debug per test
};

// Log di debug
console.log('[ChatWidget] Config loaded:', window.chatConfig);
</script>
