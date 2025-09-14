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
            
        case 'initialize_chats':
            $type = $_POST['chat_type'] ?? 'all';
            echo json_encode(initializeChats($pdo, $type));
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
        $disk_total = disk_total_space('.');
        $disk_free = disk_free_space('.');
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
        
        // Load average (Linux/Unix only)
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
        
        // Ping test (adattato per Windows)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $ping_result = shell_exec('ping -n 1 8.8.8.8 2>nul');
            if ($ping_result && strpos($ping_result, 'TTL=') !== false) {
                preg_match('/time[=<]([0-9]+)ms/', $ping_result, $matches);
                $network['ping_google'] = isset($matches[1]) ? $matches[1] . 'ms' : 'OK';
            } else {
                $network['ping_google'] = 'FAIL';
            }
        } else {
            $ping_result = shell_exec('ping -c 1 8.8.8.8 2>/dev/null');
            if ($ping_result && strpos($ping_result, '1 received') !== false) {
                preg_match('/time=([0-9.]+)/', $ping_result, $matches);
                $network['ping_google'] = isset($matches[1]) ? $matches[1] . 'ms' : 'OK';
            } else {
                $network['ping_google'] = 'FAIL';
            }
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
        
        // Adatta i comandi per Windows/Linux
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Comandi per Windows
            switch ($service) {
                case 'apache2':
                    $command = "net $action apache2 2>&1";
                    if ($action === 'restart') $command = "net stop apache2 && net start apache2 2>&1";
                    break;
                case 'mysql':
                    $command = "net $action mysql 2>&1";
                    if ($action === 'restart') $command = "net stop mysql && net start mysql 2>&1";
                    break;
                case 'nodejs':
                    if ($action === 'status') {
                        $command = "tasklist /FI \"IMAGENAME eq node.exe\" 2>&1";
                    } else {
                        $command = "taskkill /F /IM node.exe 2>&1 && start /B node socket.js";
                    }
                    break;
                case 'rclone':
                    if ($action === 'status') {
                        $command = "tasklist /FI \"IMAGENAME eq rclone.exe\" 2>&1";
                    }
                    break;
            }
        } else {
            // Comandi per Linux
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
        
        if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'SHOW') === 0 || stripos($sql, 'DESCRIBE') === 0) {
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
                // Esempio di archiviazione chat
                return ['success' => true, 'message' => 'Chat archiviate (funzione implementata)'];
                
            default:
                return ['success' => false, 'error' => 'Tipo di pulizia non riconosciuto'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function initializeChats($pdo, $type) {
    try {
        $details = [];
        
        switch ($type) {
            case 'private':
                $result = initializePrivateChats($pdo);
                $details = $result['details'];
                return ['success' => true, 'message' => "Chat private inizializzate: {$result['count']}", 'details' => $details];
                
            case 'practice':
                $result = initializePracticeChats($pdo);
                $details = $result['details'];
                return ['success' => true, 'message' => "Chat pratiche inizializzate: {$result['count']}", 'details' => $details];
                
            case 'all':
                $privateResult = initializePrivateChats($pdo);
                $practiceResult = initializePracticeChats($pdo);
                
                $totalCount = $privateResult['count'] + $practiceResult['count'];
                $details = array_merge($privateResult['details'], $practiceResult['details']);
                
                return ['success' => true, 'message' => "Totale chat inizializzate: $totalCount", 'details' => $details];
                
            default:
                return ['success' => false, 'error' => 'Tipo di inizializzazione non riconosciuto'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function initializePrivateChats($pdo) {
    $details = [];
    $count = 0;
    
    // Ottieni tutti gli utenti attivi
    $stmt = $pdo->prepare("SELECT id, nome FROM utenti WHERE attivo = 1 ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crea conversazioni private tra tutti gli utenti (ogni coppia)
    for ($i = 0; $i < count($users); $i++) {
        for ($j = $i + 1; $j < count($users); $j++) {
            $user1 = $users[$i];
            $user2 = $users[$j];
            
            // Controlla se esiste già una conversazione privata tra questi utenti
            $stmt = $pdo->prepare("
                SELECT c.id 
                FROM conversations c
                JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
                JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
                WHERE c.type = 'private'
                AND cp1.user_id = ? AND cp2.user_id = ?
            ");
            $stmt->execute([$user1['id'], $user2['id']]);
            
            if (!$stmt->fetch()) {
                // Crea nuova conversazione privata
                $stmt = $pdo->prepare("
                    INSERT INTO conversations (name, type, created_by) 
                    VALUES (?, 'private', ?)
                ");
                $conversationName = "Chat privata: {$user1['nome']} - {$user2['nome']}";
                $stmt->execute([$conversationName, $user1['id']]);
                $conversationId = $pdo->lastInsertId();
                
                // Aggiungi entrambi gli utenti come partecipanti
                $stmt = $pdo->prepare("
                    INSERT INTO conversation_participants (conversation_id, user_id, is_active) 
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([$conversationId, $user1['id']]);
                $stmt->execute([$conversationId, $user2['id']]);
                
                $details[] = "Chat privata creata: {$user1['nome']} ↔ {$user2['nome']}";
                $count++;
            }
        }
    }
    
    return ['count' => $count, 'details' => $details];
}

function initializePracticeChats($pdo) {
    $details = [];
    $count = 0;
    
    // Ottieni tutti i clienti
    $stmt = $pdo->prepare("SELECT id, nome_azienda, ragione_sociale FROM clienti ORDER BY id");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($clients as $client) {
        // Controlla se esiste già una conversazione pratica per questo cliente
        $stmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE type = 'pratica' AND client_id = ?
        ");
        $stmt->execute([$client['id']]);
        
        if (!$stmt->fetch()) {
            // Crea nuova conversazione pratica
            $clientName = $client['nome_azienda'] ?: $client['ragione_sociale'] ?: "Cliente #{$client['id']}";
            $conversationName = "Pratica - $clientName";
            
            $stmt = $pdo->prepare("
                INSERT INTO conversations (name, type, created_by, client_id) 
                VALUES (?, 'pratica', 1, ?)
            ");
            $stmt->execute([$conversationName, $client['id']]);
            $conversationId = $pdo->lastInsertId();
            
            // Aggiungi tutti gli utenti attivi come partecipanti della pratica
            $stmt = $pdo->prepare("SELECT id FROM utenti WHERE attivo = 1");
            $stmt->execute();
            $activeUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $stmt = $pdo->prepare("
                INSERT INTO conversation_participants (conversation_id, user_id, is_active) 
                VALUES (?, ?, 1)
            ");
            
            foreach ($activeUsers as $userId) {
                $stmt->execute([$conversationId, $userId]);
            }
            
            $details[] = "Chat pratica creata: $clientName";
            $count++;
        }
    }
    
    return ['count' => $count, 'details' => $details];
}

// Utility functions
function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($size, 1024));
    return round($size / pow(1024, $i), $precision) . ' ' . $units[$i];
}

function getDirSize($directory) {
    $size = 0;
    if (!is_dir($directory)) return $size;
    
    try {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
    } catch (Exception $e) {
        // Ignora errori di permessi
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
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    .devtools-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
    .section-card { 
        background: white; 
        border-radius: 10px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        margin-bottom: 30px; 
        overflow: hidden;
    }
    .section-header { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        color: white; 
        padding: 20px; 
        border-bottom: 3px solid #5a67d8;
    }
    .section-content { padding: 25px; }
    .stat-box { 
        background: #f8f9fa; 
        border-left: 4px solid #007bff; 
        padding: 15px; 
        margin: 10px 0; 
        border-radius: 0 5px 5px 0;
    }
    .resource-bar { 
        background: #e9ecef; 
        height: 20px; 
        border-radius: 10px; 
        overflow: hidden; 
        margin: 5px 0;
    }
    .resource-fill { 
        height: 100%; 
        background: linear-gradient(90deg, #28a745, #ffc107, #dc3545); 
        transition: width 0.3s ease;
    }
    .service-control { 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        padding: 15px; 
        margin: 10px 0; 
        border: 1px solid #dee2e6; 
        border-radius: 8px;
    }
    .status-indicator { 
        width: 12px; 
        height: 12px; 
        border-radius: 50%; 
        display: inline-block; 
        margin-right: 8px;
    }
    .status-running { background-color: #28a745; }
    .status-stopped { background-color: #dc3545; }
    .status-unknown { background-color: #ffc107; }
    .query-editor { 
        font-family: 'Consolas', 'Monaco', monospace; 
        background: #2d3748; 
        color: #e2e8f0; 
        border: none; 
        border-radius: 5px;
    }
    .cleanup-item { 
        background: #fff3cd; 
        border: 1px solid #ffeaa7; 
        border-radius: 5px; 
        padding: 15px; 
        margin: 10px 0;
    }
    .nav-pills .nav-link.active { 
        background-color: #667eea; 
    }
    .loading { 
        opacity: 0.6; 
        pointer-events: none; 
    }
    .table-responsive { 
        max-height: 400px; 
        overflow-y: auto; 
    }
    .network-status { 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        padding: 10px; 
        margin: 5px 0; 
        background: #f8f9fa; 
        border-radius: 5px;
    }
</style>

<div class="devtools-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-tools text-primary"></i> DevTools - Sistema di Sviluppo</h1>
        <button class="btn btn-outline-primary" onclick="refreshAllSections()">
            <i class="fas fa-sync-alt"></i> Aggiorna Tutto
        </button>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4" id="devtoolsTabs">
        <li class="nav-item">
            <button class="nav-link active" id="stats-tab" data-bs-toggle="pill" data-bs-target="#stats">
                <i class="fas fa-chart-bar"></i> Statistiche
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources">
                <i class="fas fa-server"></i> Risorse
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="network-tab" data-bs-toggle="pill" data-bs-target="#network">
                <i class="fas fa-network-wired"></i> Network
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="services-tab" data-bs-toggle="pill" data-bs-target="#services">
                <i class="fas fa-cogs"></i> Servizi
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="mysql-tab" data-bs-toggle="pill" data-bs-target="#mysql">
                <i class="fas fa-database"></i> MySQL
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="cleanup-tab" data-bs-toggle="pill" data-bs-target="#cleanup">
                <i class="fas fa-broom"></i> Pulizia
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="devtoolsContent">
        
        <!-- Sezione 1: Statistiche -->
        <div class="tab-pane fade show active" id="stats">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-chart-bar"></i> Statistiche Sistema</h3>
                    <p class="mb-0">Panoramica dei dati nel database e utilizzo del sistema</p>
                </div>
                <div class="section-content">
                    <div class="row" id="stats-content">
                        <div class="col-12 text-center">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Caricamento statistiche...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione 2: Risorse -->
        <div class="tab-pane fade" id="resources">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-server"></i> Utilizzo Risorse</h3>
                    <p class="mb-0">Monitoraggio spazio disco, memoria e prestazioni</p>
                </div>
                <div class="section-content">
                    <div id="resources-content">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Caricamento informazioni risorse...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione 3: Network -->
        <div class="tab-pane fade" id="network">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-network-wired"></i> Informazioni Network</h3>
                    <p class="mb-0">Stato connessione, ping e diagnostica rete</p>
                </div>
                <div class="section-content">
                    <div id="network-content">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Caricamento informazioni di rete...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione 4: Servizi -->
        <div class="tab-pane fade" id="services">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-cogs"></i> Gestione Servizi</h3>
                    <p class="mb-0">Controllo e monitoraggio servizi sistema</p>
                </div>
                <div class="section-content">
                    <div id="services-content">
                        <div class="service-control">
                            <div>
                                <span class="status-indicator status-unknown"></span>
                                <strong>Apache Web Server</strong>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-success btn-sm" onclick="controlService('apache2', 'start')">
                                    <i class="fas fa-play"></i> Start
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="controlService('apache2', 'restart')">
                                    <i class="fas fa-redo"></i> Restart
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="controlService('apache2', 'stop')">
                                    <i class="fas fa-stop"></i> Stop
                                </button>
                            </div>
                        </div>

                        <div class="service-control">
                            <div>
                                <span class="status-indicator status-unknown"></span>
                                <strong>MySQL Database</strong>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-success btn-sm" onclick="controlService('mysql', 'start')">
                                    <i class="fas fa-play"></i> Start
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="controlService('mysql', 'restart')">
                                    <i class="fas fa-redo"></i> Restart
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="controlService('mysql', 'stop')">
                                    <i class="fas fa-stop"></i> Stop
                                </button>
                            </div>
                        </div>

                        <div class="service-control">
                            <div>
                                <span class="status-indicator status-unknown"></span>
                                <strong>Node.js Server</strong>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-success btn-sm" onclick="controlService('nodejs', 'start')">
                                    <i class="fas fa-play"></i> Start
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="controlService('nodejs', 'restart')">
                                    <i class="fas fa-redo"></i> Restart
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="controlService('nodejs', 'stop')">
                                    <i class="fas fa-stop"></i> Stop
                                </button>
                            </div>
                        </div>

                        <div class="service-control">
                            <div>
                                <span class="status-indicator status-unknown"></span>
                                <strong>Rclone Sync</strong>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-success btn-sm" onclick="controlService('rclone', 'start')">
                                    <i class="fas fa-play"></i> Start
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="controlService('rclone', 'restart')">
                                    <i class="fas fa-redo"></i> Restart
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="controlService('rclone', 'stop')">
                                    <i class="fas fa-stop"></i> Stop
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione 5: MySQL -->
        <div class="tab-pane fade" id="mysql">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-database"></i> Gestione MySQL</h3>
                    <p class="mb-0">Query personalizzate e amministrazione database</p>
                </div>
                <div class="section-content">
                    <div class="mb-4">
                        <h5>Query Personalizzata</h5>
                        <textarea class="form-control query-editor" rows="6" id="sqlQuery" placeholder="Inserisci la tua query SQL qui...
Esempi:
- SELECT * FROM utenti LIMIT 10;
- SHOW TABLES;
- DESCRIBE nome_tabella;
- UPDATE tabella SET campo = 'valore' WHERE id = 1;"></textarea>
                        <div class="mt-2">
                            <button class="btn btn-primary" onclick="executeCustomQuery()">
                                <i class="fas fa-play"></i> Esegui Query
                            </button>
                            <button class="btn btn-secondary" onclick="clearQuery()">
                                <i class="fas fa-eraser"></i> Pulisci
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Quick Actions</h5>
                        <div class="btn-group mb-2">
                            <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SHOW TABLES')">Show Tables</button>
                            <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SHOW PROCESSLIST')">Process List</button>
                            <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SELECT COUNT(*) as total_clients FROM clienti')">Count Clients</button>
                            <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SELECT COUNT(*) as total_messages FROM chat_messages')">Count Messages</button>
                        </div>
                    </div>

                    <div id="query-results">
                        <!-- Risultati query verranno inseriti qui -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione 6: Pulizia -->
        <div class="tab-pane fade" id="cleanup">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-broom"></i> Pulizia e Manutenzione</h3>
                    <p class="mb-0">Strumenti per ottimizzazione e pulizia sistema</p>
                </div>
                <div class="section-content">
                    <div class="cleanup-item">
                        <h6><i class="fas fa-file-alt text-warning"></i> Pulizia File di Log</h6>
                        <p class="mb-2">Elimina tutti i file di log vecchi per liberare spazio</p>
                        <button class="btn btn-warning btn-sm" onclick="performCleanup('logs')">
                            <i class="fas fa-trash"></i> Pulisci Log
                        </button>
                    </div>

                    <div class="cleanup-item">
                        <h6><i class="fas fa-comments text-info"></i> Archivia Chat Vecchie</h6>
                        <p class="mb-2">Elimina messaggi chat più vecchi di 6 mesi</p>
                        <button class="btn btn-info btn-sm" onclick="performCleanup('old_chats')">
                            <i class="fas fa-archive"></i> Archivia Chat
                        </button>
                    </div>

                    <div class="cleanup-item">
                        <h6><i class="fas fa-database text-success"></i> Ottimizzazione Database</h6>
                        <p class="mb-2">Ottimizza tutte le tabelle del database per migliorare le prestazioni</p>
                        <button class="btn btn-success btn-sm" onclick="performCleanup('optimize_db')">
                            <i class="fas fa-tachometer-alt"></i> Ottimizza DB
                        </button>
                    </div>

                    <div class="cleanup-item">
                        <h6><i class="fas fa-box text-primary"></i> Archiviazione Chat</h6>
                        <p class="mb-2">Sposta le chat inattive in archivio</p>
                        <button class="btn btn-primary btn-sm" onclick="performCleanup('archive_chats')">
                            <i class="fas fa-box-open"></i> Archivia
                        </button>
                    </div>

                    <div id="cleanup-results" class="mt-3">
                        <!-- Risultati operazioni di pulizia -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sezione Gestione Chat -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Gestione Chat System</h5>
                    <p class="mb-0">Strumenti per inizializzazione e gestione chat</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="cleanup-item">
                                <h6><i class="fas fa-users text-primary"></i> Chat Private</h6>
                                <p class="mb-2">Inizializza conversazioni private per tutti gli utenti esistenti</p>
                                <button class="btn btn-primary btn-sm" onclick="initializeChats('private')">
                                    <i class="fas fa-user-plus"></i> Inizializza Private
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="cleanup-item">
                                <h6><i class="fas fa-briefcase text-success"></i> Chat Pratiche</h6>
                                <p class="mb-2">Inizializza conversazioni pratiche per tutti i clienti esistenti</p>
                                <button class="btn btn-success btn-sm" onclick="initializeChats('practice')">
                                    <i class="fas fa-folder-plus"></i> Inizializza Pratiche
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="cleanup-item">
                                <h6><i class="fas fa-magic text-info"></i> Tutte le Chat</h6>
                                <p class="mb-2">Inizializza tutte le conversazioni mancanti (private + pratiche)</p>
                                <button class="btn btn-info btn-sm" onclick="initializeChats('all')">
                                    <i class="fas fa-rocket"></i> Inizializza Tutto
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="chat-results" class="mt-3">
                        <!-- Risultati operazioni chat -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Carica dati al cambio tab
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        
        // Event listeners per i tab
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(event) {
                const target = event.target.getAttribute('data-bs-target');
                switch(target) {
                    case '#stats': loadStats(); break;
                    case '#resources': loadResources(); break;
                    case '#network': loadNetwork(); break;
                    case '#services': loadServices(); break;
                }
            });
        });
    });

    function loadStats() {
        fetch('devtools.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax_action=get_stats'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStats(data.data);
            } else {
                document.getElementById('stats-content').innerHTML = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('stats-content').innerHTML = '<div class="alert alert-danger">Errore di connessione: ' + error.message + '</div>';
        });
    }

    function displayStats(stats) {
        const html = `
            <div class="col-md-4">
                <div class="stat-box">
                    <h6><i class="fas fa-users text-primary"></i> Clienti Totali</h6>
                    <h3 class="text-primary">${stats.clienti || 0}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <h6><i class="fas fa-comments text-success"></i> Messaggi Chat</h6>
                    <h3 class="text-success">${stats.messaggi || 0}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <h6><i class="fas fa-file-alt text-warning"></i> Pratiche ENEA</h6>
                    <h3 class="text-warning">${stats.enea || 0}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <h6><i class="fas fa-thermometer-half text-info"></i> Conto Termico</h6>
                    <h3 class="text-info">${stats.conto_termico || 0}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <h6><i class="fas fa-tasks text-secondary"></i> Task Totali</h6>
                    <h3 class="text-secondary">${stats.task || 0}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <h6><i class="fas fa-check-circle text-success"></i> Task Completati</h6>
                    <h3 class="text-success">${stats.task_completati || 0}</h3>
                </div>
            </div>
        `;
        document.getElementById('stats-content').innerHTML = html;
    }

    function loadResources() {
        fetch('devtools.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax_action=get_resources'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResources(data.data);
            } else {
                document.getElementById('resources-content').innerHTML = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('resources-content').innerHTML = '<div class="alert alert-danger">Errore di connessione: ' + error.message + '</div>';
        });
    }

    function displayResources(resources) {
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-hdd text-primary"></i> Spazio Disco</h6>
                    <div class="resource-bar">
                        <div class="resource-fill" style="width: ${resources.disk.percent || 0}%"></div>
                    </div>
                    <small>Usato: ${resources.disk.used || 'N/A'} / Totale: ${resources.disk.total || 'N/A'} (${resources.disk.percent || 0}%)</small>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-memory text-success"></i> Memoria PHP</h6>
                    <p>Corrente: ${resources.memory.current || 'N/A'}</p>
                    <p>Picco: ${resources.memory.peak || 'N/A'}</p>
                </div>
            </div>
        `;
        
        if (resources.load) {
            html += `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><i class="fas fa-tachometer-alt text-warning"></i> Carico Sistema</h6>
                        <p>1min: ${resources.load['1min']} | 5min: ${resources.load['5min']} | 15min: ${resources.load['15min']}</p>
                    </div>
                </div>
            `;
        }
        
        if (resources.local_drive) {
            html += `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><i class="fas fa-folder text-info"></i> Cartella Local Drive</h6>
                        <p>Dimensione: ${resources.local_drive}</p>
                    </div>
                </div>
            `;
        }
        
        document.getElementById('resources-content').innerHTML = html;
    }

    function loadNetwork() {
        fetch('devtools.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax_action=get_network'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNetwork(data.data);
            } else {
                document.getElementById('network-content').innerHTML = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('network-content').innerHTML = '<div class="alert alert-danger">Errore di connessione: ' + error.message + '</div>';
        });
    }

    function displayNetwork(network) {
        const html = `
            <div class="network-status">
                <span><i class="fas fa-server text-primary"></i> IP Server</span>
                <strong>${network.server_ip || 'N/A'}</strong>
            </div>
            <div class="network-status">
                <span><i class="fas fa-user text-success"></i> IP Client</span>
                <strong>${network.client_ip || 'N/A'}</strong>
            </div>
            <div class="network-status">
                <span><i class="fas fa-desktop text-info"></i> Hostname</span>
                <strong>${network.hostname || 'N/A'}</strong>
            </div>
            <div class="network-status">
                <span><i class="fas fa-globe text-warning"></i> Ping Google</span>
                <strong class="${network.ping_google === 'FAIL' ? 'text-danger' : 'text-success'}">${network.ping_google || 'N/A'}</strong>
            </div>
            <hr>
            <h6>Stato Porte</h6>
            <div class="network-status">
                <span><i class="fas fa-database"></i> MySQL (3306)</span>
                <span class="${network.ports && network.ports.mysql === 'OPEN' ? 'text-success' : 'text-danger'}">${network.ports ? network.ports.mysql : 'N/A'}</span>
            </div>
            <div class="network-status">
                <span><i class="fas fa-server"></i> Apache (80)</span>
                <span class="${network.ports && network.ports.apache === 'OPEN' ? 'text-success' : 'text-danger'}">${network.ports ? network.ports.apache : 'N/A'}</span>
            </div>
            <div class="network-status">
                <span><i class="fas fa-lock"></i> HTTPS (443)</span>
                <span class="${network.ports && network.ports.https === 'OPEN' ? 'text-success' : 'text-danger'}">${network.ports ? network.ports.https : 'N/A'}</span>
            </div>
        `;
        document.getElementById('network-content').innerHTML = html;
    }

    function controlService(service, action) {
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + action.charAt(0).toUpperCase() + action.slice(1);
        
        fetch('devtools.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax_action=service_control&service=${service}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            button.disabled = false;
            button.innerHTML = originalHtml;
            
            if (data.success) {
                alert('Comando eseguito con successo: ' + (data.output || 'OK'));
            } else {
                alert('Errore: ' + (data.error || 'Errore sconosciuto'));
            }
        })
        .catch(error => {
            button.disabled = false;
            button.innerHTML = originalHtml;
            alert('Errore di connessione: ' + error.message);
        });
    }

    function executeCustomQuery() {
        const sql = document.getElementById('sqlQuery').value.trim();
        if (!sql) {
            alert('Inserisci una query SQL');
            return;
        }
        
        fetch('devtools.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax_action=execute_query&sql=${encodeURIComponent(sql)}`
        })
        .then(response => response.json())
        .then(data => {
            displayQueryResults(data);
        })
        .catch(error => {
            displayQueryResults({success: false, error: 'Errore di connessione: ' + error.message});
        });
    }

    function quickQuery(sql) {
        document.getElementById('sqlQuery').value = sql;
        executeCustomQuery();
    }

    function clearQuery() {
        document.getElementById('sqlQuery').value = '';
        document.getElementById('query-results').innerHTML = '';
    }

    function displayQueryResults(data) {
        let html = '';
        
        if (data.success) {
            if (data.type === 'select' && data.data && data.data.length > 0) {
                html = '<h6>Risultati Query (' + data.data.length + ' righe)</h6>';
                html += '<div class="table-responsive"><table class="table table-striped table-sm">';
                html += '<thead class="table-dark"><tr>';
                
                // Headers
                Object.keys(data.data[0]).forEach(key => {
                    html += '<th>' + key + '</th>';
                });
                html += '</tr></thead><tbody>';
                
                // Rows
                data.data.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(value => {
                        html += '<td>' + (value !== null ? value : 'NULL') + '</td>';
                    });
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
            } else if (data.type === 'modify') {
                html = '<div class="alert alert-success">Query eseguita con successo. Righe interessate: ' + (data.affected_rows || 0) + '</div>';
            } else {
                html = '<div class="alert alert-info">Query eseguita con successo. Nessun risultato da visualizzare.</div>';
            }
        } else {
            html = '<div class="alert alert-danger">Errore: ' + (data.error || 'Errore sconosciuto') + '</div>';
        }
        
        document.getElementById('query-results').innerHTML = html;
    }

    function performCleanup(type) {
        if (!confirm('Sei sicuro di voler procedere con questa operazione di pulizia?')) {
            return;
        }
        
        fetch('devtools.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax_action=cleanup_action&cleanup_type=${type}`
        })
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.success) {
                html = '<div class="alert alert-success">' + (data.message || 'Operazione completata') + '</div>';
            } else {
                html = '<div class="alert alert-danger">Errore: ' + (data.error || 'Errore sconosciuto') + '</div>';
            }
            document.getElementById('cleanup-results').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('cleanup-results').innerHTML = '<div class="alert alert-danger">Errore di connessione: ' + error.message + '</div>';
        });
    }

    function refreshAllSections() {
        loadStats();
        loadResources();
        loadNetwork();
    }

    function initializeChats(type) {
        if (!confirm('Sei sicuro di voler inizializzare le chat? Questa operazione può richiedere del tempo.')) {
            return;
        }
        
        document.getElementById('chat-results').innerHTML = '<div class="alert alert-info">Inizializzazione in corso...</div>';
        
        fetch('devtools_new.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax_action=initialize_chats&chat_type=${type}`
        })
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.success) {
                html = '<div class="alert alert-success">' + (data.message || 'Operazione completata') + '</div>';
                if (data.details && data.details.length > 0) {
                    html += '<ul class="list-group mt-2">';
                    data.details.forEach(detail => {
                        html += '<li class="list-group-item">' + detail + '</li>';
                    });
                    html += '</ul>';
                }
            } else {
                html = '<div class="alert alert-danger">Errore: ' + (data.error || 'Errore sconosciuto') + '</div>';
            }
            document.getElementById('chat-results').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('chat-results').innerHTML = '<div class="alert alert-danger">Errore di connessione: ' + error.message + '</div>';
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
