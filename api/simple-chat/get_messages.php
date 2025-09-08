<?php
/**
 * API Simple Chat - Get Messages History
 * Endpoint: /api/simple-chat/get_messages.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Includi configurazione database
require_once __DIR__ . '/../../includes/db.php';

// Verifica sessione
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

try {
    // Leggi input
    $input = json_decode(file_get_contents('php://input'), true);
    $conversation_id = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 1; // Default chat globale
    $limit = isset($input['limit']) ? (int)$input['limit'] : 50;
    $since = isset($input['since']) ? (int)$input['since'] : 0;
    
    // Query per messaggi della conversazione specifica
    $sql = "SELECT 
                m.id,
                m.user_id,
                u.nome as user_name,
                m.message,
                m.created_at
            FROM chat_messages m
            LEFT JOIN utenti u ON m.user_id = u.id
            WHERE m.conversation_id = :conversation_id
            AND m.is_deleted = 0";
    
    // Se richiesti solo messaggi nuovi
    if ($since > 0) {
        $sql .= " AND m.id > :since";
    }
    
    $sql .= " ORDER BY m.created_at ASC LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    if ($since > 0) {
        $stmt->bindValue(':since', $since, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatta i messaggi
    foreach ($messages as &$message) {
        $message['id'] = (int)$message['id'];
        $message['user_id'] = (int)$message['user_id'];
        $message['user_name'] = $message['user_name'] ?: 'Utente Sconosciuto';
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
    
} catch (Exception $e) {
    error_log("Errore Simple Chat get_messages: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore del server: ' . $e->getMessage()
    ]);
}
?>
