<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Connessione database
    $pdo = new PDO("mysql:host=localhost;dbname=crm;charset=utf8", 'crmuser', 'Admin123!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ottieni tutti gli utenti
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome,
            email,
            ruolo
        FROM utenti 
        ORDER BY nome ASC
    ");
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ottieni utenti online da Socket.IO
    $onlineUserIds = [];
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'method' => 'GET',
                'header' => "User-Agent: CRM-API\r\n"
            ]
        ]);
        $response = @file_get_contents('http://localhost:3002/online-users', false, $context);
        if ($response) {
            $onlineData = json_decode($response, true);
            if ($onlineData && isset($onlineData['success']) && $onlineData['success']) {
                $onlineUserIds = $onlineData['online_users'] ?? [];
            }
        }
    } catch (Exception $e) {
        error_log("Socket.IO connection error: " . $e->getMessage());
        // Continua senza errore se Socket.IO non è disponibile
    }

    $result = [];
    
    foreach ($users as $user) {
        // Controlla se l'utente è online
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
            'nome' => $full_name, // Per compatibilità
            'email' => $user['email'] ?? '',
            'ruolo' => $user['ruolo'] ?? 'employee',
            'is_online' => $is_online,
            'last_activity' => null
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $result,
        'total' => count($result),
        'online_count' => count(array_filter($result, function($u) { return $u['is_online']; })),
        'debug' => [
            'socket_online_ids' => $onlineUserIds,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection error',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'General error',
        'debug' => $e->getMessage()
    ]);
}
?>
