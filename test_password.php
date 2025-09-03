<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Test Password Verification</h2>";

// Test con il nostro utente test
$test_email = 'test@test.com';
$test_password = 'password';

$stmt = $pdo->prepare("SELECT id, nome, email, password FROM utenti WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<p><strong>Utente trovato:</strong></p>";
    echo "<ul>";
    echo "<li>ID: " . $user['id'] . "</li>";
    echo "<li>Nome: " . $user['nome'] . "</li>";
    echo "<li>Email: " . $user['email'] . "</li>";
    echo "<li>Password Hash: " . substr($user['password'], 0, 30) . "...</li>";
    echo "</ul>";
    
    $password_check = password_verify($test_password, $user['password']);
    echo "<p><strong>Password 'password' verifica:</strong> " . ($password_check ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    // Test con altre password comuni
    $common_passwords = ['admin', 'Admin123!', '123456', 'test'];
    echo "<h3>Test password comuni:</h3>";
    foreach ($common_passwords as $pwd) {
        $check = password_verify($pwd, $user['password']);
        echo "<p>Password '$pwd': " . ($check ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    }
} else {
    echo "<p>❌ Utente test non trovato</p>";
}

// Test anche con Roberto
echo "<hr><h3>Test con utente Roberto:</h3>";
$stmt = $pdo->prepare("SELECT id, nome, email, password FROM utenti WHERE email = 'roberto@crm.local'");
$stmt->execute();
$roberto = $stmt->fetch(PDO::FETCH_ASSOC);

if ($roberto) {
    echo "<p><strong>Roberto trovato:</strong></p>";
    echo "<ul>";
    echo "<li>ID: " . $roberto['id'] . "</li>";
    echo "<li>Nome: " . $roberto['nome'] . "</li>";
    echo "<li>Email: " . $roberto['email'] . "</li>";
    echo "</ul>";
    
    // Test password comuni per Roberto
    $common_passwords = ['admin', 'Admin123!', '123456', 'password', 'Roberto123', 'roberto'];
    foreach ($common_passwords as $pwd) {
        $check = password_verify($pwd, $roberto['password']);
        echo "<p>Password '$pwd': " . ($check ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    }
} else {
    echo "<p>❌ Roberto non trovato</p>";
}

echo "<hr><h3>Tutti gli utenti nel database:</h3>";
$stmt = $pdo->query("SELECT id, nome, email FROM utenti ORDER BY id");
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nome</th><th>Email</th></tr>";
foreach ($all_users as $u) {
    echo "<tr>";
    echo "<td>" . $u['id'] . "</td>";
    echo "<td>" . $u['nome'] . "</td>";
    echo "<td>" . $u['email'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
