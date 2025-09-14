<?php
/**
 * Test semplice della funzione di notifica Telegram (senza autenticazione)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/telegram.php';

echo "🔔 Test Notifica Telegram (Debug)\n\n";

try {
    // Trova la chat globale
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE type = 'global' LIMIT 1");
    $stmt->execute();
    $global_chat = $stmt->fetch();
    
    if ($global_chat) {
        echo "✅ Chat globale trovata (ID: {$global_chat['id']})\n";
        
        // Trova Roberto
        $stmt = $pdo->prepare("SELECT id FROM utenti WHERE nome = 'Roberto'");
        $stmt->execute();
        $roberto = $stmt->fetch();
        
        // Trova un altro utente
        $stmt = $pdo->prepare("SELECT id, nome FROM utenti WHERE nome != 'Roberto' LIMIT 1");
        $stmt->execute();
        $altro_utente = $stmt->fetch();
        
        if ($altro_utente && $roberto) {
            echo "✅ Utente mittente trovato: {$altro_utente['nome']} (ID: {$altro_utente['id']})\n";
            echo "✅ Roberto trovato (ID: {$roberto['id']})\n";
            
            // Verifica partecipanti della chat globale
            $stmt = $pdo->prepare("
                SELECT u.nome, u.telegram_chat_id, u.is_online 
                FROM conversation_participants cp
                JOIN utenti u ON cp.user_id = u.id
                WHERE cp.conversation_id = ? AND cp.is_active = 1
            ");
            $stmt->execute([$global_chat['id']]);
            $partecipanti = $stmt->fetchAll();
            
            echo "\n👥 Partecipanti chat globale:\n";
            foreach ($partecipanti as $p) {
                $status = $p['is_online'] ? '🟢 Online' : '🔴 Offline';
                $telegram = $p['telegram_chat_id'] ? '📱 Telegram: ✅' : '📱 Telegram: ❌';
                echo "   - {$p['nome']} - $status - $telegram\n";
            }
            
            // Test simulato
            echo "\n📨 Test simulazione notifica...\n";
            
            // Cerca solo utenti offline con Telegram che non sono il mittente
            $stmt = $pdo->prepare("
                SELECT u.nome, u.telegram_chat_id 
                FROM conversation_participants cp
                JOIN utenti u ON cp.user_id = u.id
                WHERE cp.conversation_id = ? 
                  AND cp.user_id != ? 
                  AND cp.is_active = 1
                  AND u.is_online = 0
                  AND u.telegram_chat_id IS NOT NULL 
                  AND u.telegram_chat_id != ''
            ");
            $stmt->execute([$global_chat['id'], $altro_utente['id']]);
            $destinatari = $stmt->fetchAll();
            
            echo "📋 Destinatari notifica:\n";
            if ($destinatari) {
                foreach ($destinatari as $dest) {
                    echo "   - {$dest['nome']} (Chat ID: {$dest['telegram_chat_id']})\n";
                }
                
                // Simula il messaggio che verrebbe inviato
                $messaggio_test = "Test messaggio di prova";
                $testo_notifica = "🌐 <b>Chat Globale</b>\n\n";
                $testo_notifica .= "<b>" . htmlspecialchars($altro_utente['nome']) . ":</b> '" . htmlspecialchars($messaggio_test) . "'\n\n";
                $testo_notifica .= "📅 " . date('d/m/Y H:i');
                
                echo "\n📱 Messaggio Telegram che verrebbe inviato:\n";
                echo "---\n";
                echo strip_tags(str_replace('<b>', '', str_replace('</b>', '', $testo_notifica))) . "\n";
                echo "---\n";
                
            } else {
                echo "   ❌ Nessun destinatario (utenti offline con Telegram)\n";
            }
            
        } else {
            echo "❌ Utenti non trovati\n";
        }
        
    } else {
        echo "❌ Nessuna chat globale trovata\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test completato!\n";
?>
