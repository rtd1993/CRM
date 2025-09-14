<?php
require_once '../config.php';

// Imposta headers per permettere richieste da beforeunload
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Connessione al database
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Imposta l'utente come offline
    $stmt = $pdo->prepare("UPDATE utenti SET is_online = FALSE, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Utente impostato offline per chiusura finestra',
        'user_id' => $user_id
    ]);
    
} catch(PDOException $e) {
    error_log("Errore session_end: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore database: ' . $e->getMessage()]);
}
?>
