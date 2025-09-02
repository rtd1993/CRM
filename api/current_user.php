<?php
require_once '../includes/auth.php';
require_login();

header('Content-Type: application/json');

try {
    echo json_encode([
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'] ?? '',
        'ruolo' => $_SESSION['user_ruolo'] ?? ''
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
