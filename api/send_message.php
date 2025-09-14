<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';
require_once '../includes/telegram.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $chat_id = intval($input['chat_id'] ?? $input['conversation_id'] ?? 0);
    $message = trim($input['message'] ?? '');
    $user_id = intval($input['user_id'] ?? $_SESSION['user_id']);
    
    if (!$chat_id || !$message || !$user_id) {
        throw new Exception('Parametri mancanti');
    }
    
    // Verifica che l'utente possa scrivere in questa conversazione
    $stmt = $pdo->prepare("
        SELECT 1 FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ? AND is_active = 1
    ");
    $stmt->execute([$chat_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Non sei autorizzato a scrivere in questa conversazione');
    }
    
    // Inserisci messaggio
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, user_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$chat_id, $user_id, $message]);
    
    $message_id = $pdo->lastInsertId();
    
    // Aggiorna timestamp ultima attività della conversazione
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$chat_id]);
    
    // Aggiorna lo stato di lettura per il mittente (marca come letto)
    $stmt = $pdo->prepare("
        INSERT INTO user_conversation_status (user_id, conversation_id, last_seen) 
        VALUES (?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE last_seen = NOW()
    ");
    $stmt->execute([$user_id, $chat_id]);
    
    // Invia notifiche Telegram ai partecipanti offline
    inviaNotificaTelegramChat($chat_id, $user_id, $message);
    
    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
