<?php
// Debug file per testare la modifica procedura
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG MODIFICA PROCEDURA ===\n\n";

// Test connessione database
try {
    require_once __DIR__ . '/includes/config.php';
    echo "✅ Connessione database OK\n";
    
    // Test recupero procedura
    $id = 3;
    $stmt = $pdo->prepare("SELECT * FROM procedure_crm WHERE id = ?");
    $stmt->execute([$id]);
    $procedure_data = $stmt->fetch();
    
    if ($procedure_data) {
        echo "✅ Procedura trovata: " . $procedure_data['denominazione'] . "\n";
        echo "   ID: " . $procedure_data['id'] . "\n";
        echo "   Valida dal: " . $procedure_data['valida_dal'] . "\n";
        echo "   Lunghezza testo: " . strlen($procedure_data['procedura']) . " caratteri\n";
    } else {
        echo "❌ Procedura non trovata\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}

// Test modifica (simulazione)
if (isset($_GET['test_update'])) {
    try {
        $stmt = $pdo->prepare("UPDATE procedure_crm SET procedura = ? WHERE id = ?");
        $result = $stmt->execute(["Test di modifica - " . date('Y-m-d H:i:s'), 3]);
        
        if ($result) {
            echo "✅ Test aggiornamento riuscito\n";
        } else {
            echo "❌ Test aggiornamento fallito\n";
        }
    } catch (Exception $e) {
        echo "❌ Errore aggiornamento: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FINE DEBUG ===\n";
?>
