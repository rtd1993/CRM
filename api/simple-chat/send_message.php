<?php
/**
 * API Simple Chat - Send Message
 * Endpoint: /api/simple-chat/send_message.php
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
    $message = isset($input['message']) ? trim($input['message']) : '';
    
    // Validazione
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Messaggio vuoto']);
        exit;
    }
    
    if (strlen($message) > 1000) {
        echo json_encode(['success' => false, 'error' => 'Messaggio troppo lungo (max 1000 caratteri)']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Verifica che la conversazione esista
    $stmt = $pdo->prepare("SELECT id, type FROM chat_conversations WHERE id = ? AND is_active = 1");
    $stmt->execute([$conversation_id]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'error' => 'Conversazione non trovata']);
        exit;
    }
    
    // Inserisci messaggio
    $sql = "INSERT INTO chat_messages 
            (user_id, conversation_id, message, created_at) 
            VALUES 
            (:user_id, :conversation_id, :message, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':conversation_id' => $conversation_id,
        ':message' => $message
    ]);
    
    $message_id = $pdo->lastInsertId();
    
    // Aggiorna timestamp ultima attività conversazione
    $stmt = $pdo->prepare("UPDATE chat_conversations SET last_message_at = NOW() WHERE id = ?");
    $stmt->execute([$conversation_id]);
    
    echo json_encode([
        'success' => true,
        'message_id' => (int)$message_id,
        'message' => 'Messaggio inviato con successo'
    ]);
    
} catch (Exception $e) {
    error_log("Errore Simple Chat send_message: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore del server: ' . $e->getMessage()
    ]);
}
?>
