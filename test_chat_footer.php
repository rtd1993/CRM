<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-vial"></i>
                        Test Chat Footer System
                    </h3>
                </div>
                <div class="card-body">
                    
                    <!-- Informazioni Test -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>📊 Informazioni Sistema</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Utente Corrente:</span>
                                    <strong><?= htmlspecialchars($_SESSION['user_name']) ?> (ID: <?= $_SESSION['user_id'] ?>)</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Timestamp:</span>
                                    <strong><?= date('Y-m-d H:i:s') ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Chat Footer:</span>
                                    <strong id="chatFooterStatus">🔄 Caricamento...</strong>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>🧪 Test Funzionalità</h5>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="testToggleChat()">
                                    <i class="fas fa-toggle-on"></i> Test Toggle Chat
                                </button>
                                <button class="btn btn-outline-success" onclick="testGlobalChat()">
                                    <i class="fas fa-globe"></i> Test Chat Globale
                                </button>
                                <button class="btn btn-outline-warning" onclick="testPracticeChat()">
                                    <i class="fas fa-folder"></i> Test Chat Pratiche
                                </button>
                                <button class="btn btn-outline-info" onclick="testPrivateChat()">
                                    <i class="fas fa-user"></i> Test Chat Private
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Debug Panel -->
                    <div class="row">
                        <div class="col-12">
                            <h5>🔍 Debug Panel</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <pre id="debugOutput" style="font-size: 12px; max-height: 300px; overflow-y: auto;">Caricamento debug info...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test API Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>🔧 Test API</h5>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-secondary" onclick="testAPI('users/get_list.php')">
                                    Test Users API
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="testAPI('conversations/get_private.php')">
                                    Test Private Chats API
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="testAPI('notifications/get_unread.php')">
                                    Test Notifications API
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="debugChatSystem()">
                                    Debug Chat System
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Test Functions
function testToggleChat() {
    if (window.chatFooterSystem) {
        window.chatFooterSystem.togglePanel();
        updateDebug('Toggle panel eseguito');
    } else {
        updateDebug('ERROR: Chat system non inizializzato');
    }
}

function testGlobalChat() {
    if (window.chatFooterSystem) {
        window.chatFooterSystem.openChat('globale', 1, 'Chat Generale');
        updateDebug('Apertura chat globale');
    } else {
        updateDebug('ERROR: Chat system non inizializzato');
    }
}

function testPracticeChat() {
    const select = document.getElementById('clientSelector');
    if (select && select.options.length > 1) {
        select.selectedIndex = 1;
        select.dispatchEvent(new Event('change'));
        updateDebug('Test chat pratiche con primo cliente');
    } else {
        updateDebug('ERROR: Nessun cliente disponibile per test');
    }
}

function testPrivateChat() {
    if (window.chatFooterSystem) {
        window.chatFooterSystem.showUserSelectionModal();
        updateDebug('Apertura modal selezione utenti');
    } else {
        updateDebug('ERROR: Chat system non inizializzato');
    }
}

async function testAPI(endpoint) {
    try {
        updateDebug(`Testing API: ${endpoint}`);
        
        const response = await fetch(`api/chat/${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
        
        const data = await response.json();
        updateDebug(`API Response (${response.status}):`, data);
        
    } catch (error) {
        updateDebug(`API Error: ${error.message}`);
    }
}

function debugChatSystem() {
    if (window.chatFooterSystem) {
        const debug = {
            isInitialized: window.chatFooterSystem.isInitialized,
            isVisible: window.chatFooterSystem.isVisible,
            currentChat: window.chatFooterSystem.currentChat,
            config: window.chatFooterSystem.config,
            onlineUsers: Array.from(window.chatFooterSystem.onlineUsers.entries()),
            unreadCounts: window.chatFooterSystem.unreadCounts
        };
        updateDebug('Chat System Debug:', debug);
    } else {
        updateDebug('Chat System non disponibile');
    }
}

function updateDebug(message, data = null) {
    const output = document.getElementById('debugOutput');
    const timestamp = new Date().toLocaleTimeString();
    
    let logEntry = `[${timestamp}] ${message}`;
    if (data) {
        logEntry += '\n' + JSON.stringify(data, null, 2);
    }
    
    output.textContent += logEntry + '\n\n';
    output.scrollTop = output.scrollHeight;
}

// Status Check
function checkChatStatus() {
    const statusEl = document.getElementById('chatFooterStatus');
    
    if (typeof window.chatConfig !== 'undefined') {
        if (window.chatFooterSystem && window.chatFooterSystem.isInitialized) {
            statusEl.innerHTML = '✅ <span class="text-success">Attivo e Funzionante</span>';
        } else {
            statusEl.innerHTML = '⏳ <span class="text-warning">In Inizializzazione</span>';
        }
    } else {
        statusEl.innerHTML = '❌ <span class="text-danger">Non Configurato</span>';
    }
}

// Check iniziale e ogni secondo
checkChatStatus();
setInterval(checkChatStatus, 1000);

// Log iniziale
document.addEventListener('DOMContentLoaded', function() {
    updateDebug('=== TEST CHAT FOOTER SYSTEM ===');
    updateDebug('Page loaded, checking chat system...');
    
    if (typeof window.chatConfig !== 'undefined') {
        updateDebug('Chat config found:', window.chatConfig);
    } else {
        updateDebug('ERROR: Chat config not found');
    }
    
    setTimeout(() => {
        if (window.chatFooterSystem) {
            updateDebug('Chat system initialized successfully');
            updateDebug('Available methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(window.chatFooterSystem)));
        } else {
            updateDebug('ERROR: Chat system not initialized after 2 seconds');
        }
    }, 2000);
});
</script>

<?php require_once 'includes/footer.php'; ?>
