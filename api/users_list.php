<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Recupera lista utenti con stato online/offline
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome as name,
            email,
            ruolo,
            0 as online
        FROM utenti 
        WHERE id != ?
        ORDER BY nome ASC
    ");
    $stmt->execute([$user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Implementare logica per determinare stato online
    // Per ora tutti offline, sarà aggiornato via Socket.IO
    
    echo json_encode($users);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
