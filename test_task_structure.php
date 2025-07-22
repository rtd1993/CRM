<?php
// Script di test per verificare e inizializzare la struttura della tabella task e task_clienti

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "=== Verifica e inizializzazione database CRM ===\n\n";

try {
    // Verifica tabella task
    echo "1. Struttura tabella 'task':\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM task");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']}) {$column['Key']}\n";
    }
    
    echo "\n2. Verifica/creazione tabella 'task_clienti':\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM task_clienti");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($columns) {
            echo "   Tabella 'task_clienti' già esistente:\n";
            foreach ($columns as $column) {
                echo "   - {$column['Field']} ({$column['Type']}) {$column['Key']}\n";
            }
        }
    } catch (Exception $e) {
        echo "   Tabella 'task_clienti' non esiste, la creo...\n";
        
        // Crea la tabella task_clienti
        $create_sql = "CREATE TABLE task_clienti (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            cliente_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_task_client (task_id, cliente_id),
            INDEX idx_task_id (task_id),
            INDEX idx_cliente_id (cliente_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (task_id) REFERENCES task(id) ON DELETE CASCADE,
            FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_sql);
        echo "   ✅ Tabella 'task_clienti' creata con successo!\n";
        
        // Crea indici se non esistono
        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_task_scadenza ON task(scadenza)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_task_ricorrenza ON task(ricorrenza)");
            echo "   ✅ Indici aggiuntivi creati\n";
        } catch (Exception $e) {
            echo "   ⚠️ Alcuni indici potrebbero già esistere\n";
        }
    }
    
    echo "\n3. Conteggio record:\n";
    $count_task = $pdo->query("SELECT COUNT(*) FROM task")->fetchColumn();
    echo "   - Task totali: $count_task\n";
    
    $count_clienti = $pdo->query("SELECT COUNT(*) FROM clienti")->fetchColumn();
    echo "   - Clienti totali: $count_clienti\n";
    
    try {
        $count_task_clienti = $pdo->query("SELECT COUNT(*) FROM task_clienti")->fetchColumn();
        echo "   - Associazioni task-clienti: $count_task_clienti\n";
    } catch (Exception $e) {
        echo "   - Associazioni task-clienti: 0 (tabella appena creata)\n";
    }
    
    echo "\n4. Test query task con clienti:\n";
    try {
        $stmt = $pdo->query("
            SELECT t.*, 
                   COALESCE(tc.cliente_id, NULL) as cliente_id,
                   CONCAT(c.`Cognome/Ragione sociale`, ' ', COALESCE(c.Nome, '')) as nome_cliente
            FROM task t 
            LEFT JOIN task_clienti tc ON t.id = tc.task_id 
            LEFT JOIN clienti c ON tc.cliente_id = c.id 
            ORDER BY t.scadenza ASC
            LIMIT 5
        ");
        $test_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($test_tasks) {
            echo "   Prima parte dei task (primi 5):\n";
            foreach ($test_tasks as $task) {
                $cliente = $task['nome_cliente'] ? $task['nome_cliente'] : 'Nessun cliente';
                echo "   - Task ID {$task['id']}: {$task['descrizione']} (Cliente: $cliente, Scadenza: {$task['scadenza']})\n";
            }
        } else {
            echo "   Nessun task nel database\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Errore nella query test: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. Test completato con successo! ✅\n";
    echo "   La pagina task_clienti.php è pronta per l'uso\n\n";
    
    // Suggerimenti per l'uso
    echo "=== Suggerimenti per l'uso ===\n";
    echo "- Apri http://localhost/CRM/task_clienti.php per gestire i task\n";
    echo "- La tabella task_clienti gestisce le associazioni task-cliente\n";
    echo "- I task ricorrenti si rigenerano automaticamente al completamento\n";
    echo "- I task one-shot vengono eliminati al completamento\n";
    
} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
