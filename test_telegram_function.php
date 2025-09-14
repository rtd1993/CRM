<?php
/**
 * Test rapido della funzione di notifica Telegram
 */

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/telegram.php';

// Solo admin e developer possono accedere
if (!in_array($_SESSION['user_role'], ['admin', 'developer'])) {
    die("Accesso non autorizzato.");
}

echo "🔔 Test Notifica Telegram\n\n";

// Test delle funzioni
try {
    // Trova la chat globale
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE type = 'global' LIMIT 1");
    $stmt->execute();
    $global_chat = $stmt->fetch();
    
    if ($global_chat) {
        echo "✅ Chat globale trovata (ID: {$global_chat['id']})\n";
        
        // Simula un messaggio da un utente diverso da Roberto
        $stmt = $pdo->prepare("SELECT id FROM utenti WHERE nome != 'Roberto' LIMIT 1");
        $stmt->execute();
        $altro_utente = $stmt->fetch();
        
        if ($altro_utente) {
            echo "✅ Utente mittente trovato (ID: {$altro_utente['id']})\n";
            
            // Test della funzione (modalità simulata)
            echo "\n📨 Simulando invio notifica...\n";
            
            // Impostazione temporanea per evitare l'invio reale
            $GLOBALS['TEST_MODE'] = true;
            
            $result = inviaNotificaTelegramChat($global_chat['id'], $altro_utente['id'], "Test messaggio di prova");
            
            if ($result) {
                echo "✅ Funzione eseguita correttamente!\n";
                echo "📱 Roberto dovrebbe ricevere: \"[Nome Utente]: 'Test messaggio di prova'\"\n";
            } else {
                echo "❌ Errore nell'esecuzione della funzione\n";
            }
            
        } else {
            echo "❌ Nessun altro utente trovato per il test\n";
        }
        
    } else {
        echo "❌ Nessuna chat globale trovata\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test completato!\n";
echo "Per testare realmente, invia un messaggio nella chat quando Roberto è offline.\n";
?>
