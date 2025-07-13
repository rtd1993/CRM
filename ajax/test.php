<?php
// File di test per verificare che l'endpoint AJAX sia raggiungibile
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'AJAX endpoint raggiungibile',
    'time' => date('Y-m-d H:i:s')
]);
?>
