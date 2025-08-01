<?php
// Interfaccia System Monitor per touchscreen 3.5"
header('Content-Type: text/html; charset=utf-8');

// Gestione azioni servizi
$action_result = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $commands = [
        'apache_restart' => 'sudo systemctl restart apache2',
        'mysql_restart' => 'sudo systemctl restart mysql',
        'node_restart' => 'sudo systemctl restart node-socket',
        'wireguard_restart' => 'sudo systemctl restart wg-quick@wg0',
        'apache_start' => 'sudo systemctl start apache2',
        'mysql_start' => 'sudo systemctl start mysql',
        'node_start' => 'sudo systemctl start node-socket',
        'wireguard_start' => 'sudo systemctl start wg-quick@wg0',
        'apache_stop' => 'sudo systemctl stop apache2',
        'mysql_stop' => 'sudo systemctl stop mysql',
        'node_stop' => 'sudo systemctl stop node-socket',
        'wireguard_stop' => 'sudo systemctl stop wg-quick@wg0',
        'reboot_system' => 'sudo reboot',
        'wifi_scan' => 'sudo iwlist wlan0 scan | grep ESSID'
    ];
    
    if (isset($commands[$action])) {
        $output = shell_exec($commands[$action] . ' 2>&1');
        $action_result = $action . ": " . ($output ?: "OK");
    }
}

// Funzione per ottenere lo stato dei servizi
function getServiceStatus($service) {
    $status = trim(shell_exec("systemctl is-active $service 2>/dev/null"));
    return $status === 'active';
}

// Funzione per ottenere info WiFi
function getWifiInfo() {
    $ssid = trim(shell_exec("iwgetid -r 2>/dev/null"));
    $ip = trim(shell_exec("hostname -I | awk '{print $1}'"));
    $signal = trim(shell_exec("cat /proc/net/wireless | tail -n 1 | awk '{print $3}' | sed 's/\.//'"));
    
    return [
        'connected' => !empty($ssid),
        'ssid' => $ssid ?: 'Non connesso',
        'ip' => $ip ?: 'N/A',
        'signal' => $signal ? intval($signal) : 0
    ];
}

// Funzione per ottenere info sistema
function getSystemInfo() {
    $temp = trim(shell_exec("vcgencmd measure_temp 2>/dev/null | cut -d= -f2"));
    $uptime = trim(shell_exec("uptime -p"));
    $memory = shell_exec("free -m | grep Mem | awk '{printf \"%.1f\", $3/$2 * 100.0}'");
    $disk = shell_exec("df / | tail -1 | awk '{print $5}' | sed 's/%//'");
    
    return [
        'temp' => $temp ?: 'N/A',
        'uptime' => $uptime ?: 'N/A',
        'memory' => floatval($memory ?: 0),
        'disk' => intval($disk ?: 0)
    ];
}

