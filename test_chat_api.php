<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

// Simula login per il test
if (!isset($_SESSION['user_id'])) {
    // Se non c'è sessione, mostra debug
    echo "<h2>Test Chat API - Nessuna Sessione</h2>";
    echo "<p>Sessione non presente. L'API dovrebbe restituire errore di autenticazione.</p>";
    
    // Testa l'API senza autenticazione
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/chat_notifications.php?action=unread_counts');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "<h3>Risposta API senza autenticazione:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
} else {
    // Se c'è sessione, testa l'API normalmente
    echo "<h2>Test Chat API - Con Sessione</h2>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>User Name: " . $_SESSION['user_name'] . "</p>";
    
    // Includi l'API direttamente
    $_GET['action'] = 'unread_counts';
    ob_start();
    include 'api/chat_notifications.php';
    $response = ob_get_clean();
    
    echo "<h3>Risposta API con autenticazione:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo '<p><a href="dashboard.php">← Torna alla Dashboard</a></p>';
?>
