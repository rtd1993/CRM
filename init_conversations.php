<?php
require_once __DIR__ . '/includes/config.php';

echo "Inizializzazione conversazioni base...\n";

try {
    // Assicurati che esista la conversazione globale (ID 1)
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO conversations (id, name, type, created_by, created_at, updated_at) 
        VALUES (1, 'Chat Globale', 'global', 1, NOW(), NOW())
    ");
    $stmt->execute();
    echo "✅ Conversazione globale (ID 1) creata/verificata\n";
    
    // Crea conversazioni pratiche per i primi 10 clienti (ID 1001-1010)
    $stmt = $pdo->prepare("SELECT id, Cognome_Ragione_sociale FROM clienti LIMIT 10");
    $stmt->execute();
    $clienti = $stmt->fetchAll();
    
    foreach ($clienti as $cliente) {
        $conv_id = 1000 + $cliente['id'];
        $name = "Pratica - " . $cliente['Cognome_Ragione_sociale'];
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO conversations (id, name, type, created_by, client_id, created_at, updated_at) 
            VALUES (?, ?, 'pratica', 1, ?, NOW(), NOW())
        ");
        $stmt->execute([$conv_id, $name, $cliente['id']]);
        
        // Aggiungi tutti gli utenti come partecipanti
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO conversation_participants (conversation_id, user_id, is_active, joined_at)
            SELECT ?, id, 1, NOW() FROM utenti WHERE ruolo IN ('admin', 'user')
        ");
        $stmt->execute([$conv_id]);
        
        echo "✅ Conversazione pratica {$conv_id} per cliente '{$cliente['Cognome_Ragione_sociale']}' creata\n";
    }
    
    // Crea conversazioni private per combinazioni utenti (ID 2000+)
    $stmt = $pdo->prepare("SELECT id, nome FROM utenti ORDER BY id LIMIT 5");
    $stmt->execute();
    $utenti = $stmt->fetchAll();
    
    for ($i = 0; $i < count($utenti); $i++) {
        for ($j = $i + 1; $j < count($utenti); $j++) {
            $user1 = $utenti[$i];
            $user2 = $utenti[$j];
            
            $userId1 = min($user1['id'], $user2['id']);
            $userId2 = max($user1['id'], $user2['id']);
            $conv_id = 2000 + $userId1 * 100 + $userId2;
            
            $name = "Chat: {$user1['nome']} ↔ {$user2['nome']}";
            
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO conversations (id, name, type, created_by, created_at, updated_at) 
                VALUES (?, ?, 'private', ?, NOW(), NOW())
            ");
            $stmt->execute([$conv_id, $name, $user1['id']]);
            
            // Aggiungi entrambi gli utenti come partecipanti
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO conversation_participants (conversation_id, user_id, is_active, joined_at)
                VALUES (?, ?, 1, NOW()), (?, ?, 1, NOW())
            ");
            $stmt->execute([$conv_id, $user1['id'], $conv_id, $user2['id']]);
            
            echo "✅ Conversazione privata {$conv_id} tra '{$user1['nome']}' e '{$user2['nome']}' creata\n";
        }
    }
    
    echo "\n🎉 Inizializzazione completata!\n";
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}
?>
