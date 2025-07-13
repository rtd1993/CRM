<?php
// Impedisce cache per dati in tempo reale
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'includes/config.php';

// Funzione per verificare stato servizio
function getServiceStatus($service) {
    $output = shell_exec("systemctl is-active $service 2>/dev/null");
    return trim($output) === 'active';
}

// Funzione per ottenere info sistema
function getSystemInfo() {
    $info = [];
    
    // Memoria RAM
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
    $info['mem_total'] = round($total[1] / 1024 / 1024, 1);
    $info['mem_used'] = round(($total[1] - $available[1]) / 1024 / 1024, 1);
    $info['mem_percent'] = round(($info['mem_used'] / $info['mem_total']) * 100, 1);
    
    // Spazio disco
    $disk_total = disk_total_space('/');
    $disk_free = disk_free_space('/');
    $disk_used = $disk_total - $disk_free;
    $info['disk_percent'] = round(($disk_used / $disk_total) * 100, 1);
    
    // Temperatura CPU (se disponibile)
    $temp_file = '/sys/class/thermal/thermal_zone0/temp';
    if (file_exists($temp_file)) {
        $temp = file_get_contents($temp_file);
        $info['cpu_temp'] = round($temp / 1000, 1);
    } else {
        $info['cpu_temp'] = 0;
    }
    
    // Uptime
    $uptime = file_get_contents('/proc/uptime');
    $uptime_seconds = (int)$uptime;
    $hours = floor($uptime_seconds / 3600);
    $minutes = floor(($uptime_seconds % 3600) / 60);
    $info['uptime'] = sprintf('%02dh %02dm', $hours, $minutes);
    
    // Stato WiFi
    $wifi_output = shell_exec("iwconfig 2>/dev/null | grep -o 'ESSID:\"[^\"]*\"' | cut -d'\"' -f2");
    $info['wifi_ssid'] = trim($wifi_output) ?: 'N/C';
    
    return $info;
}

// Gestione azioni AJAX
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $service = $_POST['service'] ?? '';
    
    switch ($action) {
        case 'restart':
            if (in_array($service, ['apache2', 'mysql', 'node-socket', 'wg-quick@wg0'])) {
                shell_exec("sudo systemctl restart $service 2>&1");
                echo json_encode(['success' => true]);
            }
            break;
        case 'start':
            if (in_array($service, ['apache2', 'mysql', 'node-socket', 'wg-quick@wg0'])) {
                shell_exec("sudo systemctl start $service 2>&1");
                echo json_encode(['success' => true]);
            }
            break;
        case 'stop':
            if (in_array($service, ['apache2', 'mysql', 'node-socket', 'wg-quick@wg0'])) {
                shell_exec("sudo systemctl stop $service 2>&1");
                echo json_encode(['success' => true]);
            }
            break;
        case 'reboot':
            echo json_encode(['success' => true]);
            shell_exec("sudo reboot");
            break;
        case 'status':
            $services = ['apache2', 'mysql', 'node-socket', 'wg-quick@wg0'];
            $status = [];
            foreach ($services as $svc) {
                $status[$svc] = getServiceStatus($svc);
            }
            $system_info = getSystemInfo();
            echo json_encode(['services' => $status, 'system' => $system_info]);
            break;
    }
    exit;
}

