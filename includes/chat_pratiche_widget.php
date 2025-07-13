<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'chat.php'])): ?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();
$clienti = $pdo->query("SELECT id, `cognome/ragione sociale`, nome FROM clienti ORDER BY `cognome/ragione sociale`, nome")->fetchAll();
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
                        <?= htmlspecialchars($cli['cognome/ragione sociale']) . " " . htmlspecialchars($cli['nome']) ?>
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
function togglePraticaChat() {
    document.getElementById('pratica-chat-widget').classList.toggle('open');
}
document.getElementById('clienteSelect').addEventListener('change', function() {
    let clienteId = this.value;
    document.getElementById('praticaMsg').disabled = !clienteId;
    document.getElementById('sendPraticaBtn').disabled = !clienteId;
    loadPraticaChat(clienteId);
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
        if (!messages.length) {
            chatBox.innerHTML = '<small class="text-muted">Nessun messaggio per questa pratica.</small>';
        } else {
            chatBox.innerHTML = messages.map(m =>
                `<div><strong>${m.utente}</strong> <small class="text-muted">${m.data}</small><br>${m.testo}</div><hr style="margin:0.3em 0;">`
            ).join('');
            chatBox.scrollTop = chatBox.scrollHeight;
        }
      })
      .catch(error => {
        console.error('Errore caricamento chat:', error);
        chatBox.innerHTML = '<span class="text-danger">Errore: ' + error.message + '</span>';
      });
}

document.getElementById('praticaChatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let clienteId = document.getElementById('clienteSelect').value;
    let msg = document.getElementById('praticaMsg').value.trim();
    if (!clienteId || !msg) return;
    
    const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
    fetch(baseUrl + '/ajax/pratica_chat_send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'cliente_id=' + encodeURIComponent(clienteId) + '&msg=' + encodeURIComponent(msg)
    })
    .then(r => {
        if (!r.ok) {
            throw new Error(`HTTP ${r.status}: ${r.statusText}`);
        }
        return r.json();
    })
    .then(resp => {
        if (resp.ok) {
            loadPraticaChat(clienteId);
            document.getElementById('praticaMsg').value = '';
        } else {
            console.error('Errore invio messaggio:', resp.error);
            alert('Errore nell\'invio del messaggio: ' + (resp.error || 'Errore sconosciuto'));
        }
    })
    .catch(error => {
        console.error('Errore invio messaggio:', error);
        alert('Errore nell\'invio del messaggio: ' + error.message);
    });
});
function togglePraticaChat() {
    document.getElementById('pratica-chat-widget').classList.toggle('open');
}
</script>
<?php endif; ?>