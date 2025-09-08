<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Debug informazioni
    $debug = [
        'session_user_id' => $_SESSION['user_id'] ?? 'non_set',
        'session_user_name' => $_SESSION['user_name'] ?? 'non_set',
        'pdo_connection' => $pdo ? 'OK' : 'FAILED'
    ];
    
    // Test query semplice
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utenti");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug['users_count'] = $count['count'];
    
    // Test query utenti
    $stmt = $pdo->prepare("SELECT id, nome, cognome, email, ruolo FROM utenti LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'debug' => $debug,
        'sample_users' => $users
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug ?? null
    ]);
}
?>
