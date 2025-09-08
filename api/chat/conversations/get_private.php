<?php
/**
 * API Endpoint: /api/chat/conversations/get_private.php
 * Descrizione: Restituisce la lista delle conversazioni private dell'utente
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
        'success' => false,
        'error' => 'Non autenticato'
    ]);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    // Versione semplificata per debug
    $conversations = [
        [
            'id' => 1,
            'name' => 'Admin',
            'type' => 'privata',
            'other_user' => [
                'id' => 2,
                'name' => 'Admin',
                'role' => 'admin',
                'is_online' => true
            ],
            'last_message' => [
                'content' => 'Ciao, come va?',
                'time' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                'user_id' => 2,
                'is_mine' => false
            ],
            'unread_count' => 2,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'conversations' => $conversations,
            'total' => count($conversations)
        ],
        'debug' => [
            'current_user_id' => $current_user_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_private.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server'
    ]);
}
?>
