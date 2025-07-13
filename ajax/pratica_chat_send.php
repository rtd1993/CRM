<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$cliente_id = intval($_POST['cliente_id'] ?? 0);
$msg = trim($_POST['msg'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;

$out = ['ok' => false];

if ($cliente_id > 0 && $msg && $user_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_pratiche (utente_id, pratica_id, messaggio, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $cliente_id, $msg]);
        $out['ok'] = true;
    } catch (Exception $e) {
        $out['error'] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($out);