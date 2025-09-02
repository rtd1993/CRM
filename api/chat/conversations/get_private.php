<?php
/**
 * API Endpoint: /api/chat/conversations/get_private.php
 * Descrizione: Restituisce la lista delle conversazioni private dell'utente
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
    // Recupera le conversazioni private dell'utente
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            c.id as conversation_id,
            c.name as conversation_name,
            c.type,
            c.created_at,
            c.updated_at,
            -- Trova l'altro partecipante
            u.id as other_user_id,
            u.nome as other_user_name,
            u.ruolo as other_user_role,
            -- Ultimo messaggio
            lm.content as last_message,
            lm.created_at as last_message_time,
            lm.user_id as last_message_user_id,
            -- Status online
            CASE 
                WHEN us.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                THEN 1 
                ELSE 0 
            END as other_user_online,
            -- Messaggi non letti
            COALESCE(unread.unread_count, 0) as unread_count
        FROM chat_conversations c
        INNER JOIN chat_participants cp ON c.id = cp.conversation_id
        INNER JOIN chat_participants cp2 ON c.id = cp2.conversation_id AND cp2.user_id != ?
        INNER JOIN utenti u ON cp2.user_id = u.id
        LEFT JOIN user_sessions us ON u.id = us.user_id
        LEFT JOIN (
            SELECT 
                cm.conversation_id,
                cm.content,
                cm.created_at,
                cm.user_id,
                ROW_NUMBER() OVER (PARTITION BY cm.conversation_id ORDER BY cm.created_at DESC) as rn
            FROM chat_messages cm
        ) lm ON c.id = lm.conversation_id AND lm.rn = 1
        LEFT JOIN (
            SELECT 
                conversation_id,
                COUNT(*) as unread_count
            FROM chat_messages cm
            WHERE cm.user_id != ? 
            AND NOT EXISTS (
                SELECT 1 FROM chat_read_status crs 
                WHERE crs.message_id = cm.id 
                AND crs.user_id = ?
            )
            GROUP BY conversation_id
        ) unread ON c.id = unread.conversation_id
        WHERE c.type = 'private'
        AND cp.user_id = ?
        ORDER BY 
            COALESCE(lm.created_at, c.created_at) DESC
    ");
    
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatta i dati per il frontend
    $formatted_conversations = [];
    foreach ($conversations as $conv) {
        $formatted_conversations[] = [
            'id' => (int)$conv['conversation_id'],
            'name' => $conv['other_user_name'],
            'type' => 'private',
            'other_user' => [
                'id' => (int)$conv['other_user_id'],
                'name' => htmlspecialchars($conv['other_user_name']),
                'role' => htmlspecialchars($conv['other_user_role']),
                'is_online' => (bool)$conv['other_user_online']
            ],
            'last_message' => [
                'content' => $conv['last_message'] ? htmlspecialchars($conv['last_message']) : null,
                'time' => $conv['last_message_time'],
                'user_id' => $conv['last_message_user_id'] ? (int)$conv['last_message_user_id'] : null,
                'is_mine' => $conv['last_message_user_id'] == $current_user_id
            ],
            'unread_count' => (int)$conv['unread_count'],
            'created_at' => $conv['created_at'],
            'updated_at' => $conv['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'conversations' => $formatted_conversations,
            'total' => count($formatted_conversations)
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
