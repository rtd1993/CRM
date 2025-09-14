<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Gestione sessioni senza redirect per API
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Sessione non valida'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $client_id = intval($input['client_id'] ?? 0);
    
    if (!$client_id) {
        throw new Exception('Client ID mancante');
    }
    
    // Verifica che il cliente esista
    $stmt = $pdo->prepare("SELECT Cognome_Ragione_sociale FROM clienti WHERE id = ?");
    $stmt->execute([$client_id]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        throw new Exception('Cliente non trovato');
    }
    
    // Cerca conversazione esistente per questo cliente (tipo pratica)
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM conversations c
        WHERE c.type = 'pratica' 
        AND c.client_id = ?
    ");
    $stmt->execute([$client_id]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        // Conversazione esiste già
        $conversation_id = $conversation['id'];
    } else {
        // Crea nuova conversazione pratica
        $conversation_name = "Pratica - " . $cliente['Cognome_Ragione_sociale'];
        
        $stmt = $pdo->prepare("
            INSERT INTO conversations (name, type, client_id, created_at, updated_at) 
            VALUES (?, 'pratica', ?, NOW(), NOW())
        ");
        $stmt->execute([$conversation_name, $client_id]);
        $conversation_id = $pdo->lastInsertId();
        
        // Aggiungi l'utente corrente come partecipante
        $stmt = $pdo->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id, is_active, joined_at) 
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute([$conversation_id, $user_id]);
        
        // Aggiungi tutti gli admin come partecipanti (opzionale)
        $stmt = $pdo->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id, is_active, joined_at)
            SELECT ?, id, 1, NOW() 
            FROM utenti 
            WHERE ruolo = 'admin' AND id != ?
        ");
        $stmt->execute([$conversation_id, $user_id]);
    }
    
    // Assicurati che l'utente corrente sia un partecipante
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO conversation_participants (conversation_id, user_id, is_active, joined_at) 
        VALUES (?, ?, 1, NOW())
    ");
    $stmt->execute([$conversation_id, $user_id]);
    
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversation_id,
        'client_name' => $cliente['Cognome_Ragione_sociale']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
