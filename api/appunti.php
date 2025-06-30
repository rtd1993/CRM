<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$pratica_id = isset($_GET['pratica_id']) ? intval($_GET['pratica_id']) : 0;
if ($pratica_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID pratica non valido']);
    exit;
}

$stmt = $pdo->prepare("SELECT a.testo, a.data_inserimento, u.nome 
                       FROM appunti a 
                       JOIN utenti u ON a.utente_id = u.id 
                       WHERE a.pratica_id = ? 
                       ORDER BY a.data_inserimento ASC");
$stmt->execute([$pratica_id]);
$appunti = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($appunti);
