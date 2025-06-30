<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

// Carica pratiche/clienti (supponiamo tabella clienti: id, cognome, nome)
$clienti = $pdo->query("SELECT id, `cognome/ragione sociale`, nome FROM clienti ORDER BY `cognome/ragione sociale`, nome")->fetchAll();
?>

<style>
#pratica-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 380px; /* Affiancato a chat_widget che di solito è right:20px, width:350px */
    width: 340px;
    z-index: 1080;
    font-family: 'Segoe UI', Arial, sans-serif;
}
#pratica-chat-header {
    background: #007bff;
    color: #fff;
    padding: 10px 16px;
    cursor: pointer;
    border-radius: 10px 10px 0 0;
    font-weight: bold;
    box-shadow: 0 1px 2px #00336630;
}
#pratica-chat-body {
    background: #f4f7fa;
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 10px 10px;
    padding: 10px;
    display: none;
    max-height: 370px;
    overflow-y: auto;
}
#pratica-chat-widget.open #pratica-chat-body {
    display: block;
}
</style>

<div id="pratica-chat-widget">
    <div id="pratica-chat-header" onclick="togglePraticaChat()">💬 Chat pratica</div>
    <div id="pratica-chat-body">
        <form id="praticaChatForm" autocomplete="off">
            <div class="mb-2">
                <select class="form-select" id="clienteSelect" required>
                    <option value="" selected disabled>Seleziona pratica (Cognome/Rag. Sociale)...</option>
                    <?php foreach ($clienti as $cli): ?>
                        <option value="<?= $cli['id'] ?>">
                            <?= htmlspecialchars($cli['cognome/ragione sociale']) . " " . htmlspecialchars($cli['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="praticaChatBox" style="height:200px; overflow-y:auto; background:#fff; border:1px solid #e0e0e0; border-radius:5px; padding:8px; margin-bottom:8px;">
                <small class="text-muted">Seleziona una pratica per caricare la chat...</small>
            </div>
            <div class="input-group">
                <input type="text" id="praticaMsg" class="form-control" placeholder="Scrivi un messaggio..." autocomplete="off" disabled>
                <button class="btn btn-success" type="submit" id="sendPraticaBtn" disabled>Invia</button>
            </div>
        </form>
    </div>
</div>

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
    fetch('/ajax/pratica_chat_fetch.php?cliente_id=' + clienteId)
      .then(r => r.json())
      .then(messages => {
        if (!messages.length) {
            chatBox.innerHTML = '<small class="text-muted">Nessun messaggio per questa pratica.</small>';
        } else {
            chatBox.innerHTML = messages.map(m =>
                `<div><strong>${m.utente}</strong> <small class="text-muted">${m.data}</small><br>${m.testo}</div><hr style="margin:0.3em 0;">`
            ).join('');
            chatBox.scrollTop = chatBox.scrollHeight;
        }
      });
}

document.getElementById('praticaChatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let clienteId = document.getElementById('clienteSelect').value;
    let msg = document.getElementById('praticaMsg').value.trim();
    if (!clienteId || !msg) return;
    fetch('/ajax/pratica_chat_send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'cliente_id=' + encodeURIComponent(clienteId) + '&msg=' + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.ok) {
            loadPraticaChat(clienteId);
            document.getElementById('praticaMsg').value = '';
        }
    });
});
</script>