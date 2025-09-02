<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/db.php';

header('Content-Type: application/json');

try {
    echo json_encode([
        'status' => 'success',
        'message' => 'Test Database Chat System',
        'timestamp' => date('Y-m-d H:i:s'),
        'tests' => [
            // Test 1: Verifica tabelle
            'tables' => testTables($pdo),
            
            // Test 2: Verifica chat globale
            'global_chat' => testGlobalChat($pdo),
            
            // Test 3: Verifica stored procedure
            'private_chat' => testPrivateChat($pdo),
            
            // Test 4: Verifica viste
            'views' => testViews($pdo),
            
            // Test 5: Sistema utenti
            'users' => testUsers($pdo)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function testTables($pdo) {
    $tables = ['chat_conversations', 'chat_messages', 'chat_participants', 
               'chat_read_status', 'user_sessions', 'user_telegram_config'];
    
    $result = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $result[$table] = $stmt->rowCount() > 0 ? 'EXISTS' : 'MISSING';
    }
    return $result;
}

function testGlobalChat($pdo) {
    $stmt = $pdo->query("
        SELECT c.name, COUNT(p.user_id) as participants 
        FROM chat_conversations c 
        LEFT JOIN chat_participants p ON c.id = p.conversation_id 
        WHERE c.type = 'globale' 
        GROUP BY c.id
    ");
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'No global chat found'];
}

function testPrivateChat($pdo) {
    // Test creazione chat privata
    $stmt = $pdo->prepare("CALL GetOrCreatePrivateChat(?, ?, @chat_id)");
    $stmt->execute([2, 3]);
    
    $stmt = $pdo->query("SELECT @chat_id as chat_id");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Conta chat private totali
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chat_conversations WHERE type = 'privata'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'created_chat_id' => $result['chat_id'],
        'total_private_chats' => $count['total']
    ];
}

function testViews($pdo) {
    $result = [];
    
    // Test vista chat attive
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_active_chats");
        $result['user_active_chats'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $result['user_active_chats'] = 'ERROR: ' . $e->getMessage();
    }
    
    // Test vista utenti online
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users_online_status");
        $result['users_online_status'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $result['users_online_status'] = 'ERROR: ' . $e->getMessage();
    }
    
    return $result;
}

function testUsers($pdo) {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            (SELECT COUNT(*) FROM chat_participants WHERE conversation_id = 1) as users_in_global_chat,
            (SELECT COUNT(*) FROM chat_read_status WHERE conversation_id = 1) as users_with_read_status
        FROM utenti
    ");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
