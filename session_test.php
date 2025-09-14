<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$response = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'is_logged_in' => is_logged_in(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'cookies' => $_COOKIE,
    'is_ajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
