<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Debug per sessioni non valide
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Sessione non valida - utente non loggato',
        'debug' => 'Richiedi login per accedere ai messaggi'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Metodo non consentito');
    }
    
    $conversation_id = intval($_GET['conversation_id'] ?? 0);
    
    if (!$conversation_id) {
        throw new Exception('ID conversazione mancante');
    }
    
    // Verifica che l'utente possa accedere a questa conversazione
    $stmt = $pdo->prepare("
        SELECT 1 FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ? AND is_active = 1
    ");
    $stmt->execute([$conversation_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Non sei autorizzato ad accedere a questa conversazione');
    }
    
    // Carica messaggi - supporta caricamento incrementale
    $since_id = intval($_GET['since_id'] ?? 0);
    
    if ($since_id > 0) {
        // Caricamento incrementale - solo messaggi più recenti
        $stmt = $pdo->prepare("
            SELECT m.id, m.message, m.created_at, m.user_id, u.nome as sender_name, m.message_type
            FROM messages m
            JOIN utenti u ON m.user_id = u.id
            WHERE m.conversation_id = ? AND m.id > ?
            ORDER BY m.created_at ASC
            LIMIT 50
        ");
        $stmt->execute([$conversation_id, $since_id]);
    } else {
        // Caricamento completo iniziale
        $stmt = $pdo->prepare("
            SELECT m.id, m.message, m.created_at, m.user_id, u.nome as sender_name, m.message_type
            FROM messages m
            JOIN utenti u ON m.user_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        $stmt->execute([$conversation_id]);
    }
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatta i messaggi
    $formatted_messages = [];
    foreach ($messages as $message) {
        $formatted_messages[] = [
            'id' => $message['id'],
            'message' => $message['message'],
            'timestamp' => $message['created_at'],
            'user_id' => $message['user_id'],
            'sender_name' => $message['sender_name'],
            'is_own' => ($message['user_id'] == $user_id)
        ];
    }
    
    // Aggiorna lo stato di lettura
    $stmt = $pdo->prepare("
        INSERT INTO user_conversation_status (user_id, conversation_id, last_seen) 
        VALUES (?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE last_seen = NOW()
    ");
    $stmt->execute([$user_id, $conversation_id]);
    
    echo json_encode([
        'success' => true,
        'messages' => $formatted_messages,
        'conversation_id' => $conversation_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
