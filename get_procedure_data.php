<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => '', 'procedure' => null];

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        $response['error'] = 'ID procedura non valido.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM procedure_crm WHERE id = ?");
        $stmt->execute([$id]);
        $procedure_data = $stmt->fetch();
        
        if ($procedure_data) {
            // Debug log
            error_log('Procedure data retrieved: ' . print_r($procedure_data, true));
            $response['success'] = true;
            $response['procedure'] = $procedure_data;
        } else {
            $response['error'] = 'Procedura non trovata.';
        }
    }
} catch (Exception $e) {
    $response['error'] = 'Errore del database: ' . $e->getMessage();
}

echo json_encode($response);
?>
