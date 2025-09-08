<?php
/**
 * API Endpoint: /api/chat/messages/get_history.php
 * Descrizione: Restituisce la cronologia dei messaggi per una conversazione
 * Metodo: POST
 * Autenticazione: Richiesta
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

if (!$conversation_type) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Tipo conversazione richiesto',
        'received_data' => $input
    ]);
    exit;
}

try {
    // DEBUG: Messaggi mock per il testing
    $messages = [];
    
    switch ($conversation_type) {
        case 'global':
            $messages = [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'user_name' => 'Admin',
                    'user_role' => 'admin',
                    'content' => 'Benvenuti nella chat globale!',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'is_mine' => ($current_user_id == 1)
                ],
                [
                    'id' => 2,
                    'user_id' => 2,
                    'user_name' => 'Roberto',
                    'user_role' => 'user',
                    'content' => 'Ciao a tutti!',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'is_mine' => ($current_user_id == 2)
                ],
                [
                    'id' => 3,
                    'user_id' => 1,
                    'user_name' => 'Admin',
                    'user_role' => 'admin',
                    'content' => 'Come va il lavoro oggi?',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                    'is_mine' => ($current_user_id == 1)
                ]
            ];
            break;
            
        case 'practice':
            $messages = [
                [
                    'id' => 4,
                    'user_id' => 1,
                    'user_name' => 'Admin',
                    'user_role' => 'admin',
                    'content' => "Pratica #$practice_id: Documenti caricati",
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'is_mine' => ($current_user_id == 1)
                ],
                [
                    'id' => 5,
                    'user_id' => $current_user_id,
                    'user_name' => $_SESSION['username'] ?? 'User',
                    'user_role' => 'user',
                    'content' => 'Perfetto, grazie!',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
                    'is_mine' => true
                ]
            ];
            break;
            
        case 'private':
            $messages = [
                [
                    'id' => 6,
                    'user_id' => 1,
                    'user_name' => 'Admin',
                    'user_role' => 'admin',
                    'content' => 'Ciao, come posso aiutarti?',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-20 minutes')),
                    'is_mine' => ($current_user_id == 1)
                ],
                [
                    'id' => 7,
                    'user_id' => $current_user_id,
                    'user_name' => $_SESSION['username'] ?? 'User',
                    'user_role' => 'user',
                    'content' => 'Ho una domanda sulla mia pratica',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
                    'is_mine' => true
                ]
            ];
            break;
            
        default:
            $messages = [];
    }
    
    // Log per debug
    error_log("DEBUG get_history.php - User: $current_user_id, Type: $conversation_type, Messages: " . count($messages));
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'total' => count($messages),
        'conversation_type' => $conversation_type,
        'debug' => [
            'user_id' => $current_user_id,
            'input' => $input,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_history.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server',
        'debug' => $e->getMessage()
    ]);
}
?>
