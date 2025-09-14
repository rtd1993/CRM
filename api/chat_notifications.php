<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

// Debug per identificare problemi
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'unread_counts' => [],
        'total' => 0,
        'debug' => 'Sessione non valida - conteggi azzerati'
    ]);
    exit;
}

require_login();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Ottieni conteggi messaggi non letti
            if (isset($_GET['action']) && $_GET['action'] === 'unread_counts') {
                $unreadCounts = [];
                
                // Chat globale (conversation_id = 1)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM messages m 
                    WHERE m.conversation_id = 1 
                    AND m.user_id != ? 
                    AND m.created_at > COALESCE(
                        (SELECT last_seen FROM user_conversation_status 
                         WHERE user_id = ? AND conversation_id = 1), 
                        '1970-01-01'
                    )
                ");
                $stmt->execute([$user_id, $user_id]);
                $globalCount = $stmt->fetchColumn();
                
                if ($globalCount > 0) {
                    $unreadCounts['global'] = $globalCount;
                }
                
                // Chat pratiche - ottieni tutte le conversazioni pratiche dell'utente con info cliente
                $stmt = $pdo->prepare("
                    SELECT
                        c.id,
                        c.name,
                        c.client_id,
                        cl.Cognome_Ragione_sociale,
                        cl.Nome,
                        COUNT(m.id) as unread_count
                    FROM conversations c
                    JOIN conversation_participants cp ON c.id = cp.conversation_id
                    LEFT JOIN clienti cl ON c.client_id = cl.id
                    LEFT JOIN messages m ON c.id = m.conversation_id
                        AND m.user_id != ?
                        AND m.created_at > COALESCE(
                            (SELECT last_seen FROM user_conversation_status
                             WHERE user_id = ? AND conversation_id = c.id),
                            '1970-01-01'
                        )
                    WHERE cp.user_id = ?
                    AND c.type IN ('pratica', 'cliente')
                    AND cp.is_active = 1
                    GROUP BY c.id, c.name, c.client_id, cl.Cognome_Ragione_sociale, cl.Nome
                    HAVING unread_count > 0
                ");
                $stmt->execute([$user_id, $user_id, $user_id]);
                $practiceChats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($practiceChats as $chat) {
                    // Includi informazioni cliente nel badge ID per identificazione
                    $clientName = $chat['Cognome_Ragione_sociale'] ?: $chat['Nome'] ?: "Cliente #{$chat['client_id']}";
                    $unreadCounts['practice_' . $chat['id']] = [
                        'count' => $chat['unread_count'],
                        'client_name' => $clientName,
                        'conversation_name' => $chat['name']
                    ];
                }
                
                // Chat private - ottieni conteggi per ogni altro utente
                $stmt = $pdo->prepare("
                    SELECT 
                        c.id as conversation_id,
                        cp2.user_id as other_user_id,
                        COUNT(m.id) as unread_count
                    FROM conversations c
                    JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
                    JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
                    LEFT JOIN messages m ON c.id = m.conversation_id 
                        AND m.user_id != ? 
                        AND m.created_at > COALESCE(
                            (SELECT last_seen FROM user_conversation_status 
                             WHERE user_id = ? AND conversation_id = c.id), 
                            '1970-01-01'
                        )
                    WHERE cp1.user_id = ? 
                    AND cp2.user_id != ?
                    AND c.type = 'private'
                    AND cp1.is_active = 1
                    AND cp2.is_active = 1
                    GROUP BY c.id, cp2.user_id
                    HAVING unread_count > 0
                ");
                $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
                $privateChats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($privateChats as $chat) {
                    // Usa l'ID dell'altro utente per il badge
                    $unreadCounts['private_user_' . $chat['other_user_id']] = $chat['unread_count'];
                }
                
                // Calcola il totale gestendo sia oggetti che numeri
                $total = 0;
                foreach ($unreadCounts as $value) {
                    if (is_array($value) && isset($value['count'])) {
                        $total += $value['count'];
                    } else {
                        $total += (int)$value;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'unread_counts' => $unreadCounts,
                    'total' => $total
                ]);
            } else {
                // Lista notifiche recenti
                $stmt = $pdo->prepare("
                    SELECT m.id, m.message, m.created_at, u.nome as sender_name, c.name as chat_name, c.type as chat_type
                    FROM messages m
                    JOIN utenti u ON m.user_id = u.id
                    JOIN conversations c ON m.conversation_id = c.id
                    JOIN conversation_participants cp ON c.id = cp.conversation_id
                    WHERE cp.user_id = ? 
                    AND m.user_id != ?
                    AND m.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
                    ORDER BY m.created_at DESC
                    LIMIT 20
                ");
                $stmt->execute([$user_id, $user_id]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications
                ]);
            }
            break;
            
        case 'POST':
            // Marca messaggi come letti
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['conversation_id'])) {
                $conversation_id = $input['conversation_id'];
                
                // Aggiorna last_seen per questa conversazione
                $stmt = $pdo->prepare("
                    INSERT INTO user_conversation_status (user_id, conversation_id, last_seen) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE last_seen = NOW()
                ");
                $stmt->execute([$user_id, $conversation_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Messaggi marcati come letti'
                ]);
            } else {
                throw new Exception('conversation_id richiesto');
            }
            break;
            
        default:
            throw new Exception('Metodo non supportato');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
