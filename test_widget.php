<?php
session_start();
// Simula utente autenticato per testare i widget
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Widget Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .test-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Widget Chat</h1>
        
        <div class="test-info">
            <h5>Informazioni Test:</h5>
            <ul>
                <li>✅ Sessione utente simulata (ID: <?= $_SESSION['user_id'] ?>)</li>
                <li>✅ Socket.IO: <?= getSocketIOUrl() ?></li>
                <li>✅ Database connesso</li>
            </ul>
        </div>
        
        <div class="alert alert-info">
            <strong>Controlla:</strong>
            <ul class="mb-0">
                <li>I widget dovrebbero apparire come sfere in basso a destra</li>
                <li>Cliccando si dovrebbero aprire</li>
                <li>La console del browser per eventuali errori JavaScript</li>
            </ul>
        </div>
        
        <h3>Contenuto della pagina</h3>
        <p>Questo è contenuto di test. I widget dovrebbero essere visibili in basso a destra.</p>
        
        <div style="height: 800px;">
            <p>Spazio per scrolling...</p>
        </div>
    </div>

    <!-- Include i widget -->
    <div id="crm-chat-container">
        <?php include __DIR__ . '/includes/chat_pratiche_widget.php'; ?>
        <?php include __DIR__ . '/includes/chat_widget.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= getSocketIOUrl() ?>/socket.io/socket.io.js"></script>
</body>
</html>
