<?php
/**
 * API Endpoint: /api/chat/messages/get_history.php
 * Descrizione: Restituisce la cronologia messaggi di una conversazione
 * Metodo: POST
 * Parametri: conversation_id, limit (opzionale), offset (opzionale)
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

// Leggi i dati POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['conversation_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID conversazione richiesto'
    ]);
    exit;
}

$conversation_id = (int)$input['conversation_id'];
$limit = isset($input['limit']) ? (int)$input['limit'] : 50;
$offset = isset($input['offset']) ? (int)$input['offset'] : 0;

try {
    // Verifica che l'utente sia partecipante della conversazione
    $stmt = $pdo->prepare("
        SELECT 1 FROM chat_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $current_user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accesso negato alla conversazione'
        ]);
        exit;
    }
    
    // Recupera i messaggi
    $stmt = $pdo->prepare("
        SELECT 
            cm.id,
            cm.content,
            cm.created_at,
            cm.user_id,
            u.nome as user_name,
            u.ruolo as user_role,
            -- Verifica se il messaggio è stato letto dall'utente corrente
            CASE WHEN crs.id IS NOT NULL THEN 1 ELSE 0 END as is_read
        FROM chat_messages cm
        INNER JOIN utenti u ON cm.user_id = u.id
        LEFT JOIN chat_read_status crs ON cm.id = crs.message_id AND crs.user_id = ?
        WHERE cm.conversation_id = ?
        ORDER BY cm.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$current_user_id, $conversation_id, $limit, $offset]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatta i messaggi per il frontend
    $formatted_messages = [];
    foreach (array_reverse($messages) as $msg) { // Inverti per avere ordine cronologico
        $formatted_messages[] = [
            'id' => (int)$msg['id'],
            'content' => htmlspecialchars($msg['content']),
            'created_at' => $msg['created_at'],
            'user' => [
                'id' => (int)$msg['user_id'],
                'name' => htmlspecialchars($msg['user_name']),
                'role' => htmlspecialchars($msg['user_role'])
            ],
            'is_mine' => $msg['user_id'] == $current_user_id,
            'is_read' => (bool)$msg['is_read']
        ];
    }
    
    // Marca come letti tutti i messaggi non letti di questa conversazione
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO chat_read_status (message_id, user_id, read_at)
        SELECT cm.id, ?, NOW()
        FROM chat_messages cm
        WHERE cm.conversation_id = ? 
        AND cm.user_id != ?
        AND NOT EXISTS (
            SELECT 1 FROM chat_read_status crs2 
            WHERE crs2.message_id = cm.id AND crs2.user_id = ?
        )
    ");
    $stmt->execute([$current_user_id, $conversation_id, $current_user_id, $current_user_id]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'messages' => $formatted_messages,
            'conversation_id' => $conversation_id,
            'total' => count($formatted_messages),
            'has_more' => count($messages) == $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_history.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server'
    ]);
}
?>
