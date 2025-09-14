<?php
require_once __DIR__ . '/includes/session_fix.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

echo "<h2>Debug Sessione Chat</h2>";
echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'NON_SETTATO') . "<br>";
echo "<strong>User Name:</strong> " . ($_SESSION['user_name'] ?? 'NON_SETTATO') . "<br>";
echo "<strong>Role:</strong> " . ($_SESSION['role'] ?? 'NON_SETTATO') . "<br>";

echo "<h3>Tutte le variabili di sessione:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Widget Config:</h3>";
$user_authenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $user_authenticated ? $_SESSION['user_id'] : 'null';
$user_name = $user_authenticated ? ($_SESSION['user_name'] ?? $_SESSION['nome'] ?? 'Utente') : 'Guest';

echo "<strong>User Authenticated:</strong> " . ($user_authenticated ? 'YES' : 'NO') . "<br>";
echo "<strong>Widget User ID:</strong> " . $user_id . "<br>";
echo "<strong>Widget User Name:</strong> " . $user_name . "<br>";

echo "<script>";
echo "console.log('Session Debug:', {";
echo "  authenticated: " . ($user_authenticated ? 'true' : 'false') . ",";
echo "  userId: " . $user_id . ",";
echo "  userName: '" . addslashes($user_name) . "'";
echo "});";
echo "</script>";

echo "<br><br><a href='dashboard.php'>Torna alla Dashboard</a>";
?>
