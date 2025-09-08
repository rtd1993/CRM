<?php
// Test minimale login
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test Login Minimale</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: green; color: white; padding: 10px;'>";
    echo "✅ POST ricevuto!<br>";
    echo "Email: " . htmlspecialchars($_POST['email'] ?? 'NESSUNA') . "<br>";
    echo "Password: " . (!empty($_POST['password']) ? 'Presente' : 'Assente') . "<br>";
    echo "</div>";
    
    // Test connessione database
    try {
        require_once __DIR__ . '/includes/db.php';
        echo "<p>✅ Database connesso</p>";
        
        $email = $_POST['email'] ?? '';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utenti WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();
        echo "<p>Utenti trovati con questa email: $count</p>";
        
    } catch (Exception $e) {
        echo "<p style='background: red; color: white; padding: 10px;'>❌ Errore database: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Nessun POST ricevuto ancora</p>";
}
?>

<form method="post" action="">
    <p>Email: <input type="email" name="email" required></p>
    <p>Password: <input type="password" name="password" required></p>
    <p><button type="submit">Test Login</button></p>
</form>

<p><a href="login.php">Torna al Login originale</a></p>
