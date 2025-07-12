<?php
// Test per verificare redirect e funzionalità

// Test 1: Redirect dopo creazione task
echo "Test 1: Creazione task\n";
echo "1. Vai su crea_task.php\n";
echo "2. Compila il form\n";
echo "3. Clicca Salva\n";
echo "4. Dovresti essere reindirizzato a task.php con messaggio di successo\n\n";

// Test 2: Redirect dopo modifica task
echo "Test 2: Modifica task\n";
echo "1. Vai su task.php\n";
echo "2. Clicca 'Modifica' su un task\n";
echo "3. Modifica i dati\n";
echo "4. Clicca Salva\n";
echo "5. Dovresti essere reindirizzato a task.php con messaggio di modifica\n\n";

// Test 3: Completamento task
echo "Test 3: Completamento task\n";
echo "1. Vai su task.php\n";
echo "2. Clicca 'Completato' su un task\n";
echo "3. Conferma l'azione\n";
echo "4. Dovresti essere reindirizzato a task.php con messaggio di completamento\n\n";

// Test 4: Eliminazione task
echo "Test 4: Eliminazione task\n";
echo "1. Vai su task.php\n";
echo "2. Clicca 'Elimina' su un task\n";
echo "3. Conferma l'azione\n";
echo "4. Dovresti essere reindirizzato a task.php con messaggio di eliminazione\n\n";

// Verifica configurazione PHP
echo "Verifiche tecniche:\n";
echo "- Output buffering: " . (ob_get_level() > 0 ? "ATTIVO" : "DISATTIVO") . "\n";
echo "- Session status: " . session_status() . "\n";
echo "- PHP version: " . phpversion() . "\n";
echo "- Memory limit: " . ini_get('memory_limit') . "\n";
echo "- Max execution time: " . ini_get('max_execution_time') . "\n";

// Test connessione database
try {
    require_once __DIR__ . '/includes/db.php';
    $stmt = $pdo->query("SELECT COUNT(*) FROM task");
    $count = $stmt->fetchColumn();
    echo "- Database connesso: SÌ (task trovati: $count)\n";
} catch (Exception $e) {
    echo "- Database connesso: NO (errore: " . $e->getMessage() . ")\n";
}

echo "\n=== FINE TEST ===\n";
?>
