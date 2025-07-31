<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔍 Debug Creazione Utente Impiegato</h2>";

// 1. Verifica struttura tabella utenti
echo "<h3>1. Struttura tabella utenti:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE utenti");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Errore: " . $e->getMessage() . "</p>";
}

// 2. Verifica utenti esistenti
echo "<h3>2. Utenti esistenti:</h3>";
try {
    $stmt = $pdo->query("SELECT id, nome, email, ruolo FROM utenti ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Ruolo</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['nome']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['ruolo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Errore: " . $e->getMessage() . "</p>";
}

// 3. Test creazione utente impiegato
echo "<h3>3. Test creazione utente impiegato:</h3>";
try {
    $nome = "Test Impiegato";
    $email = "test.impiegato@example.com";
    $password = "TestPassword123!";
    $ruolo = "impiegato";
    $telegram_chat_id = null;
    
    // Prima elimina se esiste già
    $stmt = $pdo->prepare("DELETE FROM utenti WHERE email = ?");
    $stmt->execute([$email]);
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO utenti (nome, email, password, ruolo, telegram_chat_id) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$nome, $email, $hash, $ruolo, $telegram_chat_id]);
    
    if ($result) {
        $inserted_id = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Utente impiegato creato con successo! ID: $inserted_id</p>";
        
        // Verifica l'inserimento
        $stmt = $pdo->prepare("SELECT * FROM utenti WHERE id = ?");
        $stmt->execute([$inserted_id]);
        $new_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h4>Dati utente inserito:</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$new_user['id']}</li>";
        echo "<li><strong>Nome:</strong> {$new_user['nome']}</li>";
        echo "<li><strong>Email:</strong> {$new_user['email']}</li>";
        echo "<li><strong>Ruolo:</strong> {$new_user['ruolo']}</li>";
        echo "<li><strong>Telegram Chat ID:</strong> " . ($new_user['telegram_chat_id'] ?: 'NULL') . "</li>";
        echo "<li><strong>Password Hash:</strong> " . substr($new_user['password'], 0, 20) . "...</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Errore nella creazione dell'utente</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Errore: " . $e->getMessage() . "</p>";
}

// 4. Test login con l'utente creato
echo "<h3>4. Test verifica password:</h3>";
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, password, ruolo FROM utenti WHERE email = ?");
    $stmt->execute(['test.impiegato@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $password_check = password_verify('TestPassword123!', $user['password']);
        if ($password_check) {
            echo "<p style='color: green;'>✅ Password verificata correttamente</p>";
        } else {
            echo "<p style='color: red;'>❌ Password non corrispondente</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Utente non trovato</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Errore: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='create_user.php'>← Torna a Crea Utente</a></p>";
echo "<p><a href='gestione_utenti.php'>← Torna a Gestione Utenti</a></p>";
?>
