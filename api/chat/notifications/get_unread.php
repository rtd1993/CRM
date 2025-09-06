<?php
/**
 * API Endpoint: /api/chat/notifications/get_unread.php
 * Descrizione: Restituisce il conteggio dei messaggi non letti per tutte le chat
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
    // DEBUG: Dati mock per il testing
    $unread_counts = [
        'global' => rand(0, 5),
        'practice' => [
            1 => rand(0, 3),
            2 => rand(0, 2),
            3 => rand(0, 4)
        ],
        'private' => [
            1 => rand(0, 2),
            2 => rand(0, 1)
        ],
        'total' => 0
    ];
    
    // Calcola il totale
    $unread_counts['total'] = $unread_counts['global'];
    foreach ($unread_counts['practice'] as $count) {
        $unread_counts['total'] += $count;
    }
    foreach ($unread_counts['private'] as $count) {
        $unread_counts['total'] += $count;
    }
    
    // Log per debug
    error_log("DEBUG get_unread.php - User ID: $current_user_id, Unread counts: " . json_encode($unread_counts));
    
    echo json_encode([
        'success' => true,
        'counts' => [
            'globale' => $unread_counts['global'],
            'pratiche' => array_sum($unread_counts['practice']),
            'private' => array_sum($unread_counts['private'])
        ],
        'data' => $unread_counts,
        'debug' => [
            'user_id' => $current_user_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_unread.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server',
        'debug' => $e->getMessage()
    ]);
}
?>
