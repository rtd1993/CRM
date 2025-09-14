<?php
session_start();

// Imposta utente come offline se loggato
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/db.php';
    
    $updateStmt = $pdo->prepare("UPDATE utenti SET is_online = FALSE WHERE id = ?");
    $updateStmt->execute([$_SESSION['user_id']]);
    
    error_log("LOGOUT: User " . $_SESSION['user_id'] . " set offline");
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
