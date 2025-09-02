<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $chat_id = intval($input['chat_id'] ?? 0);
    $user_id = intval($input['user_id'] ?? $_SESSION['user_id']);
    
    if (!$chat_id || !$user_id) {
        throw new Exception('Parametri mancanti');
    }
    
    // Ottieni l'ultimo messaggio della chat
    $stmt = $pdo->prepare("
        SELECT id FROM chat_messages_new 
        WHERE chat_id = ? AND is_deleted = 0
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$chat_id]);
    $last_message = $stmt->fetch();
    
    if ($last_message) {
        // Aggiorna o inserisci stato lettura
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status_new (user_id, chat_id, last_read_message_id, last_read_at, unread_count)
            VALUES (?, ?, ?, NOW(), 0)
            ON DUPLICATE KEY UPDATE 
                last_read_message_id = VALUES(last_read_message_id),
                last_read_at = VALUES(last_read_at),
                unread_count = 0
        ");
        $stmt->execute([$user_id, $chat_id, $last_message['id']]);
    } else {
        // Nessun messaggio, imposta comunque come letto
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status_new (user_id, chat_id, last_read_at, unread_count)
            VALUES (?, ?, NOW(), 0)
            ON DUPLICATE KEY UPDATE 
                last_read_at = NOW(),
                unread_count = 0
        ");
        $stmt->execute([$user_id, $chat_id]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
