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
    header("Location: task.php");
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
        $pdo->prepare("INSERT INTO chat (mittente, messaggio, timestamp) VALUES (?, ?, NOW())")
            ->execute([$user_name, $msg]);

        if (!empty($task['ricorrenza']) && is_numeric($task['ricorrenza']) && $task['ricorrenza'] > 0) {
            // Task ricorrente: sposta la scadenza di N giorni
            $pdo->prepare("UPDATE task SET scadenza = DATE_ADD(scadenza, INTERVAL ? DAY) WHERE id = ?")
                ->execute([$task['ricorrenza'], $id]);
        } else {
            // Task non ricorrente: elimina definitivamente
            $pdo->prepare("DELETE FROM task WHERE id = ?")->execute([$id]);
        }
    }
    header("Location: task.php");
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

<h2>📋 Task Mensili</h2>

<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; border: 1px solid #c3e6cb; margin-bottom: 1rem;">
        <strong>✅ Successo!</strong> Task creato correttamente.
    </div>
<?php endif; ?>

<p><a href="crea_task.php"><button>➕ Crea nuovo task</button></a></p>

<form method="get" style="margin-bottom: 20px;">
    <input type="text" name="search" placeholder="Cerca task..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">🔍 Cerca</button>
</form>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f0f0f0;">
            <th>Descrizione</th>
            <th>Scadenza</th>
            <th>Ricorrenza (giorni)</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($task_list as $task): 
            $scadenza = strtotime($task['scadenza']);
            $oggi = time();
            $diff_giorni = floor(($scadenza - $oggi) / 86400);
            $style = '';
            if ($diff_giorni < 0) {
                $style = 'color:red;';
            } elseif ($diff_giorni < 5) {
                $style = 'font-weight:bold; color:orange;';
            }
        ?>
        <tr>
            <td><?= htmlspecialchars($task['descrizione']) ?></td>
            <td style="<?= $style ?>">
                <?= date('d/m/Y', $scadenza) ?>
                <?php if ($diff_giorni < 5): ?> ❗<?php endif; ?>
            </td>
            <td><?= htmlspecialchars($task['ricorrenza'] ?? '—') ?></td>
            <td>
                <?php if (!empty($task['ricorrenza']) && is_numeric($task['ricorrenza']) && $task['ricorrenza'] > 0): ?>
                    <!-- Task ricorrente -->
                    <form method="post" style="display:inline;" onsubmit="return confirm('Completare il task? La prossima scadenza sarà tra <?= $task['ricorrenza'] ?> giorni.')">
                        <input type="hidden" name="complete_id" value="<?= $task['id'] ?>">
                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;">✅ Completato</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirm('ATTENZIONE: Eliminare definitivamente questo task ricorrente?')">
                        <input type="hidden" name="delete_id" value="<?= $task['id'] ?>">
                        <button type="submit" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px;">🗑️ Elimina</button>
                    </form>
                <?php else: ?>
                    <!-- Task non ricorrente -->
                    <form method="post" style="display:inline;" onsubmit="return confirm('Completare il task? Verrà eliminato definitivamente.')">
                        <input type="hidden" name="complete_id" value="<?= $task['id'] ?>">
                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;">✅ Completato</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare definitivamente questo task?')">
                        <input type="hidden" name="delete_id" value="<?= $task['id'] ?>">
                        <button type="submit" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px;">🗑️ Elimina</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</main>
</body>
</html>
