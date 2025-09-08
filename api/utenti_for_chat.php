<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Controllo autenticazione
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Non autenticato');
    }

    $current_user_id = $_SESSION['user_id'];

    // Query per ottenere tutti gli utenti tranne l'utente corrente
    $stmt = $pdo->prepare("
        SELECT 
            id,
            username,
            nome,
            cognome,
            email,
            ruolo,
            last_activity
        FROM utenti 
        WHERE id != ? 
        AND status = 'active'
        ORDER BY 
            CASE 
                WHEN last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 
                ELSE 0 
            END DESC,
            nome ASC,
            cognome ASC
    ");
    
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    
    foreach ($users as $user) {
        // Determina se l'utente è online (ultima attività negli ultimi 5 minuti)
        $is_online = false;
        if ($user['last_activity']) {
            $last_activity = new DateTime($user['last_activity']);
            $now = new DateTime();
            $diff = $now->getTimestamp() - $last_activity->getTimestamp();
            $is_online = $diff < 300; // 5 minuti
        }
        
        // Costruisci il nome completo
        $full_name = trim($user['nome'] . ' ' . $user['cognome']);
        if (empty($full_name)) {
            $full_name = $user['username'];
        }
        
        $result[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $full_name,
            'email' => $user['email'],
            'ruolo' => $user['ruolo'],
            'is_online' => $is_online,
            'last_activity' => $user['last_activity']
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
        'error' => $e->getMessage()
    ]);
}
?>
