<?php
require_once 'includes/db.php';

echo "<h1>Test Recupero Clienti</h1>";

// Test della query che usa email_invio.php
$clienti = $pdo->query("SELECT id, `Cognome_Ragione_sociale` as nome, Nome as nome_proprio, Mail as email FROM clienti WHERE Mail IS NOT NULL AND Mail != '' ORDER BY `Cognome_Ragione_sociale`")->fetchAll();

echo "<h3>Clienti con email trovati: " . count($clienti) . "</h3>";

foreach ($clienti as $cliente) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
    echo "<strong>ID:</strong> " . $cliente['id'] . "<br>";
    echo "<strong>Nome:</strong> " . htmlspecialchars($cliente['nome_proprio']) . "<br>";
    echo "<strong>Cognome/Ragione sociale:</strong> " . htmlspecialchars($cliente['nome']) . "<br>";
    echo "<strong>Email:</strong> " . htmlspecialchars($cliente['email']) . "<br>";
    
    // Test recupero singolo cliente (come fa email_invio.php)
    $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
    $stmt->execute([$cliente['id']]);
    $cliente_completo = $stmt->fetch();
    
    if ($cliente_completo) {
        $nome_completo = trim(($cliente_completo['Nome'] ?? '') . ' ' . ($cliente_completo['Cognome_Ragione_sociale'] ?? ''));
        echo "<strong>Nome completo generato:</strong> " . htmlspecialchars($nome_completo) . "<br>";
        echo "<strong>Email dal record completo:</strong> " . htmlspecialchars($cliente_completo['Mail'] ?? 'N/A') . "<br>";
    } else {
        echo "<span style='color: red;'><strong>ERRORE:</strong> Cliente non trovato con query singola!</span><br>";
    }
    
    echo "</div>";
}
?>
