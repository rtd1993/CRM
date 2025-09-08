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
    
    // Inserisci messaggio
    $sql = "INSERT INTO chat_messages 
            (user_id, conversation_type, conversation_id, message, created_at) 
            VALUES 
            (:user_id, 'globale', 1, :message, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':message' => $message
    ]);
    
    $message_id = $pdo->lastInsertId();
    
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
