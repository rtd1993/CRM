<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$user_name = $_SESSION['user_name'];

// Elimina task
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $pdo->prepare("DELETE FROM task WHERE id = ?")->execute([$id]);
    header("Location: task.php?deleted=1");
    exit;
}

// Completa task
if (isset($_POST['complete_id'])) {
    $id = intval($_POST['complete_id']);
    $stmt = $pdo->prepare("SELECT * FROM task WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        // Invia notifica chat
        $msg = "$user_name ha completato il task: " . $task['descrizione'];
        $pdo->prepare("INSERT INTO chat_messaggi (utente_id, messaggio, timestamp) VALUES (?, ?, NOW())")
            ->execute([$_SESSION['user_id'], $msg]);

        if (!empty($task['ricorrenza']) && is_numeric($task['ricorrenza']) && $task['ricorrenza'] > 0) {
            // Task ricorrente: elimina il task attuale e lo ricrea con la scadenza successiva
            
            // Calcola la nuova scadenza
            $nuova_scadenza = date('Y-m-d', strtotime($task['scadenza'] . ' + ' . $task['ricorrenza'] . ' days'));
            
            // Elimina il task attuale
            $pdo->prepare("DELETE FROM task WHERE id = ?")->execute([$id]);
            
            // Ricrea il task con la nuova scadenza
            $stmt = $pdo->prepare("INSERT INTO task (descrizione, scadenza, ricorrenza) VALUES (?, ?, ?)");
            $stmt->execute([$task['descrizione'], $nuova_scadenza, $task['ricorrenza']]);
            
            // Log per debug
            error_log("Task ricorrente ricreato: '{$task['descrizione']}' - Nuova scadenza: {$nuova_scadenza}");
            
            header("Location: task.php?completed=recurring");
        } else {
            // Task non ricorrente: elimina definitivamente
            $pdo->prepare("DELETE FROM task WHERE id = ?")->execute([$id]);
            header("Location: task.php?completed=deleted");
        }
    }
    exit;
}

// Ricerca
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM task WHERE scadenza >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$params = [];
if ($search !== '') {
    $sql .= " AND descrizione LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY scadenza ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$task_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.task-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.task-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.task-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.task-controls {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    align-items: center;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.search-form {
    display: flex;
    gap: 0.5rem;
    flex: 1;
    max-width: 400px;
}

.search-input {
    flex: 1;
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.task-table {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    overflow: hidden;
}

.task-table table {
    width: 100%;
    border-collapse: collapse;
}

.task-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e1e5e9;
}

.task-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
}

.task-table tr:hover {
    background: #f8f9fa;
}

.task-description {
    font-weight: 500;
    color: #2c3e50;
}

.task-date {
    font-weight: 500;
}

.task-date.overdue {
    color: #dc3545;
    font-weight: 600;
}

.task-date.urgent {
    color: #fd7e14;
    font-weight: 600;
}

.task-date.normal {
    color: #28a745;
}

.task-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 6px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-1px);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid transparent;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-info {
    background: #cce7ff;
    color: #0c5460;
    border-color: #b8daff;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.task-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-align: center;
    border: 1px solid #e1e5e9;
}

.stat-number {
    font-size: 2rem;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.recurring-badge {
    background: #17a2b8;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .task-header h2 {
        font-size: 2rem;
    }
    
    .task-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form {
        max-width: none;
    }
    
    .task-table {
        overflow-x: auto;
    }
    
    .task-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>

<div class="task-header">
    <h2>📋 Task Mensili</h2>
    <p>Gestione e monitoraggio delle attività</p>
</div>

<?php
// Messaggi di feedback
if (isset($_GET['success']) && $_GET['success'] == '1'):
?>
    <div class="alert alert-success">
        <strong>✅ Successo!</strong> Task creato correttamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['completed'])): ?>
    <div class="alert alert-info">
        <?php if ($_GET['completed'] === 'recurring'): ?>
            <strong>🔄 Task ricorrente completato!</strong> Il task è stato eliminato e ricreato con la prossima scadenza.
        <?php else: ?>
            <strong>✅ Task completato!</strong> Il task è stato eliminato definitivamente.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning">
        <strong>🗑️ Task eliminato!</strong> Il task è stato rimosso definitivamente.
    </div>
<?php endif; ?>

