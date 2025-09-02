<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $pratica_id = intval($_GET['pratica_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (!$pratica_id) {
        // Chat pratiche generale
        $chat_id = 1; // Chat globale per ora
        echo json_encode([
            'success' => true,
            'chat_id' => $chat_id,
            'name' => 'Chat Pratiche Generale',
            'type' => 'globale'
        ]);
        exit;
    }
    
    // Verifica che la pratica/cliente esista
    $stmt = $pdo->prepare("SELECT Cognome_Ragione_sociale FROM clienti WHERE id = ?");
    $stmt->execute([$pratica_id]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        throw new Exception('Pratica/Cliente non trovato');
    }
    
    // Cerca chat esistente per questa pratica
    $stmt = $pdo->prepare("
        SELECT id, name FROM chat_conversations 
        WHERE chat_type = 'pratica' AND pratica_id = ?
    ");
    $stmt->execute([$pratica_id]);
    $existing_chat = $stmt->fetch();
    
    if ($existing_chat) {
        // Chat esiste già
        echo json_encode([
            'success' => true,
            'chat_id' => $existing_chat['id'],
            'name' => $existing_chat['name'],
            'type' => 'pratica',
            'pratica_id' => $pratica_id
        ]);
        exit;
    }
    
    // Crea nuova chat per la pratica
    $pdo->beginTransaction();
    
    try {
        $chat_name = "Pratica " . $cliente['Cognome_Ragione_sociale'];
        
        // Crea conversazione
        $stmt = $pdo->prepare("
            INSERT INTO chat_conversations (chat_type, pratica_id, name, created_by, created_at)
            VALUES ('pratica', ?, ?, ?, NOW())
        ");
        $stmt->execute([$pratica_id, $chat_name, $user_id]);
        $chat_id = $pdo->lastInsertId();
        
        // Aggiungi tutti gli utenti attivi come partecipanti
        $stmt = $pdo->prepare("
            INSERT INTO chat_participants (chat_id, user_id, joined_at, is_active)
            SELECT ?, id, NOW(), 1 FROM utenti WHERE id > 0
        ");
        $stmt->execute([$chat_id]);
        
        // Inizializza stato lettura per tutti
        $stmt = $pdo->prepare("
            INSERT INTO chat_read_status_new (user_id, chat_id, last_read_at, unread_count)
            SELECT id, ?, NOW(), 0 FROM utenti WHERE id > 0
        ");
        $stmt->execute([$chat_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'chat_id' => $chat_id,
            'name' => $chat_name,
            'type' => 'pratica',
            'pratica_id' => $pratica_id
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
