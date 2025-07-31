<?php
// Test Inserimento Diretto nel Database

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h1>Test Inserimento Cliente nel Database</h1>";

// Dati test completi
$dati_test = [
    'Codice fiscale' => 'TESTDIRETTO2025',
    'Cognome/Ragione sociale' => 'Cliente Test Diretto SRL',
    'Nome' => 'Test',
    'Partita IVA' => '98765432101',
    'Telefono' => '333 9876543',
    'Mail' => 'testdiretto@example.com',
    'PEC' => 'testdiretto@pec.example.com',
    'Inizio rapporto' => '2025-01-01',
    'Fine rapporto' => '2025-12-31',
    'Inserito gestionale' => 1,
    'Codice ditta' => 'TEST001',
    'Colore' => '#00FF00',
    'Qualifica' => 'Test Manager',
    'Dipendenti' => 15
];

echo "<h2>Dati da inserire:</h2>";
echo "<pre>" . print_r($dati_test, true) . "</pre>";

try {
    // Preparazione query
    $campi = array_keys($dati_test);
    $campi_escaped = array_map(function($campo) {
        return "`$campo`";
    }, $campi);
    
    $placeholders = array_fill(0, count($dati_test), '?');
    
    $sql = "INSERT INTO clienti (" . implode(', ', $campi_escaped) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    echo "<h2>Query SQL:</h2>";
    echo "<code>$sql</code>";
    
    echo "<h2>Esecuzione...</h2>";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(array_values($dati_test));
    
    if ($result) {
        $nuovo_id = $pdo->lastInsertId();
        echo "<div style='background: green; color: white; padding: 15px; margin: 10px 0;'>";
        echo "✅ SUCCESSO! Cliente inserito con ID: $nuovo_id";
        echo "</div>";
        
        // Verifica lettura
        echo "<h2>Verifica dati salvati:</h2>";
        $check_stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
        $check_stmt->execute([$nuovo_id]);
        $cliente = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cliente) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Campo</th><th>Valore Originale</th><th>Valore Salvato</th><th>Match</th></tr>";
            
            foreach ($dati_test as $campo => $valore_originale) {
                $valore_salvato = $cliente[$campo] ?? 'NULL';
                $match = ($valore_originale == $valore_salvato) ? '✅' : '❌';
                
                echo "<tr>";
                echo "<td><strong>$campo</strong></td>";
                echo "<td>$valore_originale</td>";
                echo "<td>$valore_salvato</td>";
                echo "<td>$match</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "<div style='background: red; color: white; padding: 10px;'>❌ Cliente non trovato nel database!</div>";
        }
        
    } else {
        echo "<div style='background: red; color: white; padding: 10px;'>";
        echo "❌ ERRORE nell'inserimento";
        $errorInfo = $stmt->errorInfo();
        echo "<br>Dettagli: " . print_r($errorInfo, true);
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 10px;'>";
    echo "❌ ECCEZIONE: " . $e->getMessage();
    echo "</div>";
}

// Test finale: mostra tutti i clienti di test
echo "<h2>Tutti i clienti di test:</h2>";
try {
    $stmt = $pdo->query("SELECT id, `Codice fiscale`, `Cognome/Ragione sociale`, Nome, `Partita IVA` FROM clienti WHERE `Codice fiscale` LIKE 'TEST%' ORDER BY id DESC");
    $clienti_test = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($clienti_test) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Codice Fiscale</th><th>Ragione sociale</th><th>Nome</th><th>Partita IVA</th></tr>";
        foreach ($clienti_test as $cliente) {
            echo "<tr>";
            echo "<td>{$cliente['id']}</td>";
            echo "<td>{$cliente['Codice fiscale']}</td>";
            echo "<td>{$cliente['Cognome/Ragione sociale']}</td>";
            echo "<td>{$cliente['Nome']}</td>";
            echo "<td>{$cliente['Partita IVA']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nessun cliente di test trovato.</p>";
    }
} catch (Exception $e) {
    echo "<div style='background: orange; color: white; padding: 10px;'>";
    echo "⚠️ Errore nel recupero clienti: " . $e->getMessage();
    echo "</div>";
}
?>
