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
<div id="chatFooterWidget" class="chat-footer-widget" style="position: fixed !important; bottom: 20px !important; right: 20px !important; z-index: 9999 !important; font-family: 'Segoe UI', Arial, sans-serif !important;">
    <!-- Toggle Button -->
    <button id="chatToggleBtn" class="chat-toggle-button" title="Apri Chat" style="
        width: 64px !important; 
        height: 64px !important; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
        border-radius: 50% !important; 
        display: flex !important; 
        align-items: center !important; 
        justify-content: center !important; 
        color: white !important; 
        cursor: pointer !important; 
        box-shadow: 0 8px 32px rgba(0,0,0,0.15) !important; 
        transition: all 0.3s ease !important; 
        position: relative !important; 
        border: none !important; 
        outline: none !important;
    ">
        <i class="fas fa-comments" style="font-size: 24px !important; transition: transform 0.3s ease !important;"></i>
        <span id="chatTotalBadge" class="chat-total-badge hidden" style="
            position: absolute !important; 
            top: -5px !important; 
            right: -5px !important; 
            background: #dc3545 !important; 
            color: white !important; 
            border-radius: 50% !important; 
            width: 20px !important; 
            height: 20px !important; 
            font-size: 11px !important; 
            font-weight: bold !important; 
            display: flex !important; 
            align-items: center !important; 
            justify-content: center !important; 
            border: 2px solid white !important;
        ">0</span>
    </button>
    
    <!-- Chat Panel -->
    <div id="chatPanel" class="chat-panel" style="
        position: absolute !important; 
        bottom: 80px !important; 
        right: 0 !important; 
        width: 380px !important; 
        height: 500px !important; 
        background: white !important; 
        border-radius: 16px !important; 
        box-shadow: 0 20px 60px rgba(0,0,0,0.2) !important; 
        display: none !important; 
        flex-direction: column !important; 
        overflow: hidden !important; 
        border: 1px solid #e1e8ed !important;
    ">
        <!-- Panel Header -->
        <div class="chat-panel-header" style="
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
            color: white !important; 
            padding: 16px 20px !important; 
            display: flex !important; 
            justify-content: space-between !important; 
            align-items: center !important; 
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
        ">
            <h5 class="chat-panel-title" style="
                margin: 0 !important; 
                font-size: 16px !important; 
                font-weight: 600 !important; 
                display: flex !important; 
                align-items: center !important; 
                gap: 8px !important;
            ">
                <i class="fas fa-comments" style="font-size: 18px !important;"></i>
                Chat
            </h5>
            <button id="chatMinimizeBtn" class="chat-minimize-btn" title="Chiudi" style="
                background: none !important; 
                border: none !important; 
                color: white !important; 
                font-size: 18px !important; 
                cursor: pointer !important; 
                padding: 4px !important; 
                border-radius: 4px !important; 
                transition: background-color 0.2s ease !important;
            ">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Chat List Container -->
        <div id="chatListContainer" class="chat-list-container" style="
            flex: 1 !important; 
            overflow-y: auto !important; 
            padding: 16px !important; 
            background: #f8f9fa !important;
        ">
            
            <!-- Chat Globale -->
            <div class="chat-item" id="globalChatItem" data-type="globale" data-id="1" style="
                display: flex !important; 
                align-items: center !important; 
                padding: 12px 16px !important; 
                background: white !important; 
                border-radius: 12px !important; 
                margin-bottom: 8px !important; 
                cursor: pointer !important; 
                transition: all 0.2s ease !important; 
                border: 1px solid #e1e8ed !important; 
                box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
            ">
                <div class="chat-avatar global" style="
                    width: 40px !important; 
                    height: 40px !important; 
                    border-radius: 50% !important; 
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; 
                    display: flex !important; 
                    align-items: center !important; 
                    justify-content: center !important; 
                    color: white !important; 
                    font-size: 16px !important; 
                    margin-right: 12px !important; 
                    flex-shrink: 0 !important;
                ">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="chat-info" style="
                    flex: 1 !important; 
                    min-width: 0 !important;
                ">
                    <div class="chat-name" style="
                        font-weight: 600 !important; 
                        font-size: 14px !important; 
                        color: #1a1a1a !important; 
                        margin-bottom: 2px !important;
                    ">Chat Generale</div>
                    <div class="chat-last-message" style="
                        font-size: 12px !important; 
                        color: #657786 !important; 
                        overflow: hidden !important; 
                        text-overflow: ellipsis !important; 
                        white-space: nowrap !important;
                    ">Clicca per aprire la chat...</div>
                </div>
                <div class="chat-meta" style="
                    display: flex !important; 
                    flex-direction: column !important; 
                    align-items: flex-end !important; 
                    gap: 4px !important;
                ">
                    <span class="chat-time" style="
                        font-size: 11px !important; 
                        color: #657786 !important;
                    ">--:--</span>
                    <span id="globalChatBadge" class="chat-unread-badge hidden" style="
                        background: #dc3545 !important; 
                        color: white !important; 
                        border-radius: 10px !important; 
                        padding: 2px 6px !important; 
                        font-size: 11px !important; 
                        font-weight: bold !important; 
                        min-width: 18px !important; 
                        text-align: center !important;
                    ">0</span>
                </div>
            </div>
            
            <!-- Chat Pratiche -->
            <div class="chat-item" id="practiceChatItem" data-type="pratica" style="
                display: flex !important; 
                align-items: center !important; 
                padding: 12px 16px !important; 
                background: white !important; 
                border-radius: 12px !important; 
                margin-bottom: 8px !important; 
                cursor: pointer !important; 
                transition: all 0.2s ease !important; 
                border: 1px solid #e1e8ed !important; 
                box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
            ">
                <div class="chat-avatar practice" style="
                    width: 40px !important; 
                    height: 40px !important; 
                    border-radius: 50% !important; 
                    background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%) !important; 
                    display: flex !important; 
                    align-items: center !important; 
                    justify-content: center !important; 
                    color: white !important; 
                    font-size: 16px !important; 
                    margin-right: 12px !important; 
                    flex-shrink: 0 !important;
                ">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="chat-info" style="
                    flex: 1 !important; 
                    min-width: 0 !important;
                ">
                    <div class="chat-name" style="
                        font-weight: 600 !important; 
                        font-size: 14px !important; 
                        color: #1a1a1a !important; 
                        margin-bottom: 4px !important;
                    ">Chat Pratiche</div>
                    <div class="chat-selector">
                        <select id="clientSelector" class="form-select" style="
                            font-size: 12px !important; 
                            border: 1px solid #e1e8ed !important; 
                            border-radius: 6px !important; 
                            padding: 4px 8px !important; 
                            background: white !important; 
                            color: #1a1a1a !important; 
                            width: 100% !important;
                        ">
                            <option value="">-- Seleziona Cliente --</option>
                            <?php foreach ($clienti_chat as $cliente): ?>
                                <option value="<?= htmlspecialchars($cliente['id']) ?>">
                                    <?= htmlspecialchars($cliente['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="chat-meta" style="
                    display: flex !important; 
                    flex-direction: column !important; 
                    align-items: flex-end !important; 
                    gap: 4px !important;
                ">
                    <span id="practiceChatBadge" class="chat-unread-badge hidden" style="
                        background: #dc3545 !important; 
                        color: white !important; 
                        border-radius: 10px !important; 
                        padding: 2px 6px !important; 
                        font-size: 11px !important; 
                        font-weight: bold !important; 
                        min-width: 18px !important; 
                        text-align: center !important;
                    ">0</span>
                </div>
            </div>
            
            <!-- Sezione Chat Private -->
            <div class="chat-section-header" style="
                display: flex !important; 
                justify-content: space-between !important; 
                align-items: center !important; 
                padding: 12px 16px 8px 16px !important; 
                font-size: 13px !important; 
                font-weight: 600 !important; 
                color: #657786 !important; 
                text-transform: uppercase !important; 
                letter-spacing: 0.5px !important;
            ">
                <span><i class="fas fa-user-friends" style="margin-right: 6px !important;"></i> Chat Private</span>
                <button id="newPrivateChatBtn" class="btn-new-chat" title="Nuova Chat Privata" style="
                    background: #667eea !important; 
                    color: white !important; 
                    border: none !important; 
                    border-radius: 50% !important; 
                    width: 24px !important; 
                    height: 24px !important; 
                    display: flex !important; 
                    align-items: center !important; 
                    justify-content: center !important; 
                    cursor: pointer !important; 
                    font-size: 12px !important; 
                    transition: background-color 0.2s ease !important;
                ">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <!-- Lista Chat Private (dinamica) -->
            <div id="privateChatsList" style="
                padding: 0 16px !important;
            ">
                <!-- Le chat private verranno caricate dinamicamente qui -->
            </div>
            
            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="loading-indicator" style="
                display: none !important; 
                text-align: center !important; 
                padding: 20px !important; 
                color: #657786 !important; 
                font-size: 14px !important;
            ">
                <div class="spinner"></div>
                Caricamento...
            </div>
            
        </div>
        
        <!-- Chat Window -->
        <div id="chatWindow" class="chat-window" style="
            position: absolute !important; 
            top: 0 !important; 
            left: 0 !important; 
            width: 100% !important; 
            height: 100% !important; 
            background: white !important; 
            display: none !important; 
            flex-direction: column !important; 
            border-radius: 16px !important; 
            overflow: hidden !important;
        ">
            <!-- Chat Window Header -->
            <div class="chat-window-header" style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
                color: white !important; 
                padding: 16px 20px !important; 
                display: flex !important; 
                align-items: center !important; 
                gap: 12px !important; 
                border-bottom: 1px solid rgba(255,255,255,0.1) !important;
            ">
                <button id="backToListBtn" class="btn-back" title="Torna alla lista" style="
                    background: none !important; 
                    border: none !important; 
                    color: white !important; 
                    font-size: 18px !important; 
                    cursor: pointer !important; 
                    padding: 4px !important; 
                    border-radius: 4px !important; 
                    transition: background-color 0.2s ease !important;
                ">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-window-info" style="
                    flex: 1 !important;
                ">
                    <div id="chatWindowTitle" class="chat-window-title" style="
                        font-size: 16px !important; 
                        font-weight: 600 !important; 
                        margin: 0 !important;
                    ">Nome Chat</div>
                    <div id="chatWindowStatus" class="chat-window-status" style="
                        font-size: 12px !important; 
                        opacity: 0.8 !important; 
                        margin: 0 !important;
                    ">Seleziona una chat</div>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div id="chatMessagesArea" class="chat-messages-area" style="
                flex: 1 !important; 
                padding: 16px !important; 
                overflow-y: auto !important; 
                background: #f8f9fa !important; 
                display: flex !important; 
                flex-direction: column !important; 
                gap: 8px !important;
            ">
                <!-- I messaggi verranno caricati dinamicamente qui -->
                <div class="loading-indicator" style="
                    display: flex !important; 
                    align-items: center !important; 
                    justify-content: center !important; 
                    flex-direction: column !important; 
                    gap: 8px !important; 
                    padding: 40px !important; 
                    color: #657786 !important; 
                    font-size: 14px !important;
                ">
                    <div class="spinner" style="
                        width: 24px !important; 
                        height: 24px !important; 
                        border: 2px solid #e1e8ed !important; 
                        border-top-color: #667eea !important; 
                        border-radius: 50% !important; 
                        animation: spin 1s linear infinite !important;
                    "></div>
                    Caricamento messaggi...
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="chat-input-area" style="
                padding: 16px !important; 
                background: white !important; 
                border-top: 1px solid #e1e8ed !important; 
                display: flex !important; 
                gap: 8px !important; 
                align-items: center !important;
            ">
                <input 
                    type="text" 
                    id="chatMessageInput" 
                    class="chat-message-input" 
                    placeholder="Scrivi un messaggio..."
                    maxlength="1000"
                    disabled
                    style="
                        flex: 1 !important; 
                        border: 1px solid #e1e8ed !important; 
                        border-radius: 20px !important; 
                        padding: 10px 16px !important; 
                        font-size: 14px !important; 
                        outline: none !important; 
                        background: #f8f9fa !important; 
                        transition: all 0.2s ease !important;
                    "
                />
                <button id="sendMessageBtn" class="btn-send" title="Invia messaggio" disabled style="
                    background: #667eea !important; 
                    color: white !important; 
                    border: none !important; 
                    border-radius: 50% !important; 
                    width: 40px !important; 
                    height: 40px !important; 
                    display: flex !important; 
                    align-items: center !important; 
                    justify-content: center !important; 
                    cursor: pointer !important; 
                    font-size: 16px !important; 
                    transition: background-color 0.2s ease !important;
                ">
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

// Debug immediato
console.log('🚀 Chat Config caricato:', window.chatConfig);

// Test elementi DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Verifica elementi DOM:');
    const elementos = [
        'chatFooterWidget',
        'chatToggleBtn', 
        'chatPanel',
        'globalChatItem'
    ];
    
    elementos.forEach(id => {
        const el = document.getElementById(id);
        console.log(`${id}:`, el ? '✅ Trovato' : '❌ Non trovato');
    });
});// Log di debug
if (window.chatConfig.debug) {
    console.log('🐛 Debug Mode attivato');
}
</script>

