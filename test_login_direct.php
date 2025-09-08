<?php
// Test diretto login senza form
session_start();

echo "<h2>Test Login Diretto</h2>";

// Simuliamo esattamente quello che fa il login
require_once __DIR__ . '/includes/db.php';

// Ottieni un utente reale dal database
$stmt = $pdo->prepare("SELECT id, nome, email, password, ruolo FROM utenti LIMIT 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<p>Utente trovato: " . htmlspecialchars($user['nome']) . " (ID: " . $user['id'] . ")</p>";
    
    // Setta esattamente come nel login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nome'];
    $_SESSION['role'] = $user['ruolo'];
    
    echo "<div style='background: green; color: white; padding: 10px;'>";
    echo "✅ Sessione impostata!<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User Name: " . $_SESSION['user_name'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "</div>";
    
    echo "<p><a href='dashboard-test.php'>Vai alla Dashboard Test</a></p>";
    echo "<p><a href='test_session.php'>Torna al Test Sessioni</a></p>";
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px;'>";
    echo "<h3>Session Data completa:</h3>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    echo "</div>";
} else {
    echo "<p style='color: red;'>Nessun utente trovato nel database!</p>";
}
?>