$wifi = getWifiInfo();
$system = getSystemInfo();
$services = [
    'apache2' => getServiceStatus('apache2'),
    'mysql' => getServiceStatus('mysql'),
    'node-socket' => getServiceStatus('node-socket'),
    'wg-quick@wg0' => getServiceStatus('wg-quick@wg0')
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=480, height=320, initial-scale=1.0, user-scalable=no">
    <title>CRM System Monitor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 320px;
            width: 480px;
            overflow: hidden;
            user-select: none;
            cursor: default;
        }
        
        .container {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 8px;
        }
        
        .header {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: 600;
        }
        
        .clock {
            font-size: 14px;
            font-weight: 500;
        }
        
        .main-content {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            height: calc(100% - 66px);
        }
        
        .panel {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 8px;
            backdrop-filter: blur(10px);
        }
        
        .panel h3 {
            font-size: 12px;
            margin-bottom: 8px;
            text-align: center;
            opacity: 0.9;
        }
        
        .wifi-info {
            text-align: center;
            margin-bottom: 8px;
        }
        
        .wifi-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-bottom: 4px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-online { background: #4CAF50; }
        .status-offline { background: #f44336; }
        
        .wifi-details {
            font-size: 10px;
            line-height: 1.3;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 8px;
        }
        
        .service-item {
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 6px;
            text-align: center;
            font-size: 10px;
        }
        
        .service-active {
            background: rgba(76, 175, 80, 0.3);
            border: 1px solid #4CAF50;
        }
        
        .service-inactive {
            background: rgba(244, 67, 54, 0.3);
            border: 1px solid #f44336;
        }
        
        .controls-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
        }
        
        .btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            color: white;
            padding: 8px 4px;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            min-height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        
        .btn:hover, .btn:active {
            background: rgba(255,255,255,0.3);
            transform: scale(0.98);
        }
        
        .btn-restart { border-color: #ff9800; }
        .btn-start { border-color: #4CAF50; }
        .btn-stop { border-color: #f44336; }
        .btn-system { border-color: #2196F3; }
        
        .system-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            font-size: 10px;
        }
        
        .stat-item {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            padding: 6px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .stat-label {
            opacity: 0.8;
            font-size: 9px;
        }
        
        .action-result {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 10px;
            z-index: 1000;
            max-width: 90%;
            word-break: break-word;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 2px;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-memory { background: #2196F3; }
        .progress-disk { background: #ff9800; }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }
        
        .blinking {
            animation: blink 1s infinite;
        }
        
        /* Miglioramenti touch */
        .btn {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }
        
        /* Responsive per orientamento landscape */
        @media (orientation: landscape) {
            .main-content {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-server"></i> CRM Monitor</h1>
            <div class="clock" id="clock"></div>
        </div>
        
        <div class="main-content">
            <!-- Panel WiFi e Sistema -->
            <div class="panel">
                <h3><i class="fas fa-wifi"></i> Connessione</h3>
                <div class="wifi-info">
                    <div class="wifi-status">
                        <div class="status-dot <?= $wifi['connected'] ? 'status-online' : 'status-offline' ?>"></div>
                        <span><?= $wifi['connected'] ? 'Online' : 'Offline' ?></span>
                    </div>
                    <div class="wifi-details">
                        <div><strong><?= htmlspecialchars($wifi['ssid']) ?></strong></div>
                        <div>IP: <?= htmlspecialchars($wifi['ip']) ?></div>
                        <?php if ($wifi['signal'] > 0): ?>
                            <div>Segnale: <?= $wifi['signal'] ?>%</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h3 style="margin-top: 12px;"><i class="fas fa-microchip"></i> Sistema</h3>
                <div class="system-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $system['temp'] ?></div>
                        <div class="stat-label">Temperatura</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($system['memory'], 1) ?>%</div>
                        <div class="stat-label">RAM</div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-memory" style="width: <?= $system['memory'] ?>%"></div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $system['disk'] ?>%</div>
                        <div class="stat-label">Disco</div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-disk" style="width: <?= $system['disk'] ?>%"></div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="font-size: 8px;"><?= str_replace(' up ', '', $system['uptime']) ?></div>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Servizi -->
            <div class="panel">
                <h3><i class="fas fa-cogs"></i> Servizi</h3>
                <div class="services-grid">
                    <div class="service-item <?= $services['apache2'] ? 'service-active' : 'service-inactive' ?>">
                        <i class="fas fa-globe"></i><br>Apache
                    </div>
                    <div class="service-item <?= $services['mysql'] ? 'service-active' : 'service-inactive' ?>">
                        <i class="fas fa-database"></i><br>MySQL
                    </div>
                    <div class="service-item <?= $services['node-socket'] ? 'service-active' : 'service-inactive' ?>">
                        <i class="fas fa-plug"></i><br>Node.js
                    </div>
                    <div class="service-item <?= $services['wg-quick@wg0'] ? 'service-active' : 'service-inactive' ?>">
                        <i class="fas fa-shield-alt"></i><br>VPN
                    </div>
                </div>
                
                <div class="controls-grid">
                    <form method="post" style="display: contents;">
                        <button type="submit" name="action" value="apache_restart" class="btn btn-restart">
                            <i class="fas fa-redo"></i> Apache
                        </button>
                    </form>
                    <form method="post" style="display: contents;">
                        <button type="submit" name="action" value="mysql_restart" class="btn btn-restart">
                            <i class="fas fa-redo"></i> MySQL
                        </button>
                    </form>
                    <form method="post" style="display: contents;">
                        <button type="submit" name="action" value="node_restart" class="btn btn-restart">
                            <i class="fas fa-redo"></i> Node
                        </button>
                    </form>
                    <form method="post" style="display: contents;">
                        <button type="submit" name="action" value="wireguard_restart" class="btn btn-restart">
                            <i class="fas fa-redo"></i> VPN
                        </button>
                    </form>
                    <form method="post" style="display: contents;">
                        <button type="submit" name="action" value="reboot_system" class="btn btn-system" onclick="return confirm('Riavviare il sistema?')">
                            <i class="fas fa-power-off"></i> Riavvia
                        </button>
                    </form>
                    <button onclick="location.reload()" class="btn btn-system">
                        <i class="fas fa-sync"></i> Aggiorna
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($action_result): ?>
        <div class="action-result" id="actionResult">
            <?= htmlspecialchars($action_result) ?>
        </div>
    <?php endif; ?>
    
    <script>
        // Aggiorna orologio
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('it-IT', { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('clock').textContent = timeString;
        }
        
        // Aggiorna ogni secondo
        updateClock();
        setInterval(updateClock, 1000);
        
        // Auto-refresh ogni 30 secondi
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Nascondi messaggio azione dopo 5 secondi
        <?php if ($action_result): ?>
            setTimeout(() => {
                const result = document.getElementById('actionResult');
                if (result) {
                    result.style.opacity = '0';
                    setTimeout(() => result.remove(), 500);
                }
            }, 5000);
        <?php endif; ?>
        
        // Previeni zoom su doppio tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Feedback visivo per touch
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            btn.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
        
        // Impedisci selezione testo
        document.addEventListener('selectstart', e => e.preventDefault());
        document.addEventListener('contextmenu', e => e.preventDefault());
    </script>
</body>
</html>
