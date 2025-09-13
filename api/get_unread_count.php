<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

try {
    $chat_type = $_GET['chat_type'] ?? '';
    $pratica_id = $_GET['pratica_id'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    if (!$chat_type) {
        throw new Exception('Tipo chat mancante');
    }
    
    // Trova conversation_id per il tipo di chat richiesto
    $conversation_id = null;
    
    if ($chat_type === 'globale') {
        // Per chat globale (dovrebbe essere id=1)
        $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE type = 'globale' LIMIT 1");
        $stmt->execute();
        $conv = $stmt->fetch();
        $conversation_id = $conv ? $conv['id'] : 1;
    } else if ($chat_type === 'pratica' && $pratica_id) {
        // Per chat pratica specifica
        $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE type = 'pratica' AND client_id = ?");
        $stmt->execute([$pratica_id]);
        $conv = $stmt->fetch();
        $conversation_id = $conv ? $conv['id'] : null;
    } else if ($chat_type === 'privata' && isset($_GET['other_user_id'])) {
        // Per chat privata
        $other_user_id = $_GET['other_user_id'];
        $stmt = $pdo->prepare("
            SELECT id FROM chat_conversations 
            WHERE type = 'privata' 
            AND ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?))
        ");
        $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        $conv = $stmt->fetch();
        $conversation_id = $conv ? $conv['id'] : null;
    }
    
    if (!$conversation_id) {
        echo json_encode([
            'success' => true,
            'unread_count' => 0,
            'message' => 'Conversazione non trovata'
        ]);
        exit;
    }
    
    // Ottieni status lettura per questa conversazione
    $stmt = $pdo->prepare("
        SELECT unread_count, last_read_at 
        FROM chat_read_status 
        WHERE user_id = ? AND conversation_id = ?
    ");
    $stmt->execute([$user_id, $conversation_id]);
    $read_status = $stmt->fetch();
    
    if ($read_status) {
        $unread_count = (int)$read_status['unread_count'];
        $last_read = $read_status['last_read_at'];
    } else {
        // Se non esiste record, conta tutti i messaggi come non letti
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_count
            FROM chat_messages 
            WHERE conversation_id = ? AND user_id != ? AND is_deleted = FALSE
        ");
        $stmt->execute([$conversation_id, $user_id]);
        $result = $stmt->fetch();
        $unread_count = (int)($result['total_count'] ?? 0);
        $last_read = '1970-01-01 00:00:00';
    }
    

    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$unread_count,
        'last_read' => $last_read
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
