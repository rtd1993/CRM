<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

if ($_SESSION['user_role'] !== 'developer') {
    header('Location: dashboard.php');
    exit();
}

// Gestione AJAX per azioni specifiche
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'get_stats':
            echo json_encode(getSystemStats($pdo));
            break;
            
        case 'get_resources':
            echo json_encode(getResourceUsage());
            break;
            
        case 'get_network':
            echo json_encode(getNetworkStats());
            break;
            
        case 'service_control':
            $service = $_POST['service'] ?? '';
            $action = $_POST['action'] ?? '';
            echo json_encode(controlService($service, $action));
            break;
            
        case 'execute_query':
            $sql = trim($_POST['sql'] ?? '');
            echo json_encode(executeQuery($pdo, $sql));
            break;
            
        case 'cleanup_action':
            $type = $_POST['cleanup_type'] ?? '';
            echo json_encode(performCleanup($pdo, $type));
            break;
    }
    exit;
}

// Funzioni di supporto
function getSystemStats($pdo) {
    try {
        $stats = [];
        
        // Clienti
        $stmt = $pdo->query("SELECT COUNT(*) FROM clienti");
        $stats['clienti'] = $stmt->fetchColumn();
        
        // Messaggi chat
        $stmt = $pdo->query("SELECT COUNT(*) FROM chat_messages");
        $stats['messaggi'] = $stmt->fetchColumn();
        
        // Pratiche ENEA
        $stmt = $pdo->query("SELECT COUNT(*) FROM enea");
        $stats['enea'] = $stmt->fetchColumn();
        
        // Conto Termico
        $stmt = $pdo->query("SELECT COUNT(*) FROM conto_termico");
        $stats['conto_termico'] = $stmt->fetchColumn();
        
        // Task
        $stmt = $pdo->query("SELECT COUNT(*) FROM task");
        $stats['task'] = $stmt->fetchColumn();
        
        // Task completati
        $stmt = $pdo->query("SELECT COUNT(*) FROM task WHERE stato = 'completato'");
        $stats['task_completati'] = $stmt->fetchColumn();
        
        // Utenti attivi
        $stmt = $pdo->query("SELECT COUNT(*) FROM utenti WHERE active = 1");
        $stats['utenti_attivi'] = $stmt->fetchColumn();
        
        return ['success' => true, 'data' => $stats];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getResourceUsage() {
    try {
        $resources = [];
        
        // Spazio disco
        $disk_total = disk_total_space('/');
        $disk_free = disk_free_space('/');
        $disk_used = $disk_total - $disk_free;
        
        $resources['disk'] = [
            'total' => formatBytes($disk_total),
            'used' => formatBytes($disk_used),
            'free' => formatBytes($disk_free),
            'percent' => round(($disk_used / $disk_total) * 100, 2)
        ];
        
        // Memory usage
        $memory = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        $resources['memory'] = [
            'current' => formatBytes($memory),
            'peak' => formatBytes($memory_peak)
        ];
        
        // Load average (Linux)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $resources['load'] = [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2)
            ];
        }
        
        // Spazio cartella local_drive
        $drive_path = __DIR__ . '/local_drive';
        if (is_dir($drive_path)) {
            $drive_size = getDirSize($drive_path);
            $resources['local_drive'] = formatBytes($drive_size);
        }
        
        return ['success' => true, 'data' => $resources];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getNetworkStats() {
    try {
        $network = [];
        
        // IP Address
        $network['server_ip'] = $_SERVER['SERVER_ADDR'] ?? 'N/A';
        $network['client_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        
        // Hostname
        $network['hostname'] = gethostname() ?: 'N/A';
        
        // Ping test (Google DNS)
        $ping_result = shell_exec('ping -c 1 8.8.8.8 2>/dev/null');
        if ($ping_result && strpos($ping_result, '1 received') !== false) {
            preg_match('/time=([0-9.]+)/', $ping_result, $matches);
            $network['ping_google'] = isset($matches[1]) ? $matches[1] . 'ms' : 'OK';
        } else {
            $network['ping_google'] = 'FAIL';
        }
        
        // Port status
        $network['ports'] = [
            'mysql' => checkPort('localhost', 3306),
            'apache' => checkPort('localhost', 80),
            'https' => checkPort('localhost', 443)
        ];
        
        return ['success' => true, 'data' => $network];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function controlService($service, $action) {
    $allowed_services = ['apache2', 'mysql', 'nodejs', 'rclone'];
    $allowed_actions = ['start', 'stop', 'restart', 'status'];
    
    if (!in_array($service, $allowed_services) || !in_array($action, $allowed_actions)) {
        return ['success' => false, 'error' => 'Servizio o azione non validi'];
    }
    
    try {
        $command = '';
        switch ($service) {
            case 'apache2':
                $command = "systemctl $action apache2 2>&1";
                break;
            case 'mysql':
                $command = "systemctl $action mysql 2>&1";
                break;
            case 'nodejs':
                $command = "systemctl $action nodejs 2>&1";
                break;
            case 'rclone':
                if ($action === 'status') {
                    $command = "ps aux | grep rclone | grep -v grep";
                } else {
                    $command = "systemctl $action rclone 2>&1";
                }
                break;
        }
        
        if ($command) {
            $output = shell_exec($command);
            return ['success' => true, 'output' => $output ?: 'Comando eseguito'];
        }
        
        return ['success' => false, 'error' => 'Comando non trovato'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function executeQuery($pdo, $sql) {
    try {
        if (empty($sql)) {
            return ['success' => false, 'error' => 'Query vuota'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        if (stripos($sql, 'SELECT') === 0) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'type' => 'select', 'data' => $results];
        } else {
            $affected = $stmt->rowCount();
            return ['success' => true, 'type' => 'modify', 'affected_rows' => $affected];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function performCleanup($pdo, $type) {
    try {
        switch ($type) {
            case 'logs':
                // Pulizia log files
                $log_files = glob(__DIR__ . '/logs/*.log');
                $count = 0;
                foreach ($log_files as $file) {
                    if (unlink($file)) $count++;
                }
                return ['success' => true, 'message' => "Eliminati $count file di log"];
                
            case 'old_chats':
                // Elimina messaggi chat più vecchi di 6 mesi
                $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)");
                $stmt->execute();
                $deleted = $stmt->rowCount();
                return ['success' => true, 'message' => "Eliminati $deleted messaggi vecchi"];
                
            case 'optimize_db':
                // Ottimizza tutte le tabelle
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $pdo->exec("OPTIMIZE TABLE `$table`");
                }
                return ['success' => true, 'message' => 'Database ottimizzato'];
                
            case 'archive_chats':
                // Archivia chat vecchie (esempio: sposta in tabella archive)
                // Implementare logica di archiviazione
                return ['success' => true, 'message' => 'Chat archiviate (funzione da implementare)'];
                
            default:
                return ['success' => false, 'error' => 'Tipo di pulizia non riconosciuto'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Utility functions
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($size, 1024));
    return round($size / pow(1024, $i), $precision) . ' ' . $units[$i];
}

function getDirSize($directory) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

function checkPort($host, $port, $timeout = 3) {
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($connection) {
        fclose($connection);
        return 'OPEN';
    }
    return 'CLOSED';
}

require_once __DIR__ . '/includes/header.php';

// Esecuzione comandi servizi
$service_output = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_action'])) {
    $cmd = '';
    switch ($_POST['service_action']) {
        case 'apache_restart': $cmd = "sudo systemctl restart apache2 2>&1"; break;
        case 'apache_start':   $cmd = "sudo systemctl start apache2 2>&1"; break;
        case 'apache_stop':    $cmd = "sudo systemctl stop apache2 2>&1"; break;
        case 'apache_status':  $cmd = "sudo systemctl status apache2 --no-pager 2>&1"; break;
        
        case 'mysql_restart':  $cmd = "sudo systemctl restart mysql 2>&1"; break;
        case 'mysql_start':    $cmd = "sudo systemctl start mysql 2>&1"; break;
        case 'mysql_stop':     $cmd = "sudo systemctl stop mysql 2>&1"; break;
        case 'mysql_status':   $cmd = "sudo systemctl status mysql --no-pager 2>&1"; break;
        
        case 'node_start':     $cmd = "sudo systemctl start node-socket 2>&1"; break;
        case 'node_stop':      $cmd = "sudo systemctl stop node-socket 2>&1"; break;
        case 'node_restart':   $cmd = "sudo systemctl restart node-socket 2>&1"; break;
        case 'node_status':    $cmd = "sudo systemctl status node-socket --no-pager 2>&1"; break;
        
        case 'lt_start':       $cmd = "sudo systemctl start localtunnel 2>&1"; break;
        case 'lt_stop':        $cmd = "sudo systemctl stop localtunnel 2>&1"; break;
        case 'lt_restart':     $cmd = "sudo systemctl restart localtunnel 2>&1"; break;
        case 'lt_status':      $cmd = "sudo systemctl status localtunnel --no-pager 2>&1"; break;
        case 'lt_logs':        $cmd = "sudo journalctl -u localtunnel --no-pager -n 20 2>&1"; break;
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
.btn-secondary { background: #6c757d; color: white; }

.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.service-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.service-card h4 {
    margin: 0 0 15px 0;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 10px;
}

.service-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 15px;
    border: 1px solid #dee2e6;
}

.service-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.service-controls .btn {
    flex: 1;
    min-width: 80px;
    padding: 6px 12px;
    font-size: 12px;
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
        <p>Controlla e gestisci tutti i servizi del sistema in modo organizzato.</p>
        
        <div class="service-grid">
            <!-- Apache2 Web Server -->
            <div class="service-card">
                <h4>🌐 Apache2 Web Server</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active apache2 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl apache2</small>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                </div>
            </div>

            <!-- MySQL Database -->
            <div class="service-card">
                <h4>🗄️ MySQL Database</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active mysql 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl mysql</small>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                </div>
            </div>

            <!-- Node.js Socket Service -->
            <div class="service-card">
                <h4>⚡ Node.js Socket</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active node-socket 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl node-socket</small>
                </div>
                <div style="font-size: 11px; color: #6c757d; margin-bottom: 10px;">
                    <strong>Dipendenze:</strong> Apache2 + MySQL<br>
                    <em>Avvio ritardato di 5 secondi</em>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                </div>
            </div>

            <!-- LocalTunnel Service -->
            <div class="service-card">
                <h4>🚀 LocalTunnel</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active localtunnel 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl localtunnel</small>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_logs">
                        <button type="submit" class="btn btn-secondary">📋 Logs</button>
                    </form>
                </div>
                <?php if ($status === "active"): ?>
                    <div style="margin-top: 10px; padding: 8px; background: #d4edda; border-radius: 4px; font-size: 12px;">
                        <strong>🌐 URL:</strong> <a href="https://ascontabilemente.loca.lt" target="_blank">https://ascontabilemente.loca.lt</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h4>🛠️ Installazione LocalTunnel Service</h4>
        <div class="info-box">
            <p><strong>Prima installazione:</strong> Se LocalTunnel non è ancora configurato come servizio, esegui:</p>
            <div class="code-block">
                chmod +x install_localtunnel.sh<br>
                sudo ./install_localtunnel.sh
            </div>
            <p>Questo script installerà Node.js, LocalTunnel e configurerà il servizio per l'avvio automatico.</p>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
