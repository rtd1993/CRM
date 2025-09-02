<?php
require_once 'includes/header.php';
?>

<style>
    .test-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .test-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .test-button {
        background: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        margin: 5px;
    }
    
    .test-button:hover {
        background: #0056b3;
    }
    
    .test-result {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-top: 10px;
        font-family: monospace;
        font-size: 12px;
        max-height: 200px;
        overflow-y: auto;
    }
</style>

<div class="test-container">
    <h1><i class="fas fa-comments"></i> Test Sistema Chat</h1>
    
    <div class="test-card">
        <h3>🔧 Test Footer Chat</h3>
        <p>Il footer con i pulsanti chat dovrebbe essere visibile in fondo alla pagina.</p>
        
        <div class="test-buttons">
            <button class="test-button" onclick="testChatSystem()">Test Inizializzazione Chat</button>
            <button class="test-button" onclick="testAPI()">Test API</button>
            <button class="test-button" onclick="testNotifications()">Test Notifiche</button>
        </div>
        
        <div class="test-result" id="test-results">
            Premi un pulsante per iniziare i test...
        </div>
    </div>
    
    <div class="test-card">
        <h3>📋 Stato Sistema</h3>
        <ul>
            <li><strong>Chat Footer CSS:</strong> <span id="css-status">❓ Checking...</span></li>
            <li><strong>Chat System JS:</strong> <span id="js-status">❓ Checking...</span></li>
            <li><strong>Socket.IO:</strong> <span id="socket-status">❓ Checking...</span></li>
            <li><strong>Database:</strong> <span id="db-status">❓ Checking...</span></li>
        </ul>
    </div>
    
    <div class="test-card">
        <h3>📊 Statistiche Chat</h3>
        <div id="chat-stats">Caricamento...</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkSystemStatus();
    loadChatStats();
});

function log(message) {
    const results = document.getElementById('test-results');
    results.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '\n';
    results.scrollTop = results.scrollHeight;
}

function checkSystemStatus() {
    // Check CSS
    const cssLink = document.querySelector('link[href*="chat-footer.css"]');
    document.getElementById('css-status').innerHTML = cssLink ? '✅ Caricato' : '❌ Non trovato';
    
    // Check JS
    document.getElementById('js-status').innerHTML = typeof window.chatSystem !== 'undefined' ? '✅ Inizializzato' : '⏳ Caricamento...';
    
    // Check Socket.IO
    document.getElementById('socket-status').innerHTML = typeof io !== 'undefined' ? '✅ Disponibile' : '❌ Non disponibile';
    
    // Check Database
    fetch('/api/current_user.php')
        .then(r => r.json())
        .then(data => {
            document.getElementById('db-status').innerHTML = data.id ? '✅ Connesso' : '❌ Errore';
        })
        .catch(() => {
            document.getElementById('db-status').innerHTML = '❌ Errore connessione';
        });
}

function testChatSystem() {
    log('🧪 Test inizializzazione Chat System...');
    
    if (typeof window.chatSystem === 'undefined') {
        log('❌ Chat System non inizializzato');
        return;
    }
    
    log('✅ Chat System disponibile');
    log('📊 User: ' + JSON.stringify(window.chatSystem.currentUser));
    log('🔗 Socket: ' + (window.chatSystem.socket ? 'Connesso' : 'Polling mode'));
    log('📨 Chat aperte: ' + window.chatSystem.openChats.size);
}

async function testAPI() {
    log('🧪 Test API...');
    
    try {
        // Test current user
        log('📡 Test /api/current_user.php...');
        const userResponse = await fetch('/api/current_user.php');
        const userData = await userResponse.json();
        log('✅ Current user: ' + JSON.stringify(userData));
        
        // Test users list
        log('📡 Test /api/users_list.php...');
        const usersResponse = await fetch('/api/users_list.php');
        const usersData = await usersResponse.json();
        log('✅ Users list: ' + usersData.length + ' utenti');
        
        // Test notifications
        log('📡 Test /api/chat_notifications.php...');
        const notifResponse = await fetch('/api/chat_notifications.php');
        const notifData = await notifResponse.json();
        log('✅ Notifications: ' + notifData.length + ' chat');
        
        // Test pratica chat
        log('📡 Test /api/get_pratica_chat.php...');
        const praticaResponse = await fetch('/api/get_pratica_chat.php');
        const praticaData = await praticaResponse.json();
        log('✅ Pratica chat: ' + JSON.stringify(praticaData));
        
    } catch (error) {
        log('❌ Errore test API: ' + error.message);
    }
}

function testNotifications() {
    log('🧪 Test notifiche...');
    
    if (typeof window.chatSystem === 'undefined') {
        log('❌ Chat System non disponibile');
        return;
    }
    
    // Simula notifica
    window.chatSystem.incrementNotification('globale');
    log('✅ Notifica globale simulata');
    
    window.chatSystem.incrementNotification('pratiche');
    log('✅ Notifica pratiche simulata');
    
    setTimeout(() => {
        window.chatSystem.resetNotifications('globale');
        log('🔄 Notifica globale resettata');
    }, 3000);
}

async function loadChatStats() {
    try {
        const response = await fetch('/api/chat_notifications.php');
        const data = await response.json();
        
        let stats = '<div class="row">';
        stats += '<div class="col-md-6"><strong>Chat Totali:</strong> ' + data.length + '</div>';
        
        const unreadTotal = data.reduce((sum, chat) => sum + (chat.unread_count || 0), 0);
        stats += '<div class="col-md-6"><strong>Messaggi non letti:</strong> ' + unreadTotal + '</div>';
        stats += '</div>';
        
        stats += '<div class="mt-2"><h5>Dettagli Chat:</h5><ul>';
        data.forEach(chat => {
            stats += `<li><strong>${chat.name}:</strong> ${chat.unread_count || 0} non letti (${chat.chat_type})</li>`;
        });
        stats += '</ul></div>';
        
        document.getElementById('chat-stats').innerHTML = stats;
        
    } catch (error) {
        document.getElementById('chat-stats').innerHTML = '❌ Errore caricamento statistiche: ' + error.message;
    }
}

// Update JS status when chat system loads
setTimeout(() => {
    if (typeof window.chatSystem !== 'undefined') {
        document.getElementById('js-status').innerHTML = '✅ Inizializzato';
    }
}, 2000);
</script>

}
</script>

</body>
</html>
