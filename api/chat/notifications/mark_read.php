<?php
/**
 * API Endpoint: /api/chat/notifications/mark_read.php
 * Descrizione: Segna i messaggi come letti
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

if (!$input || !isset($input['conversation_type'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Tipo conversazione richiesto'
    ]);
    exit;
}

$conversation_type = $input['conversation_type'];
$conversation_id = $input['conversation_id'] ?? null;
$practice_id = $input['practice_id'] ?? null;

try {
    // DEBUG: Simulazione segnazione come letto
    $marked_count = rand(1, 5);
    
    // Log per debug
    error_log("DEBUG mark_read.php - User: $current_user_id, Type: $conversation_type, Marked: $marked_count");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'marked_count' => $marked_count,
            'conversation_type' => $conversation_type,
            'conversation_id' => $conversation_id,
            'practice_id' => $practice_id
        ],
        'debug' => [
            'user_id' => $current_user_id,
            'input' => $input,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API mark_read.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server',
        'debug' => $e->getMessage()
    ]);
}
?>
