<?php
session_start();

// Simula essere loggati come Roberto (user_id = 2) 
$_SESSION['user_id'] = 2;
$_SESSION['nome'] = 'Roberto';

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    // Connessione database
    $pdo = new PDO("mysql:host=localhost;dbname=crm;charset=utf8", 'crmuser', 'Admin123!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $current_user_id = $_SESSION['user_id'];

    // Query per ottenere tutti gli utenti tranne l'utente corrente
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome,
            email,
            ruolo
        FROM utenti 
        WHERE id != ?
        ORDER BY nome ASC
    ");
    
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Simula che Roberto (2) e Sabina (3) sono online
    $onlineUserIds = [2, 3];

    $result = [];
    
    foreach ($users as $user) {
        // Controlla se l'utente è online (usando dati fake)
        $is_online = in_array((int)$user['id'], $onlineUserIds);
        
        // Costruisci il nome completo
        $full_name = trim($user['nome']);
        if (empty($full_name)) {
            $full_name = 'Utente ' . $user['id'];
        }
        
        $result[] = [
            'id' => (int)$user['id'],
            'username' => 'user' . $user['id'],
            'name' => $full_name,
            'nome' => $full_name,
            'email' => $user['email'],
            'ruolo' => $user['ruolo'],
            'is_online' => $is_online,
            'last_activity' => null
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $result,
        'current_user_id' => $current_user_id,
        'current_user_name' => $_SESSION['nome'],
        'online_users_from_socket' => $onlineUserIds,
        'total' => count($result),
        'online_count' => count(array_filter($result, function($u) { return $u['is_online']; })),
        'debug' => 'Testing with Roberto(2) and Sabina(3) online'
    ]);

} catch (Exception $e) {
    http_response_code(500);
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
