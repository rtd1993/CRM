<?php
// File: api/session_heartbeat.php
// Mantiene attiva la sessione e aggiorna l'ultimo accesso

session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No session']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Aggiorna ultimo accesso e mantieni online
    $stmt = $pdo->prepare("UPDATE utenti SET is_online = TRUE, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
    
    echo json_encode([
        'success' => true,
        'user_id' => $user_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
