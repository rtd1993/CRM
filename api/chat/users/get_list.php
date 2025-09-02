<?php
/**
 * API Endpoint: /api/chat/users/get_list.php
 * Descrizione: Restituisce la lista degli utenti disponibili per chat private
 * Metodo: POST
 * Autenticazione: Richiesta
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Non autenticato'
    ]);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {
    // Recupera tutti gli utenti tranne quello corrente
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.nome,
            u.ruolo,
            u.email,
            CASE 
                WHEN us.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                THEN 1 
                ELSE 0 
            END as is_online,
            us.last_activity
        FROM utenti u
        LEFT JOIN user_sessions us ON u.id = us.user_id
        WHERE u.id != ?
        ORDER BY 
            is_online DESC,
            u.nome ASC
    ");
    
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatta i dati per il frontend
    $formatted_users = [];
    foreach ($users as $user) {
        $formatted_users[] = [
            'id' => (int)$user['id'],
            'name' => htmlspecialchars($user['nome']),
            'role' => htmlspecialchars($user['ruolo']),
            'email' => htmlspecialchars($user['email']),
            'is_online' => (bool)$user['is_online'],
            'last_activity' => $user['last_activity'],
            'avatar_url' => null // Placeholder per future implementazioni
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $formatted_users,
            'total' => count($formatted_users),
            'online_count' => count(array_filter($formatted_users, function($u) { return $u['is_online']; }))
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Errore API get_list.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore interno del server'
    ]);
}
?>
