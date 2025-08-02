<?php
require_once 'includes/email_config.php';

$result = inviaEmailSMTP(
    'gestione.ascontabilmente@gmail.com',
    'Test Veloce',
    'Test - ' . date('H:i:s'),
    'Messaggio di test veloce',
    false
);

echo json_encode($result);
?>
