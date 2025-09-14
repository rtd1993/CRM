<?php
// File: api/cleanup_offline_users.php
// Imposta offline gli utenti inattivi da più di 5 minuti

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    // Imposta offline utenti inattivi da più di 5 minuti
    $stmt = $pdo->prepare("
        UPDATE utenti 
        SET is_online = FALSE 
        WHERE is_online = TRUE 
        AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'users_set_offline' => $affected,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
