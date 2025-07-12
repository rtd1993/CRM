<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

// Test di aggiornamento task ricorrente
if (isset($_GET['test_id'])) {
    $id = intval($_GET['test_id']);
    
    // Ottieni il task
    $stmt = $pdo->prepare("SELECT * FROM task WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        echo "<h2>Test Task Ricorrente</h2>";
        echo "<p><strong>ID:</strong> {$task['id']}</p>";
        echo "<p><strong>Descrizione:</strong> {$task['descrizione']}</p>";
        echo "<p><strong>Scadenza attuale:</strong> {$task['scadenza']}</p>";
        echo "<p><strong>Ricorrenza:</strong> {$task['ricorrenza']}</p>";
        
        if (!empty($task['ricorrenza']) && is_numeric($task['ricorrenza']) && $task['ricorrenza'] > 0) {
            echo "<hr>";
            echo "<h3>Test di aggiornamento:</h3>";
            
            // Prova l'UPDATE
            $stmt = $pdo->prepare("UPDATE task SET scadenza = DATE_ADD(scadenza, INTERVAL ? DAY) WHERE id = ?");
            $result = $stmt->execute([$task['ricorrenza'], $id]);
            $affected_rows = $stmt->rowCount();
            
            echo "<p><strong>Risultato execute():</strong> " . ($result ? 'TRUE' : 'FALSE') . "</p>";
            echo "<p><strong>Righe modificate:</strong> {$affected_rows}</p>";
            
            // Verifica il risultato
            $stmt = $pdo->prepare("SELECT * FROM task WHERE id = ?");
            $stmt->execute([$id]);
            $updated_task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Nuova scadenza:</strong> {$updated_task['scadenza']}</p>";
            
            // Calcola la differenza
            $old_date = new DateTime($task['scadenza']);
            $new_date = new DateTime($updated_task['scadenza']);
            $diff = $old_date->diff($new_date);
            
            echo "<p><strong>Differenza:</strong> {$diff->days} giorni</p>";
            
            if ($diff->days == $task['ricorrenza']) {
                echo "<p style='color: green;'><strong>✅ SUCCESS:</strong> L'aggiornamento è funzionante!</p>";
            } else {
                echo "<p style='color: red;'><strong>❌ ERROR:</strong> L'aggiornamento non ha funzionato correttamente.</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Questo task non è ricorrente.</p>";
        }
    } else {
        echo "<p style='color: red;'>Task non trovato.</p>";
    }
}
?>

<h2>🔧 Test Task Update</h2>
<p>Questo script testa l'aggiornamento dei task ricorrenti.</p>

<h3>Task disponibili:</h3>
<?php
$stmt = $pdo->query("SELECT * FROM task ORDER BY id DESC LIMIT 10");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table border="1" style="border-collapse: collapse; width: 100%;">
    <tr>
        <th>ID</th>
        <th>Descrizione</th>
        <th>Scadenza</th>
        <th>Ricorrenza</th>
        <th>Azione</th>
    </tr>
    <?php foreach ($tasks as $task): ?>
    <tr>
        <td><?= $task['id'] ?></td>
        <td><?= htmlspecialchars($task['descrizione']) ?></td>
        <td><?= $task['scadenza'] ?></td>
        <td><?= $task['ricorrenza'] ?? '—' ?></td>
        <td>
            <a href="?test_id=<?= $task['id'] ?>">Test Update</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<p><a href="task.php">← Torna ai Task</a></p>
