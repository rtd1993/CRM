<?php
/**
 * API Endpoint: /api/chat/users/get_list.php
 * Descrizione: Restituisce la lista degli utenti disponibili per chat private
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
require_once __DIR__ . '/../../../includes/db.php';

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

try {
    // Versione semplificata per debug
    $users = [
        [
            'id' => 2,
            'name' => 'Admin',
            'role' => 'admin',
            'email' => 'admin@test.com',
            'is_online' => true,
            'last_activity' => date('Y-m-d H:i:s'),
            'avatar_url' => null
        ],
        [
            'id' => 3,
            'name' => 'User Test',
            'role' => 'user',
            'email' => 'user@test.com',
            'is_online' => false,
            'last_activity' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
            'avatar_url' => null
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $users,
            'total' => count($users),
            'online_count' => count(array_filter($users, function($u) { return $u['is_online']; }))
        ],
        'debug' => [
            'current_user_id' => $current_user_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_list.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server'
    ]);
}
?>
