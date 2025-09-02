<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Recupera contatori non letti per tutte le chat dell'utente
    $stmt = $pdo->prepare("
        SELECT 
            c.id as chat_id,
            c.chat_type,
            c.name,
            c.pratica_id,
            COALESCE(rs.unread_count, 0) as unread_count
        FROM chat_conversations c
        JOIN chat_participants p ON c.id = p.chat_id
        LEFT JOIN chat_read_status_new rs ON c.id = rs.chat_id AND rs.user_id = ?
        WHERE p.user_id = ? AND p.is_active = 1
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($notifications);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
