<?php
// Debug Socket.IO URL generation
require_once __DIR__ . '/includes/config.php';

echo "Debug Socket.IO URL:\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SERVER_ADDR: " . ($_SERVER['SERVER_ADDR'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
echo "strpos result: " . (isset($_SERVER['HTTP_HOST']) ? strpos($_SERVER['HTTP_HOST'], 'ascontabilmente.homes') : 'HTTP_HOST not set') . "\n";
echo "getSocketIOUrl(): " . getSocketIOUrl() . "\n";
?>