$system_info = getSystemInfo();
$services = [
    'apache2' => ['name' => 'Apache', 'status' => getServiceStatus('apache2')],
    'mysql' => ['name' => 'MySQL', 'status' => getServiceStatus('mysql')],
    'node-socket' => ['name' => 'Socket', 'status' => getServiceStatus('node-socket')],
    'wg-quick@wg0' => ['name' => 'VPN', 'status' => getServiceStatus('wg-quick@wg0')]
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=480, height=320, initial-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <title>CRM Touch Monitor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 320px;
            width: 480px;
            overflow: hidden;
            user-select: none;
            cursor: default;
            position: fixed;
            top: 0;
            left: 0;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 40px 1fr 60px;
            height: 100vh;
            gap: 5px;
            padding: 5px;
        }

        .header {
            grid-column: 1 / -1;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-size: 16px;
            font-weight: 600;
        }

        .time {
            font-size: 12px;
            opacity: 0.9;
        }

        .left-panel, .right-panel {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .wifi-status {
            text-align: center;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .wifi-name {
            font-size: 12px;
            font-weight: 600;
        }

        .system-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 11px;
        }

        .metric {
            text-align: center;
            padding: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .metric-value {
            font-size: 14px;
            font-weight: 600;
            color: #2ed573;
        }

        .metric-value.warning {
            color: #ffa502;
        }

        .metric-value.danger {
            color: #ff4757;
        }

        .services-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .service {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            font-size: 10px;
        }

        .service-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .service-status {
            font-size: 8px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-bottom: 6px;
            display: inline-block;
        }

        .service-status.active {
            background: rgba(46, 213, 115, 0.3);
            color: #2ed573;
        }

        .service-status.inactive {
            background: rgba(255, 71, 87, 0.3);
            color: #ff4757;
        }

        .service-buttons {
            display: flex;
            gap: 2px;
            justify-content: center;
        }

        .btn {
            padding: 4px 6px;
            border: none;
            border-radius: 4px;
            font-size: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            touch-action: manipulation;
            font-weight: 600;
        }

        .btn:active {
            transform: scale(0.95);
        }

        .btn-start {
            background: #2ed573;
            color: white;
        }

        .btn-restart {
            background: #ffa502;
            color: white;
        }

        .btn-stop {
            background: #ff4757;
            color: white;
        }

        .controls {
            grid-column: 1 / -1;
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }

        .control-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            touch-action: manipulation;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .control-btn:active {
            transform: scale(0.95);
        }

        .control-btn.refresh {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .control-btn.reboot {
            background: linear-gradient(135deg, #ff4757, #c44569);
        }

        .notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 1000;
            font-size: 12px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .notification.show {
            opacity: 1;
        }

        .loading {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Status indicator per WiFi */
        .wifi-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ed573;
            display: inline-block;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }

        .wifi-indicator.disconnected {
            background: #ff4757;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🖥️ CRM Monitor</h1>
            <div class="time" id="current-time"></div>
        </div>

        <!-- Pannello Sinistro: Sistema -->
        <div class="left-panel">
            <div class="section-title">📊 Sistema</div>
            
            <div class="wifi-status">
                <div class="wifi-name">
                    <span class="wifi-indicator <?php echo $system_info['wifi_ssid'] === 'N/C' ? 'disconnected' : ''; ?>"></span>
                    <?php echo htmlspecialchars($system_info['wifi_ssid']); ?>
                </div>
            </div>

            <div class="system-metrics">
                <div class="metric">
                    <div>💾 RAM</div>
                    <div class="metric-value <?php echo $system_info['mem_percent'] > 80 ? 'danger' : ($system_info['mem_percent'] > 60 ? 'warning' : ''); ?>">
                        <?php echo $system_info['mem_percent']; ?>%
                    </div>
                </div>
                <div class="metric">
                    <div>💿 Disco</div>
                    <div class="metric-value <?php echo $system_info['disk_percent'] > 80 ? 'danger' : ($system_info['disk_percent'] > 60 ? 'warning' : ''); ?>">
                        <?php echo $system_info['disk_percent']; ?>%
                    </div>
                </div>
                <div class="metric">
                    <div>🌡️ CPU</div>
                    <div class="metric-value <?php echo $system_info['cpu_temp'] > 70 ? 'danger' : ($system_info['cpu_temp'] > 60 ? 'warning' : ''); ?>">
                        <?php echo $system_info['cpu_temp']; ?>°C
                    </div>
                </div>
                <div class="metric">
                    <div>⏱️ Up</div>
                    <div class="metric-value">
                        <?php echo $system_info['uptime']; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pannello Destro: Servizi -->
        <div class="right-panel">
            <div class="section-title">🔧 Servizi</div>
            
            <div class="services-grid">
                <?php foreach ($services as $key => $service): ?>
                <div class="service">
                    <div class="service-name"><?php echo $service['name']; ?></div>
                    <div class="service-status <?php echo $service['status'] ? 'active' : 'inactive'; ?>" id="status-<?php echo $key; ?>">
                        <?php echo $service['status'] ? 'ON' : 'OFF'; ?>
                    </div>
                    <div class="service-buttons">
                        <button class="btn btn-start" onclick="serviceAction('start', '<?php echo $key; ?>')">▶</button>
                        <button class="btn btn-restart" onclick="serviceAction('restart', '<?php echo $key; ?>')">🔄</button>
                        <button class="btn btn-stop" onclick="serviceAction('stop', '<?php echo $key; ?>')">⏹</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Controlli -->
        <div class="controls">
            <button class="control-btn refresh" onclick="refreshStatus()">🔄 Aggiorna</button>
            <button class="control-btn reboot" onclick="rebootSystem()">🔄 Riavvia</button>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 2000);
        }

        function serviceAction(action, service) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="loading"></span>';
            button.disabled = true;

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&service=${service}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`${action.toUpperCase()} OK`);
                    setTimeout(refreshStatus, 1500);
                }
            })
            .catch(() => {
                showNotification('ERRORE');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        function refreshStatus() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=status'
            })
            .then(response => response.json())
            .then(data => {
                // Aggiorna stato servizi
                Object.keys(data.services).forEach(service => {
                    const statusElement = document.getElementById(`status-${service}`);
                    const isActive = data.services[service];
                    statusElement.textContent = isActive ? 'ON' : 'OFF';
                    statusElement.className = `service-status ${isActive ? 'active' : 'inactive'}`;
                });
            })
            .catch(() => {
                showNotification('ERRORE REFRESH');
            });
        }

        function rebootSystem() {
            if (confirm('Riavviare il sistema?')) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=reboot'
                })
                .then(() => {
                    showNotification('RIAVVIO...');
                });
            }
        }

        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('it-IT', { 
                hour: '2-digit', 
                minute: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }

        // Inizializza
        updateTime();
        setInterval(updateTime, 1000);
        setInterval(refreshStatus, 15000); // Aggiorna ogni 15 secondi

        // Feedback touch
        document.addEventListener('touchstart', function(e) {
            if (e.target.classList.contains('btn') || e.target.classList.contains('control-btn')) {
                e.target.style.transform = 'scale(0.95)';
            }
        });

        document.addEventListener('touchend', function(e) {
            if (e.target.classList.contains('btn') || e.target.classList.contains('control-btn')) {
                setTimeout(() => {
                    e.target.style.transform = '';
                }, 100);
            }
        });

        // Previeni zoom e scroll su touch
        document.addEventListener('touchmove', function(e) {
            e.preventDefault();
        }, { passive: false });
    </script>
</body>
</html>
