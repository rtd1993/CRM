<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$nome_utente = 'Sconosciuto';
$ruolo_utente = 'guest';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT nome, ruolo FROM utenti WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $nome_utente = $row['nome'];
        $ruolo_utente = $row['ruolo'];
        $_SESSION['user_name'] = $nome_utente;
        $_SESSION['user_role'] = $ruolo_utente;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Bootstrap CDN for modern style -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f7f9fb;
        }
        header.crm-header {
            background: linear-gradient(90deg, #0056b3 0%, #003366 100%);
            color: #fff;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.09);
        }
        .crm-header .crm-title {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 2rem;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 0;
            padding: 0 0 2px 0;
            color: #ffcc00;
            text-shadow: 0 1px 0 #003366;
        }
        .crm-header .crm-user {
            font-size: 0.97rem;
        }
        .crm-header nav {
            background: rgba(0,0,0,0.05);
            border-top: 1px solid #003366;
        }
        .crm-header .crm-menu {
            padding-left: 0;
            margin-bottom: 0;
            list-style: none;
            display: flex;
            flex-wrap: wrap;
        }
        .crm-header .crm-menu li {
            margin: 0 0.5rem;
        }
        .crm-header .crm-menu a {
            color: #fff;
            padding: 10px 15px 8px 15px;
            display: block;
            border-radius: 0 0 6px 6px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .crm-header .crm-menu a:hover, .crm-header .crm-menu a.active {
            background: #ffcc00;
            color: #003366;
            text-shadow: none;
        }
        @media (max-width: 600px) {
            .crm-header .crm-title {
                font-size: 1.3rem;
            }
            .crm-header .crm-menu {
                flex-direction: column;
            }
            .crm-header .crm-menu li {
                margin: 0.25rem 0;
            }
        }
    </style>
</head>
<body>
<header class="crm-header mb-4">
    <div class="container-fluid py-2 d-flex justify-content-between align-items-center">
        <div>
            <span class="crm-title">
                <svg width="34" height="34" style="vertical-align: middle;margin-right:5px;" viewBox="0 0 100 100"><ellipse rx="45" ry="45" cx="50" cy="50" fill="#ffcc00"/><text x="50%" y="55%" fill="#003366" font-size="38" font-family="Segoe UI, Arial, sans-serif" font-weight="bold" text-anchor="middle" alignment-baseline="middle" dy=".3em">CRM</text></svg>
                ASContabilmente
            </span>
        </div>
        <div class="crm-user text-end">
            <span class="d-none d-md-inline">Utente:</span>
            <strong><?= htmlspecialchars($nome_utente) ?></strong>
            <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($ruolo_utente) ?></span>
            |
            <a href="logout.php" style="color: #ffcc00; text-decoration: none;"><b>Logout</b></a>
        </div>
    </div>
    <nav>
        <ul class="crm-menu container-fluid">
            <li><a href="/dashboard.php"<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' class="active"';?>>Dashboard</a></li>
            <li><a href="/clienti.php"<?php if(basename($_SERVER['PHP_SELF'])=='clienti.php') echo ' class="active"';?>>Clienti</a></li>
            <li><a href="/drive.php"<?php if(basename($_SERVER['PHP_SELF'])=='drive.php') echo ' class="active"';?>>Drive</a></li>
            <li><a href="/calendario.php"<?php if(basename($_SERVER['PHP_SELF'])=='calendario.php') echo ' class="active"';?>>Calendario</a></li>
            <li><a href="/task.php"<?php if(basename($_SERVER['PHP_SELF'])=='task.php') echo ' class="active"';?>>Task</a></li>
            <li><a href="/chat.php"<?php if(basename($_SERVER['PHP_SELF'])=='chat.php') echo ' class="active"';?>>Chat</a></li>
            <li><a href="/info.php"<?php if(basename($_SERVER['PHP_SELF'])=='info.php') echo ' class="active"';?>>Info</a></li>
            <?php if ($ruolo_utente === 'admin' || $ruolo_utente === 'developer'): ?>
                <li><a href="/gestione_utenti.php"<?php if(basename($_SERVER['PHP_SELF'])=='gestione_utenti.php') echo ' class="active"';?>>Utenti</a></li>
            <?php endif; ?>
            <?php if ($ruolo_utente === 'developer'): ?>
                <li><a href="/devtools.php"<?php if(basename($_SERVER['PHP_SELF'])=='devtools.php') echo ' class="active"';?>>DevTools</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<?php include __DIR__ . '/chat_widget.php'; ?>
<?php if (basename($_SERVER['PHP_SELF']) !== 'chat.php'): ?>
<!-- Chat delle pratiche -->
<div class="container my-3" style="max-width:450px;">
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      Chat Pratica Cliente
    </div>
    <div class="card-body">
      <form id="praticaChatForm" autocomplete="off">
        <div class="mb-2">
          <select class="form-select" id="clienteSelect" required>
            <option value="" selected disabled>Seleziona cliente...</option>
            <?php
            // Popola i clienti dal database
            $clienti = $pdo->query("SELECT id, ragione_sociale FROM clienti ORDER BY ragione_sociale")->fetchAll();
            foreach ($clienti as $cli) {
                echo "<option value='{$cli['id']}'>" . htmlspecialchars($cli['ragione_sociale']) . "</option>";
            }
            ?>
          </select>
        </div>
        <div id="praticaChatBox" style="height:200px; overflow-y:auto; background:#f4f7fa; border:1px solid #e0e0e0; border-radius:5px; padding:8px; margin-bottom:8px;">
          <small class="text-muted">Seleziona un cliente per caricare la chat...</small>
        </div>
        <div class="input-group">
          <input type="text" id="praticaMsg" class="form-control" placeholder="Scrivi un messaggio..." autocomplete="off" disabled>
          <button class="btn btn-success" type="submit" id="sendPraticaBtn" disabled>Invia</button>
        </div>
      </form>
    </div>
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
<?php endif; ?>
<main style="padding: 20px;">