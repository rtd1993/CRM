<?php
session_start();

echo "<!DOCTYPE html>";
echo "<html><head><title>Dashboard Ultra Basic</title></head><body>";

echo "<h1>Dashboard Ultra Basilare</h1>";

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";

echo "<h3>Dati Sessione:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='background: green; color: white; padding: 10px;'>✅ SUCCESSO! User ID trovato: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='background: red; color: white; padding: 10px;'>❌ ERRORE! user_id non trovato</p>";
}

echo "<p><a href='test_session.php'>Torna al Test Sessioni</a></p>";

echo "</body></html>";
?>
