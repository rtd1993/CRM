<?php
/**
 * API Endpoint: /api/chat/messages/send.php
 * Descrizione: Invia un nuovo messaggio in una conversazione
 * Metodo: POST
 * Parametri: conversation_id, content, [practice_id per chat pratiche]
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

// Leggi i dati POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Dati POST mancanti'
    ]);
    exit;
}

// Supporta sia conversation_type che type per compatibilità
$conversation_type = $input['conversation_type'] ?? $input['type'] ?? null;
$conversation_id = $input['conversation_id'] ?? $input['id'] ?? null;
$message = $input['message'] ?? $input['content'] ?? '';
$practice_id = $input['practice_id'] ?? null;

if (!$conversation_type || !$message) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Tipo conversazione e messaggio richiesti'
    ]);
    exit;
}

$content = trim($message);

// Validazione lunghezza messaggio
if (strlen($content) > 1000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Messaggio troppo lungo (massimo 1000 caratteri)'
    ]);
    exit;
}

try {
    // DEBUG: Simulazione invio messaggio
    $message_id = rand(1000, 9999);
    $timestamp = date('Y-m-d H:i:s');
    
    // Crea oggetto messaggio simulato
    $new_message = [
        'id' => $message_id,
        'user_id' => $current_user_id,
        'user_name' => $_SESSION['username'] ?? 'User',
        'user_role' => $_SESSION['user_role'] ?? 'user',
        'content' => htmlspecialchars($content),
        'created_at' => $timestamp,
        'is_mine' => true
    ];
    
    // Log per debug
    error_log("DEBUG send.php - User: $current_user_id, Type: $conversation_type, Message: " . substr($content, 0, 50));
    
    echo json_encode([
        'success' => true,
        'message' => $new_message,
        'conversation_type' => $conversation_type,
        'conversation_id' => $conversation_id,
        'debug' => [
            'user_id' => $current_user_id,
            'input' => $input,
            'timestamp' => $timestamp
        ]
    ]);
    
    // Se non è specificata una conversazione, determinala dal contesto
    if (!$conversation_id) {
        if ($practice_id) {
            // Chat pratica - trova o crea conversazione per questo cliente
            $stmt = $pdo->prepare("
                SELECT id FROM chat_conversations 
                WHERE type = 'practice' AND practice_id = ?
            ");
            $stmt->execute([$practice_id]);
            $conv = $stmt->fetch();
            
            if (!$conv) {
                // Crea nuova conversazione pratica
                $stmt = $pdo->prepare("
                    INSERT INTO chat_conversations (name, type, practice_id, created_by) 
                    VALUES (?, 'practice', ?, ?)
                ");
                $client_name = "Cliente #" . $practice_id; // Placeholder, andrebbe recuperato dal DB
                $stmt->execute(["Chat " . $client_name, $practice_id, $current_user_id]);
                $conversation_id = $pdo->lastInsertId();
                
                // Aggiungi tutti gli utenti come partecipanti
                $stmt = $pdo->prepare("
                    INSERT INTO chat_participants (conversation_id, user_id, joined_at)
                    SELECT ?, id, NOW() FROM utenti
                ");
                $stmt->execute([$conversation_id]);
            } else {
                $conversation_id = $conv['id'];
            }
        } else {
            // Chat globale (ID fisso = 1)
            $conversation_id = 1;
        }
    }
    
    // Verifica che l'utente sia partecipante della conversazione
    $stmt = $pdo->prepare("
        SELECT 1 FROM chat_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $current_user_id]);
    
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accesso negato alla conversazione'
        ]);
        exit;
    }
    
    // Inserisci il messaggio
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (conversation_id, user_id, content, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$conversation_id, $current_user_id, $content]);
    $message_id = $pdo->lastInsertId();
    
    // Aggiorna il timestamp della conversazione
    $stmt = $pdo->prepare("
        UPDATE chat_conversations 
        SET updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$conversation_id]);
    
    // Marca il messaggio come letto per il mittente
    $stmt = $pdo->prepare("
        INSERT INTO chat_read_status (message_id, user_id, read_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$message_id, $current_user_id]);
    
    $pdo->commit();
    
    // Recupera i dati del messaggio appena inserito
    $stmt = $pdo->prepare("
        SELECT 
            cm.id,
            cm.content,
            cm.created_at,
            cm.user_id,
            u.nome as user_name,
            u.ruolo as user_role
        FROM chat_messages cm
        INNER JOIN utenti u ON cm.user_id = u.id
        WHERE cm.id = ?
    ");
    $stmt->execute([$message_id]);
    $message_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => [
                'id' => (int)$message_data['id'],
                'content' => htmlspecialchars($message_data['content']),
                'created_at' => $message_data['created_at'],
                'user' => [
                    'id' => (int)$message_data['user_id'],
                    'name' => htmlspecialchars($message_data['user_name']),
                    'role' => htmlspecialchars($message_data['user_role'])
                ],
                'is_mine' => true
            ],
            'conversation_id' => (int)$conversation_id
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API send.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server',
        'debug' => $e->getMessage()
    ]);
}
?>
