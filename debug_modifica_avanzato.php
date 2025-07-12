<?php
// Debug avanzato per modifica_cliente.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug Avanzato - Modifica Cliente</h1>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:0 auto;padding:20px;}</style>";

// Test 1: Includes
echo "<h2>1. ✅ Test Includes</h2>";
try {
    require_once __DIR__ . '/includes/auth.php';
    echo "✅ auth.php OK<br>";
    
    require_login();
    echo "✅ Autenticazione OK<br>";
    
    require_once __DIR__ . '/includes/db.php';
    echo "✅ db.php OK<br>";
    
    require_once __DIR__ . '/includes/header.php';
    echo "✅ header.php OK<br>";
    
} catch (Exception $e) {
    echo "❌ Errore includes: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Parametri GET
echo "<h2>2. ✅ Test Parametri GET</h2>";
if (!isset($_GET['id'])) {
    echo "❌ ID mancante, aggiungo ID=1<br>";
    $_GET['id'] = 1;
}
$id = intval($_GET['id']);
echo "✅ ID Cliente: $id<br>";

// Test 3: Database Query
echo "<h2>3. ✅ Test Database Query</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        echo "✅ Cliente trovato: " . htmlspecialchars($cliente['Cognome/Ragione sociale']) . "<br>";
    } else {
        echo "❌ Cliente non trovato<br>";
        die();
    }
} catch (Exception $e) {
    echo "❌ Errore query: " . $e->getMessage() . "<br>";
    die();
}

// Test 4: Funzione campo_input
echo "<h2>4. ✅ Test Funzione campo_input</h2>";
function campo_input($nome, $valore, $type = 'text') {
    $nome_escaped = htmlspecialchars($nome);
    $valore_escaped = htmlspecialchars($valore ?? '');
    
    return "<div class=\"form-field\">
        <label class=\"form-label\">{$nome_escaped}</label>
        <input type=\"{$type}\" name=\"{$nome}\" value=\"{$valore_escaped}\" class=\"form-control\">
    </div>";
}

// Test con campo problematico
$test_field = campo_input('Cognome/Ragione sociale', $cliente['Cognome/Ragione sociale'] ?? '', 'text');
echo "✅ Funzione campo_input OK<br>";
echo "Test campo: " . htmlspecialchars($test_field) . "<br>";

// Test 5: Gruppi di Campi
echo "<h2>5. ✅ Test Gruppi di Campi</h2>";
$gruppi = [
    'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Codice fiscale', 'Partita IVA'],
    'Contatti' => ['Telefono', 'Mail', 'PEC']
];

foreach ($gruppi as $titolo => $campi) {
    echo "Gruppo: $titolo<br>";
    foreach ($campi as $campo) {
        echo "- Campo: $campo = " . htmlspecialchars($cliente[$campo] ?? 'N/A') . "<br>";
    }
}

// Test 6: Generazione Form Base
echo "<h2>6. ✅ Test Generazione Form</h2>";
echo "<form method='post' style='border:1px solid #ccc;padding:20px;'>";
echo "<h3>Form Test</h3>";

foreach ($gruppi as $titolo => $campi) {
    echo "<fieldset><legend>$titolo</legend>";
    foreach ($campi as $campo) {
        echo campo_input($campo, $cliente[$campo] ?? '', 'text');
    }
    echo "</fieldset>";
}

echo "<button type='submit' style='padding:10px 20px;background:#007bff;color:white;border:none;border-radius:5px;'>Test Submit</button>";
echo "</form>";

// Test 7: CSS Minimo
echo "<h2>7. ✅ Test CSS Minimo</h2>";
?>
<style>
.form-field { margin: 10px 0; }
.form-label { display: block; font-weight: bold; margin-bottom: 5px; }
.form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
fieldset { margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>
<?php

echo "✅ CSS base applicato<br>";

// Test 8: POST Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>8. ✅ Test POST Processing</h2>";
    echo "Dati POST ricevuti:<br>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Simulazione aggiornamento (senza eseguire la query)
    $campi_validi = [];
    foreach ($_POST as $campo => $valore) {
        if (!empty($campo) && $campo !== 'submit') {
            $campi_validi[] = $campo;
        }
    }
    
    echo "Campi validi da aggiornare: " . implode(', ', $campi_validi) . "<br>";
    echo "✅ POST processing OK<br>";
}

echo "<hr><h2>🎯 Risultati Test</h2>";
echo "<p>✅ Tutti i test sono passati! Il problema nella pagina completa potrebbe essere:</p>";
echo "<ul>";
echo "<li><strong>CSS complesso:</strong> Qualche regola CSS causa problemi di rendering</li>";
echo "<li><strong>JavaScript:</strong> Errori nel JavaScript alla fine della pagina</li>";
echo "<li><strong>Memoria PHP:</strong> La pagina completa richiede più memoria</li>";
echo "<li><strong>HTML malformato:</strong> Qualche tag non chiuso correttamente</li>";
echo "</ul>";

echo "<hr><h2>🔗 Link di Test</h2>";
echo "<a href='modifica_cliente.php?id=1' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;'>🔧 Modifica Cliente Completa</a>";
echo "<a href='modifica_cliente_simple.php?id=1' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;margin:5px;'>✅ Versione Semplificata</a>";
echo "<a href='info_cliente.php?id=1' style='padding:10px 20px;background:#17a2b8;color:white;text-decoration:none;border-radius:5px;margin:5px;'>📋 Info Cliente</a>";
?>
