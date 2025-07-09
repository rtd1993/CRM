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

<style>
.devtools-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section h3 {
    margin-top: 0;
    color: #495057;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 10px;
    margin-bottom: 10px;
}

.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn-info { background: #17a2b8; color: white; }

.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.service-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.status-active { color: #28a745; }
.status-inactive { color: #dc3545; }

.terminal-output {
    background: #1a1a1a;
    color: #00fd00;
    font-family: 'Courier New', monospace;
    padding: 15px;
    border-radius: 6px;
    min-height: 100px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #333;
}

.table-responsive {
    overflow-x: auto;
    margin-top: 15px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.table th, .table td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #dee2e6;
}

.table th {
    background: #e9ecef;
    font-weight: bold;
}

.table tr:nth-child(even) {
    background: #f8f9fa;
}

.alert {
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.info-box {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

.code-block {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    font-family: 'Courier New', monospace;
    margin: 10px 0;
}
</style>

<div class="devtools-container">
    <h2>🔧 DevTools – Console di Sviluppo</h2>

    <?php if ($messaggio): ?>
        <div class="alert <?= strpos($messaggio, 'color: green') !== false ? 'alert-success' : 'alert-error' ?>">
            <?= strip_tags($messaggio) ?>
        </div>
    <?php endif; ?>

    <!-- Sezione SQL Console -->
    <div class="section">
        <h3>💻 SQL Console</h3>
        <p>Esegui query SQL personalizzate direttamente sul database.</p>
        
        <form method="post">
            <div class="form-group">
                <label for="sql">Query SQL:</label>
                <textarea name="sql" id="sql" rows="6" class="form-control" placeholder="Scrivi una query SQL (es: SELECT * FROM utenti LIMIT 10)..." required><?= htmlspecialchars($_POST['sql'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">▶️ Esegui Query</button>
        </form>

        <?php if ($campi): ?>
            <h4>Risultato SELECT</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <?php foreach ($campi as $c): ?><th><?= htmlspecialchars($c) ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($risultati as $r): ?>
                            <tr>
                                <?php foreach ($campi as $c): ?><td><?= htmlspecialchars($r[$c] ?? '') ?></td><?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sezione Visualizzazione Tabelle -->
    <div class="section">
        <h3>📊 Visualizzazione Tabelle Database</h3>
        <p>Esplora i dati contenuti nelle tabelle del database.</p>
        
        <form method="get">
            <div class="form-group">
                <label for="table">Seleziona tabella:</label>
                <select name="table" id="table" class="form-control" onchange="this.form.submit()" style="width: 300px;">
                    <option value="">-- Scegli una tabella --</option>
                    <?php foreach ($tabelle as $t): ?>
                        <option value="<?= $t ?>" <?= $t === $tabella_selezionata ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (!empty($campi_tabella)): ?>
            <h4>Contenuto tabella: <strong><?= $tabella_selezionata ?></strong></h4>
            <p><em>Mostra i primi 100 record</em></p>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <?php foreach ($campi_tabella as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tabella_dati)): ?>
                            <?php foreach ($tabella_dati as $row): ?>
                                <tr>
                                    <?php foreach ($campi_tabella as $col): ?><td><?= htmlspecialchars($row[$col] ?? '') ?></td><?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?= count($campi_tabella) ?>" style="text-align:center;">Nessun dato presente nella tabella.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sezione Gestione Servizi -->
    <div class="section">
        <h3>🖥️ Gestione Servizi Sistema</h3>
        <p>Controlla e gestisci i servizi del sistema.</p>
        
        <h4>Stato Servizi</h4>
        <div class="service-grid">
            <?php
            $services = [
                "apache2" => "Apache2 Web Server",
                "mysql" => "MySQL/MariaDB Database",
                "node-socket" => "Node.js Socket Service",
                "wg-quick@wg0" => "WireGuard VPN"
            ];
            foreach ($services as $sysname => $friendly) {
                $status = trim(shell_exec("systemctl is-active $sysname 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "Attivo" : "Inattivo";
                echo "<div class='service-status'>";
                echo "<span class='$status_class'>●</span>";
                echo "<div><strong>$friendly</strong><br><small>$sysname</small><br><span class='$status_class'>$status_text</span></div>";
                echo "</div>";
            }
            ?>
        </div>

        <h4>Controlli Servizi</h4>
        <div class="service-grid">
            <form method="post">
                <input type="hidden" name="service_action" value="apache_restart">
                <button type="submit" class="btn btn-warning">🔄 Riavvia Apache2</button>
            </form>
            <form method="post">
                <input type="hidden" name="service_action" value="node_start">
                <button type="submit" class="btn btn-success">▶️ Avvia Node.js</button>
            </form>
            <form method="post">
                <input type="hidden" name="service_action" value="node_stop">
                <button type="submit" class="btn btn-danger">⏹️ Ferma Node.js</button>
            </form>
            <form method="post">
                <input type="hidden" name="service_action" value="node_restart">
                <button type="submit" class="btn btn-info">🔄 Riavvia Node.js</button>
            </form>
            <form method="post">
                <input type="hidden" name="service_action" value="wg_start">
                <button type="submit" class="btn btn-success">▶️ Avvia WireGuard</button>
            </form>
            <form method="post">
                <input type="hidden" name="service_action" value="wg_stop">
                <button type="submit" class="btn btn-danger">⏹️ Ferma WireGuard</button>
            </form>
            <form method="post">
                <input type="hidden" name="service_action" value="wg_restart">
                <button type="submit" class="btn btn-info">🔄 Riavvia WireGuard</button>
            </form>
        </div>

        <h4>Output Operazioni</h4>
        <div class="terminal-output" id="service_output">
            <?= nl2br(htmlspecialchars($service_output)) ?>
        </div>
    </div>

    <!-- Sezione Accesso Remoto -->
    <div class="section">
        <h3>🌐 Accesso Remoto e SSH</h3>
        <p>Informazioni per accedere al server da remoto.</p>
        
        <div class="info-box">
            <h4>Connessione SSH</h4>
            <div class="code-block">
                ssh admin@<?= htmlspecialchars($_SERVER['SERVER_ADDR'] ?? '192.168.1.29') ?>
            </div>
            <p><strong>Password:</strong> admin</p>
        </div>

        <div class="info-box">
            <h4>Client SSH Consigliati</h4>
            <ul>
                <li><a href="https://mobaxterm.mobatek.net/" target="_blank">MobaXterm</a> - Client SSH completo per Windows</li>
                <li><a href="https://www.putty.org/" target="_blank">PuTTY</a> - Client SSH leggero</li>
                <li><strong>PowerShell</strong> - Prompt integrato di Windows 10+</li>
            </ul>
        </div>
    </div>
</div>

<script>
function pollServiceLog() {
    fetch('service_log.txt?rnd=' + Math.random())
        .then(response => response.text())
        .then(log => {
            const output = document.getElementById('service_output');
            if (log.trim()) {
                output.innerHTML = log.replace(/\n/g, '<br>');
                output.scrollTop = output.scrollHeight;
            }
        })
        .catch(err => console.log('Log polling error:', err));
}

// Poll service log every 3 seconds
setInterval(pollServiceLog, 3000);

// Auto-expand textarea based on content
document.getElementById('sql').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 200) + 'px';
});
</script>

</main>
</body>
</html>