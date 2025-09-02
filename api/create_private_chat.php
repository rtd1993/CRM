<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $other_user_id = intval($input['user_id'] ?? 0);
    $current_user_id = intval($input['current_user_id'] ?? $_SESSION['user_id']);
    
    if (!$other_user_id || !$current_user_id) {
        throw new Exception('Parametri mancanti');
    }
    
    // Verifica che esistano entrambi gli utenti
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utenti WHERE id IN (?, ?)");
    $stmt->execute([$other_user_id, $current_user_id]);
    
    if ($stmt->fetchColumn() !== 2) {
        throw new Exception('Uno o entrambi gli utenti non esistono');
    }
    
    // Controlla se esiste già una chat privata tra questi utenti
    $stmt = $pdo->prepare("
        SELECT DISTINCT cc.id
        FROM chat_conversations cc
        JOIN chat_participants cp1 ON cc.id = cp1.chat_id
        JOIN chat_participants cp2 ON cc.id = cp2.chat_id
        WHERE cc.chat_type = 'privata'
        AND cp1.user_id = ? AND cp1.is_active = 1
        AND cp2.user_id = ? AND cp2.is_active = 1
        AND (
            SELECT COUNT(*) FROM chat_participants 
            WHERE chat_id = cc.id AND is_active = 1
        ) = 2
    ");
    $stmt->execute([$current_user_id, $other_user_id]);
    $existing_chat = $stmt->fetch();
    
    if ($existing_chat) {
        // Chat privata esiste già
        echo json_encode([
            'success' => true,
            'chat_id' => $existing_chat['id'],
            'existing' => true
        ]);
        exit;
    }
    
    // Crea nuova chat privata
    $pdo->beginTransaction();
    
    try {
        // Ottieni nome dell'altro utente
        $stmt = $pdo->prepare("SELECT nome FROM utenti WHERE id = ?");
        $stmt->execute([$other_user_id]);
        $other_user = $stmt->fetch();
        
        // Crea conversazione
        $stmt = $pdo->prepare("
            INSERT INTO chat_conversations (chat_type, name, created_by, created_at)
            VALUES ('privata', ?, ?, NOW())
        ");
        $stmt->execute(["Chat con " . $other_user['nome'], $current_user_id]);
        $chat_id = $pdo->lastInsertId();
        
        // Aggiungi partecipanti
        $stmt = $pdo->prepare("
            INSERT INTO chat_participants (chat_id, user_id, joined_at, is_active)
            VALUES (?, ?, NOW(), 1), (?, ?, NOW(), 1)
        ");
        $stmt->execute([$chat_id, $current_user_id, $chat_id, $other_user_id]);
        
        // Inizializza stato lettura per entrambi
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status_new (user_id, chat_id, last_read_at, unread_count)
            VALUES (?, ?, NOW(), 0), (?, ?, NOW(), 0)
        ");
        $stmt->execute([$current_user_id, $chat_id, $other_user_id, $chat_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'chat_id' => $chat_id,
            'existing' => false
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
