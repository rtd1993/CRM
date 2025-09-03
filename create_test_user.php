<?php
require_once __DIR__ . '/includes/db.php';

$email = 'test@test.com';
$password = 'test123';
$nome = 'Test User';
$ruolo = 'admin';

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Creazione Utente Test</h2>";
echo "<p><strong>Email:</strong> $email</p>";
echo "<p><strong>Password:</strong> $password</p>";
echo "<p><strong>Hash generato:</strong> $hash</p>";

try {
    $stmt = $pdo->prepare("INSERT INTO utenti (nome, email, password, ruolo) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$nome, $email, $hash, $ruolo]);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Utente creato con successo!</p>";
        
        // Verifica immediata
        $stmt = $pdo->prepare("SELECT id, nome, email FROM utenti WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>✅ Verifica: Utente trovato nel database</p>";
            echo "<ul>";
            echo "<li>ID: " . $user['id'] . "</li>";
            echo "<li>Nome: " . $user['nome'] . "</li>";
            echo "<li>Email: " . $user['email'] . "</li>";
            echo "</ul>";
            
            // Test password
            $stmt = $pdo->prepare("SELECT password FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            $stored_hash = $stmt->fetchColumn();
            
            $verify_result = password_verify($password, $stored_hash);
            echo "<p><strong>Test password:</strong> " . ($verify_result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Errore: Utente non trovato dopo la creazione</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Errore durante la creazione dell'utente</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Istruzioni per il test:</h3>";
echo "<ol>";
echo "<li>Vai alla pagina di login</li>";
echo "<li>Inserisci email: <strong>$email</strong></li>";
echo "<li>Inserisci password: <strong>$password</strong></li>";
echo "<li>Clicca 'Accedi'</li>";
echo "</ol>";
?>
