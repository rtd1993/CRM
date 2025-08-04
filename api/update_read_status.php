<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id']) || !isset($input['chat_type']) || !isset($input['timestamp'])) {
        throw new Exception('Parametri mancanti');
    }
    
    $user_id = $input['user_id'];
    $chat_type = $input['chat_type'];
    $timestamp = $input['timestamp'];
    $pratica_id = isset($input['pratica_id']) ? $input['pratica_id'] : null;
    
    // Verifica che l'utente possa aggiornare solo i propri record
    if ($user_id != $_SESSION['user_id']) {
        throw new Exception('Non autorizzato');
    }
    
    // Aggiorna o inserisci record di stato lettura
    $stmt = $pdo->prepare("
        INSERT INTO chat_read_status (user_id, chat_type, pratica_id, last_read_timestamp) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            last_read_timestamp = VALUES(last_read_timestamp),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$user_id, $chat_type, $pratica_id, $timestamp]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
