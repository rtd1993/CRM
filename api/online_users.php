<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

require_login();

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Ottieni lista utenti con stato online (mock per ora, da implementare con Redis o DB)
            $stmt = $pdo->prepare("
                SELECT id, nome, email, ruolo
                FROM utenti 
                WHERE id != ?
                ORDER BY nome ASC
            ");
            $stmt->execute([$user_id]);
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
            
            $onlineUsers = [];
            foreach ($users as $user) {
                $isOnline = in_array((int)$user['id'], $onlineUserIds);
                $onlineUsers[] = [
                    'id' => (int)$user['id'],
                    'name' => $user['nome'],
                    'email' => $user['email'],
                    'role' => $user['ruolo'],
                    'is_online' => $isOnline // Vero stato online dal Socket.IO
                ];
            }
            
            echo json_encode([
                'success' => true,
                'users' => $onlineUsers
            ]);
            break;
            
        default:
            throw new Exception('Metodo non supportato');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
