<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    // Query per contare gli utenti online dal database
    $stmt = $pdo->prepare("SELECT COUNT(*) as online_count FROM utenti WHERE is_online = TRUE");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $onlineCount = $result['online_count'];
    
    echo json_encode([
        'success' => true,
        'online_count' => $onlineCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>