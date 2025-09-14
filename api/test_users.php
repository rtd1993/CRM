<?php
// Test semplice per debugging utenti
header('Content-Type: application/json');

try {
    // Connessione database base
    $host = 'localhost';
    $dbname = 'crm';
    $username = 'crmuser';
    $password = 'Admin123!';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query semplice utenti
    $stmt = $pdo->prepare("SELECT id, nome, email, ruolo FROM utenti LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ottieni utenti online da Socket.IO
    $onlineUserIds = [];
    try {
        $context = stream_context_create(['http' => ['timeout' => 1]]);
        $response = file_get_contents('http://localhost:3002/online-users', false, $context);
        if ($response) {
            $onlineData = json_decode($response, true);
            if ($onlineData && $onlineData['success']) {
                $onlineUserIds = $onlineData['online_users'];
            }
        }
    } catch (Exception $e) {
        // Ignora errori Socket.IO per test
    }

    $result = [];
    foreach ($users as $user) {
        $result[] = [
            'id' => $user['id'],
            'name' => $user['nome'],
            'email' => $user['email'],
            'ruolo' => $user['ruolo'],
            'is_online' => in_array((int)$user['id'], $onlineUserIds)
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $result,
        'online_users_from_socket' => $onlineUserIds,
        'debug' => 'Test API working'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]
    ]);
}
?>
