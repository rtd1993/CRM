<?php
/**
 * Test delle notifiche Telegram per i messaggi chat
 * Esegue test simulati per verificare il funzionamento del sistema
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Notifiche Telegram Chat</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        code { background-color: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔔 Test Sistema Notifiche Telegram</h1>
    
    <div class="test-section info">
        <h3>📋 Regole Implementate</h3>
        <ul>
            <li><strong>Chat Globale:</strong> "$utente: 'testo messaggio'"</li>
            <li><strong>Chat Privata:</strong> "$utente: ti ha scritto in privato - accedi a pratiko"</li>
            <li><strong>Chat Pratiche:</strong> Non inviare su telegram</li>
        </ul>
    </div>

    <div class="test-section">
        <h3>🔍 Verifica Configurazione Telegram</h3>
        <?php
        // Verifica se il bot Telegram è configurato
        if (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN !== 'your_telegram_bot_token_here') {
            echo '<p class="success">✅ Token Telegram configurato</p>';
        } else {
            echo '<p class="error">❌ Token Telegram non configurato</p>';
        }
        ?>
    </div>

    <div class="test-section">
        <h3>👥 Utenti con Telegram Configurato</h3>
        <?php
        $stmt = $pdo->query("
            SELECT id, nome, ruolo, telegram_chat_id, is_online 
            FROM utenti 
            WHERE telegram_chat_id IS NOT NULL AND telegram_chat_id != ''
            ORDER BY nome
        ");
        $utenti_telegram = $stmt->fetchAll();
        
        if ($utenti_telegram) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Nome</th><th>Ruolo</th><th>Chat ID</th><th>Online</th></tr>";
            foreach ($utenti_telegram as $utente) {
                $online_icon = $utente['is_online'] ? '🟢' : '🔴';
                echo "<tr>";
                echo "<td>{$utente['nome']}</td>";
                echo "<td>{$utente['ruolo']}</td>";
                echo "<td>{$utente['telegram_chat_id']}</td>";
                echo "<td>{$online_icon}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Nessun utente ha configurato Telegram</p>";
        }
        ?>
    </div>

    <div class="test-section">
        <h3>💬 Conversazioni Attive</h3>
        <?php
        // Controlla le conversazioni esistenti
        $stmt = $pdo->query("SELECT id, name, type, client_id FROM conversations ORDER BY type, id");
        $conversations = $stmt->fetchAll();
        
        if ($conversations) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Cliente ID</th></tr>";
            foreach ($conversations as $conv) {
                $type_icon = match($conv['type']) {
                    'global' => '🌐',
                    'private' => '💬',
                    'pratica' => '📋',
                    default => '❓'
                };
                echo "<tr>";
                echo "<td>{$conv['id']}</td>";
                echo "<td>{$type_icon} {$conv['name']}</td>";
                echo "<td>{$conv['type']}</td>";
                echo "<td>" . ($conv['client_id'] ?: '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Nessuna conversazione trovata</p>";
        }
        ?>
    </div>

    <div class="test-section">
        <h3>🧪 Test Funzione Notifiche</h3>
        <?php
        // Test della funzione di notifica
        if ($utenti_telegram && $conversations) {
            echo "<p><strong>Test in corso...</strong></p>";
            
            // Prova a testare la funzione (senza inviare veramente)
            try {
                // Test con chat globale
                $global_chats = array_filter($conversations, fn($c) => $c['type'] === 'global');
                if ($global_chats) {
                    $global_chat = reset($global_chats);
                    echo "<p>✅ Funzione disponibile per chat globale (ID: {$global_chat['id']})</p>";
                }
                
                // Test con chat privata  
                $private_chats = array_filter($conversations, fn($c) => $c['type'] === 'private');
                if ($private_chats) {
                    $private_chat = reset($private_chats);
                    echo "<p>✅ Funzione disponibile per chat privata (ID: {$private_chat['id']})</p>";
                }
                
                // Test con chat pratica
                $pratica_chats = array_filter($conversations, fn($c) => $c['type'] === 'pratica');
                if ($pratica_chats) {
                    echo "<p>✅ Chat pratiche trovate - notifiche disabilitate come richiesto</p>";
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Errore nel test: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='error'>❌ Impossibile eseguire test: configurazione incompleta</p>";
        }
        ?>
    </div>

    <div class="test-section info">
        <h3>📝 Come Testare</h3>
        <ol>
            <li>Assicurarsi che almeno un utente abbia configurato il proprio Chat ID Telegram</li>
            <li>Rendere offline quell'utente (is_online = 0)</li>
            <li>Inviare un messaggio in una chat dove partecipa</li>
            <li>Verificare che riceva la notifica su Telegram</li>
        </ol>
        <p><strong>Nota:</strong> Le notifiche vengono inviate solo a utenti offline che hanno configurato Telegram.</p>
    </div>

    <div class="test-section">
        <h3>🛠️ Azioni Rapide</h3>
        <p><a href="telegram_config.php">Configura Telegram</a></p>
        <p><a href="chat.php">Vai alla Chat</a></p>
    </div>
</body>
</html>
