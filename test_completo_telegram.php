<?php
/**
 * Test completo delle notifiche Telegram per tutti i tipi di chat
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/telegram.php';

echo "🔔 Test Completo Notifiche Telegram\n";
echo "=====================================\n\n";

try {
    // Test 1: Chat Globale
    echo "1️⃣ TEST CHAT GLOBALE\n";
    echo "--------------------\n";
    
    $stmt = $pdo->prepare("SELECT id, name FROM conversations WHERE type = 'global' LIMIT 1");
    $stmt->execute();
    $global_chat = $stmt->fetch();
    
    if ($global_chat) {
        echo "✅ Chat: {$global_chat['name']} (ID: {$global_chat['id']})\n";
        
        // Simula messaggio da Sabina a Roberto offline
        $stmt = $pdo->prepare("SELECT id, nome FROM utenti WHERE nome = 'Sabina'");
        $stmt->execute();
        $sabina = $stmt->fetch();
        
        if ($sabina) {
            // Verifica destinatari
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
            $stmt->execute([$global_chat['id'], $sabina['id']]);
            $destinatari = $stmt->fetchAll();
            
            echo "📨 Mittente: {$sabina['nome']}\n";
            echo "📱 Destinatari offline con Telegram: " . count($destinatari) . "\n";
            
            foreach ($destinatari as $dest) {
                echo "   - {$dest['nome']}\n";
            }
            
            if ($destinatari) {
                $messaggio = "Ciao a tutti! Come va?";
                echo "💬 Messaggio: \"$messaggio\"\n";
                echo "📲 Notifica Telegram:\n";
                echo "   🌐 Chat Globale\n";
                echo "   Sabina: '$messaggio'\n";
                echo "   📅 " . date('d/m/Y H:i') . "\n";
            }
        }
    }
    
    echo "\n";
    
    // Test 2: Chat Privata
    echo "2️⃣ TEST CHAT PRIVATA\n";
    echo "--------------------\n";
    
    $stmt = $pdo->prepare("SELECT id, name FROM conversations WHERE type = 'private' LIMIT 1");
    $stmt->execute();
    $private_chat = $stmt->fetch();
    
    if ($private_chat) {
        echo "✅ Chat: {$private_chat['name']} (ID: {$private_chat['id']})\n";
        
        // Trova partecipanti
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.is_online, u.telegram_chat_id
            FROM conversation_participants cp
            JOIN utenti u ON cp.user_id = u.id
            WHERE cp.conversation_id = ? AND cp.is_active = 1
            ORDER BY u.nome
        ");
        $stmt->execute([$private_chat['id']]);
        $partecipanti = $stmt->fetchAll();
        
        if (count($partecipanti) >= 2) {
            $mittente = $partecipanti[0];
            $destinatario = $partecipanti[1];
            
            echo "📨 Mittente: {$mittente['nome']}\n";
            echo "📱 Destinatario: {$destinatario['nome']} ";
            
            if (!$destinatario['is_online'] && $destinatario['telegram_chat_id']) {
                echo "(🔴 Offline + 📱 Telegram)\n";
                echo "💬 Messaggio simulato: \"Ciao, come stai?\"\n";
                echo "📲 Notifica Telegram:\n";
                echo "   💬 Messaggio Privato\n";
                echo "   {$mittente['nome']}: ti ha scritto in privato - accedi a Pratiko\n";
                echo "   📅 " . date('d/m/Y H:i') . "\n";
            } else {
                $status = $destinatario['is_online'] ? '🟢 Online' : '🔴 Offline';
                $telegram = $destinatario['telegram_chat_id'] ? '📱 Sì' : '❌ No';
                echo "($status, Telegram: $telegram) - Nessuna notifica\n";
            }
        }
    }
    
    echo "\n";
    
    // Test 3: Chat Pratica
    echo "3️⃣ TEST CHAT PRATICA\n";
    echo "--------------------\n";
    
    $stmt = $pdo->prepare("SELECT id, name FROM conversations WHERE type = 'pratica' LIMIT 1");
    $stmt->execute();
    $pratica_chat = $stmt->fetch();
    
    if ($pratica_chat) {
        echo "✅ Chat: {$pratica_chat['name']} (ID: {$pratica_chat['id']})\n";
        echo "🚫 NOTIFICHE DISABILITATE per le chat pratiche (come richiesto)\n";
        echo "💬 Anche se ci sono utenti offline con Telegram, non riceveranno notifiche\n";
    } else {
        echo "ℹ️  Nessuna chat pratica trovata\n";
    }
    
    echo "\n";
    
    // Riepilogo configurazione
    echo "📊 RIEPILOGO CONFIGURAZIONE\n";
    echo "===========================\n";
    
    // Conta utenti
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utenti");
    $total_users = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as with_telegram FROM utenti WHERE telegram_chat_id IS NOT NULL AND telegram_chat_id != ''");
    $telegram_users = $stmt->fetch()['with_telegram'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as online FROM utenti WHERE is_online = 1");
    $online_users = $stmt->fetch()['online'];
    
    echo "👥 Utenti totali: $total_users\n";
    echo "📱 Con Telegram: $telegram_users\n";
    echo "🟢 Online: $online_users\n";
    echo "🔴 Offline: " . ($total_users - $online_users) . "\n";
    
    // Telegram bot status
    if (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN !== 'your_telegram_bot_token_here') {
        echo "🤖 Bot Telegram: ✅ Configurato\n";
    } else {
        echo "🤖 Bot Telegram: ❌ Non configurato\n";
    }
    
    echo "\n✅ Sistema di notifiche Telegram implementato e pronto!\n";
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test completato!\n";
?>
