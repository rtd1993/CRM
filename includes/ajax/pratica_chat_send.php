<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$data = json_decode(file_get_contents('php://input'), true);
$cliente_id = intval($data['cliente_id'] ?? 0);
$testo = trim($data['testo'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;

$out = ['success' => false];

if ($cliente_id > 0 && $testo && $user_id) {
    $stmt = $pdo->prepare("INSERT INTO pratiche_chat (cliente_id, user_id, testo, data) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$cliente_id, $user_id, $testo]);
    $out['success'] = true;
}

header('Content-Type: application/json');
echo json_encode($out);