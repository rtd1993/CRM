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

// Esecuzione comandi servizi
$service_output = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_action'])) {
    $cmd = '';
    switch ($_POST['service_action']) {
        case 'apache_restart': $cmd = "sudo systemctl restart apache2 2>&1"; break;
        case 'node_start':    $cmd = "sudo systemctl start node-socket 2>&1"; break;
        case 'node_stop':     $cmd = "sudo systemctl stop node-socket 2>&1"; break;
        case 'node_restart':  $cmd = "sudo systemctl restart node-socket 2>&1"; break;
        case 'wg_start':      $cmd = "sudo wg-quick up wg0 2>&1"; break;
        case 'wg_stop':       $cmd = "sudo wg-quick down wg0 2>&1"; break;
        case 'wg_restart':    $cmd = "sudo wg-quick down wg0 2>&1 && sudo wg-quick up wg0 2>&1"; break;
    }
    if ($cmd) {
        $service_output = shell_exec($cmd);
        file_put_contents(__DIR__.'/service_log.txt', date('Y-m-d H:i:s')." $cmd\n$service_output\n", FILE_APPEND);
    }
}
?>

<h2>🔧 DevTools – Console SQL, Visualizzazione Tabelle e Servizi</h2>

<?= $messaggio ?>

<!-- SQL Section -->
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

<!-- Tabelle Section -->
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

<hr>

<!-- Servizi Section -->
<h3>🖥️ Servizi</h3>
<div style="display:flex;gap:30px;flex-wrap:wrap;">
    <form method="post" class="mb-2">
        <input type="hidden" name="service_action" value="apache_restart">
        <button type="submit" style="background:#e67e22;color:#fff;padding:8px 18px;border:none;border-radius:4px;">Riavvia Apache2</button>
    </form>
    <form method="post" class="mb-2">
        <input type="hidden" name="service_action" value="node_start">
        <button type="submit" style="background:#27ae60;color:#fff;padding:8px 18px;border:none;border-radius:4px;">Avvia Node (socket.js)</button>
    </form>
    <form method="post" class="mb-2">
        <input type="hidden" name="service_action" value="node_stop">
        <button type="submit" style="background:#c0392b;color:#fff;padding:8px 18px;border:none;border-radius:4px;">Ferma Node (socket.js)</button>
    </form>
    <form method="post" class="mb-2">
        <input type="hidden" name="service_action" value="node_restart">
        <button type="submit" style="background:#2980b9;color:#fff;padding:8px 18px;border:none;border-radius:4px;">Riavvia Node (socket.js)</button>
    </form>
    <form method="post" class="mb-2">
        <input type="hidden" name="service_action" value="wg_start">
        <button type="submit" style="background:#27ae60;color:#fff;padding:8px 18px;border:none;border-radius:4px;">Avvia WireGuard</button>
    </form>
    <form method="post" class="mb-2">
        <input type="hidden" name="service_action" value="wg_stop">
        <button type="submit" style="background:#c0392b;color:#fff;padding:8px 18px;border:none;border-radius:4px;">Ferma WireGuard</button>
    </form>
    <form method="post" class="mb-2">
        <input type="hidden" name="service_action" value="wg_restart">
        <button type="submit" style="background:#2980b9;color:#fff;padding:8px 18px;border:none;border-radius:4px;">Riavvia WireGuard</button>
    </form>
</div>

<!-- Prompt Windows/SSH -->
<hr>
<h3>💻 Prompt Windows / SSH</h3>
<p>
    <b>Comando SSH per accedere al server:</b>
    <code>ssh admin@<?= htmlspecialchars($_SERVER['SERVER_ADDR'] ?? '192.168.1.29') ?></code>
    <br>
    Password: <b>admin</b>
</p>
<p>
    <b>Prompt locale:</b> Puoi usare <a href="https://mobaxterm.mobatek.net/" target="_blank">MobaXterm</a>, <a href="https://www.putty.org/" target="_blank">PuTTY</a> o il prompt integrato di Windows 10+ (PowerShell) per aprire una shell SSH direttamente dal tuo PC.
</p>

<!-- Terminale output servizi -->
<h3>📝 Output Servizi</h3>
<div style="background:#1a1a1a;color:#00fd00;font-family:monospace;padding:10px 15px;border-radius:6px;min-height:80px;max-height:320px;overflow:auto;" id="service_output">
    <?= nl2br(htmlspecialchars($service_output)) ?>
</div>
<script>
function pollServiceLog() {
    fetch('service_log.txt?rnd=' + Math.random())
        .then(response => response.text())
        .then(log => {
            document.getElementById('service_output').innerHTML = log.replace(/\n/g, '<br>');
        });
}
setInterval(pollServiceLog, 2000);
</script>

</main>
</body>
</html>