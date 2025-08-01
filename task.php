<?php
// Avvia buffer di output per evitare problemi con header
ob_start();

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$user_name = $_SESSION['user_name'];

// Elimina task
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    try {
        $pdo->prepare("DELETE FROM task WHERE id = ?")->execute([$id]);
        header("Location: task.php?deleted=1");
        exit;
    } catch (Exception $e) {
        error_log("Errore eliminazione task: " . $e->getMessage());
        header("Location: task.php?error=Errore durante l'eliminazione del task");
        exit;
    }
}

// Completa task
if (isset($_POST['complete_id'])) {
    $id = intval($_POST['complete_id']);
    $stmt = $pdo->prepare("SELECT * FROM task WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        try {
            // Salva il completamento del task nel file di log
            $log_dir = __DIR__ . '/local_drive/ASContabilmente/';
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            
            $log_file = $log_dir . 'task_completati.txt';
            $log_entry = sprintf(
                "[%s] TASK COMPLETATO: %s | Utente: %s | Scadenza: %s | Ricorrente: %s\n",
                date('Y-m-d H:i:s'),
                $task['descrizione'],
                $user_name,
                $task['scadenza'],
                (!empty($task['ricorrenza']) && $task['ricorrenza'] > 0) ? "Sì (ogni {$task['ricorrenza']} giorni)" : "No"
            );
            
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

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
                exit;
            } else {
                // Task non ricorrente: elimina definitivamente
                $pdo->prepare("DELETE FROM task WHERE id = ?")->execute([$id]);
                header("Location: task.php?completed=deleted");
                exit;
            }
        } catch (Exception $e) {
            error_log("Errore completamento task: " . $e->getMessage());
            header("Location: task.php?error=Errore durante il completamento del task");
            exit;
        }
    } else {
        header("Location: task.php?error=Task non trovato");
        exit;
    }
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

.btn-success:active {
    transform: translateY(0);
    background: #1e7e34;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.btn-danger:active {
    transform: translateY(0);
    background: #bd2130;
}

/* Tooltip per spiegare le azioni */
.btn-sm {
    position: relative;
}

.btn-sm::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    z-index: 1000;
}

.btn-sm:hover::after {
    opacity: 1;
}

/* Tooltip per pulsanti */
        .task-actions .btn {
            position: relative;
        }
        
        .task-actions .btn[data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 5px;
        }
        
        .task-actions .btn[data-tooltip]:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: rgba(0,0,0,0.8);
            z-index: 1000;
        }

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
}

.alert-dismiss {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0.7;
    color: inherit;
}

.alert-dismiss:hover {
    opacity: 1;
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
<?php
// Messaggi di successo/errore
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case '1':
            $success_message = 'Task creato con successo!';
            break;
        case '2':
            $success_message = 'Task modificato con successo!';
            break;
    }
}

if (isset($_GET['deleted'])) {
    $success_message = 'Task eliminato con successo!';
}

if (isset($_GET['completed'])) {
    switch ($_GET['completed']) {
        case 'recurring':
            $success_message = 'Task ricorrente completato! È stato ricreato con la prossima scadenza.';
            break;
        case 'deleted':
            $success_message = 'Task completato ed eliminato!';
            break;
    }
}

if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}
?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <strong>✅ Successo!</strong> <?= $success_message ?>
        <button class="alert-dismiss" onclick="this.parentElement.style.display='none';">×</button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error">
        <strong>❌ Errore!</strong> <?= $error_message ?>
        <button class="alert-dismiss" onclick="this.parentElement.style.display='none';">×</button>
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
                            <a href="crea_task.php?edit=<?= $task['id'] ?>" class="btn btn-primary btn-sm" data-tooltip="Modifica questo task">✏️ Modifica</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Completare questo task ricorrente? Sarà ricreato con la prossima scadenza.');">
                                <input type="hidden" name="complete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm" data-tooltip="Il task sarà ricreato con la prossima scadenza">✅ Completato</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare definitivamente questo task ricorrente? Questa azione non può essere annullata.');">
                                <input type="hidden" name="delete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" data-tooltip="Elimina definitivamente questo task ricorrente">🗑️ Elimina</button>
                            </form>
                        <?php else: ?>
                            <!-- Task non ricorrente -->
                            <a href="crea_task.php?edit=<?= $task['id'] ?>" class="btn btn-primary btn-sm" data-tooltip="Modifica questo task">✏️ Modifica</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Completare questo task? Sarà eliminato definitivamente.');">
                                <input type="hidden" name="complete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm" data-tooltip="Il task sarà eliminato definitivamente">✅ Completato</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare definitivamente questo task? Questa azione non può essere annullata.');">
                                <input type="hidden" name="delete_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" data-tooltip="Elimina definitivamente questo task">🗑️ Elimina</button>
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

<?php
// Flush del buffer di output
ob_end_flush();
?>