<!-- Stili CSS aggiuntivi per assicurare la visualizzazione -->
<style>
/* Animazione spinner */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* CSS di override per garantire visibilità */
#chatFooterWidget {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    z-index: 9999 !important;
    font-family: 'Segoe UI', Arial, sans-serif !important;
}

/* Hover effects */
#chatToggleBtn:hover {
    transform: scale(1.1) translateY(-2px) !important;
    box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3) !important;
}

.chat-item:hover {
    background: #f8f9fa !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1) !important;
}

#chatMinimizeBtn:hover,
#backToListBtn:hover {
    background: rgba(255,255,255,0.2) !important;
}

#newPrivateChatBtn:hover {
    background: #5a67d8 !important;
}

#sendMessageBtn:hover {
    background: #5a67d8 !important;
}

#chatMessageInput:focus {
    border-color: #667eea !important;
    background: white !important;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1) !important;
}

/* Media query per responsive */
@media (max-width: 768px) {
    #chatFooterWidget {
        bottom: 10px !important;
        right: 10px !important;
    }
    
    #chatPanel {
        width: calc(100vw - 20px) !important;
        height: calc(100vh - 100px) !important;
        bottom: 70px !important;
        right: 0 !important;
        left: 10px !important;
    }
}

/* Classe hidden */
.hidden {
    display: none !important;
}
</style>
