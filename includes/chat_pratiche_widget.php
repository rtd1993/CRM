<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'chat.php'])): ?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();
// Carica i clienti direttamente in PHP per affidabilità
$clienti = $pdo->query("SELECT id, `cognome/ragione sociale` as cognome_ragione_sociale, nome FROM clienti ORDER BY `cognome/ragione sociale`, nome")->fetchAll();
?>
<div class="crm-chat-widget" id="chat-pratiche-widget" data-tooltip="Chat Pratiche">
    <div class="crm-chat-header" onclick="toggleChatWidget('chat-pratiche-widget')">
        <span class="chat-icon">📁</span>
        <span class="chat-text">Chat Pratiche</span>
    </div>
    <div class="crm-chat-body">
        <form id="praticaChatForm" autocomplete="off">
            <select class="form-select crm-chat-select" id="clienteSelect" required>
                <option value="" selected disabled>Seleziona pratica (Cognome/Rag. Sociale)...</option>
                <?php foreach ($clienti as $cli): ?>
                    <option value="<?= $cli['id'] ?>">
                        <?= htmlspecialchars($cli['cognome_ragione_sociale']) . " " . htmlspecialchars($cli['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="crm-chat-messages" id="praticaChatBox">
                <small class="text-muted">Seleziona una pratica per caricare la chat...</small>
            </div>
            <div class="crm-chat-input-group">
                <input type="text" id="praticaMsg" class="form-control crm-chat-pratiche-input" placeholder="Scrivi un messaggio..." autocomplete="off" disabled>
                <button class="crm-chat-send-btn btn btn-success" type="submit" id="sendPraticaBtn" disabled>Invia</button>
            </div>
        </form>
    </div>
</div>
<script>
function toggleChatWidget(id) {
    document.getElementById(id).classList.toggle('open');
}
</script>

<script>
let currentClienteId = null;
let praticheSocket = null;

function togglePraticaChat() {
    document.getElementById('pratica-chat-widget').classList.toggle('open');
}

// Inizializza Socket.IO per le pratiche
function initializePraticheSocket() {
    if (typeof io === 'undefined') {
        setTimeout(initializePraticheSocket, 500);
        return;
    }
    
    const socketUrl = '<?= getSocketIOUrl() ?>';
    praticheSocket = io(socketUrl);
    
    praticheSocket.emit("register", <?= json_encode($_SESSION['user_id']) ?>);
    
    // Ricevi appunti in tempo reale
    praticheSocket.on("appunto aggiunto", data => {
        if (data.pratica_id == currentClienteId) {
            addMessageToChat(data.utente_nome, data.testo, data.data_inserimento);
        }
    });
}

document.getElementById('clienteSelect').addEventListener('change', function() {
    let clienteId = this.value;
    currentClienteId = clienteId;
    
    document.getElementById('praticaMsg').disabled = !clienteId;
    document.getElementById('sendPraticaBtn').disabled = !clienteId;
    
    if (clienteId) {
        loadPraticaChat(clienteId);
    }
});

function loadPraticaChat(clienteId) {
    const chatBox = document.getElementById('praticaChatBox');
    chatBox.innerHTML = '<span class="text-secondary">Caricamento...</span>';
    
    const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
    
    fetch(baseUrl + '/ajax/pratica_chat_fetch.php?cliente_id=' + clienteId)
      .then(r => {
        if (!r.ok) {
            throw new Error(`HTTP ${r.status}: ${r.statusText}`);
        }
        return r.json();
      })
      .then(messages => {
        renderChatMessages(messages, chatBox);
      })
      .catch(error => {
        console.error('Errore caricamento chat:', error);
        chatBox.innerHTML = '<span class="text-danger">Errore: ' + error.message + '</span>';
      });
}

// Rendering ottimizzato dei messaggi
function renderChatMessages(messages, chatBox) {
    chatBox.innerHTML = '';
    
    if (!messages.length) {
        chatBox.innerHTML = '<small class="text-muted">Nessun messaggio per questa pratica.</small>';
        return;
    }
    
    const fragment = document.createDocumentFragment();
    
    messages.forEach(m => {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'pratica-message';
        msgDiv.innerHTML = `<strong>${m.utente}</strong> <small class="text-muted">${m.data}</small><br>${m.testo}`;
        
        const hr = document.createElement('hr');
        hr.style.margin = '0.3em 0';
        
        fragment.appendChild(msgDiv);
        fragment.appendChild(hr);
    });
    
    chatBox.appendChild(fragment);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Aggiungi un singolo messaggio alla chat (per tempo reale)
function addMessageToChat(utente, testo, timestamp) {
    const chatBox = document.getElementById('praticaChatBox');
    
    // Se la chat è vuota, rimuovi il messaggio placeholder
    if (chatBox.innerHTML.includes('Nessun messaggio')) {
        chatBox.innerHTML = '';
    }
    
    const msgDiv = document.createElement('div');
    msgDiv.className = 'pratica-message';
    
    const data = new Date(timestamp).toLocaleString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    msgDiv.innerHTML = `<strong>${utente}</strong> <small class="text-muted">${data}</small><br>${testo}`;
    
    const hr = document.createElement('hr');
    hr.style.margin = '0.3em 0';
    
    chatBox.appendChild(msgDiv);
    chatBox.appendChild(hr);
    chatBox.scrollTop = chatBox.scrollHeight;
    
    // Notifica sonora
    playNotificationSound();
}

// Invio messaggio via Socket.IO
document.getElementById('praticaChatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let clienteId = document.getElementById('clienteSelect').value;
    let msg = document.getElementById('praticaMsg').value.trim();
    
    if (!clienteId || !msg || !praticheSocket) return;
    
    // Invia via Socket.IO per tempo reale
    praticheSocket.emit("nuovo appunto", {
        utente_id: <?= json_encode($_SESSION['user_id']) ?>,
        utente_nome: <?= json_encode($_SESSION['user_name']) ?>,
        pratica_id: parseInt(clienteId),
        testo: msg
    });
    
    document.getElementById('praticaMsg').value = '';
});

// Funzione per il suono di notifica
function playNotificationSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(800, audioContext.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0.05, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (e) {
        console.log('Audio context not available');
    }
}

// Inizializza Socket.IO quando il DOM è pronto
document.addEventListener('DOMContentLoaded', initializePraticheSocket);
</script>
<?php endif; ?>