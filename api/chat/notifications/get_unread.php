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
    // Conta i messaggi non letti per tipo di conversazione
    $stmt = $pdo->prepare("
        SELECT 
            c.type,
            c.id as conversation_id,
            c.practice_id,
            COUNT(cm.id) as unread_count
        FROM chat_conversations c
        INNER JOIN chat_participants cp ON c.id = cp.conversation_id AND cp.user_id = ?
        INNER JOIN chat_messages cm ON c.id = cm.conversation_id AND cm.user_id != ?
        LEFT JOIN chat_read_status crs ON cm.id = crs.message_id AND crs.user_id = ?
        WHERE crs.id IS NULL
        GROUP BY c.id, c.type, c.practice_id
    ");
    
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizza i risultati per tipo
    $unread_counts = [
        'global' => 0,
        'practice' => [],
        'private' => [],
        'total' => 0
    ];
    
    foreach ($results as $row) {
        $count = (int)$row['unread_count'];
        $unread_counts['total'] += $count;
        
        switch ($row['type']) {
            case 'global':
                $unread_counts['global'] += $count;
                break;
                
            case 'practice':
                $practice_id = (int)$row['practice_id'];
                if (!isset($unread_counts['practice'][$practice_id])) {
                    $unread_counts['practice'][$practice_id] = 0;
                }
                $unread_counts['practice'][$practice_id] += $count;
                break;
                
            case 'private':
                $conversation_id = (int)$row['conversation_id'];
                $unread_counts['private'][$conversation_id] = $count;
                break;
        }
    }
    
    // Aggiorna la sessione utente per il tracking online
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, last_activity) 
        VALUES (?, NOW()) 
        ON DUPLICATE KEY UPDATE last_activity = NOW()
    ");
    $stmt->execute([$current_user_id]);
    
    echo json_encode([
        'success' => true,
        'data' => $unread_counts
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_unread.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server'
    ]);
}
?>
