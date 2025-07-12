<?php
// Debug della pagina info_cliente.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug Info Cliente</h1>";

// Test 1: Verifica inclusioni
echo "<h3>1. Test Inclusioni</h3>";
try {
    echo "- auth.php: ";
    require_once __DIR__ . '/includes/auth.php';
    echo "✅ OK<br>";
    
    echo "- db.php: ";
    require_once __DIR__ . '/includes/db.php';
    echo "✅ OK<br>";
    
    echo "- header.php: ";
    require_once __DIR__ . '/includes/header.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "<br>";
}

// Test 2: Verifica parametri
echo "<h3>2. Test Parametri</h3>";
echo "- GET['id']: " . ($_GET['id'] ?? 'NON PRESENTE') . "<br>";
echo "- is_numeric: " . (isset($_GET['id']) && is_numeric($_GET['id']) ? 'SÌ' : 'NO') . "<br>";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    echo "- ID processato: $id<br>";
    
    // Test 3: Verifica database
    echo "<h3>3. Test Database</h3>";
    try {
        $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cliente) {
            echo "✅ Cliente trovato: " . ($cliente['Cognome/Ragione sociale'] ?? 'N/A') . "<br>";
        } else {
            echo "❌ Cliente non trovato<br>";
        }
    } catch (Exception $e) {
        echo "❌ Errore database: " . $e->getMessage() . "<br>";
    }
}

// Test 4: Verifica funzioni
echo "<h3>4. Test Funzioni</h3>";
try {
    function test_format_label($label) {
        $label = str_replace('_', ' ', $label);
        $label = str_replace('/', ' / ', $label);
        return ucwords($label);
    }
    
    echo "- format_label: ✅ OK<br>";
    echo "- Test format: " . test_format_label('Codice_fiscale/test') . "<br>";
} catch (Exception $e) {
    echo "❌ Errore funzioni: " . $e->getMessage() . "<br>";
}

// Test 5: Verifica array gruppi
echo "<h3>5. Test Array Gruppi</h3>";
try {
    $gruppi = [
        'Anagrafica' => ['Cognome/Ragione sociale', 'Nome'],
        'Contatti' => ['Telefono', 'Mail'],
    ];
    echo "- Array gruppi: ✅ OK (" . count($gruppi) . " gruppi)<br>";
} catch (Exception $e) {
    echo "❌ Errore array: " . $e->getMessage() . "<br>";
}

echo "<h3>🔗 Link di Test</h3>";
echo '<a href="info_cliente.php?id=1">Test con ID 1</a><br>';
echo '<a href="info_cliente.php?id=999">Test con ID inesistente</a><br>';
echo '<a href="info_cliente.php">Test senza ID</a><br>';

?>
