<?php
// Dashboard semplificato per test 524 timeout
session_start();

// Debug della sessione
echo "<div style='background: yellow; padding: 10px; margin: 10px;'>";
echo "<h3>DEBUG SESSIONE:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";
echo "Cookies: <pre>" . print_r($_COOKIE, true) . "</pre>";
echo "</div>";

// Controllo sessione semplice
if (!isset($_SESSION['user_id'])) {
    echo "<div style='background: red; color: white; padding: 10px;'>";
    echo "Errore: Sessione non valida. user_id non trovato in sessione.";
    echo "<br><a href='login.php' style='color: white;'>Torna al login</a>";
    echo "</div>";
    exit;
} else {
    echo "<div style='background: green; color: white; padding: 10px;'>";
    echo "✅ Sessione valida! User ID: " . $_SESSION['user_id'];
    echo "</div>";
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
