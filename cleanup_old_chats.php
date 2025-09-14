<?php
// Script pulizia chat più vecchie di un anno
// Eseguito il primo giorno di ogni mese alle 03:00

$log_file = '/var/www/CRM/logs/chat_cleanup_monthly.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

try {
    log_message("=== INIZIO PULIZIA CHAT MENSILE ===");
    
    // Connessione database
    $pdo = new PDO("mysql:host=localhost;dbname=crm;charset=utf8", 'crmuser', 'Admin123!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Data limite: 1 anno fa
    $data_limite = date('Y-m-d H:i:s', strtotime('-1 year'));
    log_message("Data limite per eliminazione: $data_limite");
    
    // Conta messaggi da eliminare
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE created_at < ?");
    $stmt->execute([$data_limite]);
    $messaggi_da_eliminare = $stmt->fetchColumn();
    
    log_message("Messaggi da eliminare: $messaggi_da_eliminare");
    
    if ($messaggi_da_eliminare > 0) {
        // Elimina messaggi più vecchi di un anno
        $stmt = $pdo->prepare("DELETE FROM messages WHERE created_at < ?");
        $stmt->execute([$data_limite]);
        
        log_message("✅ Eliminati $messaggi_da_eliminare messaggi più vecchi di un anno");
    } else {
        log_message("✅ Nessun messaggio da eliminare");
    }
    
    // Pulizia conversazioni vuote (senza messaggi)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM conversations c 
        WHERE NOT EXISTS (
            SELECT 1 FROM messages m WHERE m.conversation_id = c.id
        ) AND c.type != 'global'
    ");
    $stmt->execute();
    $conversazioni_vuote = $stmt->fetchColumn();
    
    if ($conversazioni_vuote > 0) {
        // Elimina prima i partecipanti delle conversazioni vuote
        $pdo->prepare("
            DELETE cp FROM conversation_participants cp
            JOIN conversations c ON cp.conversation_id = c.id
            WHERE NOT EXISTS (
                SELECT 1 FROM messages m WHERE m.conversation_id = c.id
            ) AND c.type != 'global'
        ")->execute();
        
        // Elimina conversazioni vuote (tranne quella globale)
        $pdo->prepare("
            DELETE FROM conversations 
            WHERE NOT EXISTS (
                SELECT 1 FROM messages m WHERE m.conversation_id = conversations.id
            ) AND type != 'global'
        ")->execute();
        
        log_message("✅ Eliminate $conversazioni_vuote conversazioni vuote");
    } else {
        log_message("✅ Nessuna conversazione vuota da eliminare");
    }
    
    // Ottimizza tabelle dopo la pulizia
    $tabelle = ['messages', 'conversations', 'conversation_participants'];
    foreach ($tabelle as $tabella) {
        $pdo->exec("OPTIMIZE TABLE $tabella");
        log_message("🔧 Ottimizzata tabella: $tabella");
    }
    
    log_message("✅ PULIZIA CHAT COMPLETATA CON SUCCESSO");
    
} catch (Exception $e) {
    log_message("❌ ERRORE: " . $e->getMessage());
    exit(1);
}
?>
