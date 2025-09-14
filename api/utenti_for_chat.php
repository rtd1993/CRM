<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Connessione database diretta
    $pdo = new PDO("mysql:host=localhost;dbname=crm;charset=utf8", 'crmuser', 'Admin123!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ottieni user_id da GET parameter o sessione
    session_start();
    $current_user_id = null;
    
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $current_user_id = intval($_GET['user_id']);
    } elseif (isset($_SESSION['user_id'])) {
        $current_user_id = $_SESSION['user_id'];
    } else {
        throw new Exception('Nessun utente identificato');
    }

    // Query per ottenere tutti gli utenti tranne l'utente corrente
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome,
            email,
            ruolo,
            is_online
        FROM utenti 
        WHERE id != ?
        ORDER BY nome ASC
    ");
    
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ottieni utenti online da Socket.IO per debug, ma usa database come fonte primaria
    $socketOnlineUsers = [];
    try {
        $context = stream_context_create(['http' => ['timeout' => 1]]);
        $response = @file_get_contents('http://localhost:3002/online-users', false, $context);
        if ($response) {
            $onlineData = json_decode($response, true);
            if ($onlineData && $onlineData['success']) {
                $socketOnlineUsers = $onlineData['online_users'];
            }
        }
    } catch (Exception $e) {
        // Ignora errori Socket.IO, usa solo database
    }

    $result = [];
    
    foreach ($users as $user) {
        // Usa lo stato online dal database (fonte primaria)
        $is_online = (bool)$user['is_online'];
        
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
        'users' => $result,
        'current_user_id' => $current_user_id,
        'socket_online_users' => $socketOnlineUsers,
        'total' => count($result),
        'online_count' => count(array_filter($result, function($u) { return $u['is_online']; })),
        'source' => 'database'
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
