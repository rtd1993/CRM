<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

try {
    $stmt = $pdo->prepare("SELECT id, `cognome/ragione sociale` as cognome_ragione_sociale, nome FROM clienti ORDER BY `cognome/ragione sociale`, nome");
    $stmt->execute();
    $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($clienti);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
