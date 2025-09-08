<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Leggi il file SQL per la creazione delle tabelle chat
    $sql_file = __DIR__ . '/../install_chat_database.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception('File SQL non trovato: ' . $sql_file);
    }
    
    $sql_content = file_get_contents($sql_file);
    
    if (!$sql_content) {
        throw new Exception('Impossibile leggere il file SQL');
    }
    
    // Esegui le query SQL
    $statements = explode(';', $sql_content);
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tabelle chat installate',
        'executed_statements' => $executed,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
