<?php
// Test semplice per debug gestione template
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Test Template Debug</h2>";

try {
    // Test connessione database
    echo "1. Test connessione database: ";
    $pdo->query("SELECT 1");
    echo "✅ OK<br>";
    
    // Test tabella email_templates
    echo "2. Test tabella email_templates: ";
    $result = $pdo->query("SELECT COUNT(*) as count FROM email_templates");
    $count = $result->fetch()['count'];
    echo "✅ OK - $count template trovati<br>";
    
    // Test query completa
    echo "3. Test query completa: ";
    $templates = $pdo->query("SELECT * FROM email_templates ORDER BY nome")->fetchAll();
    echo "✅ OK - " . count($templates) . " template caricati<br>";
    
    // Mostra template
    echo "<h3>Template esistenti:</h3>";
    foreach ($templates as $template) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<strong>ID:</strong> " . $template['id'] . "<br>";
        echo "<strong>Nome:</strong> " . htmlspecialchars($template['nome']) . "<br>";
        echo "<strong>Oggetto:</strong> " . htmlspecialchars($template['oggetto']) . "<br>";
        echo "<strong>Corpo:</strong> " . htmlspecialchars(substr($template['corpo'], 0, 100)) . "...<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
?>
