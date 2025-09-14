<?php
// Test semplificato API notifiche
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

try {
    $user_id = 2; // Test con Roberto
    
    // Test conteggi messaggi non letti
    echo "=== Test Conteggi Messaggi Non Letti ===\n";
    
    // Chat globale (conversation_id = 1)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM messages m 
        WHERE m.conversation_id = 1 
        AND m.user_id != ? 
        AND m.created_at > COALESCE(
            (SELECT last_seen FROM user_conversation_status 
             WHERE user_id = ? AND conversation_id = 1), 
            '1970-01-01'
        )
    ");
    $stmt->execute([$user_id, $user_id]);
    $globalCount = $stmt->fetchColumn();
    
    echo "Messaggi non letti chat globale per utente $user_id: $globalCount\n";
    
    // Test messaggi totali
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE conversation_id = 1");
    $stmt->execute();
    $totalMessages = $stmt->fetchColumn();
    echo "Messaggi totali chat globale: $totalMessages\n";
    
    // Test utenti chat globale
    $stmt = $pdo->prepare("SELECT COUNT(*) as participants FROM conversation_participants WHERE conversation_id = 1");
    $stmt->execute();
    $participants = $stmt->fetchColumn();
    echo "Partecipanti chat globale: $participants\n";
    
    // Test ultimi messaggi
    $stmt = $pdo->prepare("
        SELECT m.id, m.message, m.user_id, m.created_at, u.nome
        FROM messages m
        JOIN utenti u ON m.user_id = u.id
        WHERE m.conversation_id = 1
        ORDER BY m.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Ultimi 5 messaggi:\n";
    foreach ($messages as $msg) {
        echo "- [{$msg['created_at']}] {$msg['nome']}: {$msg['message']}\n";
    }
    
} catch (Exception $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
}
