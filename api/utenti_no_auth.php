<?php
// Endpoint temporaneo per debug senza autenticazione
header('Content-Type: application/json');

try {
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    
    // Test connessione database
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utenti");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Carica alcuni utenti per test
    $stmt = $pdo->query("SELECT id, nome, email, ruolo FROM utenti LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($users as $user) {
        $full_name = trim($user['nome']);
        if (empty($full_name)) {
            $full_name = 'Utente ' . $user['id'];
        }
        
        $result[] = [
            'id' => $user['id'],
            'username' => 'user' . $user['id'],
            'name' => $full_name,
            'email' => $user['email'],
            'ruolo' => $user['ruolo'],
            'is_online' => false,
            'last_activity' => null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $result,
        'total_users' => $count['count']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
?>