<?php
// Calcola statistiche
$total_tasks = count($task_list);
$overdue_tasks = 0;
$urgent_tasks = 0;
$recurring_tasks = 0;

foreach ($task_list as $task) {
    $scadenza = strtotime($task['scadenza']);
    $oggi = time();
    $diff_giorni = floor(($scadenza - $oggi) / 86400);
    
    if ($diff_giorni < 0) $overdue_tasks++;
    elseif ($diff_giorni < 5) $urgent_tasks++;
    
    if (!empty($task['ricorrenza']) && $task['ricorrenza'] > 0) $recurring_tasks++;
}
?>

<div class="task-stats">
    <div class="stat-card">
        <div class="stat-number"><?= $total_tasks ?></div>
        <div class="stat-label">Task Totali</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #dc3545;"><?= $overdue_tasks ?></div>
        <div class="stat-label">In Scadenza</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #fd7e14;"><?= $urgent_tasks ?></div>
        <div class="stat-label">Urgenti</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #17a2b8;"><?= $recurring_tasks ?></div>
        <div class="stat-label">Ricorrenti</div>
    </div>
</div>

<div class="task-controls">
    <a href="crea_task.php" class="btn btn-primary">➕ Crea nuovo task</a>
    <form method="get" class="search-form">
        <input type="text" name="search" class="search-input" placeholder="Cerca task..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">🔍 Cerca</button>
    </form>
</div>

<?php if (empty($task_list)): ?>
    <div class="empty-state">
        <i>📋</i>
        <h3>Nessun task trovato</h3>
        <p>Non ci sono task per il periodo selezionato.</p>
        <a href="crea_task.php" class="btn btn-primary">➕ Crea il primo task</a>
    </div>
<?php else: ?>
    <div class="task-table">
        <table>
            <thead>
                <tr>
                    <th>Descrizione</th>
                    <th>Scadenza</th>
                    <th>Ricorrenza</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($task_list as $task): 
                    $scadenza = strtotime($task['scadenza']);
                    $oggi = time();
                    $diff_giorni = floor(($scadenza - $oggi) / 86400);
                    
                    $date_class = 'normal';
                    if ($diff_giorni < 0) {
                        $date_class = 'overdue';
                    } elseif ($diff_giorni < 5) {
                        $date_class = 'urgent';
                    }
                    
                    $is_recurring = !empty($task['ricorrenza']) && is_numeric($task['ricorrenza']) && $task['ricorrenza'] > 0;
                ?>
                <tr>
                    <td class="task-description">
                        <?= htmlspecialchars($task['descrizione']) ?>
                        <?php if ($is_recurring): ?>
                            <span class="recurring-badge">🔄 Ricorrente</span>
                        <?php endif; ?>
                    </td>
                    <td class="task-date <?= $date_class ?>">
                        <?= date('d/m/Y', $scadenza) ?>
                        <?php if ($diff_giorni < 0): ?>
                            <br><small>⏰ Scaduto <?= abs($diff_giorni) ?> giorni fa</small>
                        <?php elseif ($diff_giorni == 0): ?>
                            <br><small>⚡ Scade oggi</small>
                        <?php elseif ($diff_giorni < 5): ?>
                            <br><small>⏳ Scade tra <?= $diff_giorni ?> giorni</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_recurring): ?>
                            Ogni <?= $task['ricorrenza'] ?> giorni
                        <?php else: ?>
                            <span style="color: #6c757d;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="task-actions">
                        <?php if ($is_recurring): ?>
                            <!-- Task ricorrente -->
                            <form method="post" style="display:inline;" onsubmit="return confirm('Completare il task ricorrente? Sarà ricreato con la prossima scadenza.')">
                                <input type="hidden" name="complete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm">✅ Completato</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare definitivamente questo task ricorrente?')">
                                <input type="hidden" name="delete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Elimina</button>
                            </form>
                        <?php else: ?>
                            <!-- Task non ricorrente -->
                            <form method="post" style="display:inline;" onsubmit="return confirm('Completare il task? Sarà eliminato definitivamente.')">
                                <input type="hidden" name="complete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm">✅ Completato</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare definitivamente questo task?')">
                                <input type="hidden" name="delete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Elimina</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
// Auto-refresh della pagina ogni 5 minuti per aggiornare le scadenze
setTimeout(() => {
    location.reload();
}, 300000);
</script>

</main>
</body>
</html>
