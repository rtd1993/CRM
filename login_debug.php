<?php
// Login debug - include files uno per uno
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Login Debug Step by Step</h1>";

echo "<p>1. Avvio script... ✅</p>";

try {
    echo "<p>2. Includendo db.php...</p>";
    require_once __DIR__ . '/includes/db.php';
    echo "<p>✅ db.php caricato</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore db.php: " . $e->getMessage() . "</p>";
    exit;
}

try {
    echo "<p>3. Includendo config.php...</p>";
    require_once __DIR__ . '/includes/config.php';
    echo "<p>✅ config.php caricato</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore config.php: " . $e->getMessage() . "</p>";
    exit;
}

try {
    echo "<p>4. Includendo tunnel_bypass.php...</p>";
    require_once __DIR__ . '/includes/tunnel_bypass.php';
    echo "<p>✅ tunnel_bypass.php caricato</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore tunnel_bypass.php: " . $e->getMessage() . "</p>";
    exit;
}

try {
    echo "<p>5. Avviando sessione...</p>";
    session_start();
    echo "<p>✅ Sessione avviata</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore sessione: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h3>Test POST:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: green; color: white; padding: 10px;'>";
    echo "✅ POST ricevuto!<br>";
    echo "Email: " . htmlspecialchars($_POST['email'] ?? 'NESSUNA') . "<br>";
    echo "Password: " . (!empty($_POST['password']) ? 'Presente' : 'Assente') . "<br>";
    echo "</div>";
    
    // Test login semplice
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, nome, email, password, ruolo FROM utenti WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['role'] = $user['ruolo'];
        
        echo "<p style='background: green; color: white; padding: 10px;'>✅ LOGIN SUCCESS! Redirecting...</p>";
        echo "<script>setTimeout(() => window.location.href = 'dashboard.php', 2000);</script>";
        exit();
    } else {
        echo "<p style='background: red; color: white; padding: 10px;'>❌ Credenziali non valide</p>";
    }
}
?>

<form method="post" action="">
    <p>Email: <input type="email" name="email" required></p>
    <p>Password: <input type="password" name="password" required></p>
    <p><button type="submit">Login Debug</button></p>
</form>

<p><a href="login.php">Torna al Login originale</a></p>
