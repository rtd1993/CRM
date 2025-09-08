<?php
// Test configurazione PHP sessioni
session_start(); // IMPORTANTE: session_start() PRIMA di session_id()

echo "<h1>Configurazione PHP Sessioni</h1>";

echo "<h3>Informazioni PHP Session:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><td><strong>session.save_handler</strong></td><td>" . ini_get('session.save_handler') . "</td></tr>";
echo "<tr><td><strong>session.save_path</strong></td><td>" . ini_get('session.save_path') . "</td></tr>";
echo "<tr><td><strong>session.use_cookies</strong></td><td>" . ini_get('session.use_cookies') . "</td></tr>";
echo "<tr><td><strong>session.use_only_cookies</strong></td><td>" . ini_get('session.use_only_cookies') . "</td></tr>";
echo "<tr><td><strong>session.cookie_lifetime</strong></td><td>" . ini_get('session.cookie_lifetime') . "</td></tr>";
echo "<tr><td><strong>session.cookie_path</strong></td><td>" . ini_get('session.cookie_path') . "</td></tr>";
echo "<tr><td><strong>session.cookie_domain</strong></td><td>" . ini_get('session.cookie_domain') . "</td></tr>";
echo "<tr><td><strong>session.cookie_secure</strong></td><td>" . ini_get('session.cookie_secure') . "</td></tr>";
echo "<tr><td><strong>session.cookie_httponly</strong></td><td>" . ini_get('session.cookie_httponly') . "</td></tr>";
echo "<tr><td><strong>session.gc_probability</strong></td><td>" . ini_get('session.gc_probability') . "</td></tr>";
echo "<tr><td><strong>session.gc_divisor</strong></td><td>" . ini_get('session.gc_divisor') . "</td></tr>";
echo "<tr><td><strong>session.gc_maxlifetime</strong></td><td>" . ini_get('session.gc_maxlifetime') . "</td></tr>";
echo "</table>";

$save_path = ini_get('session.save_path');
echo "<h3>Test cartella sessioni:</h3>";
echo "<p><strong>Percorso:</strong> $save_path</p>";

echo "<h3>Session corrente:</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";

if (is_dir($save_path)) {
    echo "<p>✅ Cartella esistente</p>";
    if (is_writable($save_path)) {
        echo "<p>✅ Cartella scrivibile</p>";
    } else {
        echo "<p>❌ Cartella NON scrivibile</p>";
    }
    
    // Lista file sessioni
    $files = glob($save_path . '/sess_*');
    echo "<p><strong>File sessioni trovati:</strong> " . count($files) . "</p>";
    if (count($files) > 0) {
        echo "<ul>";
        foreach (array_slice($files, 0, 5) as $file) {
            echo "<li>" . basename($file) . " (size: " . filesize($file) . " bytes, modified: " . date('Y-m-d H:i:s', filemtime($file)) . ")</li>";
        }
        echo "</ul>";
    }
    
    // Test scrittura sessione
    $_SESSION['test_config'] = 'test_value_' . time();
    echo "<p>✅ Valore test aggiunto alla sessione</p>";
    
    // Cerca il nostro file di sessione
    $our_session_file = $save_path . '/sess_' . session_id();
    if (file_exists($our_session_file)) {
        echo "<p>✅ Il nostro file di sessione esiste: " . basename($our_session_file) . "</p>";
        echo "<p><strong>Contenuto:</strong> " . file_get_contents($our_session_file) . "</p>";
    } else {
        echo "<p>❌ Il nostro file di sessione NON esiste: sess_" . session_id() . "</p>";
    }
} else {
    echo "<p>❌ Cartella NON esistente</p>";
}

echo "<p><strong>Session Data:</strong><pre>" . print_r($_SESSION, true) . "</pre></p>";

echo "<p><a href='test_session.php'>Torna al Test Sessioni</a></p>";
?>
