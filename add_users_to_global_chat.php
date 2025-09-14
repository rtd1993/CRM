<?php
// Script per aggiungere automaticamente tutti gli utenti alla chat globale
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ID della chat globale
    $global_chat_id = 1;
    
    // Trova tutti gli utenti che NON sono nella chat globale
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome 
        FROM utenti u 
        WHERE u.id NOT IN (
            SELECT cp.user_id 
            FROM conversation_participants cp 
            WHERE cp.conversation_id = ? AND cp.is_active = 1
        )
    ");
    $stmt->execute([$global_chat_id]);
    $users_missing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Utenti da aggiungere alla chat globale: " . count($users_missing) . "\n";
    
    // Aggiungi ogni utente mancante alla chat globale
    $insert_stmt = $pdo->prepare("
        INSERT INTO conversation_participants (conversation_id, user_id, role, is_active) 
        VALUES (?, ?, 'member', 1)
    ");
    
    foreach ($users_missing as $user) {
        $insert_stmt->execute([$global_chat_id, $user['id']]);
        echo "✅ Aggiunto utente: {$user['nome']} (ID: {$user['id']})\n";
    }
    
    echo "✅ Tutti gli utenti sono ora nella chat globale!\n";
    
} catch(Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}
?>
