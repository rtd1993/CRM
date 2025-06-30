<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

// Carica pratiche/clienti (supponiamo tabella clienti: id, cognome, nome)
$clienti = $pdo->query("SELECT id, 'cognome/ragione sociale', nome FROM clienti ORDER BY 'cognome/ragione sociale', nome")->fetchAll();
?>

<div class="card shadow-sm mb-4" style="max-width: 420px;">
  <div class="card-header bg-primary text-white">
    Chat Pratica Cliente
  </div>
  <div class="card-body">
    <form id="praticaChatForm" autocomplete="off">
      <div class="mb-2">
        <select class="form-select" id="clienteSelect" required>
          <option value="" selected disabled>Seleziona pratica (cognome)...</option>
          <?php foreach ($clienti as $cli): ?>
            <option value="<?= $cli['id'] ?>">
              <?= htmlspecialchars($cli['cognome/ragione sociale']) . " " . htmlspecialchars($cli['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="praticaChatBox" style="height:200px; overflow-y:auto; background:#f4f7fa; border:1px solid #e0e0e0; border-radius:5px; padding:8px; margin-bottom:8px;">
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
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({cliente_id: clienteId, testo: msg})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('praticaMsg').value = '';
            loadPraticaChat(clienteId);
        }
    });
});
</script>