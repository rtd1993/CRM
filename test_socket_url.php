<?php
require_once __DIR__ . '/includes/config.php';

// Set the HTTP_HOST to simulate being accessed via the domain
$_SERVER['HTTP_HOST'] = 'ascontabilmente.homes';

echo "Current HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "Socket.IO URL: " . getSocketIOUrl() . "\n";
echo "URL contains ascontabilmente.homes: " . (strpos($_SERVER['HTTP_HOST'], 'ascontabilmente.homes') !== false ? 'YES' : 'NO') . "\n";

// Test what would be output in JavaScript
echo "\nJavaScript output would be:\n";
echo "const socket = io('" . getSocketIOUrl() . "');\n";
?>
