<?php
// Simula più utenti online per testare la visualizzazione
header('Content-Type: application/json');

// Simula che Roberto (2) e Sabina (3) sono online
$fakeOnlineUsers = [2, 3];

echo json_encode([
    'success' => true,
    'online_users' => $fakeOnlineUsers,
    'total_online' => count($fakeOnlineUsers),
    'debug' => 'Fake data for testing'
]);
?>
