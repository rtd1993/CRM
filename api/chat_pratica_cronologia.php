<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

$pratica_id = intval($_GET['pratica_id'] ?? 0);
if ($pratica_id === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.messaggio, a.timestamp, u.nome
    FROM chat_pratiche a
    JOIN utenti u ON a.utente_id = u.id
    WHERE a.pratica_id = ?
    ORDER BY a.timestamp ASC
");
$stmt->execute([$pratica_id]);
$risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($risultati);
