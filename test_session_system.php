<!DOCTYPE html>
<html>
<head>
    <title>Test Sistema Sessioni CRM</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .online { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .offline { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        button { margin: 5px; padding: 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .log { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin: 10px 0; height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <h1>Test Sistema Gestione Sessioni CRM</h1>
    
    <div class="status offline" id="sessionStatus">
        Status: Disconnesso
    </div>
    
    <div>
        <button onclick="testLogin()">Test Login</button>
        <button onclick="testHeartbeat()">Test Heartbeat</button>
        <button onclick="testLogout()">Test Logout</button>
        <button onclick="loadUsers()">Carica Utenti</button>
        <button onclick="clearLog()">Pulisci Log</button>
    </div>
    
    <div class="log" id="logArea"></div>
    
    <div id="usersList">
        <h3>Lista Utenti Online:</h3>
        <div id="usersContent">Clicca "Carica Utenti" per vedere gli utenti online</div>
    </div>

    <script>
        let sessionHeartbeatInterval;
        
        function log(message) {
            const logArea = document.getElementById('logArea');
            const timestamp = new Date().toLocaleTimeString();
            logArea.innerHTML += `[${timestamp}] ${message}\n`;
            logArea.scrollTop = logArea.scrollHeight;
        }
        
        function updateStatus(isOnline, message) {
            const status = document.getElementById('sessionStatus');
            status.className = `status ${isOnline ? 'online' : 'offline'}`;
            status.textContent = `Status: ${message}`;
        }
        
        async function testLogin() {
            try {
                log('Tentativo di login...');
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'username=admin&password=admin'
                });
                
                if (response.ok) {
                    log('Login effettuato con successo');
                    updateStatus(true, 'Online');
                    startHeartbeat();
                } else {
                    log('Errore nel login: ' + response.status);
                    updateStatus(false, 'Errore Login');
                }
            } catch (error) {
                log('Errore: ' + error.message);
                updateStatus(false, 'Errore di Rete');
            }
        }
        
        async function testHeartbeat() {
            try {
                log('Invio heartbeat...');
                const response = await fetch('api/session_heartbeat.php', {
                    method: 'POST',
                    credentials: 'include'
                });
                
                const result = await response.json();
                if (result.success) {
                    log('Heartbeat inviato con successo');
                    updateStatus(true, 'Online (Heartbeat OK)');
                } else {
                    log('Errore heartbeat: ' + result.error);
                    updateStatus(false, 'Sessione Scaduta');
                }
            } catch (error) {
                log('Errore heartbeat: ' + error.message);
            }
        }
        
        async function testLogout() {
            try {
                log('Logout in corso...');
                clearInterval(sessionHeartbeatInterval);
                
                const response = await fetch('logout.php', {
                    method: 'GET',
                    credentials: 'include'
                });
                
                if (response.ok) {
                    log('Logout effettuato con successo');
                    updateStatus(false, 'Disconnesso');
                } else {
                    log('Errore nel logout: ' + response.status);
                }
            } catch (error) {
                log('Errore logout: ' + error.message);
            }
        }
        
        async function loadUsers() {
            try {
                log('Caricamento utenti online...');
                const response = await fetch('api/utenti_for_chat.php', {
                    credentials: 'include'
                });
                
                const users = await response.json();
                const usersContent = document.getElementById('usersContent');
                
                if (Array.isArray(users) && users.length > 0) {
                    usersContent.innerHTML = users.map(user => {
                        const status = user.is_online ? '🟢 Online' : '🔴 Offline';
                        return `<div>${user.nome} (${user.username}) - ${status}</div>`;
                    }).join('');
                    log(`Caricati ${users.length} utenti`);
                } else {
                    usersContent.innerHTML = 'Nessun utente trovato o errore nel caricamento';
                    log('Nessun utente trovato');
                }
            } catch (error) {
                log('Errore caricamento utenti: ' + error.message);
            }
        }
        
        function startHeartbeat() {
            // Pulisce eventuali interval precedenti
            if (sessionHeartbeatInterval) {
                clearInterval(sessionHeartbeatInterval);
            }
            
            // Avvia heartbeat ogni 2 minuti
            sessionHeartbeatInterval = setInterval(testHeartbeat, 120000);
            log('Heartbeat automatico avviato (ogni 2 minuti)');
        }
        
        function clearLog() {
            document.getElementById('logArea').innerHTML = '';
        }
        
        // Gestione chiusura finestra
        window.addEventListener('beforeunload', function() {
            if (sessionHeartbeatInterval) {
                navigator.sendBeacon('api/session_end.php');
            }
        });
        
        // Log iniziale
        log('Sistema di test caricato. Prova il login per iniziare.');
    </script>
</body>
</html>
