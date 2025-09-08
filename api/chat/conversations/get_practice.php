<?php
/**
 * API Chat - Get or Create Practice Conversation
 * Trova o crea una conversazione per una pratica cliente
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_login();
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$user_id = $_SESSION['user_id'];

try {
    // Leggi input
    $input = json_decode(file_get_contents('php://input'), true);
    $client_id = isset($input['client_id']) ? (int)$input['client_id'] : null;
    
    if (!$client_id) {
        echo json_encode(['success' => false, 'error' => 'client_id richiesto']);
        exit;
    }
    
    // Verifica che il cliente esista
    $stmt = $pdo->prepare("SELECT Cognome_Ragione_sociale FROM clienti WHERE id = ?");
    $stmt->execute([$client_id]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        echo json_encode(['success' => false, 'error' => 'Cliente non trovato']);
        exit;
    }
    
    // Cerca conversazione esistente per questo cliente
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM chat_conversations 
        WHERE type = 'pratica' 
        AND client_id = ? 
        AND is_active = 1
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$client_id]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        // Conversazione esistente trovata
        echo json_encode([
            'success' => true,
            'conversation_id' => (int)$conversation['id'],
            'conversation_name' => $conversation['name'],
            'action' => 'found'
        ]);
        exit;
    }
    
    // Crea nuova conversazione pratica
    $conversation_name = "Pratica: " . $cliente['Cognome_Ragione_sociale'];
    
    $stmt = $pdo->prepare("
        INSERT INTO chat_conversations 
        (type, name, client_id, created_by, created_at) 
        VALUES ('pratica', ?, ?, ?, NOW())
    ");
    $stmt->execute([$conversation_name, $client_id, $user_id]);
    
    $conversation_id = $pdo->lastInsertId();
    
    // Aggiungi l'utente corrente come partecipante
    $stmt = $pdo->prepare("
        INSERT INTO chat_participants 
        (conversation_id, user_id, joined_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$conversation_id, $user_id]);
    
    // Opzionalmente, aggiungi tutti gli utenti alla conversazione pratica
    // (per permettere a tutti di vedere/contribuire alle pratiche)
    $stmt = $pdo->prepare("
        INSERT INTO chat_participants (conversation_id, user_id, joined_at)
        SELECT ?, u.id, NOW()
        FROM utenti u 
        WHERE u.id != ? 
        AND u.id NOT IN (
            SELECT user_id FROM chat_participants 
            WHERE conversation_id = ?
        )
    ");
    $stmt->execute([$conversation_id, $user_id, $conversation_id]);
    
    echo json_encode([
        'success' => true,
        'conversation_id' => (int)$conversation_id,
        'conversation_name' => $conversation_name,
        'action' => 'created'
    ]);
    
} catch (Exception $e) {
    error_log("Errore Chat get_practice: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore del server: ' . $e->getMessage()
    ]);
}
?>
