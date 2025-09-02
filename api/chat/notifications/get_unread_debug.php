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
    // Versione semplificata per debug
    $unread_counts = [
        'global' => 0,
        'practice' => [],
        'private' => [],
        'total' => 0
    ];
    
    // Aggiorna la sessione utente per il tracking online
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, last_activity) 
            VALUES (?, NOW()) 
            ON DUPLICATE KEY UPDATE last_activity = NOW()
        ");
        $stmt->execute([$current_user_id]);
    } catch (Exception $e) {
        error_log("Errore aggiornamento sessione: " . $e->getMessage());
    }
    
    // Per ora restituiamo dati mock per testare
    $unread_counts['global'] = 2;
    $unread_counts['practice'][1] = 1;
    $unread_counts['private'][2] = 3;
    $unread_counts['total'] = 6;
    
    echo json_encode([
        'success' => true,
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
