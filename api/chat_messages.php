<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $chat_id = intval($_GET['chat_id'] ?? 0);
    $limit = intval($_GET['limit'] ?? 50);
    $user_id = $_SESSION['user_id'];
    
    if (!$chat_id) {
        throw new Exception('Chat ID mancante');
    }
    
    // Verifica che l'utente possa accedere a questa chat
    $stmt = $pdo->prepare("
        SELECT 1 FROM chat_participants 
        WHERE chat_id = ? AND user_id = ? AND is_active = 1
    ");
    $stmt->execute([$chat_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Accesso non autorizzato a questa chat');
    }
    
    // Recupera messaggi
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.message,
            m.message_type,
            m.file_path,
            m.created_at,
            m.user_id,
            u.nome as user_name
        FROM chat_messages_new m
        JOIN utenti u ON m.user_id = u.id
        WHERE m.chat_id = ? AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$chat_id, $limit]);
    $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo json_encode($messages);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
