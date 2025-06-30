<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$cliente_id = intval($_GET['cliente_id'] ?? 0);
$out = [];

if ($cliente_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, u.nome as utente FROM pratiche_chat c JOIN utenti u ON c.user_id=u.id WHERE c.cliente_id=? ORDER BY c.data ASC");
        $stmt->execute([$cliente_id]);
        while ($row = $stmt->fetch()) {
            $out[] = [
                'utente' => $row['utente'],
                'data' => date("d/m H:i", strtotime($row['data'])),
                'testo' => htmlspecialchars($row['testo'])
            ];
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([]);
        exit;
    }
}

echo json_encode($out);
exit;