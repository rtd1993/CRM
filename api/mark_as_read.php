<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verifica metodo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Legge i dati JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['type']) || !isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametri mancanti']);
    exit;
}

$type = $input['type'];
$id = $input['id'];

try {
    $pdo->beginTransaction();
    
    if ($type === 'globale') {
        // Segna come letti tutti i messaggi della chat globale per questo utente
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status (user_id, conversation_id, message_id, read_at)
            SELECT ?, cm.conversation_id, cm.id, NOW()
            FROM chat_messages cm
            JOIN chat_conversations cc ON cm.conversation_id = cc.id
            WHERE cc.type = 'globale' 
            AND cm.sender_id != ?
            AND NOT EXISTS (
                SELECT 1 FROM chat_read_status crs 
                WHERE crs.user_id = ? AND crs.message_id = cm.id
            )
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        
    } elseif ($type === 'pratiche') {
        // Segna come letti tutti i messaggi di tutte le pratiche per questo utente
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status (user_id, conversation_id, message_id, read_at)
            SELECT ?, cm.conversation_id, cm.id, NOW()
            FROM chat_messages cm
            JOIN chat_conversations cc ON cm.conversation_id = cc.id
            WHERE cc.type = 'pratica' 
            AND cm.sender_id != ?
            AND NOT EXISTS (
                SELECT 1 FROM chat_read_status crs 
                WHERE crs.user_id = ? AND crs.message_id = cm.id
            )
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        
    } elseif ($type === 'pratica') {
        // Segna come letti tutti i messaggi di una pratica specifica
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status (user_id, conversation_id, message_id, read_at)
            SELECT ?, cm.conversation_id, cm.id, NOW()
            FROM chat_messages cm
            JOIN chat_conversations cc ON cm.conversation_id = cc.id
            WHERE cc.type = 'pratica' 
            AND cc.pratica_id = ?
            AND cm.sender_id != ?
            AND NOT EXISTS (
                SELECT 1 FROM chat_read_status crs 
                WHERE crs.user_id = ? AND crs.message_id = cm.id
            )
        ");
        $stmt->execute([$user_id, $id, $user_id, $user_id]);
        
    } elseif ($type === 'private') {
        // Segna come letti tutti i messaggi di una chat privata specifica
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status (user_id, conversation_id, message_id, read_at)
            SELECT ?, cm.conversation_id, cm.id, NOW()
            FROM chat_messages cm
            JOIN chat_conversations cc ON cm.conversation_id = cc.id
            WHERE cc.type = 'private' 
            AND cc.id = ?
            AND cm.sender_id != ?
            AND NOT EXISTS (
                SELECT 1 FROM chat_read_status crs 
                WHERE crs.user_id = ? AND crs.message_id = cm.id
            )
        ");
        $stmt->execute([$user_id, $id, $user_id, $user_id]);
        
    } else {
        throw new Exception('Tipo di chat non valido');
    }
    
    $affected_rows = $stmt->rowCount();
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'messages_marked' => $affected_rows,
        'type' => $type,
        'id' => $id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Errore mark_as_read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore interno del server']);
}
?>
