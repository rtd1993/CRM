<?php
// API per ricaricare lista clienti dinamicamente
header('Content-Type: application/json');
require_once '../config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, Cognome_Ragione_sociale AS nome FROM clienti ORDER BY Cognome_Ragione_sociale ASC");
    $stmt->execute();
    $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clienti' => $clienti,
        'total' => count($clienti)
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore database: ' . $e->getMessage()]);
}
?>
