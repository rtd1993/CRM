<?php
// Dashboard semplificato per test 524 timeout
session_start();

// Controllo sessione semplice
if (!isset($_SESSION['user_id'])) {
    echo "Errore: Sessione non valida. <a href='login.php'>Torna al login</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Test - CRM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: green;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        .nav-link {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav-link:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅ Login e Dashboard Test - SUCCESSO!</div>
        
        <div class="info">
            <h3>Test di accesso completato</h3>
            <p><strong>Utente:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'N/A') ?></p>
            <p><strong>Ruolo:</strong> <?= htmlspecialchars($_SESSION['role'] ?? 'N/A') ?></p>
            <p><strong>Sessione ID:</strong> <?= htmlspecialchars($_SESSION['user_id'] ?? 'N/A') ?></p>
            <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <div style="margin-top: 30px;">
            <h3>Navigazione:</h3>
            <a href="dashboard.php" class="nav-link">Dashboard Completa</a>
            <a href="clienti.php" class="nav-link">Clienti</a>
            <a href="chat.php" class="nav-link">Chat</a>
            <a href="logout.php" class="nav-link" style="background: #dc3545;">Logout</a>
        </div>

        <div style="margin-top: 30px; font-size: 12px; color: #666;">
            <p>Questo è un dashboard semplificato per testare il problema del timeout 524.</p>
            <p>Se questa pagina si carica velocemente, il problema è nella dashboard completa.</p>
        </div>
    </div>
</body>
</html>
