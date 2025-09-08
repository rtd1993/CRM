<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Ottieni la struttura della tabella utenti
    $stmt = $pdo->query("DESCRIBE utenti");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ottieni alcuni utenti reali
    $stmt = $pdo->query("SELECT * FROM utenti LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'table_structure' => $columns,
        'sample_users' => $users
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
