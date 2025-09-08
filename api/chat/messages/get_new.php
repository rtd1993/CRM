<?php
/**
 * API Endpoint: /api/chat/messages/get_new.php
 * Descrizione: Restituisce i nuovi messaggi per il polling
 * Metodo: POST
 * Autenticazione: Richiesta
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Non autenticato'
    ]);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Leggi i dati POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Dati POST mancanti'
    ]);
    exit;
}

// Supporta sia conversation_type che type per compatibilità
$conversation_type = $input['conversation_type'] ?? $input['type'] ?? null;
$conversation_id = $input['conversation_id'] ?? $input['id'] ?? null;
$practice_id = $input['practice_id'] ?? null;
$last_message_id = $input['last_message_id'] ?? $input['since'] ?? 0;

if (!$conversation_type) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Tipo conversazione richiesto'
    ]);
    exit;
}

try {
    // DEBUG: Simulazione di nuovi messaggi casuali
    $new_messages = [];
    
    // Solo a volte restituiamo un nuovo messaggio (per simulare l'attività)
    if (rand(1, 10) <= 2) { // 20% di probabilità
        $message_id = $last_message_id + rand(1, 3);
        
        $new_messages[] = [
            'id' => $message_id,
            'user_id' => ($current_user_id == 1) ? 2 : 1, // Messaggio dall'altro utente
            'user_name' => ($current_user_id == 1) ? 'Roberto' : 'Admin',
            'user_role' => ($current_user_id == 1) ? 'user' : 'admin',
            'content' => 'Nuovo messaggio di test #' . $message_id,
            'created_at' => date('Y-m-d H:i:s'),
            'is_mine' => false
        ];
    }
    
    // Log per debug
    error_log("DEBUG get_new.php - User: $current_user_id, Type: $conversation_type, New messages: " . count($new_messages));
    
    echo json_encode([
        'success' => true,
        'messages' => $new_messages,
        'count' => count($new_messages),
        'conversation_type' => $conversation_type,
        'last_checked' => date('Y-m-d H:i:s'),
        'debug' => [
            'user_id' => $current_user_id,
            'input' => $input,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_new.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server',
        'debug' => $e->getMessage()
    ]);
}
?>
