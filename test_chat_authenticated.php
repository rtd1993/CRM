<?php
// Simuliamo una sessione utente per testare le API
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_role'] = 'admin';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Chat Widget con Sessione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/chat-footer.css">
    <style>
        body { padding: 20px; }
        .debug-panel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .api-test-btn {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Chat Widget - Utente Autenticato</h1>
        
        <div class="debug-panel">
            <h3>Informazioni Sessione</h3>
            <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?></p>
            <p><strong>Nome:</strong> <?= $_SESSION['user_name'] ?></p>
            <p><strong>Ruolo:</strong> <?= $_SESSION['user_role'] ?></p>
        </div>
        
        <div class="debug-panel">
            <h3>Test API Diretti</h3>
            <button class="btn btn-primary api-test-btn" onclick="testAPI('/api/chat/users/get_list.php')">
                Test Users API
            </button>
            <button class="btn btn-success api-test-btn" onclick="testAPI('/api/chat/conversations/get_private.php')">
                Test Private Chats
            </button>
            <button class="btn btn-warning api-test-btn" onclick="testAPI('/api/chat/notifications/get_unread.php')">
                Test Notifications
            </button>
            <button class="btn btn-info api-test-btn" onclick="testAPI('/api/chat/messages/get_history.php', {conversation_id: 1})">
                Test Messages
            </button>
        </div>
        
        <div class="debug-panel">
            <h3>Output API</h3>
            <pre id="apiOutput" style="background: #000; color: #0f0; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;"></pre>
        </div>
        
        <div class="debug-panel">
            <h3>Chat Widget Status</h3>
            <p>Widget visibile: <span id="widgetStatus">-</span></p>
            <p>Sistema inizializzato: <span id="systemStatus">-</span></p>
            <button class="btn btn-primary" onclick="toggleWidget()">Toggle Widget</button>
            <button class="btn btn-secondary" onclick="debugWidget()">Debug Widget</button>
        </div>
        
        <div style="height: 200px; background: #f0f0f0; padding: 20px; text-align: center; margin-top: 30px;">
            <h4>Area di test - Il widget dovrebbe apparire in basso a destra</h4>
            <p>Clicca il pulsante chat per testare l'apertura del pannello</p>
        </div>
    </div>

    <!-- Chat Widget -->
    <?php include 'includes/chat-footer-widget-simple.php'; ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/chat-footer.js"></script>
    
    <script>
        async function testAPI(endpoint, data = {}) {
            const output = document.getElementById('apiOutput');
            output.textContent += `\n[${new Date().toLocaleTimeString()}] Testing ${endpoint}...\n`;
            
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                output.textContent += `Status: ${response.status}\n`;
                output.textContent += `Response: ${JSON.stringify(result, null, 2)}\n`;
                output.textContent += '─'.repeat(50) + '\n';
                
            } catch (error) {
                output.textContent += `ERROR: ${error.message}\n`;
                output.textContent += '─'.repeat(50) + '\n';
            }
            
            output.scrollTop = output.scrollHeight;
        }
        
        function updateStatus() {
            const widgetEl = document.getElementById('chatFooterWidget');
            const widgetStatus = document.getElementById('widgetStatus');
            const systemStatus = document.getElementById('systemStatus');
            
            widgetStatus.textContent = widgetEl ? 'SI' : 'NO';
            systemStatus.textContent = window.chatFooterSystem && window.chatFooterSystem.isInitialized ? 'SI' : 'NO';
        }
        
        function toggleWidget() {
            if (window.chatFooterSystem) {
                window.chatFooterSystem.togglePanel();
            }
        }
        
        function debugWidget() {
            if (window.chatFooterSystem) {
                console.log('Chat System Debug:', {
                    isInitialized: window.chatFooterSystem.isInitialized,
                    isVisible: window.chatFooterSystem.isVisible,
                    config: window.chatFooterSystem.config,
                    elements: Object.keys(window.chatFooterSystem.elements || {})
                });
                alert('Debug info logged to console (F12)');
            }
        }
        
        // Aggiorna status ogni secondo
        setInterval(updateStatus, 1000);
        updateStatus();
        
        // Log iniziale
        document.getElementById('apiOutput').textContent = '[READY] API Test Console\n' + '='.repeat(50) + '\n';
    </script>
</body>
</html>
