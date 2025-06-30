<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if ($_SESSION['user_role'] !== 'developer') {
    die("Accesso riservato ai developer.");
}

$messaggio = "";
$risultati = [];
$campi = [];

// Esegui query SQL manuale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql'])) {
    $sql = trim($_POST['sql']);
    try {
        $stmt = $pdo->query($sql);

        if (stripos($sql, 'SELECT') === 0) {
            $risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $campi = array_keys($risultati[0] ?? []);
            $messaggio = "<p style='color: green;'>Query SELECT eseguita con successo.</p>";
        } else {
            $messaggio = "<p style='color: green;'>Query eseguita con successo.</p>";
        }
    } catch (PDOException $e) {
        $messaggio = "<p style='color: red;'>Errore: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Elenco tabelle per visualizzazione
$tabelle = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$tabella_dati = [];
$campi_tabella = [];
$tabella_selezionata = $_GET['table'] ?? '';

if ($tabella_selezionata && in_array($tabella_selezionata, $tabelle)) {
    $stmt = $pdo->query("SELECT * FROM `$tabella_selezionata` LIMIT 100");
    $tabella_dati = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($tabella_dati)) {
        $campi_tabella = array_keys($tabella_dati[0]);
    } else {
        // Recupera i nomi dei campi anche se la tabella è vuota
        $stmt = $pdo->query("DESCRIBE `$tabella_selezionata`");
        $campi_tabella = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    }
}
?>

<h2>🔧 DevTools – Console SQL e Visualizzazione Tabelle</h2>

<?= $messaggio ?>

<h3>Esegui una query SQL</h3>
<form method="post">
    <textarea name="sql" rows="5" cols="80" placeholder="Scrivi una query SQL..." required><?= htmlspecialchars($_POST['sql'] ?? '') ?></textarea><br>
    <button type="submit">▶️ Esegui Query</button>
</form>

<?php if ($campi): ?>
    <h3>Risultato SELECT</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <?php foreach ($campi as $c): ?><th><?= htmlspecialchars($c) ?></th><?php endforeach; ?>
        </tr>
        <?php foreach ($risultati as $r): ?>
            <tr>
                <?php foreach ($campi as $c): ?><td><?= htmlspecialchars($r[$c]) ?></td><?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<hr>

<h3>📊 Visualizza tabella</h3>
<form method="get">
    <label for="table">Seleziona tabella:</label>
    <select name="table" onchange="this.form.submit()">
        <option value="">-- scegli --</option>
        <?php foreach ($tabelle as $t): ?>
            <option value="<?= $t ?>" <?= $t === $tabella_selezionata ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if (!empty($campi_tabella)): ?>
    <h4>Dati da tabella: <strong><?= $tabella_selezionata ?></strong></h4>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <?php foreach ($campi_tabella as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
        </tr>
        <?php foreach ($tabella_dati as $row): ?>
            <tr>
                <?php foreach ($campi_tabella as $col): ?><td><?= htmlspecialchars($row[$col]) ?></td><?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($tabella_dati)): ?>
            <tr><td colspan="<?= count($campi_tabella) ?>" style="text-align:center;">Nessun dato presente nella tabella.</td></tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

</main>
</body>
</html>