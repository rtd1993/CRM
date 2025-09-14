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
    
    $other_user_id = intval($input['other_user_id'] ?? 0);
    
    if (!$other_user_id || $other_user_id == $user_id) {
        throw new Exception('ID utente destinatario non valido');
    }
    
    // Verifica che l'altro utente esista
    $stmt = $pdo->prepare("SELECT nome FROM utenti WHERE id = ?");
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch();
    
    if (!$other_user) {
        throw new Exception('Utente destinatario non trovato');
    }
    
    // Cerca conversazione privata esistente tra i due utenti
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM conversations c
        JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
        JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
        WHERE c.type = 'private' 
        AND cp1.user_id = ? AND cp1.is_active = 1
        AND cp2.user_id = ? AND cp2.is_active = 1
    ");
    $stmt->execute([$user_id, $other_user_id]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        // Conversazione esiste già
        $conversation_id = $conversation['id'];
    } else {
        // Crea nuova conversazione privata
        $current_user_name = $_SESSION['user_name'] ?? 'Utente';
        $conversation_name = "Chat: {$current_user_name} ↔ {$other_user['nome']}";
        
        $stmt = $pdo->prepare("
            INSERT INTO conversations (name, type, created_by, created_at, updated_at) 
            VALUES (?, 'private', ?, NOW(), NOW())
        ");
        $stmt->execute([$conversation_name, $user_id]);
        $conversation_id = $pdo->lastInsertId();
        
        // Aggiungi entrambi gli utenti come partecipanti
        $stmt = $pdo->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id, is_active, joined_at) 
            VALUES (?, ?, 1, NOW()), (?, ?, 1, NOW())
        ");
        $stmt->execute([$conversation_id, $user_id, $conversation_id, $other_user_id]);
    }
    
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversation_id,
        'other_user_name' => $other_user['nome']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
