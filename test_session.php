<?php
// Test sessioni PHP
session_start();

echo "<h2>Test Sessioni PHP</h2>";

// Test simulazione login
if (isset($_GET['simulate_login'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['role'] = 'admin';
    echo "<p style='background: green; color: white; padding: 10px;'>✅ Simulazione login eseguita!</p>";
}

// Imposta un valore di test
if (!isset($_SESSION['test_value'])) {
    $_SESSION['test_value'] = 'test_' . time();
    $_SESSION['counter'] = 1;
    echo "<p>✅ Sessione inizializzata</p>";
} else {
    $_SESSION['counter']++;
    echo "<p>✅ Sessione esistente trovata</p>";
}

echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px;'>";
echo "<h3>Informazioni Sessione:</h3>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>Session Save Path:</strong> " . session_save_path() . "<br>";
echo "<strong>Session Cookie Params:</strong> " . print_r(session_get_cookie_params(), true) . "<br>";
echo "<strong>Session Data:</strong><pre>" . print_r($_SESSION, true) . "</pre>";
echo "<strong>All Cookies:</strong><pre>" . print_r($_COOKIE, true) . "</pre>";
echo "</div>";

// Test scrittura/lettura
$_SESSION['timestamp'] = date('Y-m-d H:i:s');
echo "<p>Timestamp aggiunto alla sessione: " . $_SESSION['timestamp'] . "</p>";

// Link per testare
echo "<p><a href='test_session.php'>Ricarica per testare persistenza</a></p>";
echo "<p><a href='test_session.php?simulate_login=1'>🔥 SIMULA LOGIN (aggiungi user_id alla sessione)</a></p>";
echo "<p><a href='test_login_direct.php'>🔧 TEST LOGIN DIRETTO (con database reale)</a></p>";
echo "<p><a href='dashboard-test.php'>Test Dashboard dopo simulazione login</a></p>";
echo "<p><a href='login.php'>Torna al Login</a></p>";
?>
