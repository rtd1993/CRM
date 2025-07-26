<?php
// Test della configurazione dinamica Socket.IO
require_once 'includes/config.php';

echo "<h2>🔧 Test Configurazione Socket.IO</h2>";

echo "<p><strong>Server IP:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'Non disponibile') . "</p>";
echo "<p><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Non disponibile') . "</p>";
echo "<p><strong>Socket.IO URL:</strong> " . getSocketIOUrl() . "</p>";

echo "<hr>";

echo "<h3>📋 Test connessione:</h3>";
echo "<script>";
echo "console.log('Socket.IO URL:', '" . getSocketIOUrl() . "');";
echo "fetch('" . getSocketIOUrl() . "/socket.io/?transport=polling')";
echo ".then(response => {";
echo "  console.log('Connessione Socket.IO:', response.status === 200 ? 'OK' : 'Errore');";
echo "  document.getElementById('test-result').innerHTML = response.status === 200 ? '✅ Socket.IO raggiungibile' : '❌ Socket.IO non raggiungibile';";
echo "})";
echo ".catch(err => {";
echo "  console.error('Errore connessione:', err);";
echo "  document.getElementById('test-result').innerHTML = '❌ Errore: ' + err.message;";
echo "});";
echo "</script>";

echo "<div id='test-result'>🔄 Testing...</div>";

echo "<hr>";
echo "<h3>📝 Suggerimenti:</h3>";
echo "<ul>";
echo "<li>Se vedi '✅ Socket.IO raggiungibile', la configurazione funziona</li>";
echo "<li>Se vedi '❌', verifica che il server Socket.IO sia avviato: <code>node socket.js</code></li>";
echo "<li>La chat ora funzionerà su qualsiasi IP della rete locale</li>";
echo "</ul>";

echo "<p><a href='chat.php'>🧪 Testa la chat</a></p>";
?>
