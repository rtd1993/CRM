<?php
// Test script per verificare le API dei widget chat
session_start();

// Simula un utente loggato (sostituisci con ID utente valido)
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';

require_once 'includes/db.php';

echo "=== TEST API CHAT WIDGETS ===" . PHP_EOL;
echo "User ID: " . $_SESSION['user_id'] . PHP_EOL;
echo "User Name: " . $_SESSION['user_name'] . PHP_EOL;
echo PHP_EOL;

// Test 1: Controlla messaggi non letti chat globale
echo "1. Test messaggi non letti chat globale:" . PHP_EOL;
$_GET['chat_type'] = 'globale';
ob_start();
include 'api/get_unread_count.php';
$response = ob_get_clean();
echo "Risposta: " . $response . PHP_EOL;
echo PHP_EOL;

// Test 2: Controlla messaggi non letti chat pratiche
echo "2. Test messaggi non letti chat pratiche (pratica_id=1):" . PHP_EOL;
$_GET['chat_type'] = 'pratica';
$_GET['pratica_id'] = 1;
ob_start();
include 'api/get_unread_count.php';
$response = ob_get_clean();
echo "Risposta: " . $response . PHP_EOL;
echo PHP_EOL;

// Test 3: Aggiorna stato lettura chat globale
echo "3. Test aggiornamento stato lettura chat globale:" . PHP_EOL;
$input = json_encode([
    'user_id' => $_SESSION['user_id'],
    'chat_type' => 'globale',
    'timestamp' => date('Y-m-d H:i:s')
]);

// Simula POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$GLOBALS['HTTP_RAW_POST_DATA'] = $input;

// Mock php://input
if (!function_exists('mock_file_get_contents')) {
    function mock_file_get_contents($filename) {
        if ($filename === 'php://input') {
            return $GLOBALS['HTTP_RAW_POST_DATA'];
        }
        return file_get_contents($filename);
    }
    
    // Temporary override
    $original_func = 'file_get_contents';
    eval('function file_get_contents_backup($f) { return ' . $original_func . '($f); }');
    eval('function file_get_contents($f) { return mock_file_get_contents($f); }');
}

ob_start();
include 'api/update_read_status.php';
$response = ob_get_clean();
echo "Risposta: " . $response . PHP_EOL;
echo PHP_EOL;

echo "=== FINE TEST ===" . PHP_EOL;
?>
