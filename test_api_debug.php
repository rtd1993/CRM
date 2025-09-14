<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'unread_counts' => [],
        'total' => 0,
        'debug' => 'No session - returning zero counts'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Test diretto delle query per debug
    $unreadCounts = [];
    
    // Chat globale (conversation_id = 1)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM messages m 
        WHERE m.conversation_id = 1 
        AND m.user_id != ? 
        AND m.created_at > COALESCE(
            (SELECT last_seen FROM user_conversation_status 
             WHERE user_id = ? AND conversation_id = 1), 
            '1970-01-01'
        )
    ");
    $stmt->execute([$user_id, $user_id]);
    $globalCount = $stmt->fetchColumn();
    
    $debug = [
        'user_id' => $user_id,
        'global_count_raw' => $globalCount,
        'global_count_added' => false
    ];
    
    if ($globalCount > 0) {
        $unreadCounts['global'] = $globalCount;
        $debug['global_count_added'] = true;
    }
    
    // Totale
    $total = array_sum($unreadCounts);
    
    echo json_encode([
        'success' => true,
        'unread_counts' => $unreadCounts,
        'total' => $total,
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => 'Exception occurred'
    ]);
}
?>
