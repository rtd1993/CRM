<?php
/**
 * API Endpoint: /api/chat/conversations/create_private.php
 * Descrizione: Crea una nuova conversazione privata tra due utenti
 * Metodo: POST
 * Parametri: other_user_id
 * Autenticazione: Richiesta
 */

// Debug: aggiungi gestione errori dettagliata
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../../../includes/auth.php';
    require_login();
    require_once __DIR__ . '/../../../includes/config.php';
    require_once __DIR__ . '/../../../includes/db.php';
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');

    $current_user_id = $_SESSION['user_id'];

    // Leggi i dati POST
    $input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['other_user_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID utente destinatario richiesto'
    ]);
    exit;
}

$other_user_id = (int)$input['other_user_id'];

// Non puoi creare una chat con te stesso
if ($other_user_id == $current_user_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Non puoi creare una chat con te stesso'
    ]);
    exit;
}

try {
    // Verifica che l'altro utente esista
    $stmt = $pdo->prepare("SELECT nome FROM utenti WHERE id = ?");
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch();
    
    if (!$other_user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Utente non trovato'
        ]);
        exit;
    }
    
    // Verifica se esiste già una conversazione privata tra questi due utenti
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM chat_conversations c
        INNER JOIN chat_participants cp1 ON c.id = cp1.conversation_id AND cp1.user_id = ?
        INNER JOIN chat_participants cp2 ON c.id = cp2.conversation_id AND cp2.user_id = ?
        WHERE c.type = 'private'
        AND (
            SELECT COUNT(*) FROM chat_participants cp3 WHERE cp3.conversation_id = c.id
        ) = 2
        LIMIT 1
    ");
    $stmt->execute([$current_user_id, $other_user_id]);
    $existing_conversation = $stmt->fetch();
    
    if ($existing_conversation) {
        // Conversazione già esistente, restituiscila
        echo json_encode([
            'success' => true,
            'data' => [
                'conversation_id' => (int)$existing_conversation['id'],
                'is_new' => false,
                'message' => 'Conversazione esistente'
            ]
        ]);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Crea nuova conversazione
    $stmt = $pdo->prepare("
        INSERT INTO chat_conversations (name, type, created_by, created_at) 
        VALUES (?, 'private', ?, NOW())
    ");
    $conversation_name = "Chat con " . $other_user['nome'];
    $stmt->execute([$conversation_name, $current_user_id]);
    $conversation_id = $pdo->lastInsertId();
    
    // Aggiungi entrambi gli utenti come partecipanti
    $stmt = $pdo->prepare("
        INSERT INTO chat_participants (conversation_id, user_id, joined_at) 
        VALUES (?, ?, NOW()), (?, ?, NOW())
    ");
    $stmt->execute([$conversation_id, $current_user_id, $conversation_id, $other_user_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'conversation_id' => (int)$conversation_id,
            'conversation_name' => htmlspecialchars($conversation_name),
            'other_user_name' => htmlspecialchars($other_user['nome']),
            'is_new' => true,
            'message' => 'Conversazione creata con successo'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Include error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Errore API create_private.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?>
