<?php
// Test API senza autenticazione per debug
header('Content-Type: application/json');

// Simula sessione
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';

require_once __DIR__ . '/../includes/db.php';

try {
    $chat_type = $_GET['chat_type'] ?? '';
    $pratica_id = $_GET['pratica_id'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    if (!$chat_type) {
        throw new Exception('Tipo chat mancante');
    }
    
    // Debug info
    error_log("DEBUG: user_id=$user_id, chat_type=$chat_type, pratica_id=$pratica_id");
    
    // Ottieni timestamp ultimo messaggio letto
    $stmt = $pdo->prepare("
        SELECT last_read_timestamp 
        FROM chat_read_status 
        WHERE user_id = ? AND chat_type = ? AND pratica_id <=> ?
    ");
    $stmt->execute([$user_id, $chat_type, $pratica_id]);
    $read_status = $stmt->fetch();
    
    $last_read = $read_status ? $read_status['last_read_timestamp'] : '1970-01-01 00:00:00';
    error_log("DEBUG: last_read=$last_read");
    
    // Conta messaggi non letti
    if ($chat_type === 'globale') {
        // Per chat globale
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count
            FROM chat_messaggi 
            WHERE timestamp > ? AND utente_id != ?
        ");
        $stmt->execute([$last_read, $user_id]);
    } else if ($chat_type === 'pratica' && $pratica_id) {
        // Per chat pratiche
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count
            FROM chat_pratiche 
            WHERE timestamp > ? AND pratica_id = ? AND utente_id != ?
        ");
        $stmt->execute([$last_read, $pratica_id, $user_id]);
    } else {
        throw new Exception('Parametri non validi');
    }
    
    $result = $stmt->fetch();
    $unread_count = $result['unread_count'] ?? 0;
    
    error_log("DEBUG: unread_count=$unread_count");
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$unread_count,
        'last_read' => $last_read,
        'debug' => [
            'user_id' => $user_id,
            'chat_type' => $chat_type,
            'pratica_id' => $pratica_id
        ]
    ]);
    
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
