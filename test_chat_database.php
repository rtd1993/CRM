<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/db.php';

echo "<h3>🔍 Diagnostica Database Chat</h3>\n";

try {
    // Controlla tabelle chat esistenti
    echo "<h4>📋 Tabelle Chat Esistenti:</h4>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE '%chat%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Nessuna tabella chat trovata!</p>\n";
    } else {
        echo "<ul>\n";
        foreach ($tables as $table) {
            echo "<li>✅ $table</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Controlla struttura chat_messaggi se esiste
    if (in_array('chat_messaggi', $tables)) {
        echo "<h4>🏗️ Struttura chat_messaggi:</h4>\n";
        $stmt = $pdo->query("DESCRIBE chat_messaggi");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        foreach ($columns as $col) {
            echo $col['Field'] . " | " . $col['Type'] . " | " . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
        echo "</pre>";
        
        // Conta messaggi
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM chat_messaggi");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 Messaggi totali: <strong>$count</strong></p>\n";
        
        // Ultimi 3 messaggi
        if ($count > 0) {
            echo "<h4>💬 Ultimi 3 messaggi:</h4>\n";
            $stmt = $pdo->query("
                SELECT c.id, c.messaggio, c.timestamp, u.nome 
                FROM chat_messaggi c 
                JOIN utenti u ON c.utente_id = u.id 
                ORDER BY c.timestamp DESC 
                LIMIT 3
            ");
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>";
            foreach ($messages as $msg) {
                echo "[{$msg['timestamp']}] {$msg['nome']}: {$msg['messaggio']}\n";
            }
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Tabella chat_messaggi non trovata!</p>\n";
        echo "<h4>🔧 Creazione tabella chat_messaggi:</h4>\n";
        
        $create_sql = "
            CREATE TABLE chat_messaggi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                utente_id INT NOT NULL,
                messaggio TEXT NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
                INDEX idx_timestamp (timestamp),
                INDEX idx_utente (utente_id)
            )
        ";
        
        $pdo->exec($create_sql);
        echo "<p style='color: green;'>✅ Tabella chat_messaggi creata con successo!</p>\n";
    }
    
    // Controlla utenti
    echo "<h4>👥 Utenti Sistema:</h4>\n";
    $stmt = $pdo->query("SELECT id, nome, email FROM utenti ORDER BY nome");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>\n";
    foreach ($users as $user) {
        echo "<li>ID: {$user['id']} - {$user['nome']} ({$user['email']})</li>\n";
    }
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
