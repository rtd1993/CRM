<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Test delle tabelle del database chat
    $tables_to_check = [
        'chat_conversations',
        'chat_messages', 
        'chat_participants',
        'utenti'
    ];
    
    $table_status = [];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $table_status[$table] = [
                'exists' => true,
                'columns' => $columns
            ];
        } catch (Exception $e) {
            $table_status[$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'current_user' => $_SESSION['user_id'],
        'tables' => $table_status
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
