<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

try {
    $user_id = $_SESSION['user_id'];
    
    // Inizializza contatori
    $counts = [
        'globale' => 0,
        'pratiche' => 0,
        'private' => 0,
        'total' => 0
    ];
    
    // 1. Conta messaggi non letti chat globale
    $stmt = $pdo->prepare("
        SELECT COALESCE(rs.unread_count, 0) as unread_count
        FROM chat_conversations c
        LEFT JOIN chat_read_status rs ON c.id = rs.conversation_id AND rs.user_id = ?
        WHERE c.type = 'globale'
        ORDER BY c.id ASC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if ($result) {
        $counts['globale'] = (int)$result['unread_count'];
    }
    
    // 2. Conta messaggi non letti chat pratiche (somma di tutte le pratiche)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(rs.unread_count), 0) as total_unread
        FROM chat_conversations c
        LEFT JOIN chat_read_status rs ON c.id = rs.conversation_id AND rs.user_id = ?
        WHERE c.type = 'pratica'
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if ($result) {
        $counts['pratiche'] = (int)$result['total_unread'];
    }
    
    // 3. Conta messaggi non letti chat private (somma di tutte le private)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(rs.unread_count), 0) as total_unread
        FROM chat_conversations c
        LEFT JOIN chat_read_status rs ON c.id = rs.conversation_id AND rs.user_id = ?
        WHERE c.type = 'privata' 
        AND (c.user1_id = ? OR c.user2_id = ?)
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $result = $stmt->fetch();
    if ($result) {
        $counts['private'] = (int)$result['total_unread'];
    }
    
    // Calcola totale
    $counts['total'] = $counts['globale'] + $counts['pratiche'] + $counts['private'];
    
    echo json_encode([
        'success' => true,
        'counts' => $counts,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
