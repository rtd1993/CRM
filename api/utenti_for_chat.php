<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
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

    // Ottieni utenti realmente online dal server Socket.IO
    $onlineUserIds = [];
    try {
        $context = stream_context_create(['http' => ['timeout' => 2]]);
        $response = file_get_contents('http://localhost:3002/online-users', false, $context);
        if ($response) {
            $onlineData = json_decode($response, true);
            if ($onlineData && $onlineData['success']) {
                $onlineUserIds = $onlineData['online_users'];
            }
        }
    } catch (Exception $e) {
        error_log("Errore connessione Socket.IO server: " . $e->getMessage());
    }

    $result = [];
    
    foreach ($users as $user) {
        // Controlla se l'utente è realmente online
        $is_online = in_array((int)$user['id'], $onlineUserIds);
        
        // Costruisci il nome completo
        $full_name = trim($user['nome']);
        if (empty($full_name)) {
            $full_name = 'Utente ' . $user['id'];
        }
        
        $result[] = [
            'id' => $user['id'],
            'username' => 'user' . $user['id'], // Genera un username basato sull'ID
            'name' => $full_name,
            'email' => $user['email'],
            'ruolo' => $user['ruolo'],
            'is_online' => $is_online,
            'last_activity' => null
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
